ToolsController.$inject = ['$rootScope', '$http', '$timeout', 'lodash'];

function ToolsController($rootScope, $http, $timeout, _) {
  var $ctrl = this;

  $ctrl.syncProjects = function() {
    dataService.syncProjects()
    .then(function(projects) {
      console.log('got projects', projects);
      $ctrl.projects = projects;
    });
  };

  $ctrl.syncIssues = function(postId) {
    dataService.syncIssues(postId)
    .then(function(issues) {
      console.log('got issues', issues);
      $ctrl.issues = issues;
    });
  };
}

module.exports = {
  templateUrl: window.templatesRoot + '/tools.html',
  controller: ToolsController,
  bindings: {
  }
};