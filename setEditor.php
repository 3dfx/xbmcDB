<?php
include_once "check.php";
include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";

	if (!isAdmin()) { return; }
?>
<html>
	<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
	<title>Set Editor</title>
<?php
	$closeFrame = getEscGet('closeFrame', 0);
?>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<link rel="stylesheet" type="text/css" href="class.css" />
	<script type="text/javascript">
		function cursorBusy() {
			$('body').css('cursor', 'wait');
			$('td').css('cursor', 'wait');
		}

		function setMoviesetCover(idSet, idMovie) {
			if (idSet == null || idMovie == null) { return; }

			var frage = unescape("Set movie cover/fanart as set cover/fanart?");
			var answer = confirm(frage);
			if (!answer) { return; }

			cursorBusy();
			window.location.href='./dbEdit.php?act=setMoviesetCover&id=' + idSet + '&' + 'idMovie=' + idMovie;
		}

		function setSetName(id, name) {
			if (id == null || id == 0 || name == null ||  name == '') { return; }

			var answer = prompt("Enter new Name", name);
			if (answer == null || name == answer) { return; }

			name = name.replace('&', '_AND_');

			cursorBusy();
			window.location.href='./dbEdit.php?act=setname&id=' + id + '&' + 'name=' + answer;
		}

		function addSet() {
			var answer = $.trim( prompt("Enter Set Name") );
			if (answer == null || answer === '') { return; }

			cursorBusy();
			window.location.href='./dbEdit.php?act=addset&name=' + answer;
		}

		function deleteSet(id, name) {
			if (id == null || id == 0) { return; }

			var frage = unescape("Delete set '" + name + "'?");
			var answer = confirm(frage);
			if (!answer) { return; }

			cursorBusy();
			window.location.href='./dbEdit.php?act=delete&id=' + id;
		}
		
<?php
	if (!empty($closeFrame)) {
?>
			parent.$.fancybox.close();
			// return false;
<?php
	}
?>
	</script>
	</head>
	<body>
	<table id="serieSet" class="film" style="width:99%; padding:0; margin-bottom:0; margin-left:-9px !important; z-index:1;">
		<tr><th class="righto" style="padding:0 0 0 15px;">id</th><th colspan="2" style="padding-left:10px !important;">Setname</th></tr>
<?php postSets(); ?>
		<tr><td class="righto" colspan="3" style="cursor:pointer; padding-right:10px;" onclick="addSet(); return false;"><img src="./img/add.png" style="height:16px; width:16px;" title="add set"></td></tr>
	</table>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function postSets() {
	$dbh = getPDO();
	try {
		$SQL_SETS = 'SELECT * FROM sets ORDER BY strSet;';
		$result = $dbh->query($SQL_SETS);
		$sets     = array();
		$setNames = array();
		$idNames  = array();

		foreach($result as $row) {
		    $idSet  = $row['idSet'];
		    $strSet = $row['strSet'];
			$sets[$idSet]['idSet']  = $idSet;
			$sets[$idSet]['strSet'] = $strSet;

			$switched = switchPronoms($strSet);
			$idNames[$switched] = $idSet;
			$setNames[] = $switched;
		}

		sort($setNames);

		$SQL_SETLINK = 'SELECT S.idSet, S.strSet, M.c00 AS filmname, M.idMovie AS idMovie FROM movie M, sets S WHERE M.idSet = S.idSet ORDER BY S.strSet, M.c00;';
		$result = $dbh->query($SQL_SETLINK);
		$movies = array();
		$counts = array();

		foreach($result as $row) {
			$idSet   = $row['idSet'];

			$movieArray = isset($movies[$idSet]) ? $movies[$idSet] : array();
			$count = count($movieArray);

			$movieArray['idSet']    = $idSet;
			$movieArray['idMovie']  = $row['idMovie'];
			$movieArray['filmname'] = switchPronoms($row['filmname']);
			$movies[$idSet][$count] = $movieArray;
			$counts[$idSet] = isset($counts[$idSet]) ? $counts[$idSet]+1 : 1;
		}

		foreach($setNames as $setName) {
		    $idSet = $idNames[$setName];
		    $set = $sets[$idSet];
		    if ($idSet != $set['idSet']) { continue; }

			postSet($idSet, $setName, $set['strSet'], !empty($counts[$idSet]) ? $counts[$idSet] : 0);

			if (isset($movies[$idSet])) {
				$moviez = $movies[$idSet];
				foreach($moviez as $movie) {
					if ($movie['idSet'] != $idSet) { continue; }
					postSetMovie($movie['idSet'], $movie['idMovie'], $movie['filmname']);
				}
			}
		}

	} catch(Throwable $e) {
		echo $e->getMessage();
	}
}

function postSetMovie($idSet, $idMovie, $name) {
	echo "\t\t".'<tr>';
	echo '<td></td>';
	echo '<td style="padding-left:25px !important; font-size:10px; overflow-x:hidden; max-width:370px; width:370px;">'.$name.'</td>';
	echo '<td class="righto" style="cursor:pointer;" onclick="setMoviesetCover('.$idSet.', '.$idMovie.'); return false;"><img src="./img/apply.png" style="height:16px; width:16px;" title="apply cover/fanart of movie"></td>';
	echo '</tr>';
	echo "\r\n";
}

function postSet($id, $name, $jsName, $count) {
	echo "\t\t".'<tr>';
	echo '<td class="righto" style="padding-right:0 !important;">'.$id.'</td>';
	$jsName = str_replace("'", "\'", $jsName);
	echo '<td style="cursor:pointer; padding-left:10px !important;" onclick="setSetName(\''.$id.'\', \''.$jsName.'\'); return false;">'.$name.' (<b>'.$count.'</b>)</td>';
	echo '<td class="righto" style="cursor:pointer; padding-right:8px;" onclick="deleteSet(\''.$id.'\', \''.$jsName.'\'); return false;"><img src="./img/del.png" style="height:18px; width:18px;" title="delete"></td>';
	echo '</tr>';
	echo "\r\n";
}
?>
