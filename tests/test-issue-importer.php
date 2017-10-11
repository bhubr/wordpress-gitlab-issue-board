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

use bhubr\wp\glib\wpdb_io;

require_once( realpath( __DIR__ . '/../src/wpdb-io-common.php' ) );
require_once( realpath( __DIR__ . '/../src/wpdb-io-issues.php' ) );

/**
 * Sample test case.
 */
class WPDB_IO_Issues_Test extends WP_UnitTestCase {


	public function setUp() {
	    parent::setUp();
	   
	    global $wp_rest_server;
	    $this->server = $wp_rest_server = new WP_REST_Server;
	    do_action( 'rest_api_init' );

	    reset_auto_increments();

		$attrs = [
			'id' => 287200,
			'description' => 'lorem ipsum go to hell',
			'name_with_namespace' => 'Basilisk / pea-brained witch',
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch'
		];
		$result = wpdb_io\import_one_project( $attrs );

	}

	public function tearDown() {
	    parent::tearDown();
	   
	    global $wp_rest_server;
	    $wp_rest_server = null;
	}

	function test_record_already_exists_none_before() {
		$exists = $post_id = wpdb_io\record_already_exists([
			'id' => 17100,
			'iid' => 23,
			'created_at' => '2017-10-10T01:31:51.081Z',
			'updated_at' => '2017-10-10T01:33:23.081Z',
			'title' => 'Tumble misunderstood demon',
			'description' => 'lorem ipsum go to hell',
			'state' => 'opened',
			'labels' => [],
			'project_id' => 287200,
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/23'
		], 'issue' );
		$this->assertFalse( $exists );
	}

	function test_record_already_exists_yes_after_import() {
		$attrs = [
			'id' => 17200,
			'iid' => 24,
			'created_at' => '2017-10-10T01:31:51.081Z',
			'updated_at' => '2017-10-10T01:33:23.081Z',
			'title' => 'Relax flirting sea monster',
			'description' => 'lorem ipsum go to hell',
			'state' => 'opened',
			'labels' => [],
			'project_id' => 287200,
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/24'
		];
		wpdb_io\import_one_issue( $attrs );
		$existing_issue = wpdb_io\record_already_exists( $attrs, 'issue' );
		$this->assertNotEmpty( $existing_issue );
	}


	function test_compare_issue_attributes() {
		$attrs = [
			'id' => 17300,
			'iid' => 25,
			'created_at' => '2017-10-10T01:31:51.081Z',
			'updated_at' => '2017-10-10T01:33:23.081Z',
			'title' => 'Encourage confused sprite',
			'description' => 'lorem ipsum go to hell',
			'state' => 'opened',
			'labels' => [],
			'project_id' => 287200,
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/25'
		];
		wpdb_io\import_one_issue( $attrs );
		$existing_issue = wpdb_io\record_already_exists( $attrs, 'issue' );
		$new_attrs = [
			'title' => 'Discourage confused sprite',
			'description' => 'lorem ipsum go to heaven',
			'state' => 'closed'
		];
		$attrs_updated = wpdb_io\compare_issue_attributes( $existing_issue, $new_attrs );
		$this->assertEquals( [
			'post_title' => 'Discourage confused sprite',
			'post_content' => 'lorem ipsum go to heaven',
			'gl_state' => 'closed'
		], $attrs_updated );
	}

	function test_get_set_attributes_query_part() {
		$set_attrs_part = wpdb_io\get_set_attributes_query_part( [
			'description' => 'foo',
			'title' => 'bar'
		] );
		$this->assertEquals( "description='%s',title='%s'", $set_attrs_part );
	}

	/**
	 * Try importing one issue only
	 */
	function test_import_one_once() {
		list($post_id, $status) = wpdb_io\import_one_issue([
			'id' => 17400,
			'iid' => 26,
			'created_at' => '2017-10-10T01:31:51.081Z',
			'updated_at' => '2017-10-10T01:33:23.081Z',
			'title' => 'Stroke hyperactive tree nymph',
			'description' => 'lorem ipsum go to hell',
			'state' => 'opened',
			'labels' => [],
			'project_id' => 287200,
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/26'
		]);
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post_id}", OBJECT );
		$result = array_pop( $results );
		$this->assertEquals( 17400, $result->comment_count );
		$this->assertEquals( 26, $result->menu_order );
		$this->assertEquals( 1, $result->post_parent );
		$this->assertEquals( 'Stroke hyperactive tree nymph', $result->post_title );
		$this->assertEquals( 'lorem ipsum go to hell', $result->post_content );
		$this->assertEquals( GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/26', $result->guid );
		$this->assertEquals( 'issue', $result->post_type );
		$this->assertEquals( 'publish', $result->post_status );
		$meta_state = get_post_meta( $result->ID, 'gl_state', true );
		$this->assertEquals( 'opened', $meta_state );
		$meta_proj_id = get_post_meta( $result->ID, 'gl_project_id', true );
		$this->assertEquals( 287200, $meta_proj_id );

		$request = new WP_REST_Request( 'GET', '/wp/v2/issue' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->status );
		$picked_values = pick_array_keys( $response->data[0], [
			'id', 'gl_id', 'gl_iid', 'percent_done', 'priority', 'gl_project_id', 'wp_project_id', 'gl_state', 'guid', 'post_title', 'status', 'type', 'slug', 'title', 'content'
		] );
		$this->assertEquals( [
    			'id' => 2,
    			'gl_id' => 17400,
    			'guid' => [
    				'rendered' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/26'
    			],
    			'title' => [
    				'rendered' => "Stroke hyperactive tree nymph"
				],
    			'content' => [
    				'rendered' => 'lorem ipsum go to hell',
    				'protected' => false
				],
				'status' => 'publish',
				'type' => 'issue',
				'slug' => 'stroke-hyperactive-tree-nymph',
				'wp_project_id' => 1,
				'gl_iid' => 26,
				'gl_project_id' => 287200,
				'gl_state' => 'opened',
				'percent_done' => '',
				'priority' => ''
	    	], $picked_values
	    );
	}


	/**
	 * Try importing the same issue twice
	 */
	function test_import_one_twice_the_same() {
		$attrs = [
			'id' => 17500,
			'iid' => 27,
			'created_at' => '2017-10-10T01:31:51.081Z',
			'updated_at' => '2017-10-10T01:33:23.081Z',
			'title' => 'Remember vile leprechaun',
			'description' => 'lorem ipsum go to hell',
			'state' => 'opened',
			'labels' => [],
			'project_id' => 287200,
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/27'
		];
		$post_id1 = wpdb_io\import_one_issue( $attrs );
		$post_id2 = wpdb_io\import_one_issue( $attrs );
		$posts = get_posts( [
			'post_type' => 'issue'
		] );
		$this->assertEquals( 1, count( $posts ) );
	}


	/**
	 * Try importing the same issue twice
	 */
	function test_import_one_twice_with_update() {
		$attrs = [
			'id' => 17600,
			'iid' => 28,
			'created_at' => '2017-10-10T01:31:51.081Z',
			'updated_at' => '2017-10-10T01:33:23.081Z',
			'title' => 'Please out-of-control Godzilla',
			'description' => 'lorem ipsum go to hell',
			'state' => 'opened',
			'labels' => [],
			'project_id' => 287200,
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/28'
		];
		list($post_id, $status) = wpdb_io\import_one_issue( $attrs );
		$attrs['description'] = "keep calm and go to hell";
		// $attrs['web_url'] = GITLAB_DOMAIN . '/imp/bat-shit-crazy-ogress';
		wpdb_io\import_one_issue( $attrs );

		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post_id}", OBJECT );
		$this->assertEquals( 1, count( $results ) );
		$result = array_pop( $results );
		$this->assertEquals( 'Please out-of-control Godzilla', $result->post_title );
		$this->assertEquals( 'keep calm and go to hell', $result->post_content );
		$this->assertEquals( GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/28', $result->guid );
	}


	/**
	 * Try importing the same issue twice
	 */
	function test_import_many() {
		$seed = [
			[
				'id' => 17700,
				'iid' => 29,
				'created_at' => '2017-10-10T01:31:51.081Z',
				'updated_at' => '2017-10-10T01:33:23.081Z',
				'title' => 'Search angry titan',
				'description' => 'lorem ipsum go to hell',
				'state' => 'opened',
				'labels' => [],
				'project_id' => 287200,
				'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/29'
			],
			[
				'id' => 17800,
				'iid' => 30,
				'created_at' => '2017-10-10T01:31:51.081Z',
				'updated_at' => '2017-10-10T01:33:23.081Z',
				'title' => 'Search angry titan',
				'description' => 'lorem ipsum go to hell',
				'state' => 'opened',
				'labels' => [],
				'project_id' => 287200,
				'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch/issues/30'
			]
		];
		$issues = wpdb_io\import_many_issues( $seed );
		$this->assertEquals( 2, count( $issues ) );
		$posts = get_posts( [
			'post_type' => 'issue'
		] );
		$this->assertEquals( 2, count( $posts ) );
	}

}