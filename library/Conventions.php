<?php
	// @title    Conventions (class of configurations and all)
	// @author   Matt Todd <matt@matttoddphoto.com>
	// @created  2006-03-29
	// @desc     Conventions and default values to be used throughout the system
	// @requires stdexception.php (StdException class)
	// @note     Yes, it came a little late in the game.
	
	// This file is to be clean
	
	class Conventions {
		public static $app_dir = '';
		
		// path info (for routing)
		public static function path_info() {
			return substr($_SERVER['PATH_INFO'], 1);
		}
		
		// 'directories' contains the directory names for various components.
		// It will appear to be repetitive, but this is due to the fact that
		// the keys are the same as the default so that the internal system
		// never has to know it's something different!
		public static $directories = array(
			'library'=>'library', // standard and concrete for now
			'adapters'=>'library/adapters',
			'controllers'=>'controllers',
			'models'=>'models',
			'helpers'=>'helpers',
			'views'=>'views',
			'config'=>'config',
			'logs'=>'logs',
			'extensions'=>'extensions',
		); // directories
		
		// static method to get the directory name
		public static function directory($name) {
			return self::$directories[$name];
		}
		
		// 'names' contains specifications for how names should be formed
		// and derived. If custom functionality is desired, modify the
		// names and the functions, such as to Camelize names for names
		// as 'PublicController' instead of 'public_controller'
		public static $names = array(
			// config
			'config'=> array(
				// cache file
				'cache'=> 'config.cache',
				
				// the generic file (for non-specific config-files)
				'file'=> '%s.yml',
				
				// explicit files
				'files'=> array(
					'routes'=> 'routes.php',
				),
			),
			
			// controllers
			'controllers'=> array(
				'class'=> '%s_controller',
				'file'=>  '%s_controller.php',
				'action'=> '%s',
			),
			
			// models
			'models'=> array(
				'class'=> '%s',
				'file'=>  '%s.php',
			),
			
			// views
			'views'=> array(
				'name'=>      '%s',
				'class'=>      '%s_view',
				'file'=>      '%s_view.php',
				'action'=>     '%s.php',
				'controller'=> '%s',
				'layout' =>    'layout.php',
			),
			
			// helpers
			'helpers'=> array(
				'class'=> '%s_helper',
				'file'=>  '%s_helper.php',
			),
			
			// libraries
			'library'=> array(
				'file'=>		'%s.php',
				'adapter'=>	'%s.php',
			),
			
			// extensions
			'extensions'=> array(
				'file'=> '%s.php',
			),
			
			// logs
			'logs'=> array(
				'file'=> '%s.log',
				'separate_file'=> '%s.%s.log'
			),
		); // names
		
		// naming methods ///////////////////////////////////////////////////////////////////////////////////////////////////
		// form standard names
		
		// controller naming functions
		public static function controller_name($name) {
			return sprintf(self::$names['controllers']['class'], $name);
		}
		public static function controller_class_name($name) {
			return self::controller_name($name);
		}
		public static function controller_file_name($name) {
			return sprintf(self::$names['controllers']['file'], $name);
		}
		// action name
		public static function action_name($name) {
			return sprintf(self::$names['controllers']['action'], $name);
		}
		
		// model naming functions
		public static function model_name($name) {
			return sprintf(self::$names['models']['class'], $name);
		}
		public static function model_class_name($name) {
			return self::model_name($name);
		}
		public static function model_file_name($name) {
			return sprintf(self::$names['models']['file'], $name);
		}
		
		// view naming functions
		public static function view_name($name) {
			return sprintf(self::$names['views']['name'], $name);
		}
		public static function view_class_name($name) {
			return sprintf(self::$names['views']['class'], $name);
		}
		public static function view_class_file_name($name) {
			return sprintf(self::$names['views']['file'], $name);
		}
		public static function view_file_name($name) {
			return sprintf(self::$names['views']['action'], $name);
		}
		public static function view_controller_name($name) {
			return sprintf(self::$names['views']['controller'], $name);
		}
		public static function view_layout_file_name() {
			return self::$names['views']['layout'];
		}
		
		// helper naming functions
		public static function helper_name($name) {
			return sprintf(self::$names['helpers']['class'], $name);
		}
		public static function helper_class_name($name) {
			return self::helper_name($name);
		}
		public static function helper_file_name($name) {
			return sprintf(self::$names['helpers']['file'], $name);
		}
		
		// extensions and libraries
		public static function library_file_name($name) {
			return sprintf(self::$names['library']['file'], $name);
		}
		public static function extension_file_name($name) {
			return sprintf(self::$names['extensions']['file'], $name);
		}
		
		// adapters
		public static function adapter_file_name($name) {
			return sprintf(self::$names['library']['adapter'], strtolower($name));
		}
		
		// paths methods //////////////////////////////////////////////////////////////////////////////////////////////////////
		
		// generic path function
		public static function path($dir, $file) {
			return self::$app_dir . self::directory($dir) . '/' . $file;
		}
		
		// controller path
		public static function controller_path($name) {
			return self::path('controllers', self::controller_file_name($name));
		}
		// model path
		public static function model_path($name) {
			return self::path('models', self::model_file_name($name));
		}
		// view path
		public static function view_path($controller, $name) {
			return self::path('views', (self::view_controller_name($controller) . '/' . self::view_file_name($name)));
		}
		public static function view_class_path($name) {
			return self::path('views', self::view_class_name($name));
		}
		public static function view_class_file_path($name) {
			return self::path('views', self::view_class_file_name($name));
		}
		// helper path
		public static function helper_path($name) {
			return self::path('helpers', self::helper_file_name($name));
		}
		// library path
		public static function library_path($name) {
			return self::path('library', self::library_file_name($name));
		}
		// extension path
		public static function extension_path($name) {
			return self::path('extensions', self::extension_file_name($name));
		}
		// adapter path
		public static function adapter_path($name) {
			return self::path('adapters', self::adapter_file_name($name));
		}
		
		// specific file methods //
		
		// config cache file
		public static function config_cache_file() {
			return self::path('config', self::$names['config']['cache']);
		}
		// config file name
		public static function config_file($name) {
			// if the filename is explicitly set, return that value
			if(!empty(self::$names['config']['files'][$name]))
				return self::path('config', self::$names['config']['files'][$name]);
			
			// otherwise, return the default form of the config file (such as '{$name}.yml')
			return self::path('config', sprintf(self::$names['config']['file'], $name));
		}
		
		// log file
		// config file name
		public static function log_file($name) {
			return self::path('logs', sprintf(self::$names['logs']['file'], $name));
		}
		public static function separate_log_file($section, $name) {
			return self::path('logs', sprintf(self::$names['logs']['separate_file'], $section, $name));
		}
	}
?>