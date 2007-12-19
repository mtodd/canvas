<?php
	// @title	MySQL DB Connection class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-09-28
	// @desc	Used to connect and interact with MySQL databases.  Inherits
	//		basic functionality and structure from the DBAbstract class.
	
//	include_once 'db.php';
//	include_once 'stdexception.php';
	
	class mysql extends database {
		// defined in the 'database' class
		// public $config, $handle, $query, $result, $is_connected; // state and configuration
		
		// functions to override
/*		abstract public function connect();
		abstract public function disconnect();
		abstract public function select_db($database);
		abstract public function query($query);
		abstract public function iterate();
		abstract public function select($params, $table);
*/
		
		// overridden functions
		public function connect() {
			// establish connection
			if(!($this->handle = @mysql_pconnect($this->config['host'], $this->config['username'], $this->config['password']))) die(mysql_error()); // throw new DBConnectionException(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
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
		
		public function query($query) {
			$this->query = $query;
			// print $query . "<br />\n";
			
			@mysql_free_result($this->result);
			
			Debug::log("Executed '{$this->query}'", 'sql', 'info', 'MySQL');
			
//			try {
				if(!($this->result = @mysql_query($this->query, $this->db))) throw new CouldNotIterate(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
				return true;
//			}
//			catch(DBException $e) {
//				// $this->result = null;
//				print_r($e);
//				$this->error = $e;
//				return false;
//			}
		}
		
		public function get_row() {
			try {
				switch($this->row_result_type) {
					case "self::ROWRESTYPE_INDEXED":
					case self::ROWRESTYPE_INDEXED:
						if(!($this->row = @mysql_fetch_row($this->result))) throw new DBException(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
						return $this->row;
						break;
					default:
					case "self::ROWRESTYPE_ASSOC":
					case self::ROWRESTYPE_ASSOC:
						if(!($this->row = @mysql_fetch_assoc($this->result))) throw new DBException(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
						return $this->row;
						break;
					case "self::ROWRESTYPE_OBJECT":
					case self::ROWRESTYPE_OBJECT:
						if(!($this->row = @mysql_fetch_object($this->result))) throw new DBException(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
						return $this->row;
						break;
				}
			}
			catch(DBException $e) {
				print_r($e);
				$this->row = null;
				$this->error = $e;
				return false;
			}
		}
		
		public function iterate($result = null) {
			if($result == null) $result = $this->result;
//			try {
				if(!($this->row = @mysql_fetch_assoc($result)) && $this->row != false) throw new CouldNotIterate(mysql_errno(), mysql_error(), (__FILE__ . ', line ' . __LINE__));
				// print_r($this->row);
				return $this->row;
//			}
//			catch(DBException $e) {
//				print_r($e);
//				$this->row = null;
//				$this->error = $e;
//				return false;
//			}
		}
		public function select($params, $table) {
			// where operators
			$where_operator = !empty($params["where_operator"]) ? $params["where_operator"] : "AND";
			
			// compile list of columns to select (usually defaults to "*")
			if(is_array($params["columns"])) {
				$columns = implode($params["columns"], ", ");
			} else {
				// if nothing is specified, select everything
				$columns = !empty($params["columns"]) ? $params["columns"] : "*";
			}
			
			// compile the list of WHERE arguments
			if(is_array($params["where"])) {
				foreach($params["where"] as $column=>$values) {
					if(is_array($values) && is_int($column)) {
						// support for passing in arrays of ('key','value')
						if($where != "") $where .= " {$where_operator} "; // append value of $strWhereOperator
						$where .= "{$values[0]}='{$values[1]}'";
					} elseif(is_array($values)) {
						foreach($values as $value) {
							if($clause != "") $clause .= " OR ";
							$clause .= "{$column}='{$value}'";
						}
						if($where != "") $where .= " {$where_operator} "; // append value of $strWhereOperator
						$where .= $clause;
					} else {
						if($where != "") $where .= " {$where_operator} "; // append value of $strWhereOperator
						$where .= "{$column}='{$values}'";
					}
				}
			} else {
				if(!empty($params["where"])) $where = $params["where"];
			}
			if(!empty($where)) $where = "WHERE {$where}";
			
			// order by
			if(!empty($params["order_by"])) $order_by = "ORDER BY " . $params["order_by"];
			
			// limit
			if(!empty($params["limit"])) $limit = "LIMIT " . $params["limit"];
			
			// "SELECT {$columns} FROM {$table} {$strWhere}{$strOrderBy};"
			$query = 'SELECT %s FROM %s %s %s %s;';
			
			// debug purposes
			// print sprintf($query, $columns, $table, $where, $order_by, $limit) . "<br />\n";
			
			// execute query
			$this->query(sprintf($query, $columns, $table, $where, $order_by, $limit));
		}
		public function insert($params, $table) {
			// get columns of the table
			$table_columns = $this->explain_table($table);
			
			// compile list of columns and values to insert
			foreach($params as $column=>$value) {
				// check if it's an association
				if(is_array($value) || is_object($value)) continue;
				
				// don't insert 'id' values
				if($column == "id") continue;
				
				// also, don't update if it's not one of the columns in the table (uh, duh)
				if(!in_array($column, $table_columns)) continue;
				
				// add commas
				if(!empty($columns)) $columns .= ', ';
				if(!empty($values)) $values .= ', ';
				
				// add slashes to help prevent SQL Injection problems
				// $value = addslashes($value);
				
				// assemble query strings
				$columns .= "{$column}";
				$values .= "'{$value}'";
			}
			
			// "INSERT INTO {$table} ({$strColumns}) VALUES ({$values});"
			$query = 'INSERT INTO %s (%s) VALUES (%s);';
			
			// debug purposes
			// print sprintf($query, $table, $columns, $values);
			
			// execute query
			$this->query(sprintf($query, $table, $columns, $values));
		}
		public function update($params, $table) {
			// get columns of the table
			$table_columns = $this->explain_table($table);
			
			// compile list of columns and values to insert
			foreach($params as $column=>$value) {
				// check if it's an association
				if(is_array($value) || is_object($value)) continue;
				
				// don't update 'id' values (in the where clause)
				if($column == "id") continue;
				
				// also, don't update if it's not one of the columns in the table (uh, duh)
				if(!in_array($column, $table_columns)) continue;
				
				// add commas
				if(!empty($columns)) $columns .= ', ';
				
				// add slashes to help prevent SQL Injection problems
				// $value = addslashes($value);
				
				// assemble query strings
				$columns .= "{$column}='{$value}'";
			}
			
			// "UPDATE {$table} SET {$columns} WHERE id='{$params[id]}';"
			$query = 'UPDATE %s SET %s WHERE id=\'%s\';';
			
			// debug purposes
			// print sprintf($query, $table, $columns, $params['id']);
			
			// execute query
			$this->query(sprintf($query, $table, $columns, $params['id']));
		}
		public function delete($params, $table) {
			// get params
			$id = empty($params['id']) ? $params['where']['id'] : $params['id'];
			
			// "DELETE FROM {$table} WHERE id='{$nId}';"
			$query = 'DELETE FROM %s WHERE id=\'%s\' LIMIT 1;';
			
			// execute query
			$this->query(sprintf($query, $table, $id));
			// print "DELETE FROM {$table} WHERE id='{$nId}';";
		}
		public function delete_all($params, $table) {
			// where operators
			$where_operator = !empty($params["where_operator"]) ? $params["where_operator"] : "AND";
			
			// compile list of columns to select (usually defaults to "*")
			if(is_array($params["columns"])) {
				$columns = implode($params["columns"], ", ");
			} else {
				// if nothing is specified, select everything
				$columns = !empty($params["columns"]) ? $params["columns"] : "*";
			}
			
			// compile the list of WHERE arguments
			if(is_array($params["where"])) {
				foreach($params["where"] as $column=>$value) {
					if($where != "") $where .= " {$where_operator} "; // append value of $strWhereOperator
					$where .= "{$column}='{$value}'";
				}
			} else {
				if(!empty($params["where"])) $where = $params["where"];
			}
			if(!empty($where)) $where = "WHERE {$where}";
			
			if(!empty($params['limit'])) $limit = "LIMIT {$params[limit]}"; // for when in 'delete' // else $limit = "LIMIT 1";
			
			// "SELECT {$columns} FROM {$table} {$strWhere}{$strOrderBy};"
			$query = 'DELETE FROM %s %s %s;';
			
			// debug purposes
			// print sprintf($query, $table, $where, $limit) . "<br />\n";
			
			// execute query
			$this->query(sprintf($query, $table, $where, $limit));
		}
		
		// functions
		public function get_last_id() {
			return mysql_insert_id();
		}
		public function rows_found() {
			return mysql_num_rows($this->result);
		}
		public function affected_rows() {
			return mysql_affected_rows($this->db);
		}
		
		// get table column names (to make sure 'updates' and 'inserts' don't try to update a column that doesn't exist)
		public function explain_table($table) {
			// query
			$query = "EXPLAIN {$table};";
			
			// get table details
			$result = @mysql_query($query, $this->db);
			
			// loop through details
			while($row = @mysql_fetch_assoc($result)) {
				$rows[] = $row;
			}
			
			foreach($rows as $column) {
				$columns[] = $column['Field'];
			}
			
			return $columns;
		}
	}
	
	class CouldNotConnect extends StdException { }
	class CouldNotSelectDB extends StdException { }
	class CouldNotIterate extends StdException { }
	class CouldNotFind extends StdException { }
	class CouldNotSave extends StdException { }
	class CouldNotDelete extends StdException { }
?>