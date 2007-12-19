<?php
	
	// @title		Configuration Component
	// @author	Matt Todd
	// @updated	2006-05-24 16:22:35
	// @desc		The module for storing and retreiving configuration data
	
	include_once('stdexception.php');
	
	class Config2 {
		public static $environment = '';
		public static $config = array();
		
		// instance methods (for accessing)
		public function __construct() {
			// make sure that the config data has been loaded
			if(empty(self::$config)) self::load();
		}
		public function __get($name) {
			return self::$config[$name];
		}
		public function __set($name, $value) {
			return false;
		}
		
		// static methods (for loading)
		public static function load() {
			// load cached/serialized configuration
			self::$config = self::load_cache();
			
			// check for cache expiration (by updating the originals)
			if(self::cache_expired()) {
				$begin_time = microtime(true);
				
				// cache has expired, so update it
				self::$config = self::load_config();
				
				// cache config
				self::save_cache();
				
				$time = microtime(true) - $begin_time;
				
				// log that config files were reloaded and cached
				Debug::log("Config files were reloaded and cached ({$time}sec)", 'config', 'notice', 'Config');
			}
			
			// set the environment static property
			self::$environment = self::$config['environment'];
			
			// map convention-modifying configurations
			self::map_config();
			
			// return the configuration
			return self::$config;
		}
		
		public static function load_cache() {
			return unserialize(@file_get_contents(Conventions::config_cache_file()));
		}
		
		public static function load_config() {
			// get the YAML generic configurations and database configurations
			$config = YAML::load(Conventions::config_file('config'));
			$config['database'] = YAML::load(Conventions::config_file('database'));
			
			// disabled because the Routes file is PHP instead of YAML
			// $config['routes'] = YAML::load(Conventions::config_file('routes'));
			
			return $config;
		}
		
		public static function save_cache() {
			self::$config['checksum'] = self::config_checksum();
			$cache = serialize(self::$config);
			@file_put_contents(Conventions::config_cache_file(), $cache);
		}
		
		public static function cache_expired() {
			if(self::$config['checksum'] != self::config_checksum()) {
				// cache expired!
				return true;
			} else {
				// cache is fine
				return false;
			}
		}
		
		public static function config_checksum() {
			return md5(
				@file_get_contents(Conventions::config_file('config'))
				. @file_get_contents(Conventions::config_file('database'))
				// disabled because the Routes file is PHP instead of YAML
				// . @file_get_contents(Conventions::config_file('routes'))
			);
		}
		
		// map convention-modifying configs
		public static function map_config() {
			// map directories (may add new ones, which is OK)
			foreach(self::$config['directories'] as $convention=>$dir) {
				Conventions::$directories[$convention] = $dir;
			}
			
			// map others
		}
	}
	
?>