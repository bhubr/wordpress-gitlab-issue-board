BoardController.$inject = ['$rootScope', '$http', '$timeout', 'lodash', 'dataService'];

function BoardController($rootScope, $http, $timeout, _, dataService) {
  var $ctrl = this;
  $ctrl.message = 'The issue board will appear here!';

  $ctrl.syncProjects = function() {
    dataService.syncProjects()
    .then(function(projects) {
      $ctrl.projects = projects;
    });
  }
}

module.exports = {
  templateUrl: window.templatesRoot + '/board.html',
  controller: BoardController,
  bindings: {
    projects: '='
  }
};