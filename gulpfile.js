var assetsDir = 'assets/';
var assetsBuild = '.build/assets/';

var config = {

    styles: {
        src: assetsBuild + 'styles/**/*.scss',
        compass: {
            css: assetsDir + 'styles/',
            image: assetsDir + 'img/',
            sass: assetsBuild + 'styles/',
            style: 'expanded'
        }
    },

    scripts: {
        base: assetsDir + 'scripts/',
        build: assetsBuild + 'scripts/',
        packages: ['ui', 'admin', 'admin-bar'],
    },

    reload: [
        '**/*.php',
        '**/*.html'
    ],

    minify: {
        svg: {
            src: [
                '**/*.svg',
                '!**/*.min.svg',
                '!assets/img/icons/mdi/**/*.svg',
                '!assets/img/icons/mdi/*.svg'
            ],
            dest: './'
        },
        scripts: {
            src: [
                assetsDir + 'scripts/*.js',
                '!' + assetsDir + 'scripts/*.min.js'
            ],
            dest: './'
        }
    },

    default: [
        'styles',
        'scripts',
        'minify',
        'watch'
    ]
}

var gulp = require('gulp');
var minify = require('gulp-minify-css');
var $ = require('gulp-load-plugins')();
var webpack = require('webpack');
var gulpWebpack = require('webpack-stream');
var babelloader = require('babel-loader');

var errorLog = function (error) {
    console.log(error);
    if (this.emit) {
        this.emit('end');
    }
};

/**
 * ----------------
 * TASKS ----------
 * ----------------
 */

/**
 * Compass (styles & fonts)
 */

gulp.task('styles', function () {
    gulp.src(config.styles.src)
        .pipe($.compass({
            css: config.styles.compass.css,
            image: config.styles.compass.image,
            sass: config.styles.compass.sass,
            style: config.styles.compass.style,
            require: ['sass-json-vars'],
        }))
        .on('error', errorLog)
        // minify
        .pipe(minify())
        .pipe($.rename({
            suffix: '.min'
        }))
        .on('error', errorLog)
        .pipe(gulp.dest(config.styles.compass.css))
        //reload
        .pipe($.livereload());
});

/**
 * Scripts
 */


var scriptsAll = [];

config.scripts.packages.forEach(function (key) {

    scriptsAll.push('scripts:' + key);

    gulp.task('scripts:' + key, function () {
        gulp.src([config.scripts.build + key + '/**/*.js', '!' + config.scripts.build + key + '/modules/*.js'])
            .pipe(
                gulpWebpack({
                    module: {
                        rules: [
                            {
                                test: /\.js$/,
                                exclude: /node_modules/,
                                loader: "babel-loader"
                            }
                        ]
                    },
                    output: {
                        filename: key + '.js'
                    },
                    externals: {
                        "jquery": "jQuery"
                    }
                }, webpack)
            )
            .on('error', errorLog)
            .pipe(gulp.dest(config.scripts.base))
    });
});

gulp.task('scripts', scriptsAll);

/**
 * Minify
 */

var minifyAll = [];

gulp.task('minify:scripts', function(){

    return gulp.src(config.minify.scripts.src)
        .pipe($.uglify())
        .pipe($.rename({
            suffix: '.min'
        }))
        .on('error', errorLog)
        .pipe(gulp.dest(config.scripts.base))
        .pipe($.livereload());
});
minifyAll.push('minify:scripts');

gulp.task('minify:svg', function () {

    return gulp.src(config.minify.svg.src)
        .pipe($.svgmin())
        .pipe($.rename({
            suffix: '.min'
        }))
        .on('error', errorLog)
        .pipe(gulp.dest(config.minify.svg.dest));
});
minifyAll.push('minify:svg');

gulp.task('minify', minifyAll);

/**
 * Reload
 */

gulp.task('reload', function () {
    return gulp.src(config.reload)
        .pipe($.livereload());
});

/**
 * Watch
 */

gulp.task('watch', function () {

    $.livereload.listen();

    console.log('starting styles..');
    gulp.watch(config.styles.src, ['styles']);

    config.scripts.packages.forEach(function (key) {
        console.log('starting scripts:' + key + '..');
        gulp.watch(config.scripts.build + key + '/**/*.js', ['scripts:' + key]);
    });

    console.log('starting reload..');
    gulp.watch(config.reload).on('change', $.livereload.changed);

    for (var key in config.minify) {
        console.log('starting minify:' + key + '..');
        gulp.watch(config.minify[key].src, ['minify:' + key]);
    }
});

/**
 * Default
 */

gulp.task('default', config.default);
