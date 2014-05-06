<?php

	class Random_Item extends TransforMap {
		
		protected static $types = array();
		public static $randomNumberGenerator;
		
		public static function getFunction($type, $details) {
			self::setRandomNumberGenerator();
			$selectionType = self::getSelectionType($type);
			$collection = MongORM::for_collection(App::$cache->get('collection_name'));
			return $details['get_from_collection']($collection, $selectionType);
		}
		
		public static function getSelectionTypeFunction($type, $details) {
			$rightIsHigh = App::$cache->get('right_is_high');
			if($type == 'right') {
				$selectionType = $rightIsHigh ? 'high' : 'low';
			} else {
				$selectionType = $rightIsHigh ? 'low' : 'high';
			}
			return $selectionType;
		}
		
		public static function setRandomNumberGenerator() {
			if(self::$randomNumberGenerator == null) {
				self::$randomNumberGenerator = new Random_Number_Generator();
			}
		}
		
		public static function rightType() {
			return array(
				'get_from_collection' => function($collection, $selectionType) {
					$totalItems = App::$cache->get('total_items');
											
					$offset = Random_Selection::getRandomOffset($selectionType);
					
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
