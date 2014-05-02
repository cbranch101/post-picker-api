<?php

class SDK {

	public $facebook = null;
	
	public function initialize($config) {
		
		if($config) {
			// only initalize the sdk if it's already
			if($this->facebook == null) {
				$this->facebook = new Facebook($config);
			}
		} else {
			
			// if there's no sdk and no config
			if($this->facebook == null) {
				throw new Exception("Config array with App Secret and App ID required to initialize");
			}
		}
		
	}
	
	public function transmit($call) {			
		$method = $call['method'];
		$params = $call['params'];
		$relativeURL = $call['relative_url'];
		$response = $this->facebook->api($relativeURL, $method, $params);
		return $response;
	} 

}