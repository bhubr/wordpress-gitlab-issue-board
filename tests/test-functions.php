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

namespace bhubr\wp\glib;

require_once( realpath( __DIR__ . '/../src/wpdb-io-projects.php' ) );

/**
 * Sample test case.
 */
class Functions_Test extends \WP_UnitTestCase {

	public function test_get_domain() {
		$this->assertFalse( empty( get_domain() ) );
	}
}