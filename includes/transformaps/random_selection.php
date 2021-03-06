<?php

	class Random_Selection extends TransforMap {
		
		protected static $types = array();
		
		
		// get a random offset for a given type
		public static function getRandomOffsetFunction($type, $details) {
			$offsetParams = $details['offset_params'];
			$offset = self::calculateRandomOffset($offsetParams['min'], $offsetParams['max']);
			return $offset;
		}
		
		
		public static function calculateRandomOffset($lowPercentage, $highPercentage) {
			$generator = App::$cache->get('random_number_generator');
			$offsetMin = self::getTotalItemOffset($lowPercentage);
			$offsetMax = self::getTotalItemOffset($highPercentage);
			return $generator->rand($offsetMin, $offsetMax);
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
		
		public static function lowType() {
			return array(
				'offset_params' => array(
					'min' => '.10',
					'max' => '.40',
				),
			);
		}
		
	}
