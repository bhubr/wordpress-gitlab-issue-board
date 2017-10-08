var gitlabIssuesApp = angular.module('WordPressGitlabIssueBoard', [
  'ui.router',
  'ngLodash'
])
.component('board', require('./components/Board'))
.component('tools', require('./components/Tools'))
.config(require('./config/UIRouter'))
.run(function() {
  console.log('running WordPressGitlabIssueBoard app');
});
