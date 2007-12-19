<?php
	// @title    Pager
	// @role     handles paging for large result sets
	// @author   Matt Todd <matt@matttoddphoto.com>
	// @created	 2006-03-15
	// @requires extexception.php (extends ExtException class)
	// @usage    $p = new Pager($rows, $per_page);
	//           $p->page;
	
	// classes
	class Pager {
		private $pages = array();
		private $page = null;
		
		// constructor (vital)
		public function __construct($collection, $per_page) {
			if(!is_array($collection)) $collection = array();
			$this->pages = array_chunk($collection, $per_page);
		}
		public static function paginate($collection, $per_page) {
			return new Pager($collection, $per_page);
		}
		
		// navigation/iteration functions
		public function next() {
			if(!next($this->pages)) return false;
			return $this;
		}
		public function previous() {
			if(!prev($this->pages)) return false;
			return $this;
		}
		public function first() {
			reset($this->pages);
			return $this;
		}
		public function last() {
			end($this->pages);
			return $this;
		}
		public function current() {
			return current($this->pages);
		}
		public function all() {
			return current($this->pages);
		}
		public function page($page = null) {
			$this->page = $page;
			if($page !== null) return $this->pages[$page - 1];
			return current($this->pages);
		}
		public function all_pages() {
			return $this->pages;
		}
		public function page_numbers() {
			for($i = 0; $i < count($this->pages); $i++) {
				$page_numbers[] = $i + 1;
			}
			return $page_numbers;
		}
		public function page_number() {
			if($this->page == null) return 1;
			return $this->page;
		}
		
		// testing functions
		public function has_next() {
			// if the page has been set and the next one is set, then there has to be a next page
			if(!empty($this->page)) {
				if(!empty($this->pages[($this->page - 1) + 1])) return true; // see note below
					else return false;
					// NOTE: the page - 1 + 1 is basically
					// for semantics because the page value
					// is actually one greater than the actual
					// page id in the array
			}
			// skips all this extra complicated crap only if $this->page has been set (by $this->page($page);)
			$pages = $this->pages;
			self::set_pointer($pages);
			if(!next($pages)) return false;
			return true;
		}
		public function has_previous() {
			// if the page has been set and it's above 1, then there has to be a previous page
			if(!empty($this->page)) {
				if(($this->page - 1) > 0) return true;
					else return false;
			}
			// skips all this extra complicated crap only if $this->page has been set (by $this->page($page);)
			$pages = $this->pages;
			self::set_pointer($pages);
			if(!prev($pages)) return false;
			return true;
		}
		private function set_pointer(&$pages) {
			foreach($pages as $page) {
				$res = array_diff(current($this->pages), $page);
				if(empty($res)) break;
			}
		}
		
		// magic functions
		public function __get($name) {
			if($name == 'page') return $this->current();
			if($name == 'next_page') return $this->page + 1;
			if($name == 'previous_page') return $this->page - 1;
			if($name == 'count' || $name == 'page_count' || $name == 'sizeof') return count($this->collection);
			// if($name == '') return $x; //
			return false;
		}
		public function __set($name, $value) {
			return false;
		}
	}
	
	class PagerException extends StdException {}
?>