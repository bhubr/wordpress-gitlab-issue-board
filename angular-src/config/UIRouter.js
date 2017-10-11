UIRouterConfig.$inject = [
  '$stateProvider',
  '$urlRouterProvider',
  '$locationProvider',
  '$httpProvider'
];

function UIRouterConfig(
  $stateProvider,
  $urlRouterProvider,
  $locationProvider,
  $httpProvider
) {

  $urlRouterProvider.otherwise('/tools');

  var states = [
    {
      name: 'board',
      url: '/',
      component: 'board',
      resolve: {
        issues: [
          'dataService', function(dataService) {
            return dataService.getIssues();
          }
        ],
        issueCats: [
          'dataService', function(dataService) {
            return dataService.getIssueCats();
          }
        ],
        issueLabels: [
          'dataService', function(dataService) {
            return dataService.getIssueLabels();
          }
        ]
      }
    },
    {
      name: 'tools',
      url: '/tools',
      component: 'tools'
    }
  ];
  states.forEach(function(state) {
    $stateProvider.state(state);
  });
}

module.exports = UIRouterConfig;