'use strict'

/**
 * # Avaiable gulp-tasks
 *
 * - lint
 * - test
 * - watch
 */

var gulp = require('gulp')
const JsHint = require('gulp-jshint')
const Mocha = require('gulp-mocha')
const Standard = require('gulp-standard')
var join = require('path').join

// @jsFiles
var jsFiles = [
  join(process.cwd(), '**', '*.js'),
  `!${join(process.cwd(), 'node_modules', '**', '*')}`
]
// @specFiles
var specFiles = join(process.cwd(), 'spec', '**', '*Spec.js')

/**
 * Gulp-task: lint
 * Runs on all @jsFiles
 * Check code-style using StandardJs and JsHint it afterwards.
 */
gulp.task('lint', function () {
  return gulp.src(jsFiles)
    .pipe(Standard())
    .pipe(Standard.reporter('default', { breakOnError: false }))
    .pipe(JsHint())
    .pipe(JsHint.reporter('default'))
})

/**
 * Gulp-task: test
 * Runs mocha using @specFiles
 */
gulp.task('test', function () {
  return gulp.src(specFiles)
    .pipe(Mocha({ui: 'bdd', reporter: 'spec'}))
})

/**
 * Gulp-task: watch
 * Watches for changes on all @jsFiles and runs the lint and test tasks
 */
gulp.task('watch', function () {
  gulp.watch(jsFiles, ['lint', 'test'])
})

/**
 * Gulp-task: default
 * Start the watch-task
 */
gulp.task('default', function () {
  gulp.start('watch')
})
