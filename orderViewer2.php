<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";
	
	if (!isAdmin()) { exit; }
	$tableExists = existsOrderzTable();
	if (!$tableExists) { exit; }
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
		<title>Order Viewer</title>
<?php
	$self        = $_SERVER['PHP_SELF'];
	$idOrder     = getEscGet('idOrder');
	$deleteOrder = getEscGet('deleteOrder');
	$unreadOrder = getEscGet('unreadOrder');
	$srcDrive    = getEscGet('srcDrive');
	$selDrive    = getEscGet('selDrive');
	
	if (!empty($idOrder)) {
		if (!empty($unreadOrder)) {
			execSQL("UPDATE orderz SET fresh = 1 WHERE idOrder = ".$idOrder.";", false);
			unset( $idOrder, $unreadOrder );
			
		} else if (!empty($deleteOrder)) {
			execSQL("DELETE FROM orderz WHERE idOrder = ".$idOrder.";", false);
			unset( $idOrder, $deleteOrder );
		}
	}
	
	$entries = array();
	$res = querySQL("SELECT * FROM orderz ORDER BY idOrder DESC;");
	$counter = 0;
	foreach($res as $row) {
		$entries[$counter]['idOrder']   = $row['idOrder'];
		$entries[$counter]['dateAdded'] = $row['dateAdded'];
		$entries[$counter]['user']      = $row['user'];
		$entries[$counter]['fresh']     = $row['fresh'];
		$entries[$counter]['counter']   = $counter;
		$counter++;
	}

	$counter = 0;
	$count   = count($entries);
	foreach($entries as $entry)
		$entries[$counter++]['counter'] = $count--;
?>
		<link rel="stylesheet" type="text/css" href="class.css" />
<?php if (empty($idOrder)) { ?>
		<script type="text/javascript" src="./template/js/jquery.min.js"></script>
		<script type="text/javascript">
			function cursorBusy() {
				$('body').css('cursor', 'wait');
				$('td').css('cursor', 'wait');
			}
			
			function readOrder(idOrder) {
				if (idOrder == null || idOrder == -1) { return; }
				
				cursorBusy();
				window.location.href='<?php echo $self; ?>?idOrder=' + idOrder + '&srcDrive=' + $('#srcDrive').val() + '&selDrive=' + $('#selDrive').val();
			}
			
			function unReadOrder(idOrder) {
				if (idOrder == null || idOrder == -1) { return; }
				
				cursorBusy();
				window.location.href='<?php echo $self; ?>?unreadOrder=1&idOrder=' + idOrder;
			}
			
			function dletOrder(idOrder) {
				if (idOrder == null || idOrder == -1) { return; }
				
				var frage = unescape("Delete order: '" + idOrder + "'?");
				var answer = confirm(frage);
				if (!answer) { return; }
				
				cursorBusy();
				window.location.href='<?php echo $self; ?>?deleteOrder=1&idOrder=' + idOrder;
			}
		</script>
<?php } else { ?>
<?php /*
		<script type="text/javascript">
			function selectText(obj) {
				if (document.selection) {
					var range = document.body.createTextRange();
					range.moveToElementText(obj);
					range.select();
				} else if (window.getSelection) {
					var range = document.createRange();
					range.selectNode(obj);
					window.getSelection().addRange(range);
				}
			}
		</script>
*/ ?>
<?php } /* empty($idOrder) */ ?>
	</head>
	<body style='padding:10px 0px; margin:0px;'>
<?php if (empty($idOrder)) { ?>

		<table id="orders" class="film" style="width:95%; padding:0px; z-index:1;">
<?php 	if (!empty($counter)) { ?>
			<tr><th class="righto" colspan="5">
				<select id='srcDrive' style='width:40px;'><?php printLetters(true); ?></select>
				<select id='selDrive' style='width:40px;'><?php printLetters(false); ?></select>
			</th></tr>
<?php 	} ?>
			<tr><th class="righto">#</th><th style="padding-left:10px !important;">User</th><th class="righto">Date</th><th></th><th></th></tr>
<?php 	readOrders($entries); ?>
		</table>
<?php } else {
	$content = generateOnDemandCopyScript($idOrder);
	execSQL("UPDATE orderz SET fresh = 0 WHERE idOrder = ".$idOrder.";", false);
	
	echo "\r\n<b>Order: ".$idOrder."</b>\r\n";
	echo "<hr style='background:#69C; border:0px; height:2px; width:300%;' />\r\n";
	#echo "<pre onclick='selectText(this);'>\r\n";
	echo "<pre>\r\n";
	echo encodeString($content);
	echo "</pre>\r\n";
} /* !empty($idOrder) */ ?>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function readOrders($entries) {
	$counter = count($entries);
	if (empty($counter)) {
		echo "\t\t\t".'<tr>';
		echo '<td colspan="5">No orders found!</td>';
		echo '</tr>';
		echo "\r\n";
		return;
	}
	
	foreach($entries as $entry) {
		postOrder($entry);
	}
}

function postOrder($entry) {
	$idOrder = $entry['idOrder'];
	$date    = $entry['dateAdded'];
	$user    = $entry['user'];
	$fresh   = $entry['fresh'];
	$c       = $entry['counter'];
	if ($c <= 0)
		return;
	$c = sprintf("%02d", $c);
	
	$date  = date('d.m.Y H:i', $date);
	$style = $fresh ? ' font-weight:bold;' : '';
	echo "\t\t\t".'<tr'.$style.'>';
	echo '<td onclick="readOrder('.$idOrder.'); return false;" style="cursor:pointer;'.$style.'" class="righto">'.$c.'</td>';
	echo '<td onclick="readOrder('.$idOrder.'); return false;" style="cursor:pointer;'.$style.' padding-left:10px !important;">'.$user.'</td>';
	echo '<td onclick="readOrder('.$idOrder.'); return false;" style="cursor:pointer;'.$style.'" class="righto">'.$date.'</td>';
	echo '<td onclick="unReadOrder('.$idOrder.'); return false;" style="cursor:pointer;'.$style.' max-width:35px;" class="righto"><img src="./img/apply.png" style="height:16px; width:16px;" title="set unread"></td>';
	echo '<td onclick="dletOrder('.$idOrder.'); return false;" style="cursor:pointer;'.$style.' max-width:35px;" class="righto"><img src="./img/del.png" style="height:16px; width:16px;" title="delete"></td>';
	echo '</tr>';
	echo "\r\n";
}

function printLetters($src) {
	$letter = '';
	if ($src) { $letter = isset($GLOBALS['COPYASSCRIPT_COPY_FROM_LETTER']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM_LETTER'] : ''; }
	else {      $letter = isset($GLOBALS['COPYASSCRIPT_COPY_TO_LETTER'])   ? $GLOBALS['COPYASSCRIPT_COPY_TO_LETTER']   : ''; }
	for ($d = 65; $d <= 90; $d++) {
		$d_ = chr($d);
		echo "<option value='".$d_."'".($letter == $d_ ? " selected='selected'" : "").">".$d_.":</option>";
	}
	echo "\r\n";
}
?>
