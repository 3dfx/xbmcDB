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
	$admin = isAdmin();
	
	$maself = $_SERVER['PHP_SELF'];
	postNavBar($maself == '/index.php');
	
	echo "\r\n\t".'<div class="tabDiv" onmouseover="closeNavs();">'."\r\n";
	fillTable();
	echo "\t".'</div>'."\r\n";
?>
</body>
	
<?php	
function fillTable() {
	$sessionKey = 'mvidz';
	$mvids = fetchMVids($sessionKey, 1);
	
	echo "\t".'<table id="showsTable" class="film">'."\r\n";
	#echo "\t\t".'<tr class="emptyTR" style="border-bottom:0px;">';
	#echo '<th colspan="'.(6 + $colspan).'" style="padding:0px;"></th>';
	#echo '</tr>'."\r\n";
	echo "\t\t".'<tr class="emptyTR" id="emptyTR" style="border-top:0px;">';
	echo '<th class="showShowInfo1" style="float:right;">#</th>';
	echo '<th class="showShowInfo1" style="margin-left:-10px;">Artist</th>';
	echo '<th class="showShowInfo1" style="margin-left:-10px;">Title</th>';
	echo '<th class="showShowInfo1" style="margin-left:-10px;">Featuring</th>';
	echo '</tr>'."\r\n";
	postMVids($mvids);
	echo "\t".'</table>'."\r\n";
}

function postMVids($mvids) {
	$mvids = $mvids->getMVids();
	$lmLen = strlen(count($mvids));
	$count = 1;
	foreach ($mvids as $mvid) {
		$strCount = $count;
		while(strlen($strCount) < $lmLen) { $strCount = '0'.$strCount; }
		$count++;
		
		echo "\t\t".'<tr class="showShowInfo">';
		echo '<td style="color:silver;" class="sInfoSize">'.$strCount.'</td>';
		echo '<td style="padding-left:3px;">'.$mvid->getArtist().'</td>';
		echo '<td style="padding-left:3px;">'.$mvid->getTitle().'</td>';
		echo '<td style="padding-left:3px;">'.$mvid->getFeat().'</td>';
		//.' [ '.$mvid->getAlbum().' ]';
		echo '</tr>'."\r\n";
	}
}
?>