<?php

	class Random_Selection extends TransforMap {
		
		protected static $types = array();
		
		
		// get a random offset for a given type
		public static function getRandomOffsetFunction($type, $details) {
			
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
		
		public static function highType() {
			return array(
				'offset_params' => array(
					'min' => '.70',
					'max' => '.90',
				),
			);
		}
		
	}
