<?php
include_once "globals.php";
include_once "./template/config.php";
include_once "./template/functions.php";

	$gast_users     = isset($GLOBALS['GAST_USERS'])     ? $GLOBALS['GAST_USERS']     : array();
	$login_username = isset($GLOBALS['LOGIN_USERNAME']) ? $GLOBALS['LOGIN_USERNAME'] : null;
	$login_passwort = isset($GLOBALS['LOGIN_PASSWORT']) ? $GLOBALS['LOGIN_PASSWORT'] : null;

	if (empty($login_username) || empty($login_passwort)) {
		die('<pre>admin is missing!</pre>');
	}

	startSession();
	setSessionParams(true);
	if (!isLogedIn()) {
		$path = dirname($_SERVER['PHP_SELF']);
		include_once './login.php';
		//exit;
	}
?>
