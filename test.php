<?php
	header('Location:http://'.gethostbyname('btfh.dyndns.org'));
	
	exit;
	
	header('Content-Type: text/html');
	
	include_once "auth.php";
	include_once "template/config.php";
	include_once "template/functions.php";
	startSession();
?>	

<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
<head>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/highlight.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/docs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./class.css" />
	<script type="text/javascript">

/*
	$(".fancy_iframe").fancybox({
		'width'				: 1280,
		'height'			: 720,
		'transitionIn'			: 'elastic',
		'transitionOut'			: 'elastic',
		'overlayColor'			: '#000',
		'overlayOpacity'		: 0.9,
		'speedIn'			: 500,
		'speedOut'			: 250,
		'padding'			: 5,
		'autoScale'			: true,
		'centerOnScroll'		: true,
		'enableEscapeButton'		: true,
		'scrolling'			: 'no',
		'type'				: 'iframe'
	});
*/
</script>
</head>

</body>
</html>