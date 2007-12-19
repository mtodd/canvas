<?php
	// @title	Response class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-11-27 
	// @desc	Responds to the controller
	// @requires stdexception.php (StdException class)
	
	include_once('stdexception.php');
	
	class Response {
		// public variables
		public $response = array();		// container for the response data
		public $error = null;			// error object
		
		// functions
		public function __set($name, $value) {
			return $this->response[$name] = $value;
		}
		public function __get($name) {
			return $this->response[$name];
		}
		
		public function respond() {
			return $this->response;
		}
	}
	
	class ResponseException extends StdException {
	}
?>