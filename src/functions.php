<?php

namespace bhubr\wp\glib;
use bhubr\wp\Gitlab_Issue_Board_Configurator;

function get_domain() {
	return Gitlab_Issue_Board_Configurator::get_instance()
		->get_domain();
}

function get_host() {
	return Gitlab_Issue_Board_Configurator::get_instance()
		->get_host();
}

function get_domain_from_web_url( $web_url ) {
	$parsed_url = parse_url( $web_url );
	return $parsed_url['scheme'] . '://' . $parsed_url['host'];
}