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
      url: window.siteRoot + '/wp-json/wp/v2/' + type,
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
      url: window.siteRoot + '/wp-json/wp/v2/' + type + '/' + data.id,
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
    var url = window.siteRoot + '/wp-json/wp/v2/' + type;
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
      url: window.siteRoot + '/wp-json/wpglib/v1/sync-projects',
      data: {},
      headers: {
        'X-WP-Nonce': wpApiSettings.nonce
      }
    })
    .then(function(response) {
      return response.data;
    });;
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
    getProjects: getProjects,
    syncProjects: syncProjects,
    getIssues: getIssues,
    getIssueCats: getIssueCats,
    getIssueLabels: getIssueLabels,
    getResources: getResources,
    createResource: createResource,
    updateResource: updateResource
  };
}

module.exports = DataService;