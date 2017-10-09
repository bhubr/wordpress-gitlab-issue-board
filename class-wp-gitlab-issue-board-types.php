<?php
class WP_Gitlab_Issue_Board_Types {

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

		add_action( 'init', array( $this, 'register_types' ) );
		add_action( 'rest_api_init', array( $this, 'register_rest_fields' ) );
		//The Following registers an api route with multiple parameters. 
		add_action( 'rest_api_init', array( $this, 'add_custom_routes' ) );
		 
	}


	/**
	 * Create the unique class instance on first call, return it always
	 *
	 * @param void
	 * @return Singleton
	 */
	public static function get_instance() {
	  if( is_null( self::$_instance ) ) {
		  self::$_instance = new WP_Gitlab_Issue_Board_Types();
	  }
	  return self::$_instance;
	}


	/**
	 * Register types for the app
	 */
	public function register_types() {
	   register_post_type('project', [
			'labels'       => [
				'name'     => 'Projects',
			],
			'supports'     => [ 'title', 'editor', 'thumbnail' ],
			'show_ui'      => true,
			'show_in_rest' => true
		]);
	   register_post_type('issue', [
			'labels'       => [
				'name'     => 'Issues',
			],
			'supports'     => [ 'title', 'editor', 'thumbnail' ],
			'show_ui'      => true,
			'show_in_rest' => true
		]);
	   register_taxonomy('issue_label', 'issue', [
			'labels'       => [
				'name'     => 'Issue labels',
			],
			'hierarchical' => false,
			'show_ui'      => true,
			'show_in_rest' => true
		]);
	   register_taxonomy('issue_cat', 'issue', [
			'labels'       => [
				'name'     => 'Issue categories',
			],
			'hierarchical' => true,
			'show_ui'      => true,
			'show_in_rest' => true
		]);
	}


	public function register_rest_fields() {
		register_rest_field( 'project',
			'gl_pid',
			array(
			   'get_callback'    => 'wpgli_slug_get_post_meta_cb',
			   'update_callback' => 'wpgli_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_id',
			array(
			   'get_callback'    => 'wpgli_slug_get_post_meta_cb',
			   'update_callback' => 'wpgli_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_iid',
			array(
			   'get_callback'    => 'wpgli_slug_get_post_meta_cb',
			   'update_callback' => 'wpgli_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_pid',
			array(
			   'get_callback'    => 'wpgli_slug_get_post_meta_cb',
			   'update_callback' => 'wpgli_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_state',
			array(
			   'get_callback'    => 'wpgli_slug_get_post_meta_cb',
			   'update_callback' => [$this, 'wpgli_update_state_meta_cb'],
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'priority',
			array(
			   'get_callback'    => 'wpgli_slug_get_post_meta_cb',
			   'update_callback' => 'wpgli_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'percent_done',
			array(
			   'get_callback'    => 'wpgli_slug_get_post_meta_cb',
			   'update_callback' => 'wpgli_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		 );
	}


	public function add_custom_routes(){
	    register_rest_route( 'wpglib/v1', '/sync-projects', array(
	        'methods' => 'POST',
	        'callback' => array( $this, 'sync_projects_gitlab_to_wpdb' ),
	    ));
	}


	private function has_post_by_meta( $post_type, $key, $val ) {
		$args = array(
			'post_type'  => $post_type,
		    'meta_query' => array(
		       array(
		           'key' => $key,
		           'value' => $val,
		           'compare' => '=',
		       )
		   )
		);
		$query = new WP_Query($args);
		return $query->have_posts();
	}


	public function sync_projects_gitlab_to_wpdb() {
		$client = WP_Gitlab_Issue_Board_API_Client::get_instance();
		$projects = $client->get_all_of_type( 'project' );

		foreach( $projects as $project ) {

			$project_id = $project['id'];
			if( $this->has_post_by_meta( 'project', 'gl_pid', $project_id ) ) {
				continue;
			}

			$post = array(
				'post_type'     => 'project',
				'post_status'   => 'publish',
				'post_title'    => $project['path_with_namespace'],
				'post_date'   => $project['created_at']
			);
			$id = wp_insert_post( $post );
			if( ! $id ) {
				die("could not create post");
			}

			update_post_meta( $id, 'gl_pid', $project_id );

		}
		$project_posts = get_posts( [ 'post_type' => 'project' ] );
		$project_ids = array_map( function( $proj_post ) {
			return $proj_post->ID;
		},  $project_posts );
	}



}