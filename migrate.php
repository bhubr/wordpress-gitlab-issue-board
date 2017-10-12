<?php

require '../../../wp-load.php';

if ( php_sapi_name() !== "cli" ) {
	exit;
}

use bhubr\wp\glib\wpdb_io;

$issues = wpdb_io\query_all_records( 'issue', [] );

foreach( $issues as $issue ) {
	$wp_id = $issue['ID'];
	$gitlab_id = (int) get_post_meta( $wp_id, 'gl_id', true );
	$gitlab_iid = (int) get_post_meta( $wp_id, 'gl_iid', true );
	$gitlab_pid = (int) get_post_meta( $wp_id, 'gl_pid', true );
	$gitlab_state = (int) get_post_meta( $wp_id, 'gl_state', true );
	printf("%4d %s --- GitLab params - id: %d, iid: %d, pid: %d, state: %s\n", $wp_id, $gitlab_id, $gitlab_iid, $gl_pid, $gl_state );
}