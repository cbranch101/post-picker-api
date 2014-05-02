<?php

// load slim
require 'libs/Slim/Slim.php';

\Slim\Slim::registerAutoloader();

$app = new \Slim\Slim();

require_once('includes/app.php');

App::$instance = $app;


// load all other required files
	$loadMap = array(
		'shared' => array(
			'libs' => array(
				'fb_sdk/facebook.php',
				'php_mongorm/php_mongorm.php',
				'transformap/transformap.php',
			),
			'controllers' => array(
				'post.php',
			),
			'includes' => array(
				'config.php',
				'app.php',
			),
		),
	);
		
	// Load underscore as it will be used to load other files
	require_once('libs/underscore_kit/underscore_kit.php');
			
	__kit::initialize();
	
	$currentEnvironment = $_SERVER['SERVER_NAME'];
	
	$environments = array(
		'test' => array(
			'database' => 'live',
		),
		'localhost' => array(
			'database' => 'staging',
		),
		'dev.fbdev.me' => array(
			'database' => 'live',
		),
	);
	
	
	__::each($loadMap['shared'], function($loadPaths, $loadDirectory){
		__::each($loadPaths, function($loadPath) use($loadDirectory){
			require_once($loadDirectory . '/' . $loadPath);
		});
	});
	
	$databases = array(
		'staging' => array(
			'name' => 'dash_staging',
			'uri' => Config::$stagingURI,
		),
		'live' => array(
			'name' => 'dash_live',
			'uri' => Config::$liveURI,
		),
	);
	
	App::setEnvironment($currentEnvironment, $environments, $databases, $branch);
		
	MongORM::connect(App::$mongoDatabase, App::$mongoURI);
	
	$app->run();