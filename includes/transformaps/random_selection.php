<?php

	class Random_Selection extends TransforMap {
		
		protected static $types = array();
		
		
		// get a random offset for a given type
		public static function getRandomOffsetFunction($type, $details) {
			
		}
		
		public static function highType() {
			return array(
				'offset_params' => array(
					'min' => '.70',
					'max' => '.90',
				),
			);
		}
		
	}
