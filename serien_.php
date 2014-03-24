<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/config.php";
include_once "./template/functions.php";
include_once "./template/_SERIEN.php";
include_once "globals.php";
?>

<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
	<title>XBMC Database</title>
	<link rel="shortcut icon" href="favicon.ico" />
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/docs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./class.css" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<script type="text/javascript" src="./template/js/jquery.marquee.min.js"></script>
	<script type="text/javascript" src="./template/js/jquery.knob.js"></script>
	<script type="text/javascript">
<?php
	$xbmControl = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$bindF      = isset($GLOBALS['BIND_CTRL_F']) ? $GLOBALS['BIND_CTRL_F'] : true;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var isAdmin = '.(isAdmin() ? '1' : '0').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
	echo "\t\t".'var newMovies = '.(checkLastHighest() ? 'true' : 'false').";\r\n";
?>

		$(document).ready(function() { $('.knob-dyn').knob(); });
	</script>
<?php if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/general.js"></script>
	<script type="text/javascript" src="./template/js/serien.js"></script>
<?php } else { ?>
	<script type="text/javascript" src="./template/js/general.min.js"></script>
	<script type="text/javascript" src="./template/js/serien.min.js"></script>
<?php } ?>
<?php if(isAdmin() && $xbmControl) { ?>
	<script type="text/javascript" src="./template/js/xbmcJson.js"></script>
<?php } ?>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
#main
	$isAdmin = isAdmin();
	$isDemo  = isDemo();
	
	$maself = $_SERVER['PHP_SELF'];
	postNavBar($maself == '/index.php');
	
	echo "\r\n\t".'<div class="tabDiv" onmouseover="closeNavs();">'."\r\n";
	fillTable();
	echo "\t".'</div>'."\r\n";
?>
	<div id="showDesc" onmouseover="closeNavs();"></div>
	<div id="showInfo" onmouseover="closeNavs();"></div>
	<div id="showEpDesc" onmouseover="closeNavs();"></div>
	
<?php
	if (!$isAdmin && !$isDemo) {
		echo "\r\n";
		echo '<div id="movieList" class="lefto" style="padding-left:15px; z-order=1; height:60px; display:none;">'."\r\n";
		echo "\t".'<div>';
		if ($COPYASSCRIPT_ENABLED && !$isAdmin) {
			echo "\t<input type='checkbox' id='copyAsScript' onClick='doRequest(".$isAdmin."); return true;' style='float:left;'/><label for='copyAsScript' style='float:left; margin-top:-5px;'>as copy script</label>";
			echo "<br/>";
		}
		echo "<input type='button' name='orderBtn' id='orderBtn' onclick='saveSelection(".$isAdmin."); return true;' value='save'/>";
		echo '</div>';

		echo "\r\n\t".'<div id="result" class="selectedfield"></div>'."\r\n";
		echo '</div>'."\r\n";
	}
?>
</body>
<?php
function fillTable() {
	$isAdmin  = isAdmin();
	$isDemo   = isDemo();
	$dbh      = getPDO();
	$runCount = $isAdmin ? fetchRuncount($dbh) : null;
	$SQL      = $GLOBALS['SerienSQL'].';';
	$serien   = fetchSerien($SQL, null, $dbh);
	$serien->sortSerien();
	fetchNextEpisodesFromDB($dbh);
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
	$isAdmin = isAdmin();
	$isDemo  = isDemo();
	$counter = 1;
	foreach ($serien->getSerien() as $serie) {
		if (!is_object($serie)) { continue; }
		
		$info     = '';
		$airDate  = '';
		$daysLeft = '';
		$missed   = false;
		$idShow   = $serie->getIdShow();
		$idTvDb   = $serie->getIdTvdb();
		$running  = $serie->isRunning();
		$spanId   = 'iDS'.$idShow;
		
		$checkAirDate = isset($GLOBALS['CHECK_NEXT_AIRDATE']) ? $GLOBALS['CHECK_NEXT_AIRDATE'] : false;
		if ($checkAirDate && $running) {
			$airDate  = $serie->getNextAirDateStr();
			$daysLeft = daysLeft(addRlsDiffToDate($airDate));
			$missed   = dateMissed($airDate) && !empty($airDate);
			$airDate  = toEuropeanDateFormat(addRlsDiffToDate($airDate));
			$info     = '<b style="color:'.($missed && $isAdmin ? 'red' : 'silver').';" title="'.(!empty($airDate) ? $airDate : 'running...').'">...</b>';
		}
		
		echo "\t\t".'<tr id="iD'.$idShow.'" class="sTR showShowInfo">';
		if (!$isAdmin && !$isDemo) {
			echo '<td class="checkaCheck righto">';
			echo '<input type="checkbox" name="checkSerien[]" id="opt_'.$idShow.'" class="checka" value="'.$idShow.'" onClick="return selected(this, true, true, '.$isAdmin.');" />';
			echo '</td>';
		}
		$run1 = $isAdmin ? '<a tabindex="-1" class="fancy_msgbox" style="cursor:default;" href="./dbEdit.php?act=setRunning&val='.($running ? 0 : 1).'&idShow='.$idShow.'">' : '';
		$run2 = $isAdmin ? '</a>' : '';
		$strCounter = $counter;
		echo '<td class="showShowInfo1 righto">'.$run1.$strCounter.$run2.'</td>';
		echo '<td id="epl_'.$idShow.'" onclick="loadShowInfo(this, '.$idShow.'); return true;" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'">';
		echo '<span class="showName">'.($running ? '<i>' : '').$serie->getName().$info.($running ? '</i>' : '').'</span>';
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
					$name      = str_replace ("'",  "", $serie->getName());
					$nextEp    = fetchNextEpisodeFromDB($idShow);
					$enNum     = !empty($nextEp) ? getFormattedSE($nextEp['s'], $nextEp['e']) : null;
					$epSearch  = !empty($enNum)  ? $ANONYMIZER.$EP_SEARCH.$name.'+'.$enNum : null;
				}
				
				$fSize = ' font-size:11px;';
				$fSize = ($daysLeft > 30 ? ' font-size:9px;' : $fSize);
				$fSize = ($daysLeft > 60 ? ' font-size:8px;' : $fSize);
				#$fSize = ($daysLeft > 90 ? ' font-size:7px;' : $fSize);
				$title = ($daysLeft < 0 ? 'Missed episode' : 'In '.$daysLeft.' day'.($daysLeft > 1 ? 's' : ''));
				$eSrch1 = $isAdmin && !empty($epSearch) ? '<a tabindex="-1" class="fancy_iframe4" href="'.$epSearch.'">' : '';
				$eSrch2 = $isAdmin && !empty($epSearch) ? '</a>' : '';
				echo $eSrch1.'<span class="airdate sInfoSize" style="display:none; vertical-align:middle; cursor:default;'.($missed ? ' color:red;' : '').$fSize.'" title="'.$title.'">'.$airDate.'</span>'.$eSrch2;
			}
			echo '</td>';
		}
		
		echo '<td><span class="sInfoRating">'.$serie->getRating().'</span></td>';
		
		$stCount  = $serie->getStaffelCount();
		$strCount = ($stCount < 10 ? '0' : '').$stCount;
		echo '<td>'.$strCount.' Season'.($stCount > 1 ? 's' : '').'</td>';
		$allEpsCount = $serie->getAllEpisodeCount();
		echo '<td class="righto">'.$allEpsCount.' Episode'.($allEpsCount > 1 ? 's' : '&nbsp;').'</td>';
		echo '<td class="righto">';
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
		echo '</td>';
		echo '</tr>';
		echo "\r\n";
		
		$counter++;
	}
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