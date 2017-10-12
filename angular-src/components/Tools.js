ToolsController.$inject = ['$rootScope', '$http', '$timeout', 'lodash', 'dataService'];

function ToolsController($rootScope, $http, $timeout, _, dataService) {
  var $ctrl = this;
  $ctrl.fields = {
    catName: ''
  };

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

  $ctrl.createIssueCat = function(projectId) {
    console.log('createIssueCat',$ctrl.fields);
    dataService.createResource('issue_cat', {
      wp_project_id: projectId,
      name: $ctrl.fields.catName
    })
    .then(function(resource) {
      console.log('created resource', resource)
    })
  }

  $ctrl.cleanup = function() {
    $http({
      method: 'DELETE',
      url: window.wpglib.siteRoot + '/wp-json/wpglib/v1/cleanup'
    })
    .then(function(response) {
      console.log('after cleanup', response.data);
    })
  }
}

module.exports = {
  templateUrl: window.wpglib.templatesRoot + '/tools.html',
  controller: ToolsController,
  bindings: {
    issues: '=',
    projects: '='
  }
};