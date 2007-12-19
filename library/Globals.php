<?php
	// @title	Session class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-09-26
	// @desc	A session class for easily accessing and administering sessions (can
	//		transparently keep all session data in the database)
	// @requires stdexception.php (StdException class)
	
	include_once('stdexception.php');
	
	// constants
	// hash type constants
//	define("AUTH_HASHTYPE_SHA", 2);
//	define("AUTH_HASHTYPE_MD5", 5);
	
	// classes
	class Globals {
		// static functions
		public static function retreive($name) {
			return $GLOBALS[$name];
		}
		public static function store($values) {
			foreach($values as $key=>$value) {
				$GLOBALS[$key] = $value;
			}
		}
		// special functions
		public static function retreive_section($name, $section) {
			return $GLOBALS[$name][$section];
		}
		public static function store_section($section, $property, $value) {
			$GLOBALS[$section][$property] = $value;
		}
		
		// functions
		public function __set($name, $value) {
			$GLOBALS[$name] = $value;
		}
		public function __get($name) {
			return $GLOBALS[$name];
		}
	}
	
	class GlobalsException extends StdException {}
?>