<?php
	include_once "template/config.php";
	include_once "template/functions.php";
	include_once "globals.php";
	
	$gast_username = $GLOBALS['gast_username'];
	$gast_passwort = $GLOBALS['gast_passwort'];
	$login_username = $GLOBALS['login_username'];
	$login_passwort = $GLOBALS['login_passwort'];

	if (empty($login_username) || empty($login_passwort) ||
	    empty($gast_username)  || empty($gast_passwort)) {

	    die('<pre>admin/user is missing!</pre>');
	}
	
	startSession();
	setSessionParams(true);
	if (!isLogedIn()) {
		$path = dirname($_SERVER['PHP_SELF']);
		include './login.php';
		exit;
	}
?>
