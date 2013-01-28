<?php
	include_once "auth.php";
	include_once "check.php";

	include_once "template/config.php";
	include_once "template/functions.php";
	include_once "globals.php";
	include_once "_SERIEN.php";
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
<head>
	<title>XBMC Database</title>
	<link rel="shortcut icon" href="favicon.ico" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
	<!--
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	-->
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<script type="text/javascript" src="./template/js/serien.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./class.css" />
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
#main
	$admin = isAdmin();

	$maself = $_SERVER['PHP_SELF'];
	postNavBar($maself == '/index.php');

	echo "\t".'<div class="tabDiv" onmouseover="closeNavs();">';
	echo "\r\n";
	fillTable();
	echo '</div>';
	echo "\r\n";
?>
<div id="showDesc" style="border:0px; position:absolute; top:0px; left:0px; width:0px; height:0px;"></div>
<div id="showInfo" style="border:0px; position:absolute; top:0px; left:0px; width:0px; height:0px;"></div>
<div id="showEpDesc" style="border:0px; position:absolute; top:0px; left:0px; width:0px; height:0px;"></div>
</body>
</html>

<?php
function fillTable() {
	$SQL =  $GLOBALS['SerienSQL'].';';
	#logc( $SQL );
	
	$serien = fetchSerien($SQL, null);
	$serien->sortSerien();

	echo "\t".'<table id="showsTable" class="film" style="z-index:10; border:1px solid #69C; margin-bottom:15px;">';
	echo "\r\n";
	echo "\t\t".'<tr id="emptyTR" style="height:20px; border:1px solid #69C;"><td colspan="6" style="padding:0px;">';
	echo '<div style="position:relative; right:110px; top:10px; text-align:right; float:right;">'.$serien->getSerienCount().' Serien ('._format_bytes($serien->getSize()).')</div>';
	echo '<div style="position:relative; top:0px; left:0px; border:0px; padding:10px; padding-top:25px; text-align:center;"><img class="innerCoverImg" src="'.getRandomBanner().'" style="height:54px;" /></div>';
	echo '</td></tr>';
	echo "\r\n";
	postSerien($serien);
	echo "\t".'</table>';
	echo "\r\n";
}

function postSerien($serien) {
	$admin = $GLOBALS['admin'];
	$counter = 1;
	foreach ($serien->getSerien() as $serie) {
		if (!is_object($serie)) {
			continue;
		}

		$idShow = $serie->getIdShow();
		$spanId = 'iDS'.$idShow;

		echo "\t\t".'<tr class="showShowInfo" onclick="loadShowInfo(this, '.$idShow.'); return true;" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'" style="cursor:default; width:1px; white-space:nowrap; height:20px;">';

		$strCounter = $counter;
		if ($counter < 10) { $strCounter = '0'.$counter; }
		echo '<td style="color:silver; padding:2px 4px;">'.$strCounter.'</td>';
		echo '<td><span style="float:left;">'.$serie->getName().'</span><span style="color:silver; float:right; padding-left:10px;">'._format_bytes($serie->getSize()).'</span></td>';
		echo '<td><span style="float:right; padding-left:10px;">'.$serie->getRating().'</span></td>';

		$stCount = $serie->getStaffelCount();
		$strCount = $stCount;
		if ($stCount < 10) { $strCount = '0'.$stCount; }
		echo '<td>'.$strCount.' Season'.($stCount > 1 ? 's' : '').'</td>';
		$allEpsCount = $serie->getAllEpisodeCount();
		echo '<td class="righto">'.$allEpsCount.' Episode'.($allEpsCount > 1 ? 's' : '&nbsp;').'</td>';
		echo '<td class="righto">';
		if ($admin) {
			echo '<a class="fancy_addEpisode" href="./addEpisode.php?idShow='.$idShow.'&idTvdb='.$serie->getIdTvdb().'">';
			echo '<img src="./img/add.png" class="galleryImage" title="add Episode" style="width:9px !important; height:9px !important; z-index:50;" />';
			echo '</a> ';
			
			if ($serie->isWatched() || $serie->isWatchedAny()) {
				$img = './img/check'.($serie->isWatched() ? '' : 'B').'.png';
				echo ' <img src="'.$img.'" class="galleryImage" title="'.($serie->isWatched() ? '' : 'partly ').'watched" style="width:9px !important; height:9px !important;" /> ';
			} else {
				echo ' <img src="./img/empty.png" class="galleryImage" style="width:9px !important; height:9px !important;" /> ';
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
	$d = dir("./img/banners/");
	$res = array();
	while (false !== ($entry = $d->read())) { if ($entry == '..' || $entry == '.') {continue;} $res[] = $entry; }
	$d->close();
	
	$img = './img/banners/'.$res[ rand(1, count($res)-1) ];
	return $img;
}
?>