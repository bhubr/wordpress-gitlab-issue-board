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


function get_post_meta_cb( $object, $field_name, $request ) {
	$value = get_post_meta( $object[ 'id' ], $field_name, true );
	return is_numeric( $value ) ? (int) $value : $value;
}

function update_post_meta_cb( $value, $object, $field_name ) {
	return update_post_meta( $object->ID, $field_name, $value );
}

/**
 * REST fields
 */
function register_fields() {
	register_rest_field( 'project',
		'gl_project_id',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_object_gitlab_id',
			'update_callback' => null, //'glib_update_meta_gitlab_project_id',
			'schema'          => null,
		)
	);
	register_rest_field( 'issue',
		'gl_id',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_object_gitlab_id',
			'update_callback' => '\\bhubr\\wp\\glib\\rest\\update_post_meta_cb',
			'schema'          => null,
		)
	);
	register_rest_field( 'issue',
		'gl_iid',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_issue_iid',
			'update_callback' => '\\bhubr\\wp\\glib\\rest\\update_post_meta_cb',
			'schema'          => null,
		)
	);
	register_rest_field( 'issue',
		'gl_project_id',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_post_meta_cb',
			'update_callback' => '\\bhubr\\wp\\glib\\rest\\update_post_meta_cb',
			'schema'          => null,
		)
	);
	register_rest_field( 'issue',
		'wp_project_id',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_issue_wp_post_id',
			'update_callback' => '\\bhubr\\wp\\glib\\rest\\update_post_meta_cb',
			'schema'          => null,
		)
	);
	register_rest_field( 'issue',
		'gl_state',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_post_meta_cb',
			'update_callback' => null, // [$this, 'wpgli_update_state_meta_cb'],
			'schema'          => null,
		)
	);
	register_rest_field( 'issue',
		'priority',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_post_meta_cb',
			'update_callback' => '\\bhubr\\wp\\glib\\rest\\update_post_meta_cb',
			'schema'          => null,
		)
	);
	register_rest_field( 'issue',
		'percent_done',
		array(
			'get_callback'    => '\\bhubr\\wp\\glib\\rest\\get_post_meta_cb',
			'update_callback' => '\\bhubr\\wp\\glib\\rest\\update_post_meta_cb',
			'schema'          => null,
		)
	 );
}


function update_meta_gitlab_project_id( $value, $object, $field_name ) {
	throw new Exception( 'should not attempt to update project id' );
}

function get_db_record_field( $record_id, $field ) {
	global $wpdb;
	$query = $wpdb->prepare( "SELECT $field FROM {$wpdb->prefix}posts WHERE ID=%d", $record_id );
	$results = $wpdb->get_results( $query, OBJECT );
	$result = array_pop( $results );
	return (int) $result->$field;
}
function get_object_gitlab_id(  $object, $field_name, $request ) {
	return get_db_record_field( $object['id'], 'comment_count' );
}

function get_issue_iid( $object, $field_name, $request ) {
	return get_db_record_field( $object['id'], 'menu_order' );
}

function get_issue_wp_post_id( $object, $field_name, $request ) {
	return get_db_record_field( $object['id'], 'post_parent' );
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
	} catch( Exception $e ) {
		return new \WP_REST_Response( [ 'error' => $e->getMessage() ], 500 );
	}

	$results = wpdb_io\import_many_projects( $projects );

	$mapped = map_wp_posts_fields( $results );

	return new \WP_REST_Response( $mapped, 200 );
}

function sync_gitlab_issues_to_wp( \WP_REST_Request $request ) {
	// 1. get issues
	// 2. inject them in db
	$params = $request->get_json_params();
	$client = Gitlab_Issue_Board_API_Client::get_instance();
	$issues = null;
	try {
		$issues = $client->get_all_issues( $params['post_id'] );
	} catch( Exception $e ) {
		return new \WP_REST_Response( [ 'error' => $e->getMessage() ], 500 );
	}

	$results = wpdb_io\import_many_issues( $issues );

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
        'callback' => '\\bhubr\\wp\\glib\\rest\\sync_gitlab_issues_to_wp'
    ));
}