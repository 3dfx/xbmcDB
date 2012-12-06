<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
<?php
	include_once "auth.php";
	include_once "check.php";
	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "globals.php";

	$admin = isAdmin();
	if ($admin) {
		$idMovie = -1;
		if (isset($_GET['idMovie'])) { $idMovie = trim($_GET['idMovie']); }

		$idGenre = -1;
		//if (isset($_GET['idGenre'])) { $idGenre = trim($_GET['idGenre']); }

		if ($idMovie == -1 && $idGenre == -1) { return; }

		$change = '';
		if (isset($_GET['change'])) { $change = trim($_GET['change']); }
		if ($change == '') { return; }
		
		$closeFrame = 0;
		if (isset($_GET['closeFrame'])) { $closeFrame = trim($_GET['closeFrame']); }

		$res = fetchInfos();
		$title = ($res == null ? '' : $res[0]);
		if ($change == 'movie') {
			$jahr      = ($res == null ? '' : $res[1]);
			$filename  = ($res == null ? '' : $res[2]);
			$idFile    = ($res == null ? -1 : $res[3]);
			$dateAdded = ($res == null ? '' : $res[4]);
			$rating    = ($res == null ? '' : $res[5]);
		}
?>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<link rel="stylesheet" type="text/css" href="class.css" />
	<script type="text/javascript">
		$(document).ready(function(){
			var inp = document.getElementById('title');
			if (inp != null) {
				inp.focus();
			}

<?php
	if ($closeFrame) {
?>
			//$("form").submit(function() {
			//	$.post($("form").attr('action'), $("form").serializeArray());
			parent.$.fancybox.close();
			return false;
			//});
<?php
	}
?>
		});

		function cursorBusy() {
			$('body').css('cursor', 'wait');
			$('button').css('cursor', 'wait');
			$('input').css('cursor', 'wait');
			$('td').css('cursor', 'wait');
		}

<?php
if ($change == 'genre') {
?>
		function setGenreInfos(btn) {
			btn.blur();

			var inTitle = document.getElementById('title');
			if (inTitle == null) {
				return;
			}

			var title = $.trim(inTitle.value);

<?php postOrValues(); ?>

			var changes = false;
			var href = './dbEdit.php?act=setgenreinfo&idGenre=' + idGenre;
			if (title != orTitle && title != '') {
				href = href + '&title=' + title;
				changes = true;
			}

			if (!changes) {
				alert('Nothing to change!');
				return;
			}

			cursorBusy();
			$(btn).addClass('okButtonClicked');
			window.location.href=href;
		}
<?php
}

if (empty($change) || $change == 'movie') {
		$change == 'movie';
?>
		function setMovieInfos(btn) {
			btn.blur();

			var inTitle = document.getElementById('title');
			var inJahr = document.getElementById('jahr');
			var inFile = document.getElementById('filename');
			var inDateAdded = document.getElementById('dateAdded');
			var inRating = document.getElementById('rating');

			if (inTitle == null || inJahr == null || inFile == null || inDateAdded == null || inRating == null) {
				return;
			}

			var title     = $.trim(inTitle.value);
			var jahr      = $.trim(inJahr.value);
			var file      = $.trim(inFile.value);
			var rating    = $.trim(inRating.value);
			var dateAdded = $.trim(inDateAdded.value);

<?php postOrValues(); ?>

			var changes = false;
			var href = './dbEdit.php?act=setmovieinfo&idMovie=' + idMovie + '&idFile=' + idFile;
			if (title != orTitle && title != '') {
				title = title.replace('&', '_AND_');
				href = href + '&title=' + title;
				changes = true;
			}
			if (jahr != orJahr && jahr != '') {
				href = href + '&jahr=' + jahr;
				changes = true;
			}
			if (file != orFile && file != '') {
				href = href + '&filename=' + file;
				changes = true;
			}
			if (dateAdded != orDateAdded && dateAdded != '') {
				href = href + '&dateAdded=' + dateAdded;
				changes = true;
			}
			if (rating != orRating && rating != '') {
				href = href + '&rating=' + rating;
				changes = true;
			}

			if (!changes) {
				alert('Nothing to change!');
				return;
			}

			cursorBusy();
			$(btn).addClass('okButtonClicked');
			window.location.href=href;
		}
<?php
}
	//$jScriptFunc = ($change == 'movie' ? 'setMovieInfos' : 'setGenreInfos');
?>
	</script>
<?php } ?>
	</head>
	<body style="margin:7px 2px; padding:0px !important;"><?php if ($admin) { ?>
	<table id="movieInfos" class="key film" style="width:350px; padding:0px; z-index:1; margin:0px !important;">
<?php postEditor(); ?>
		<tr><td colspan="2" class="righto" style="padding:10 0px !important;"><div style="float:right; padding:0px 11px;"><input type="button" value="Ok" class="key okButton" style="height:25px; width:275px;" onclick="setMovieInfos(this); return false;"></div></tr>
	</table>
<?php } ?>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function fetchInfos() {
	$idMovie = $GLOBALS['idMovie'];
	$idGenre = $GLOBALS['idGenre'];
	$change = $GLOBALS['change'];

	/*** make it or break it ***/
	error_reporting(E_ALL);
	try {
		$db_name = $GLOBALS['db_name'];
		$dbh = new PDO($db_name);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

		if ($change == 'movie') {
			$SQL = 'SELECT F.idFile, M.c00 as title, M.c05 as rating, M.c07 as jahr, F.strFilename as filename, FM.dateAdded as dateAdded '.
			'FROM movie M, filemap FM, files F WHERE FM.idFile = F.idFile AND M.idFile = F.idFile AND M.idMovie = '.$idMovie.';';
			$result = $dbh->query($SQL);
			$res = array();
			$i = 0;
			foreach($result as $row) {
				$res[0] = trim($row['title']);
				$res[1] = trim($row['jahr']);
				$res[2] = trim($row['filename']);
				$res[3] = trim($row['idFile']);
				$res[4] = trim($row['dateAdded']);
				$res[5] = trim($row['rating']);
				$i++;
			}

		} else if ($change == 'genre') {
			$SQL = 'SELECT * FROM genre WHERE idGenre = '.$idGenre.';';
			$result = $dbh->query($SQL);
			$res = array();
			$i = 0;
			foreach($result as $row) {
				$res[0] = trim($row['strGenre']);
				$i++;
			}
		}

		return ($i == 0 ? null : $res);

	} catch(PDOException $e) {
		echo $e->getMessage();
	}
}

function postEditor() {
	$change = $GLOBALS['change'];

	$title = $GLOBALS['title'];

	echo "\t\t".'<tr>';
	if ($change == 'movie') {
		$filename = $GLOBALS['filename'];
		$jahr = $GLOBALS['jahr'];
		$idFile = $GLOBALS['idFile'];
		$dateAdded = $GLOBALS['dateAdded'];
		$rating = $GLOBALS['rating'];

		echo '<td style="padding-left:5px;">Jahr:</td>';
		echo '<td><input type="text" id="jahr" class="key inputbox" style="width:75px;" value="'.$jahr.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td style="padding-left:5px;">Rating:</td>';
		echo '<td><input type="text" id="rating" class="key inputbox" style="width:75px;" value="'.$rating.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td style="padding-left:5px;">Hinzugef&uuml;gt:</td>';
		echo '<td><input type="text" id="dateAdded" class="key inputbox" style="width:275px;" value="'.$dateAdded.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td style="padding-left:5px;">Datei:</td>';
		echo '<td><input type="text" id="filename" class="key inputbox" style="width:275px;" value="'.$filename.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
	}
	echo '<td style="padding-left:5px;">Name:</td>';
	echo '<td><input type="text" id="title" class="key inputbox" style="width:275px;" value="'.$title.'" onfocus="this.select();" onclick="this.select();" /></td>';
	echo '</tr>';
	echo "\r\n";
}

function postOrValues() {
	$change = $GLOBALS['change'];
	$title = $GLOBALS['title'];

	echo "\t\t\t".'var orTitle = "'.$title.'";'."\r\n";
	if ($change == 'movie') {
		$filename  = $GLOBALS['filename'];
		$jahr      = $GLOBALS['jahr'];
		$idFile    = $GLOBALS['idFile'];
		$idMovie   = $GLOBALS['idMovie'];
		$dateAdded = $GLOBALS['dateAdded'];
		$rating    = $GLOBALS['rating'];

		echo "\t\t\t".'var orJahr      = "'.$jahr.'";'."\r\n";
		echo "\t\t\t".'var orRating    = "'.$rating.'";'."\r\n";
		echo "\t\t\t".'var orFile      = "'.$filename.'";'."\r\n";
		echo "\t\t\t".'var idFile      = '.$idFile.';'."\r\n";
		echo "\t\t\t".'var idMovie     = '.$idMovie.';'."\r\n";
		echo "\t\t\t".'var orDateAdded = \''.$dateAdded.'\';'."\r\n";

	} else if ($change == 'genre') {
		$idGenre = $GLOBALS['idGenre'];
		echo "\t\t\t".'var idGenre = '.$idGenre.';'."\r\n";
	}
}
?>
