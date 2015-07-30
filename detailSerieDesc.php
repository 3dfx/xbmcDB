<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "./template/_SERIEN.php";

	$isAdmin = isAdmin();
	$id = getEscGet('id');
	if (empty($id) || $id < 0) { return; }
	
	$serien   = fetchSerien($GLOBALS['SerienSQL'], null);
	$serie    = $serien->getSerie($id);
	$idShow   = $serie->getIdShow();
	$idTvdb   = $serie->getIdTvdb();
	$desc     = $serie->getDesc();
	$genre    = $serie->getGenre();
	$studio   = $serie->getStudio();
	$fsk      = $serie->getFsk();
	$running  = $serie->isRunning();
	#$codecs_ = $serie->getCodecStats();
	$codecs_  = fetchShowCodecs($idShow);
	$banner   = null;
	$imgURL   = 'http://thetvdb.com/banners/graphical/'.$idTvdb.'-g.jpg';
	#$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$tvdbURL = $ANONYMIZER.'http://thetvdb.com/?tab=series&id='.$idTvdb;

	$cols = array(
		0 => '#000000',
		1 => '#00FF00',
		2 => '#009900',
		3 => '#FF0000',
		4 => '#550000',
	);
	
	$data   = getEscGet('data');
	$sum    = 0;
	$codecs = array();
	
	foreach ($codecs_ as $codec => $count) {
		$codecs[postEditVCodec($codec)] = $count + (isset($codecs[postEditVCodec($codec)]) ? $codecs[postEditVCodec($codec)] : 0);
		$sum += $count;
	}
	arsort($codecs);
	
	if (isset($data)) {
		$result = '['."\n\r";
		$idx = 0;
		$end = count($codecs);
		foreach ($codecs as $codec => $count) {
			$prc   = round($count / $sum * 100, 0);
			$codec = postEditVCodec($codec);
			$perf  = decodingPerf($codec);
			
			$result .= "\t".'{ "value":'.$prc.', "color":"'.$cols[$perf].'", "highlight":"'.$cols[$perf].'", "label":"'.$codec.'" }';
			if (++$idx < $end) { $result .= ','; }
			$result .= "\n\r";
		}
		$result .= ']';
		echo $result;
		return;
	}
	
	
	echo '<table class="film tableDesc">';
	echo '<tr class="showDesc">';
	echo '<td class="showDescTD">';
	if (!empty($idTvdb) && $idTvdb != -1) {
		$imgFile = './img/banners/'.$idTvdb.'.jpg';
		if (loadImage($imgURL, $imgFile) == -1) { $imgFile = null; }
		wrapItUp('banner', $idTvdb, $imgFile);
		$banner = empty($imgFile) ? $imgURL : getImageWrap($imgFile, $idTvdb, 'banner', 0);
		echo '<img id="tvBanner" class="openTvdb" src="'.$banner.'" href="'.$tvdbURL.'" />';
	}
	
	echo '<div class="fClose" onclick="closeShow();"><img src="./img/gnome_close.png" /></div>';
	echo '<div class="descDiv">';
	if ($isAdmin) {
		$episodes = getShowInfo($idTvdb);
		$runningTvDb = isset($episodes[0][0]) ? $episodes[0][0] : null;
		
		$pad1 = $runningTvDb == null ? 'class="padbot15" ' : '';
		$pad2 = $runningTvDb != null ? 'class="padbot15" ' : '';
		echo '<div '.$pad1.'style="overflow-x:hidden;"><u><i><b>idShow:</b></i></u><span class="flalright">'.$idShow.'</span></div>';
		if ($runningTvDb != null) {
			$run1 = '<a tabindex="-1" class="fancy_msgbox" href="./dbEdit.php?act=setRunning&val='.($running ? 0 : 1).'&idShow='.$idShow.'">';
			$run2 = '</a>';
			$diff = ($running && $runningTvDb == 'e') || (!$running && $runningTvDb == 'r');
			echo '<div '.$pad2.'style="overflow-x:hidden;">'.
			     '<u><i><b>Status:</b></i></u>'.
			     $run1.
			     '<span class="flalright"'.($diff ? ' style="font-weight:bold; color:red;"' : '').'>'.($runningTvDb == 'r' ? 'running' : 'ended').'</span>'.
			     $run2.
			     '</div>';
		}
	}

	$idx   = 0;
	$title = '';
	$end   = count($codecs);
	foreach ($codecs as $codec => $count) {
		#$perf   = decodingPerf($codec);
		$codec  = postEditVCodec($codec);
		$prc    = round($count / $sum * 100, 0);
		$title .= $codec.': '.$prc.'%'.(++$idx < $end ? "\r\n" : '');
	}
	
	echo '<div class="padbot15" style="overflow-x:hidden;"><u><i><b>Codecs:</b></i></u><span class="flalright" style="padding-top:2.5px;" title="'.$title.'">';
	echo '<canvas id="donutChartPir" width="20" height="20"></canvas>';
	echo '</span></div>';
	
	if (!empty($genre))  { echo '<div style="overflow-x:hidden;"><u><i><b>Genre:</b></i></u><span class="flalright">'.$genre.'</span></div>'; }
	if (!empty($fsk))    { echo '<div style="overflow-x:hidden;"><u><i><b>FSK:</b></i></u><span class="flalright">'.$fsk.'</span></div>'; }
	if (!empty($studio)) { echo '<div style="overflow-x:hidden;"><u><i><b>Studio:</b></i></u><span class="flalright">'.$studio.'</span></div>'; }
	if (!empty($genre.$studio.$fsk)) {
		echo '<div class="padbot15" style="overflow-x:hidden;"></div>';
	}
	
	if (checkAirDate() && $running) {
		$airDate     = null;
		$nextEpisode = null;
		$nextAirDate = $serie->getNextAirDateStr();
		
		if (empty($nextAirDate) || dateMissed($nextAirDate)) {
			//tvdb
			$nextEpisode = getNextEpisode($serie);
			$airDate     = getNextAirDate($nextEpisode);
		} else {
			$airDate = $nextAirDate;
		}
		
		if (!empty($airDate)) {
			$season  = -1;
			$episode = -1;
			
			if (!empty($nextEpisode)) {
				//tvdb
				$season  = $nextEpisode['SeasonNumber'];
				$episode = $nextEpisode['EpisodeNumber'];
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
			$clear1    = $isAdmin ? '<a tabindex="-1" class="fancy_msgbox" href="./dbEdit.php?act=clearAirdate&idShow='.$idShow.'">' : '';
			$clear2    = $isAdmin ? '</a>' : '';
			
			echo 'Next airdate:<br />'.$clear1.$info.$clear2.'<br /><br />';
			
			if (!empty($nextEpisode) && compareDates($nextAirDate, $dbDate)) {
				updateAirdateInDb($id, $season, $episode, $dbDate);
				clearMediaCache();
			}
		}
	}
	
	echo $desc;
	echo '</div>';
	echo '</td>';
	echo '</tr>';
	echo '</table>';
?>