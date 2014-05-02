<?php


// get posts
App::$instance->get('/test', function() {	
	
	$posts = MongORM::for_collection('movement_processed_posts')
		->find_many()
		->limit(10)
		->as_array();
		
	echo json_encode($posts);		
	
	
});

	
