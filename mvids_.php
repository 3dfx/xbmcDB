<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/config.php";
include_once "./template/functions.php";
include_once "./template/_MVID.php";
include_once "globals.php";

$maself = getEscServer('PHP_SELF');
$isMain = (substr($maself, -9) == 'index.php');
?>

<head>
<?php include("head.php"); ?>
	<script type="text/javascript">
		$(document).ready(function() {
			$('.dropdown-toggle').dropdown();
			$('#myNavbar').load( './navbar.php?maself=<?php echo ($isMain ? 1 : 0); ?>', function() { initNavbarFancies(); } );
		});

		function openNav(objId) {
			closeNavs();
			$(objId).addClass('open');
		}

		function closeNavs() {
			$('#dropAdmin').removeClass('open');
		}
		
		function checkForCheck() { return true; }
		
		function markActive(obj) {
			$('.showShowInfo').children('TD').removeClass('selectedShow');
			$(obj).children('TD').addClass('selectedShow');
		}
		
<?php
	$bindF = false;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
	echo "\t\t".'var newMovies = '.(checkLastHighest() ? 'true' : 'false').";\r\n";
?>
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
#main
	postNavBar();
	
	echo "\r\n\t".'<div class="tabDiv" onmouseover="closeNavs();">'."\r\n";
	fillTable();
	echo "\t".'</div>'."\r\n";
?>
</body>
	
<?php	
function fillTable() {
	$sort  = isset($_SESSION['mvSort']) ? $_SESSION['mvSort'] : null;
	$mvids = fetchMVids('', $sort);
	
	echo "\t".'<table id="showsTable" class="film">'."\r\n";
	echo "\t\t".'<tr class="emptyTR" id="emptyTR" style="border-top:0px;">';
	echo '<th class="th1" style="float:right;"><a href="?show=mvids&mvSort=4"'.($sort == 4 ? ' style="color:red;"' : '').'>#</a></th>';
	echo '<th class="th1" style="margin-left:-10px;"><a href="?show=mvids&mvSort=1"'.($sort == 1 ? ' style="color:red;"' : '').'>Artist</a></th>';
	echo '<th class="th1" style="margin-left:-10px;"><a href="?show=mvids&mvSort=2"'.($sort == 2 ? ' style="color:red;"' : '').'>Title</a></th>';
	echo '<th class="th1" style="margin-left:-10px;">Featuring</th>';
	echo '</tr>'."\r\n";
	postMVids($mvids);
	echo "\t".'</table>'."\r\n";
}

function postMVids($mvids) {
	$mvids = $mvids->getMVids();
	$lmLen = strlen(count($mvids));
	$count = 1;
	
	$isAdmin     = isAdmin();
	$xbmcRunning = xbmcRunning();
	
	foreach ($mvids as $mvid) {
		#$strCount = $count;
		#while(strlen($strCount) < $lmLen) { $strCount = '0'.$strCount; }
		#$count++;
		$frmt = '%0'.$lmLen.'d';
		$strCount = sprintf($frmt, $count++);
		
		$filename = $isAdmin && $xbmcRunning ? prepPlayFilename($mvid->getFilename()) : null;
		$playItem = empty($filename) ? null : ' onclick="playItem(\''.$filename.'\'); return false;"';
		
		echo "\t\t".'<tr class="showShowInfo" onclick="markActive(this);">';
		echo '<td style="color:silver;'.(!empty($playItem) ? ' cursor:pointer;' : '').'" class="sInfoSize"'.$playItem.'>'.$strCount.'</td>';
		echo '<td style="padding-left:3px;">'.$mvid->getArtist().'</td>';
		echo '<td style="padding-left:3px;">'.$mvid->getTitle().'</td>';
		echo '<td style="padding-left:3px;">'.$mvid->getFeat().'</td>';
		//.' [ '.$mvid->getAlbum().' ]';
		echo '</tr>'."\r\n";
	}
}
?>