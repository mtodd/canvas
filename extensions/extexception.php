<?php
	// @title	Extension Exception class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-10-07
	// @desc	An Exception class specifically for 3rd party extensions (to be extended)
	
	class ExtException extends Exception {
		// private variables
		public $nErrorCode;
		public $strErrorMessage;
		public $strErrorLocation;
		public $strErrorContext;
		public $aDump;
		
		// constructor
		public function __construct($nErrorCode = "0099", $strErrorMessage = "Undefined", $strErrorLocation = "", $strErrorContext = "", $aDump = "") {
			$this->nErrorCode = $nErrorCode;
			$this->strErrorMessage = $strErrorMessage;
			$this->strErrorLocation = $strErrorLocation;
			$this->strErrorContext = $strErrorContext;
			$this->aDump = $aDump;
		}
		
		// functions
		public function GetErrorCode() {
			return $this->nErrorCode;
		}
		public function GetErrorMessage() {
			return $this->strErrorMessage;
		}
		public function GetErrorLocation() {
			return $this->strErrorLocation;
		}
		public function GetErrorContext() {
			return $this->strErrorContext;
		}
		public function GetDump() {
			return $this->aDump;
		}
		public function AsString($bHTML = false) {
			if($bHTML) {
				return "<strong>Error No. {$this->strErrorCode}</strong>: {$this->strErrorMessage} (<em>{$this->strErrorLocation}</em>)";
			} else {
				return "Error No. {$this->strErrorCode}: {$this->strErrorMessage} ({$this->strErrorLocation})";
			}
		}
		
		// aliases
		public function GetError() {
			return "Error No. " . $this->GetErrorCode() . ": " . $this->GetErrorMessage();
		}
	}
?>