<?php
class WP_Gitlab_Issue_Board_API_Client {

	/**
	 * @var Singleton
	 * @access private
	 * @static
	 */
	private static $_instance = null;


	private $client;


	private $domain;


	private $access_token;

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


	public function get_client() {
		return $this->client;
	}


	public function set_domain( $domain ) {
		$this->domain = $domain;
	}

	public function set_access_token( $access_token ) {
		$this->access_token = $access_token;
	}


	public function init_gitlab_client() {
		if( $this->client ) {
			return;
		}
		$this->client = \Gitlab\Client::create($this->domain)
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


	public function get_issue_page( $page, $args ) {
		$this->init_gitlab_client();
		return $this->client->api('issues')->all($args['gl_pid'], array(
			'per_page' => 10,
			'page'     => $page
		) );
	}


	public function get_all_projects() {
		$client = $this->get_client();
	    $pager = new \Gitlab\ResultPager($client);
	    $api = $client->api('projects');
	    return $pager->fetchAll($api, 'all');
	}


	public function get_all_of_type( $type, $args = array() ) {
		$page_getter_func = "get_{$type}_page";
		$page = 1;
		$all_items = array();
		$items = array();
		while( count( $items = $this->$page_getter_func( $page, $args ) ) ) {
			echo "$page_getter_func $page";
			// var_dump($all_items);
			$all_items = array_merge( $all_items, $items );
			$page++;
		}
		return $all_items;
	}


}