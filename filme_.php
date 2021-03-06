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

	$orderz = findUserOrder();
	$orderz = isset($orderz[1]) ? $orderz[1] : null;
	$oItems = !$isAdmin && !empty($orderz) ? count($orderz) : 0;
#	print_r( $orderz );
	#$orderz = null;
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
<?php
		if ($oItems > 0) {
?>
			selected(null, true, true, false);
<?php
		}
?>
		});
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	postNavBar();

	echo '<div class="tabDiv" onmouseover="closeNavs();">';
	echo "\r\n";
	echo '<table class="'.($gallerymode ? 'gallery' : 'film').'" cellspacing="0">';
	echo "\r\n";

	createTable($orderz);

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
function createTable($orderz) {
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
		checkARTable($dbh);

		$SkQL        = getSessionKeySQL();
		$SQL         = $SkQL['SQL'];
		$sessionKey  = $SkQL['sessionKey'];
		$saferSearch = $SkQL['saferSearch'];

		$dirActorEnabled = true;

		$result = fetchMovies($dbh, $SQL, $sessionKey);
		$zeilen = generateRows($dbh, $result, $orderz, $sessionKey, $dirActorEnabled);
		if (!$newmode && empty($sort)) { sort($zeilen); }
		postRows($dbh, $zeilen, $saferSearch, $dirActorEnabled);

		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->commit(); }

	} catch(Throwable $e) {
		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->rollBack(); }
		if (isAdmin()) { echo $e->getMessage(); }
	}
} // function createTable

function getSessionKeySQL() {
	$res = array();

	$dbVer       = fetchDbVer();
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

	$sessionKey  = 'movies_';
	if (!isset($unseen) || (!empty($_just) && !empty($_which) || !empty($dbSearch)) ) {
		$unseen = 3;
	}
	$sessionKey .= 'unseen_'.$unseen.'_';

	$filter      = '';
	$saferSearch = '';
	if (!empty($dbSearch)) {
		$_SESSION['newmode'] = $newmode = 0;
		$_SESSION['mode']    = $mode    = 0;
		$saferSearch = strtolower(SQLite3::escapeString($dbSearch));
/*
//if (isAdmin()) {
		$actorSQL = "SELECT ".mapDBC('idActor')." FROM actor WHERE lower(name) LIKE lower('%".$saferSearch."%') LIMIT 1;";
		$idRes = querySQL_($dbh, $actorSQL, false);
		$ids = $idRes->fetch();
		#print_r( $ids );
		if (isset($ids['actor_id'])) {
			$saferSearch = null;
			$_which = 'artist';
			$_just  = $ids['actor_id'];
		}
//}
*/
	}

	if (!empty($saferSearch)) {
		$sessionKey .= 'search_'.str_replace(' ', '-', $saferSearch).'_';
		$filter      =  " AND (".
				" lower(B.strFilename) LIKE lower('%".$saferSearch."%') OR".
				" lower(A.c00) LIKE lower('%".$saferSearch."%') OR".
				" lower(A.c01) LIKE lower('%".$saferSearch."%') OR".
				" lower(A.c03) LIKE lower('%".$saferSearch."%') OR".
				" lower(A.c14) LIKE lower('%".$saferSearch."%') OR".
				" lower(A.c15) LIKE lower('%".$saferSearch."%') OR".
				" lower(A.c16) LIKE lower('%".$saferSearch."%')".
				")";

	} else if ($_which == 'artist') {
		$filter = " AND E.".mapDBC('idActor')." = '".$_just."' AND E.".mapDBC('idMovie')." = A.idMovie AND E.media_type = 'movie'";
		$sessionKey .= 'just_'.$filter.'_';

	} else if ($_which == 'regie') {
		$filter = " AND D.".mapDBC('idDirector')." = '".$_just."' AND D.".mapDBC('idMovie')." = A.idMovie AND D.media_type = 'movie'";
		$sessionKey .= 'just_'.$filter.'_';

	} else if ($_which == 'genre') {
		$filter = " AND G.".mapDBC('idGenre')." = '".$_just."' AND G.".mapDBC('idMovie')." = A.idMovie AND G.media_type = 'movie'";
		$sessionKey .= 'just_'.$filter.'_';

	} else if ($_which == 'year') {
		$filter = " AND ".mapDBC('A.c07')." = '".$_just."'";
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

	$SQL =  "SELECT DISTINCT A.idFile, A.idMovie, ".mapDBC('A.c05')." AS rating, B.playCount, A.c00, A.c01, A.c02, A.c14, A.c15, B.strFilename AS filename, ".
		"M.dateAdded as dateAdded, M.value as dateValue, ".
		mapDBC('A.c07')." AS jahr, A.c08 AS thumb, A.c11 AS dauer, A.c19 AS trailer, ".mapDBC('A.c09')." AS imdbId, C.strPath AS path, F.filesize, F.fps, F.bit ".
		"FROM movie A, files B, path C ".
//		"FROM movie_view A, files B, path C ".
		"LEFT JOIN fileinfo F ON B.idFile = F.idFile ".
		mapDBC('joinIdMovie').
		mapDBC('joinRatingMovie').
		"LEFT JOIN filemap M ON B.strFilename = M.strFilename ".
		(isset($mode) && ($mode == 2) ? "LEFT JOIN streamdetails SD ON (B.idFile = SD.idFile AND SD.strAudioLanguage IS NOT NULL) " : '').
		(isset($filter, $_which) && ($_which == 'artist') ? ', '.mapDBC('actorlinkmovie').' E'    : '').
		(isset($filter, $_which) && ($_which == 'regie')  ? ', '.mapDBC('directorlinkmovie').' D' : '').
		(isset($filter, $_which) && ($_which == 'genre')  ? ', '.mapDBC('genrelinkmovie').' G'    : '').
		" WHERE A.idFile = B.idFile AND C.idPath = B.idPath ";

	if (!empty($sort)) {
		     if ($sort == 'jahr')    { $sessionKey .= 'orderJahr_';    $sqlOrder = " ORDER BY ".mapDBC('A.c07')." DESC, dateAdded DESC";      }
		else if ($sort == 'jahra')   { $sessionKey .= 'orderJahrA_';   $sqlOrder = " ORDER BY ".mapDBC('A.c07')." ASC, dateAdded ASC";        }
		else if ($sort == 'title')   { $sessionKey .= 'orderTitle_';   $sqlOrder = " ORDER BY A.c00 DESC";                                    }
		else if ($sort == 'titlea')  { $sessionKey .= 'orderTitleA_';  $sqlOrder = " ORDER BY A.c00 ASC";                                     }
		else if ($sort == 'rating')  { $sessionKey .= 'orderRating_';  $sqlOrder = " ORDER BY rating DESC";                                   }
		else if ($sort == 'ratinga') { $sessionKey .= 'orderRatingA_'; $sqlOrder = " ORDER BY rating ASC";                                    }
		else if ($sort == 'size')    { $sessionKey .= 'orderSize_';    $sqlOrder = " ORDER BY F.filesize DESC";                               }
		else if ($sort == 'sizea')   { $sessionKey .= 'orderSizeA_';   $sqlOrder = " ORDER BY F.filesize ASC";                                }

	} else if ($newmode) {
		$sqlOrder = " ORDER BY ".($newsort == 1 ? "dateValue" : "A.idMovie")." DESC";
		$sessionKey .= ($newsort == 1 ? 'orderDateValue_' : 'orderIdMovie_');

	} else {
		$sqlOrder = " ORDER BY A.c00 ASC";
		$sessionKey .= 'orderName_';
	}

	switch ($mode) {
		case 2:
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
			$uncut = " AND (lower(B.strFilename) LIKE '%extended%' OR lower(A.c00) LIKE '%extended%' OR lower(B.strFilename) LIKE '%see%' OR lower(A.c00) LIKE '%see%') ";
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

		case 8:
			$uncut = " AND (lower(B.strFilename) LIKE '%remastered%' OR lower(A.c00) LIKE '%remastered%') ";
			$sessionKey .= 'remasteredCut';
			break;

		case 9:
			$uncut = " AND (lower(B.strFilename) LIKE '%atmos%') ";
			$sessionKey .= 'atmos';
			break;

		default:
			unset($uncut);
	}

	$params = (isset($filter) ? $filter : '').(isset($uncut) ? $uncut : '').$unseenCriteria.$sqlOrder;
	$SQL .= $params.";";

	#echo $SQL;

	$res['SQL']         = $SQL;
	$res['sessionKey']  = $sessionKey;
	$res['saferSearch'] = $saferSearch;
	return $res;
} //getSessionKeySQL

function generateRows($dbh, $result, $orderz, $sessionKey, $dirActorEnabled = true) {
	$isAdmin       = isAdmin();
	$isDemo        = isDemo();
	$dbVer         = fetchDbVer();

	$xbmcRunning   = xbmcRunning();
	$newAddedCount = getNewAddedCount();

	$idGenre       = getGenres($dbh);
	$idStream      = getResolution($dbh);

	$existArtTable = existsArtTable($dbh);
	$artCovers     = fetchArtCovers($existArtTable, $dbh);
	$actorImgs     = fetchActorCovers($dbh);
	$directorImgs  = fetchDirectorCovers($dbh);

	$filter_name   = isset($_SESSION['name']) ? $_SESSION['name'] : '';
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
	for ($rCnt = 0; $rCnt < count($result); $rCnt++) {
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
		$jahr      = substr($row['jahr'], 0, 4);
		$filesize  = $row['filesize'];
		$fps       = $row['fps'];
		$bits      = $row['bits'];
		$playCount = $row['playCount'];
		$trailer   = $row['trailer'];
		$rating    = $row['rating'];
		$imdbId    = $row['imdbId'];
		$genres    = $row['c14'];
		$vRes      = isset($idStream[$idFile]) ? $idStream[$idFile] : array();
		$fnam      = $path.$filename;
		$cover     = null;
		$isNew     = !empty($lastHighest) && $idMovie > $lastHighest;

		if (empty($dateAdded)) {
			$dateAdded = getCreation($fnam);
			$dateAdded = isset($dateAdded) ? $dateAdded : '2001-01-01 12:00:00';
			$SQL_ = "REPLACE INTO filemap(idFile, strFilename, dateAdded, value) VALUES(".$idFile.", '".$filename."', '".$dateAdded."', '".strtotime($dateAdded)."');";
			execSQL_($dbh, $SQL_, false, true);
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
						$type = isset($row2['type']) ? $row2['type'] : null;
						$url  = isset($row2['url'])  ? $row2['url']  : null;
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
			$checked  = isset($orderz[$idMovie]);
			$higlight = $checked ? ' highLighTR' : '';

#counter
			$spalTmp = '<td class="countTD'.$higlight.'">';
			if ($COVER_OVER_TITLE && !empty($cover)) { $spalTmp .= '<a tabindex="-1" class="hoverpic" rel="'.getImageWrap($cover, $idMovie, 'movie', 0).'">'; }
			if ($isAdmin) { $spalTmp .= '<span class="fancy_movieEdit" href="./nameEditor.php?change=movie&idMovie='.$idMovie.'">'; }
			$spalTmp .= '_C0UNTER_';
			if ($isAdmin) { $spalTmp .= '</span>'; }
			if ($COVER_OVER_TITLE && !empty($cover)) { $spalTmp .= '</a>'; }
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#checkbox
			$spalTmp = '<td class="titleTD'.$higlight.'"'.($isNew ? ' style="font-weight:bold;"' : '').'>';
			if (!$isDemo) {
			$spalTmp .= '<input tabindex="-1" type="checkbox" class="checka" name="checkFilme[]" id="opt_'.$idMovie.'" value="'.$idMovie.'"'.($checked ? ' checked="checked" selected="selected"' : '').' onClick="selected(this, true, true, '.$isAdmin.'); return true;">';
			}

#seen
			if ($isAdmin) {
				$chk      = $playCount >= 1;
				$spalTmp .= '<span'.(!$chk ? ' style="padding-right:10px;"' : '').'>';
				$spalTmp .= $chk ? '<img src="img/check.png" class="check10v1">' : ' ';
				$spalTmp .= '</span> ';
			}

#title
			$suffix = '';
			if (is3d($filename)) { $suffix = ' (3D)'; }
			if ($wasCutoff) { $spalTmp .= '<a tabindex="-1" class="fancy_iframe" href="./?show=details&idShow='.$idMovie.'">'.$filmname.$suffix.'<span class="searchField" style="display:none;">'.$filmname0.'</span></a>'; }
			else { $spalTmp .= '<a tabindex="-1" class="fancy_iframe" href="./?show=details&idShow='.$idMovie.'"><span class="searchField">'.$filmname.$suffix.'</span></a>'; }

#trailer
			if ($SHOW_TRAILER && !empty($trailer)) {
				$spalTmp .= '<a tabindex="-1" class="fancy_iframe2" href="'.$ANONYMIZER.$trailer.'"> <img src="img/filmrolle.jpg" width=15px; border=0px;></a>';
			}
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#year
			$spalTmp = '<td class="yearTD'.$higlight;
			$spalTmp .= !empty($jahr) ? '"><a tabindex="-1" href="?show=filme&country=&mode=1&which=year&just='.$jahr.'&name='.$jahr.'" title="filter"><span class="searchField">'.$jahr.'</span></a>' : ' centro">-';
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#rating
			$rating = substr($rating, 0, 4);
			$rating = substr($rating, 0, substr($rating, 2, 1) == '.' ? 4 : 3);

			$spalTmp = '<td class="ratingTD'.$higlight.($rating > 0 ? ' righto' : ' centro').'">';
			if (!empty($imdbId)) {
				$spalTmp .= '<a tabindex="-1" class="openImdb" href="'.$ANONYMIZER.$IMDBFILMTITLE.$imdbId.'">';
			} else {
				$spalTmp .= '<a tabindex="-1" class="openImdb" href="'.$ANONYMIZER.$FILMINFOSEARCH.$titel.'">';
			}

			$spalTmp .= ($rating > 0 ? $rating : '&nbsp;&nbsp;-');
			$spalTmp .= '</a>';
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#genre
			$spalTmp = '<td class="genreTD'.$higlight.'"';
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

				if (isset($_just) && !empty($_just) && $_which == 'genre' && !empty($filter_name)) {
					$spalTmp .= '<span class="searchField">'.$filter_name.'</span>';
				}
				else if (($genreId != -1) && (!isset($_just) || empty($_just) || $_which != 'genre' || $_just != $genreId)) {
					$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=genre&just='.$genreId.'&name='.$genre.'" title="filter"><span class="searchField">'.$genre.'</span></a>';
				} else {
					$spalTmp .= '<span class="searchField">'.$genre.'</span>';
				}
			} else {
				$spalTmp .= ' style="padding-left:20px;">-';
			}
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#artist
if ($dirActorEnabled) {
			$spalTmp = '<td class="actorTD'.$higlight.'"';
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
				$SQL_ = "SELECT A.".mapDBC('strActor').", B.role, B.".mapDBC('idActor').", A.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC('actorlinkmovie')." B, ".mapDBC('actors')." A WHERE A.".mapDBC('idActor')." = B.".mapDBC('idActor')." AND B.media_type='movie' AND B.".mapDBC('idMovie')." = ".$idMovie." ORDER BY B.".mapDBC('iOrder').";";
				$result2 = querySQL_($dbh, $SQL_, false);
				foreach($result2 as $row2) {
					$artist      = $row2[mapDBC('strActor')];
					$idActor     = $row2[mapDBC('idActor')];
					$actorpicURL = $row2['actorimage'];

					if (empty($firstartist)) {
						if (empty($artist) || empty($idActor))
							continue;
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
					$url  = isset($row3['url']) ? $row3['url'] : null;
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				} //POWERFUL_CPU
			}

			if (!empty($firstartist) && !empty($firstId)) {
				wrapItUp('actor', $firstId, $actorimg, $sessionKey);

				$spalTmp .= '>';
				#$spalTmp .= '<a tabindex="-1" class="openIMDB filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstartist.'">[i] </a>';
				$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=artist&just='.$firstId.'&name='.$firstartist.'"';
				if (file_exists($actorimg)) {
					$spalTmp .= ' class="hoverpic" rel="'.getImageWrap($actorimg, $firstId, 'actor', 0).'" title="'.$firstartist.'"';
				} else {
					$spalTmp .= ' title="filter"';
				}

				$spalTmp .= '><span class="searchField">'.$firstartist.'</span></a>';
			} else {
				$spalTmp .= ' style="padding-left:40px;">-';
			}
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#director
			$spalTmp = '<td class="direcTD'.$higlight.'"';
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
				$SQL_ = "SELECT A.".mapDBC('strActor').", B.".mapDBC('idDirector').", A.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC('directorlinkmovie')." B, ".mapDBC('actors')." A WHERE B.".mapDBC('idDirector')." = A.".mapDBC('idActor')." AND B.media_type = 'movie' AND B.".mapDBC('idMovie')." = ".$idMovie.";";
				$result3 = querySQL_($dbh, $SQL_, false);
				foreach($result3 as $row3) {
					$artist      = $row3[mapDBC('strActor')];
					$idActor     = $row3[mapDBC('idDirector')];
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
					$url  = isset($row3['url']) ? $row3['url'] : null;
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				} //POWERFUL_CPU
			}

			if (!empty($firstdirector) && !empty($firstId)) {
				wrapItUp('director', $firstId, $actorimg, $sessionKey);

				$spalTmp .= '>';
				#$spalTmp .= '<a tabindex="-1" class="openImdb filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstdirector.'">[i] </a>';
				$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=regie&just='.$firstId.'&name='.$firstdirector.'"';
				if (file_exists($actorimg)) {
					$spalTmp .= ' class="hoverpic" rel="'.getImageWrap($actorimg, $firstId, 'director', 0).'" title="'.$firstdirector.'"';
				} else {
					$spalTmp .= ' title="filter"';
				}

				$spalTmp .= '><span class="searchField">'.$firstdirector.'</span></a>';
			} else {
				$spalTmp .= ' style="padding-left:40px;">-';
			}
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
} //$dirActorEnabled

#resolution/codec/10bit/fps/hdr
			if (!$isDemo) {
				$cols      = isset($GLOBALS['CODEC_COLORS']) ? $GLOBALS['CODEC_COLORS'] : null;
				$f4Ke      = preg_match_all('/f4Ke/',    $filename) > 0 ? true : false;
				$hdr       = preg_match_all('/\bHDR|HDR10|HDR10p\b/', $filename) > 0 ? true : false;
				$resInfo   = getResDesc($vRes);
				$resPerf   = getResPerf($vRes, $hdr);
				$resColor  = ($cols == null || $resPerf < 4 ? null : $cols[$resPerf]);
				$resStyle  = '';
				if (!empty($resColor) || $f4Ke) {
				    $resStyle = ' style="';
				    if ($f4Ke)
						$resStyle .= 'text-shadow: 0 0 2px rgba(222,0,0,0.75);';
					if (!empty($resColor))
						$resStyle .= ($f4Ke ? ' ' : '').'color:'.$resColor.';';
					$resStyle .= '"';
				}
				$resTD     = (empty($resInfo) ? '' : '<span class="searchField"'.(empty($resStyle) ? '' : $resStyle).'>'.$resInfo.'</span>');
				$resTip    = (empty($vRes) ? '' : $vRes[0].'x'.$vRes[1]).($hdr ? ' | HDR' : '').($f4Ke ? ' | Fake 4K' : '');
				$codec     = (empty($vRes) ? '' : postEditVCodec($vRes[2]));
				$fps       = array($bits, formatFps($fps));
				$bit10     = (!empty($fps) ? $fps[0] >= 10 : preg_match_all('/\b1(0|2)bit\b/', $filename) > 0) ? true : false;
				$perf      = (empty($codec) ? 0 : decodingPerf($codec, $bit10));
				$color     = ($cols == null || $perf < 4 ? null : $cols[$perf]);
				$codecST   = (empty($color) ? '' : ' style="color:'.$color.';"');
				if (isAdmin()) {
					$codec = '<a tabindex="-1" class="fancy_msgbox clearFileSize"'.$codecST.' href="./dbEdit.php?clrStream=1&act=clearFileSize&idFile='.$idFile.'">'.$codec.'</a>';
					$codecST = '';
				}
				$codecTD   = (empty($codec) ? '' : '<span class="searchField"'.$codecST.'>'.$codec.'</span>');
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD'.$higlight.' hideMobile" align="right" title="'.$resTip.'">'.$resTD.'</td>';
				$fpsTitle  = (empty($fps) || !is_array($fps) || empty($fps[1]) ? '' : $fps[1].' fps');
				$fpsTitle  = ($bit10 ? '10bit' : '').($bit10 && !empty($fps) ? ' | ' : '').$fpsTitle;
				$fpsTitle  = ($hdr   ? 'HDR'   : '').($hdr   && !empty($fpsTitle) ? ' | ' : '').$fpsTitle;
				$fpsTitle  = 'title="'.$fpsTitle.'"';
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD'.$higlight.' hideMobile" align="right" '.$fpsTitle.'>'.$codecTD.'</td>';

#filesize
				$filename = prepPlayFilename($path.$filename);
				$playItem = $isAdmin && $xbmcRunning && !empty($path) && !empty($filename) ? ' onclick="playItem(\''.$filename.'\'); return false;" style="cursor:pointer;"' : null;
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD'.$higlight.' hideMobile" align="right"'.$playItem.'>'.$moviesize.'</td>';
			}

			$zeile++;
		} // else gallerymode == 1

		if ($newmode && ++$counter2 >= $newAddedCount) { break; }
	} //foreach

	return $zeilen;
} //generateRows

function postRows($dbh, $zeilen, $saferSearch, $dirActorEnabled = true) {
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
		if (!empty($saferSearch)) { $saferSearch = '&dbSearch='.$saferSearch; }
		echo "\t";
		echo '<tr><th class="th0"> </th>';
		echo '<th class="th4">';
		if (!$isDemo) {
			echo '<input tabindex="-1" type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this); return true;">';
		}

		echo '<span style="padding-right:10px;"> </span><a tabindex="-1" style="font-weight:bold;'.(!empty($sort) && ($sort=='title' || $sort=='titlea')   ? ' color:red;' : '').'" href="?show=filme&sort='.($sort=='titlea' ? 'title' : 'titlea').($saferSearch).'">Title</a>'.$titleInfo.'</th>';
		echo '<th class="th0"><a tabindex="-1" style="font-weight:bold;'.(!empty($sort) && ($sort=='jahr'   || $sort=='jahra')   ? ' color:red;' : '').'" href="?sort='.($sort=='jahr' ? 'jahra' : 'jahr').($saferSearch).'">Year</a></th>';
		echo '<th class="th1"><a tabindex="-1" style="font-weight:bold;'.(!empty($sort) && ($sort=='rating' || $sort=='ratinga') ? ' color:red;' : '').'" href="?sort='.($sort=='rating' ? 'ratinga' : 'rating').($saferSearch).'">Rating</a></th>';
		echo '<th class="th3">Genre</th>';
		if ($dirActorEnabled) {
		echo '<th class="th2">Actor</th>';
		echo '<th class="th2">Director</th>';
		}
		if (!$isDemo) {
			echo '<th class="th5 hideMobile">Res</th>';
			echo '<th class="th5 hideMobile">Codec</th>';
			echo '<th class="th5 hideMobile"><a tabindex="-1" style="font-weight:bold;'.(!empty($sort) && ($sort=='size' || $sort=='sizea') ? 'color:red;' : '').'" href="?sort='.($sort=='size' ? 'sizea' : 'size').($saferSearch).'">Size</a></th></tr>';
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
	echo '<form action="" name="moviefrm" method="post">';
	echo "\r\n";

	if ($newmode) {
		$newAddedCount = getNewAddedCount();

		if (!isAdmin()) {
			$sizes = isset($GLOBALS['SHOW_NEW_VALUES']) ? $GLOBALS['SHOW_NEW_VALUES'] : array(30, 60);

			echo "\t";
			echo '<tr>';
			echo '<th class="newAddedTH" colspan="'.getColSpan().'">';
			echo '<div style="float:right; padding-top:1px; margin-right:0px;">';
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
