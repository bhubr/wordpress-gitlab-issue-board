<?php
class WP_Gitlab_Issue_Board_Project_Importer {

	/**
	 * Check that the project doesn't already exist in DB.
	 * To be called before importing the project.
	 * Match criteria:
	 *  - guid (should be the web URL e.g. http://gitlab.example.com/root/shivering-raven-spirit)
	 *  - comment_count (bigint 20, ok for storing GitLab IDs)
	 */
	public static function already_exists( $project ) {
		global $wpdb;
		$query = $wpdb->prepare(
			"SELECT ID FROM {$wpdb->prefix}posts WHERE comment_count=%d AND guid='%s'",
			$project['id'], $project['web_url']
		);
		$results = $wpdb->get_results( $query, OBJECT );
		return ! empty( $results );
	}


	/**
	 * Inject a project into db if it does not exist
	 */
	public static function import_one( $project ) {
		if( self::already_exists( $project ) ) {
			return false;
		}
		$id = wp_insert_post( [
			'post_type'     => 'project',
			'post_status'   => 'publish',
			'post_title'    => $project['name_with_namespace'],
			'post_content'  => $project['description'],
			'guid'          => $project['web_url']
		] );
		global $wpdb;
		$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}posts SET comment_count=%d WHERE ID=%d", $project['id'], $id );
		$wpdb->query( $query );
		return $id;
	}


	/**
	 * Import several projects
	 */
	public static function import_many( $projects ) {

		// Will help us sort out the new projects from the old
		$new_project_ids = [];
		
		foreach( $projects as $project ) {

			$id_or_false = self::import_one( $projects );
			if( $id_or_false ) {
				$new_project_ids[] = $id_or_false;
			}

		}

		$all_projects = self::query_all();
		foreach ( $all_projects as $p ) {
			$p['_is_new'] = array_search( $p->ID, $new_project_ids ) !== false;
		}

	}


	public static function query_all() {
		global $wpdb;
		$results = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}posts WHERE post_type='project'", ARRAY_A
		);
		return $results;
	}


	public function has_existing_project( $post_title ) {
		$posts = get_posts( array(
			'post_type' => 'project',
			'guid' => site_url() . $project_id
		) );
		return ! empty( $posts );
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

}
