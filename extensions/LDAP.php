<?php
	// @title   LDAP class
	// @author  Matt Todd <matt@matttoddphoto.com>
	// @created 2005-11-28
	// @desc    Handles LDAP authentication and information retreival
	
	/* @usage
			
			// show one completed
			
	*/
	
	class LDAP {
		// properties
		public $is_connected = false;
		public $is_bound = false;
		private $handlers = array();
		private $results = array();
		public $count = 0;
		private $results_order = array();
		
		// constructor
		public function __construct($config = null) {
			$this->config = (empty($config) ? Config2::$config['adapters']['ldap'] : $config);
		}
		
		// accessors
		public function __get($name) {
			$entry = current($this->results);
			return $entry[$name];
		}
		public function as_array() {
			return $this->results;
		}
		
		// iteration and navigation
		public function current() {
			return current($this->results);
		}
		public function next() {
			next($this->results);
			return $this;
		}
		public function previous() {
			prev($this->results);
			return $this;
		}
		public function first() {
			reset($this->results);
			return $this;
		}
		public function last() {
			end($this->results);
			return $this;
		}
		
		// data functions
		protected function full_domain($username = null) {
			foreach($this->config['domain']['offices'] as $office) { // assemble offices
				if(!empty($offices)) $offices .= ',';
				$offices .= sprintf('OU=%s', $office);
			}
			if($username === null) return "{$offices},DC={$this->config[domain][subdomain]},DC={$this->config[domain][domain]},DC={$this->config[domain][top_level_domain]}";
			return "CN={$username},{$offices},DC={$this->config[domain][subdomain]},DC={$this->config[domain][domain]},DC={$this->config[domain][top_level_domain]}";
		}
		protected function short_domain($username = null) {
			if($username === null) return "{$this->config[domain][short_domain]}";
			return "{$username}@{$this->config[domain][short_domain]}";
		}
		
		// clean results to a sensible structure
		protected function clean($results) {
			if(is_array($results)) {
				// remove 'count' keys
				if(!empty($results['count'])) unset($results['count']);
				
				// if results has only one value, return it
				if((count($results) == 1) && (!empty($results[0])) && (!is_array($results[0]))) {
					return $results[0];
				}
				
				// cleans repetitive data, et al
				foreach($results as $key=>$entry) {
					# (int)0 == "count", so we need to use ===
					if($k = array_search($key, $results)) unset($results[$k]);
					
					// remove all integer keys except for those with valuable data
					if(is_int($key) && is_string($entry) && is_array($results[$entry])) {
						unset($results[$key]);
						continue;
					}
					
					// clean children too
					if(is_array($entry)) {
						$results[$key] = $this->clean($entry);
					}
				}
			}
			// return data
			return $results;
		}
		
		// overridden functions
		public function connect() {
			// connect to LDAP server
			if(!($this->handlers['connection'] = ldap_connect($this->config['server']))) { // , 636
				// could not connect to the LDAP server, throw error
				throw new Exception("Could not connect to LDAP server {$this->config[server]}");
			} else {
				// ldap_set_option($this->handlers['connection'], LDAP_OPT_PROTOCOL_VERSION, 3);
				// set connection status
				$this->is_connected = true;
				return true;
			}
		}
		public function user_bind($username, $password, $full_domain = false) {
			// get proper username
			// $username = (($full_domain) ? $this->full_domain($username) : $this->short_domain($username));
			
			// set default bind status
			$bound = false;
			
			// bind to LDAP server
			// this takes the provided departments and if one level doesn't work, removes it and attempts to bind again
			while($this->config['domain']['offices']) {
				if($this->handlers['binding'] = @ldap_bind($this->handlers['connection'], $this->full_domain($username), $password)) {
					$bound = true;
					break;
				} else {
					array_shift($this->config['domain']['offices']);
				}
			}
			if(!$bound) {
				// could not bind to the server as $username
				throw new Exception("Could not bind the LDAP server as <em>{$username}</em>");
			} else {
				// successfully connected and bound server as $username
				$this->is_bound = true;
				return true;
			}
		}
		public function bind($full_domain = false) {
			// ldap reader binding
			$username = $this->config['domain']['reader']['username'];
			$password = $this->config['domain']['reader']['password'];
			
			// bind to LDAP server
			if(!($this->handlers['binding'] = @ldap_bind($this->handlers['connection'], $username, $password))) {
				// could not bind to the server as $username
				throw new Exception("Could not bind the LDAP server as <em>{$username}</em>");
			} else {
				// successfully connected and bound server as $username
				$this->is_bound = true;
				return true;
			}
		}
		public function disconnect() {
			// unbind/disconnect LDAP server
			if(!(ldap_unbind($this->handlers['connection']))) return false;
			
			$this->is_bound = false;
			$this->is_connected = false;
			$this->handlers['connection'] = null;
			
			return true;
		}
		public function find($params, $full_domain = false) {
			// remove previous results
			$this->handlers['results'] = null;
			
			// set default params
			$restrict_to = array();
			$filter = array();
			$sort = array();
			
			// get settings from config
			if(!empty($this->config['restrict_to'])) $restrict_to = $this->config['restrict_to'];
			if(!empty($this->config['filter'])) $filter = $this->config['filter'];
			if(!empty($this->config['sort'])) $sort = $this->config['sort'];
			
			// get parameters for search
			$domain = (($full_domain) ? $this->full_domain() : $this->short_domain());
			if(!empty($params['filter'])) $filter = $params['filter']; // such as: "(|(sn=$person*)(givenname=$person*))";
			if(!empty($params['restrict_to'])) $restrict_to = $params['restrict_to']; // such as: array("ou", "sn", "givenname", "mail");
			if(!empty($params['sort'])) $sort = $params['sort']; // such as: array("department", "sn");
			
			// find
			$this->handlers['results'] = @ldap_search($this->handlers['connection'], $domain, $filter, $restrict_to);
			
			// if sort param set, sort results
			if(!empty($sort)) {
				foreach($sort as $key) {
					@ldap_sort($this->handlers['connection'], $this->handlers['results'], $key);
				}
			}
			
			// get entries
			$results = @ldap_get_entries($this->handlers['connection'], $this->handlers['results']);
			
			// get count
			$this->count = $results['count'];
			
			// clean results
			$results = $this->clean($results);
			
			// process results
			foreach($results as $key=>$entry) {
				$this->results_order[$key] = $entry['name'];
				$this->results[$entry['name']] = new entry($entry);
			}
			
			// remove results handle
			$this->handlers['results'] = array();
			
			// return this object for more actions
			return $this;
		}
		public function locate($name) {
			return $this->results[$name];
		}
		public function save($entry, $params = null, $full_domain = false) {
			// save entry
		}
		public function insert($params, $entry, $full_domain = false) {
			// get domain
			if(empty($params['domain'])) $domain = (($full_domain) ? $this->full_domain() : $this->short_domain()); else $domain = $params['domain'];
			
			// insert data
			if(!(ldap_add($this->handlers['connection'], $domain, $entry))) return false;
			
			return true;
		}
		public function update($params, $entry, $full_domain = false) {
			// get domain
			if(empty($params['domain'])) $domain = (($full_domain) ? $this->full_domain() : $this->short_domain()); else $domain = $params['domain'];
			
			// insert data
			if(!(ldap_modify($this->handlers['connection'], $domain, $entry))) return false;
			
			return true;
		}
		public function delete($params, $full_domain = false) {
			// get domain
			if(empty($params['domain'])) $domain = (($full_domain) ? $this->full_domain() : $this->short_domain()); else $domain = $params['domain'];
			
			// insert data
			if(!(ldap_delete($this->handlers['connection'], $domain))) return false;
			
			return true;
		}
		
		// retrieve LDAP error on failures
		public function error() {
			return ldap_error($this->handlers['connection']);
		}
	}
	
	class entry {
		private $properties = array();
		
		// constructor
		public function __construct($properties) {
			$this->properties = $properties;
		}
		
		// accessors
		public function __get($name) {
			return $this->properties[$name];
		}
		public function as_array() {
			return $this->properties;
		}
	}
?>