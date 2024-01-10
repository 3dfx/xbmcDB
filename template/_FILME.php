<?php
include_once "./template/functions.php";

/** @noinspection PhpIssetCanBeReplacedWithCoalesceInspection */
function getSessionKeySQL($newAddedCount = 0) {
	$res = array();

	$_just       = $GLOBALS['just'];
	$_which      = $GLOBALS['which'];
	$mode        = isset($_SESSION['mode'])        ? $_SESSION['mode']        : 0;
	$unseen      = isset($_SESSION['unseen'])      ? $_SESSION['unseen']      : 0;
	$newmode     = isset($_SESSION['newmode'])     ? $_SESSION['newmode']     : 0;
	$newsort     = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;
	$sort        = isset($_SESSION['sort'])        ? $_SESSION['sort']        : null;
	$dbSearch    = isset($_SESSION['dbSearch'])    ? $_SESSION['dbSearch']    : null;
	$country     = isset($_SESSION['country'])     ? $_SESSION['country']     : null;

	$sqlOrder = "";
	$sqlLimit = "";

	if (!isAdmin() && $sort == 'pCount') {
		$sort = null;
	}

	if (!isset($unseen) || (!empty($_just) && !empty($_which) || !empty($dbSearch)) ) {
		$unseen = 3;
	}

	$saferSearch = '';
	if (!empty($dbSearch)) {
		$_SESSION['newmode'] = $newmode = 0;
		$_SESSION['mode']    = $mode    = 0;
		$saferSearch = strtolower(SQLite3::escapeString($dbSearch));
	}

	$filter      = '';
	$sessionKey  = 'unseen_'.$unseen.'_';
	if (!empty($saferSearch)) {
		$sessionKey .= 'search_'.str_replace(' ', '-', $saferSearch).'_';
		$filter      =  " AND (".
			" lower(B.strFilename) LIKE lower('%".$saferSearch."%') OR".
			" lower(A.c00) LIKE lower('%".$saferSearch."%') OR".
			" lower(A.c01) LIKE lower('%".$saferSearch."%') OR".
			" lower(A.c03) LIKE lower('%".$saferSearch."%') OR".
			" lower( ".mapDBC('A.c09').") LIKE lower('%".$saferSearch."%') OR".
			" lower(A.c14) LIKE lower('%".$saferSearch."%') OR".
			" lower(A.c15) LIKE lower('%".$saferSearch."%') OR".
			" lower(A.c16) LIKE lower('%".$saferSearch."%')".
			")";

	} else if ($_which == 'artist') {
		$filter = " AND E.".mapDBC('idActor')." = '".$_just."' AND E.".mapDBC('idMovie')." = A.idMovie AND E.media_type = 'movie'";
		$sessionKey .= 'just_'.$filter.'_';

	} else if ($_which == 'regie') {
		$filter = " AND D.".mapDBC('idDirector')." = '".$_just."' AND D.".mapDBC('idMovie')." = A.idMovie AND D.media_type = 'movie'";
		$sessionKey .= 'just_'.$filter.'_';

	} else if ($_which == 'genre') {
		$filter = " AND G.".mapDBC('idGenre')." = '".$_just."' AND G.".mapDBC('idMovie')." = A.idMovie AND G.media_type = 'movie'";
		$sessionKey .= 'just_'.$filter.'_';

	} else if ($_which == 'year') {
		$filter = " AND ".mapDBC('A.c07')." = '".$_just."'";
		$sessionKey .= 'just_'.$filter.'_';

	} else if ($_which == 'set') {
		$_SESSION['newmode'] = $newmode = 0;
		$filter = " AND A.idSet = '".$_just."'";
		$sessionKey .= 'just_'.$filter.'_';

	} else {
		$filter = null;
		$_which = null;
	}

	$unseenCriteria = '';
	if ($unseen == "0") {
		$unseenCriteria = " AND playCount > ".($sort == 'pCount' ? 1 : 0)." ";

	} else if ($unseen == "1") {
		$unseenCriteria = " AND (playCount = 0 OR playCount IS NULL)";

	} else if ($unseen == "3") {
		$unseenCriteria = '';
	}

	$SQL =  "SELECT DISTINCT ".
		"A.idFile, A.idMovie, ".mapDBC('A.c05')." AS rating, B.playCount AS playCount, B.lastPlayed AS lastPlayed, A.c00 AS movieName, A.c14 AS genres, B.strFilename AS filename, ".
		"M.dateAdded AS dateAdded, M.value AS dateValue, ".mapDBC('A.c07')." AS jahr, A.c19 AS trailer, ".
		mapDBC('A.c09')." AS imdbId, C.strPath AS path, F.filesize, F.fps, F.bit ".
		"FROM movie A, files B, path C ".
		"LEFT JOIN filemap  M ON B.strFilename = M.strFilename ".
		"LEFT JOIN fileinfo F ON B.idFile = F.idFile ".
		mapDBC('joinIdMovie').
		mapDBC('joinRatingMovie').
		(isset($mode) && ($mode == 2) ? "LEFT JOIN streamdetails SD ON (B.idFile = SD.idFile AND SD.strAudioLanguage IS NOT NULL) " : '').
		(isset($filter, $_which) && ($_which == 'artist') ? ', '.mapDBC('actorlinkmovie').' E'    : '').
		(isset($filter, $_which) && ($_which == 'regie')  ? ', '.mapDBC('directorlinkmovie').' D' : '').
		(isset($filter, $_which) && ($_which == 'genre')  ? ', '.mapDBC('genrelinkmovie').' G'    : '').
		" WHERE A.idFile = B.idFile AND C.idPath = B.idPath "
	;

	if (!empty($sort)) {
			 if ($sort == 'jahr')    { $sessionKey .= 'orderJahr_';    $sqlOrder = " ORDER BY jahr DESC, dateAdded DESC";      }
		else if ($sort == 'jahra')   { $sessionKey .= 'orderJahrA_';   $sqlOrder = " ORDER BY jahr  ASC, dateAdded  ASC";      }
		else if ($sort == 'size')    { $sessionKey .= 'orderSize_';    $sqlOrder = " ORDER BY F.filesize DESC";                }
		else if ($sort == 'sizea')   { $sessionKey .= 'orderSizeA_';   $sqlOrder = " ORDER BY F.filesize  ASC";                }
		else if ($sort == 'title')   { $sessionKey .= 'orderTitle_';   $sqlOrder = " ORDER BY movieName DESC";                 }
		else if ($sort == 'titlea')  { $sessionKey .= 'orderTitleA_';  $sqlOrder = " ORDER BY movieName  ASC";                 }
		else if ($sort == 'rating')  { $sessionKey .= 'orderRating_';  $sqlOrder = " ORDER BY rating DESC, movieName ASC";     }
		else if ($sort == 'ratinga') { $sessionKey .= 'orderRatingA_'; $sqlOrder = " ORDER BY rating  ASC, movieName ASC";     }
		else if ($sort == 'pCount')  { $sessionKey .= 'orderPCount_';  $sqlOrder = " ORDER BY playCount DESC, movieName ASC";  }

	} else if ($newmode) {
		$sqlOrder = " ORDER BY ".($newsort == 1 ? "dateValue" : "A.idMovie")." DESC";
		$sqlLimit = " LIMIT ".$newAddedCount;
		$sessionKey .= ($newsort == 1 ? 'orderDateValue_' : 'orderIdMovie_');
		$sessionKey .= ($newAddedCount > 0 ? 'limit_'.$newAddedCount.'_' : '');

	} else {
		$sqlOrder = " ORDER BY movieName ASC";
		$sessionKey .= 'orderName_';
	}

	switch ($mode) {
		case 2:
			$LANGMAP = isset($GLOBALS['LANGMAP']) ? $GLOBALS['LANGMAP'] : array();
			$_country = '';
			if (!empty($country)) {
				$_country  = " AND (";
				$_country .= "SD.strAudioLanguage LIKE '".$country."' ";
				if (isset($LANGMAP[$country])) {
					$map = $LANGMAP[$country];
					for ($c = 0; $c < count($map); $c++) {
						$_country .= " OR ";
						$_country .= "SD.strAudioLanguage LIKE '".$map[$c]."'";
					}
				}
				$_country .= ") ";
			}

			$sessionKey .= $country;
			$SQL .= $_country;
			break;

		case 3:
			$uncut  = " AND (";
			$uncut .= "(lower(B.strFilename) LIKE '%director%' AND lower(B.strFilename) LIKE '%cut%' )";
			$uncut .= " OR ";
			$uncut .= "(lower(movieName) LIKE '%director%' AND lower(movieName) LIKE '%cut%')";
			$uncut .= ") ";
			$sessionKey .= 'directorCut';
			break;

		case 4:
			$uncut  = " AND (";
			$uncut .= "lower(B.strFilename) LIKE '%extended%'";
			$uncut .= " OR ";
			$uncut .= "lower(B.strFilename) LIKE '%extendet%'"; //typo-fix
			$uncut .= " OR ";
			$uncut .= "lower(movieName) LIKE '%extended%'";
			$uncut .= " OR ";
			$uncut .= "lower(movieName) LIKE '%extendet%'"; //typo-fix
			$uncut .= " OR ";
			$uncut .= "B.strFilename LIKE '%(SEE)%'";
			$uncut .= " OR ";
			$uncut .= "movieName LIKE '%(SEE)%'";
			$uncut .= ") ";
			$sessionKey .= 'extendedCut';
			break;

		case 5:
			$uncut = " AND (lower(B.strFilename) LIKE '%uncut%' OR lower(movieName) LIKE '%uncut%') ";
			$sessionKey .= 'uncutCut';
			break;

		case 6:
			$uncut = " AND (lower(B.strFilename) LIKE '%unrated%' OR lower(movieName) LIKE '%unrated%') ";
			$sessionKey .= 'unratedCut';
			break;

		case 7:
			$uncut = " AND (lower(B.strFilename) LIKE '%.3d.%' OR lower(movieName) LIKE '%(3d)%') ";
			$sessionKey .= '3dCut';
			break;

		case 8:
			$uncut = " AND (lower(B.strFilename) LIKE '%remastered%' OR lower(movieName) LIKE '%remastered%') ";
			$sessionKey .= 'remasteredCut';
			break;

		case 9:
			$uncut  = " AND (";
			$uncut .= "lower(B.strFilename) LIKE '%atmos%'";
			$uncut .= " OR ";
			$uncut .= "lower(B.strFilename) LIKE '%dtsx%'";
			$uncut .= " OR ";
			$uncut .= "F.atmosx like '%1%'";
			$uncut .= ") ";
			$sessionKey .= 'atmos';
			break;

		default:
			unset($uncut);
	}

	$params = (isset($filter) ? $filter : '').(isset($uncut) ? $uncut : '').$unseenCriteria.$sqlOrder.$sqlLimit;
	$SQL .= $params.";";

	$res['SQL']         = $SQL;
	$res['sessionKey']  = $sessionKey;
	$res['saferSearch'] = $saferSearch;
	return $res;
} //getSessionKeySQL

function fetchVariants($sessionKey = null, $dbh = null) {
	$dbh = empty($dbh) ? getPDO() : $dbh;
	$varIds = array();
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['movies'][$sessionKey]['variants']) && $overrideFetch == 0) {
		$result = unserialize($_SESSION['movies'][$sessionKey]['variants']);
		foreach ($result as $media) {
			foreach ($media as $type) {
				$varIds[] = $type['idFile'];
			}
		}
		if (!empty($sessionKey) && isset($_SESSION['movies'][$sessionKey]['ids'])) {
			$orIds = unserialize($_SESSION['movies'][$sessionKey]['ids']);
			$ids = array_unique(array_merge($orIds, $varIds));
			$_SESSION['movies'][$sessionKey]['ids'] = serialize($ids);
		}
		return $result;
	}

	$movieIdFilter = "";
	if (isset($_SESSION['movies'][$sessionKey]['movieIds'])) {
		$movieIds = unserialize($_SESSION['movies'][$sessionKey]['movieIds']);
		$movieIdFilter = "WHERE VV.idMedia IN (".implode(",", $movieIds).")";
	}

	if (empty($movieIdFilter)) {
		$movieIdFilter = "WHERE VV.idMedia IN (SELECT DISTINCT(idMedia) FROM videoversion)";
	}

	$result = array();

	$SQLv = "SELECT VV.*, VT.name AS movietype, F.idFile, F.playCount, F.lastPlayed, F.strFilename, FI.filesize, FI.fps, FI.bit FROM files F LEFT JOIN fileinfo FI ON F.idFile = FI.idFile ".
    "LEFT JOIN videoversion VV ON F.idFile = VV.idFile AND VV.media_type = 'movie' ".
    "LEFT JOIN videoversiontype VT ON VV.idType = VT.id ".
	$movieIdFilter." ORDER BY VV.idMedia;";
	$rows = querySQL(
		$SQLv,
		true, $dbh
	);

	foreach($rows as $row) {
		$idFile    = $row['idFile'];
		$idMedia   = $row['idMedia'];

		$varIds[] = $idFile;
		$res = [
			'idFile'      => $idFile,
			'idMedia'     => $idMedia,
			'idType'      => $row['idType'],
			'movietype'   => $row['movietype'],
			'playCount'   => $row['playCount'],
			'lastPlayed'  => $row['lastPlayed'],
			'strFilename' => isset($row['strFilename']) ? $row['strFilename'] : null,
			'filesize'    => isset($row['filesize'])    ? $row['filesize']    : null,
			'fps'         => isset($row['fps'])         ? $row['fps']         : null,
			'bit'         => isset($row['bit'])         ? $row['bit']         : null,
		];
		$result[$idMedia][$idFile] = $res;
	}

	if (!empty($sessionKey) && isset($_SESSION['movies'][$sessionKey]['ids'])) {
		$orIds = unserialize($_SESSION['movies'][$sessionKey]['ids']);
		$ids = array_unique(array_merge($orIds, $varIds));
		$_SESSION['movies'][$sessionKey]['ids'] = serialize($ids);
	}

	$_SESSION['movies'][$sessionKey]['variants']   = serialize($result);
	return $result;
}

/** @noinspection PhpIssetCanBeReplacedWithCoalesceInspection */
function fetchMovies($SkQL, $dbh = null) {
	$dbh = empty($dbh) ? getPDO() : $dbh;
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	$sessionKey = $SkQL['sessionKey'];

	$result = $fileIds = $movieIds = array();
	if (isset($_SESSION['movies'][$sessionKey]['items']) && $overrideFetch == 0) {
		$result = unserialize($_SESSION['movies'][$sessionKey]['items']);

	} else {
		$SQL = $SkQL['SQL'];
		$rows = querySQL($SQL, false, $dbh);
		$count = 0;
		foreach($rows as $row) {
			$result[$count]['idFile']     = isset($row['idFile'])     ? $row['idFile']     : -1;
			$fileIds[]  = $result[$count]['idFile'];
			$result[$count]['idMovie']    = isset($row['idMovie'])    ? $row['idMovie']    : -1;
			$movieIds[] = $result[$count]['idMovie'];
			$result[$count]['movieName']  = isset($row['movieName'])  ? $row['movieName']  : '';
			$result[$count]['playCount']  = isset($row['playCount'])  ? $row['playCount']  : '';
			$result[$count]['lastPlayed'] = isset($row['lastPlayed']) ? $row['lastPlayed'] : '';
			$result[$count]['filename']   = isset($row['filename'])   ? $row['filename']   : '';
			$result[$count]['fps']        = isset($row['fps'])        ? $row['fps']  	    : '';
			$result[$count]['bits']       = isset($row['bit'])        ? $row['bit']        : '';
			$result[$count]['dateAdded']  = isset($row['dateAdded'])  ? $row['dateAdded']  : '';
			$result[$count]['path']       = isset($row['path'])       ? $row['path']       : '';
			$result[$count]['jahr']       = isset($row['jahr'])       ? $row['jahr']       : '';
			$result[$count]['filesize']   = isset($row['filesize'])   ? $row['filesize']   : '';
			$result[$count]['playCount']  = isset($row['playCount'])  ? $row['playCount']  : '';
			$result[$count]['trailer']    = isset($row['trailer'])    ? $row['trailer']    : '';
			$result[$count]['rating']     = isset($row['rating'])     ? $row['rating']     : '';
			$result[$count]['imdbId']     = isset($row['imdbId'])     ? $row['imdbId']     : '';
			$result[$count]['genres']     = isset($row['genres'])     ? $row['genres']     : '';
			$result[$count]['filename']   = isset($row['filename'])   ? $row['filename']   : '';
			$count++;
		}

		$_SESSION['movies'][$sessionKey]['items']    = serialize($result);
		$_SESSION['movies'][$sessionKey]['ids']      = serialize($fileIds);
		$_SESSION['movies'][$sessionKey]['movieIds'] = serialize($movieIds);
		unset( $_SESSION['movies']['overrideFetch'] );
	}
	return $result;
}

?>
