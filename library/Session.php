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
	class Session {
		// protected variables
		private $strSID;			// the session ID
		
		// retreive an instance of the Session object (not a singleton, more of a factory of sorts)
		public static function retreive() {
			return new Session();
		}
		
		// initialize
		public static function initialize() {
			// session_id("thehub"); // must be unique
			session_name("thehub");
			session_start();
		}
		public static function flush() {
			session_write_close();
		}
		public static function destroy() {
			$_SESSION = array(); // empty session
			setcookie(session_name(), '', time()-42000, '/'); // destroys associated session cookie
			unset($_COOKIE[session_name()]); // just in case
			session_destroy(); // destroys the session
			session_start(); // overwrite it, yeah!
			
			return true;
		}
		
		public static function flash() {
			if(empty($_SESSION['flash'])) {
				return null;
			} else {
				$flash = $_SESSION['flash'];
				$_SESSION['flash'] = null;
				return $flash;
			}
		}
		public static function continue_to($url) {
			$_SESSION['continue_to'] = $url;
		}
		
		// functions
		public function __set($name, $value) {
			// setcookie($name, $value->username, (15 * 60), ".clayton.edu", "/");
			$_SESSION[$name] = $value;
		}
		public function __get($name) {
			return $_SESSION[$name];
		}
		
		// descructor
		public function __destruct() {
			// possibly put some serialization code in here?
			// end;
		}
	}
	
	class SessionException extends StdException {
	}
?>