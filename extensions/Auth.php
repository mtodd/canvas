<?php
	// @title	Auth class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-12-22
	// @desc	Handles authentication. Simple, no? However, this needs to be
	//			altered to integrate with the current authentication system
	// @requires stdexception.php (StdException class)
	// @requires modles/user.php (User model)
	
	include_once 'extexception.php';
	
	// classes
	class Auth {
		// functions
		public static function authenticate($username, $password) {
			// LDAP authentication for username and password
		}
		
		public static function find_login_or_session_data(&$username, &$password) {
			// retreive the current session
			$session = Session::retreive();
			$session_auth = $session->auth;
			
			if(!empty($session_auth)) {
				$login = $session->auth;
			} elseif(!empty($_POST['login'])) {
				$login = $_POST['login'];
				// Make an MD5 hash of the password from the form:
				// this is a security risk if we just execute a plain query
				// with the password from the form because the password
				// will be stored in the logs (yikes!).
				// Plus, it reduces it down to one query, either from
				// the login form or from sessions!
				$login['password'] = md5($login['password']);
			} else {
				return false;
			}
			
			$username = $login['username'];
			$password = $login['password'];
			
			return true;
		}
		
		public static function authenticated() {
				$session = Session::retreive();
				$auth = $session->auth;
				if(!empty($auth)) return true;
				return false;
		}
		
		public static function check_role($username, $role) {
			$user = new user();
			
			try {
				$user->find_by_username($username);
			} catch(Exception $e) {
				return false;
			}
			
			if($user->role['role'] == $role) return true;
			return false;
		}
	}
	
	class AuthException extends ExtException {}
?>