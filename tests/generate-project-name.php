<?php
function pick_from_array( $array ) {
	$cnt = count( $array );
	return $array[ rand( 0, $cnt - 1 ) ];
}

function generate_project_name() {
	$adjectives = require('adjectives.php');
	$monsters = require('monsters.php');
	return ucfirst( pick_from_array( $monsters ) ) . ' / ' .
		pick_from_array( $adjectives ) . ' ' .
		pick_from_array( $monsters );
}