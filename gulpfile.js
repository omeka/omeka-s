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
    string: ['php-path', 'module'],
    boolean: 'dev',
    default: {'php-path': 'php', 'dev': true, 'module': null}
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
            return runPhpCommand(installerPath, [], {cwd: buildDir});
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

function getModulePath(module) {
    if (!module) {
        return Promise.reject(new Error('No module given! Use --module to specify the module to work on.'));
    }

    var modulePath = path.join(__dirname, 'modules', module);
    return fs.statAsync(modulePath).then(function (stats) {
        if (!stats.isDirectory()) {
            return Promise.reject(new Error('Invalid module given! (not a directory)'))
        }

        return modulePath;
    });
}

function compileToMo(file) {
    var outFile = path.join(path.dirname(file), path.basename(file, '.po') + '.mo');
    return runCommand('msgfmt', [file, '-o', outFile]);
}

gulp.task('css', function () {
    return cssToSass('./application');
});

gulp.task('css:watch', function () {
    gulp.watch('./application/asset/sass/**/*.scss', gulp.parallel('css'));
});

gulp.task('css:module', function () {
    var modulePathPromise = getModulePath(cliOptions.module);
    return modulePathPromise.then(function(modulePath) {
        return cssToSass(modulePath);
    });
});

gulp.task('css:watch:module', function () {
    var modulePathPromise = getModulePath(cliOptions.module);
    modulePathPromise.then(function(modulePath) {
        gulp.watch(modulePath + '/asset/sass/**/*.scss', gulp.parallel('css:module'));
    });
});

gulp.task('test:cs', function () {
    return ensureBuildDir().then(function () {
        return runCommand('vendor/bin/php-cs-fixer', ['fix', '--dry-run', '--verbose', '--diff', '--cache-file=build/cache/.php_cs.cache']);
    });
});
gulp.task('test:php', function () {
    return ensureBuildDir().then(function () {
        return runCommand(composerDir + '/phpunit', [
            '-d',
            'date.timezone=America/New_York',
            '--log-junit',
            buildDir + '/test-results.xml'
        ], {cwd: 'application/test'});
    });
});
gulp.task('test', gulp.series('test:cs', 'test:php'));

gulp.task('deps', function () {
    return composer(['install']);
});
gulp.task('deps:update', function () {
    return composer(['update']);
});
gulp.task('deps:js', function (cb) {
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
})

gulp.task('dedist', function () {
    return gulp.src(['./.htaccess.dist', './config/*.dist', './logs/*.dist', './application/test/config/*.dist'], {base: '.'})
        .pipe(rename(function (path) {
            path.extname = '';
        }))
        .pipe(gulp.dest('.', {overwrite: false}))
});

gulp.task('db:schema', function () {
    return runPhpCommand(scriptsDir + '/create-schema.php');
});
gulp.task('db:proxies', function () {
    return runCommand(composerDir + '/doctrine', ['orm:generate-proxies']);
});
gulp.task('db:create-migration', function () {
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
})
gulp.task('db', gulp.series('db:schema', 'db:proxies'));

gulp.task('i18n:template', function () {
    return Promise.all([
        i18nXgettext('.', ['themes/**', 'modules/**']),
        i18nTaggedStrings('.'),
        i18nVocabStrings()
    ]).then(function (tempFiles) {
        return runCommand('msgcat', tempFiles.concat(['--use-first', '-o', pot]));
    });
});

gulp.task('i18n:compile', function () {
    return glob('application/language/*.po').then(function (files) {
        return Promise.all(files.map(compileToMo));
    });
})

gulp.task('i18n:debug', function () {
    var debugPo = path.join(langDir, 'debug.po');
    return runCommand('podebug', ['-i', pot, '-o', debugPo, '--rewrite=unicode']).then(function () {
        return compileToMo(debugPo);
    });
})

gulp.task('i18n:module:template', function () {
    var modulePathPromise = getModulePath(cliOptions.module);
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
});

gulp.task('i18n:module:compile', function () {
    return getModulePath(cliOptions.module).then(function (modulePath) {
        return glob('language/*.po', {cwd: modulePath, absolute: true}).then(function (files) {
            return Promise.all(files.map(compileToMo));
        });
    });
});

gulp.task('create-media-type-map', function () {
    return runPhpCommand(scriptsDir + '/create-media-type-map.php');
});

gulp.task('init', gulp.series('dedist', 'deps'));

gulp.task('clean', function () {
    return rimraf(buildDir).then(function () {
        rimraf(__dirname + '/vendor');
    });
});

gulp.task('zip', gulp.series('clean', 'init', function () {
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
}));
