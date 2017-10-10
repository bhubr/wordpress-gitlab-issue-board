BoardController.$inject = ['$rootScope', '$http', '$timeout', 'lodash', 'dataService'];

function BoardController($rootScope, $http, $timeout, _, dataService) {
  var $ctrl = this;
  $ctrl.message = 'The issue board will appear here!';
}

module.exports = {
  templateUrl: window.templatesRoot + '/board.html',
  controller: BoardController,
  bindings: {
    projects: '=',
    issues: '='
  }
};