<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<title>Omnia &mdash; The New Nothing &mdash; My Home</title>
		
		<meta http-equiv="content-type" content="text/html;charset=iso-8859-1" />
		<meta name="author" content="Matt Todd, Mike White, Brian Hursey, Christopher Pigg" />
		<meta name="keywords" content="nothing, utility, distribution, system, tools, the HUB" />
		<meta name="description" content="A Utility Distribution System (UDS) to replace the old Nothing (hence the 'The New Nothing' line)." />

		<meta name="robots" content="none" />
		
		<link rel="stylesheet" type="text/css" media="all" href="<%resource request=$request resource='res/hub.css'%>" />
		<link rel="stylesheet" type="text/css" media="print" href="<%resource request=$request resource='res/hub.print.css'%>" />
		<!-- <%$request->host%>-->
		<script type="text/javascript" src="<%resource request=$request resource='res/js/prototype.js'%>"></script>
		<script type="text/javascript" src="<%resource request=$request resource='res/js/scriptaculous.js'%>"></script>
		<script type="text/javascript" src="<%resource request=$request resource='res/js/moo.fx.js'%>"></script>
		<script type="text/javascript" src="<%resource request=$request resource='res/js/moo.fx.pack.js'%>"></script>
		<script type="text/javascript" src="<%resource request=$request resource='res/js/omnia.js'%>>"></script>
		<script type="text/javascript">
			function toggle(id) {
				e = document.getElementById(id);
				if(e.className == 'hidden') e.className = ''; else e.className = 'hidden';
			}
		</script>
	</head>
	<body id="Omnia">
		<div id="body">
			<div id="topbar">
				<a href="http://thehub.clayton.edu/">Back to the HUB</a> &middot; <%link_to controller='files' action='logout' title='Logout'%>
			</div><!-- topbar -->
			<div id="header">
				<h1>Omnia</h1>
				<h2>The New Nothing</h2> &nbsp;Beta
			</div><!-- header -->
			<div id="menu">
				<ul id="menu-links">
					<li><%link_to controller='files' action='index' title='Home' rel='index' extra='class="menu"'%></li>
					<li><%link_to controller='files' action='favorites' title='Favorites' extra='class="menu"'%></li>
					<li><%link_to controller='files' action='popular' title='Popular' extra='class="menu"'%></li>
					<li><%link_to controller='files' action='recent' title='Recent' extra='class="menu"'%><li>
					<li><%link_to controller='users' action='profile' id=$session_user->id title='My Stats' extra='class="menu"'%></li>
				</ul>
			</div><!-- menu -->
			<div id="container">
				<div id="content"><div class="pad">
					<%if !empty($flash)%>
						<div id="Flash" class="<%$flash.class|default:"none"%>" onclick="new Effect.Fade(this, {duration: 1});"><div>
							<%$flash.message%>
						</div></div>
					<%/if%>
					<%include file="../_upload.php"%>
					
					<%include file=$template%>
					
				</div></div>
			</div><!-- container -->
			<div id="sidebar">
				<%include file="../_sidebar.php"%>
			</div>
			<div class="clearer">&nbsp;</div>
			<div id="footer">
				&copy; 2005 The HUB.  All Rights Reserved.  Valid <a href="http://validator.w3.org/check?uri=http%3A%2F%2Fthehub.clayton.edu%2Fomnia%2F">XHTML 1.0 Strict</a> and <a href="http://jigsaw.w3.org/css-validator/validator?uri=http%3A%2F%2Fthehub.clayton.edu%2Fomnia%2F">CSS</a>.
			</div><!-- footer -->
		</div><!-- body -->
	</body>
</html>