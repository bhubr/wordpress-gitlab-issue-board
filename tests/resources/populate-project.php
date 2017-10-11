<?php
phpinfo();
// connexion
error_reporting(E_ALL);
ini_set('display_errors', 1);

// require '../../vendor/autoload.php';
require '../../vendor/mustache/mustache/src/Mustache/Autoloader.php';
Mustache_Autoloader::register();
$mu = new Mustache_Engine;
$mdb = new MongoClient();
exit;
// $template = file_get_contents('project-template.json');
// $data = [
// 	'domain' => 'http://localhost:3000'
// 	'user' => [
// 		'id'       => 77,
// 		'name'     => 'Swamp monster',
// 		'username' => 'swampmonster'
// 	],
// 	'project' => [
// 		'name' => 'narrow-minded yeti',
// 		'slug' => 'narrow-minded-yeti'
// 	]
// ];
// echo $template;
// echo $mdb->render( $template, $data );
// die();
// // sélection d'une base de données
// $db = $mdb->selectDB("gitlabapi");

// // sélectionne une collection (analogue à une table de base de données relationnelle)
// $collection = $db->projects;

// // ajoute un enregistrement
// $document = array( "title" => "Calvin and Hobbes", "author" => "Bill Watterson" );
// $collection->insert($document);

// // ajoute un autre enregistrement, avec une façon différente d'insertion
// $document = array( "title" => "XKCD", "online" => true );
// $collection->insert($document);

// // récupère tout de la collection
// $cursor = $collection->find();

// // traverse les résultats
// foreach ($cursor as $document) {
//     echo $document["title"] . "\n";
// }
