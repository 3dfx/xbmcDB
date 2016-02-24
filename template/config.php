<?php
	$DB_PATH = '/public';

	$GAST_USERS                    = array(
#						''   => ''
					);
	
	#$DEMO_USERS                    = array( 'live' => 'demo' );
	$DEMO_ENABLED                  = isset($DEMO_USERS) && count($DEMO_USERS) > 0;
	
	$NO_LOG_FROM                   = array(
//						'admin' => array('host1.com', 'host2.com')
					);
	
	$REFF                          = 'HOST.dyndns.org';
	
	$TVSHOWDIR                     = ''; //'/mnt/media/Serien/';
	#$HIDE_WATCHED_ANY_EP_IN_MAIN   = array(1 => true, 2 => true);
	
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
	
	#$EXCLUDEDIRS                   = array('/mnt/media/folder/' => id);
	
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
	
	$MUSICVIDS_ENABLED             = false;
	
	$ELEMSINROW                    = 7; //values: 13, 11, 9, 7, 5 or 3
	$DEFAULT_NEW_ADDED             = 30;
	
	$USECACHE                      = true;
	
	$CHECK_NEXT_AIRDATE            = false;
	$RLS_OFFSET_IN_DAYS            = 1;
	$EUROPEAN_DATE_FORMAT          = false;
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
	$COPYASSCRIPT_COPY_TO          = 'G:\\folder1\\';
	$COPYASSCRIPT_COPY_FROM        = 'D:\\folder1\\';
	$COPYASSCRIPT_COPY_TO_SHOW     = 'G:\\folder2\\';
	$COPYASSCRIPT_COPY_FROM_SHOW   = 'D:\\folder2\\';
	
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
	
	$CPU_TEMPS                     = array(45,45,60);
	$ADMIN_INFO                    = true;
	$NAS_CONTROL                   = false;
	$NAS_IP                        = null;
	
	$BIND_CTRL_F                   = true;
	
	$BLACKLIST_RETRY_LIMIT         = 3;
	
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
