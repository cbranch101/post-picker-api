<?php

	class __kit {
		
		/**
		 * filterByCondition function.
		 * 
		 * $condtions = array(
		 * 		'condition_key' => function() {
		 *			// check if the condition happened in here
		 *		},
		 * 		'condition_key_2 => function() {
		 *			// test		
		 *		}
		 * );
		 * 
		 * @access public
		 * @static
		 * @param mixed $content
		 * @param mixed $conditions
		 * @return void
		 */
		
		static function filterByConditions($content, $conditions, $onFilter = null) {
			$filteredContent = __::filter($content, function($item) use($onFilter, $conditions) {
				$shouldBeFiltered = false;
				__::map($conditions, function($condition, $conditionType) use($item, &$shouldBeFiltered, $onFilter){
					$currentConditionOccured = $condition($item);
					$shouldBeFiltered = $shouldBeFiltered ? true : $currentConditionOccured;
					if($onFilter != null && $currentConditionOccured) {
						$onFilter($conditionType, $item);	
					}
				});
				return !$shouldBeFiltered;
			});
			return $filteredContent;
		}
		
		static function grab($content, $keyToGrab, $ifNotSet = null) {
			return isset($content[$keyToGrab]) ? $content[$keyToGrab] : $ifNotSet;
		}
		
		static function indexBy($content, $indexByKey, $ifNotSet = null) {
			$indexedContent = array();
			
			__::map($content, function($item) use($indexByKey, $ifNotSet, &$indexedContent){
				$indexByValue = __::grab($item, $indexByKey);
				
				if($indexByValue != null) {
					$indexedContent[$indexByValue] = $item;
				}
				
				if($ifNotSet != null) {
					if(!isset($item[$indexByKey])) {
						if(!isset($indexedContent[$ifNotSet])) {
							$indexedContent[$ifNotSet] = array();
						}
						array_push($indexedContent[$ifNotSet], $item);
					}
				}
			});
			
			return $indexedContent;
		} 
		
		static function stitch($stitchTo, $stitchFrom, $keyToTransfer, $stitchToKey, $stitchFromKey, $onNotFound = null) {
			
			$stitchFrom = __::indexBy($stitchFrom, $stitchFromKey);
			
			$output = __::map($stitchTo, function($item) use($onNotFound, $stitchFrom, $stitchTo, $stitchToKey, $keyToTransfer){
				if(isset($item[$stitchToKey])) {
					$value = $item[$stitchToKey];
					if(isset($stitchFrom[$value])) {
						$foundItem = $stitchFrom[$value];
						$item[$keyToTransfer] = $foundItem[$keyToTransfer];
					} else{
						
						if($onNotFound != null) {
							$item = $onNotFound($item, $value);
						}
					}
				}
				
				return $item;
				
			});
			
			return $output;
			
		}
		
		static function setDefault($content, $defaults) {
			__::map($defaults, function($defaultValue, $defaultKey) use(&$content){
				print_r($defaultValue);
				$content = __::map($content, function($item) use($defaultValue, $defaultKey){
					
					if(is_array($defaultValue)) {
						if(isset($item[$defaultKey])) {
							$item[$defaultKey] = __::setDefault($item[$defaultKey], $defaultValue);
							
						}
						
					} else {
						
						if(is_array($item)) {
							if(!isset($item[$defaultKey])) {
								print_r($firing);
								$item[$defaultKey] = $defaultValue;
							}
						} else {
							return $defaultValue;
						}
						
						
					}
					return $item;
				});
				
			});
			
			return $content;
			
		}
				
		static function process($content, $keysToProcess, $setKey = null) {
			if($setKey == null) {
				$setKey = function($item, $key, $value) {
					$item[$key] = $value;
					return $item;
				};
			}
			$processedContent = __::map($content, function($item) use($keysToProcess, $setKey){
				__::map($keysToProcess, function($processFunction, $key) use(&$item, $setKey){
					if(is_callable($processFunction)) {
						$value = $processFunction($item);
						$item = $setKey($item, $key, $value);
					} else {
						unset($item[$key]);
					}
				});
				return $item;
			});
			return $processedContent;
		}
		
		static function quantize($content, $keyToSet, $quanta, $quantizeOptions, $isMember, $getLabel, $onNotFound = null) {
			$quantizedContent = __::map($content, function($item) use($quanta, $isMember, $onNotFound, $quantizeOptions, $getLabel, $keyToSet) {
				$appliedQuanta = __::filter($quanta, function($quantum) use($isMember, $item){
					return $isMember($item, $quantum);
				});
				
				if(count($appliedQuanta) > 1) {
					$output = json_encode($item);
					throw new Exception("This item applies to multiple quanta, this is not allowed : $output");
				}
				
				if(count($appliedQuanta) > 0) {
					$quantum = $appliedQuanta[0];
					$item[$keyToSet] = $getLabel($item, $quantum);
				} else {
					if($onNotFound != null) {
						$item[$keyToSet] = $onNotFound($item, $quantizeOptions);
					}
				}
				return $item;
				
			});
			return $quantizedContent;
		}
		
		static function setMixins() {
			
			$mixins = array(
				'filterByConditions' => function($content, $conditions, $onFilter = null) {
					return __kit::filterByConditions($content, $conditions, $onFilter);
				}, 
				'grab' => function($content, $keyToGrab, $ifNotSet = null) {
					return __kit::grab($content, $keyToGrab, $ifNotSet);
				},  
				'indexBy' => function($content, $keyToGrab, $ifNotSet = null) {
					return __kit::indexBy($content, $keyToGrab, $ifNotSet);
				},  
				'stitch' => function($stichTo, $stitchFrom, $keyToTransfer, $stitchToKey, $stitchFromKey, $onNotFound = null) {
					return __kit::stitch($stichTo, $stitchFrom, $keyToTransfer, $stitchToKey, $stitchFromKey, $onNotFound);
				},  
				'process' => function($content, $keysToProcess, $setKey = null) {
					return __kit::process($content, $keysToProcess, $setKey);	
				},
				'quantize' => function($content, $keyToSet, $quanta, $quantizeOptions, $isMember, $getLabel, $onNotFound = null) {
					return __kit::quantize($content, $keyToSet, $quanta, $quantizeOptions, $isMember, $getLabel, $onNotFound);
				},
				'deepDifference' => function($array1, $array2, $label1 = 'array1', $label2 = 'array2') {
					return __kit::deepDifference($array1, $array2, $label1, $label2);
				},
				'setDefault' => function($content, $defaults) {
					return __kit::setDefault($content, $defaults);
				}
			);
			
			__::mixin($mixins);
		}
		
		static function deepDifference($array1, $array2, $label1 = 'array1', $label2 = 'array2') {
		    return __kit::getDeepDifference($array1, $array2, $label1, $label2);
		}
			    
	    static function getDeepDifference($array1, $array2, $label1, $label2, $isTopLevel = true) {
		   	
		   	
		   	$differences = array();
		   	
		   	$checkForErrorsAndAddToDifferences = function($value1, $value2, $key, $addWithKey) use($label1, $label2, &$differences){
			   	
			   	// if the values don't fully equal each other
			   	if($value1 !== $value2) {
				   	// if the difference is due to types and not values
				   	if($value1 == $value2) {
					   	
					   	// store the type and value in the output
					   	$buildTypeValue = function($value) {
						   	return array(
						   		'type' => gettype($value),
						   		'value' => $value,
						   	);
					   	};
					   	$value1 = $buildTypeValue($value1);
					   	$value2 = $buildTypeValue($value1);
				   	}
				   	
				   	// if we want to store the output in a key or not
				   	if($addWithKey) {
					   	
					   	// create the output using labels
					   	$getOutput = function($value1, $value2) use($label1, $label2) {
						   	return array(
						   		$label1 => $value1,
						   		$label2 => $value2,
						   	);
					   	};
					   	
					   	$differences[$key] = $getOutput($value1, $value2);
				   	} else {
					   	$differences = $getOutput($value1, $value2);
				   	}
			   	}
		   	};
		   	
		   	
		    if(is_array($array1) && count($array1) > 0) {
			    __::map($array1, function($value, $key) use($array2, &$differences, $label1, $label2){
				    
				    $value2 = isset($array2[$key]) ? $array2[$key] : 'not_found';
					
				    if(is_array($value)) {
					    $subDifferences = __kit::getDeepDifference($value, $value2, $label1, $label2, false);
					    print_r($subDifferences);
					    if(count($subDifferences) > 0) {
						   
						   $differences[$key] = $subDifferences; 
					    }
				    } else {
					    if($value != $value2) {
						    $differences[$key] = array(
						    	$label1 => $value,
						    	$label2 => $value2,
						    );
					    }
				    }
			    });
			    
		    } else {
			   	
			    if($array1 != $array2){
				    $differences = array(
				    	$label1 => $array1,
				    	$label2 => $array2,
				    ); 
			    }
			   
		    }
		    
			if($isTopLevel) {
			    __::map($array2, function($value, $key) use($array1, &$differences, $label1, $label2){
				    if(!isset($array1[$key])) {
					    $differences[$key] = array(
					    	$label1 => 'not_found',
					    	$label2 => $value,
					    );
				    }
			    });
			}
		    
		    return $differences;
	    }
		
		
		
		static function initialize() {
			require_once('underscore.php');
			self::setMixins();
		}
		
		
		
		
		
	}
