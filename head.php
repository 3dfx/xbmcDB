<?php
	include_once "./template/config.php";
	
	$XBMCONTROL = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$TITLE      = isset($GLOBALS['HTML_TITLE'])          ? $GLOBALS['HTML_TITLE']          : 'XBMC Database';
	$show       = isset($_SESSION['show']) ? $_SESSION['show'] : 'filme';
	$isMain     = $show == 'filme'  ? true : false;
	$isTvshow   = $show == 'serien' ? true : false;
	#$isMVids    = $show == 'mvids'  ? true : false;
	#$isMPExp    = $show == 'mpExp'  ? true : false;
?>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0" />
	<title><?php echo $TITLE; ?></title>
<?php /*
	<link rel="shortcut icon" href="favicon.ico" />
*/ ?>
	<link rel="icon" type="image/png" href="img/favicon15.png">
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/docs.css" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" />
	<link rel="stylesheet" type="text/css" href="./class.css" />
<?php if ($isMain) { ?>
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/select/select2.css" />
<?php } ?>
<?php if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/jquery.js"></script>
<?php } else { ?>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
<?php } ?>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
<?php if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
<?php } else { ?>
	<script type="text/javascript" src="./template/js/myfancy.min.js"></script>
<?php } ?>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<script type="text/javascript" src="./template/js/jquery.marquee.min.js"></script>
<?php if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/general.js"></script>
<?php } else { ?>
	<script type="text/javascript" src="./template/js/general.min.js"></script>
<?php } ?>
<?php if ($isMain) { ?>
	<script type="text/javascript" src="./template/js/bootstrap/select/select2.min.js"></script>
	<script type="text/javascript" src="./template/js/highlight.js"></script>
<?php 		if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/filme.js"></script>
<?php 		} else { ?>
	<script type="text/javascript" src="./template/js/filme.min.js"></script>
<?php 		} ?>
<?php } else if($isTvshow) { ?>
<?php 		if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/chart.js"></script>
	<script type="text/javascript" src="./template/js/serien.js"></script>
	<script type="text/javascript" src="./template/js/jquery.knob.js"></script>
<?php 		} else { ?>
	<script type="text/javascript" src="./template/js/chart.min.js"></script>
	<script type="text/javascript" src="./template/js/serien.min.js"></script>
	<script type="text/javascript" src="./template/js/jquery.knob.min.js"></script>
<?php 		} ?>
<?php } ?>
<?php if(isAdmin() && $XBMCONTROL) { ?>
	<script type="text/javascript" src="./template/js/xbmcJson.js"></script>
<?php } ?>