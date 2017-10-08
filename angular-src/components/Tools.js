ToolsController.$inject = ['$rootScope', '$http', '$timeout', 'lodash'];

function ToolsController($rootScope, $http, $timeout, _) {
  this.message = 'The tools will appear here!';
}

module.exports = {
  templateUrl: window.templatesRoot + '/tools.html',
  controller: ToolsController,
  bindings: {
  }
};