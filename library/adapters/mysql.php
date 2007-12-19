<?php
	// @title	MySQL DB Connection class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-09-28
	// @desc	Used to connect and interact with MySQL databases.  Inherits
	//		basic functionality and structure from the DBAbstract class.
	
	include_once 'library/database.php';
	include_once 'library/stdexception.php';
	
	class mysql extends database {
		// $config, $handle, $is_connected; // state and configuration // defined in 'database' class
		
		// query templates
		const       select = 'SELECT :cache :columns FROM :table :join :where :group_by :having :order_by :limit :offset;';
		const       delete = 'DELETE FROM :table :where :order_by :limit;';
		const       insert = 'INSERT INTO :table SET :values;';
		const       update = 'UPDATE :table SET :values :where :order_by :limit;';
		// transactional statements
		const        begin = 'BEGIN;';
		const       commit = 'COMMIT;';
		const     rollback = 'ROLLBACK;';
		// query templates' parts
		const        cache = 'SQL_CACHE';
		const         join = 'LEFT JOIN :table ON :on :join';
		const        where = 'WHERE :where';
		const     group_by = 'GROUP BY :group_by';
		const       having = 'HAVING :having';
		const     order_by = 'ORDER BY :order_by';
		const        limit = 'LIMIT :limit';
		const       offset = 'OFFSET :offset';
		// meta templates
		const named_entity = ':name AS :alias';
		const     equality = '"{:1}" = "{:2}"';
		const        value = '{:1} = "{:2}"';
		
		// constructor // there's magic working here, so don't overwrite what's already in here unless
		// you are willing to clean up after yourself
		public function __construct($params) {
			parent::__construct($params);
			parent::$adapter_class = get_class();
		}
		
		// actions
		public function connect() {
			// establish connection
			if(!($this->handle = @mysql_pconnect($this->config['host'], $this->config['username'], $this->config['password'])))
				throw new DBConnectionException(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
			parent::$static_handle = $this->handle;
			$this->is_connected = true;
			return true;
		}
		
		public function disconnect() {
			if(mysql_close($this->db)) {
				$this->is_connected = false;
				$this->db = null;
				return true;
			} else {
				return false;
			}
		}
		
		public function select_db($database = null) {
			if(!empty($database)) $this->config['database'] = $database; else $database = $this->config['database'];
			if($this->is_connected) {
				if(!@mysql_select_db($database, $this->handle)) throw new CouldNotSelectDB(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
				return true;
			} else {
				throw new CouldNotSelectDB("0002", "Not connected to the Database", (__FILE__ . ', line ' . __LINE__));
			}
		}
		
		protected function query($query) {
			@mysql_free_result($this->result);
			
			Debug::log("Executed '{$query}'", 'sql', 'info', 'adapter::MySQL');
			
// 			try {
// 				if(!($this->result = @mysql_query($query, $this->handle))) throw new DBQueryException(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
// 			} catch(Exception $e) {
// 				print_r($e); die();
// 			}
			
			// execute the query and return the result
			$this->result = @mysql_query($query, $this->handle);
			
			return $this->result;
		}
		
		public function iterate($result) {
			// iterate through the results
			return @mysql_fetch_assoc($result);
		}
		
		// functions
		public function rows_found() {
			return mysql_num_rows($this->result);
		}
		public function affected_rows() {
			return mysql_affected_rows();
		}
		public function last_id() {
			return mysql_insert_id();
		}
		// alias
		public function id() {
			return $this->last_id();
		}
		
		// sanitize values
		public static function sanitize($value) {
			return addslashes(stripslashes($value));
		}
		
		// get table column names (to make sure 'updates' and 'inserts' don't try to update a column that doesn't exist)
		public static function columns($table) {
			// query
			$query = "EXPLAIN {$table};";
			
			// get table details
			$result = @mysql_query($query, parent::$static_handle);
			
			// loop through details
			while($row = @mysql_fetch_assoc($result)) {
				$columns[] = $row['Field'];
			}
			
			return $columns;
		}
	}
?>