<?php
/*
 Plugin Name: GitLab Issue Board
 Version: 0.0.1
 Plugin URI: https://developpeur-web-toulouse.fr/wordpress/gitlab-issue-board/
 Description: Shows a Trello-like GitLab issue board in WordPress dashboard
 Author: Benoît Hubert
 Author URI: https://developpeur-web-toulouse.fr/
 License: GPL2
 License URI: https://www.gnu.org/licenses/gpl-2.0.html
 Text Domain: wpglib
 */

class WP_Gitlab_Issue_Board {


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
		add_action( 'admin_menu', array( $this, 'register_board_app_page_menu' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_board_app_assets' ) );
	}


	/**
	 * Create the unique class instance on first call, return it always
	 *
	 * @param void
	 * @return Singleton
	 */
	public static function get_instance() {
	  if( is_null( self::$_instance ) ) {
		  self::$_instance = new WP_Gitlab_Issue_Board();
	  }
	  return self::$_instance;
	}


	/**
	 * Enqueue the CSS&JS for the issue board app
	 */
	function load_board_app_assets() {
		if( ! isset( $_GET['page'] ) || $_GET['page'] !== 'gitlab-issue-board' ) {
			return;
		}
		$templates_url = plugins_url( 'templates/', __FILE__ );
		echo '<script type="text/javascript">window.templatesRoot = "' . $templates_url . '"; window.siteRoot = "' . site_url() . '";</script>';
		wp_enqueue_script( 'wpglib_vendor', plugins_url( 'js/wpglib-vendor.bundle.js', __FILE__ ), array( 'jquery' ), '1.0.0' );
		wp_enqueue_script( 'wpglib_app', plugins_url( 'js/wpglib-app.bundle.js', __FILE__ ), array( 'wpglib_vendor' ), '1.0.0' );
		wp_enqueue_style( 'wpglib_styles', plugins_url( 'css/wpglib-styles.min.css', __FILE__ ), array(), '1.0.0' );
		wp_localize_script( 'wpglib_app', 'wpApiSettings', array(
			'root' => esc_url_raw( rest_url() ),
			'nonce' => wp_create_nonce( 'wp_rest' )
		) );
	}


	/**
	 * Register the board app in the menu.
	 */
	public function register_board_app_page_menu() {
		add_menu_page(
			__( 'Gitlab Issue Board', 'wpglib' ),
			'Gitlab Issue Board',
			'manage_options',
			'gitlab-issue-board',
			array( $this, 'gitlab_issue_board_app' ),
			plugins_url( 'gitlab-issue-board/images/gitlab-logo.png' ),
			6
		);
	}


	/**
	 * Show the Angular app
	 */
	public function gitlab_issue_board_app() {
	?>
		<h3>Gitlab Issue Board</h3>
		<a href="#!/">Board</a> | <a href="#!/tools">Tools</a>
		<div ng-app="WordPressGitlabIssueBoard" id="wp-gitlab-issues-app">
			<ui-view></ui-view>
		</div>
	<?php }

}

// Instantiate the plugin
$plugin = WP_Gitlab_Issue_Board::get_instance();