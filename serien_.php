<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/config.php";
include_once "./template/functions.php";
include_once "./template/Series/_SERIEN.php";
include_once "globals.php";

	$isAdmin = isAdmin();
	$isDemo  = isDemo();
	$maself  = getEscServer('PHP_SELF');
	$isMain  = (substr($maself, -9) == 'index.php');

	$orderz = findUserOrder();
	$orderz = isset($orderz[0]) ? $orderz[0] : null;
	$oItems = !$isAdmin && !empty($orderz) ? count($orderz) : 0;

	$dbh    = getPDO();
	$SQL    = $GLOBALS['SerienSQL'].';';
	checkFileInfoTable($dbh);
	$serien = fetchSerien($SQL, null, $dbh);
	$serien->sortSerien();
	fetchNextEpisodesFromDB($dbh);
	existsOrderzTable($dbh);
	$gallerymode        = isset($_SESSION['gallerymode'])       ? $_SESSION['gallerymode']       : 0;
	$TVSHOW_GAL_ENABLED = isset($GLOBALS['TVSHOW_GAL_ENABLED']) ? $GLOBALS['TVSHOW_GAL_ENABLED'] : false;

	if (getEscGet('jsVars')) {
		if (isset($_SESSION['param_tvShowJsVars']))
			echo unserialize($_SESSION['param_tvShowJsVars']);
		return;
	}

	//if (getEscGet('data')) {
	if (isAjax()) {
		if ($TVSHOW_GAL_ENABLED && $gallerymode && $isAdmin) {
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
	echo "\t\t".'var bindF          = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var newMovies      = '.(checkLastHighest() ? 'true' : 'false').";\r\n";
	echo "\t\t".'var isAdmin        = '.($isAdmin ? '1' : '0').";\r\n";
	echo "\t\t".'var xbmcRunning    = '.($isAdmin && xbmcRunning() ? '1' : '0').";\r\n";
?>
		$(document).ready(function() {
			$('#myNavbar').load( './navbar.php?maself=<?php echo ($isMain ? 1 : 0); ?>', function() { if (isAdmin) { initNavbarFancies(); } } );
			$('#showsDiv').load( './serien_.php', function() {
				$('.knob-dyn').knob();
				initShowFancies();
<?php
				if ($oItems > 0) {
?>
				selected(null, true, true, false);
<?php
				}
?>

				if (isAdmin) {
					$.ajax({
					url:    'serien_.php?jsVars=1',
					type:   'GET',
					async:   true,
					success: function(json) {
						if (json == null || json == '') { return; }
						var missed = JSON.parse(json);
						if (missed.missedEps   > 0) { $('#missEps').css("color", "red"); }
						if (missed.awaitingEps > 0) { $('#missEps').prop('title', missed.awaitingEpsStr); }
						$('#missEps').html(missed.missedEpsStr);
					}
					});
				}
			});
		});
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	postNavBar();

#main
	echo "\t".'<div class="tabDiv" onmouseover="closeNavs();">'."\r\n";
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
	$runCount = $isAdmin ? fetchRunCount($dbh) : null;
	$colspan1 = $isDemo  ? 0 : 1;
	$colspan2 = $isAdmin ? 1 : 0;

	echo "\t".'<table id="showsTable" class="film">'."\r\n";
	echo "\t".'<tbody id="showsBody">'."\r\n";

	echo "\t\t".'<tr class="emptyTR" style="border-bottom:0px;">';
	echo '<th colspan="'.(6 + $colspan1).'" style="padding:0px;">';
	echo '<div id="showBanner"><img class="tvRandomBanner" src="'.getRandomBanner().'" style="height:54px;" /></div>';
	echo '</th>';
	echo '</tr>'."\r\n";

	echo "\t\t".'<tr class="emptyTR" id="emptyTR" style="border-top:0px; border-bottom:3px double #6699cc;">';
	if (!$isAdmin && !$isDemo) {
		echo '<th class="checka checkaCheck righto">';
		echo '<input type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this); return true;">';
		echo '</th>';
	}
	echo '<th class="righto">';
	echo '<span class="showshowinfo1" style="cursor:default;"'.(!empty($runCount) ? ' title="'.$runCount.' running"' : '').'>&nbsp;'.$serien->getSerienCount().'</span>';
	echo '</th>';
	echo '<th>';
	echo '<span class="showshowinfo1" style="float:left; margin-left:-10px; cursor:default;"'.(!empty($runCount) ? ' title="'.$runCount.' running"' : '').'> tv-shows</span>';
	if (!$isDemo) {
		echo '<span class="sInfoSize" style="padding-top:3px; cursor:default;">'._format_bytes($serien->getSize()).'</span>';
	}
	echo '</th>';

	echo '<th colspan="'.(4 + $colspan2).'" style="cursor:default;"><span id="missEps" class="sInfoSize" onclick="toggleAirdates();" style="padding-top:1px; float:left;"></span></th>';
	echo '</tr>'."\r\n";

	$res = postSerien($serien);
	echo "\t".'</tbody>'."\r\n";
	echo "\t".'</table>'."\r\n";

	$json = "";
	if ($isAdmin) {
		$missed   = $res[0];
		$awaiting = $res[1];
		$miss = $missed   == 0 ? 'next airdates' : pluralize('missed episode', $missed, 's', "%02d");
		$wait = $awaiting == 0 ? '' : $awaiting.' episodes airing in the next 7 days';
		$json .= "{";
		$json .= '"missedEpsStr":"'.$miss.'",';
		$json .= '"missedEps":'.$missed.',';
		$json .= '"awaitingEpsStr":"'.$wait.'",';
		$json .= '"awaitingEps":'.$awaiting;
		$json .= "}";
	}
	$_SESSION['param_tvShowJsVars'] = serialize($json);
}

function postSerien($serien) {
	$runningItalic = isset($GLOBALS['TVSHOW_RUNNING_ITALIC']) ? $GLOBALS['TVSHOW_RUNNING_ITALIC'] : false;
	$counter = 1;
	$missed  = array(0, 0);
	foreach ($serien->getSerien() as $serie) {
		# || !$serie->isRunning()
		if (!is_object($serie)) { continue; }
		$res = postSerie($serie, $counter++, $runningItalic);
		$missed[0] += $res[0];
		$missed[1] += $res[1];
	}
	return $missed;
}

function postSerie($serie, $counter, $runningItalic = false) {
	$isAdmin  = isAdmin();
	$isDemo   = isDemo();
	$info     = '';
	$fCol     = '';
	$airDate  = null;
	$daysLeft = -1;
	$missed   = 0;
	$awaiting = 0;
	$idShow   = $serie->getIdShow();
	$idTvDb   = $serie->getIdTvdb();
	$running  = $serie->isRunning();
	$spanId   = 'iDS'.$idShow;
	$higlight = '';

	$checkAirDate = isset($GLOBALS['CHECK_NEXT_AIRDATE']) ? $GLOBALS['CHECK_NEXT_AIRDATE'] : false;
	if ($checkAirDate && $running) {
		$airDate  = $serie->getNextAirDateStr();
		$daysLeft = daysLeft(addRlsDiffToDate($airDate));
		$fCol     = getDateColor($airDate, $daysLeft);
		$airDate  = toEuropeanDateFormat(addRlsDiffToDate($airDate));
		$info     = '<b style="'.$fCol.'" title="'.(!empty($airDate) ? $airDate : 'running...').'">...</b>';
	}

	$chkBoxTD = '';
	$sizeSpan = '';
	if (!$isDemo) {
		if (!$isAdmin) {
			$orderz = $GLOBALS['orderz'];
			$oItems = $GLOBALS['oItems'];
			$checked  = isset($orderz[$idShow]);
			$higlight = $checked ? ' highLighTR' : '';
			$chkBoxTD  = '<td class="checka checkaCheck righto'.$higlight.'">';
			//$chkBoxTD  = '<td class="checka checkaCheck righto'.$higlight.'">';
			$chkBoxTD .= '<input type="checkbox" name="checkSerien[]" id="opt_'.$idShow.'" class="checka" value="'.$idShow.'"'.($checked ? ' checked="checked" selected="selected"' : '').' onClick="return selected(this, true, true, '.$isAdmin.');" style="top:1px;" />';
			$chkBoxTD .= '</td>';
		}

		$sizeSpan = '<span class="sInfoSize">'._format_bytes($serie->getSize()).'</span>';
	}
	$run1 = $isAdmin ? '<a tabindex="-1" class="fancy_msgbox" href="./dbEdit.php?act=setRunning&val='.($running ? 0 : 1).'&idShow='.$idShow.'">' : '';
	$run2 = $isAdmin ? '</a>' : '';
	$strCounter = $counter;

	echo "\t\t".'<tr id="iD'.$idShow.'" class="sTR showShowInfo">';
	echo $chkBoxTD;
	echo '<td class="showShowInfo1 righto'.$higlight.'">'.$run1.$strCounter.$run2.'</td>';
	echo '<td class="'.$higlight.'" id="epl_'.$idShow.'" onclick="loadShowInfo(this, '.$idShow.'); return true;" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'">';
	echo '<span class="showName airdHidden">'.($running && $runningItalic ? '<i>' : '').$serie->getName().$info.($running && $runningItalic ? '</i>' : '').'</span>'.$sizeSpan.'</td>';

	if ($isAdmin) {
		echo '<td class="noPM'.$higlight.'">';
		if (!empty($airDate)) {
			$ANONYMIZER = $GLOBALS['ANONYMIZER'];
			$EP_SEARCH  = isset($GLOBALS['EP_SEARCH']) ? $GLOBALS['EP_SEARCH'] : null;
			$epSearch   = null;
			$enNum      = null;
			if (!empty($EP_SEARCH)) {
				$name     = fixNameForSearch($serie->getName());
				$nextEp   = fetchNextEpisodeFromDB($idShow);
				$enNum    = !empty($nextEp) ? getFormattedSE($nextEp['s'], $nextEp['e']) : null;
				$epSearch = !empty($enNum) && $daysLeft <= 1 ? $ANONYMIZER.$EP_SEARCH.$name.' '.$enNum : null;
			}

			$title = $daysLeft  > 0 ?
				($daysLeft == 1 ? 'Tomorrow' : 'In '.$daysLeft.' days') :
				($daysLeft  < 0 ? 'Missed episode' : 'Today');
			$missed   = $daysLeft <= 0 ? 1 : 0;
			$awaiting = $daysLeft <= 7 ? 1 : 0;
			$eSrch1 = !empty($epSearch) ? '<a tabindex="-1" class="fancy_iframe4" href="'.$epSearch.'">' : '';
			$eSrch2 = !empty($epSearch) ? '</a>' : '';
			echo $eSrch1.'<span class="airdate sInfoSize defAirdate" style="'.$fCol.getDateFontsize($daysLeft).(!empty($epSearch) ? ' cursor:pointer;' : '').'" title="'.$title.(!empty($enNum) ? ': '.$enNum : '').'">'.$airDate.'</span>'.$eSrch2;
		}
		echo '</td>';
	}

	echo '<td class="showRating'.$higlight.'"><span class="hideMobile sInfoRating">'.$serie->getRating().'</span></td>';

	$stCount  = $serie->getStaffelCount();
	echo '<td class="showSeasons'.$higlight.'"><span class="hideMobile">'.pluralize('Season', $stCount, 's', "%02d").'</span></td>';
	$allEpsCount = $serie->getAllEpisodeCount();
	$missCount = $serie->getMissingCount();
	$missTitle = $isAdmin && $missCount != 0 ? ' title="'.pluralize('episode', $missCount).' missing"' : '';
	$mCountCol = $isAdmin && $missCount != 0 ? ' style="color:#FF0000;"' : '';
	echo '<td class="righto'.$higlight.' showEpisodes"><span class="hideMobile"'.$mCountCol.$missTitle.'>'.$allEpsCount.'</span><span class="hideMobile"> Episode'.($allEpsCount > 1 ? 's' : '&nbsp;').'</span></td>';
	echo '<td class="righto'.$higlight.' addEp"><span class="hideMobile">';
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
					echo '<span title="'.$percent.'% [ '.$serie->getEpCountWatched().'/'.$serie->getAllEpisodeCount().' ]" class="perWatched">';
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
	return array($missed, $awaiting);
}

function fetchRunCount($dbh = null) {
	$runCount = isset($_SESSION['param_runCount']) ? $_SESSION['param_runCount'] : null;
	if (empty($runCount)) {
		$runCount = fetchFromDB("SELECT COUNT(*) AS count FROM tvshowrunning;", false, $dbh);
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

		if (count($res) == 0) { return ''; }
		$img = './img/banners/'.$res[ rand(1, count($res)-1) ];
		if (!isFile($img)) { return ''; }
		wrapItUp('banner', 'random', $img);
	}

	return getImageWrap($img, 'random', 'banner', 0);
}
?>
