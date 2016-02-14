<?php
include_once "./template/config.php";
include_once "./template/functions.php";
include_once "globals.php";
	
	startSession();
	if (!isAdmin()) { exit; }
	$TITLE = isset($GLOBALS['NAV_TITLE']) ? $GLOBALS['NAV_TITLE'] : 'xbmcDB';
?>

<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>XBMC Database</title>
	<link rel="icon" type="image/png" href="img/favicon15.png">
	<link rel="stylesheet" type="text/css" href="class.css" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<script type="text/javascript">
		$(document).ready(function() { $('#pass').focus(); });
		function hideFailed() { if ($('#hash').length) { $('#hash').hide(); } }
		function animateNav() { $('#navLogin').animate({ "top": "0%" }, 250, function() { return true; }); }
	</script>
</head>
<body style='overflow-x:hidden; overflow-y:auto;'>
<?php
	$pass = getEscPost('pass');
	if (isset($pass)) {
?>
		<div id='hash' class='navBarBrand' style='min-width:0%; margin:0 auto; font-size:16pt; width:500px; left:25%; top:45.5%; position:absolute; z-index:1;'><?php echo sha1($pass); ?></div>
<?php } ?>
	<div class='navbar<?php echo ($INVERSE ? ' navbar-inverse' : ''); ?>' id='navLogin' style='margin:0px -15px; position:absolute; top:45%; width:102%;'>
		<div class='navbar-inner' style='height:30px;'>
<?php if (empty($pass)) { ?>
			<div class='container' style='margin:0px auto; width:auto;'>
				<div class='nav-collapse' style='margin:0px;'><a class='brand navBarBrand' style='font-size:20px; top:4px; position: absolute;' href='#'><?php echo $TITLE; ?></a></div>
				<form action='_hash.php' method='post' class='navbar-search pull-right' style='height:25px; width:100%;' onsubmit='hideFailed(); return animateNav();'>
				<ul class='nav' style='color:#FFF; width:80%; float:right;'>
					<li style='margin:0px; width:100%;'>
						<input class='search-query span1' style='margin:1px 10px; width:500px; height:30px; float:left;' type='text' id='pass' name='pass' placeholder='password' />
						<input type='submit' value='Ok' class='btn' style='height:20px; padding-top:0px; margin:5px 0px; float:right;' onfocus='this.blur();'/>
					</li>
				</ul>
				</form>
			</div>
<?php } ?>
		</div>
	</div>
</body>
</html>
