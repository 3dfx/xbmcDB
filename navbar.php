<?php
include_once "auth.php";
include_once "./template/config.php";
include_once "./template/functions.php";

	$maself = getEscGet('maself');
	echo postNavBar_($maself == 1);
?>