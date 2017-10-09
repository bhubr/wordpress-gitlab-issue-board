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
		"SELECT ID,post_title,post_content,guid FROM {$wpdb->prefix}posts WHERE comment_count=%d AND guid LIKE '%%%s%%'",
		$project['id'], \bhubr\wp\glib\get_domain()
	);
	$results = $wpdb->get_results( $query, ARRAY_A );
	return empty( $results ) ? false : $results[0];
}

function compare_project_attributes( $project_record, $new_attrs ) {
	$attrs_updated = [];
	$key_mapping = [
		'post_title'   => 'name_with_namespace',
		'post_content' => 'description',
		'guid'         => 'web_url'
	];
	// echo "\n\n\n#### compare\n";
	// var_dump($project_record);
	// var_dump($new_attrs);
	foreach( $key_mapping as $post_key => $attr_key ) {
		if( isset( $new_attrs[ $attr_key ] ) &&
			$project_record[ $post_key ] !== $new_attrs[ $attr_key ]
		) {
			$attrs_updated[ $post_key ] = $new_attrs[ $attr_key ];
		}
	}
	return $attrs_updated;
}

function get_set_attributes_query_part( $attrs ) {
	$updates = array_map( function( $k ) {
		return "$k='%s'";
	}, array_keys( $attrs ) );
	return implode( ',', $updates );
}

function update_project( $project, $attrs ) {
	global $wpdb;
	$set_attrs = get_set_attributes_query_part( $attrs );
	$query = $wpdb->prepare(
		"UPDATE {$wpdb->prefix}posts SET {$set_attrs} WHERE ID=%d",
		array_merge( array_values( $attrs ), [ $project['ID'] ] )
	);
	$wpdb->query( $query );
}

/**
 * Inject a project into db if it does not exist
 */
function import_one_project( $project_attrs ) {
	$existing_project = false;
	if( $existing_project = project_already_exists( $project_attrs ) ) {
		if( $updated_attrs = compare_project_attributes( $existing_project, $project_attrs ) ) {
			update_project( $existing_project, $updated_attrs );
			return [ $existing_project['ID'], 'updated' ];
		}
		else {
			return [ $existing_project['ID'], 'unchanged' ];
		}
	}
	$id = wp_insert_post( [
		'post_type'     => 'project',
		'post_status'   => 'publish',
		'post_title'    => $project_attrs['name_with_namespace'],
		'post_content'  => $project_attrs['description'],
		'guid'          => $project_attrs['web_url']
	] );
	global $wpdb;
	$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}posts SET comment_count=%d WHERE ID=%d", $project_attrs['id'], $id );
	$wpdb->query( $query );
	return [ $id, 'created' ];
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
