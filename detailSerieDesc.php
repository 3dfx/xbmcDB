<?php
include_once "check.php";

include_once "globals.php";
include_once "./template/functions.php";
include_once "./template/config.php";
include_once "./template/Series/_SERIEN.php";

//header('x-frame-options: "allow-from https://anon.to/"');

	$isAdmin = isAdmin();
	$id = getEscGet('id');
	if (empty($id) || $id < 0) { return; }

	$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$serien   = fetchSerien($GLOBALS['SerienSQL'], null);
	$serie    = $serien->getSerie($id);
	$idShow   = $serie->getIdShow();
	$idTvdb   = $serie->getIdTvdb();
	$desc     = $serie->getDesc();
	$genre    = $serie->getGenre();
	$studio   = $serie->getStudio();
	$fsk      = $serie->getFsk();
	$running  = $serie->isRunning();
	$codecs_  = fetchShowCodecs($idShow);
	$banner   = null;
	$imgURL   = 'http://thetvdb.com/banners/graphical/'.$idTvdb.'-g.jpg';
	$imgURL2  = 'http://thetvdb.com/banners/graphical/'.$idTvdb.'-g2.jpg';
	$tvdbURL  = $ANONYMIZER.'http://thetvdb.com/?tab=series&id='.$idTvdb;

	$sum    = 0;
	$codecs = array();
	foreach ($codecs_ as $codec => $count) {
		$codecs[postEditVCodec($codec)] = $count + (isset($codecs[postEditVCodec($codec)]) ? $codecs[postEditVCodec($codec)] : 0);
		$sum += $count;
	}
	arsort($codecs);

	$data   = getEscGet('data');
	//if (isset($data)) {
	if (isAjax() && isset($data)) {
		header('Content-Type: application/json');
		echo genJsonData($codecs, $sum);
		return;
	}

	echo '<table class="film tableDesc">';
	echo '<tr class="showDesc">';
	echo '<td class="showDescTD">';
	if (!empty($idTvdb) && $idTvdb != -1) {
		$imgFile = './img/banners/'.$idTvdb.'.jpg';
		$ok1 = loadImage($imgURL,  $imgFile) != -1;
		$ok2 = loadImage($imgURL2, $imgFile) != -1;
		if (!$ok1 && !$ok2)
			$imgFile = null;
		wrapItUp('banner', $idTvdb, $imgFile);
		$banner = empty($imgFile) ? ($ok1 ? $imgURL : $imgURL2) : getImageWrap($imgFile, $idTvdb, 'banner', 0);
		echo '<a target="_blank" href="'.$tvdbURL.'"><img id="tvBanner" src="'.$banner.'" /></a>';
	}

	echo '<div class="fClose" onclick="closeShow();"><img src="./img/close.png" style="cursor:pointer;"/></div>';
	echo '<div class="descDiv">';
	if ($isAdmin) {
		$lastStaffel = $serie->getLastStaffel();
		$episodes = getShowInfo($idTvdb, empty($lastStaffel) ? null : $lastStaffel->getStaffelNum());
		$runningTvDb = isset($episodes[0][0]) ? $episodes[0][0] : '';

		$pad1 = $runningTvDb == null ? 'class="padbot15" ' : '';
		$pad2 = $runningTvDb != null ? 'class="padbot15" ' : '';
		echo '<div '.$pad1.'style="overflow-x:hidden;"><i><b>idShow:</b></i><span class="flalright">'.$idShow.'</span></div>';
		if ($runningTvDb !== null) {
			$run1 = '<a tabindex="-1" class="fancy_msgbox" href="./dbEdit.php?act=setRunning&val='.($running ? 0 : 1).'&idShow='.$idShow.'">';
			$run2 = '</a>';
			$diff = ($running && $runningTvDb == 'e') || (!$running && $runningTvDb == 'r');
			echo '<div '.$pad2.'style="overflow-x:hidden;">'.
			     '<i><b>Status:</b></i>'.
			     $run1.
			     '<span class="flalright"'.($diff ? ' style="font-weight:bold; color:red;"' : '').'>'.($running == 'r' ? 'running' : 'ended').'</span>'.
			     $run2.
			     '</div>';
		}
	}

	$idx   = 0;
	$title = '';
	$end   = count($codecs);
	foreach ($codecs as $codec => $count) {
		$codec  = postEditVCodec($codec);
		$prc    = round($count / $sum * 100);
		$title .= $codec.': '.$prc.'%'.(++$idx < $end ? "\r\n" : '');
	}

	echo '<div class="padbot15" style="overflow-x:hidden;"><i><b>Codecs:</b></i><span class="flalright" style="padding-top:2.5px;" title="'.$title.'">';
	echo '<canvas id="donutChartPie" width="20" height="20"></canvas>';
	echo '</span></div>';

	if (!empty($genre))  { echo '<div style="overflow-x:hidden;"><i><b>Genre:</b></i><span class="flalright">'.$genre.'</span></div>'; }
	if (!empty($fsk))    { echo '<div style="overflow-x:hidden;"><i><b>FSK:</b></i><span class="flalright">'.$fsk.'</span></div>'; }
	if (!empty($studio)) { echo '<div style="overflow-x:hidden;"><i><b>Studio:</b></i><span class="flalright">'.$studio.'</span></div>'; }
	if (!empty($genre.$studio.$fsk)) {
		echo '<div class="padbot15" style="overflow-x:hidden;"></div>';
	}

	$clear1  = $isAdmin ? '<a tabindex="-1" class="fancy_msgbox" style="font-size:11px;" href="./dbEdit.php?act=clearAirdate&idShow='.$idShow.'">' : '';
	$clear2  = $isAdmin ? '</a>' : '';

	$airings = '';
	$dur     = '';
	$epFirst = $serie->getFirstStaffel()->getFirstEpisode();
	$epLast  = empty($lastStaffel) ? null : $lastStaffel->getLastEpisode();
	if ($epFirst != null && $epLast != null) {
		$airings = toEuropeanDateFormat($epFirst->getAirDate());
		$lastAir = toEuropeanDateFormat($epLast->getAirDate());
		if ($airings != $lastAir) { $airings .= ' - '.$lastAir; }
		$dur = getDuration($epFirst, $epLast);
		echo '<div class="padbot15" style="overflow-x:hidden;"><i><b>Aired:</b></i><span class="flalright">'.$clear1.$airings.$dur.$clear2.'</span></div>';
	}

	if ($isAdmin && $running && checkAirDate()) {
		$airDate     = null;
		$nextEpisode = null;
		$lastEpisode = null;
		$nextAirDate = $serie->getNextAirDateStr();

		if (empty($nextAirDate) || dateMissed($nextAirDate)) {
			//tvdb
			$nextEpisode = getNextEpisode($serie);
			$lastEpisode = getLastEpisode($serie);
			$airDate     = getNextAirDate($nextEpisode);
		} else {
			$airDate = $nextAirDate;
		}

		if (!empty($airDate)) {
			$season  = -1;
			$episode = -1;

			if (!empty($nextEpisode)) {
				//tvdb
				$season  = $nextEpisode['airedSeason'];
				$episode = $nextEpisode['airedEpisodeNumber'];
			} else {
				$st      = !empty($serie) && is_object($serie) ? $serie->getLastStaffel() : null;
				$ep      = !empty($st) && is_object($st)       ? $st->getLastEpisode()    : null;
				$season  = !empty($st) && is_object($st)       ? $st->getStaffelNum()     : null;
				$episode = !empty($ep) && is_object($ep)       ? $ep->getEpNum()          : null;
			}

			$dbDate    = $airDate;
			$airDate   = addRlsDiffToDate($airDate);
			$dayOfWeek = dayOfWeek($airDate);
			$daysLeft  = daysLeft($airDate);
			$missed    = dateMissed($airDate);
			$info1     = $daysLeft > 0 ?
					(($daysLeft == 1 ? 'Tomorrow' : 'In '.$daysLeft.' days').' on '.$dayOfWeek) :
					($isAdmin ? ($missed ? 'Missed episode' : 'Today') : '');

			$info2     = ' [ <b style="color:'.($missed && $isAdmin ? 'red' : 'silver').';">'.toEuropeanDateFormat($airDate).'</b> ]';
			$info      = $info1.$info2;

			echo '<i><b>Next airdate:</b></i><br />'.$clear1.$info.$clear2.'<br /><br />';

			if (!empty($nextEpisode) && compareDates($nextAirDate, $dbDate)) {
				updateAirdateInDb($id, $season, $episode, $dbDate, $lastEpisode);
				clearMediaCache();
			}
		}
	}

	echo $desc;
	echo '</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';

function genJsonData($codecs, $sum) {
	$cols = isset($GLOBALS['CODEC_COLORS']) ? $GLOBALS['CODEC_COLORS'] : null;

	$labels  = '"labels": ['."\n\t\t";
	$data    = '"data": ['."\n\t\t\t";
	$bgColor = '"backgroundColor": ['."\n\t\t\t";

	$idx = 0;
	$end = count($codecs);
	foreach ($codecs as $codec => $count) {
		$prc   = round($count / $sum * 100);
		$codec = postEditVCodec($codec);
		$perf  = decodingPerf($codec);
		$color = $cols == null ? '#000000' : $cols[$perf];

		$labels  .= '"'.$codec.'"';
		$data    .= $prc;
		$bgColor .= '"'.$color.'"';

		if (++$idx < $end) {
			$labels  .= ',';
			$data    .= ',';
			$bgColor .= ',';
		}
	}

	$labels  .= "\n\t"."]";
	$data    .= "\n\t\t"."]";
	$bgColor .= "\n\t\t"."]";

	$result = "{"."\n";
	$result .= "\t".$labels.","."\n";
	$result .= "\t".'"datasets": [{'."\n";
	$result .= "\t\t".$data.","."\n";
	$result .= "\t\t".$bgColor."\n";
	$result .= "\t"."}]"."\n";
	$result .= "}";
	$result = str_replace(array("\n","\r","\t", " "),"", $result);
	return $result;
}

function getDuration($epFirst, $epLast) {
	$dtFirst = new DateTime($epFirst->getAirDate());
	$dtLast  = new DateTime($epLast->getAirDate());

	$yrFirst = $dtFirst->format('y');
	$yrLast  = $dtLast->format('y');
	if ($yrFirst == $yrLast) {
		return diffToString(1);
	}

	$diff = $dtFirst->diff($dtLast);
	$res  = $dtFirst->diff($dtLast)->format('%y');
	return diffToString($res+1);
}

function diffToString($val) {
	return ' ('.pluralize('year', $val).')';
}
?>
