var gitlabIssuesApp = angular.module('WordPressGitlabIssueBoard', [
  'ui.router',
  'ngLodash',
  'ngDragDrop'
])
.component('board', require('./components/Board'))
.component('issueEditor', require('./components/IssueEditor'))
.component('tools', require('./components/Tools'))
.factory('dataService', require('./factories/DataService'))
.config(require('./config/UIRouter'))
.run(function() {
  console.log('running WordPressGitlabIssueBoard app');
});
