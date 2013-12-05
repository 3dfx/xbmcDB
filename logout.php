<?php
	include_once "./template/functions.php";
	startSession();
	if (isset($_SESSION)) {
		storeSession();
		$dhb = getPDO();
		session_destroy();
	}
	
	startSession();
	$_SESSION['refferLoged'] = true;
	redirectPage('', true);
?>