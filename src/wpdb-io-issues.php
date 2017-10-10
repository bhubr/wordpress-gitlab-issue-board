<?php

namespace bhubr\wp\glib\wpdb_io;

/**
 * Inject a issue into db if it does not exist
 */
function import_one_issue( $issue_attrs ) {
	$existing_issue = false;
	if( $existing_issue = record_already_exists( $issue_attrs ) ) {
		if( $updated_attrs = compare_issue_attributes( $existing_issue, $issue_attrs ) ) {
			update_record( $existing_issue, $updated_attrs );
			return [ $existing_issue['ID'], 'updated' ];
		}
		else {
			return [ $existing_issue['ID'], 'unchanged' ];
		}
	}
	$id = wp_insert_post( [
		'post_type'     => 'issue',
		'post_status'   => 'publish',
		'post_title'    => $issue_attrs['title'],
		'post_content'  => $issue_attrs['description'],
		'post_parent'   => $issue_attrs['project_id'],
		'menu_order'    => $issue_attrs['iid'],
		'post_date'     => $issue_attrs['created_at'],
		'post_modified' => $issue_attrs['updated_at'],
		'guid'          => $issue_attrs['web_url']
	] );
	if( ! $id ) {
		// die("could not create post");
	}

	global $wpdb;
	$query = $wpdb->prepare( "UPDATE {$wpdb->prefix}posts SET comment_count=%d WHERE ID=%d", $issue_attrs['id'], $id );
	$wpdb->query( $query );

	// update_post_meta( $id, 'gl_id', $issue['id'] );  //=> comment_count
	// update_post_meta( $id, 'gl_iid', $issue['iid'] ); // => menu_order
	// update_post_meta( $id, 'gl_pid', $issue['project_id'] ); // post_parent

	update_post_meta( $id, 'gl_state', $issue_attrs['state'] );  // => meta

	foreach( $issue_attrs['labels'] as $label ) {
		$existing = get_term_by( 'name', $label, 'issue_label' );
		if( ! $existing ) {
			$t_tt_ids = wp_insert_term( $label, 'issue_label' );

			/// SET META FOR TERM (project id)
		}
	}
	wp_set_post_terms( $id, $issue_attrs['labels'], 'issue_label', false );
	// error_log( sprintf( "create post: %d %s %s", $id, $issue_attrs['title'], $issue_attrs['web_url'] ) );
	return [ $id, 'created' ];
}



/**
 * Import several projects
 *
 * @param array $projects an array of projects given back by the GitLab API
 */
function import_many_issues( $gitlab_issues ) {
	return import_many_gitlab_objects( $gitlab_issues, 'issue' );
}

function compare_issue_attributes( $record, $new_attrs ) {
	$key_mapping = [
		'post_title'   => 'title',
		'post_content' => 'description',
		'guid'         => 'web_url',
		'post_parent'   => 'project_id',
		'menu_order'    => 'iid',
	];
	// var_dump($record);
	// var_dump($new_attrs);
	$updated_attrs = compare_record_attributes( $record, $new_attrs, $key_mapping );
	$meta_state = get_post_meta( $record['ID'], 'gl_state', true );
	if( isset( $new_attrs['state'] ) &&
		$meta_state !== $new_attrs['state']
	) {
		$updated_attrs['gl_state'] = $new_attrs['state'];
	}
	return $updated_attrs;
}

	// public function import_issues() {
	// 	// $issues = $this->get_issues();
	// 	// file_put_contents('all_issues.json', json_encode($issues));

	// 	$issues_json = file_get_contents( __DIR__ . '/all_issues.json' );
	// 	$issues = json_decode( $issues_json, true );


	// 	foreach( $issues as $issue ) {
	// 		$post = array(

	// 		);
	// 		$id = wp_insert_post( $post );

	// 	}
	// }