BoardController.$inject = ['$rootScope', '$http', '$timeout', 'lodash', 'dataService'];

function BoardController($rootScope, $http, $timeout, _, dataService) {
  var $ctrl = this;
  $ctrl.message = 'The issue board will appear here!';

  $ctrl.syncProjects = function() {
    dataService.syncProjects()
    .then(function(projects) {
      console.log('got projects', projects);
      $ctrl.projects = projects;
    });
  }

  $ctrl.syncIssues = function(postId) {
    dataService.syncIssues(postId)
    .then(function(issues) {
      console.log('got issues', issues);
      $ctrl.issues = issues;
    });
  }
}

module.exports = {
  templateUrl: window.templatesRoot + '/board.html',
  controller: BoardController,
  bindings: {
    projects: '=',
    issues: '='
  }
};