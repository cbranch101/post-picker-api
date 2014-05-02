<?php

	class TransforMap {
		
		
		protected static $types = null;
		protected static $className = null;
		
		public static function getAllTypes() {
			static::setTypes();
			return static::$types;			
		}
		
		protected static function setTypes() {
			if(static::$types == null) {
				$typeArray = static::buildIndexedArrayBasedOnSuffix('Type');
				$that = static::$className;
				$typeArray = __::map($typeArray, function($details, $type) use($that){
					$details = $that::initializeTypeArrays($type, $details);
					return $details;
				});
				static::$types = $typeArray;
			}
		}
		
		public static function initializeTypeArrays($type, $details) {
			return $details;
		}
		
		protected static function buildIndexedArrayBasedOnSuffix($suffix) {
			$indexedArray = array();
			static::$className = get_called_class();
			$classMethods = get_class_methods(static::$className);
			$currentClass = static::$className;
			__::map($classMethods, function($classMethod) use($suffix, &$indexedArray, $currentClass){
				
				// split up the method name
				$pieces = preg_split('/(?=[A-Z])/',$classMethod);
				
				// if the last piece of the method is the suffix
				if($pieces[count($pieces)-1] == $suffix) {
					
					// remove the last piece
					$firstPiece = array_pop($pieces);
					
					// convert the function name to lower case
					$pieces = __::map($pieces, function($piece){
						return strtolower($piece);
					});
					
					// add underscores between the pieces to get the key
					// that will be used to hold the contents
					$key = implode('_', $pieces);
					
					// get the contents that are going to be added to the indexed array
					$contents = call_user_func(array($currentClass, $classMethod));
					
					// update the indexed array
					$indexedArray[$key] = $contents;
				}
			});
			
			return $indexedArray;
		}
		
	}
