<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/config.php";
include_once "./template/functions.php";
include_once "./template/_SERIEN.php";
include_once "globals.php";

	$isAdmin = isAdmin();
	$isDemo  = isDemo();
	$maself  = getEscServer('PHP_SELF');
	$isMain  = (substr($maself, -9) == 'index.php');

	$dbh    = getPDO();
	$SQL    = $GLOBALS['SerienSQL'].';';
	$serien = fetchSerien($SQL, null, $dbh);
	#$serien->setUnsorted();
	$serien->sortSerien();
	#$serien->sortSerienRatingAsc();
	#$serien->sortSerienRatingDesc();
	fetchNextEpisodesFromDB($dbh);
	#existsOrdersTable($dbh);
	existsOrderzTable($dbh);
	$gallerymode = isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0;

	if (getEscGet('data')) {
		if (false && $gallerymode && $isAdmin) {
			return fillTableGal($serien, $dbh);
		} else {
			return fillTable($serien, $dbh);
		}
	}
?>

<head>
<?php include("head.php"); ?>
	<script type="text/javascript">
<?php
	$bindF = isset($GLOBALS['BIND_CTRL_F']) ? $GLOBALS['BIND_CTRL_F'] : true;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var isAdmin = '.($isAdmin ? '1' : '0').";\r\n";
	echo "\t\t".'var xbmcRunning = '.($isAdmin && xbmcRunning() ? '1' : '0').";\r\n";
	echo "\t\t".'var newMovies = '.(checkLastHighest() ? 'true' : 'false').";\r\n";
?>

		$(document).ready(function() {
			$('#myNavbar').load( './navbar.php?maself=<?php echo ($isMain ? 1 : 0); ?>', function() { if (isAdmin) { initNavbarFancies(); } } );
			$('#showsDiv').load( './serien_.php?data=1', function() { $('.knob-dyn').knob(); initShowFancies(); } );
		});
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	postNavBar();

#main
	echo "\t".'<div class="tabDiv" onmouseover="closeNavs();">'."\r\n";
	/*
	if (false && $gallerymode && $isAdmin) {
		fillTableGal($serien, $dbh);
	} else {
		fillTable($serien, $dbh);
	}
	*/
	echo "\t\t".'<div id="showsDiv" onmouseover="closeNavs();"></div>'."\r\n";
	echo "\t".'</div>'."\r\n";
?>
	<div id="showDesc" onmouseover="closeNavs();"></div>
	<div id="showInfo" onmouseover="closeNavs();"></div>
	<div id="showEpDesc" onmouseover="closeNavs();"></div>
<?php
	if (!$isAdmin && !$isDemo) {
		echo "\r\n";
		echo "\t".'<div id="movieList" class="lefto" style="padding-left:15px; z-order=1; height:60px; display:none;">'."\r\n";
		echo "\t\t".'<div>';
		if ($COPYASSCRIPT_ENABLED && !$isAdmin) {
			echo "<input type='checkbox' id='copyAsScript' onClick='doRequest(".$isAdmin."); return true;' style='float:left;'/><label for='copyAsScript' style='float:left; margin-top:-5px;'>as copy script</label>";
			echo "<br/>";
		}
		echo "<input type='button' name='orderBtn' id='orderBtn' onclick='saveSelection(".$isAdmin."); return true;' value='save'/>";
		echo '</div>';

		echo "\r\n\t\t".'<div id="result" class="selectedfield"></div>'."\r\n";
		echo "\t".'</div>'."\r\n";
	}
?>
</body>
<?php
function fillTableGal($serien, $dbh) {
	echo '<table id="showsTable" class="gallery" style="border:0px; width:900px;" cellpadding="9">';
	postSerienGal($serien);
	echo '</table>'."\r\n";
}

function postSerienGal($serien) {
	echo '<tr class="emptyTR" id="emptyTR" style="border-top:0px;">';
	echo '</tr>'."\r\n";

	$counter = 1;
	echo '<tr>'."\r\n";
	foreach ($serien->getSerien() as $serie) {
		if (!is_object($serie)) { continue; }
		postSerieGal($serie, $counter++);
	}
	echo '</tr>'."\r\n";
}

function postSerieGal($serie, $counter) {
	echo '<td>';
	echo '<span>';
	postSerieImg($serie);
	echo '</span>';
	#echo '<span>';
	#echo $serie->getDesc();
	#echo '</span>';
	echo '</td>';

	if ($counter % 3 == 0) {
		echo "\n</tr>\r\n<tr>";
	}
	echo "\r\n";
}

function postSerieImg($serie) {
	$idTvdb  = $serie->getIdTvdb();
	$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$tvdbURL = $ANONYMIZER.'http://thetvdb.com/?tab=series&id='.$idTvdb;
	if (!empty($idTvdb) && $idTvdb != -1) {
		$imgFile = './img/banners/'.$idTvdb.'.jpg';
		if (loadImage($imgURL, $imgFile) == -1) { $imgFile = null; }
		wrapItUp('banner', $idTvdb, $imgFile);
		$banner = empty($imgFile) ? $imgURL : getImageWrap($imgFile, $idTvdb, 'banner', 0);
		echo '<img id="tvBanner" class="innerCoverImg" src="'.$banner.'" href="'.$tvdbURL.'" />';
	}
}

function fillTable($serien, $dbh) {
	$isAdmin  = isAdmin();
	$isDemo   = isDemo();
	$runCount = $isAdmin ? fetchRuncount($dbh) : null;
	$colspan1 = $isDemo  ? 0 : 1;
	$colspan2 = $isAdmin ? 1 : 0;

	echo "\t".'<table id="showsTable" class="film">'."\r\n";
	echo "\t".'<tbody id="showsBody">'."\r\n";

	echo "\t\t".'<tr class="emptyTR" style="border-bottom:0px;">';
	echo '<th colspan="'.(6 + $colspan1).'" style="padding:0px;">';
	echo '<div id="showBanner"><img class="tvRandomBanner" src="'.getRandomBanner().'" style="height:54px;" /></div>';
	echo '</th>';
	echo '</tr>'."\r\n";

	echo "\t\t".'<tr class="emptyTR" id="emptyTR" style="border-top:0px;">';
	if (!$isAdmin && !$isDemo) {
		echo '<th class="checkaCheck righto">';
		echo '<input type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this); return true;">';
		echo '</th>';
	}
	echo '<th class="righto">';
	echo '<span class="showshowinfo1" style="cursor:default;"'.(!empty($runCount) ? ' title="'.$runCount.' running"' : '').'>';
	echo $serien->getSerienCount();
	echo '</span>';
	echo '</th>';
	echo '<th>';
	echo '<span class="showshowinfo1" style="float:left; margin-left:-10px; cursor:default;"'.(!empty($runCount) ? ' title="'.$runCount.' running"' : '').'> tv-shows</span>';
	if (!$isDemo) {
		echo '<span class="sInfoSize" style="padding-top:3px; cursor:default;" onclick="toggleAirdates();">'._format_bytes($serien->getSize()).'</span>';
	}
	echo '</th>';
	echo '<th colspan="'.(4 + $colspan2).'"></th>';
	echo '</tr>'."\r\n";

	postSerien($serien);
	echo "\t".'</tbody>'."\r\n";
	echo "\t".'</table>'."\r\n";
}

function postSerien($serien) {
	$counter = 1;
	foreach ($serien->getSerien() as $serie) {
		if (!is_object($serie)) { continue; }
		postSerie($serie, $counter++);
	}
}

function postSerie($serie, $counter) {
	$isAdmin  = isAdmin();
	$isDemo   = isDemo();
	$info     = '';
	$fCol     = '';
	$airDate  = null;
	$daysLeft = -1;
	$idShow   = $serie->getIdShow();
	$idTvDb   = $serie->getIdTvdb();
	$running  = $serie->isRunning();
	$spanId   = 'iDS'.$idShow;

	$checkAirDate = isset($GLOBALS['CHECK_NEXT_AIRDATE']) ? $GLOBALS['CHECK_NEXT_AIRDATE'] : false;
	if ($checkAirDate && $running) {
		$airDate  = $serie->getNextAirDateStr();
		$daysLeft = daysLeft(addRlsDiffToDate($airDate));
		$fCol     = getDateColor($airDate, $daysLeft);
		$airDate  = toEuropeanDateFormat(addRlsDiffToDate($airDate));
		$info     = '<b style="'.$fCol.'" title="'.(!empty($airDate) ? $airDate : 'running...').'">...</b>';
	}

	echo "\t\t".'<tr id="iD'.$idShow.'" class="sTR showShowInfo">';
	if (!$isAdmin && !$isDemo) {
		echo '<td class="checkaCheck righto">';
		echo '<input type="checkbox" name="checkSerien[]" id="opt_'.$idShow.'" class="checka" value="'.$idShow.'" onClick="return selected(this, true, true, '.$isAdmin.');" />';
		echo '</td>';
	}
	$run1 = $isAdmin ? '<a tabindex="-1" class="fancy_msgbox" href="./dbEdit.php?act=setRunning&val='.($running ? 0 : 1).'&idShow='.$idShow.'">' : '';
	$run2 = $isAdmin ? '</a>' : '';
	$strCounter = $counter;
	echo '<td class="showShowInfo1 righto">'.$run1.$strCounter.$run2.'</td>';
	echo '<td id="epl_'.$idShow.'" onclick="loadShowInfo(this, '.$idShow.'); return true;" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'">';
	echo '<span class="showName airdHidden">'.($running ? '<i>' : '').$serie->getName().$info.($running ? '</i>' : '').'</span>';
	if (!$isDemo) {
		echo '<span class="sInfoSize">'._format_bytes($serie->getSize()).'</span>';
	}
	echo '</td>';
	if ($isAdmin) {
		echo '<td style="padding:0px; margin:0px;">';
		if (!empty($airDate)) {
			$ANONYMIZER = $GLOBALS['ANONYMIZER'];
			$EP_SEARCH  = isset($GLOBALS['EP_SEARCH']) ? $GLOBALS['EP_SEARCH'] : null;
			$epSearch   = null;
			if (!empty($EP_SEARCH)) {
				$name     = str_replace("'",  "", $serie->getName());
				if (substr_count($name, ', The') > 0) {
					$name = 'The '.str_replace(", The",  "", $name);
				}
				$nextEp   = $daysLeft <= 1  ? fetchNextEpisodeFromDB($idShow) : null;
				$enNum    = !empty($nextEp) ? getFormattedSE($nextEp['s'], $nextEp['e']) : null;
				$epSearch = !empty($enNum)  ? $ANONYMIZER.$EP_SEARCH.$name.'+'.$enNum : null;
			}
			
			$title = $daysLeft > 0 ?
				($daysLeft == 1 ? 'Tomorrow' : 'In '.$daysLeft.' days') :
				($daysLeft  < 0 ? 'Missed episode' : 'Today');
			$eSrch1 = !empty($epSearch) ? '<a tabindex="-1" class="fancy_iframe4" href="'.$epSearch.'">' : '';
			$eSrch2 = !empty($epSearch) ? '</a>' : '';
			echo $eSrch1.'<span class="airdate sInfoSize" style="display:none; vertical-align:middle; cursor:default; '.$fCol.getDateFontsize($daysLeft).'" title="'.$title.'">'.$airDate.'</span>'.$eSrch2;
		}
		echo '</td>';
	}

	echo '<td class="showRating"><span class="hideMobile sInfoRating">'.$serie->getRating().'</span></td>';

	$stCount  = $serie->getStaffelCount();
	$strCount = ($stCount < 10 ? '0' : '').$stCount;
	echo '<td class="showSeasons"><span class="hideMobile">'.$strCount.' Season'.($stCount > 1 ? 's' : '').'</span></td>';
	$allEpsCount = $serie->getAllEpisodeCount();
	echo '<td class="righto showEpisodes"><span class="hideMobile">'.$allEpsCount.' Episode'.($allEpsCount > 1 ? 's' : '&nbsp;').'</span></td>';
	echo '<td class="righto addEp"><span class="hideMobile">';
	if ($isAdmin) {
		echo '<a tabindex="-1" class="fancy_addEpisode" href="./addEpisode.php?idShow='.$idShow.'&idTvdb='.$idTvDb.'">';
		echo '<img src="./img/add.png" class="galleryImage" title="add Episode" />';
		echo '</a> ';

		$showEmpty = false;
		if ($serie->isWatched() || (!isWatchedAnyHiddenInMain($idShow) && $serie->isWatchedAny())) {
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

		if ($showEmpty) {
			echo ' <img src="./img/empty.png" class="galleryImage" />';
		}
	}
	echo '</span></td>';
	echo '</tr>';
	echo "\r\n";
}

function getDateColor($airDate, $daysLeft) {
	$color = 'silver';
	if (isAdmin()) {
		$missed = !empty($airDate) && dateMissed($airDate);
		if ($missed) { $color = 'red'; }
		else if ($daysLeft > -1) {
			if ($daysLeft <= 3) { $color = 'lightblue'; }
			if ($daysLeft <= 2) { $color = 'purple';    }
			if ($daysLeft <= 1) { $color = 'brown';     }
		}
	}
	return 'color:'.$color.';';
}

function getDateFontsize($daysLeft) {
	$fSize = ' font-size:8pt;';
	$fSize = ($daysLeft >= 1 ? ' font-size:7pt;' : $fSize);
	$fSize = ($daysLeft >= 2 ? ' font-size:6pt;' : $fSize);
	$fSize = ($daysLeft > 30 ? ' font-size:5pt;' : $fSize);
	$fSize = ($daysLeft > 60 ? ' font-size:4pt;' : $fSize);
	$fSize = ($daysLeft > 90 ? ' font-size:3pt;' : $fSize);
	return $fSize;
}

function fetchRuncount($dbh = null) {
	$runCount = isset($_SESSION['param_runCount']) ? $_SESSION['param_runCount'] : null;
	if (empty($runCount)) {
		$runCount = fetchFromDB_($dbh, "SELECT COUNT(*) AS count FROM tvshowrunning;");
		$runCount = !empty($runCount) && is_array($runCount) ? $runCount['count'] : '-';
		$_SESSION['param_runCount'] = $runCount;
	}
	return $runCount;
}

function getRandomBanner() {
	$img = null;
	if (empty($_SESSION['thumbs']['banner']['random'])) {
		$d = dir("./img/banners/");
		$res = array();
		while (false !== ($entry = $d->read())) { if ($entry == '..' || $entry == '.') {continue;} $res[] = $entry; }
		$d->close();

		if (count($res) == 0) { return; }
		$img = './img/banners/'.$res[ rand(1, count($res)-1) ];
		if (!file_exists($img)) { return; }
		wrapItUp('banner', 'random', $img);
	}

	return getImageWrap($img, 'random', 'banner', 0);
}
?>