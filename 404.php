<?php
	include_once "template/functions.php";
	if (!isset($_SESSION)) { session_start(); }
?>
<html>
	<head>
		<title>404 error!</title>
		<link rel="shortcut icon" href="favicon.ico" />
		<link rel="stylesheet" type="text/css" href="./class.css" />
	</head>
	<body>
		<div class="tabDiv">
			The page you requested doesn't exist!<br />
<?php
			echo "\t\t\t";
			if (isLoggedIn()) {
				echo 'But you can go <a href="./index.php" style="font-weight:bold;">BACK</a>.';
			} else {
				echo 'But you can login <a href="./login.php" style="font-weight:bold;">HERE</a>.';
			}
			echo "\r\n";
?>
		</div>
	</body>
</html>
