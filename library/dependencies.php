<?PHP
	
	// @title				Dependencies
	// @desc				Loads all necessary libraries for a Canvas application (comment out as appropriate, but carefully)
	// @author			Matt Todd
	// @email				<mtodd@clayton.edu>
	// @created_on	29 Apr 2006
	// @note				Any statement followed by a "// *" comment (possibly followed by commentary) is REQUIRED!
	
	{ // required libraries and globals
		// load conventions (for paths et al)
		include_once $GLOBALS['working_dir'] . "/library/Conventions.php"; // *
		
		// set app_dir for conventions
		Conventions::$app_dir = $GLOBALS['working_dir'] . '/'; // *
		
		// super-dependencies (that all of the others use)
		include_once Conventions::library_path("YAML"); // * // adds YAML file parsing (for config)
		
		include_once Conventions::library_path("Config2"); // *
		include_once Conventions::library_path("AutoLoad"); // * // autoloads models as needed
		include_once Conventions::library_path("Globals"); // *
		
		// settings & config libraries
		include_once Conventions::library_path("Debug"); // * // handles debugging
		include_once Conventions::library_path("File"); // *
		include_once Conventions::library_path("Router2"); // *
		
		// primary components and libraries
		include_once Conventions::library_path("Request"); // *
		include_once Conventions::library_path("Response"); // *
		include_once Conventions::library_path("Session"); // *
		
		// mvc components
		// @desc	You can exclude any of these, but it makes sense to use at least one, such as Model2,
		//				or Controller. Please read the API for overloading default functionality if you choose
		//				to use just one (so that Controller isn't dependent on View, for instance).
		include_once Conventions::library_path("Controller"); 					// @depends_on Config2, Conventions, Debug, View
		include_once Conventions::library_path("Model2"); 							// @depends_on Config2, YAML, Conventions, Debug
		include_once Conventions::library_path("View");									// @depends_on Smarty (unless overridden)
		
		// base mvc classes
		// @desc	These are the default classes for the system: any other classes (Controllers, Views, Helpers) should
		// inheret from these base classes, unless you prefer to have no global functionality (such as exception-handling)
		include_once Conventions::controller_path('application'); 			// loads the default controller
		include_once Conventions::helper_path('application');						// loads the default helper
		// include_once Conventions::view_class_file_path('application');	// loads the default view // unused as of 1.0.4
		
		// Smarty libs
		include_once Conventions::library_path('smarty/Smarty.class');	// @used_by	View
		
		// standard extensions
		include_once Conventions::library_path("ext/Pager");			// handles pagination
		include_once Conventions::library_path("ext/Pluralize");	// handles pluralization (used in helpers)
		include_once Conventions::extension_path("RedCloth");			// adds specialized formatting for input/output
	}
	
	{	// load route mappings
		include_once Conventions::config_file('routes');
	}
	
?>