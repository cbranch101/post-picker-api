<?php

	class Shared_Data {
		
		private $cachedDataArray = array();
		
		public function get($dataIndex) {
			
			$requestedData = $this->getAndStoreRequestedData($dataIndex);
			return $requestedData;
			
		}
		
		private function getAndStoreRequestedData($dataIndex) {
			
			if(isset($this->cachedDataArray[$dataIndex])) {
				$requestedData = $this->cachedDataArray[$dataIndex];
			} else {
				$requestedData = call_user_func(array($this, $dataIndex));
				$this->cachedDataArray[$dataIndex] = $requestedData;
			}

			return $requestedData;
		}
		
		public function random_items() {
			return array(
				'right',
				'left',
			);
		}
		
		public function	request_params() {
			return App::$instance->request()->params();
		}
		
		public function dashboard_id() {
			return $this->getFromRequestParams('dashboard_id', null);
		}
		
		public function collection_name() {
			return $this->get('dashboard_id') . '_processed_posts';
		}
		
		public function total_items() {
			$count = MongORM::for_collection(App::$cache->get('collection_name'))
				->find_many()
				->count();
			return $count;
		}
		
		public function random_number_generator() {
			return new Random_Number_Generator();
		}
		
		// randomly select if right should be high or low
		public function right_is_high() {
			$generator = $this->get('random_number_generator');
			$value = $generator->rand(1, 10);
			return $value > 5;
		}
				
		public function getFromRequestParams($key, $emptyValue) {
			$requestParams = $this->get('request_params');
			return __::grab($requestParams, $key, $emptyValue);
		}			
	
	}
