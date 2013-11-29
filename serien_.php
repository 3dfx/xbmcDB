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
	<script type="text/javascript">
<?php
	$xbmControl = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$bindF      = isset($GLOBALS['BIND_CTRL_F']) ? $GLOBALS['BIND_CTRL_F'] : true;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var isAdmin = '.(isAdmin() ? '1' : '0').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
/*
	echo "\t\t".'var freshloaded = false;'."\r\n";
	if (isset($_SESSION['tvShowParam'])) {
		$sId      = isset($_SESSION['tvShowParam']['idShow'])    ? $_SESSION['tvShowParam']['idShow']    : null;
		$epId     = isset($_SESSION['tvShowParam']['idEpisode']) ? $_SESSION['tvShowParam']['idEpisode'] : null;
		$idSeason = isset($_SESSION['tvShowParam']['idSeason'])  ? $_SESSION['tvShowParam']['idSeason']  : null;
		if (!empty($sId) && !empty($epId) && !empty($idSeason)) {
			echo "\t\t".'$(document).ready(function() {'."\r\n";
			echo "\t\t\t".'if (!freshloaded) {'."\r\n";
			echo "\t\t\t".'var obj = document.getElementById(\'epl_'.$sId.'\');'."\r\n";
			echo "\t\t\t".'loadLatestShowInfo(obj, '.$sId.', '.$epId.', \''.$idSeason.'\', 10);'."\r\n";
			echo "\t\t\t".'freshloaded = true;'."\r\n";
			echo "\t\t\t".'}'."\r\n";
			echo "\t\t".'});'."\r\n";
		}
	}
*/
?>
	</script>
<?php if(isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/serien.js"></script>
<?php } else { ?>
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
	$SQL    = $GLOBALS['SerienSQL'].';';
	$serien = fetchSerien($SQL, null);
	$serien->sortSerien();
	$colspan = isAdmin() ? 1 : 0;
	
	echo "\t".'<table id="showsTable" class="film">'."\r\n";
	echo "\t".'<tbody id="showsBody">'."\r\n";
	echo "\t\t".'<tr class="emptyTR" style="border-bottom:0px;">';
	echo '<th colspan="'.(6 + $colspan).'" style="padding:0px;">';
	echo '<div id="showBanner"><img class="innerCoverImg" src="'.getRandomBanner().'" style="height:54px;" /></div>';
	echo '</th>';
	echo '</tr>'."\r\n";
	echo "\t\t".'<tr class="emptyTR" id="emptyTR" style="border-top:0px;">';
	echo '<th class="showShowInfo1 righto">';
	if (!isAdmin() && !isDemo()) {
		echo '<input type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this); return true;">';
	}
	echo $serien->getSerienCount().'</th>';
	echo '<th><span class="showshowinfo1" style="float:left; margin-left:-10px; cursor:default;"> tv-shows</span>';
	if (!isDemo()) {
		echo '<span class="sInfoSize" style="padding-top:3px; cursor:default;" onclick="toggleAirdates();">'._format_bytes($serien->getSize()).'</span>';
	}
	echo '</th>';
	
	echo '<th colspan="'.(4 + $colspan).'"></th>';
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
			$info     = '<b style="color:'.($missed && isAdmin() ? 'red' : 'silver').';" title="'.(!empty($airDate) ? $airDate : 'running...').'">...</b>';
		}
		
		echo "\t\t".'<tr id="iD'.$idShow.'" class="sTR showShowInfo">';
		
		$check = $isAdmin || $isDemo ? '' : '<input type="checkbox" name="checkSerien[]" id="opt_'.$idShow.'" class="checka" value="'.$idShow.'" onClick="return selected(this, true, true, '.$isAdmin.');" />';
		$strCounter = $counter;
		echo '<td class="showShowInfo1 righto">'.$check.$strCounter.'</td>';
		echo '<td id="epl_'.$idShow.'" onclick="loadShowInfo(this, '.$idShow.'); return true;" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'"><span style="float:left;">'.($running ? '<i>' : '').$serie->getName().$info.'</span>';
		if (!isDemo()) {
			echo '<span class="sInfoSize">'._format_bytes($serie->getSize()).'</span>';
		}
		echo '</td>';
		if (isAdmin()) {
			echo '<td style="padding:0px; margin:0px;">';
			if (!empty($airDate)) {
				$fSize = '';
				$fSize = ($daysLeft > 30 ? ' font-size:9px;' : $fSize);
				$fSize = ($daysLeft > 60 ? ' font-size:8px;' : $fSize);
				#$fSize = ($daysLeft > 90 ? ' font-size:7px;' : $fSize);
				$title = ($daysLeft < 0 ? 'Missed episode' : 'In '.$daysLeft.' days');
				echo '<span class="airdate sInfoSize" style="display:none; vertical-align:middle;'.($missed ? ' color:red;' : '').$fSize.'" title="'.$title.'">'.$airDate.'</span>';
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
			echo '<a class="fancy_addEpisode" href="./addEpisode.php?idShow='.$idShow.'&idTvdb='.$idTvDb.'">';
			echo '<img src="./img/add.png" class="galleryImage" title="add Episode" />';
			echo '</a> ';
			
			if ($serie->isWatched() || (!isWatchedAnyHiddenInMain($idShow) && $serie->isWatchedAny())) {
				$img = './img/check'.($serie->isWatched() ? '' : 'B').'.png';
				echo ' <img src="'.$img.'" class="galleryImage" title="'.($serie->isWatched() ? '' : 'partly ').'watched" />';
			} else {
				echo ' <img src="./img/empty.png" class="galleryImage" />';
			}
		}
		echo '</td>';
		echo '</tr>';
		echo "\r\n";
		
		$counter++;
	}
}

function fetchFilesizes() {
	$filesizes = array();

	try {
		checkFileInfoTable($dbh);

		$sqlFS = "SELECT * FROM fileinfo";
		$resultFS = $dbh->query($sqlFS);
		foreach($resultFS as $rowFS) {
			$idFile = trim($rowFS['idFile']);
			$filesizes[$idFile] = $rowFS['filesize'];
		}
	} catch(PDOException $e) { echo $e->getMessage(); }

	return $filesizes;
}

function getRandomBanner() {
	$img = null;
	if (empty($_SESSION['thumbs']['banner']['random'])) {
		$d = dir("./img/banners/");
		$res = array();
		while (false !== ($entry = $d->read())) { if ($entry == '..' || $entry == '.') {continue;} $res[] = $entry; }
		$d->close();

		$img = './img/banners/'.$res[ rand(1, count($res)-1) ];
		wrapItUp('banner', 'random', $img);
	}
	
	return getImageWrap($img, 'random', 'banner', 0);
}
?>