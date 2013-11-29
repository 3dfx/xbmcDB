<?php
	include_once "auth.php";
	include_once "check.php";

	include_once "./template/functions.php";
	include_once "./template/config.php";
	include_once "globals.php";
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
<?php
	startSession();
	if (!isAdmin()) { return; };
	
	$idMovie    = getEscGPost('idMovie', -1);
	$closeFrame = getEscGPost('closeFrame', 0);;
	
	$dbh = null;
	
	$idSet = -1;
	$dbh = getPDO();
	try {
		$idMovie = $GLOBALS['idMovie'];
		$SQL_SET = 'SELECT idSet FROM movie WHERE idMovie = '.$idMovie.';';

		$result = $dbh->query($SQL_SET);
		$row = $result->fetch();
		$idSet = $row['idSet'];

	} catch(PDOException $e) {
		echo $e->getMessage();
	}

/*	FUNCTIONS	*/
function postSets() {
	$idSet = $GLOBALS['idSet'];
	
	$dbh = getPDO();
	try {
		$SQL_SETS = 'SELECT * FROM sets ORDER BY strSet;';
		$result = $dbh->query($SQL_SETS);
		$sets = array();
		$s = 0;
		foreach($result as $row) {
			postSet($idSet, $row['idSet'], $row['strSet']);
		}
		
	} catch(PDOException $e) {
		echo $e->getMessage();
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

			if (idMovie == null || idMovie == -1 || sel == null) {
				return;
			}

			var action = (idSet != -1 ? 'linkUpdate' : 'linkInsert');
			if (sel.value == -1) {
				action = 'linkDelete';
			}

			window.location.href='./dbEdit.php?act=' + action + '&id=' + sel.value + '&' + 'idMovie=' + idMovie;
		}
	</script>
	</head>
<body style="wdith:350px; height:50px; margin:0px; padding:15 10px;">
	<div style="float:right; padding:0px 5px;">
		<input type="button" value="Ok" class="okButton" onclick="setMovieSet(); return false;">
	</div>
	<div style="float:left; width:200px; padding:2px 0px 0px 0px;">
		<select id="setSelect" class="styled-select" style="position:absolute; font-size:10px !important; width:195px !important; height:18px !important;" size="1">
			<option value="-1"> </option>
<?php postSets(); ?>
		</select>
	</div>
</body>
</html>
