<?php
	include_once "auth.php";
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "globals.php";
	
	if (!isAdmin()) { exit; }
	$exist = existsOrdersTable();
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
		<title>Order Viewer</title>
<?php
	$self        = $_SERVER['PHP_SELF'];
	$orderName   = isset($_GET['orderName'])   ? trim($_GET['orderName']) : null;
	$deleteOrder = isset($_GET['deleteOrder']) ? trim($_GET['deleteOrder']) : 0;
	
	if (!empty($deleteOrder)) {
		$fname = './orders/'.$orderName;
		if (file_exists($fname)) {
			unlink($fname);
		}
		
		if ($exist) {
			$sql = "DELETE FROM orders WHERE strFilename = '".$fname."';";
			execSQL($sql, false);
		}
		
		unset( $deleteOrder, $orderName );
	}
?>
		<link rel="stylesheet" type="text/css" href="class.css" />
<?php if (empty($orderName)) { ?>
		<script type="text/javascript" src="./template/js/jquery.min.js"></script>
		<script type="text/javascript">
			function cursorBusy() {
				$('body').css('cursor', 'wait');
				$('td').css('cursor', 'wait');
			}
			
			function readOrder(orderName) {
				if (orderName == null || orderName == '') { return; }
				
				cursorBusy();
				window.location.href='<?php echo $self; ?>?orderName=' + orderName;
			}
			
			function dletOrder(orderName) {
				if (orderName == null || orderName == '') { return; }
				
				var frage = unescape("Delete order '" + orderName + "'?");
				var answer = confirm(frage);
				if (!answer) { return; }
				
				cursorBusy();
				window.location.href='<?php echo $self; ?>?deleteOrder=1&orderName=' + orderName;
			}
		</script>
<?php } else { ?>
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
<?php } ?>
	</head>
	<body style='padding:10px 0px; margin:0px;'>
<?php if (empty($orderName)) { ?>

		<table id="orders" class="film" style="width:95%; padding:0px; z-index:1;">
			<tr><th class="righto">#</th><th style="padding-left:10px !important;">User</th><th class="righto">Date</th><th></th></tr>
<?php readOrders(); ?>
		</table>
<?php } ?>
<?php if (!empty($orderName)) {
	$exist = $GLOBALS['exist'];
	echo "\r\n<b>".$orderName."</b>\r\n";
	
	$fname = './orders/'.$orderName;
	$size  = filesize($fname);
	if ($size > 0) {
		$file = fopen($fname, 'r');
		$content = fread($file, $size);
		fclose($file);
		
		echo "<hr style='background:#69C; border:0px; height:2px; width:300%;' />\r\n";
		echo "<pre onclick='selectText(this);'>\r\n";
		if ($size > 0) { echo encodeString($content); }
		echo "</pre>\r\n";
	}
	
	if ($exist) {
		$sql = "UPDATE orders SET fresh = 0 WHERE strFilename = '".$fname."';";
		execSQL($sql, false);
	}
} ?>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function readOrders() {
	$exist = $GLOBALS['exist'];
	$dir = './orders';
	
	$freshs = array();
	if ($exist) {
		$sql = "SELECT strFilename, fresh FROM orders;";
		$res = fetchFromDB($sql);
		foreach($res as $row) {
			$fname = $row['strFilename'];
			$fresh = $row['fresh'];
			$freshs[$fname] = $fresh;
		}
	}
	
	$ver = array();
	$d = dir($dir);
	$counter = 0;
	while (false !== ($entry = $d->read())) {
		$entry = trim($entry);
		if (empty($entry) || $entry == '.' || $entry == '..') { continue; }
		
		$fname = './orders/'.$entry;
		$fresh = isset($freshs[$fname]) ? $freshs[$fname] : 0;
		
		if (!file_exists($fname)) { continue; }
		$size = filesize($fname);
		if ($size > 0) {
			postOrder($counter+1, $entry, $fname, $fresh);
			$counter++;
		}
	}
	$d->close();
	
	if (empty($counter)) {
		echo "\t\t\t".'<tr>';
		echo '<td colspan="4">No orders found!</td>';
		echo '</tr>';
		echo "\r\n";
	}
}

function postOrder($c, $name, $fname, $fresh) {
	$elem  = explode('_', $name);
	$date  = strtotime($elem[0]);
	$date  = date('d.m.Y H:i', filectime($fname));
	$user  = $elem[1];
	$style = $fresh ? ' font-weight:bold;' : '';
	echo "\t\t\t".'<tr'.$style.'>';
	echo '<td onclick="readOrder(\''.$name.'\'); return false;" style="cursor:pointer;'.$style.'" class="righto">'.$c.'</td>';
	echo '<td onclick="readOrder(\''.$name.'\'); return false;" style="cursor:pointer;'.$style.' padding-left:10px !important;">'.$user.'</td>';
	echo '<td onclick="readOrder(\''.$name.'\'); return false;" style="cursor:pointer;'.$style.'" class="righto">'.$date.'</td>';
	echo '<td onclick="dletOrder(\''.$name.'\'); return false;" style="cursor:pointer;'.$style.'" class="righto"><img src="./img/del.gif"/ title="delete"></td>';
	echo '</tr>';
	echo "\r\n";
}
?>
