<?php
/** @noinspection SqlResolve */
include_once "./globals.php";
include_once "./template/config.php";
include_once "./template/SimpleImage.php";
include_once "./template/Series/_SERIEN.php";
require_once "./rss_php.php";

function startSession()   { if (!isset($_SESSION)) { session_start(); } }
function allowedRequest($reffer = null) {
	$ext = isset($GLOBALS['EXT_HOST']) ? $GLOBALS['EXT_HOST'] : null;
	if (!empty($ext) && !empty($reffer) && substr_count($reffer, gethostbyname($ext)) != 0)
		return false;
	return true;
}
function getOS() {
	startSession();
	if (!isset($_SESSION['OS']) || isset($_SESSION['overrideFetch'])) { $_SESSION['OS'] = strtoupper(php_uname('s')); }
	return $_SESSION['OS'];
}

function isAjax() {
	return isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest';
}

function isLinux() { return 'LINUX' === getOS(); }

function getTimeOut($intra = false) {
	return $intra ? 1 : 5;
}

function execSQL($SQL, $throw = true, $dbh = null) {
	return execSQL_($SQL, $throw, true, $dbh);
}

function execSQL_($SQL, $throw = true, $commitExtern = true, $dbh = null) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		if (!$commitExtern && !$dbh->inTransaction()) {
			$dbh->beginTransaction();
		}

		$dbh->exec($SQL);

		if (!$commitExtern && $dbh->inTransaction()) {
			$dbh->commit();
		}

	} catch(Throwable $e) {
		if (!$commitExtern && $dbh->inTransaction()) {
			$dbh->rollBack();
		}
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function querySQL($SQL, $throw = true, $dbh = null) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		return $dbh->query($SQL);

	} catch(Throwable $e) {
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function fetchCount($table, $dbh = null) {
	$result = querySQL("SELECT COUNT(*) AS c FROM ".$table.";", false, $dbh);
	foreach($result as $row) {
		return $row['c'];
	}
	return 0;
}

function singleSQL($SQL, $throw = true, $dbh = null) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		return $dbh->querySingle($SQL);

	} catch(Throwable $e) {
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function fetchFromDB($SQL, $throw = true, $dbh = null) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		$result = $dbh->query($SQL);
		$fetched = $result->fetch();
		if ($fetched !== false) {
			return $fetched;
		}

	} catch(Throwable $e) {
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function getTableNames() {
	$dbh = getPDO();
	$result = array();
	try {
		$res    = $dbh->query("SELECT name FROM sqlite_master WHERE type='table' AND name IS NOT 'sqlite_sequence';");
		foreach($res as $row) {
			$result[] = $row['name'];
		}

		sort($result);

	} catch(Throwable $e) {
		if (isAdmin()) { echo $e->getMessage(); }
	}

	return $result;
}

function getStreamDetails($idFile, $dbh = null) {
	return querySQL("SELECT * FROM streamdetails WHERE (strAudioLanguage IS NOT NULL OR strVideoCodec IS NOT NULL OR strSubtitleLanguage IS NOT NULL OR strHdrType IS NOT NULL) AND idFile = ".$idFile.";", false, $dbh);
}

function getGenres($dbh) {
	$idGenre = array();
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['idGenre']) && $overrideFetch == 0) {
		$idGenre = unserialize($_SESSION['idGenre']);

	} else {
		$sqlG = "SELECT * FROM genre";
		$resultG = $dbh->query($sqlG);
		foreach($resultG as $rowG) {
			if (empty($rowG[mapDBC('strGenre')])) { continue; }
			$str = ucwords(strtolower(trim($rowG[mapDBC('strGenre')])));
			if (empty($str)) { continue; }

			if (empty($rowG[mapDBC('idGenre')])) { continue; }
			$idGenre[$str][0] = $rowG[mapDBC('idGenre')];
			$idGenre[$str][1] = 0;
		}

		$_SESSION['idGenre'] = serialize($idGenre);
		unset( $_SESSION['overrideFetch'] );
	}

	return $idGenre;
}

function getOverrideAR($idFile, $idMedia, $dbh = null) {
	$result = fetchFromDB("SELECT ratio FROM aspectratio WHERE idFile = ".$idFile." AND idMovie = ".$idMedia.";", false, $dbh);
	if (empty($result) || empty($result['ratio']) || !is_numeric($result['ratio'])) {
		return null;
	}

	return $result['ratio'];
}

function getToneMapping($idFile, $dbh = null) {
	$result = fetchFromDB("SELECT TonemapMethod, TonemapParam FROM settings WHERE idFile = ".$idFile.";", false, $dbh);
	if (empty($result) || !is_numeric($result['TonemapParam']) || $result['TonemapParam'] == 1.0) {
		return null;
	}

	return array(
		'Method' => TONEMAPMETHOD[$result['TonemapMethod']],
		'Param'  => $result['TonemapParam']
	);
}

function getResolution($SkQL, $isMovie, $dbh = null) {
	$idStream   = array();
	$movKey     = $isMovie ? "movie" : "episode";
	$sessionKey = $SkQL['sessionKey'];

	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['idStream'][$movKey][$sessionKey]) && $overrideFetch == 0) {
		$idStream = unserialize($_SESSION['idStream'][$movKey][$sessionKey]);

	} else {
		$SQL = "SELECT streamdetails.*, ".$movKey.".idFile FROM streamdetails ".
			"LEFT JOIN ".$movKey." ON ".$movKey.".idFile = streamdetails.idFile ".
			"WHERE streamdetails.iVideoWidth IS NOT NULL";

		if (isset($_SESSION['movies'][$sessionKey]['ids'])) {
			$ids = unserialize($_SESSION['movies'][$sessionKey]['ids']);
			if (count($ids) < 300) {
				$SQL .= " AND streamdetails.idFile IN (".implode(',', $ids).")";
			}
		}

		$result = querySQL($SQL, false, $dbh);
		foreach($result as $row) {
			if (!empty($id = $row['idFile'])) {
				$idStream[$id][] = $row['iVideoWidth'];
				$idStream[$id][] = $row['iVideoHeight'];
				$idStream[$id][] = $row['strVideoCodec'];
				$idStream[$id][] = $row['strHdrType'];
			}
		}

		$_SESSION['idStream'][$movKey][$sessionKey] = serialize($idStream);
		unset( $_SESSION['overrideFetch'] );
	}

	return $idStream;
}

function getWrapper() {
	return isset($GLOBALS['IMAGE_DELIVERY']) ? $GLOBALS['IMAGE_DELIVERY'] : null;
}

function wrapItUp($type, $id, $strFname) {
	$dlvry = getWrapper();
	if (isset($dlvry) && $dlvry == 'wrapped' && !empty($strFname)) {
		$_SESSION['thumbs'][$type][$id] = $strFname;
	}
}

function getImageWrap($strFname, $id, $type, $size, $dlvry = null) {
	if (empty($dlvry)) { $dlvry = getWrapper(); }
	if ($dlvry == 'encoded') {
		if (isFile($strFname)) { return base64_encode_image($strFname); }
		$dlvry = 'wrapped';
	}
	if ($dlvry == 'wrapped') { return './?img='.$id.'&'.$type.(!empty($size) ? '='.$size : ''); }

	return $strFname;
}

function loadImage($imgURL, $localfile) {
	if (isFile($localfile)) { return 0; }
	if ($fd = fopen ($imgURL, "rb")) {
		$buffer = stream_get_contents($fd);
		if ($fh = fopen($localfile, 'w') ) {
			fwrite($fh, $buffer);
			fclose($fh);
			return 1;
		}
	}

	return -1;
}

function _format_bytes($a_bytes) {
	if (empty($a_bytes)) {
		return '';
	}

	$res = '';
	$round = 2;

	if ($a_bytes < 1024) {
		$res = $a_bytes .' B';

	} elseif ($a_bytes < 1048576) {
		$round = 0;
		$res = round($a_bytes / 1024, $round) .' KiB';

	//} elseif ($a_bytes < 1073741824) {
	} elseif ($a_bytes < 1073000000) { //1024MB hack
		$round = 0;
		$res = round($a_bytes / 1048576, $round) . ' MiB';

	} elseif ($a_bytes < 1099511627776) {
		$res = round($a_bytes / 1073741824, $round) . ' GiB';

	} elseif ($a_bytes < 1125899906842624) {
		$res = round($a_bytes / 1099511627776, $round) .' TiB';

	} elseif ($a_bytes < 1152921504606846976) {
		$res = round($a_bytes / 1125899906842624, $round) .' PiB';

	} elseif ($a_bytes < 1180591620717411303424) {
		$res = round($a_bytes / 1152921504606846976, $round) .' EiB';

	} elseif ($a_bytes < 1208925819614629174706176) {
		$res = round($a_bytes / 1180591620717411303424, $round) .' ZiB';

	} else {
		$res = round($a_bytes / 1208925819614629174706176, $round) .' YiB';
	}

	if ($round != 0) {
		$r = explode(' ', $res);
		$unit = $r[1];

		$r = explode('.', $r[0]);
		if (count($r) > 1) {
			$l = strlen($r[1]);
			$r[1] .= ($l < 2 ? '0' : '');
			$res = $r[0].'.'.$r[1].' '.$unit;

		} else {
			$res = str_replace(' ', '.00 ', $res);
		}
	}

	$gibAsGb = isset($GLOBALS['GIB_AS_GB']) ? $GLOBALS['GIB_AS_GB'] : false;
	if ($gibAsGb) {
		return str_replace('iB', 'B', $res);
	}

	return $res;
}

function filesize_n($path) {
	if (!isLinux()) { return null; }
        $size = -1;
        if ($size < 0 ){
            ob_start();
            system('ls -al "'.$path.'" | awk \'BEGIN {FS=" "}{print $5}\'');
            $size = ob_get_clean();
        }
        return $size;
}

function existsArtTable($dbh = null) {
	return existsTable('art', 'table', $dbh);
}

function existsSetTable($dbh = null) {
	return existsTable('sets', 'table', $dbh);
}

function checkEpLinkEpTable($dbh = null) {
	$exist = existsTable('episodelinkepisode', 'table', $dbh);
	if (!$exist) { execSQL("CREATE TABLE IF NOT EXISTS episodelinkepisode(idFile INTEGER NOT NULL, delta INTEGER, CONSTRAINT 'C01_idFile' UNIQUE (idFile), CONSTRAINT 'C02_idFile' FOREIGN KEY (idFile) REFERENCES files (idFile) ON DELETE CASCADE);", false, $dbh); }
}

function checkFileInfoTable($dbh = null) {
	$exist = existsTable('fileinfo', 'table', $dbh);
	if (!$exist) {
		execSQL("CREATE TABLE IF NOT EXISTS fileinfo(idFile INTEGER NOT NULL, filesize LONGINT, fps FLOAT, src INTEGER, CONSTRAINT 'C01_idFile' UNIQUE (idFile), CONSTRAINT 'C02_idFile' FOREIGN KEY (idFile) REFERENCES files (idFile) ON DELETE CASCADE);", false, $dbh);
		execSQL("CREATE UNIQUE INDEX IF NOT EXISTS ix_fileinfo_1 ON fileinfo (idFile);", false, $dbh);

	} else {
		$IGNORE = isset($GLOBALS['IGNORE_COL_CHECKS']) ? $GLOBALS['IGNORE_COL_CHECKS'] : null;
		if (!empty($IGNORE)) {
			$IGNORE = isset($IGNORE['fileinfo']) ? $IGNORE['fileinfo'] : null;
		}
		checkAndAlterTableCol('fileinfo', 'param_fpsColChecked', 'fps',    'FLOAT',     $IGNORE, $dbh);
		checkAndAlterTableCol('fileinfo', 'param_srcColChecked', 'src',    'TINYINT',   $IGNORE, $dbh);
		checkAndAlterTableCol('fileinfo', 'param_fpsColChecked', 'bit',    'TINYINT',   $IGNORE, $dbh);
		checkAndAlterTableCol('fileinfo', 'param_atmColChecked', 'atmosx', 'CHARACTER', $IGNORE, $dbh);
	}
}

function checkARTable($dbh = null) {
	$exist = existsTable('aspectratio', 'table', $dbh);
	if (!$exist) {
		execSQL("CREATE TABLE IF NOT EXISTS aspectratio(idFile INTEGER NOT NULL, idMovie INTEGER NOT NULL, ratio FLOAT, CONSTRAINT 'C01_idFile' UNIQUE (idFile), CONSTRAINT 'C02_idFile' FOREIGN KEY (idFile) REFERENCES files (idFile) ON DELETE CASCADE);", false, $dbh);
		execSQL("CREATE UNIQUE INDEX IF NOT EXISTS ix_aspectratio_1 ON aspectratio (idFile, idMovie);", false, $dbh);
		execSQL("CREATE UNIQUE INDEX IF NOT EXISTS ix_aspectratio_2 ON aspectratio (idMovie, idFile);", false, $dbh);
	}
}

function checkMyEpisodeView($dbh = null) {
	$exist = existsTable('episodeviewMy', 'view', $dbh);
	if (!$exist) { execSQL("CREATE VIEW IF NOT EXISTS episodeviewMy AS SELECT episode.*, files.strFileName AS strFileName, path.idPath AS idPath, path.strPath AS strPath, files.playCount AS playCount, files.lastPlayed AS lastPlayed, tvshow.c00 AS strTitle, tvshow.c14 AS strStudio, tvshow.idShow AS idShow, tvshow.c05 AS premiered, tvshow.c13 AS mpaa, tvshow.c16 AS strShowPath FROM tvshow JOIN episode ON episode.idShow=tvshow.idShow JOIN files ON files.idFile=episode.idFile JOIN path ON files.idPath=path.idPath;", false, $dbh); }
}

function checkTvshowRunningTable($dbh = null) {
	$exist = existsTable('tvshowrunning', 'table', $dbh);
	if (!$exist) { execSQL("CREATE TABLE IF NOT EXISTS tvshowrunning(idShow INTEGER NOT NULL, running INTEGER, CONSTRAINT 'C01_idShow' UNIQUE (idShow), CONSTRAINT 'C02_idShow' FOREIGN KEY (idShow) REFERENCES tvshow (idShow) ON DELETE CASCADE);", false, $dbh); }
}

function checkNextAirDateTable($dbh = null) {
	$exist = existsTable('nextairdate', 'table', $dbh);
	if (!$exist) { execSQL("CREATE TABLE IF NOT EXISTS nextairdate(idShow INTEGER NOT NULL, season INTEGER, episode INTEGER, lastEpisode INTEGER, airdate LONGINT, maxSeason INTEGER, maxEpisode INTEGER, CONSTRAINT 'C01_idShow' UNIQUE (idShow), CONSTRAINT 'C02_idShow' FOREIGN KEY (idShow) REFERENCES tvshow (idShow) ON DELETE CASCADE);", false, $dbh); }
	else {
		$IGNORE = isset($GLOBALS['IGNORE_COL_CHECKS']) ? $GLOBALS['IGNORE_COL_CHECKS'] : null;
		if (!empty($IGNORE)) {
			$IGNORE = isset($IGNORE['nextairdate']) ? $IGNORE['nextairdate'] : null;
		}
		checkAndAlterTableCol('nextairdate', 'param_lStColChecked', 'maxSeason',  'INTEGER', $IGNORE, $dbh);
		checkAndAlterTableCol('nextairdate', 'param_lEpColChecked', 'maxEpisode', 'INTEGER', $IGNORE, $dbh);
	}
}

function checkAndAlterTableCol($table, $sessionKey, $col, $colType, $IGNORE, $dbh = null) {
	if (isset($_SESSION[$sessionKey])) {
		return;
	}
	if (!empty($IGNORE) && isset($IGNORE[$col])) {
		$_SESSION[$sessionKey] = true;
		return;
	}

	$colFound = false;
	$dbh = (!empty($dbh) ? $dbh : getPDO());
	$res = $dbh->query("PRAGMA TABLE_INFO('".$table."');");
	foreach($res as $row) {
		if ($row[1] == $col) {
			$colFound = true;
			break;
		}
	}

	if (!$colFound) { $dbh->exec("ALTER TABLE ".$table." ADD ".$col." ".$colType.";"); }
	$_SESSION[$sessionKey] = true;
}

function existsOrderzTable($dbh = null) {
	$saveOrderInDB = isset($GLOBALS['SAVE_ORDER_IN_DB']) ? $GLOBALS['SAVE_ORDER_IN_DB'] : false;
	if (!$saveOrderInDB) { return false; }

	$exist = existsTable('orderz', 'table', $dbh);
	if (!$exist) {
		execSQL_("CREATE TABLE IF NOT EXISTS orderz(idOrder INTEGER primary key, dateAdded INTEGER, user TEXT, fresh INTEGER, CONSTRAINT 'C01_idOrder' UNIQUE (idOrder));", false, false, $dbh);
		execSQL_("CREATE TABLE IF NOT EXISTS orderItemz(idOrder INTEGER, idElement INTEGER, movieOrShow INTEGER, CONSTRAINT 'C01_ids' UNIQUE (idOrder, idElement), CONSTRAINT 'C02_idOrder' FOREIGN KEY (idOrder) REFERENCES orderz (idOrder) ON DELETE CASCADE);", false, false, $dbh);
		$exist = existsTable('orderz', 'table', $dbh);
	}
	return $exist;
}

function existsFilemapTable($dbh = null) {
	$exist = existsTable('filemap', 'table', $dbh);
	if (!$exist) { execSQL("CREATE TABLE IF NOT EXISTS filemap( idFile integer primary key, strFilename text, dateAdded text, value longint );", false, $dbh); }
}

function checkFileMapTable($dbh) {
	existsFilemapTable($dbh);
}

function existsOrdersTable($dbh = null) {
	$exist = existsTable('orders', 'table', $dbh);
	if (!$exist) {
		execSQL_("CREATE TABLE IF NOT EXISTS orders(strFilename TEXT primary key, dateAdded INTEGER, user TEXT, fresh INTEGER);", false, false, $dbh);
		$exist = existsTable('orders', 'table', $dbh);
	}
	return $exist;
}

function existsTable($tableName, $type = 'table', $dbh = null) {
	$dbh = (!empty($dbh) ? $dbh : getPDO());
	try {
		$exist = isset($_SESSION['existsTable'][$tableName]) ? $_SESSION['existsTable'][$tableName] : -1;
		if ($exist !== -1) { return $exist; }

		$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='".$type."' and name='".$tableName."';");
		$row = $res->fetch();

		$exist = !empty($row['name']);
		$_SESSION['existsTable'][$tableName] = $exist;
		return $exist;

	} catch(Throwable $e) { }

	return false;
}

function fetchSeasonIds($idShow) {
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;

	$ids = array();
	if ( isset($_SESSION['param_seasonIds']) && isset($_SESSION['param_seasonIds'][$idShow]) && $overrideFetch == 0) {
		$ids = unserialize($_SESSION['param_seasonIds'][$idShow]);

	} else {
		$SQL = "SELECT idSeason,season FROM seasons WHERE idShow = ".$idShow.";";

		$dbh = getPDO();
		$res = querySQL($SQL, false, $dbh);

		$index = 0;
		foreach($res as $row) {
			$ids[$index][0] = $row['idSeason'];
			$ids[$index][1] = $row['season'];
			$index++;
		}

		$_SESSION['param_seasonIds'][$idShow] = serialize($ids);
		unset( $_SESSION['overrideFetch'] );
	}

	return $ids;
}

function fetchPaths() {
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;

	$paths = array();
	if (isset($_SESSION['paths']) && $overrideFetch == 0) {
		$paths = unserialize($_SESSION['paths']);

	} else {
		// $TVSHOWDIR = isset($GLOBALS['TVSHOWDIR']) ? $GLOBALS['TVSHOWDIR'] : '';
		$SQL = "SELECT idPath,strPath FROM path WHERE strPath IN (SELECT DISTINCT(strPath) FROM ".mapDBC('episodeview').");";

		$dbh = getPDO();
		$res = querySQL($SQL, false, $dbh);

		$index = 0;
		foreach($res as $row) {
			$paths[$index][0] = $row['idPath'];
			$paths[$index][1] = $row['strPath'];
			$index++;
		}

		$_SESSION['paths'] = serialize($paths);
		unset( $_SESSION['overrideFetch'] );
	}

	return $paths;
}

function fetchAudioFormat($idFile, $path, $filename, $atmosx, $dbh, $eventuallyAtmos = false) {
	if (!isLinux()) { return null; }
	$enabled = isset($GLOBALS['FETCH_ATMOSX']) ? $GLOBALS['FETCH_ATMOSX'] : false;
	if (!$enabled) { return null; }

	if (!empty($atmosx)) {
		return $atmosx;
	}

	$fname = strtolower($filename);
	$eventuallyAtmos |= (substr_count($fname, 'atmos') > 0 || substr_count($fname, 'dtsx') > 0);
	if (!$eventuallyAtmos) { return 0; }

	$res = getAudioProfile($path.$filename, $fname);
	if (empty($res)) {
		persistAtmosFlag(null, $idFile, $dbh);
		return 0;
	}

	$atmosRes = array();
	for ($i = 0; $i < count($res); ++$i) {
		$item = empty($res[$i]) ? '' : strtolower($res[$i]);
		$isAtmos = (substr_count($item, 'atmos') > 0 || substr_count($item, 'x / ma') > 0 || $item == 'dts:x' || $item == 'object based');
		$atmosRes[$i] = $isAtmos ? 1 : 0;
	}

	persistAtmosFlag($atmosRes, $idFile, $dbh);

	return $atmosRes;
}

function persistAtmosFlag($atmosRes, $idFile, $dbh) {
	$dbValue = empty($atmosRes) ? '' : implode(',', $atmosRes);
	$dbhIsNull = ($dbh === null);
	try {
		if ($dbhIsNull) { $dbh = getPDO(); }

		$sqli = "UPDATE fileinfo SET atmosx = '".$dbValue."' WHERE idFile = '$idFile';";

		if ($dbhIsNull && !$dbh->inTransaction()) { $dbh->beginTransaction(); }
		$dbh->exec($sqli);
		if ($dbhIsNull && $dbh->inTransaction()) { $dbh->commit(); }

	} catch(Throwable $e) {
		if ($dbhIsNull && $dbh->inTransaction()) { $dbh->rollBack(); }
		if (isAdmin()) { echo $e->getMessage(); }
	}
}

function fetchFps($idFile, $path, $filename, $fps, $dbh) {
	if (!isLinux()) { return null; }
	$enabled = isset($GLOBALS['FETCH_FPS']) ? $GLOBALS['FETCH_FPS'] : false;
	if (!$enabled) { return null; }

	if ($fps === null || $fps[0] === null || $fps[1] === null) {
		$stacked = (substr($filename, 0, 8) == "stack://");
		if ($stacked)
			$fps = null;
		else
			$fps = getFps($path.$filename);

		if (empty($fps)) { $fps = array(null, null); }
		else {
			if (substr_count($fps, ' ') >= 1)
				$fps = explode(' ', $fps);
			else
				$fps = array($fps, null);
		}

		//variable FPS fix
		if (isset($fps[2]) && $fps[2] != null && $fps[1] == null)
			$fps = array($fps[0], $fps[2]);
		else
			$fps = array($fps[0], $fps[1]);

		$dbhIsNull = ($dbh === null);
		try {
			if ($dbhIsNull) { $dbh = getPDO(); }

			$sqli = "UPDATE fileinfo SET fps = '".$fps[1]."', bit = '".intval($fps[0])."' WHERE idFile = '$idFile';";

			if ($dbhIsNull && !$dbh->inTransaction()) { $dbh->beginTransaction(); }
			$dbh->exec($sqli);
			if ($dbhIsNull && $dbh->inTransaction()) { $dbh->commit(); }

			//clearMediaCache();

		} catch(Throwable $e) {
			if ($dbhIsNull && $dbh->inTransaction()) { $dbh->rollBack(); }
			if (isAdmin()) { echo $e->getMessage(); }
		}
	} // if fps == null...

	if (empty($fps)) {
		return array(null, null);
	}

	$fps[1] = formatFps($fps[1]);
	return $fps;
}

function formatFps($fps) {
	if (isset($fps) && intval($fps) == 0)
		$fps = null;
	if (isset($fps) && (substr($fps, -3) == '000' || substr($fps, -2) == '.0'))
		$fps = intval($fps);
	return $fps;
}

function fetchFileSize($idFile, $path, $filename, $fsize, $dbh) {
	if ($fsize == null || $fsize == 0) {
		$stacked = (substr($filename, 0, 8) == "stack://");
		if ($stacked) {
			$fsize = getStackedFilesize($filename);
		} else {
			$fsize = getFilesize($path.$filename);
		}

		if (empty($fsize)) { return 0; }

		$dbhIsNull = ($dbh == null);
		try {
			if ($dbhIsNull) { $dbh = getPDO(); }

			$sqli = "REPLACE INTO fileinfo(idFile, filesize, src) VALUES(".$idFile.", ".$fsize.", (SELECT src FROM fileinfo WHERE idFile = ".$idFile."));";
			if ($dbhIsNull && !$dbh->inTransaction()) { $dbh->beginTransaction(); }

			$dbh->exec($sqli);

			if ($dbhIsNull && $dbh->inTransaction()) { $dbh->commit(); }

			clearMediaCache();

		} catch(Throwable $e) {
			if ($dbhIsNull && $dbh->inTransaction()) { $dbh->rollBack(); }
			if (isAdmin()) { echo $e->getMessage(); }
		}
	} // if fsize == null...

	return $fsize;
}

function getStackedFilesize($filename) {
	$files = explode(' , ', $filename);
	$fsize = 0;

	for ($i = 0; $i < count($files); $i++) {
		$file = str_replace('stack://', '', $files[$i]);
		$file = mapSambaDirs($file);
		$fsize += getFilesize($file);
	}

	return $fsize;
}

function getFilesize($file) {
	if (!isLinux()) { return null; }
	#$file = correctFilename($file);
	#if (empty($file)) { return null; }

	$output = execCommand($file, 'stat -c %s ');
	if ($output != null && count($output) > 0) {
		return trim($output[0]);
	}

	return null;
}

function getCreation($file) {
	if (!isLinux()) { return null; }
	#$file = correctFilename($file);
	#if (empty($file)) { return null; }

	$output = execCommand($file, 'stat -c %y ');
	if ($output != null && count($output) > 0) {
		return substr(trim($output[0]), 0, 19);
	}

	return null;
}

function getFps($file) {
	if (!isLinux()) { return null; }
	#$output = execCommand($file, 'mediainfo --Inform="Video;%BitDepth% %FrameRate% %FrameRate_Original% %transfer_characteristics%" ');
	$output = execCommand($file, 'mediainfo --Inform="Video;%BitDepth% %FrameRate% %FrameRate_Original%" ');

	if ($output != null && count($output) > 0) {
		$res = trim($output[0]);
		return empty($res) ? null : $res;
	}

	return 0;
}

function getAudioProfile($file, $fname) {
	if (!isLinux()) { return null; }

	$res 	= null;
	$resDTS = null;
	if (substr_count($fname, 'atmos') > 0) {
		$res = checkAudioOutput(
			execCommand($file, 'mediainfo --Inform="Audio;%Format_Profile%\n" ')
		);
		if (!empty($res) && "Blu-ray Disc" !== $res[0]) { return $res; }

		$res = checkAudioOutput(
			execCommand($file, 'mediainfo --Language=raw -f --Inform="Audio;%Format_Commercial%\n" ')
		);
	}

	if (substr_count($fname, 'dtsx') > 0) {
		$resDTS = checkAudioOutput(
			execCommand($file, 'mediainfo --Inform="Audio;%ChannelLayout_Original%\n" ')
		);

	}
	if (empty($resDTS)) {
		$resDTS = checkAudioOutput(
			execCommand($file, 'mediainfo --Inform="Audio;%Title%\n" ')
		);
	}

	if (empty($res)) {
		$res = $resDTS;

	} elseif (!empty($resDTS)) {
		for ($i = 0; $i < count($res); ++$i) {
			if ($res[$i] != $resDTS[$i]) {
				$res[$i] = $resDTS[$i];
			}
		}
	}

	return empty($res) ? null : $res;
}

function checkAudioOutput($output) {
	if ($output != null && count($output) > 0) {
		$res = $output;
		if (empty($res)) { return null; }
		unset($res[count($res)-1]);
		if (!empty($res)) {
			$check = implode('', $res);
			if ($check == '') {
				for ($i = count($res); $i > 0; $i--) {
					if (empty($res[$i-1])) { unset($res[$i-1]); }
				}
			}

			if (!empty($res)) { return $res; }
		}
	}

	return null;
}

function execCommand($file, $execString) {
	if (!isLinux()) { return null; }
	$file = correctFilename($file);
	if (empty($file)) { return null; }

	exec($execString.$file, $output);
	return $output;
}

function base64_encode_image($imagefile) {
	$filename = isFile($imagefile) ? htmlentities($imagefile) : '';
	if (empty($filename)) { return null; }

	$imgtype = array('jpg', 'gif', 'png', 'tbn');
	$filetype = pathinfo($filename, PATHINFO_EXTENSION);
	if ($filetype == 'tbn') { $filetype = 'png'; }
	if (in_array($filetype, $imgtype)){
		$imgbinary = fread(fopen($filename, "r"), filesize($filename));
	} else {
		return null;
	}

	return 'data:image/'.$filetype.';base64,'.base64_encode($imgbinary);
}

function thumbnailHash($input) {
	$chars = strtolower($input);
	$crc   = 0xffffffff;
	for ($ptr = 0; $ptr < strlen($chars); $ptr++) {
		$chr = ord($chars[$ptr]);
		$crc ^= $chr << 24;
		for ($i = 0; $i < 8; $i++) {
			if ($crc & 0x80000000) {
				$crc = ($crc << 1) ^ 0x04C11DB7;
			} else {
				$crc <<= 1;
			}
		}
	}

	//Formatting the output in an 8 character hex
	if ($crc >= 0) {
		$hash = sprintf((PHP_INT_SIZE !== 4) ? "%16s" : "%08s", sprintf("%x", sprintf("%u", $crc)));
	} else {
		$source = sprintf("%b", $crc);
		$hash = '';
		while ($source != '') {
			$digit  = substr($source, -4);
			$hash   = dechex(bindec($digit)).$hash;
			$source = substr($source, 0, -4);
		}
	}

	return (PHP_INT_SIZE !== 4) ? substr($hash, 8) : $hash;
}

function mapSambaDirs($path, $DIRMAP = null) {
	if (empty($DIRMAP)) {
		$DIRMAP = isset($GLOBALS['DIRMAP']) ? $GLOBALS['DIRMAP'] : array();
	}

	for ($d = 0; $d < count($DIRMAP); $d++) {
		$src = trim($DIRMAP[$d][0]);
		$mnt = trim($DIRMAP[$d][1]);

		if (!empty($src) && !empty($mnt)) {
			$path = str_replace($src, $mnt, $path);
		}
	}

	return $path;
}

function setSeenDelMovie($what, $checkFilme) {
	if (empty($what) || empty($checkFilme)) { return; }

	$dbh = getPDO();
	try {
		if (!$dbh->inTransaction()) { $dbh->beginTransaction(); }
		$SQL     = "SELECT idFile FROM movie WHERE idMovie IN (".implode(',', $checkFilme).")";

		$qryRes  = $dbh->query($SQL);
		$result  = $qryRes->fetchAll();
		$idFiles = array();
		for ($i = 0; $i < count($result); $i++) {
			if (!empty($result[$i]['idFile'])) {
				$idFiles[] = $result[$i]['idFile'];
			}
		}

		if (!empty($idFiles)) {
			switch ($what) {
				case 1: // unseen
					$dbh->exec("UPDATE files SET playcount=0 WHERE idFile IN (".implode(',', $idFiles).");");
					break;

				case 2: // seen
					$dbh->exec("UPDATE files SET playcount=1 WHERE idFile IN (".implode(',', $idFiles).");");
					break;

				case 3: // delete
					$dbh->exec("DELETE FROM movie WHERE idFile IN (".implode(',', $idFiles).");");
					$dbh->exec("DELETE FROM fileinfo WHERE idFile IN (".implode(',', $idFiles).");");
					$dbh->exec("DELETE FROM files WHERE idFile IN (".implode(',', $idFiles).");");
					break;

				case 4: // clear streamdetails
					$dbh->exec("DELETE FROM streamdetails WHERE idFile IN (".implode(',', $idFiles).");");
					break;
			}

			clearMediaCache();
		}

		if ($dbh->inTransaction()) { $dbh->commit(); }

	} catch(Throwable $e) {
		if ($dbh->inTransaction()) { $dbh->rollBack(); }
		if (isAdmin()) { echo $e->getMessage(); }
	}
}

function clearMediaCache() { foreach ($_SESSION as $key => $value) { if ( startsWith($key, 'movies') || startsWith($key, 'episodes') || startsWith($key, 'param_') || startsWith($key, 'SSerien') || startsWith($key, 'LSerien') || startsWith($key, 'FSerien') || startsWith($key, 'idStream') || startsWith($key, 'MVid') || startsWith($key, 'covers') || startsWith($key, 'thumbs') || startsWith($key, 'lastMovie') || startsWith($key, 'paths') || startsWith($key, 'TvDbCache') ) { unset( $_SESSION[$key] ); } } $_SESSION['overrideFetch'] = 1; }
function startsWith($haystack, $needle) { return !strncmp($haystack, $needle, strlen($needle)); }

function resizeImg($SRC, $DST, $w, $h) {
	if (empty($SRC) && empty($orSRC)) { return; }
	if (substr($SRC, 0, strlen('image://')) == 'image://') { return; }

	$image = new SimpleImage();
	try {
		$image->load($SRC);
	} catch (Throwable $e) { }

	if ($image->isEmpty()) { return; }

	if ($w != null && $h != null) {
		$image->resize($w, $h);

	} else if ($w == null) {
		$image->resizeToHeight($h);

	} else if ($h == null) {
		$image->resizeToWidth($w);
	}

	$image->save($DST);
	unset($image);
}

function generateImg($SRC, $DST, $orSRC, $w, $h) {
	$pic = false;
	$cachedImgExist  = isFile($SRC);
	$resizedImgExist = isFile($DST);

	if ($resizedImgExist) {
		return $DST;
	}

	if ($cachedImgExist) {
		resizeImg($SRC, $DST, $w, $h);
		$pic = true;
	} else {
		if (!empty($DST) && strlen($DST) > 0 && !empty($orSRC)) {
			$orSRC = str_replace('<thumb>',  '', $orSRC);
			$orSRC = str_replace('</thumb>', '', $orSRC);
			resizeImg($orSRC, $DST, $w, $h);
			$pic = true;
		}
	}

	return ($pic ? $DST : null);
}

function getThumbnailDir() {
	return isset($GLOBALS['THUMBNAIL_DIR']) ? $GLOBALS['THUMBNAIL_DIR'] : './img/Thumbnails/';
}

function getTvShowThumb($file) {
	$pic = false;
	$crc = thumbnailHash($file);
	$cachedimg = getThumbnailDir().substr($crc, 0, 1)."/".$crc.".jpg";

	return isFile($cachedimg) ? $cachedimg : null;
}

function getActorThumb($actor, $URL, $newmode) {
	$crc = ( $newmode ? thumbnailHash($actor) : thumbnailHash('actor'.$actor) );
	$cachedimg = getThumbnailDir().substr($crc, 0, 1)."/".$crc.".jpg";

	$resizedfile = "./img/actors/actor_".$crc.".jpg";
	return generateImg($cachedimg, $resizedfile, $URL, null, 200);
}

function getFanart0($fanart, $fanartThumb) {
	$imgInfo = getimagesize($fanart);
	if ($imgInfo != null) {
		$fWidth = $imgInfo[0];
		$fHeight = $imgInfo[1];
		$fAR = $fWidth / $fHeight;

		$ftHeight = isset($GLOBALS['DETAILFANARTHEIGHT']) ? $GLOBALS['DETAILFANARTHEIGHT'] : 720;
		$ftWidth = round($ftHeight * $fAR);

		return generateImg($fanart, $fanartThumb, null, $ftWidth, $ftHeight);
	}

	return $fanart;
}

function getCover($SRC, $orSRC, $cacheDir, $subDir, $subName, $w, $h, $newmode) {
	$k1 = crc32($SRC);
	$k2 = crc32($orSRC);
	$CACHE_KEY = ($k1 == $k2 ? $k1 : $k1.'_'.$k2).'_'.$cacheDir.'_'.$subDir.'_'.$subName.'_'.$w.'_'.$h.'_'.($newmode ? 1 : 0);
	$CACHE_KEY = str_replace("__", "_", $CACHE_KEY);
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['covers'][$subName][$CACHE_KEY])) { return $_SESSION['covers'][$subName][$CACHE_KEY]; }

	$crc = thumbnailHash($SRC);
	$cachedimg = getThumbnailDir().($cacheDir != null ? $cacheDir : '').($cacheDir == null ? substr($crc, 0, 1) : '').'/'.$crc.'.jpg';

	$ftime = '';
	try {
		if (isFile($cachedimg)) {
			$ftime = '_'.filemtime($cachedimg);
		}
	} catch (Throwable $e) {
		$ftime = '';
	}

	$resizedfile = './img/'.$subDir.'/'.$crc.'-'.$subName.$ftime.'.jpg';
	$_SESSION['covers'][$subName][$CACHE_KEY] = generateImg($cachedimg, $resizedfile, $orSRC, $w, $h);
	return $_SESSION['covers'][$subName][$CACHE_KEY];
}

function getCoverThumb($SRC, $orSRC, $newmode) {
	return getCover($SRC, $orSRC, null, 'thumbs', 'thumb', null, 138, $newmode);
}

function getCoverMid($SRC, $orSRC, $newmode) {
	return getCover($SRC, $orSRC, null, 'covers', 'cover', 250, null, $newmode);
}

function getCoverBig($SRC, $orSRC, $newmode) {
	return getCover($SRC, $orSRC, null, 'coversbig', 'coverbig', 500, null, $newmode);
}

function getFanart($SRC, $orSRC, $newmode) {
	return getCover($SRC, $orSRC, 'Fanart', 'fanart', 'fanart', null, 720, $newmode);
}

function fetchArtCovers($existArtTable, $dbh = null) {
	if (!hasPowerfulCpu()) { return null; }
	if (!$existArtTable) { return array(); }
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['covers']['art'])) { return $_SESSION['covers']['art']; }

	$result = array();
	$SQL    = "SELECT media_id AS idMedia, media_type AS media, type, url FROM art WHERE url NOT NULL AND (media_type = 'movie' OR media_type = 'actor') AND (type = 'poster' OR type = 'thumb');";
	$res    = querySQL($SQL, false, $dbh);

	foreach($res as $row) {
		$idMedia = $row['idMedia'];
		$media   = $row['media'];
		$type    = $row['type'];
		$url     = $row['url'];

		if (($type != 'poster' && isset($result[$media][$idMedia])) || empty($url)) { continue; }
		$cover = ($media == 'movie' ? getCoverThumb($url, $url, true) : getActorThumb($url, $url, true));
		$result[$media][$idMedia]['cover'] = $cover;
		$result[$media][$idMedia]['type']  = $type;
	}

	$_SESSION['covers']['art'] = $result;
	return $result;
}

function fetchActorCovers($dbh = null) {
	if (!hasPowerfulCpu()) { return null; }
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['covers']['actors'])) { return $_SESSION['covers']['actors']; }

	$result = array();
	$SQL    = "SELECT B.".mapDBC('idMovie')." AS idMovie, A.".mapDBC('strActor')." AS strActor, B.".mapDBC('idActor')." AS idActor, A.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC('actorlinkmovie')." B, ".mapDBC('actors')." A WHERE A.".mapDBC('idActor')." = B.".mapDBC('idActor')." AND B.".mapDBC('iOrder')." = 0;";
	$res    = querySQL($SQL, false, $dbh);

	foreach($res as $row) {
		$idMovie = $row['idMovie'];
		$idActor = $row['idActor'];
		$artist  = $row['strActor'];
		$image   = $row['actorimage'];

		if (isset($result[$idMovie]['artist'])) { continue; }
		$result[$idMovie]['id']     = $idActor;
		$result[$idMovie]['artist'] = $artist;
		$result[$idMovie]['image']  = $image;
	}

	$_SESSION['covers']['actors'] = $result;
	return $result;
}

function fetchDirectorCovers($dbh = null) {
	if (!hasPowerfulCpu()) { return null; }
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['covers']['directors'])) { return $_SESSION['covers']['directors']; }

	$result = array();
	$SQL    = "SELECT B.".mapDBC('idMovie')." AS idMovie, A.".mapDBC('strActor')." AS strActor, B.".mapDBC('idDirector')." AS idDirector, A.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC('directorlinkmovie')." B, ".mapDBC('actors')." A WHERE B.".mapDBC('idDirector')." = A.".mapDBC('idActor').";";
	$res    = querySQL($SQL, false, $dbh);

	foreach($res as $row) {
		$idMovie     = $row['idMovie'];
		$idDirector  = $row['idDirector'];
		$artist      = $row['strActor'];
		$image       = $row['actorimage'];

		if (isset($result[$idMovie]['artist'])) { continue; }
		$result[$idMovie]['id']     = $idDirector;
		$result[$idMovie]['artist'] = $artist;
		$result[$idMovie]['image']  = $image;
	}

	$_SESSION['covers']['directors'] = $result;
	return $result;
}

function hasPowerfulCpu() {
	$result = false;
	if (isAdmin())
		$result = isset($GLOBALS['POWERFUL_CPU_ADMIN']) ? $GLOBALS['POWERFUL_CPU_ADMIN'] : $result;
	else
		$result = isset($GLOBALS['POWERFUL_CPU'])       ? $GLOBALS['POWERFUL_CPU']       : $result;
	return $result;
}

function fetchHighestMovieId() {
	if (!isset($_SESSION['lastMovie']['highestId'])) {
		$SQL = "SELECT idMovie FROM movie ORDER BY idMovie DESC LIMIT 0,1;";
		$res = fetchFromDB($SQL, false);
		$_SESSION['lastMovie']['highestId'] = $res['idMovie'];
	}
	return $_SESSION['lastMovie']['highestId'];
}

function checkLastHighest() {
	if (isDemo()) { return false; }
	$highestId   = fetchHighestMovieId();
	$lastHighest = isset($_SESSION['lastHighest']) ? $_SESSION['lastHighest'] : null;

	if (empty($highestId) || isset($_SESSION['lastMovie']['confirmed'])) { return false; }
	$_SESSION['lastMovie']['confirmed'] = true;
	return $highestId > $lastHighest;
}

function setLastHighest() {
	$_SESSION['lastHighest'] = fetchHighestMovieId();
	$_SESSION['lastMovie']['set'] = true;
	unset($_SESSION['lastMovie']['confirmed'], $_SESSION['lastMovie']['seen']);
}

function getNewAddedCount() {
	$newAddedCount = isset($GLOBALS['DEFAULT_NEW_ADDED']) ? $GLOBALS['DEFAULT_NEW_ADDED'] : 30;
	$newAddedCount = isset($_SESSION['newAddedCount'])    ? $_SESSION['newAddedCount']    : $newAddedCount;
	return $newAddedCount;
}

function getNavTitle() {
	return isset($GLOBALS['NAV_TITLE'])  ? $GLOBALS['NAV_TITLE']  : 'xbmcDB';
}

function getHtmlTitle() {
	return isset($GLOBALS['HTML_TITLE']) ? $GLOBALS['HTML_TITLE'] : 'XBMC Database';
}

function getSrcMarker($source) {
	$res = '';
	if (!isAdmin())
		return $res;
	switch($source) {
		case 1:
			$res = 'hdtv';
			break;
		case 2:
			$res = 'dvdrip';
			break;
		case 3:
		case 4:
			$res = 'webrip';
			break;
		case 5:
		case 6:
		case 7:
			$res = 'bdrip';
			break;
		default:
			$res = 'unknown';
	}

	return '<span class="source '.$res.'">|</span>';
}

function postNavBar() {
	fetchMediaCounts();
	$INVERSE = isset($GLOBALS['NAVBAR_INVERSE']) ? $GLOBALS['NAVBAR_INVERSE'] : false;
	echo "\t".'<div id="myNavbar" class="navbar'.($INVERSE ? ' navbar-inverse' : '').'" style="margin:-10px -15px 15px; position: fixed; width: 101%; z-index: 50; height:40px;"></div>'."\r\n";
}

function postNavBar_($isMain) {
	$isAdmin     = isAdmin();
	$saferSearch = null;

	$show                = isset($_SESSION['show'])        ? $_SESSION['show']        : 'filme';
	$country             = isset($_SESSION['country'])     ? $_SESSION['country']     : '';
	$mode                = isset($_SESSION['mode'])        ? $_SESSION['mode']        : 0;
	$sort                = isset($_SESSION['sort'])        ? $_SESSION['sort']        : 0;
	$newmode             = isset($_SESSION['newmode'])     ? $_SESSION['newmode']     : 0;
	$newsort             = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;
	$gallerymode         = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;
	$dbSearch            = isset($_SESSION['dbSearch'])    ? $_SESSION['dbSearch']    : null;
	$unseen              = isset($_SESSION['unseen'])      ? $_SESSION['unseen']      : 3;
	# $serienmode          = isset($_SESSION['serienmode'])  ? $_SESSION['serienmode']  : 0;

	$which               = isset($_SESSION['which'])       ? $_SESSION['which']       : '';
	$just                = isset($_SESSION['just'])        ? $_SESSION['just']        : '';
	$filter_name         = isset($_SESSION['name'])        ? $_SESSION['name']        : '';

	$countMVids          = isset($_SESSION['param_mvC'])   ? $_SESSION['param_mvC']   : 0;
	$countTVshows        = isset($_SESSION['param_tvC'])   ? $_SESSION['param_tvC']   : 0;
	# $countMovies         = isset($_SESSION['param_movC'])  ? $_SESSION['param_movC']  : 0;

	$TITLE               = isset($GLOBALS['NAV_TITLE'])           ? $GLOBALS['NAV_TITLE']           : 'xbmcDB';
	$INVERSE             = isset($GLOBALS['NAVBAR_INVERSE'])      ? $GLOBALS['NAVBAR_INVERSE']      : false;
	$SEARCH_ENABLED      = isset($GLOBALS['SEARCH_ENABLED'])      ? $GLOBALS['SEARCH_ENABLED']      : true;
	$CUTSENABLED         = isset($GLOBALS['CUTS_ENABLED'])        ? $GLOBALS['CUTS_ENABLED']        : true;
	$DREIDENABLED        = isset($GLOBALS['DREID_ENABLED'])       ? $GLOBALS['DREID_ENABLED']       : true;
	$ATMOSENABLED        = isset($GLOBALS['ATMOS_ENABLED'])       ? $GLOBALS['ATMOS_ENABLED']       : true;
	$XBMCCONTROL_ENABLED = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$CHOOSELANGUAGES     = isset($GLOBALS['CHOOSELANGUAGES'])     ? $GLOBALS['CHOOSELANGUAGES']     : false;
	$MUSICVIDS_ENABLED   = isset($GLOBALS['MUSICVIDS_ENABLED'])   ? $GLOBALS['MUSICVIDS_ENABLED']   : false;
	# $TVSHOW_GAL_ENABLED  = isset($GLOBALS['TVSHOW_GAL_ENABLED'])  ? $GLOBALS['TVSHOW_GAL_ENABLED']  : false;
	$countryLabel        = $CHOOSELANGUAGES ? 'language' : '';
	# $newAddedCount       = getNewAddedCount();

	$isMain              = $show == 'filme'  ? true : false;
	$isTvshow            = $show == 'serien' ? true : false;
	$isMVids             = $show == 'mvids'  ? true : false;

	$unsetParams  = '&dbSearch&which&just&sort';
	$unsetMode    = '&mode=1';
	$unsetCountry = '&country';

	$bs211 = ' padding-top:7px; height:18px !important;';

	$res = '';
	//--echo $res .= '<div class="navbar'.($INVERSE ? ' navbar-inverse' : '').'" style="margin:-10px -15px 15px; position: fixed; width: 101%; z-index: 50;">';
	$res .= '<div class="navbar-inner" style="height:30px;">';
	$res .= '<div class="container" style="margin:0 auto; width:auto; height:40px;">';

	$res .= '<a class="brand navBarBrand" href="#" onmouseover="closeNavs();" onfocus="this.blur();">'.$TITLE.'</a>';

	$res .= '<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>';
	$res .= '<div class="nav-collapse">';
	$res .= '<ul class="nav" style="padding-top:2px;">';
	$res .= '<li class="divider-vertical" style="height:36px;"></li>';

	if (!$isMain) {
		$res .= '<li'.($isMain ? ' class="active"' : '').'>';
		$res .= '<a tabindex="1" href="?show=filme'.($isMain ? '&unseen=3&newmode=0&gallerymode=0'.$unsetParams.$unsetMode.$unsetCountry : $unsetParams).'" onmouseover="closeNavs();" onclick="this.blur(); return checkForCheck();"'.($isMain ? ' class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">movies</a>';
		$res .= '</li>';
	}

	if (!empty($dbSearch)) {
		$saferSearch = trim(SQLite3::escapeString($dbSearch));
	}

	if ($isMain) {
		$selectedIs = 'all';
		if (!empty($saferSearch)) {
			$selectedIs = $saferSearch;
			$just = $newmode = $mode = $newsort = 0;
			$country = '';

		} else if ($newmode == 1 && !empty($sort)) {
			$newsort = 0;
			$newmode = 0;
			$selectedIs = '';
			unset( $_SESSION['newsort'] );

		} else if (!empty($country)) {
			$COUNTRIES = isset($GLOBALS['COUNTRIES']) ? $GLOBALS['COUNTRIES'] : array();
			for ($i = 0; $i < count($COUNTRIES); $i++) {
				$lang = $COUNTRIES[$i];
				if ($lang[0] == $country) {
					$countryLabel = $lang[1];
					break;
				}
			}
			$selectedIs = $countryLabel;

		} else if ($unseen == 1 && empty($just)) {
			$selectedIs = 'unseen';

		} else if (!empty($just) && !empty($filter_name)) {
			$selectedIs = $filter_name;

		} else if ($unseen == 0 && empty($just)) {
			$selectedIs = 'seen';

		} else if ($newmode && empty($just)) {
			$selectedIs = 'recently added';

		} else if ($mode == 3 && empty($just)) {
			$selectedIs = 'directors cut';

		} else if ($mode == 4 && empty($just)) {
			$selectedIs = 'extended cut';

		} else if ($mode == 5 && empty($just)) {
			$selectedIs = 'uncut';

		} else if ($mode == 6 && empty($just)) {
			$selectedIs = 'unrated';

		} else if ($mode == 7 && empty($just)) {
			$selectedIs = '3D';

		} else if ($mode == 8 && empty($just)) {
			$selectedIs = 'remastered';

		} else if ($mode == 9 && empty($just)) {
			$selectedIs = 'atmos';
		}

		$res .= '<li class="dropdown" role="menu" aria-labelledby="dLabel" id="dropOptions" onmouseover="openNav(\'#dropOptions\');">';
		#$res .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">'.$selectedIs.' <b class="caret"></b></a>';
		$res .= '<a tabindex="1" class="dropdown-toggle '.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'" style="font-weight:bold;'.($bs211).'" href="?show=filme'.($isMain ? '&unseen=3&newmode=0&gallerymode=0'.$unsetParams.$unsetMode.$unsetCountry : '').'" onmouseover="closeNavs();" onclick="this.blur(); return checkForCheck();">movies <b class="caret"></b></a>';
		$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';

		$res .= '<li class="dropdown-submenu">';
		$res .= '<a href="#" class="dropdown-toggle" data-toggle="dropdown" onclick="this.blur();" style="'.($bs211).'">'.(!$gallerymode ? 'list' : 'gallery').'</a>';
		$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';
		$res .= '<li><a href="?show='.$show.'&gallerymode=0" onclick="return checkForCheck();"'.($gallerymode ? '' : ' class="selectedItem"').'>list</a></li>';
		$res .= '<li><a href="?show='.$show.'&gallerymode=1" onclick="return checkForCheck();"'.($gallerymode ? ' class="selectedItem"' : '').'>gallery</a></li>';
		$res .= '</ul>';
		$res .= '</li>';

		$res .= '<li class="divider"></li>';

		#$all = ((!isset($unseen) || $unseen == 3) && $newmode != 1 && empty($just) && empty($mode) && empty($saferSearch) && empty($country) ? ' class="selectedItem"' : '');
		$all = ($selectedIs == 'all' ? ' class="selectedItem"' : '');
		$res .= '<li><a href="?show=filme&newmode=0&unseen=3'.$unsetParams.$unsetMode.$unsetCountry.'" onclick="return checkForCheck();"'.$all.'>all</a></li>';

		$res .= '<li class="dropdown-submenu">';
		$res .= '<a tabindex="-1" href="#"'.($newmode && empty($just) ? ' class="selectedItem"' : '').'>'.(!$newmode ? 'recently added' : ($newsort == 2 ? 'sort by id' : 'sort by date')).'</a>';
		$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';
		$res .= '<li><a tabindex="-1" href="?show=filme&newmode=1&newsort=2&unseen=3'.$unsetParams.$unsetMode.$unsetCountry.'" onclick="return checkForCheck();"'.($newmode && $newsort == 2 && empty($just) ? ' class="selectedItem"' : '').'>sort by id</a></li>';
		$res .= '<li><a tabindex="-1" href="?show=filme&newmode=1&newsort=1&unseen=3'.$unsetParams.$unsetMode.$unsetCountry.'" onclick="return checkForCheck();"'.($newmode && $newsort == 1 && empty($just) ? ' class="selectedItem"' : '').'>sort by date</a></li>';
		$res .= '</ul>';
		$res .= '</li>';

		if ($CHOOSELANGUAGES) {
			$COUNTRIES = isset($GLOBALS['COUNTRIES']) ? $GLOBALS['COUNTRIES'] : array();
			if (count($COUNTRIES) > 0) {
				//$countryLabel  = 'language';
				$international = false;
				if (!isset($mode) || $mode != 2) { $international = true; }

				$langMenu  = '<li class="dropdown-submenu">';
				$langMenu .= '<a tabindex="-1" href="#"'.(!empty($country) && $country != $COUNTRIES[0] ? ' class="selectedItem"' : '').' style="height:20px;">'.($countryLabel).'</a>';
				$langMenu .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';

				$menuCountry = 'international';
				$thisCountry = false;
				for ($i = 0; $i < count($COUNTRIES); $i++) {
					$lang = $COUNTRIES[$i];
					$thisCountry = ($country == $lang[0]);
					if ($thisCountry) { $menuCountry = $lang[1]; }
					$langMenu .= '<li><a tabindex="-1" href="?show=filme&mode=2&newmode=0&unseen=3&country='.$lang[0].$unsetParams.'" style="height:20px;" onclick="return checkForCheck();"'.($thisCountry ? ' class="selectedItem"' : '').'>'.$lang[1].'</a></li>';
				}

				$langMenu .= '</ul>';
				$langMenu .= '</li>';

				$langMenu = str_replace('[MENUCOUNTRY]', $menuCountry, $langMenu);
				$res .= $langMenu;
			}
		}

		if ($isAdmin) {
			$res .= '<li class="divider"></li>';
			$res .= '<li><a href="?show=filme'.$unsetMode.$unsetCountry.$unsetParams.'=pCount&unseen=0&newmode=0" onclick="return checkForCheck();"'.($sort == 'pCount' && empty($just) ? ' class="selectedItem"' : '').'>most seen</a></li>';
			$res .= '<li><a href="?show=filme&unseen=1&newmode=0'.$unsetMode.$unsetCountry.$unsetParams.'" onclick="return checkForCheck();"'.($unseen == 1 && empty($just) && $sort != 'pCount' ? ' class="selectedItem"' : '').'>unseen</a></li>';
			$res .= '<li><a href="?show=filme&unseen=0&newmode=0'.$unsetMode.$unsetCountry.$unsetParams.'" onclick="return checkForCheck();"'.($unseen == 0 && empty($just) && $sort != 'pCount' ? ' class="selectedItem"' : '').'>seen</a></li>';
		}

		if ($CUTSENABLED || $DREIDENABLED || $ATMOSENABLED) {
			$res .= '<li class="divider"></li>';
		}
		if ($ATMOSENABLED) {
			$res .= '<li><a href="?show=filme&mode=9'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 9 && empty($just) ? ' class="selectedItem"' : '').'>Atmos / DTS:X</a></li>';
		}
		if ($DREIDENABLED) {
			$res .= '<li><a href="?show=filme&mode=7'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 7 && empty($just) ? ' class="selectedItem"' : '').'>3D</a></li>';
		}
		if ($CUTSENABLED) {
			if ($ATMOSENABLED || $DREIDENABLED) {
				$res .= '<li class="divider"></li>';
			}

			$res .= '<li><a href="?show=filme&mode=3'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 3 && empty($just) ? ' class="selectedItem"' : '').'>directors cut</a></li>';
			$res .= '<li><a href="?show=filme&mode=4'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 4 && empty($just) ? ' class="selectedItem"' : '').'>extended cut</a></li>';
			$res .= '<li><a href="?show=filme&mode=5'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 5 && empty($just) ? ' class="selectedItem"' : '').'>uncut</a></li>';
			$res .= '<li><a href="?show=filme&mode=6'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 6 && empty($just) ? ' class="selectedItem"' : '').'>unrated</a></li>';
			$res .= '<li><a href="?show=filme&mode=8'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 8 && empty($just) ? ' class="selectedItem"' : '').'>remastered</a></li>';
		}

		$res .= '</ul>';
		$res .= '</li>';
	} //$isMain

/*
	if ($isMain || ($isTvshow && $TVSHOW_GAL_ENABLED)) {
		$res .= '<li class="dropdown" id="dropViewmode" onmouseover="openNav(\'#dropViewmode\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" onclick="this.blur();" style="font-weight:bold;'.($bs211).'">'.(!$gallerymode ? 'list' : 'gallery').' <b class="caret"></b></a>';
		$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';
		$res .= '<li><a href="?show='.$show.'&gallerymode=0" onclick="return checkForCheck();"'.($gallerymode ? '' : ' class="selectedItem"').'>list</a></li>';
		$res .= '<li><a href="?show='.$show.'&gallerymode=1" onclick="return checkForCheck();"'.($gallerymode ? ' class="selectedItem"' : '').'>gallery</a></li>';
		$res .= '</ul>';
		$res .= '</li>';
	}

	if ($SEARCH_ENABLED && $isMain) {
		$res .= createSearchSubmenu($isMain, $isTvshow, $gallerymode, $saferSearch, $bs211);
	}
*/

	if ($countTVshows > 0) {
		$res .= '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
		if ($isTvshow) {
			$res .= '<li class="dropdown" id="dropLatestEps" onmouseover="openNav(\'#dropLatestEps\');">';
			$res .= '<a tabindex="2" href="?show=serien&dbSearch" onmouseover="closeNavs();" onclick="this.blur(); return checkForCheck();" class="dropdown-toggle '.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'" style="font-weight:bold;'.($bs211).'" onfocus="openNav(\'#dropLatestEps\');">tv-shows <b class="caret"></b></a>';
			$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';
			$res .= createEpisodeSubmenu(fetchLastSerien());
			$res .= '</ul>';
			$res .= '</li>';
		} else {
			$res .= '<li style="font-weight:bold;">';
			$res .= '<a href="?show=serien" onmouseover="closeNavs();" onclick="return checkForCheck();" style="font-weight:bold;'.($bs211).'">tv-shows</a>';
			$res .= '</li>';
		}
	}

	if ($SEARCH_ENABLED) {
		$res .= '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
		$res .= createSearchSubmenu($isMain, $isTvshow, $gallerymode, $saferSearch, $bs211);
	}

	if ($MUSICVIDS_ENABLED && $countMVids > 0) {
		$res .= '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
		$res .= '<li'.($isMVids ? ' class="active"' : '').' style="font-weight:bold;">';
		$res .= '<a tabindex="51" href="?show=mvids" onmouseover="closeNavs();" onclick="this.blur(); return checkForCheck();"'.($isMVids ? ' class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">music-videos</a>';
		$res .= '</li>';
	}

	$res .= '</ul>'; //after this menu on right-side

	$xbmcRunning = $isAdmin && $XBMCCONTROL_ENABLED && xbmcRunning();
	$res .= '<ul class="nav pull-right" style="padding-top:2px;">';
	if ($isAdmin && $XBMCCONTROL_ENABLED) {
		if ($xbmcRunning != 0) {
		$playing = $xbmcRunning != 0 ? cleanedPlaying(xbmcGetNowPlaying())  : '';
		$state   = $xbmcRunning != 0 ? intval(xbmcGetPlayerstate()) : '';
		$state   = ($state == 1 ? 'playing' : ($state == 0 ? 'paused' : ''));
		$res .= '<span id="xbmControlWrap" style="float:left;">';
		$res .= '<li id="xbmControl" onmouseover="closeNavs();" style="cursor:default; height:35px;'.(empty($playing) ? ' display:none;' : '').'">';
			$res .= '<a id="xbmcPlayLink" class="navbar" onclick="playPause(); return false;" style="cursor:pointer; font-weight:bold; max-width:300px; width:300px; height:20px; float:left; padding:8px; margin:0; white-space:nowrap; overflow:hidden;">';
			$res .= '<span id="xbmcPlayerState_" style="color:'.($INVERSE ? 'white' : 'black').'; position:absolute; top:10px; font-weight:bold; left:-65px;"><span id="xbmcPlayerState">'.$state.'</span>: </span>';
			$res .= '<span id="xbmcPlayerFile" style="color:'.($INVERSE ? 'white' : 'black').'; top:0; position:relative; max-width:350px; width:350px; height:20px; left:-7px;">'.$playing.'</span>';
			$res .= '</a> ';
			$res .= '<a class="navbar" onclick="stopPlaying(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0;"><img src="./img/stop.png" style="width:24px; height:24px;" /></a>';
			$res .= '<a class="navbar" onclick="playNext(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0;"><img src="./img/next.png" style="width:24px; height:24px;" /></a>';
			$res .= '<a class="navbar" onclick="playPrev(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0;"><img src="./img/prev.png" style="width:24px; height:24px;" /></a>';
		$res .= '</li>';
		$res .= '</span>';

		$res .= '<li id="plaYTdivide" class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
		$res .= '<li id="plaYoutube_" style="font-weight:bold;">';
		$res .= '<span style="position:relative; top:3px;">';
		$res .= '<input id="plaYoutube" name="plaYoutube" class="search-query span2" style="margin:4px 5px; width:500px; height:23px; display:none;" type="text" placeholder="play youTube / vimeo" onfocus="this.select();" onkeydown="return playItemPrompt(this, event); return false;" onmouseover="focus(this);" />';
		$res .= '<img id="ytIcon" src="./img/yt.png" style="width:32px; height:32px;" />';
		$res .= '</span>';
		$res .= '</li>';
		}
	}
	$res .= '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';

	if ($isAdmin) {
		$saveOrderInDB = isset($GLOBALS['SAVE_ORDER_IN_DB']) ? $GLOBALS['SAVE_ORDER_IN_DB'] : false;
		$viewerPage    = $saveOrderInDB ? 'orderViewer2.php' : 'orderViewer.php';
		$msgs     = checkNotifications();
		$msgStr   = '';
		$selected = '';
		if ($msgs > 0) {
			$selected = ' selectedItem';
			$res .= '<span class="notification badge badge-important skewR radius03corner fancy_sets" href="'.$viewerPage.'" onmouseover="closeNavs();"><div class="notificationInner skewL">'.$msgs.'</div></span>';
			#$msgStr = '<span style="padding-left:15px; color:silver;"><sub>['.$msgs.']</sub></span>';
		}
		$res .= '<li class="dropdown" id="dropAdmin" onmouseover="openNav(\'#dropAdmin\');">';
		$res .= '<a tabindex="60" href="#" class="dropdown-toggle" onclick="this.blur();" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">admin <b class="caret"></b></a>';
		$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';

		$res .= '<li><a class="fancy_sets'.$selected.'" href="'.$viewerPage.'">Order Viewer'.$msgStr.'</a></li>';

		$USESETS = isset($GLOBALS['USESETS']) ? $GLOBALS['USESETS'] : true;
		if ($USESETS) {
			$res .= '<li><a class="fancy_sets" href="setEditor.php">Set Editor</a></li>';
		}

		$res .= '<li class="divider"></li>';
		if ($XBMCCONTROL_ENABLED) {
			if (xbmcRunning() != 0) {
				$res .= '<li><a href="" onclick="scanLib(); return false;">Scan Library</a></li>';
			}
			$res .= '<li><a href="" onclick="clearCache(); return false;">Clear cache</a></li>';
		}
		$res .= '<li class="divider"></li>';
		$res .= '<li><a class="fancy_msgbox" href="guestStarLinks.php">Import guest links</a></li>';
		$res .= '<li><a class="fancy_msgbox" href="dbEdit.php?act=fixRunTime">Fix runtime</a></li>';
		$res .= '<li class="divider"></li>';

		$res .= '<li><a class="fancy_logs" href="./loginPanel.php?which=2">Login-log</a></li>';
		$res .= '<li><a class="fancy_logs" href="./loginPanel.php?which=1">Refferer-log</a></li>';
		$res .= '<li><a class="fancy_blocks" href="./blacklistControl.php">Blacklist Control</a></li>';
		$res .= '<li><a class="fancy_logs" href="./_hash.php">Pass Generator</a></li>';

		/*
		$res .= '<li class="divider"></li>';
		$res .= '<li><a href="?show=export">DB-Export</a></li>';
		$res .= '<li><a href="?show=import">DB-Import</a></li>';
		$res .= '</li>';
		*/

		$NAS_CONTROL   = isset($GLOBALS['NAS_CONTROL'])    ? $GLOBALS['NAS_CONTROL']    : false;
		$MP3_EXPLORER  = isset($GLOBALS['MP3_EXPLORER'])   ? $GLOBALS['MP3_EXPLORER']   : false;
		$privateFolder = isset($GLOBALS['PRIVATE_FOLDER']) ? $GLOBALS['PRIVATE_FOLDER'] : null;
		$upgrLog       = isLinux() && isFile($privateFolder.'/upgradeLog.php') && isFile('./myScripts/logs/upgrade.log');
		if ($upgrLog || $NAS_CONTROL || $MP3_EXPLORER) {
			$res .= '<li class="divider"></li>';
		}
		if ($xbmcRunning && $MP3_EXPLORER && isFile('fExplorer.php')) {
			$res .= '<li><a href="?show=mpExp">MP3 Explorer</a></li>';
			#$res .= '<li><a class="fancy_explorer" href="?show=mpExp">MP3 Explorer</a></li>';
		}
		if ($NAS_CONTROL) {
			$res .= '<li><a class="fancy_iframe3" href="./nasControl.php">NAS Control</a></li>';
		}
		if ($upgrLog) {
			$res .= '<li><a class="fancy_logs" href="'.$privateFolder.'/upgradeLog.php">Upgrade-log</a></li>';
		}

		$res .= '</ul>';
		$res .= '</li>';

		$res .= '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
	} //if ($isAdmin)

	#if ($INVERSE)
	#$res .= '<li><a tabindex="69" onmouseover="closeNavs();" onclick="this.blur(); darkSideOfTheForce();" style="font-weight:bold;'.($bs211).'" href="#">dark side</a></li>';
	$res .= '<li><a tabindex="70" href="?show=logout" onmouseover="closeNavs();"'.(!$isMVids ? ' onclick="this.blur(); return checkForCheck();"' : '').' style="font-weight:bold;'.($bs211).'">logout</a></li>';

	$res .= '</ul>';
	$res .= '</div>';
	$res .= '</div></div>';
	//--$res .= '</div>'."\r\n\r\n";

	return $res;
} //navbar_

function createSearchSubmenu($isMain, $isTvshow, $gallerymode, $saferSearch, $bs211) {
	$INVERSE = isset($GLOBALS['NAVBAR_INVERSE']) ? $GLOBALS['NAVBAR_INVERSE'] : false;
	$res  = '<li class="dropdown" id="dropSearch" onmouseover="openNav(\'#dropSearch\');">';
	$res .= '<a tabindex="50" href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'" onclick="this.blur();" onfocus="openNav(\'#dropSearch\');">search <b class="caret"></b></a>';
	$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';
		$res .= '<li'.($isMain || empty($saferSearch) ? ' class="navbar-search"' : ' style="margin:0;"').'>';
		$res .= '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchDBfor" name="searchDBfor" placeholder="search..." onfocus="this.select();" onkeyup="return searchDbForString(this, event); return false;" onmouseover="focus(this);" '.(!empty($saferSearch) ? 'value="'.$saferSearch.'"' : '').'/>';
		$res .= '<a class="search-close"'.($isTvshow && !empty($saferSearch) ? 'style="top:9px; left:132px;"' : '').' onclick="return resetDbSearch();"><img src="./img/close.png" /></a>';
		$res .= '</li>';

		if ($isTvshow && !empty($saferSearch)) {
			$res .= createEpisodeSubmenu(fetchSearchSerien(strtolower($saferSearch)));
		}

		if (!$isTvshow) {
			$res .= '<li class="navbar-search" style="margin:0;">';
			$res .= '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchfor" name="searchfor" placeholder="filter..." onfocus="this.select();" onkeyup="searchForString(this, event); return false;" onmouseover="focus(this);"'.($gallerymode || !$isMain ? ' disabled' : '').' />';
			$res .= '<a class="search-close"'.($gallerymode || !$isMain ? ' style="cursor:not-allowed;"' : ' onclick="resetFilter();"').'><img src="./img/close.png" /></a>';
			$res .= '</li>';
		}
	$res .= '</ul>';
	$res .= '</li>';
	return $res;
}

function createEpisodeSubmenu($result) {
	$INVERSE = isset($GLOBALS['NAVBAR_INVERSE'])    ? $GLOBALS['NAVBAR_INVERSE']    : false;
	$LIMIT   = isset($GLOBALS['TVSHOW_MENU_LIMIT']) ? $GLOBALS['TVSHOW_MENU_LIMIT'] : 30;
	$isAdmin = isAdmin();
	$counter = 1;
	$tabIndx = 2;
	$res = '';

	foreach($result as $key => $show) {
		$lId = 'sub_'.str_replace(' ', '_', $key);
		$res .= '<li class="dropdown-submenu" id="'.$lId.'" style="cursor:default;">';
		$ttip = count($show) > 1 ? ' title="'.count($show).' episodes"' : '';
		$res .= '<a onfocus="openNav_(\'#'.$lId.'\', false);" tabindex="'.($tabIndx++).'"><span'.$ttip.'>'.$key.'</span></a>';
		$res .= '<ul class="dropdown-menu'.($INVERSE ? ' navbar-inverse' : '').'">';

		foreach($show as $row) {
			$idShow    = $row['idShow'];    $serie  = $row['serie'];  $season  = $row['season'];
			$idEpisode = $row['idEpisode']; $title  = $row['title'];  $episode = $row['episode'];
			$playCount = $row['playCount']; $rating = $row['rating']; $rating_ = $row['rating_'];
			$sCount    = $row['sCount'];
			$srCol = $isAdmin ? getSrcMarker($row['src']) : '';

			$season   = sprintf("%02d", $season);
			$episode  = sprintf("%02d", $episode);
			$epTrId   = 'iD'.$idShow.'.S'.$season;
			$noRating = emptyRating($rating);
			$noRating = $noRating && emptyRating($rating_);

			$SE = '<span class="dropdown-menu_epTitle"><b><sub>S'.$season.'.E'.$episode.$srCol.'</sub></b></span> ';
			$showTitle = '<span class="nOverflow flalleft" style="position:relative; left:-15px;'.($noRating ? ' font-style:italic;' : '').'">'.$SE.trimDoubles($title).'</span>';
			$chkImg = ($isAdmin && $playCount > 0 ? ' <span class="flalright mnuIcon"><img src="./img/check.png" class="icon24" title="watched" /></span>' : '');
			$res .= '<li _href="./detailEpisode.php?id='.$idEpisode.'" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'" onclick="loadLatestShowInfo(this, '.$idShow.', '.$idEpisode.', \''.$epTrId.'\', '.$sCount.'); return true;" onmouseover="toggleActive(this);" onmouseout="toggleDActive(this);" style="cursor:pointer;"><a tabindex="'.$tabIndx++.'" class="elem"><div style="height:20px;">'.$showTitle.'</div></a>'.$chkImg.'</li>';
		}

		$res .= '</ul>';
		$res .= '</li>';
		if ($LIMIT <= $counter++)
			break;
	}

	return $res;
}

function fetchMediaCounts() {
	$dbh = getPDO();
	if (!isset($_SESSION['param_mvC'])) {
		$_SESSION['param_mvC']  = fetchCount('musicvideo', $dbh);
	}
	if (!isset($_SESSION['param_tvC'])) {
		$_SESSION['param_tvC']  = fetchCount('tvshow', $dbh);
	}
}

function xbmcGetPlayerId() {
	$json_res = curlJson('"method": "Player.GetActivePlayers", "params":{}, "id":1');
	if (empty($json_res)) { return -1; }
	$res = json_decode($json_res);
	return (empty($res) || empty($res->{'result'})) ? -1 : intval($res->{'result'}[0]->{'playerid'});
}

function cleanedPlaying($playing) {
	$from = array('.mp3','.aac','.flac','.m4a','.mkv','.avi','.mp4','.flv');
	$to   = array(''     ,''    ,''    ,''    ,''    ,''    ,''    ,''    );
	return str_replace($from, $to, $playing);
}

function xbmcGetNowPlaying() {
	$pid = xbmcGetPlayerId();
	if ($pid < 0) { return null; }
	$json_res = curlJson('"method":"Player.GetItem", "params":{"playerid":'.$pid.'}, "id":1');
	if (empty($json_res)) { return null; }

	$res  = json_decode($json_res);
	return !empty($res) ? $res->{'result'}->{'item'}->{'label'} : 'unknown';
}

function xbmcGetPlayerstate() {
	$pid = xbmcGetPlayerId();
	if ($pid < 0) { return null; }
	$json_res = curlJson('"method":"Player.GetProperties", "params":{"playerid":'.$pid.', "properties":["speed"]}, "id":1');
	if (empty($json_res)) { return null; }

	$res = json_decode($json_res);
	return !empty($res) ? $res->{'result'}->{'speed'}.'' : null;
}

function xbmcPlayPause() {
	$pid = xbmcGetPlayerId();
	if ($pid < 0) { return null; }
	$json_res = curlJson('"method":"Player.PlayPause", "params":{"playerid":'.$pid.'}, "id":0');
	if (empty($json_res)) { return null; }
	else { return true; }
}

function xbmcPlayNext() { return xbmcPlayGo('next');     }
function xbmcPlayPrev() { return xbmcPlayGo('previous'); }
function xbmcPlayGo($direction) {
	$pid = xbmcGetPlayerId();
	if ($pid < 0) { return null; }
	$json_res = curlJson('"method":"Player.GoTo", "params":{"playerid":'.$pid.', "to": "'.$direction.'"}, "id":1');
	if (empty($json_res)) { return null; }
	else { return true; }
}

function xbmcStop() {
	$pid = xbmcGetPlayerId();
	if ($pid < 0) { return null; }
	$json_res = curlJson('"method":"Player.Stop", "params":{"playerid":'.$pid.'}, "id":0');
	if (empty($json_res)) { return null; }
	else { return true; }
}

function xbmcPlayFile($filename) {
	$json_res = curlJson('"method":"Player.Open", "params":{ "item":{ "file":"'.$filename.'" } }, "id":0');

	if (empty($json_res)) { return null; }
	else { return true; }
}

function xbmcScanLibrary() {
	$json_res = curlJson('"method":"VideoLibrary.Scan", "params":{}');
	if (empty($json_res)) { return null; }

	$res = json_decode($json_res);
	if (!empty($res) && $res->{'result'} == 'OK') { clearMediaCache(); }
}

function xbmcSendMsg($msg, $title = 'xbmcDB', $time = 1500) {
	if ($title == null) { $title = 'xbmcDB'; }
	$json_res = curlJson('"method": "GUI.ShowNotification", "params":{"title":"'.$title.'","message":"'.$msg.'","displaytime":'.$time.'}');
	if (empty($json_res)) { return null; }
	else { return true; }
}

function curlJson($method) {
	$xbmControl = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	if (!$xbmControl || empty($GLOBALS['JSON_USERNAME']) || empty($GLOBALS['JSON_PASSWORT']) || empty($GLOBALS['JSON_PORT'])) { return null; }
	if (xbmcRunning() == 0) { return null; }

	$json_username = $GLOBALS['JSON_USERNAME'];
	$json_passwort = $GLOBALS['JSON_PASSWORT'];
	$json_port     = $GLOBALS['JSON_PORT'];
	$json_url      = 'http://127.0.0.1:'.$json_port.'/jsonrpc';
	$json_string   = '{"jsonrpc":"2.0", '.$method.'}';

	$ch = curl_init($json_url);
	$options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERPWD        => $json_username.':'.$json_passwort,
		CURLOPT_HTTPHEADER     => array('content-type:application/json') ,
		CURLOPT_POSTFIELDS     => $json_string,
		CURLOPT_TIMEOUT        => getTimeOut(true)
	);

	curl_setopt_array($ch, $options);
	$json_res = curl_exec($ch);
	if (
	    empty($json_res) ||
	    substr_count($json_res, 'Failed to execute') > 0 ||
	    substr_count($json_res, 'Method not found')  > 0
	   ) { return null; }

	return $json_res;
}

function xbmcRunning() {
	if (!isAdmin() || !isLinux()) { return 0; }

	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['param_xbmcRunning'])) { return $_SESSION['param_xbmcRunning']; }
	exec('ps -ea | grep kodi | wc -l', $output);
	$res = intval(trim($output[0]));
	$_SESSION['param_xbmcRunning'] = $res;
	return $res;
}

function checkNotifications() {
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	$orders = isset($_SESSION['param_orders']) ? $_SESSION['param_orders'] : -1;

	$whichTable = 0;
	if (empty($whichTable) && existsOrderzTable()) { $whichTable = 1; }
	if (empty($whichTable) && existsOrdersTable()) { $whichTable = 2; }

	if (($orders < 0 || $overrideFetch) && !empty($whichTable)) {
		$tableName = ($whichTable == 1 ? 'orderz' : 'orders');
		$res = querySQL("SELECT COUNT(fresh) AS count FROM ".$tableName." WHERE fresh = 1;");
		$orders = 0;
		if (!empty($res)) {
			$row    = $res->fetch();
			$orders = $row['count'];
		}
		unset( $_SESSION['overrideFetch'] );
		$_SESSION['param_orders'] = $orders;
		return $orders;
	}
	return max($orders, 0);
}

function logc($val, $noadmin = false) { if (isAdmin() || $noadmin) { echo '<script type="text/javascript">console.log( \''.$val.'\' );</script>'."\r\n"; } }
function pre($val, $noadmin = false)  { if (isAdmin() || $noadmin) { echo '<pre>'.$val.'</pre>'."\r\n"; } }

function redirectPage($subPage = null, $redirect = false) {
	$hostname = getHostnameWPath().($subPage != null ? $subPage : '');

	if (!empty($_GET) || !empty($_POST)) { setSessionParams(); }
	if ($redirect) { header('Location:'.$hostname); }
	return $redirect;
}

function getHostnameWPath() {
	$phpSelf = getEscServer('PHP_SELF');
	return getHostnamee().dirname($phpSelf);
}

function getLocalhostWPath() {
	$phpSelf = getEscServer('PHP_SELF');
	return getLocalhost().dirname($phpSelf);
}

function setSessionParams($isAuth = false) {
	if (!isset( $_SESSION )) { return; }

	$mode          = getEscGet('mode');
	$unseen        = getEscGet('unseen');
	$show          = getEscGet('show');
	$sort          = getEscGet('sort');
	$idShow        = getEscGet('idShow');
	$ref           = getEscGet('ref');
	$newmode       = getEscGet('newmode');
	$country       = getEscGet('country');
	$gallerymode   = getEscGet('gallerymode');
	$which         = getEscGet('which');
	$name          = getEscGet('name');
	$just          = getEscGet('just');
	$newAddedCount = getEscGPost('newAddedCount');

	if (!empty( $mode          )) { unset($_SESSION['newmode']); $_SESSION['mode']    = $mode;   }
	if (!empty( $unseen        )) { unset($_SESSION['newmode']); $_SESSION['unseen']  = $unseen; }
	if (!empty( $show          )) { if($show != 'logout') { $_SESSION['show'] = $show; } }
	if (!empty( $sort          )) { $_SESSION['sort']          = $sort;          }
	if (!empty( $idShow        )) { $_SESSION['idShow']        = $idShow;        }
	if (!empty( $ref           )) { $_SESSION['reffer']        = $ref;           }
	if (!empty( $newmode       )) { $_SESSION['newmode']       = $newmode;       }
	if (!empty( $country       )) { $_SESSION['country']       = $country;       }
	if (!empty( $gallerymode   )) { $_SESSION['gallerymode']   = $gallerymode;   }
	if (!empty( $which         )) { $_SESSION['which']         = $which;         }
	if (!empty( $name          )) { $_SESSION['name']          = $name;          }
	if (!empty( $just          )) { $_SESSION['just']          = $just;          }
	if (!empty( $newAddedCount )) { $_SESSION['newAddedCount'] = $newAddedCount; }

	if (!$isAuth) {
		unset( $_SESSION['submit'], $_SESSION['export'] );

		if (!empty($_SESSION['which']))    { unset( $_SESSION['dbSearch'] ); }
		if (!empty($_SESSION['dbSearch'])) { unset( $_SESSION['which'], $_SESSION['just'] ); }

		foreach ($_POST as $key => $value)    {
			if ($key == 'passwort' || $key == 'username') { continue; }
			$_SESSION[$key] = SQLite3::escapeString($value);
		}
		foreach ($_GET  as $key => $value)    {
			if ($key == 'passwort' || $key == 'username') { continue; }
			if (isset($_GET['show']) &&
			   $_GET['show'] != 'logout') { $_SESSION[$key] = SQLite3::escapeString($value); }
		}
	}

	//unset($_GET, $_POST);
}

function restoreSession() {
	$orSession = $_SESSION; //save pre-login values
	$filename  = './sessions/'.$_SESSION['user'].'.log';
	if (isFile($filename)) {
		$sessionfile = fopen($filename, "r");
		$sessiondata = fread($sessionfile,  4096);
		fclose($sessionfile);
		session_decode($sessiondata);
	}
	$_SESSION = array_merge($_SESSION, $orSession); //override pre-login values

	/*
	$show = isset($_SESSION['show']) ? $_SESSION['show'] : null;
	if (!empty($show) && $show != 'filme' && $show != 'serien') {
		unset($_SESSION['show']);
	}
	*/
}

function storeSession() {
	$user = isset($_SESSION['user']) ? $_SESSION['user'] : null;
	if (empty($user)) { return; }
	setLastHighest();
	clearMediaCache();
	unset( $_SESSION['username'],   $_SESSION['user'],  $_SESSION['idGenre'],  $_SESSION['refferLogged'], $_SESSION['overrideFetch'],
	       $_SESSION['passwort'],   $_SESSION['gast'],  $_SESSION['idStream'], $_SESSION['tvShowParam'],  $_SESSION['OS'],
	       $_SESSION['angemeldet'], $_SESSION['demo'],  $_SESSION['thumbs'],   $_SESSION['existsTable'],  $_SESSION['DB_MAPPING'],
	       $_SESSION['private'],    $_SESSION['paths'], $_SESSION['covers'],   $_SESSION['reffer'],       $_SESSION['name'],
	       $_SESSION['dbName'],     $_SESSION['dbVer']

	     ); //remove values that should be determined at login
//	if (isset($_SESSION['show']) && ($_SESSION['show'] == 'airdate' || $_SESSION['show'] == 'details')) { unset($_SESSION['show']); }
	if (isset($_SESSION['show']) && ($_SESSION['show'] != 'filme' && $_SESSION['show'] != 'serien')) { unset($_SESSION['show']); }

	$sessionfile = fopen('./sessions/'.$user.'.log', 'w');
	fputs($sessionfile, session_encode());
	fclose($sessionfile);
}

function adminInfo($start, $show) {
	$adminInfo = isset($GLOBALS['ADMIN_INFO']) ? $GLOBALS['ADMIN_INFO'] : true;
	if (isAdmin() && $adminInfo && ($show == 'filme' || $show == 'serien' || $show == 'mvids')) {
		echo '<div class="bs-docs" id="adminInfo" style="z-index:10;" onmouseover="hideAdminInfo(true);" onmouseout="hideAdminInfo(false);">';

		if (isLinux()) {
			//cpu temp
			$filename = './myScripts/logs/cpuTemp_.log';
			if (isFile($filename)) {
				$log = file($filename);
				$cpu_ = explode(' ', $log[0]);
				echo getTempDiv($cpu_[0], 0);
				echo getTempDiv($cpu_[1], 1);
				echo '<br id="brr0" style="display:none;" />';
				
				echo getTempDiv($cpu_[2], 2);
				echo getTempDiv($cpu_[3], 3);
				echo '<br id="brr1" style="display:none;" />';
			}
			//cpu temp
		
			$ssdTemp = '';
			//hdparm
			$filename = './myScripts/logs/hdparm.log';
			if (isFile($filename)) {
				$log = file($filename);
				for ($i = 0; $i < count($log); $i++) {
				#for ($i = count($log); $i >= 0; $i--) {
					$line = explode(',', $log[$i]);
					$label = trim($line[0]);
					$state = trim($line[1]);

					if (empty($label)) { continue; }
					if ($label == '/ssd') {
						$ssdTemp = $state;
						continue;
					}

					$title = $state;
					if ($state != 'unknown')
						$title = ($state == 'standby' ? 'idle' : 'active');
					$tCol = (empty($state) ? '' : ($state == 'standby' || $state == 'unknown' ? ' label' :  ' label-success') );
					$tCol_='';
					if (empty($tCol) || $state == 'unknown')
						$tCol_='background:#bcbcbc; ';
					echo '<span id="hdp'.$i.'" style="'.$tCol_.'cursor:default; display:none; padding:5px 8px; margin-left:5px; margin-bottom:5px;" class="label'.$tCol.'" title="'.$title.'">'.$label.'</span>';
				}
				#echo '<br id="brr3" style="display:none;" />';
			}
			//hdparm

			//ssd temp
			if (!empty($ssdTemp)) {
				echo getTempDiv($ssdTemp, -1, 'ssd');
				echo '<br id="brr4" style="display:none;" />';
			}
			//ssd temp

			//cpu load
			unset($output);
			if (isFile('./myScripts/logs/cpuTemps.log'))
				exec("tail /var/spool/myScripts/logs/cpuTemps.log -n1 | cut -d ',' -f 4", $output);
			else
				exec("ps -eo pcpu | awk ' {cpu_load+=$1} END {print cpu_load}'", $output);

			echo getLoadDiv($output);
			//cpu load
		} //isLinux

		//time
		$end = round(microtime(true)-$start, 2);
		$eCol = '';
		if ($end >= 1)   { $eCol = ' label-important'; }
		if ($end <= 0.1) { $eCol = ' label-success'; }
		echo '<span id="spTime" style="cursor:default; display:none; padding:5px 8px;" class="label'.$eCol.'" title="loadtime">'.$end.'s</span>';
		//time

		echo '</div>';
	}
}

function getTempDiv($output, $core, $title = '') {
	if (!empty($output)) {
		$output = str_replace("\n", "", $output);
		$output = trim($output);
	}
	if (empty($output))
		return '';

	$privateFolder = isset($GLOBALS['PRIVATE_FOLDER']) ? $GLOBALS['PRIVATE_FOLDER'] : null;
	$CPU_TEMPS     = isset($GLOBALS['CPU_TEMPS'])      ? $GLOBALS['CPU_TEMPS']      : array(30,40,50);

	#$output = $output[0];
	$tCol = ' label-success';
	if ($output <= $CPU_TEMPS[0]) { $tCol = ' label-success';   }
	if ($output >= $CPU_TEMPS[1]) { $tCol = ' label-warning';   }
	if ($output >= $CPU_TEMPS[2]) { $tCol = ' label-important'; }
	$href = $core >= 0 && isFile($privateFolder.'/cpu.php') ? ' href="'.$privateFolder.'/cpu.php?load=0&interpolate=1"' : '';
	$label = $core >= 0 ? 'core '.($core+1) : $title;
	return '<span id="spTemp'.$core.'"'.$href.' style="cursor:default; display:none; padding:5px 8px; margin-left:5px; margin-bottom:5px;" class="label'.$tCol.(!empty($href) ? ' fancy_cpu' : '').'" title="'.$label.'">'.$output.'&deg;C</span>';
}

function getLoadDiv($output) {
	if (empty($output))
		return '';

	$privateFolder = isset($GLOBALS['PRIVATE_FOLDER']) ? $GLOBALS['PRIVATE_FOLDER'] : null;
	$CPU_LOADS     = isset($GLOBALS['CPU_LOADS'])      ? $GLOBALS['CPU_LOADS']      : array(33,33,66);

	$output = $output[0];
	$tCol = '';
	if ($output <  $CPU_LOADS[0]) { $tCol = ' label-success';   }
	if ($output >= $CPU_LOADS[1]) { $tCol = ' label-warning';   }
	if ($output >= $CPU_LOADS[2]) { $tCol = ' label-important'; }
	$href = isFile($privateFolder.'/cpu.php') ? ' href="'.$privateFolder.'/cpu.php?load=1&interpolate=0"' : null;
	return '<span id="spLoad"'.$href.' style="cursor:default; display:none; padding:5px 8px; margin-right:5px;" class="label'.$tCol.(!empty($href) ? ' fancy_cpu' : '').'" title="CPU load">'.$output.'%</span>';
}

function adminInfoJS() {
	$adminInfo = isset($GLOBALS['ADMIN_INFO']) ? $GLOBALS['ADMIN_INFO'] : true;
	if (isAdmin() && $adminInfo) {
		echo '<script type="text/javascript">'."\r\n";
		echo "$(document).ready(function() {\r\n";
		echo "\tvar info = document.getElementById('adminInfo');\r\n";
		echo "\tif (info != null) { $('.bs-docs').addClass('info'); }\r\n";
		echo "});\r\n";
		echo "\r\n";
		echo "function hideAdminInfo(show) {\r\n";
		$filename = './myScripts/logs/hdparm.log';
		if (isFile($filename)) {
			echo "\t$( '[id^=hdp]' ).toggle(show);\r\n";
		}

		echo "\t$( '[id^=sp]' ).toggle(show);\r\n";
		echo "\t$( '[id^=brr]' ).toggle(show);\r\n";
		echo "\t$( '#adminInfo.bs-docs' ).css('padding', show ? '35px 10px 14px' : '10px 10px 14px');\r\n";
		echo "}\r\n";
		echo '</script>'."\r\n";
	}
}

/** @noinspection PhpUnnecessaryStopStatementInspection */
function moveUploadedFile($prefix, $fileType) {
	if (isset($_POST[$prefix.'Upload']) && $_POST[$prefix.'Upload'] == 'senden' && !empty($_FILES['thefile'])) {
		unset($_SESSION[$prefix.'Error'], $_SESSION[$prefix.'File']);

		// Validate the uploaded file
		if($_FILES['thefile']['size'] === 0 || empty($_FILES['thefile']['tmp_name'])) {
			$_SESSION[$prefix.'Error'] = "<p>No file chosen!.</p>\r\n";
			return;

		} else if($_FILES['thefile']['size'] > 10485760) {
			$_SESSION[$prefix.'Error'] = "<p>Filesize exceeds limit.</p>\r\n";
			return;

		} else if($_FILES['thefile']['type'] != $fileType) {
			$_SESSION[$prefix.'Error'] = $_FILES['thefile']['type']."<p>Wrong filetype, File ist not a ".(strtolower($prefix) == 'vcf' ? 'VCF' : 'Excel')."-File!</p>\r\n";
			return;

		} else if($_FILES['thefile']['error'] !== UPLOAD_ERR_OK) {
			// There was a PHP error
			$_SESSION[$prefix.'Error'] = "<p>Error while uploading.</p>\r\n";
			return;

		} else { // OK - do the rest of the file handling here
			$phpSelf = getEscServer('PHP_SELF');
			$target_path = '/var/www'.dirname($phpSelf).'/uploads/';
			$target_path = $target_path.basename($_FILES['thefile']['name']);

			if (move_uploaded_file($_FILES['thefile']['tmp_name'], $target_path)) {
				//echo "The file ".basename( $_FILES['thefile']['name'])." has been uploaded!<br/>";
			} else {
				$_SESSION[$prefix.'Error'] = "An error occurred while uploading the file, please try again!<br/>";
				return;
			}

			$_SESSION[$prefix.'File'] = $target_path;
		}
	}
}

function isLocalHost() {
	$lhost = isset($GLOBALS['PC_NAME']) ? $GLOBALS['PC_NAME'] : null;
	if (empty($lhost)) { return false; }

	$host = gethostbyaddr( $_SERVER['REMOTE_ADDR'] );
	if (empty($host)) { return false; }

	return $host == $lhost;
}

function isAdmin() {
	return (isset($_SESSION['angemeldet']) && $_SESSION['angemeldet'] == true) ? 1 : 0;
}

function isGast() {
	return (isset($_SESSION['gast']) && $_SESSION['gast'] == true) ? 1 : 0;
}

function isDemo() {
	return (isset($_SESSION['demo']) && $_SESSION['demo'] == true) ? 1 : 0;
}

function isLogedIn() { return isLoggedIn(); }
function isLoggedIn() { return (isAdmin() || isGast() || isDemo() ? true : false); }

function checkOpenGuest() {
//-- deactivated --//
	$LOCALHOST = isset($GLOBALS['LOCALHOST'])          ? $GLOBALS['LOCALHOST']          : false;
	$OPEN_4_LH = isset($GLOBALS['OPEN_FOR_LOCALHOST']) ? $GLOBALS['OPEN_FOR_LOCALHOST'] : false;
	if (!$OPEN_4_LH) { return false; }
	else { $LOCALHOST = isLocalHost(); }

	$gast_users = $GLOBALS['GAST_USERS'];
	if ($LOCALHOST || count($gast_users) == 0 && !isAdmin()) {
		$_SESSION['demo'] = true;
		return true;
	}

	return false;
}

function getHttPre() {
	$https   = getEscServer('HTTPS');
	$isHTTPs = (isset($https) && $https == 'on');
	return 'http'.($isHTTPs ? 's' : '').'://';
}

function getHostnamee() {
	$httpHost = getEscServer('HTTP_HOST');
	$hostname = isset($httpHost) ? $httpHost : null;
	if (empty($hostname)) { header("HTTP/1.1 400 Bad Request"); exit; }
	return getHttPre().$hostname;
}

function expandIpv6($ip){
	$hex = unpack("H*hex", inet_pton($ip));
	return substr(preg_replace("/([A-f0-9]{4})/", "$1:", $hex['hex']), 0, -1);
}

function getHttpResponseCode($url) {
	$headers = get_headers($url);
	return explode(' ', $headers[0])[1];
}

function getLocalhost() {
	$hostname = '127.0.0.1';
	return getHttPre().$hostname;
}

function logRefferer($reffer = null) {
	if (isset($_SESSION['refferLogged'])) { return false; }
	$_SESSION['refferLogged'] = true;
	$exit = false;

	$LOCALHOST   = isset($GLOBALS['LOCALHOST'])   ? $GLOBALS['LOCALHOST']   : false;
	$HOMENETWORK = isset($GLOBALS['HOMENETWORK']) ? $GLOBALS['HOMENETWORK'] : false;

	if (!($LOCALHOST || $HOMENETWORK)) {
		$ip = getEscServer('REMOTE_ADDR');
		$host = gethostbyaddr($ip);

		$hostname = getEscServer('HTTP_HOST');
		if (empty($reffer)) {
			$reffer = getEscServer('HTTP_REFERER', '');
		}

		$datei = "./logs/reffer.php";

		$datum = strftime("%d.%m.%Y");
		$time = strftime("%X");
		if (!allowedRequest($reffer)) { $exit = true; }

		$input = $datum."|".$time."|".$ip."|".$host."|".$reffer."|".($exit ? 1 : 0)."\n";

		$fp = fopen($datei, "r");
		$eintraege = '';
		while(!feof($fp)) {
			$eintraege = fgets($fp, 1000);
			$input .= $eintraege;
		}
		fclose($fp);

		$input = str_replace('<? /*', '', $input);
		$input = str_replace('*/ ?>', '', $input);
		$input = str_replace("\n\n", "\n", $input);
		$input = '<? /*'."\n".$input.'*/ ?>';

		$fp = fopen($datei, "w+");
		fputs($fp, $input);
		fclose($fp);
	}
	return $exit;
}

function isWatchedAnyHiddenInMain($idShow) {
	$hidden = isset($GLOBALS['HIDE_WATCHED_ANY_EP_IN_MAIN']) ? $GLOBALS['HIDE_WATCHED_ANY_EP_IN_MAIN'] : null;
	if (empty($hidden)) { return false; }

	return isset($hidden[$idShow]);
}

function authTvDB() {
	$TVDB_API_KEY = isset($GLOBALS['TVDB_API_KEY']) ? $GLOBALS['TVDB_API_KEY'] : null;
	if (empty($TVDB_API_KEY)) { return null; }

	if (isset($_SESSION['TvDbCache']['JWT_TOKEN']))
		return $_SESSION['TvDbCache']['JWT_TOKEN'];

	$URL  = 'https://api.thetvdb.com/login';

	$opts = array('http' => array(
		'method'  => 'POST',
		'timeout' => getTimeOut(),
		'header'  => 'Content-Type: application/json'."\r\n".'Accept: application/json',
		'content' => '{"apikey": "'.$TVDB_API_KEY.'", "userkey": "", "username": ""}'
	));
	$context = stream_context_create($opts);
	$result  = null;
	$oldHandler = set_error_handler('handleError');
	try {
		$result  = file_get_contents($URL, false, $context);
		if (!empty($oldHandler)) { set_error_handler($oldHandler); }
	} catch (Throwable $e) {
		if (!empty($oldHandler)) { set_error_handler($oldHandler); }
		return null;
	}

	if (!empty($oldHandler)) { set_error_handler($oldHandler); }
	if ($result === FALSE || $result == null) {
		unset($_SESSION['TvDbCache']['JWT_TOKEN']);
		return null;
	}

	$res = json_decode($result);
	if (!empty($res))
		$_SESSION['TvDbCache']['JWT_TOKEN'] = $res->{'token'};
	else
		unset($_SESSION['TvDbCache']['JWT_TOKEN']);
	return $_SESSION['TvDbCache']['JWT_TOKEN'];
}

/** @noinspection PhpUnnecessaryLocalVariableInspection */
function createGEToptions() {
	$TOKEN = authTvDB();
	if (empty($TOKEN))
		return null;

	$opts = array('http' => array(
		'method'  => 'GET',
		'timeout' => getTimeOut(),
		'header'  => 'Accept:application/json'."\r\n".'Authorization:Bearer '.$TOKEN
	));

	return $opts;
}

function getBanner($URL) {
	$opts    = createGEToptions();
	$context = stream_context_create($opts);
	$result  = null;
	try {
		$result = file_get_contents($URL, false, $context);

	} catch (Throwable $e) {
		return null;
	}

	if ($result === FALSE) {
		return null;
	}

	return $result;
}

function getShowInfoJson($idTvDb) {
	if (empty($idTvDb) || $idTvDb < 0) { return null; }
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['TvDbCache']['showinfo'][$idTvDb]) && $overrideFetch == 0) {
		return unserialize($_SESSION['TvDbCache']['showinfo'][$idTvDb]);
	}

	$opts    = createGEToptions();
	$context = stream_context_create($opts);
	$result  = null;
	try {
		$URL = 'https://api.thetvdb.com/series/'.$idTvDb;
		$result = file_get_contents($URL, false, $context);

	} catch (Throwable $e) {
		return null;
	}

	if ($result === FALSE) {
		return null;
	}

	$_SESSION['TvDbCache']['showinfo'][$idTvDb] = serialize($result);
	return $result;
}

function getEpisodes($idTvDb, $page=1) {
	if (empty($idTvDb) || $idTvDb < 0) { return null; }
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['TvDbCache']['episodes'][$idTvDb][$page]) && $overrideFetch == 0) {
		return unserialize($_SESSION['TvDbCache']['episodes'][$idTvDb][$page]);
	}

	$opts = createGEToptions();
	$result = null;
	try {
		$URL = 'https://api.thetvdb.com/series/'.$idTvDb.'/episodes?page='.$page;
		//$resCode = getHttpResponseCode($URL);
		//if ($resCode != 200)
		//	return null;
		$context = stream_context_create($opts);
		$oldHandler = set_error_handler('handleError');
		$result = file_get_contents($URL, false, $context);

		if (!empty($oldHandler)) { set_error_handler($oldHandler); }

	} catch (Throwable $e) {
		return null;
	}
	if ($result === FALSE) {
        $_SESSION['TvDbCache']['episodes'][$idTvDb][$page] = null;
		return null;
	}

	$_SESSION['TvDbCache']['episodes'][$idTvDb][$page] = serialize($result);
	return $result;
}

function getShowInfo($idTvDb, $lastStaffel = null) {
	if (empty($idTvDb) || $idTvDb < 0) { return null; }
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['TvDbCache'][$idTvDb]) && $overrideFetch == 0) {
		return unserialize($_SESSION['TvDbCache'][$idTvDb]);
	}

	//$LANG         = isset($GLOBALS['TVDB_LANGUAGE']) ? $GLOBALS['TVDB_LANGUAGE'] : 'en';
	$TVDB_API_KEY = isset($GLOBALS['TVDB_API_KEY'])  ? $GLOBALS['TVDB_API_KEY']  : null;
	if (empty($TVDB_API_KEY)) { return null; }

	$count    = 1;
	$episodes = array();
	$hasNext  = true;
	$limit    = empty($lastStaffel) ? 10 : round($lastStaffel * 2);
	for ($page = 1; $page <= $limit; $page++) {
		$json = getEpisodes($idTvDb, $page);
		if (empty($json)) { break; } //# return null;

		$items = json_decode($json, true);
		if (isset($items['links']) && isset($items['links']['next']) && $page >= $items['links']['next']) {
			$hasNext = false;
		}
		$items = isset($items['data']) ? $items['data'] : null;

		$itemsCount = empty($items) ? 0 : count($items);
		for ($i = 0; $i < $itemsCount; $i++) {
			$item = $items[$i];

			$s = $item['airedSeason'];
			$e = $item['airedEpisodeNumber'];
			$episodes[$s][$e] = $item;
			$count++;
		}

		if (!$hasNext) {
			break;
		}
	}

	ksort($episodes);

	$status = null;
	$json = getShowInfoJson($idTvDb);
	if (!empty($json)) {
		$res = json_decode($json);
		$status = $res->{'data'}->{'status'};
		//$banner = $res->{'data'}->{'banner'};
	}
	if (isset($status)) { $episodes[0][0] = (trim($status) == 'Ended' ? 'e' : 'r'); }
	$episodes[0][1] = getLastEpisodeInfo($episodes, $lastStaffel); //last season/episode

	$_SESSION['TvDbCache'][$idTvDb] = serialize($episodes);
	return $episodes;
}

function urlCheck($url) {
	$s_link   = trimURL($url);
	$s_link   = str_replace('::', ':', $s_link);
	$address_ = explode (':',"$s_link");
	$address  = empty($address_[0]) ? $address_[1] : $address_[0];
	$churl    = @fsockopen($address, 80, $errno, $errstr, 5);

	return !$churl ? false : true;
}

function trimURL($url) {
	$url = str_replace('http://', '', $url);
	$url = str_replace('www.',    '', $url);
	if (strstr($url,'/')) { $url = substr($url, 0, strpos($url, '/')); }
	return $url;
}

function isFile($filename) {
	return !empty($filename) && file_exists($filename);
}

function getEpisodeInfo($episodes, $getSeason, $getEpisode) {
	if ($getSeason == -1 || $getEpisode == -1) { return null; }
	if (empty($episodes) || empty($episodes[$getSeason]) || empty($episodes[$getSeason][$getEpisode])) { return null; }

	return $episodes[$getSeason][$getEpisode];
}

function getLastEpisodeInfo($episodes, $lastStaffel = null) {
	$seas = count($episodes)-1;
	if (!isset($episodes[$seas]))
		$seas--;

	$epis = null;
	if ($lastStaffel != null) {
		for ($i = intval($seas); $i >= 0; $i--) {
			if (!isset($episodes[$i]))
				continue;

			$epis = count($episodes[$i]);
			if (!isset($episodes[$i][$epis])) {
				$epis--;
				if (!isset($episodes[$i][$epis]))
					continue;
			}

			$airedSeason = isset($episodes[$i][$epis]['airedSeason']) ? $airedSeason = $episodes[$i][$epis]['airedSeason'] : null;
			if (!empty($airedSeason) && intval($lastStaffel) == intval($airedSeason)) {
				#$seas = intval($episodes[$i][$epis]['airedSeason']);
				$seas = $i;
				break;
			}
		}
	}

	if ($epis == null)
		return null;

	#$epis = count($episodes[$seas])-1;
	if (isset($episodes[$seas]) && isset($episodes[$seas][$epis]))
		return array('ms' => $episodes[$seas][$epis]['airedSeason'], 'me' => $episodes[$seas][$epis]['airedEpisodeNumber']);
	return null;
}

function checkAirDate() {
	return isset($GLOBALS['CHECK_NEXT_AIRDATE']) ? $GLOBALS['CHECK_NEXT_AIRDATE'] : false;
}

function fetchAndUpdateAirdate($idShow, $dbh = null) {
	if (!checkAirDate()) { return; }
	if (empty($idShow) || $idShow < 0) { return; }

	$serien      = fetchSerien($GLOBALS['SerienSQL'], null);
	if (!is_object($serien)) { return; }
	$serie       = $serien->getSerie($idShow);
	$nextEpisode = getNextEpisode($serie);

	if (empty($nextEpisode)) { return; }
	$airDate     = getNextAirDate($nextEpisode);
	$last        = getLastEpisode($serie);

	//tvdb
	$season  = $nextEpisode['airedSeason'];
	$episode = $nextEpisode['airedEpisodeNumber'];

	if (empty($season) || empty($episode)) { return; }
	updateAirdateInDb($idShow, $season, $episode, $airDate, $last, $dbh);
	clearMediaCache();
}

function clearAirdateInDb($idShow, $dbh = null) {
	if (!checkAirDate()) { return; }
	$SQL = "DELETE FROM nextairdate WHERE idShow = ".$idShow.";";
	execSQL_($SQL, false, false, $dbh);
}

function updateAirdateInDb($idShow, $season, $episode, $airdate, $last = null, $dbh = null) {
	if (!checkAirDate()) { return; }
	if ($idShow == -1 || $season == -1 || $episode == -1 || empty($episode) || empty($airdate)) { return; }
	if (empty($last)) { $last = array('ms'=>null,'me'=>null); }
	$SQL = "REPLACE INTO nextairdate (idShow, season, episode, airdate, maxSeason, maxEpisode) VALUES ('".$idShow."', '".$season."', '".$episode."', '".strtotime($airdate)."', '".$last['ms']."', '".$last['me']."'".");";
	execSQL_($SQL, false, false, $dbh);
}

function fetchNextEpisodeFromDB($idShow, $dbh = null) {
	if (!checkAirDate() || $idShow == -1) { return null; }
	$eps = fetchNextEpisodesFromDB($dbh);
	return empty($eps) || !isset($eps[$idShow]) ? null : $eps[$idShow];
}

function fetchNextEpisodesFromDB($dbh = null) {
	if (!checkAirDate()) { return null; }

	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['param_nextEpisodes'])) { return $_SESSION['param_nextEpisodes']; }

	$SQL = "SELECT idShow, season, episode, airdate, maxSeason, maxEpisode FROM nextairdate;";
	$result = querySQL($SQL, false, $dbh);
	$res    = array();
	foreach($result as $row) {
		$res[$row['idShow']]['s']   = $row['season'];
		$res[$row['idShow']]['e']   = $row['episode'];
		$res[$row['idShow']]['ms']  = $row['maxSeason'];
		$res[$row['idShow']]['me']  = $row['maxEpisode'];
		$res[$row['idShow']]['air'] = $row['airdate'];
	}

	$_SESSION['param_nextEpisodes'] = $res;
	return $res;
}

function getLastEpisode($serie) {
	$running  = $serie->isRunning();
	if (!$running) { return null; }
	$idTvDb   = $serie->getIdTvdb();
	$lastStaffel = $serie->getLastStaffel();
	$episodes = getShowInfo($idTvDb, empty($lastStaffel) ? null : $lastStaffel->getStaffelNum());
	return $episodes[0][1];
}

function getNextEpisode($serie) {
	if (!checkAirDate()) { return null; }
	$idTvDb      = $serie->getIdTvdb();
	$stCount     = $serie->getStaffelCount();
	$running     = $serie->isRunning();
	$nextEpisode = null;

	if (!$running) { return null; }
	$staffel  = $serie->getLastStaffel();
	$episodes = getShowInfo($idTvDb, empty($staffel) ? null : $staffel->getStaffelNum());

	$epCount  = is_object($staffel) ? $staffel->getLastEpNum() : -1;
	if ($epCount < 0)
		return null;

	if ($staffel->getStaffelNum() > $stCount)
		$stCount = $staffel->getStaffelNum();

	$info = getEpisodeInfo($episodes, $stCount, $epCount+1);  //check next episode in given season
	if (empty($info)) {
		$info = getEpisodeInfo($episodes, $stCount+1, 1); //check first episode of next season
	}

	return !empty($info) ? $info : null;
}

function getNextAirDate($info) {
	return isset($info['firstAired']) ? $info['firstAired'] : null;
}

/** @noinspection PhpUnnecessaryLocalVariableInspection */
function getFormattedSE($sNum, $eNum, $epDelta = 0) {
	$pattern = isset($GLOBALS['EP_SEARCH_PATTERN'])      ? $GLOBALS['EP_SEARCH_PATTERN']      : 'S[season#]E[episode#]';
	$lZero   = isset($GLOBALS['EP_SEARCH_LEADING_ZERO']) ? $GLOBALS['EP_SEARCH_LEADING_ZERO'] : true;

	$eNum += $epDelta;
	if ($lZero) {
		$sNum = sprintf("%02d", $sNum);
		$eNum = sprintf("%02d", $eNum);
	}

	$res = $pattern;
	$res = str_replace('[season#]',  $sNum, $res);
	$res = str_replace('[episode#]', $eNum, $res);
	return $res;
}

function fixNameForSearch($name) {
	$name = str_replace("'",       "",    $name);
	$name = str_replace("(",       "",    $name);
	$name = str_replace(")",       "",    $name);
	$name = str_replace("&",       "and", $name);
	$name = str_replace("&#44; ",  " ",   $name);
	$name = str_replace(",",       "",    $name);
	if (substr_count($name, ', The') > 0)
		$name = 'The '.str_replace(", The",  "", $name);
	return $name;
}

function getGuests($guests) {
	if (empty($guests)) { return null; }
	$guests = str_replace(',',  ' / ', $guests);
	$ex = explode(" / ", $guests);
	$gs = array();
	foreach($ex as $g) {
		if (substr_count($g, 'Autor') > 0) { continue; }
		if (substr_count($g, 'Gast') == 0 && substr_count($g, 'Guest') == 0) { continue; }

		$g = str_replace('(Gast Star)',  '', $g);
		$g = str_replace('(Guest Star)', '', $g);
		$g = str_replace('(Gast)',  '', $g);
		$g = str_replace('(Guest)', '', $g);
		$g = str_replace('  ', ' ', $g);
		$g = trim($g);
		$gs[] = $g;
	}
	return $gs;
}

function getDateColor($airDate, $daysLeft) {
	$color = 'silver';
	if (isAdmin() && !empty($airDate)) {
		if (dateMissed($airDate))   { $color = 'red';       }
		else if ($daysLeft > -1) {
			if ($daysLeft <= 3) { $color = 'lightblue'; }
			if ($daysLeft <= 2) { $color = 'purple';    }
			if ($daysLeft <= 1) { $color = 'brown';     }
		}
	}
	return 'color:'.$color.';';
}

/** @noinspection PhpUnnecessaryLocalVariableInspection */
function getDateFontsize($daysLeft) {
	$fSize = ' font-size:8pt; font-weight:bold;';
	$fSize = ($daysLeft >= 1 ? ' font-size:7pt;' : $fSize);
	$fSize = ($daysLeft >= 2 ? ' font-size:6pt;' : $fSize);
	$fSize = ($daysLeft > 30 ? ' font-size:5pt;' : $fSize);
	$fSize = ($daysLeft > 60 ? ' font-size:4pt;' : $fSize);
	$fSize = ($daysLeft > 90 ? ' font-size:3pt;' : $fSize);
	return $fSize;
}

function getToday() {
	return strtotime(date('Y-m-d', strtotime('now')));
}

function dateMissed($given) {
	return (empty($given) ? false : daysLeft($given) <= -1);
}

function daysLeft($given) {
	if (empty($given))
		return 0;

	$oneDay = $GLOBALS['DAY_IN_SECONDS'];
	if (checkIfDateIsString($given))
		$given = strtotime($given);
	return round(($given - getToday()) / $oneDay);
}

function dayOfWeek($given) {
	return date('l', strtotime($given));
}

function dayOfWeekShort($given) {
	return date('D', strtotime($given));
}

function toEuropeanDateFormatWDay($given) {
	$eurDateFormat = isset($GLOBALS['EUROPEAN_DATE_FORMAT']) ? $GLOBALS['EUROPEAN_DATE_FORMAT'] : false;
	$strFormat = $eurDateFormat ? 'd.m.y \(D\)' : 'Y-m-d \(D\)';
	if (checkIfDateIsString($given)) {
		$given = strtotime($given);
	}
	return (empty($given) ? $given : date($strFormat, $given));
}

function toEuropeanDateFormat($given) {
	$eurDateFormat = isset($GLOBALS['EUROPEAN_DATE_FORMAT']) ? $GLOBALS['EUROPEAN_DATE_FORMAT'] : false;
	$strFormat = $eurDateFormat ? 'd.m.y' : 'Y-m-d';
	if (checkIfDateIsString($given)) {
		$given = strtotime($given);
	}
	return (empty($given) ? $given : date($strFormat, $given));
}

function addRlsDiffToDate($given) {
	if (empty($given)) { return null; }

	$rlsDiff = isset($GLOBALS['RLS_OFFSET_IN_DAYS']) ? $GLOBALS['RLS_OFFSET_IN_DAYS'] : 1;
	$oneDay  = $GLOBALS['DAY_IN_SECONDS'];
	$diff    = $rlsDiff * $oneDay;
	if (checkIfDateIsString($given))
		return date('Y-m-d', strtotime($given) + $diff);
	return $given + $diff;
}

function compareDates($given1, $given2) {
	if (empty($given1)) { return true; }
	if (empty($given2)) { return false;  }
	if (checkIfDateIsString($given1) && checkIfDateIsString($given2))
		return strtotime($given1) < strtotime($given2);
	return $given1 < $given2;
}

function checkIfDateIsString($given) {
	return intval($given).'' != $given.'';
}

function formatToDeNotation($str) {
	$res = number_format($str, 3, ',', '.');
	return substr($res, 0, strlen($res)-4);
}

function getPausedAt($timeAt) {
	$sec = $timeAt % 60;
	$min = $timeAt / 60 % 60;
	$hrs = floor($min/60);
	$hrs = round($hrs);
	return sprintf('%02d:%02d:%02d', $hrs, $min, $sec);
}

function convertSecondsToHM($seconds) {
	$result = array('hrs' => null, 'min' => null);
	if (!empty($seconds)) {
		$minutes = floor($seconds/60);
		$hours   = floor($minutes/60).':'.sprintf ("%02d", $minutes % 60).'\'';
		$result['hrs'] = $hours;
		$result['min'] = $minutes.'\'';
	}
	return $result;
}

function switchPronoms($medianame, $PRONOMS = null) {
	if (empty($PRONOMS)) {
		$PRONOMS = isset($GLOBALS['PRONOMS']) ? $GLOBALS['PRONOMS'] : null;
	}

	if (empty($PRONOMS)) { return $medianame; }
	$pr = strtolower(substr($medianame, 0, 4));
	for ($prs = 0; $prs < count($PRONOMS); $prs++) {
		if ($pr == $PRONOMS[$prs]) {
			$medianame = strtoupper(substr($medianame, 4, 1)).substr($medianame, 5, strlen($medianame)).', '.substr($medianame, 0, 3);
		}
	}

	return $medianame;
}

function formatRating($rating) {
	if (empty($rating) || substr($rating, 0, 1) == "0") {
		return NULL;
	}

	return sprintf('%2.1f', $rating);
}

function emptyRating($rating) {
	$rating = empty($rating) ? 0 : formatRating($rating);
	return $rating <= 0 || $rating > 10;
}

function workaroundMTime($img) {
	if (!isLinux()) { return null; }
	exec('stat -c %Y "'.$img.'"', $output);
	return $output != null && count($output) > 0 ? $output[0] : null;
}

function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {
	// error was suppressed with the @-operator
	/** @noinspection PhpUnhandledExceptionInspection */
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function is3d($filename) {
	return matchString('/\b\.3D\.\b/', $filename);
}

function isHDR($filename, $hdrType = null) {
	if (!empty($hdrType)) {
		return true;
	}

	#mediainfo --Inform="Video;%HDR_Format_Compatibility%"
	return matchString('/\bHDR|HDR10|HDR10P|DV\b/', $filename);
}

function isFake4K($filename) {
	return matchString('/F4KE/', $filename);
}

function isUpscaled($filename) {
	return matchString('/UPSCALED|REGRADED/', $filename);
}

function matchString($pattern, $string) {
	return preg_match_all($pattern, strtoupper($string)) > 0 ? true : false;
}

function getCountryMap() {
	$LANG = isset($GLOBALS['LANG']) ? $GLOBALS['LANG'] : 'EN';
	return COUNTRY_MAP[$LANG];
}

function postEditLanguage($str, $buildLink = true) {
	$chosenMap = getCountryMap();
	if (empty($chosenMap)) { return $str; }

	if ($str == 'GER') { $str = makeLangLink($str, 'GER', 'DEU', 'deu', $chosenMap[$str], $buildLink); }
	if ($str == 'GMH') { $str = makeLangLink($str, 'GMH', 'DEU', 'deu', $chosenMap[$str], $buildLink); }
	if ($str == 'DEU') { $str = makeLangLink($str, 'DEU', 'DEU', 'deu', $chosenMap[$str], $buildLink); }

	if ($str == 'ENG') { $str = makeLangLink($str, 'ENG', 'ENG', 'eng', $chosenMap[$str], $buildLink); }

	if ($str == 'TUR') { $str = makeLangLink($str, 'TUR', 'TUR', 'tur', $chosenMap[$str], $buildLink); }

	if ($str == 'FRE') { $str = makeLangLink($str, 'FRE', 'FRE', 'fre', $chosenMap[$str], $buildLink); }

	if ($str == 'ITA') { $str = makeLangLink($str, 'ITA', 'ITA', 'ita', $chosenMap[$str], $buildLink); }
	if ($str == 'SPA') { $str = makeLangLink($str, 'SPA', 'SPA', 'ita', $chosenMap[$str], $buildLink); }
	if ($str == 'POR') { $str = makeLangLink($str, 'POR', 'POR', 'ita', $chosenMap[$str], $buildLink); }

	if ($str == 'JPN') { $str = makeLangLink($str, 'JPN', 'JPN', 'jpn', $chosenMap[$str], $buildLink); }
	if ($str == 'CHI') { $str = makeLangLink($str, 'CHI', 'CHI', 'jpn', $chosenMap[$str], $buildLink); }
	if ($str == 'KOR') { $str = makeLangLink($str, 'KOR', 'KOR', 'jpn', $chosenMap[$str], $buildLink); }

	if ($str == 'POL') { $str = str_replace('POL', '<span title="'.$chosenMap[$str].'">POL</span>', $str); }

	return $str;
}

function makeLangLink($strSource, $strToReplace, $strReplaceToken, $strFilter, $strTitle, $buildLink = true) {
	$url = '?show=filme&mode=2&newmode=0&unseen=3&which=&just=&country=';
	return str_replace($strToReplace, '<span title="'.$strTitle.'">'.($buildLink ? '<a href="'.$url.$strFilter.'" target="_parent" class="detailLink">' : '').$strReplaceToken.($buildLink ? '</a>' : '').'</span>', $strSource);
}

function postEditHdrType($str) {
	return empty($str) ? null : str_replace('DOLBYVISION', 'DV', strtoupper($str));
}

/** @noinspection PhpUnnecessaryLocalVariableInspection */
function postEditVCodec($str) {
	$str = strtoupper($str);

	$str = str_replace('V_MPEGH/ISO/HEVC', 'x265', $str);
	$str = str_replace('HEVC',             'x265', $str);
	$str = str_replace('H.265',            'x265', $str);
	$str = str_replace('H265',             'x265', $str);
	$str = str_replace('X265',             'x265', $str);

	$str = str_replace('V_MPEG4/ISO/AVC',  'x264', $str);
	$str = str_replace('DAVC',             'x264', $str);
	$str = str_replace('AVC1',             'x264', $str);
	$str = str_replace('AVC',              'x264', $str);
	$str = str_replace('VSSH',             'x264', $str);
	$str = str_replace('H.264',            'x264', $str);
	$str = str_replace('H264',             'x264', $str);
	$str = str_replace('X264',             'x264', $str);

	$str = str_replace('XVID',             'Xvid', $str);

	$str = str_replace('DIVX 3 LOW',       'divX', $str);
	$str = str_replace('DIVX 4',           'divX', $str);
	$str = str_replace('DX50',             'divX', $str);
	$str = str_replace('DIV3',             'divX', $str);
	$str = str_replace('DIVX',             'divX', $str);

	$str = str_replace('MPEG1VIDEO',       'mpeg-1', $str);
	$str = str_replace('MPEG2VIDEO',       'mpeg-2', $str);
	$str = str_replace('MP4V',             'mpeg-4', $str);

	$str = str_replace('WVC1',             'VC-1', $str);
	$str = str_replace('WMV3',             'wmv3', $str);
	$str = str_replace('VP8',              'vp8',  $str);

	return $str;
}

function decodingPerf($str, $bit10 = false) {
	$str = strtoupper($str);

	if ($str == 'MPEG-1' || $str == 'MPEG-2' || $str == 'MPEG-4')
		return 1;

	if ($str == 'XVID' || $str == 'DIVX' || $str == 'WMV3')
		return 2;

	if ($str == 'X264' || $str == 'VC-1' || $str == 'VP8')
		return 3;

	if ($str == 'X265')
		return $bit10 ? 5 : 4;

	return 0;
}

function is4K($vRes) {
	return ($vRes[0] >= 3700 || $vRes[1] >= 2000);
}

function getResDesc($vRes) {
	if (empty($vRes)) {
		return '';
	}

	if ($vRes[0] >= 3700 || $vRes[1] >= 2000) {
		return '2160p'; //4k
	}
	if ($vRes[0] >= 2200) {
		return '1440p'; //2k
	}
	if ($vRes[0] >= 1800 || $vRes[1] >= 780) {
		return '1080p';
	}
	if ($vRes[0] >= 1200) {
		return '720p';
	}
	if ($vRes[1] >= 570) {
		return '576p';
	}
	return '480p';
}

function getResPerf($vRes, $hdr = false) {
	if ($hdr) {
		return 5;
	}

	if (empty($vRes)) {
		return 0;
	}

	if ($vRes[0] >= 2200) {
		return 4;
	}

	return 0;
}

function getFpsPerf($fps) {
	if (empty($fps) || $fps <= 30) {
		return 0;
	}

	return 5;
}

function atmosFlagPossibleToSet($codec) {
	return (
		$codec == 'EAC3'   ||
		$codec == 'A_EAC3' ||
		$codec == 'TRUEHD'
	);
}

function postEditACodec($codec, $atmosx = null) {
	$tipp = null;
	if (!empty($atmosx)) {
		switch ($codec) {
			case 'EAC3':
			case 'A_EAC3':
			case 'TRUEHD':
				$tipp = ($codec == 'TRUEHD' ? 'True-HD' : 'E-AC3').' with Dolby Atmos';
				$codec = 'Atmos';
				break;

			case 'DCA':
			case 'DTS':
			case 'DTSHD_MA':
			case 'DTSHD_HRA':
				$tipp = 'DTS - High Definition';
				$codec = 'DTS:X';
				break;
		}

	} else {

		switch ($codec) {
			case 'MP3FLOAT':
				$codec = 'MP3';
				break;
			case 'AAC':
				$tipp = 'Advanced Audio Coding';
				$codec = 'AAC';
				break;

			case 'EAC3':
			case 'A_EAC3':
				$tipp = 'Enhanced AC3';
				$codec = 'E-AC3';
				break;
			case 'TRUEHD':
				$tipp = 'True High Definition';
				$codec = 'True-HD';
				break;

			case 'DCA':
			case 'DTS':
				$codec = 'DTS';
				break;
			case 'DTSHD_MA':
				$tipp = '"DTS - High Definition (Master Audio)';
				$codec = 'DTS-HD MA';
				break;
			case 'DTSHD_HRA':
				$tipp = 'DTS - High Definition (High Resolution Audio)';
				$codec = 'DTS-HD HRA';
				break;
		}
	}

	$result  = '<span>';
	if (!empty($tipp)) {
		$result  = '<span title="'.$tipp.'">';
	}
	$result .= $codec.'</span>';

	return $result;
}

function postEditChannels($str) {
	return ($str >= 3) ? ($str-1).'.1' : $str.'.0';
}

function prepPlayFilename($filename) {
	if (empty($filename)) { return null; }

	$filename = addslashes($filename);
	$filename = str_replace("&", "_AND_", $filename);
	return encodeString($filename);
}

function trimDoubles($text) {
	return str_replace("''", "'", $text);
}

function pluralize($str, $val, $plr = 's', $format = null) {
	return (isset($format) ? sprintf("%02d", $val) : $val).' '.$str.($val > 1 ? $plr : '');
}

function correctFilename($file) {
	$ersetzen = array(
		'Ã¤' => 'ä',
		'Ã¶' => 'ö',
		'Ã¼' => 'ü',
		'ß'  => '%',
		' '  => '\ ',
		'['  => '\[',
		']'  => '\]',
		'('  => '\(',
		')'  => '\)',
		'&'  => '\&',
	);

	$file = strtr($file, $ersetzen);
	$file = mapSambaDirs($file);
	return $file;
}

function encodeString($text, $plain = false) {
	$text = str_replace("''", "'", $text);
	//return htmlspecialchars($text, ENT_QUOTES);

	if (!$plain) {
		$text = str_replace ("ä",  "&auml;",  $text);
		$text = str_replace ("Ä",  "&Auml;",  $text);
//		$text = str_replace ("Ü¤", "&auml;",  $text);
		$text = str_replace ("ö",  "&ouml;",  $text);
		$text = str_replace ("Ö",  "&Ouml;",  $text);
		$text = str_replace ("ü",  "&uuml;",  $text);
		$text = str_replace ("Ü",  "&Uuml;",  $text);
		$text = str_replace ("Ã",  "&Uuml;",  $text);
		$text = str_replace ("ß",  "&szlig;", $text);
		$text = str_replace ("'",  "&#039;",  $text);
		$text = str_replace ("'",  "&#039;",  $text);
	} else {
		$text = str_replace ("ç", "c", $text);
		$text = str_replace ("Ç", "C", $text);
		$text = str_replace ("ü", "u", $text);
		$text = str_replace ("Ü", "U", $text);
		$text = str_replace ("ö", "o", $text);
		$text = str_replace ("Ö", "O", $text);
		$text = str_replace ("ä", "a", $text);
		$text = str_replace ("Ä", "A", $text);
	}

	return $text;
}

function decodeString($text) {
	$text = str_replace("&#039;", "'", $text);
	//return htmlspecialchars_decode($text, ENT_QUOTES);

 	$text = str_replace ("&amp;",   "&", $text);
	$text = str_replace ("&auml;",  "ä", $text);
	$text = str_replace ("&Auml;",  "Ä", $text);
	$text = str_replace ("&ouml;",  "ö", $text);
	$text = str_replace ("&Ouml;",  "Ö", $text);
	$text = str_replace ("&uuml;",  "ü", $text);
	$text = str_replace ("&Uuml;",  "Ü", $text);
	$text = str_replace ("&szlig;", "ß", $text);
	return $text;
}

function shortenGenre($genre) {
	if ("Science Fiction" == $genre) {
		return "Sci-Fi";
	}

	return $genre;
}

function isBlacklisted($ipIs = null) {
	$blacklisted = restoreBlacklist();
	$ip          = empty($ipIs) ? getEscServer('REMOTE_ADDR') : $ipIs;
	$retryLimit  = isset($GLOBALS['BLACKLIST_RETRY_LIMIT']) ? $GLOBALS['BLACKLIST_RETRY_LIMIT'] : 4;

	if (!isset($blacklisted[$ip])) { return false; }
	if ($blacklisted[$ip]['count'] <= $retryLimit) { return false; }
	$date = $blacklisted[$ip]['date'];
	//30 minutes
	if (time()-intval($date) > 30*60) { return false; }

	return true;
}

function removeBlacklist($ipDel = null) {
	$blacklisted = restoreBlacklist();
	$ip          = empty($ipDel) ? getEscServer('REMOTE_ADDR') : $ipDel;

	unset( $blacklisted[$ip] );

	storeBlacklist($blacklisted);
}

function addBlacklist() {
	$blacklisted = restoreBlacklist();
	$ip          = getEscServer('REMOTE_ADDR');

	if (empty($blacklisted["$ip"])) {
		$blacklisted["$ip"] = array();
	}
	$blacklisted["$ip"]['date']  = time();
	$blacklisted["$ip"]['count'] = isset($blacklisted["$ip"]['count']) ? $blacklisted["$ip"]['count']+1 : 1;

	storeBlacklist($blacklisted);
}

function storeBlacklist($blacklisted) {
	$blFile = $GLOBALS['BLACKLIST_FILE'];
	$store  = serialize($blacklisted);
	$fp = fopen($blFile, "w+");
	fputs($fp, $store);
	fclose($fp);
}

function restoreBlacklist() {
	$blFile = $GLOBALS['BLACKLIST_FILE'];
	if (isFile($blFile)) {
		$read = '';
		$fp = fopen($blFile, "r");
		while(!feof($fp)) { $read = fgets($fp, 1000); }
		fclose($fp);

		$result = unserialize($read);
		if (!is_array($result)) {
			$result = array();
		}
		return $result;
	}

	return array();
}

function escapeArray($val) {
	if (!is_array($val)) { return trim(SQLite3::escapeString($val)); }
	else { for ($i = 0; $i < count($val); $i++) { $val[$i] = escapeArray($val[$i]); } return $val; }
}

function generateOnDemandCopyScript($idOrder) {
	$newLine   = isset($GLOBALS['newLine'])  ? $GLOBALS['newLine']  : '';
	$srcLetter = isset($GLOBALS['srcDrive']) ? $GLOBALS['srcDrive'] : '';
	$dstLetter = isset($GLOBALS['selDrive']) ? $GLOBALS['selDrive'] : '';
	$scriptCopyWin = isset($GLOBALS['COPYASSCRIPT_COPY_WIN']) ? $GLOBALS['COPYASSCRIPT_COPY_WIN']  : false;
	if (empty($srcLetter)) { $srcLetter = isset($GLOBALS['COPYASSCRIPT_COPY_FROM_LETTER']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM_LETTER'] : $srcLetter; }
	if (empty($dstLetter)) { $dstLetter = isset($GLOBALS['COPYASSCRIPT_COPY_TO_LETTER'])   ? $GLOBALS['COPYASSCRIPT_COPY_TO_LETTER']   : $dstLetter; }

	$user = fetchFromDB("SELECT user FROM orderz WHERE idOrder = ".$idOrder.";", false);
	$res  = querySQL("SELECT * FROM orderItemz WHERE idOrder = ".$idOrder." ORDER BY movieOrShow, idElement;");
	$idShows  = array();
	$idMovies = array();
	foreach($res as $row) {
		if ($row['movieOrShow'] == 0) { $idShows[] = $row['idElement']; }
		else { $idMovies[] = $row['idElement']; }
	}
	$idShows  = array_unique($idShows);
	$idMovies = array_unique($idMovies);
	$idShows  = implode(',', $idShows);
	$idMovies = implode(',', $idMovies);

	$shows  = empty($idShows)  ? null : getItemsForRequest($idShows,   true);
	$movies = empty($idMovies) ? null : getItemsForRequest($idMovies, false);

	$res  = $scriptCopyWin ? 'chcp 1252'.$newLine."\n" : '';
	$res .= 'rem for: '.$user['user']."\n\n";
	if (!empty($shows)) {
		$res .= doTheStuffTvShow($shows, true, false, $srcLetter, $dstLetter);
	}
	if (!empty($movies)) {
		$res .= doTheStuffMovie($movies, true, true, $srcLetter, $dstLetter);
	}

	$res .= "\n";
	return $res;
}

function getItemsForRequest($ids, $isShow) {
	$SQL = '';
	if ($isShow) {
		$SQL =  "SELECT strPath, c00 AS name FROM ".mapDBC('tvshowview')." WHERE idShow IN (".$ids.") ORDER BY name;";
	} else {
		$SQL =  "SELECT c00 AS filmname, B.strFilename AS filename, c.strPath AS path, d.filesize FROM movie a, files b, path c, fileinfo d ".
			"WHERE idMovie IN (".$ids.") AND A.idFile = B.idFile AND c.idPath = b.idPath AND a.idFile = d.idFile ".
			"ORDER BY filename;";
	}
	return querySQL($SQL, false);
}

function doTheStuffTvShow($result, $forOrder = false, $append = false, $srcLetter = '', $dstLetter = '') {
	$copyAsScriptEnabled = isset($GLOBALS['COPYASSCRIPT_ENABLED'])        ? $GLOBALS['COPYASSCRIPT_ENABLED']        : false;
	$copyAsScript        = isset($GLOBALS['copyAsScript'])                ? $GLOBALS['copyAsScript']                : false;
	$scriptCopyWin       = isset($GLOBALS['COPYASSCRIPT_COPY_WIN'])       ? $GLOBALS['COPYASSCRIPT_COPY_WIN']       : false;
	$scriptCopyFrom      = isset($GLOBALS['COPYASSCRIPT_COPY_FROM_SHOW']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM_SHOW'] : null;
	$tvShowDir           = isset($GLOBALS['TVSHOWDIR'])                   ? $GLOBALS['TVSHOWDIR']                   : $scriptCopyFrom;
	$scriptCopyTo        = isset($GLOBALS['COPYASSCRIPT_COPY_TO_SHOW'])   ? $GLOBALS['COPYASSCRIPT_COPY_TO_SHOW']   : '/mnt/hdd/';

	$scriptCopyFrom = $srcLetter.$scriptCopyFrom;
	$scriptCopyTo   = $dstLetter.$scriptCopyTo;

	$newLine = $forOrder ? "\n" : '<br />';
	$res = '';
	if (count($result) == 0)
		return $res;
	foreach($result as $row) {
		if ($forOrder || ($copyAsScript && $copyAsScriptEnabled)) {
			$path = $row['strPath'];
			$path = str_replace($tvShowDir, $scriptCopyFrom, $path);

			$newFoldername = '';
			if ($scriptCopyWin) {
				$newFoldername = str_replace($scriptCopyFrom, '', $path);
				if (substr($path, -1) == '/') {
					$path = substr($path, 0, strlen($path)-1);
				}

				$path = str_replace("/", "\\", $path);
				$newFoldername = str_replace("/", "\\", $newFoldername);

			} else {
				$newFoldername = str_replace($scriptCopyFrom, '', $path);
			}

			$path = $forOrder ? decodeString(encodeString($path)) : $path;

			$res .= $scriptCopyWin ? 'xcopy /S /J /Y ' : 'cp -r ';
			$res .= '"'.$path.'" "'.$scriptCopyTo.($scriptCopyWin ? $newFoldername : '').'"'.$newLine;
		} else {
			$name = $row['name'];
			$res .= $name.$newLine;
		}
	}

	return $res;
}

function doTheStuffMovie($result, $forOrder = false, $append = false, $srcLetter = '', $dstLetter = '') {
	$copyAsScriptEnabled = isset($GLOBALS['COPYASSCRIPT_ENABLED'])   ? $GLOBALS['COPYASSCRIPT_ENABLED']   : false;
	$copyAsScript        = isset($GLOBALS['copyAsScript'])           ? $GLOBALS['copyAsScript']           : false;
	$scriptCopyWin       = isset($GLOBALS['COPYASSCRIPT_COPY_WIN'])  ? $GLOBALS['COPYASSCRIPT_COPY_WIN']  : false;
	$scriptCopyFrom      = isset($GLOBALS['COPYASSCRIPT_COPY_FROM']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM'] : null;
	$scriptCopyTo        = isset($GLOBALS['COPYASSCRIPT_COPY_TO'])   ? $GLOBALS['COPYASSCRIPT_COPY_TO']   : '/mnt/hdd/';

	$scriptCopyFrom = $srcLetter.$scriptCopyFrom;
	$scriptCopyTo   = $dstLetter.$scriptCopyTo;

	$newLine = $forOrder ? "\n" : '<br />';
	$oldPath   = '';
	$totalsize = 0;
	$res = '';
	foreach($result as $row) {
		$path       = $row['path'];
		$filename   = $row['filename'];
		$filmname   = $row['filmname'];
		$size       = $row['filesize'];
		$totalsize += $size;

		$name = $forOrder ? decodeString(encodeString($filename)) : $filmname;

		$pos1 = strpos($name, 'smb://');
		$pos2 = strpos($name, 'stack:');
		if ($pos1 > -1 || $pos2 > -1) {
			$name = null;
		}

		if (!empty($name) && ($forOrder || !isAdmin())) {
			if ($forOrder || ($copyAsScript && $copyAsScriptEnabled)) {
				#$res .= getScriptString($path, $res, $formattedName);
				if (!empty($scriptCopyFrom)) {
					$path = $scriptCopyFrom;
				}

				$formattedName = $name;
				if (!$scriptCopyWin) {
					$formattedName = str_replace(" ", "\ ", $formattedName);
					$formattedName = str_replace("(", "\(", $formattedName);
					$formattedName = str_replace(")", "\)", $formattedName);
					$formattedName = str_replace("[", "\[", $formattedName);
					$formattedName = str_replace("]", "\]", $formattedName);

					$path = mapSambaDirs($path);
					$path = str_replace(' ', '\ ', $path);
				}

				$res .= $scriptCopyWin ? 'xcopy /S /J /Y ' : 'cp ';
				$res .= ($scriptCopyWin ? '"' : '').$path.$formattedName.($scriptCopyWin ? '"' : '').' '.$scriptCopyTo.' ';
				$res .= $newLine;
				$oldPath = $path;

			} else {
				$res .= $name.$newLine;
			}
		}
	}

	if (!$forOrder && !empty($totalsize)) {
		$s = _format_bytes($totalsize);
		$res .= $newLine.$newLine.'Total size: '.$s;
	}

	return $res;
}

function getIdOrder($dbh = null) {
	$GETID_SQL = 'SELECT idOrder FROM orderz ORDER BY idOrder DESC LIMIT 0, 1;';
	$row       = fetchFromDB($GETID_SQL, false, $dbh);
	$lastId    = $row['idOrder'];
	return $lastId+1;
}

function findUserOrder() {
	$saveOrderInDB = isset($GLOBALS['SAVE_ORDER_IN_DB']) ? $GLOBALS['SAVE_ORDER_IN_DB'] : false;
	if (isAdmin() || !$saveOrderInDB) {
		return null;
	}

	$user = $_SESSION['user'];
	$SQL  = "SELECT idOrder AS idOrder FROM orderz WHERE fresh=1 AND user='[USER]' ORDER BY idOrder DESC;";
	$SQL  = str_replace('[USER]', $user, $SQL);
	$res  = querySQL($SQL);
	$ids  = array();
	foreach($res as $row) {
		$ids[] = $row['idOrder'];
	}

	if (empty($ids) || count($ids) == 0) {
		return null;
	}

	$_SESSION['param_idOrder'] = $ids[0];
	$ids  = implode(',', $ids);
	$SQL  = "SELECT movieOrShow AS movie, idElement AS id FROM orderItemz WHERE idOrder IN (".$ids.");";
	$res  = querySQL($SQL);
	$result = array(0=>array(), 1=>array());
	foreach($res as $row) {
		$result[$row['movie']][$row['id']] = $row['id'];
	}

	return $result;
}

$dbConnection = getPD0();
function getPDO() { return $GLOBALS['dbConnection']; /* return DB_CONN::getConnection(); */ }
function getPD0() {
	$dbh = new PDO($GLOBALS['db_name']);
	try {
		/*** make it or break it ***/
		error_reporting(E_ALL);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbh->exec("PRAGMA foreign_keys = ON;");
	} catch(Throwable $e) { }
	return $dbh;
}

function getEscServer($key, $defVal = null) { return isset($_SERVER[$key]) ? htmlspecialchars($_SERVER[$key], ENT_QUOTES, 'UTF-8') : $defVal; }
function getEscGet($key, $defVal = null)    { return isset($_GET[$key])  ? escapeArray($_GET[$key])  : $defVal; }
function getEscPost($key, $defVal = null)   { return isset($_POST[$key]) ? escapeArray($_POST[$key]) : $defVal; }
function getEscGPost($key, $defVal = null)  {
	$res = getEscGet($key);
	if (isset($res)) { return $res; }
	$res = getEscPost($key);
	if (isset($res)) { return $res; }
	return $defVal;
}

?>
