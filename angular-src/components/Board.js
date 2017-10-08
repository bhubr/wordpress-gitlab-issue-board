BoardController.$inject = ['$rootScope', '$http', '$timeout', 'lodash'];

function BoardController($rootScope, $http, $timeout, _) {
  this.message = 'The issue board will appear here!';
}

module.exports = {
  templateUrl: window.templatesRoot + '/board.html',
  controller: BoardController,
  bindings: {
  }
};