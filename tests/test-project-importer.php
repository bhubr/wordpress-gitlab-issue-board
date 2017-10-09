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

require( realpath( __DIR__ . '/../src/class-wp-gitlab-issue-board-project-importer.php' ) );

/**
 * Sample test case.
 */
class WP_Gitlab_Issue_Board_Project_Importer_Test extends WP_UnitTestCase {


	public function setUp() {
	    parent::setUp();
	   
	    global $wp_rest_server;
	    $this->server = $wp_rest_server = new WP_REST_Server;
	    do_action( 'rest_api_init' );
	}

	public function tearDown() {
	    parent::tearDown();
	   
	    global $wp_rest_server;
	    $wp_rest_server = null;
	}

	/**
	 * A single example test.
	 */
	function test_import_one() {
		$post_id = WP_Gitlab_Issue_Board_Project_Importer::import_one([
			'id' => 98765,
			'name_with_namespace' => 'Spirit / tripping Frankenstein’s monster',
			'web_url' => 'https://gitlab.example.com/spirit/tripping-frankensteins-monster'
		]);
		global $wpdb;
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post_id}", OBJECT );
		$result = array_pop( $results );
		var_dump($result);
		$this->assertEquals( 98765, $result->comment_count );
		$this->assertEquals( 'Spirit / tripping Frankenstein’s monster', $result->post_title );
		$this->assertEquals( 'https://gitlab.example.com/spirit/tripping-frankensteins-monster', $result->guid );
		$this->assertEquals( 'project', $result->post_type );
		$this->assertEquals( 'publish', $result->post_status );
	    $this->request_get('/project', 200, [
	    	[
    			'id' => 3, 'gl_project_id' => 98765, 'guid' => array( 'https://gitlab.example.com/spirit/tripping-frankensteins-monster' )
	    	]
	    ]);
	}


	protected function request_get($url, $expected_status, $expected_data) {
	  $request = new WP_REST_Request( 'GET', '/wp/v2' . $url );
	  $response = $this->server->dispatch( $request );
	  $this->assertEquals( $expected_status, $response->status );
	  $this->assertEquals( $expected_data, $response->data );
	}

	// public function test_get() {

	// 	global $wpdb;
	// 	// $results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID=3", OBJECT );
	// 	// $result = array_pop( $results );
	// 	// var_dump($result);

	//     // $model1 = bhubr\Foo::create(['name' => 'Pouet']);
	//     // $model2 = bhubr\Foo::create(['name' => 'Youpla boum']);
	//     // $this->request_get('/foos', 200, [
	//     //     ['id' => 3, 'name' => 'Pouet', 'slug' => 'pouet', 'foo_cat' => null, 'foo_tags' => []],
	//     //     ['id' => 4, 'name' => 'Youpla boum', 'slug' => 'youpla-boum', 'foo_cat' => null, 'foo_tags' => []],
	//     // ]);
	// }
}