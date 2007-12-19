<?php
	// @role    AutoLoad
	// @title   Auto loader
	// @author  Matt Todd
	// @date    2006-03-24 10:16AM
	// @desc    Handles autoloading models so that they all don't have to be loaded all the time
	//          (to keep load time and memory requirements to a minimum)
	
	function __autoload($file) {
		if(file_exists(Conventions::model_path($file))) include_once Conventions::model_path($file);
		if(file_exists(Conventions::library_path($file))) include_once Conventions::library_path($file);
		if(file_exists(Conventions::extension_path($file))) include_once Conventions::extension_path($file);
	}
?>