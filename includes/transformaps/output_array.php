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
					
					__::map(App::$cache->get('random_items'), function($randomItem) use(&$output){
						$output[$randomItem] = Random_Item_Handler::get($randomItem);
					});
																				
					return $output;
		
				},
				'output_key' => 'random_items',
			);
		}
		
		
				
	}
