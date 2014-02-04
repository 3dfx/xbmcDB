<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";
	
	function getNeededColspan($res, $spalte) {
		$colspan = 1;
		if ($spalte == 5) {
			return $colspan;
		}
		
		for ($j = $spalte; $j < count($res); $j++) {
			$val = trim($res[$j]);
			if ($val != null || $val != '') {
				break;
			}
			
			$colspan++;
		}
		
		if ($spalte == 1) {
			return ($colspan > 5 ? 5 : $colspan);
		}
		
		return $colspan;
	}
	
	function getCovers($fnam, $cover, $idMovie) {
		$existArtTable = $GLOBALS['existArtTable'];
		
		$res = array();
		if (file_exists(getCoverMid($fnam, $cover, false))) {
			$res[0] = getCoverMid($fnam, $cover, false); //cover
			$res[1] = getCoverBig($fnam, $cover, false); //cover_big

		} else if ($existArtTable) {
			$res2 = querySQL("SELECT url,type FROM art WHERE media_type = 'movie' AND (type = 'poster' OR type = 'thumb') AND media_id = '".$idMovie."';");
			foreach($res2 as $row2) {
				$type = $row2['type'];
				$url  = $row2['url'];
				if (!empty($url)) {
					$res[0] = getCoverMid($url, $url, true); //cover
					$res[1] = getCoverBig($url, $url, true); //cover_big
					if ($type == 'poster') { break; }
				}
			}
		}

		return $res;
	}
	
	function getFanartCover($fnam, $idMovie) {
		$existArtTable = $GLOBALS['existArtTable'];
		
		$crc = thumbnailHash($fnam);
		$fanart = "./img/Thumbnails/Fanart/".$crc.".jpg";
		
		$fanartExists = file_exists($fanart);
		if ($fanartExists) {
			$ftime = '';
			try {
				$ftime = filemtime($fanart);
			} catch (Exception $e) { }

			$fanartThumb = "./img/fanart/".$crc."-fanart_".$ftime.".jpg";
			return getFanart0($fanart, $fanartThumb);
			
		} else if ($existArtTable) {
			$row2 = fetchFromDB("SELECT url FROM art WHERE media_type = 'movie' AND type = 'fanart' AND media_id = '".$idMovie."';");
			$url  = $row2['url'];
			if (!empty($url)) {
				return getFanart($url, $url, true);
			}
		}
		
		return null;
	}
?>

<head>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
	<script type="text/javascript" src="./template/js/jquery.knob.js"></script>
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="class.css" />
	<style> body {background-color:#<?php echo ($DETAILFANART) ? 'FFFFFF' : 'D9E8FA'; ?>;} </style>
	<script Language="JavaScript">
		var flag = true;

		function spoilIt() {
			$( '#spoiler' ).hide();
			$( '#movieDescription' ).show();
		}

		function showRestInhalt() {
			var inhaltDots = document.getElementById('inhaltDots');
			var inhaltRest = document.getElementById('inhaltRest');

			inhaltDots.style.display = 'none';
			inhaltRest.style.display = 'inline';
		}

		function showHiddenTRs(id, name, flag) {
			var rows = document.getElementsByTagName('TR');
			for (var i = 0; i < rows.length; i++) {
				var row = rows[i];
				if (row == null || row.getAttribute('name') != name) {
					continue;
				}

				row.style.display = flag ? '' : 'none';
			}

			var doTr = document.getElementById(id);
			doTr.style.display = flag ? 'none' : '';

			if (name != 'genres') {
				this.flag = !flag;
			}
		}

		function Show_Image(IDS) {
		  document.getElementById(IDS).style.display = 'block';
		}
		
		function Hide_Image(IDS) {
		  document.getElementById(IDS).style.display = 'none';
		}
		
		$(document).ready(function() { $('.knob-dyn').knob(); });
	</script>
</head>


<?php
	$ANONYMIZER = $GLOBALS['ANONYMIZER'];
	$IMDB = $GLOBALS['IMDB'];
	$PERSONINFOSEARCH = $GLOBALS['PERSONINFOSEARCH'];
	$FILMINFOSEARCH = $GLOBALS['FILMINFOSEARCH'];
	$DETAILFANART = isset($GLOBALS['DETAILFANART']) ? $GLOBALS['DETAILFANART'] : true;
	$ENCODE = isset($GLOBALS['ENCODE_IMAGES']) ? $GLOBALS['ENCODE_IMAGES'] : false;
	
	$id = isset($_SESSION['idShow']) ? $_SESSION['idShow'] : null;
	if (empty($id)) { die('<br/>no param!<br/>'); }
		
		$isAdmin = isAdmin();
		$idFile  = -1;
	
		$existArtTable = existsArtTable();
		
		$SQL = "SELECT c00, c01, c02, idMovie, c07 AS jahr, c08 AS thumb, c09 AS imdbId, B.strFilename AS filename, A.c19 AS trailer, c04, c05, c11, c14, c16, ".
			"C.strPath AS path, D.filesize, A.idFile, B.playCount AS playCount ".
			"FROM movie A, files B, path C LEFT JOIN fileinfo D ON A.idFile = D.idFile WHERE idMovie = $id AND A.idFile = B.idFile AND C.idPath = B.idPath ";
		$row = fetchFromDB($SQL, false);
		
		if (empty($row)) { die('not found...'); }
		
		$SQL2     = "SELECT B.strActor, A.strRole, A.idActor, B.strThumb AS actorimage FROM actorlinkmovie A, actors B WHERE A.idActor = B.idActor AND A.idMovie = '$id'";
		$result2  = querySQL($SQL2);
		$result2_ = querySQL($SQL2);
		
		$idFile   = $row['idFile'];
		$result3  = getStreamDetails($idFile);
		
		$SHOW_TRAILER = isset($GLOBALS['SHOW_TRAILER']) ? $GLOBALS['SHOW_TRAILER'] : false;
		$size         = $row['filesize'];
		$idMovie      = $row["idMovie"];
		$path         = $row["path"];
		$filename     = $row['filename'];
		$watched      = $row['playCount'];
		$fnam         = $path.$filename;
		$idMovie      = $row['idMovie'];
		$titel        = trim($row['c00']);
		$orTitel      = trim($row['c16']);
		$jahr         = $row['jahr'];
		$inhalt       = $row['c01'];
		
		$percent;
		$timeAt;
		$pausedAt;
		$timeTotal;
		if ($isAdmin) {
			$result    = fetchFromDB("SELECT timeInSeconds AS timeAt, totalTimeInSeconds AS timeTotal FROM bookmark WHERE idFile = '".$idFile."';");
			$timeAt    = $result['timeAt'];
			$timeTotal = $result['timeTotal'];
			$pausedAt  = getPausedAt($timeAt);
			$percent   = round($timeAt / $timeTotal * 100, 0);
		}
		
  		$fanart    = '';
  		$covers    = getCovers($fnam, '', $idMovie);
  		$cover     = getImageWrap($covers[0], $idMovie, 'movie', 1);
  		$cover_big = getImageWrap($covers[1], $idMovie, 'movie', 2);
		
		$fanartExists = false;
		if ($DETAILFANART) {
			$fanart = getFanartCover($fnam, $idMovie);
			$fanartExists = !empty($fanart);
			wrapItUp('fanart', $idMovie, $fanart);
		}
?>
<body>
<?php
		$fanart  = getImageWrap($fanart, $idMovie, 'fanart', 0);
		
		$pos1 = strpos($filename, '.3D.');
		if ( $pos1 == true) {
			$titel .= ' (3D)';
		}
		
		if ($DETAILFANART && $fanartExists || true) {
			if ($ENCODE) { $fanart = base64_encode_image($fanart); }
			echo '<div class="fanartBg"><img src="'.$fanart.'" style="width:100%; height:100%;"/></div>';
			echo "\r\n";
		}
		
		if (!empty($cover)) {
			if ($ENCODE) {
				$cover     = base64_encode_image($cover);
				$cover_big = base64_encode_image($cover_big);
			}
			echo "\r\n";
			echo '<div class="coverDiv">';
			echo '<div class="innerCoverDiv">';
			echo "\r\n";
			echo '<img class="innerCoverImg" src="'.$cover.'" href="'.$cover_big.'" title="'.$titel.'" style="width:250px;">';
			echo "\r\n";
			echo '</div>';
			echo "\r\n";
			echo '</div>';
			echo "\r\n";
		}
		
		echo '<div class="moviebox2">';
		echo '<div class="movieTitle">';
		echo '<a style="font-size:26px; font-weight:bold;" class="openImdbDetail" href="'.$ANONYMIZER.$IMDBFILMTITLE.$row['imdbId'].'">'.$titel.' ('.$jahr.')'.'</a>';
		$trailer = $row['trailer'];
		if ($SHOW_TRAILER && !empty($trailer)) {
			echo ' <sup><a class="fancy_iframe3" href="'.$ANONYMIZER.$trailer.'"><img src="img/filmrolle.png" style="height:22px; border:0px; vertical-align:middle;"></a></sup>';
			echo "\r\n";
		}
		
		$cmpTitel = strtolower($titel);
		$cmpTitel = str_replace('and', '', $cmpTitel);
		$cmpTitel = str_replace('und', '', $cmpTitel);
		$cmpTitel = str_replace('&', '', $cmpTitel);
		
		$cmpOrTit = strtolower($orTitel);
		$cmpOrTit = str_replace('and', '', $cmpOrTit);
		$cmpOrTit = str_replace('und', '', $cmpOrTit);
		$cmpOrTit = str_replace('&', '', $cmpOrTit);
		$orTitleShown = false;
		if (!empty($cmpOrTit) && $cmpTitel != $cmpOrTit) {
			$orTitleShown = true;
			echo "\r\n";
			echo '<span class="originalTitle">';
			echo '<br/>';
			echo '<b>Original title</b>: '.$row['c16'];
			echo '</span>';
			echo "\r\n";
		}
		
		if ($isAdmin && !empty($percent)) {
			echo '<span class="epCheckSpan" title="'.$pausedAt.' ('.$percent.'%)" style="top:'.($orTitleShown ? '-30' : '0').'px; right:-8px;">';
			#if ($watched > 0) { echo '<img src="./img/check.png" class="galleryImage thumbCheck" style="position:relative; bottom:4px;" title="watched" />'; }
			if (!empty($percent)) {
				echo '<input type="text" class="knob-dyn" data-width="25" data-height="25" data-fgColor="#6CC829" data-angleOffset="180" data-thickness=".4" data-displayInput="false" data-readOnly="true" value="'.$percent.'" style="display:none;" />';
			}
			echo '</span>';
		}
		echo '</div>';
		echo "\r\n";
		
		if (!empty($inhalt)) {
			$MAXLEN = isset($GLOBALS['MAXMOVIEINFOLEN']) ? $GLOBALS['MAXMOVIEINFOLEN'] : 1000;
			$tmp_inhalt = $inhalt;
			if (strlen($tmp_inhalt) > $MAXLEN) {
				$inhalt  = '<span id="inhaltShort">';
				$inhalt .= substr($tmp_inhalt, 0, $MAXLEN-3);
				$inhalt .= '<span id="inhaltDots" class="moreDots" onclick="showRestInhalt();" title="mehr...">...</span>';
				$inhalt .= '</span>';
				$inhalt .= '<span id="inhaltRest" style="display:none;">';
				$inhalt .= substr($tmp_inhalt, $MAXLEN-3, strlen($tmp_inhalt));
				$inhalt .= '</span>';
			}
			
			$spProtect = isset($GLOBALS['SPOILPROTECTION']) ? $GLOBALS['SPOILPROTECTION'] : true;
			
			if (!$spProtect || ($isAdmin && $watched >= 1)) {
				echo '<span id="movieDescription">';
				echo $inhalt;
				echo '</span>';

			} else if ($spProtect || ($spProtect && $isAdmin && (empty($watched)))) {
				echo '<div id="spoiler" style="padding-top:15px; color:red; cursor:pointer;" onclick="spoilIt(); return false;"><u><i><b>spoil it!</b></i></u></div>';
				echo '<span id="movieDescription" style="display:none;">';
				echo $inhalt;
				echo '</span>';
			}
			
		} else {
			echo '<i>Keine Beschreibung vorhanden.</i>';
		}
		
		echo "\r\n";
		echo '<div style="width:700px; height:2px;"></div>';
		echo "\r\n";
		
		$size1     = '';
		$minutes   = '';
		$hours     = '';
		$rating    = '';
		$stimmen   = '';
		$ar        = '';
		$width     = '';
		$height    = '';
		$vCodec    = '';
		$genre     = array();
		$aCodec    = array();
		$aChannels = array();
		$aLang     = array();
		$sLang     = array();
		$res       = array();
		$run       = 0;
		
		if ($row['c00'] != $row['c16']) {
			$orTitle = $row['c16'];
			$run = 1;
		}
		if (!empty($row['c11'])) {
			$secs = $row['c11'];
			$minutes = floor($secs/60);
			$hours = floor($minutes/60).':'.sprintf ("%02d", $minutes % 60).'\'';
			$minutes = $minutes.'\'';
			$run = 1;
		}
		if (substr($row['c05'], 0, 1) != "0") {
			$rating = substr($row['c05'], 0, 3);
			$stimmen = $row['c04'];
			$run = 1;
		}
		if (!empty($row['c14'])) {
			$genre = explode(" / ", $row['c14']);
			$run = 1;
		}
		
		if (!empty($size)) {
			$size1 = _format_bytes($size);
		}
		
		foreach($result3 as $row3) {
			$tmp = $row3['fVideoAspect'];
			if ($tmp != null) {
				if ($tmp != '') {
					$tmp  = round($tmp, 2);
					$tmp .= (strlen($tmp) < 4 ? '0' : '').':1';
				}
				$ar = $tmp;
			}
			
			$tmp = $row3['iVideoWidth'];
			if (!empty($tmp)) { $width = $tmp; }

			$tmp = $row3['iVideoHeight'];
			if (!empty($tmp)) { $height = $tmp; }

			$tmp = $row3['strVideoCodec'];
			if (!empty($tmp) && !isDemo()) { $vCodec = strtoupper($tmp); }

			$tmp = $row3['strAudioCodec'];
			if (!empty($tmp) && !isDemo()) { $aCodec[count($aCodec)] = strtoupper($tmp); }

			$tmp = $row3['iAudioChannels'];
			if (!empty($tmp) && !isDemo()) { $aChannels[count($aChannels)] = $tmp; }

			$tmp = $row3['strAudioLanguage'];
			if (!empty($tmp) && !isDemo()) { $aLang[count($aLang)] = strtoupper($tmp); }
			
			$tmp = $row3['strSubtitleLanguage'];
			if (!empty($tmp) && !isDemo()) { $sLang[count($sLang)] = strtoupper($tmp); }
			
			$run++;
		}
		
		$sqlG = "select * from genre";
		$resultG = querySQL($sqlG);
		$idGenre = array();
		foreach($resultG as $rowG) {
			$str = ucwords(strtolower(trim($rowG['strGenre'])));
			if ($str == null || $str == '') {
				continue;
			}

			$idGenre[$str] = $rowG['idGenre'];
		}
		
		$max = max(max(count($aCodec), count($aChannels), count($aLang), count($sLang), count($genre)), 1);
		$spalten = 11;
		for ($g = 0; $g < $max; $g++) {
			for ($x = 0; $x < $spalten; $x++) {
				$res[$g][$x] = null;
			}

			if ($g < count($genre)) {
				$genreId = -1;
				$strGenre = ucwords(strtolower(trim($genre[$g])));
				if ($strGenre != null && $strGenre != '') {
					if (isset($idGenre[$strGenre])) {
						$genreId = $idGenre[$strGenre];
					}
				}

				if ($genreId != -1) {
					$res[$g][4] = '<a href="?show=filme&which=genre&just='.$genreId.'&name='.$strGenre.'" target="_parent" class="detailLink" title="filter">'.$strGenre.'</a>';
				} else {
					$res[$g][4] = $strGenre;
				}
			}

			if ($g < count($aLang)) {
				$res[$g][7] = postEditLanguage($aLang[$g]);
			}

			if ($g < count($aCodec)) {
				$res[$g][8] = postEditCodec($aCodec[$g]);
			}

			if ($g < count($aChannels)) {
				$res[$g][9] = postEditChannels($aChannels[$g]);
			}
			
			if ($g < count($sLang)) {
				$res[$g][10] = postEditLanguage($sLang[$g], false);
			}
		}
		
		$res[0][0] = $hours;
		$res[0][1] = $rating;
		$res[0][2] = $stimmen;
		$res[0][3] = isset($jahr) ? '<a href="?show=filme&country=&mode=1&which=year&just='.$jahr.'&name='.$jahr.'" target="_parent" class="detailLink" title="filter">'.$jahr.'</a>' : '';
		if (!empty($width) && !empty($height)) {
			$res[0][5] = $width.'x'.$height;
		}
		$res[0][6] = $vCodec;
		
		$res[1][0] = $minutes;
		$res[1][5] = $ar;
		
		if ($run > 0) {
			echo '<div class="stream">';
			echo '<table cellspacing="0" class="streaminfo">';
			echo "\r\n";
			echo '<tr>';
			echo '<th>Duration</th><th>Rating</th><th>Votes</th><th>Year</th><th class="streaminfoGenreTH">Genre</th>';
			echo '<th class="streaminfoAV'.(count($aLang) > 0 ? '' : '2').'" colspan="2">Video</th>';
			if (!empty($aCodec)) {
				echo '<th class="streaminfoAV'.(count($aLang) > 0 ? '' : '3').'" colspan="3">Audio</th>';
			} else { $spalten--; }
			if (!empty($sLang)) {
				echo '<th class="streaminfoLasTD">Sub</th>';
			} else { $spalten--; }
			echo '</tr>';
			echo "\r\n";
			echo '<tr class="abstand"><td colspan="10"></td></tr>';
			echo "\r\n";

			$zeilen = 0;
			$hiddenGenres = count($genre)-2;
			$hiddenSubs = count($sLang)-2;
			for ($i = 0; $i < $max; $i++) {
				echo '<tr'.($i >= 2 && ($hiddenGenres > 1 || $hiddenSubs > 1) ? ' name="genres" style="display:none;"' : '').'>';
				$emptyGenreFilled = false;
				for ($j = 0; $j < count($res[$i]); $j++) {
					$val = $res[$i][$j];
					if (count($genre) == 0 && $j == 4) {
						if ($emptyGenreFilled) {
							continue;
						}

					} else if ($val == null && $j > 0 && $j != 5) {
						continue;
					}

					echo '<td';
					$colspan = getNeededColspan($res[$i], $j+1);
					if ($j == 3 && count($genre) == 0) {
						$colspan = 1;
					}
					if ($colspan > 1) {
						echo ' colspan="'.$colspan.'"';
					}

					switch ($j) {
						case 0:
							if ($i > 0 && $i >= count($genre)) {
								echo ' class="streaminfoGenre"';
								$emptyGenreFilled = true;
							}
							break;

						case 2:
							if ($colspan > 1) {
								echo ' class="streaminfoGenre"';
								$emptyGenreFilled = true;
							}
							break;

						case 3:
							if ($colspan > 1) {
								echo ' class="streaminfoGenre"';
								$emptyGenreFilled = true;
							}
							break;

						case 4:
							if (!$emptyGenreFilled) {
								echo ' class="streaminfoGenre"';
							}
							break;

						case 5:
							echo ' class="streaminfoAV"';
							break;

						case 7:
							echo ' class="streaminfoAV"';
							break;

						case 9:
							echo ' class="'.(empty($sLang) ? 'streaminfoLasTD' : 'streaminfoAV').'"';
							break;
							
						case 10:
							echo ' class="streaminfoLasTD"';
							break;
					}

					echo '>'.($val == null || $val == '' ? '&nbsp;' : $val);
					echo '</td>';
				}
				echo '</tr>';
				$zeilen++;
			}
			
			if ($zeilen > 2 && ($hiddenGenres >= 1 || $hiddenSubs >= 1)) {
				if ($hiddenGenres > 1) {
					echo '<tr id="genreDots"><td colspan="4"></td><td class="streaminfoGenre lefto">';
					echo '<span class="moreDots" onclick="showHiddenTRs(\'genreDots\', \'genres\', true);" title="mehr...">...</span>';
					echo '</td>';
					echo '<td colspan="'.($hiddenSubs >= 1 ? 5 : 4).'"></td>';
					if ($hiddenSubs >= 1) {
						echo '<td><span class="moreDots" onclick="showHiddenTRs(\'genreDots\', \'genres\', true);" title="mehr...">...</span></td>';
					}
					echo '</tr>';
					echo "\r\n";
					
				} else if ($hiddenSubs > 1) {
					echo '<tr id="genreDots"><td colspan="4"></td><td class="streaminfoGenre lefto">';
					if ($hiddenGenres >= 1) {
						echo '<span class="moreDots" onclick="showHiddenTRs(\'genreDots\', \'genres\', true);" title="mehr...">...</span>';
					}
					echo '</td>';
					echo '<td colspan="5"></td>';
					echo '<td><span class="moreDots" onclick="showHiddenTRs(\'genreDots\', \'genres\', true);" title="mehr...">...</span></td>';
					echo '</tr>';
					echo "\r\n";
				}
			}
			
			$smb = (substr($filename, 0, 6) == 'smb://');
			$stacked = (substr($filename, 0, 8) == 'stack://');
			if ($smb || $stacked) {
				$filename = '';
			}
			
			
			echo '<tr class="abstand"><td colspan="10"></td></tr>';
			echo '<tr><td class="streaminfoLLine streaminfoLasTD" colspan="'.$spalten.'">';
			if (!isDemo()) {
			echo '<span class="filename lefto"'.($isAdmin ? ' title="'.$path.'"' : '').'>'.encodeString($filename).'</span>';
			echo '<span class="filesize righto" title="'.formatToDeNotation($size).'">'.$size1.'</span>';
			}
			echo '</td></tr>';
			echo "\r\n";
			
			$SQL_SET = 'SELECT S.strSet, S.idSet FROM sets S, movie M WHERE M.idSet = S.idSet AND M.idMovie = '.$id.';';
			$result = querySQL($SQL_SET);
			if (!empty($result)) {
				echo '<tr><td class="streaminfoLasTD" style="padding-top:10px;" colspan="'.$spalten.'">';
				$row = $result->fetch();
				$set = $row['strSet'];
				$idSet = $row['idSet'];
				$isSet = ($set != null && $set != '');

				$href = ($isSet ? '<b>'.$set.'</b>' : '<i>Not in any set!</i>');
				if ($isSet) {
					$href = '<a href="?show=filme&which=set&just='.$idSet.'&name='.$set.'" target="_parent">'.$href.'</a>';
				}
				if ($isAdmin) {
					$href .= ' <a class="fancy_movieset" href="./changeMovieSet.php?idMovie='.$id.'"><img style="border:0px; height:9px;" src="img/edit-pen.png" title="change set" /></a>';
				}

				echo '<span class="filename lefto">'.$href.'</span>';
				echo '</td></tr>';
				echo "\r\n";
			}
			
			echo '</table>';
			echo '</div>';
			echo "\r\n";
		}
		
        	$artist  = '';
		$actors  = 0;
		$actCnt  = 0;
		$counter = 1;
		$acLimit = 5;
		$schauspTblOut = array();
		foreach($result2_ as $row2) { $actCnt++; }
		if ($actCnt-1 == $acLimit) { $acLimit++; }
		foreach($result2 as $row2) {
			$artist      = $row2['strActor'];
			$idActor     = $row2['idActor'];
			$actorpicURL = $row2['actorimage'];
			
			$actorimg = getActorThumb($artist, $actorpicURL, false);
			if (!file_exists($actorimg) && $existArtTable) {
				// ayar
				$row3 = fetchFromDB("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '$idActor';");
				$url  = $row3['url'];
				if (!empty($url)) {
					$actorimg = getActorThumb($url, $url, true);
				}
			}
			
			wrapItUp('actor', $idActor, $actorimg);
			
			$schauspTblOut[$actors]  = '<tr'.($actors >= $acLimit ? ' name="artists" style="display:none;"' : '').'>';
			$schauspTblOut[$actors] .= '<td class="art">';
			$schauspTblOut[$actors] .= '<a class="openImdbDetail filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$artist.'">[i] </a>';
			$schauspTblOut[$actors] .= '<a href="?show=filme&which=artist&just='.$idActor.'&name='.$artist.'" target="_parent" ';
			if (file_exists($actorimg)) {
				$schauspTblOut[$actors] .= ' class="hoverpic" rel="'.getImageWrap($actorimg, $idActor, 'actor', 0).'" title="'.$artist.'"';
			} else {
				 $schauspTblOut[$actors] .= 'title="filter"';
			}
			$schauspTblOut[$actors] .= '>'.$artist.'</a>';

			$schauspTblOut[$actors] .= '</td>';
			$schauspTblOut[$actors] .= '<td class="role">';
			if (!empty($row2['strRole'])) {
				$strRole = $row2['strRole'];
				$schauspTblOut[$actors] .= str_replace('/', ' / ', $strRole);
			} else {
				$schauspTblOut[$actors] .= '&nbsp;';
			}
			$schauspTblOut[$actors] .= '</td>';
			$schauspTblOut[$actors] .= '</tr>';
			$schauspTblOut[$actors] .= "\r\n";

			$actors++;
		}

		if ($actors > 0) {
			echo '<div class="artitabbox">';
			echo "\r\n";
			echo '<table cellspacing="0" class="artists">';
			echo "\r\n";
			echo '<tr><th colspan="2">';
			echo '<span class="moreDots" onclick="showHiddenTRs(\'doTr\', \'artists\', flag);">Actors</span>';
			echo '</th></tr>';
			echo '<tr class="abstand"><td colspan="2"></td></tr>';
			echo "\r\n";
			for ($i = 0; $i < count($schauspTblOut); $i++) {
				echo $schauspTblOut[$i];
			}
			
			if ($actors > $acLimit) {
				echo '<tr id="doTr"><td colspan="2">';
				echo '<span class="moreDots" onclick="showHiddenTRs(\'doTr\', \'artists\', true);" title="mehr...">...</span>';
				echo '</td></tr>';
				echo "\r\n";
			}
			echo '</table>';
			echo "\r\n";
		}
		
		echo '</div>';
		echo "\r\n";

	unset( $_SESSION['show'], $_SESSION['idShow'] );
?>
