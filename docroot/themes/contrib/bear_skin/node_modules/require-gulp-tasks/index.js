'use strict'

/**
 * Add tasks exported by module to gulpInstance.tasks
 * @param  {string} module       A node-module which exports gulp.tasks
 * @param  {object} gulpInstance The gulp-instance for adding the tasks of
 *                               module
 */
const gulpRequireTasks = function (module, gulpInstance) {
  let taskList = require(module)
  for (let taskName in taskList) {
    gulpInstance.tasks[taskName] = taskList[taskName]
  }
}

module.exports = gulpRequireTasks
