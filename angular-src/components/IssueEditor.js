IssueEditorController.$inject = ['$http', '$timeout', 'lodash', 'dataService'];

function IssueEditorController($http, $timeout, _, dataService) {
  var $ctrl = this;

  $timeout(function() {
    var issue = $ctrl.issue;
    if(issue.id) {
      console.log('existing issue', issue.id, issue.title, issue.content);
      issue._title = issue.title.rendered;
      issue._description = issue.content.rendered;
    }
  }, 50);
 
  this.handleSubmit = function() {
    this.onSubmit();
  }

  this.handleCancel = function() {
    this.onCancel();
  }
}

module.exports = {
  templateUrl: window.wpglib.templatesRoot + '/issue-editor.html',
  controller: IssueEditorController,
  bindings: {
    issue: '=',
    onSubmit: '&',
    onCancel: '&'
  }
};