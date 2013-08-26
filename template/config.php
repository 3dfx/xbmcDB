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
	
	$GAST_USERS                    = array(
#						''   => ''
					);
	
	$NO_LOG_FROM                   = array(
//						'admin' => array('host1.com', 'host2.com')
					);
	
	$REFF                          = 'HOST.dyndns.org';
	
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
					
	$DIRMAP_IMG                    = array(
						array("/mnt/media/Serien", "./img/Serien")
					);
	*/
	
	//$TVSHOWDIR                     = 'media/Serien/';
	$THUMBNAIL_DIR                 = './img/thumbs/';
	
	$NAVBAR_INVERSE                = false;
	$LANGMAP                       = array(
						'deu' => array('ger','gmh'),
						'jpn' => array('chi','kor'),
						'ita' => array('spa','por')
					);
	$COUNTRIES                     = array(
						array('', 'all languages'),
						array('deu', 'german'),
						array('eng', 'english'),
						array('tur', 't&uuml;rk&#231;e'),
						array('fre', 'french'),
						array('ita', 'latino (ITA / SPA / POR)'),
						array('jpn', 'asia (JPN / CHI / KOR)'),
						#array('ita', '<span style="float:left;">latino</span><span style="float:right;">&nbsp;(ITA / SPA / POR)</span>'),
						#array('jpn', '<span style="float:left;">asia</span><span style="float:right;">&nbsp;(JPN / CHI / KOR)</span>'),
						array('und', 'undefiniert')
					);
	
	$TAG_MAP                       = array(
						array('.3d.' => ' (3D)'),
						array('.uncut.' => ' (uncut)'),
						array('.unrated.' => ' (unrated)'),
						array('.directors.cut.' => ' (directors cut)'),
						array('.extended.' => ' (extended)'),
						array('uncut' => ' (uncut)'),
						array('unrated' => ' (unrated)'),
						array('directors cut' => ' (directors cut)'),
						array('extended' => ' (extended)')
					);
	
	$ELEMSINROW                    = 7; //values: 13, 11, 9, 7, 5 or 3
	$DEFAULT_NEW_ADDED             = 30;
	
	$USECACHE                      = true;
	$TVSHOW_THUMBS                 = true;
	$TVSHOW_THUMBS_FROM_SRC        = true;
	$ENCODE_IMAGES                 = false;
	$ENCODE_IMAGES_TVSHOW          = false;
	$IMAGE_DELIVERY                = 'wrapped'; // options: 'wrapped', 'encoded' or 'direct'
	
	$USESETS                       = true;
	$LOCALHOST                     = false;
	
	$DETAILFANART                  = true;
	$DETAILFANARTHEIGHT            = 576;
	
	$COPYASSCRIPT_ENABLED          = false;
	$COPYASSCRIPT_COPY_WIN         = true;
	#$COPYASSCRIPT_COPY_TO          = '/media/Elements/movies';
	$COPYASSCRIPT_COPY_TO          = 'D:\\';
	$COPYASSCRIPT_COPY_FROM        = 'X:\\';
	
	$SEARCH_ENABLED                = true;
	$CUTS_ENABLED                  = true;  // extended, directors, uncut / unrated
	$DREID_ENABLED                 = false; // 3d
	$CHOOSELANGUAGES               = true;
	
	$CUT_OFF_MOVIENAMES            = -1; //35;
	$SPOILPROTECTION               = true;
	
	$MAXMOVIEINFOLEN               = 300;
	
	$COVER_OVER_TITLE              = true;
	$SHOW_TRAILER                  = false;
	
	$GIB_AS_GB                     = true;
	
	$TVDB_LANGUAGE                 = 'en'; // 'de';
	$LANG                          = 'EN';
	
	$importLogging                 = true;
	
	$NAS_CONTROL                   = false;
	$NAS_IP                        = null;
	
	$BIND_CTRL_F                   = true;
	
	//- sensitive Data -//
	$LOGIN_USERNAME                = '';
	$LOGIN_PASSWORT                = '';
	$XBMCCONTROL_ENABLED           = false;
	$JSON_USERNAME                 = 'xbmc';
	$JSON_PASSWORT                 = 'admin';
	$JSON_PORT                     = 8018;
	$PRIVATE_FOLDER                = null;
	//- sensitive Data -//
?>