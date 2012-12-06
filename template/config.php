<?php
	$DB_PATH = '/public';
	$db_name = fetchDbName();
	function fetchDbName() {
		if (!isset($_SESSION)) { session_start(); }
		if (isset($_SESSION['dbName']) && !empty($_SESSION['dbName'])) { return $_SESSION['dbName']; }
		
		$dir = isset($GLOBALS['DB_PATH']) ? $GLOBALS['DB_PATH'] : '/public';
		
		$ver = array();
		$d = dir($dir);
		$counter = 0;
		while (false !== ($entry = $d->read())) {
			if ($entry == '.' || $entry == '..') { continue; }
			if (substr($entry, 0, 8) != 'MyVideos') { continue; }
			if (substr($entry, -3) != '.db') { continue; }
			
			$ver[$counter][0] = intval(substr($entry, 8, 2));
			$ver[$counter++][1] = $entry;
		}
		$d->close();
		
		rsort($ver);
		$_SESSION['dbName'] = 'sqlite:'.$dir.'/'.$ver[0][1];
		return $_SESSION['dbName'];
	}
	
	$gast_username                 = '';
	$gast_passwort                 = '';
	$login_username                = '';
	$login_passwort                = '';
	$REFF                          = 'USER.dyndns.org';
	
	/*
	// Just needed if samba drive is mounted....
	$SMB_USRPASS                   = 'user:pass';
	$SMB_IP                        = '192.168.0.123';
	$SMB_USIP                      = "smb://".$SMB_USRPASS."@".$SMB_IP."/";
	$SMB                           = 'smb://NAS/';
	
	$DIRMAP                        = array(
						array($SMB, "/mnt/")
						#,array($SMB, $SMB_USIP)
						#,array($SMB_USIP, "/mnt/")
					);
	*/
	
	//$TVSHOWDIR                     = 'media/Serien/';
	
	$NAVBAR_INVERSE                = false;
	$LANGMAP                       = array(
						"deu" => array("ger","gmh"),
						"jpn" => array("chi","kor"),
						"ita" => array("spa","por")
					);
	
	$COUNTRIES                     = array(
						array("", "all languages"),
						array("deu", "german"),
						array("eng", "english"),
						array("tur", "turkish"),
						array("fre", "french"),
						array("ita", "latino (ITA / SPA / POR)"),
						array("jpn", "asia (JPN / CHI / KOR)"),
						array("und", "undefined")
					);
	
	$TAG_MAP                       = array(
						array(".3d." => " (3D)"),
						array(".uncut." => " (uncut)"),
						array(".unrated." => " (unrated)"),
						array(".directors.cut." => " (directors cut)"),
						array(".extended." => " (extended)"),
						array("uncut" => " (uncut)"),
						array("unrated" => " (unrated)"),
						array("directors cut" => " (directors cut)"),
						array("extended" => " (extended)")
					);
	
	$ELEMSINROW                    = 7; //values: 13, 11, 9, 7, 5 or 3
	
	$USECACHE                      = true;
	$TVSHOW_THUMBS                 = true;
	
	$USESETS                       = true;
	$LOCALHOST                     = false;
	
	$DETAILFANART                  = true;
	$DETAILFANARTHEIGHT            = 576;
	
	$COPYASSCRIPT_ENABLED          = false;
	$COPYASSCRIPT_COPY_TO          = 'x:\\';
	
	$SEARCH_ENABLED                = true;
	$CUTS_ENABLED                  = true;  // extended, directors, uncut / unrated
	$DREID_ENABLED                 = false; // 3d
	$CHOOSELANGUAGES               = true;
	
	$CUT_OFF_MOVIENAMES            = -1; //35;
	$SPOILPROTECTION               = true;
	
	$MAXMOVIEINFOLEN               = 300;
	
	$COVER_OVER_TITLE              = false;
	$SHOW_TRAILER                  = false;
	
	$GIB_AS_GB                     = true;
	
	$TVDB_LANGUAGE                 = 'en'; // 'de';
	
	$importLogging                 = true;
	
	$NAS_CONTROL                   = false;
	$NAS_IP                        = null;
?>