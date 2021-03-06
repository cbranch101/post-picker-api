<?php

	error_reporting(E_ALL); 
	ini_set( 'display_errors','1');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/php-underscore/underscore.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/php_mongorm/php_mongorm.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/functional_test_builder/functional_test_builder.php');

	class MongORMTest extends PHPUnit_Framework_TestCase {
		
		static $functionalBuilderConfig;
				
		static $verifyExpectedActual = true;
		
		function __construct() {
			self::$functionalBuilderConfig = self::getFunctionalBuilderConfig();
			
		}
		
		protected function tearDown() {
			MongORM::for_collection('test_records')
				->delete_many();
		}
		
		
		
		public function getFunctionalBuilderConfig() {
			return array(
				'configuration_map' => self::getConfigurationMap(),
				'entry_point_map' => self::getEntryPointMap(),
			);
		}
		
		public function getExpectedActualFunction() {
			$expAct = function($expectedActual) {
				return MongORMTest::buildExpectedActualArgs($expectedActual['expected'], $expectedActual['actual']);
			};
			
			return $expAct;
		}
		
		public function buildExpectedActualArgs($expected, $actual) {
			if($expected != $actual && self::$verifyExpectedActual) {
				$output = Test_Builder::confirmExpected($expected, $actual);
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
				'collection' => self::getCollectionEntryPoint(),
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
		
		public function getCollectionEntryPoint() {
			
			return array(
				'input' => array(),
				'get_output' => function($input, $extraParams) {
					$buildOutput = $extraParams['build_output'];
					$inputData = isset($extraParams['input_data']) ? $extraParams['input_data'] : null;
					if($inputData) {
						MongORM::for_collection('test_records')
							->create_many($inputData);
					}
					$collection = MongORM::for_collection('test_records');
					$collection = MongORMTest::addFindQueriesToCollection($collection, $extraParams);
					return $buildOutput($collection);
				},
			);
			
		}
		
		public function addFindQueriesToCollection($collection, $extraParams) {
			
			$findMap = array(
				'find_many' => function($collection, $query) {
					return $collection->find_many($query);
				},
				'find_one' => function($collection, $query) {
					return $collection->find_one($query);
				}
			);
			foreach($findMap as $findType => $findFunction) {						
				
				if(isset($extraParams[$findType])) {
					$query = $extraParams[$findType];
					$collection = $findFunction($collection, $query);
				}
			}
			return $collection;
			
		}
				
		public function getConfigurationMap() {
						
			return array(
				'two_records' => self::getTwoRecordsConfiguration(),
				'empty_records' => self::getEmptyRecordsConfiguration(),
				'three_records' => self::getThreeRecordsConfiguration(),
				'two_records_extra_info' => self::getTwoRecordsExtraInfoConfiguration(),
			);			
		}
		
		public function getTwoRecordsConfiguration() {
			return array(
				'extra_params' => array(
					'input_data' => array(
						array(
							'_id' => 1,
							'test' => 1,
						),
						array(
							'_id' => 2,
							'test' => 1,
						),
					),
					
				),
			);	
		}
		
		public function getTwoRecordsExtraInfoConfiguration() {
			return array(
				'extra_params' => array(
					'input_data' => array(
						array(
							'_id' => 1,
							'test' => 1,
							'foo' => 'bar',
							'biz' => 'nass',
						),
						array(
							'_id' => 2,
							'test' => 1,
							'foo' => 'bar',
							'biz' => 'nass',
						),
					),
					
				),
			);	
		}
		
		public function getThreeRecordsConfiguration() {
			return array(
				'extra_params' => array(
					'input_data' => array(
						array(
							'_id' => 1,
							'test' => 1,
						),
						array(
							'_id' => 2,
							'test' => 2,
						),
						array(
							'_id' => 3,
							'test' => 2,
						),
					),
					
				),
			);	
		}
		
		public function getEmptyRecordsConfiguration() {
			return array(
			);
		}
						
		public function testConnect() {
			MongORM::connect('test');
			$this->assertNotNull(MongORM::$database);
		}
		
		public function testDeleteOne() {
			
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->delete_one(array('_id' => 1));
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							2 => array(
								'_id' => 2,
								'test' => 1,
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testDeleteMany() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'three_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->delete_many(array('test' => 2));
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 1,
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testCreateOne() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'empty_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							$record = array(
								'_id' => 1,
								'test' => 1,
							);
							$collection->create_one($record);
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 1,
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testUpdateUsingSetFromQuery() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($record) {
							
							$record->test = 'updated';
							$record->update();
							
							return MongORM::for_collection('test_records')
								->find_one(array('_id' => 1))
								->as_array();
							
						};
						
						$extraParams['find_one'] = array(
							'_id' => 1,
						);
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 'updated',
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testUpdateUsingArray() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($record) {
							
							$dataToSet = array(
								'test' => 'updated',
								'foo' => 'bar',
							);
							
							$record->set($dataToSet);
							$record->update();
							
							return MongORM::for_collection('test_records')
								->find_one(array('_id' => 1))
								->as_array();
							
						};
						
						$extraParams['find_one'] = array(
							'_id' => 1,
						);
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 'updated',
								'foo' => 'bar',
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testSingleSelect() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records_extra_info',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->select('biz');
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'biz' => 'nass',
							),
							2 => array(
								'_id' => 2,
								'biz' => 'nass',
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testArraySelect() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records_extra_info',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->select(array('foo', 'biz'));
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'foo' => 'bar',
								'biz' => 'nass',
								
							),
							2 => array(
								'_id' => 2,
								'foo' => 'bar',
								'biz' => 'nass',
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testEnsureIndex() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->ensure_index(array('test' => 1), array("unique" => 1));
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 1,
								
							),
							2 => array(
								'_id' => 2,
								'test' => 1,
								
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testDeleteIndexes() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->delete_indexes();
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 1,
								
							),
							2 => array(
								'_id' => 2,
								'test' => 1,
								
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testHasContents() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records_extra_info',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$hasContentsPreCursor = $collection->find_many()
								->has_contents();
								
							$collection->test = 2;
							$hasContentsPostCursor = $collection->has_contents();
							
							$collection2 = MongORM::for_collection('test_records')
								->find_many(
									array('test' => 'fuzz')
								);
								
							$noContentsPreCursor = $collection2->has_contents();
							
							return array(
								'has_contents_pre_cursor' => $hasContentsPreCursor,
								'has_contents_post_cursor' => $hasContentsPostCursor,
								'no_contents_pre_cursor' => $noContentsPreCursor,
							);
							
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
								'has_contents_pre_cursor' => true,
								'has_contents_post_cursor' => true,
								'no_contents_pre_cursor' => false,
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testCount() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$preCursorCount = $collection->find_many()
								->count();
							$array = $collection->as_array();
							$postCursorCount = $collection->count();
						
							
								
							return array(
								'pre_cursor_count' => $preCursorCount,
								'post_cursor_count' => $postCursorCount,
							);
								
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
								'pre_cursor_count' => 2,
								'post_cursor_count' => 2,
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testIDExists() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$exists = $collection->id_exists(1);
							$noExists = $collection->id_exists(5);
							return array(
								'exists' => $exists,
								'no_exists' => $noExists,
							);

						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						return array(
							'expected' => array(
								'exists' => true,
								'no_exists' => false,
							),
						);
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testAsCursor() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($record) {
							
							$cursor = $record->as_cursor();
							return $cursor;
							
						};
						
						$extraParams['find_many'] = array(
							'test' => 1,
						);
						return $extraParams;
					},
					'get_assert_args' => function($getAssertArgs) {
						return function($output, $assertInput) {
							return array(
								'expectedInstance' => 'MongoCursor',
								'instance' => $output,
							);
						};
					},
					'asserts' => function($asserts){
						return array(
							'assertInstanceOf' => array(
								'expectedInstance',
								'instance',
							),
						);
					},
					
				),
			);
			$test = self::buildTest($test);			
		}
				
		public function testFindMany() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'three_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['find_many'] = array(
							'test' => 2,
						);
						
						$extraParams['build_output'] = function($collection) {
							return $collection->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							2 => array(
								'_id' => 2,
								'test' => 2,
							),
							3 => array(
								'_id' => 3,
								'test' => 2,
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testFindByID() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							return $collection->find_by_id(1)
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 1,
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testCombineQuery() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records_extra_info',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							return $collection->select('foo')
								->find_many(array('_id' => 1))
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'foo' => 'bar',
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testWhereQuery() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->where('_id', 1);
							return $collection->find_many()
								->as_array();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1 => array(
								'_id' => 1,
								'test' => 1,
							),
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testGetFromMultiple() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->find_many();
							return $collection->test;
							
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = array(
							1,
							1,
						);
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		public function testGetFromSingle() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'two_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							
							$collection->where('_id', 1);
							$collection->find_one();
							return $collection->test;
							
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						$assertInput['expected'] = 1;
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
	    /**
	     * @expectedException Exception
	     * @expectedExceptionMessage Method foo_bar not found
	     */		
		public function testBadMethod() {
			$test = array(
				'entry_point' => 'collection',
				'configuration' => 'empty_records',
				'alterations' => array(
					'extra_params' => function($extraParams) {
						$extraParams['build_output'] = function($collection) {
							$collection->foo_bar();
						};
						return $extraParams;
					},
					'assert_input' => function($assertInput) {
						return $assertInput;
					},
				),
			);
			$test = self::buildTest($test);			
		}
		
		
				
		
				
}
