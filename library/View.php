<?php
	// @role		View
	// @title		View Class
	// @author		Matt Todd
	// @date		2005-12-28
	// @desc		Handles rendering the results to the user, possibly with Smarty
	
	include_once('stdexception.php');
	
	// classes
	class View { // should implement the IView interface
		// render
		public static function render($response, $params = null) {
			$view_name = $response->controller;
			$helper_name = file_exists("{$response->controller}_helper") ? "{$response->controller}_helper" : "application_helper";
			
			$smarty = new Smarty();
			
			$smarty->left_delimiter = "<" . "%"; // split them up as to keep them from parsing accidentally
			$smarty->right_delimiter = "%". ">"; // ditto
			$smarty->template_dir = "./views/{$view_name}/";
			$smarty->compile_dir = "./views/{$view_name}/compile/";
			$smarty->cache_dir = "./views/{$view_name}/cache/";
			$smarty->config_dir = "./views/{$view_name}/config/";
			
			// register helper functions
			self::register_helper_functions($helper_name, $smarty);
			
			// register while block
			$smarty->register_block('while', array(get_class(), 'while_block'));
			
			$smarty->assign('response', $response);
			foreach($response->respond() as $key=>$value) {
				// if($key == "response") continue;
				$smarty->assign($key, $value);
			}
			
			// render layout if set, action template if not...
			if($response->layout == null) {
				$smarty->display($response->template);
			} else {
				$smarty->display($response->layout);
			}
		}
		
		// this static method will analyze the methods of the helper class that is to be associated
		// with the views and will register these functions appropriately (automating the registration
		// process)
		private static function register_helper_functions($helper_name, &$smarty) {
			$helper_class = new ReflectionClass($helper_name);
			$filters = self::find_helper_filters($helper_class);
			foreach($helper_class->getMethods() as $method) {
				if(in_array($method->name, $filters)) {
					$smarty->register_modifier($method->name, array($helper_name, $method->name)); continue; // register filters as such
				}
				$smarty->register_function($method->name, array($helper_name, $method->name));
			}
		}
		private static function find_helper_filters($helper_class) {
			$properties = $helper_class->getProperties();
			$helper_class_name = $helper_class->getName();
			$helper_instance = new $helper_class_name();
			foreach($properties as $filter) {
				if($filter->getName() == "treat_as_filter") return $filter->getValue($helper_instance);
			}
		}
		
		// custom tags/functions for Smarty
		public static function while_block($params, $content, &$smarty, &$repeat) {
			$name = $params['name'];
			$value = $params['value'];
			$$name = $value;
			return $content;
		}
	}

	class ViewException extends StdException {}
?>