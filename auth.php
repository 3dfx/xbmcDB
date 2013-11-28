<?php
	include_once "./template/config.php";
	include_once "./template/functions.php";
	include_once "globals.php";
	
//	$gast_username = $GLOBALS['gast_username'];
//	$gast_passwort = $GLOBALS['gast_passwort'];
	$gast_users     = $GLOBALS['GAST_USERS'];
	$login_username = $GLOBALS['LOGIN_USERNAME'];
	$login_passwort = $GLOBALS['LOGIN_PASSWORT'];

	if (empty($login_username) || empty($login_passwort) ||
	    empty($gast_users)  || count($gast_users) == 0) {
//	    empty($gast_username)  || empty($gast_passwort)) {

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
