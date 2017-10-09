<?php
require realpath( __DIR__ . '/../../../../wp-load.php' );
require 'generate-project-name.php';
require 'utils.php';

// do_action('init');
echo "\n\n";
for( $i = 0 ; $i < 15 ; $i++ ) {
	$title = generate_project_name();
	$bits = explode(' / ', $title);
	$name_with_ns = sanitize_title($bits[0]) . '/' . sanitize_title($bits[1]);
	printf("%80s %50s\n", $title, $name_with_ns);
}

// fake_n_projects(10);