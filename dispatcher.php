<?php
	// @role		dispatcher
	// @title		Application Dispatcher
	// @author		Matt Todd
	// @date		2005-11-26 4:48PM
	// @desc		Interprets requests, instanciates appropriate controller,
	//				and runs specified action with the appropriate Request data.
	
	// timing
	$GLOBALS['dispatch_time_begin'] = microtime(true);
	
	{ // required libraries and globals
		// get the working directory for this web app (useful for ensuring security)
		$GLOBALS['working_dir'] = dirname(__FILE__);
		
		// load dependencies
		include_once 'library/dependencies.php';
		
		// extensions
		include_once Conventions::extension_path("RSS"); // generates RSS feeds
	}
	
	try { // startup/boot logic
		// handle sessions, initializations, & primary instanciations
		if(class_exists('Session')) Session::initialize(); // initialize Session
		
		// interpret request
		$request = new Request(Conventions::path_info());
		$controller_name = Conventions::controller_name($request->controller);
		$helper_name = Conventions::helper_name($request->controller);
		$action_name = Conventions::action_name($request->action);
		$action_id = $request->id;
		
		{ // handle instanciating request and executing requested function
			// include controller classes
			if(file_exists(Conventions::controller_path($request->controller)))
				include_once Conventions::controller_path($request->controller);
			else
				Debug::generic_exception_handler(new Exception("'{$controller_name}' does not exist"));
			// includ helper (application or controller-specific)
			if(file_exists(Conventions::helper_path($request->controller))) include_once Conventions::helper_path($request->controller); else $helper_name = "application_helper";
			
			// instanciate controller
			$controller = new $controller_name($request);
			
			// dispatch action
			$controller->dispatch($action_name, $action_id, $request);
		}
	} catch(Exception $e) {
		print_r($e);
	}
	
	// timing
	$execution_time = round(microtime(true) - $GLOBALS['dispatch_time_begin'], 5);
	$execution_memory = round(memory_get_usage()/1024, 2);
	Debug::log("Dispatched {$_SERVER['PATH_INFO']} ({$execution_time}sec/{$execution_memory}kb)", 'internal', 'notice', 'Dispatcher');
	
?>