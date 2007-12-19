<?php
	// @title	File class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2006-02-06
	// @desc	Handles interaction with the filesystem.
	// @requires stdexception.php (StdException class)
	// @refer_to	http://www.php.net/manual/en/ref.filesystem.php
	
	include_once('stdexception.php');
	
	// classes
	class FSFile {
		public $filename = null;
		public $mode = "a"; // append by default (a+ for append and read)
		public $file = null;
		public $exists = false;
		public $filesize = 0;
		
		// constructor
		public function __construct($filename = null, $mode = null) {
			if(!empty($filename)) $this->open($filename, $mode);
		}
		
		// functions
		public function open($filename = null, $mode = null) {
			// make sure there's a filename to open
			if($filename == null) $filename = $this->filename;
			if($filename == null) return null; // return null if none present to open
			// get reading/writing mode
			if($mode == null) $mode = $this->mode;
			
			// if it doens't exist, return null
			if(file_exists($filename)) $this->exists = true; else throw new FileDoesNotExistException(); // return null; // decided to throw an exception
			
			// get filesize
			$this->filesize = filesize($filename);
			
			// open the file (returning the handle to $this->file)
			$this->file = @fopen($filename, $mode);
			if($this->file == null) throw new FileNotOpenException();
		}
		
		public function close() {
			// close the file
			return @fclose($this->file);
		}
		
		// reading and writing functions
		public function read() {
			// check for existence and whether it's open
			if(!$this->exists) return false;
			if($this->file == null) $this->open();
			if($this->file == null) return false;
			
			// read and return everything
			return fread($this->file, $this->filesize);
		}
		public function write($data) {
			// check for existence and whether it's open
			if(!$this->exists) return false;
			if($this->file == null) $this->open();
			if($this->file == null) return false;
			
			// write to the file (following the guidelines of the writing mode)
			$bytes_written = @fwrite($this->file, $data);
			
			// return true if the number of bytes written is greater than zero, or the same size as the source data
			if(($bytes_written > 0) || (strlen($data) == $bytes_written))
				return true;
			else
				return false;
		}
		
		// uploading functions
		public static function upload($file, $destination, $id = null) {
			if(!is_uploaded_file($file['tmp_name'])) return false;
			
			// destination
			$destination = self::build_path($destination, self::sanitize_filename($file['name'], $id));
			
			// if the file exists, throw an exception!
			if(file_exists($destination)) throw new FileExistsAlreadyException("'{$destination}' already exists!");
			
			if(move_uploaded_file($file['tmp_name'], $destination)) {
				return true;
			} else {
				return false; // @consider	maybe throw an exception?
			}
		}
		
		// file management functions
		public static function delete($location, $filename) {
			// get full filename
			$filename = self::build_path($location, $filename);
			
			// check for existence
			if(!file_exists($filename)) return false;
			
			// delete (aka unlink) file
			if(!@unlink($filename)) return false;
			return true;
		}
		public function remove($filename = null) {
			// check for existence and whether it's open
			if(!$this->exists) return false;
			if($this->file == null) $this->open();
			if($this->file == null) return false;
			
			if(!@unlink($this->filename)) return false;
			return true;
		}
		
		// useful static functions
		public static function sanitize_filename($filename, $id = null) {
			// haha, 'sanitize'
			$filename_info = pathinfo($filename);
			$filename = str_replace(' ', '_', substr($filename_info['basename'], 0, -(strlen($filename_info['extension']) + ($filename_info['extension'] == '' ? 0 : 1))));
			return $filename . ($id ? ('_' . $id) : '') . ($filename_info['extension'] ? ".{$filename_info[extension]}" : '');
		}
		
		public static function current_dir() {
			$working_dir = $GLOBALS['working_dir']; // set in Dispatcher // find a better way to do this!
			return $working_dir . '/';
		}
		
		public static function build_path($dir, $filename = '') {
			return self::current_dir() . $dir . '/' . $filename;
		}
		
		// directory functions
		public static function dir_listing($source_dir = null) {
			if($source_dir == null) $source_dir = self::current_dir();
			$dir = dir($source_dir);
			while (false !== ($file = $dir->read())) {
				if($file != '.' && $file != '..' && !is_dir($source_dir . $file)) {
					$files[] = $file;
				}
			}
			
			return $files;
		}
		
		// descructor
		public function __destruct() {
			$this->close();
		}
	}
	
	class FileException extends StdException {
		// hopefully won't ever need to be used, but you never know
	}
	class FileNotOpenException extends StdException {}
	class FileDoesNotExistException extends StdException {}
?>