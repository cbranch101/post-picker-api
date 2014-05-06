<?php

	class Random_Item_Handler {
				
		public static function getItem($randomItemType) {
			$selectionType = self::getSelectionType($randomItemType);
			$collection = MongORM::for_collection(App::$cache->get('collection_name'));
			$totalItems = App::$cache->get('total_items');
			$offset = Random_Selection::getRandomOffset($selectionType);
			$randomItem = self::getItemWithOffset($collection, $offset);
			$randomItem = self::processRandomItem($randomItem, $selectionType);		
			return $randomItem;
		}
		
		public static function processRandomItem($randomItem, $selectionType) {
			$processedItem = array();
			$processedItem['metric'] = $randomItem['likes']['value'];
			$processedItem['is_correct'] = $selectionType == 'high' ? true : false;
			$processedItem['message'] = $randomItem['message']['formatted_value'];
			return $processedItem;
		}
				
		public static function getSelectionType($randomItemType) {
			$rightIsHigh = App::$cache->get('right_is_high');
			if($randomItemType == 'right') {
				$selectionType = $rightIsHigh ? 'high' : 'low';
			} else {
				$selectionType = $rightIsHigh ? 'low' : 'high';
			}
			return $selectionType;
		}
				
		public static function getItemWithOffset($collection, $offset) {
			$item = $collection->find_many()
				->find_one(App::$cache->get('facebook_filter_query'))
				->sort(
					array('likes.value' => 1)
				)
				->skip($offset - 1)
				->as_array();
				
			return reset($item);
		}
								
	}
