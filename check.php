<?php
	include_once "template/functions.php";

	startSession();
	if ( !isAdmin() && !isGast() ) {
		//die('invalid session...');
		echo '<div style="padding:20px;">invalid session...</div>';
		exit;
	}
?>
