<?php

require_once 'gitlab-api-settings.php';

function setup_client() {
	$client_wrapper = WP_Gitlab_Issue_Board_API_Client::get_instance();
	$client_wrapper->set_domain( GITLAB_DOMAIN );
	$client_wrapper->set_access_token( GITLAB_ACCESS_TOKEN );
	$client_wrapper->init_gitlab_client();
}

function get_client() {
	return WP_Gitlab_Issue_Board_API_Client::get_instance()->get_client();
}

function remove_all_projects() {
	$client_wrapper = WP_Gitlab_Issue_Board_API_Client::get_instance();
	$client = $client_wrapper->get_client();

    $pager = new \Gitlab\ResultPager($client);
    $api = $client->api('projects');
    $projects = $pager->fetchAll($api, 'all');
    // $result = $pager->fetch($api, 'all');
    // var_dump(count( $result) );
	foreach( $projects as $p ) {
		$client->projects()->remove( $p['id'] );
	}
}

function fake_n_projects( $n ) {
	$faker = Faker\Factory::create();
	$client = get_client();

	for( $i = 0 ; $i < $n ; $i++ ) {
		# Creating a new project
		$project = \Gitlab\Model\Project::create(
			$client, generate_project_name(), array(
				'description' => $faker->text,
				'issues_enabled' => false
			)
		);
	}
}

function pick_array_keys( $array, $keys ) {
	$output = [];
	foreach( $keys as $k ) {
		if( isset( $array[ $k ] ) ) {
			$output[ $k ] = $array[ $k ];
		}
	}
	return $output;
}