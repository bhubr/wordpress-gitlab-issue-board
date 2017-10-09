<?php

namespace bhubr\wp\glib\wpdb_io;


/**
 * Check that the project doesn't already exist in DB.
 * To be called before importing the project.
 * Match criteria:
 *  - guid (should be the web URL e.g. http://gitlab.example.com/root/shivering-raven-spirit)
 *  - comment_count (bigint 20, ok for storing GitLab IDs)
 */
function project_already_exists( $project ) {
	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT ID FROM {$wpdb->prefix}posts WHERE comment_count=%d AND guid LIKE '%%%s%%'",
		$project['id'], \bhubr\wp\glib\get_domain()
	);
	$results = $wpdb->get_results( $query, OBJECT );
	return ! empty( $results );
}



/**
 * Inject a project into db if it does not exist
 */
function import_one_project( $project ) {
	if( project_already_exists( $project ) ) {
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
function import_many_projects( $projects ) {

	// Will help us sort out the new projects from the old
	$new_project_ids = [];
	
	foreach( $projects as $project ) {

		$id_or_false = import_one_project( $project );
		if( $id_or_false ) {
			$new_project_ids[] = $id_or_false;
		}

	}

	$all_projects = query_all_projects();
	foreach ( $all_projects as $p ) {
		$p['_is_new'] = array_search( $p['ID'], $new_project_ids ) !== false;
	}

	return $all_projects;

}


function query_all_projects() {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}posts WHERE post_type='project'", ARRAY_A
	);
	return $results;
}
