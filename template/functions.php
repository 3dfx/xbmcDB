<?php
include_once "./globals.php";
include_once "./template/config.php";
include_once "./template/SimpleImage.php";
require_once "./rss_php.php";

function startSession()   { if (!isset($_SESSION)) { session_start(); } }
function allowedRequest() { return true; }

function execSQL($SQL, $throw = true) {
	$dbh = getPDO();
	try {
		$dbh->beginTransaction();
		$dbh->exec($SQL);
		$dbh->commit();

	} catch(PDOException $e) {
		$dbh->rollBack();
		if ($throw) { echo $e->getMessage(); }
	}
	return;
}

function singleSQL($SQL, $throw = true) {
	$dbh = getPDO();
	try {
		return $dbh->querySingle($SQL);

	} catch(PDOException $e) {
		if ($throw) { echo $e->getMessage(); }
	}
	return null;
}

function fetchFromDB_($dbh, $SQL, $throw = true) {
	/*** make it or break it ***/
	error_reporting(E_ALL);
	try {
		return $dbh->query($SQL);

	} catch(PDOException $e) {
		if ($throw) {
			echo $e->getMessage();
		}
	}
	return null;
}

function fetchFromDB($SQL, $throw = true) {
	$dbh = getPDO();
	try {
		return $dbh->query($SQL);

	} catch(PDOException $e) {
		if ($throw) { echo $e->getMessage(); }
	}
	return null;
}

function getTableNames() {
	$dbh = getPDO();
	try {
		$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='table' AND name IS NOT 'sqlite_sequence';");
		$result = array();
		foreach($res as $row) {
			$result[] = $row['name'];
		}

		sort($result);
		return $result;

	} catch(PDOException $e) {
		echo $e->getMessage();
	}
}

function getStreamDetails($idFile) {
	$sql = "SELECT * FROM streamdetails WHERE (strAudioLanguage IS NOT NULL OR strVideoCodec IS NOT NULL OR strSubtitleLanguage IS NOT NULL) AND idFile = ".$idFile.";";
	return fetchFromDB($sql);
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
		$sql = "SELECT * FROM streamdetails WHERE iVideoWidth IS NOT NULL";
		$result = $dbh->query($sql);
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
	if (isset($dlvry) && $dlvry == 'wrapped') {
		$_SESSION['thumbs'][$type][$id] = $strFname;
	}
}

function getImageWrap($strFname, $id, $type, $size, $dlvry = null) {
	if (empty($dlvry)) { $dlvry = getWrapper(); }
	if ($dlvry == 'encoded') {
		if (file_exists($strFname)) { return base64_encode_image($strFname); }
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
        $size = -1;
        if ($size < 0 ){
            ob_start();
            system('ls -al "'.$path.'" | awk \'BEGIN {FS=" "}{print $5}\'');
            $size = ob_get_clean();
        }
        return $size;
}

function existsArtTable($dbh) {
	return existsTable('art', 'table', $dbh);
}

function existsSetTable($dbh) {
	return existsTable('sets', 'table', $dbh);
}

function checkFileInfoTable($dbh) {
	$exist = existsTable('fileinfo', 'table', $dbh);
	if (!$exist) { $dbh->exec("CREATE TABLE IF NOT EXISTS fileinfo (idFile INTEGER NOT NULL, filesize LONGINT, CONSTRAINT 'C01_idFile' UNIQUE (idFile), CONSTRAINT 'C02_idFile' FOREIGN KEY (idFile) REFERENCES files (idFile) ON DELETE CASCADE);"); }
}

function checkMyEpisodeView($dbh) {
	$exist = existsTable('episodeviewMy', 'view', $dbh);
	if (!$exist) { $dbh->exec("CREATE VIEW IF NOT EXISTS episodeviewMy as select episode.*,files.strFileName as strFileName,path.idPath as idPath,path.strPath as strPath,files.playCount as playCount,files.lastPlayed as lastPlayed,tvshow.c00 as strTitle,tvshow.c14 as strStudio,tvshow.idShow as idShow,tvshow.c05 as premiered, tvshow.c13 as mpaa, tvshow.c16 as strShowPath from tvshow join episode on episode.idShow=tvshow.idShow join files on files.idFile=episode.idFile join path on files.idPath=path.idPath;"); }
}

function existsOrdersTable($dbh = null) {
	$exist = existsTable('orders', 'table', $dbh);
	if (!$exist) {
		$sql = "CREATE TABLE IF NOT EXISTS orders (strFilename text primary key, dateAdded text, user text, fresh integer);";
		if (empty($dbh)) {
			execSQL($sql, false);
		} else {
			try { $dbh->exec($sql); }
			catch(PDOException $e) { $dbh->rollBack(); }
		}
		$exist = existsTable('orders', $dbh);
	}
	
	return $exist;
}

function existsTable($tableName, $type = 'table', $dbh = null) {
	$dbh = getPDO();
	try {
		$exist = isset($_SESSION['existsTable'.$tableName]) ? $_SESSION['existsTable'.$tableName] : -1;
		if ($exist !== -1) { return $exist; }
		
		$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='".$type."' and name='".$tableName."';");
		$row = $res->fetch();
		
		$exist = !empty($row['name']);
		$_SESSION['existsTable'.$tableName] = $exist;
		return $exist;

	} catch(PDOException $e) { }
	
	return false;
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
	$TVSHOWDIR = isset($GLOBALS['TVSHOWDIR']) ? $GLOBALS['TVSHOWDIR'] : '';
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;

	$SQL = "SELECT idPath, strPath FROM path WHERE strPath like '%".$TVSHOWDIR."%' ORDER BY strPath ASC;";
	
	$paths = array();
	if (isset($_SESSION['paths']) && $overrideFetch == 0) {
		$paths = unserialize($_SESSION['paths']);

	} else {
		$dbh = getPDO();
		try {
			$dbh->beginTransaction();
			$result = $dbh->query($SQL);
			
			$index = 0;
			foreach($result as $row) {
				$paths[$index][0] = $row['idPath'];
				$paths[$index][1] = $path = $row['strPath'];
				
				$index++;
			}
			
			$dbh->commit();

		} catch(PDOException $e) {
			echo $e->getMessage();
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
			$fnam = $path.$filename;
			$fsize = getFilesize($fnam);
		}
		
		if (empty($fsize)) { return 0; }
		
		try {
			$dbhIsNull = ($dbh == null);
			if ($dbhIsNull) {
				$dbh = $dbh = getPDO();
			}
			
			$sqli = "REPLACE INTO fileinfo (idFile, filesize) VALUES('$idFile', '$fsize');";
			if ($dbhIsNull) { $dbh->beginTransaction(); }

			$dbh->exec($sqli);

			if ($dbhIsNull) { $dbh->commit(); }

			$_SESSION['overrideFetch'] = 1;

		} catch(PDOException $e) {
			$dbh->rollBack();
			echo $e->getMessage();
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
	
	#if (!file_exists($file)) { return null; }
	
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
	$crc = 0xffffffff;
	for ($ptr = 0; $ptr < strlen($chars); $ptr++) {
		$chr = ord($chars[$ptr]);
		$crc ^= $chr << 24;
		for ($i=0; $i<8; $i++){
			if ($crc & 0x80000000) {
				$crc = ($crc << 1) ^ 0x04C11DB7;
			} else {
				$crc <<= 1;
			}
		}
	}
	
	$val0 = sprintf("%u",$crc);
	$val  = '';
	
	//Formatting the output in a 8 character hex
	if ($crc>=0){
		//positive results will hash properly without any issues
		$val = sprintf("%x",$val0);
	} else {
		/*
		 * negative values will need to be properly converted to
		 * unsigned integers before the value can be determined.
		 */
		$val = gmp_strval(gmp_init($val0),16);
	}
	
	return sprintf("%08s", $val);
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
		$dbh->beginTransaction();
		for ($i = 0; $i < count($checkFilme); $i++) {
			$id = $checkFilme[$i];

			if ($id != null) {
				$sql = "SELECT idFile FROM movie WHERE idMovie = '$id'";
				$result = $dbh->query($sql);
				$row = $result->fetch();
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
		
		$dbh->commit();
		
	} catch(PDOException $e) {
		$dbh->rollBack();
		echo $e->getMessage();
	}
}

function clearMediaCache() { foreach ($_SESSION as $key => $value) { if ( startsWith($key, 'movies_') || startsWith($key, 'SSerien') || startsWith($key, 'LSerien') || startsWith($key, 'FSerien') ) { unset( $_SESSION[$key] ); } } $_SESSION['overrideFetch'] = 1; }
function startsWith($haystack, $needle) { return !strncmp($haystack, $needle, strlen($needle)); }

function resizeImg($SRC, $DST, $w, $h) {
	if (empty($SRC) && empty($orSRC)) { return; }
	
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
	return generateImg($cachedimg, $resizedfile, null, null, 200);
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
	return generateImg($cachedimg, $resizedfile, $orSRC, $w, $h);
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

function getNewAddedCount() {
	$newAddedCount = isset($GLOBALS['DEFAULT_NEW_ADDED']) ? $GLOBALS['DEFAULT_NEW_ADDED'] : 30;
	$newAddedCount = isset($_SESSION['newAddedCount']) ? $_SESSION['newAddedCount'] : $newAddedCount;
	return $newAddedCount;
}

function postNavBar($isMain) {
	$admin = isAdmin();
	$saferSearch = null;
	
	$isMain          = !isset($_SESSION['show']) || $_SESSION['show'] == 'filme'  ? true : false;
	$isTvshow        = isset($_SESSION['show'])  && $_SESSION['show'] == 'serien' ? true : false;
	
	$country         = isset($_SESSION['country'])     ? $_SESSION['country']     : '';
	$mode            = isset($_SESSION['mode'])        ? $_SESSION['mode']        : 0;
	$sort            = isset($_SESSION['sort'])        ? $_SESSION['sort']        : 0;
	$newmode         = isset($_SESSION['newmode'])     ? $_SESSION['newmode']     : 0;
	$newsort         = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;
	$gallerymode     = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;
	$dbSearch        = isset($_SESSION['dbSearch'])    ? $_SESSION['dbSearch']    : null;
	$unseen          = isset($_SESSION['unseen'])      ? $_SESSION['unseen']      : 3;
	$serienmode      = isset($_SESSION['serienmode'])  ? $_SESSION['serienmode']  : 0;
	
	$which           = isset($_SESSION['which'])       ? $_SESSION['which']       : '';
	$just            = isset($_SESSION['just'])        ? $_SESSION['just']        : '';
	$filter_name     = isset($_SESSION['name'])        ? $_SESSION['name']        : '';
	
	$INVERSE             = isset($GLOBALS['NAVBAR_INVERSE'])      ? $GLOBALS['NAVBAR_INVERSE']      : false;
	$SEARCH_ENABLED      = isset($GLOBALS['SEARCH_ENABLED'])      ? $GLOBALS['SEARCH_ENABLED']      : true;
	$CUTSENABLED         = isset($GLOBALS['CUTS_ENABLED'])        ? $GLOBALS['CUTS_ENABLED']        : true;
	$DREIDENABLED        = isset($GLOBALS['DREID_ENABLED'])       ? $GLOBALS['DREID_ENABLED']       : true;
	$XBMCCONTROL_ENABLED = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$CHOOSELANGUAGES     = isset($GLOBALS['CHOOSELANGUAGES'])     ? $GLOBALS['CHOOSELANGUAGES']     : false;
	$countryLabel        = $CHOOSELANGUAGES ? 'language' : '';
	$newAddedCount       = getNewAddedCount();
	
	$dev = $admin;
	
	$unsetWhichParam = '&dbSearch=&which=&just=&sort=';
	$unsetMode = '&mode=';
	$unsetCountry = '&country=';
	
	$bs211 = ' padding-top:7px; height:18px !important;';
	
	echo '<div class="navbar'.($INVERSE ? ' navbar-inverse' : '').'" style="margin:-10px -15px 15px; position: fixed; width: 101%; z-index: 50;">';
	echo '<div class="navbar-inner" style="height:30px;">';
	echo '<div class="container" style="margin:0px auto; width:auto;">';
	
	echo '<a class="brand navBarBrand" href="#" onmouseover="closeNavs();">xbmcDB</a>';
	
	echo '<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>';
	echo '<div class="nav-collapse">';
	echo '<ul class="nav" style="padding-top:2px;">';
	echo '<li class="divider-vertical" style="height:36px;"></li>';
	echo '<li'.($isMain ? ' class="active"' : '').'>';
	echo '<a href="?show=filme&mode=1&unseen=3&newmode=0&gallerymode=0'.$unsetWhichParam.$unsetMode.$unsetCountry.'" onmouseover="closeNavs();"'.($isMain ? ' onclick="return checkForCheck();" class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">movies</a>';
	echo '</li>';
	
	if (!empty($dbSearch)) {
		$saferSearch = strtolower(trim(SQLite3::escapeString($dbSearch)));
	}
	
	if ($isMain) {
		$selectedIs = 'all';
		if (!empty($dbSearch)) {
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
		
		echo '<li class="dropdown" role="menu" aria-labelledby="dLabel" id="dropOptions" onmouseover="openNav(\'#dropOptions\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">'.$selectedIs.' <b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		$all = ((!isset($unseen) || $unseen == 3) && $newmode != 1 && empty($just) && empty($mode) && empty($dbSearch) && empty($country) ? ' class="selectedItem"' : '');
		echo '<li><a href="?show=filme&newmode=0&unseen=3'.$unsetWhichParam.$unsetMode.$unsetCountry.'"'.($isMain ? ' onclick="return checkForCheck();"' : '').$all.'>all</a></li>';
		
		echo '<li class="dropdown-submenu">';
		echo '<a tabindex="-1" href="#"'.($newmode && empty($just) ? ' class="selectedItem"' : '').'>'.(!$newmode ? 'newly added' : ($newsort == 2 ? 'sort by id' : 'sort by date')).'</a>';
		echo '<ul class="dropdown-menu">';
		echo '<li><a tabindex="-1" href="?show=filme&newmode=1&newsort=2&unseen=3'.$unsetWhichParam.$unsetMode.$unsetCountry.'"'.($isMain ? ' onclick="return checkForCheck();"' : '').($newmode && $newsort == 2 && empty($just) ? ' class="selectedItem"' : '').'>sort by id</a></li>';
		echo '<li><a tabindex="-1" href="?show=filme&newmode=1&newsort=1&unseen=3'.$unsetWhichParam.$unsetMode.$unsetCountry.'"'.($isMain ? ' onclick="return checkForCheck();"' : '').($newmode && $newsort == 1 && empty($just) ? ' class="selectedItem"' : '').'>sort by date</a></li>';
		echo '</ul>';
		echo '</li>';
		
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
					$langMenu .= '<li><a tabindex="-1" href="?show=filme&mode=2&newmode=0&unseen=3&country='.$lang[0].$unsetWhichParam.'" style="height:20px;" onclick="return checkForCheck();"'.($thisCountry ? ' class="selectedItem"' : '').'>'.$lang[1].'</a></li>';
				}
				
				$langMenu .= '</ul>';
				$langMenu .= '</li>';
				
				$langMenu = str_replace('[MENUCOUNTRY]', $menuCountry, $langMenu);
				echo $langMenu;
			}
		}
		
		if ($CUTSENABLED || $DREIDENABLED) {
			echo '<li class="divider"></li>';
		}
		
		if ($CUTSENABLED) {
			echo '<li><a href="?show=filme&mode=3'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 3 && empty($just) ? ' class="selectedItem"' : '').'>directors cut</a></li>';
			echo '<li><a href="?show=filme&mode=4'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 4 && empty($just) ? ' class="selectedItem"' : '').'>extended cut</a></li>';
			echo '<li><a href="?show=filme&mode=5'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 5 && empty($just) ? ' class="selectedItem"' : '').'>uncut</a></li>';
			echo '<li><a href="?show=filme&mode=6'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 6 && empty($just) ? ' class="selectedItem"' : '').'>unrated</a></li>';
		}
		
		if ($DREIDENABLED) {
			echo '<li><a href="?show=filme&mode=7'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 7 && empty($just) ? ' class="selectedItem"' : '').'>3D</a></li>';
		}
		
		if ($admin) {
			echo '<li class="divider"></li>';
			echo '<li><a href="?show=filme&unseen=1&newmode=0'.$unsetWhichParam.$unsetMode.'" onclick="return checkForCheck();"'.($unseen == 1 && empty($just) ? ' class="selectedItem"' : '').'>unseen</a></li>';
			echo '<li><a href="?show=filme&unseen=0&newmode=0'.$unsetWhichParam.$unsetMode.'" onclick="return checkForCheck();"'.($unseen == 0 && empty($just) ? ' class="selectedItem"' : '').'>seen</a></li>';
		}
		echo '</ul>';
		echo '</li>';
		
		$params = 'gallerymode='.($gallerymode ? 0 : 1);
		if (!empty($which) && !empty($just)) {
			$params .= '&which='.$which.'&just='.$just;
			unset($_SESSION['newmode']);
		} else {
			$params .= '&mode='.$mode.'&unseen='.$unseen.'&newmode='.$newmode.'&newAddedCount='.$newAddedCount;
		}
		
		echo '<li class="dropdown" id="dropViewmode" onmouseover="openNav(\'#dropViewmode\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">'.(!$gallerymode ? 'list' : 'gallery').' <b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		echo '<li><a href="?show=filme&gallerymode=0" onclick="return checkForCheck();"'.($gallerymode ? '' : ' class="selectedItem"').'>list</a></li>';
		echo '<li><a href="?show=filme&gallerymode=1" onclick="return checkForCheck();"'.($gallerymode ? ' class="selectedItem"' : '').'>gallery</a></li>';
		echo '</ul>';
		echo '</li>';
	} //$isMain
	
	if ($isTvshow) {
		echo '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
	}
	if ($SEARCH_ENABLED) {
		echo '<li class="dropdown" id="dropSearch" onmouseover="openNav(\'#dropSearch\');">';
		echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">search <b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
			echo '<li tabindex="1"'.($isMain || empty($saferSearch) ? ' class="navbar-search"' : ' style="margin:0px;"').'>';
			echo '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchDBfor" name="searchDBfor" placeholder="search..." onfocus="this.select();" onkeyup="return searchDbForString(this, event); return false;" onmouseover="focus(this);" '.(!empty($saferSearch) ? 'value="'.$saferSearch.'"' : '').'/>';
			echo '<a class="search-close"'.($isTvshow && !empty($saferSearch) ? 'style="top:9px; left:132px;"' : '').'onclick="return resetDbSearch();"><img src="./img/fancy-close.png" /></a>';
			echo '</li>';
			
			if ($isTvshow && !empty($saferSearch)) {
				createEpisodeSubmenu(fetchSearchSerien($saferSearch));
			}
			
			if (!$isTvshow) {
				echo '<li class="navbar-search" style="margin:0px;">';
				echo '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchfor" name="searchfor" placeholder="filter..." onfocus="this.select();" onkeyup="searchForString(this, event); return false;" onmouseover="focus(this);"'.($gallerymode || !$isMain ? ' disabled' : '').' />';
				echo '<a class="search-close"'.($gallerymode || !$isMain ? ' style="cursor:not-allowed;"' : ' onclick="resetFilter();"').'><img src="./img/fancy-close.png" /></a>';
				echo '</li>';
			}
		echo '</ul>';
		echo '</li>';
	}
	
	if (!$isTvshow) {
		echo '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
		echo '<li'.($isTvshow ? ' class="active"' : '').' style="font-weight:bold;">';
		echo '<a href="?show=serien"'.($isMain ? ' onmouseover="closeNavs();" onclick="return checkForCheck();"' : '').($isTvshow ? ' class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">tv-shows</a>';
		echo '</li>';
	} else {
		echo '<li class="dropdown" id="dropLatestEps" onmouseover="openNav(\'#dropLatestEps\');">';
		echo '<a href="?show=serien&dbSearch=" onmouseover="closeNavs();" zz_onclick="location.reload(true);"'.($isTvshow ? ' class="dropdown-toggle '.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' zz_data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">tv-shows <b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		
		createEpisodeSubmenu(fetchLastSerien());
		echo '</ul>';
		echo '</li>';
	}
	echo '</ul>';
	
	echo '<ul class="nav pull-right" style="padding-top:2px;">';
	if ($admin && $XBMCCONTROL_ENABLED) {
		$run = xbmcRunning();
		$playing = $run != 0 ? cleanedPlaying(xbmcGetNowPlaying())  : '';
		$state   = $run != 0 ? intval(xbmcGetPlayerstate()) : '';
		$state   = ($state == 1 ? 'playing' : ($state == 0 ? 'paused' : ''));
		echo '<li id="xbmControl" onmouseover="closeNavs();" style="cursor:default; height:35px;'.(empty($playing) ? ' display:none;' : '').'">';
			echo '<span style="color:black; position:absolute; top:10px; font-weight:bold; left:-65px;"><span id="xbmcPlayerState">'.$state.'</span>: </span>';
			echo '<a class="navbar" onclick="playPause(); return false;" style="cursor:pointer; font-weight:bold; max-width:300px; width:300px; height:20px; float:left; padding:8px; margin:0px; white-space:nowrap; overflow:hidden;">';
				echo '<span id="xbmcPlayerFile" style="top:0px; position:relative; max-width:350px; width:350px; height:20px; left:-7px;">'.$playing.'</span>';
			echo '</a> ';
			echo '<a class="navbar" onclick="stopPlaying(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0px;"><img src="./img/stop.png" style="width:24px; height:24ps;" /></a>';
			echo '<a class="navbar" onclick="playNext(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0px;"><img src="./img/next.png" style="width:24px; height:24ps;" /></a>';
			echo '<a class="navbar" onclick="playPrev(); return false;" style="cursor:pointer; float:right; padding:6px; margin:0px;"><img src="./img/prev.png" style="width:24px; height:24ps;" /></a>';
		echo '</li>';
		
		#echo '<a href="#" onclick="playItemPrompt();">test</a>';
	}
	echo '<li class="divider-vertical" style="height:36px;" onmouseover="closeNavs();"></li>';
	
	if ($admin) {
		$msgs = checkNotifications();
		$selected = '';
		if ($msgs > 0) {
			$selected = ' selectedItem';
			echo '<span class="notification badge badge-important"><span style="margin:-2px;">'.$msgs.'</span></span>';
		}
		echo '<li class="dropdown" id="dropAdmin" onmouseover="openNav(\'#dropAdmin\');">';
		echo '<a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">admin <b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		if (file_exists('./fExplorer/index.php')) {
			echo '<li><a class="fancy_explorer" href="fExplorer/index.php">File Explorer</a></li>';
			echo '<li class="divider"></li>';
		}
		
		echo '<li><a class="fancy_sets'.$selected.'" href="orderViewer.php">Order Viewer</a></li>';
		
		$USESETS = isset($GLOBALS['USESETS']) ? $GLOBALS['USESETS'] : true;
		if ($USESETS) {
			echo '<li><a class="fancy_sets" href="setEditor.php">Set Editor</a></li>';
		}
		
		echo '<li class="divider"></li>';
		echo '<li><a class="fancy_logs" href="./loginPanel.php?which=2">Login-log</a></li>';
		echo '<li><a class="fancy_logs" href="./loginPanel.php?which=1">Refferer-log</a></li>';
		echo '<li><a class="fancy_blocks" href="./blacklistControl.php">Blacklist Control</a></li>';
		
		echo '<li class="divider"></li>';
		echo '<li><a href="" onclick="clearCache(); return false;">Clear cache</a></li>';
		if (xbmcRunning() != 0) {
			echo '<li><a href="" onclick="scanLib(); return false;">Scan Library</a></li>';
		}
		
		/*
		echo '<li class="divider"></li>';
		echo '<li><a href="?show=export">DB-Export</a></li>';
		echo '<li><a href="?show=import">DB-Import</a></li>';
		echo '</li>';
		*/
		
		$NAS_CONTROL     = isset($GLOBALS['NAS_CONTROL']) ? $GLOBALS['NAS_CONTROL'] : false;
		if ($NAS_CONTROL) {
			echo '<li class="divider"></li>';
			echo '<li><a class="fancy_iframe3" href="./nasControl.php">NAS Control</a></li>';
		}
		
		echo '</ul>';
		echo '</li>';
	}
	echo '<li><a href="?show=logout" onmouseover="closeNavs();"'.($isMain ? ' onclick="return checkForCheck();"' : '').' style="font-weight:bold;'.($bs211).'">logout</a></li>';
	
	echo '</ul>';
	echo '</div>';
	echo '</div></div></div>';
	
	return;
}

function createEpisodeSubmenu($result) {
	$admin = isAdmin();
	$counter = 2;
	foreach($result as $key => $show) {
		echo '<li class="dropdown-submenu">';
		$count = count($show);
		echo '<a tabindex="'.$counter++.'" href="#"><span title="'.$count.' episode'.($count > 1 ? 's' : '').'">'.$key.'</span></a>';
		echo '<ul class="dropdown-menu">';
		
		foreach($show as $row) {
			$idShow    = $row['idShow'];    $serie = $row['serie']; $season  = $row['season'];
			$idEpisode = $row['idEpisode']; $title = $row['title']; $episode = $row['episode'];
			$playCount = $row['playCount']; $sCount = $row['sCount'];
			
			if ($season  < 10) { $season  = '0'.$season;  }
			if ($episode < 10) { $episode = '0'.$episode; }
			$epTrId = 'iD'.$idShow.'.S'.$season;
			
			$SE = '<span style="padding-right:10px; color:silver;"><b><sub>S'.$season.'.E'.$episode.'</sub></b></span> ';
			$showTitle = '<span class="nOverflow flalleft" style="position:relative; left:-15px;">'.$SE.trimDoubles($title).'</span>';
			$chkImg = ($admin && $playCount > 0 ? ' <span class="flalright mnuIcon"><img src="./img/check.png" class="icon24" title="watched" /></span>' : '');
			echo '<li href="./detailEpisode.php?id='.$idEpisode.'" onclick="loadLatestShowInfo(this, '.$idShow.', '.$idEpisode.', \''.$epTrId.'\', '.$sCount.'); return true;" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'"><a tabindex="-1" href="#"><div style="height:20px;">'.$showTitle.'</div></a>'.$chkImg.'</li>';
		}

		echo '</ul>';
		echo '</li>';
	}
}

function xbmcGetPlayerId() {
	$json_res = curlJson('"method": "Player.GetActivePlayers", "params":{}, "id":1');
	$res = json_decode($json_res);
	return empty($res->{'result'}) ? -1 : intval($res->{'result'}[0]->{'playerid'});
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

	$res = json_decode(encodeString($json_res, true));
	return $res->{'result'}->{'item'}->{'label'};
}

function xbmcGetPlayerstate() {
	$pid = xbmcGetPlayerId();
	if ($pid < 0) { return null; }
	$json_res = curlJson('"method":"Player.GetProperties", "params":{"playerid":'.$pid.', "properties":["speed"]}, "id":1');
	if (empty($json_res)) { return null; }
	
	$res = json_decode($json_res);
	return $res->{'result'}->{'speed'}.'';
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
	if ($res->{'result'} == 'OK') { $_SESSION['overrideFetch'] = 1; }
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
	if (!isAdmin()) { return 0; }
	
	$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
	if ($overrideFetch == 0 && isset($_SESSION['xbmcRunning'])) { return $_SESSION['xbmcRunning']; }
	
	exec('ps -ea | grep lightdm | wc -l', $output);
	$res = intval(trim($output[0]));
	$_SESSION['xbmcRunning'] = $res;
	return $res;
}
	
function checkNotifications() {
	if (existsOrdersTable()) {
		$sql = "SELECT COUNT(fresh) AS count FROM orders WHERE fresh = 1;";
		$res = fetchFromDB($sql);
		if (empty($res)) { return 0; }
		$row = $res->fetch();
		return $row['count'];
	}
	
	return 0;
}

function logc($val, $noadmin = false) { if (isAdmin() || $noadmin) { echo '<script type="text/javascript">console.log( \''.$val.'\' );</script>'."\r\n"; } }
function pre($val, $noadmin = false)  { if (isAdmin() || $noadmin) { echo '<pre>'.$val.'</pre>'."\r\n"; } }

function redirectPage($subPage = null, $redirect = false) {
	$path = dirname($_SERVER['PHP_SELF']);
	$hostname = getHostnamee().$path.($subPage != null ? $subPage : '');
	
	if (!empty($_GET) || !empty($_POST)) { setSessionParams(); }
	if ($redirect) { header('Location:'.$hostname); }
	return $redirect;
}

function setSessionParams($isAuth = false) {
	if (!isset( $_SESSION )) { return; }
	
	if (isset( $_GET['mode']           )) { unset($_SESSION['newmode']); $_SESSION['mode']    = $_GET['mode'];   }
	if (isset( $_GET['unseen']         )) { unset($_SESSION['newmode']); $_SESSION['unseen']  = $_GET['unseen']; }
	if (isset( $_GET['show']           )) { if($_GET['show'] != 'logout') { $_SESSION['show'] = $_GET['show']; } }
	if (isset( $_GET['sort']           )) { $_SESSION['sort']          = $_GET['sort'];           }
	if (isset( $_GET['idShow']         )) { $_SESSION['idShow']        = $_GET['idShow'];         }
	if (isset( $_GET['ref']            )) { $_SESSION['reffer']        = $_GET['ref'];            }
	if (isset( $_GET['newmode']        )) { $_SESSION['newmode']       = $_GET['newmode'];        }
	if (isset( $_GET['country']        )) { $_SESSION['country']       = $_GET['country'];        }
	if (isset( $_GET['gallerymode']    )) { $_SESSION['gallerymode']   = $_GET['gallerymode'];    }
	if (isset( $_GET['which']          )) { $_SESSION['which']         = $_GET['which'];          }
	if (isset( $_GET['name']           )) { $_SESSION['name']          = $_GET['name'];           }
	if (isset( $_GET['just']           )) { $_SESSION['just']          = $_GET['just'];           }
	if (isset( $_GET['newAddedCount']  )) { $_SESSION['newAddedCount'] = $_GET['newAddedCount'];  }
	if (isset( $_POST['newAddedCount'] )) { $_SESSION['newAddedCount'] = $_POST['newAddedCount']; }

	if (!$isAuth) {
		unset( $_SESSION['submit'], $_SESSION['export'] );
		
		if (!empty($_SESSION['which']))    { unset( $_SESSION['dbSearch'] ); }
		if (!empty($_SESSION['dbSearch'])) { unset( $_SESSION['which'], $_SESSION['just'] ); }
		
		foreach ($_POST as $key => $value)    { $_SESSION[$key] = $value; }
		foreach ($_GET as $key => $value)     {
			if(isset($_GET['show']) && 
			   $_GET['show'] != 'logout') { $_SESSION[$key] = $value; }
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
	clearMediaCache();
	unset( $_SESSION['username'],   $_SESSION['user'],  $_SESSION['idGenre'],  $_SESSION['xbmcRunning'], $_SESSION['overrideFetch'],
	       $_SESSION['passwort'],   $_SESSION['gast'],  $_SESSION['idStream'], $_SESSION['refferLoged'], $_SESSION['dbName'], 
	       $_SESSION['angemeldet'], $_SESSION['paths'], $_SESSION['thumbs'],   $_SESSION['private']
	     ); //remove values that should be determined at login
	
	$sessionfile = fopen('./sessions/'.$user.'.log', 'w');
	fputs($sessionfile, session_encode());
	fclose($sessionfile);
}

function adminInfo($start, $show) {
	$adminInfo = isset($GLOBALS['ADMIN_INFO']) ? $GLOBALS['ADMIN_INFO'] : true;
	if (isAdmin() && $adminInfo && ($show == 'filme' || $show == 'serien')) {
		echo '<div class="bs-docs" id="adminInfo" onmouseover="hideAdminInfo(true);" onmouseout="hideAdminInfo(false);">';
		
		//hdparm
		$filename = './myScripts/hdparm.log';
		if (file_exists($filename)) {
			$log = file($filename);
			for ($i = 0; $i < count($log); $i++) {
				$line = explode(',',$log[$i]);
				$label = trim($line[0]);
				$state = trim($line[1]);
				
				if (empty($label)) { continue; }
				
				$tCol = (empty($state) ? '' : ($state == 'standby' ? ' label-important' :  ' label-success') );
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
			$target_path = '/var/www'.dirname($_SERVER['PHP_SELF']).'/uploads/';
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

function isLogedIn() {
	checkOpenGuest();
	return (isAdmin() || isGast() ? 1 : 0);
}

function checkOpenGuest() {
//-- deactivated --//
	$LOCALHOST = isset($GLOBALS['LOCALHOST']) ? $GLOBALS['LOCALHOST'] : false;
	$gast_users = $GLOBALS['GAST_USERS'];
	
	if ($LOCALHOST || count($gast_users) == 0 && !isAdmin()) {
		$_SESSION['gast'] = true;
		return 1;
	}
}

function getHttPre() {
	$isHTTPs = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on');
	return 'http'.($isHTTPs ? 's' : '').'://';
}

function getHostnamee() {
	$hostname = $_SERVER['HTTP_HOST'];
	return getHttPre().$hostname;
}

function logRefferer($reffer) {
	if (isset($_SESSION['refferLoged'])) {
		return false;
	}
	
	//if (empty($reffer)) { return false; }
	$exit = false;
	
	$_SESSION['refferLoged'] = true;
	
	$LOCALHOST   = isset($GLOBALS['LOCALHOST'])   ? $GLOBALS['LOCALHOST']   : false;
	$HOMENETWORK = isset($GLOBALS['HOMENETWORK']) ? $GLOBALS['HOMENETWORK'] : false;

	if (!($LOCALHOST || $HOMENETWORK)) {
		$ip = $_SERVER['REMOTE_ADDR'];
		$host = gethostbyaddr($ip);

		$hostname = $_SERVER['HTTP_HOST'];
		if (empty($reffer)) {
			$reffer = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : '';
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

function getShowInfo($getShowId) {
	if ($getShowId == -1) { return null; }
	
	$TVDB_API_KEY = isset($GLOBALS['TVDB_API_KEY']) ? $GLOBALS['TVDB_API_KEY'] : null;
	if ($TVDB_API_KEY == null) { return array(); }
	
	$lang = isset($GLOBALS['TVDB_LANGUAGE']) ? $GLOBALS['TVDB_LANGUAGE'] : 'en';
	
	$rss_0 = new rss_php;
	$rss_0->load('http://www.thetvdb.com/api/'.$TVDB_API_KEY.'/series/'.$getShowId.'/all/'.$lang.'.xml');
	$items = $rss_0->getItems();

	$episodes = array();
	$count = 1;
	foreach($items as $index => $item0) {
		foreach($item0 as $jndex => $item) {
			if (!isset($item0['Episode:'.$count])) { break; }
			$item = $item0['Episode:'.$count];
			
			$s = $item['SeasonNumber'];
			$e = $item['EpisodeNumber'];
			$id = $item['id'];
			
			$episodes[$s][$e] = $item;
			$count++;
		}
	}
	
	return $episodes;
}

function getEpisodeInfo($episodes, $getSeason, $getEpisode) {
	if ($getSeason == -1 || $getEpisode == -1) { return null; }
	
	$TVDB_API_KEY = isset($GLOBALS['TVDB_API_KEY']) ? $GLOBALS['TVDB_API_KEY'] : null;
	if ($TVDB_API_KEY == null) { return null; }
	
	if ($episodes == null) { return null; }
	
	return $episodes[$getSeason][$getEpisode];
}

function formatToDeNotation($str) {
	$res = number_format($str, 3, ',', '.');
	return substr($res, 0, strlen($res)-4);
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

function trimDoubles($text) {
	$text = str_replace("''", "'", $text);
	$text = str_replace("\'", "'", $text);
	return $text;
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
	$ip          = empty($ipIs) ? $_SERVER['REMOTE_ADDR'] : $ipIs;
	
	if (!isset($blacklisted[$ip])) { return false; }
	if ($blacklisted[$ip]['count'] <= 4) { return false; }
	$date = $blacklisted[$ip]['date'];
	//30 minutes
	if (time()-intval($date) > 30*60) { return false; }
	
	return true;
}

function removeBlacklist($ipDel = null) {
	$blacklisted = restoreBlacklist();
	$ip          = empty($ipDel) ? $_SERVER['REMOTE_ADDR'] : $ipDel;
	
	unset( $blacklisted[$ip] );
	
	storeBlacklist($blacklisted);
}

function addBlacklist() {
	$blacklisted = restoreBlacklist();
	$ip          = $_SERVER['REMOTE_ADDR'];
	
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

$dbConnection = getPD0();
function getPDO() { return $GLOBALS['dbConnection']; }
function getPD0() {
	$db_name = $GLOBALS['db_name'];
	$dbh = new PDO($db_name);
	try {
		/*** make it or break it ***/
		error_reporting(E_ALL);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		return $dbh;
		
	} catch(Exception $e) { }
	return $dbh;
}
?>
