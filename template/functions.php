<?php
include_once "./globals.php";
include_once "./template/config.php";
include_once "./template/SimpleImage.php";
include_once "./template/_SERIEN.php";
require_once "./rss_php.php";

function startSession()   { if (!isset($_SESSION)) { session_start(); } }
function allowedRequest() { return true; }
function getOS() {
	startSession();
	if (!isset($_SESSION['OS']) || isset($_SESSION['overrideFetch'])) { $_SESSION['OS'] = strtoupper(php_uname('s')); }
	return $_SESSION['OS'];
}

function isLinux() { return 'LINUX' == getOS(); }

function execSQL($SQL, $throw = true) {
	$dbh = getPDO();
	return execSQL_($dbh, $SQL, $throw);
}

function execSQL_($dbh, $SQL, $throw = true, $commitExtern = true) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		if (!$commitExtern && !$dbh->inTransaction()) {
			$dbh->beginTransaction();
		}

		$dbh->exec($SQL);

		if (!$commitExtern && $dbh->inTransaction()) {
			$dbh->commit();
		}

	} catch(PDOException $e) {
		if (!$commitExtern && $dbh->inTransaction()) {
			$dbh->rollBack();
		}
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function querySQL($SQL, $throw = true) {
	$dbh = getPDO();
	return querySQL_($dbh, $SQL, $throw);
}

function querySQL_($dbh, $SQL, $throw = true) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		return $dbh->query($SQL);

	} catch(PDOException $e) {
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function singleSQL($SQL, $throw = true) {
	$dbh = getPDO();
	return singleSQL_($dbh, $SQL, $throw);
}

function singleSQL_($dbh, $SQL, $throw = true) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		return $dbh->querySingle($SQL);

	} catch(PDOException $e) {
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function fetchFromDB($SQL, $throw = true) {
	$dbh = getPDO();
	return fetchFromDB_($dbh, $SQL, $throw);
}

function fetchFromDB_($dbh, $SQL, $throw = true) {
	if (empty($dbh)) { $dbh = getPDO(); }

	try {
		$result = $dbh->query($SQL);
		return $result->fetch();

	} catch(PDOException $e) {
		if ($throw || isAdmin()) { echo $e->getMessage(); }
	}
	return null;
}

function getTableNames() {
	$dbh = getPDO();
	try {
		$res    = $dbh->query("SELECT name FROM sqlite_master WHERE type='table' AND name IS NOT 'sqlite_sequence';");
		$result = array();
		foreach($res as $row) {
			$result[] = $row['name'];
		}

		sort($result);
		return $result;

	} catch(PDOException $e) {
		if (isAdmin()) { echo $e->getMessage(); }
	}
}

function getStreamDetails($idFile) {
	return querySQL("SELECT * FROM streamdetails WHERE (strAudioLanguage IS NOT NULL OR strVideoCodec IS NOT NULL OR strSubtitleLanguage IS NOT NULL) AND idFile = ".$idFile.";");
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
			$str = ucwords(strtolower(trim($rowG['strGenre'])));
			if (empty($str)) {
				continue;
			}

			$idGenre[$str][0] = $rowG['idGenre'];
			$idGenre[$str][1] = 0;
		}

		$_SESSION['idGenre'] = serialize($idGenre);
		unset( $_SESSION['overrideFetch'] );
	}

	return $idGenre;
}

function getResolution($dbh) {
	$idStream = array();
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['idStream']) && $overrideFetch == 0) {
		$idStream = unserialize($_SESSION['idStream']);

	} else {
		$SQL = "SELECT * FROM streamdetails WHERE iVideoWidth IS NOT NULL";
		$result = querySQL_($dbh, $SQL, false);
		foreach($result as $row) {
			$id = $row['idFile'];
			$idStream[$id][0] = $row['iVideoWidth'];
			$idStream[$id][1] = $row['iVideoHeight'];
		}

		$_SESSION['idStream'] = serialize($idStream);
		unset( $_SESSION['overrideFetch'] );
	}

	return $idStream;
}

function getWrapper() {
	return isset($GLOBALS['IMAGE_DELIVERY']) ? $GLOBALS['IMAGE_DELIVERY'] : null;
}

function wrapItUp($type, $id, $strFname, $sessionKey = null) {
	$dlvry = getWrapper();
	if (isset($dlvry) && $dlvry == 'wrapped' && !empty($strFname)) {
		$_SESSION['thumbs'][$type][$id] = $strFname;
	}
}

function getImageWrap($strFname, $id, $type, $size, $dlvry = null) {
	if (empty($dlvry)) { $dlvry = getWrapper(); }
	if ($dlvry == 'encoded') {
		if (!empty($strFname) && file_exists($strFname)) { return base64_encode_image($strFname); }
		$dlvry = 'wrapped';
	}
	if ($dlvry == 'wrapped') { return './?img='.$id.'&'.$type.(!empty($size) ? '='.$size : ''); }

	return $strFname;
}

function loadImage($imgURL, $localfile) {
	if (file_exists($localfile)) { return 0; }
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

function checkFileInfoTable($dbh = null) {
	$exist = existsTable('fileinfo', 'table', $dbh);
	if (!$exist) { execSQL_($dbh, "CREATE TABLE IF NOT EXISTS fileinfo (idFile INTEGER NOT NULL, filesize LONGINT, CONSTRAINT 'C01_idFile' UNIQUE (idFile), CONSTRAINT 'C02_idFile' FOREIGN KEY (idFile) REFERENCES files (idFile) ON DELETE CASCADE);", false); }
}

function checkMyEpisodeView($dbh = null) {
	$exist = existsTable('episodeviewMy', 'view', $dbh);
	if (!$exist) { execSQL_($dbh, "CREATE VIEW IF NOT EXISTS episodeviewMy AS SELECT episode.*,files.strFileName AS strFileName,path.idPath AS idPath,path.strPath AS strPath,files.playCount AS playCount,files.lastPlayed AS lastPlayed,tvshow.c00 AS strTitle,tvshow.c14 AS strStudio,tvshow.idShow AS idShow,tvshow.c05 AS premiered, tvshow.c13 AS mpaa, tvshow.c16 AS strShowPath FROM tvshow JOIN episode ON episode.idShow=tvshow.idShow JOIN files ON files.idFile=episode.idFile JOIN path ON files.idPath=path.idPath;", false); }
}

function checkTvshowRunningTable($dbh = null) {
	$exist = existsTable('tvshowrunning', 'table', $dbh);
	if (!$exist) { execSQL_($dbh, "CREATE TABLE IF NOT EXISTS tvshowrunning (idShow INTEGER NOT NULL, running INTEGER, CONSTRAINT 'C01_idShow' UNIQUE (idShow), CONSTRAINT 'C02_idShow' FOREIGN KEY (idShow) REFERENCES tvshow (idShow) ON DELETE CASCADE);", false); }
}

function checkNextAirDateTable($dbh = null) {
	$exist = existsTable('nextairdate', 'table', $dbh);
	if (!$exist) { execSQL_($dbh, "CREATE TABLE IF NOT EXISTS nextairdate (idShow INTEGER NOT NULL, season INTEGER, episode INTEGER, lastEpisode INTEGER, airdate LONGINT, CONSTRAINT 'C01_idShow' UNIQUE (idShow), CONSTRAINT 'C02_idShow' FOREIGN KEY (idShow) REFERENCES tvshow (idShow) ON DELETE CASCADE);", false); }
}

function existsOrderzTable($dbh = null) {
	$saveOrderInDB = isset($GLOBALS['SAVE_ORDER_IN_DB']) ? $GLOBALS['SAVE_ORDER_IN_DB'] : false;
	if (!$saveOrderInDB) { return false; }

	$exist = existsTable('orderz', 'table', $dbh);
	if (!$exist) {
		execSQL_($dbh, "CREATE TABLE IF NOT EXISTS orderz (idOrder INTEGER primary key, dateAdded INTEGER, user TEXT, fresh INTEGER, CONSTRAINT 'C01_idOrder' UNIQUE (idOrder));", false, false);
		execSQL_($dbh, "CREATE TABLE IF NOT EXISTS orderItemz (idOrder INTEGER, idElement INTEGER, movieOrShow INTEGER, CONSTRAINT 'C01_ids' UNIQUE (idOrder, idElement), CONSTRAINT 'C02_idOrder' FOREIGN KEY (idOrder) REFERENCES orderz (idOrder) ON DELETE CASCADE);", false, false);
		$exist = existsTable('orderz', 'table', $dbh);
	}
	return $exist;
}

function existsOrdersTable($dbh = null) {
	$exist = existsTable('orders', 'table', $dbh);
	if (!$exist) {
		execSQL_($dbh, "CREATE TABLE IF NOT EXISTS orders (strFilename TEXT primary key, dateAdded INTEGER, user TEXT, fresh INTEGER);", false, false);
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

	} catch(PDOException $e) { }

	return false;
}

function getIdOrder($dbh = null) {
	$GETID_SQL = 'SELECT idOrder FROM orderz ORDER BY idOrder DESC LIMIT 0, 1;';
	$row       = fetchFromDB_($dbh, $GETID_SQL, false);
	$lastId    = $row['idOrder'];
	$idFile    = $lastId + 1;

	return $idFile;
}

function checkFileMapTable($dbh) {
	$db_name = $GLOBALS['db_name'];
	if (substr($db_name, -5) == '60.db') {
		$dateAddedFound = false;
		$res = $dbh->query("PRAGMA TABLE_INFO('files');");
		foreach($res as $row) {
			if ($row[1] == 'dateAdded') {
				$dateAddedFound = true;
				break;
			}
		}

		if (!$dateAddedFound) {
			$dbh->exec("ALTER TABLE files ADD dateAdded text;");
		}
	}

	$exist = existsTable('filemap', 'table', $dbh);
	if (!$exist) {
		$dbh->exec("CREATE TABLE IF NOT EXISTS filemap( idFile integer primary key, strFilename text, dateAdded text, value longint );");
	}
}

function fetchPaths() {
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;

	$paths = array();
	if (isset($_SESSION['paths']) && $overrideFetch == 0) {
		$paths = unserialize($_SESSION['paths']);

	} else {
		$TVSHOWDIR = isset($GLOBALS['TVSHOWDIR']) ? $GLOBALS['TVSHOWDIR'] : '';
		$SQL = "SELECT idPath, strPath FROM path WHERE strPath like '%".$TVSHOWDIR."%' ORDER BY strPath ASC;";

		$dbh = getPDO();
		$res = querySQL_($dbh, $SQL, false);

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

function fetchFileSize($idFile, $path, $filename, $fsize, $dbh) {
	if ($fsize == null || $fsize == 0) {
		$stacked = (substr($filename, 0, 8) == "stack://");
		if ($stacked) {
			$fsize = getStackedFilesize($filename);
		} else {
			$fnam  = $path.$filename;
			$fsize = getFilesize($fnam);
		}

		if (empty($fsize)) { return 0; }

		$dbhIsNull = ($dbh == null);
		try {
			if ($dbhIsNull) { $dbh = getPDO(); }

			$sqli = "REPLACE INTO fileinfo (idFile, filesize) VALUES(".$idFile.", ".$fsize.");";
			if ($dbhIsNull && !$dbh->inTransaction()) { $dbh->beginTransaction(); }

			$dbh->exec($sqli);

			if ($dbhIsNull && $dbh->inTransaction()) { $dbh->commit(); }

			clearMediaCache();

		} catch(PDOException $e) {
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

	$ersetzen = array(
		'Ã¤' => 'ä',
		'Ã¶' => 'ö',
		'Ã¼' => 'ü',
		'ß' => '%',
		' ' => '\ ',
		'[' => '\[',
		']' => '\]',
		'(' => '\(',
		')' => '\)',
		'&' => '\&',
		);

	$file = strtr($file, $ersetzen);
	$execString = 'stat -c %s '.$file;
	exec($execString, $output);
	if ($output != null && count($output) > 0) {
		return trim($output[0]);
	}

	return null;
}

function getCreation($file) {
	if (!isLinux()) { return null; }

	$ersetzen = array(
		'Ã¤' => 'ä',
		'Ã¶' => 'ö',
		'Ã¼' => 'ü',
		'ß' => '%',
		' ' => '\ ',
		'[' => '\[',
		']' => '\]',
		'(' => '\(',
		')' => '\)',
		);

	$file = strtr($file, $ersetzen);
	$file = mapSambaDirs($file);

	$execString = 'stat -c %y '.$file;
	exec($execString, $output);
	if ($output != null && count($output) > 0) {
		return substr(trim($output[0]), 0, 19);
	}

	return null;
}

function base64_encode_image($imagefile) {
	$filename = file_exists($imagefile) ? htmlentities($imagefile) : '';
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
		for ((int) $i = 0; $i < 8; $i++) {
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
		for ($i = 0; $i < count($checkFilme); $i++) {
			$id = $checkFilme[$i];

			if ($id != null) {
				$sql = "SELECT idFile FROM movie WHERE idMovie = '$id'";
				$result = $dbh->query($sql);
				$row    = $result->fetch();
				$idFile = $row['idFile'];

				if ($what == 1) { // unseen
					$dbh->exec("UPDATE files SET playcount=0 WHERE idFile = '$idFile'");

				} else if ($what == 2) { // seen
					$dbh->exec("UPDATE files SET playcount=1 WHERE idFile = '$idFile'");

				} else if ($what == 3) { // delete
					$dbh->exec("DELETE FROM movie WHERE idMovie = '$id'");
					$dbh->exec("DELETE FROM fileinfo WHERE idFile = '$idFile'");
					$dbh->exec("DELETE FROM files WHERE idFile = '$idFile'");
				}

				clearMediaCache();
			} // id is not null
		} // for each keys

		if ($dbh->inTransaction()) { $dbh->commit(); }

	} catch(PDOException $e) {
		if ($dbh->inTransaction()) { $dbh->rollBack(); }
		if (isAdmin()) { echo $e->getMessage(); }
	}
}

function clearMediaCache() { foreach ($_SESSION as $key => $value) { if ( startsWith($key, 'movies_') || startsWith($key, 'param_') || startsWith($key, 'SSerien') || startsWith($key, 'LSerien') || startsWith($key, 'FSerien') || startsWith($key, 'idStream') || startsWith($key, 'MVid') || startsWith($key, 'covers') || startsWith($key, 'thumbs') || startsWith($key, 'lastMovie') ) { unset( $_SESSION[$key] ); } } $_SESSION['overrideFetch'] = 1; }
function startsWith($haystack, $needle) { return !strncmp($haystack, $needle, strlen($needle)); }

function resizeImg($SRC, $DST, $w, $h) {
	if (empty($SRC) && empty($orSRC)) { return; }
	if (substr($SRC, 0, strlen('image://')) == 'image://') { return; }

	$image = new SimpleImage();
	try {
		$image->load($SRC);
	} catch (Exception $e) { }

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
	$cachedImgExist  = file_exists($SRC);
	$resizedImgExist = file_exists($DST);

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

	return ($pic == true ? $DST : null);
}

function getThumbnailDir() {
	return isset($GLOBALS['THUMBNAIL_DIR']) ? $GLOBALS['THUMBNAIL_DIR'] : './img/Thumbnails/';
}

function getTvShowThumb($file) {
	$pic = false;
	$crc = thumbnailHash($file);
	$cachedimg = getThumbnailDir().substr($crc, 0, 1)."/".$crc.".jpg";

	return file_exists($cachedimg) ? $cachedimg : null;
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
	$CACHE_KEY = $SRC.'_'.$orSRC.'_'.$cacheDir.'_'.$subDir.'_'.$subName.'_'.$w.'_'.$h.'_'.$newmode;
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['covers'][$CACHE_KEY])) { return $_SESSION['covers'][$CACHE_KEY]; }

	$crc = thumbnailHash($SRC);

	$cachedimg = '';
	if ($newmode) {
		$cachedimg = getThumbnailDir().substr($crc, 0, 1)."/".$crc.".jpg";
	} else {
		$cachedimg = getThumbnailDir()."Video/".substr($crc, 0, 1)."/".$crc.".tbn";
	}

	$cachedimg = getThumbnailDir().($cacheDir != null ? $cacheDir : '').($cacheDir == null ? substr($crc, 0, 1) : '').'/'.$crc.'.jpg';
	$cachedImgExist = file_exists($cachedimg);

	$ftime = '';
	try {
		if ($cachedImgExist) {
			$ftime = filemtime($cachedimg);
		}
	} catch (Exception $e) { }

	$resizedfile = './img/'.$subDir.'/'.$crc.'-'.$subName.(empty($ftime) ? '' : '_').$ftime.'.jpg';
	$_SESSION['covers'][$CACHE_KEY] = generateImg($cachedimg, $resizedfile, $orSRC, $w, $h);
	return $_SESSION['covers'][$CACHE_KEY];
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
	$res    = querySQL_($dbh, $SQL, false);

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
	$SQL    = "SELECT B.idMovie AS idMovie, A.strActor, B.idActor, A.strThumb AS actorimage FROM actorlinkmovie B, actors A WHERE A.idActor = B.idActor AND B.iOrder = 0;";
	$res    = querySQL_($dbh, $SQL, false);

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
	$SQL    = "SELECT B.idMovie AS idMovie, A.strActor, B.idDirector, A.strThumb AS actorimage FROM directorlinkmovie B, actors A WHERE B.idDirector = A.idActor;";
	$res    = querySQL_($dbh, $SQL, false);

	foreach($res as $row) {
		$idMovie     = $row['idMovie'];
		$idDirector  = $row['idDirector'];
		$artist      = $row['strActor'];
		$image   = $row['actorimage'];

		if (isset($result[$idMovie]['artist'])) { continue; }
		$result[$idMovie]['id']     = $idDirector;
		$result[$idMovie]['artist'] = $artist;
		$result[$idMovie]['image']  = $image;
	}

	$_SESSION['covers']['directors'] = $result;
	return $result;
}

function hasPowerfulCpu() {
	return isset($GLOBALS['POWERFUL_CPU']) ? $GLOBALS['POWERFUL_CPU'] : false;
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
}

function getNewAddedCount() {
	$newAddedCount = isset($GLOBALS['DEFAULT_NEW_ADDED']) ? $GLOBALS['DEFAULT_NEW_ADDED'] : 30;
	$newAddedCount = isset($_SESSION['newAddedCount'])    ? $_SESSION['newAddedCount']    : $newAddedCount;
	return $newAddedCount;
}

function postNavBar() {
	$INVERSE = isset($GLOBALS['NAVBAR_INVERSE']) ? $GLOBALS['NAVBAR_INVERSE'] : false;
	echo "\t".'<div id="myNavbar" class="navbar'.($INVERSE ? ' navbar-inverse' : '').'" style="margin:-10px -15px 15px; position: fixed; width: 101%; z-index: 50;"></div>'."\r\n";
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
	$serienmode          = isset($_SESSION['serienmode'])  ? $_SESSION['serienmode']  : 0;

	$which               = isset($_SESSION['which'])       ? $_SESSION['which']       : '';
	$just                = isset($_SESSION['just'])        ? $_SESSION['just']        : '';
	$filter_name         = isset($_SESSION['name'])        ? $_SESSION['name']        : '';

	$INVERSE             = isset($GLOBALS['NAVBAR_INVERSE'])      ? $GLOBALS['NAVBAR_INVERSE']      : false;
	$SEARCH_ENABLED      = isset($GLOBALS['SEARCH_ENABLED'])      ? $GLOBALS['SEARCH_ENABLED']      : true;
	$CUTSENABLED         = isset($GLOBALS['CUTS_ENABLED'])        ? $GLOBALS['CUTS_ENABLED']        : true;
	$DREIDENABLED        = isset($GLOBALS['DREID_ENABLED'])       ? $GLOBALS['DREID_ENABLED']       : true;
	$XBMCCONTROL_ENABLED = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$CHOOSELANGUAGES     = isset($GLOBALS['CHOOSELANGUAGES'])     ? $GLOBALS['CHOOSELANGUAGES']     : false;
	$MUSICVIDS_ENABLED   = isset($GLOBALS['MUSICVIDS_ENABLED'])   ? $GLOBALS['MUSICVIDS_ENABLED']   : false;
	$countryLabel        = $CHOOSELANGUAGES ? 'language' : '';
	$newAddedCount       = getNewAddedCount();

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
	$res .= '<div class="container" style="margin:0px auto; width:auto;">';

	$res .= '<a class="brand navBarBrand" href="#" onmouseover="closeNavs();">xbmcDB</a>';

	$res .= '<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>';
	$res .= '<div class="nav-collapse">';
	$res .= '<ul class="nav" style="padding-top:2px;">';
	$res .= '<li class="divider-vertical" style="height:36px;"></li>';
	$res .= '<li'.($isMain ? ' class="active"' : '').'>';
	$res .= '<a tabindex="1" href="?show=filme'.($isMain ? '&unseen=3&newmode=0&gallerymode=0'.$unsetParams.$unsetMode.$unsetCountry : '').'" onmouseover="closeNavs();" onclick="this.blur(); return checkForCheck();"'.($isMain ? ' class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">movies</a>';
	$res .= '</li>';

	if (!empty($dbSearch)) {
		$saferSearch = strtolower(trim(SQLite3::escapeString($dbSearch)));
	}

	if ($isMain) {
		$selectedIs = 'all';
		if (!empty($saferSearch)) {
			$selectedIs = $saferSearch;
			$just = $newmode = $mode = $newsort = 0;
			$country = '';

		} else if ($newmode == 1 && !empty($sort)) {
			$newsort = 0;
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
			$selectedIs = 'newly added';

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
		}

		$res .= '<li class="dropdown" role="menu" aria-labelledby="dLabel" id="dropOptions" onmouseover="openNav(\'#dropOptions\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">'.$selectedIs.' <b class="caret"></b></a>';
		$res .= '<ul class="dropdown-menu">';
		$all = ((!isset($unseen) || $unseen == 3) && $newmode != 1 && empty($just) && empty($mode) && empty($saferSearch) && empty($country) ? ' class="selectedItem"' : '');
		$res .= '<li><a href="?show=filme&newmode=0&unseen=3'.$unsetParams.$unsetMode.$unsetCountry.'" onclick="return checkForCheck();"'.$all.'>all</a></li>';

		$res .= '<li class="dropdown-submenu">';
		$res .= '<a tabindex="-1" href="#"'.($newmode && empty($just) ? ' class="selectedItem"' : '').'>'.(!$newmode ? 'newly added' : ($newsort == 2 ? 'sort by id' : 'sort by date')).'</a>';
		$res .= '<ul class="dropdown-menu">';
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
				$langMenu .= '<ul class="dropdown-menu">';

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

		if ($CUTSENABLED || $DREIDENABLED) {
			$res .= '<li class="divider"></li>';
		}

		if ($CUTSENABLED) {
			$res .= '<li><a href="?show=filme&mode=3'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 3 && empty($just) ? ' class="selectedItem"' : '').'>directors cut</a></li>';
			$res .= '<li><a href="?show=filme&mode=4'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 4 && empty($just) ? ' class="selectedItem"' : '').'>extended cut</a></li>';
			$res .= '<li><a href="?show=filme&mode=5'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 5 && empty($just) ? ' class="selectedItem"' : '').'>uncut</a></li>';
			$res .= '<li><a href="?show=filme&mode=6'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 6 && empty($just) ? ' class="selectedItem"' : '').'>unrated</a></li>';
		}

		if ($DREIDENABLED) {
			$res .= '<li><a href="?show=filme&mode=7'.$unsetParams.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 7 && empty($just) ? ' class="selectedItem"' : '').'>3D</a></li>';
		}

		if ($isAdmin) {
			$res .= '<li class="divider"></li>';
			$res .= '<li><a href="?show=filme&unseen=1&newmode=0'.$unsetParams.$unsetMode.'" onclick="return checkForCheck();"'.($unseen == 1 && empty($just) ? ' class="selectedItem"' : '').'>unseen</a></li>';
			$res .= '<li><a href="?show=filme&unseen=0&newmode=0'.$unsetParams.$unsetMode.'" onclick="return checkForCheck();"'.($unseen == 0 && empty($just) ? ' class="selectedItem"' : '').'>seen</a></li>';
		}
		$res .= '</ul>';
		$res .= '</li>';
	} //$isMain

	if ($isMain || $gallerymode && $isAdmin) { # || $isTvshow && $isAdmin) {
		$res .= '<li class="dropdown" id="dropViewmode" onmouseover="openNav(\'#dropViewmode\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" onclick="this.blur();" style="font-weight:bold;'.($bs211).'">'.(!$gallerymode ? 'list' : 'gallery').' <b class="caret"></b></a>';
		$res .= '<ul class="dropdown-menu">';
		$res .= '<li><a href="?show='.$show.'&gallerymode=0" onclick="return checkForCheck();"'.($gallerymode ? '' : ' class="selectedItem"').'>list</a></li>';
		$res .= '<li><a href="?show='.$show.'&gallerymode=1" onclick="return checkForCheck();"'.($gallerymode ? ' class="selectedItem"' : '').'>gallery</a></li>';
		$res .= '</ul>';
		$res .= '</li>';
	}

	if ($SEARCH_ENABLED && $isMain) {
		$res .= createSearchSubmenu($isMain, $isTvshow, $gallerymode, $saferSearch, $bs211);
	}

	$res .= '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
	if ($isTvshow) {
		$res .= '<li class="dropdown" id="dropLatestEps" onmouseover="openNav(\'#dropLatestEps\');">';
		$res .= '<a tabindex="2" href="?show=serien&dbSearch" onmouseover="closeNavs();" onclick="this.blur(); return checkForCheck();" class="dropdown-toggle '.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'" style="font-weight:bold;'.($bs211).'" onfocus="openNav(\'#dropLatestEps\');">tv-shows <b class="caret"></b></a>';
		$res .= '<ul class="dropdown-menu">';
		$res .= createEpisodeSubmenu(fetchLastSerien());
		$res .= '</ul>';
		$res .= '</li>';
	} else {
		$res .= '<li style="font-weight:bold;">';
		$res .= '<a href="?show=serien" onmouseover="closeNavs();" onclick="return checkForCheck();" style="font-weight:bold;'.($bs211).'">tv-shows</a>';
		$res .= '</li>';
	}

	if ($SEARCH_ENABLED && $isTvshow) {
		$res .= createSearchSubmenu($isMain, $isTvshow, $gallerymode, $saferSearch, $bs211);
	}

	if ($MUSICVIDS_ENABLED) {
		$res .= '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
		$res .= '<li'.($isMVids ? ' class="active"' : '').' style="font-weight:bold;">';
		$res .= '<a tabindex="51" href="?show=mvids" onmouseover="closeNavs();" onclick="this.blur(); return checkForCheck();"'.($isMVids ? ' class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">music-videos</a>';
		$res .= '</li>';
	}

	$res .= '</ul>'; //after this menu on right-side

	$res .= '<ul class="nav pull-right" style="padding-top:2px;">';
	if ($isAdmin && $XBMCCONTROL_ENABLED) {
		$run = xbmcRunning();
		if ($run != 0) {
		$playing = $run != 0 ? cleanedPlaying(xbmcGetNowPlaying())  : '';
		$state   = $run != 0 ? intval(xbmcGetPlayerstate()) : '';
		$state   = ($state == 1 ? 'playing' : ($state == 0 ? 'paused' : ''));
		$res .= '<span id="xbmControlWrap" style="float:left;">';
		$res .= '<li id="xbmControl" onmouseover="closeNavs();" style="cursor:default; height:35px;'.(empty($playing) ? ' display:none;' : '').'">';
			$res .= '<span id="xbmcPlayerState_" style="color:black; position:absolute; top:10px; font-weight:bold; left:-65px;"><span id="xbmcPlayerState">'.$state.'</span>: </span>';
			$res .= '<a id="xbmcPlayLink" class="navbar" onclick="playPause(); return false;" style="cursor:pointer; font-weight:bold; max-width:300px; width:300px; height:20px; float:left; padding:8px; margin:0px; white-space:nowrap; overflow:hidden;">';
			$res .= '<span id="xbmcPlayerFile" style="top:0px; position:relative; max-width:350px; width:350px; height:20px; left:-7px;">'.$playing.'</span>';
			$res .= '</a> ';
			$res .= '<a class="navbar" onclick="stopPlaying(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0px;"><img src="./img/stop.png" style="width:24px; height:24px;" /></a>';
			$res .= '<a class="navbar" onclick="playNext(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0px;"><img src="./img/next.png" style="width:24px; height:24px;" /></a>';
			$res .= '<a class="navbar" onclick="playPrev(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0px;"><img src="./img/prev.png" style="width:24px; height:24px;" /></a>';
		$res .= '</li>';
		$res .= '</span>';

		$res .= '<li id="plaYTdivide" class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
		$res .= '<li id="plaYoutube_" style="font-weight:bold;">';
		$res .= '<span style="position:relative; top:3px;">';
		$res .= '<input id="plaYoutube" name="plaYoutube" class="search-query span2" style="margin:4px 5px; width:200px; height:23px; display:none;" type="text" placeholder="play youTube / vimeo" onfocus="this.select();" onkeydown="return playItemPrompt(this, event); return false;" onmouseover="focus(this);" />';
		$res .= '<img id="ytIcon" src="./img/yt.png" style="width:32px; height:32px;" onmousmove=""; onmouseout=""; />';
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
		$res .= '<ul class="dropdown-menu">';
		if (file_exists('fExplorer.php')) {
			$res .= '<li><a class="fancy_explorer" href="fExplorer.php">File Explorer</a></li>';
			$res .= '<li class="divider"></li>';
		}

		$res .= '<li><a class="fancy_logs" href="./loginPanel.php?which=2">Login-log</a></li>';
		$res .= '<li><a class="fancy_logs" href="./loginPanel.php?which=1">Refferer-log</a></li>';
		$res .= '<li><a class="fancy_blocks" href="./blacklistControl.php">Blacklist Control</a></li>';
		$res .= '<li class="divider"></li>';

		$res .= '<li><a class="fancy_sets'.$selected.'" href="'.$viewerPage.'">Order Viewer'.$msgStr.'</a></li>';

		$USESETS = isset($GLOBALS['USESETS']) ? $GLOBALS['USESETS'] : true;
		if ($USESETS) {
			$res .= '<li><a class="fancy_sets" href="setEditor.php">Set Editor</a></li>';
		}

		$res .= '<li class="divider"></li>';
		$res .= '<li><a href="" onclick="clearCache(); return false;">Clear cache</a></li>';
		if (xbmcRunning() != 0) {
			$res .= '<li><a href="" onclick="scanLib(); return false;">Scan Library</a></li>';
		}
		$res .= '<li><a class="fancy_msgbox" href="guestStarLinks.php">Import guest links</a></li>';

		/*
		$res .= '<li class="divider"></li>';
		$res .= '<li><a href="?show=export">DB-Export</a></li>';
		$res .= '<li><a href="?show=import">DB-Import</a></li>';
		$res .= '</li>';
		*/

		$privateFolder = isset($GLOBALS['PRIVATE_FOLDER']) ? $GLOBALS['PRIVATE_FOLDER'] : null;
		$upgrLog = isLinux() && file_exists($privateFolder.'/upgradeLog.php') && file_exists('./myScripts/upgrade.log');
		$NAS_CONTROL = isset($GLOBALS['NAS_CONTROL']) ? $GLOBALS['NAS_CONTROL'] : false;
		if ($NAS_CONTROL || $upgrLog) {
			$res .= '<li class="divider"></li>';
		}
		if ($NAS_CONTROL) {
			$res .= '<li><a class="fancy_iframe3" href="./nasControl.php">NAS Control</a></li>';
		}
		if ($upgrLog) {
			$res .= '<li><a class="fancy_logs" href="'.$privateFolder.'/upgradeLog.php">Upgrade-Log</a></li>';
		}

		$res .= '</ul>';
		$res .= '</li>';
	}
	$res .= '<li><a tabindex="70" href="?show=logout" onmouseover="closeNavs();"'.(!$isMVids ? ' onclick="this.blur(); return checkForCheck();"' : '').' style="font-weight:bold;'.($bs211).'">logout</a></li>';

	$res .= '</ul>';
	$res .= '</div>';
	$res .= '</div></div>';
	//--$res .= '</div>'."\r\n\r\n";

	return $res;
} //navbar_

function createSearchSubmenu($isMain, $isTvshow, $gallerymode, $saferSearch, $bs211) {
	$res = '';
	$res .= '<li class="dropdown" id="dropSearch" onmouseover="openNav(\'#dropSearch\');">';
	$res .= '<a tabindex="50" href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'" onclick="this.blur();" onfocus="openNav(\'#dropSearch\');">search <b class="caret"></b></a>';
	$res .= '<ul class="dropdown-menu">';
		$res .= '<li'.($isMain || empty($saferSearch) ? ' class="navbar-search"' : ' style="margin:0px;"').'>';
		$res .= '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchDBfor" name="searchDBfor" placeholder="search..." onfocus="this.select();" onkeyup="return searchDbForString(this, event); return false;" onmouseover="focus(this);" '.(!empty($saferSearch) ? 'value="'.$saferSearch.'"' : '').'/>';
		$res .= '<a class="search-close"'.($isTvshow && !empty($saferSearch) ? 'style="top:9px; left:132px;"' : '').'onclick="return resetDbSearch();"><img src="./img/gnome_close.png" /></a>';
		$res .= '</li>';

		if ($isTvshow && !empty($saferSearch)) {
			$res .= createEpisodeSubmenu(fetchSearchSerien($saferSearch));
		}

		if (!$isTvshow) {
			$res .= '<li class="navbar-search" style="margin:0px;">';
			$res .= '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchfor" name="searchfor" placeholder="filter..." onfocus="this.select();" onkeyup="searchForString(this, event); return false;" onmouseover="focus(this);"'.($gallerymode || !$isMain ? ' disabled' : '').' />';
			$res .= '<a class="search-close"'.($gallerymode || !$isMain ? ' style="cursor:not-allowed;"' : ' onclick="resetFilter();"').'><img src="./img/gnome_close.png" /></a>';
			$res .= '</li>';
		}
	$res .= '</ul>';
	$res .= '</li>';
	return $res;
}

function createEpisodeSubmenu($result) {
	$isAdmin = isAdmin();
	$counter = 2;
	$res = '';
	foreach($result as $key => $show) {
		$lId = 'sub_'.str_replace(' ', '_', $key);
		$res .= '<li class="dropdown-submenu" id="'.$lId.'">';
		$count = count($show);
		$res .= '<a onfocus="openNav_(\'#'.$lId.'\', false);" tabindex="'.($counter++).'" _href="#" style="cursor:pointer;"><span title="'.$count.' episode'.($count > 1 ? 's' : '').'">'.$key.'</span></a>';
		$res .= '<ul class="dropdown-menu">';

		foreach($show as $row) {
			$idShow    = $row['idShow'];    $serie = $row['serie'];   $season  = $row['season'];
			$idEpisode = $row['idEpisode']; $title = $row['title'];   $episode = $row['episode'];
			$playCount = $row['playCount']; $rating = $row['rating']; $sCount = $row['sCount'];

			if ($season  < 10) { $season  = '0'.$season;  }
			if ($episode < 10) { $episode = '0'.$episode; }
			$epTrId = 'iD'.$idShow.'.S'.$season;
			$noRating = empty($rating) || substr($rating, 0, 1) == '0';

			$SE = '<span style="padding-right:10px; color:silver;"><b><sub>S'.$season.'.E'.$episode.'</sub></b></span> ';
			$showTitle = '<span class="nOverflow flalleft" style="position:relative; left:-15px;'.($noRating ? ' font-style:italic;' : '').'">'.$SE.trimDoubles($title).'</span>';
			$chkImg = ($isAdmin && $playCount > 0 ? ' <span class="flalright mnuIcon"><img src="./img/check.png" class="icon24" title="watched" /></span>' : '');
			$res .= '<li _href="./detailEpisode.php?id='.$idEpisode.'" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'" onclick="loadLatestShowInfo(this, '.$idShow.', '.$idEpisode.', \''.$epTrId.'\', '.$sCount.'); return true;" onmouseover="toggleActive(this);" onmouseout="toggleDActive(this);" style="cursor:pointer;"><a tabindex="'.$counter++.'"><div style="height:20px;">'.$showTitle.'</div></a>'.$chkImg.'</li>';
		}

		$res .= '</ul>';
		$res .= '</li>';
	}

	return $res;
}

function xbmcGetPlayerId() {
	$json_res = curlJson('"method": "Player.GetActivePlayers", "params":{}, "id":1');
	if (empty($json_res)) { return -1; }
	$res = json_decode($json_res);
	return (empty($res) || empty($res->{'result'})) ? -1 : intval($res->{'result'}[0]->{'playerid'});
}

function cleanedPlaying($playing) {
	$from = array('.flac','.mp3','.m4a','.mkv','.avi','.mp4','.flv');
	$to   = array(''     ,''    ,''    ,''    ,''    ,''    ,''    );
	return str_replace($from, $to, $playing);
}

function xbmcGetNowPlaying() {
	$pid = xbmcGetPlayerId();
	if ($pid < 0) { return null; }
	$json_res = curlJson('"method":"Player.GetItem", "params":{"playerid":'.$pid.'}, "id":1');
	if (empty($json_res)) { return null; }

	$res  = json_decode(encodeString($json_res, true));
	$name = !empty($res) ? $res->{'result'}->{'item'}->{'label'} : 'unknown';
	return $name;
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
	$json_res = curlJson('"method":"Player.PlayPause", "params":{"playerid":'.$pid.'}');
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
	$json_res = curlJson('"method":"Player.Stop", "params":{"playerid":'.$pid.'}');
	if (empty($json_res)) { return null; }
	else { return true; }
}

function xbmcPlayFile($filename) {
	$json_res = curlJson('"method":"Player.Open", "params":{ "item":{ "file":"'.$filename.'" } }');

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
	$json_string   = '{"jsonrpc": "2.0", '.$method.'}';

	$ch = curl_init($json_url);
	$options = array(
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_USERPWD        => $json_username.':'.$json_passwort,
		CURLOPT_HTTPHEADER     => array('content-type:application/json') ,
		CURLOPT_POSTFIELDS     => $json_string,
		CURLOPT_TIMEOUT        => 1
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

	exec('ps -ea | grep lightdm | wc -l', $output);
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
	return $orders < 0 ? 0 : $orders;
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

		foreach ($_POST as $key => $value)    { $_SESSION[$key] = SQLite3::escapeString($value); }
		foreach ($_GET  as $key => $value)    {
			if(isset($_GET['show']) &&
			   $_GET['show'] != 'logout') { $_SESSION[$key] = SQLite3::escapeString($value); }
		}

		if (isset($_POST['xlsUpload'])) { moveUploadedFile('xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); }
		else { unset( $_SESSION['xlsError'], $_SESSION['xlsFile'] ); }
	}

	//unset($_GET, $_POST);
}

function restoreSession() {
	$orSession = $_SESSION; //save pre-login values
	$filename  = './sessions/'.$_SESSION['user'].'.log';
	if (file_exists($filename)) {
		$sessionfile = fopen($filename, "r");
		$sessiondata = fread($sessionfile,  4096);
		fclose($sessionfile);
		session_decode($sessiondata);
	}
	$_SESSION = array_merge($_SESSION, $orSession); //override pre-login values
}

function storeSession() {
	$user = $_SESSION['user'];
	setLastHighest();
	clearMediaCache();
	unset( $_SESSION['username'],   $_SESSION['user'],  $_SESSION['idGenre'],  $_SESSION['refferLogged'], $_SESSION['overrideFetch'],
	       $_SESSION['passwort'],   $_SESSION['gast'],  $_SESSION['idStream'], $_SESSION['tvShowParam'],  $_SESSION['dbName'],
	       $_SESSION['angemeldet'], $_SESSION['demo'],  $_SESSION['thumbs'],   $_SESSION['existsTable'],  $_SESSION['TvDbCache'],
	       $_SESSION['private'],    $_SESSION['paths'], $_SESSION['covers'],   $_SESSION['reffer'],       $_SESSION['lastMovie'],
	       $_SESSION['OS']

	     ); //remove values that should be determined at login

	$sessionfile = fopen('./sessions/'.$user.'.log', 'w');
	fputs($sessionfile, session_encode());
	fclose($sessionfile);
}

function adminInfo($start, $show) {
	$adminInfo = isset($GLOBALS['ADMIN_INFO']) ? $GLOBALS['ADMIN_INFO'] : true;
	if (isAdmin() && $adminInfo && ($show == 'filme' || $show == 'serien' || $show == 'mvids')) {
		echo '<div class="bs-docs" id="adminInfo" style="z-index:10;" onmouseover="hideAdminInfo(true);" onmouseout="hideAdminInfo(false);">';

		if (isLinux()) {
			//hdparm
			$filename = './myScripts/hdparm.log';
			if (file_exists($filename)) {
				$log = file($filename);
				for ($i = 0; $i < count($log); $i++) {
					$line = explode(',',$log[$i]);
					$label = trim($line[0]);
					$state = trim($line[1]);

					if (empty($label)) { continue; }

					$tCol = (empty($state) ? '' : ($state == 'standby' ? ' label' :  ' label-success') );
					echo '<span id="hdp'.$i.'" style="cursor:default; display:none; padding:5px 8px; margin-left:5px; margin-bottom:5px;" class="label'.$tCol.'">'.$label.'</span>';
				}
				echo '<br id="brr0" style="display:none;" />';
			}
			//hdparm

			//cpu temp
			unset($output);
			exec('sensors | sed -ne "s/Core\ 0: \+[-+]\([0-9]\+\).*/\1/p"', $output);
			$cpu0 = getCPUdiv($output, 0);
			unset($output);
			exec('sensors | sed -ne "s/Core\ 1: \+[-+]\([0-9]\+\).*/\1/p"', $output);
			$cpu1 = getCPUdiv($output, 1);
			unset($output);
			exec("ps -eo pcpu | awk ' {cpu_load+=$1} END {print cpu_load}'", $output);
			$load = getLoadDiv($output);

			echo $cpu0;
			echo $cpu1;
			echo '<br id="brr1" style="display:none;" />';
			//cpu temp

			echo $load;
		} //isLinux

		//time
		$end = round(microtime(true)-$start, 2);
		$eCol = '';
		if ($end >= 1)   { $eCol = ' label-important'; }
		if ($end <= 0.1) { $eCol = ' label-success'; }
		echo '<span id="spTime" style="cursor:default; display:none; padding:5px 8px; margin-top:5px;" class="label'.$eCol.'">'.$end.'s</span>';
		//time

		echo '</div>';
	}
}

function getCPUdiv($output, $core) {
	$privateFolder = isset($GLOBALS['PRIVATE_FOLDER']) ? $GLOBALS['PRIVATE_FOLDER'] : null;
	$CPU_TEMPS     = isset($GLOBALS['CPU_TEMPS'])      ? $GLOBALS['CPU_TEMPS']      : array(30,40,50);

	if (!empty($output)) {
		$output = $output[0];
		$tCol = ' label-success';
		if ($output <= $CPU_TEMPS[0]) { $tCol = ' label-success'; }
		if ($output >= $CPU_TEMPS[1]) { $tCol = ' label-warning'; }
		if ($output >= $CPU_TEMPS[2]) { $tCol = ' label-important'; }
		$href = file_exists($privateFolder.'/cpu.php') ? ' href="'.$privateFolder.'/cpu.php?load=0&interpolate=1"' : null;
		return '<span id="spTemp'.$core.'"'.$href.' style="cursor:default; display:none; padding:5px 8px; margin-left:5px;" class="label'.$tCol.(!empty($href) ? ' fancy_logs' : '').'" title="core '.$core.'">'.$output.'&deg;C</span>';
	}
}

function getLoadDiv($output) {
	$privateFolder = isset($GLOBALS['PRIVATE_FOLDER']) ? $GLOBALS['PRIVATE_FOLDER'] : null;
	if (!empty($output)) {
		$output = $output[0];
		$tCol = '';
		if ($output <  40) { $tCol = ' label-success'; }
		if ($output >= 40) { $tCol = ' label-warning'; }
		if ($output >= 90) { $tCol = ' label-important'; }
		$href = file_exists($privateFolder.'/cpu.php') ? ' href="'.$privateFolder.'/cpu.php?load=1&interpolate=0"' : null;
		return '<span id="spLoad"'.$href.' style="cursor:default; display:none; padding:5px 8px; margin-right:5px;" class="label'.$tCol.(!empty($href) ? ' fancy_logs' : '').'" title="CPU load">'.$output.'%</span>';
	}
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
		$filename = './myScripts/hdparm.log';
		if (file_exists($filename)) {
			$log = file($filename);
			for ($i = 0; $i < count($log); $i++) {
				echo "\t$( '#hdp".$i."' ).toggle(show);\r\n";
			}
		}
		echo "\tif ($( '#brr0' ) != null) { $( '#brr0' ).toggle(show); }\r\n";
		echo "\tif ($( '#brr1' ) != null) { $( '#brr1' ).toggle(show); }\r\n";
		echo "\t$( '#spTemp0' ).toggle(show);\r\n";
		echo "\t$( '#spTemp1' ).toggle(show);\r\n";
		echo "\t$( '#spLoad' ).toggle(show);\r\n";
		echo "\t$( '#spTime' ).toggle(show);\r\n";
		echo "\t$( '#adminInfo.bs-docs' ).css('padding', show ? '35px 10px 14px' : '10px 10px 14px');\r\n";
		echo "}\r\n";
		echo '</script>'."\r\n";
	}
}

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
	$LOCALHOST  = isset($GLOBALS['LOCALHOST']) ? $GLOBALS['LOCALHOST'] : false;
	$gast_users = $GLOBALS['GAST_USERS'];

	if ($LOCALHOST || count($gast_users) == 0 && !isAdmin()) {
		$_SESSION['demo'] = true;
		return 1;
	}
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
		if (!allowedRequest()) { $exit = true; }

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
		$input = '<? /*'."\n".$input."".'*/ ?>';

		$fp = fopen($datei, "w+");
		fputs($fp, $input);
		fclose($fp);

		return $exit;
	}
}

function isWatchedAnyHiddenInMain($idShow) {
	$hidden = isset($GLOBALS['HIDE_WATCHED_ANY_EP_IN_MAIN']) ? $GLOBALS['HIDE_WATCHED_ANY_EP_IN_MAIN'] : null;
	if (empty($hidden)) { return false; }

	return isset($hidden[$idShow]);
}

function getShowInfo($idShow) {
	if (empty($idShow) || $idShow < 0) { return null; }
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if (isset($_SESSION['TvDbCache'][$idShow]) && $overrideFetch == 0) {
		return unserialize($_SESSION['TvDbCache'][$idShow]);
	}

	$LANG         = isset($GLOBALS['TVDB_LANGUAGE']) ? $GLOBALS['TVDB_LANGUAGE'] : 'en';
	$TVDB_API_KEY = isset($GLOBALS['TVDB_API_KEY'])  ? $GLOBALS['TVDB_API_KEY']  : null;

	if (empty($TVDB_API_KEY)) { return null; }

	$rss_0 = new rss_php;
	$URL = 'http://www.thetvdb.com/api/'.$TVDB_API_KEY.'/series/'.$idShow.'/all/'.$LANG.'.xml';
	$rss_0->load($URL);
	$items = $rss_0->getItems();

	$episodes = array();
	$count    = 1;
	foreach($items as $index => $item0) {
		foreach($item0 as $jndex => $item) {
			if (!isset($item0['Episode:'.$count])) { break; }

			$item = $item0['Episode:'.$count];
			$s    = $item['SeasonNumber'];
			$e    = $item['EpisodeNumber'];
			$episodes[$s][$e] = $item;
			$count++;
		}
	}

	$_SESSION['TvDbCache'][$idShow] = serialize($episodes);
	return $episodes;
}

function getEpisodeInfo($episodes, $getSeason, $getEpisode) {
	if ($getSeason == -1 || $getEpisode == -1) { return null; }

	$TVDB_API_KEY = isset($GLOBALS['TVDB_API_KEY']) ? $GLOBALS['TVDB_API_KEY'] : null;
	if (empty($TVDB_API_KEY)) { return null; }
	if (empty($episodes) || empty($episodes[$getSeason]) || empty($episodes[$getSeason][$getEpisode])) { return null; }

	return $episodes[$getSeason][$getEpisode];
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

	//tvdb
	$season  = $nextEpisode['SeasonNumber'];
	$episode = $nextEpisode['EpisodeNumber'];

	if (empty($season) || empty($episode)) { return; }
	updateAirdateInDb($idShow, $season, $episode, $airDate, $dbh);
	clearMediaCache();
}

function clearAirdateInDb($idShow, $dbh = null) {
	if (!checkAirDate()) { return; }
	$SQL = "DELETE FROM nextairdate WHERE idShow = ".$idShow.";";
	execSQL_($dbh, $SQL, false, false);
}

function updateAirdateInDb($idShow, $season, $episode, $airdate, $dbh = null) {
	if (!checkAirDate()) { return; }
	if ($idShow == -1 || $season == -1 || $episode == -1 || empty($episode) || empty($airdate)) { return; }
	$SQL = "REPLACE INTO nextairdate (idShow, season, episode, airdate) VALUES ('".$idShow."', '".$season."', '".$episode."', '".strtotime($airdate)."');";
	execSQL_($dbh, $SQL, false, false);
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

	$SQL = "SELECT idShow, season, episode FROM nextairdate;";
	$result = querySQL_($dbh, $SQL, false);
	$res    = array();
	foreach($result as $row) {
		$res[$row['idShow']]['s'] = $row['season'];
		$res[$row['idShow']]['e'] = $row['episode'];
	}

	$_SESSION['param_nextEpisodes'] = $res;
	return $res;
}

function getNextEpisode($serie) {
	if (!checkAirDate()) { return null; }
	$idTvDb      = $serie->getIdTvdb();
	$stCount     = $serie->getStaffelCount();
	$running     = $serie->isRunning();
	$nextEpisode = null;

	if (!$running) { return null; }
	$episodes = getShowInfo($idTvDb);
	$staffel  = $serie->getLastStaffel();
	$epCount  = $staffel->getLastEpNum();

	$info = getEpisodeInfo($episodes, $stCount, $epCount+1); //check next episode in given season
	if (empty($info)) {
		$info = getEpisodeInfo($episodes, $stCount+1, 1); //check first episode of next season
	}

	return !empty($info) ? $info : null;
}

function getNextAirDate($info) {
	return isset($info['FirstAired']) ? $info['FirstAired'] : null;
}

function getFormattedSE($sNum, $eNum, $epDelta = 0) {
	$pattern = isset($GLOBALS['EP_SEARCH_PATTERN'])      ? $GLOBALS['EP_SEARCH_PATTERN']      : 'S[season#]E[episode#]';
	$lZero   = isset($GLOBALS['EP_SEARCH_LEADING_ZERO']) ? $GLOBALS['EP_SEARCH_LEADING_ZERO'] : true;

	$eNum  += $epDelta;
	if ($lZero) {
		if ($sNum < 10) { $sNum = '0'.$sNum; }
		if ($eNum < 10) { $eNum = '0'.$eNum; }
	}

	$res = $pattern;
	$res = str_replace('[season#]',  $sNum, $res);
	$res = str_replace('[episode#]', $eNum, $res);
	return $res;
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
	if (isAdmin()) {
		if (dateMissed($airDate))   { $color = 'red';       }
		else if ($daysLeft > -1) {
			if ($daysLeft <= 3) { $color = 'lightblue'; }
			if ($daysLeft <= 2) { $color = 'purple';    }
			if ($daysLeft <= 1) { $color = 'brown';     }
		}
	}
	return 'color:'.$color.';';
}

function getDateFontsize($daysLeft) {
	$fSize = ' font-size:8pt;';
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
	#return empty($given) ? false : (strtotime($given)) < getToday();
	return (empty($given) ? false : daysLeft($given) < 0);
}

function daysLeft($given) {
	$oneDay  = $GLOBALS['DAY_IN_SECONDS'];
	return round((strtotime($given) - getToday()) / $oneDay, 0);
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
	return (empty($given) ? $given : date($strFormat, strtotime($given)));
}

function toEuropeanDateFormat($given) {
	$eurDateFormat = isset($GLOBALS['EUROPEAN_DATE_FORMAT']) ? $GLOBALS['EUROPEAN_DATE_FORMAT'] : false;
	$strFormat = $eurDateFormat ? 'd.m.y' : 'Y-m-d';
	return (empty($given) ? $given : date($strFormat, strtotime($given)));
}

function addRlsDiffToDate($given) {
	if (empty($given)) { return null; }

	$rlsDiff = isset($GLOBALS['RLS_OFFSET_IN_DAYS']) ? $GLOBALS['RLS_OFFSET_IN_DAYS'] : 1;
	$oneDay  = $GLOBALS['DAY_IN_SECONDS'];
	$diff    = $rlsDiff * $oneDay;

	return date('Y-m-d', strtotime($given) + $diff);
}

function compareDates($given1, $given2) {
	if (empty($given1)) { return true; }
	if (empty($given2)) { return false;  }
	return strtotime($given1) < strtotime($given2);
}

function formatToDeNotation($str) {
	$res = number_format($str, 3, ',', '.');
	return substr($res, 0, strlen($res)-4);
}

function getPausedAt($timeAt) {
	$sec = $timeAt % 60;
	$min = $timeAt / 60 % 60;
	$hrs = round($timeAt / 3600, 0);
	return sprintf('%02d:%02d:%02d', $hrs, $min, $sec);
}

function workaroundMTime($img) {
	if (!isLinux()) { return null; }
	exec('stat -c %Y "'.$img.'"', $output);
	return $output != null && count($output) > 0 ? $output[0] : null;
}

function handleError($errno, $errstr, $errfile, $errline, array $errcontext) {
	// error was suppressed with the @-operator
	throw new ErrorException($errstr, 0, $errno, $errfile, $errline);
}

function is3d($filename) {
	return strpos(strtoupper($filename), '.3D.') >= 1 || strpos(strtoupper($filename), '(3D)') >= 1;
}

function getCountyMap() {
	$COUNTRY_MAP = isset($GLOBALS['COUNTRY_MAP']) ? $GLOBALS['COUNTRY_MAP'] : null;
	if (empty($COUNTRY_MAP)) { return null; }

	$LANG = isset($GLOBALS['LANG']) ? $GLOBALS['LANG'] : 'EN';
	return $COUNTRY_MAP[$LANG];
}

function postEditLanguage($str, $buildLink = true) {
	$chosenMap = getCountyMap();
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

function postEditCodec($str) {
	//$str = str_replace('AC3', '<span title="Audio Coding 3">AC3</span>', $str);
	$str = str_replace('AAC', '<span title="Advanced Audio Coding">AAC</span>', $str);
	//$str = str_replace('VORBIS', '<span title="Vorbis">VORBIS</span>', $str);
	$str = str_replace('DCA', 'DTS', $str);
	$str = str_replace('TRUEHD', '<span title="True High Definition">True-HD</span>', $str);
	$str = str_replace('DTSHD_MA', '<span title="DTS - High Definition (Master Audio)">DTS-HD MA</span>', $str);
	$str = str_replace('DTSHD_HRA', '<span title="DTS - High Definition (High Resolution Audio)">DTS-HD HRA</span>', $str);

	return $str;
}

function postEditChannels($str) {
	return ($str >= 3) ? ($str-1).'.1' : $str.'.0';
}

function prepPlayFilename($filename) {
	if (empty($filename)) { return null; }

	$filename = addslashes($filename);
	$filename = str_replace("&", "_AND_", $filename);
	$filename = encodeString($filename);
	return $filename;
}

function trimDoubles($text) {
	return str_replace("''", "'", $text);
}

function encodeString($text, $plain = false) {
	$text = str_replace("''", "'", $text);
	//return htmlspecialchars($text, ENT_QUOTES);

	if (!$plain) {
		$text = str_replace ("ä",  "&auml;", $text);
		$text = str_replace ("Ä",  "&Auml;", $text);
		//$text = str_replace ("Ü¤",  "&auml;", $text);
		$text = str_replace ("ö",  "&ouml;", $text);
		$text = str_replace ("Ã¶", "&ouml;", $text);
		$text = str_replace ("Ö",  "&Ouml;", $text);
		$text = str_replace ("ü",  "&uuml;", $text);
		$text = str_replace ("Ã¼", "&uuml;", $text);
		$text = str_replace ("Ü",  "&Uuml;", $text);
		$text = str_replace ("Ã",  "&Uuml;", $text);
		$text = str_replace ("ß", "&szlig;", $text);
		$text = str_replace ("'",  "&#039;", $text);
		$text = str_replace ("'",  "&#039;", $text);
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

	$text = str_replace ("&amp;", "&", $text);
	$text = str_replace ("&auml;", "ä", $text);
	$text = str_replace ("&Auml;", "Ä", $text);
	$text = str_replace ("&ouml;", "ö", $text);
	$text = str_replace ("&Ouml;", "Ö", $text);
	$text = str_replace ("&uuml;", "ü", $text);
	$text = str_replace ("&Uuml;", "Ü", $text);
	$text = str_replace ("&szlig;", "ß", $text);
	return $text;
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

	$blacklisted[$ip]['date']  = time();
	$blacklisted[$ip]['count'] = isset($blacklisted[$ip]['count']) ? $blacklisted[$ip]['count']+1 : 1;

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
	if (file_exists($blFile)) {
		$read = '';
		$fp = fopen($blFile, "r");
		while(!feof($fp)) { $read = fgets($fp, 1000); }
		fclose($fp);
		return unserialize($read);

	} else { return array(); }
}

function escapeArray($val) {
	if (!is_array($val)) { return trim(SQLite3::escapeString($val)); }
	else { for ($i = 0; $i < count($val); $i++) { $val[$i] = escapeArray($val[$i]); } return $val; }
}

function generateOnDemandCopyScript($idOrder) {
	$srcLetter = isset($GLOBALS['srcDrive']) ? $GLOBALS['srcDrive'] : '';
	$dstLetter = isset($GLOBALS['selDrive']) ? $GLOBALS['selDrive'] : '';
	if (empty($srcLetter)) { $srcLetter = isset($GLOBALS['COPYASSCRIPT_COPY_FROM_LETTER']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM_LETTER'] : $srcLetter; }
	if (empty($dstLetter)) { $dstLetter = isset($GLOBALS['COPYASSCRIPT_COPY_TO_LETTER'])   ? $GLOBALS['COPYASSCRIPT_COPY_TO_LETTER']   : $dstLetter; }

	$res = querySQL("SELECT * FROM orderItemz WHERE idOrder = ".$idOrder." ORDER BY movieOrShow, idElement;");
	$idShows  = array();
	$idMovies = array();
	foreach($res as $row) {
		if ($row['movieOrShow'] == 0) { $idShows[] = $row['idElement']; }
		else { $idMovies[] = $row['idElement']; }
	}
	$idShows  = implode(',', $idShows);
	$idMovies = implode(',', $idMovies);

	$shows  = getItemsForRequest($idShows, true);
	$movies = getItemsForRequest($idMovies, false);

	$res  = '';
	$res .= doTheStuffTvShow($shows, true, false, $srcLetter, $dstLetter);
	$res .= doTheStuffMovie($movies, true, true, $srcLetter, $dstLetter);
	return $res;
}

function getItemsForRequest($ids, $isShow) {
	$SQL = '';
	if ($isShow) {
		$SQL =  "SELECT strPath, c00 AS name FROM tvshowview WHERE idShow IN (".$ids.") ORDER BY name;";
	} else {
		$SQL =  "SELECT c00 AS filmname, B.strFilename AS filename, c.strPath AS path, d.filesize FROM movie a, files b, path c, fileinfo d ".
			"WHERE idMovie IN (".$ids.") AND A.idFile = B.idFile AND c.idPath = b.idPath AND a.idFile = d.idFile ".
			"ORDER BY filename;";
	}
	return querySQL($SQL, false);
}

function doTheStuffTvShow($result, $forOrder = false, $append = false, $srcLetter = '', $dstLetter = '') {
	$copyAsScriptEnabled = isset($GLOBALS['COPYASSCRIPT_ENABLED'])        ? $GLOBALS['COPYASSCRIPT_ENABLED']   : false;
	$copyAsScript        = isset($GLOBALS['copyAsScript'])                ? $GLOBALS['copyAsScript']           : false;
	$scriptCopyWin       = isset($GLOBALS['COPYASSCRIPT_COPY_WIN'])       ? $GLOBALS['COPYASSCRIPT_COPY_WIN']  : false;
	$tvShowDir           = isset($GLOBALS['TVSHOWDIR'])                   ? $GLOBALS['TVSHOWDIR']              : $scriptCopyFrom;
	$scriptCopyFrom      = isset($GLOBALS['COPYASSCRIPT_COPY_FROM_SHOW']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM_SHOW'] : null;
	$scriptCopyTo        = isset($GLOBALS['COPYASSCRIPT_COPY_TO_SHOW'])   ? $GLOBALS['COPYASSCRIPT_COPY_TO_SHOW']   : '/mnt/hdd/';

	$scriptCopyFrom = $srcLetter.$scriptCopyFrom;
	$scriptCopyTo   = $dstLetter.$scriptCopyTo;

	$newLine = $forOrder ? "\n" : '<br />';
	$res = $scriptCopyWin && $forOrder && !$append ? 'chcp 1252'.$newLine : '';
	foreach($result as $row) {
		if ($forOrder || ($copyAsScript && $copyAsScriptEnabled)) {
			$path = $row['strPath'];
			$path = str_replace($tvShowDir, $srcLetter.$scriptCopyFrom, $path);

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

	$oldPath   = '';
	$newLine = $forOrder ? "\n" : '<br />';
	$res = $scriptCopyWin && $forOrder && !$append ? 'chcp 1252'.$newLine : '';
	$totalsize = 0;
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

/*
function getScriptString($path, $names, $formattedName) {
	$scriptCopyTo = $GLOBALS['scriptCopyTo'];
	$oldPath = '';

	$path = mapSambaDirs($path);
	$path = str_replace(' ', '\ ', $path);
	if ($oldPath != $path) {
		$names .= 'cd '.$path.'<br>';
	}
	$names .= 'cp ';
	$names .= $formattedName;
	$names .= ' '.$scriptCopyTo.' ';
	$names .= '<br>';
	$oldPath = $path;

	return $names;
}
*/

$dbConnection = getPD0();
function getPDO() { return $GLOBALS['dbConnection']; /* return DB_CONN::getConnection(); */ }
function getPD0() {
	$dbh = new PDO($GLOBALS['db_name']);
	try {
		/*** make it or break it ***/
		error_reporting(E_ALL);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbh->exec("PRAGMA foreign_keys = ON;");
	} catch(Exception $e) { }
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

/* worse than old method
class DB_CONN {
	private static $pdo;
	public static function getConnection() {
		if (!isLoggedIn()) { return null; }
		if (empty(self::$pdo)) { self::$pdo = getPD0(); }
		return self::$pdo;
	}
	public static function destruct() { unset(self::$pdo); }
}
*/
?>
