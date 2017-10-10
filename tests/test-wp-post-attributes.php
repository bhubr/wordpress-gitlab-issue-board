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

require 'generate-project-name.php';

/**
 * Check how WordPress handles attributes in wp_insert_post.
 */
class WP_Post_Attributes_Test extends WP_UnitTestCase {

	/**
	 * Insert a post and check that the attributes are what they're supposed to be.
	 */
	function test_wp_insert_post() {

		$id = wp_insert_post( [
			'post_type'     => 'project',
			'post_status'   => 'publish',
			'post_title'    => 'Administrator / shivering raven spirit',
			'guid'          => GITLAB_DOMAIN . '/root/shivering-raven-spirit',
			'post_parent'   => 10,
			'menu_order'    => 7,
			'comment_count' => 667
		] );
		$post = get_post( $id );
		$this->assertEquals( 'Administrator / shivering raven spirit', $post->post_title );
		$this->assertEquals( 'publish', $post->post_status );
		$this->assertEquals( 'project', $post->post_type );
		$this->assertEquals( 10, $post->post_parent );
		$this->assertEquals( 7, $post->menu_order );
		$this->assertEquals( 0, $post->comment_count ); // WP erases this field on post insertion
		$this->assertEquals( GITLAB_DOMAIN . '/root/shivering-raven-spirit', $post->guid );
		global $wpdb;
		$update_result = $wpdb->get_results( "UPDATE {$wpdb->prefix}posts SET comment_count=666 WHERE ID={$post->ID}" );
		$query_results = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}posts WHERE ID={$post->ID}", OBJECT );
		$result = array_pop( $query_results );
		$this->assertEquals( 666, $result->comment_count );
	}


}
