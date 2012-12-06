<?php
	include_once "template/functions.php";
	
	startSession();
	if (isset($_SESSION)) {
		unset($_SESSION['gast']);
		unset($_SESSION['angemeldet']);
		session_destroy();
	}

	redirectPage('', true);
?>
