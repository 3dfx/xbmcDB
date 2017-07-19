<?php
include_once "auth.php";
include_once "check.php";
include_once "./template/config.php";
include_once "./template/functions.php";
include_once "./template/_SERIEN.php";
include_once "globals.php";
if (!isAdmin()) { exit; }
?>
<head>
<?php include("head.php"); ?>
	<script type="text/javascript">
<?php
	echo "\t\t".'var bindF = false;'.";\r\n";
	echo "\t\t".'var isAdmin = '.(isAdmin() ? '1' : '0').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
	echo "\t\t".'var newMovies = false;'.";\r\n";
?>
		$(document).ready(function() {
			initShowFancies();
		});
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
#main
	#$maself = $_SERVER['PHP_SELF'];
	#postNavBar($maself == '/index.php');
	$dbh    = getPDO();
	$SQL    = $GLOBALS['SerienSQL'].';';
	$serien = fetchSerien($SQL, null, $dbh);
	$serien->sortSerienAirdateAsc();
	#echo "\t".'<div class="tabDiv" onmouseover="closeNavs();">'."\r\n";
	fillTable($serien, $dbh);
	#echo "\t".'</div>'."\r\n";
?>
</body>
<?php
function fillTable($serien, $dbh) {
	echo "\t".'<table id="showsTable" class="film">'."\r\n";
	echo "\t".'<tbody id="showsBody">'."\r\n";
	postSerien($serien);
	echo "\t".'</tbody>'."\r\n";
	echo "\t".'</table>'."\r\n";
}
function postSerien($serien) {
	$counter = 1;
	foreach ($serien->getSerien() as $serie) {
		if (!is_object($serie)) { continue; }
		if (!$serie->isRunning()) { continue; }
		$counter = postSerie($serie, $counter);
	}
}
function postSerie($serie, $counter) {
	$airDate  = null;
	$daysLeft = -1;
	$missed   = false;
	$fCol     = '';
	$idShow   = $serie->getIdShow();
	$idTvDb   = $serie->getIdTvdb();
	
	$checkAirDate = isset($GLOBALS['CHECK_NEXT_AIRDATE']) ? $GLOBALS['CHECK_NEXT_AIRDATE'] : false;
	if ($checkAirDate) {
		$airDate  = $serie->getNextAirDateStr();
		$daysLeft = daysLeft(addRlsDiffToDate($airDate));
		$fCol     = getDateColor($airDate, $daysLeft);
		$airDate  = toEuropeanDateFormat(addRlsDiffToDate($airDate));
	}
	
	if (empty($airDate)) { return $counter; }
	$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$EP_SEARCH  = isset($GLOBALS['EP_SEARCH']) ? $GLOBALS['EP_SEARCH'] : null;
	$epSearch   = null;
	if (!empty($EP_SEARCH)) {
		$name     = str_replace ("'",  "", $serie->getName());
		$nextEp   = fetchNextEpisodeFromDB($idShow);
		$enNum    = !empty($nextEp) ? getFormattedSE($nextEp['s'], $nextEp['e']) : null;
		$epSearch = !empty($enNum)  ? $ANONYMIZER.$EP_SEARCH.$name.'+'.$enNum : null;
	}
	
	$title = $daysLeft  > 0 ?
		($daysLeft == 1 ? 'Tomorrow' : 'In '.$daysLeft.' days') :
		($daysLeft  < 0 ? 'Missed episode' : 'Today');
	$eSrch1 = !empty($epSearch) ? '<a tabindex="-1" class="fancy_iframe4" href="'.$epSearch.'">' : '';
	$eSrch2 = !empty($epSearch) ? '</a>' : '';
	
	echo "\t\t".'<tr id="iD'.$idShow.'" class="sTR showShowInfo">';
	echo '<td class="righto">'.$counter.'</td>';
	echo '<td id="epl_'.$idShow.'"><span class="showName">'.$serie->getName().'</span></td>';
	echo '<td style="padding-right:15px; margin:0px;">'.$eSrch1.'<span class="airdate sInfoSize" style="vertical-align:middle; cursor:default; '.$fCol.getDateFontsize($daysLeft).'" title="'.$title.'">'.$airDate.'</span>'.$eSrch2.'</td>';
	echo '</tr>'."\r\n";
	return ++$counter;
}
?>