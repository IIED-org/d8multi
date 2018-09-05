require('chai').should()
const Path = require('path')
var gulpRequireTasks = require(Path.join(process.cwd(), 'index'))

describe('gulpRequireTasks', function () {
  var gulpInstance
  var fixtureModule

  beforeEach(function () {
    fixtureModule = Path.join(process.cwd(), 'spec', 'fixtures', 'testModule')
    gulpInstance = {
      tasks: {}
    }
    gulpRequireTasks(fixtureModule, gulpInstance)
  })

  it('should add testTask to gulp.tasks', function () {
    gulpInstance.tasks.testTask.should.exist
  })

  it('should add testTask2 to gulp.tasks', function () {
    gulpInstance.tasks.testTask2.should.exist
  })
})
