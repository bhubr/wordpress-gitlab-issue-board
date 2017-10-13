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

  var defaultProjectId = localStorage.getItem('defaultProjectId');
  if( null !== defaultProjectId ) {
    defaultProjectId = parseInt( defaultProjectId, 10 )
  }
  else if(wpglib.projects.length > 0) {
    defaultProjectId = wpglib.projects[0].id;
  }
  var defaultUrl = ! defaultProjectId ? '/tools' : '/';

  $urlRouterProvider.otherwise(defaultUrl);

  var states = [
    {
      name: 'board',
      url: '/',
      component: 'board',
      resolve: {
        projectId: function() {
          return defaultProjectId;
        },
        board: [
          'dataService', function(dataService) {
            return dataService.getBoard(defaultProjectId)
            .then(function(data) {
              console.log('board data', data);
              return Object.assign(data, { projectId: defaultProjectId });
            });
          }
        ]
      }
    },
    {
      name: 'tools',
      url: '/tools',
      component: 'tools',
      resolve: {
        issues: [
          'dataService', function(dataService) {
            return dataService.getIssues();
          }
        ],
        projects: [
          'dataService', function(dataService) {
            return dataService.getProjects();
          }
        ]
      }
    }
  ];
  states.forEach(function(state) {
    $stateProvider.state(state);
  });
}

module.exports = UIRouterConfig;