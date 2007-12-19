<?php
	class application_helper { // extends Controller {
		// all functions must be public and static, and must take the following
		// parameters: ($params, &$smarty)
		public $treat_as_filter = array('ago', 'human_readable', 'inspect', 'size_by_popularity');
		
		public static function resource($params, &$smarty) {
			$request = $params['request'];
			$resource = $params['resource'];
			return sprintf('%s://%s/%s/%s', $request->protocol, $request->host, $request->directory, $resource);
		}
		
		// link_to
		public static function link_to($params, &$smarty) {
			// get location
			$location = $smarty->_tpl_vars['request']->location;
			
			// ancient stuff, for review (pull out the sanitize stuff)
			// $controller = !empty($params["controller"]) ? $params["controller"] : $smarty->_tpl_vars['request']->route['controller'];
			// $action = !empty($params["action"]) ? $params["action"] : $smarty->_tpl_vars['request']->route['action'];
			// $id = !empty($params["id"]) ? self::sanitize_id($params["id"]) : "";
			// $mul = !empty($params["mul"]) ? $params["mul"] : "";
			// $mul_id = !empty($params["mulid"]) ? self::sanitize_id($params["mulid"]) : "";
			// if(!empty($mul)) $id = $id . $mul;
			// if(!empty($mul_id)) $id = $id . $mul_id;
			
			// assemble link
			$rel = !empty($params['rel']) ? " rel='{$params['rel']}'" : "";
			$html_options = $params["extra"]; // change from 'extra' to 'html_options' (will affect template code)
			$link_item = $params["title"];
			
			$onclick = !empty($params["confirm"]) ? sprintf(' onClick="if(confirm(\'%s\')) return true; else return false;"', $params["confirm"]) : "";
			$onclick = !empty($params["onclick"]) ? sprintf(' onClick="%s"', $params["onclick"]) : $onclick;
			
			if(empty($link_item) && !empty($params['image'])) $link_item = self::img($params, $smarty);
			// if(empty($link_item)) $link_item = $url;
			
			// remove non-route params
			unset($params['rel']);
			unset($params['extra']);
			unset($params['title']);
			unset($params['confirm']);
			unset($params['onclick']);
			
			// assemble route
			$route = Router2::url_for($params);
			
			// conjoin location and route
			$url = empty($params['url']) ? "{$location}{$route}" : $params['url'];
			
			$link = '<a href="%s" %s%s%s>%s</a>';
			
			return sprintf($link, $url, $html_options, $rel, $onclick, $link_item);
		}
		
		// creates an image
		public static function img($params, &$smarty) {
			$location = $smarty->_tpl_vars['request']->location;
			$img_dir = empty($params['img_dir']) ? "res/" : $params['img_dir'];
			$image = $params['image'];
			$border = empty($params['img_border']) ? "0" : $params['img_border'];
			$alt_text = empty($params['img_alt']) ? "" : $params['img_alt'];
			$extra = $params['img_extra'];
			
			// string template
			$img = '<img src="%s%s%s" border="%s" alt="%s" %s />';
			
			// return image tag
			return sprintf($img, $location, $img_dir, $image, $border, $alt_text, $extra);
		}
		
		// create_link
		public static function create_link($params, &$smarty) {
			return self::url_for($params, $smarty);
		}
		public static function url_for($params, &$smarty) {
			// get location
			$location = $smarty->_tpl_vars['request']->location;
			
			// assemble route
			$route = Router2::url_for($params);
			
			// old school (glean the default values from it)
			// $controller = !empty($params["controller"]) ? $params["controller"] : $smarty->_tpl_vars['request']->route['controller'];
			// $action = !empty($params["action"]) ? $params["action"] : $smarty->_tpl_vars['request']->route['action'];
			// $id = !empty($params["id"]) ? self::sanitize_id($params["id"]) : "";
			
			$link = '%s%s';
			
			return sprintf($link, $location, $route);
		}
		
		// sanitizes IDs (for IDs like Tags or Categories that can be separated by spaces...)
		public static function sanitize_id($id) {
			return urlencode($id);
		}
		
		// select
		public static function select($params, &$smarty) {
			// @params name, options, selected, html_options
			// set variables
			$name = $params["name"];
			$select_options = $params["options"];
			$selected = $params["selected"];
			$html_options = $params["extra"]; // change from 'extra' to something else considering there are already 'options'
			
			// templates
			$select_template = '<select name="%s" %s>%s</select>';
			$option_template = '<option value="%s" %s>%s</option>';
			
			foreach($select_options as $value=>$option) {
				$option_selected = ($selected == $value) ? 'selected="selected"' : '';
				$options .= sprintf($option_template, $value, $option_selected, $option);
			}
			
			return sprintf($select_template, $name, $html_options, $options);
		}
		
		public static function textile($params, &$smarty) {
			return RedCloth::to_html($params['text']);
		}
		
		// format_post
		public static function format_post($params, &$smarty) {
			$output = RedCloth::to_html($params['post']);
			return $output;
			
			$post = stripslashes($params["post"]);
			
			$p = '<p>%s</p>';
			
			foreach(explode("\n\n", str_replace("\r", "", $post)) as $para) {
				if(empty($para)) continue;
				
				$paras .= sprintf($p, $para) . "\n";
				
				if($params['truncate'] == 'intro') break; // just have the first paragraph (ingenious, no?)
			}
			$post = $paras;
			
			// do specialized formatting (Textile style)
			
			return $post;
		}
		
		// format_comment
		public static function format_comment($params, &$smarty) {
			$comment = htmlentities(stripslashes($params["comment"]));
			
			$p = '<p>%s</p>';
			foreach(explode("\n\n", str_replace("\r", "", $comment)) as $para) {
				$paras .= sprintf($p, $para) . "\n";
			}
			$comment = $paras;
			
			// do specialized formatting (Textile style)
			
			return $comment;
		}
		
		// count_comments
		public static function count_comments($params, &$smarty) {
			return count($params["comments"]);
		}
		
		// pluralize
		public static function pluralize($params, &$smarty) {
			$word = $params['word'];
			$number = (is_array($params['number'])) ? count($params['number']) : intval($params['number']);
			$string_together = ($params['string_together'] == 'true') ? true : false;
			
			// pluralize word if the count is greater than 1
			if($number > 1 || $number == 0) $word = Pluralize::pluralize($word);
			
			// Pluralize supports alternate syntax:
			// - $Pluralize::word // returns 'words' regardless of number
			// - $Pluralize::word = number // returns 'word' or 'words' depending on number given to it
			// note: this is only supported for instances and not static syntax
			
			// if you want to have the string "12 comments" or "1 feast" returned instead of just the pluralized form
			if($string_together) $word = sprintf("%d %s", $number, $word);
			
			return $word;
			
			// @refer_to "An Algorithmic Approach to English Pluralization":http://www.csse.monash.edu.au/~damian/papers/HTML/Plurals.html
		}
		
		// date functions
		public $date_formats = array(
			'standard'=>'Y-m-d',
			'standard+time'=>'Y-m-d H:i:s',
			'natural'=>'F j, Y, g:i a',
			'mysql'=>'Y-m-d H:i:s',
		);
		public static function now($params, &$smarty) {
			if(!empty(self::$date_formats[$params['format']])) $format = self::$date_formats[$params['format']];
			if(empty($params['format'])) $format = "Y-m-d H:i:s"; else $format = $params['format'];
			return date($format);
		}
		
		// filters (make sure these are registered in $treat_as_filter at the top)
		public static function ago($date) {
			$date = $date . ' ' . date('H:i:s');
			$date = strtotime($date);
			$days_ago = (strftime("%j") + strftime("%Y") * 365) - (strftime("%j", $date) + strftime("%Y", $date) * 365);
			if($days_ago < 1) {
				$hours_ago = (strftime("%H") - strftime("%H", $date));
				if($hours_ago == 0) return "under a minute ago";
				if($hours_ago < 1) return "under an hour ago";
				if($hours_ago == 1) return "about an hour ago";
				return "today";
			} else if($days_ago < 7) {
				// handle under a week
				if($days_ago == 1) return "yesterday";
				return "about {$days_ago} days ago";
			} else if($days_ago < 27) {
				// handle weeks
				$weeks_ago = round($days_ago/7);
				if($weeks_ago == 1) return "about a week ago";
				return "about {$weeks_ago} weeks ago";
			} else if($days_ago < 360) {
				// handle months
				$months_ago = round($days_ago/30);
				if($months_ago == 1) return "about a month ago";
				return "about {$months_ago} months ago";
			} else {
				// handle years
				$years_ago = round($days_ago/366);
				if($years_ago == 1) return "about a year ago";
				return "about {$years_ago} years ago";
			}
		}
		public static function human_readable($filesize) {
			if(($filesize/1024) < 1024 )
				return round(($filesize/1024), 2) . 'kb'; // return filesize in KB
			else
				return round((($filesize/1024)/1024), 2) . 'mb'; // otherwise, filesize in MB
		}
		public static function size_by_popularity($tag, $full_usage = 50, $smallest = 6) {
			// popularity, uses, total
			return (string)(round((float)$tag->popularity) + 0.8);// smaller ems
			return (string)(round((float)$tag->popularity + 0.8));// smaller ems
			return (string)(round((float)$tag->popularity) + 1);// ems
			return (string)((((float)$tag->popularity * 2) + 1) * $smallest); // weighted
			return (string)(((float)$tag->popularity + 1) * $smallest); // simple trajectory
			return (string)((int)($full_usage - ($full_usage / (((float)$tag->popularity) / 1.8)))); // old
		}
		public static function inspect($object) {
			print "<pre>";
			print_r($object);
			print "</pre>";
		}
	}
?>