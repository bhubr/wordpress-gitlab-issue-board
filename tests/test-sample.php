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

// require_once 'gitlab-api-settings.php';
require 'generate-project-name.php';

/**
 * Sample test case.
 */
class SampleTest extends WP_UnitTestCase {

	/**
	 * A single example test.
	 */
	function test_sample() {
		$id = wp_insert_post( [
			'post_type'     => 'project',
			'post_status'   => 'publish',
			'post_title'    => 'Administrator / shivering raven spirit',
			'guid'          => 'http://i-do-whatever-i-want.yo',
			'comment_count' => 667
		] );
		$post = get_post( $id );
		$this->assertEquals( 'Administrator / shivering raven spirit', $post->post_title );
		$this->assertEquals( 'publish', $post->post_status );
		$this->assertEquals( 'project', $post->post_type );
		$this->assertEquals( 'http://i-do-whatever-i-want.yo', $post->guid );
		global $wpdb;
		$result = $wpdb->get_results( "UPDATE {$wpdb->prefix}posts SET comment_count=666 WHERE ID={$post->ID}" );
		// var_dump( $result );

		$result = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post->ID}" );
		// var_dump( $result );

		$post = get_post( $id );
		$results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post->ID}", OBJECT );
		$result = array_pop( $results );
		// var_dump($post);
		$this->assertEquals( 666, $result->comment_count );

		// Replace this with some actual testing code.
		// echo "pouet";
		// fake_n_projects( 10 );
		// echo "pouet";
		$this->assertTrue( true );
		// $this->assertEquals( 10, count( $projects ) );
	}

	/**
	 * A single example test.
	 */
	function test_posts() {
		// Replace this with some actual testing code.
		$posts = get_posts();
		$this->assertEquals( 0, count( $posts ) );
	}

	// function test_plugin() {
		// $faker = Faker\Factory::create();
		// $client_wrapper = WP_Gitlab_Issue_Board_API_Client::get_instance();
		// $client_wrapper->set_domain( GITLAB_DOMAIN );
		// $client_wrapper->set_access_token( GITLAB_ACCESS_TOKEN );
		// $client_wrapper->init_gitlab_client();
		// $client = $client_wrapper->get_client();

		// # Creating a new project
		// $project = \Gitlab\Model\Project::create($client, generate_project_name(), array(
		//   'description' => $faker->text,
		//   'issues_enabled' => false
		// ));
		// $projects = $client_wrapper->get_all_of_type( 'project' );
		// $this->assertEquals( 1, count( $projects ) );
	// }
}
