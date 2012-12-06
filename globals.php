<?php
	$TVDB_API_KEY       = '00A91C42DCF02C8A';
	$ANONYMIZER         = "http://anonym.to/?";

	$IMDB               = "http://www.imdb.de/find?s=";
	$IMDBFILMTITLE      = "http://www.imdb.de/title/";
	$PERSONINFOSEARCH   = $IMDB."nm&q=";
	$FILMINFOSEARCH     = $IMDB."tt&q=";

	$COLUMNCOUNT        = 8;
	
	$client_ip = null;
	if (!isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
		$client_ip = $_SERVER['REMOTE_ADDR'];
	} else {
	    $client_ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	}

	$LOCALHOST = false;
	$HOMENETWORK = false;
	if ($client_ip != null) {
		$local0 = "127.0.0";
		$local1 = "192.168";
		$check = substr($client_ip, 0, 7);
		if ($check == $local0) {
			$LOCALHOST = true;
		}

		if ($check == $local1) {
			$USECACHE = true;
			$HOMENETWORK = true;
		}
	}

	$SHOW_NEW_VALUES = array(10, 30, 60, 90, 120, 150, 180);
	$DEFAULT_NEW_ADDED = 30;
?>
