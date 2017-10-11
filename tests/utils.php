<?php


require_once 'gitlab-api-settings.php';

function setup_client() {
	$client_wrapper = bhubr\wp\Gitlab_Issue_Board_API_Client::get_instance();
	$client_wrapper->set_domain( GITLAB_DOMAIN );
	$client_wrapper->set_access_token( GITLAB_ACCESS_TOKEN );
	$client_wrapper->init_gitlab_client();
	$configurator = bhubr\wp\Gitlab_Issue_Board_Configurator::get_instance();
	$configurator->set_domain( GITLAB_DOMAIN );
	$configurator->set_host( GITLAB_HOST );
}

function get_client() {
	return bhubr\wp\Gitlab_Issue_Board_API_Client::get_instance()->get_client();
}

function remove_all_projects() {
	$client_wrapper = bhubr\wp\Gitlab_Issue_Board_API_Client::get_instance();
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

function reset_auto_increments() {
	global $wpdb;
	$wpdb->query( "ALTER TABLE {$wpdb->prefix}posts AUTO_INCREMENT = 1" );
	if($wpdb->last_error !== '') :
	    $wpdb->print_error();
	endif;
}

function dump_project_posts( $projects ) {
	echo "\n#### dump_project_posts\n";
	foreach( $projects as $p )  {
		printf("%3d %5d %s\n", $p['id'],  $p['gl_project_id'],  $p['title']['rendered']);
	}
}

function dump_issue_posts( $issues ) {
	echo "\n#### dump_issue_posts\n";
	foreach( $issues as $i )  {
		printf("wp id: %4d, gl id %6d iid: %3d proj. id: %5d title: %s\n", $i['id'], $i['gl_id'], $i['gl_iid'],  $i['gl_project_id'],  $i['title']['rendered']);
	}
}

function pick_post_titles( $posts ) {
	return array_map( function( $post ) {
		return $post['title']['rendered'];
	}, $posts );
}