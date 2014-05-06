<?php

	class Random_Number_Generator {
		
		
		// wrapper for PHP random number functions so they can be stubbed in tests
		public function rand($min, $max) {
			return rand($min, $max);
		}
		
	}

