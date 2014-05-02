<?php

	error_reporting(E_ALL); 
	ini_set( 'display_errors','1');
	require_once('underscore_kit.php');
	
	__kit::initialize();
	
	$conditions = array(
		'greater_than_one' => function($item) {
			return $item['foo'] > 1;
		}
	);
	
	$items = array(
		array(
			'foo' => 1,
		),
		array(
			'foo' => 1,
		),
		array(
			'foo' => 2,
		),
		array(
			'foo' => 2,
		),
	);
	
	$output = __::chain($items, $conditions)
				->filterByConditions($conditions)
				->map(function($item){
					$item['other'] = 1;
					return $item;
				})
			->value();
	
	echo json_encode($output);
	
	
