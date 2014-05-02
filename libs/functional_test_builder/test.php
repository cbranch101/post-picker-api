<?php
	error_reporting(E_ALL); 
	ini_set( 'display_errors','1');

	require_once('functional_test_builder.php');
	
	$expected = array(
		'test' => array(
			'Fans' => array(
				'name' => 'mike',
				'age' => 10,
			),
			'Clicks' => array(
				'name' => 'mike',
				'age' => 10,
			),
		),
		'test1' => array(
			'Fans' => array(
				'name' => 'mike',
				'age' => 10,
			),
			'Clicks' => array(
				'name' => 'mike',
				'age' => 10,
			),
		),

	);
	
	$actual = array(
		'test' => array(
			'Fans' => array(
				'name' => 'mike',
				'age' => 20,
			),
			'Clicks' => array(
				'name' => 'mike',
				'age' => 10,
			),
		),
		'test1' => array(
			'Fans' => array(
				'name' => 'mike',
				'age' => 10,
			),
			'Clicks' => array(
				'name' => 'mike',
				'age' => 20,
			),
		),
	);
		
	$output = Test_Builder::confirmExpectedWithDrillDown(array(), $expected, $actual, 2);
	
	echo json_encode($output);
	 
	
	
