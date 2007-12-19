<?php
	// @title	DataBase abstract class
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-09-26
	// @desc	An abstract class to standardize the database interaction methods
	//		(to be implemented/inherited from a specific class, such as 'MySQL',
	//		'MSSQL', et al).
	// @requires stdexception.php (StdException class)
	
	include_once 'stdexception.php';
	
	// classes
	abstract class database {
		// protected variables
		public $config, $handle, $result, $is_connected; // state and configuration
		
		// adapter type
		public static $adapter_class = ''; // set automatically in the constructor
		public static $static_handle = null; // static handle to the DB
		
		/*
			@ Adapters need to define these constants for the Query Builder
			
			// query templates
			const       select = 'SELECT :columns FROM :table :join :where :group_by :having :order_by :limit';
			const       delete = 'DELETE FROM :table :where :order_by :limit;';
			const       insert = 'INSERT INTO :table SET :values;';
			const       update = 'UPDATE :table SET :values :where :order_by :limit;';
			// query templates' parts
			const         join = 'LEFT JOIN :table ON :on :join';
			const        where = 'WHERE :where';
			const     group_by = 'GROUP BY :group_by';
			const       having = 'HAVING :having';
			const     order_by = 'ORDER BY :order_by';
			const        limit = 'LIMIT :limit :offset';
			const       offset = 'OFFSET :offset';
			// meta templates
			const named_entity = ':name AS :alias';
			const     equality = '":1" = ":2"';
		*/
		
		// constructor
		public function __construct($params) {
			// foreach($params as $key=>$value) $this->config[$key] = $value; // '$this->config = $params;' is much shorter and far more optimized
			$this->config = $params;
			
			// defaults
			$this->handle = null;
			self::$static_handle = null;
			$this->is_connected = false;
		}
		
		// abstract functions
		abstract public function connect();
		abstract public function select_db($database = null);
		abstract public function disconnect();
		abstract protected function query($params);
		abstract public function iterate($result);
		
		// interface functions
		public function find($params) {
			// if $params is null, that means we should select everything!
			// or, if it's a string, select whichever is specified: first, last, all, et al
			
			// select all columns by default
			if(empty($params['columns'])) $params['columns'] = '*';
			
			if(is_array($params)) {
				// minimal preparations needed to be done to execute select query
			} elseif(is_string($params)) {
				// depending on string, set certain parameters
				switch(strtolower($params)) {
					case 'all':   break;
					case 'first': $params['limit'] = '1';
					              break;
					case 'last':  $params['order_by'] = 'id DESC';
					              $params['limit'] = '1';
					              break;
				}
				$params = self::prepare_params($params);
			} else {
				$params = self::prepare_params($params);
			}
			
			$result = $this->select($params);
			
			// throw an exception if there's a problem
			// why throw an exception? // if($result == false) throw new CouldNotFind('Could not find anything');
			
			while($row = $this->iterate($result)) {
				$rows[$row['id']] = new row($row);
			}
			
			return $rows;
		}
		public function save($params) {
			$row = $params['values'];
			$params['values'] = self::build_values($params['values']->as_array());
			
			// if the ID is set, update the row; otherwise, insert a new one
			if($row->id)
				$result = $this->update($params);
			else
				$result = $this->insert($params);
			
			// throw an exception if there's a problem
			if($result == false) throw new CouldNotSave('Could not save the data');
			
			// get the ID set properly if it isn't set (for new entries)
			if(!$row->id) $row->id = $this->id($result);
			
			// return the row saved
			return $row;
		}
		
		// action functions
		protected function select($params) {
			$params[0] = self::get_clause('select');
			
			// build special clauses
			$params['columns'] = self::build_columns($params);
			$params = self::build_join($params);
			if(empty($params['where'][0]) && !empty($params['where'])) $params['where'][0] = self::build_where_query($params['where']);
			
			// build other clauses
			$clauses = array('cache', 'where', 'order_by', 'group_by', 'limit', 'offset', 'having');
			foreach($clauses as $clause) {
				if(!empty($params[$clause])) $params[$clause] = self::build_clause($clause, $params[$clause]);
			}
			
			return $this->query(self::build_query(self::prepare_params($params)));
		}
		protected function insert($params) {
			$params[0] = self::get_clause('insert');
			
			return $this->query(self::build_query(self::prepare_params($params)));
		}
		protected function update($params) {
			$params[0] = self::get_clause('update');
			
			// build other clauses
			$clauses = array('where', 'order_by', 'limit');
			foreach($clauses as $clause) {
				if(!empty($params[$clause])) $params[$clause] = self::build_clause($clause, $params[$clause]);
			}
			
			return $this->query(self::build_query(self::prepare_params($params)));
		}
		public function delete($params) {
			$params[0] = self::get_clause('delete');
			
			// build where clause
			if(empty($params['where'][0]) && !empty($params['where'])) $params['where'][0] = self::build_where_query($params['where']);
			
			// build other clauses
			$clauses = array('where', 'order_by', 'group_by', 'limit', 'offset', 'having');
			foreach($clauses as $clause) {
				if(!empty($params[$clause])) $params[$clause] = self::build_clause($clause, $params[$clause]);
			}
			
			$result = $this->query(self::build_query(self::prepare_params($params)));
			
			// throw an exception if there's a problem
			if($result == false) throw new CouldNotDelete('Could not delete the row');
			
			return $result;
		}
		
		// transactional functionality
		protected function begin() {
			$params[0] = self::get_clause('begin');
			return $this->query(self::build_query(self::prepare_params($params)));
		}
		protected function commit() {
			$params[0] = self::get_clause('commit');
			return $this->query(self::build_query(self::prepare_params($params)));
		}
		protected function end() {
			// alias of commit()
			return $this->commit();
		}
		protected function rollback() {
			$params[0] = self::get_clause('rollback');
			return $this->query(self::build_query(self::prepare_params($params)));
		}
		
		// internal functions (builders, dependent builders, special builders, et al)
    protected static function build_query($params) {
			$query = $params[0];
			unset($params[0]);
			
			$query = self::build_sub_query($query, $params);
			
			// handle root references
			foreach($params as $key=>$value) {
				$query = str_replace(":@{$key}", $value, $query);
			}
			
			return $query;
		}
		protected static function build_sub_query($query, $params) {
			foreach($params as $key=>$value) {
				// if it's an array, it needs to be built itself and then the contents can be directly subbed in
				if(is_array($value))
					$value = self::build_sub_query($query, $value);
				
				if(empty($value))
					$query = str_replace(" :{$key}", '', $query);
				else {
					$query = str_replace(":{$key}", $value, $query);
				}
			}
			
			// handle backreferences and parent references
			$query = self::build_query_backreferences($query, $params);
			
			return $query;
		}
		protected static function build_query_backreferences($query, $params) {
			foreach($params as $key=>$value) {
				$query = str_replace(":{$key}", $value, $query);
			}
			return preg_replace('/:#(#*)([\w\d_]+)/', ':$1$2', $query);
		}
		protected static function prepare_params($params) {
			// the clauses to check against
			$clauses = self::get_clauses_from($params[0]);
			
			// make sure that all values are set and the proper clauses are set
			foreach($clauses as $clause) {
				if(empty($params[$clause])) { $params[$clause] = ''; continue; } // if it's not been set, give it an empty value
				// if(is_array($params[$clause]) && empty($params[0])) $params[$clause][0] = self::get_clause($clause); // otherwise, get its clause
			}
			
			return $params;
		}
		
		// special dependent builder functions
		protected static function build_values($params) {
			foreach($params as $column=>$value) {
				if($column == 'id') continue;
				$value = self::sanitize($value);
				$values .= (((!empty($values)) ? ', ' : '') . str_replace('{:1}', $column, str_replace('{:2}', $value, self::get_clause('value'))));
			}
			return $values;
		}
		protected static function build_where_query($params) {
			foreach($params as $column=>$value) {
				$query .= (((!empty($query)) ? ' and ' : '') . str_replace('{:1}', $column, str_replace('{:2}', ":{$column}", self::get_clause('value'))));
			}
			return $query;
		}
		protected static function build_clause($name, $clause_data) {
			$clause_template = self::get_clause($name);
			if(empty($clause_template)) $clause_template = ":{$name}";
			if(is_array($clause_data)) {
				$clause_form = $clause_data[0];
				unset($clause_data[0]);
				foreach($clause_data as $key=>$value) {
					$clause_form = str_replace(":{$key}", $value, $clause_form);
				}
				$clause_data = $clause_form;
			}
			return str_replace(":{$name}", $clause_data, $clause_template);;
		}
		protected static function get_clause($name) {
			$class = new ReflectionClass(self::$adapter_class);
			$constants = $class->getConstants();
			return $constants[$name];
		}
		protected static function get_clauses_from($query) {
			$query = explode(' ', $query);
			
			foreach($query as $clause) {
				if($clause[0] != ':') continue;
				$clauses[] = substr($clause, 1);
			}
			
			return $clauses;
		}
		
		// special, overridable builders
		protected static function build_columns($params) {
			if(!is_array($params['columns'])) return $params['columns'];
			
			$columns = $params['columns'];
			foreach($columns as $column) {
				$columns_as_string = ((!empty($columns_as_string)) ? ', ' : '') . "{$params['table']}.*";
			}
			
			return $columns;
		}
		protected static function build_join($params) {
			// if(empty($params['join'][0])) $params['join'][0] = self::get_clause('join');
			// if(!empty($params['join']['join'])) $params['join']['join'] = self::build_join($params['join']);
			
			if(empty($params['join'])) return $params;
			
			// handle columns
			if(empty($params['columns']) || $params['columns'] == '*') $params['columns'] = array("{$params['table']}.*");
			foreach(self::get_join_columns($params['join']) as $column) {
				$params['columns'][] = $column;
			}
			$params['columns'] = implode(', ', $params['columns']);
			
			// assemble join clauses
			$params['join'] = self::add_join_clause($params['join']);
			
			return $params;
		}
		protected static function add_join_clause($join) {
			$query = $join[0];
			unset($join[0]);
			
			// get sub-join query clause if there is one available
			if(empty($query)) $query = self::get_clause('join');
			
			// get the table name as singular form
			$join['singular_table'] = Inflector::singularize($join['table']);
			
			if(!empty($join['join']))
				$join['join'] = self::add_join_clause($join['join']);
			else
				$query = str_replace(' :join', '', str_replace(' :#join', '', $query));
				
			$query = self::build_sub_query($query, $join);
			
			return $query;
		}
		protected static function get_join_columns($join) {
			// get nested joins' columns
			if(!empty($join['join']))
				$columns = self::get_join_columns($join['join']);
			
			if(empty($join['columns'])) $join['columns'] = self::get_columns($join['table']);
			
			// get columns
			if(!is_array($join['columns'])) $join['columns'] = explode(',', str_replace(' ', '', $join['columns']));
			foreach($join['columns'] as $column) {
				// skip over 'id' columns (because 'id' is gotten for the parent table)
				if($column == 'id') continue;
				// associate table and columns
				$columns[] = "{$join['table']}.{$column}" . ($column == '*' ? '' : " AS {$column}");
			}
			
			return $columns;
		}
		
		// support functions
		protected static function get_columns($table) {
			return call_user_func(array(self::$adapter_class, 'columns'), $table);
		}
		
		protected static function sanitize($value) {
			return call_user_func(array(self::$adapter_class, 'sanitize'), $value);
		}
		
		// descructor
		public function __destruct() {
			// possibly put some serialization code in here?
			// end;
		}
	}
	
	// as soon as Row is not used, get rid of it
	class row {
		// variables
		private $row;
		
		// constructor
		public function __construct($row = array()) {
			$this->row = $row;
		}
		
		// magic function override
		public function __set($name, $value) {
			$this->row[$name] = $value;
		}
		public function __get($name) {
			return $this->row[$name];
		}
		
		// functions
		public function to_array() {
			return $this->row;
		}
		// alias
		public function as_array() {
			return $this->to_array();
		}
		
		// remove non columns (to help with associations and joins)
		public function remove_non_columns($columns) {
			foreach($this->row as $column=>$value) {
				if(array_search($column, $columns) === false) unset($this->row[$column]);
			}
		}
	}
	
	class DBException extends StdException {}
	class DBQueryException extends DBException {}
	class DBExecutionException extends DBException {}
	class CouldNotConnect extends DBException {}
	class CouldNotSelectDB extends DBException {}
	class CouldNotIterate extends DBException {}
	class CouldNotFind extends DBException {}
	class CouldNotSave extends DBException {}
	class CouldNotDelete extends DBException {}
?>