<?php

namespace bhubr\wp\glib\wpdb_io;


/**
 * Calls the generic attributes comparison func with mappings specific to
 * the project Custom Post Type
 */
function compare_project_attributes( $project_record, $new_attrs ) {
	$key_mapping = [
		'post_title'   => 'name_with_namespace',
		'post_content' => 'description',
		'guid'         => 'web_url'
	];
	return compare_record_attributes( $project_record, $new_attrs, $key_mapping );
}

/**
 * Inject a project into db if it does not exist
 */
function import_one_project( $project_attrs ) {
	$existing_project = false;
	if( $existing_project = record_already_exists( $project_attrs, 'project' ) ) {
		if( $updated_attrs = compare_project_attributes( $existing_project, $project_attrs ) ) {
			update_record( $existing_project, $updated_attrs );
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
	return import_many_gitlab_objects( $gitlab_projects, 'project' );
}
