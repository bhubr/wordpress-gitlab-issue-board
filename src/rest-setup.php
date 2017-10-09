<?php

namespace bhubr\wp\glib\rest;

use bhubr\wp\Gitlab_Issue_Board_API_Client;
use bhubr\wp\glib\wpdb_io;

/**
 * REST setup
 */
function setup() {
	register_routes();
	register_fields();
	add_action('the_content', '\\bhubr\\wp\\glib\\rest\\disable_auto_paragraph', 8);
}



/**
 * Disable automatic paragraph wrapping of content by WP
 */
function disable_auto_paragraph( $content ) {
	global $post;
	if( array_search( $post->post_type, ['project', 'issue'] ) !== false ) {
		remove_action( 'the_content', 'wpautop' );
	}
	return $content;
}

/**
 * REST fields
 */
function register_fields() {
	register_rest_field( 'project',
		'gl_project_id',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_meta_gitlab_project_id',
			'update_callback' => null, //'glib_update_meta_gitlab_project_id',
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
			'update_callback' => null, // [$this, 'wpgli_update_state_meta_cb'],
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

function map_wp_post_fields( $record ) {
	return [
		'id'            => $record['ID'],
		'slug'          => $record['post_name'],
		'type'          => $record['post_type'],
		'status'        => $record['post_status'],
		'date'          => $record['post_date'],
		'date_gmt'      => $record['post_date_gmt'],
		'modified'      => $record['post_modified'],
		'modified_gmt'  => $record['post_modified_gmt'],
		'link'          => get_permalink( $record['ID'] ),
		'gl_project_id' => $record['comment_count'],
		'title'         =>  [
			'rendered'  => $record['post_title']
		],
		'content'       => [
			'rendered'  => $record['post_content']
		],
		'guid'          =>  [
			'rendered'  => $record['guid']
		],
		'_changed'      => $record['_changed']
	];
}

function map_wp_posts_fields( $records ) {
	return array_map( '\\bhubr\\wp\\glib\\rest\\map_wp_post_fields', $records );
}

function sync_gitlab_projects_to_wp() {

	// 1. get projects
	// 2. inject them in db
	$client = Gitlab_Issue_Board_API_Client::get_instance();
	$projects = null;
	try {
		$projects = $client->get_all_projects();
		error_log( 'api client fetched ' . count($projects) . ' projects' );
	} catch( Exception $e ) {
		return new \WP_REST_Response( [ 'error' => $e->getMessage() ], 500 );
	}

	$results = wpdb_io\import_many_projects( $projects );

	$mapped = map_wp_posts_fields( $results );

	return new \WP_REST_Response( $mapped, 200 );
}

/**
 * REST routes
 */
function register_routes() {
    register_rest_route( 'wpglib/v1', '/sync-projects', array(
        'methods' => 'POST',
        'callback' => '\\bhubr\\wp\\glib\\rest\\sync_gitlab_projects_to_wp'
    ));
    register_rest_route( 'wpglib/v1', '/sync-issues', array(
        'methods' => 'POST',
        'callback' => 'pouet_issues',
    ));
}