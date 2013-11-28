<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/config.php";
include_once "./template/matrix.php";
include_once "./template/functions.php";
include_once "globals.php";
include_once "_FILME.php";
?>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>XBMC Database</title>
	<link rel="shortcut icon" href="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/docs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/select/select2.css" media="screen" />
<!--
	<link rel="stylesheet" type="text/css" href="./class.css" />
-->
<link rel="stylesheet" href="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.css" />
<script src="http://code.jquery.com/jquery-1.9.1.min.js"></script>
<script src="http://code.jquery.com/mobile/1.3.1/jquery.mobile-1.3.1.min.js"></script>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/highlight.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/select/select2.min.js"></script>
	<script type="text/javascript" src="./template/js/jquery.marquee.min.js"></script>
	<script type="text/javascript">
<?php
	$bindF = isset($GLOBALS['BIND_CTRL_F']) ? $GLOBALS['BIND_CTRL_F'] : true;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var isAdmin = '.(isAdmin() ? '1' : '0').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
?>
	</script>
<?php if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/xbmcJson.js"></script>
<?php } ?>
	<script type="text/javascript" src="./template/js/filme.js"></script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	$isAdmin = isAdmin();
	
	$maself = $_SERVER['PHP_SELF'];
	$isMain = (substr($maself, -9) == 'index.php');
	postNavBar($isMain);

	$mode = 0;
	if (isset($_SESSION['mode'])) { $mode = $_SESSION['mode']; }

	$newmode = (isset($_SESSION['newmode']) ? $_SESSION['newmode'] : 0);
	$newsort = (isset($_SESSION['newsort']) ? $_SESSION['newsort'] : 0);
	$gallerymode = (isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0);

	$which = (isset($_SESSION['which']) ? $_SESSION['which'] : '');
	$just = (isset($_SESSION['just']) ? $_SESSION['just'] : '');
	$country = (isset($_SESSION['country']) ? $_SESSION['country'] : '');

#if (isAdmin()) { pre( print_r($_SESSION) ); }

	echo '<div class="tabDiv" onmouseover="closeNavs();">';
	echo "\r\n";
	echo '<table class="'.($gallerymode != 1 ? 'film' : 'gallery').'" cellspacing="0" data-role="table">';
	echo "\r\n";

	createTable();

	if ($isAdmin && !$gallerymode) {
		echo "\t";
		echo '<tr><td colspan="'.getColSpan().'" class="optTD">';
		echo '<div style="float:right; padding:4px 5px;">';
		echo '<input type="submit" value="Ok" name="submit" class="okButton">';
		echo '</div>';
		echo '<div style="float:right; padding-top:2px; margin-right:165px;">';
		echo '<select class="styled-select2" name="aktion" size="1">';
		echo '<option value="0" label="          "></option>';
		echo '<option value="1">mark as unseen</option>';
		echo '<option value="2">mark as seen</option>';
		echo '<option value="3">delete</option>';
		echo '</select>';
		echo '</div>';
		echo '</td></tr>';
		echo "\r\n\t";
	}

	echo '</form>';
	echo "\r\n";
   	echo '</table>';
	echo "\r\n";
	echo '</div>';

	if ($COPYASSCRIPT_ENABLED || !$isAdmin) {
		if ($gallerymode != 1) {
			echo "\r\n";
			echo '<div id="movieList" class="lefto" style="padding-left:15px; z-order=1; height:60px; display:none;">'."\r\n";
			echo "\t".'<div>';
			if ($COPYASSCRIPT_ENABLED && !$isAdmin) {
				echo "\t<input type='checkbox' id='copyAsScript' onClick='doRequest(".$isAdmin."); return true;' style='float:left;'/><label for='copyAsScript' style='float:left; margin-top:-5px;'>as copy script</label>";
				echo "<br/>";
			}
			echo "<input type='button' name='orderBtn' id='orderBtn' onclick='saveSelection(".$isAdmin."); return true;' value='save'/>";
			echo '</div>';
			
			echo "\r\n\t".'<div id="result" class="selectedfield"></div>'."\r\n";
			echo '</div>'."\r\n";
		}
	}
?>

<?php
/*          FUNCTIONS          */
function infobox($title, $info) {
	echo '<div id="boxx"><a href="#">';
	echo $title;
	echo '<span>';
 	echo $info;
	echo '</span></a></div>';
}

function getColspan() {
	$gallerymode   = $GLOBALS['gallerymode'];
	$elementsInRow = getElemsInRow();
	$COLUMNCOUNT   = isset($GLOBALS['COLUMNCOUNT']) ? $GLOBALS['COLUMNCOUNT'] : 9;
	$newAddedCount = getNewAddedCount();
	return ($gallerymode != 1 ? $COLUMNCOUNT : ($newAddedCount < $elementsInRow ? $newAddedCount : $elementsInRow));
}

function generateForm() {
	$newmode = $GLOBALS['newmode'];
	$newsort = $GLOBALS['newsort'];
	
	$mode = $GLOBALS['mode'];
	echo "\t";
	echo '<form action="index.php" name="moviefrm" method="post">';
	echo "\r\n";
	
	if ($newmode) {
		$newAddedCount = getNewAddedCount();
		
		if (!isAdmin()) {
			$sizes = isset($GLOBALS['SHOW_NEW_VALUES']) ? $GLOBALS['SHOW_NEW_VALUES'] : array(30, 60);

			echo "\t";
			echo '<tr>';
			echo '<th class="newAddedTH" colspan="'.getColSpan().'">';
			echo '<div style="float:right; padding-top:1px; margin-right:55px;">';
			echo '<select class="styled-select" name="newAddedCount" size="1" onChange="newlyChange();">';
			for ($i = 0; $i < count($sizes); $i++) {
				$size = $sizes[$i];
				echo '<option value="'.$size.'"'.($size == $newAddedCount ? ' SELECTED' : '').'>'.$size.'</option>';
			}
			echo '</select>';
			echo '</div>';
			echo '<div style="float:right; padding:8px 8px; font-size:9pt;">show newest: </div>';
			echo '</th>';
			echo '</tr>';
			echo "\r\n";
		}
	}
}

function createTable() {
	$hostname = $_SERVER['HTTP_HOST'];
	
	$isAdmin = isAdmin();
	$dev = $isAdmin;
	$NAS_CONTROL = isset($GLOBALS['NAS_CONTROL']) ? $GLOBALS['NAS_CONTROL'] : false;
	$COVER_OVER_TITLE = isset($GLOBALS['COVER_OVER_TITLE']) ? $GLOBALS['COVER_OVER_TITLE'] : false;
	
	$IMDB = $GLOBALS['IMDB'];
	$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$IMDBFILMTITLE = $GLOBALS['IMDBFILMTITLE'];
	$FILMINFOSEARCH = $GLOBALS['FILMINFOSEARCH'];
	$PERSONINFOSEARCH = $GLOBALS['PERSONINFOSEARCH'];
	
	$mode          = 1;
	$sort          = null;
	$unseen        = '';
	$dbSearch      = null;
	$newmode       = 0;
	$newsort       = 0;
	$thumbsize     = 1;
	$gallerymode   = 0;
	$newAddedCount = getNewAddedCount();
	$elemsInRow    = getElemsInRow();
	
	$zeilen = array();
	
	$dbh = getPDO();
	try {
		$dbh->beginTransaction();
		$existArtTable = existsArtTable($dbh);
		checkFileInfoTable($dbh);
		checkFileMapTable($dbh);
		existsOrdersTable($dbh);

		$_just  = $GLOBALS['just'];
		$_which = $GLOBALS['which'];
		if (isset($_SESSION['mode']))        { $mode          = $_SESSION['mode'];        }
		if (isset($_SESSION['sort']))        { $sort          = $_SESSION['sort'];        }
		if (isset($_SESSION['unseen']))      { $unseen        = $_SESSION['unseen'];      }
		if (isset($_SESSION['dbSearch']))    { $dbSearch      = $_SESSION['dbSearch'];    }
		if (isset($_SESSION['newmode']))     { $newmode       = $_SESSION['newmode'];     }
		if (isset($_SESSION['newsort']))     { $newsort       = $_SESSION['newsort'];     }
		if (isset($_SESSION['gallerymode'])) { $gallerymode   = $_SESSION['gallerymode']; }
		if (isset($_SESSION['thumbsize']))   { $thumbsize     = $_SESSION['thumbsize'];   }
		
		$sessionKey = 'movies_';
		if ($unseen == '' || (!empty($_just) && !empty($_which) || !empty($dbSearch)) ) {
			$unseen = 3;
		}
		$sessionKey .= 'unseen_'.$unseen.'_';

		$saferSearch = '';
		if (!empty($dbSearch)) {
			$mode = $newmode = 0;
			$saferSearch = strtolower(SQLite3::escapeString($dbSearch));
			$sessionKey .= 'search_'.str_replace(' ', '-', $saferSearch).'_';
			$filter = 	" AND (".
					" lower(A.c00) LIKE '%".$saferSearch."%' OR".
					" lower(A.c01) LIKE '%".$saferSearch."%' OR".
					" lower(A.c03) LIKE '%".$saferSearch."%' OR".
					" lower(A.c14) LIKE '%".$saferSearch."%' OR".
					" lower(A.c16) LIKE '%".$saferSearch."%'".
					")";
			
		} else if ($_which == 'artist') {
			$filter = " AND E.idActor = '".$_just."' AND E.idMovie = A.idMovie";
			$sessionKey .= 'just_'.$filter.'_';

		} else if ($_which == 'regie') {
			$filter = " AND D.idDirector = '".$_just."' AND D.idMovie = A.idMovie";
			$sessionKey .= 'just_'.$filter.'_';

		} else if ($_which == 'genre') {
			$filter = " AND G.idGenre = '".$_just."' AND G.idMovie = A.idMovie";
			$sessionKey .= 'just_'.$filter.'_';

		} else if ($_which == 'year') {
			$filter = " AND A.c07 = '".$_just."'";
			$sessionKey .= 'just_'.$filter.'_';
			
		} else if ($_which == 'set') {
			$filter = " AND A.idSet = '".$_just."'";
			$sessionKey .= 'just_'.$filter.'_';

		} else {
			$filter = null;
			$_which = null;
		}
		
		#if ($isAdmin) { $newAddedCount = 30; }

		$unseenCriteria = '';
		if ($unseen == "0") {
			$unseenCriteria = " AND B.playCount > 0 ";
		} else if ($unseen == "1") {
			$unseenCriteria = " AND (B.playCount = 0 OR B.playCount IS NULL)";
		} else if ($unseen == "3") {
			$unseenCriteria = '';
		}
		
		$SQL =  "SELECT DISTINCT A.idFile, A.idMovie, A.c05, B.playCount, A.c00, A.c01, A.c02, A.c14, A.c15, B.strFilename AS filename, ".
			#"A.idFile, A.idMovie, ".
			"M.dateAdded as dateAdded, M.value as dateValue, ".
			#"SD.iVideoWidth as vWidth, ".
			"A.c07 AS jahr, A.c08 AS thumb, A.c11 AS dauer, A.c19 AS trailer, A.c09 AS imdbId, C.strPath AS path, F.filesize ".
			"FROM movie A, files B, path C ".
			"LEFT JOIN fileinfo F ON B.idFile = F.idFile ".
			"LEFT JOIN filemap M ON B.strFilename = M.strFilename ".
			#"LEFT JOIN streamdetails SD ON (B.idFile = SD.idFile AND SD.iVideoWidth IS NOT NULL) ".
			(isset($mode) && ($mode == 2) ? "LEFT JOIN streamdetails SD ON (B.idFile = SD.idFile AND SD.strAudioLanguage IS NOT NULL) " : '').
			(isset($filter, $_which) && ($_which == 'artist') ? ", actorlinkmovie E" : '').
			(isset($filter, $_which) && ($_which == 'regie') ? ", directorlinkmovie D" : '').
			(isset($filter, $_which) && ($_which == 'genre') ? ", genrelinkmovie G" : '').
			" WHERE A.idFile = B.idFile AND C.idPath = B.idPath ";
			
		if (!empty($sort)) {
			     if ($sort == 'jahr')    { $sessionKey .= 'orderJahr_';    $sqlOrder = " ORDER BY A.c07 DESC, dateAdded DESC";      }
			else if ($sort == 'jahra')   { $sessionKey .= 'orderJahrA_';   $sqlOrder = " ORDER BY A.c07 ASC, dateAdded ASC";        }
			else if ($sort == 'rating')  { $sessionKey .= 'orderRating_';  $sqlOrder = " ORDER BY A.c05 DESC";      }
			else if ($sort == 'ratinga') { $sessionKey .= 'orderRatingA_'; $sqlOrder = " ORDER BY A.c05 ASC";       }
			else if ($sort == 'size')    { $sessionKey .= 'orderSize_';    $sqlOrder = " ORDER BY F.filesize DESC"; }
			else if ($sort == 'sizea')   { $sessionKey .= 'orderSizeA_';   $sqlOrder = " ORDER BY F.filesize ASC";  }
			
		} else if ($newmode) {
			$sqlOrder = " ORDER BY ".($newsort == 1 ? "dateValue" : "F.idFile")." DESC";
			$sessionKey .= ($newsort == 1 ? 'orderDateValue_' : 'orderIdMovie_');
			
		} else {
			$sqlOrder = " ORDER BY A.c00 ASC";
			$sessionKey .= 'orderName_';
		}
		
		switch ($mode) {
			case 2:
				$country = $GLOBALS['country'];
				$LANGMAP = isset($GLOBALS['LANGMAP']) ? $GLOBALS['LANGMAP'] : array();
				$_country = '';
				if ($country != '') {
					$_country = " AND (SD.strAudioLanguage LIKE '".$country."' ";
					if (isset($LANGMAP[$country])) {
						$map = $LANGMAP[$country];
						for ($c = 0; $c < count($map); $c++) {
							$_country .= " OR SD.strAudioLanguage LIKE '".$map[$c]."' ";
						}
					}
					
					$_country .= ") ";
				}
				
				$sessionKey .= $country;
				$SQL .= $_country;
			break;

			case 3:
				$uncut = " AND (lower(B.strFilename) LIKE '%director%' OR lower(A.c00) LIKE '%director%') ";
				$sessionKey .= 'directorCut';
			break;

			case 4:
				$uncut = " AND (lower(B.strFilename) LIKE '%extended%' OR lower(A.c00) LIKE '%extended%') ";
				$sessionKey .= 'extendedCut';
			break;

			case 5:
				$uncut = " AND (lower(B.strFilename) LIKE '%uncut%' OR lower(A.c00) LIKE '%uncut%') ";
				$sessionKey .= 'uncutCut';
			break;

			case 6:
				$uncut = " AND (lower(B.strFilename) LIKE '%unrated%' OR lower(A.c00) LIKE '%unrated%') ";
				$sessionKey .= 'unratedCut';
			break;

			case 7:
				$uncut = " AND (lower(B.strFilename) LIKE '%.3d.%' OR lower(A.c00) LIKE '%(3d)%') ";
				$sessionKey .= '3dCut';
			break;

			default:
				unset($uncut);
				#" AND (c21 not like 'Turkey%') ".
				#" AND (upper(B.strFilename) not like '%.AVI' AND upper(B.strFilename) not like '%.MPG' AND upper(B.strFilename) not like '%.DAT')".
		}
		
		$params = (isset($filter) ? $filter : '').(isset($uncut) ? $uncut : '').$unseenCriteria.$sqlOrder;
		$SQL .= $params.";";
#logc($SQL);
		
		$counter = 0;
		$zeile = 0;
		$counter2 = 0;
		$moviesTotal = 0;
		$moviesSeen = 0;
		$moviesUnseen = 0;
		
		$idGenre  = getGenres($dbh);
		$idStream = getResolution($dbh);
		
		$EXCLUDEDIRS  = isset($GLOBALS['EXCLUDEDIRS'])   ? $GLOBALS['EXCLUDEDIRS']   : array();
		$SHOW_TRAILER = isset($GLOBALS['SHOW_TRAILER'])  ? $GLOBALS['SHOW_TRAILER']  : false;
		//$ENCODE       = isset($GLOBALS['ENCODE_IMAGES']) ? $GLOBALS['ENCODE_IMAGES'] : true;
		
		$xbmcRunning = xbmcRunning();
		$pronoms = array('the ', 'der ', 'die ', 'das ');
		$result = fetchMovies($dbh, $SQL, $sessionKey);
		for($rCnt = 0; $rCnt < count($result); $rCnt++) {
			$row = $result[$rCnt];
			$zeilenSpalte = 0;
			
			$idFile    = $row['idFile'];
			$idMovie   = $row['idMovie'];
			#$id        = $row['idMovie'];
			$filmname  = $row['c00'];
			$watched   = $row['playCount'];
			$thumb     = $row['thumb'];
			$filename  = $row['filename'];
			$dateAdded = $row['dateAdded'];
			$path      = $row["path"];
			$jahr      = $row['jahr'];
			$filesize  = $row['filesize'];
			$playCount = $row['playCount'];
			$trailer   = $row['trailer'];
			$rating    = $row['c05'];
			$imdbId    = $row['imdbId'];
			$genres    = $row['c14'];
			$filename  = $row['filename'];
			$vRes      = isset($idStream[$idFile]) ? $idStream[$idFile] : null;
			$fnam      = $path.$filename;
			$cover     = '';
			
			if  (empty($dateAdded)) {
				$creation = getCreation($fnam);
				#logc( $creation );
				if (empty($creation)) {
					$creation = '2001-01-01 12:00:00';
				}
				$dateAdded = $creation;

				try {
					$datum = SQLite3::escapeString(strtotime($dateAdded));
					$dbfname = SQLite3::escapeString($filename);
					$dbh->exec("REPLACE INTO filemap(idFile, strFilename, dateAdded, value) VALUES(".$idFile.", '".$dbfname."', '".$dateAdded."', '".$datum."');");
					#$dbh->exec("UPDATE files SET dateAdded = '$dateAdded' WHERE idFile = '$idFile';");
				} catch(PDOException $e) {
					echo $e->getMessage();
				}
			}

			if ($gallerymode || $COVER_OVER_TITLE) {
				if (file_exists(getCoverThumb($fnam, $cover, false))) {
					$cover = getCoverThumb($fnam, $cover, false);
					
				} else if ($existArtTable) {
					$res2 = $dbh->query("SELECT url,type FROM art WHERE media_type = 'movie' AND (type = 'poster' OR type = 'thumb') AND media_id = '".$idMovie."';");
					foreach($res2 as $row2) {
						$type = $row2['type'];
						$url  = $row2['url'];
						if (!empty($url)) { $cover = getCoverThumb($url, $url, true); }
						if ($type == 'poster') { break; }
					}
				}
			}
			
			$path = mapSambaDirs($path);
			if (count($EXCLUDEDIRS) > 0 && isset($EXCLUDEDIRS[$path]) && $EXCLUDEDIRS[$path] != $mode) { continue; }

			$fsize = fetchFileSize($idFile, $path, $filename, $filesize, $dbh);
			$moviesize = _format_bytes($fsize);

			$filmname0 = $filmname;
			$titel = $filmname;
			
			$wasCutoff = false;
			$cutoff = isset($GLOBALS['CUT_OFF_MOVIENAMES']) ? $GLOBALS['CUT_OFF_MOVIENAMES'] : -1;
			if (strlen($filmname) >= $cutoff && $cutoff > 0) {
				$filmname = substr($filmname, 0, $cutoff).'...';
				$wasCutoff = true;
			}

			$pr = strtolower(substr($filmname, 0, 4));
			for ($prs = 0; $prs < count($pronoms); $prs++) {
				if ($pr == $pronoms[$prs]) {
					$part1 = strtoupper(substr($filmname, 4, 1)).substr($filmname, 5, strlen($filmname));
					$part2 = ', '.substr($filmname, 0, 3);
					$filmname = $part1.$part2;
				}
			}
			
			#$_SESSION['thumbs']['cover'][$idMovie] = $cover;
			wrapItUp('cover', $idMovie, $cover, $sessionKey);

			if ($gallerymode) {
					$zeilen[$counter][0] = $filmname.($jahr != 0 ? ' ('.$jahr.')' : '');
					$zeilen[$counter][1] = 'show=details&idShow='.$idMovie;
					$zeilen[$counter][2] = $watched;
					$zeilen[$counter][3] = getImageWrap($cover, $idMovie, 'movie', 0);
					$zeilen[$counter][4] = is3d($filename);
					$zeilen[$counter][5] = $path;
					$zeilen[$counter][6] = $filename;
					$counter++;

			} else {
				
				$zeilen[$zeile][$zeilenSpalte++] = $filmname;
#counter
				$spalTmp = '<td class="countTD">';
				#if ($COVER_OVER_TITLE && !empty($cover)) { $spalTmp .= '<a class="hoverpic" rel="'.$cover.'" style="cursor:default;">'; }
				if ($COVER_OVER_TITLE && !empty($cover)) { $spalTmp .= '<a class="hoverpic" rel="'.getImageWrap($cover, $idMovie, 'movie', 0).'" style="cursor:default;">'; }
				if ($isAdmin) { $spalTmp .= '<span class="fancy_movieEdit" href="./nameEditor.php?change=movie&idMovie='.$idMovie.'" style="cursor:pointer;">'; }
				$spalTmp .= '_C0UNTER_';
				if ($isAdmin) { $spalTmp .= '</span>'; }
				if ($COVER_OVER_TITLE && !empty($cover)) { $spalTmp .= '</a>'; }
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#checkbox
				$spalTmp = '<td class="titleTD">';
				$spalTmp .= '<input type="checkbox" name="checkFilme[]" id="opt_'.$idMovie.'" class="checka" value="'.$idMovie.'" onClick="selected(this, true, true, '.$isAdmin.'); return true;">';
#title
				$suffix = '';
				if (is3d($filename)) { $suffix = ' (3D)'; }
				if ($wasCutoff) { $spalTmp .= '<a class="fancy_iframe" href="./?show=details&idShow='.$id.'">'.$filmname.$suffix.'<span class="searchField" style="display:none;">'.$filmname0.'</span></a>'; }
				else { $spalTmp .= '<a class="fancy_iframe" href="./?show=details&idShow='.$idMovie.'"><span class="searchField">'.$filmname.$suffix.'</span></a>'; }
#seen
				if ($isAdmin) {
					if ($playCount >= 1) {
						$spalTmp .= ' <img src="img/check.png" class="check10">';
						$moviesSeen++;
					} else {
						$moviesUnseen++;
					}

					$moviesTotal++;
				}

				if ($SHOW_TRAILER && !empty($trailer)) {
					$spalTmp .= '<a class="fancy_iframe2" href="'.$ANONYMIZER.$trailer.'"> <img src="img/filmrolle.jpg" width=15px; border=0px;></a>';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#jahr
				$spalTmp = '<td class="yearTD';
				if (!empty($jahr)) {
					$spalTmp .= '">';
					$spalTmp .= '<a href="?show=filme&country=&mode=1&which=year&just='.$jahr.'&name='.$jahr.'" title="filter"><span class="searchField">'.$jahr.'</span></a>';
				} else {
					$spalTmp .= ' centro">-';
				}
				$spalTmp .= '</td>';

				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#bewertung
				$rating = substr($rating, 0, 4);
				$rating = substr($rating, 0, substr($rating, 2, 1) == '.' ? 4 : 3);
				
				$spalTmp = '<td class="ratingTD '.($rating > 0 ? 'righto' : 'centro').'">';
				if (!empty($imdbId)) {
					$spalTmp .= '<a class="openImdb" href="'.$ANONYMIZER.$IMDBFILMTITLE.$imdbId.'">';
				} else {
					$spalTmp .= '<a class="openImdb" href="'.$ANONYMIZER.$FILMINFOSEARCH.$titel.'">';
				}
				
				$spalTmp .= ($rating > 0 ? $rating : '&nbsp;&nbsp;-');
				$spalTmp .= '</a>';
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#artist
				$spalTmp = '<td class="actorTD"';
				$sql2 = "select A.strActor, B.strRole, B.idActor, A.strThumb as actorimage from actorlinkmovie B, actors A where A.idActor = B.idActor and B.idMovie = '$idMovie'";
				$result2 = $dbh->query($sql2);
				$firstartist = '';
				$firstId = '';
				$artist = '';
				$artistinfo = '';
				$actorpicURL = '';
				foreach($result2 as $row2) {
					$artist = $row2['strActor'];
					$idActor = $row2['idActor'];
					$actorpicURL = $row2['actorimage'];

					if (empty($firstartist)) {
						$firstartist = $artist;
						$firstId = $idActor;
						break;
					}
				}

				$actorimg = getActorThumb($firstartist, $actorpicURL, false);
				if (!file_exists($actorimg) && !empty($firstId) && $existArtTable) {
					$res3 = $dbh->query("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';");
					$row3 = $res3->fetch();
					$url = $row3['url'];
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				}
				
				if (!empty($firstartist) && !empty($firstId)) {
					#$_SESSION['thumbs']['actor'][$firstId] = $actorimg;
					wrapItUp('actor', $firstId, $actorimg, $sessionKey);
					
					$spalTmp .= '>';
					$spalTmp .= '<a class="openIMDB filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstartist.'">[i] </a>';

					$spalTmp .= '<a href="?show=filme&country=&mode=1&which=artist&just='.$firstId.'&name='.$firstartist.'"';
					if (file_exists($actorimg)) {
						#$spalTmp .= ' class="hoverpic" rel="'.$actorimg.'" title="'.$firstartist.'"';
						$spalTmp .= ' class="hoverpic" rel="'.getImageWrap($actorimg, $firstId, 'actor', 0).'" title="'.$firstartist.'"';
					} else {
						$spalTmp .= 'title="filter"';
					}

					$spalTmp .= '><span class="searchField">'.$firstartist.'</span></a>';
				} else {
					$spalTmp .= ' style="padding-left:40px;">-';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#genre
				$spalTmp = '<td class="genreTD"';
				$genres = explode("/", $genres);
				$genre = count($genres) > 0 ? trim($genres[0]) : '';
				$genreId = -1;

				if (!empty($genre)) {
					$spalTmp .= '>';
					$genre = ucwords(strtolower($genre));
					if (isset($idGenre[$genre])) {
						$genreId = $idGenre[$genre][0];
						$idGenre[$genre][1] = $idGenre[$genre][1] + 1;
					}

					if (($genreId != -1) && (!isset($_just) || empty($_just) || $_which != 'genre' || $_just != $genreId)) {
						$spalTmp .= '<a href="?show=filme&country=&mode=1&which=genre&just='.$genreId.'&name='.$genre.'" title="filter"><span class="searchField">'.$genre.'</span></a>';
					} else {
						$spalTmp .= '<span class="searchField">'.$genre.'</span>';
					}
				} else {
					$spalTmp .= ' style="padding-left:20px;">-';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#regie
				$spalTmp = '<td class="direcTD"';
				$sql3 = "SELECT A.strActor, B.idDirector, A.strThumb AS actorimage FROM directorlinkmovie B, actors A WHERE B.idDirector = A.idActor AND B.idMovie = '$idMovie'";
				$result3 = $dbh->query($sql3);
				$firstdirector = '';
				$firstId = '';
				$artistinfo = '';
				$actorpicURL = '';
				foreach($result3 as $row3) {
					$artist = $row3['strActor'];
					$idActor = $row3['idDirector'];
					$actorpicURL = $row3['actorimage'];

					if (empty($firstdirector)) {
						$firstdirector = $artist;
						$firstId = $idActor;
						break;
					}
				}
				
				$actorimg = getActorThumb($firstdirector, $actorpicURL, false);
				if (!file_exists($actorimg) && !empty($firstId) && $existArtTable) {
					$res3 = $dbh->query("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';");
					$row3 = $res3->fetch();
					$url = $row3['url'];
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				}
				
				if (!empty($firstdirector) && !empty($firstId)) {
					#$_SESSION['thumbs']['director'][$firstId] = $actorimg;
					wrapItUp('director', $firstId, $actorimg, $sessionKey);
					
					$spalTmp .= '>';
					$spalTmp .= '<a class="openImdb filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstdirector.'">[i] </a>';
					
					$spalTmp .= '<a href="?show=filme&country=&mode=1&which=regie&just='.$firstId.'&name='.$firstdirector.'"';
					if (file_exists($actorimg)) {
						#$spalTmp .= ' class="hoverpic" rel="'.$actorimg.'" title="'.$firstdirector.'"';
						$spalTmp .= ' class="hoverpic" rel="'.getImageWrap($actorimg, $firstId, 'director', 0).'" title="'.$firstdirector.'"';
					} else {
						$spalTmp .= 'title="filter"';
					}

					$spalTmp .= '><span class="searchField">'.$firstdirector.'</span></a>';
				} else {
					$spalTmp .= ' style="padding-left:40px;">-';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#resolution
				$resInfo = ( $vRes == null ? '-' : ($vRes[0] == 1920 ? '1080p' : '720p') );
				#if (isAdmin()) { $resInfo = '<img src="./img/'.($vRes[0] == 1920 ? '1080p' : '720p').'.png" style="height:16px;" />'; }
				#$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD" align="right" title="'.($zeile % 2 == 1 ? '' : ' ').$vRes[0].'x'.$vRes[1].($zeile % 2 == 0 ? '' : ' ').'"><span class="searchField">'.$resInfo.'</span></td>';
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD" align="right"'.($vRes != null ? ' title="'.$vRes[0].'x'.$vRes[1].'"').'><span class="searchField">'.$resInfo.'</span></td>';
#filesize
				$playItem = $isAdmin && $xbmcRunning && !empty($path) && !empty($filename) ? ' onclick="playItem(\''.encodeString($path.$filename).'\'); return false;" style="cursor:pointer;"' : null;
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD" align="right"'.$playItem.'>'.$moviesize.'</td>';
				
				$zeile++;
			} // else gallerymode == 1

			if ($newmode && ++$counter2 >= $newAddedCount) {
				break;
			}

		} //foreach
		
		if (!$newmode && empty($sort)) {
			sort($zeilen);
		}

		if ($gallerymode) {
			generateForm();

			$iElems = count($zeilen);

			echo "<tr>";
			$spread = -1;
			$thumbsAddedInRow = 0;
			for ($t = 0; $t < $iElems; $t++) {
				if ($t % $elemsInRow == 0 && $t > 0) {
					$thumbsAddedInRow = 0;
					echo "\n</tr>\r\n<tr>";

					if (($iElems - $t) < $elemsInRow) {
						$spread = $elemsInRow - ($iElems - $t);
						if ($spread > 0) {
							$matrix = getMatrix($iElems - $t);
						}
					}
				}

				if (isset($matrix)) {
					$thumbsAddedInRow = echoEmptyTdIfNeeded($matrix, $thumbsAddedInRow, $elemsInRow);
				}

				echo "\n\t";
				echo '<td class="galleryTD">';
				#echo '<a class="fancy_iframe" href="./?'.$zeilen[$t][1].'">';
				$covImg = (!empty($zeilen[$t][3]) ? $zeilen[$t][3] : './img/nothumb.png');
				//if ($ENCODE) { $covImg = base64_encode_image($covImg); }
				echo '<div class="galleryCover" style="background:url('.$covImg.') #FFFFFF no-repeat;">';
				echo '<div class="galleryCoverHref1 fancy_iframe" href="./?'.$zeilen[$t][1].'" title="'.$zeilen[$t][0].'"></div>';
				
				$playCount = $zeilen[$t][2] >= 1 && $isAdmin;
				$is3d      = $zeilen[$t][4];
				$path      = $zeilen[$t][5];
				$filename  = $zeilen[$t][6];
				 
				$showSpan = $is3d || $playCount;
				$break3d  = $is3d && $isAdmin && $playCount;
				$breakPl  = false;
				
				$playItem = '';
				if ($isAdmin && $xbmcRunning && !empty($path) && !empty($filename)) {
					$showSpan = true;
					$breakPl  = $playCount;
					$playItem = '<img src="./img/play.png" class="icon24 galleryPlay'.($is3d ? ' galleryPlay2nd' : '').'" onclick="playItem(\''.encodeString($path.$filename).'\'); return false;" />';
				}
				
				if ($showSpan) { echo '<div class="gallerySpan">'; }
					if ($showSpan && $is3d) { echo '<img src="./img/3d.png" class="icon24 gallery3d" />'; }
					if ($showSpan && $break3d) { echo '<br/>'; }
					echo $playItem;
					if ($showSpan && $breakPl) { echo '<br/>'; }
					if ($playCount) { echo '<img src="./img/check.png" class="icon32 gallery2nd" />'; }
				if ($showSpan) { echo '</div>'; }
				echo '</div>';
				#echo '</a>';
				echo '</td>';
				
				$thumbsAddedInRow++;
				
				if (isset($matrix)) {
					$thumbsAddedInRow = echoEmptyTdIfNeeded($matrix, $thumbsAddedInRow, $elemsInRow);
				}
			}
			echo "\n";
			echo '</tr>';
			echo "\n";
		} else { // if ($galleryMode)
			$titleInfo = '';
			if ($isAdmin && !$gallerymode && (!empty($unseen) || $unseen == 3)) {
				$percUnseen = '';
				$percSeen = '';

				if ($moviesTotal > 0 && $moviesTotal >= $moviesSeen && $moviesTotal >= $moviesUnseen) {
					$percUnseen = round($moviesUnseen / $moviesTotal * 100, 0).'%';
					$percSeen = round($moviesSeen / $moviesTotal * 100, 0).'%';
				}
			}

			if (!empty($saferSearch)) { $saferSearch = '&dbSearch='.$saferSearch; }
			echo "\t";
			echo '<thead>';
			echo '<tr><th class="th0"> </th>';
			echo '<th class="th4"><input type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this); return true;"><a style="font-weight:bold;" href="?sort='.($saferSearch).'">Title</a>'.$titleInfo.'</th>';
			echo '<th class="th0"><a style="font-weight:bold;'.(!empty($sort) && ($sort=='jahr' || $sort=='jahra') ? 'color:red;' : '').'" href="?sort='.($sort=='jahr' ? 'jahra' : 'jahr').($saferSearch).'">Year</a></th>';
			echo '<th class="th1"><a style="font-weight:bold;'.(!empty($sort) && ($sort=='rating' || $sort=='ratinga') ? 'color:red;' : '').'" href="?sort='.($sort=='rating' ? 'ratinga' : 'rating').($saferSearch).'">Rating</a></th>';
			echo '<th class="th2">Actor</th>';
			echo '<th class="th2">Genre</th>';
			echo '<th class="th2">Director</th>';
			echo '<th class="th5">Res</th>';
			echo '<th class="th5"><a style="font-weight:bold;'.(!empty($sort) && ($sort=='size' || $sort=='sizea') ? 'color:red;' : '').'" href="?sort='.($sort=='size' ? 'sizea' : 'size').($saferSearch).'">Size</a></th></tr>';
			echo '</thead>';
			echo "\r\n";
			
			echo '<tbody>';

			generateForm();

			$zeilenCount = count($zeilen);
			for ($z = 0; $z < $zeilenCount; $z++) {
				echo "\t".'<tr class="searchFlag">';
				$zeile = $zeilen[$z];
				for ($sp = 1; $sp < count($zeile); $sp++) {
					$spalte = str_replace('_C0UNTER_', $z+1, $zeile[$sp]);
					if ($z == $zeilenCount-1) {
						if (substr_count($spalte, '<td>') == 1) {
							$spalte = str_replace('<td>', '<td class="bottomTD">', $spalte);

						} else if (substr_count($spalte, 'class') >= 1) {
							$spalte = str_replace('<td class="', '<td class="bottomTD ', $spalte);

						} else {
							$spalte = str_replace('<td ', '<td class="bottomTD" ', $spalte);
						}
					}
					echo $spalte;
				}
				echo '</tr>'."\r\n";
			}
			
			echo '</tbody>';
		} // if ($galleryMode)
		
		$dbh->commit();

	} catch(PDOException $e) {
		$dbh->rollBack();
		echo $e->getMessage();
	}
} // function createTable

function echoEmptyTdIfNeeded($matrix, $thumbsAddedInRow, $elemsInRow) {
	while ($thumbsAddedInRow < $elemsInRow && $matrix[$thumbsAddedInRow] == 0) {
		echo "\n\t";
		echo '<td class="galleryEmptyTD">&nbsp;</td>';

		$thumbsAddedInRow++;
	}

	return $thumbsAddedInRow;
}
/*          FUNCTIONS          */
?>