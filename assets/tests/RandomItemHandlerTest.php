<?php
	
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/libs/functional_test_builder/functional_test_builder.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/libs/underscore_kit/underscore_kit.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/libs/php_mongorm/php_mongorm.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/includes/app.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/libs/transformap/transformap.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/includes/random_item_handler.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/includes/transformaps/output_array.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/includes/shared_data.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/includes/transformaps/data_pipeline.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/includes/transformaps/random_selection.php');
	require_once('/Users/cbranch101/Sites/clay/movement_strategy/post_picker_api/includes/random_number_generator.php');	
	class RandomItemHandlerTest extends PHPUnit_Framework_TestCase {
		
		static $functionalBuilderConfig;
		
		static $verifyKey = null;
				
		static $verifyExpectedActual = true;
		
		static $levelsToDrillDown = 1;
		
		static $passedIntoScraper = array();
		
		static $passedIntoTableBuilder = array();
		
		static $collectionsToReset = array(
			'knicks_processed_posts',
		);
				
		function __construct() {
			self::$functionalBuilderConfig = self::getFunctionalBuilderConfig();
			__kit::initialize();
			MongORM::connect('test', 'localhost');
			self::resetCollections();
		}
										
		public function setUp() {
			self::resetCollections();
		}
		
		protected function tearDown() {
			self::resetCollections();
		}
		
		public function getFunctionalBuilderConfig() {
			return array(
				'configuration_map' => self::getConfigurationMap(),
				'entry_point_map' => self::getEntryPointMap(),
			);
		}
		
		public function getExpectedActualFunction() {
			$expAct = function($expectedActual) {
				return RandomItemHandlerTest::buildExpectedActualArgs($expectedActual['expected'], $expectedActual['actual']);
			};
			
			return $expAct;
		}
		
		public function buildExpectedActualArgs($expected, $actual) {
			if($expected != $actual && self::$verifyExpectedActual) {
				if(self::$levelsToDrillDown == 0) {
					$output = Test_Builder::confirmExpected($expected, $actual);
				} else {
					$output = Test_Builder::confirmExpectedWithDrillDown(array(), $expected, $actual, self::$levelsToDrillDown, false, self::$verifyKey);
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
		
		public function populateCollections($inputData) {
			self::resetCollections();
			foreach($inputData as $collectionToPopulate => $recordsToCreate) {
				$recordsToCreate = $inputData[$collectionToPopulate];
				if(count($recordsToCreate) > 0) {
					MongORM::for_collection($collectionToPopulate)
						->create_many($recordsToCreate);
				}
			}
		}
		
		public function getAllFromCollections() {
			return __::chain(self::$collectionsToReset)
				->map(function($collectionToReset){
					$data = MongORM::for_collection($collectionToReset)
						->find_many()
						->as_array();
						
					return array(
						$collectionToReset => $data,
					);
				})
				->flatten(true)
			->value();
		}
		
		public function resetCollections() {
			foreach(self::$collectionsToReset as $collectionToReset) {
				MongORM::for_collection($collectionToReset)
					->delete_many();
			} 
		}
				
		public function getEntryPointMap() {
			
			return array(
				'all' => self::getAllEntryPoint(),
				'get_random_item' => self::getRandomItemEntryPoint(),
			);
			
		}
		
		public function getConfigurationMap() {
			return array(
				'right' => self::getRightRandomItemConfiguration(),
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
		
		public static function getStubCache($stubSharedData, $test) {
			
			$keys = __::keys($stubSharedData);
			
			$stubCache = $test->getMock('Shared_Data', $keys);
			
			__::map($stubSharedData, function($value, $key) use(&$stubCache, $test){
				$stubCache->expects($test->any())
					->method($key)
					->will($test->returnCallback(
			               function() use($value) {
							   	
			                	return $value;
			               	
			               }
			         ));
			});
			
		              		              
			return $stubCache;
		}
		
		public static function getStubRandomNumberGenerator($test, $stubRandomValues) {
			
			$stubRandomNumberGenerator = $test->getMock('Random_Number_Generator');
			$stubRandomNumberGenerator->expects($test->any())
				->method('rand')
				->will($test->returnCallback(
	                function($min, $max) use(&$stubRandomValues) {
						$currentValue = array_shift($stubRandomValues);
						return $currentValue;
	                }
                ));
                
			return $stubRandomNumberGenerator;
		}
		
		public function getRandomItemEntryPoint() {
			$test = $this;
			return array(
				'get_output' => function($input, $extraParams) use($test){	
					$stubSharedData = $input['stub_shared_data'];
					$stubSharedData['random_number_generator'] = RandomItemHandlerTest::getStubRandomNumberGenerator($test, $input['stub_random_values']);
					App::$cache = RandomItemHandlerTest::getStubCache($stubSharedData, $test);
					RandomItemHandlerTest::populateCollections($extraParams['collections_to_populate']);
					return Data_Pipeline::run('get_random_items');
				},
			);
		}
		
		public function getProcessedPostCollection() {
			$process = function($value, $formattedValue = null, $data = null){
				$value =  array(
					'value' => $value,
					'formatted_value' => $formattedValue,
				);
				
				if($data != null) {
					$value['data'] = $data;
				}
				
				return $value;
			};
			
			$getGoodPost = function($id, $likes) use($process){
				return array(
					'_id' => $id,
					'likes' => $process($likes),
					'message' => $process('Test', 'Test',
						array(
							'has_picture' => true,
						)
					),
				);
			};
			
			return array(
				$getGoodPost(1, 100),
				$getGoodPost(2, 200),
				$getGoodPost(3, 300),
				$getGoodPost(4, 400),
				$getGoodPost(5, 500),
				$getGoodPost(6, 600),
				$getGoodPost(7, 700),
				$getGoodPost(8, 800),
				$getGoodPost(9, 900),
				$getGoodPost(10, 1000),
			);
		}
				
		public function getRightRandomItemConfiguration() {
						
			return array(
				'input' => array(
					'stub_shared_data' => array(
						'random_items' => array(
							'right',
						),
						'request_params' => array(
							'dashboard_id' => 'knicks',
						),
					),
					'stub_random_values' => array(
						
						// this value is going to be used to assign whether or not the 
						// right random item is high or not 
						6,
						
						// this is the value that will be used as the random offset
						8,
					),	
				),
				'extra_params' => array(
					'collections_to_populate' => array(
						'knicks_processed_posts' => self::getProcessedPostCollection(),
					),
				),
				'assert_input' => array(
					'expected' => array(
						'random_items' => array(
							'right' => array(
								'_id' => 8,
								'likes' => array(
									'value' => 800,
									'formatted_value' => null,
								),
							),
						),
					),	
				),
			);
			
		}
						
		public function getCollectionsToPopulate() {
		return array(
			'scrape_logs' => array(
				
				// this is going to be eliminated because it's too long ago
				array(
					'created_time' => 1375333111,
					'table_type' => 'facebook_posts',
					'dashboard_id' => 'movement',
					'successful' => true,
				),
				
				
				array(
					'created_time' => 1376333111,
					'table_type' => 'facebook_posts',
					'dashboard_id' => 'movement',
					'successful' => false,
					'error' => "Facebook API Error",
				),
				
				array(
					'created_time' => 1376333111,
					'table_type' => 'tweets',
					'dashboard_id' => 'zumiez',
					'successful' => true,
				),
				
				array(
					'created_time' => 1376333111,
					'table_type' => 'instagram_posts',
					'dashboard_id' => 'zumiez',
					'successful' => true,
				),
				
				array(
					'created_time' => 1376333111,
					'table_type' => 'ga',
					'dashboard_id' => 'zumiez',
					'successful' => true,
				),
				
				array(
					'created_time' => 1376333111,
					'table_type' => 'facebook_posts',
					'dashboard_id' => 'zumiez',
					'successful' => true,
				),
				
				array(
					'created_time' => 1376333111,
					'table_type' => 'ga',
					'dashboard_id' => 'movement',
					'successful' => true,
				),
			),
			'failed_scrapes' => array(
				
				// will be ignored because it's too long ago
				array(
					'created_time' => 1366331111,
					'table_type' => 'facebook_posts',
					'dashboard_id' => 'movement',
					'successful' => false,
					'error' => "Too Long Ago",
				),
				
				// this is going to eliminate a table type for movement
				array(
					'created_time' => 1376331111,
					'table_type' => 'instagram_posts',
					'dashboard_id' => 'movement',
					'successful' => false,
					'error' => "Test Error",
				),
			),
			'app_info' => array(
				array(
					'emails_to_notify' => array(
						'clay@movementstrategy.com',
						'jason@movementstrategy.com',
					),
				),
			),
			'dashboards' => array(
				array(
					'_id' => 'movement',
					'dashboard' => array(
						'id' => 'movement',
						'has_bitly' => true,
						'has_facebook' => true,
						'has_twitter' => true,
						'has_instagram' => true,
						'has_google_analytics' => true,
					),
				),
				array(
					'_id' => 'zumiez',
					'dashboard' => array(
						'id' => 'zumiez',
						'has_bitly' => true,
						'has_facebook' => true,
						'has_twitter' => true,
						'has_instagram' => true,
						'has_google_analytics' => true,
					),
				),
				array(
					'_id' => 'rangers',
					'dashboard' => array(
						'id' => 'zumiez',
						'has_bitly' => false,
						'has_facebook' => false,
						'has_twitter' => false,
						'has_instagram' => false,
						'has_google_analytics' => false,
					),
				),
			),
		);
			
		}
														
		public function testRightRandomItem() {
						
			$test = array(
				'configuration' => 'right',
				'entry_point' => 'get_random_item',
			);
			
			self::buildTest($test);
			
		}
		
		public function testLeftRandomItem() {
						
			$test = array(
				'configuration' => 'right',
				'entry_point' => 'get_random_item',
				'alterations' => array(
					'input' => function($input){
						return array(
							'stub_shared_data' => array(
								'random_items' => array(
									'left',
								),
								'request_params' => array(
									'dashboard_id' => 'knicks',
								),
							),
							'stub_random_values' => array(
								
								// this value is going to be used to assign whether or not the 
								// right random item is high or not 
								6,
								
								// this is the value that will be used as the random offset
								3,
							),	
						);
					},
					'assert_input' => function($assertInput){
						$assertInput['expected'] = array(
							'random_items' => array(
								'left' => array(
									'_id' => 3,
									'likes' => array(
										'value' => 300,
										'formatted_value' => null,
									),
								),
							),
						);
						
						return $assertInput;
					},
				),
			);
			
			self::buildTest($test);
			
		}
														
}
