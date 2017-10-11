 <?php
/**
 * Class SampleTest
 *
 * Since we want to have reproducible tests, we want to
 * backup/restore our GitLab database:
 * https://github.com/gitlabhq/gitlabhq/blob/master/doc/raketasks/backup_restore.md
 *
 * @package Gitlab_Issue_Board
 */

use bhubr\wp\glib;
use GuzzleHttp\Psr7\Request;

/**
 * Sample test case.
 */
class Custom_REST_Routes_Test extends WP_UnitTestCase {


	public function setUp() {
	    parent::setUp();
	   
	    global $wp_rest_server;
	    $this->server = $wp_rest_server = new WP_REST_Server;
	    do_action( 'rest_api_init' );

	    // reset post autoincrement
	    reset_auto_increments();

	    $this->client = new GuzzleHttp\Client(['base_uri' => GITLAB_DOMAIN]);
	    $request = new Request('POST', GITLAB_DOMAIN . '/reset');
	    $response = $this->client->send($request, []);
	}


	public function tearDown() {
	    parent::tearDown();
	   
	    global $wp_rest_server;
	    $wp_rest_server = null;
	}


	public function test_mock_backend_project_update() {
		$updated_name = 'updated name ' . time();
		$response = $this->client->request('PUT', GITLAB_DOMAIN . '/api/v4/projects/4001', [
			'json' => [ 'name' => $updated_name ]
		]);

		$this->assertEquals( 200, $response->getStatusCode() );
		$updated_proj = json_decode( $response->getBody()->getContents(), true );
		$this->assertEquals( $updated_name, $updated_proj['name'] );

		$response = $this->client->request('GET', GITLAB_DOMAIN . '/api/v4/projects/4001');
		$this->assertEquals( 200, $response->getStatusCode() );
		$proj = json_decode( $response->getBody()->getContents(), true );
		$this->assertEquals( $updated_name, $proj['name'] );
	}


	public function test_mock_backend_project_delete() {
		$response = $this->client->request('DELETE', GITLAB_DOMAIN . '/api/v4/projects/4001');

		$this->assertEquals( 200, $response->getStatusCode() );
		$body = json_decode( $response->getBody()->getContents(), true );
		$this->assertEquals( $body, ['success' => true] );

		$response = $this->client->request('GET', GITLAB_DOMAIN . '/api/v4/projects');
		$this->assertEquals( 200, $response->getStatusCode() );
		$projects = json_decode( $response->getBody()->getContents(), true );
		$this->assertEquals( 4, count( $projects ) );
	}

	public function test_mock_backend_project_read_not_found() {
		try {
			$response = $this->client->request('GET', GITLAB_DOMAIN . '/api/v4/projects/99999');
		} catch( \Exception $e ) {
			$this->assertEquals( 404, $e->getCode() );
		}
		
	}

	public function test_mock_backend_project_update_not_found() {
		try {
			$response = $this->client->request('PUT', GITLAB_DOMAIN . '/api/v4/projects/99999', [
				'json' => [ 'name_with_namespace' => 'new name' ]
			]);
		} catch( \Exception $e ) {
			$this->assertEquals( 404, $e->getCode() );
		}
	}

	public function test_mock_backend_project_delete_not_found() {
		try {
			$response = $this->client->request('DELETE', GITLAB_DOMAIN . '/api/v4/projects/99999');
		} catch( \Exception $e ) {
			$this->assertEquals( 404, $e->getCode() );
		}
	}


	public function test_mock_backend_issue_update() {
		$updated_title = 'updated title ' . time();
		$response = $this->client->request('PUT', GITLAB_DOMAIN . '/api/v4/projects/4001/issues/2', [
			'json' => [ 'title' => $updated_title ]
		]);

		$this->assertEquals( 200, $response->getStatusCode() );
		$updated_issue = json_decode( $response->getBody()->getContents(), true );
		$this->assertEquals( $updated_title, $updated_issue['title'] );

		$response = $this->client->request('GET', GITLAB_DOMAIN . '/api/v4/projects/4001/issues/2');
		$this->assertEquals( 200, $response->getStatusCode() );
		$issue = json_decode( $response->getBody()->getContents(), true );
		$this->assertEquals( $updated_title, $issue['title'] );
	}

	public function test_mock_backend_issue_read_not_found() {
		try {
			$response = $this->client->request('GET', GITLAB_DOMAIN . '/api/v4/projects/4001/issues/999');
		} catch( \Exception $e ) {
			$this->assertEquals( 404, $e->getCode() );
		}
		
	}

	public function test_mock_backend_issue_update_not_found() {
		try {
			$response = $this->client->request('PUT', GITLAB_DOMAIN . '/api/v4/projects/4001/issues/999', [
				'json' => [ 'name_with_namespace' => 'new name' ]
			]);
		} catch( \Exception $e ) {
			$this->assertEquals( 404, $e->getCode() );
		}
	}

	public function test_mock_backend_issue_delete_not_found() {
		try {
			$response = $this->client->request('DELETE', GITLAB_DOMAIN . '/api/v4/projects/4001/issues/999');
		} catch( \Exception $e ) {
			$this->assertEquals( 404, $e->getCode() );
		}
	}

	public function test_sync_projects() {
		$request = new WP_REST_Request( 'POST', '/wpglib/v1/sync-projects' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$synced_projects = $response->data;
		$this->assertEquals( 5, count( $synced_projects ) );
		foreach( $synced_projects as $sp ) {
			$this->assertEquals( 'created', $sp['_changed'] );
		}
	}

	public function test_sync_projects_after_update_and_delete() {

		// first sync
		$request = new WP_REST_Request( 'POST', '/wpglib/v1/sync-projects' );
		$response = $this->server->dispatch( $request );
		$synced_projects = $response->data;
		// var_dump($synced_projects);
		dump_project_posts($synced_projects);

		foreach( [4001, 4003] as $id) {
			$response = $this->client->request('DELETE', GITLAB_DOMAIN . '/api/v4/projects/' . $id);	
		}

		foreach( [4002 => 'foo/dummy', 4004 => 'bar/fake'] as $id => $new_name) {
			$response = $this->client->request('PUT', GITLAB_DOMAIN . '/api/v4/projects/' . $id, [
				'json' => [ 'name_with_namespace' => $new_name ]
			]);
		}

		$names = pick_post_titles( $synced_projects );

		// second sync: statuses should have changed
		$request = new WP_REST_Request( 'POST', '/wpglib/v1/sync-projects' );
		$response = $this->server->dispatch( $request );

		$this->assertEquals( 200, $response->status );

		$synced_projects = $response->data;
		$this->assertEquals( 5, count( $synced_projects ) );

		$reduced_output = array_map(function($proj) {
			return pick_array_keys($proj, ['id', 'gl_project_id', 'title', '_changed']);
		}, $synced_projects);

		$this->assertEquals( [
			['id' => '1', 'gl_project_id' => '4001', 'title' => ['rendered' => $names[0]], '_changed' => 'deleted'],
			['id' => '2', 'gl_project_id' => '4002', 'title' => ['rendered' => 'foo/dummy'], '_changed' => 'updated'],
			['id' => '3', 'gl_project_id' => '4003', 'title' => ['rendered' => $names[2]], '_changed' => 'deleted'],
			['id' => '4', 'gl_project_id' => '4004', 'title' => ['rendered' => 'bar/fake'], '_changed' => 'updated'],
			['id' => '5', 'gl_project_id' => '4005', 'title' => ['rendered' => $names[4]], '_changed' => 'unchanged']
		], $reduced_output );

	}

	public function test_sync_issues() {
		// First of all sync projects
		$request = new WP_REST_Request( 'POST', '/wpglib/v1/sync-projects' );
		$response = $this->server->dispatch( $request );

		$request = new WP_REST_Request( 'POST', '/wpglib/v1/sync-issues/4002' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->status );

		$synced_issues = $response->data;
		$this->assertEquals( 5, count( $synced_issues ) );
		dump_issue_posts($synced_issues);
		foreach( $synced_issues as $idx => $si ) {
			$this->assertEquals( $idx + 100006, $si['gl_id'] );
			$this->assertEquals( $idx + 1, $si['gl_iid'] );
			$this->assertEquals( 'opened', $si['gl_state'] );
			$this->assertEquals( '4002', $si['gl_project_id'] );
			$this->assertEquals( 'created', $si['_changed'] );
		}
	}

	public function test_create_issue_cat() {
		$issue_cat_attrs = [
			'name' => 'Root cat #1',
			'wp_post_id' => 1
		];
		echo "pouet\n";
		$request = new WP_REST_Request( 'POST', '/wp/v2/issue_cat', $issue_cat_attrs );
		$response = $this->server->dispatch( $request );
echo "pouet\n";
		$this->assertEquals( 200, $response->status );
		$issue_cat_obj = $response->data;
		$this->assertEquals( 'Root cat #1', $issue_cat_obj['name'] );
		$this->assertEquals( '1', $issue_cat_obj['wp_post_id'] );

	}

}