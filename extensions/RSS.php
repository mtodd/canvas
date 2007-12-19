<?php
	// RSS feed generator
	// @author     Matt Todd
	// @email      <mtodd@clayton.edu>
	// @created_on 29 Apr 2006
	
	class RSS {
		// internal static variables
		public static $version_templates = array(
			'rss20'=>array(), // RSS2.0 templates
		);
		
		// internal instance variables
		private $templates = array();
		private $feed = '';
		private $items = array();
		
		// constructor
		public function __construct($version = 'rss20', $options = array()) {
			// get templates
			$this->templates = self::$version_templates[$version];
			
			// process data into templates
			$feed = $this->templates['body'];
			foreach($options as $key=>$option) {
				$feed = str_replace(":{$key}", $option, $feed);
			}
			
			// update the feed data
			$this->feed = $feed;
		}
		
		// route request
		public function add($item) {
			// get template
			$item_template = $this->templates['item'];
			
			// parse through values
			foreach($item as $key=>$value) {
				$item_template = str_replace(":{$key}", $value, $item_template);
			}
			
			// store new item
			$this->items[] = $item_template;
		}
		
		public function to_string() {
			return $this->render();
		}
		public function render() {
			return str_replace(':items', implode("\n\n", $this->items), $this->feed);
		}
	}
	
RSS::$version_templates['rss20']['body'] = <<<EOF
<?xml version="1.0" encoding="iso-8859-1"?>
<rss version="2.00">
	<channel>
		<title>:title</title>
		<link>:link</link>
		<description>
			:description
		</description>
		<language>:language</language>
		
		:items
		
	</channel>
</rss>
EOF;
RSS::$version_templates['rss20']['item'] = <<<EOF
<item>
	<title>:title</title>
	<link>:link</link>
	<guid>:guid</guid>
	<author>:author</author>
	<pubDate>:pubDate</pubDate>
	<description>
		<![CDATA[

		:description

		]]>
	</description>
	<comments>:comments</comments>
</item>
EOF;
?>