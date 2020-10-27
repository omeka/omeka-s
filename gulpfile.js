'use strict';

var child_process = require('child_process');
var readline = require('readline');
var path = require('path');

var Promise = require('bluebird');
var dateFormat = require('dateformat');
var minimist = require('minimist');

var gulp = require('gulp');
var replace = require('gulp-replace');
var rename = require('gulp-rename');
var zip = require('gulp-zip');

var fs = require('fs');
Promise.promisifyAll(fs);
var glob = Promise.promisify(require('glob'));
var rimraf = Promise.promisify(require('rimraf'));
var tmpFile = Promise.promisify(require('tmp').file, {multiArgs: true});

var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');

var composerDir = __dirname + '/vendor/bin';
var buildDir = __dirname + '/build';
var dataDir = __dirname + '/application/data';
var scriptsDir = dataDir + '/scripts';
var langDir = __dirname + '/application/language';
var pot = langDir + '/template.pot';

var cliOptions = minimist(process.argv.slice(2), {
    string: ['php-path', 'module-name'],
    boolean: 'dev',
    alias: {'module-name': 'module'},
    default: {'php-path': 'php', 'dev': true, 'module-name': null}
});

function ensureBuildDir() {
    return fs.statAsync(buildDir).catch(function (e) {
        return fs.mkdirAsync(buildDir);
    }).then(function () {
        return fs.statAsync(buildDir + '/cache');
    }).catch(function (e) {
        fs.mkdirAsync(buildDir + '/cache');
    });
}

function download(url, path) {
    return ensureBuildDir().then(function () {
        return new Promise(function (resolve, reject) {
            var https = require('https');
            var file = fs.createWriteStream(path);
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

function composer(args) {
    var composerPath = buildDir + '/composer.phar';
    var installerPath = buildDir + '/composer-installer';
    var installerUrl = 'https://getcomposer.org/installer';
    var stat = Promise.promisify(fs.stat);

    return stat(composerPath).catch(function (e) {
        return download(installerUrl, installerPath).then(function () {
            return runPhpCommand(installerPath, ['--1'], {cwd: buildDir});
        });
    }).then(function () {
        return runPhpCommand(composerPath, ['self-update']);
    }).then(function () {
        if (!cliOptions['dev']) {
            args.push('--no-dev');
        }
        return runPhpCommand(composerPath, args);
    });
}

function cssToSass(dir) {
    return gulp.src(dir + '/asset/sass/**/*.scss')
        .pipe(sass({
            outputStyle: 'compressed',
            includePaths: ['node_modules/susy/sass']
        }).on('error', sass.logError))
        .pipe(postcss([autoprefixer()]))
        .pipe(gulp.dest(dir + '/asset/css'));
}

function i18nXgettext(dir, ignore) {
    return glob('**/*.{php,phtml}', {ignore: ignore, cwd: dir}).then(function (files) {
        return tmpFile({postfix: 'xgettext.pot'}).spread(function (path, fd) {
            var args = ['--language=php', '--from-code=utf-8', '--keyword=translate', '-o', path];
            return runCommand('xgettext', args.concat(files), {cwd: dir}, path);
        });
    });
}

function i18nTaggedStrings(dir) {
    return tmpFile({postfix: 'tagged.pot'}).spread(function (path, fd) {
        return runPhpCommand(composerDir + '/extract-tagged-strings.php', [],
            {stdio: ['pipe', fd, process.stderr], cwd: dir}, path);
    });
}

function i18nVocabStrings() {
    return tmpFile({postfix: 'vocab.pot'}).spread(function (path, fd) {
        return runPhpCommand(scriptsDir + '/extract-vocab-strings.php', [],
            {stdio: ['pipe', fd, process.stderr]}, path);
    });
}

function i18nStaticStrings(dir) {
    var staticPath = path.join(dir, 'language', 'template.static.pot');
    return fs.statAsync(staticPath).then(function () {
        return staticPath;
    }).catch(function (e) {
        return null;
    });
}

function getModulePath() {
    var modulePath;
    var moduleName = cliOptions['module-name'];

    if (moduleName) {
        modulePath = path.join(__dirname, 'modules', moduleName);
    } else {
        modulePath = getCurrentModulePath();
    }

    if (!modulePath) {
        return Promise.reject(new Error('No module given! Run gulp from within the module, or use --module-name to specify the module to work on.'));
    }
    return fs.statAsync(modulePath).then(function (stats) {
        if (!stats.isDirectory()) {
            return Promise.reject(new Error('Invalid module given! (not a directory)'))
        }

        return modulePath;
    });
}

function getCurrentModulePath() {
    var relativePathSegs = path.relative(process.cwd(), process.env.INIT_CWD).split(path.sep);
    if (relativePathSegs.length < 2 || relativePathSegs[0] !== 'modules') {
        return false;
    }
    return path.resolve(relativePathSegs[0], relativePathSegs[1]);
}

function compileToMo(file) {
    var outFile = path.join(path.dirname(file), path.basename(file, '.po') + '.mo');
    return runCommand('msgfmt', [file, '-o', outFile]);
}

function phpCsFixer(fix, modulePath) {
    let args = ['fix', '--verbose'];
    if (!fix) {
        args = args.concat(['--dry-run', '--diff', '--diff-format=udiff']);
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
    return ensureBuildDir().then(function () {
        return runCommand('vendor/bin/php-cs-fixer', args);
    });
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

function taskCssModule() {
    var modulePathPromise = getModulePath();
    return modulePathPromise.then(function(modulePath) {
        return cssToSass(modulePath);
    });
}
taskCssModule.description = 'Build css for a module';
taskCssModule.flags = {'--module-name': 'Folder name of the module to build for (required)'};
gulp.task('css:module', taskCssModule);

function taskCssModuleWatch() {
    var modulePathPromise = getModulePath();
    modulePathPromise.then(function(modulePath) {
        gulp.watch(modulePath + '/asset/sass/**/*.scss', gulp.parallel('css:module'));
    });
}
taskCssModuleWatch.description = 'Watch for module sass changes and auto-build css';
taskCssModuleWatch.flags = {'--module-name': 'Folder name of the module to watch for (required)'};
gulp.task('css:module:watch', taskCssModuleWatch);

function taskTestCs() {
    return phpCsFixer(false);
}
taskTestCs.description = 'Check code standards';
gulp.task('test:cs', taskTestCs);

function taskTestModuleCs() {
    return ensureBuildDir()
        .then(getModulePath)
        .then(function (modulePath) {
            return phpCsFixer(false, modulePath);
        }
    );
}
taskTestModuleCs.description = 'Check code standards for a module';
taskTestModuleCs.flags = {'--module-name': 'Folder name of the module to check'};
gulp.task('test:module:cs', taskTestModuleCs);

function taskTestPhp() {
    return ensureBuildDir().then(function () {
        return runCommand(composerDir + '/phpunit', [
            '-d',
            'date.timezone=America/New_York',
            '--log-junit',
            buildDir + '/test-results.xml'
        ], {cwd: 'application/test'});
    });
}
taskTestPhp.description = 'Run PHPUnit automated tests';
gulp.task('test:php', taskTestPhp);

var taskTest = gulp.series('test:cs', 'test:php');
taskTest.description = 'Run all tests'
gulp.task('test', taskTest);

function taskFixCs() {
    return phpCsFixer(true);
}
taskFixCs.description = 'Fix code standards';
gulp.task('fix:cs', taskFixCs);

function taskFixModuleCs() {
    return ensureBuildDir()
        .then(getModulePath)
        .then(function (modulePath) {
            return phpCsFixer(true, modulePath);
        }
    );
}
taskFixModuleCs.description = 'Fix code standards for a module';
taskFixModuleCs.flags = {'--module-name': 'Folder name of the module to fix'};
gulp.task('fix:module:cs', taskFixModuleCs);

function taskDeps() {
    return composer(['install']);
}
taskDeps.description = 'Install Composer dependencies';
gulp.task('deps', taskDeps);

function taskDepsUpdate() {
    return composer(['update']);
}
taskDepsUpdate.description = 'Update locked Composer dependencies';
gulp.task('deps:update', taskDepsUpdate);

function taskDepsJs(cb) {
    var deps = {
        'chosen-js': ['**', '!*.proto.*'],
        'ckeditor': ['**', '!samples/**'],
        'jquery': 'dist/jquery.min.js',
        'jstree': 'dist/jstree.min.js',
        'openseadragon': 'build/openseadragon/**',
        'semver': 'semver.min.js',
        'sortablejs': 'Sortable.min.js',
        'tablesaw': 'dist/stackonly/**'
    }

    Object.keys(deps).forEach(function (module) {
        var moduleDeps = deps[module];
        if (!(moduleDeps instanceof Array)) {
            moduleDeps = [moduleDeps];
        }
        moduleDeps = moduleDeps.map(function (value) {
            if (value[0] === '!') {
                return '!' + './node_modules/' + module + '/' + value.substr(1);
            }
            return './node_modules/' + module + '/' + value;
        });
        gulp.src(moduleDeps, {nodir: true})
            .pipe(gulp.dest('./application/asset/vendor/' + module));
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
        var now = new Date();
        var timestamp = dateFormat(now, 'UTC:yyyymmddhhMMss');
        var rl = readline.createInterface({input: process.stdin, output: process.stdout});
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

var taskDb = gulp.series('db:schema', 'db:proxies');
taskDb.description = 'Update database files following entity changes';
gulp.task('db', taskDb);

function taskI18nTemplate() {
    return Promise.all([
        i18nXgettext('.', ['themes/**', 'modules/**']),
        i18nTaggedStrings('.'),
        i18nVocabStrings()
    ]).then(function (tempFiles) {
        return runCommand('msgcat', tempFiles.concat(['--use-first', '-o', pot]));
    });
}
taskI18nTemplate.description = 'Update translation template';
gulp.task('i18n:template', taskI18nTemplate);

function taskI18nCompile() {
    return glob('application/language/*.po').then(function (files) {
        return Promise.all(files.map(compileToMo));
    });
}
taskI18nCompile.description = 'Build translation files';
gulp.task('i18n:compile', taskI18nCompile);

function taskI18nDebug() {
    var debugPo = path.join(langDir, 'debug.po');
    return runCommand('podebug', ['-i', pot, '-o', debugPo, '--rewrite=unicode']).then(function () {
        return compileToMo(debugPo);
    });
}
taskI18nDebug.description = 'Create debugging dummy translation file (debug.po)';
gulp.task('i18n:debug', taskI18nDebug);

function taskI18nModuleTemplate() {
    var modulePathPromise = getModulePath();
    var preDedupePromise = modulePathPromise.then(function (modulePath) {
        return Promise.all([
            i18nXgettext(modulePath),
            i18nTaggedStrings(modulePath),
            i18nStaticStrings(modulePath)
        ]);
    }).then(function (tempFiles) {
        return tmpFile({postfix: 'module-prededupe.pot'}).spread(function (path, fd) {
            tempFiles = tempFiles.filter(function (path) {
                // Remove null paths.
                return path;
            });
            return runCommand('msgcat', tempFiles.concat(['--use-first', '-o', path]), {}, path);
        });
    });
    var dupesPromise = preDedupePromise.then(function (preDedupePot) {
        return tmpFile({postfix: 'module-dupes.pot'}).spread(function (path, fd) {
            return runCommand('msgcomm', ['-o', path, preDedupePot, pot], {}, path);
        });
    });
    var languageDirPromise = modulePathPromise.then(function (modulePath) {
        var languageDir = path.join(modulePath, 'language');
        return fs.statAsync(languageDir).then(function (stats) {
            if (!stats.isDirectory()) {
                throw new Error('Language dir path exists, but is not a directory!');
            }
        }, function () {
            return fs.mkdirAsync(languageDir);
        }).then(function () {
            return languageDir;
        });
    })

    return Promise.join(languageDirPromise, preDedupePromise, dupesPromise, function (languageDir, preDedupePot, dupesPot) {
        var modulePot = path.join(languageDir, 'template.pot');
        return runCommand('msgcomm', ['--unique', '--to-code=utf-8', '-o', modulePot, preDedupePot, dupesPot]);
    });
}
taskI18nModuleTemplate.description = 'Update translation template for a module';
taskI18nModuleTemplate.flags = {'--module-name': 'Name of module (required)'}
gulp.task('i18n:module:template', taskI18nModuleTemplate);

function taskI18nModuleCompile() {
    return getModulePath(cliOptions['module-name']).then(function (modulePath) {
        return glob('language/*.po', {cwd: modulePath, absolute: true}).then(function (files) {
            return Promise.all(files.map(compileToMo));
        });
    });
}
taskI18nModuleCompile.description = 'Build translation files for a module';
taskI18nModuleCompile.flags = {'--module-name': 'Name of module (required)'}
gulp.task('i18n:module:compile', taskI18nModuleCompile);

function taskCreateMediaTypeMap() {
    return runPhpCommand(scriptsDir + '/create-media-type-map.php');
}
taskCreateMediaTypeMap.description = 'Update media type to file extension mappings'
gulp.task('create-media-type-map', taskCreateMediaTypeMap);

var taskInit = gulp.series('dedist', 'deps');
taskInit.description = 'Run first-time setup for a source checkout'
gulp.task('init', taskInit);

function taskClean() {
    return rimraf(buildDir).then(function () {
        rimraf(__dirname + '/vendor');
    });
}
taskClean.description = 'Clean build files and installed dependencies'
gulp.task('clean', taskClean);

var taskZip = gulp.series('clean', 'init', function () {
    return gulp.src(
        [
            './**',
            '!./**/*.dist',
            '!./build/**',
            '!./**/node_modules/**',
            '!./package.json',
            '!./package-lock.json',
            '!./**/.tx/**',
            '!./.php_cs',
            '!./.php_cs_module',
            '!./.php_cs.cache',
            '!./.travis.yml',
            '!./gulpfile.js',
            '!./**/.git/**',
            '!./**/.gitattributes',
            '!./**/.gitignore'
        ],
        {base: '.', nodir: true, dot: true})
        .pipe(rename(function (path) {
            path.dirname = 'omeka-s/' + path.dirname;
        }))
        .pipe(zip('omeka-s.zip'))
        .pipe(gulp.dest(buildDir))
});
taskZip.description = 'Create zip archive'
gulp.task('zip', taskZip);
