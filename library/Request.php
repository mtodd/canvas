<?php
	
	// @title	Request class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-10-14
	// @desc	A class to handle Request variables
	// @requires stdexception.php (StdException class)
	// @refer_to	http://www.php.net/manual/en/function.parse-str.php for parsing
	//		the URL parameters following ? before the # (e.g.: x?param=val#z)
	
	include_once('stdexception.php');
	
	// classes
	class Request {
		// original request
		public $url;
		
		// primary public variables
		public $route;						// the route requested (array of parameters passed in)
		public $controller;				// the Controller requested
		public $action;						// the Controller's requested Action
		public $id;								// the Id to be acted upon by the Controller's Action
		
		// location variables
		public $protocol;					// e.g.: 'http' or 'https'
		public $location;					// e.g.: 'http://anything.clayton.edu/omnia/files/index' // does not add params on to end
		public $host;							// e.g.: 'http://anything.clayton.edu/'
		public $directory;				// e.g.: 'omnia/'
		public $request;					// e.g.: 'files/list'
		public $params_string;		// e.g.: '?extra=foo' -> array of values
		public $params;						// array parsing of $_params
		public $redirect_url;			// e.g.: '/mvc/admin/'
		public $referrer;					// the 'sender'
		
		// continuation
		public $continue_to;			// e.g.: 'http://anything.clayton.edu/omnia/files/show/124'
		
		// request type
		public $request_type;			// e.g.: POST, GET, or XHR (XMLHTTPRequest)
		public $xmlhttprequest;		// a remote call
		public $xhr;							// alias of above
		public $remote;						// alias of above
		
		// protected variables
		public $request_url;			// the request (ie: the URL)
		public $get;							// PHP magic $_GET variable
		public $post;						// PHP magic $_POST variable
		public $cookie;					// PHP magic $_COOKIE variable
		public $files;						// PHP magic $_FILES variable
		
		// error variables
		public $error;						// error object
		
		// constructor
		public function __construct($request_url) {
			// set request string
			$this->request_url = $request_url;
			$this->process_request($this->request_url);
			
			// set default values
			$this->post = new RequestIn($_POST);
			$this->get = new RequestIn($_GET);
			$this->cookie = new RequestIn($_COOKIE);
			$this->files = new RequestIn($_FILES);
			$this->server = new RequestIn($_SERVER);
			$this->error = null; // why?
			
			// set location data
			$this->protocol = (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] == 'off') ? "http" : "https";
			$this->host = $_SERVER['HTTP_HOST'];
			$this->directory = substr(str_replace("dispatcher.php", "", $_SERVER['SCRIPT_NAME']), 1);
			$this->request = substr($_SERVER['PATH_INFO'], 1);
			$this->params_string = $_SERVER['QUERY_STRING']; // http://www.php.net/manual/en/function.parse-str.php
			parse_str($this->params_string, $params); // creates an associative array of the params
			$this->params = $params;
			$this->location = "{$this->protocol}://{$this->host}/{$this->directory}";
			$this->redirect_url = $_SERVER['REDIRECT_URL'];
			$this->referrer = $_SERVER['HTTP_REFERER'];
			
			// if there was a continue_to parameter passed in after redirection, set the property
			$session = Session::retreive();
			$this->continue_to = $session->continue_to;
			$session->continue_to = null;
			
			// set properties of a remote call if applicable
			if(!empty($_REQUEST['remote']) || !empty($_REQUEST['xhr']) || !empty($_REQUEST['xmlhttprequest'])) {
				$this->remote = $this->xhr = $this->xmlhttprequest = true;
			}
			
			// the original URL
			$params = empty($this->params_string) ? "" : "?{$this->params_string}";
			$request_url = substr($request_url, 1);
			$this->url = "{$this->protocol}://{$this->host}/{$this->directory}{$request_url}{$params}";
			
			// possibly put some serialization code in here?
		}
		
		// functions
		private function process_request($request) {
			// process routing
			$route = Router2::route($request);
			
			// set routing variables
			$this->route = $route;
			foreach($route->mapping() as $routing_symbol) {
				$this->$routing_symbol = $route->$routing_symbol;
			}
		}
		
		public function __set($name, $value) {
			return false; // cannot set Request variables, it's just the nature of the Request object/variables
		}
		public function __get($name) {
			if(isset($this->post->$name)) return $this->post->$name;
			if(isset($this->get->$name)) return $this->get->$name;
			if(isset($this->cookie->$name)) return $this->cookie->$name;
		}
		
		// return all params (from the route and from the ?... params)
		public function params() {
			$params = array();
			
			// get params from _GET
			foreach($_GET as $get_param => $value) {
				$params[$get_param] = $value;
			}
			
			// getting routing values
			foreach($this->route->mapping() as $routing_symbol) {
				$params[$routing_symbol] = $this->route->$routing_symbol;
			}
			
			return $params;
		}
		
		// descructor
		public function __destruct() {
			// possibly put some serialization code in here?
			// end;
		}
	}
	
	// specific request variables (POST, GET, etc)
	class RequestIn {
		private $values = array();
		
		// constructor
		public function __construct($values) {
			foreach($values as $key=>$value) $this->values[$key] = $value;
		}
		
		// accessors
		public function __get($name) {
			return $this->values[$name];
		}
		public function __set($name, $value) {
			return $this->values[$name] = $value;
		}
	}
	
	class RequestException extends StdException {}
	
?>