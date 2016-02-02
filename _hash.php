<?php
include_once "./template/config.php";
include_once "./template/functions.php";
include_once "globals.php";
	
	startSession();
	if (!isAdmin())
		exit;
	
	$pass = getEscGet('pass');
	echo sha1($pass);
?>
