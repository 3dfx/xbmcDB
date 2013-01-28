<script type="text/javascript" src="./template/js/myfancy.js"></script>
<?php
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "_SERIEN.php";

	$admin = (isset($_SESSION['angemeldet']) && $_SESSION['angemeldet'] == true) ? 1 : 0;

	$id = $_GET['id'];
	if ($id == null || $id == 0) {
		echo 'No id given!';
        die();
	}

	$SQL =  $GLOBALS['SerienSQL'];
	$serien = fetchSerien($SQL, null);

	echo '<table id="serieTable" class="film" style="width:350px; padding:0px; z-index:1;">';
	echo "\r\n";
	$serie = $serien->getSerie($id);
	postSerie($serie);
	echo '</table>';

/*	FUNCTIONS	*/
function postSerien($serien) {
	foreach ($serien->getSerien() as $serie) {
		if (!is_object($serie)) {
			continue;
		}

		postSerie($serie);
	}
}

function postSerie($serie) {
	$admin = $GLOBALS['admin'];
	$USECACHE = isset($GLOBALS['USECACHE']) ? $GLOBALS['USECACHE'] : true;

	$stCount = $serie->getStaffelCount();
	$allEpsCount = $serie->getAllEpisodeCount();
	if ($allEpsCount < 10) { $allEpsCount = '0'.$allEpsCount; }

	echo '<tr id="descTR" style="height:20px; cursor:default;">';
	echo '<th style="padding:0px 0px 0px 25px;">'.$stCount.' Season'.($stCount > 1 ? 's' : '').'</th>';
	echo '<th class="righto" style="padding-right:2px;">'.$allEpsCount.'</th>';
	echo '<th class="lefto"> Episode'.($allEpsCount > 1 ? 's' : '').'</th>';
	echo '<th class="righto">'.$serie->getRating().'</th>';
	echo '<th class="righto">'._format_bytes($serie->getSize()).'</th>';
	echo '<th class="righto" colspan="2">';
	if ($admin) {
		if ($serie->isWatched() || $serie->isWatchedAny()) {
			$img = './img/check'.($serie->isWatched() ? '' : 'B').'.png';
			echo ' <img src="'.$img.'" class="galleryImage" title="'.($serie->isWatched() ? '' : 'partly ').'watched" style="width:9px !important; height:9px !important;" />';
		} else {
			echo ' <img src="./img/empty.png" class="galleryImage" style="width:9px !important; height:9px !important;" /> ';
		}
	}
	echo '</th>';
	echo '</tr>';
	echo "\r\n";

	foreach ($serie->getStaffeln() as $staffel) {
		if (!is_object($staffel)) {
			continue;
		}

		postStaffel($staffel);
	}
}

function postStaffel($staffel) {
	$admin = $GLOBALS['admin'];

	$eps = $staffel->getEpisodeCount();
	if ($eps == 0) { continue; }

	$strAllEps = $eps;
	if ($eps < 10) { $strAllEps = '0'.$eps; }

	$sNum = $staffel->getStaffelNum();
	if ($sNum < 10) { $sNum = '0'.$sNum; }

	$idShow = $staffel->getIdShow();
	$spanId = 'iD'.$idShow.'.S'.$sNum;
	echo '<tr class="seasonTR">';
	echo '<td style="padding:0 0 0 10px;"><A HREF="#" class="plmin hidelink" onclick="toggleEps(\''.$spanId.'\', '.$eps.', this); $(this).blur(); return false;"></A>Season '.$sNum.'</td>';
	echo '<td class="righto" style="padding-right:2px;">'.$strAllEps.'</td>';
	echo '<td class="lefto">'.' Episode'.($eps > 1 ? 's' : '&nbsp;').'</td>';
	echo '<td class="righto" style="padding-left:10px;">'.$staffel->getRating().'</td>';
	echo '<td class="righto" style="color:silver;">'._format_bytes($staffel->getSize()).'</td>';
	echo '<td class="righto" colspan="2">';
	if ($admin) {
		if ($staffel->isWatched() || $staffel->isWatchedAny()) {
			$img = './img/check'.($staffel->isWatched() ? '' : 'B').'.png';
			echo ' <img src="'.$img.'" class="galleryImage" title="'.($staffel->isWatched() ? '' : 'partly ').'watched" style="width:9px !important; height:9px !important;" />';
		} else {
			echo ' <img src="./img/empty.png" class="galleryImage" style="width:9px !important; height:9px !important;" /> ';
		}
	}
	echo '</td>';
	echo '</tr>';
	echo "\r\n";
	
	foreach ($staffel->getEpisoden() as $epi) {
		if (!is_object($epi)) { continue; }

		$epNum = $epi->getEpNum();
		if ($epNum < 10) { $epNum = '0'.$epNum; }
		
		$idEpisode = $epi->getIdEpisode();
		$idTvdb = $epi->getIdTvdb();
		$epTitle = $epi->getName();
		$hover = (strlen($epTitle) >= 27) ? ' title="'.$epTitle.'"' : '';
		
		echo '<tr class="epTR" id="iD'.$idShow.'.S'.$sNum.'.E'.$epNum.'" href="./detailEpisode.php?id='.$idEpisode.'" style="display:none; cursor:default;" onclick="loadEpDetails(this, '.$idEpisode.');">';
		echo '<td colspan="3" style="padding:0px 0px 0px 25px; max-width:180px; overflow:hidden;"'.$hover.'><span style="color:silver;">'.$epNum.'  </span>'.$epTitle.'</td>';
		echo '<td class="righto" style="padding-left:10px;">'.$epi->getRating().'</td>';
		echo '<td class="righto" style="padding-left:10px;"><span style="color:silver;">'._format_bytes($epi->getSize()).'</span></td>';
		echo '<td class="righto">';
		if ($admin) {
			echo '<a class="fancy_addEpisode" href="./addEpisode.php?update=1&idShow='.$idShow.'&idTvdb='.$idTvdb.'&idEpisode='.$idEpisode.'">';
			echo '<img src="./img/add.png" class="galleryImage" title="edit Episode" style="width:9px !important; height:9px !important; z-index:50;" />';
			echo '</a> ';
		}
		echo '</td>';
		echo '<td class="righto">';
		if ($admin) {
			if ($epi->isWatched()) {
				echo '<a class="fancy_movieEdit" href="./dbEdit.php?act=setUnseen&idFile='.$epi->getIdFile().'">';
				echo '<img src="./img/check.png" class="galleryImage" title="watched" style="width:9px !important; height:9px !important;" /> ';
				echo '</a>';

			} else {
				echo '<a class="fancy_movieEdit" href="./dbEdit.php?act=setSeen&idFile='.$epi->getIdFile().'">';
				echo '<img src="./img/checkR.png" class="galleryImage" title="set watched" style="width:9px !important; height:9px !important;" /> ';
				echo '</a>';
			}
		}
		echo '</td>';
		echo '</tr>';
		echo "\r\n";
	}
}

?>
