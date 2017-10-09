<?php

namespace bhubr\wp\glib;
use bhubr\wp\Gitlab_Issue_Board_Configurator;

function log_startup() {
	add_action( 'init', '\\bhubr\\wp\\glib\\log_config_status' );
}

function log_config_status() {
	$configurator = Gitlab_Issue_Board_Configurator::get_instance();
	$status_texts = [
		Gitlab_Issue_Board_Configurator::ACCOUNT_NOT_READY_NO_CONFIG =>
			'Not ready / No config',
		Gitlab_Issue_Board_Configurator::ACCOUNT_NOT_READY_HAS_CONFIG =>
			'Not ready / Config but no data',
		Gitlab_Issue_Board_Configurator::ACCOUNT_READY =>
			'Ready / Config and data'
	];
	$status = $configurator->get_account_status();
	echo "Configurator account status: $status => " . $status_texts[ $status ];
}