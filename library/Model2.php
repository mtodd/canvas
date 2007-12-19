<?php

	// @title		Model Abstract Class
	// @author	Matt Todd
	// @date		2005-11-26 5:26PM
	// @desc		Handles database interactivity for the specific Model (this
	//					is the abstract class that all the other Models are based on).
	
	// load StdException
	include_once 'stdexception.php';
	
	// get adapter name for this environment
	$adapter = new Config2();
	$adapter = $adapter->database;
	$adapter = strtolower($adapter[Config2::$environment]['adapter']);
	
	// load adapter
	include_once Conventions::adapter_path($adapter);
	
	// classes
	abstract class Model {
		protected static $adapter = array();	// the current, active adapter
		protected static $columns = array();	// columns for this table
		public $table, $scope;								// table name and scope (helps refine queries, usually for security purposes)
		protected $rows = array();						// the row(s) being operated on or saught
		protected $has_one, $has_many,				// column associations
			$has_property, $has_properties,
			$belongs_to_many;
		protected $validates;									// data validation
		
		// associations default values
		public $default_order = '';
		
		// constructor and loader
		public function __construct() {
			// load configuration
			$config = new Config2();
			
			// get all database config data
			$config = $config->database;
			
			// load environment-specific database connection information into the adapter
			self::$adapter['config'] = $config[Config2::$environment];
			
			// get the table name (usually the plural form of the model name, eg: shoe=>shoes or person=>people)
			if(empty($this->table)) $this->table = Inflector::pluralize(get_class($this));
			
			// make sure adapter is loaded
			self::load_adapter();
		}
		// __constructor static-aliases
		public static function begin($model) {
			return new $model();
		}
		
		protected static function load_adapter() {
			// get the adapter
			$adapter = strtolower(self::$adapter['config']['adapter']);
			
			// create an instance of the adapter if it doesn't already exist
			if(empty(self::$adapter['handle']))
				self::$adapter['handle'] = new $adapter(self::$adapter['config']);
			
			// should this be here? do we want it to automatically connect?
			self::$adapter['handle']->connect();
			self::$adapter['handle']->select_db(self::$adapter['config']['database']);
		}
		
		protected function map_associations($params) {
			if(array_search('non_greedy', $params) !== false) return $params['join'];
			/*
				@use
					Internally, find() calls this to set up joins for any immediate
					associations (has_one/has_propert(y/ies)) or greedy associations
					(has_many)
			*/
			
			// check for has_property
			if(!empty($this->has_property))
				$this->has_properties[] = $this->has_property;
				$this->has_property = null;
			// and check for has_properties and then parse them
			if(!empty($this->has_properties)) {
				foreach(self::has_properties($this->has_properties) as $association) {
					$joins[] = $association;
				}
				// $joins = array_unique($joins); // handle accidental duplication // deprecated of removing the data from $this->has_property instead
			}
			
			// handle many-to-many relationships
			if(!empty($this->belongs_to_many)) {
				$joins[] = array('table'=>$this->belongs_to_many[0], 'on'=>':@table.:singular_table_id=:table.id');
			}
			
			/*// handle has_one relationships when they are specified as 'greedy'
			if(false && !empty($this->has_one)) {
				foreach(self::has_one($this->has_one) as $association) {
					$joins[] = $association;
				}
			}
			
			// handle has_many relationships when they are specified as 'greedy'
			if(false && !empty($this->has_many)) {
				foreach(self::has_many($this->has_many as $association) {
					$joins[] = $association;
				}
			}*/
			
			// build up the joins into the proper format and return it
			return self::build_joins_up($joins);
		}
		protected static function build_joins_up($all_joins) {
			if(is_array($all_joins)) $joins = array_shift($all_joins); else return $all_joins;
			if(!empty($all_joins)) $joins['join'] = self::build_joins_up($all_joins);
			return $joins;
		}
		
		// database action functions
		public function find($params = null) {
			$this->before_find(); // callback
			
			// reset results and iteration
			$this->rows = null;
			$this->rows = array();
			
			// add the table to the parameters of the query and process all other automatic properties
			if(empty($this->belongs_to_many)) {
				$params['table'] = $this->table;
			} else { // many-to-many relationship
				$params['table'] = $this->belongs_to_many['through'];
			}
			// map associations
			$params['join'] = $this->map_associations($params);
			
			// execute select
			$this->rows = self::$adapter['handle']->find($params); // include $scope as third param to give even more definition to select queries (for security)
			
			if(!empty($this->rows)) foreach($this->rows as $id=>$row) {
				if(empty($id) || empty($row)) continue;
				$this->rows[$id] = $this->after_find_for_each($row);
			}
			
			$this->after_find(); // callback
			
			// return $this to allow for stacked calls (like '$model->find()->all()')
			return $this;
			
			// deprecated to keep from having to do tedious error checking when a find() is called...
			// return $this when number of rows is greater than 0, false if 0
			// if(self::$adapter['handle']->rows_found() > 0) return $this; else return false;
		}
		
		public function find_all($params = null) {
			if(!$this->find($params)) return false;
			return $this->all();
		}
		
		public function find_by($params) {
			// $params will be an associative array of ids and values as well as the where clause (eg: 'id=:id')
			if(!$this->find(array('where'=>$params))) return false;
			return $this->all();
		}
		
		public function find_by_id($id) {
			return $this->find(array("where"=>array(':table.id=":id"', 'id'=>$id)));
		}
		
		public function save() {
			$this->before_save(); // callback
			
			// set up the params
			$params = array('table'=>$this->table, 'values'=>$this->remove_non_columns($this->current()), 'where'=>array('id=":id"', 'id'=>$this->id), 'limit'=>'1');
			
			// save the current row and update the reference within the collection
			if($row = self::$adapter['handle']->save($params)) {
				if(key($this->rows) == 'new') unset($this->rows['new']);
				$this->rows[$row->id] = $row;
			} else throw new ModelException();
			
			$this->after_save(); // callback
			
			// return $this when number of affected rows is greater than 0, false if 0
			if(self::$adapter['handle']->affected_rows() > 0) return $this; else return false;
		}
		
		public function delete($params = null) {
			$this->before_delete(); // callback
			
			if(!$this->id) return false; // either find() and then delete() or delete() with parameters! duh!
			
			// if $params is empty, give it a default selector of the current element
			if(empty($params)) $params = array('table'=>$this->table, 'where'=>array('id=":id"', 'id'=>$this->id), 'limit'=>'1'); // delete the currently selected ID (and only the one)
			
			$result = self::$adapter['handle']->delete($params);
			
			// callback // passing in the parameters
			// and the result/return value for good
			// measure (for verbosity)
			$this->after_delete($params, $result);
			
			// return $this when number of affected rows is greater than 0, false if 0
			if(self::$adapter['handle']->affected_rows() > 0) return $this; else return false;
		}
		public function delete_all($params = null) {
			$this->before_delete(); // callback
			
			// perform a find if there aren't any results already
			if($this->is_empty()) $this->find($params);
			
			// loop through results and delete away
			foreach($this->rows as $rows) {
				// delete this row
				$this->delete(array('table'=>$this->table, 'where'=>array('id=":id"', 'id'=>$row->id), 'limit'=>'1'));
			}
			
			$this->after_delete($params, $result); // callback
			
			// return true when number of affected rows is greater than 0, false if 0
			if($this->db->affected_rows() > 0) return true; else return false;
		}
		
		// transactional functionality
		public function start() {
			// begin transaction
			if($row = self::$adapter['handle']->begin()) {
				// return on success
				return true;
			} else throw new ModelException();
		}
		public function commit() {
			// commit and end transaction
			if($row = self::$adapter['handle']->commit()) {
				// return on success
				return true;
			} else throw new ModelException();
		}
		public function end() {
			// alias of commit
			return $this->commit();
		}
		public function rollback() {
			// rollback transactions if there were problems
			if($row = self::$adapter['handle']->rollback()) {
				// return on success
				return true;
			} else throw new ModelException();
		}
		
		// facilitates the 'find_by_blah_and_blah' dynamic find functions
		public function __call($name, $params) {
			if(substr_count($name, "find_by_") > 0) { // find by
				// take out function name
				$keys = str_replace("find_by_", "", $name);
				
				$params = $this->process_find_call($keys, $params);
				
				return $this->find(array("where"=>$params));
			} elseif(substr_count($name, "find_or_create_by_") > 0) { // find or create by
				// take out function name
				$keys = str_replace("find_or_create_by_", "", $name);
				
				$params = $this->process_find_call($keys, $params);
				
				if($this->find(array("where"=>$params))->is_empty()) {
					foreach($params as $key=>$value) {
						$this->$key = $value;
					}
					$this->save();
				}
				
				return true;
			} else {
				// something totally different and unexpected
			}
		}
		protected function process_find_call($name, $func_params) {
			// add support in for column1_or_column2 parsing
			$columns = explode("_and_", $name);
			while((list(, $key) = each($columns)) && (list(, $value) = each($func_params))) {
				$params[$key] = $value;
			}
			return $params;
		}
		
		public function __set($key, $value) {
			if(empty($this->rows)) {
				$this->rows['new'] = new row();
				$id = 'new';
			} else $id = ($this->current()->id) ? $this->current()->id : 'new';
			return $this->rows[$id]->$key = $value;
		}
		public function __get($key) {
			if(empty($this->rows)) return false;
			
			$id = $this->current()->id;
			
			// handle associations if applicable
			if($associate = $this->has_one($key)) {
				$this->rows[$id]->$key = $associate;
				return $associate;
			}
			if($associates = $this->has_many($key)) {
				$this->rows[$id]->$key = $associates;
				return $associates;
			}
			
			// handle has_* requests (for tests if associations have been set or exist)
			if(substr($key, 0, 4) == 'has_') {
				$key = substr($key, 4); // get out the keyword (minus the has_ prefix)
				$singular_key = Inflector::singularize(get_class($this));
				$count = $this->$key->count(array("{$singular_key}_id=':id'", 'id'=>$this->id));
				if(!empty($count)) return true;
// 				if(!empty($this->$key)) return true;
// 				if(!$this->$key->is_empty()) return true;
					else return false;
			}
			
			// handle quantity requests
			if($key == 'count') return count($this->rows);
			
			// calls the current row object
			// (important to remain this way because just doing $this->id would recursively call itself)
			return stripslashes($this->rows[$this->current()->id]->$key);
		}
		
		// iteration support methods
		// returns the current row object (vital for __get() and __set() functionality)
		public function current() {
			if(empty($this->rows)) return false;
			return current($this->rows);
		}
		// this returns $this->rows (an array of row objects)
		public function all() {
			if(empty($this->rows)) return false;
			return $this->rows;
		}
		// the rest of these return $this to allow for $model->next()->id or such.
		public function next() {
			if(empty($this->rows)) return false;
			next($this->rows);
			return $this;
		}
		public function previous() {
			if(empty($this->rows)) return false;
			prev($this->rows);
			return $this;
		}
		public function first() {
			if(empty($this->rows)) return false;
			reset($this->rows);
			return $this;
		}
		public function last() {
			if(empty($this->rows)) return false;
			end($this->rows);
			return $this;
		}
		
		// get the data in an array form
		public function all_as_array() {
			if(empty($this->rows)) return false;
			foreach($this->rows as $row) {
				$rows[$row->id] = $row->as_array();
			}
			return $rows;
		}
		
		// check for the presence of data
		public function is_empty() {
			return empty($this->rows);
		}
		
		// return number of entries
		public function count($conditions = null) {
			if($conditions === null) $conditions = array();
			$model = get_class($this);
			$model = new $model();
			return $model->find(array('where'=>$conditions, 'columns'=>'count(id) as row_count', 'non_greedy'))->row_count;
		}
		public function count_rows() {
			return count($this->rows);
		}
		
		// verification and data integrity support methods
		protected function remove_non_columns($row) {
			// get columns (and cache them if they aren't cached already)
			if(empty(self::$columns[$this->table])) self::$columns[$this->table] = self::$adapter['handle']->columns($this->table);
			$row->remove_non_columns(self::$columns[$this->table]);
			return $row;
		}
		
		// assocation methods
		protected static function has_properties($properties) {
			// get {$with}
			if($properties['with'] !== null) {
				$with = $properties['with'];
				unset($properties['with']);
			}
			
			foreach($properties as $property) {
				$property = explode('.', $property);
				$table = $property[0];
				$columns = $property[1];
				$single_table_id = ((empty($with)) ? ":singular_table_id" : $with);
				$joins[] = array('table'=>$table, 'on'=>":@table.{$single_table_id}=:table.id", 'columns'=>$columns);
			}
			
			return $joins;
		}
		protected function has_one($association) {
			if(!empty($this->rows[$this->current()->id]->$association)) return $this->rows[$this->current()->id]->$association;
			if(!empty($this->has_one) && array_search($association, $this->has_one) !== false) {
				$associate = new $association();
				$associating_column = "{$association}_id";
				$associate->find_by_id($this->$associating_column);
				return $associate;
			} else return false;
		}
		protected function has_many($association) {
			if(!empty($this->rows[$this->current()->id]->$association)) return $this->rows[$this->current()->id]->$association;
			if(!empty($this->has_many) && array_search($association, $this->has_many) !== false) {
				$association_class = Inflector::singularize($association);
				$associating_column = get_class($this);
				$associate = new $association_class();
				$associate->find(array('where'=>array($associating_column . '_id=":id"', 'id'=>$this->id), 'order_by'=>$associate->default_order));
				return $associate;
			} else return false;
		}
		
		// events/callbacks
		protected function before_find() {}
		protected function after_find() {}
		protected function before_save() {}
		protected function after_save() {}
		protected function before_delete() {}
		protected function after_delete() {}
		protected function before_create() {}
		protected function after_create() {}
		protected function before_update() {}
		protected function after_update() {}
		protected function before_next() {}
		protected function after_next() {}
		
		// special events/callbacks
		protected function after_find_for_each($row) {
			return $row;
		}
		
		// descructor
		public function __destruct() {
			// possibly put some serialization code in here?
			// end;
		}
	}
	
	class ModelException extends StdException {}
?>