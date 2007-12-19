<?php
	// @title				Adapter interface
	// @creator			Matt Todd
	// @created_on	2006-01-13
	
	interface adapter_interface {
		public function connect();
		public function find();
		public function update();
		public function insert();
		public function delete();
		public function disconnect();
	}
?>