<?php
date_default_timezone_set('Europe/Berlin');

/** Includes */
include_once 'auth.php';
include_once 'template/functions.php';
require_once 'template/export/myExcelFunks.php';
#require_once 'template/export/PHPExcel.php';
require_once 'template/export/PHPExcel/IOFactory.php';
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
<head>
	<title>xbmcDB - Datenbank Import</title>
	<link rel="shortcut icon" href="favicon.ico" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.js"></script>
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

	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	if (isLogedIn()) { postNavBar(); }
?>
	<div style="padding-top:45px; max-width:950px; width:950px;">
	<form enctype="multipart/form-data" action="index.php" name="fileUpload" method="post">
		<input type="hidden" name="max_file_size" value="10485760">
		Excel: <input name="thefile" type="file">
		<input type="submit" name="xlsUpload" value="senden">
	</form>
<?php
	if (!empty($_SESSION['xlsError'])) {
		echo $_SESSION['xlsError'];
		return;

	} else if (isset($_SESSION['xlsUpload']) && !empty($_SESSION['xlsFile'])) {
		$xlFile = $_SESSION['xlsFile'];
		if (empty($xlFile) || !is_file($xlFile)) {
			return;
		}

		echo 'Nach dem Import sollte die Foliensortierung ausgeführt werden!<br/>';
		echo '<a href="?show=sort" style="font-weight:bold;">Sortiere Folien</a><p/><hr/>';
		importTablesDo($xlFile);

	} else {
		echo 'Vor dem Import sollte sicherheitshalber Die Datenbank (nicht Excel) gesichert werden.<br/>';
		echo '<a style="font-weight:bold;" href="./template/folien.db">Download folien.db</a>';
		return;
	}
?>
	</div>
</body>
</html>