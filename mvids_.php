<?php
	include_once "auth.php";
	include_once "check.php";

	include_once "./template/config.php";
	include_once "./template/functions.php";
	include_once "./template/_MVID.php";
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
	<script type="text/javascript">
		$(document).ready(function() {
			$('.dropdown-toggle').dropdown();

			if (bindF) { $(document).keydown(function(event) {
				if(event.ctrlKey && event.keyCode == '70') {
					event.preventDefault();
					openNav('#dropSearch');
					$('#searchDBfor').focus();
				}
			}); }
		});

		function openNav(objId) {
			closeNavs();
			$(objId).addClass('open');
		}

		function closeNavs() {
			$('#dropAdmin').removeClass('open');
			$('#dropSearch').removeClass('open');
			$('#dropLatestEps').removeClass('open');
		}
		
		function checkForCheck() { return true; }
		
		function markActive(obj) {
			$('.showShowInfo').children('TD').removeClass('selectedShow');
			$(obj).children('TD').addClass('selectedShow');
		}
		
<?php
	$xbmControl = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	$bindF      = false;
	echo "\t\t".'var bindF = '.($bindF ? 'true' : 'false').";\r\n";
	echo "\t\t".'var xbmcRunning = '.(isAdmin() && xbmcRunning() ? '1' : '0').";\r\n";
?>
	</script>
	<script type="text/javascript" src="./template/js/jquery.marquee.min.js"></script>
<?php if(isAdmin() && $xbmControl) { ?>
	<script type="text/javascript" src="./template/js/xbmcJson.js"></script>
<?php } ?>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
#main
	$maself = getEscServer('PHP_SELF');
	postNavBar($maself == '/index.php');
	
	echo "\r\n\t".'<div class="tabDiv" onmouseover="closeNavs();">'."\r\n";
	fillTable();
	echo "\t".'</div>'."\r\n";
?>
</body>
	
<?php	
function fillTable() {
	$sort  = $_SESSION['mvSort'];
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
		$strCount = $count;
		while(strlen($strCount) < $lmLen) { $strCount = '0'.$strCount; }
		$count++;
		
		$filename = prepPlayFilename($mvid->getFilename());
		$playItem = $isAdmin && $xbmcRunning && !empty($filename) ? ' onclick="playItem(\''.$filename.'\'); return false;"' : null;
		
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