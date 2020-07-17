<?php
include_once "./globals.php";
include_once "./template/config.php";
include_once "Serien.php";
include_once "Episode.php";

ini_set('memory_limit', '1024M');

/*
	$MenuSerienSQL   = "SELECT V.idShow AS idShow, V.idEpisode AS idEpisode, V.strTitle AS strTitle, V.c12 AS season, V.c13 AS episode, V.c00 AS title, V.playCount AS playCount, ".mapDBC('V.c03')." AS rating, (SELECT COUNT(*) FROM episode E WHERE E.idShow = V.idShow AND E.c12 = V.c12) AS sCount, F.src AS src FROM episodeviewMy V LEFT JOIN fileinfo F ON V.idFile = F.idFile";
	$LastEpisodeSQL  = $MenuSerienSQL." ORDER BY idEpisode DESC LIMIT [LIMIT]";
	$LastSerienSQL   = "SELECT COUNT(DISTINCT(idShow)) AS itemz FROM episodeviewMy WHERE idEpisode IN (SELECT idEpisode FROM episodeviewMy ORDER BY idEpisode DESC LIMIT [LIMIT])";
*/
	$MenuSerienSQL   = "SELECT V.idShow AS idShow, V.idEpisode AS idEpisode, V.strTitle AS strTitle, V.c12 AS season, V.c13 AS episode, V.c00 AS title, V.playCount AS playCount, ".mapDBC('V.c03')." AS rating, (SELECT COUNT(*) FROM episode E WHERE E.idShow = V.idShow AND E.c12 = V.c12) AS sCount, F.src AS src FROM ".mapDBC('episodeview')." V LEFT JOIN fileinfo F ON V.idFile = F.idFile";
	$LastEpisodeSQL  = $MenuSerienSQL." ORDER BY idEpisode DESC LIMIT [LIMIT]";
	$LastSerienSQL   = "SELECT COUNT(DISTINCT(idShow)) AS itemz FROM ".mapDBC('episodeview')." WHERE idEpisode IN (SELECT idEpisode FROM ".mapDBC('episodeview')." ORDER BY idEpisode DESC LIMIT [LIMIT])";
	$SearchSerienSQL = $MenuSerienSQL." WHERE lower(V.c04) LIKE '%[SEARCH]%' OR lower(V.c01) LIKE '%[SEARCH]%' OR lower(V.c00) LIKE '%[SEARCH]%' ORDER BY V.strTitle ASC";
	$SQLrunning      = "SELECT R.idShow AS idShow, R.running AS running, A.airdate AS airdate FROM tvshowrunning R LEFT JOIN nextairdate A ON R.idShow = A.idShow;";
	$SeasonDirSQL    = "SELECT idShow, strPath FROM ".mapDBC('seasonview').";";
	$ShowCodecSQL    = "SELECT E.idShow AS idShow, SD.strVideoCodec AS codec, COUNT(SD.strVideoCodec) AS count FROM streamdetails AS SD, episode AS E WHERE SD.strVideoCodec IS NOT NULL AND SD.idfile = E.idFile AND E.idShow=[IDSHOW] GROUP BY E.idShow, SD.strVideoCodec ORDER BY count DESC;";
	$episodeLinkSQL  = "SELECT * FROM episodelinkepisode";
	$SerienSQL       = "SELECT V.*, ".
			   "V.c00 AS epName, ".
			   "V.c01 AS epDesc, ".
			   "V.rating AS epRating, ".
			   "V.c04 AS guests, ".
			   "V.c05 AS airDate, ".
			   "V.c09 AS duration, ".
			   "V.c12 AS season, ".
			   "V.c13 AS episode, ".
			   #"T.c16 AS showPath, ".
			   "V.strTitle AS serie, ".
			   "V.idFile AS idFile, ".
			   "V.strFilename AS filename, ".
			   "V.strPath AS path, ".
			   "T.c01 AS showDesc, ".
			   "T.c08 AS genre, ".
			   mapDBC('T.c12')." AS idTvdb, ".
			   "T.c13 AS fsk, ".
			   "T.c14 AS studio, ".
			   "P.idPath AS idPath, ".
			   "F.filesize AS filesize, ".
			   "F.fps AS fps, ".
			   "F.bit AS bits, ".
			   "F.src AS source, ".
			   "F.atmosx AS atmosx ".
			   "FROM ".mapDBC('episodeview')." V, ".mapDBC('tvshowview')." T ".
			   "LEFT JOIN fileinfo F ON V.idFile = F.idFile ".
			   "LEFT JOIN files FS ON V.idFile=FS.idFile ".
			   "LEFT JOIN path P ON P.idPath=FS.idPath ".
//			   mapDBC('joinIdShow').
			   "WHERE T.idShow = V.idShow";

	function fetchSearchSerien($search) {
		$SQL = $GLOBALS['SearchSerienSQL'].';';
		$SQL = str_replace('[SEARCH]', $search, $SQL);
		return fetchSerienToArray($SQL, 'FSerien_'.$search);
	}

	function getLastItemsCount($LIMIT) {
		$LastSerienSQL = $GLOBALS['LastSerienSQL'].';';
		$SQL = str_replace('[LIMIT]', $LIMIT, $LastSerienSQL);
		$res = fetchFromDB($SQL);
		return $res['itemz'];
	}

	function fetchLastSerien() {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
		$LIMIT = isset($GLOBALS['TVSHOW_MENU_LIMIT']) ? $GLOBALS['TVSHOW_MENU_LIMIT'] : 30;
		if (isset($_SESSION['param_S_LIMIT']) && $overrideFetch == 0)
			$LIMIT = $_SESSION['param_S_LIMIT'];
		else {
			$runs  = 1;
			$items = 0;
			while(($items = getLastItemsCount($LIMIT)) < $LIMIT) {
				$LIMIT += 5*$runs;
				if ($runs++ >= 5)
					break;
			}
			$_SESSION['param_S_LIMIT'] = $LIMIT;
		}

		$LastEpisodeSQL = $GLOBALS['LastEpisodeSQL'].';';
		$SQL = str_replace('[LIMIT]', $LIMIT, $LastEpisodeSQL);
		#echo $SQL;
		return fetchSerienToArray($SQL, 'LSerien');
	}

	function fetchSerienToArray($SQL, $sessionKey) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;

		$result = array();
		if (isset($_SESSION[$sessionKey]) && $overrideFetch == 0) {
			$result = unserialize($_SESSION[$sessionKey]);

		} else {
			$sqlResult = querySQL($SQL, false);
			foreach($sqlResult as $row) {
				$result[$row['strTitle']][] = array(
				'idShow' => $row['idShow'], 'serie'     => $row['strTitle'],  'episode' => $row['episode'],
				'season' => $row['season'], 'idEpisode' => $row['idEpisode'], 'title'   => $row['title'], 
				'sCount' => $row['sCount'], 'playCount' => $row['playCount'], 'src'     => $row['src'],
				'rating' => $row['rating'], 'rating_'   => $row['rating_']
				);
			}

			$_SESSION[$sessionKey] = serialize($result);
			unset( $_SESSION['overrideFetch'] );
		}

		return $result;
	}

	function fetchSerien($SQL, $id, $dbh = null) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;

		if (empty($SQL)) { $SQL = $GLOBALS['SerienSQL'].';'; }
		if (empty($id))  { $id  = ''; }
		$SSerien = null;

		if (isset($_SESSION['SSerien'.$id]) && $overrideFetch == 0) {
			$SSerien = unserialize($_SESSION['SSerien'.$id]);

		} else {
			$SSerien = fetchSerien__($SQL, $dbh);
			$SSerien->getSize();
			$SSerien->fetchRating();
			$_SESSION['SSerien'.$id] = serialize($SSerien);
			unset( $_SESSION['overrideFetch'] );
		}
		return $SSerien;
	}

	function fetchSerien__($SQL, $dbh = null) {
		$runs   = array();
		$serien = new Serien();

		checkEpLinkEpTable($dbh);
		checkMyEpisodeView($dbh);
		checkNextAirDateTable($dbh);
		checkTvshowRunningTable($dbh);

		$SQLepLnk= $GLOBALS['episodeLinkSQL'];
		$resL    = querySQL_($dbh, $SQLepLnk, false);
		$links   = array();
		foreach($resL as $row) { $links[$row['idFile']] = $row['delta']; }

		$SQLrunning = $GLOBALS['SQLrunning'];
		$resF       = querySQL_($dbh, $SQLrunning, false);
		foreach($resF as $run_) {
			$idShow      = $run_['idShow'];
			$running     = isset($run_['running']) ? true : false;
			$nextAirDate = isset($run_['airdate']) ? $run_['airdate'] : null;
			$runs[$idShow]['running']     = $running;
			$runs[$idShow]['nextairdate'] = $nextAirDate;
		}

		$SQLdirs = $GLOBALS['SeasonDirSQL'];
		$resD    = querySQL_($dbh, $SQLdirs, false);
		$dirs    = array();
		foreach($resD as $row) {
			$idShow  = $row['idShow'];
			$strPath = $row['strPath'];
			if (empty($dirs[$idShow])) { $dirs[$idShow] = $strPath; }
		}

		$result = querySQL_($dbh, $SQL, false);
		foreach($result as $row) {
			$idFile      = $row['idFile'];
			$idShow      = $row['idShow'];
			$idEpisode   = $row['idEpisode'];
			$idSeason    = $row['idSeason'];
			$idPath      = $row['idPath'];
			$airDate     = $row['airDate'];
			$serienname  = $row['serie'];
			$epName      = $row['epName'];
			$duration    = $row['duration'];
			$showDesc    = $row['showDesc'];
			$season      = $row['season'];
			$idTvdb      = $row['idTvdb'];
			$rating      = $row['epRating'];
			$playcount   = $row['playCount'];
			$genre       = $row['genre'];
			$fsk         = $row['fsk'];
			$studio      = $row['studio'];
			$episodeNum  = $row['episode'];
			$delta       = isset($links[$idFile]) ? $links[$idFile] : null;
			$filename    = $row['filename'];
			$path        = $row['path'];
			$showPath    = isset($dirs[$idShow]) ? $dirs[$idShow] : '';
			$filesize    = $row['filesize'];
			$source      = $row['source'];
			$epDesc      = '';
			$running     = (!empty($runs) && isset($runs[$idShow]['running']) ? true : false);
			$nextAirDate = (!empty($runs) && isset($runs[$idShow]['nextairdate']) ? $runs[$idShow]['nextairdate'] : null);

			$ep = new Episode($episodeNum, $delta, $season, $idShow, $idTvdb, $idEpisode, $idSeason, $idFile, $idPath, $path, $epName, $epDesc, $rating, $serienname, $airDate, $duration, $playcount, $filename, $filesize, $source);
			if (is_object($serien) && is_object($ep)) {
				#$serien->addEpisode($ep, $fsk, $genre, $studio, $showDesc, $showPath, $running, $nextAirDate, $codecs[$idShow]);
				$serien->addEpisode($ep, $fsk, $genre, $studio, $showDesc, $showPath, $running, $nextAirDate);
			}
		}

		return $serien;
	}

	function fetchShowCodecs($idShow, $dbh = null) {
		$SQLcodec = $GLOBALS['ShowCodecSQL'];
		$SQLcodec = str_replace('[IDSHOW]', $idShow, $SQLcodec);
		$resC     = querySQL_($dbh, $SQLcodec, false);
		$codecs   = array();
		foreach($resC as $row)
			$codecs[$row['codec']] = $row['count'];

		return $codecs;
	}
?>
