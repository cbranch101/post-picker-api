<?php

	class Random_Item_Handler {
				
		public static function getItem($randomItemType) {
			$selectionType = self::getSelectionType($randomItemType);
			$totalItems = App::$cache->get('total_items');
			$randomItem = self::getItemUsingSelectionType($selectionType);
			$randomItem = self::processRandomItem($randomItem, $selectionType);		
			return $randomItem;
		}
		
		public static function processRandomItem($randomItem, $selectionType) {
			$processedItem = array();
			$processedItem['metric'] = $randomItem['likes']['value'];
			$processedItem['is_correct'] = $selectionType == 'high' ? true : false;
			$processedItem['message'] = $randomItem['message']['formatted_value'];
			$imageURL = $randomItem['message']['data']['post_picture_url'];
			$processedItem = self::setImageURLs($processedItem, $imageURL);
			
			return $processedItem;
		}
		
		public static function setImageURLs($processedItem, $imageURL) {
			
			$processedItem['image_url'] = $imageURL;
			$processedItem['big_image_url'] = self::getBigImageURL($imageURL);
			return $processedItem;
		} 
		
		public static function getBigImageURL($imageURL) {
			$replaceStrings = array(
				'_x.',
				'_s.',
				'_a.',
				'_b.',
			);
			
			$replaced = false;
			
			__::map($replaceStrings, function($replaceString) use($imageURL, &$bigImageURL, &$replaced){
				$updatedURL = str_replace($replaceString, '_o.', $imageURL);
				if($updatedURL != $imageURL && !$replaced) {
					$bigImageURL = $updatedURL;
					$replaced = true;
				}
			});
			return $bigImageURL;
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
				
		public static function getItemUsingSelectionType($selectionType) {
			$collection = MongORM::for_collection(App::$cache->get('collection_name'));
			$offset = Random_Selection::getRandomOffset($selectionType);
			$response = $collection->find_many()
				->find_one(App::$cache->get('facebook_filter_query'))
				->sort(
					array('likes.value' => 1)
				)
				->skip($offset - 1)
				->as_array();
				
			$item = reset($response);
			
			if(self::itemHasInvalidPostPictureURL($item)) {
				$item = self::getItemUsingSelectionType($selectionType);
			}
			
			return $item;
		}
		
		public static function itemHasInvalidPostPictureURL($item) {
			$postPictureURL = $item['message']['data']['post_picture_url'];
			if(self::isSafeImageURL($postPictureURL) || self::hasURLParam($postPictureURL)) {
				return true;
			} else {
				return false;
			}
		}
		
		public static function hasURLParam($url) {
			if(strpos($url,'?') !== false) {
				return true;	
			} else {
				return false;
			}
		}
				
		public static function isSafeImageURL($url) {
			$stringStartsWith = function($needle, $haystack) {
				return $needle === "" || strpos($haystack, $needle) === 0;
			};
			
			$invalidURLStart = 'https://fbexternal-a.akamaihd.net/safe_image';
						
			return $stringStartsWith($invalidURLStart, $url);
		}
								
	}
