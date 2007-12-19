<?php
	// @title	Debug class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-12-22
	// @desc	Handles various aspects of debugging, such as logging, asserting,
	//		default exception handlers or verbose exception handlers,
	//		and potentially suppoting breakpoints/interactive
	//		inspection during runtime
	// @requires stdexception.php (StdException class)
	// @refer_to	http://www.php.net/manual/en/function.proc-open.php for
	// 		breakpoint/interactive inspection during runtime
	// @refer_to http://www.php.net/manual/en/function.assert.php for
	//		assertions and peculiarities therein
	// @refer_to http://www.php.net/manual/en/function.apd-breakpoint.php
	
	include_once('stdexception.php');
	
	// classes
	class Debug extends Canvas {
		// property functions
		public static function __set($name, $value) {
			$GLOBALS["debug"][$name] = $value;
		}
		public static function __get($strName) {
			return $GLOBALS["debug"][$name];
		}
		
		// functions
		// Debug::log($message, $type = 'general', $level = 'general', $from = null);
		public static function log($message, $type = 'general', $level = 'notice', $from = null) {
			// handle logging the $message (possibly formatting)
			
			// configuration values
			$env = Config2::$environment;
			$log_config = Config2::$config['logging'];
			$dir = Config2::$config['directories']['logs'];
			$filename = "{$env}.log";
			if($filename == '.log') $filename = 'system.log';
			$log_level = $log_config['log_level'];
			$log_separately = $log_config['log_separately']; // array of types of messages to log separetely (in their own logs)
			$always_log = $log_config['always_log']; // array of types of messages to always log
			
			// handle default values (convention over configuration)
			if(empty($dir)) $dir = 'logs/'; // default logging directory
			if(empty($log_level)) $log_level = 'notice'; // default log level
			if(empty($always_log)) $always_log = array();
			if(empty($log_separately)) $log_separately = array();
			// make sure logging is supposed to happen for this event
			// if($env == 'production') return; // should logging be skipped in the production environment?
			if(self::log_level($level) >= self::log_level($log_level) || in_array($type, $always_log) || in_array($type, $log_separately)) {
				if(in_array($type, $log_separately)) $filename = "{$type}.log";
			} else {
				if(!in_array($type, $always_log)) return;
			}
			
			// log format
			if(empty($log_config['log_format']))
				$log_format = "%s (%s:%s)%s %s\n";
			else
				$log_format = $log_config['log_format'];
			
			// changes if it was set
			if(!empty($from)) $from = " [{$from}]";
			
			$log_file = new FSFile(FSFile::build_path($dir, $filename));
			$date = date("Y-m-d H:i:s"); // alternatively // date("c");
			$log_file->write(sprintf($log_format, $date, $type, $level, $from, $message));
		}
		private static function log_level($level) {
			// determines the log level value (a numerical index) for each logging level
			// used to determine if logging of the specified level should be skipped or not
			
			// log levels and their numerical index
			$log_levels = array(
				"low"=>0,
				"info"=>1,
				"notice"=>2,
				"warn"=>3,
				"error"=>4,
				"fatal"=>5,
			);
			
			// return numerical index
			// return 0 if it's not in the array of predefined levels
			return (array_key_exists($level, $log_levels)) ? $log_levels[$level] : 0;
		}
		
		public static function assert($expression, $value) {
			if($expression == $value) return true; // assertion was successful
				else throw new AssertionException($expression); // assertion did not succeed and must be handled
			
			// if $expression is a boolean value, return it
			// if not, evaluate it and then return that value
			// if no value returned by expression, return null value
			
			// refer to http://www.php.net/manual/en/function.assert.php
		}
		
		public static function breakpoint() {
			// refer to http://www.php.net/manual/en/function.proc-open.php to possibly implement
			// and also http://www.php.net/manual/en/function.apd-breakpoint.php
		}
		
		// standard/verbose exception handlers
		public static function generic_exception_handler($e) {
			// handle generic exception
			$exception_type = get_class($e);
			$e_dump = print_r($e, true);
			$template = <<<TPL
<html>
	<head>
		<title>Exception: {$exception_type}</title>
	</head>
	<body>
		<h1>Internal Exception!</h1>
		<p>{$e->getMessage()}</p>
		<pre>{$e_dump}</pre>
	</body>
</html>
TPL;
			print $template;
			die();
		}
		public static function verbose_exception_handler($e) {
			// verbosely evaluate the exception and return/print the evaluation for inspection during development
		}
		public static function minimal_exception_handler($e) {
			// give as little error information as possible (reserved primarily for the production environment for unhandled exceptions, a bad thing to begin with)
		}
		
		// handle timing
		public static function timing($component) {
			if(empty($GLOBALS['debug']['timing'][$component])) {
				// begin timing
				$GLOBALS['debug']['timing'][$component] = microtime(true);
			} else {
				// finish timing
				$time = microtime(true) - $GLOBALS['debug']['timing'][$component];
				Debug::log("took $time seconds...", 'bebug:timing', 'warn', $component);
			}
		}
	}
	
	class DebugException extends StdException {} // hopefully won't ever need to be used, but you never know
	class AssertionException extends StdException {} // an assertion failure exception
?>