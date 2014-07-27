<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/config.php";
include_once "./template/matrix.php";
include_once "./template/functions.php";
include_once "./template/_FILME.php";
include_once "globals.php";
	
	$isAdmin = isAdmin();
	$isDemo  = isDemo();
	$maself  = getEscServer('PHP_SELF');
	$isMain  = (substr($maself, -9) == 'index.php');
	
	$newsort     = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;
	$gallerymode = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;
	$which       = isset($_SESSION['which'])       ? $_SESSION['which']       : null;
	$just        = isset($_SESSION['just'])        ? $_SESSION['just']        : null;
?>

<head>
<?php include("head.php"); ?>
	<script type="text/javascript">
<?php
	$bindF = isset($GLOBALS['BIND_CTRL_F']) ? $GLOBALS['BIND_CTRL_F'] : true;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var isAdmin = '.(isAdmin() ? '1' : '0').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
	echo "\t\t".'var newMovies = '.(checkLastHighest() && $newsort != 2 ? 'true' : 'false').";\r\n";
?>
		
		$(document).ready(function() {
			$('#myNavbar').load( './navbar.php?maself=<?php echo ($isMain ? 1 : 0); ?>', function() { initNavbarFancies(); } );
		});
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	postNavBar();
	
	echo '<div class="tabDiv" onmouseover="closeNavs();">';
//--	echo '<div class="tabDiv_" onmouseover="closeNavs();">';
	echo "\r\n";
	echo '<table class="'.($gallerymode ? 'gallery' : 'film').'" cellspacing="0">';
	echo "\r\n";
	
	createTable();
	
	if (isset($_SESSION['lastMovie']['seen']) && !isset($_SESSION['lastMovie']['set'])) { setLastHighest(); }
	if ($newsort == 2 && !$gallerymode) { $_SESSION['lastMovie']['seen'] = true; }
	if ($isAdmin && !$gallerymode) {
		echo "\t";
		echo '<tr><td colspan="'.getColSpan().'" class="optTD">';
		echo '<div style="float:right; padding:4px 5px;">';
		echo '<input tabindex="-1" type="submit" value="Ok" name="submit" class="okButton">';
		echo '</div>';
		echo '<div style="float:right; padding-top:2px; margin-right:5px;">';
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
	echo "\r\n".'</table>';
//--	echo "\r\n".'</div>';
	echo "\r\n".'</div>';
	
	if (!$isAdmin && !$isDemo) {
		if ($gallerymode != 1) {
			echo "\r\n";
			echo '<div id="movieList" class="lefto" style="padding-left:15px; z-order=1; height:60px; display:none;">'."\r\n";
			echo "\t".'<div>';
			if ($COPYASSCRIPT_ENABLED && !$isAdmin) {
				echo "\t<input tabindex='-1' type='checkbox' id='copyAsScript' onClick='doRequest(); return true;' style='float:left;'/><label for='copyAsScript' style='float:left; margin-top:-5px;'>as copy script</label>";
				echo "<br/>";
			}
			echo "<input tabindex='-1' type='button' name='orderBtn' id='orderBtn' onclick='saveSelection(); return true;' value='save'/>";
			echo '</div>';
			
			echo "\r\n\t".'<div id="result" class="selectedfield"></div>'."\r\n";
			echo '</div>'."\r\n";
		}
	}
?>
</body>
<?php
/*          FUNCTIONS          */
function createTable() {
	$newmode = isset($_SESSION['newmode']) ? $_SESSION['newmode'] : 0;
	$sort    = isset($_SESSION['sort'])    ? $_SESSION['sort']    : 0;
	$dbh     = getPDO();
	try {
		$dbh->beginTransaction();
		$existArtTable = existsArtTable($dbh);
		checkFileInfoTable($dbh);
		checkFileMapTable($dbh);
		#existsOrdersTable($dbh);
		existsOrderzTable($dbh);
		
		$SkQL        = getSessionKeySQL();
		$SQL         = $SkQL['SQL'];
		$sessionKey  = $SkQL['sessionKey'];
		$saferSearch = $SkQL['saferSearch'];
		
		$result = fetchMovies($dbh, $SQL, $sessionKey);
		$zeilen = generateRows($dbh, $result, $sessionKey);
		if (!$newmode && empty($sort)) { sort($zeilen); }
		postRows($dbh, $zeilen, $saferSearch);
		
		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->commit(); }
		
	} catch(PDOException $e) {
		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->rollBack(); }
		if (isAdmin()) { echo $e->getMessage(); }
	}
} // function createTable

function getSessionKeySQL() {
	$res = array();
	
	$_just       = $GLOBALS['just'];
	$_which      = $GLOBALS['which'];
	$mode        = isset($_SESSION['mode'])        ? $_SESSION['mode']        : 0;
	$sort        = isset($_SESSION['sort'])        ? $_SESSION['sort']        : 0;
	$unseen      = isset($_SESSION['unseen'])      ? $_SESSION['unseen']      : 0;
	$dbSearch    = isset($_SESSION['dbSearch'])    ? $_SESSION['dbSearch']    : null;
	$newmode     = isset($_SESSION['newmode'])     ? $_SESSION['newmode']     : 0;
	$newsort     = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;
	$gallerymode = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;
	$which       = isset($_SESSION['which'])       ? $_SESSION['which']       : null;
	$country     = isset($_SESSION['country'])     ? $_SESSION['country']     : null;
	
	$saferSearch = '';
	$sessionKey  = 'movies_';
	if (empty($unseen) || (!empty($_just) && !empty($_which) || !empty($dbSearch)) ) {
		$unseen = 3;
	}
	$sessionKey .= 'unseen_'.$unseen.'_';
	
	if (!empty($dbSearch)) {
		$mode = $newmode = 0;
		$saferSearch = strtolower(SQLite3::escapeString($dbSearch));
		$sessionKey .= 'search_'.str_replace(' ', '-', $saferSearch).'_';
		$filter      =  " AND (".
				" lower(A.c00) LIKE '%".$saferSearch."%' OR".
				" lower(A.c01) LIKE '%".$saferSearch."%' OR".
				" lower(A.c03) LIKE '%".$saferSearch."%' OR".
				" lower(A.c14) LIKE '%".$saferSearch."%' OR".
				" lower(A.c15) LIKE '%".$saferSearch."%' OR".
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
	
	$unseenCriteria = '';
	if ($unseen == "0") {
		$unseenCriteria = " AND B.playCount > 0 ";
	} else if ($unseen == "1") {
		$unseenCriteria = " AND (B.playCount = 0 OR B.playCount IS NULL)";
	} else if ($unseen == "3") {
		$unseenCriteria = '';
	}
	
	$SQL =  "SELECT DISTINCT A.idFile, A.idMovie, A.c05, B.playCount, A.c00, A.c01, A.c02, A.c14, A.c15, B.strFilename AS filename, ".
		"M.dateAdded as dateAdded, M.value as dateValue, ".
		"A.c07 AS jahr, A.c08 AS thumb, A.c11 AS dauer, A.c19 AS trailer, A.c09 AS imdbId, C.strPath AS path, F.filesize ".
		"FROM movie A, files B, path C ".
		"LEFT JOIN fileinfo F ON B.idFile = F.idFile ".
		"LEFT JOIN filemap M ON B.strFilename = M.strFilename ".
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
			//--$country = $GLOBALS['country'];
			$LANGMAP = isset($GLOBALS['LANGMAP']) ? $GLOBALS['LANGMAP'] : array();
			$_country = '';
			if (!empty($country)) {
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
	}
	
	$params = (isset($filter) ? $filter : '').(isset($uncut) ? $uncut : '').$unseenCriteria.$sqlOrder;
	$SQL .= $params.";";
	
	$res['SQL']         = $SQL;
	$res['sessionKey']  = $sessionKey;
	$res['saferSearch'] = $saferSearch;
	return $res;
} //getSessionKeySQL

function generateRows($dbh, $result, $sessionKey) {
	$isAdmin       = isAdmin();
	$isDemo        = isDemo();
	
	$xbmcRunning   = xbmcRunning();
	$newAddedCount = getNewAddedCount();
	
	$idGenre       = getGenres($dbh);
	$idStream      = getResolution($dbh);
	
	$existArtTable = existsArtTable($dbh);
	$artCovers     = fetchArtCovers($existArtTable, $dbh);
	$actorImgs     = fetchActorCovers($dbh);
	$directorImgs  = fetchDirectorCovers($dbh);
	
	$lastHighest   = $isDemo ? null : (isset($_SESSION['lastHighest']) ? $_SESSION['lastHighest'] : null);
	$pronoms       = array('the ', 'der ', 'die ', 'das ');
	$counter       = 0;
	$counter2      = 0;
	
	$_just            = $GLOBALS['just'];
	$_which           = $GLOBALS['which'];
	$IMDB             = $GLOBALS['IMDB'];
	$ANONYMIZER       = $GLOBALS['ANONYMIZER'];
	$IMDBFILMTITLE    = $GLOBALS['IMDBFILMTITLE'];
	$FILMINFOSEARCH   = $GLOBALS['FILMINFOSEARCH'];
	$PERSONINFOSEARCH = $GLOBALS['PERSONINFOSEARCH'];
	$newmode          = isset($_SESSION['newmode'])         ? $_SESSION['newmode']         : 0;
	$gallerymode      = isset($_SESSION['gallerymode'])     ? $_SESSION['gallerymode']     : 0;
	$mode             = isset($_SESSION['mode'])            ? $_SESSION['mode']            : 0;
	$COVER_OVER_TITLE = isset($GLOBALS['COVER_OVER_TITLE']) ? $GLOBALS['COVER_OVER_TITLE'] : false;
	$EXCLUDEDIRS      = isset($GLOBALS['EXCLUDEDIRS'])      ? $GLOBALS['EXCLUDEDIRS']      : array();
	$SHOW_TRAILER     = isset($GLOBALS['SHOW_TRAILER'])     ? $GLOBALS['SHOW_TRAILER']     : false;
	
	$zeile  = 0;
	$zeilen = array();
	for($rCnt = 0; $rCnt < count($result); $rCnt++) {
		$zeilenSpalte = 0;
		$row       = $result[$rCnt];
		$idFile    = $row['idFile'];
		if ($idFile < 0) { continue; }
		$idMovie   = $row['idMovie'];
		$filmname  = $row['c00'];
		$watched   = $row['playCount'];
		$thumb     = $row['thumb'];
		$filename  = $row['filename'];
		$dateAdded = $row['dateAdded'];
		$path      = $row['path'];
		$jahr      = $row['jahr'];
		$filesize  = $row['filesize'];
		$playCount = $row['playCount'];
		$trailer   = $row['trailer'];
		$rating    = $row['c05'];
		$imdbId    = $row['imdbId'];
		$genres    = $row['c14'];
		$filename  = $row['filename'];
		$vRes      = isset($idStream[$idFile]) ? $idStream[$idFile] : array();
		$fnam      = $path.$filename;
		$cover     = null;
		$isNew     = !empty($lastHighest) && $idMovie > $lastHighest;
		
		if (empty($dateAdded)) {
			$creation = getCreation($fnam);
			if (empty($creation)) { $creation = '2001-01-01 12:00:00'; }
			$dateAdded = $creation;
			if (!empty($dateAdded)) {
				$datum = strtotime($dateAdded);
				$SQL_ = "REPLACE INTO filemap(idFile, strFilename, dateAdded, value) VALUES(".$idFile.", '".$filename."', '".$dateAdded."', '".$datum."');";
				execSQL_($dbh, $SQL_, false, true);
			}
		}
#covers
		if ($gallerymode || $COVER_OVER_TITLE) {
			if (!empty($artCovers)) {
				$cover_ = getCoverThumb($fnam, $cover, false);
				if (!empty($cover_) && file_exists($cover_)) {
					$cover = $cover_;
				} else if ($existArtTable && isset($artCovers['movie'][$idMovie])) {
					$cover = $artCovers['movie'][$idMovie]['cover'];
				}
			} else {
				if (file_exists(getCoverThumb($fnam, $cover, false))) {
					$cover = getCoverThumb($fnam, $cover, false);
					
				} else if ($existArtTable) {
					$SQL_ = "SELECT url,type FROM art WHERE media_type = 'movie' AND (type = 'poster' OR type = 'thumb') AND media_id = '".$idMovie."';";
					$res2 = querySQL_($dbh, $SQL_, false);
					foreach($res2 as $row2) {
						$type = $row2['type'];
						$url  = $row2['url'];
						if (!empty($url)) { $cover = getCoverThumb($url, $url, true); }
						if ($type == 'poster') { break; }
					}
				}
			} //POWERFUL_CPU
		}
		
		$path = mapSambaDirs($path);
		if (count($EXCLUDEDIRS) > 0 && isset($EXCLUDEDIRS[$path]) && $EXCLUDEDIRS[$path] != $mode) { continue; }
		
		$fsize     = fetchFileSize($idFile, $path, $filename, $filesize, $dbh);
		$moviesize = _format_bytes($fsize);
		
		$filmname0 = $filmname;
		$titel     = $filmname;
		
		$wasCutoff = false;
		$cutoff    = isset($GLOBALS['CUT_OFF_MOVIENAMES']) ? $GLOBALS['CUT_OFF_MOVIENAMES'] : -1;
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
			if ($COVER_OVER_TITLE && !empty($cover)) { $spalTmp .= '<a tabindex="-1" class="hoverpic" rel="'.getImageWrap($cover, $idMovie, 'movie', 0).'" style="cursor:default;">'; }
			if ($isAdmin) { $spalTmp .= '<span class="fancy_movieEdit" href="./nameEditor.php?change=movie&idMovie='.$idMovie.'" style="cursor:pointer;">'; }
			$spalTmp .= '_C0UNTER_';
			if ($isAdmin) { $spalTmp .= '</span>'; }
			if ($COVER_OVER_TITLE && !empty($cover)) { $spalTmp .= '</a>'; }
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;				
#checkbox
			$spalTmp = '<td class="titleTD"'.($isNew ? ' style="font-weight:bold;"' : '').'>';
			if (!$isDemo) {
			$spalTmp .= '<input tabindex="-1" type="checkbox" name="checkFilme[]" id="opt_'.$idMovie.'" class="checka" value="'.$idMovie.'" onClick="selected(this, true, true, '.$isAdmin.'); return true;">';
			}
#title
			$suffix = '';
			if (is3d($filename)) { $suffix = ' (3D)'; }
			if ($wasCutoff) { $spalTmp .= '<a tabindex="-1" class="fancy_iframe" href="./?show=details&idShow='.$id.'">'.$filmname.$suffix.'<span class="searchField" style="display:none;">'.$filmname0.'</span></a>'; }
			else { $spalTmp .= '<a tabindex="-1" class="fancy_iframe" href="./?show=details&idShow='.$idMovie.'"><span class="searchField">'.$filmname.$suffix.'</span></a>'; }
#seen
			$spalTmp .= ($isAdmin && $playCount >= 1) ? ' <img src="img/check.png" class="check10">' : '';
			/* //--
			if ($isAdmin) {
				if ($playCount >= 1) {
					$spalTmp .= ' <img src="img/check.png" class="check10">';
					$moviesSeen++;
				} else {
					$moviesUnseen++;
				}
				$moviesTotal++;
			}
			*/
			if ($SHOW_TRAILER && !empty($trailer)) {
				$spalTmp .= '<a tabindex="-1" class="fancy_iframe2" href="'.$ANONYMIZER.$trailer.'"> <img src="img/filmrolle.jpg" width=15px; border=0px;></a>';
			}
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#jahr
			$spalTmp = '<td class="yearTD';
			$spalTmp .= !empty($jahr) ? '"><a tabindex="-1" href="?show=filme&country=&mode=1&which=year&just='.$jahr.'&name='.$jahr.'" title="filter"><span class="searchField">'.$jahr.'</span></a>' : ' centro">-';
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#bewertung
			$rating = substr($rating, 0, 4);
			$rating = substr($rating, 0, substr($rating, 2, 1) == '.' ? 4 : 3);
			
			$spalTmp = '<td class="ratingTD '.($rating > 0 ? 'righto' : 'centro').'">';
			if (!empty($imdbId)) {
				$spalTmp .= '<a tabindex="-1" class="openImdb" href="'.$ANONYMIZER.$IMDBFILMTITLE.$imdbId.'">';
			} else {
				$spalTmp .= '<a tabindex="-1" class="openImdb" href="'.$ANONYMIZER.$FILMINFOSEARCH.$titel.'">';
			}
			
			$spalTmp .= ($rating > 0 ? $rating : '&nbsp;&nbsp;-');
			$spalTmp .= '</a>';
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#artist
			$spalTmp = '<td class="actorTD"';
			$firstId     = '';
			$firstartist = '';
			$actorpicURL = '';
			if (!empty($actorImgs)) {
				if (isset($actorImgs[$idMovie])) {
					$firstId     = $actorImgs[$idMovie]['id'];
					$actorpicURL = $actorImgs[$idMovie]['image'];
					$firstartist = $actorImgs[$idMovie]['artist'];
				}
			} else {
				$SQL_ = "SELECT A.strActor, B.strRole, B.idActor, A.strThumb AS actorimage FROM actorlinkmovie B, actors A WHERE A.idActor = B.idActor AND B.iOrder = 0 AND B.idMovie = '".$idMovie."';";
				$result2 = querySQL_($dbh, $SQL_, false);
				foreach($result2 as $row2) {
					$artist      = $row2['strActor'];
					$idActor     = $row2['idActor'];
					$actorpicURL = $row2['actorimage'];
					
					if (empty($firstartist)) {
						$firstartist = $artist;
						$firstId     = $idActor;
						break;
					}
				}
			} //POWERFUL_CPU
			
			$actorimg = getActorThumb($firstartist, $actorpicURL, false);
			if (!file_exists($actorimg) && !empty($firstId) && $existArtTable) {
				if (!empty($artCovers)) {
					if (isset($artCovers['actor'][$firstId])) {
						$actorimg = $artCovers['actor'][$firstId];
					}
				} else {
					$SQL_ = "SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';";
					$res3 = querySQL_($dbh, $SQL_, false);
					$row3 = $res3->fetch();
					$url = $row3['url'];
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				} //POWERFUL_CPU
			}
			
			if (!empty($firstartist) && !empty($firstId)) {
				wrapItUp('actor', $firstId, $actorimg, $sessionKey);
				
				$spalTmp .= '>';
				$spalTmp .= '<a tabindex="-1" class="openIMDB filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstartist.'">[i] </a>';
				$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=artist&just='.$firstId.'&name='.$firstartist.'"';
				if (file_exists($actorimg)) {
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
					$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=genre&just='.$genreId.'&name='.$genre.'" title="filter"><span class="searchField">'.$genre.'</span></a>';
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
			$firstId       = '';
			$firstdirector = '';
			$actorpicURL   = '';
			if (!empty($directorImgs)) {
				if (isset($directorImgs[$idMovie])) {
					$firstId       = $directorImgs[$idMovie]['id'];
					$actorpicURL   = $directorImgs[$idMovie]['image'];
					$firstdirector = $directorImgs[$idMovie]['artist'];
				}
			} else {
				$SQL_ = "SELECT A.strActor, B.idDirector, A.strThumb AS actorimage FROM directorlinkmovie B, actors A WHERE B.idDirector = A.idActor AND B.idMovie = '".$idMovie."';";
				$result3 = querySQL_($dbh, $SQL_, false);
				foreach($result3 as $row3) {
					$artist      = $row3['strActor'];
					$idActor     = $row3['idDirector'];
					$actorpicURL = $row3['actorimage'];
					
					if (empty($firstdirector)) {
						$firstdirector = $artist;
						$firstId = $idActor;
						break;
					}
				}
			} //POWERFUL_CPU
			
			$actorimg = getActorThumb($firstdirector, $actorpicURL, false);
			if (!file_exists($actorimg) && !empty($firstId) && $existArtTable) {
				if (!empty($artCovers)) {
					if (isset($artCovers['actor'][$firstId])) {
						$actorimg = $artCovers['actor'][$firstId];
					}
				} else {
					$SQL_ = "SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';";
					$res3 = querySQL_($dbh, $SQL_, false);
					$row3 = $res3->fetch();
					$url = $row3['url'];
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				} //POWERFUL_CPU
			}
			
			if (!empty($firstdirector) && !empty($firstId)) {
				wrapItUp('director', $firstId, $actorimg, $sessionKey);
				
				$spalTmp .= '>';
				$spalTmp .= '<a tabindex="-1" class="openImdb filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstdirector.'">[i] </a>';
				$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=regie&just='.$firstId.'&name='.$firstdirector.'"';
				if (file_exists($actorimg)) {
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
			if (!$isDemo) {
				$resInfo = (!empty($vRes) ? ($vRes[0] == 1920 ? '1080p' : ($vRes[0] == 1280 ? '720p' : '480p')) : '');
				$resTip  = (!empty($vRes) ? $vRes[0].'x'.$vRes[1] : '');
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD" align="right" title="'.$resTip.'"><span class="searchField">'.$resInfo.'</span></td>';
#filesize
				$filename = prepPlayFilename($path.$filename);
				$playItem = $isAdmin && $xbmcRunning && !empty($path) && !empty($filename) ? ' onclick="playItem(\''.$filename.'\'); return false;" style="cursor:pointer;"' : null;
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD" align="right"'.$playItem.'>'.$moviesize.'</td>';
			}
			
			$zeile++;
		} // else gallerymode == 1
		
		if ($newmode && ++$counter2 >= $newAddedCount) { break; }
	} //foreach
	
	return $zeilen;
} //generateRows

function postRows($dbh, $zeilen, $saferSearch) {
	$isAdmin       = isAdmin();
	$isDemo        = isDemo();
	
	$xbmcRunning   = xbmcRunning();
	$newAddedCount = getNewAddedCount();
	$existArtTable = existsArtTable($dbh);
	$elemsInRow    = getElemsInRow();
	
	$_just         = $GLOBALS['just'];
	$_which        = $GLOBALS['which'];
	$sort          = isset($_SESSION['sort'])        ? $_SESSION['sort']        : 0;
	$unseen        = isset($_SESSION['unseen'])      ? $_SESSION['unseen']      : 0;
	$gallerymode   = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;
	
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
			$covImg = (!empty($zeilen[$t][3]) ? $zeilen[$t][3] : './img/nothumb.png');
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
			$filename = prepPlayFilename($path.$filename);
			if ($isAdmin && $xbmcRunning && !empty($path) && !empty($filename)) {
				$showSpan = true;
				$breakPl  = $playCount;
				$playItem = '<img src="./img/play.png" class="icon24 galleryPlay'.($is3d ? ' galleryPlay2nd' : '').'" onclick="playItem(\''.$filename.'\'); return false;" />';
			}
			
			if ($showSpan) { echo '<div class="gallerySpan">'; }
			if ($showSpan && $is3d) { echo '<img src="./img/3d.png" class="icon24 gallery3d" />'; }
			echo $playItem;
			
			$gCnt = 0;
			if ($xbmcRunning) { $gCnt++; }
			if ($break3d)     { $gCnt++; }
			if ($playCount)   { $gCnt++; }
			
			switch ($gCnt) {
				case 3:
					$gCount = '3rd';
					break;
				case 2:
					$gCount = '2nd';
					break;
				case 1:
				default:
					$gCount = '1st';
					break;
			}
			
			if ($playCount) { echo '<img src="./img/check.png" class="icon32 gallery'.$gCount.'" />'; }
			if ($showSpan) { echo '</div>'; }
			echo '</div>';
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
		/*
		if ($isAdmin && !$gallerymode && (!empty($unseen) || $unseen == 3)) {
			$percUnseen = '';
			$percSeen = '';
			
			if ($moviesTotal > 0 && $moviesTotal >= $moviesSeen && $moviesTotal >= $moviesUnseen) {
				$percUnseen = round($moviesUnseen / $moviesTotal * 100, 0).'%';
				$percSeen = round($moviesSeen / $moviesTotal * 100, 0).'%';
			}
		}
		*/
		
		if (!empty($saferSearch)) { $saferSearch = '&dbSearch='.$saferSearch; }
		echo "\t";
		echo '<tr><th class="th0"> </th>';
		echo '<th class="th4">';
		if (!$isDemo) {
			echo '<input tabindex="-1" type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this); return true;">';
		}
		
		echo '<a tabindex="-1" style="font-weight:bold;" href="?show=filme&sort'.($saferSearch).'">Title</a>'.$titleInfo.'</th>';
		echo '<th class="th0"><a tabindex="-1" style="font-weight:bold;'.(!empty($sort) && ($sort=='jahr' || $sort=='jahra') ? 'color:red;' : '').'" href="?sort='.($sort=='jahr' ? 'jahra' : 'jahr').($saferSearch).'">Year</a></th>';
		echo '<th class="th1"><a tabindex="-1" style="font-weight:bold;'.(!empty($sort) && ($sort=='rating' || $sort=='ratinga') ? 'color:red;' : '').'" href="?sort='.($sort=='rating' ? 'ratinga' : 'rating').($saferSearch).'">Rating</a></th>';
		echo '<th class="th2">Actor</th>';
		echo '<th class="th2">Genre</th>';
		echo '<th class="th2">Director</th>';
		if (!$isDemo) {
			echo '<th class="th5">Res</th>';
			echo '<th class="th5"><a tabindex="-1" style="font-weight:bold;'.(!empty($sort) && ($sort=='size' || $sort=='sizea') ? 'color:red;' : '').'" href="?sort='.($sort=='size' ? 'sizea' : 'size').($saferSearch).'">Size</a></th></tr>';
		}
		echo "\r\n";
		
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
	} // if ($galleryMode)
} //postRows

function infobox($title, $info) {
	echo '<div id="boxx"><a tabindex="-1" href="#">';
	echo $title;
	echo '<span>';
 	echo $info;
	echo '</span></a></div>';
}

function getColspan() {
	$gallerymode   = $GLOBALS['gallerymode'];
	$elementsInRow = getElemsInRow();
	$COLUMNCOUNT   = isset($GLOBALS['COLUMNCOUNT']) ? $GLOBALS['COLUMNCOUNT'] : 9;
	if (isDemo()) { $COLUMNCOUNT = $COLUMNCOUNT - 2; }
	$newAddedCount = getNewAddedCount();
	return ($gallerymode != 1 ? $COLUMNCOUNT : ($newAddedCount < $elementsInRow ? $newAddedCount : $elementsInRow));
}

function generateForm() {
	$mode        = isset($_SESSION['mode'])        ? $_SESSION['mode']        : 0;
	$newmode     = isset($_SESSION['newmode'])     ? $_SESSION['newmode']     : 0;
	$newsort     = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;
	
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