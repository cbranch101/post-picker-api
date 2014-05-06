<?php

	// this is the global state class that holds the necessary variables
	// for building the app. 
	class App {
		
		static $mongoDatabase;
		static $mongoURI;
		static $cache;
		static $instance;
		
		static function setEnvironment($currentEnvironment, $environments, $databases) {
			$databaseType = $environments[$currentEnvironment]['database'];
			$databaseInfo = $databases[$databaseType];
			self::setDatabase($databaseInfo);
		}	
		
		
		static function setDatabase($databaseInfo) {
			self::$mongoDatabase = $databaseInfo['name'];
			self::$mongoURI = $databaseInfo['uri'];
		}	
		
	}
