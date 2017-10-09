<?php
namespace bhubr\wp\glib;

function register_types() {
	register_post_type('project', [
		'labels'       => [
			'name'     => 'Projects',
		],
		'supports'     => [ 'title', 'editor', 'thumbnail' ],
		'show_ui'      => true,
		'show_in_rest' => true
	]);
	register_post_type('issue', [
		'labels'       => [
			'name'     => 'Issues',
		],
		'supports'     => [ 'title', 'editor', 'thumbnail' ],
		'show_ui'      => true,
		'show_in_rest' => true
	]);
	register_taxonomy('issue_label', 'issue', [
		'labels'       => [
			'name'     => 'Issue labels',
		],
		'hierarchical' => false,
		'show_ui'      => true,
		'show_in_rest' => true
	]);
	register_taxonomy('issue_cat', 'issue', [
		'labels'       => [
			'name'     => 'Issue categories',
		],
		'hierarchical' => true,
		'show_ui'      => true,
		'show_in_rest' => true
	]);
}