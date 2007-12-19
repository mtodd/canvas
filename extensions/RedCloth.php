<?php
	// @title	RedCloth
	// @role	Text formatting
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2006-01-08
	// @desc	Handles formatting (based on Textile)
	// @refer_to "RedHanded":http://redhanded.hobix.com/
	// @requires stdexception.php (ExtException class)
	
	include_once('extexception.php');
	
	// classes
	class RedCloth {
		// create HTML from RedCloth formatting
		public static function to_html($input) {
			$input = addslashes($input);
			$output = `./extensions/RedCloth/redcloth "{$input}"`;
			return stripslashes($output);
		}
	}
	
	class RedClothException extends ExtException {
	}
?>
