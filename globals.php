<?php
	$DB_PATH = '/public';

	$SEARCH_ENABLED                = true;
	$CUTS_ENABLED                  = true;  // extended, directors, uncut / unrated
	$DREID_ENABLED                 = true;  // 3d
	$ATMOS_ENABLED                 = true;
	$CHOOSELANGUAGES               = true;
	$IGNORE_COL_CHECKS             = array();

	$TVDB_API_KEY       = '00A91C42DCF02C8A';
	$ANONYMIZER         = 'https://anon.to/?';

	$IMDB               = 'http://www.imdb.com/find?s=';
	$IMDBFILMTITLE      = 'http://www.imdb.com/title/';
	$PERSONINFOSEARCH   = $IMDB.'nm&q=';
	$FILMINFOSEARCH     = $IMDB.'tt&q=';

	$PRONOMS            = array('the ', 'der ', 'die ', 'das ');
	$SHOW_NEW_VALUES    = array(10, 30, 60, 90, 120, 150, 180);
	$COLUMNCOUNT        = 10;
	$TVSHOW_MENU_LIMIT  = 30;

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

	const COUNTRY_MAP =	array(
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

	const SOURCE = array(
		null => 'unknown',
		0    => 'unknown',
		1    => 'TVrip',
		2    => 'DVDrip',
		3    => 'WEB-DL',
		4    => 'WEBrip',
		5    => 'BluRay',
		6    => 'BluRay (upscaled)',
		7    => 'UHD BluRay',
	);

	const CODEC_COLORS = array(
		0 => '#000000',
		1 => '#00FF00', //MPEG
		2 => '#009900', //DivX
		3 => '#FF7F27', //x264
		4 => '#880000', //x265
		5 => '#DD0000', //x265 10bit
	);

	const TONEMAPMETHOD = array(
		0 => 'OFF',
		1 => 'Reinhard',
		2 => 'Aces',
		3 => 'Hable',
		4 => 'Max'
	);

	const DETAIL_COLS = array(
		'DUR'    =>  0,
		'RATE'   =>  1,
		'YEAR'   =>  2,
		'GENRE'  =>  3,
		'VIDEO1' =>  4,
		'VIDEO2' =>  5,
		'AUDIO1' =>  6,
		'AUDIO2' =>  7,
		'AUDIO3' =>  8,
		'SUB'    =>  9
	);

	const DB_MAPPINGS = array(
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
			'joinIdMovie'       => '',
			'joinRatingMovie'   => '',
		),
		107 => array(
			'joinIdMovie'       => 'LEFT JOIN uniqueid I ON (I.media_type="movie" AND I.type="imdb" AND I.media_id=A.idMovie) ',
			'joinRatingMovie'   => 'LEFT JOIN rating   R ON (R.media_type="movie" AND R.media_id=A.idMovie) ',

			'A.c04'             => 'R.votes',
			'A.c05'             => 'R.rating',
			'A.c07'             => 'substr(A.premiered,1,4)',
			'A.c07_'            => 'A.premiered',
			'A.c09'             => 'I.value',

			'T.c12'             => 'T.uniqueid_value',
			'V.rating'          => 'R.rating',
			'V.c03'             => 'V.c03 AS rating_, (SELECT R.rating FROM rating R WHERE R.media_id = V.idEpisode AND R.media_type="episode")',
		)
	);

function mergeMapping($dbVer) {
	if (isset($_SESSION['DB_MAPPING'])) { return unserialize($_SESSION['DB_MAPPING']); }

	$merged   = array();
	$mappings = DB_MAPPINGS;
	sort($mappings);
	foreach($mappings as $key => $value) {
		if ($key > $dbVer) { continue; }
		$merged = array_merge($merged, $value);
	}

	$_SESSION['DB_MAPPING'] = serialize($merged);
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

$db_ver  = fetchDbVer();
$db_name = fetchDbName();

function fetchDbVer() {
	if (!isset($_SESSION)) { session_start(); }
	if (!isset($_SESSION['dbVer']) || empty($_SESSION['dbVer'])) { fetchDbName(); }
	return $_SESSION['dbVer'];
}

function fetchDbName() {
	if (!isset($_SESSION)) { session_start(); }
	if (isset($_SESSION['dbName']) && !empty($_SESSION['dbName'])) { return $_SESSION['dbName']; }

	$dir = isset($GLOBALS['DB_PATH']) ? $GLOBALS['DB_PATH'] : '/public';

	$ver = array();
	$d = dir($dir);
	$counter = 0;
	while (false !== ($entry = $d->read())) {
		$ver_ = getVer_($entry);
		if (empty($ver_)) { continue; }

		$ver[$counter][0] = intval($ver_);
		#$ver[$counter][0] = intval(substr($entry, 8, 2));
		$ver[$counter++][1] = $entry;
	}
	$d->close();

	rsort($ver);
	$_SESSION['dbName'] = 'sqlite:'.$dir.'/'.$ver[0][1];
	$_SESSION['dbVer']  = $ver[0][0];
	return $_SESSION['dbName'];
}

function getVer_($entry) {
	if ($entry == '.' || $entry == '..')    { return null; }
	if (substr($entry, 0, 8) != 'MyVideos'
	 || substr($entry, -3)   != '.db') { return null; }

	$entry = str_replace('MyVideos', '', $entry);
	$entry = str_replace('.db',      '', $entry);
	return intval($entry);
}

?>
