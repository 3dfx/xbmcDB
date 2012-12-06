<?php
	include_once "template/config.php";
	include_once "template/functions.php";
	include_once "globals.php";
	
	startSession();
	setSessionParams(true);
	//if (!isAdmin() && !isGast()) {
	if (!isLogedIn()) {
		$path = dirname($_SERVER['PHP_SELF']);
		include './login.php';
		exit;
	}
?>
