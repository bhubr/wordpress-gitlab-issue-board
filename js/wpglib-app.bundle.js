webpackJsonp([0],{

/***/ 53:
/***/ (function(module, exports, __webpack_require__) {

var gitlabIssuesApp = angular.module('WordPressGitlabIssueBoard', [
  'ui.router',
  'ngLodash',
  'ngDragDrop'
])
.component('board', __webpack_require__(54))
.component('issueEditor', __webpack_require__(85))
.component('tools', __webpack_require__(55))
.factory('dataService', __webpack_require__(56))
.config(__webpack_require__(57))
.run(function() {
  console.log('running WordPressGitlabIssueBoard app');
});


/***/ }),

/***/ 54:
/***/ (function(module, exports) {

BoardController.$inject = ['$rootScope', '$http', '$timeout', 'lodash', 'dataService'];

function BoardController($rootScope, $http, $timeout, _, dataService) {
  var $ctrl = this;
  $ctrl.stateFilter = 'opened';
  $ctrl.filteredIssues = [];
  $ctrl.rootCats = [];
  $ctrl.issueEditing = null;
  $ctrl.parentCatEditing = null;
  $ctrl.data = {
    listName: '',
    cardName: ''
  };

  $ctrl.filterIssues = function( state ) {
    if( state ) {
      $ctrl.stateFilter = state;
    }
    $ctrl.filteredIssues = $ctrl.stateFilter === 'all' ?
      $ctrl.issues :
      $ctrl.issues.filter(function(issue) {
        return issue.gl_state === $ctrl.stateFilter &&
          issue.issue_cat.length === 0;
      });
  };


  function filterChildIssues(cat) {
    cat.issues = $ctrl.issues.filter(function(issue) {
      return issue.issue_cat.indexOf(cat.id) > -1;
    });
    cat.closedIssues = cat.issues.filter(function(issue) {
      return issue.gl_state === 'closed';
    });
  }

  function filterChildCategories(rootCat) {
    rootCat.childCats = $ctrl.issueCats.filter(function(cat) {
      return cat.parent === rootCat.id;
    });
    rootCat.childCats.forEach(filterChildIssues);
  }

  $ctrl.createList = function() {
    console.log('createList', $ctrl.projectId);
    dataService.createIssueCat({
      name: $ctrl.data.listName,
      wp_project_id: $ctrl.projectId
    })
    .then(function(issueCat) {
      console.log(issueCat);
      $ctrl.rootCats.push(issueCat);
    });
  };

  $ctrl.createCard = function(parentCat) {
    console.log('createCard', $ctrl.projectId, parentCat.id);
    dataService.createIssueCat({
      name: $ctrl.data.cardName,
      parent: parentCat.id,
      wp_project_id: $ctrl.projectId
    })
    .then(function(issueCat) {
      console.log(issueCat);
      parentCat.childCats.push(issueCat);
    });
  };


  $timeout(function() {

    $ctrl.issues = $ctrl.board.issues;
    $ctrl.issueCats = $ctrl.board.issueCats;
    $ctrl.issueLabels = $ctrl.board.issueLabels;
    $ctrl.projectId = $ctrl.board.projectId;


    // Sort out root cats (boards) and 2nd-level cats (cards)
    $ctrl.rootCats = $ctrl.issueCats.filter(function(cat) {
      return cat.parent === 0;
    });
    $ctrl.rootCats.forEach(filterChildCategories);
    console.log('board content', $ctrl, $ctrl.board);
    // Filter issues
    $ctrl.filterIssues('opened');

  }, 50);

  $ctrl.dropped = function(evt, elem) {
    var targetElem = evt.target;
    var targetCatIdStr = targetElem.id.substr( 'issue-cat-'.length );
    var targetCatId = parseInt(targetCatIdStr, 10);

    var droppedElems = elem.draggable;
    var droppedIssueIdStr = droppedElems[0].id.substr( 'issue-'.length );
    var droppedIssueId = parseInt(droppedIssueIdStr, 10);

    var issue = _.find($ctrl.issues, { id: droppedIssueId });
    var issueIdx = $ctrl.issues.indexOf(issue);
console.log('drop issue', issue);
    issue.issue_cat.push(targetCatId);

    dataService.updateResource('issue', issue)
    .then(function(updatedIssue) {
      var issueCat = _.find($ctrl.issueCats, { id: targetCatId });
      issueCat.issues.push(updatedIssue);
      $ctrl.issues[issueIdx] = updatedIssue;
      $ctrl.filterIssues();
    });
  };

  $ctrl.editIssue = function(parentCat, issue) {
    if($ctrl.parentCatEditing !== null) {
      $ctrl.parentCatEditing._isEditing = false;
    }
    $ctrl.parentCatEditing = parentCat;
    parentCat._isEditing = true;
    $ctrl.issueEditing = issue ? issue : {
      _title: '',
      _description: ''
    };
    console.log('editIssue', $ctrl.issueEditing);
  };

  $ctrl.cancelEditing = function() {
    $ctrl.parentCatEditing._isEditing = false;
    $ctrl.parentCatEditing = null;
    $ctrl.issueEditing = null;
  };

  $ctrl.saveIssue = function() {

    // shorthand for issue
    var issue = $ctrl.issueEditing;

    // trigger hiding of issue editor in card
    $ctrl.parentCatEditing._isEditing = false;

    // set back the real title and desc properties
    issue.title = issue._title;
    issue.content = issue._description;
    issue.post_parent = $ctrl.projectId;
    delete issue._title;
    delete issue._description;
console.log('saving issue', issue);
    // save to backend
    if(issue.id) {
      dataService.updateResource('issue', issue)
      .then(function(updatedIssue) {
        var originalIssue = _.find($ctrl.issues, { id: issue.id });
        var issueIdx = $ctrl.issues.indexOf(originalIssue);
        $ctrl.issues[issueIdx] = updatedIssue;
        filterChildIssues($ctrl.parentCatEditing);
        $ctrl.cancelEditing();
        $rootScope.$emit('alertOn', {
          class: 'success', text: 'Issue updated'
        });
      });
    }
    else {
      issue.status = 'publish';
      issue.issue_cat = [$ctrl.parentCatEditing.id];
      dataService.createResource('issue', issue)
      .then(function(newIssue) {
        $ctrl.issues.push(newIssue);
        filterChildIssues($ctrl.parentCatEditing);
        $ctrl.cancelEditing();
        $rootScope.$emit('alertOn', {
          class: 'success', text: 'Issue created'
        });

      });
    }
  };
}

module.exports = {
  templateUrl: window.wpglib.templatesRoot + '/board.html',
  controller: BoardController,
  bindings: {
    board: '='
  }
};

/***/ }),

/***/ 55:
/***/ (function(module, exports) {

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

/***/ }),

/***/ 56:
/***/ (function(module, exports) {

/*----------------------------------------
 | Data service
 *----------------------------------------
 |
 */
DataService.$inject = ['$rootScope', '$http', '$q', 'lodash'];

function DataService($rootScope, $http, $q, _) {

  // var cached = {};
  // var ajaxPending = 0;

  // function alertPending() {
  //   ajaxPending++;
  //   $rootScope.$emit('alertOn', {
  //     class: 'info', text: ajaxPending + ' requests pending'
  //   });
  // }

  // function stopPending() {
  //   ajaxPending--;
  //   if( ajaxPending === 0 ) {
  //     $rootScope.$emit('alertOff');  
  //   }
  // }

  // https://stackoverflow.com/questions/1714786/query-string-encoding-of-a-javascript-object
  serialize = function(obj) {
    var str = [];
    for(var p in obj)
      if (obj.hasOwnProperty(p)) {
        str.push(encodeURIComponent(p) + "=" + encodeURIComponent(obj[p]));
      }
    return str.join("&");
  };

  function createResource(type, data) {
    // alertPending();
    return $http({
      method: 'POST',
      url: window.wpglib.siteRoot + '/wp-json/wp/v2/' + type,
      data: data,
      headers: {
        'X-WP-Nonce': wpApiSettings.nonce
      }
    })
    .then(function(response) {
      // stopPending();
      return response.data;
    });
  }

  function updateResource(type, data) {
    // alertPending();
    return $http({
      method: 'PUT',
      url: window.wpglib.siteRoot + '/wp-json/wp/v2/' + type + '/' + data.id,
      data: data,
      headers: {
        'X-WP-Nonce': wpApiSettings.nonce
      }
    })
    .then(function(response) {
      // stopPending();
      return response.data;
    });
  }

  function getResources(type, args) {
    // if(cached[type]) {
    //   console.log('return cached values for type', type);
    //   return $q.when(cached[type]);
    // }
    // alertPending();
    var url = window.wpglib.siteRoot + '/wp-json/wp/v2/' + type;
    if (args) {
      url += '?' + serialize(args);
    }
    console.log('getResources', type, args, url);
    return $http.get(url)
    .then(function(response) {
      // stopPending();
      // console.log('response', response);
      // cached[type] = response.data;
      return response.data;
    });
  }

  function getAllPages(type, args, carry) {
    if(! carry) {
      args.page = 1;
      carry = [];
    }
    return getResources(type, args)
    .then(function(values) {
      carry = carry.concat(values);
      console.log('got page', args.page, values.length + ' items', 'CARRY: ' + carry.length + ' items');
      if(values.length < 100) {
        return $q.when(carry);
      }
      else {
        args.page++;
        console.log('proceed to page', args.page);
        return getAllPages(type, args, carry);
      }
    });
  }

  function syncProjects() {
    return $http({
      method: 'POST',
      url: window.wpglib.siteRoot + '/wp-json/wpglib/v1/sync-projects',
      data: {},
      headers: {
        'X-WP-Nonce': wpApiSettings.nonce
      }
    })
    .then(function(response) {
      return response.data;
    });;
  }


  function post(path, data) {
    return $http({
      method: 'POST',
      url: window.wpglib.siteRoot + '/wp-json' + path,
      data: data,
      headers: {
        'X-WP-Nonce': wpApiSettings.nonce
      }
    })
    .then(function(response) {
      return response.data;
    })
  }

  function createIssueCat( data ) {
    return post('/wp/v2/issue_cat', data);
  }
  function syncIssues(projectId) {
    return $http({
      method: 'POST',
      url: window.wpglib.siteRoot + '/wp-json/wpglib/v1/sync-issues/' + projectId,
      // data: {
      //   post_id: postId
      // },
      headers: {
        'X-WP-Nonce': wpApiSettings.nonce
      }
    })
    .then(function(response) {
      return response.data;
    });;
  }

  function getBoard(wpProjectId) {
    return $http.get(window.wpglib.siteRoot + '/wp-json/wpglib/v1/board/' + wpProjectId)
    .then(function(response) {
      return response.data;
    })
  }

  function getIssues() {
    return getAllPages('issue', {
      per_page: 100, orderby: 'date', order: 'asc'
    });
  }

  function getProjects() {
    return getResources('project', {
      per_page: 100
    });
  }

  function getIssueCats() {
    return getResources('issue_cat', {
      per_page: 100
    });
  }

  function getIssueLabels() {
    return getResources('issue_label');
  }


  // function getCached(resourceName) {
  //   return cached[resourceName];
  // }

  return {
    getBoard: getBoard,
    createIssueCat: createIssueCat,
    getProjects: getProjects,
    syncProjects: syncProjects,
    syncIssues: syncIssues,
    getIssues: getIssues,
    getIssueCats: getIssueCats,
    getIssueLabels: getIssueLabels,
    getResources: getResources,
    createResource: createResource,
    updateResource: updateResource
  };
}

module.exports = DataService;

/***/ }),

/***/ 57:
/***/ (function(module, exports) {

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

/***/ }),

/***/ 85:
/***/ (function(module, exports) {

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

/***/ })

},[53]);