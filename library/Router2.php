<?php
	// Router v2 (a Canvas component)
	// @author     Matt Todd
	// @email      <mtodd@clayton.edu>
	// @created_on 23 Apr 2006
	
	class Router2 {
		// internal static variables
		private static $routes = array(); // the routes provided to route against
		private static $route = null;       // the route for the request
		
		// regular expressions for validations
		private static $validations = array(
			// primary
			'controller'=>'([\w_]+)',
			'action'=>'([\w_]+)',
			'id'=>'([\d]*)?', // makes the :id optional
			
			// character/text
			'word'=>'([\w\d_+-\.\?]+)',
			
			// numeric
			'numeric'=>'([\d]+)',
			
			// specialty
			'multiple'=>'((%s\/?)+)',
			
			// dates
			'date'=>'(\d\d\d\d-(0[1-9]|1[1-2])-([0-2][0-9]|3[0-2]))',
			'year'=>'(\d\d\d\d)',
			'month'=>'(0?[1-9]|1[1-2])',
			'day'=>'(0?[1-9]|[1-2][0-9]|3[0-2])'
		);
		
		// route request
		public static function route($request) {
			// compare the request against the available routes
			foreach(self::$routes as $route=>$options) {
				if(preg_match("/^{$route}$/", $request, $matches) == 1) break;
			}
			
			// map values into $map
			foreach($options['values'] as $name=>$value) {
				$map[$name] = $value;
			}
			array_shift($matches);
			foreach($matches as $key=>$match) {
				if($options['names'][$key][strlen($options['names'][$key]) - 1] != '*') {
					// not a multiple name... (it's not sucking up the rest of the values)
					$map[$options['names'][$key]] = $match;
				} else {
					$map[substr($options['names'][$key], 0, -1)] = explode('/', $match);
					break;
				}
			}
			
			// create new Route object and give it to self::$route, then return it
			self::$route = new Route($route, $map);
			return self::$route;
		}
		
		// add route
		public static function map($route, $values = array(), $validates = array()) {
			// @description    Takes a route, like ':controller/:action/:id', and values to include in the route when not specified
			//                 bythe actual route itself. This is handy for complex requests with simplified routes.
			
			$request = $route;
			
			// parse $route into regex
			if($route[0] == '/') $route = substr($route, 1); // strip out initial slash
			$route = explode('/', $route); // pull route into its parts
			foreach($route as $key=>$part) {
				// pull out names (start with a ':')
				if($part[0] == ':') {
					$name = substr($part, 1);
					$names[] = $name;
				} else {
					// literal value
					$name = $part;
				}
				
				// put in regular expression pieces (opting for whatever is in $validates, using 'word' as default unless it's a literal)
				if(self::$validations[$name]) $test = self::$validations[$name]; // default values
				if($validates[$name]) $test = self::$validations[$validates[$name]]; // use specific validations as often as possible
				if($part[0] != ':') $test = $part; // just keep literals
				if(empty($test)) $test = self::$validations['word']; // last resort
				if($name[strlen($name) - 1] == '*') $test = sprintf(self::$validations['multiple'], $test);
				$route[$key] = $test;
				$test = ''; // reset $test;
			}
			
			// assemble new regular expression route for testing
			$route = implode('\/', $route);
			
			// assign $routes with appropriate data
			self::$routes[$route] = array('route'=>$request, 'names'=>$names, 'values'=>$values, 'validates'=>$validates);
			
			return $route;
		}
		
		// generate a route, given a Route object
		public static function url_for($request = array()) {
			// loop through routes, matching them
			foreach(self::$routes as $route=>$options) {
				// default to none found
				$found = false;
				
				// find an appropriate route
				if(is_array($request)) {
					// if it's an array
					foreach(array_keys($request) as $key) {
						if((is_array($options['names']) && array_search($key, $options['names']) !== false) || ($options['values'][$key] == $request[$key])) {
							$found = true;
						} else {
							$found = false;
							break;
						}
					}
					if($found == true) break;
				} else {
					// if it's a string, return the routing data
					if(preg_match("/^{$route}$/", $request) > 0) {
						return array($route=>$options);
					}
				}
			}
			
			if($found) {
				// create new URL
				
				// get the route (to form the URL)
				$url = $options['route']; // the route
				
				// loop through the names, inserting the values into the route/URL
				foreach($options['names'] as $name) {
					$url = str_replace(":{$name}", $request[$name], $url);
				}
				
				// that's it! the Request or Response objects will handle the domain data (because that's their domain!)
				
				return $url;
			} else {
				// return failure
			}
		}
	}
	
	// the tangible, active part of the routing system, representing an actual route
	class Route {
		// variables
		private $map = array(); // the route mapping
		private $route = '';    // the route requested
		
		// magic functions
		public function __get($name) {
			return $this->map[$name];
		}
		public function __set($name, $value) {
			return $this->map[$name] = $value;
		}
		
		// give the route's mapping
		public function mapping() {
			return array_keys($this->map);
		}
		
		// constructor
		public function __construct($route, $map) {
			$this->route = $route;
			$this->map = $map;
		}
	}
?>