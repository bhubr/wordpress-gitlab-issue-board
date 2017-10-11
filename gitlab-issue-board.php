<?php
/*
 Plugin Name: GitLab Issue Board
 Version: 0.0.3
 Plugin URI: https://developpeur-web-toulouse.fr/wordpress/gitlab-issue-board/
 Description: Shows a Trello-like GitLab issue board in WordPress dashboard
 Author: Benoît Hubert
 Author URI: https://developpeur-web-toulouse.fr/
 License: GPL2
 License URI: https://www.gnu.org/licenses/gpl-2.0.html
 Text Domain: wpglib
 */
namespace bhubr\wp;
error_log('begin wpglib');

define( 'WPGLIB_DEBUG', false );
require 'vendor/autoload.php';
require 'src/class-wp-gitlab-issue-board-configurator.php';
require 'src/class-wp-gitlab-issue-board-notifier.php';
require 'src/class-wp-gitlab-issue-board-client.php';
require 'src/functions.php';
require 'src/register-types.php';
require 'src/rest-setup.php';
require 'src/wpdb-io-issues.php';
require 'src/wpdb-io-projects.php';
require 'src/wpdb-io-common.php';

// add_action( 'init', function() {
// 	echo "\n\n############## DUMP INCOMING BODY\n\n";
// 	var_dump(file_get_contents('php://input'));
// }, 1 );

class Gitlab_Issue_Board {


	/**
	 * Configurator
	 */
	private $configurator = null;


	/**
	 * Notifier
	 */
	private $notifier = null;


	/**
	 * Types
	 */
	private $types = null;


	/**
	 * GitLab Client
	 */
	private $gitlab_client = null;


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

		$this->configurator = Gitlab_Issue_Board_Configurator::get_instance();
		$this->notifier = Gitlab_Issue_Board_Notifier::get_instance();
		// $this->types = Gitlab_Issue_Board_Types::get_instance();
		$this->gitlab_client = Gitlab_Issue_Board_API_Client::get_instance();

		add_action( 'init', '\\bhubr\\wp\\glib\\register_types' );
		add_action( 'rest_api_init', '\\bhubr\\wp\\glib\\rest\\setup' );
		if( WP_DEBUG && WPGLIB_DEBUG ) {
			require 'src/log-startup.php';
			glib\log_startup();
		}

		add_action( 'init', function() {
			if( $this->configurator->is_ready() ) {
				$this->gitlab_client->set_access_token(
					$this->configurator->get_access_token()
				);
				$this->gitlab_client->set_domain(
					$this->configurator->get_domain()
				);
			}
		}, 10 );
	}


	/**
	 * Create the unique class instance on first call, return it always
	 *
	 * @param void
	 * @return Singleton
	 */
	public static function get_instance() {
	  if( is_null( self::$_instance ) ) {
		  self::$_instance = new Gitlab_Issue_Board();
	  }
	  return self::$_instance;
	}


	/**
	 * Enqueue the CSS&JS for the issue board app
	 */
	public function load_board_app_assets() {
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
	 * Show the Angular app OR the config form if account not set up
	 */
	public function gitlab_issue_board_app() {

		// Account is ready, that is:
		// 1. We have provided GitLab app params,
		// 2. We got an access token and user data from GitLab
		if( $this->configurator->is_ready() ) {
			$this->display_board_app();
		}
		// Account not ready. We use the same form but different parameters.
		else {
			$this->configurator->display_config_form();
		}

	}


	/**
	 * Actually show the Angular app
	 */
	public function display_board_app() {
	?>
		<h1>GitLab Issue Board</h1>
		<div ng-app="WordPressGitlabIssueBoard" id="wp-gitlab-issues-app">
		<a href="#!/">Board</a> | <a href="#!/tools">Tools</a> | <a href="#!/sync-projects">Sync projects</a>
			<ui-view></ui-view>
		</div>
	<?php
	}

}

// Instantiate the plugin
$plugin = Gitlab_Issue_Board::get_instance();