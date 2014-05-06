<?php

	class Random_Item extends TransforMap {
		
		protected static $types = array();
		
		public static function getFunction($type, $details) {
			$collection = MongORM::for_collection(App::$cache->get('collection_name'));
			return $details['get_from_collection']($collection);
		}
		
		public static function rightType() {
			return array(
				'get_from_collection' => function($collection) {
					$totalItems = App::$cache->get('total_items');
					
												
					$offset = Random_Item::getRandomOffset(.20, .80);
					
					$results = $collection->find_many()
						->find_one()
						->skip($offset - 1)
						->as_array();
					
					return $results;
				},	
			);
		}
		
		public static function getRandomOffset($lowPercentage, $highPercentage) {
			$offsetMin = self::getTotalItemOffset($lowPercentage);
			$offsetMax = self::getTotalItemOffset($highPercentage);
			return rand($offsetMin, $offsetMax);
		}
		
		public static function getTotalItemOffset($percentage) {
			$offset = round(App::$cache->get('total_items') * $percentage, 0);
			return $offset;
		}
		
		public static function leftType() {
			return array(
				'get_from_collection' => function($collection) {
					return array();	
				},	
			);
		}
		
	}
