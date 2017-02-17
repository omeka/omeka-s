'use strict';

var child_process = require('child_process');
var fs = require('fs');
var readline = require('readline');

var Promise = require('bluebird');
var dateFormat = require('dateformat');
var glob = require('glob');
var tmp = require('tmp');

var gulp = require('gulp');
var changed = require('gulp-changed');
var replace = require('gulp-replace');
var rename = require('gulp-rename');

var sass = require('gulp-sass');
var postcss = require('gulp-postcss');
var autoprefixer = require('autoprefixer');

var composerDir = __dirname + '/vendor/bin';
var buildDir = __dirname + '/build';
var dataDir = __dirname + '/application/data';
var scriptsDir = dataDir + '/scripts';
var langDir = __dirname + '/application/language';

function ensureBuildDir() {
    if (!fs.existsSync(buildDir)) {
        fs.mkdirSync(buildDir);
    }
}

function download(url, path) {
    return new Promise(function (resolve, reject) {
        ensureBuildDir();
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

function composer(args) {
    var composerPath = buildDir + '/composer.phar';
    var installerPath = buildDir + '/composer-installer';
    var installerUrl = 'https://getcomposer.org/installer';
    var getComposer = new Promise(function (resolve, reject) {
        fs.stat(composerPath, function(err, stats) {
            if (!err) {
                resolve();
            } else {
                download(installerUrl, installerPath)
                    .then(function () {
                        return runCommand('php', [installerPath], {cwd: buildDir})
                    })
                    .then(function () {
                        resolve();
                    });
            }
        });
    });
    return getComposer
        .then(function () {
            return runCommand('php', [composerPath, 'self-update']);
        })
        .then(function () {
            return runCommand('php', [composerPath].concat(args));
        });
}

function testCs() {
    return runCommand('vendor/bin/php-cs-fixer', ['fix', '--dry-run', '--verbose', '--diff']);
}

function testPhp() {
    return runCommand(composerDir + '/phpunit', [
        '-d',
        'date.timezone=America/New_York',
        '--log-junit',
        buildDir + '/test-results.xml'
    ], {cwd: 'application/test'});
}

gulp.task('css', function () {
    return gulp.src('./application/asset/sass/*.scss')
        .pipe(sass({
            outputStyle: 'compressed',
            includePaths: ['node_modules/susy/sass']
        }).on('error', sass.logError))
        .pipe(postcss([
            autoprefixer({browsers: ['> 5%', '> 5% in US', 'last 2 versions']})
        ]))
        .pipe(gulp.dest('./application/asset/css'));
});

gulp.task('css:watch', function () {
    gulp.watch('./application/asset/sass/*.scss', ['css']);
});


gulp.task('test:cs', testCs);
gulp.task('test:php', testPhp);
gulp.task('test', function () {
    return testPhp().then(testCs);
});

gulp.task('deps', function () {
    return composer(['install']);
});
gulp.task('deps:update', function () {
    return composer(['update']);
});

gulp.task('dedist', function () {
    return gulp.src(['./.htaccess.dist', './config/*.dist', './logs/*.dist', './application/test/config/*.dist'], {base: '.'})
        .pipe(rename(function (path) {
            path.extname = '';
        }))
        // workaround to prevent overwrites (possibly removable under Gulp 4)
        .pipe(changed('.', {hasChanged: function (stream, cb, src, dest) {
            fs.stat(dest, function (err) {
                if (err) {
                    stream.push(src);
                }
                cb();
            });
        }}))
        .pipe(gulp.dest('.'))
});

gulp.task('db:schema', function () {
    return runCommand('php', [scriptsDir + '/create-schema.php']);
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
gulp.task('db', ['db:schema', 'db:proxies']);

gulp.task('i18n:template', function () {
    var pot = langDir + '/template.pot';
    return Promise.all([
        new Promise(function(resolve, reject) {
            glob('**/*.{php,phtml}', {ignore: ['themes/**', 'modules/**']}, function (err, files) {
                if (err) {
                    reject(err);
                    return;
                }
                tmp.file({postfix: 'xgettext.pot'}, function (err, path, fd) {
                    if (err) {
                        reject(err);
                        return;
                    }
                    var args = ['--language=php', '--from-code=utf-8', '--keyword=translate', '-o', path];
                    runCommand('xgettext', args.concat(files)).then(function () {
                        resolve(path);
                    });
                });
            });
        }),
        new Promise(function (resolve, reject) {
            tmp.file({postfix: 'tagged.pot'}, function (err, path, fd) {
                if (err) {
                    reject(err);
                    return;
                }
                var args = ['--language=php', '--from-code=utf-8', '--keyword=translate', '-o', path];
                runCommand(composerDir + '/extract-tagged-strings.php', [], {stdio: ['pipe', fd, 'pipe']}).then(function () {
                    resolve(path);
                });
            });
        }),
        new Promise(function (resolve, reject) {
            tmp.file({postfix: 'vocab.pot'}, function (err, path, fd) {
                if (err) {
                    reject(err);
                    return;
                }
                var args = ['--language=php', '--from-code=utf-8', '--keyword=translate', '-o', path];
                runCommand('php', [scriptsDir + '/extract-vocab-strings.php'], {stdio: ['pipe', fd, 'pipe']}).then(function () {
                    resolve(path);
                });
            });
        })
    ])
    .then(function (tempFiles) {
        return runCommand('msgcat', tempFiles.concat(['-o', pot]));
    });
});

gulp.task('init', ['dedist', 'deps']);
