<?php

namespace bhubr\wp;

class Gitlab_Issue_Board_Notifier {

	private $notice = array();


	/**
	 * @var Singleton
	 * @access private
	 * @static
	 */
	 private static $_instance = null;


	/**
	 * Constructor: plug WordPress hooks
	 */
	private function __construct() {

		$this->prepare_admin_notice();
	}


	/**
	 * Create the unique class instance on first call, return it always
	 *
	 * @param void
	 * @return Singleton
	 */
	public static function get_instance() {
	  if( is_null( self::$_instance ) ) {
		  self::$_instance = new Gitlab_Issue_Board_Notifier();
	  }
	  return self::$_instance;
	}


	/**
	 * Prepare admin notices if $_GET contains valid error or success key
	 */
	public function prepare_admin_notice() {
		if( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gitlab-issue-board' ) {
			return;
		}
		if( isset( $_GET['success'] ) ) {
			$messages = array(
				1 => 'Settings saved',
				2 => 'GitLab authorization successful'
			);
			$msg_key = (int)$_GET['success'];
			if( array_key_exists( $msg_key, $messages ) ) {
				$this->notice['status'] = 'success';
				$this->notice['message'] = $messages[ $msg_key ];
			}
		}
		else if( isset( $_GET['error'] ) ) {
			$messages = array(
				1 => 'An authorization error occurred'
			);
			$msg_key = (int)$_GET['error'];
			if( array_key_exists( $msg_key, $messages ) ) {
				$this->notice['status'] = 'error';
				$this->notice['message'] = $messages[ $msg_key ];
			}
		}
		if( ! empty( $this->notice ) ) {
			add_action( 'admin_notices', array( $this, 'show_admin_notice' ) );
		}
	}


	/**
	 * Show admin notice
	 */
	public function show_admin_notice() {
		?>
		<div class="notice notice-<?php echo $this->notice['status']; ?> is-dismissible">
			<p><?php echo $this->notice['message']; ?></p>
		</div>
		<?php
	}

}