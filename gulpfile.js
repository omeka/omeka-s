'use strict';

var gulp = require('gulp');
var child_process = require('child_process');
var Promise = require('bluebird');

var composerDir = __dirname + '/vendor/bin';
var buildDir = __dirname + '/build';

function runCommand (cmd, args, options) {
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
                    reject(new Error(`Command "${cmd}" exited with code ${code}.`));
                } else {
                    resolve();
                }
            });
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
    var sass = require('gulp-sass');
    var postcss = require('gulp-postcss');
    var autoprefixer = require('autoprefixer');

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
