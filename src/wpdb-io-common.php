<?php

namespace bhubr\wp\glib\wpdb_io;

/**
 * Check that the record (project or issue) doesn't already exist in DB.
 * To be called before importing the project or issue.
 * Match criteria:
 *  - guid: should be the web URL e.g. http://gitlab.example.com/root/shivering-raven-spirit
 *    or http://gitlab.example.com/root/shivering-raven-spirit/issues/7
 *  - comment_count (bigint 20, ok for storing GitLab IDs)
 */
function record_already_exists( $record ) {
	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}posts WHERE comment_count=%d AND guid LIKE '%%%s%%'",
		$record['id'], \bhubr\wp\glib\get_domain()
	);
	$results = $wpdb->get_results( $query, ARRAY_A );
	if( ! empty( $results ) ) {
		$post = $results[0];
		error_log( sprintf( "record_already_exists: %d %s %s", $post['ID'], $post['post_title'], $post['guid'] ) );
	}
	return empty( $results ) ? false : $results[0];
}

/**
 * Compare an existing WP_Post record's attributes to the newer attributes
 * it is about to be updated with
 */
function compare_record_attributes( $record, $new_attrs, $key_mapping ) {
	$attrs_updated = [];

	foreach( $key_mapping as $post_key => $attr_key ) {
		if( isset( $new_attrs[ $attr_key ] ) &&
			$record[ $post_key ] !== $new_attrs[ $attr_key ]
		) {
			$attrs_updated[ $post_key ] = $new_attrs[ $attr_key ];
		}
	}
	// if( ! empty( $attrs_updated ) ) {
	// 	error_log( sprintf( "compare_record_attributes, changed: %s", implode( ',', array_keys( $attrs_updated ) ) ) );
	// }
	return $attrs_updated;
}

/**
 * Get the part of the UPDATE SQL between SET and WHERE
 */
function get_set_attributes_query_part( $attrs ) {
	$updates = array_map( function( $k ) {
		return "$k='%s'";
	}, array_keys( $attrs ) );
	return implode( ',', $updates );
}

/**
 * Perform an update query
 */
function update_record( $wp_post, $attrs ) {
	global $wpdb;
	$set_attrs = get_set_attributes_query_part( $attrs );
	$query = $wpdb->prepare(
		"UPDATE {$wpdb->prefix}posts SET {$set_attrs} WHERE ID=%d",
		array_merge( array_values( $attrs ), [ $wp_post['ID'] ] )
	);
	$wpdb->query( $query );
}

function import_many_gitlab_objects( $gitlab_objects, $type ) {
	$import_func = "\\bhubr\\wp\\glib\\wpdb_io\\import_one_{$type}";
	$id_status_change_map = [];
	foreach( $gitlab_objects as $record ) {
		list( $wp_id, $status ) = $import_func( $record );
		$id_status_change_map[ $wp_id ] = $status;
	}

	$wp_posts = query_all_records( $type );
	foreach ( $wp_posts as $idx => $p ) {
		if( ! isset( $id_status_change_map[ $p['ID'] ] ) ) {
			error_log( sprintf( "not found, trash: %d  %s", $p['ID'], $p['post_title'], $p['guid'] ) );

			update_record( $p, ['post_status' => 'trash'] );
			$wp_posts[ $idx ]['post_status'] = 'trash';
			$wp_posts[ $idx ]['_changed'] = 'deleted';
		}
		else {
			$wp_posts[ $idx ]['_changed'] = $id_status_change_map[ $p['ID'] ];
		}
	}

	return $wp_posts;
}


function query_all_records( $type ) {
	global $wpdb;
	$results = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}posts WHERE post_type='{$type}' AND post_status IN('publish','draft')", ARRAY_A
	);
	return $results;
}



