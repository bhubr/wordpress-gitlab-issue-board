<?php

function clean_taxonomy_terms( $taxonomy ) {
	global $wpdb;

	$terms = get_terms( array(
	    'taxonomy' => $taxonomy,
	    'hide_empty' => false,
	) );

	foreach( $terms as $term ) {
		$term_id = $term->term_id;
		$term_tax_id = $term->term_taxonomy_id;
		$removed_metas = $wpdb->get_results(
			"DELETE FROM {$wpdb->prefix}termmeta WHERE meta_id=$term_id"
		);
		$removed_relationships = $wpdb->get_results(
			"DELETE FROM {$wpdb->prefix}term_relationships WHERE term_taxonomy_id=$term_tax_id"
		);
		$removed_tax = $wpdb->get_results(
			"DELETE FROM {$wpdb->prefix}term_taxonomy WHERE term_id=$term_id"
		);
		$removed_terms = $wpdb->get_results(
			"DELETE FROM {$wpdb->prefix}terms WHERE term_id=$term_id"
		);
		$terms = $wpdb->get_results(
			"SELECT * FROM {$wpdb->prefix}terms WHERE term_id=$term_id"
		);
	}
}

function clean_custom_posts( $post_type ) {
	global $wpdb;

	$posts = get_posts( array(
	    'post_type' => $post_type,
	    'post_status' => 'any',
	) );

	foreach( $posts as $post ) {
		$post_id = $post->ID;
		$removed_posts = $wpdb->get_results(
			"DELETE FROM {$wpdb->prefix}postmeta WHERE post_id=$post_id"
		);
		$removed_relationships = $wpdb->get_results(
			"DELETE FROM {$wpdb->prefix}term_relationships WHERE object_id=$post_id"
		);
	}
	$removed_posts = $wpdb->get_results(
		"DELETE FROM {$wpdb->prefix}posts WHERE post_type='$post_type'"
	);
}
