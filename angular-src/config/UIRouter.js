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

  $urlRouterProvider.otherwise('/');

  var states = [
    {
      name: 'board',
      url: '/',
      component: 'board'
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