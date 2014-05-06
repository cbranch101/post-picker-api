<?php

	class TransforMap {
		
		
		protected static $types = array();
		
		public static function getAllTypes() {
			self::setTypes();
			return static::$types;			
		}
		
		public static function __callStatic($name, $arguments) {
			$currentClass = get_called_class();
			 
			$allTypes = self::getAllTypes();
			
			$type = array_shift($arguments);
			if(isset($allTypes[$type])) {
				$details = $allTypes[$type];
			} else {
				Throw new Exception("$type is not a defined type in " . $currentClass);
			}
			
			// add the arguments back in
			array_unshift($arguments, $details);
			array_unshift($arguments, $type);
			
			$functionName = $name . 'Function';
			
			return call_user_func_array(array($currentClass, $functionName), $arguments);
		}
		
		protected static function setTypes() {
			$currentClass = get_called_class();
			
			if(static::$types == null) {
				$typeArray = self::buildIndexedArrayBasedOnSuffix('Type');
				$typeArray = __::map($typeArray, function($details, $type) use($currentClass){
					$details = $currentClass::initializeTypeArrays($type, $details);
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
			$currentClass = get_called_class();
			$classMethods = get_class_methods($currentClass);
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
