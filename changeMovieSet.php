<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";
	
	startSession();
	if (!isAdmin()) { return; }
?>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<?php
	$closeFrame = getEscGPost('closeFrame', 0);
$idMovie    = getEscGPost('idMovie', -1);
	
	$row    = fetchFromDB('SELECT idSet FROM movie WHERE idMovie = '.$idMovie.';');
	$idSet  = $row['idSet'];
	
/*	FUNCTIONS	*/
function postSets($idSet) {
	$result = querySQL('SELECT * FROM sets ORDER BY strSet;');
	foreach($result as $row) {
		postSet($idSet, $row['idSet'], $row['strSet']);
	}
}

function postSet($idSet, $id, $name) {
	echo "\t\t\t".'<option value="'.$id.'"'.($idSet == $id ? ' SELECTED' : '').'>'.$name.'</option>';
	echo "\r\n";
}
?>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<link rel="stylesheet" type="text/css" href="class.css" />
	<script type="text/javascript">
		$(document).ready(function(){
			$('.styled-select').customStyle();
<?php
	if ($closeFrame) {
?>
			parent.parent.$.fancybox.close();
			return false;
<?php
	}
?>
		});

		function cursorBusy() {
			$('body').css('cursor', 'wait');
			$('div').css('cursor', 'wait');
			$('select').css('cursor', 'wait');
			$('button').css('cursor', 'wait');
		}

		function setMovieSet(obj) {
			cursorBusy();
			<?php echo 'var idSet = '.(isset($GLOBALS['idSet']) ? $GLOBALS['idSet'] : '-1').';'."\r\n"; ?>
			<?php echo 'var idMovie = '.(isset($GLOBALS['idMovie']) ? $GLOBALS['idMovie'] : '-1').';'."\r\n"; ?>
			var sel = document.getElementById('setSelect');

			if (idMovie === null || idMovie === -1 || sel === null) {
				return;
			}

			var action = (idSet !== -1 ? 'linkUpdate' : 'linkInsert');
			if (sel.value === -1) {
				action = 'linkDelete';
			}

			window.location.href='./dbEdit.php?act=' + action + '&id=' + sel.value + '&' + 'idMovie=' + idMovie;
		}
	</script>
	</head>
	<body style="width:350px; height:50px; margin:0; padding:15px 10px;">
		<div style="float:left; width:200px; padding:2px 0 0 0;">
			<select id="setSelect" class="styled-select" style="position:absolute; font-size:10px !important; width:195px !important; height:18px !important;" size="1">
				<option value="-1"> </option>
<?php postSets($idSet); ?>
			</select>
		</div>
		<div style="float:right; padding:0 5px; position: relative; right: 165px;">
			<input type="button" value="Ok" class="okButton" onclick="setMovieSet(); return false;">
		</div>
	</body>
</html>
