<?php
	// @title	YAML
	// @role	user-defined extension (used in Config, et al)
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2006-02-08
	// @desc	Handles parsing YAML formatted documents
	// @refer_to "SPYC":http://spyc.sourceforge.net/
	// @requires YAML/spyc.php5 (YAML PHP5 parser class)
	// @requires extexception.php (StdException class)
	
	include_once('YAML/spyc.php');
	include_once('stdexception.php');
	
	// classes
	class YAML {
		// loads and parses a YAML document
		public static function load($yaml) {
			$output = Spyc::YAMLLoad($yaml);
			return $output;
		}
	}
	
	class YAMLFileNotFound extends StdException {}
	class YAMLException extends StdException {}
?>