<?php
	// @role		Controller abstract class
	// @title		Controller Abstract Class
	// @author		Matt Todd
	// @date		2005-11-26 5:26PM
	// @desc		Handles performing the actions required by the requests.
	
	include_once('stdexception.php');
	
	// classes
	abstract class Controller extends Canvas { // should implement the IController interface
		// public variables
		public $controller;		// this controller
		public $action;			// the action // defaults to 'index'
		public $id;				// the action ID
		
		public $properties;
		public $response;
		public $flash;
		
		// protected variables
		protected $request;		// the request
		protected $session;		// the session
		
		// action modification properties
		protected $skip_authentication = array();
		protected $authenticate = array();
		
		// error variables
		public $error;			// the error object
		
		// constructor // consider making this 'final'
		public function __construct(Request $request) {
			$this->request = $request;
			$this->session = new Session();
			$this->config = $this->load_config();
			$this->response = new Response();
			foreach($this->request->route->mapping() as $routing_symbol) {
				$this->$routing_symbol = $this->request->route->$routing_symbol;
			}
			$this->environment = Config2::$environment;
		}
		
		// dispatcher
		/*	@note		If the user defined 'dispatch' as a controller action, it could
						be disasterous. Instead, make it final so that it is not able
						to be overridden.
			@consider	Consider changing this from 'dispatch' to '_dispatch' or even
						'__dispatch', to simulate the 'magic methods' for classes,
						like '__get' and '__call'.
						Consider handling action response (for view rendering) in a
						different way.
			@summary	Dispatcher handles the majority of the overhead logic, such as
						making sure the appropriate instance variables are set correctly,
						executing the default handles/events/callbacks, and actually
						calling the requested action's function (transparently).
						Currently, the response of the $action is put in the $response
						instance variable, within the array index of ['response'].
		*/
		final public function dispatch($action, $id = null, $request = null) {
			if(empty($action)) $action = "index";
			$this->action = $action;
			$this->id = $id;
			
			// execute authentication & session handling event
			$this->authenticate_request();
			$this->handle_sessions();
			
			$this->before_action(); // callback
			
			try {
				// dispatch action, calling the exception handler if an exception
				// which is thrown is not caught
				// $this->$action($id); // deprecated because it only supports one single param
				call_user_func(array($this, $action), $this->request->params());
			} catch(Exception $e) {
				$this->handle_exception($e);
			}
			
			if($this->after_action_performed != true) {
				$this->after_action(); // callback (if not already performed)
				$this->after_action_performed = true;
			}
			
			// render (if not already rendered; also, consider layouts in views)
			if(!$this->rendered) $this->render();
			
			return $this->response;
		}
		
		// events
		protected function authenticate_request() {
			try { // check if the action is to be authenticated... skip it if it isn't
				if(is_array($this->skip_authentication) && !empty($this->skip_authentication) && !in_array($this->action, $this->skip_authentication)) {
					return $this->authenticate();
				} elseif(is_array($this->authenticate) && !empty($this->authenticate) && in_array($this->action, $this->authenticate)) {
					return $this->authenticate();
				} else {
					// dep // $this->redirect_to(array("controller"=>"admin")); // handled within 'authenticate()'
				}
			} catch(Exception $e) {
				$this->handle_exception($e);
			}
		}
		protected function authenticate() {
			// this function is to be overwritten only if authentication is deemed necessary
			return true;
		}
		
		protected function handle_exception($e) {
			$exception_type = get_class($e); // will be the type of exception that was thrown
			$handler = !empty($this->exception_handlers[$exception_type]) ? $this->exception_handlers[$exception_type] : $this->exception_handlers['*'];
			if(!empty($handler)) {
				$this->$handler($e);
			} else {
				Debug::generic_exception_handler($e);
			}
			
			// terminate execution on exception after handling exception
			die();
		}
		
		protected function handle_sessions() {
			// this function is to be overwritten only if specific session control is deemed necessary (rare!)
			// this will require updating sessions and everything, particularly for flashes
		}
		
		public function __call($name, $params) {
			// @consider	Consider handling dynamic requests.
			
			// catch-all function (throw an error)
			throw new Exception("The <em>{$this->request->action}</em> action does not exist");
		}
		
		// renderers
		protected function render($params = null) {
			// if the after_action() hasn't been performed yet (by the action explicitly calling the render() method), call it now
			if($this->after_action_performed != true) {
				$this->after_action();
				$this->after_action_performed = true;
			}
			
			$this->rendered = true; // so that multiple render calls aren't made (such as when the user calls render() and the dispatcher knows not to call render() too)
			
			$this->before_render(); // callback
			
			// so that if no $params were specified, it would still be an array and an access violation
			// wouldn't occur below when $params['foo'] was accessed
			if($params == null) $params = array();
			
			// render results to screen
			// passes important data to templates
			// maybe just creates the View object and does these things? yeah
			// or, even better, create Response and send it, from the main dispatcher, to a view object?
			
			// actually, respond with the template file
			// also should provide the a 'url' and the basics of the header/caller information as properties of the 'response' object/[array]
			$this->response->layout = (!empty($params['layout'])) ? "{$params[layout]}.php" : (file_exists("views/{$this->controller}/layout.php") ? "layout.php" : '../layout.php');
			if($params['layout'] == "null") $this->response->layout = null;
			if(empty($this->response->template)) $this->response->template = (!empty($params['template'])) ? "{$params[template]}.php" : "{$this->action}.php"; // for smarty, took out 'views/{$this->controller}/' before because smarty keeps the template dir internally
			$this->response->url = "{$this->controller}/{$this->action}/{$this->id}";
			$this->response->controller = $this->controller;
			$this->response->action = $this->action;
			$this->response->id = $this->id;
			$this->response->request = $this->request;
			$this->response->flash = Session::flash();
			
			View::render($this->response, $params);
			
			$this->after_render(); // callback
		}
		protected function render_partial($partial, $no_layout = true) {
			$this->render(array("template"=>$partial, "layout"=>($no_layout ? "null" : null)));
		}
		protected function render_template($template, $no_layout = false) {
			$this->render(array("template"=>$template, "layout"=>($no_layout ? "null" : null)));
		}
		protected function render_layout($layout) {
			$this->render(array("layout"=>$layout));
		}
		protected function redirect_to($request, $flash = null) {
			$this->before_redirect(); // callback
			
			// $this->response->flash = Session::flash();
			$_SESSION['flash'] = $this->flash;
			
			// if there's continuance to be done in the future, put it in the session data
			if(!empty($request['continue_to'])) {
				$this->session->continue_to = $request['continue_to'];
				$this->session->continue_to($request['continue_to']);
			}
			
			// if the direct url hasn't been set, do normally... otherwise if it has been
			if(empty($request['url'])) {
				// redirect to the appropriate protocol if specified
				if(!empty($request['protocol'])) $protocol = $request['protocol']; else $protocol = $this->request->protocol;
				unset($request['protocol']);
				
				// location
				$location = "{$protocol}://{$this->request->host}/{$this->request->directory}";
				
				// assemble route
				$route = Router2::url_for($request);
				$url = '%s%s';
				$request = sprintf($url, $location, $route);
				
				// set defaults if not set
				// if(empty($controller)) $controller = $this->controller;
				// shouldn't happen... should forward to default action // if(empty($action)) $action = $this->action; // probably won't happen... doesn't make sense, does it?
				// 
				// assemble request
				// 
				// $request = "{$controller}/{$action}/{$id}";
				// if(empty($id)) $request = "{$controller}/{$action}";
				// if(empty($action)) $request = "{$controller}/";
			} else {
				$request = $request['url'];
			}
			
			// handle session closing to prevent data loss
			Session::flush();
			
			// actually redirect
			header("Location: {$request}");
			
			// not sure if this will ever happen, but you never know
			$this->after_redirect(); // callback
			
			// log redirection
			$execution_time = round(microtime(true) - $GLOBALS['dispatch_time_begin'], 5);
			$execution_memory = round(memory_get_usage()/1024, 2);
			Debug::log("Redirected {$_SERVER['PATH_INFO']} to {$path} ({$execution_time}sec/{$execution_memory}kb)", 'internal', 'notice', 'Controller');
			
			// kill the rest of execution for this request
			die("Action calls for redirection. Click <a href='{$request}'>here</a> if you are not automatically forwarded.");
		}
		
		// ajax response
		protected function ajax_response($response) {
			// log remote request
			$execution_time = round(microtime(true) - $GLOBALS['dispatch_time_begin'], 5);
			$execution_memory = round(memory_get_usage()/1024, 2);
			Debug::log("Remote request {$_SERVER['PATH_INFO']} ({$execution_time}sec/{$execution_memory}kb)", 'internal', 'notice', 'Controller');
			
			if(is_array($response)) {
				// full on xml response
				$this->response->response_id = $response['id'];
				$this->response->units = $response['units'];
				$this->render(array(
					'template'=>'../templates/ajax_response',
					'layout'=>'null',
				));
			} else {
				// plain text response
				die($response);
			}
		}
		
		// property handler
		// @desc		These allow for developers to simply assign values to $this->foo
		//			and have them automatically sent to the Response object and,
		//			ultimately, the View object.
		protected function __get($key) {
			return $this->properties[$key];
			return $this->response->$key;
		}
		protected function __set($key, $value) {
			return $this->properties[$key] = $value;
			return $this->response->$key = $value;
		}
		
		// flash function (which replaces just using $this->flash = array())
		protected function flash($message, $class, $params = array()) {
			// graft the message and the class into optionally-specified the $params array (whereby more data can be passed on)
			$params['message'] = $message;
			$params['class'] = $class;
			$this->flash = $params;
		}
		
		// events/callbacks
		protected function before_action() {}
		protected function after_action() {}
		protected function before_render() {} // @consider	Consider moving this to the View class, or at least calling before_render() of the View as well... maybe
		protected function after_render() {}
		protected function before_redirect() {}
		protected function after_redirect() {} // this is executed right before the header() call and the die()
		
		// descructor
		public function __destruct() {
			// possibly put some serialization code in here?
			
			$_SESSION['flash'] = $this->flash;
			
			session_write_close();
			
			// end;
		}
	}
?>