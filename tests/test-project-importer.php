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
require_once( realpath( __DIR__ . '/../src/wpdb-io-projects.php' ) );
echo ABSPATH . "\n";
/**
 * Sample test case.
 */
class WPDB_IO_Projects_Test extends WP_UnitTestCase {


	public function setUp() {
	    parent::setUp();
echo "project importer " . site_url() . ' ' . ABSPATH . "\n";	   
	    global $wp_rest_server;
	    $this->server = $wp_rest_server = new WP_REST_Server;
	    do_action( 'rest_api_init' );

	    global $wpdb;
	    $wpdb->query( "ALTER TABLE {$wpdb->prefix}posts AUTO_INCREMENT = 1" );
	}

	public function tearDown() {
	    parent::tearDown();
	   
	    global $wp_rest_server;
	    $wp_rest_server = null;
	}

	function test_record_already_exists_none_before() {
		$exists = $post_id = wpdb_io\record_already_exists([
			'id' => 17100,
			'description' => 'lorem ipsum go to hell',
			'name_with_namespace' => 'Basilisk / pea-brained witch',
			'web_url' => GITLAB_DOMAIN . '/basilisk/pea-brained-witch'
		], 'project' );
		$this->assertFalse( $exists );
	}

	function test_record_already_exists_yes_after_import() {
		$attrs = [
			'id' => 17200,
			'description' => 'lorem ipsum go to hell',
			'name_with_namespace' => 'Golum / drunken gorgon',
			'web_url' => GITLAB_DOMAIN . '/golum/drunken-gorgon'
		];
		wpdb_io\import_one_project( $attrs );
		$existing_project = wpdb_io\record_already_exists( $attrs, 'project' );
		$this->assertNotEmpty( $existing_project );
	}

	function test_record_already_exists_yes_after_changing_web_url() {
		$attrs = [
			'id' => 17300,
			'description' => 'lorem ipsum go to hell',
			'name_with_namespace' => 'Gnome / cruel-hearted troll',
			'web_url' => GITLAB_DOMAIN . '/gnome/cruel-hearted-troll'
		];
		wpdb_io\import_one_project( $attrs );
		$attrs['web_url'] = GITLAB_DOMAIN . '/gnome/cruel-hearted-zombie';
		$existing_project = wpdb_io\record_already_exists( $attrs, 'project' );
		$this->assertNotEmpty( $existing_project );
	}

	function test_compare_project_attributes() {
		$attrs = [
			'id' => 17300,
			'description' => 'lorem ipsum go to hell',
			'name_with_namespace' => 'Gnome / cruel-hearted troll',
			'web_url' => GITLAB_DOMAIN . '/gnome/cruel-hearted-troll'
		];
		wpdb_io\import_one_project( $attrs );
		$existing_project = wpdb_io\record_already_exists( $attrs, 'project' );
		$new_attrs = [
			'description' => 'lorem ipsum go to heaven',
			'name_with_namespace' => 'Gnome / cruel-hearted mountain troll',
			'web_url' => GITLAB_DOMAIN . '/gnome/cruel-hearted-troll'
		];
		$attrs_updated = wpdb_io\compare_project_attributes( $existing_project, $new_attrs );
		$this->assertEquals( [
			'post_title' => 'Gnome / cruel-hearted mountain troll',
			'post_content' => 'lorem ipsum go to heaven'
		], $attrs_updated );
	}

	function test_get_set_attributes_query_part() {
		$set_attrs_part = wpdb_io\get_set_attributes_query_part( [
			'description' => 'foo',
			'name_with_namespace' => 'bar'
		] );
		$this->assertEquals( "description='%s',name_with_namespace='%s'", $set_attrs_part );
	}

	/**
	 * Try importing one project only
	 */
	function test_import_one_once() {
		list($post_id, $status) = wpdb_io\import_one_project([
			'id' => 17400,
			'description' => 'lorem ipsum go to hell',
			'name_with_namespace' => 'Spirit / tripping Frankenstein’s monster',
			'web_url' => GITLAB_DOMAIN . '/spirit/tripping-frankensteins-monster'
		]);
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post_id}", OBJECT );
		$result = array_pop( $results );
		$this->assertEquals( 17400, $result->comment_count );
		$this->assertEquals( 'Spirit / tripping Frankenstein’s monster', $result->post_title );
		$this->assertEquals( 'lorem ipsum go to hell', $result->post_content );
		$this->assertEquals( GITLAB_DOMAIN . '/spirit/tripping-frankensteins-monster', $result->guid );
		$this->assertEquals( 'project', $result->post_type );
		$this->assertEquals( 'publish', $result->post_status );

		$request = new WP_REST_Request( 'GET', '/wp/v2/project' );
		$response = $this->server->dispatch( $request );
		$this->assertEquals( 200, $response->status );
		$picked_values = pick_array_keys( $response->data[0], [
			'id', 'gl_project_id', 'guid', 'post_title', 'status', 'type', 'slug', 'title', 'content'
		] );
		$this->assertEquals( [
    			'id' => 1,
    			'gl_project_id' => 17400,
    			'guid' => [
    				'rendered' => GITLAB_DOMAIN . '/spirit/tripping-frankensteins-monster'
    			],
    			'title' => [
    				'rendered' => "Spirit / tripping Frankenstein’s monster"
				],
    			'content' => [
    				'rendered' => 'lorem ipsum go to hell',
    				'protected' => false
				],
				'status' => 'publish',
				'type' => 'project',
				'slug' => 'spirit-tripping-frankensteins-monster'
	    	], $picked_values
	    );
	}


	/**
	 * Try importing the same project twice
	 */
	function test_import_one_twice_the_same() {
		$attrs = [
			'id' => 17500,
			'name_with_namespace' => 'Sea monster / misunderstood yeti',
			'web_url' => GITLAB_DOMAIN . '/sea-monster/misunderstood-yeti',
			'description' => 'lorem ipsum go to hell'
		];
		$post_id1 = wpdb_io\import_one_project( $attrs );
		$post_id2 = wpdb_io\import_one_project( $attrs );
		$posts = get_posts( [
			'post_type' => 'project'
		] );
		$this->assertEquals( 1, count( $posts ) );
	}


	/**
	 * Try importing the same project twice
	 */
	function test_import_one_twice_with_update() {
		$attrs = [
			'id' => 17600,
			'name_with_namespace' => 'Imp / bat-shit-crazy ogre',
			'web_url' => GITLAB_DOMAIN . '/imp/bat-shit-crazy-ogre',
			'description' => 'lorem ipsum go to hell'
		];
		list($post_id, $status) = wpdb_io\import_one_project( $attrs );
		$attrs['description'] = "keep calm and go to hell";
		$attrs['web_url'] = GITLAB_DOMAIN . '/imp/bat-shit-crazy-ogress';
		wpdb_io\import_one_project( $attrs );

		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post_id}", OBJECT );
		$this->assertEquals( 1, count( $results ) );
		$result = array_pop( $results );
		$this->assertEquals( 'Imp / bat-shit-crazy ogre', $result->post_title );
		$this->assertEquals( 'keep calm and go to hell', $result->post_content );
		$this->assertEquals( GITLAB_DOMAIN . '/imp/bat-shit-crazy-ogress', $result->guid );
	}


	/**
	 * Try importing the same project twice
	 */
	// function test_import_many() {
	// 	$seed = [
	// 		[
	// 			'id' => 17700,
	// 			'name_with_namespace' => 'Hydra / zombie-like devil',
	// 			'web_url' => GITLAB_DOMAIN . '/hydra/zombie-like-devil',
	// 			'description' => 'lorem ipsum go to hell'
	// 		],
	// 		[
	// 			'id' => 17800,
	// 			'name_with_namespace' => 'merman / misunderstood leviathan',
	// 			'web_url' => GITLAB_DOMAIN . '/merman/misunderstood-leviathan',
	// 			'description' => 'lorem ipsum go to hell'
	// 		]
	// 	];
	// 	$projects = wpdb_io\import_many_projects( $seed );
	// 	$this->assertEquals( 2, count( $projects ) );
	// 	$posts = get_posts( [
	// 		'post_type' => 'project'
	// 	] );
	// 	$this->assertEquals( 2, count( $posts ) );
	// }

}