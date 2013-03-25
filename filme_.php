<?php
	include_once "auth.php";
	include_once "check.php";

	include_once "template/config.php";
	include_once "template/matrix.php";
	include_once "template/functions.php";
	include_once "globals.php";
	include_once "_FILME.php";
?>
<head>
	<title>XBMC Database</title>
	<link rel="shortcut icon" href="favicon.ico" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/highlight.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap.min.js"></script>
	<script type="text/javascript" src="./template/js/bootstrap/js/bootstrap-dropdown.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/docs.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./template/js/bootstrap/css/bootstrap-responsive.min.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="./class.css" />
	<script type="text/javascript">
		var ids = '';
<?php
		echo "\t\t".'var isAdmin = '.(isAdmin() ? '1' : '0').';';
?>

		$(document).ready(function() {
			$('.dropdown-toggle').dropdown();
			$('.styled-select').customStyle();
			$('.styled-select2').customStyle();
		});

		function cursorBusy(state) {
			$('body').css('cursor', state);
			$('#xbmcDB').css('cursor', state);
			$('td').css('cursor', state);
			$('a').css('cursor', state);
		}
<?php
if (isset($GLOBALS['BIND_CTRL_F']) ? $GLOBALS['BIND_CTRL_F'] : true) {
?>

		$(document).keydown(function(event) {
			if(event.keyCode == '70') {
				event.preventDefault();
				openNav('#dropSearch');
				$('#searchDBfor').focus();
			}
		});
<?php
}
?>

		function openNav(objId) {
			closeNavs();
			$(objId).addClass('open');
		}

		function closeNavs() {
			$('#dropOptions').removeClass('open');
			$('#dropViewmode').removeClass('open');
			$('#dropLanguage').removeClass('open');
			$('#dropAdmin').removeClass('open');
			$('#dropSearch').removeClass('open');
		}

		function checkForCheck() {
			return (ids != null && ids != '' ? confirm("Achtung:\nAuswahl geht verloren!") : true);
		}

		function clearSelectBoxes(obj, admin) {
			var node_list = document.getElementsByTagName('input');

			for (i = 0; i < node_list.length; i++) {
				var a = node_list[i];
				if (a == null) {
					continue;
				}

				if (a.id == 'copyAsScript' || a.id == 'clearSelectAll') {
					continue;
				}

				if (a.type == 'checkbox' && a.checked != obj.checked && !a.disabled) {
					a.checked = obj.checked;
					selected(a, false, false, admin);
				}
			}

			var resBox = document.getElementById('result');
			if (resBox == null) { return; }

			doRequest(admin);

			resBox.style.display = obj.checked ? 'block' : 'none';
			if (!obj.checked) {
				resBox.innerHTML = '';
				ids = '';
			}
		}

		function selected(obj, changeMaster, postRequest, admin) {
			var tr = $( obj ).parent().parent();
			
			if (obj.checked) {
				ids = ids + (ids.length == 0 ? '' : ', ') + obj.value;
				$( tr ).children().addClass('highLighTR');

			} else {
				$( tr ).children().removeClass('highLighTR');
				
				if (ids.indexOf(obj.value + ', ') != -1) {
					ids = ids.replace(obj.value + ', ', '');

				} else if (ids.indexOf(', ' + obj.value) != -1) {
					ids = ids.replace(', ' + obj.value, '');

				} else if (ids.indexOf(',') == -1) {
					ids = ids.replace(obj.value, '');
				}
			}

			var resBox = document.getElementById('result');
			if (resBox == null) { return; }
			var clearSelectAll = document.getElementById('clearSelectAll');
			if (ids == '') {
				resBox.style.display = 'none';
				resBox.innerHTML = '';

			} else {
				resBox.style.display = 'block';
			}

			if (changeMaster) {
				clearSelectAll.checked = ids == '' ? false : true;
			}

			if (postRequest) {
				doRequest(admin);
			}
		}

		function doRequest(admin) {
			var resBox = document.getElementById('result');
			if (resBox == null) { return; }

			var copyAsScript = document.getElementById('copyAsScript');
			var asscript = (copyAsScript != null && copyAsScript.checked) ? 1 : 0;
			
			document.getElement
			$.ajax({
				type: "POST",
				url: "request.php",
				data: "contentpage="+"&ids=" + ids + "&copyAsScript=" + asscript + "&admin="+admin,
				success: function(data){
					$("#result").html(data);
				}
			});
		}
		
		function orderStuff(admin) {
			var resBox = document.getElementById('result');
			if (resBox == null) { return; }

			var copyAsScript = document.getElementById('copyAsScript');
			var asscript = (copyAsScript != null && copyAsScript.checked) ? 1 : 0;
			
			document.getElement
			$.ajax({
				type: "POST",
				url: "request.php",
				data: "contentpage="+"&ids=" + ids + "&copyAsScript=" + asscript + "&admin="+admin + "&forOrder=1",
				success: function(data) {
					if (data == 'success') {
						alert('Saved selection!');
					} else {
						alert('Error saving!');
					}
				}
			});
		}
		
		function collectIds() {
			ids = '';
			var trs = $('TR.searchFlag');
			for (var r = 0; r < trs.length; r++) {
				var tr = trs[r];
				var obj = $( tr ).find('.checka')[0];
				if (obj.disabled || !obj.checked) { continue; }
				ids = ids + (ids.length == 0 ? '' : ', ') + obj.value;
			}
		}

		function newlyChange() {
			if (!checkForCheck()) { return false; }
			$('body').css('cursor', 'wait');
			moviefrm.submit();
		}

		var searchLength = 0;
		function searchForString(obj, event) {
			var search = ( obj == null ? '' : $.trim(obj.value).toLowerCase() );
			
			if (obj != null && event != null) {
				var kC = getKeyCode(event);
				if (kC != 13) { return false; }
			}
			
			$('span').removeHighlight();
			
			if (search.length < 3) {
				$('TR.searchFlag').show();
				$('TR').find('.checka').removeAttr('disabled');
				return false;
			}
			
			var trs = $('TR.searchFlag');
			for (var r = 0; r < trs.length; r++) {
				var tr = trs[r];
				
				var foundString = false;
				var spans = $( tr ).find('.searchField');
				for (var s = 0; s < spans.length; s++) {
					var span = spans[s];
					if (span == null) { continue; }
					
					var string = $.trim(span.innerHTML).toLowerCase();
					if (string == null || string == '') { continue; }
					
					foundString = (string.indexOf(search) >= 0 ? true : false);
					if (foundString) { break; }
				}
				
				if (foundString) {
					$( tr ).show(); 
					$( $( tr ).find('.checka')[0] ).removeAttr('disabled');
					
				} else {
					$( tr ).hide(); 
					$( $( tr ).find('.checka')[0] ).attr('disabled', 'disabled');
					$( $( tr ).find('.checka')[0] ).attr('checked', false);
				}
				
				if (foundString) { continue; }
			}
			
			if (search != null && search != '') { $('span').highlight(search); }
			searchLength = search.length;
			
			collectIds();
			doRequest(isAdmin);
		}
		
		function searchDbForString(obj, event) {
			if (obj == null) { return false; }
			var search = $.trim(obj.value).toLowerCase();
			
			var kC = getKeyCode(event);
			if (kC != 13) { return false; }
			if (!checkForCheck()) { return false; }
			
			var href = './?show=filme&dbSearch=';
			if (search != null && search != '') { href = href + search; }
			
			window.location.href=href;
		}

		function resetFilter() {
			var filter = document.getElementById('searchfor');
			filter.value = "";
			searchForString(null, null);
		}
		
		function resetDbSearch() {
			if (!checkForCheck()) { return false; }
			window.location.href='./?show=filme&dbSearch=';
		}
		
		function getKeyCode(event) {
			event = event || window.event;
			return event.keyCode;
		}
	</script>
</head>
<body id="xbmcDB" style="overflow-x:hidden; overflow-y:auto;">
<?php
	$admin = isAdmin();
	
	$maself = $_SERVER['PHP_SELF'];
	$isMain = (substr($maself, -9) == 'index.php');
	postNavBar($isMain);

	$mode = 0;
	if (isset($_SESSION['mode'])) { $mode = $_SESSION['mode']; }

	$newmode = (isset($_SESSION['newmode']) ? $_SESSION['newmode'] : 0);
	$newsort = (isset($_SESSION['newsort']) ? $_SESSION['newsort'] : 0);
	$gallerymode = (isset($_SESSION['gallerymode']) ? $_SESSION['gallerymode'] : 0);

	$which = (isset($_SESSION['which']) ? $_SESSION['which'] : '');
	$just = (isset($_SESSION['just']) ? $_SESSION['just'] : '');
	$country = (isset($_SESSION['country']) ? $_SESSION['country'] : '');

#if (isAdmin()) { pre( print_r($_SESSION) ); }

	echo '<div class="tabDiv" onmouseover="closeNavs();">';
	echo "\r\n";
	echo '<table class="'.($gallerymode != 1 ? 'film' : 'gallery').'" cellspacing="0">';
	echo "\r\n";

	createTable();

	if ($admin && !$gallerymode) {
		echo "\t";
		echo '<tr><td colspan="'.getColSpan().'" class="optTD">';
		echo '<div style="float:right; padding:0px 5px;">';
		echo ' <input type="submit" value="Ok" name="submit" class="okButton">';
		echo '</div>';
		echo '<div style="float:right; padding-top:5px;">';
		echo '<select class="styled-select2" style="position:absolute; opacity:0; margin:0px; font-size:10px !important; width:115px !important; height:18px !important;" name="aktion" size="1">';
		echo '<option value="0">-- select option </option>';
		echo '<option value="1">mark as unseen</option>';
		echo '<option value="2">mark as seen</option>';
		echo '<option value="3">delete</option>';
		echo '</select>';
		echo '</div>';
		echo '</td></tr>';
		echo "\r\n\t";
	}

	echo '</form>';
	echo "\r\n";
   	echo '</table>';
	echo "\r\n";
	echo '</div>';

	if ($COPYASSCRIPT_ENABLED || !$admin) {
		if ($gallerymode != 1) {
			echo "\r\n";
			echo '<div class="lefto" style="padding-left:15px; z-order=1; height:60px;">';
			if ($COPYASSCRIPT_ENABLED && !$admin) {
				echo "\t<input type='checkbox' id='copyAsScript' onClick='doRequest($admin); return true;' style='float:left;'/><label for='copyAsScript' style='float:left; margin-top:-5px;'>as copy script</label>";
				echo "<br/>";
			}
			echo "\t<input type='button' name='orderBtn' id='orderBtn' onclick='orderStuff($admin); return true;' value='save'/>";
			echo "</div>";
		}

		echo "\r\n";
		echo '<div id="result" class="selectedfield"></div>';
		echo "\r\n";
	}
?>

<?php
/*          FUNCTIONS          */
function infobox($title, $info) {
	echo '<div id="boxx"><a href="#">';
	echo $title;
	echo '<span>';
 	echo $info;
	echo '</span></a></div>';
}

function getColspan() {
	$gallerymode = $GLOBALS['gallerymode'];
	$elementsInRow = getElemsInRow();
	$newAddedCount = isset($GLOBALS['DEFAULT_NEW_ADDED']) ? $GLOBALS['DEFAULT_NEW_ADDED'] : 30;
	$COLUMNCOUNT = isset($GLOBALS['COLUMNCOUNT']) ? $GLOBALS['COLUMNCOUNT'] : 8;
	$newAddedCount = (isset($_SESSION['newAddedCount']) ? $_SESSION['newAddedCount'] : $newAddedCount);
	return ($gallerymode != 1 ? $COLUMNCOUNT : ($newAddedCount < $elementsInRow ? $newAddedCount : $elementsInRow));
}

function generateForm() {
	$newmode = $GLOBALS['newmode'];
	$newsort = $GLOBALS['newsort'];
	
	$admin = $GLOBALS['admin'];
	$mode = $GLOBALS['mode'];
	echo "\t";
	echo '<form action="index.php" name="moviefrm" method="post">';
	echo "\r\n";

	if ($newmode && !$admin) {
		$sizes = isset($GLOBALS['SHOW_NEW_VALUES']) ? $GLOBALS['SHOW_NEW_VALUES'] : array(30, 60);
		$newAddedCount = isset($GLOBALS['DEFAULT_NEW_ADDED']) ? $GLOBALS['DEFAULT_NEW_ADDED'] : 30;
		$newAddedCount = (isset($_SESSION['newAddedCount']) ? $_SESSION['newAddedCount'] : $newAddedCount);

		echo "\t";
		echo '<tr>';
		echo '<th class="newAddedTH" colspan="'.getColSpan().'">';
		echo '<div style="float:right; padding-top:1px;">';
		echo '<select class="styled-select" style="position:absolute; opacity:0; margin:0px; font-size:10px !important; width:55px !important; height:18px !important;" name="newAddedCount" size="1" onChange="newlyChange();">';
		for ($i = 0; $i < count($sizes); $i++) {
			$size = $sizes[$i];
			echo '<option value="'.$size.'"'.($size == $newAddedCount ? ' SELECTED' : '').'>'.$size.'</option>';
		}
		echo '</select>';
		echo '</div>';
		echo '<div style="float:right; padding:2px 5px;">show newest: </div>';
		echo '</th>';
		echo '</tr>';
		echo "\r\n";
	}
}

function createTable() {
	$hostname = $_SERVER['HTTP_HOST'];

	$admin = $GLOBALS['admin'];
	$dev = $admin;
	$NAS_CONTROL = isset($GLOBALS['NAS_CONTROL']) ? $GLOBALS['NAS_CONTROL'] : false;
	$COVER_OVER_TITLE = isset($GLOBALS['COVER_OVER_TITLE']) ? $GLOBALS['COVER_OVER_TITLE'] : false;
	$USECACHE = isset($GLOBALS['USECACHE']) ? $GLOBALS['USECACHE'] : true;

	$IMDB = $GLOBALS['IMDB'];
	$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$IMDBFILMTITLE = $GLOBALS['IMDBFILMTITLE'];
	$FILMINFOSEARCH = $GLOBALS['FILMINFOSEARCH'];
	$PERSONINFOSEARCH = $GLOBALS['PERSONINFOSEARCH'];

	$mode = 1;
	$sort = null;
	$unseen = '';
	$dbSearch = null;
	$newmode = 0;
	$newsort = 0;
	$thumbsize = 1;
	$gallerymode = 0;
	$newAddedCount = 30;
	$elemsInRow = getElemsInRow();

	$zeilen = array();

	/*** make it or break it ***/
	error_reporting(E_ALL);
	$db_name = $GLOBALS['db_name'];
	#logc( $db_name );
	$dbh = new PDO($db_name);
	try {
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$dbh->beginTransaction();
		checkFileInfoTable($dbh);
		checkFileMapTable($dbh);
		
		if (isset($_SESSION['mode']))          { $mode          = $_SESSION['mode'];          }
		if (isset($_SESSION['sort']))          { $sort          = $_SESSION['sort'];          }
		if (isset($_SESSION['unseen']))        { $unseen        = $_SESSION['unseen'];        }
		if (isset($_SESSION['dbSearch']))      { $dbSearch      = $_SESSION['dbSearch'];      }
		if (isset($_SESSION['newmode']))       { $newmode       = $_SESSION['newmode'];       }
		if (isset($_SESSION['newsort']))       { $newsort       = $_SESSION['newsort'];       }
		if (isset($_SESSION['gallerymode']))   { $gallerymode   = $_SESSION['gallerymode'];   }
		if (isset($_SESSION['thumbsize']))     { $thumbsize     = $_SESSION['thumbsize'];     }
		if (isset($_SESSION['newAddedCount'])) { $newAddedCount = $_SESSION['newAddedCount']; }
		
		$sessionKey = 'movies_';
		$_just = $GLOBALS['just'];
		$_which = $GLOBALS['which'];
		if (!empty($_just) && !empty($_which) || !empty($dbSearch)) {
			$unseen = 3;
		}
		$sessionKey .= 'unseen_'.$unseen.'_';
		
		if (!empty($dbSearch)) {
			$mode = $newmode = 0;
			$saferSearch = SQLite3::escapeString($dbSearch);
			$sessionKey .= 'search_'.str_replace(' ', '-', $saferSearch).'_';
			$filter = 	" AND (".
					" A.c00 LIKE '%".$saferSearch."%' OR".
					" A.c01 LIKE '%".$saferSearch."%' OR".
					" A.c03 LIKE '%".$saferSearch."%' OR".
					" A.c14 LIKE '%".$saferSearch."%' OR".
					" A.c16 LIKE '%".$saferSearch."%'".
					")";
			
		} else if ($_which == 'artist') {
			$filter = " AND E.idActor = '".$_just."' AND E.idMovie = A.idMovie";
			$sessionKey .= 'just_'.$filter.'_';

		} else if ($_which == 'regie') {
			$filter = " AND D.idDirector = '".$_just."' AND D.idMovie = A.idMovie";
			$sessionKey .= 'just_'.$filter.'_';

		} else if ($_which == 'genre') {
			$filter = " AND G.idGenre = '".$_just."' AND G.idMovie = A.idMovie";
			$sessionKey .= 'just_'.$filter.'_';

		} else if ($_which == 'jahr') {
			$filter = " AND A.c07 = '".$_just."'";
			$sessionKey .= 'just_'.$filter.'_';

		} else {
			unset($filter);
			unset($_which);
		}

		if ($admin) { $newAddedCount = 30; }

		$unseenCriteria = '';
		if ($unseen == "0") {
			$unseenCriteria = " AND B.playCount > 0 ";
		} else if ($unseen == "1") {
			$unseenCriteria = " AND (B.playCount = 0 OR B.playCount IS NULL)";
		} else if ($unseen == "3") {
			$unseenCriteria = '';
		}
		
		$SQL =  "SELECT DISTINCT A.idFile, A.c05, B.playCount, A.c00, A.c01, A.c02, A.c14, A.c15, B.strFilename AS filename, M.dateAdded as dateAdded, ".
			"A.idFile, A.idMovie, ".
			"M.value as dateValue, ".
			"A.c07 AS jahr, A.c08 AS thumb, A.c11 AS dauer, A.c19 AS trailer, A.c09 AS imdbId, C.strPath AS path, F.filesize ".
			"FROM movie A, files B, path C LEFT JOIN fileinfo F ON B.idFile = F.idFile ".
			"LEFT JOIN filemap M ON B.strFilename = M.strFilename ".
			(isset($mode) && ($mode == 2) ? "LEFT JOIN streamdetails SD on B.idFile = SD.idFile " : '').
			(isset($filter, $_which) && ($_which == 'artist') ? ", actorlinkmovie E" : '').
			(isset($filter, $_which) && ($_which == 'regie') ? ", directorlinkmovie D" : '').
			(isset($filter, $_which) && ($_which == 'genre') ? ", genrelinkmovie G" : '').
			" \nWHERE A.idFile = B.idFile AND C.idPath = B.idPath ";
			
		if (!empty($sort)) {
			     if ($sort == 'jahr')    { $sessionKey .= 'orderJahr_';    $sqlOrder = " ORDER BY A.c07 DESC";      }
			else if ($sort == 'jahra')   { $sessionKey .= 'orderJahrA_';   $sqlOrder = " ORDER BY A.c07 ASC";       }
			else if ($sort == 'rating')  { $sessionKey .= 'orderRating_';  $sqlOrder = " ORDER BY A.c05 DESC";      }
			else if ($sort == 'ratinga') { $sessionKey .= 'orderRatingA_'; $sqlOrder = " ORDER BY A.c05 ASC";       }
			else if ($sort == 'size')    { $sessionKey .= 'orderSize_';    $sqlOrder = " ORDER BY F.filesize DESC"; }
			else if ($sort == 'sizea')   { $sessionKey .= 'orderSizeA_';   $sqlOrder = " ORDER BY F.filesize ASC";  }
			
		} else if ($newmode) {
			$sqlOrder = " ORDER BY ".($newsort == 2 ? "F.idFile" : "dateValue")." DESC";
			$sessionKey .= ($newsort == 2 ? 'orderIdMovie_' : 'orderDateValue_');
			
		} else {
			$sqlOrder = " ORDER BY A.c00 ASC";
			$sessionKey .= 'orderName_';
		}
		
		switch ($mode) {
			case 2:
				$country = $GLOBALS['country'];
				$LANGMAP = isset($GLOBALS['LANGMAP']) ? $GLOBALS['LANGMAP'] : array();
				$_country = '';
				if ($country != '') {
					$_country = " AND (SD.strAudioLanguage LIKE '".$country."' ";
					if (isset($LANGMAP[$country])) {
						$map = $LANGMAP[$country];
						for ($c = 0; $c < count($map); $c++) {
							$_country .= " OR SD.strAudioLanguage LIKE '".$map[$c]."' ";
						}
					}
					
					$_country .= ") ";
				}
				
				$sessionKey .= $country;
				$SQL .= $_country;
			break;

			case 3:
				$uncut = " AND (lower(B.strFilename) LIKE '%director%' OR lower(A.c00) LIKE '%director%') ";
				$sessionKey .= 'directorCut';
			break;

			case 4:
				$uncut = " AND (lower(B.strFilename) LIKE '%extended%' OR lower(A.c00) LIKE '%extended%') ";
				$sessionKey .= 'extendedCut';
			break;

			case 5:
				$uncut = " AND (lower(B.strFilename) LIKE '%uncut%' OR lower(A.c00) LIKE '%uncut%') ";
				$sessionKey .= 'uncutCut';
			break;

			case 6:
				$uncut = " AND (lower(B.strFilename) LIKE '%unrated%' OR lower(A.c00) LIKE '%unrated%') ";
				$sessionKey .= 'unratedCut';
			break;

			case 7:
				$uncut = " AND (lower(B.strFilename) LIKE '%.3d.%' OR lower(A.c00) LIKE '%(3d)%') ";
				$sessionKey .= '3dCut';
			break;

			default:
				unset($uncut);
				#" AND (c21 not like 'Turkey%') ".
				#" AND (upper(B.strFilename) not like '%.AVI' AND upper(B.strFilename) not like '%.MPG' AND upper(B.strFilename) not like '%.DAT')".
		}
		
		$params = (isset($filter) ? $filter : '').(isset($uncut) ? $uncut : '').$unseenCriteria.$sqlOrder;
		$SQL .= $params.";";
		
		$counter = 0;
		$zeile = 0;
		$counter2 = 0;
		$moviesTotal = 0;
		$moviesSeen = 0;
		$moviesUnseen = 0;
		
		$idGenre = getGenres($dbh);
		
		$EXCLUDEDIRS = isset($GLOBALS['EXCLUDEDIRS']) ? $GLOBALS['EXCLUDEDIRS'] : array();
		$SHOW_TRAILER = isset($GLOBALS['SHOW_TRAILER']) ? $GLOBALS['SHOW_TRAILER'] : false;
		$ENCODE = isset($GLOBALS['ENCODE_IMAGES']) ? $GLOBALS['ENCODE_IMAGES'] : true;
		$existArtTable = existsArtTable($dbh);
		
		$result = fetchMovies($dbh, $SQL, $sessionKey);
		for($rCnt = 0; $rCnt < count($result); $rCnt++) {
			$row = $result[$rCnt];
			$zeilenSpalte = 0;
			
			$idMovie   = $row['idMovie'];
			$id        = $row['idMovie'];
			$filmname  = $row['c00'];
			$watched   = $row['playCount'];
			$thumb     = $row['thumb'];
			$idFile    = $row['idFile'];
			$filename  = $row['filename'];
			$dateAdded = $row['dateAdded'];
			$path      = $row["path"];
			$jahr      = $row['jahr'];
			$filesize  = $row['filesize'];
			$playCount = $row['playCount'];
			$trailer   = $row['trailer'];
			$rating    = $row['c05'];
			$imdbId    = $row['imdbId'];
			$genres    = $row['c14'];
			$filename  = $row['filename'];
			$fnam      = $path.$filename;
			$cover     = '';
			
			if  (empty($dateAdded)) {
				$creation = getCreation($fnam);
				#logc( $creation );
				if (empty($creation)) {
					$creation = '2001-01-01 12:00:00';
				}
				$dateAdded = $creation;

				try {
					$datum = SQLite3::escapeString(strtotime($dateAdded));
					$dbfname = SQLite3::escapeString($filename);
					$dbh->exec("REPLACE INTO filemap(idFile, strFilename, dateAdded, value) VALUES(".$idFile.", '".$dbfname."', '".$dateAdded."', '".$datum."');");
					#$dbh->exec("UPDATE files SET dateAdded = '$dateAdded' WHERE idFile = '$idFile';");
				} catch(PDOException $e) {
					echo $e->getMessage();
				}
			}

			if ($USECACHE && $gallerymode) {
				if (file_exists(getCoverThumb($fnam, $cover, false))) {
					$cover = getCoverThumb($fnam, $cover, false);
					
				} else if ($existArtTable) {
					#echo "idMovie: ".$idMovie."...<br>";
					$res2 = $dbh->query("SELECT url,type FROM art WHERE media_type = 'movie' AND (type = 'poster' OR type = 'thumb') AND media_id = '".$idMovie."';");
					#$row2 = $res2->fetch();
					foreach($res2 as $row2) {
						$type = $row2['type'];
						$url = $row2['url'];
						if (!empty($url)) {
							#logc( thumbnailHash($url) );
							#logc( "Hash: ".$hash );
							#logc( 'getCover: '.$url );
							$cover = getCoverThumb($url, $url, true);
							if ($type == 'poster') { break; }
						}
					}
				}
			}
			
			$path = mapSambaDirs($path);
			if (count($EXCLUDEDIRS) > 0) {
				if (isset($EXCLUDEDIRS[$path]) && $EXCLUDEDIRS[$path] != $mode) {
					continue;
				}
			}

			$fsize = fetchFileSize($idFile, $path, $filename, $filesize, $dbh);
			$moviesize = _format_bytes($fsize);

			$filmname0 = $filmname;
			$titel = $filmname;

			$wasCutoff = false;
			$cutoff = isset($GLOBALS['CUT_OFF_MOVIENAMES']) ? $GLOBALS['CUT_OFF_MOVIENAMES'] : -1;
			if (strlen($filmname) >= $cutoff && $cutoff > 0) {
				$filmname = substr($filmname, 0, $cutoff).'...';
				$wasCutoff = true;
			}

			$pr = strtolower(substr($filmname, 0, 4));
			$pronoms = array('the ', 'der ', 'die ', 'das ');
			for ($prs = 0; $prs < count($pronoms); $prs++) {
				if ($pr == $pronoms[$prs]) {
					$part1 = strtoupper(substr($filmname, 4, 1)).substr($filmname, 5, strlen($filmname));
					$part2 = ', '.substr($filmname, 0, 3);
					$filmname = $part1.$part2;
				}
			}

			if ($gallerymode) {
					$zeilen[$counter][0] = $filmname.($jahr != 0 ? ' ('.$jahr.')' : '');
					$zeilen[$counter][1] = '<a class="fancy_iframe" href="./?show=details&idShow='.$id.'">';
					$zeilen[$counter][2] = $watched;
					$zeilen[$counter][3] = $cover;
					$counter++;

			} else {
				
				$zeilen[$zeile][$zeilenSpalte++] = $filmname;
#counter
				$spalTmp = '<td class="countTD">';
				if ($COVER_OVER_TITLE && !empty($cover)) {
					$spalTmp .= '<a class="fancyimage1" href="'.$cover.'" title="'.$filmname.'">';
				}

				$spalTmp .= '_C0UNTER_';

				if ($COVER_OVER_TITLE && !empty($cover)) {
					$spalTmp .= '</a>';
				}

				if ($admin) {
					$spalTmp .= ' <a class="fancy_movieEdit" href="./nameEditor.php?change=movie&idMovie='.$id.'"><img style="border:0px; height:9px;" src="img/edit-pen.png" title="edit movie" /></a>';
				}

				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#checkbox
				$spalTmp = '<td class="titleTD">';
				$spalTmp .= '<input type="checkbox" name="checkFilme[]" id="opt_'.$id.'" class="checka" value="'.$id.'" onClick="selected(this, true, true, '.$admin.'); return true;">';
#title
				$suffix = '';
				$pos1 = strpos($filename, '.3D.');
				if ( $pos1 == true) {
					$suffix = ' (3D)';
				}
				if ($wasCutoff) {
					$spalTmp .= '<a class="fancy_iframe" href="./?show=details&idShow='.$id.'">'.$filmname.$suffix.'<span class="searchField" style="display:none;">'.$filmname0.'</span></a>';;
				} else {
					$spalTmp .= '<a class="fancy_iframe" href="./?show=details&idShow='.$id.'"><span class="searchField">'.$filmname.$suffix.'</span></a>';;
				}
#seen
				if ($admin) {
					if ($playCount >= 1) {
						$spalTmp .= ' <img src="img/check.png" width=10px; border=0px;>';
						$moviesSeen++;
					} else {
						$moviesUnseen++;
					}

					$moviesTotal++;
				}

				if ($SHOW_TRAILER && !empty($trailer)) {
					$spalTmp .= '<a class="fancy_iframe2" href="'.$ANONYMIZER.$trailer.'"> <img src="img/filmrolle.jpg" width=15px; border=0px;></a>';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#jahr
				$spalTmp = '<td class="yearTD';
				if (!empty($jahr)) {
					$spalTmp .= '">';
					$spalTmp .= '<a href="?show=filme&country=&mode=1&which=year&just='.$jahr.'&name='.$jahr.'" title="filter"><span class="searchField">'.$jahr.'</span></a>';
				} else {
					$spalTmp .= ' centro">-';
				}
				$spalTmp .= '</td>';

				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#bewertung
				$rating = substr($rating, 0, 4);
				$rating = substr($rating, 0, substr($rating, 2, 1) == '.' ? 4 : 3);
				
				$spalTmp = '<td class="ratingTD '.($rating > 0 ? 'righto' : 'centro').'">';
				if (!empty($imdbId)) {
					$spalTmp .= '<a class="openImdb" href="'.$ANONYMIZER.$IMDBFILMTITLE.$imdbId.'">';
				} else {
					$spalTmp .= '<a class="openImdb" href="'.$ANONYMIZER.$FILMINFOSEARCH.$titel.'">';
				}
				
				$spalTmp .= ($rating > 0 ? $rating : '&nbsp;&nbsp;-');
				$spalTmp .= '</a>';
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#artist
				$spalTmp = '<td class="actorTD"';
				$sql2 = "select A.strActor, B.strRole, B.idActor, A.strThumb as actorimage from actorlinkmovie B, actors A where A.idActor = B.idActor and B.idMovie = '$idMovie'";
				$result2 = $dbh->query($sql2);
				$firstartist = '';
				$firstId = '';
				$artist = '';
				$artistinfo = '';
				$actorpicURL = '';
				foreach($result2 as $row2) {
					$artist = $row2['strActor'];
					$idActor = $row2['idActor'];
					$actorpicURL = $row2['actorimage'];

					if (empty($firstartist)) {
						$firstartist = $artist;
						$firstId = $idActor;
						break;
					}
				}

				$actorimg = getActorThumb($firstartist, $actorpicURL, false);
				if (!file_exists($actorimg) && !empty($firstId) && $existArtTable) {
					$res3 = $dbh->query("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';");
					$row3 = $res3->fetch();
					$url = $row3['url'];
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				}				

				if (!empty($firstartist) && !empty($firstId)) {
					$spalTmp .= '>';
					$spalTmp .= '<a class="openIMDB filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstartist.'">[i] </a>';

					$spalTmp .= '<a href="?show=filme&country=&mode=1&which=artist&just='.$firstId.'&name='.$firstartist.'"';
					if (file_exists($actorimg)) {
						$spalTmp .= ' class="hoverpic" rel="'.$actorimg.'" title="'.$firstartist.'"';
					} else {
						$spalTmp .= 'title="filter"';
					}

					$spalTmp .= '><span class="searchField">'.$firstartist.'</span></a>';
				} else {
					$spalTmp .= ' style="padding-left:40px;">-';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#genre
				$spalTmp = '<td class="genreTD"';
				$genres = explode("/", $genres);
				$genre = count($genres) > 0 ? trim($genres[0]) : '';
				$genreId = -1;

				if (!empty($genre)) {
					$spalTmp .= '>';
					$genre = ucwords(strtolower($genre));
					if (isset($idGenre[$genre])) {
						$genreId = $idGenre[$genre][0];
						$idGenre[$genre][1] = $idGenre[$genre][1] + 1;
					}

					if (($genreId != -1) && (!isset($_just) || empty($_just) || $_which != 'genre')) {
						$spalTmp .= '<a href="?show=filme&country=&mode=1&which=genre&just='.$genreId.'&name='.$genre.'" title="filter"><span class="searchField">'.$genre.'</span></a>';
					} else {
						$spalTmp .= '<span class="searchField">'.$genre.'</span>';
					}
				} else {
					$spalTmp .= ' style="padding-left:20px;">-';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;
#regie
				$spalTmp = '<td class="direcTD"';
				$sql3 = "select A.strActor, B.idDirector, A.strThumb as actorimage from directorlinkmovie B, actors A where B.idDirector = A.idActor and B.idMovie = '$idMovie'";
				$result3 = $dbh->query($sql3);
				$firstdirector = '';
				$firstId = '';
				$artistinfo = '';
				$actorpicURL = '';
				foreach($result3 as $row3) {
					$artist = $row3['strActor'];
					$idActor = $row3['idDirector'];
					$actorpicURL = $row3['actorimage'];

					if (empty($firstdirector)) {
						$firstdirector = $artist;
						$firstId = $idActor;
						break;
					}
				}
				
				$actorimg = getActorThumb($firstdirector, $actorpicURL, false);
				if (!file_exists($actorimg) && !empty($firstId) && $existArtTable) {
					$res3 = $dbh->query("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '".$firstId."';");
					$row3 = $res3->fetch();
					$url = $row3['url'];
					if (!empty($url)) {
						$actorimg = getActorThumb($url, $url, true);
					}
				}
				
				if (!empty($firstdirector) && !empty($firstId)) {
					$spalTmp .= '>';
					$spalTmp .= '<a class="openImdb filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$firstdirector.'">[i] </a>';
					
					$spalTmp .= '<a href="?show=filme&country=&mode=1&which=regie&just='.$firstId.'&name='.$firstdirector.'"';
					if (file_exists($actorimg)) {
						$spalTmp .= ' class="hoverpic" rel="'.$actorimg.'" title="'.$firstdirector.'"';
					} else {
						$spalTmp .= 'title="filter"';
					}

					$spalTmp .= '><span class="searchField">'.$firstdirector.'</span></a>';
				} else {
					$spalTmp .= ' style="padding-left:40px;">-';
				}
				$spalTmp .= '</td>';
				$zeilen[$zeile][$zeilenSpalte++] = $spalTmp;

#filesize
				$zeilen[$zeile][$zeilenSpalte++] = '<td class="fsizeTD" align="right">'.$moviesize.'</td>';
				
				$zeile++;
			} // else gallerymode == 1

			if ($newmode && ++$counter2 >= $newAddedCount) {
				break;
			}

		} //foreach
		
		if (!$newmode && empty($sort)) {
			sort($zeilen);
		}

		if ($gallerymode) {
			generateForm();

			$iElems = count($zeilen);

			echo "<tr>";
			$spread = -1;
			$thumbsAddedInRow = 0;
			for ($t = 0; $t < $iElems; $t++) {
				if ($t % $elemsInRow == 0 && $t > 0) {
					$thumbsAddedInRow = 0;
					echo "\n</tr>\r\n<tr>";

					if (($iElems - $t) < $elemsInRow) {
						$spread = $elemsInRow - ($iElems - $t);
						if ($spread > 0) {
							$matrix = getMatrix($iElems - $t);
						}
					}
				}

				if (isset($matrix)) {
					$thumbsAddedInRow = echoEmptyTdIfNeeded($matrix, $thumbsAddedInRow, $elemsInRow);
				}

				echo "\n\t";
				echo '<td class="galleryTD">';
				echo $zeilen[$t][1];
				$covImg = (!empty($zeilen[$t][3]) ? $zeilen[$t][3] : './img/nothumb.png');
				if ($ENCODE) { $covImg = base64_encode_image($covImg); }
				echo '<div class="galleryCover" style="background:url('.$covImg.') #FFFFFF no-repeat;" title="'.$zeilen[$t][0].'">';
				if ($admin && $zeilen[$t][2] >= 1) {
					echo '<span class="gallerySpan"><img src="img/check.png" class="galleryImage"></span>';
				}
				echo '</div></a></td>';

				$thumbsAddedInRow++;

				if (isset($matrix)) {
					$thumbsAddedInRow = echoEmptyTdIfNeeded($matrix, $thumbsAddedInRow, $elemsInRow);
				}
			}
			echo "\n";
			echo '</tr>';
			echo "\n";
		} else {
			$titleInfo = '';
			if ($admin && !$gallerymode && (!empty($unseen) || $unseen == 3)) {
				$percUnseen = '';
				$percSeen = '';

				if ($moviesTotal > 0 && $moviesTotal >= $moviesSeen && $moviesTotal >= $moviesUnseen) {
					$percUnseen = round($moviesUnseen / $moviesTotal * 100, 0).'%';
					$percSeen = round($moviesSeen / $moviesTotal * 100, 0).'%';
				}
			}

			echo "\t";
			echo '<tr><th class="th0"> </th>';
			echo '<th class="th4"><input type="checkbox" id="clearSelectAll" name="clearSelectAll" title="clear/select all" onClick="clearSelectBoxes(this, '.$admin.'); return true;"><a style="font-weight:bold;" href="?sort=">Title</a>'.$titleInfo.'</th>';
			echo '<th class="th0"><a style="font-weight:bold;'.(!empty($sort) && ($sort=='jahr' || $sort=='jahra') ? 'color:red;' : '').'" href="?sort='.($sort=='jahr' ? 'jahra' : 'jahr').'">Year</a></th>';
			echo '<th class="th1"><a style="font-weight:bold;'.(!empty($sort) && ($sort=='rating' || $sort=='ratinga') ? 'color:red;' : '').'" href="?sort='.($sort=='rating' ? 'ratinga' : 'rating').'">Rating</a></th>';
			echo '<th class="th2">Actor</th>';
			echo '<th class="th2">Genre</th>';
			echo '<th class="th2">Director</th>';
			echo '<th class="th5"><a style="font-weight:bold;'.(!empty($sort) && ($sort=='size' || $sort=='sizea') ? 'color:red;' : '').'" href="?sort='.($sort=='size' ? 'sizea' : 'size').'">Size</a></th></tr>';
			echo "\r\n";

			generateForm();

			$zeilenCount = count($zeilen);
			for ($z = 0; $z < $zeilenCount; $z++) {
				echo "\t".'<tr class="searchFlag">';
				$zeile = $zeilen[$z];
				for ($sp = 1; $sp < count($zeile); $sp++) {
					$spalte = str_replace('_C0UNTER_', $z+1, $zeile[$sp]);
					if ($z == $zeilenCount-1) {
						if (substr_count($spalte, '<td>') == 1) {
							$spalte = str_replace('<td>', '<td class="bottomTD">', $spalte);

						} else if (substr_count($spalte, 'class') >= 1) {
							$spalte = str_replace('<td class="', '<td class="bottomTD ', $spalte);

						} else {
							$spalte = str_replace('<td ', '<td class="bottomTD" ', $spalte);
						}
					}
					echo $spalte;
				}
				echo '</tr>'."\r\n";
			}
		} // if ($galleryMode)
		
		$dbh->commit();

	} catch(PDOException $e) {
		$dbh->rollBack();
		echo $e->getMessage();
	}
} // function createTable

function echoEmptyTdIfNeeded($matrix, $thumbsAddedInRow, $elemsInRow) {
	while ($thumbsAddedInRow < $elemsInRow && $matrix[$thumbsAddedInRow] == 0) {
		echo "\n\t";
		echo '<td class="galleryEmptyTD">&nbsp;</td>';

		$thumbsAddedInRow++;
	}

	return $thumbsAddedInRow;
}
/*          FUNCTIONS          */
?>