<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "./template/_SERIEN.php";

	header("Content-Type: text/html; charset=UTF-8");
	
	$isAdmin = isAdmin();
	$isDemo  = isDemo();
	
	$id       = getEscGPost('id');
	$idSeason = getEscGPost('idSeason');
	
	if ($id == null || $id <= 0) { die('No id given!'); }
	
	$_SESSION['tvShowParam']['idEpisode'] = $id;
	$_SESSION['tvShowParam']['idSeason']  = $idSeason;
	
	$SQL =  $GLOBALS['SerienSQL'].' AND V.idEpisode = '.$id.';';
	
	$idFile     = 0;
	$title      = '';
	$epDesc     = '';
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
	
	$existArtTable = existsArtTable();

	$result = querySQL($SQL);
	foreach($result as $row) {
		$idFile     = $row['idFile'];
		$title      = trimDoubles(trim($row['epName']));
		$epDesc     = trimDoubles(trim($row['epDesc']));
		$path       = $row['path'];
		$filename   = $row['filename'];
		$lastPlayed = $row['lastPlayed'];
		$playCount  = $row['playCount'];
		$epRating   = $row['epRating'];
		$season     = $row['season'];
		$episode    = $row['episode'];
		$airDate    = $row['airDate'];
		$filesize   = $row['filesize'];
	}
	
	$percent;
	$pausedAt;
	$timeAt;
	$timeTotal;
	if ($playCount <= 0) {
		$result    = fetchFromDB("SELECT timeInSeconds AS timeAt, totalTimeInSeconds AS timeTotal FROM bookmark WHERE idFile = '".$idFile."';");
		$timeAt    = $result['timeAt'];
		$timeTotal = $result['timeTotal'];
		$pausedAt  = getPausedAt($timeAt);
		$percent   = round($timeAt / $timeTotal * 100, 0);
	}
	
	$coverP = $path;
	
	if ($season  < 10) { $season  = '0'.$season;  }
	if ($episode < 10) { $episode = '0'.$episode; }
	
	$path  = mapSambaDirs($path);
	$fsize = _format_bytes(fetchFileSize($idFile, $path, $filename, $filesize, null));
	
	$duration   = 0;
	$ar         = null;
	$width      = null;
	$height     = null;
	$vCodec     = null;
	$aCodec     = array();
	$aChannels  = array();
	$aLang      = array();
	$subtitle   = array();
	
	$stream = getStreamDetails($idFile);
	foreach($stream as $stRow) {
		$tmp = $stRow['fVideoAspect'];
		if (!empty($tmp)) {
			if ($tmp != '') {
				$tmp  = round($tmp, 2);
				$tmp .= (strlen($tmp) < 4 ? '0' : '').':1';
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
		if (!empty($tmp)) { $vCodec = strtoupper($tmp); }

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
	$thumbsUp = isset($GLOBALS['TVSHOW_THUMBS']) ? $GLOBALS['TVSHOW_THUMBS'] : false;
	$ENCODE   = isset($GLOBALS['ENCODE_IMAGES_TVSHOW']) ? $GLOBALS['ENCODE_IMAGES_TVSHOW'] : true;
	
	if ($thumbsUp) {
		$fromSrc = isset($GLOBALS['TVSHOW_THUMBS_FROM_SRC']) ? $GLOBALS['TVSHOW_THUMBS_FROM_SRC'] : false;
		if ($fromSrc && empty($sessionImg)) {
			// READ EMBER GENERATED THUMBS FROM SOURCE
			$DIRMAP_IMG = isset($GLOBALS['DIRMAP_IMG']) ? $GLOBALS['DIRMAP_IMG'] : null;
			
			#echo $path.$filename.'<br/>';
			$sessionImg = mapSambaDirs($path, $DIRMAP_IMG).substr($filename, 0, strlen($filename)-3).'tbn';
			$smb = (substr($sessionImg, 0, 6) == 'smb://');
			
			if (file_exists($sessionImg)) {
				$thumbImg = getImageWrap($sessionImg, $idFile, 'file', 0, $ENCODE || $smb ? 'encode' : null);
			}
		}
		
		if (empty($sessionImg)) {
			$sessionImg = getTvShowThumb($coverP.$filename);
			$thumbImg   = getImageWrap($sessionImg, $idFile, 'file', 0, $ENCODE ? 'encode' : null);
			
			if (empty($sessionImg) && $existArtTable) {
				#$res2 = $dbh->query("SELECT url FROM art WHERE url NOT NULL AND url NOT LIKE '' AND media_type = 'episode' AND type = 'thumb' AND media_id = '".$id."';");
				#$row2 = $res2->fetch();
				$SQL  = "SELECT url FROM art WHERE url NOT NULL AND url NOT LIKE '' AND media_type = 'episode' AND type = 'thumb' AND media_id = '".$id."';";
				$row2 = fetchFromDB_($dbh, $SQL, false);
				$url = $row2['url'];
				#logc( $id.' - '.$url );
				if (!empty($url)) {
					$sessionImg = getTvShowThumb($url);
					if (file_exists($img)) {
						$thumbImg = getImageWrap($sessionImg, $idFile, 'file', 0, $ENCODE ? 'encode' : null);
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
	echo '<div style="padding-bottom:'.(!empty($thumbImg) ? '2' : '15').'px;"><div><u><i><b>Title:</b></i></u></div><span>'.$title.' <font color="silver">[</font> S'.$season.'.E'.$episode.' <font color="silver">]</font></span>';
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
		echo '<div class="thumbDiv"><img class="thumbImg" src="'.$thumbImg.'" /></div>';
	}
	
	echo '<div style="padding-right:5px;">';
	if (!empty($epDesc)) {
		$spProtect = isset($GLOBALS['SPOILPROTECTION']) ? $GLOBALS['SPOILPROTECTION'] : true;
		
		$tmp = '<div class="epDesc"><u><i><b>Description:</b></i></u><br />'.$epDesc.'</div>';
		if (!$isAdmin || ($isAdmin && empty($playCount))) {
			echo '<div id="epSpoiler" class="padbot15" onclick="spoilIt(); return false;"><u><i><b>Description:</b></i></u> <span style="color:red; cursor:pointer;">spoil it!</span></div>';
			echo '<span id="epDescr" style="display:none;">';
			echo $tmp;
			echo '</span>';
			
		} else if (!$spProtect || !empty($playCount)) {
			echo $tmp;
		}
	}
	
	$rating = substr($epRating, 0, 3);
	if ($rating != '0.0') {
		echo '<div'.(empty($duration) ? ' class="padbot15"' : '').'><span><u><i><b>Rating:</b></i></u></span><span class="flalright">'.$rating.'</span></div>';
	}

	if (!empty($duration)) {
		$duration = round($duration / 60, 0);
		echo '<div class="padbot15"><span><u><i><b>Duration:</b></i></u></span><span class="flalright">'.$duration.' min</span></div>';
	}
	
	if (!empty($airDate)) {
		$dayOfWk = dayOfWeekShort($airDate);
		$airDate = toEuropeanDateFormat($airDate);
		echo '<div><span><u><i><b>Airdate:</b></i></u></span><span class="flalright" style="width:35px;"><font color="silver">(</font>'.$dayOfWk.'<font color="silver">)</font></span> <span class="flalright" style="padding-right:3px;">'.$airDate.'</span> </div>';
	}
	
	if ($isAdmin) {
		if (!empty($lastPlayed) && $playCount > 0) {
			$dayOfWk    = dayOfWeekShort($airDate);
			$lastPlayed = toEuropeanDateFormat(substr($lastPlayed, 0, 10));
			echo '<div><span><u><i><b>Watched:</b></i></u></span><span class="flalright" style="width:35px;"><font color="silver">(</font>'.$dayOfWk.'<font color="silver">)</font></span><span class="flalright" style="padding-right:5px;">'.$lastPlayed.'</span></div>';
		}
	}
	
	if (!$isDemo) {
		echo '<div class="padtop15"><span><u><i><b>Size:</b></i></u></span><span class="flalright">'.$fsize.'</span></div>';
	}
	
	if ($isAdmin) {
		echo '<div class="padtop15" style="overflow-x:hidden;"><u><i><b>File:</b></i></u><br />'.encodeString($path.$filename).'</div>';
	}
	
	if (!$isDemo) {
		if (!empty($width) && !empty($height)) {
			echo '<div class="padtop15"><span><u><i><b>Video:</b></i></u></span><span class="flalright">'.$width.'x'.$height.(!empty($ar) ? ' <font color="silver">[</font> '.$ar.' <font color="silver">]</font>' : '').'</span></div>';
		}
		if (!empty($aCodec)) {
			$codecs = '';
			$countyMap = getCountyMap();
			for($i = 0; $i < count($aCodec); $i++) { $codecs .= postEditCodec($aCodec[$i]).getLanguage($countyMap, $aLang, $i).($i < count($aCodec)-1 ? ' <font color="silver">|</font> ' : ''); }
			echo '<div style="overflow-x:hidden;"><span><u><i><b>Audio:</b></i></u></span><span class="flalright">'.count($aCodec).' <font color="silver">[</font> '.$codecs.' <font color="silver">]</font></span></div>';
		}
		if (!empty($subtitle)) {
			$codecs = '';
			$countyMap = getCountyMap();
			for($i = 0; $i < count($subtitle); $i++) { $codecs .= getLanguage($countyMap, $aLang, $i, false).($i < count($subtitle)-1 ? ' <font color="silver">|</font> ' : ''); }
			echo '<div style="overflow-x:hidden;"><span><u><i><b>Sub:</b></i></u></span><span class="flalright">'.count($subtitle).' <font color="silver">[</font> '.$codecs.' <font color="silver">]</font></span></div>';
		}
	}
	
	if ($isAdmin) {
		echo '<div class="padtop15" style="overflow-x:hidden;"><u><i><b>idEpisode:</b></i></u><span class="flalright">'.$id.'</span></div>';
	}
	echo '</div>';
	echo '</div></td>';
	echo '</tr>';
	echo '</table>';
	
//- FUNCTIONS -//
function getLanguage($countyMap, $aLang, $i, $trenner = true) {
	return isValidLang($countyMap, $aLang, $i) ? ($trenner ? ' - ' : '').postEditLanguage(strtoupper($aLang[$i]), false) : '';
}

function isValidLang($countyMap, $aLang, $i) {
	return isset($aLang[$i]) && isset($countyMap[strtoupper($aLang[$i])]);
}
//- FUNCTIONS -//
?>