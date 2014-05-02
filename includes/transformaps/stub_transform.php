<?php

	class Stub_Transform extends TransforMap{
		
		public static function firstTestType() {
			return array(
				'is_valid' => true,
				'amount_to_add' => 1,
			);
		}
		
		public static function initializeTypeArrays($type, $details) {
			$details['is_init'] = true;
			return $details; 
		}
		
		public static function secondTestType() {
			return array(
				'is_valid' => false,
				'amount_to_add' => 2,
			);
		}
		
		public static function addNumberFunction($type, $details, $number) {
			return $number + $details['amount_to_add'];
		}
		
	}
