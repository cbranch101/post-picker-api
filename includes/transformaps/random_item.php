<?php

	class Random_Item {
				
		public static function get($type) {
			self::setRandomNumberGenerator();
			$selectionType = self::getSelectionType($type);
			$collection = MongORM::for_collection(App::$cache->get('collection_name'));
			$totalItems = App::$cache->get('total_items');
			$offset = Random_Selection::getRandomOffset($selectionType);
			$randomItem = Random_Item::getItemWithOffset($collection, $offset);		
			return $randomItem;
		}
		
		public static function getSelectionType($type) {
			$rightIsHigh = App::$cache->get('right_is_high');
			if($type == 'right') {
				$selectionType = $rightIsHigh ? 'high' : 'low';
			} else {
				$selectionType = $rightIsHigh ? 'low' : 'high';
			}
			return $selectionType;
		}
				
		public static function rightType() {
			return array(
				'get_from_collection' => function($collection, $selectionType) {
				},	
			);
		}
				
		public static function getItemWithOffset($collection, $offset) {
			$item = $collection->find_many()
				->find_one()
				->skip($offset - 1)
				->as_array();
				
			return reset($item);
		}
						
		public static function leftType() {
			return array(
				'get_from_collection' => function($collection) {
					return array();	
				},	
			);
		}
		
	}
