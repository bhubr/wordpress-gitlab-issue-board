<!-- <?php

namespace bhubr\wp;

class Gitlab_Issue_Board_Project_Importer {



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
		$client = Gitlab_Issue_Board_API_Client::get_instance();
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
 -->