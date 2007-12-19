<?php
	// @title    Canvas core class
	// @author   Matt Todd <matt@matttoddphoto.com>
	// @created  2006-03-29
	// @desc     The core class every major Canvas component inherets from
	// @requires stdexception.php (StdException class)
	// @note     Yes, it came a little late in the game.
	
	include_once('stdexception.php');
	
	// classes
	class Canvas {
		protected $config = array();
		
		// loads the configuration for the current component if no other config data is requested
		protected function load_config($config_section = null) {
			$config = new Config2(); // load the configuration
			
			// determine section, if any
			if($config_section == true && !empty($this->config_section)) $config_section = $this->config_section; // if there's a special section name already set, use that
			if($config_section == true) $config_section = strtolower(get_class()); // if nothing's been set, use the class name
			
			// set config data
			$this->config = ($config_section == null) ? $config->config : $config->$config_section;
		}
	}
?>