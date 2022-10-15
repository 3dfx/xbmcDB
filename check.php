<?php
include_once "./template/functions.php";

	startSession();
	if (!isLoggedIn()) {
		exit;
	}
?>
