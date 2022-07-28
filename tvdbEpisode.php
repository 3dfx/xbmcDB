<?php
include_once "auth.php";
include_once "./template/functions.php";

	$idEpisode = getEscGet('idEp');
	if (empty($idEpisode)) {
		echo null;
		return;
	}

	$TOKEN = authTvDB();
	if (empty($TOKEN)) {
		echo null;
		return;
	}

	$LANG = isset($GLOBALS['TVDB_LANGUAGE']) ? $GLOBALS['TVDB_LANGUAGE'] : 'en';
	$URL = 'https://api.thetvdb.com/episodes/'.$idEpisode;
	$opts = array('http' => array(
		'method'  => 'GET',
		'timeout' => getTimeOut(),
		'header'  => 'Accept:application/json'."\r\n".'Accept-Language:'.$LANG."\r\n".'Authorization:Bearer '.$TOKEN
	));
	$context = stream_context_create($opts);
	
	$json    = null;
	$oldHandler = set_error_handler('handleError');
	try {
		$json = file_get_contents($URL, false, $context);
	} catch (Exception $e) {
		if (!empty($oldHandler)) { set_error_handler($oldHandler); }
		echo null;
		return;
	}

	if (!empty($oldHandler)) { set_error_handler($oldHandler); }
	echo $json;
?>
