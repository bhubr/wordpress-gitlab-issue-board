<?php
namespace bhubr\wp\glib;

// We have to restrict access so that only logged-in users can access the data
function register_types() {
	$show_in_rest = is_user_logged_in() || php_sapi_name() === 'cli';

	register_post_type('project', [
		'labels'       => [
			'name'     => 'Projects',
		],
		'supports'     => [ 'title', 'editor', 'thumbnail' ],
		'show_ui'      => true,
		'show_in_rest' => $show_in_rest
	]);
	register_post_type('issue', [
		'labels'       => [
			'name'     => 'Issues',
		],
		'supports'     => [ 'title', 'editor', 'thumbnail' ],
		'show_ui'      => true,
		'show_in_rest' => $show_in_rest
	]);
	register_taxonomy('issue_label', 'issue', [
		'labels'       => [
			'name'     => 'Issue labels',
		],
		'hierarchical' => false,
		'show_ui'      => true,
		'show_in_rest' => $show_in_rest
	]);
	register_taxonomy('issue_cat', 'issue', [
		'labels'       => [
			'name'     => 'Issue categories',
		],
		'hierarchical' => true,
		'show_ui'      => true,
		'show_in_rest' => $show_in_rest
	]);
}