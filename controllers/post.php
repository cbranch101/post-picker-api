<?php


// get posts
App::$instance->get('/test', function() {	
		
	$randomItems = Data_Pipeline::run('get_random_items');
	
	echo json_encode($randomItems);	
	
	
});

	
