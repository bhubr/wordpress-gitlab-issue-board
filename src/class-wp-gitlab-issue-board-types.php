<?php

function wpglib_slug_get_post_meta_cb( $object, $field_name, $request ) {
	return get_post_meta( $object[ 'id' ], $field_name, true );
}

function wpglib_slug_update_post_meta_cb( $value, $object, $field_name ) {

	return update_post_meta( $object->ID, $field_name, $value );
}

function update_meta_gitlab_project_id( $value, $object, $field_name ) {
	throw new Exception( 'should not attempt to update project id' );
}

function get_meta_gitlab_project_id(  $object, $field_name, $request ) {
	global $wpdb;
	$query = $wpdb->prepare( "SELECT comment_count FROM {$wpdb->prefix}posts WHERE ID=%d", $object['id'] );
	$results = $wpdb->get_results( $query, OBJECT );
	$result = array_pop( $results );
	return (int) $result->comment_count;
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

		// THIS is GOOD. Remove bloody auto <p> insertion for my plugin's CPTs. Take that WP!!
		add_action('the_content', function( $content ) {
			global $post;
			if( array_search( $post->post_type, ['project', 'issue'] ) !== false ) {
				remove_action( 'the_content', 'wpautop' );
			}
			return $content;
		}, 8);

		 
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
			'gl_project_id',
			array(
			   'get_callback'    => 'get_meta_gitlab_project_id',
			   'update_callback' => null, //'update_meta_gitlab_project_id',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_id',
			array(
			   'get_callback'    => 'wpglib_slug_get_post_meta_cb',
			   'update_callback' => 'wpglib_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_iid',
			array(
			   'get_callback'    => 'wpglib_slug_get_post_meta_cb',
			   'update_callback' => 'wpglib_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_pid',
			array(
			   'get_callback'    => 'wpglib_slug_get_post_meta_cb',
			   'update_callback' => 'wpglib_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'gl_state',
			array(
			   'get_callback'    => 'wpglib_slug_get_post_meta_cb',
			   'update_callback' => [$this, 'wpgli_update_state_meta_cb'],
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'priority',
			array(
			   'get_callback'    => 'wpglib_slug_get_post_meta_cb',
			   'update_callback' => 'wpglib_slug_update_post_meta_cb',
			   'schema'          => null,
			)
		);
		register_rest_field( 'issue',
			'percent_done',
			array(
			   'get_callback'    => 'wpglib_slug_get_post_meta_cb',
			   'update_callback' => 'wpglib_slug_update_post_meta_cb',
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

	private function get_posts_by_meta( $post_type, $key, $val ) {
		$args = array(
			'post_type'  => $post_type,
		    'meta_query' => array(
		        array(
		            'key' => $key,
		            'value' => $val,
		            'compare' => '=',
		        )
		   ),
		    'posts_per_page' => 200
		);
		$query = new WP_Query($args);
		return $query->get_posts();
	}


	public function has_existing_project( $post_title ) {
		$posts = get_posts( array(
			'post_type' => 'project',
			'guid' => site_url() . $project_id
		) );
		return ! empty( $posts );
	}


	public function import_projects( $projects ) {
		
	}


	public function sync_projects_gitlab_to_wpdb() {

		// 1. get projects
		// 2. inject them in db
		$client = WP_Gitlab_Issue_Board_API_Client::get_instance();
		try {
			$projects = $client->get_all_projects();
		} catch( Exception $e ) {
			header('HTTP/1.0 500 Internal error');
			// var_dump($e->getMessage());
			die('Internal error: ' . $e->getMessage());
		}
		$new_project_ids = array();
		foreach( $projects as $project ) {
			$project_id = $project['id'];
			$slugified_name = str_replace('/', '-', $project['path_with_namespace'] );
			echo $slugified_name;
			// $post_title = $project_id . '-' . $slugified_name;
			// if( $this->has_post_by_meta( 'project', 'gl_pid', $project_id ) ) {
			if( $this->has_existing_project( $post_title ) ) {
				continue;
			}

			$post = array(
				'post_type'     => 'project',
				'post_status'   => 'publish',
				'post_title'    => $slugified_name,
				'post_date'   => $project['created_at']
			);
			$id = wp_insert_post( $post );
			$updated_post = array(
				'ID'   => $id,
				'guid' => site_url() . 'project/' . $project_guid
			);
			wp_update_post( $updated_post );
			$new_project_ids[] = $id;
			if( ! $id ) {
				die("could not create post");
			}

			// update_post_meta( $id, 'gl_pid', $project_id );

		}
		$project_posts = get_posts( [ 'post_type' => 'project' ] );
		$data = array_map( function( $post ) use( $new_project_ids ) {
			return array(
				'id' => $post->ID,
				'title' => array( 'rendered' => $post->post_title ),
				'gl_pid' => get_post_meta( $post->ID, 'gl_pid', true ),
				'_is_new' => array_search( $post->ID, $new_project_ids ) !== false
			);
		}, $project_posts );
		die( json_encode( $data ) );
	}


	public function get_existing_issue( $gl_project_id, $gl_issue_iid ) {
		global $wpdb;
		$metas_with_same_iid = $wpdb->get_results(
			"SELECT meta_id, post_id FROM {$wpdb->postmeta} where meta_key='gl_iid' and meta_value='{$gl_issue_iid}'"
		);
		// echo "metas with same iid $gl_issue_iid\n";
		// var_dump($metas_with_same_iid);
		$posts_with_same_iid_ids = array_map( function( $meta ) {
			return $meta->post_id;
		}, $metas_with_same_iid );
		$posts_ids_joined = implode( ',', $posts_with_same_iid_ids );
		$issues_with_same_iid_and_pid = $wpdb->get_results(
			"SELECT meta_id, post_id FROM {$wpdb->postmeta} where meta_key='gl_pid' and meta_value='{$gl_project_id}' AND post_id IN({$posts_ids_joined})"
		);
		// echo "ids joined\n";
		// var_dump($posts_ids_joined);
		// echo "issues with same iid $gl_issue_iid and pid $gl_project_id\n";
		// var_dump($issues_with_same_iid_and_pid);
		error_log( empty( $issues_with_same_iid_and_pid ) ?
			"not found: issue $gl_issue_iid of project $gl_issue_iid" :
			"exists: issue $gl_issue_iid of project $gl_issue_iid"
		 );
		return empty( $issues_with_same_iid_and_pid );
	}


	// TODO: handle update from GitLab also
	public function sync_issues_gitlab_to_wpdb( WP_REST_Request $request ) {
		$body = $request->get_json_params();
		$project_post_id = (int)$body['post_id'];
		$gitlab_pid = (int)get_post_meta( $project_post_id, 'gl_pid', true );
		$client = WP_Gitlab_Issue_Board_API_Client::get_instance();
		$issues = $client->get_all_of_type( 'issue', array(
			'gl_pid' => $gitlab_pid
		) );
		$new_issue_ids = array();


		foreach( $issues as $issue ) {

			$issue_iid = $issue['iid'];
			// if( $existing_entries = $this->get_posts_by_meta( 'issue', 'gl_iid', $issue_iid ) ) {
			// 	$existing_ids = array_map( function( $entry ) {
			// 		return $entry->ID;
			// 	}, $existing_entries);
			// 	continue;
			// }
			if( $this->get_existing_issue( $gitlab_pid, $issue_iid ) ) {
				continue;
			}

			$post = array(
				'post_type'     => 'issue',
				'post_status'   => 'publish',
				'post_title'    => $issue['title'],
				'post_content'  => $issue['description'],
				'post_date'     => $issue['created_at'],
				'post_modified' => $issue['updated_at']
			);
			$id = wp_insert_post( $post );
			if( ! $id ) {
				die("could not create post");
			}
			$new_issue_ids[] = $id;

			update_post_meta( $id, 'wp_pid', $project_post_id );
			update_post_meta( $id, 'gl_id', $issue['id'] );
			update_post_meta( $id, 'gl_iid', $issue['iid'] );
			update_post_meta( $id, 'gl_pid', $issue['project_id'] );
			update_post_meta( $id, 'gl_state', $issue['state'] );

			foreach( $issue['labels'] as $label ) {
				$existing = get_term_by( 'name', $label, 'issue_label' );
				if( ! $existing ) {
					$t_tt_ids = wp_insert_term( $label, 'issue_label' );
				}
			}
			wp_set_post_terms( $id, $issue['labels'], 'issue_label', false );
		}
		// $issue_posts = get_posts( [ 'post_type' => 'issue' ] );
		$issue_posts = $this->get_posts_by_meta( 'issue', 'gl_pid', $gitlab_pid );
		$data = array_map( function( $post ) use( $new_issue_ids ) {
			return array(
				'id' => $post->ID,
				'title' => array( 'rendered' => $post->post_title ),
				'wp_pid' => get_post_meta( $post->ID, 'wp_pid', true ),
				'gl_id' => get_post_meta( $post->ID, 'gl_id', true ),
				'gl_iid' => get_post_meta( $post->ID, 'gl_iid', true ),
				'gl_pid' => get_post_meta( $post->ID, 'gl_pid', true ),
				'gl_state' => get_post_meta( $post->ID, 'gl_state', true ),
				'_is_new' => array_search( $post->ID, $new_issue_ids ) !== false
			);
		}, $issue_posts );
		die( json_encode( $data ) );
	}


}