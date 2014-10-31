<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "./template/_SERIEN.php";

	$isAdmin = isAdmin();
	$id = getEscGet('id');
	if (empty($id) || $id < 0) { return; }
	
	$serien  = fetchSerien($GLOBALS['SerienSQL'], null);
	$serie   = $serien->getSerie($id);
	$idShow  = $serie->getIdShow();
	$idTvdb  = $serie->getIdTvdb();
	$desc    = $serie->getDesc();
	$genre   = $serie->getGenre();
	$studio  = $serie->getStudio();
	$fsk     = $serie->getFsk();
	$running = $serie->isRunning();
	$banner  = null;
	$imgURL  = 'http://thetvdb.com/banners/graphical/'.$idTvdb.'-g.jpg';
	#$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$tvdbURL = $ANONYMIZER.'http://thetvdb.com/?tab=series&id='.$idTvdb;
	
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
		echo '<div class="padbot15" style="overflow-x:hidden;"><u><i><b>idShow:</b></i></u><span class="flalright">'.$idShow.'</span></div>';
	}
	
	if (!empty($genre)) {
		echo '<div class="'.($isAdmin ? '' : 'padtop15').'" style="overflow-x:hidden;"><u><i><b>Genre:</b></i></u><span class="flalright">'.$genre.'</span></div>';
	}
	if (!empty($fsk)) {
		echo '<div class="" style="overflow-x:hidden;"><u><i><b>FSK:</b></i></u><span class="flalright">'.$fsk.'</span></div>';
	}
	if (!empty($studio)) {
		echo '<div class="" style="overflow-x:hidden;"><u><i><b>Studio:</b></i></u><span class="flalright">'.$studio.'</span></div>';
	}
	
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
			$airDate = getNextAirDate($nextEpisode);
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
				$ep      = !empty($st) && is_object($st) ? $st->getLastEpisode() : null;
				$season  = !empty($st) && is_object($st) ? $st->getStaffelNum() : null;
				$episode = !empty($ep) && is_object($ep) ? $ep->getEpNum() : null;
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