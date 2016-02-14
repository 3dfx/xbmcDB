<?php
	$TVDB_API_KEY       = '00A91C42DCF02C8A';
	$ANONYMIZER         = 'http://dontknow.me/at/?';
	
	$IMDB               = 'http://www.imdb.de/find?s=';
	$IMDBFILMTITLE      = 'http://www.imdb.de/title/';
	$PERSONINFOSEARCH   = $IMDB.'nm&q=';
	$FILMINFOSEARCH     = $IMDB.'tt&q=';
	
	$SHOW_NEW_VALUES    = array(10, 30, 60, 90, 120, 150, 180);
	$COLUMNCOUNT        = 10;
	
	$BLACKLIST_FILE     = './logs/blacklist.log';
	
	$DAY_IN_SECONDS     = 86400;
	
	$LOCALHOST          = false;
	$HOMENETWORK        = false;
	$CLIENT_IP          = !isset($_SERVER['HTTP_X_FORWARDED_FOR']) ? $_SERVER['REMOTE_ADDR'] : $_SERVER['HTTP_X_FORWARDED_FOR'];
	if ($CLIENT_IP != null) {
		$local0 = '127.0.0';
		$local1 = '192.168';
		$check = substr($CLIENT_IP, 0, 7);
		$LOCALHOST   = ($check == $local0);
		$HOMENETWORK = ($check == $local0 || $check == $local1);
	}
	
	$COUNTRY_MAP =	array(
		'DE' => array(
				'GER' => 'Deutsch',
				'GMH' => 'Deutsch',
				'DEU' => 'Deutsch',

				'ENG' => 'Englisch',

				'TUR' => 'T&uuml;rkce',

				'FRE' => 'Franz&ouml;sisch',

				'ITA' => 'Italienisch',
				'SPA' => 'Spanisch',
				'POR' => 'Portugiesisch',

				'JPN' => 'Japanisch',
				'CHI' => 'Chinesisch',
				'KOR' => 'Koreanisch',

				'POL' => 'Polnisch'
			),

		'EN' => array(
				'GER' => 'German',
				'GMH' => 'German',
				'DEU' => 'German',

				'ENG' => 'English',

				'TUR' => 'Turkish',

				'FRE' => 'French',

				'ITA' => 'Italian',
				'SPA' => 'Spanish',
				'POR' => 'Portuguese',

				'JPN' => 'Japanese',
				'CHI' => 'Chinese',
				'KOR' => 'Korean',

				'POL' => 'Polish'
			)
	);
	
	$CODEC_COLORS = array(
		0 => '#000000',
		1 => '#00FF00',
		2 => '#009900',
		3 => '#FF0000',
		4 => '#550000',
	);
	
	$DB_MAPPINGS = array(
		93 => array(
			'actorlinkmovie'    => 'actor_link',
			'actorlinkepisode'  => 'actor_link',
			'actorlinktvshow'   => 'actor_link',
			'directorlinkmovie' => 'director_link',
			'genrelinkmovie'    => 'genre_link',
			'countrylinkmovie'  => 'country_link',

			'actors'            => 'actor',
			'iOrder'            => 'cast_order',
			'idActor'           => 'actor_id',
			'idCountry'         => 'country_id',
			'idDirector'        => 'actor_id',
			'idGenre'           => 'genre_id',
			'idMovie'           => 'media_id',
			'strActor'          => 'name',
			'strCountry'        => 'name',
			'strGenre'          => 'name',
			'strRole'           => 'role',
			'strThumb'          => 'art_urls',

			'episodeview'       => 'episode_view',
		),
		99 => array(
			'seasonview'        => 'season_view',
			'tvshowview'        => 'tvshow_view',
		)
	);

function mergeMapping($dbVer) {
	if (isset($_SESSION['DB_MAPPING'])) { return $_SESSION['DB_MAPPING']; }
	
	$merged   = array();
	$mappings = $GLOBALS['DB_MAPPINGS'];
	foreach($mappings as $key => $value) {
		if ($key > $dbVer) { continue; }
		$merged = array_merge($merged, $value);
	}
	
	$_SESSION['DB_MAPPING'] = $merged;
	return $merged;
}

function mapDBC($str) {
	$dbVer = $GLOBALS['db_ver'];
	if ($dbVer >= 93) {
		$map = mergeMapping($dbVer);
		return isset($map[$str]) ? $map[$str] : $str;
	}
	
	return $str;
}
	
?>
