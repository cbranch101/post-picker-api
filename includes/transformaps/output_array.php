<?php

	class Output_Array extends TransforMap {
		
		protected static $types = array();
		
		public static function getFunction($type, $details, $output, $inputParams = array()) {
			$outputKey = $details['output_key'];
			$output[$outputKey] = $details['get_output']($inputParams);
			return $output;
		}
		
		public static function randomItemsType() {
			return array(
				'get_output' => function($inputParams) {
					$randomItems = array();
					
					$randomItems = __::chain(App::$cache->get('random_items')) 
						->map(function($randomItemType) use(&$output){
							$randomItem = Random_Item_Handler::getItem($randomItemType);
							$randomItem['name'] = $randomItemType;
							return $randomItem;
						})
						->values()
					->value();
					
					print_r($randomItems);
										
					return $randomItems;
		
				},
				'output_key' => 'random_items',
			);
		}
		
		
				
	}
