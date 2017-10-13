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

add_action('save_post', '\\bhubr\\wp\\glib\\rest\\save_issue_callback');

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
	register_rest_field( 'issue_cat',
	    'wp_project_id',
	    array(
	        'get_callback'    => '\\bhubr\\wp\\glib\\rest\\issue_cat_get_wp_project_id_field',
	        'update_callback' => '\\bhubr\\wp\\glib\\rest\\issue_cat_update_wp_project_id_field',
	        'schema' => null
	    )
	);
	register_rest_field( 'issue_label',
	    'wp_project_id',
	    array(
	        'get_callback'    => '\\bhubr\\wp\\glib\\rest\\issue_cat_get_wp_project_id_field',
	        'update_callback' => '\\bhubr\\wp\\glib\\rest\\issue_cat_update_wp_project_id_field',
	        'schema' => null
	    )
	);
}

function issue_cat_get_wp_project_id_field(  $object, $meta_key ) {
	$wp_pid_meta = get_term_meta( $object['id'], $meta_key, true );
	return empty( $wp_pid_meta ) ? null : (int) $wp_pid_meta;
}

function issue_cat_update_wp_project_id_field( $meta_value, $term, $meta_key ) {
	update_term_meta( $term->term_id, $meta_key, $meta_value );
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
function get_object_gitlab_id( $object, $field_name, $request ) {
	return get_db_record_field( $object['id'], 'comment_count' );
}

function get_issue_iid( $object, $field_name, $request ) {
	return get_db_record_field( $object['id'], 'menu_order' );
}

function get_issue_wp_post_id( $object, $field_name, $request ) {
	return get_db_record_field( $object['id'], 'post_parent' );
}

function map_wp_post_fields( $record ) {
	$mapped = [
		'id'            => (int) $record['ID'],
		'slug'          => $record['post_name'],
		'type'          => $record['post_type'],
		'status'        => $record['post_status'],
		'date'          => $record['post_date'],
		'date_gmt'      => $record['post_date_gmt'],
		'modified'      => $record['post_modified'],
		'modified_gmt'  => $record['post_modified_gmt'],
		'link'          => get_permalink( $record['ID'] ),
		'title'         =>  [
			'rendered'  => $record['post_title']
		],
		'content'       => [
			'rendered'  => $record['post_content']
		],
		'guid'          =>  [
			'rendered'  => $record['guid']
		]
	];

	if( isset( $record['_changed'] ) ) {
		$mapped['_changed'] = $record['_changed'];
	}
	return $mapped;
}

function map_wp_issue_term_fields( $record ) {
	$record['count'] = (int) $record['count'];
	$record['parent'] = (int) $record['parent'];
	$record['id'] = (int) $record['term_id'];
	unset( $record ['term_id'] );
	$record['wp_project_id'] = issue_cat_get_wp_project_id_field( $record, 'wp_project_id' );
	return $record;
}

function map_wp_post_fields_project( $record ) {
	return array_merge(
		map_wp_post_fields($record),
		[
			'gl_project_id' => $record['comment_count']
		]
	);
}

function map_wp_post_fields_issue( $record ) {
	$related_terms = [];
	foreach( ['issue_cat', 'issue_label'] as $taxonomy ) {
		$terms = wp_get_post_terms( $record['ID'], $taxonomy );
		$term_ids = array_map( function( $term ) {
			return $term->term_id;
		}, $terms );
		$related_terms[ $taxonomy ] = $term_ids;
	}
	return  array_merge(
		map_wp_post_fields($record),
		$related_terms,
		[
			'wp_project_id' => (int) $record['post_parent'],
			'gl_id' => (int) $record['comment_count'],
			'gl_iid' => (int) $record['menu_order'],
			'gl_state' => get_post_meta( $record['ID'], 'gl_state', true ),
			'gl_project_id' => (int) get_post_meta( $record['ID'], 'gl_project_id', true )
		]
	);
}

function map_wp_issue_terms_fields( $records ) {
	return array_map( '\\bhubr\\wp\\glib\\rest\\map_wp_issue_term_fields', $records );
}


function map_wp_projects_fields( $records ) {
	return array_map( '\\bhubr\\wp\\glib\\rest\\map_wp_post_fields_project', $records );
}

function map_wp_issues_fields( $records ) {
	return array_map( '\\bhubr\\wp\\glib\\rest\\map_wp_post_fields_issue', $records );
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
	$mapped = map_wp_projects_fields( $results );

	return new \WP_REST_Response( $mapped, 200 );
}

function sync_gitlab_issues_to_wp( \WP_REST_Request $request ) {
	global $disable_save_hook;
	$disable_save_hook = true;

	// 1. get issues
	// 2. inject them in db
	$params = $request->get_url_params();
	$client = Gitlab_Issue_Board_API_Client::get_instance();
	$issues = null;
	try {
		// var_dump($params);
		$issues = $client->get_all_issues( $params['id'] );
		// var_dump($issues);
	} catch( Exception $e ) {
		return new \WP_REST_Response( [ 'error' => $e->getMessage() ], 500 );
	}

	$results = wpdb_io\import_many_issues( $issues, $params['id'] );
	$mapped = map_wp_issues_fields( $results );

	return new \WP_REST_Response( $mapped, 200 );
}

function cleanup_posts_terms( \WP_REST_Request $request ) {
	require 'cleanup.php';
	foreach( ['issue_cat', 'issue_label'] as $taxonomy ) {
		clean_taxonomy_terms( $taxonomy );
	}
	foreach( ['issue', 'project'] as $post_type ) {
		clean_custom_posts( $post_type );
	}
	return new \WP_REST_Response( ['success' => true], 200 );
}

function get_board( \WP_REST_Request $request ) {
	$params = $request->get_url_params();
	$wp_project_id = $params['id'];

	$issues = wpdb_io\query_all_records( 'issue', ['post_parent' => $wp_project_id] );
	$issue_cats = wpdb_io\query_project_custom_terms( 'issue_cat', $wp_project_id );
	$issue_labels = wpdb_io\query_project_custom_terms( 'issue_label', $wp_project_id );
	// $issue_cats = array_filter( function( $term ) {
	// 	return $term[]
	// }, $all_project_terms );

	return new \WP_REST_Response( [
		'issues' => map_wp_issues_fields( $issues ),
		'issueCats' => map_wp_issue_terms_fields( $issue_cats ),
		'issueLabels' => map_wp_issue_terms_fields( $issue_labels )
	], 200 );
}

/**
 * REST routes
 */
function register_routes() {
    register_rest_route( 'wpglib/v1', '/cleanup', array(
        'methods' => 'DELETE',
        'callback' => '\\bhubr\\wp\\glib\\rest\\cleanup_posts_terms'
    ));
    register_rest_route( 'wpglib/v1', '/board/(?P<id>\d+)', array(
        'methods' => 'GET',
        'callback' => '\\bhubr\\wp\\glib\\rest\\get_board'
    ));
    register_rest_route( 'wpglib/v1', '/sync-projects', array(
        'methods' => 'POST',
        'callback' => '\\bhubr\\wp\\glib\\rest\\sync_gitlab_projects_to_wp'
    ));
    register_rest_route( 'wpglib/v1', '/sync-issues/(?P<id>\d+)', array(
        'methods' => 'POST',
        'callback' => '\\bhubr\\wp\\glib\\rest\\sync_gitlab_issues_to_wp'
    ));
}

function get_parsed_raw_body() {
	$body_text = file_get_contents('php://input');
	$body_json = json_decode( $body_text, true );
	if( empty( $body_json ) ) {
		throw new \Exception( 'not a json body ' . $_SERVER['REQUEST_METHOD'] . print_r( $body_text, true ) );
	}
	return $body_json;
}

function save_issue_callback( $post_id ) {
	global $disable_save_hook;
	if( $disable_save_hook ) {
		return;
	}
	if( 'issue' !== get_post_type( $post_id ) ) {
		return;
	}
	$post = get_post( $post_id );
	if( $post->post_status !== 'publish' ) {
		return;
	}
	// echo "parent: " . $post->post_parent . "\n";
	// var_dump($_REQUEST);
	$body = get_parsed_raw_body();
	$post_parent_id = (int) $body['post_parent'];
	$parent_project_post = get_post( $post_parent_id );
	$gitlab_iid = $post->menu_order;
	$client_wrapper = Gitlab_Issue_Board_API_Client::get_instance();
	$client = $client_wrapper->get_client();
	// var_dump($parent_project_post);
	if( empty( $gitlab_iid ) ) {
		try {
			$new_issue = $client->api('issues')->create( $parent_project_post->comment_count, array(
				'title'       => $post->post_title,
				'description' => $post->post_content
			) );
			global $wpdb;
			$query = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}posts SET comment_count=%d,menu_order=%d,post_parent=%d WHERE ID=%d",
				$new_issue['id'], $new_issue['iid'], $post_parent_id, $post_id );
			$wpdb->query( $query );

			update_post_meta( $post_id, 'gl_project_id', $new_issue['project_id'] );
			update_post_meta( $post_id, 'gl_state', $new_issue['state'] );
		} catch( Exception $e ) {
			header('HTTP/1.0 500 Internal Server Error');
			die( 'Error 500 - ' . $e->getMessage() );
		}

	}
	else {
		try {
			$client->api('issues')->update( $parent_project_post->comment_count, $gitlab_iid, array(
				'title'       => $post->post_title,
				'description' => $post->post_content
			) );
		} catch( Exception $e ) {
			header('HTTP/1.0 500 Internal Server Error');
			die( 'Error 500 - ' . $e->getMessage() );
		}
	}
}