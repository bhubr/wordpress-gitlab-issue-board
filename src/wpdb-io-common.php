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
function record_already_exists( $record, $post_type ) {
	global $wpdb;
	$query = $wpdb->prepare(
		"SELECT * FROM {$wpdb->prefix}posts WHERE post_type='%s' AND comment_count=%d AND guid LIKE '%%%s%%'",
		$post_type, $record['id'], \bhubr\wp\glib\get_domain()
	);
	$results = $wpdb->get_results( $query, ARRAY_A );
	if( ! empty( $results ) ) {
		$post = $results[0];
		error_log( sprintf( "record_already_exists: %d (%s) %s %s", $post['ID'], gettype( $post['ID'] ), $post['post_title'], $post['guid'] ) );
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
			$record[ $post_key ] != $new_attrs[ $attr_key ]
		) {
			error_log( sprintf( '$record[ $post_key ] !== $new_attrs[ $attr_key ] %s %s %s %s', $record[ $post_key ], gettype( $record[ $post_key ] ), $new_attrs[ $attr_key ], gettype( $new_attrs[ $attr_key ] ) ) );
			$attrs_updated[ $post_key ] = $new_attrs[ $attr_key ];
		}
	}
	if( ! empty( $attrs_updated ) ) {
		error_log( sprintf( "compare_record_attributes, changed: %s %s", implode( ',', array_keys( $attrs_updated ) ), print_r( $attrs_updated, true ) ) );
	}
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

function import_many_gitlab_objects( $gitlab_objects, $type, $where = [] ) {
	$import_func = "\\bhubr\\wp\\glib\\wpdb_io\\import_one_{$type}";
	$id_status_change_map = [];
	foreach( $gitlab_objects as $record ) {
		list( $wp_id, $status ) = $import_func( $record );
		$id_status_change_map[ $wp_id ] = $status;
	}

	$wp_posts = query_all_records( $type, $where );
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


function query_all_records( $type, $where_criteria ) {
	global $wpdb;
	$select = "SELECT * FROM {$wpdb->prefix}posts";
	$where = "post_type='{$type}' AND post_status IN('publish','draft')";
	foreach ($where_criteria as $key => $value) {
		$quoted_value = is_numeric( $value ) ? $value : "'$value'";
		$where .= " AND $key=$quoted_value";
	}
	error_log("query_all_records: " . "$select WHERE $where");
	$results = $wpdb->get_results(
		"$select WHERE $where", ARRAY_A
	);
	return $results;
}

function query_project_custom_terms( $taxonomy, $wp_project_id ) {
	global $wpdb;
	$metas = $wpdb->get_results(
		"SELECT term_id FROM {$wpdb->prefix}termmeta WHERE meta_key='wp_project_id' and meta_value='$wp_project_id'"
	);
	$term_ids = array_map( function( $meta ) {
		return $meta->term_id;
	}, $metas );
	$where = empty( $term_ids ) ? '' : 'AND term_id IN (' . implode( ',', $term_ids ) . ')';
	$term_tax_entries = $wpdb->get_results(
		"SELECT term_id FROM {$wpdb->prefix}term_taxonomy WHERE taxonomy ='$taxonomy' $where"
	);
	$term_ids = array_map( function( $meta ) {
		return $meta->term_id;
	}, $term_tax_entries );
	$where = empty( $term_ids ) ? '(0)' : '(' . implode( ',', $term_ids ) . ')';
	$terms = $wpdb->get_results(
		"SELECT * FROM {$wpdb->prefix}terms WHERE term_id IN $where", ARRAY_A
	);
	return $terms;
}
