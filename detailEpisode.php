<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "./template/Series/_SERIEN.php";
include_once "./template/Series/StreamDetails.php";
?>
<head>
<?php if (isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/hoverpic.js"></script>
<?php } else { ?>
	<script type="text/javascript" src="./template/js/hoverpic.min.js"></script>
<?php } ?>
	<script type="text/javascript">$(document).ready(function() { initShowFancies(); });</script>
</head>
<?php
	header("Content-Type: text/html; charset=UTF-8");

	$VAL_DELIM = '&nbsp;<font color="silver"><b>|</b></font>&nbsp;';

	$isAdmin   = isAdmin();
	$isDemo    = isDemo();
	$idEpisode = getEscGPost('id');
	$idSeason  = getEscGPost('idSeason');

	if ($idEpisode == null || $idEpisode <= 0) { die('No id given!'); }

	$_SESSION['tvShowParam']['idEpisode'] = $idEpisode;
	$_SESSION['tvShowParam']['idSeason']  = $idSeason;

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
	$src        = null;
	$fps        = null;
	$bits       = null;

	$dbh = getPDO();
	$existArtTable = existsArtTable();

/* TODO: check reasoning
	$ep = getCachedEpisode($idEpisode);
	if (!empty($ep)) {
		$ep = unserialize($ep);
	}
*/

	$SQL    = $GLOBALS['EpisodeSQL'].' WHERE V.idEpisode = '.$idEpisode.';';
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
		$atmosx     = $row['atmosx'];
	}

	$pausedAt  = '';
	$percent   = null;
	$timeAt    = null;
	$timeTotal = null;
	if ($playCount <= 0) {
		$result    = fetchFromDB("SELECT timeInSeconds AS timeAt, totalTimeInSeconds AS timeTotal FROM bookmark WHERE idFile = '".$idFile."';");
		$timeAt    = empty($result['timeAt'])    ? null : $result['timeAt'];
		$timeTotal = empty($result['timeTotal']) ? null : $result['timeTotal'];
		if (!empty($timeAt) && !empty($timeTotal)) {
			$pausedAt  = getPausedAt($timeAt);
			$percent   = round($timeAt / $timeTotal * 100);
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

	$streamDetails = new StreamDetails($idFile, $idEpisode, $path, $filename, $filesize);
	if ($streamDetails->getFetchedFPS() != null) {
		$bits = $streamDetails->getFetchedFPS()[0];
		$fps  = $streamDetails->getFetchedFPS()[1];
	}
	$thumbImg = getThumbImg($idFile, $idEpisode, $path, $filename, $coverP, $existArtTable, $dbh);

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
					echo '<img src="./img/check.png" class="galleryImage thumbCheck" style="position:relative; bottom:4px;" />';

				} else if (!empty($percent)) {
					echo '<input type="text" class="knob-dyn" data-width="15" data-fgColor="#6CC829" data-angleOffset="180" data-thickness=".4" data-displayInput="false" data-readOnly="true" value="'.$percent.'" style="display:none;" />';
				}
			}
		echo '</span>';
	echo '</div>';

	if (!empty($thumbImg)) {
		echo '<div class="thumbDiv"><img id="thumbImg" class="thumbImg" src="'.$thumbImg.'" /></div>';
	}

	echo '<div style="width:300px;">';
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

	$covers = fetchActorCovers($idEpisode, 'episode', true, $dbh);
	if (!empty($covers)) {
		$output = createActorsDiv($covers, $dbh);

		if (!empty($output)) {
			echo '<div id="epActor" class="padbot15" onclick="episodeActors(); return false;"><i><b>Actors:</b></i> <span style="color:red; cursor:pointer; float:right;">show actors!</span></div>';
			echo '<div id="epActors" class="padbot15" style="display:none;"><i><b>Actors:</b></i><br />'.$output.'</div>';
		}
	}

	$rating = null; //DISABLED: formatRating($epRating);
	if (!emptyRating($rating)) {
		echo '<div'.(empty($streamDetails->getDuration()) ? ' class="padbot15"' : '').'><span><i><b>Rating:</b></i></span><span class="flalright">'.$rating.'</span></div>';
	}

	if (!empty($streamDetails->getDuration())) {
		$duration = round($streamDetails->getDuration() / 60);
		echo '<div class="padbot15"><span><i><b>Duration:</b></i></span><span class="flalright">'.$duration.' min</span></div>';
	}

	if (!empty($airDate)) {
		echo '<div><span><i><b>Airdate:</b></i></span>';
		echo '<span class="flalright" style="width:45px;"><font color="silver">[ </font>'.dayOfWeekShort($airDate).'<font color="silver"> ]</font></span> ';
		echo '<span class="flalright" style="padding-right:3px;">'.toEuropeanDateFormat($airDate).'</span> ';
		echo '</div>';
	}

	if ($isAdmin) {
		if (!empty($lastPlayed)) {
			echo '<div><span><i><b>Watched:</b></i></span>';
			echo '<span class="flalright" style="width:45px;"><font color="silver">[ </font>'.dayOfWeekShort($lastPlayed).'<font color="silver"> ]</font></span>';
			echo '<span class="flalright" style="padding-right:3px;">'.toEuropeanDateFormat(substr($lastPlayed, 0, 10)).'</span>';
			echo '</div>';
		}

		if ($playCount > 1) {
			echo '<div><span><i><b>Count:</b></i></span><span class="flalright" style="padding-right:5px;">'.$playCount.'x</span></div>';
		}
	}

	if (!$isDemo) {
		if (!empty($streamDetails->getVCodec()) || (!empty($streamDetails->getWidth()) && !empty($streamDetails->getHeight()))) {
			echo '<div class="padtop15 padbot20">';
			echo '<span><i><b>Video:</b></i></span>';
			if ((!empty($streamDetails->getWidth()) && !empty($streamDetails->getHeight())) || !empty($fps)) {
				$arInner = "";
				$ar   = $streamDetails->getAr();
				$arOR = $streamDetails->getArOR();

				$arInner = '';
				$arSP1   = '';
				$arSP2   = '';

				if (!empty($arOR)) {
					$ar = $arOR;
					$arSP1 = '<span style="font-style:italic;" title="aspect-ratio // overridden"> ';
					$arSP2 = '</span>';
				}

				if (!empty($ar)) {
					$arInner = $arSP1.' <font color="silver">[</font> '.$ar.':1 <font color="silver">]</font>'.$arSP2;
				}

				$arSpan = '<span style="cursor:pointer;" onclick="setAspectRatio('.$idFile.', '.$idEpisode.', '.$ar.'); return false;">'.$arInner.'</span>';

				echo '<span class="flalright">';
				if (!empty($streamDetails->getWidth()) && !empty($streamDetails->getHeight())) {
					echo $streamDetails->getWidth().'x'.$streamDetails->getHeight().$arSpan;
					if (!empty($fps)) {
						echo '<br/>';
					}
				}
				if (!empty($fps)) {
					echo '<span class="flalleft">'.$fps.' fps</span>';
				}
				echo '</span>';
			}

			if (!empty($streamDetails->getVCodec()) || !empty($bits)) {
				$cAction = '';
				if (!empty($streamDetails->getHdrType())) {
					$param = empty($streamDetails->getHdrToneMapParam()) ? 1.0 : $streamDetails->getHdrToneMapParam();
					$cAction = ' style="cursor:pointer;" onclick="setToneMapParam('.$idFile.', '.$param.'); return false;"';
				}

				echo '<span'.$cAction.' class="flalright">';
				$color = '';
				if (!empty($streamDetails->getVCodec())) {
					$vCodec = postEditVCodec($streamDetails->getVCodec());
					$perf   = decodingPerf($vCodec, !empty($bits) && $bits >= 10);
					echo '<font color="'.CODEC_COLORS[$perf].'">'.$vCodec.'</font>'.$VAL_DELIM;
					if (!empty($streamDetails->getHdrType())) {
						echo '<font color="'.CODEC_COLORS[$perf].'">'.postEditHdrType($streamDetails->getHdrType()).'</font>';
						if ($streamDetails->getHdrToneMapParam() != null) {
							echo '<span class="tonemap" title="'.$streamDetails->getHdrToneMapMethod().': '.$streamDetails->getHdrToneMapParam().'"> (t)</span>';
						}
						echo $VAL_DELIM;
					}
					if (!empty($bits)) {
						echo '<br/>';
					}
				}
				if (!empty($bits)) {
					echo $bits.' bit'.$VAL_DELIM;
				}
				echo '</span>';
			}
			echo '</div>';
		}

		$countryMap = !empty($streamDetails->getACodec()) || !empty($streamDetails->getSubtitle()) ? getCountryMap() : null;
		if (!empty($streamDetails->getAChannels())) {
			$codecs = '';
			$atmosFound      = false;
			$eventuallyAtmos = false;
			if (!empty($atmosx)) {
				$atmosx = explode(',', $atmosx);
				if (count($atmosx) < count($streamDetails->getACodec())) {
					$atmosx = null;
				}
			}

			for ($i = 0; $i < count($streamDetails->getACodec()); $i++) {
				$eventuallyAtmos |= atmosFlagPossibleToSet($streamDetails->getACodec()[$i]);
				$atmos = $atmosx = fetchAudioFormat($idFile, $path, $filename, $atmosx, $dbh, $eventuallyAtmos);
				$atmosFound |= !empty($atmos) && !empty($atmos[$i]) && $atmos[$i] == 1;
				$codecs .= postEditACodec($streamDetails->getACodec()[$i], !empty($atmos) && !empty($atmos[$i])).
					(isset($streamDetails->getAChannels()[$i]) ? ' '.postEditChannels($streamDetails->getAChannels()[$i]) : '').
					getLanguage($countryMap, $streamDetails->getALang(), $i).
					($i < count($streamDetails->getACodec())-1 ? (($i+1 % 3 == 0) ? '<br />' : $VAL_DELIM) : '');
			}

			$atmosBtn_1 = $atmosBtn_2 = '';
			if ($isAdmin && $eventuallyAtmos) {
				$atmosBtn_1 = '<a tabindex="-1" class="fancy_msgbox" style="font-size:11px;" href="./dbEdit.php?act=toggleAtmos&idFile='.$idFile.'&val='.($atmosFound ? 0 : 1).'">';
				$atmosBtn_2 = '</a>';
			}

			echo '<div style="overflow-x:hidden;"><span><i><b>Audio</b></i> <font color="silver">[</font> '.count($streamDetails->getACodec()).' <font color="silver">]</font><b>:</b> </span><span class="flalright">'.$atmosBtn_1.$codecs.$atmosBtn_2.'</span></div>';
		}

		if (!empty($streamDetails->getSubtitle())) {
			$cCount = 0;
			$codecs = '';
			foreach ($streamDetails->getSubtitle() as $i => $sub) {
				$codec = getLanguage($countryMap, $streamDetails->getSubtitle(), $i, false);
				if (!empty($codec)) {
					$codecs .= ($cCount > 0 && !empty($sub) ? (($cCount % 6 == 0) ? '<br />' : $VAL_DELIM) : '') . $codec;
					$cCount++;
				}
			}

			echo '<div style="overflow-x:hidden; padding-top: 8px;"><span title="Count is not unique"><i><b>Sub</b></i> <font color="silver">[</font> '.$streamDetails->getSubtitleCount().' <font color="silver">]</font><b>:</b> </span><span class="flalright">'.$codecs.'</span></div>';
		}
	} // !isDemo

	if (!$isDemo) {
		if ($isAdmin) {
			echo '<div class="padtop15"><hr style="position:width:335px; margin:0; border-bottom-width:0; border-color:#BBCCDD;" /></div>';
			echo '<div class="padtop15"><span><i><b>Source:</b></i></span>';
			echo '<span class="flalright">';
			echo '<a class="fancy_changesrc" style="font-size:11px;" href="./changeSource.php?idFile='.$idFile.'">'.SOURCE[$src].'</a>';
			echo '</span>';
			echo '</div>';
		}

		echo '<div class="padtop15"><span><i><b>Size:</b></i></span><span class="flalright">'.$streamDetails->getFsize().'</span></div>';
	}
	if ($isAdmin) {
		echo '<div style="overflow-x:hidden;"><i><b>File:</b></i><br />';
		$filename = '<span onclick="selSpanText(this);">'.encodeString($filename).'</span>';
		echo encodeString($path).$filename;
		echo '</div>';

		echo '<div class="padtop15" style="overflow-x:hidden;"><i><b>idEpisode</b>/<b>idFile:</b></i><span class="flalright">'.$idEpisode.' <b>|</b> '.$idFile.'</span></div>';
	}
	echo '</div>';
	echo '</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';

//- FUNCTIONS -//
function getThumbImg(int $idFile, $idEpisode, $path, string $filename, string $coverP, $existArtTable, $dbh = null) {
	$thumbImg = null;
	$thumbsUp = isset($GLOBALS['TVSHOW_THUMBS']) ? $GLOBALS['TVSHOW_THUMBS'] : false;
	if ($thumbsUp) {
		$sessionImg = null;
		$ENCODE = isset($GLOBALS['ENCODE_IMAGES_TVSHOW']) ? $GLOBALS['ENCODE_IMAGES_TVSHOW'] : true;
		$fromSrc = isset($GLOBALS['TVSHOW_THUMBS_FROM_SRC']) ? $GLOBALS['TVSHOW_THUMBS_FROM_SRC'] : false;
		if ($fromSrc) {
			// READ EMBER GENERATED THUMBS FROM SOURCE
			$DIRMAP_IMG = isset($GLOBALS['DIRMAP_IMG']) ? $GLOBALS['DIRMAP_IMG'] : null;

			$sessionImg = mapSambaDirs($path, $DIRMAP_IMG).substr($filename, 0, strlen($filename) - 3).'tbn';
			$smb = (substr($sessionImg, 0, 6) == 'smb://');

			if (isFile($sessionImg)) {
				$thumbImg = getImageWrap($sessionImg, $idFile, 'file', 0, $ENCODE || $smb ? 'encoded' : null);
			} else {
				unset($sessionImg);
			}
		}

		if (empty($sessionImg)) {
			$sessionImg = getTvShowThumb($coverP.$filename);
			$thumbImg = getImageWrap($sessionImg, $idFile, 'file', 0);

			if ((empty($sessionImg) || !file_exists($sessionImg)) && $existArtTable) {
				$SQL = "SELECT url FROM art WHERE url NOT NULL AND url NOT LIKE '' AND media_type = 'episode' AND type = 'thumb' AND media_id = '".$idEpisode."';";
				$row2 = fetchFromDB($SQL, false, $dbh);
				$url = !empty($row2) && isset($row2['url']) ? $row2['url'] : null;
				if (!empty($url)) {
					$sessionImg = getTvShowThumb($url);
					if (isFile($sessionImg)) {
						$thumbImg = getImageWrap($sessionImg, $idFile, 'file', 0);
					}
				}
			}
		}

		wrapItUp('file', $idFile, $sessionImg);
	}
	return $thumbImg;
}

function getLanguage($countryMap, $languages, $i, $trenner = true) {
	return isValidLang($countryMap, $languages, $i) ? ($trenner ? ' - ' : '').postEditLanguage(strtoupper($languages[$i]), false) : '';
}

function isValidLang($countryMap, $languages, $i) {
	return isset($languages[$i]) && isset($countryMap[strtoupper($languages[$i])]);
}
//- FUNCTIONS -//
?>
