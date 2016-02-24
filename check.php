<?php
include_once "./template/functions.php";

	startSession();
	if (!isLogedIn()) {
		#echo '<div style="padding:20px;">invalid session...</div>';
		exit;
	}
?>
