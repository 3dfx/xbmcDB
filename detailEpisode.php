<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "./template/_SERIEN.php";

	header("Content-Type: text/html; charset=UTF-8");

	$isAdmin  = isAdmin();
	$isDemo   = isDemo();
	$id       = getEscGPost('id');
	$idSeason = getEscGPost('idSeason');

	if ($id == null || $id <= 0) { die('No id given!'); }

	$_SESSION['tvShowParam']['idEpisode'] = $id;
	$_SESSION['tvShowParam']['idSeason']  = $idSeason;
	$SOURCE = $GLOBALS['SOURCE'];

	$idFile     = 0;
	$title      = '';
	$epDesc     = '';
	$guests     = null;
	$path       = '';
	$coverP     = '';
	$filename   = '';
	$lastPlayed = '';
	$playCount  = 0;
	$epRating   = '';
	$season     = '';
	$episode    = '';
	$airDate    = '';
	$filesize   = 0;
	$fsize      = 0;
	$src        = null;
	$fps        = null;
	$bits       = null;

	$dbh = getPDO();
	$existArtTable = existsArtTable();

	$SQL    = $GLOBALS['SerienSQL'].' AND V.idEpisode = '.$id.';';
	$result = querySQL($SQL);
	foreach($result as $row) {
		$idFile     = $row['idFile'];
		$title      = trimDoubles(trim($row['epName']));
		$epDesc     = trimDoubles(trim($row['epDesc']));
		$guests     = $row['guests'];
		$path       = $row['path'];
		$filename   = $row['filename'];
		$lastPlayed = $row['lastPlayed'];
		$playCount  = $row['playCount'];
		$epRating   = $row['epRating'];
		$season     = $row['season'];
		$episode    = $row['episode'];
		$airDate    = $row['airDate'];
		$filesize   = $row['filesize'];
		$src        = $row['source'];
		$fps        = $row['fps'];
		$bits       = $row['bits'];
	}

	$percent;
	$pausedAt;
	$timeAt;
	$timeTotal;
	if ($playCount <= 0) {
		$result    = fetchFromDB("SELECT timeInSeconds AS timeAt, totalTimeInSeconds AS timeTotal FROM bookmark WHERE idFile = '".$idFile."';");
		$timeAt    = $result['timeAt'];
		$timeTotal = $result['timeTotal'];
		if (!empty($timeAt) && !empty($timeTotal)) {
			$pausedAt  = getPausedAt($timeAt);
			$percent   = round($timeAt / $timeTotal * 100, 0);
		}
	}

	$result = fetchFromDB("SELECT delta AS delta FROM episodelinkepisode WHERE idFile = '".$idFile."';");
	$delta  = empty($result['delta']) ? null : $result['delta'];

	$coverP  = $path;
	$episod_ = $episode;
	$season  = sprintf("%02d", $season);
	$episode = sprintf("%02d", $episode);
	if (!empty($delta)) {
		$delta   += $episod_;
		$delta    = '-E'.sprintf("%02d", $delta);
		$episode  = $episode.$delta;
	}
	$path  = mapSambaDirs($path);
	$fsize = _format_bytes(fetchFileSize($idFile, $path, $filename, $filesize, null));
	$fps   = fetchFps($idFile, $path, $filename, array($bits, $fps), getPDO());
	if ($fps != null) {
		$bits = $fps[0];
		$fps  = $fps[1];
	}
#if (isAdmin()) { print_r( $fps ); }

	$duration  = 0;
	$ar        = null;
	$width     = null;
	$height    = null;
	$vCodec    = null;
	$aCodec    = array();
	$aChannels = array();
	$aLang     = array();
	$subtitle  = array();

	$stream = getStreamDetails($idFile);
	foreach($stream as $stRow) {
		$tmp = $stRow['fVideoAspect'];
		if (!empty($tmp)) {
			if ($tmp != '') {
				$tmp  = round($tmp, 2);
				if ($tmp-1 != 1 && $tmp-1 != 0)
					$tmp .= (strlen($tmp) < 4 ? '0' : '');
				$tmp .= ':1';
			}
			$ar = $tmp;
		}

		$tmp = $stRow['iVideoWidth'];
		if (!empty($tmp)) { $width = $tmp; }

		$tmp = $stRow['iVideoHeight'];
		if (!empty($tmp)) { $height = $tmp; }

		$tmp = $stRow['iVideoDuration'];
		if (!empty($tmp)) { $duration = $tmp; }

		$tmp = $stRow['strVideoCodec'];
		if (!empty($tmp)) { $vCodec = $tmp; }

		$tmp = $stRow['strAudioCodec'];
		if (!empty($tmp)) { $aCodec[count($aCodec)] = strtoupper($tmp); }

		$tmp = $stRow['iAudioChannels'];
		if (!empty($tmp)) { $aChannels[count($aChannels)] = $tmp; }

		$tmp = $stRow['strAudioLanguage'];
		if (!empty($tmp)) { $aLang[count($aLang)] = $tmp; }

		$tmp = $stRow['strSubtitleLanguage'];
		if (!empty($tmp)) { $subtitle[count($subtitle)] = $tmp; }
	}

	$thumbImg   = null;
	$sessionImg = null;
	$thumbsUp = isset($GLOBALS['TVSHOW_THUMBS'])        ? $GLOBALS['TVSHOW_THUMBS']        : false;
	$ENCODE   = isset($GLOBALS['ENCODE_IMAGES_TVSHOW']) ? $GLOBALS['ENCODE_IMAGES_TVSHOW'] : true;

	if ($thumbsUp) {
		$fromSrc = isset($GLOBALS['TVSHOW_THUMBS_FROM_SRC']) ? $GLOBALS['TVSHOW_THUMBS_FROM_SRC'] : false;
		if ($fromSrc) {
			// READ EMBER GENERATED THUMBS FROM SOURCE
			$DIRMAP_IMG = isset($GLOBALS['DIRMAP_IMG']) ? $GLOBALS['DIRMAP_IMG'] : null;

			$sessionImg = mapSambaDirs($path, $DIRMAP_IMG).substr($filename, 0, strlen($filename)-3).'tbn';
			$smb = (substr($sessionImg, 0, 6) == 'smb://');

			if (file_exists($sessionImg)) {
				$thumbImg = getImageWrap($sessionImg, $idFile, 'file', 0, $ENCODE || $smb ? 'encoded' : null);
			} else {
				unset($sessionImg);
			}
		}

		if (empty($sessionImg)) {
			$sessionImg = getTvShowThumb($coverP.$filename);
			$thumbImg   = getImageWrap($sessionImg, $idFile, 'file', 0);

			if ((empty($sessionImg) || !file_exists($sessionImg)) && $existArtTable) {
				$SQL  = "SELECT url FROM art WHERE url NOT NULL AND url NOT LIKE '' AND media_type = 'episode' AND type = 'thumb' AND media_id = '".$id."';";
				$row2 = fetchFromDB_($dbh, $SQL, false);
				$url = $row2['url'];
				if (!empty($url)) {
					$sessionImg = getTvShowThumb($url);
					if (file_exists($sessionImg)) {
						$thumbImg = getImageWrap($sessionImg, $idFile, 'file', 0);
					}
				}
			}
		}

		wrapItUp('file', $idFile, $sessionImg);
	}

	if (!empty($timeAt)) { echo "<script type=\"text/javascript\">$(document).ready(function() { $('.knob-dyn').knob(); });</script>"; }

	echo '<table id="epDescription" class="film">';
	echo '<tr class="showDesc">';
	echo '<td class="showDescTD2">';
	echo '<div style="width:300px;">';
		echo '<div style="padding-bottom:'.(!empty($thumbImg) ? '2' : '15').'px;">';
			echo '<span style="float:left;"><i><b>Title:</b></i></span>';
			echo '<span style="float:right;"><font color="silver">[</font> S'.$season.'.E'.$episode.' <font color="silver">]</font></span>';
		echo '</div>';
		echo '<br /><span onclick="selSpanText(this);">'.$title.'</span>';
		echo '<span class="epCheckSpan"'.($isAdmin && !empty($percent) ? ' title="'.$pausedAt.' ('.$percent.'%)"' : '').'>';
			if ($isAdmin) {
				if ($playCount > 0) {
					echo '<img src="./img/check.png" class="galleryImage thumbCheck" style="position:relative; bottom:4px;" title="watched" />';
				} else if (!empty($percent)) {
					echo '<input type="text" class="knob-dyn" data-width="15" data-fgColor="#6CC829" data-angleOffset="180" data-thickness=".4" data-displayInput="false" data-readOnly="true" value="'.$percent.'" style="display:none;" />';
				}
			}
		echo '</span>';
	echo '</div>';

	if (!empty($thumbImg)) {
		echo '<div class="thumbDiv"><img id="thumbImg" class="thumbImg" src="'.$thumbImg.'" /></div>';
	}

	echo '<div style="padding-right:5px;">';
	if (!empty($epDesc)) {
		$spProtect = isset($GLOBALS['SPOILPROTECTION']) ? $GLOBALS['SPOILPROTECTION'] : true;

		$descDiv = '<div class="epDesc"><i><b>Description:</b></i><br />'.$epDesc.'</div>';
		if (!$isAdmin || ($isAdmin && empty($playCount))) {
			echo '<div id="epSpoiler" class="padbot15" onclick="spoilIt(); return false;"><i><b>Description:</b></i> <span style="color:red; cursor:pointer; float:right;">spoil it!</span></div>';
			echo '<span id="epDescr" style="display:none;">';
			echo $descDiv;
			echo '</span>';

		} else if (!$spProtect || !empty($playCount)) {
			echo $descDiv;
		}
	}

	$guests = getGuests($guests);
	if (!empty($guests)) {
		$gString = '';
		$len = count($guests);
		for ($i = 0; $i < $len; $i++) { $gString .= $guests[$i].($i < $len-1 ? '<br />' : ''); }
		echo '<div id="epGuest" class="padbot15" onclick="showGuests(); return false;"><i><b>Guests:</b></i> <span style="color:red; cursor:pointer; float:right;">show guests!</span></div>';
		echo '<div id="epGuests" class="padbot15" style="display:none;"><i><b>Guests:</b></i><br />'.$gString.'</div>';
	}

	$rating = formatRating($epRating);
	if (!emptyRating($rating)) {
		echo '<div'.(empty($duration) ? ' class="padbot15"' : '').'><span><i><b>Rating:</b></i></span><span class="flalright">'.$rating.'</span></div>';
	}

	if (!empty($duration)) {
		$duration = round($duration / 60, 0);
		echo '<div class="padbot15"><span><i><b>Duration:</b></i></span><span class="flalright">'.$duration.' min</span></div>';
	}

	if (!empty($airDate)) {
		$dayOfWk = dayOfWeekShort($airDate);
		$airDate = toEuropeanDateFormat($airDate);
		echo '<div><span><i><b>Airdate:</b></i></span><span class="flalright" style="width:45px;"><font color="silver">[ </font>'.$dayOfWk.'<font color="silver"> ]</font></span> <span class="flalright" style="padding-right:3px;">'.$airDate.'</span> </div>';
	}

	if ($isAdmin) {
		if (!empty($lastPlayed) && $playCount > 0) {
			$dayOfWk    = dayOfWeekShort($lastPlayed);
			$lastPlayed = toEuropeanDateFormat(substr($lastPlayed, 0, 10));
			echo '<div><span><i><b>Watched:</b></i></span><span class="flalright" style="width:45px;"><font color="silver">[ </font>'.$dayOfWk.'<font color="silver"> ]</font></span><span class="flalright" style="padding-right:5px;">'.$lastPlayed.'</span></div>';
		}
	}

	if (!$isDemo) {
		if (!empty($vCodec) || (!empty($width) && !empty($height))) {
			echo '<div class="padtop15 padbot20">';
			echo '<span><i><b>Video:</b></i></span>';
			if ((!empty($width) && !empty($height)) || !empty($fps)) {
				echo '<span class="flalright">';
				if (!empty($width) && !empty($height)) {
					echo $width.'x'.$height.(!empty($ar) ? ' <font color="silver">[</font> '.$ar.' <font color="silver">]</font>' : '');
					if (!empty($fps))
						echo '<br/>';
				}
				if (!empty($fps))
					echo '<span class="flalleft">'.$fps.' fps</span>';
				echo '</span>';
			}
			if (!empty($vCodec) || !empty($bits)) {
				echo '<span class="flalright">';
				$color = '';
				if (!empty($vCodec)) {
					$cols   = isset($GLOBALS['CODEC_COLORS']) ? $GLOBALS['CODEC_COLORS'] : null;
					$vCodec = postEditVCodec($vCodec);
					$perf   = decodingPerf($vCodec, !empty($bits) && $bits >= 10);
					$color  = $cols == null ? '#000000' : $cols[$perf];
					echo '<font color="'.$color.'">'.$vCodec.'</font>&nbsp;<font color="silver"><b>|</b></font>&nbsp;';
					if (!empty($bits))
						echo '<br/>';
				}
				if (!empty($bits))
					echo $bits.' bit&nbsp;<font color="silver"><b>|</b></font>&nbsp;';
				echo '</span>';
			}
			echo '</div>';
		}

		$countryMap = !empty($aCodec) || !empty($subtitle) ? getCountryMap() : null;
		if (!empty($aCodec)) {
			$codecs = '';
			for ($i = 0; $i < count($aCodec); $i++) { $codecs .= postEditACodec($aCodec[$i]).(isset($aChannels[$i]) ? ' '.postEditChannels($aChannels[$i]) : '').getLanguage($countryMap, $aLang, $i).($i < count($aCodec)-1 ? ' <font color="silver"><b>|</b></font> ' : ''); }
			echo '<div style="overflow-x:hidden;"><span><i><b>Audio:</b></i></span><span class="flalright">'.count($aCodec).' <font color="silver">[</font> '.$codecs.' <font color="silver">]</font></span></div>';
		}

		if (!empty($subtitle)) {
			$subtitle = array_unique($subtitle);
			$cCount = 0;
			$codecs = '';
			foreach ($subtitle as $i => $sub) {
				$codec = getLanguage($countryMap, $subtitle, $i, false);
				if (!empty($codec)) {
					$codecs .= ($cCount > 0 && !empty($sub) ? ' <font color="silver"><b>|</b></font> ' : '') . $codec;
				}
				$cCount++;
			}
			echo '<div style="overflow-x:hidden;"><span title="Count is not unique"><i><b>Sub:</b></i></span><span class="flalright">'.$cCount.' <font color="silver">[</font> '.$codecs.' <font color="silver">]</font></span></div>';
		}
	} // !isDemo

	if (!$isDemo) {
		if ($isAdmin) {
			echo '<div class="padtop15"><hr style="position:width:335px; margin:0px; border-bottom-width:0px; border-color:#BBCCDD;" /></div>';
			echo '<div class="padtop15"><span><i><b>Source:</b></i></span><span class="flalright">'.$SOURCE[$src].'</span></div>';
		}
		echo '<div class="padtop15"><span><i><b>Size:</b></i></span><span class="flalright">'.$fsize.'</span></div>';
	}
	if ($isAdmin) {
		echo '<div style="overflow-x:hidden;"><i><b>File:</b></i><br />';
		$filename = '<span onclick="selSpanText(this);">'.encodeString($filename).'</span>';
		echo encodeString($path).$filename;
		echo '</div>';

		echo '<div class="padtop15" style="overflow-x:hidden;"><i><b>idEpisode</b>/<b>idFile:</b></i><span class="flalright">'.$id.' <b>|</b> '.$idFile.'</span></div>';
	}
	echo '</div>';
	echo '</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';

//- FUNCTIONS -//
function getLanguage($countryMap, $languages, $i, $trenner = true) {
	return isValidLang($countryMap, $languages, $i) ? ($trenner ? ' - ' : '').postEditLanguage(strtoupper($languages[$i]), false) : '';
}

function isValidLang($countryMap, $languages, $i) {
	return isset($languages[$i]) && isset($countryMap[strtoupper($languages[$i])]);
}
//- FUNCTIONS -//
?>
