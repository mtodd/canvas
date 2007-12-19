<?php
	class Auth2 {
		private $ldap_server = "ldap://ccsunet.clayton.edu";
		private $auth_user = "CN=Directory Reader,cn=Users,DC=ccsunet,DC=clayton,DC=edu";
		private $auth_pass = "CC\$U_DirRead#";
		private $base_dn = "DC=ccsunet, DC=clayton, DC=edu";
		
		// authenticates the user
		public static function authenticate() {
			// get a copy of the session object
			$session = Session::retreive();
			
			// get username from session or from login page
			$session_username = $session->auth['username'];
			$session_password = $session->auth['password'];
			// if it's not in the session data, get it from the login form
			$username = !empty($session_username) ? $session_username : $_POST['login']['username'];
			$password = !empty($session_password) ? $session_password : md5($_POST['login']['password']);
			
			// determine if previously authenticated (in session)
			$ldap = new LDAP();
			if($ldap->find($username, $password)) {
				$session->auth['username'] = $username;
				$session->auth['password'] = $password;
				
				return true;
			} else {
				return false;
			}
		}
		
		// checks privileges
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
?>