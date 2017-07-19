<?php
include_once "auth.php";
include_once "./template/functions.php";

	$idEpisode = getEscGet('idEp');
	if (empty($idEpisode))
		return;

	$TOKEN = authTvDB();
	if (empty($TOKEN))
		return;

	$LANG = isset($GLOBALS['TVDB_LANGUAGE']) ? $GLOBALS['TVDB_LANGUAGE'] : 'en';
	$URL = 'https://api.thetvdb.com/episodes/'.$idEpisode;
	$opts = array('http' => array(
		'method'  => 'GET',
		'header'  => 'Accept:application/json'."\r\n".'Accept-Language:'.$LANG."\r\n".'Authorization:Bearer '.$TOKEN
	));
	$context = stream_context_create($opts);
	$json    = file_get_contents($URL, false, $context);

	#$res = json_decode($json);
	#$rating = $res->{'data'}->{'siteRating'};

	#echo $rating;
	echo $json;
?>
