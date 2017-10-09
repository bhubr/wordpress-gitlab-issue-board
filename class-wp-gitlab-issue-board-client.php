<?php
class WP_Gitlab_Issue_Board_API_Client {

	/**
	 * @var Singleton
	 * @access private
	 * @static
	 */
	 private static $_instance = null;


	 private $client;

	/**
	 * Constructor: plug WordPress hooks
	 */
	private function __construct() {

	}


	/**
	 * Create the unique class instance on first call, return it always
	 *
	 * @param void
	 * @return Singleton
	 */
	public static function get_instance() {
	  if( is_null( self::$_instance ) ) {
		  self::$_instance = new WP_Gitlab_Issue_Board_API_Client();
	  }
	  return self::$_instance;
	}


	public function set_access_token( $access_token ) {
		// die('at'. $access_token);
		$this->access_token = $access_token;
	}


	public function init_gitlab_client() {
		if( $this->client ) {
			return;
		}
		$this->client = \Gitlab\Client::create('https://gitlab.com')
			->authenticate($this->access_token, \Gitlab\Client::AUTH_OAUTH_TOKEN);
	}


	public function get_project_page( $page ) {
		$this->init_gitlab_client();
		if($page > 2) exit;
		return $this->client->api('projects')->all( array(
			'membership' => true,
			'per_page'   => 10,
			'page'       => $page
		) );
	}


	public function get_all_of_type( $type ) {
		$page_getter_func = "get_{$type}_page";
		$page = 1;
		$all_items = array();
		$items = array();
		while( count( $items = $this->$page_getter_func( $page ) ) ) {
			$all_items = array_merge( $all_items, $items );
			$page++;
		}
		return $all_items;
	}


}