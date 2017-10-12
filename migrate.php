<?php

require '../../../wp-load.php';

if ( php_sapi_name() !== "cli" ) {
	exit;
}

use bhubr\wp\glib\wpdb_io;

global $wpdb;



/// 1 - issue metas and props
$issues = wpdb_io\query_all_records( 'issue', [] );

foreach( $issues as $issue ) {
	$wp_id = $issue['ID'];
	$gitlab_id = (int) get_post_meta( $wp_id, 'gl_id', true );
	$gitlab_iid = (int) get_post_meta( $wp_id, 'gl_iid', true );
	$gitlab_pid = (int) get_post_meta( $wp_id, 'gl_pid', true );
	$gitlab_state = (int) get_post_meta( $wp_id, 'gl_state', true );
	printf(
		"%4d %s --- GitLab params - id: %d, iid: %d, pid: %d, state: %s\n", $wp_id, $issue['post_title'], $gitlab_id, $gitlab_iid, $gitlab_pid, $gitlab_state
	);

	$query = $wpdb->prepare(
		"UPDATE {$wpdb->prefix}posts SET comment_count=%d, menu_order=%d post_parent=%d WHERE ID=%d", $gitlab_id, $gitlab_iid, 110, $wp_id
	);
	$wpdb->query( $query );
	echo "DONE #1 (CPT metas)\n\n";
}


// 2 - postmeta keys
$wpdb->query( "UPDATE {$wpdb->prefix}postmeta SET meta_key='gl_project_id' WHERE meta_key='gl_pid'" );
echo "DONE #2 (meta keys changed)\n\n";


// 3 - terms wp post id
$cats = get_terms( [
	'taxonomy'   => 'issue_cat',
	'hide_empty' => false
] );
$labels = get_terms( [
	'taxonomy'   => 'issue_label',
	'hide_empty' => false
] );
$all_terms = array_merge( $cats, $labels );
foreach( $all_terms as $term ) {
	printf(
		"%4d %s\n", $term->term_id, $term->name
	);
	add_term_meta( $term->term_id, 'wp_project_id', 110, true );
}

echo "DONE #3 (terms project assigned)\n";