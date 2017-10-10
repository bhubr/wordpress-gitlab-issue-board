<?php
require realpath( __DIR__ . '/../../../../wp-load.php' );
require 'generate-project-name.php';
require 'utils.php';

echo "\n\n";
for( $i = 0 ; $i < 15 ; $i++ ) {
	echo generate_issue_title() . "\n";
}

