# gulp-require-tasks
Require gulp-tasks exported by other modules in your gulp.tasks.

## Install
Install it via npm: ```npm install gulp-require-tasks```

## Usage
Its fairly easy. Require the module. It exports a function which takes a string
for the module to import and an object for the instance of gulp.
```javascript
// gulpfile.js
var gulp = require('gulp')
var gulpRequireTasks = require('gulp-require-tasks')
gulpRequireTasks('./yourGulpTasksModule', gulp)
```

The imported module should export gulp.tasks, as shown below.
```javascript
// yourGulpTaskModule.js
var Gulp = require('gulp')
Gulp.task('logHello', function () {
  console.log('Hello')
})
module.exports = Gulp.tasks
```

## License

ISC
Maintained by Siggi Duenkel
