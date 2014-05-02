<?php
	
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_dashboard/libs/functional_test_builder/functional_test_builder.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/underscore_kit/underscore_kit.php');
		
	class UnderscoreKitTest extends PHPUnit_Framework_TestCase {
		
		static $functionalBuilderConfig;
				
		static $verifyExpectedActual = true;
		
		static $levelsToDrillDown = 0;
		
		static $testStorage = array();
		static $onFilterResults = array();
				
		function __construct() {
			self::$functionalBuilderConfig = self::getFunctionalBuilderConfig();
			__kit::initialize();
		}
								
		public function getFunctionalBuilderConfig() {
			return array(
				'configuration_map' => self::getConfigurationMap(),
				'entry_point_map' => self::getEntryPointMap(),
			);
		}
		
		public function getExpectedActualFunction() {
			$expAct = function($expectedActual) {
				return UnderscoreKitTest::buildExpectedActualArgs($expectedActual['expected'], $expectedActual['actual']);
			};
			
			return $expAct;
		}
		
		public function buildExpectedActualArgs($expected, $actual) {
			if($expected != $actual && self::$verifyExpectedActual) {
				if(self::$levelsToDrillDown == 0) {
					$output = Test_Builder::confirmExpected($expected, $actual);
				} else {
					$output = Test_Builder::confirmExpectedWithDrillDown(array(), $expected, $actual, self::$levelsToDrillDown);
				}
				print_r($output);
			}
			return array(
				 'expected' => $expected,
				 'actual' => $actual,
			);
		}
						
		public function buildTest($test) {
			Test_Builder::buildTest($test, self::$functionalBuilderConfig);
		}
						
		public function getEntryPointMap() {
			
			return array(
				'all' => self::getAllEntryPoint(),
				'call_function' => self::getCallFunctionEntryPoint(),
			);
			
		}
		
		public function getConfigurationMap() {
			return array(
				'call_function' => self::getCallFunctionConfiguration(),
			);
		}
		
		public function getAllEntryPoint() {
			$expAct = self::getExpectedActualFunction();
			return array(
				'test' => $this,
				'build_input' => function($input) {
					return $input;
				},
				'get_assert_args' => function($output, $assertInput) use($expAct){
					return $expAct(
						array(
							'expected' => $assertInput['expected'],					
							'actual' => $output,
						)
					);
				},
				'input' => array(),
				'extra_params' => array(),
				'assert_input' => array(),
				'asserts' => array (
					'assertEquals' => array(
						'expected', 
						'actual',
					),
				),
			);
		}
		
		public function getCallFunctionEntryPoint() {
			$test = $this;
			return array(
				'get_output' => function($input, $extraParams) use($test){	
					$testStorage = array();
					$function = $input['function'];
					$arguments = $input['arguments'];
					$buildOutput = $input['build_output'];
					$result = call_user_func_array("__::$function", $arguments);
					return $buildOutput($result);
				},
			);
		}
												
		public function getCallFunctionConfiguration() {
						
			return array(
				'input' => array(
					'function' => 'filterByConditions',
					'arguments' => array(
						array(
							array(
								'foo' => 3,
								'bar' => 1, 
							),
							array(
								'foo' => 0,
								'bar' => 0, 
							),
							array(
								'foo' => 1,
								'bar' => 3, 
							),
						),
						array(
							'foo_is_greater_than_one' => function($item) {
								return $item['foo'] > 1;
							},
							'bar_is_greater_than_one' => function($item) {
								return $item['bar'] > 1;
							},
						),
						function($type, $item) {
							UnderscoreKitTest::$onFilterResults[$type] = $item;
						},
					),
					'build_output' => function($result) {
						return array(
							'content' => $result,
							'on_filter' => UnderscoreKitTest::$onFilterResults,
						);
					},
				),
				'assert_input' => array(
					'expected' => array(
						'content' => array(
							array(
								'foo' => 0,
								'bar' => 0,
							),
						),
						'on_filter' => array(
							'foo_is_greater_than_one' => array(
								'foo' => 3,
								'bar' => 1, 
							),
							'bar_is_greater_than_one' => array(
								'foo' => 1,
								'bar' => 3, 
							),
						),
					),
				),
			);
			
		}
														
		public function testFilterByConditions() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
			);
			self::buildTest($test);
		}
		
		public function testGrab() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
				'alterations' => array(
					'input' => function($input) {
						return array(
							'function' => 'grab',
							'arguments' => array(
								// content
								array(
									'foo' => 1,	 
								),
								// key to grab
								'foo',
								// if not set
								'not_set',
							),
							'build_output' => function($result) {
								return array(
									'result' => $result,
								);
							},
						);
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'result' => 1,
							),
						);
					} 
				),
			);
			self::buildTest($test);
		}
		
		public function testGrabWithNoKeySet() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
				'alterations' => array(
					'input' => function($input) {
						return array(
							'function' => 'grab',
							'arguments' => array(
								// content
								array(
									'foo' => 1,	 
								),
								// key to grab
								'bar',
							),
							'build_output' => function($result) {
								return array(
									'result' => $result,
								);
							},
						);
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'result' => null,
							),
						);
					} 
				),
			);
			self::buildTest($test);
		}
		
		public function testGrabWithIfNotSetKey() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
				'alterations' => array(
					'input' => function($input) {
						return array(
							'function' => 'grab',
							'arguments' => array(
								// content
								array(
									'foo' => 1,	 
								),
								// key to grab
								'bar',
								'not_set',
							),
							'build_output' => function($result) {
								return array(
									'result' => $result,
								);
							},
						);
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'result' => 'not_set',
							),
						);
					} 
				),
			);
			self::buildTest($test);
		}
		
		public function testIndexBy() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
				'alterations' => array(
					'input' => function($input) {
						return array(
							'function' => 'indexBy',
							'arguments' => array(
								// content
								array(
									array(
										'foo' => 1,	 
									),
									array(
										'foo' => 2,
									),
									array(
										'bar' => 2,
									),
								),
								// key to grab
								'foo',
								// if not set
							),
							'build_output' => function($result) {
								return array(
									'result' => $result,
								);
							},
						);
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'result' => array(
									1 => array(
										'foo' => 1,
									),
									2 => array(
										'foo' => 2,
									),
								),
							),
						);
					} 
				),
			);
			self::buildTest($test);
		}
		
		public function testIndexByWithNotSet() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
				'alterations' => array(
					'input' => function($input) {
						return array(
							'function' => 'indexBy',
							'arguments' => array(
								// content
								array(
									array(
										'foo' => 1,	 
									),
									array(
										'foo' => 2,
									),
									array(
										'bar' => 2,
									),
								),
								// key to grab
								'foo',
								'no_key',
								// if not set
							),
							'build_output' => function($result) {
								return array(
									'result' => $result,
								);
							},
						);
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'result' => array(
									1 => array(
										'foo' => 1,
									),
									2 => array(
										'foo' => 2,
									),
									'no_key' => array(
										array(
											'bar' => 2,
										),
									),	
								),
							),
						);
					} 
				),
			);
			self::buildTest($test);
		}
		
		public function testStitch() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
				'alterations' => array(
					'input' => function($input) {
						return array(
							'function' => 'stitch',
							'arguments' => array(
								// to content
								array(
									array(
										'id' => 1,	 
									),
									array(
										'id' => 2,
									),
									array(
										'id' => 3,
									),
								),
								
								// from content
								array(
									array(
										'foo' => 1,
										'other_id' => 1,
									),
									array(
										'foo' => 2,
										'other_id' => 2,
									),
									array(
										'foo' => 3,
										'other_id' => 3,
									),
								),
								// key to grab
								
								'foo',
								'id',
								'other_id',
								
							),
							'build_output' => function($result) {
								return array(
									'result' => $result,
								);
							},
						);
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'result' => array(
									array(
										'id' => 1,	 
										'foo' => 1,
									),
									array(
										'id' => 2,
										'foo' => 2,
									),
									array(
										'id' => 3,
										'foo' => 3,
									),
								),
							),
						);
					} 
				),
			);
			self::buildTest($test);
		}
		
		public function testStitchWithOnNotFound() {
			$test = array(
				'configuration' => 'call_function',
				'entry_point' => 'call_function',
				'alterations' => array(
					'input' => function($input) {
						return array(
							'function' => 'stitch',
							'arguments' => array(
								// to content
								array(
									array(
										'id' => 1,	 
									),
									array(
										'id' => 2,
									),
									array(
										'id' => 3,
									),
									array(
										'id' => 4,
									),
								),
								
								// from content
								array(
									array(
										'foo' => 1,
										'other_id' => 1,
									),
									array(
										'foo' => 2,
										'other_id' => 2,
									),
									array(
										'foo' => 3,
										'other_id' => 3,
									),
								),
								// key to grab
								
								'foo',
								'id',
								'other_id',
								function($item, $key) {
									array_push(UnderscoreKitTest::$testStorage, array($key => $item));
									return $item;
								},
								
							),
							'build_output' => function($result) {
								return array(
									'result' => $result,
									'on_not_found' => UnderscoreKitTest::$testStorage,
								);
							},
						);
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'result' => array(
									array(
										'id' => 1,	 
										'foo' => 1,
									),
									array(
										'id' => 2,
										'foo' => 2,
									),
									array(
										'id' => 3,
										'foo' => 3,
									),
									array(
										'id' => 4,
									),
								),
								'on_not_found' => array(
									array(
										4 => array(
											'id' => 4,
										),	
									),
								),
							),
						);
					} 
				),
			);
			self::buildTest($test);
		}
				
}
