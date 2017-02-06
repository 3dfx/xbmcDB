<?php
	include_once "auth.php";
	include_once "check.php";
	include_once "./template/functions.php";
	include_once "./template/config.php";
	include_once "globals.php";

	if (!isAdmin()) { return; }
	
	$idMovie = getEscGPost('idMovie', -1);
	$idGenre = -1;
	if ($idMovie == -1 && $idGenre == -1) { return; }
	$change     = getEscGPost('change');
	$closeFrame = getEscGPost('closeFrame', 0);
	
	$res = fetchInfos();
	$title = ($res == null ? '' : $res[0]);
	if (empty($change) || $change == 'movie') {
		$jahr      = ($res == null ? '' : $res[1]);
		$filename  = ($res == null ? '' : $res[2]);
		$idFile    = ($res == null ? -1 : $res[3]);
		$dateAdded = ($res == null ? '' : $res[4]);
		$rating    = ($res == null ? '' : $res[5]);
		$genre     = ($res == null ? '' : $res[6]);
	}
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
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
			parent.$.fancybox.close();
			return false;
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

			var inTitle     = document.getElementById('title');
			var inJahr      = document.getElementById('jahr');
			var inFile      = document.getElementById('filename');
			var inDateAdded = document.getElementById('dateAdded');
			var inRating    = document.getElementById('rating');
			var inGenre     = document.getElementById('genre');

			if (inTitle == null || inJahr == null || inFile == null || inDateAdded == null || inRating == null) {
				return;
			}

			var title     = $.trim(inTitle.value);
			var jahr      = $.trim(inJahr.value);
			var file      = $.trim(inFile.value);
			var rating    = $.trim(inRating.value);
			var dateAdded = $.trim(inDateAdded.value);
			var genre     = $.trim(inGenre.value);

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
			if (genre != orGenre && genre != '') {
				href = href + '&genre=' + genre;
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
?>
	</script>
	</head>
	<body style="margin:7px 2px; padding:0px !important;">
	<table id="movieInfos" class="key film" style="width:350px; padding:0px; z-index:1; margin:0px !important;">
<?php postEditor(); ?>
		<tr><td colspan="2" class="righto" style="padding:10 0px !important;"><div style="float:right; padding:0px 11px;"><input type="button" value="Ok" class="key okButton" style="height:25px; width:275px;" onclick="setMovieInfos(this); return false;"></div></tr>
	</table>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function fetchInfos() {
	$idMovie = $GLOBALS['idMovie'];
	$idGenre = $GLOBALS['idGenre'];
	$change  = $GLOBALS['change'];
	
	if ($change == 'movie') {
		$SQL = "SELECT F.idFile, A.c00 AS title, ".mapDBC('A.c05')." AS rating, A.c07 AS jahr, A.c14 AS genre, F.strFilename AS filename, FM.dateAdded AS dateAdded ".
		"FROM movie A, filemap FM, files F ".mapDBC('joinRatingMovie')." WHERE FM.idFile = F.idFile AND A.idFile = F.idFile AND A.idMovie = ".$idMovie.";";
		$result = querySQL($SQL);
		$res = array();
		$i = 0;
		foreach($result as $row) {
			$res[] = trim($row['title']);
			$res[] = trim($row['jahr']);
			$res[] = trim($row['filename']);
			$res[] = trim($row['idFile']);
			$res[] = trim($row['dateAdded']);
			$res[] = trim($row['rating']);
			$res[] = trim($row['genre']);
			$i++;
		}
		
	} else if ($change == 'genre') {
		$SQL = "SELECT * FROM genre WHERE idGenre = ".$idGenre.";";
		$result = querySQL($SQL);
		$res = array();
		$i = 0;
		foreach($result as $row) {
			$res[0] = trim($row['strGenre']);
			$i++;
		}
	}
	
	return ($i == 0 ? null : $res);
}

function postEditor() {
	$change = $GLOBALS['change'];
	$title  = $GLOBALS['title'];
	
	echo "\t\t".'<tr>';
	if ($change == 'movie') {
		$filename  = $GLOBALS['filename'];
		$jahr      = $GLOBALS['jahr'];
		$idFile    = $GLOBALS['idFile'];
		$dateAdded = $GLOBALS['dateAdded'];
		$rating    = $GLOBALS['rating'];
		$genre     = $GLOBALS['genre'];

		echo '<td style="padding-left:5px;">Year:</td>';
		echo '<td><input type="text" id="jahr" class="key inputbox" style="width:75px;" value="'.$jahr.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td style="padding-left:5px;">Rating:</td>';
		echo '<td><input type="text" id="rating" class="key inputbox" style="width:75px;" value="'.$rating.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td style="padding-left:5px;">Date added:</td>';
		echo '<td><input type="text" id="dateAdded" class="key inputbox" style="width:275px;" value="'.$dateAdded.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td style="padding-left:5px;">Genre:</td>';
		echo '<td><input type="text" id="genre" class="key inputbox" style="width:275px;" value="'.$genre.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
		echo '<td style="padding-left:5px;">Filename:</td>';
		echo '<td><input type="text" id="filename" class="key inputbox" style="width:275px;" value="'.$filename.'" onfocus="this.select();" onclick="this.select();" /></td>';
		echo '</tr>';
		echo '<tr>';
	}
	echo '<td style="padding-left:5px;">Title:</td>';
	echo '<td><input type="text" id="title" class="key inputbox" style="width:275px;" value="'.$title.'" onfocus="this.select();" onclick="this.select();" /></td>';
	echo '</tr>';
	echo "\r\n";
}

function postOrValues() {
	$change = $GLOBALS['change'];
	$title  = $GLOBALS['title'];
	
	echo "\t\t\t".'var orTitle     = "'.$title.'";'."\r\n";
	if ($change == 'movie') {
		$filename  = $GLOBALS['filename'];
		$jahr      = $GLOBALS['jahr'];
		$idFile    = $GLOBALS['idFile'];
		$idMovie   = $GLOBALS['idMovie'];
		$dateAdded = $GLOBALS['dateAdded'];
		$rating    = $GLOBALS['rating'];
		$genre     = $GLOBALS['genre'];
		
		echo "\t\t\t".'var orJahr      = "'.$jahr.'";'."\r\n";
		echo "\t\t\t".'var orRating    = "'.$rating.'";'."\r\n";
		echo "\t\t\t".'var orFile      = "'.$filename.'";'."\r\n";
		echo "\t\t\t".'var idFile      = '.$idFile.';'."\r\n";
		echo "\t\t\t".'var idMovie     = '.$idMovie.';'."\r\n";
		echo "\t\t\t".'var orDateAdded = "'.$dateAdded.'";'."\r\n";
		echo "\t\t\t".'var orGenre     = "'.$genre.'";'."\r\n";
		
	} else if ($change == 'genre') {
		$idGenre = $GLOBALS['idGenre'];
		echo "\t\t\t".'var idGenre = '.$idGenre.';'."\r\n";
	}
}
?>
