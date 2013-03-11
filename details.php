<?php
	include_once "auth.php";
	include_once "check.php";
	
	include_once "template/functions.php";
	include_once "template/matrix.php";
	include_once "template/config.php";
	include_once "globals.php";

	function getNeededColspan($res, $spalte) {
		$colspan = 1;
		if ($spalte == 5) {
			return $colspan;
		}

		for ($j = $spalte; $j < count($res); $j++) {
			$val = $res[$j];
			if (!empty($val)) {
				break;
			}

			$colspan++;
		}

		if ($spalte == 1) {
			return ($colspan > 5 ? 5 : $colspan);
		}

		return $colspan;
	}
?>

<head>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
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

	$idFile = 0;

	try {
		error_reporting(E_ALL);
		$dbh = new PDO($db_name);
		$existArtTable = existsArtTable($dbh);
		
		$sql = "SELECT c00, c01, c02, idMovie, c07 as jahr, c08 as thumb, c09 as imdbId, B.strFilename as filename, A.c19 as trailer, c04, c05, c11, c14, c16, ".
			"C.strPath as path, D.filesize, A.idFile, B.playCount as playCount ".
			"FROM movie A, files B, path C LEFT JOIN fileinfo D on A.idFile = D.idFile WHERE idMovie = $id and A.idFile = B.idFile and C.idPath = B.idPath ";
		$result = $dbh->query($sql);	
		$row = $result->fetch();

		if (empty($row)) { die('not found...'); }

		$sql2 = "select B.strActor, A.strRole, A.idActor, B.strThumb as actorimage from actorlinkmovie A, actors B where A.idActor = B.idActor and A.idMovie = '$id'";
		$result2 = $dbh->query($sql2);
		$result2_ = $dbh->query($sql2);
		
		$idFile = $row['idFile'];
		$result3 = getStreamDetails($idFile);

  		$fanart = '';
  		$cover = '';
  		$cover_big = '';

		// thumb from local-cache
		$USECACHE = isset($GLOBALS['USECACHE']) ? $GLOBALS['USECACHE'] : true;
		$SHOW_TRAILER = isset($GLOBALS['SHOW_TRAILER']) ? $GLOBALS['SHOW_TRAILER'] : false;
		$size = $row['filesize'];
		$path = $row["path"];
		$idMovie = $row["idMovie"];
		$filename = $row['filename'];
		$watched = $row['playCount'];
		$fnam = $path.$filename;

		$fanartExists = false;
		if ($USECACHE) {
			if (file_exists(getCoverMid($fnam, $cover, false))) {
				$cover_big = getCoverBig($fnam, $cover, false);
				$cover = getCoverMid($fnam, $cover, false);
				
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
						$cover_big = getCoverBig($url, $url, true);
						$cover = getCoverMid($url, $url, true);
						if ($type == 'poster') { break; }
					}
				}
			}

			if ($DETAILFANART) {
				$crc = thumbnailHash($fnam);
				#$fanart = "./img/Thumbnails/Video/Fanart/".$crc.".tbn";
				$fanart = "./img/Thumbnails/Fanart/".$crc.".jpg";
				#logc('orFanart: '.$fanart);
				
				$fanartExists = file_exists($fanart);
				#logc( $fanartExists ? 'exist' : 'notExist' );
				if ($fanartExists) {
					$ftime = '';
					try {
						$ftime = filemtime ($fanart);
					} catch (Exception $e) { }

					$fanartThumb = "./img/fanart/".$crc."-fanart_".$ftime.".jpg";
					$fanart = getFanart0($fanart, $fanartThumb);
				} else if ($existArtTable) {
					// ayar
					$res2 = $dbh->query("SELECT url FROM art WHERE media_type = 'movie' AND type = 'fanart' AND media_id = '$idMovie';");
					$row2 = $res2->fetch();
					$url = $row2['url'];
					#logc('url: '.$url);
					if (!empty($url)) {
						#$fanart = getCoverBig($url, $url, true);
						$fanart = getFanart($url, $url, true);
						$fanartExists = true;
					}
				}
			}
		}

		$inhalt = "";
		$idMovie = $row['idMovie'];
		$titel = trim($row['c00']);
		$orTitel = trim($row['c16']);
		$jahr = $row['jahr'];
		$inhalt .= $row['c01'];
?>
<body>
<?php
		$pos1 = strpos($filename, '.3D.');
		if ( $pos1 == true) {
			$titel .= ' (3D)';
		}
		
		if ($DETAILFANART && $fanartExists) {
			if ($ENCODE) { $fanart = base64_encode_image($fanart); }
			echo '<div class="fanartBg"><img src="'.$fanart.'" style="width:100%; height:100%;"/></div>';
			echo "\r\n";
		}
		
		if (!empty($cover)) {
			if ($ENCODE) {
				$cover = base64_encode_image($cover);
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
		if (!empty($orTitel) && strtolower($titel) != strtolower($orTitel)) {
			echo "\r\n";
			echo '<span class="originalTitle">';
			echo '<br/>';
			echo '<b>Original title</b>: '.$row['c16'];
			echo '</span>';
			echo "\r\n";
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
			$admin = isAdmin();

			if (!$spProtect || ($admin && $watched >= 1)) {
				echo '<span id="movieDescription">';
				echo $inhalt;
				echo '</span>';

			} else if ($spProtect || ($spProtect && $admin && (empty($watched)))) {
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

		#if ($size != '' && $size > 0) {
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
			if (!empty($tmp)) { $vCodec = strtoupper($tmp); }

			$tmp = $row3['strAudioCodec'];
			if (!empty($tmp)) { $aCodec[count($aCodec)] = strtoupper($tmp); }

			$tmp = $row3['iAudioChannels'];
			if (!empty($tmp)) { $aChannels[count($aChannels)] = $tmp; }

			$tmp = $row3['strAudioLanguage'];
			if (!empty($tmp)) { $aLang[count($aLang)] = strtoupper($tmp); }
			
			$tmp = $row3['strSubtitleLanguage'];
			if (!empty($tmp)) { $sLang[count($sLang)] = strtoupper($tmp); }
			
			$run++;
		}
		
		$sqlG = "select * from genre";
		$resultG = $dbh->query($sqlG);
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
		$res[0][3] = $jahr;
		if ($width != '' && $height != '') {
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
			echo '<th class="streaminfoAV'.(count($aLang) > 0 ? '' : '3').'" colspan="3">Audio</th>';
			if (!empty($sLang)) {
				echo '<th class="streaminfoLasTD">Sub</th>';
			} else {
				$spalten--;
			}
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
			echo '<span class="filename lefto">'.encodeString($filename).'</span>';
			echo '<span class="filesize righto" title="'.formatToDeNotation($size).'">'.$size1.'</span>';
			echo '</td></tr>';
			echo "\r\n";
			
			#if (existsSetTable($dbh)) {
			#$SQL_SET = 'SELECT * FROM sets S, setlinkmovie L WHERE L.idSet = S.idSet AND L.idMovie = '.$id.';';
			$SQL_SET = 'SELECT S.strSet, S.idSet FROM sets S, movie M WHERE M.idSet = S.idSet AND M.idMovie = '.$id.';';
			$result = $dbh->query($SQL_SET);
			if (!empty($result)) {
				echo '<tr><td class="streaminfoLasTD" style="padding-top:10px;" colspan="'.$spalten.'">';
				$row = $result->fetch();
				$set = $row['strSet'];
				$idSet = $row['idSet'];
				$isSet = ($set != null && $set != '');

				$href = ($isSet ? '<b>'.$set.'</b>' : '<i>In keinem Set!</i>');
				$admin = (isset($_SESSION['angemeldet']) && $_SESSION['angemeldet'] == true) ? 1 : 0;
				if ($admin) {
					$href = '<a class="fancy_movieset" href="./changeMovieSet.php?idMovie='.$id.'">'.$href.'</a>';
				}

				echo '<span class="filename lefto">'.$href.'</span>';
				echo '</td></tr>';
				echo "\r\n";
			}
			#}
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
			$artist = $row2['strActor'];
			$idActor = $row2['idActor'];
			$actorpicURL = $row2['actorimage'];
			
			$actorimg = getActorThumb($artist, $actorpicURL, false);
			#logc( 'orActor: '.$actorimg.(file_exists($actorimg) ? '__YES' : '') );
			if (!file_exists($actorimg) && $existArtTable) {
				// ayar
				$res3 = $dbh->query("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '$idActor';");
				$row3 = $res3->fetch();
				$url = $row3['url'];
				if (!empty($url)) {
					$actorimg = getActorThumb($url, $url, true);
				}
			}
			
			$schauspTblOut[$actors]  = '<tr'.($actors >= $acLimit ? ' name="artists" style="display:none;"' : '').'>';
			$schauspTblOut[$actors] .= '<td class="art">';
			$schauspTblOut[$actors] .= '<a class="openImdbDetail filterX" href="'.$ANONYMIZER.$PERSONINFOSEARCH.$artist.'">[i] </a>';
			$schauspTblOut[$actors] .= '<a href="?show=filme&which=artist&just='.$idActor.'&name='.$artist.'" target="_parent" ';
			if (file_exists($actorimg)) {
				$schauspTblOut[$actors] .= ' class="hoverpic" rel="'.$actorimg.'" title="'.$artist.'"';
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
		
	} catch(PDOException $e) {
		echo $e->getMessage();
	}

	unset($_SESSION['show']);
?>
