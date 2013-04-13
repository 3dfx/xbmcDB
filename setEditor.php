<?php
	include_once "auth.php";
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "globals.php";
	
	if (!isAdmin()) { exit; }
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
	<title>Set Editor</title>
<?php
	$closeFrame = isset($_GET['closeFrame']) ? trim($_GET['closeFrame']) : 0;
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
			if (answer == null || answer == '') { return; }
			
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
			return false;
<?php
	}
?>
	</script>
	</head>
	<body>
	<table id="serieSet" class="film" style="width:350px; padding:0px; z-index:1;">
		<tr><th class="righto">id</th><th colspan="2" style="padding-left:10px !important;">Setname</th></tr>
<?php postSets(); ?>
		<tr><td class="righto" colspan="3" style="cursor:pointer;" onclick="addSet(); return false;"><img src="./img/add.png"/ title="add"></td></tr>
	</table>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function postSets() {
	/*** make it or break it ***/
	error_reporting(E_ALL);
	
	try {
		$db_name = $GLOBALS['db_name'];
		$dbh = new PDO($db_name);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		/*
		$SQL_SETLINK = 'SELECT S.idSet, S.strSet, M.c00 AS filmname, M.idMovie AS idMovie FROM movie M, sets S WHERE M.idSet = S.idSet ORDER BY S.strSet, M.c00;';
		$result = $dbh->query($SQL_SETLINK);
		$lastSet = -1;
		foreach($result as $entry) {
			$idSet    = $entry['idSet'];
			$strSet   = $entry['strSet'];
			$idMovie  = $entry['idMovie'];
			$filmname = $entry['filmname'];
			
			if ($idSet != $lastSet) {
				postSet($idSet, $strSet);
			}
			postSetMovie($idSet, $idMovie, $filmname);
			$lastSet = $idSet;
		}
		*/
		
		$SQL_SETS = 'SELECT * FROM sets ORDER BY strSet;';
		$result = $dbh->query($SQL_SETS);
		$sets = array();
		$s = 0;
		foreach($result as $row) {
			$sets[$s]['idSet'] = $row['idSet'];
			$sets[$s]['strSet'] = $row['strSet'];
			$s++;
		}
		
		$SQL_SETLINK = 'SELECT S.idSet, S.strSet, M.c00 AS filmname, M.idMovie AS idMovie FROM movie M, sets S WHERE M.idSet = S.idSet ORDER BY S.strSet, M.c00;';
		$result = $dbh->query($SQL_SETLINK);
		$movies = array();
		$i = 0;
		foreach($result as $row) {
			$movies[$i]['idSet']    = $row['idSet'];
			$movies[$i]['idMovie']  = $row['idMovie'];
			$movies[$i]['strSet']   = $row['strSet'];
			$movies[$i]['filmname'] = $row['filmname'];
			$i++;
		}
		
		foreach($sets as $entry) {
			$idSet = $entry['idSet'];
			$strSet = $entry['strSet'];
			postSet($idSet, $strSet);
			
			foreach($movies as $movie) {
				if ($movie['idSet'] != $idSet) {
					continue;
				}
				
				postSetMovie($movie['idSet'], $movie['idMovie'], $movie['filmname']);
			}
		}
		
	} catch(PDOException $e) {
		echo $e->getMessage();
	}
}

function postSetMovie($idSet, $idMovie, $name) {
	echo "\t\t".'<tr>';
	echo '<td></td>';
	echo '<td style="padding-left:25px !important; font-size:10px;">'.$name.'</td>';
	echo '<td class="righto" style="cursor:pointer;" onclick="setMoviesetCover('.$idSet.', '.$idMovie.'); return false;"><img src="./img/add.png"/ title="set Cover and Fanart"></td>';
	echo '</tr>';
	echo "\r\n";
}

function postSet($id, $name) {
	echo "\t\t".'<tr>';
	echo '<td class="righto" style="padding-right:0px !important;">'.$id.'</td>';
	$jsName = str_replace("'", "\'", $name);
	echo '<td style="cursor:pointer; padding-left:10px !important;" onclick="setSetName(\''.$id.'\', \''.$jsName.'\'); return false;">'.$name.'</td>';
	echo '<td class="righto" style="cursor:pointer;" onclick="deleteSet(\''.$id.'\', \''.$jsName.'\'); return false;"><img src="./img/del.gif"/ title="delete"></td>';
	echo '</tr>';
	echo "\r\n";
}
?>
