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

	$COPYASSCRIPT_ENABLED = isset($GLOBALS['COPYASSCRIPT_ENABLED']) ? $GLOBALS['COPYASSCRIPT_ENABLED'] : false;
	$PRONOMS     = isset($GLOBALS['PRONOMS'])      ? $GLOBALS['PRONOMS']      : null;
	$newsort     = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;
	$gallerymode = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;
	$which       = isset($_SESSION['which'])       ? $_SESSION['which']       : null;
	$just        = isset($_SESSION['just'])        ? $_SESSION['just']        : null;

	$orderz      = findUserOrder();
	$orderz      = isset($orderz[1]) ? $orderz[1] : null;
	$oItems      = !$isAdmin && !empty($orderz) ? count($orderz) : 0;
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

	echo "\t".'<div class="tabDiv" onmouseover="closeNavs();">';
	echo "\r\n";
	echo "\t\t".'<table class="'.($gallerymode ? 'gallery' : 'film').'" cellspacing="0">';
	echo "\r\n";

	createTable($orderz);

	if (isset($_SESSION['lastMovie']['seen']) && !isset($_SESSION['lastMovie']['set'])) { setLastHighest(); }
	if ($newsort == 2 && !$gallerymode) { $_SESSION['lastMovie']['seen'] = true; }
	if ($isAdmin && !$gallerymode) {
		echo "\t\t\t\t";
		echo '<tr><td colspan="'.getColSpan().'" class="optTD tHidden">';
		echo '<div style="float:right; padding:4px 5px;">';
		echo '<input tabindex="-1" type="submit" value="Ok" name="submit" class="okButton">';
		echo '</div>';
		echo '<div style="float:right; padding-top:2px; margin-right:5px;">';
		echo '<select class="styled-select2" name="aktion" size="1">';
		echo '<option value="0" label="          "></option>';
		echo '<option value="1">mark as unseen</option>';
		echo '<option value="2">mark as seen</option>';
		echo '<option value="3">delete</option>';
		echo '<option value="4">clear StreamDetails</option>';
		echo '</select>';
		echo '</div>';
		echo '</td></tr>';
		echo "\r\n";
	}

	echo "\t\t\t".'</form>';
	echo "\r\n\t\t".'</table>';
	echo "\r\n\t".'</div>';
	echo "\r\n";

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
	$dbh = getPDO();
	try {
		$dbh->beginTransaction();
		checkTables($dbh);

		$newAddedCount = getNewAddedCount();
		$SkQL   = getSessionKeySQL($newAddedCount);
		$zeilen = generateRows($orderz, $newAddedCount, $SkQL, $dbh);
		postRows($zeilen, $SkQL);

		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->commit(); }

	} catch(Throwable $e) {
		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->rollBack(); }
		if (isAdmin()) { echo $e->getMessage(); }
	}
}

function checkTables($dbh) {
	$dbh = (!empty($dbh) ? $dbh : getPDO());
	if (!$dbh->inTransaction()) {
		$dbh->beginTransaction();
	}

	checkFileInfoTable($dbh);
	checkFileMapTable($dbh);
	existsOrderzTable($dbh);
	checkARTable($dbh);
	#existsOrdersTable($dbh);
}

/** @noinspection PhpIssetCanBeReplacedWithCoalesceInspection
 * @noinspection PhpTernaryExpressionCanBeReplacedWithConditionInspection
 */
function generateRows($orderz, $newAddedCount, $SkQL, $dbh = null) {
	$dirActorEnabled  = true;
	$_just            = $GLOBALS['just'];
	$_which           = $GLOBALS['which'];
	$IMDB             = $GLOBALS['IMDB'];
	$PRONOMS          = $GLOBALS['PRONOMS'];
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

	//$dbVer         = fetchDbVer();
	$isAdmin       = isAdmin();
	$isDemo        = isDemo();
	$filter_name   = isset($_SESSION['name']) ? $_SESSION['name'] : '';
	$lastHighest   = $isDemo ? null : (isset($_SESSION['lastHighest']) ? $_SESSION['lastHighest'] : null);

	$xbmcRunning   = xbmcRunning();

	$existArtTable = existsArtTable($dbh);
	$artCovers     = fetchArtCovers($existArtTable, $dbh);
	$genreIDs      = fetchGenreIDs($dbh);
	$result        = fetchMovies($SkQL, $dbh);
	$idStream      = getResolution($SkQL, true, $dbh);

	$counter  = 0;
	$counter2 = 0;
	$zeile    = 0;
	$zeilen = array();
	for ($rCnt = 0; $rCnt < count($result); $rCnt++) {
		$zeilenSpalte = 0;
		$row        = $result[$rCnt];
		$idFile     = $row['idFile'];
		if ($idFile < 0) { continue; }
		$idMovie    = $row['idMovie'];
		$filmname   = $row['movieName'];
		//$thumb    = $row['thumb'];
		$filename   = $row['filename'];
		$dateAdded  = $row['dateAdded'];
		$path       = $row['path'];
		$jahr       = substr($row['jahr'], 0, 4);
		$filesize   = $row['filesize'];
		$fps        = $row['fps'];
		$bits       = $row['bits'];
		$playCount  = $row['playCount'];
		$lastPlayed = $row['lastPlayed'];
		$trailer    = $row['trailer'];
		$rating     = $row['rating'];
		$imdbId     = $row['imdbId'];
		$genres     = $row['genres'];
		$vRes       = isset($idStream[$idFile]) ? $idStream[$idFile] : array();
		$fnam       = $path.$filename;
		$cover      = null;
		$isNew      = !empty($lastHighest) && $idMovie > $lastHighest;

		$f4Ke       = isFake4K($filename);
		$scaled     = isUpscaled($filename);
		$is3D       = is3d($filename);

		$filmname0  = $filmname;
		$titel      = $filmname;

		$path = mapSambaDirs($path);
		if (count($EXCLUDEDIRS) > 0 && isset($EXCLUDEDIRS[$path]) && $EXCLUDEDIRS[$path] != $mode) { continue; }

		$fsize     = fetchFileSize($idFile, $path, $filename, $filesize, $dbh);
		$moviesize = _format_bytes($fsize);

		$wasCutoff = false;
		$cutoff    = isset($GLOBALS['CUT_OFF_MOVIENAMES']) ? $GLOBALS['CUT_OFF_MOVIENAMES'] : -1;
		if (strlen($filmname) >= $cutoff && $cutoff > 0) {
			$filmname = substr($filmname, 0, $cutoff).'...';
			$wasCutoff = true;
		}
		$filmname = switchPronoms($filmname, $PRONOMS);

		if (empty($dateAdded)) {
			$dateAdded = getCreation($fnam);
			$dateAdded = isset($dateAdded) ? $dateAdded : '2001-01-01 12:00:00';
			$SQL_ = "REPLACE INTO filemap(idFile, strFilename, dateAdded, value) VALUES(".$idFile.", '".$filename."', '".$dateAdded."', '".strtotime($dateAdded)."');";
			execSQL_($SQL_, false, true, $dbh);
		}

#covers
		if ($gallerymode || $COVER_OVER_TITLE) {
			if (!empty($artCovers)) {
				$cover_ = getCoverThumb($fnam, $cover, false);
				if (isFile($cover_)) {
					$cover = $cover_;
				} else if ($existArtTable && isset($artCovers['movie'][$idMovie])) {
					$cover = $artCovers['movie'][$idMovie]['cover'];
				}
			} else {
				if ($existArtTable) {
					$SQL_ = "SELECT url,type FROM art WHERE media_type = 'movie' AND (type = 'poster' OR type = 'thumb') AND media_id = '".$idMovie."';";
					$res2 = querySQL($SQL_, false, $dbh);
					foreach($res2 as $row2) {
						$type = isset($row2['type']) ? $row2['type'] : null;
						$url  = isset($row2['url'])  ? $row2['url']  : null;
						if (!empty($url)) { $cover = getCoverThumb($url, $url, true); }
						if ($type == 'poster') { break; }
					}
				} else if (isFile(getCoverThumb($fnam, $cover, false))) {
					$cover = getCoverThumb($fnam, $cover, false);
				}
			} //POWERFUL_CPU
		}

		wrapItUp('cover', $idMovie, $cover);

		if ($gallerymode) {
				$zeilen[$counter][0] = $filmname.($jahr != 0 ? ' ('.$jahr.')' : '');
				$zeilen[$counter][1] = 'show=details&idMovie='.$idMovie;
				$zeilen[$counter][2] = $playCount;
				$zeilen[$counter][3] = getImageWrap($cover, $idMovie, 'movie', 0);
				$zeilen[$counter][4] = $is3D;
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
			$isWatched = $isAdmin && $playCount >= 1;

			$spalTmp = '<td class="titleTD'.$higlight.'"'.($isNew ? ' style="font-weight:bold;"' : '').'>';
			if (!$isDemo) {
				$spalTmp .= '<input tabindex="-1" type="checkbox"'.(!$isWatched ? ' style="margin-right:2px;"' : '').' class="checka'.($isAdmin ? ' tHidden' : '').'" name="checkFilme[]" id="opt_'.$idMovie.'" value="'.$idMovie.'"'.($checked ? ' checked="checked" selected="selected"' : '').' onClick="selected(this, true, true, '.$isAdmin.'); return true;">';
			}

#seen
			if ($isAdmin) {
				$when = toEuropeanDateFormat($lastPlayed, false);
				if ($playCount > 1) {
					$when = $playCount.'x: '.$when;
				}

				$spalTmp .= '<span'.(!$isWatched ? ' style="padding-right:13px;"' : '').'>';
				$spalTmp .= $isWatched ? '<img src="img/check.png" class="check10v1" title="'.$when.'">' : '';
				$spalTmp .= '</span> ';
			}

#title
			$suffix = '';
			if ($is3D) { $suffix = ' (3D)'; }
			if ($wasCutoff) { $spalTmp .= '<a tabindex="-1" class="fancy_iframe" href="./?show=details&idMovie='.$idMovie.'">'.$filmname.$suffix.'<span class="searchField" style="display:none;">'.$filmname0.'</span></a>'; }
			else { $spalTmp .= '<a tabindex="-1" class="fancy_iframe" href="./?show=details&idMovie='.$idMovie.'"><span class="searchField">'.$filmname.$suffix.'</span></a>'; }

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

			$spalTmp = '<td class="ratingTD righto'.$higlight.'">';
			if (!empty($imdbId)) {
				$spalTmp .= '<a tabindex="-1" class="openImdb" href="'.$ANONYMIZER.$IMDBFILMTITLE.$imdbId.'">';
			} else {
				$spalTmp .= '<a tabindex="-1" class="openImdb" href="'.$ANONYMIZER.$FILMINFOSEARCH.$titel.'">';
			}

			$spalTmp .= (empty($rating) ? '-&nbsp;&nbsp;' : sprintf("%02.1f", round($rating, 1)));
			$spalTmp .= '</a>';
			$spalTmp .= '</td>';
			$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#genre
			$spalTmp = '<td class="genreTD hideMobile'.$higlight.'"';
			$genres = explode("/", $genres);
			$genre = count($genres) > 0 ? trim($genres[0]) : '';
			$genreId = -1;

			if (!empty($genre)) {
				$spalTmp .= '>';
				$genre = ucwords(strtolower($genre));
				if (isset($genreIDs[$genre])) {
					$genreId = $genreIDs[$genre];
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
			$spalTmp = '<td class="actorTD hideMobile'.$higlight.'"';
			$firstId     = '';
			$firstartist = '';
			$actorpicURL = '';

			$SQL_ = "SELECT A.".mapDBC('strActor').", B.role, B.".mapDBC('idActor').", A.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC('actorlinkmovie')." B, ".mapDBC('actors')." A WHERE A.".mapDBC('idActor')." = B.".mapDBC('idActor')." AND B.media_type='movie' AND B.".mapDBC('idMovie')." = ".$idMovie." ORDER BY B.".mapDBC('iOrder').";";
			$result2 = querySQL($SQL_, false, $dbh);
			foreach($result2 as $row2) {
				$artist      = $row2[mapDBC('strActor')];
				$idActor     = $row2[mapDBC('idActor')];
				$actorpicURL = $row2['actorimage'];

				if (empty($firstartist)) {
					if (empty($artist) || empty($idActor)) {
						continue;
					}
					$firstartist = $artist;
					$firstId     = $idActor;
					break;
				}
			}

			$actorimg = getActorThumb($firstartist, $actorpicURL, false);
			if ($existArtTable && !empty($firstId) && !isFile($actorimg)) {
				if (!empty($artCovers)) {
					if (isset($artCovers['actor'][$firstId])) {
						$actorimg = $artCovers['actor'][$firstId];
					}
				} else {
					$SQL_ = "SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';";
					$res3 = querySQL($SQL_, false, $dbh);
					$row3 = $res3->fetch();
					$url  = isset($row3['url']) ? $row3['url'] : null;
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				} //POWERFUL_CPU
			}

			if (!empty($firstartist) && !empty($firstId)) {
				wrapItUp('actor', $firstId, $actorimg);

				$spalTmp .= '>';
				#$spalTmp .= '<a tabindex="-1" class="openIMDB filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstartist.'">[i] </a>';
				$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=artist&just='.$firstId.'&name='.$firstartist.'"';
				if (isFile($actorimg)) {
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
			$spalTmp = '<td class="direcTD hideMobile'.$higlight.'"';
			$firstId       = '';
			$firstdirector = '';
			$actorpicURL   = '';

			$SQL_ = "SELECT A.".mapDBC('strActor').", B.".mapDBC('idDirector').", A.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC('directorlinkmovie')." B, ".mapDBC('actors')." A WHERE B.".mapDBC('idDirector')." = A.".mapDBC('idActor')." AND B.media_type = 'movie' AND B.".mapDBC('idMovie')." = ".$idMovie.";";
			$result3 = querySQL($SQL_, false, $dbh);
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

			$actorimg = getActorThumb($firstdirector, $actorpicURL, false);
			if ($existArtTable && !empty($firstId) && !isFile($actorimg)) {
				if (!empty($artCovers)) {
					if (isset($artCovers['actor'][$firstId])) {
						$actorimg = $artCovers['actor'][$firstId];
					}
				} else {
					$SQL_ = "SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';";
					$res3 = querySQL($SQL_, false, $dbh);
					$row3 = $res3->fetch();
					$url  = isset($row3['url']) ? $row3['url'] : null;
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				} //POWERFUL_CPU
			}

			if (!empty($firstdirector) && !empty($firstId)) {
				wrapItUp('director', $firstId, $actorimg);

				$spalTmp .= '>';
				#$spalTmp .= '<a tabindex="-1" class="openImdb filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstdirector.'">[i] </a>';
				$spalTmp .= '<a tabindex="-1" href="?show=filme&country=&mode=1&which=regie&just='.$firstId.'&name='.$firstdirector.'"';
				if (isFile($actorimg)) {
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
				$hdrType   = postEditHdrType($vRes[3]);
				$hdr       = isHDR($filename, $hdrType);
				if ($hdr && empty($hdrType)) {
					$hdrType = 'HDR';
				}

				$resInfo   = getResDesc($vRes);
				$resPerf   = getResPerf($vRes, $hdr);
				$resColor  = ($resPerf < 4 ? null : CODEC_COLORS[$resPerf]);
				$resStyle  = '';
				$arOR = getOverrideAR($idFile, $idMovie, $dbh);

				if ($f4Ke || $scaled || !empty($resColor) || !empty($arOR)) {
				    if ($f4Ke || $scaled) {
						$resStyle .= 'text-shadow: 0 0 2px rgba(222,0,0,0.75);';
					}
					if (!empty($resColor)) {
						$resStyle .= (!empty($resStyle) ? ' ' : '').'color:'.$resColor.';';
					}
					if (!empty($arOR)) {
						$resStyle .= (!empty($resStyle) ? ' ' : '').'font-style:italic;';
					}
					if (!empty($resStyle)) {
						$resStyle = ' style="'.$resStyle.'"';
					}
				}

				$resTD     = (empty($resInfo) ? '' : '<span class="searchField"'.(empty($resStyle) ? '' : $resStyle).'>'.$resInfo.'</span>');
				$tipSuffix = $f4Ke   ? ' | Fake 4K' : '';
				$tipSuffix = $scaled ? ' | Upscaled 4K' : $tipSuffix;

				if (!empty($arOR)) {
					$vRes[1] = intval($vRes[0] / $arOR);
				}

				$resTip    = (empty($vRes) ? '' : $vRes[0].'x'.$vRes[1]).(!empty($arOR) ? ' // overridden' : '').$tipSuffix;
				$codec     = (empty($vRes) ? '' : postEditVCodec($vRes[2]));
				$fps       = array($bits, formatFps($fps));
				$bit10     = (!empty($fps) ? $fps[0] >= 10 : preg_match_all('/\b1(0|2)bit\b/', $filename) > 0) ? true : false;
				$perf      = (empty($codec) ? 0 : decodingPerf($codec, $bit10));
				$color     = ($perf < 4 ? null : CODEC_COLORS[$perf]);
				$codecST   = (empty($color) ? '' : ' style="color:'.$color.';"');
				if (isAdmin()) {
					$codec = '<a tabindex="-1" class="fancy_msgbox clearFileSize"'.$codecST.' href="./dbEdit.php?clrStream=1&act=clearFileSize&idFile='.$idFile.'">'.$codec.'</a>';
					$codecST = '';
				}
				$codecTD   = (empty($codec) ? '' : '<span class="searchField"'.$codecST.'>'.$codec.'</span>');
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="resCodecTD'.$higlight.'" title="'.$resTip.'">'.$resTD.'</td>';
				$fpsTitle  = (empty($fps) || !is_array($fps) || empty($fps[1]) ? '' : $fps[1].' fps');
				$fpsTitle  = ($bit10 ? '10bit' : '').($bit10 && !empty($fps) ? ' | ' : '').$fpsTitle;
				$fpsTitle  = ($hdr   ? $hdrType : '').($hdr && !empty($fpsTitle) ? ' | ' : '').$fpsTitle;
				$fpsTitle  = 'title="'.$fpsTitle.'"';
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="resCodecTD'.$higlight.'" '.$fpsTitle.'>'.$codecTD.'</td>';

#filesize
				$filename = prepPlayFilename($path.$filename);
				$playItem = $isAdmin && $xbmcRunning && !empty($filename) ? ' onclick="playItem(\''.$filename.'\'); return false;"' : null;
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD'.$higlight.'" '.$playItem.'>'.$moviesize.'</td>';
			}

			$zeile++;
		} // else gallerymode == 1

		if ($newmode && ++$counter2 >= $newAddedCount) { break; }
	} //foreach

	return $zeilen;
} //generateRows

/** @noinspection PhpIssetCanBeReplacedWithCoalesceInspection */
function postRows($zeilen, $SkQL, $dirActorEnabled = true) {
	$newmode       = isset($_SESSION['newmode'])     ? $_SESSION['newmode']     : 0;
	$sort          = isset($_SESSION['sort'])        ? $_SESSION['sort']        : 0;
	$gallerymode   = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;

	if (!$newmode && empty($sort)) { sort($zeilen); }

	if ($gallerymode) {
		postGalleryRows($zeilen);
	} else {
		postTableRows($zeilen, $sort, $SkQL, $dirActorEnabled);
	}
}

function postTableRows($zeilen, $sort, $SkQL, $dirActorEnabled = true): void {
	$titleInfo = '';
	$isDemo    = isDemo();

	$saferSearch = $SkQL['saferSearch'];
	if (!empty($saferSearch)) {
		$saferSearch = '&dbSearch='.$saferSearch;
	}
	echo "\t\t\t";
	echo '<tr><th class="th0" onclick="showCheckBoxes(); return true;">#</th>';
	echo '<th class="th4">';
	if (!$isDemo) {
		echo '<input tabindex="-1"'.(isAdmin() ? ' class="tHidden"' : '').'type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this); return true;">';
	}

	if (empty($sort)) {
		$sort = "";
	}

	echo '<span style="padding-right:10px;"> </span><a tabindex="-1" style="font-weight:bold;'.(($sort == 'title' || $sort == 'titlea') ? ' color:red;' : '').'" href="?show=filme&sort='.($sort == 'titlea' ? 'title' : 'titlea').($saferSearch).'">Title</a>'.$titleInfo.'</th>';
	echo '<th class="th02"><a tabindex="-1" style="font-weight:bold;'.(($sort == 'jahr' || $sort == 'jahra') ? ' color:red;' : '').'" href="?sort='.($sort == 'jahr' ? 'jahra' : 'jahr').($saferSearch).'">Year</a></th>';
	echo '<th class="th1"><a tabindex="-1" style="font-weight:bold;'.(($sort == 'rating' || $sort == 'ratinga') ? ' color:red;' : '').'" href="?sort='.($sort == 'rating' ? 'ratinga' : 'rating').($saferSearch).'">Rating</a></th>';
	echo '<th class="th3 hideMobile">Genre</th>';
	if ($dirActorEnabled) {
		echo '<th class="th2 hideMobile">Actor</th>';
		echo '<th class="th2 hideMobile">Director</th>';
	}
	if (!$isDemo) {
		echo '<th class="th5">Res</th>';
		echo '<th class="th5">Codec</th>';
		echo '<th class="th6"><a tabindex="-1" style="font-weight:bold;'.(($sort == 'size' || $sort == 'sizea') ? 'color:red;' : '').'" href="?sort='.($sort == 'size' ? 'sizea' : 'size').($saferSearch).'">Size</a></th></tr>';
	}
	echo "\r\n";

	generateForm();

	$zeilenCount = count($zeilen);
	for ($z = 0; $z < $zeilenCount; $z++) {
		echo "\t\t\t\t".'<tr class="searchFlag">';
		$zeile = $zeilen[$z];
		for ($sp = 1; $sp < count($zeile); $sp++) {
			$spalte = str_replace('_C0UNTER_', $z + 1, $zeile[$sp]);
			if ($z == $zeilenCount - 1) {
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
}

function postGalleryRows($zeilen): void {
	generateForm();

	$isAdmin     = isAdmin();
	$xbmcRunning = xbmcRunning();
	$elemsInRow  = getElemsInRow();
	$iElems      = count($zeilen);

	echo "\t\t\t\t<tr>";
	$spread = -1;
	$thumbsAddedInRow = 0;
	for ($t = 0; $t < $iElems; $t++) {
		if ($t % $elemsInRow == 0 && $t > 0) {
			$thumbsAddedInRow = 0;
			echo "\n\t\t\t\t</tr>\r\n\t\t\t\t<tr>";

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

		echo "\n\t\t\t\t\t";
		echo '<td class="galleryTD">';
		$covImg = (!empty($zeilen[$t][3]) ? $zeilen[$t][3] : './img/nothumb.png');
		echo '<div class="galleryCover" style="background:url('.$covImg.') #FFFFFF no-repeat;">';
		echo '<div class="galleryCoverHref1 fancy_iframe" href="./?'.$zeilen[$t][1].'" title="'.$zeilen[$t][0].'"></div>';

		$playCount = $zeilen[$t][2] >= 1 && $isAdmin;
		$is3d = $zeilen[$t][4];
		$path = $zeilen[$t][5];
		$filename = $zeilen[$t][6];

		$showSpan = $is3d || $playCount;
		$break3d = $is3d && $isAdmin && $playCount;
		$breakPl = false;

		$playItem = '';
		$filename = prepPlayFilename($path.$filename);
		if ($isAdmin && $xbmcRunning && !empty($path) && !empty($filename)) {
			$showSpan = true;
			$breakPl = $playCount;
			$playItem = '<img src="./img/play.png" class="icon24 galleryPlay'.($is3d ? ' galleryPlay2nd' : '').'" onclick="playItem(\''.$filename.'\'); return false;" />';
		}

		if ($showSpan) {
			echo '<div class="gallerySpan" style="cursor:pointer;">';
		}
		if ($showSpan && $is3d) {
			echo '<img src="./img/3d.png" class="icon24 gallery3d" />';
		}
		echo $playItem;

		$gCnt = 0;
		if ($xbmcRunning) {
			$gCnt++;
		}
		if ($break3d) {
			$gCnt++;
		}
		if ($playCount) {
			$gCnt++;
		}

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

		if ($playCount) {
			echo '<img src="./img/check.png" class="icon32 gallery'.$gCount.'" style="cursor:default;" />';
		}
		if ($showSpan) {
			echo '</div>';
		}
		echo '</div>';
		echo '</td>';

		$thumbsAddedInRow++;

		if (isset($matrix)) {
			$thumbsAddedInRow = echoEmptyTdIfNeeded($matrix, $thumbsAddedInRow, $elemsInRow);
		}
	}
	echo "\n";
	echo "\t\t\t\t".'</tr>';
	echo "\n";
}

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
	return ($gallerymode != 1 ? $COLUMNCOUNT : (min($newAddedCount, $elementsInRow)));
}

function generateForm() {
	$mode        = isset($_SESSION['mode'])        ? $_SESSION['mode']        : 0;
	$newmode     = isset($_SESSION['newmode'])     ? $_SESSION['newmode']     : 0;
	$newsort     = isset($_SESSION['newsort'])     ? $_SESSION['newsort']     : 0;

	echo "\t\t\t";
	echo '<form action="" name="moviefrm" method="post">';
	echo "\r\n";

	if ($newmode) {
		if (!isAdmin()) {
			$sizes = isset($GLOBALS['SHOW_NEW_VALUES']) ? $GLOBALS['SHOW_NEW_VALUES'] : array(30, 60);
			$newAddedCount = getNewAddedCount();

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
		echo "\n\t\t\t\t\t";
		echo '<td class="galleryEmptyTD">&nbsp;</td>';
		$thumbsAddedInRow++;
	}
	return $thumbsAddedInRow;
}
/*          FUNCTIONS          */
?>
