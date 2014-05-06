<?php

	class Data_Pipeline extends TransforMap {
		
		protected static $types = array();
		
		public static function runFunction($type, $details, $outputArrays = null) {
			// use the outputArrays from arguments if they're available, 
			// otherwise, use the ones from details
			// this is primarily for testing purposes
			self::setSharedDataInstance();
			$outputArrays = $outputArrays == null ? $details['output_arrays'] : $outputArrays;
			$output = array();
			__::map($outputArrays, function($outputArrayName) use(&$output){
				$output = Output_Array::get($outputArrayName, $output);
			});
			
			
			
			return $output;
		}
		
		public static function setSharedDataInstance() {
			
			// this singleton pattern allows for a stub Shared Data instance to substitued in tests
			if(App::$cache == null) {
				App::$cache = new Shared_Data();
			}
		}
		
		public static function getRandomItemsType() {
			return array(
				'output_arrays' => array(
					'random_items',
				),
			);
		}		
		
		
	}
