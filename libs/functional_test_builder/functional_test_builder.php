<?php

	class Test_Builder {
		
		static function runTest($configuredTest) {
			
			$associatedTestClass = $configuredTest['test'];
			$input = $configuredTest['build_input']($configuredTest['input']);
			$extraParams = isset($configuredTest['extra_params']) ? $configuredTest['extra_params'] : array();
			$output = $configuredTest['get_output']($input, $extraParams, $associatedTestClass);
			$asserts = $configuredTest['asserts'];
			$assertInput = $configuredTest['assert_input'];
			$assertArgs = $configuredTest['get_assert_args']($output, $assertInput);
			$onTestComplete = isset($configuredTest['on_test_complete']) ? $configuredTest['on_test_complete'] : null;
			self::callAsserts($asserts, $associatedTestClass, $assertArgs);
			if($onTestComplete) {
				$onTestComplete($output);
			}
		}
		
		static function buildTest($testDetails, $config) {
			$configuredTest = self::getConfiguredTest($testDetails, $config);
			self::runTest($configuredTest);
		}
		
		static function getConfiguredTest($testDetails, $config) {
			
			$entryPointMap = $config['entry_point_map'];
			$configurationMap = $config['configuration_map'];
			
			$alterations = isset($testDetails['alterations']) ? $testDetails['alterations'] : array();
			$commonDetails = isset($entryPointMap['all']) ?$entryPointMap['all'] : array();
			
			$entryPointDetails = $config['entry_point_map'][$testDetails['entry_point']];
			
			$currentDetails = self::branch($commonDetails, $entryPointDetails);
			
			$configurationDetails = isset($testDetails['configuration']) ? $config['configuration_map'][$testDetails['configuration']] : array();
			
			$currentDetails = self::branch($currentDetails, $configurationDetails);
			
			$configuredTest = self::makeAlterations($alterations, $currentDetails);
			
			return $configuredTest;
			
			
		}
		
		static function makeAlterations($alterations, $currentDetails) {
			foreach($alterations as $alterationType => $alteration) {
				$detailToAlter = $currentDetails[$alterationType];
				$alteredDetail = $alteration($detailToAlter);
				$currentDetails[$alterationType] = $alteredDetail;
			}
			return $currentDetails;
		}
		
		static function branch($trunk, $branch) {
			foreach($branch as $branchKey => $branchValue) {
				$trunk[$branchKey] = $branchValue;	
			}
			return $trunk;
		}
		
		static function callAsserts($asserts, $test, $assertArgs) {
			
			foreach($asserts as $assertName => $args) {
				
				$argsToAssert = array();
				foreach($args as $argKey) {
					array_push($argsToAssert, $assertArgs[$argKey]);
				}
				
				call_user_func_array(array($test, $assertName), $argsToAssert);
			}
			
		}
		
		static function confirmExpected($expected, $actual) {
			
			$errors = self::confirmThatEverythingInExpectedIsInActual($expected, $actual);
			$errors = self::confirmThatThereIsNothingExtraInActual($expected, $actual, $errors);
			
			$output['errors'] = $errors;
			$output['expected'] = $expected;
			$output['actual'] = $actual;
			
			return $output;
			
		}
		
		static function confirmExpectedWithDrillDown($currentOutput, $expected, $actual, $levels, $fullOutput = false, $verifyKey = null) {
			if($verifyKey != null) {
				$expected = $expected[$verifyKey];
				$actual = $actual[$verifyKey];
			}
			if($levels > 0) {
				$levels--;
				foreach($expected as $expectedKey => $expectedValue) {
					$actualValue = isset($actual[$expectedKey]) ? $actual[$expectedKey] : array();
					$nextLevel = isset($currentOutput[$expectedKey]) ? $currentOutput[$expectedKey] : array();
					$currentOutput[$expectedKey] = self::confirmExpectedWithDrillDown($nextLevel, $expectedValue, $actualValue, $levels);
				}
			} else {
				$bottomLevelOutput =  self::confirmExpected($expected, $actual);
				if(!$fullOutput) {
					unset($bottomLevelOutput['expected']);
					unset($bottomLevelOutput['actual']);
				}
				return $bottomLevelOutput;
			}
			return $currentOutput;
		}
				
		static function confirmThatThereIsNothingExtraInActual($expected, $actual, $errors) {
			foreach($actual as $actualKey => $actualValue) {
				
				$extraValue = array_key_exists($actualKey, $expected) ? null : $actualValue;
				
				if($extraValue) {
				
					$errors[$actualKey]['extra_value_in_actual'] = $extraValue;
					
				}
				
			}
			return $errors; 
		}
		
		static function confirmThatEverythingInExpectedIsInActual($expected, $actual) {
			
			$output = array();
			foreach($expected as $expectedKey => $expectedValue) {
				
				$actualValue = array_key_exists($expectedKey, $actual) ? $actual[$expectedKey] : 'not_found';
									
				if($expectedValue === $actualValue) {
					
					$output[$expectedKey] = 'ok';
					
				} else {
					$output['expected_type'] = gettype($expectedValue);
					$output['actual_type'] = gettype($actualValue);
					$output[$expectedKey]['expected'] = $expectedValue;
					$output[$expectedKey]['actual'] = $actualValue;
					
				}
				
			}
			
			return $output;
		}		
		
		
		
	}
