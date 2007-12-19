<?php
	// @title		Routes
	// @author	Matt Todd <matt@matttoddphoto.com>
	// @created	2005-12-23
	// @desc		The routing patterns to match the request against to set the appropriate request variables
	
	// setup date routes for searching times
	Router2::map(':year/:month/:day',
		array('controller'=>'archive', 'action'=>'timespan'),
		array('year'=>'year', 'month'=>'month', 'day'=>'day'));
	Router2::map(':year/:month',
		array('controller'=>'archive', 'action'=>'timespan'),
		array('year'=>'year', 'month'=>'month'));
	Router2::map(':year',
		array('controller'=>'archive', 'action'=>'timespan'),
		array('year'=>'year'));
	
	// map multiple tags to the blog/tags controller/action
	Router2::map('tags/:tags*',	array('controller'=>'blog', 'action'=>'tags'));
	
	// flexi-generic route (for show/:id or maybe comment/:id, etc)
	Router2::map(':action/:id', array('controller'=>'blog'));
	
	// flexi-generic route (for :controller/:action/:id)
	Router2::map(':controller/:action/:id');
	
	// flexi-generic route (for show/:id or maybe comment/:id, etc)
	Router2::map(':action/comment/:id/from/post/:post_id', array('controller'=>'comments'));
	
	// default to the blog/index controller/action
	Router2::map('', array('controller'=>'blog', 'action'=>'index'));
	
?>