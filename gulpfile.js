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

var cliOptions = minimist(process.argv.slice(2), {
    string: 'php-path',
    boolean: 'dev',
    default: {'php-path': 'php', 'dev': true}
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
                file.close(resolve());
            });
            https.get(url, function (response) {
                response.pipe(file);
            }).on('error', function(err) {
                reject(err);
            });
        });
    });
}

function runCommand(cmd, args, options) {
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
                    resolve();
                }
            });
    });
}

function runPhpCommand(cmd, args, options) {
    return runCommand(cliOptions['php-path'], [cmd].concat(args), options);
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

gulp.task('css', function () {
    return gulp.src('./application/asset/sass/*.scss')
        .pipe(sass({
            outputStyle: 'compressed',
            includePaths: ['node_modules/susy/sass']
        }).on('error', sass.logError))
        .pipe(postcss([
            autoprefixer({browsers: ['> 5%', '> 1% in US']})
        ]))
        .pipe(gulp.dest('./application/asset/css'));
});

gulp.task('css:watch', function () {
    gulp.watch('./application/asset/sass/*.scss', gulp.parallel('css'));
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
        'chosen-js': '**',
        'ckeditor': ['**', '!samples/**'],
        'jquery': 'dist/jquery.min.js',
        'jstree': 'dist/jstree.min.js',
        'openseadragon': 'build/openseadragon/**',
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
    var pot = langDir + '/template.pot';

    var xgettext = glob('**/*.{php,phtml}', {ignore: ['themes/**', 'modules/**']}).then(function (files) {
        return tmpFile({postfix: 'xgettext.pot'}).spread(function (path, fd) {
            var args = ['--language=php', '--from-code=utf-8', '--keyword=translate', '-o', path];
            return runCommand('xgettext', args.concat(files)).then(function () {
                return path;
            });
        });
    });
    var taggedStrings = tmpFile({postfix: 'tagged.pot'}).spread(function (path, fd) {
        return runPhpCommand(composerDir + '/extract-tagged-strings.php', [], {stdio: ['pipe', fd, 'pipe']})
        .then(function () {
            return path;
        });
    });
    var vocabStrings = tmpFile({postfix: 'vocab.pot'}).spread(function (path, fd) {
        return runPhpCommand(scriptsDir + '/extract-vocab-strings.php', [], {stdio: ['pipe', fd, 'pipe']})
        .then(function () {
            return path;
        });
    });

    return Promise.all([xgettext, taggedStrings, vocabStrings]).then(function (tempFiles) {
        return runCommand('msgcat', tempFiles.concat(['-o', pot]));
    });
});

gulp.task('i18n:compile', function () {
    function compileToMo(file) {
        var outFile = path.join(path.dirname(file), path.basename(file, '.po') + '.mo');
        return runCommand('msgfmt', [file, '-o', outFile]);
    }
    return glob('application/language/*.po').then(function (files) {
        return Promise.all(files.map(compileToMo));
    });
})

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
    return gulp.src(['./**', '!./**/*.dist', '!./build/**', '!./**/node_modules/**', '!./**/.git/**', '!./**/.gitattributes', '!./**/.gitignore'],
        {base: '.', nodir: true, dot: true})
        .pipe(rename(function (path) {
            path.dirname = 'omeka-s/' + path.dirname;
        }))
        .pipe(zip('omeka-s.zip'))
        .pipe(gulp.dest(buildDir))
}));
