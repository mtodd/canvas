<?php
	// @title	 			Generic Application Controller
	// @author			Matt Todd
	// @created_on	2005-12-28
	// @desc				This controller class will be included in every controller the
	//							user defines for functionality. Add functions in this class to
	//							be used site-wide
	
	class application_controller extends Controller {
		protected $skip_authentication = array('login', 'logout'); // functions to not authenticate
		protected $exception_handlers = array('NotLoggedIn'=>'require_login', 'PostNotFound'=>'post_not_found', '*'=>'exception_handler');
		
		protected $default_controller	= 'blog';
		protected $default_action			= 'index';
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		// exception handling /////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		protected function exception_handler($e) {
			// log error, set flash, and redirect to index
			Debug::log($e->getMessage(), 'exception_handler', 'error', get_class($this));
			$this->flash = array('message'=>'Error: ' . $e->getMessage(), 'class'=>'Bad');
			$this->redirect_to(array('controller'=>$this->default_controller, 'action'=>$this->default_action));
			
			// Debug::generic_exception_handler($e); // this is ugly, and only for development!
		}
		
		// specific exception handling ////////////////////////////////////////////////////////////////////////////
		
		protected function require_login($e) {
			$this->session->continue_to = $this->request->url;
			$this->flash = array('message'=>$e->getMessage(), 'class'=>'Bad');
			$this->redirect_to(array('controller'=>$this->default_controller, 'action'=>$this->default_action));
		}
		
		protected function post_not_found($e) {
			$this->flash = array('message'=>$e->getMessage(), 'class'=>'Bad');
			$this->redirect_to(array('controller'=>$this->default_controller, 'action'=>$this->default_action));
		}
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		// authentication /////////////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		// global authentication and session handler
		protected function authenticate() {
			// check for previous user authentication
			$user = new user();
			$user->find_by_username_and_password($this->session->auth->username, $this->session->auth->password);
			
			if(!$user->is_empty()) {
				// authenticated
				$this->session->auth = $user; // array('user'=>array('username'=>$user->username, 'password'=>$user->password, 'id'=>$user->id, 'role'=>$user->role));
				$this->user = $user;
				return true;
			} else {
				// just skip authentication
				return true;
			}
		}
		
		public function login() {
			// if login form has been submitted, process... otherwise, display the login form/page
			$login = $this->request->post->login;
			if(empty($login)) {
				
				// send to the index page
				$this->flash('...', 'Info');
				$this->session->continue_to = $this->request->continue_to; // remembering continuation
				$this->redirect_to(array('controller'=>$this->default_controller, 'action'=>$this->default_action));
				
			} else {
				
				// authenticate user
				$user = new user();
				if(!$user->find_by_username_and_password($login['username'], md5($login['password']))->is_empty()) {
					// store user data in session
					$this->session->auth = $user;
					$this->user = $user;
					
					// display 'logged in' on the next (visible) page load
					$this->flash('Logged in!', 'Good');
					
					// if there was a previous request, go there instead of the main index
					if(!empty($this->request->continue_to)) {
						$this->redirect_to(array('url'=>$this->request->continue_to));
					} else
						$this->redirect_to(array('controller'=>$this->default_controller, "action"=>$this->default_action));
				} else {
					// did not authenticate, so display error message and take user to login screen
					$this->flash("Login failed", "Bad");
					$this->redirect_to(array('controller'=>$this->default_controller, "action"=>$this->default_action));
					
					// halt request
					die();
					
				}
			}
		}
		public function logout() {
			// kill the session
			Session::destroy();
			
			// display 'logged out' and redirect to the login page
			$this->flash("Logged out", "Good");
			$this->redirect_to(array('controller'=>$this->default_controller, "action"=>$this->default_action));
		}
		
		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		// events and callbacks ///////////////////////////////////////////////////////////////////////////////////
		///////////////////////////////////////////////////////////////////////////////////////////////////////////
		
		// add in data (after all other processing) for forms, et al
		public function after_action() {
			// use this to respond to the views with whatever data is necessary (on every page, usually)
		}
	}
	
	class NotLoggedIn extends Exception {}
	class InsufficientPrivilegesException extends Exception {}
?>