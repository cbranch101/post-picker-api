<?php
	
	require_once('sdk.php');
	class FB_Request_Monkey {
	
		const MAX_ITEMS_IN_BATCH = 50;
		
		public static $actionKeyMap = array(
			'query' => 'relative_url',
			'token' => 'access_token',
			'name' => 'name',
			'method' => 'method',
			'params' =>  'params',
			'label' => 'label',
			'relative_url' => 'relative_url',
			'access_token' => 'access_token',
		);
				
		public static $sdk = null;
		public static $testArray = array();
		
		/**
		 * sendOne function.
		 *
		 * Sends a single action
		 * 
		 * @access public
		 * @static
		 * @param array $action the details of a single facebook request
		 * @param array $config (default: null) FB PHP SDK config details
		 * @return array facebook results
		 */
		public static function sendOne($action, $config = null, $options = array()) {
			$actions = array($action);
			return self::sendMany($actions, $config, $options);
		}
						
		/**
		 * sendMany function.
		 *
		 * Sends multiple actions.  If a config array is passed in, the PHP SDK will initialize
		 * 
		 * @access public
		 * @static
		 * @param array $actions an array of action arrays
		 * @param array $config (default: null) FB PHP SDK config details
		 * @param array $options (default: array)
		 * @return array facebook results
		 */
		public static function sendMany($actions, $config = null, $options = array()) {
			
			self::validateActions($actions, $options);
			
			// set allow errors if its in the options array, if not, set it as false
			$allowErrors = isset($options['allowErrors']) ? $options['allowErrors'] : false;
			$failsafeToken = isset($options['failsafeToken']) ? $options['failsafeToken'] : null;
			
			// an access token that has been confirmed to be valid to ensure that a batch request will go out
			self::initialize($config);
			$results = array();
			
			$processedResponses = self::getProcessedResponsesFromActions($actions, $allowErrors, $failsafeToken);
			$results = self::addDataFromProcessedResponsesToResults($processedResponses, $results);
			$overflowActions = self::getOverflowActions($processedResponses);
			
			// if there any overflow actions
			if(count($overflowActions) > 0) {
				$overflowProcessedResponses = self::getProcessedResponsesFromActions($overflowActions, $allowErrors, $failsafeToken);
				
				// because these are overflow requests, the sent result number is inaccurate, so it is set to zero
				// to correct for the discrepency
				$overflowProcessedResponses = self::setSentDataCountToZero($overflowProcessedResponses);
				$results = FB_Request_Monkey::addDataFromProcessedResponsesToResults($overflowProcessedResponses, $results);
				
				// combine the two response sets together so they can be checked
				// for the number of results
				$processedResponses = array_merge($processedResponses, $overflowProcessedResponses);
			}
			self::checkDataCount($processedResponses, $allowErrors);
			return $results;
		}
		
		/**
		 * validateActions function.
		 * 
		 * @access public
		 * @static
		 * @param mixed $actions
		 * @param mixed $options
		 * @return void
		 */
		public static function validateActions($actions, $options) {
			__::each($actions, function($action) use($options){
				if(isset($options['failsafeToken']) && $action['query'] == 'debug_token') {
					throw new Exception("The Facebook API doesn't allow failsafe tokens on debug_token queries, please remove the failsafe token before continuing");
				}
			});
		}
				
		/**
		 * getProcessedResponsesFromActions function.
		 * 
		 *
		 * @access public
		 * @static
		 * @param array $actions
		 * @return array
		 */
		public static function getProcessedResponsesFromActions($actions, $allowErrors, $failsafeToken) {
			$actionCount = count($actions);
			$responseQueue = self::getResponseQueueFromActions($actions, $failsafeToken);
			$processedResponses = self::processResponseQueue($responseQueue, $actionCount, $allowErrors);
			return $processedResponses;
		}
		
		/**
		 * getResponseQueueFromActions function.
		 * 
		 * take the actions send them and get back the response queue
		 * 
		 * @access public
		 * @static
		 * @param mixed $actions
		 * @param mixed $allowErrors
		 * @param mixed $failsafeToken
		 * @return void
		 */
		public static function getResponseQueueFromActions($actions, $failsafeToken) {
			$callQueue = self::getCallQueue($actions);
			$formattedCallQueue = self::formatCallQueue($callQueue, $failsafeToken);
			$responseQueue = self::sendAllCalls($formattedCallQueue, $actions);
			$responseQueue = self::fillInMissingData($responseQueue, $failsafeToken);
			return $responseQueue;
		}
		
		/**
		 * fillInMissingData function.
		 * 
		 * If there are more request in a batch than facebook can handle
		 * at a particular time, they return null instead of the
		 * this function handle resending request to get that missing
		 * data
		 * 
		 * @access public
		 * @static
		 * @param mixed $responseQueue
		 * @return void
		 */
		public static function fillInMissingData($responseQueue, $failsafeToken) {
			$fillInActions = self::getFillInActionsFromResponseQueue($responseQueue);
			$responseQueue = self::addDataFromFillInActionsToResponseQueue($responseQueue, $fillInActions, $failsafeToken);
			return $responseQueue;
		}
		
		public static function addDataFromFillInActionsToResponseQueue($responseQueue, $fillInActions, $failsafeToken) {
			if(count($fillInActions) > 0) {
				$fillInResponseQueue = self::getResponseQueueFromActions($fillInActions, $failsafeToken);
				__::map($fillInResponseQueue, function($responsePackage) use(&$responseQueue){
					$actions = $responsePackage['actions'];
					$batches = $responsePackage['batches'];
					__::map($batches, function($batch, $currentBatchIndex)use(&$responseQueue, $actions){
						$associatedAction = $actions[$currentBatchIndex];
						$responseIndex = $associatedAction['label'][0];
						$batchIndex = $associatedAction['label'][1];
						$responseQueue[$responseIndex]['batches'][$batchIndex] = $batch;
					});
				});
			}
			return $responseQueue;
		}
		
		public static function getFillInActionsFromResponseQueue($responseQueue) {
			$fillInActions = array();
			__::chain($responseQueue)
				->map(function($responsePackage, $responseIndex) use(&$fillInActions){
					$actions = $responsePackage['actions'];
					$batches = $responsePackage['batches'];
					__::map($batches, function($batch, $batchIndex) use($actions, &$fillInActions, $responseIndex){
						if($batch == null) {
							$fillInActions = FB_Request_Monkey::addFillInActionForNullBatch($responseIndex, $batchIndex, $fillInActions, $actions);
						}
					});
				});
			
			return $fillInActions;
		}
		
		public static function addFillInActionForNullBatch($responseIndex, $batchIndex, $fillInActions, $actions) {
			$fillInAction = $actions[$batchIndex];
			$fillInAction['label'] = array(
				$responseIndex,
				$batchIndex,
			);
			array_push($fillInActions, $fillInAction);
			return $fillInActions;
		} 
		
		/**
		 * getOverflowActions function.
		 *
		 * Facebook requests have an inherent limit on the number of results that can be returned
		 * in a single request.  This function iterates over processed responses and generates new actions
		 * to get any results above and beyond the limit for that particular request
		 * 
		 * @access public
		 * @static
		 * @param array $allProcessedResponses
		 * @return array
		 */
		public static function getOverflowActions($allProcessedResponses) {
			return __::chain($allProcessedResponses)
				->map(function($processedResponse) {
					return FB_Request_Monkey::buildOverflowActionsForResponse($processedResponse);
				})
				->flatten(true)
				
				// not all requests have over flow actions
				// eliminate the empty arrays
				->compact()
			->value();
		}
		
		/**
		 * buildOverflowActionsForResponse function.
		 * 
		 * Depending on the number of available results in the response, 
		 * there could be one or more actions needed to get those results.
		 * This function handles generating all of these needed actions
		 * 
		 * @access public
		 * @static
		 * @param array $processedResponse
		 * @return array an array of overflow actions
		 */
		public static function buildOverflowActionsForResponse($processedResponse) {
			$overflowActions = array();
			// if this response has more results than could be returned and it doesn't have any errors
			if($processedResponse['hasMoreResults']) {
				
				// get the action associated with the response
				// so it can be sent again
				$previousAction = $processedResponse['action'];
				
				// the total number of results available on facebook
				$count = $processedResponse['pageData']['count'];
				
				// the max number of results that can be returned in a single request of this type
				// note: this changes based on the type of request
				$limit = $processedResponse['pageData']['limit'];
				$offset = $processedResponse['pageData']['offset'];
				
				$currentOffset = $offset;
				
				// while there are results remaining to generate overflow actions for
				while(($currentOffset + $limit) < $count) {
					$currentOffset += $limit;
					array_push($overflowActions, self::buildOverflowAction($previousAction, $currentOffset));
				}
			}
			return $overflowActions;
		}
				
		/**
		 * buildOverflowAction function.
		 *
		 * Passes the updated offset into the params array of a previous action
		 * So it can be resent as an overflow action
		 * 
		 * @access protected
		 * @param array $previousAction
		 * @param int $currentOffset
		 * @return array
		 */
		protected function buildOverflowAction($previousAction, $currentOffset) {
			
			$params = isset($previousAction['params']) ? $previousAction['params'] : array();
			$params['offset'] = $currentOffset;
			$previousAction['params'] = $params;
			return $previousAction;
			
		}
				
		/**
		 * processResponseQueue function.
		 *
		 * Responses can come back from Facebook in a variety of ways.  They can be batched or unbatched. 
		 * They can have data detailing counts and limits, or not, among other details.  This function takes
		 * these responses in a variety of different formats, and conditionally processes them so they're all in a uniform
		 * format that can be used by other functions later on the program
		 * 
		 * @access public
		 * @static
		 * @param array $responseQueue
		 * @param array $allowErrors
		 * @return array
		 */
		public static function processResponseQueue($responseQueue, $actionCount, $allowErrors) {
			$allProcessedResponses = __::chain($responseQueue)
				
				// iterate over all the returned response packages
				->map(function($responsePackage) use($allowErrors){
					
					// get all of the actions
					$actions = $responsePackage['actions'];
										
					// get all of the batches in this response package 
					$batches = $responsePackage['batches'];
					
					$processedBatches = FB_Request_Monkey::processBatches($batches, $actions, $allowErrors);
					return $processedBatches;
					// if its a single response
				})
			->flatten(true)
			->value();
			// if there are multiple responses, flatten them into a single array
			
			return $allProcessedResponses;
		}
		
		public static function processBatches($batches, $actions, $allowErrors) {
			$responseIndex = 0;
			$processedBatches = __::chain($batches)
				
				// iterate over the responses
				->map(function($batch) use(&$responseIndex, $actions, $allowErrors) {
					
					// get the action associated
					$action = $actions[$responseIndex];
					$processedResponse = FB_Request_Monkey::processBatch($batch, $action, $allowErrors);
					$responseIndex++;
					return $processedResponse;
				})
			->value();
			return $processedBatches;
		}
		
		/**
		 * addDataFromProcessedResponseToResults function.
		 * 
		 * Gets the results out of the response and adds them to overall collections of results
		 * If there's an applied label for the action associated with this response
		 * add the data to the key for that label
		 * if there's not, key it as 'data'
		 * 
		 * @access public
		 * @static
		 * @param array $processedResponse
		 * @param array $results
		 * @return array
		 */
		public static function addDataFromProcessedResponseToResults($processedResponse, $results) {
			$action = $processedResponse['action'];
			
			// get the label for the current resposne from the associated action
			$label = isset($action['label']) ? $action['label'] : 'data';
			
			if(is_array($label)) {
				$labels = $label;
			} else {
				$labels = array($label);
			}
						
			// get the needed data out of the response
			$result = $processedResponse['data'];
			$updatedResults = self::recursivelyAddLabelsToResults($labels, $result, $results);
			return $updatedResults;
		}
		
		/**
		 * recursivelyAddLabelsToResults function.
		 *
		 * Recursively add the results for the data into the correct labels
		 * 
		 * @access public
		 * @static
		 * @param mixed $labels
		 * @param mixed $resultToAdd
		 * @param mixed $currentLevel
		 * @return void
		 */
		public static function recursivelyAddLabelsToResults($labels, $resultToAdd, $currentLevel) {
			// get the current label
			$currentLabel = array_shift($labels);
			// if there are any labels left
			if(count($labels) > 0) {
				
				// if the key isn't set, set it with an empty array
				if(!isset($currentLevel[$currentLabel])) {
					$currentLevel[$currentLabel] = array();
				}
				// because there are more labels, call the function again
				$currentLevel[$currentLabel] = self::recursivelyAddLabelsToResults($labels, $resultToAdd, $currentLevel[$currentLabel]);
				return $currentLevel;
			
			// this is the last level
			} else {
				
				// if the key isn't set, set it with an empty array
				if(!isset($currentLevel[$currentLabel])) {
					$currentLevel[$currentLabel] = array();
				}
				
				// add the results to this level
				$currentLevel[$currentLabel] = array_merge($currentLevel[$currentLabel], $resultToAdd);
				return $currentLevel;
			}
		}
		
		/**
		 * sendAllCalls function.
		 *
		 * If there are more than 50 actions being sent, more than one actual request will be sent to Facebook
		 * This handles sending all of these requests and getting the response, handles some basic processing
		 * on the response so I can be properly handled further down the line
		 * 
		 * @access public
		 * @static
		 * @param array $formattedCallQueue
		 * @param array $actions
		 * @return array
		 */
		public static function sendAllCalls($formattedCallQueue, $actions) {
			
			$responsePackages = __::map($formattedCallQueue, function($formattedCall) use($actions) {
				
				// is this a batch request or not
				$batches = FB_Request_Monkey::transmit($formattedCall);
				$responsePackage =  array(
					'batches' => $batches,
					'actions' => $formattedCall['actions'],
				);
				return $responsePackage;
			});
			return $responsePackages;
		}
				
		/**
		 * processBatch function.
		 *
		 * Identifies if the response has only a single item, gets and stores the count, offset and limit
		 * gets the data for the response, identifies if the response has more results
		 * 
		 * @access public
		 * @static
		 * @param array $response
		 * @param boolean $isBatched
		 * @param array $action
		 * @return array
		 */
		public static function processBatch($batch, $action, $allowErrors) {
			
			$processedBatch = array();
			$processedBatch['action'] = $action;
			$hasOneItem = false;
			$returnedDataCount = 0;
			$hasErrors = false;
			$hasMoreResults = false;
			
			$count = null;
			$limit = null;
			
			// check if there are errors in the response
			
			
			// the wrapper that data goes in is json_encoded in 
			// batch responses, but not in single responses
			$body = json_decode($batch['body'], true);
			// if its batched the count is wrapped in a 'body' key
			
			$processedBatch = self::handleErrors($processedBatch, $batch, $action, $allowErrors);
			
			// certain types of requests have their data stored in a data key, others don't
			// this handles this different behavior
			$data = isset($body['data']) ? $body['data'] : $body;
			
			// if there's only one item, calling count on the data array will return
			// an incorrect results, 
			$processedBatch['hasOneItem'] = $hasOneItem;
			
			
			
			// if there's a count and limit specified, get them
			// if not, set them as if there's a single result being returned
			if(isset($body['count']) && isset($body['limit'])) {
				$count = $body['count'];
				$returnedDataCount = count($data);
				$limit = $body['limit'];
				$offset = $body['offset'];
			} else {
				$count = 1;
				$returnedDataCount = 1;
				$limit = 1;
				$offset = 0;
			}
			
			$hasMoreResults = $count > $limit;
			
			// add the needed variables into the response
			$processedBatch['pageData'] = array(
				'offset' => $offset,
				'count' => $count,
				'limit' => $limit,
				'sentDataCount' => $count - $offset,
				'returnedDataCount' => $returnedDataCount,
			);
				
			$processedBatch['hasMoreResults'] = $hasMoreResults;
			$processedBatch['data'] = $data;
			return $processedBatch;
		} 
		
		public static function handleErrors($processedBatch, $batch, $action, $allowErrors) {
			$hasErrors = self::batchHasErrors($batch);
			$processedBatch['hasErrors'] = $hasErrors;
			// if there's an error in the response
			if($hasErrors) {
				
				// if we don't want to throw error
				if(!$allowErrors) {
					self::generateException($batch, $action);
				}
				
			}
			return $processedBatch;
		}
		
		/**
		 * batchResponseHasErrors function.
		 *
		 * Check if their are error is the batch response
		 *
		 * @access public
		 * @static
		 * @param array $response
		 * @return void
		 */
		public static function batchHasErrors($batch) {
			$code = $batch['code'];
			$body = json_decode($batch['body'], true);
			return $code != 200;
		}
		
		/**
		 * generateException function.
		 * 
		 * Turn a batch result that contains an error into an exception
		 * 
		 * @access public
		 * @static
		 * @param array $response
		 * @param array $action
		 * @return void
		 */
		public static function generateException($response, $action) {
			$output = json_encode($action);
			$code = $response['code'];
			$responseBody = json_decode($response['body'], true);
			
			// make sure theres actually a message set
			$messagePiece = isset($responseBody['error']['message']) ? $responseBody['error']['message'] : "Facebook API $code error";
			$message = "$messagePiece in the following action: $output";
			throw new Exception($message); 
		}
				
		/**
		 * addDataFromProcessedResponsesToResults function.
		 *
		 * Get the results from the processed responses array and stored them in the correct format
		 * 
		 * @access public
		 * @static
		 * @param array $processedResponses
		 * @param array $results
		 * @return array
		 */
		public static function addDataFromProcessedResponsesToResults($processedResponses, $results) {
			__::each($processedResponses, function($processedResponse) use(&$results) {
				$results = FB_Request_Monkey::addDataFromProcessedResponseToResults($processedResponse, $results);
			});
			return $results;
		}
		
		/**
		 * transmit function.
		 *
		 * Use the FB PHP SDK to send the request
		 * 
		 * @access public
		 * @static
		 * @param array $call
		 * @return array facebook data
		 */
		public static function transmit($call) {			
			return self::$sdk->transmit($call);
		}
		
		public static function initialize($config) {
			
			if(self::$sdk) {
				
			} else {
				self::$sdk = new SDK();
				self::$sdk->initialize($config); 
			}
			
		}
		
		/**
		 * setSentDataCountToZero function.
		 *
		 * At the end of the transmission process, a function will confirm that the
		 * correct number of results are being returned. In the case of overflow actions, 
		 * you don't want to count the results in that particular response twice, so set 
		 * its number of contained results to zero
		 * 
		 * @access public
		 * @static
		 * @param array $processedResponse
		 * @return array
		 */
		public static function setSentDataCountToZero($processedResponse) {
			return __::map($processedResponse, function($response){
				$response['pageData']['sentDataCount'] = 0;
				return $response;
			});
		}
		
		/**
		 * checkDataCount function.
		 *
		 * Make sure that all of the expected results are being returned, 
		 * If not, throw an exception
		 * 
		 * @access public
		 * @static
		 * @param mixed $processedResponses
		 * @return void
		 */
		public static function checkDataCount($processedResponses, $allowErrors) {
			if(!$allowErrors) {
				$reducedCounts = self::getReducedCounts($processedResponses);
				$totalSent = $reducedCounts['total_sent'];
				$totalReturned = $reducedCounts['total_returned'];
				if($totalSent != $totalReturned) {
					$problemAmount = abs($totalSent - $totalReturned);
					$messagePiece = $totalSent > $totalReturned ? 'results missing' : 'extra results being returned';
					$message = "Result Count Error: There are $problemAmount $messagePiece.";
					throw new Exception($message);
				}
			}
		}
		
		/**
		 * getReducedCounts function.
		 * 
		 * Reduce the processed responses to the total number of sent and received requests
		 * 
		 * @access public
		 * @static
		 * @param array $processedResponses
		 * @return array
		 */
		public static function getReducedCounts($processedResponses) {
			$reducedCounts = self::fancyReduce($processedResponses, function($next){
				$returns = array();
				$returns['total_sent'] = $next['pageData']['sentDataCount'];
				$returns['total_returned'] = $next['pageData']['returnedDataCount'];
				return $returns;
			}, function($prev, $next){
				$prev['total_sent'] += $next['pageData']['sentDataCount'];
				$prev['total_returned'] += $next['pageData']['returnedDataCount'];
				return $prev;
			});
			return $reducedCounts;
		}
		
		/**
		 * getCallQueue function.
		 *
		 * Turns actions into a discrete array of calls to be sent to facebook
		 * Only 50 actions can be in a single call, and batch calls need to be formatted different than
		 * single calls.  This function handles this processing
		 * 
		 * @access public
		 * @static
		 * @param array $actions
		 * @return array
		 */
		public static function getCallQueue($actions) {
			$batch = array();
			$callQueue = array();
			
			__::each($actions, function($action) use(&$batch, &$callQueue) {
				$action = FB_Request_Monkey::keyMapAction($action);
				$returns = FB_Request_Monkey::addActionToCallQueue($action, $callQueue, $batch);
				$batch = $returns['batch'];
				$callQueue = $returns['call_queue'];
			});
			
			
			// if there's a left over batch,
			// add it
			if(count($batch) > 0) {
				array_push($callQueue, $batch);
			}
			return $callQueue;
		}	
		
		/**
		 * keyMapAction function.
		 * 
		 * Convert the user friendly inputs into the specific keys that facebook needs to use
		 * 
		 * @access public
		 * @static
		 * @param array $action
		 * @return array
		 */
		public static function keyMapAction($action) {
			$results = __::map($action, function($value, $key){
				$mappedKey = FB_Request_Monkey::$actionKeyMap[$key];
				return array($mappedKey => $value);
			});
			return __::flatten($results, true);
		}
		
		/**
		 * addActionToCallQueue function.
		 * 
		 * Adds an action to a batch, adds the batch to the call queue
		 * 
		 * @access public
		 * @static
		 * @param mixed $action
		 * @param mixed $callQueue
		 * @param mixed $batch
		 * @return void
		 */
		public static function addActionToCallQueue($action, $callQueue, $batch) {
					
			// add action to batch
			array_push($batch, $action);
			
			if(self::batchIsFull($batch)) {
				
				// add the batch to the call queue
				array_push($callQueue, $batch);
				
				// reset the batch
				$batch = array();
			
			}
			$returns = array(
				'call_queue' => $callQueue,
				'batch' => $batch,
			);
			return $returns;		
		}
				
		/**
		 * batchIsFull function.
		 *
		 * Can the batch hold anymore actions? 
		 * 
		 * @access public
		 * @static
		 * @param array $batch
		 * @return boolean
		 */
		public static function batchIsFull($batch) {
			return count($batch) >= self::MAX_ITEMS_IN_BATCH;
		}
		
		/**
		 * formatCallQueue function.
		 * 
		 * iterate over the call queue and convert it into the format
		 * needed to be transmitted by the facebook SDK
		 * @access public
		 * @static
		 * @param array $callQueue
		 * @param string $failsafeToken
		 * @return array
		 */
		public static function formatCallQueue($callQueue, $failsafeToken) {
			
			return __::map($callQueue, function($call) use($failsafeToken){
				// if there are more than one actions in the call
				return FB_Request_Monkey::formatCall($call, $failsafeToken);
			});
		}
							
		/**
		 * formatMultiActionCall function.
		 * 
		 * @access public
		 * @static
		 * @param array $call
		 * @param string $failsafeToken
		 * @return array
		 */
		public static function formatCall($call, $failsafeToken) {
			return array(
				'method' => 'POST',
				'relative_url' => '',
				'params' => self::getBatchParams($call, $failsafeToken),
				'actions' => $call,
			);
		}
		
		/**
		 * formatRelativeURL function.
		 * 
		 * Add a forward slash to a the relative url
		 * so you don't have have to pointlessly include it
		 * Don't do this if its an empty url
		 * 
		 * @access public
		 * @static
		 * @param string $relativeURL
		 * @return string
		 */
		public static function formatRelativeURL($relativeURL) {
			return "/$relativeURL";
		}
		
		/**
		 * getBatchParams function.
		 * 
		 * Converts actions in an array that will be passed in params
		 * array of batch calls
		 * 
		 * @access public
		 * @static
		 * @param array $call
		 * @param string $failsafeToken
		 * @return array
		 */
		public static function getBatchParams($call, $failsafeToken) {
			
			$preparedActions = __::map($call, function($action) use($failsafeToken){
				$name = isset($action['name']) ? $action['name'] : null;
				$params = isset($action['params']) ? $action['params'] : array();
				$method = $action['method'];
				$relativeURL = $action['relative_url'];
				// add the access token to the params
				$params['access_token'] = $action['access_token'];
				$params = FB_Request_Monkey::handleBoundaryQueriesInParams($relativeURL, $params);
				$relativeURL = FB_Request_Monkey::addParamsToRelativeURL($relativeURL, $params);
				$preparedAction = array(
					'method' => $method,
					'relative_url' => FB_Request_Monkey::formatRelativeURL($relativeURL),
				);
				if($name) {
					$preparedAction['name'] = $name;
				}
				return $preparedAction;
			});
			
			$batchParams = array(
				'batch' => $preparedActions,
			);	
			
			if($failsafeToken !== null) {
				$batchParams['access_token'] = $failsafeToken;
			}

			return $batchParams;
		}
		
		/**
		 * handleBoundaryQueries function.
		 *
		 * Certain FB queries have erratic behavior that needs to be handled
		 * in different ways, the processing for handling them happens here
		 * 
		 * @access public
		 * @static
		 * @return void
		 */
		public static function handleBoundaryQueriesInParams($relativeURL, $params) {
			$boundaryQueryMap = array(
				'debug_token' => function($relativeURL, $params){
					unset($params['access_token']);
					return $params;
				},
			);
			
			if(isset($boundaryQueryMap[$relativeURL])) {
				$params = $boundaryQueryMap[$relativeURL]($relativeURL, $params); 
			}
			return $params;
		}
		
		/**
		 * addParamsToRelativeURL function.
		 * 
		 * the params in batched actions needed to be added directly into the relativeURL
		 * this adds them to the relative URL
		 * 
		 * @access public
		 * @static
		 * @param string $relativeURL
		 * @param array $params (default: null)
		 * @return string
		 */
		public static function addParamsToRelativeURL($relativeURL, $params) {
			if(count($params) > 0) {
				$encodedParams = self::jsonEncodeNonStringValues($params);
				$convertedParams = self::convertParamsToURL($encodedParams);
				$relativeURL .= $convertedParams;
			}
			return $relativeURL;
		}
				
	    /**
	     * convertParamsToURL function.
	     * 
	     * @access public
	     * @static
	     * @param array $params
	     * @return array
	     */
	    public static function convertParamsToURL($params) {
	    	return '?' . http_build_query($params, null, '&');
	    }
		
	    /**
	     * jsonEncodeNonStringValues function.
	     * 
	     * @access public
	     * @static
	     * @param array $array
	     * @return array
	     */
	    public static function jsonEncodeNonStringValues($array) {
		    foreach ($array as $key => $value) {
		      if (!is_string($value)) {
		        $array[$key] = json_encode($value);
		      }
	    	}
	    	return $array;
	    }
	    
		/**
		 * fancyReduce function.
		 * 
		 * @access public
		 * @static
		 * @param array $array
		 * @param closure $onFirst
		 * @param closure $onNext
		 * @return mixed
		 */
		static function fancyReduce($array, $onFirst, $onNext) {
			return __::reduce($array, function($prev, $next) use($onFirst, $onNext){
				if($prev) {
					return call_user_func($onNext, $prev, $next);
				} else {
					return call_user_func($onFirst, $next);
				}
			});
		}
	}
