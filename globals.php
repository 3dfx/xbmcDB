<?php
	$TVDB_API_KEY       = '00A91C42DCF02C8A';
	$ANONYMIZER         = "http://anonym.to/?";

	$IMDB               = "http://www.imdb.de/find?s=";
	$IMDBFILMTITLE      = "http://www.imdb.de/title/";
	$PERSONINFOSEARCH   = $IMDB."nm&q=";
	$FILMINFOSEARCH     = $IMDB."tt&q=";

	$SHOW_NEW_VALUES    = array(10, 30, 60, 90, 120, 150, 180);
	$COLUMNCOUNT        = 9;
	
	$BLACKLIST_FILE     = './logs/blacklist.log';
	
	$LOCALHOST          = false;
	$HOMENETWORK        = false;
	$CLIENT_IP          = !isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR'];
	if ($CLIENT_IP != null) {
		$local0 = "127.0.0";
		$local1 = "192.168";
		$check = substr($CLIENT_IP, 0, 7);
		if ($check == $local0) {
			$LOCALHOST = true;
		}
		if ($check == $local0 || $check == $local1) {
			$USECACHE    = true;
			$HOMENETWORK = true;
		}
	}
	
	$COUNTRY_MAP =	array(
				"DE" => array(
						"GER" => "Deutsch",
						"GMH" => "Deutsch",
						"DEU" => "Deutsch",

						"ENG" => "Englisch",

						"TUR" => "T&uuml;rkce",

						"FRE" => "Franz&ouml;sisch",

						"ITA" => "Italienisch",
						"SPA" => "Spanisch",
						"POR" => "Portugiesisch",

						"JPN" => "Japanisch",
						"CHI" => "Chinesisch",
						"KOR" => "Koreanisch",

						"POL" => "Polnisch"
					),
				
				"EN" => array(
						"GER" => "German",
						"GMH" => "German",
						"DEU" => "German",

						"ENG" => "English",

						"TUR" => "Turkish",

						"FRE" => "French",

						"ITA" => "Italian",
						"SPA" => "Spanish",
						"POR" => "Portuguese",

						"JPN" => "Japanese",
						"CHI" => "Chinese",
						"KOR" => "Korean",

						"POL" => "Polish"
					)
			);
?>
