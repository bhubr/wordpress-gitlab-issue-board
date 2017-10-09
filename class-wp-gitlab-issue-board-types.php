<?php

function wpgli_slug_get_post_meta_cb( $object, $field_name, $request ) {
	return get_post_meta( $object[ 'id' ], $field_name, true );
}

function wpgli_slug_update_post_meta_cb( $value, $object, $field_name ) {
	return update_post_meta( $object->ID, $field_name, $value );
}

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
	    register_rest_route( 'wpglib/v1', '/sync-issues', array(
	        'methods' => 'POST',
	        'callback' => array( $this, 'sync_issues_gitlab_to_wpdb' ),
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
		// var_dump($query->get_queried_object_id());
		return $query->have_posts();
	}


	public function sync_projects_gitlab_to_wpdb() {
		$client = WP_Gitlab_Issue_Board_API_Client::get_instance();
		$projects = $client->get_all_of_type( 'project' );
		$new_project_ids = array();

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
			$new_project_ids[] = $id;
			if( ! $id ) {
				die("could not create post");
			}

			update_post_meta( $id, 'gl_pid', $project_id );

		}
		$project_posts = get_posts( [ 'post_type' => 'project' ] );
		$data = array_map( function( $post ) use( $new_project_ids ) {
			return array(
				'id' => $post->ID,
				'title' => array( 'rendered' => $post->post_title ),
				'gl_pid' => get_post_meta( $post->ID, 'gl_pid', true ),
				'new' => array_search( $post->ID, $new_project_ids ) !== false
			);
		}, $project_posts );
		die( json_encode( $data ) );
	}

	public function sync_issues_gitlab_to_wpdb( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		$post_id = (int)$body['post_id'];
		$gitlab_pid = (int)get_post_meta( $post_id, 'gl_pid', true );
		$client = WP_Gitlab_Issue_Board_API_Client::get_instance();
		$issues = $client->get_all_of_type( 'issue', array(
			'gl_pid' => $gitlab_pid
		) );
		die( json_encode( $issues ) );
	}


}