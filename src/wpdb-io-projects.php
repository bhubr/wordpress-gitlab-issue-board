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
	if( ! empty( $results ) ) {
		$post = $results[0];
		error_log( sprintf( "project_already_exists: %d %s %s", $post['ID'], $post['post_title'], $post['guid'] ) );
	}
	return empty( $results ) ? false : $results[0];
}

function compare_project_attributes( $project_record, $new_attrs ) {
	$attrs_updated = [];
	$key_mapping = [
		'post_title'   => 'name_with_namespace',
		'post_content' => 'description',
		'guid'         => 'web_url'
	];
	foreach( $key_mapping as $post_key => $attr_key ) {
		if( isset( $new_attrs[ $attr_key ] ) &&
			$project_record[ $post_key ] !== $new_attrs[ $attr_key ]
		) {
			$attrs_updated[ $post_key ] = $new_attrs[ $attr_key ];
		}
	}
	if( ! empty( $attrs_updated ) ) {
		error_log( sprintf( "compare_project_attributes, changed: %s", implode( ',', array_keys( $attrs_updated ) ) ) );
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
	error_log( sprintf( "create post: %d %s %s", $id, $project_attrs['name_with_namespace'], $project_attrs['web_url'] ) );
	global $wpdb;
	$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}posts SET comment_count=%d WHERE ID=%d", $project_attrs['id'], $id );
	$wpdb->query( $query );
	return [ $id, 'created' ];
}


/**
 * Import several projects
 *
 * @param array $projects an array of projects given back by the GitLab API
 */
function import_many_projects( $gitlab_projects ) {
	$id_status_change_map = [];
	foreach( $gitlab_projects as $project ) {
		list( $wp_id, $status ) = import_one_project( $project );
		$id_status_change_map[ $wp_id ] = $status;
	}

	$wp_projects = query_all_projects();
	foreach ( $wp_projects as $idx => $p ) {
		if( ! isset( $id_status_change_map[ $p['ID'] ] ) ) {
			error_log( sprintf( "not found, trash: %d  %s", $p['ID'], $p['post_title'], $p['guid'] ) );

			update_project( $p, ['post_status' => 'trash'] );
			$wp_projects[ $idx ]['post_status'] = 'trash';
			$wp_projects[ $idx ]['_changed'] = 'deleted';
		}
		else {
			$wp_projects[ $idx ]['_changed'] = $id_status_change_map[ $p['ID'] ];
		}
	}

	return $wp_projects;

}


function query_all_projects() {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}posts WHERE post_type='project' AND post_status IN('publish','draft')", ARRAY_A
	);
	return $results;
}
