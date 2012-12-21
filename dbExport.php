<?php
date_default_timezone_set('Europe/Berlin');

/** Includes */
include_once 'auth.php';
include_once 'template/functions.php';
require_once 'template/export/myExcelFunks.php';
require_once 'template/export/PHPExcel.php';
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
<head>
	<title>xbmcDB - Datenbank Export</title>
	<link rel="shortcut icon" href="favicon.ico" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./class.css" />

	<script type="text/javascript">
		function openNav(objId) {
			closeNavs();
			$(objId).addClass('open');
		}

		function closeNavs() {
			$('#dropOptions').removeClass('open');
			$('#dropViewmode').removeClass('open');
			$('#dropLanguage').removeClass('open');
			$('#dropAdmin').removeClass('open');
		}
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	if (isLogedIn()) { postNavBar(); }
?>
	<div style="padding-top:45px; max-width:950px; width:950px;">
<?php
	$tables = getTableNames();
	/*
	$tables = array(
		'actorlinkepisode', 'actorlinkmovie', 'actorlinktvshow', 'actors', 'artistlinkmusicvideo', 'bookmark', 
		'country', 'countrylinkmovie', 'directorlinkepisode', 'directorlinkmovie', 'directorlinkmusicvideo', 
		'directorlinktvshow', 'episode', 'episodeview', 'episodeviewMy', 'fileinfo', 'filemap', 'files', 'genre', 
		'genrelinkmovie', 'genrelinkmusicvideo', 'genrelinktvshow', 'movie', 'movielinktvshow', 'movieview', 
		'musicvideo', 'musicvideoview', 'path', 'setlinkmovie', 'sets', 'settings', 'stacktimes', 'streamdetails', 
		'studio', 'studiolinkmovie', 'studiolinkmusicvideo', 'studiolinktvshow', 'tvshow', 'tvshow_lang', 
		'tvshowlinkepisode', 'tvshowlinkpath', 'tvshowview', 'version', 'writerlinkepisode', 'writerlinkmovie'
	);
	*/
	
	if (isset($_SESSION['export']) && $_SESSION['export'] == 'exportieren') {
		exportTablesDo($tables);
	} else {
		resetSelectsInSession($tables);
		printSelecta($tables);
	}

function resetSelectsInSession($tables) {
	for ($t = 0; $t < count($tables); $t++) {
		unset( $_SESSION['xP_'.$tables[$t]] );
	}
}

function printSelecta($tables) {
	echo "\t".'<form action="index.php" name="tableselect" method="post">';
	$breaked = false;
	echo '<table>';
	for ($t = 0; $t < count($tables); $t++) {
		echo '<tr>';
		echo '<td class="lefto">';
		echo "\t".'<label for="id'.$tables[$t].'" class="labelHack2">'.$tables[$t].'</label>';
		#echo '<input type="checkbox" class="checkHack2" id="id'.$tables[$t].'" name="xP_'.$tables[$t].'" checked="checked />';
		echo '<input type="checkbox" class="checkHack2" id="id'.$tables[$t].'" name="xP_'.$tables[$t].'" />';
		echo '</td>';
		echo '</tr>';
		echo "\n";
	}
	echo '</tr>';
	echo '<tr>';
	echo '<td>';
		echo '<input type="submit" name="export" value="exportieren" class="okButton" style="width:130px !important;" onfocus="this.blur();" onclick="this.blur();" />';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
	echo "\n\t</form>";
}
?>
	</div>
</body>
</html>