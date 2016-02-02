<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "./template/_SERIEN.php";
?>
<?php /*
<script type="text/javascript" src="./template/js/myfancy.js"></script>
*/ ?>
<?php
	$isAdmin = isAdmin();
	$id = getEscGPost('id');
	if (empty($id) || $id < 0) { return; }
	
	$_SESSION['tvShowParam']['idShow'] = $id;
	
	$SQL    = $GLOBALS['SerienSQL'];
	$serien = fetchSerien($SQL, null);
	$serie  = $serien->getSerie($id);
	
	if ($serie->isWatchedAny()) { echo "<script type=\"text/javascript\">$(document).ready(function() { $('.knob-dyn').knob(); });</script>"; }
	
	echo '<table id="serieTable" class="film">';
	echo "\r\n";
	postSerie($serie);
	echo '</table>';

/*	FUNCTIONS	*/
function postSerien($serien) {
	foreach ($serien->getSerien() as $serie) {
		if (is_object($serie)) { postSerie($serie); }
	}
}

function postSerie($serie) {
	$isAdmin = $GLOBALS['isAdmin'];
	$USECACHE = isset($GLOBALS['USECACHE']) ? $GLOBALS['USECACHE'] : true;
	
	$stCount = $serie->getStaffelCount();
	$allEpsCount = sprintf("%02d", $serie->getAllEpisodeCount());
	
	echo '<tr id="descTR">';
	echo '<th class="descTRd1">'.$stCount.' Season'.($stCount > 1 ? 's' : '').'</th>';
	echo '<th class="righto" style="padding-right:2px;">'.$allEpsCount.'</th>';
	echo '<th class="lefto"> Episode'.($allEpsCount > 1 ? 's' : '').'</th>';
	echo '<th class="righto">'.$serie->getRating().'</th>';
	#echo '<th class="righto">'.(isDemo() ? '' : _format_bytes($serie->getSize())).'</th>';
	echo '<th class="righto vSpan">'.(isDemo() ? '' : ($isAdmin ? '<a class="fancy_msgbox clearFileSize" href="./dbEdit.php?act=clearFileSizes&idFiles='.$serie->getIdFiles().'">' : '')._format_bytes($serie->getSize())).($isAdmin ? '</a>' : '').'</th>';
	echo '<th class="righto" colspan="2">';
	if ($isAdmin) {
		$showEmpty = false;
		if ($serie->isWatched() || $serie->isWatchedAny()) {
			if ($serie->isWatched()) {
				echo ' <img src="./img/check.png" class="galleryImage" title="watched" />';
			} else {
				$percent = $serie->getWatchedPercent();
				$showEmpty = empty($percent);
				if (!empty($percent)) {
					echo '<span title="'.$percent.'% [ '.$serie->getEpCountWatched().'/'.$serie->getAllEpisodeCount().' ]" style="position:relative; right:2px; top:3px; padding-left:4px;">';
					echo '<input type="text" class="knob-dyn" data-width="12" data-height="12" data-fgColor="#6CC829" data-angleOffset="180" data-thickness=".4" data-displayInput="false" data-readOnly="true" value="'.$percent.'" style="display:none;" />';
					echo '</span>';
				}
			}
		} else {
			$showEmpty = true;
		}
		
		if ($showEmpty) { echo ' <img src="./img/empty.png" class="galleryImage" /> '; }
	}
	echo '</th>';
	echo '</tr>';
	echo "\r\n";
	
	foreach ($serie->getStaffeln() as $staffel) {
		if (is_object($staffel)) { postStaffel($staffel); }
	}
}

function postStaffel($staffel) {
	$isAdmin = $GLOBALS['isAdmin'];
	
	$staffel->sortEpisoden();
	$eps = $staffel->getEpisodeCount();
	if ($eps == 0) { return; }
	
	$strAllEps = sprintf("%02d", $eps);
	$sNum      = sprintf("%02d", $staffel->getStaffelNum());
	
	$idShow   = $staffel->getIdShow();
	#$spanId   = 'iD'.$idShow.'.S'.$sNum;
	$linkId   = 'tgl'.$idShow.'.S'.$sNum;
	$seasonId = 'iD'.$idShow.'.S'.$sNum;
	echo '<tr class="seasonTR">';
	echo '<td class="seasonTRd1"><a tabindex="-1" href="#" id="'.$linkId.'" class="plmin hidelink" onclick="toggleEps(\''.$seasonId.'\', '.$eps.', this); $(this).blur(); return false;"></a>Season '.$sNum.'</td>';
	echo '<td class="seasonTRd2 righto">'.$strAllEps.'</td>';
	echo '<td class="lefto">'.' Episode'.($eps > 1 ? 's' : '&nbsp;').'</td>';
	echo '<td class="righto padTD">'.$staffel->getRating().'</td>';
	echo '<td class="righto vSpan">'.(isDemo() ? '' : ($isAdmin ? '<a class="fancy_msgbox clearFileSize" href="./dbEdit.php?clrStream=1&act=clearFileSizes&idFiles='.$staffel->getIdFiles().'">' : '')._format_bytes($staffel->getSize())).($isAdmin ? '</a>' : '').'</td>';
	echo '<td class="righto" colspan="2">';
	if ($isAdmin) {
		$showEmpty = false;
		if ($staffel->isWatched() || $staffel->isWatchedAny()) {
			if ($staffel->isWatched()) {
				echo ' <img src="./img/check.png" class="galleryImage" title="watched" />';
			} else {
				$percent = $staffel->getWatchedPercent();
				$showEmpty = empty($percent);
				if (!empty($percent)) {
					echo '<span title="'.$percent.'% [ '.$staffel->getEpCountWatched().'/'.$staffel->getEpisodeCount().' ]" style="position:relative; right:2px; top:3px; padding-left:4px;">';
					echo '<input type="text" class="knob-dyn" data-width="12" data-height="12" data-fgColor="#6CC829" data-angleOffset="180" data-thickness=".4" data-displayInput="false" data-readOnly="true" value="'.$percent.'" style="display:none;" />';
					echo '</span>';
				}
			}
		} else {
			$showEmpty = true;
		}
		
		if ($showEmpty) {
			echo ' <img src="./img/empty.png" class="galleryImage" /> ';
		}
	}
	echo '</td>';
	echo '</tr>';
	echo "\r\n";
	
	$lastEp      = null;
	$xbmcRunning = xbmcRunning();
	foreach ($staffel->getEpisoden() as $epi) {
		if (!is_object($epi)) { continue; }
		
		$chk    = intval(1);
		$epNum_ = intval($epi->getEpNum());
		if (!empty($lastEp)) {
			$delta = $lastEp->getDelta();
			$chk   = !empty($delta) ? $delta : $lastEp->getEpNum();
			$chk   = intval($chk)+1;
		}
		
		if ($chk != $epNum_) {
			$missCount = $epNum_ - $chk;
			for ($i = $missCount; $i > 0; $i--)
				postMissingRow($seasonId, rand(-9999,-1), $epNum_ - $i, 'missing episode');
		}
		
		$delta = $epi->getDelta();
		$epsde = sprintf("%02d", $epNum_);
		$epDlt = !empty($delta) ? '-'.sprintf("%02d", $delta) : '';
		$epNum = $epsde.$epDlt;
		
		$idEpisode = $epi->getIdEpisode();
		$idTvdb    = $epi->getIdTvdb();
		$epTitle   = trimDoubles($epi->getName());
		$hover     = (strlen($epTitle) >= 27) ? ' title="'.$epTitle.'"' : '';
		
		$path      = $epi->getPath();
		$filename  = $epi->getFilename();
		$fRating   = (floatval($epi->getRating()) > 0 ? $epi->getRating() : '');
		$fSize     = _format_bytes($epi->getSize());
		#$xbmcRunning=1;
		$playItem  = isAdmin() && !empty($path) && !empty($filename) && $xbmcRunning ? '<a tabindex="-1" class="showPlayItem" href="#" onclick="playItem(\''.encodeString($path.$filename).'\'); return false;">'.$fRating.'</a>' : '<span class="showPlayItem">'.$fRating.'</span>';
		$clearSize = isAdmin() && !empty($path) && !empty($filename) ? '<a tabindex="-1" class="fancy_msgbox clearFileSize" href="./dbEdit.php?clrStream=1&act=clearFileSize&idFile='.$epi->getIdFile().'">'.(isDemo() ? '' : $fSize).'</a>' : '<span class="clearFileSize">'.(isDemo() ? '' : $fSize).'</span>';
		
		echo '<tr class="epTR '.$seasonId.'" id="'.$idEpisode.'" _href="./detailEpisode.php?id='.$idEpisode.'&idSeason='.$seasonId.'" style="display:none;" onclick="loadEpDetails(this, '.$idEpisode.');">';
		echo '<td class="epTRd1'.(empty($epDlt) ? '' : ' epTRd2').'" colspan="3"'.$hover.'><span class="vSpan">'.$epNum.'  </span><span class="searchField">'.$epTitle.'</span></td>';
		echo '<td class="righto padTD">'.$playItem.'</td>';
		echo '<td class="righto padTD">'.$clearSize.'</td>';
		echo '<td class="righto">';
		if ($isAdmin) {
			echo '<a tabindex="-1" class="fancy_addEpisode" href="./addEpisode.php?update=1&idShow='.$idShow.'&idTvdb='.$idTvdb.'&idEpisode='.$idEpisode.'">';
			echo '<img src="./img/add.png" class="galleryImage" title="edit Episode" />';
			echo '</a>';
		}
		echo '</td>';
		echo '<td class="righto">';
		if ($isAdmin) {
			$watched = $epi->isWatched();
			echo '<a tabindex="-1" class="fancy_msgbox" href="./dbEdit.php?act='.($watched ? 'setUnseen' : 'setSeen').'&idFile='.$epi->getIdFile().'">';
			echo '<img src="./img/check'.($watched ? '' : 'R').'.png" class="galleryImage" title="'.($watched ? 'watched' : 'set watched').'" /> ';
			echo '</a>';
		}
		echo '</td>';
		echo '</tr>';
		echo "\r\n";
		
		$lastEp = $epi;
	}
}

function postMissingRow($seasonId, $idEpisode, $epNum, $epTitle) {
	$epNum = sprintf("%02d", $epNum);
	echo '<tr class="epTR '.$seasonId.'" id="'.$idEpisode.'" _href="./detailEpisode.php?id='.$idEpisode.'&idSeason='.$seasonId.'" style="display:none;">';
	echo '<td class="epTRd1" colspan="3"><span class="vSpan" style="color:#FF0000;">'.$epNum.'  </span><span class="searchField" style="color:#FF0000; font-weight:bold;">'.$epTitle.'</span></td>';
	echo '<td class="righto padTD"></td>';
	echo '<td class="righto padTD"></td>';
	echo '<td class="righto"></td>';
	echo '<td class="righto"></td>';
	echo '</tr>';
	echo "\r\n";
}

?>
