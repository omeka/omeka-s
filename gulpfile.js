'use strict';

const child_process = require('child_process');
const readline = require('readline');
const path = require('path');

const dateFormat = require('dateformat');
const minimist = require('minimist');

const gulp = require('gulp');
const replace = require('gulp-replace');
const rename = require('gulp-rename');
const zip = require('gulp-zip');

const fs = require('fs/promises');
const {createWriteStream} = require('fs');
const {glob} = require('glob');
const tmp = require('tmp');
tmp.setGracefulCleanup();

const sass = require('gulp-sass')(require('sass'));
const postcss = require('gulp-postcss');
const autoprefixer = require('autoprefixer');

const composerDir = __dirname + '/vendor/bin';
const buildDir = __dirname + '/build';
const dataDir = __dirname + '/application/data';
const scriptsDir = dataDir + '/scripts';
const langDir = __dirname + '/application/language';
const pot = langDir + '/template.pot';

const cliOptions = minimist(process.argv.slice(2), {
    string: ['php-path', 'module-name', 'port', 'host', 'db-path'],
    boolean: 'dev',
    alias: {'module-name': 'module'},
    default: {'php-path': 'php', 'dev': true, 'module-name': null}
});

function tmpFile(options) {
    return new Promise(function(resolve, reject) {
        tmp.file(options, function (err, path, fd) {
            if (err) {
                reject(err);
            } else {
                resolve({path, fd});
            }
        });
    });
}

function ensureBuildDir() {
    return fs.mkdir(buildDir + '/cache', {recursive: true});
}

function download(url, path) {
    return ensureBuildDir().then(function () {
        return new Promise(function (resolve, reject) {
            const https = require('https');
            const file = createWriteStream(path);
            file.on('finish', function () {
                resolve();
            });
            https.get(url, function (response) {
                response.pipe(file);
            }).on('error', function(err) {
                reject(err);
            });
        });
    });
}

function runCommand(cmd, args, options, resolveWith) {
    return new Promise(function (resolve, reject) {
        if (!options) {
            options = {};
        }
        if (!options.stdio) {
            options.stdio = 'inherit';
        }
        child_process.spawn(cmd, args, options)
            .on('exit', function (code) {
                if (code !== 0) {
                    reject(new Error('Command "' + cmd + '" exited with code ' +  code));
                } else {
                    resolve(resolveWith);
                }
            });
    });
}

function runPhpCommand(cmd, args, options, resolveWith) {
    return runCommand(cliOptions['php-path'], [cmd].concat(args), options, resolveWith);
}

async function composer(args, options) {
    const composerPath = buildDir + '/composer.phar';
    const installerPath = buildDir + '/composer-installer';
    const installerUrl = 'https://getcomposer.org/installer';

    try {
        await fs.stat(composerPath);
    } catch (e) {
        await download(installerUrl, installerPath);
        await runPhpCommand(installerPath, ['--2'], {cwd: buildDir});
    }
    await runPhpCommand(composerPath, ['self-update', '--2']);

    if (!cliOptions['dev']) {
        args.push('--no-dev');
    }
    return runPhpCommand(composerPath, args, options);
}

async function ensureModuleUsesComposer(modulePath) {
    const composerPath = path.join(modulePath, 'composer.json');
    try {
        await fs.stat(composerPath);
        return modulePath;
    } catch (e) {
        throw new Error('No composer.json found in this module.');
    }
}

function cssToSass(dir) {
    return new Promise(function (resolve, reject) {
        gulp.src(dir + '/asset/sass/**/*.scss')
            .pipe(sass({
                outputStyle: 'compressed',
                includePaths: ['node_modules/susy/sass']
            }).on('error', sass.logError))
            .pipe(postcss([autoprefixer()]))
            .pipe(gulp.dest(dir + '/asset/css'))
            .on('error', reject)
            .on('end', resolve);
    });
}

async function i18nXgettext(dir, ignore) {
    const files = await glob('**/*.{php,phtml}', {ignore: ignore, cwd: dir, nodir: true});
    files.sort();
    const {path} = await tmpFile({postfix: 'xgettext.pot'});
    const args = ['--language=php', '--from-code=utf-8', '--keyword=translate', '-o', path];
    return runCommand('xgettext', args.concat(files), {cwd: dir}, path);
}

async function i18nTaggedStrings(dir) {
    const {path, fd} = await tmpFile({postfix: 'tagged.pot'});
    return runPhpCommand(composerDir + '/extract-tagged-strings.php', [], {stdio: ['pipe', fd, process.stderr], cwd: dir}, path);
}

async function i18nVocabStrings() {
    const {path, fd} = await tmpFile({postfix: 'vocab.pot'});
    return runPhpCommand(scriptsDir + '/extract-vocab-strings.php', [], {stdio: ['pipe', fd, process.stderr]}, path);
}

async function i18nStaticStrings(dir) {
    const staticPath = path.join(dir, 'language', 'template.static.pot');
    try {
        await fs.stat(staticPath);
        return staticPath;
    } catch (e) {
        return null;
    }
}

async function getModulePath() {
    let modulePath;
    const moduleName = cliOptions['module-name'];

    if (moduleName) {
        modulePath = path.join(__dirname, 'modules', moduleName);
    } else {
        modulePath = getCurrentModulePath();
    }

    if (!modulePath) {
        throw new Error('No module given! Run gulp from within the module, or use --module-name to specify the module to work on.');
    }
    const stats = await fs.stat(modulePath);
    if (!stats.isDirectory()) {
        throw new Error('Invalid module given! (not a directory)');
    }
    return modulePath;
}

function getCurrentModulePath() {
    const relativePathSegs = path.relative(process.cwd(), process.env.INIT_CWD).split(path.sep);
    if (relativePathSegs.length < 2 || relativePathSegs[0] !== 'modules') {
        return false;
    }
    return path.resolve(relativePathSegs[0], relativePathSegs[1]);
}

function compileToMo(file) {
    const outFile = path.join(path.dirname(file), path.basename(file, '.po') + '.mo');
    return runCommand('msgfmt', [file, '-o', outFile]);
}

async function phpCsFixer(fix, modulePath) {
    let args = ['fix', '--verbose'];
    if (!fix) {
        args = args.concat(['--dry-run', '--diff']);
    }
    if (modulePath) {
        const [moduleName] = modulePath.split(path.sep).slice(-1);
        args = args.concat([
            '--cache-file=build/cache/.php_cs.cache_' + moduleName,
            '--config=.php_cs_module', modulePath
        ]);
    } else {
        args.push('--cache-file=build/cache/.php_cs.cache');
    }
    await ensureBuildDir();
    return runCommand('vendor/bin/php-cs-fixer', args);
}

function taskCss() {
    return cssToSass('./application');
}
taskCss.description = 'Build css for the core';
gulp.task('css', taskCss);

function taskCssWatch() {
    gulp.watch('./application/asset/sass/**/*.scss', gulp.parallel('css'));
}
taskCssWatch.description = 'Watch for core sass changes and auto-build css';
gulp.task('css:watch', taskCssWatch);

async function taskCssModule() {
    const modulePath = await getModulePath();
    return cssToSass(modulePath);
}
taskCssModule.description = 'Build css for a module';
taskCssModule.flags = {'--module-name': 'Folder name of the module to build for (required)'};
gulp.task('css:module', taskCssModule);

async function taskCssModuleWatch() {
    const modulePath = await getModulePath();
    gulp.watch(modulePath + '/asset/sass/**/*.scss', gulp.parallel('css:module'));
}
taskCssModuleWatch.description = 'Watch for module sass changes and auto-build css';
taskCssModuleWatch.flags = {'--module-name': 'Folder name of the module to watch for (required)'};
gulp.task('css:module:watch', taskCssModuleWatch);

function taskTestCs() {
    return phpCsFixer(false);
}
taskTestCs.description = 'Check code standards';
gulp.task('test:cs', taskTestCs);

async function taskTestModuleCs() {
    const modulePath = await getModulePath();
    return phpCsFixer(false, modulePath);
}
taskTestModuleCs.description = 'Check code standards for a module';
taskTestModuleCs.flags = {'--module-name': 'Folder name of the module to check'};
gulp.task('test:module:cs', taskTestModuleCs);

async function taskTestPhp() {
    await ensureBuildDir();
    return runCommand(composerDir + '/phpunit', [
        '-d',
        'date.timezone=America/New_York',
        '--log-junit',
        buildDir + '/test-results.xml'
    ], {cwd: 'application/test'});
}
taskTestPhp.description = 'Run PHPUnit automated tests';
gulp.task('test:php', taskTestPhp);

const taskTest = gulp.series('test:cs', 'test:php');
taskTest.description = 'Run all tests';
gulp.task('test', taskTest);

function taskFixCs() {
    return phpCsFixer(true);
}
taskFixCs.description = 'Fix code standards';
gulp.task('fix:cs', taskFixCs);

async function taskFixModuleCs() {
    const modulePath = await getModulePath();
    return phpCsFixer(true, modulePath);
}
taskFixModuleCs.description = 'Fix code standards for a module';
taskFixModuleCs.flags = {'--module-name': 'Folder name of the module to fix'};
gulp.task('fix:module:cs', taskFixModuleCs);

function taskDeps() {
    return composer(['install']);
}
taskDeps.description = 'Install Composer dependencies';
gulp.task('deps', taskDeps);

async function taskDepsModule() {
    const modulePath = await getModulePath();
    await ensureModuleUsesComposer(modulePath);
    return composer(['install'], {cwd: modulePath});
}
taskDepsModule.description = 'Install Composer dependencies for a module';
taskDepsModule.flags = {'--module-name': 'Folder name of the module'};
gulp.task('deps:module', taskDepsModule);

function taskDepsUpdate() {
    return composer(['update']);
}
taskDepsUpdate.description = 'Update locked Composer dependencies';
gulp.task('deps:update', taskDepsUpdate);

async function taskDepsModuleUpdate() {
    const modulePath = await getModulePath();
    await ensureModuleUsesComposer(modulePath);
    return composer(['update'], {cwd: modulePath});
}
taskDepsModuleUpdate.description = 'Update locked Composer dependencies for a module';
taskDepsModuleUpdate.flags = {'--module-name': 'Folder name of the module'};
gulp.task('deps:module:update', taskDepsModuleUpdate);

function taskDepsJs(cb) {
    const deps = {
        'chosen-js': ['**', '!*.proto.*'],
        //'ckeditor4': ['**', '!samples/**'],
        'compare-versions': 'lib/umd/index.js',
        'jquery': 'dist/jquery.min.js',
        'jstree': 'dist/jstree.min.js',
        'lightgallery': ['lightgallery.min.js', '[c]ss/lightgallery-bundle.min.css', '[f]onts/**', '[i]mages/**',
            '[p]lugins/@(hash|rotate|thumbnail|video|zoom)/*.min.js'],
        'mirador': ['dist/**', '!dist/*.es.*'],
        'openseadragon': 'build/openseadragon/**',
        'sortablejs': 'Sortable.min.js',
        'tablesaw': 'dist/stackonly/**'
    };
    const depRenames = {
        //'ckeditor4': 'ckeditor'
    };

    Object.keys(deps).forEach(function (module) {
        let moduleDeps = deps[module];
        const dest = depRenames.hasOwnProperty(module) ? depRenames[module] : module;
        if (!(moduleDeps instanceof Array)) {
            moduleDeps = [moduleDeps];
        }
        moduleDeps = moduleDeps.map(function (value) {
            if (value[0] === '!') {
                return '!' + './node_modules/' + module + '/' + value.substr(1);
            }
            return './node_modules/' + module + '/' + value;
        });
        gulp.src(moduleDeps, {encoding: false})
            .pipe(gulp.dest('./application/asset/vendor/' + dest));
    });
    cb();
}
taskDepsJs.description = 'Update in-browser javascript dependencies';
gulp.task('deps:js', taskDepsJs);

function taskDedist() {
    return gulp.src(['./.htaccess.dist', './config/*.dist', './logs/*.dist', './application/test/config/*.dist'], {base: '.'})
        .pipe(rename(function (path) {
            path.extname = '';
        }))
        .pipe(gulp.dest('.', {overwrite: false}))
}
taskDedist.description = 'Copy .dist files to their real runtime paths';
gulp.task('dedist', taskDedist);

function taskDbSchema() {
    return runPhpCommand(scriptsDir + '/create-schema.php');
}
taskDbSchema.description = 'Update database schema installer files';
gulp.task('db:schema', taskDbSchema);

function taskDbProxies() {
    return runCommand(composerDir + '/doctrine', ['orm:generate-proxies']);
}
taskDbProxies.description = 'Update Doctrine proxies';
gulp.task('db:proxies', taskDbProxies);

function taskDbCreateMigration() {
    return new Promise(function(resolve, reject) {
        const now = new Date();
        const timestamp = dateFormat(now, 'UTC:yyyymmddhhMMss');
        const rl = readline.createInterface({input: process.stdin, output: process.stdout});
        rl.question('Migration name (UpperCamelCased): ', function (migrationName) {
            rl.close();
            gulp.src(dataDir + '/build/migration.php.tpl')
                .pipe(replace(/@ClassName@/g, migrationName))
                .pipe(rename(timestamp + '_' + migrationName + '.php'))
                .pipe(gulp.dest(dataDir + '/migrations/'))
                .on('end', resolve);
        });
    });
}
taskDbCreateMigration.description = 'Create new blank DB migration';
gulp.task('db:create-migration', taskDbCreateMigration);

const taskDb = gulp.series('db:schema', 'db:proxies');
taskDb.description = 'Update database files following entity changes';
gulp.task('db', taskDb);

async function taskI18nTemplate() {
    const tempFiles = await Promise.all([
        i18nXgettext('.', ['themes/**', 'modules/**']),
        i18nTaggedStrings('.'),
        i18nVocabStrings()
    ]);
    return runCommand('msgcat', tempFiles.concat(['--use-first', '-o', pot]));
}
taskI18nTemplate.description = 'Update translation template';
gulp.task('i18n:template', taskI18nTemplate);

async function taskI18nCompile() {
    const files = await glob('application/language/*.po');
    return Promise.all(files.map(compileToMo));
}
taskI18nCompile.description = 'Build translation files';
gulp.task('i18n:compile', taskI18nCompile);

async function taskI18nDebug() {
    const debugPo = path.join(langDir, 'debug.po');
    await runCommand('podebug', ['-i', pot, '-o', debugPo, '--rewrite=unicode']);
    return compileToMo(debugPo);
}
taskI18nDebug.description = 'Create debugging dummy translation file (debug.po)';
gulp.task('i18n:debug', taskI18nDebug);

async function taskI18nModuleTemplate() {
    const modulePath = await getModulePath();
    const potentialTempFiles = await Promise.all([
        i18nXgettext(modulePath),
        i18nTaggedStrings(modulePath),
        i18nStaticStrings(modulePath)
    ]);
    const tempFiles = potentialTempFiles.filter(path => path); // remove null paths
    const {path: preDedupePot} = await tmpFile({postfix: 'module-prededupe.pot'});
    await runCommand('msgcat', tempFiles.concat(['--use-first', '-o', preDedupePot]));

    const {path: dupesPot} = await tmpFile({postfix: 'module-dupes.pot'});
    await runCommand('msgcomm', ['-o', dupesPot, preDedupePot, pot]);

    const languageDir = path.join(modulePath, 'language');
    await fs.mkdir(languageDir, {recursive: true});

    const modulePot = path.join(languageDir, 'template.pot');
    return runCommand('msgcomm', ['--unique', '--to-code=utf-8', '-o', modulePot, preDedupePot, dupesPot]);
}
taskI18nModuleTemplate.description = 'Update translation template for a module';
taskI18nModuleTemplate.flags = {'--module-name': 'Name of module (required)'};
gulp.task('i18n:module:template', taskI18nModuleTemplate);

async function taskI18nModuleCompile() {
    const modulePath = getModulePath();
    const files = await glob('language/*.po', {cwd: modulePath, absolute: true});
    return Promise.all(files.map(compileToMo));
}
taskI18nModuleCompile.description = 'Build translation files for a module';
taskI18nModuleCompile.flags = {'--module-name': 'Name of module (required)'};
gulp.task('i18n:module:compile', taskI18nModuleCompile);

function taskCreateMediaTypeMap() {
    return runPhpCommand(scriptsDir + '/create-media-type-map.php');
}
taskCreateMediaTypeMap.description = 'Update media type to file extension mappings';
gulp.task('create-media-type-map', taskCreateMediaTypeMap);

const taskInit = gulp.series('dedist', 'deps');
taskInit.description = 'Run first-time setup for a source checkout';
gulp.task('init', taskInit);

async function taskServe() {
    const port = cliOptions['port'] || 8080;
    const host = cliOptions['host'] || 'localhost';
    console.log('Starting PHP development server on http://' + host + ':' + port);
    return runCommand(cliOptions['php-path'], [
        '-S', host + ':' + port,
        '-t', __dirname
    ], {stdio: 'inherit'});
}
taskServe.description = 'Start PHP development server';
taskServe.flags = {'--port': 'Port number (default: 8080)', '--host': 'Host address (default: localhost)'};
gulp.task('serve', taskServe);

async function taskServeSqlite() {
    const port = cliOptions['port'] || 8080;
    const host = cliOptions['host'] || 'localhost';
    const dbPath = path.resolve(cliOptions['db-path'] || path.join(__dirname, 'db', 'omeka.db'));
    const dbDir = path.dirname(dbPath);
    const configPath = path.join(__dirname, 'config', 'database.ini');

    await fs.mkdir(dbDir, {recursive: true});

    const configContent = 'driver   = "pdo_sqlite"\npath     = "' + dbPath + '"\n';
    await fs.writeFile(configPath, configContent);
    console.log('Configured SQLite database at: ' + dbPath);
    console.log('Starting PHP development server on http://' + host + ':' + port);

    return runCommand(cliOptions['php-path'], [
        '-S', host + ':' + port,
        '-t', __dirname
    ], {stdio: 'inherit'});
}
taskServeSqlite.description = 'Configure SQLite and start PHP development server';
taskServeSqlite.flags = {
    '--port': 'Port number (default: 8080)',
    '--host': 'Host address (default: localhost)',
    '--db-path': 'SQLite database file path (default: ./db/omeka.db)'
};
gulp.task('serve:sqlite', taskServeSqlite);

function taskClean() {
    return Promise.all([
        fs.rm(buildDir, {recursive: true, force: true}),
        fs.rm(__dirname + '/vendor', {recursive: true, force: true})
    ]);
}
taskClean.description = 'Clean build files and installed dependencies';
gulp.task('clean', taskClean);

const taskZip = gulp.series('clean', 'init', function () {
    return gulp.src(
        [
            './**',
            '!./**/*.dist',
            '!./build/**',
            '!./**/node_modules/**',
            '!./package.json',
            '!./package-lock.json',
            '!./**/.tx/**',
            '!./.php-cs-fixer.dist.php',
            '!./.php_cs_module',
            '!./.php_cs.cache',
            '!./.github/**',
            '!./gulpfile.js',
            '!./**/.git/**',
            '!./**/.gitattributes',
            '!./**/.gitignore'
        ],
        {base: '.', dot: true, encoding: false, resolveSymlinks: false})
        .pipe(rename(function (path) {
            path.dirname = 'omeka-s/' + path.dirname;
        }))
        .pipe(zip('omeka-s.zip'))
        .pipe(gulp.dest(buildDir))
});
taskZip.description = 'Create zip archive';
gulp.task('zip', taskZip);
