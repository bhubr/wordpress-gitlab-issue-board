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

/**
 * Sample test case.
 */
class Test_Utils_Test extends WP_UnitTestCase {

	public function test_pick_array_keys() {
		$arr = [
			'one'   => 'This is one',
			'two'   => 'This is two',
			'three' => 'This is three',
			'four'  => 'This is four',
			'five'  => 'This is five'
		];
		$out = pick_array_keys( $arr, ['two', 'four', 'five'] );
		// var_dump($out);
		$this->assertEquals( 3, count( $out ) );
		$this->assertEquals( [
			'two'   => 'This is two',
			'four'  => 'This is four',
			'five'  => 'This is five'
		], $out );
	}
}