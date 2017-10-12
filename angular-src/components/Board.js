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
    delete issue._title;
    delete issue._description;

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