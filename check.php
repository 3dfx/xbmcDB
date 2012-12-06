<?php
	include_once "template/functions.php";

	startSession();
	if ( !isAdmin() && !isGast() ) {
		echo '<div style="padding:20px;">invalid session...</div>';
		exit;
	}
?>
