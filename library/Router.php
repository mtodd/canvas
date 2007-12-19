<?php
	// @title	Routes handler
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-12-22
	// @desc	A class to handle routing requests (breaking down request URLs to
	//		their property values, returned straight-up
	// @requires stdexception.php (StdException class)
	
	include_once('library/stdexception.php');
	
	// classes
	class Router {
		private $validators = array(
			"name"=>'/[\w\d_\?!]+/',
			"word"=>'/[\w\?!]+/',
			"number"=>'/\d+/',
			"date"=>'/(19|20)\d\d-[01]?\d-[0-3]?\d/', // e.g.: 2005-12-24
			"year"=>'/(19|20)\d\d/', // e.g.: 2005 (but not 2105, sorry)
			"month"=>'/[01]?\d/', // e.g.: 12 (but not 22)
			"day"=>'/[0-3]?\d/', // e.g.: 24 (but not 44)
			"time"=>'/\d\d:\d\d(:\d\d)?/',
			"filename"=>'/[\w\d_\.]+\.[\w\d]+/',
			"anything"=>'/.*/',
			
			// format but optional
			"optional name"=>'/[\w\d_\?!]*/',
			"optional word"=>'/[\w\?!]*/',
			"optional number"=>'/\d*/'
		);
		
		// functions
		public static function route($request) {
			// split $request into the components
			$request = explode('/', ($request . ((substr($request, -1, 1) != '/') ? '/' : '')));
			array_shift($request); // get rid of the first, tempty element
			
			// get routes information from config file
			$routes = Config::load('routes');
			$default_route = $routes['default_route'];
			$routes['default_route'] = null;
			
			// loop through the $this->routes property, attempting to match a request
			foreach($routes as $route) {
				if($matched_route = self::match($request, $route)) break;
			}
			
			if(empty($matched_route)) $matched_route = $default_route;
			
			// debugging
			// crap // Debug::log(str_replace('  ', ' ', str_replace("\n", '', print_r($matched_route, true))), 'internal', 'info', 'Router');
			
			return $matched_route;
		}
		
		public static function map($route) {
			//
		}
		
		private static function match($request, $route) {
			// create $router instance for properties
			$router = new Router();
			
			// split up route nodes (from '/controller/action' to an array of 'controller','action')
			$route_nodes = explode('/', $route['route']);
			
			// loop through route and requested resource... return if it doesn't match/meet validation requirements, et al
			while((list(, $request_node) = each($request)) && (list(, $route_node) = each($route_nodes))) {
				// set the route name (if route just gives name and not explicit value, take out ':' in name and assign it to $route_name
				if($route_node[0] == ':') $route_name = substr($route_node, 1); else $route_name = $route_node;
				// if there's no specific validation spacified, set the validation pattern to a pre-defined constant for a valid string
				if($route_node[0] == ':') $route_node = !empty($route['validate'][$route_name]) ? $router->validators[$route['validate'][$route_name]] : $router->validators['name'];
				// handle missing/empty parameters (should be fixed to be more flexible!!!)
				if(empty($request_node) && $route_name == "action") $request_node = "index";
				if(empty($request_node) && $route_name == "id") $request_node = "null";
				if(empty($request_node)) $request_node = !empty($route['default'][$route_name]) ? $route['default'][$route_name] : null;
				// if its a plaintext entry in the route (for only working with certain requests), add /s around the word to make it a valid PCRE regex pattern
				if($route_node[0] != '/') $route_node = "/{$route_node}/";
				
				// debugging
				// this is really crappy // Debug::log("route name: '{$route_name}', route node: '{$route_node}', request node: '{$request_node}';", 'internal', 'low', 'Router');
				
				// test to see if it matches; if not, return null to signify that the request did not match the route, or return the actual route
				if($request_node == null || preg_match($route_node, $request_node) != 1) {
					return null; // no match
				} else {
					// if it's an ID with the value of "null", convert it to the actual null value
					if($route_name == "id" && $request_node == "null") $request_node = null;
					// add data to route array if it matched alright
					$matched_route[$route_name] = $request_node;
				}
			}
			
			// add the routing map to the matched route
			$matched_route['_map'] = $route;
			
			// return matched route
			return $matched_route;
		}
	}
	
	class RoutesException extends StdException {}
?>