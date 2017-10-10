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
        projects: [
          'dataService', function(dataService) {
            return dataService.getProjects();
          }
        ],
        issues: [
          'dataService', function(dataService) {
            return dataService.getIssues();
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