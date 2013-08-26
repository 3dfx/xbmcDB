<?php
	include_once "auth.php";
	include_once "check.php";

	include_once "template/config.php";
	include_once "template/functions.php";
	include_once "globals.php";
	include_once "_SERIEN.php";
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
	<!--
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	-->
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<script type="text/javascript">
<?php
	$xbmControl = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$bindF      = isset($GLOBALS['BIND_CTRL_F']) ? $GLOBALS['BIND_CTRL_F'] : true;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
?>
	</script>
	<script type="text/javascript" src="./template/js/serien.js"></script>
	<script type="text/javascript" src="./template/js/jquery.marquee.min.js"></script>
<?php if(isAdmin() && $xbmControl) { ?>
	<script type="text/javascript" src="./template/js/xbmcJson.js"></script>
<?php } ?>
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
<div id="showDesc" onmouseover="closeNavs();"></div>
<div id="showInfo" onmouseover="closeNavs();"></div>
<div id="showEpDesc" onmouseover="closeNavs();"></div>

<?php
function fillTable() {
	$SQL =  $GLOBALS['SerienSQL'].';';
	#logc( $SQL );
	
	$serien = fetchSerien($SQL, null);
	$serien->sortSerien();

	echo "\t".'<table id="showsTable" class="film">';
	echo "\r\n";
	echo "\t\t".'<tr id="emptyTR"><td colspan="6" style="padding:0px;">';
	echo '<div id="showsCount">'.$serien->getSerienCount().' tv-shows ('._format_bytes($serien->getSize()).')</div>';
	echo '<div id="showBanner"><img class="innerCoverImg" src="'.getRandomBanner().'" style="height:54px;" /></div>';
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

		echo "\t\t".'<tr class="showShowInfo" onclick="loadShowInfo(this, '.$idShow.'); return true;" desc="./detailSerieDesc.php?id='.$idShow.'" eplist="./detailSerie.php?id='.$idShow.'">';

		$strCounter = $counter;
		if ($counter < 10) { $strCounter = '0'.$counter; }
		echo '<td class="showShowInfo1">'.$strCounter.'</td>';
		echo '<td><span style="float:left;">'.$serie->getName().'</span><span class="sInfoSize">'._format_bytes($serie->getSize()).'</span></td>';
		echo '<td><span class="sInfoRating">'.$serie->getRating().'</span></td>';

		$stCount = $serie->getStaffelCount();
		$strCount = $stCount;
		if ($stCount < 10) { $strCount = '0'.$stCount; }
		echo '<td>'.$strCount.' Season'.($stCount > 1 ? 's' : '').'</td>';
		$allEpsCount = $serie->getAllEpisodeCount();
		echo '<td class="righto">'.$allEpsCount.' Episode'.($allEpsCount > 1 ? 's' : '&nbsp;').'</td>';
		echo '<td class="righto">';
		if ($admin) {
			echo '<a class="fancy_addEpisode" href="./addEpisode.php?idShow='.$idShow.'&idTvdb='.$serie->getIdTvdb().'">';
			echo '<img src="./img/add.png" class="galleryImage" title="add Episode" />';
			echo '</a> ';
			
			if ($serie->isWatched() || $serie->isWatchedAny()) {
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