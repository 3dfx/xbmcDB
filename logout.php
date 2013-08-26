<?php
	include_once "template/functions.php";
	startSession();
	if (isset($_SESSION)) {
		storeSession();
		$dhb = getPDO();
		session_destroy();
	}
	redirectPage('', true);
?>