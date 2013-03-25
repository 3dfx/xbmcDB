<?php
include_once "globals.php";
include_once "template/config.php";
include_once "template/SimpleImage.php";
require_once "rss_php.php";

function startSession() { if (!isset($_SESSION)) { session_start(); } }
function allowedRequest() { return true; }

function execSQL($SQL) {
	/*** make it or break it ***/
	error_reporting(E_ALL);
	try {
		$db_name = $GLOBALS['db_name'];
		$dbh = new PDO($db_name);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbh->beginTransaction();
		
		#$stmt = $dbh->prepare("SELECT user FROM users WHERE (user=:user)");
		#$stmt->bindValue(':user', $user);
		
		$dbh->exec($SQL);
		$dbh->commit();

	} catch(PDOException $e) {
		$dbh->rollBack();
		echo $e->getMessage();
	}

	return;
}

function fetchFromDB_($dbh, $SQL) {
	/*** make it or break it ***/
	error_reporting(E_ALL);
	try {
		return $dbh->query($SQL);

	} catch(PDOException $e) {
		echo $e->getMessage();
	}

	return null;
}

function fetchFromDB($SQL) {
	/*** make it or break it ***/
	error_reporting(E_ALL);
	try {
		$db_name = $GLOBALS['db_name'];
		$dbh = new PDO($db_name);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		return $dbh->query($SQL);

	} catch(PDOException $e) {
		echo $e->getMessage();
	}

	return null;
}

function getTableNames() {
	/*** make it or break it ***/
	error_reporting(E_ALL);
	try {
		$db_name = $GLOBALS['db_name'];
		$dbh = new PDO($db_name);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
	$sql = "SELECT strVideoCodec, fVideoAspect, iVideoWidth, iVideoHeight, strAudioCodec, iAudioChannels, strAudioLanguage, strSubtitleLanguage FROM streamdetails WHERE (strAudioLanguage IS NOT NULL OR strVideoCodec IS NOT NULL OR strSubtitleLanguage IS NOT NULL) AND idFile = ".$idFile.";";
	return fetchFromDB($sql);
}

function getGenres($dbh) {
	$overrideFetch = isset($_SESSION['overrideFetch']) ? $_SESSION['overrideFetch'] : false;
	if (isset($_SESSION['idGenre']) && $overrideFetch == 0) {
		$idGenre = unserialize($_SESSION['idGenre']);
		
	} else {
		$idGenre = array();
		$sqlG = "select * from genre";
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

	#} elseif ($a_bytes < 1073741824) {
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

function checkMyEpisodeView($dbh) {
	$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='view' and name='episodeviewMy';");
	$row = $res->fetch();
	if ($row['name'] == null) {
		$dbh->exec("CREATE VIEW episodeviewMy as select episode.*,files.strFileName as strFileName,path.idPath as idPath,path.strPath as strPath,files.playCount as playCount,files.lastPlayed as lastPlayed,tvshow.c00 as strTitle,tvshow.c14 as strStudio,tvshow.idShow as idShow,tvshow.c05 as premiered, tvshow.c13 as mpaa, tvshow.c16 as strShowPath from tvshow join episode on episode.idShow=tvshow.idShow join files on files.idFile=episode.idFile join path on files.idPath=path.idPath;");
	}
}

function existsArtTable($dbh) {
	$exist = isset($_SESSION['existsArtTable']) ? $_SESSION['existsArtTable'] : false;
	if ($exist) { return true; }
	
	$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='table' and name='art';");
	$row = $res->fetch();
	if ($row['name'] == null) {
		unset( $_SESSION['existsArtTable'] );
		return false;
	}
	
	$_SESSION['existsArtTable'] = true;
	return true;
}

function existsSetTable($dbh) {
	$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='table' and name='sets';");
	$row = $res->fetch();
	if ($row['name'] == null) { return false; }
	return true;
}

function checkFileInfoTable($dbh) {
	$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='table' and name='fileinfo';");
	$row = $res->fetch();
	if ($row['name'] == null) {
		$dbh->exec("CREATE TABLE fileinfo (idFile INTEGER NOT NULL, filesize LONGINT, CONSTRAINT 'C01_idFile' UNIQUE (idFile), CONSTRAINT 'C02_idFile' FOREIGN KEY (idFile) REFERENCES files (idFile) ON DELETE CASCADE);");
	}
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
	
	$res = $dbh->query("SELECT name FROM sqlite_master WHERE type='table' AND name='filemap';");
	$row = $res->fetch();
	if ($row['name'] == null) {
		$dbh->exec("CREATE TABLE filemap( idFile integer primary key, strFilename text, dateAdded text, value longint );");
	}
}

function fetchPaths() {
	$TVSHOWDIR = isset($GLOBALS['TVSHOWDIR']) ? $GLOBALS['TVSHOWDIR'] : '';
	$overrideFetch = isset($_SESSION['overrideFetch']) ? $_SESSION['overrideFetch'] : false;

	$SQL = "SELECT idPath, strPath FROM path WHERE strPath like '%".$TVSHOWDIR."%' ORDER BY strPath ASC;";
	
	$paths = array();
	if (isset($_SESSION['paths']) && !$overrideFetch) {
		$paths = unserialize($_SESSION['paths']);

	} else {
		/*** make it or break it ***/
		error_reporting(E_ALL);

		try {
			$db_name = $GLOBALS['db_name'];
			$dbh = new PDO($db_name);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
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
				$db_name = $GLOBALS['db_name'];
				$dbh = new PDO($db_name);
				$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			}
			
			#logc( "REPLACE INTO fileinfo (idFile, filesize) VALUES($idFile, $fsize);" );
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
	
	/*** make it or break it ***/
	error_reporting(E_ALL);
	try {
		$db_name = $GLOBALS['db_name'];
		$dbh = new PDO($db_name);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
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
				
				clearMovieCache();
			} // id is not null
		} // for each keys
		
		$dbh->commit();
		
	} catch(PDOException $e) {
		$dbh->rollBack();
		echo $e->getMessage();
	}
}

function clearMovieCache() { foreach ($_SESSION as $key => $value) { if (substr($key, 0, 7) == 'movies_') { unset( $_SESSION[$key] ); } } }

function resizeImg($SRC, $DST, $w, $h) {
	if (empty($SRC) && empty($orSRC)) {
		#logc( 'SRC EMPTY' );
		return;
	}
	
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
	$cachedImgExist = file_exists($SRC);
	$resizedImgExist = file_exists($DST);
	
	if ($resizedImgExist) {
		return $DST;
	}

	if ($cachedImgExist) {
		resizeImg($SRC, $DST, $w, $h);
		$pic = true;
	} else {
		if (!empty($DST) && strlen($DST) > 0 && !empty($orSRC)) {
		# && !file_exists($orSRC)
			resizeImg($orSRC, $DST, $w, $h);
			$pic = true;
		}
	}

	return ($pic == true ? $DST : null);
}

function getTvShowThumb($file) {
	$pic = false;
	$crc = thumbnailHash($file);
	$cachedimg = "./img/Thumbnails/".substr($crc, 0, 1)."/".$crc.".jpg";
	
	return file_exists($cachedimg) ? $cachedimg : null;
}

function getActorThumb($actor, $URL, $newmode) {
	$crc = ( $newmode ? thumbnailHash($actor) : thumbnailHash('actor'.$actor) );
	$cachedimg = "./img/Thumbnails/".substr($crc, 0, 1)."/".$crc.".jpg";
	
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
	#logc('getCover: '.$SRC.' '.$orSRC);
	#$pic = false;
	$crc = thumbnailHash($SRC);
	$cachedimg = '';
	if ($newmode) {
		$cachedimg = "./img/Thumbnails/".substr($crc, 0, 1)."/".$crc.".jpg";
	} else {
		$cachedimg = "./img/Thumbnails/Video/".substr($crc, 0, 1)."/".$crc.".tbn";
	}
	
	$cachedimg = './img/Thumbnails/'.($cacheDir != null ? $cacheDir : '').($cacheDir == null ? substr($crc, 0, 1) : '').'/'.$crc.'.jpg';
	#logc('cached: '.$cachedimg);
	
	$cachedImgExist = file_exists($cachedimg);
	#logc($cachedImgExist ? 'existCached: ' : 'notCached' );

	$ftime = '';
	try {
		if ($cachedImgExist) {
			$ftime = filemtime($cachedimg);
		}
	} catch (Exception $e) { }
	
	$resizedfile = './img/'.$subDir.'/'.$crc.'-'.$subName.(empty($ftime) ? '' : '_').$ftime.'.jpg';
	#if (!empty($cacheDir)) { logc( 'newFile: '.$resizedfile ); }
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

function postNavBar($isMain) {
	$admin = isAdmin();
	$saferSearch = null;
	
	$isMain          = !isset($_SESSION['show']) || $_SESSION['show'] == 'filme' ? true : false;
	
	$mode            = isset($_SESSION['mode']) ? $_SESSION['mode'] : 0;
	$sort            = isset($_SESSION['sort']) ? $_SESSION['sort'] : 0;
	$newmode         = isset($_SESSION['newmode']) ? $_SESSION['newmode'] : 0;
	$newsort         = isset($_SESSION['newsort']) ? $_SESSION['newsort'] : 0;
	$country         = isset($_SESSION['country']) ? $_SESSION['country'] : '';
	$gallerymode     = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;
	$dbSearch        = isset($_SESSION['dbSearch']) ? $_SESSION['dbSearch'] : null;
	$unseen          = isset($_SESSION['unseen']) ? $_SESSION['unseen'] : 3;
	$newAddedCount   = isset($_SESSION['newAddedCount']) ? $_SESSION['newAddedCount'] : 30;
	$serienmode      = isset($_SESSION['serienmode']) ? $_SESSION['serienmode'] : 0;
	
	$which           = isset($_SESSION['which']) ? $_SESSION['which'] : '';
	$just            = isset($_SESSION['just']) ? $_SESSION['just'] : '';
	$filter_name     = isset($_SESSION['name']) ? $_SESSION['name'] : '';
	
	$INVERSE         = isset($GLOBALS['NAVBAR_INVERSE']) ? $GLOBALS['NAVBAR_INVERSE'] : false;
	$SEARCH_ENABLED  = isset($GLOBALS['SEARCH_ENABLED']) ? $GLOBALS['SEARCH_ENABLED'] : true;
	$NAS_CONTROL     = isset($GLOBALS['NAS_CONTROL']) ? $GLOBALS['NAS_CONTROL'] : false;
	$CUTSENABLED     = isset($GLOBALS['CUTS_ENABLED']) ? $GLOBALS['CUTS_ENABLED'] : true;
	$DREIDENABLED    = isset($GLOBALS['DREID_ENABLED']) ? $GLOBALS['DREID_ENABLED'] : true;
	$CHOOSELANGUAGES = isset($GLOBALS['CHOOSELANGUAGES']) ? $GLOBALS['CHOOSELANGUAGES'] : false;
	$countryLabel    = $CHOOSELANGUAGES ? 'language' : '';
	
	if ($admin) { $newAddedCount = 30; }
	$dev = $admin;
	
	$unsetWhichParam = '&dbSearch=&which=&just=&sort=';
	$unsetMode = '&mode=';
	$unsetCountry = '&country=';
	
	$bs211 = ' padding-top:7px; height:18px !important;';
	
	echo '<div class="navbar'.($INVERSE ? ' navbar-inverse' : '').'" style="margin:-10px -15px 15px; position: fixed; width: 101%; z-index: 50;">';
	echo '<div class="navbar-inner" style="height:30px;">';
	echo '<div class="container" style="margin:0px auto; width:auto;">';
	
	echo '<a class="brand navBarBrand" href="#"'.($isMain ? ' onmouseover="closeNavs();"' : '').'>xbmcDB</a>';
	
	echo '<a class="btn btn-navbar" data-toggle="collapse" data-target=".nav-collapse"><span class="icon-bar"></span><span class="icon-bar"></span><span class="icon-bar"></span></a>';
	echo '<div class="nav-collapse">';
	echo '<ul class="nav" style="padding-top:2px;">';
	echo '<li class="divider-vertical" style="height:36px;"></li>';
	echo '<li'.($isMain ? ' class="active"' : '').'>';
	echo '<a href="?show=filme&mode=1&unseen=3&newmode=0&gallerymode=0'.$unsetWhichParam.$unsetMode.$unsetCountry.'"'.($isMain ? ' onmouseover="closeNavs();" onclick="return checkForCheck();" class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">movies</a>';
	echo '</li>';
	
	if ($isMain) {
		$selectedIs = 'all';
		if (!empty($dbSearch)) {
			$saferSearch = trim(SQLite3::escapeString($dbSearch));
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
		
		echo '<li class="dropdown" role="menu" aria-labelledby="dLabel" id="dropOptions" onmouseover="openNav(\'#dropOptions\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">'.$selectedIs.'<b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		$all = ((!isset($unseen) || $unseen == 3) && $newmode != 1 && empty($just) && empty($mode) && empty($dbSearch) && empty($country) ? ' class="selectedItem"' : '');
		echo '<li>';
		echo '<a href="?show=filme&newmode=0&unseen=3'.$unsetWhichParam.$unsetMode.$unsetCountry.'"'.($isMain ? ' onclick="return checkForCheck();"' : '').$all.'>all</a>';
		echo '</li>';
		
		echo '<li class="dropdown-submenu">';
		echo '<a tabindex="-1" href="#"'.($newmode && empty($just) ? ' class="selectedItem"' : '').'>newly added</a>';
		echo '<ul class="dropdown-menu">';
		echo '<li>';
		echo '<a tabindex="-1" href="?show=filme&newmode=1&newsort=2&unseen=3'.$unsetWhichParam.$unsetMode.$unsetCountry.'"'.($isMain ? ' onclick="return checkForCheck();"' : '').($newmode && $newsort == 2 && empty($just) ? ' class="selectedItem"' : '').'>sort by id</a>';
		echo '</li>';
		echo '<li>';
		echo '<a tabindex="-1" href="?show=filme&newmode=1&newsort=1&unseen=3'.$unsetWhichParam.$unsetMode.$unsetCountry.'"'.($isMain ? ' onclick="return checkForCheck();"' : '').($newmode && $newsort == 1 && empty($just) ? ' class="selectedItem"' : '').'>sort by date</a>';
		echo '</li>';
		echo '</ul>';
		echo '</li>';
		
		if ($CHOOSELANGUAGES) {
			$COUNTRIES = isset($GLOBALS['COUNTRIES']) ? $GLOBALS['COUNTRIES'] : array();
			if (count($COUNTRIES) > 0) {
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
					$langMenu .= '<li>';
					$langMenu .= '<a tabindex="-1" href="?show=filme&mode=2&newmode=0&unseen=3&country='.$lang[0].$unsetWhichParam.'" style="height:20px;" onclick="return checkForCheck();"'.($thisCountry ? ' class="selectedItem"' : '').'>'.$lang[1].'</a>';
					$langMenu .= '</li>';
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
			echo '<li>';
			echo '<a href="?show=filme&mode=3'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 3 && empty($just) ? ' class="selectedItem"' : '').'>directors cut</a>';
			echo '</li>';
			echo '<li>';
			echo '<a href="?show=filme&mode=4'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 4 && empty($just) ? ' class="selectedItem"' : '').'>extended cut</a>';
			echo '</li>';
			echo '<li>';
			echo '<a href="?show=filme&mode=5'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 5 && empty($just) ? ' class="selectedItem"' : '').'>uncut</a>';
			echo '</li>';
			echo '<li>';
			echo '<a href="?show=filme&mode=6'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 6 && empty($just) ? ' class="selectedItem"' : '').'>unrated</a>';
			echo '</li>';
		}
		
		if ($DREIDENABLED) {
			echo '<li>';
			echo '<a href="?show=filme&mode=7'.$unsetWhichParam.$unsetCountry.'" onclick="return checkForCheck();"'.($mode == 7 && empty($just) ? ' class="selectedItem"' : '').'>3D</a>';
			echo '</li>';
		}
		
		if ($admin) {
			echo '<li class="divider"></li>';

			echo '<li>';
			echo '<a href="?show=filme&unseen=1&newmode=0'.$unsetWhichParam.$unsetMode.'" onclick="return checkForCheck();"'.($unseen == 1 && empty($just) ? ' class="selectedItem"' : '').'>unseen</a>';
			echo '</li>';
			echo '<li>';
			echo '<a href="?show=filme&unseen=0&newmode=0'.$unsetWhichParam.$unsetMode.'" onclick="return checkForCheck();"'.($unseen == 0 && empty($just) ? ' class="selectedItem"' : '').'>seen</a>';
			echo '</li>';
		}
		
		echo '</ul>';
		echo '</li>';
		
		if ($SEARCH_ENABLED) {
			echo '<li class="dropdown" id="dropSearch" onmouseover="openNav(\'#dropSearch\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">search<b class="caret"></b></a>';
			echo '<ul class="dropdown-menu">';
				echo '<li class="navbar-search" style="margin:0px;">';
				echo '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchDBfor" name="searchDBfor" placeholder="search..." onfocus="this.select();" onkeyup="return searchDbForString(this, event); return false;" onmouseover="focus(this);" '.(!empty($saferSearch) ? 'value="'.$saferSearch.'"' : '').'/>';
				echo '<a class="search-close" onclick="return resetDbSearch();"><img src="./img/fancy-close.png" /></a>';
				echo '</li>';
				
				#if (!$gallerymode) {
				echo '<li class="navbar-search" style="margin:0px;">';
				echo '<input class="search-query span2" style="margin:4px 5px; width:150px; height:23px;" type="text" id="searchfor" name="searchfor" placeholder="filter..." onfocus="this.select();" onkeyup="searchForString(this, event); return false;" onmouseover="focus(this);" '.($gallerymode ? 'disabled ' : '').'/>';
				echo '<a class="search-close" onclick="resetFilter();"><img src="./img/fancy-close.png" /></a>';
				echo '</li>';
				#}
			echo '</ul>';
			echo '</li>';
		}
		
		$params = 'gallerymode='.($gallerymode ? 0 : 1);
		if (!empty($which) && !empty($just)) {
			$params .= '&which='.$which.'&just='.$just;
			unset($_SESSION['newmode']);
		} else {
			$params .= '&mode='.$mode.'&unseen='.$unseen.'&newmode='.$newmode.'&newAddedCount='.$newAddedCount;
		}
		
		echo '<li class="dropdown" id="dropViewmode" onmouseover="openNav(\'#dropViewmode\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">'.(!$gallerymode ? 'list' : 'gallery').'<b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		
		echo '<li>';
		echo '<a href="?show=filme&gallerymode=0" onclick="return checkForCheck();"'.($gallerymode ? '' : ' class="selectedItem"').'>list</a>';
		echo '</li>';
		
		echo '<li>';
		echo '<a href="?show=filme&gallerymode=1" onclick="return checkForCheck();"'.($gallerymode ? ' class="selectedItem"' : '').'>gallery</a>';
		echo '</li>';
		
		echo '</ul>';
		echo '</li>';
	} //$isMain
	
	$isTvshow = isset($_SESSION['show']) && $_SESSION['show'] == 'serien' ? true : false;
	echo '<li class="divider-vertical" style="height:36px;"'.($isMain ? ' onmouseover="closeNavs();"' : '').'></li>';
	echo '<li'.($isTvshow ? ' class="active"' : '').' style="font-weight:bold;">';
	echo '<a href="?show=serien"'.($isMain ? ' onmouseover="closeNavs();" onclick="return checkForCheck();"' : '').($isTvshow ? ' class="'.($INVERSE ? 'selectedMainItemInverse' : 'selectedMainItem').'"' : '').' style="font-weight:bold;'.($bs211).'">tv-shows</a>';
	echo '</li>';
	
	echo '</ul>';
	
	echo '<ul class="nav pull-right" style="padding-top:2px;">';
	echo '<li class="divider-vertical" style="height:36px;"'.($admin ? ' onmouseover="closeNavs();"' : '').'></li>';
	$USESETS = isset($GLOBALS['USESETS']) ? $GLOBALS['USESETS'] : true;
	if ($admin) {
		echo '<li class="dropdown" id="dropAdmin" onmouseover="openNav(\'#dropAdmin\');"><a href="#" class="dropdown-toggle" data-toggle="dropdown" style="font-weight:bold;'.($bs211).'">admin<b class="caret"></b></a>';
		echo '<ul class="dropdown-menu">';
		
		if ($USESETS) {
			echo '<li>';
			echo '<a class="fancy_sets" href="setEditor.php">sets</a>';
			echo '</li>';
			echo '<li class="divider"></li>';
		}
		
		echo '<li><a href="?show=export">DB-Export</a></li>';
		echo '<li><a href="?show=import">DB-Import</a></li>';
		echo '</li>';
		
		echo '<li class="divider"></li>';
		
		echo '<li>';
		echo '<a class="fancy_logs" href="./loginPanel.php?which=2">login-log</a>';
		echo '</li>';
		echo '<li>';
		echo '<a class="fancy_logs" href="./loginPanel.php?which=1">refferer-log</a>';
		echo '</li>';
		
		if ($NAS_CONTROL) {
			echo '<li class="divider"></li>';
			echo '<li>';
			echo '<a class="fancy_iframe3" href="./nasControl.php">NAS Control</a>';
			echo '</li>';
		}
		
		echo '</ul>';
		echo '</li>';
	}
	echo '<li>';
	echo '<a href="?show=logout" onmouseover="closeNavs();"'.($isMain ? ' onclick="return checkForCheck();"' : '').' style="font-weight:bold;'.($bs211).'">logout</a>';
	echo '</li>';
	
	echo '</ul>';
	echo '</div>';
	echo '</div></div></div>';
	
	return;
}

function logc($val, $noadmin = false) { if (isAdmin() || $noadmin) { echo '<script type="text/javascript">console.log( \''.$val.'\' );</script>'."\r\n"; } }
function pre($val, $noadmin = false)  { if (isAdmin() || $noadmin) { echo '<pre>'.$val.'</pre>'."\r\n"; } }

function redirectPage($subPage = null, $redirect = false) {
	$path = dirname($_SERVER['PHP_SELF']);
	$hostname = getHostnamee().$path.($subPage != null ? $subPage : '');

	if (!empty($_GET) || !empty($_POST)) { setSessionParams(); }
	if ($redirect) { header('Location:'.$hostname); }
	return;
}

function setSessionParams($isAuth = false) {
	if (!isset($_SESSION)) { return; }
		
	if ( isset($_GET['mode']) ) { unset($_SESSION['newmode']); $_SESSION['mode'] = $_GET['mode']; }
	if ( isset($_GET['unseen']) ) { unset($_SESSION['newmode']); $_SESSION['unseen'] = $_GET['unseen']; }
	if ( isset($_GET['show']) ) { $_SESSION['show'] = $_GET['show']; }
	if ( isset($_GET['sort']) ) { $_SESSION['sort'] = $_GET['sort']; }
	if ( isset($_GET['idShow']) ) { $_SESSION['idShow'] = $_GET['idShow']; }
	if ( isset($_GET['ref']) ) { $_SESSION['reffer'] = $_GET['ref']; }
	if ( isset($_GET['newmode']) ) { $_SESSION['newmode'] = $_GET['newmode']; }
	if ( isset($_GET['country']) ) { $_SESSION['country'] = $_GET['country']; }
	if ( isset($_GET['gallerymode']) ) { $_SESSION['gallerymode'] = $_GET['gallerymode']; }
	if ( isset($_GET['which']) ) { $_SESSION['which'] = $_GET['which']; }
	if ( isset($_GET['name']) ) { $_SESSION['name'] = $_GET['name']; }
	if ( isset($_GET['just']) ) { $_SESSION['just'] = $_GET['just']; }
	if ( isset($_GET['newAddedCount']) ) { $_SESSION['newAddedCount'] = $_GET['newAddedCount']; }
	if ( isset($_POST['newAddedCount']) ) { $_SESSION['newAddedCount'] = $_POST['newAddedCount']; }

	if (!$isAuth) {
		unset( $_SESSION['submit'], $_SESSION['export'] );
		
		foreach ($_GET as $key => $value)   { $_SESSION[$key] = $value; }
		foreach ($_POST as $key => $value)  { $_SESSION[$key] = $value; }
		
		if (isset($_POST['xlsUpload'])) { moveUploadedFile('xls', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'); }
		else { unset( $_SESSION['xlsError'], $_SESSION['xlsFile'] ); }
	}
	
	//unset($_GET, $_POST);
}

function adminInfo($start, $show) {
	$adminInfo = isset($GLOBALS['ADMIN_INFO']) ? $GLOBALS['ADMIN_INFO'] : true;
	if (isAdmin() && $adminInfo && ($show == 'filme' || $show == 'serien')) {
		echo '<div class="bs-docs" id="adminInfo" style="top:60px; right:5px; position:absolute; text-align:right; padding:10px 10px 14px;" onmouseover="hideAdminInfo(true);" onmouseout="hideAdminInfo(false);">';
		
		#hdparm
		#/-*
		#unset($output);
		#exec('/sbin/hpLog', $output);
		#print_r( $output );
		$filename = './myScripts/hdparm.log';
		if (file_exists($filename)) {
			$log = file($filename);
			for ($i = 0; $i < count($log); $i++) {
				$line = explode(',',$log[$i]);
				$label = trim($line[0]);
				$state = trim($line[1]);
				$tCol = $state == 'standby' ? ' label-important' :  ' label-success';
				echo '<span id="hdp'.$i.'" style="cursor:default; display:none; padding:5px 8px; margin-bottom:5px;" class="label'.$tCol.' cursor:pointer;">'.$label.'</span><br id="brr'.$i.'" style="display:none;" />';
			}
		}
		#*/
		#hdparm

		#cpu temp
		unset($output);
		exec('sensors | sed -ne "s/Physical\ id\ 0: \+[-+]\([0-9]\+\).*/\1/p"', $output);
		if (!empty($output)) {
			$output = $output[0];
			$tCol = ' label-success';
			if ($output >= 30) { $tCol = ''; }
			if ($output >= 40) { $tCol = ' label-warning'; }
			if ($output >= 50) { $tCol = ' label-important'; }
			echo '<span id="spTemp" style="cursor:default; display:none; padding:5px 8px;" class="label'.$tCol.' cursor:pointer;">'.$output.'&deg;C</span>';
		}
		#cpu temp
		
		#time
		$end = round(microtime(true)-$start, 2);
		$eCol = '';
		if ($end >= 1) { $eCol = ' label-important'; }
		if ($end <= 0.1) { $eCol = ' label-success'; }
		echo '<span id="spTime" style="cursor:default; display:none; padding:5px 8px; margin-left:5px;" class="label'.$eCol.'">'.$end.'s</span>';
		#time
		
		
		echo '</div>';
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
		#echo "var show = false;";
		echo "function hideAdminInfo(show) {\r\n";
		#echo "\tshow = !show;\r\n";
		$filename = './myScripts/hdparm.log';
		if (file_exists($filename)) {
			$log = file($filename);
			for ($i = 0; $i < count($log); $i++) {
				echo "\t$( '#hdp".$i."' ).toggle(show);\r\n";
				echo "\t$( '#brr".$i."' ).toggle(show);\r\n";
			}
		}
		echo "\t$( '#spTime' ).toggle(show);\r\n";
		echo "\t$( '#spTemp' ).toggle(show);\r\n";
		echo "\t$( '#adminInfo.bs-docs' ).css('padding', show ? '35px 10px 14px' : '10px 10px 14px');\r\n";
		echo "}\r\n";
		echo '</script>'."\r\n";
	}
}

function moveUploadedFile($prefix, $fileType) {
	if (isset($_POST[$prefix.'Upload']) && $_POST[$prefix.'Upload'] == 'senden' && !empty($_FILES['thefile'])) {
		#echo 'LALA  ';
		#echo $_FILES['thefile']['tmp_name'];
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
				#echo "The file ".basename( $_FILES['thefile']['name'])." has been uploaded!<br/>";
			} else {
				$_SESSION[$prefix.'Error'] = "An error occurred while uploading the file, please try again!<br/>";
				return;
			}

			$_SESSION[$prefix.'File'] = $target_path;
		}

		#echo 'POOO';
		#echo '<br/>';
	}
}

function isAdmin() {
	return (isset($_SESSION['angemeldet']) && $_SESSION['angemeldet'] == true) ? 1 : 0;
}

function isGast() {
	return (isset($_SESSION['gast']) && $_SESSION['gast'] == true) ? 1 : 0;
}

function checkOpenGuest() {
#deactivated
	$LOCALHOST = isset($GLOBALS['LOCALHOST']) ? $GLOBALS['LOCALHOST'] : false;
	$gast_users = $GLOBALS['gast_users'];
	
	if ($LOCALHOST || size($gast_users) == 0 && !isAdmin()) {
		$_SESSION['gast'] = true;
		return 1;
	}
}

function isLogedIn() {
	checkOpenGuest();
	return (isAdmin() || isGast() ? 1 : 0);
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
	
	if ($reffer == null) { return false; }
	$exit = false;
	
	$_SESSION['refferLoged'] = true;
	
	$LOCALHOST = isset($GLOBALS['LOCALHOST']) ? $GLOBALS['LOCALHOST'] : false;
	$HOMENETWORK = isset($GLOBALS['HOMENETWORK']) ? $GLOBALS['HOMENETWORK'] : false;

	if (!($LOCALHOST || $HOMENETWORK)) {
		$ip = $_SERVER['REMOTE_ADDR'];
		$host = gethostbyaddr($ip);

		$hostname = $_SERVER['HTTP_HOST'];
		if ($reffer != null) {
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
		$input = '<? /*'."\n".$input.'*/ ?>';

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

function postEditLanguage($str, $buildLink = true) {
	$COUNTRY_MAP = isset($GLOBALS['COUNTRY_MAP']) ? $GLOBALS['COUNTRY_MAP'] : null;
	if (empty($COUNTRY_MAP)) { return $str; }
	
	$LANG = isset($GLOBALS['LANG']) ? $GLOBALS['LANG'] : 'EN';
	$chosenMap = $COUNTRY_MAP[$LANG];
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
	//$str = str_replace('AAC', '<span title="Advanced Audio Coding">AAC</span>', $str);
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

function encodeString($text) {
	$text = str_replace("''", "'", $text);
	#return htmlspecialchars($text, ENT_QUOTES);

	$text = str_replace ("ä", "&auml;", $text);
	$text = str_replace ("Ä", "&Auml;", $text);
	$text = str_replace ("ö", "&ouml;", $text);
	$text = str_replace ("Ã¶", "&ouml;", $text);
	$text = str_replace ("Ö", "&Ouml;", $text);
	$text = str_replace ("ü", "&uuml;", $text);
	$text = str_replace ("Ã¼", "&uuml;", $text);
	$text = str_replace ("Ü", "&Uuml;", $text);
	$text = str_replace ("Ã", "&Uuml;", $text);
	$text = str_replace ("ß", "&szlig;", $text);
	$text = str_replace("'", "&#039;", $text);
	return $text;
}

function decodeString($text) {
	$text = str_replace("&#039;", "'", $text);
	#return htmlspecialchars_decode($text, ENT_QUOTES);

	$text = str_replace ("&auml;", "ä", $text);
	$text = str_replace ("&Auml;", "Ä", $text);
	$text = str_replace ("&ouml;", "ö", $text);
	$text = str_replace ("&Ouml;", "Ö", $text);
	$text = str_replace ("&uuml;", "ü", $text);
	$text = str_replace ("&Uuml;", "Ü", $text);
	$text = str_replace ("&szlig;", "ß", $text);
	return $text;
}

?>
