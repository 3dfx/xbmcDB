<?php
	include_once "./template/functions.php";
	startSession();
	if (isset($_SESSION)) {
		storeSession();
		session_destroy();
		DB_CONN::destruct();
	}
	
	startSession();
	$_SESSION['refferLoged'] = true;
	redirectPage('', true);
?>