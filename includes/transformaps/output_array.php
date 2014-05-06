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
					$output = array();
					
					__::map(App::$cache->get('random_items'), function($randomItemType) use(&$output){
						$output[$randomItemType] = Random_Item_Handler::get($randomItemType);
					});
																				
					return $output;
		
				},
				'output_key' => 'random_items',
			);
		}
		
		
				
	}
