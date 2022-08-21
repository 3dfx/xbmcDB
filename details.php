<?php
include_once "check.php";
include_once "globals.php";
include_once "./template/config.php";
include_once "./template/functions.php";

	$id = isset($_SESSION['idShow']) ? $_SESSION['idShow'] : null;
	#if (empty($id)) { $id = getEscGPost('idShow'); }
	if (empty($id)) { die('<br/>no param!<br/>'); }

    $DETAILFANART = isset($GLOBALS['DETAILFANART']) ? $GLOBALS['DETAILFANART'] : true;
?>

<head>
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.pack.js"></script>
	<script type="text/javascript" src="./template/js/myfancy.js"></script>
<?php if (isAdmin()) { ?>
	<script type="text/javascript" src="./template/js/jquery.knob.js"></script>
<?php } else { ?>
	<script type="text/javascript" src="./template/js/jquery.knob.min.js"></script>
<?php } ?>
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<link rel="stylesheet" type="text/css" href="class.css" />
	<style> body {background-color:#<?php echo ($DETAILFANART) ? 'FFFFFF' : 'D9E8FA'; ?>;} </style>
	<script type="text/javascript">
		var flag = true;

		function spoilIt() {
			$( '#spoiler' ).hide();
			$( '#movieDescription' ).show();
		}

		function showRestInhalt() {
			let inhaltDots = document.getElementById('inhaltDots');
			let inhaltRest = document.getElementById('inhaltRest');

			inhaltDots.style.display = 'none';
			inhaltRest.style.display = 'inline';
		}

		function showHiddenTRs(id, name, flag) {
			let rows = document.getElementsByTagName('TR');
			for (let i = 0; i < rows.length; i++) {
				let row = rows[i];
				if (row === null || row.getAttribute('name') !== name) {
					continue;
				}

				row.style.display = flag ? '' : 'none';
			}

			let doTr = document.getElementById(id);
			doTr.style.display = flag ? 'none' : '';

			if (name !== 'genres') {
				this.flag = !flag;
			}
		}

		function setAspectRatio(idFile, idMovie, ar = "") {
			if (idFile === null || idMovie === null) { return; }

			let answer = prompt("Enter aspect ratio", ar);
			if (answer === null || answer.trim() === "") { ar = ""; }
			else { answer = answer.replace(",", "."); }

			if (answer !== "" && !isNaN(answer)) {
				ar = Number.parseFloat(answer);
			}

			window.location.href='./dbEdit.php?act=setAspectRatio&idFile=' + idFile + '&idMovie=' + idMovie + '&' + 'aRatio=' + ar;
		}
<?php /*
		function Show_Image(IDS) {
			document.getElementById(IDS).style.display = 'block';
		}

		function Hide_Image(IDS) {
			document.getElementById(IDS).style.display = 'none';
		}
*/ ?>

		$(document).ready(function() { $('.knob-dyn').knob(); });
	</script>
</head>


<?php
	$ANONYMIZER       = $GLOBALS['ANONYMIZER'];
	$IMDB             = $GLOBALS['IMDB'];
	$PERSONINFOSEARCH = $GLOBALS['PERSONINFOSEARCH'];
	$IMDBFILMTITLE    = $GLOBALS['IMDBFILMTITLE'];
	$FILMINFOSEARCH   = $GLOBALS['FILMINFOSEARCH'];
	$SOURCE           = $GLOBALS['SOURCE'];

		$COLS = array(
			'DUR'    =>  0,
			'RATE'   =>  1,
			'YEAR'   =>  2,
			'GENRE'  =>  3,
			'VIDEO1' =>  4,
			'VIDEO2' =>  5,
			'AUDIO1' =>  6,
			'AUDIO2' =>  7,
			'AUDIO3' =>  8,
			'SUB'    =>  9
		);

		$isAdmin = isAdmin();
		$idFile  = -1;

		$existArtTable = existsArtTable();

		$SQL = "SELECT c00 AS movieName, c01 AS desc, A.c03 AS sndTitle, idMovie, ".mapDBC('A.c07')." AS jahr, c08 AS thumb, ".mapDBC('A.c09')." AS imdbId, B.strFilename AS filename, A.c19 AS trailer, ".mapDBC('A.c04')." AS votes, ".mapDBC('A.c05')." AS rating, c11 AS seconds, c14 AS genres, c16 AS orTitle, ".
			"C.strPath AS path, D.filesize, D.fps, D.bit, D.atmosx, D.src, A.idFile, B.playCount AS playCount ".
			"FROM movie A, files B, path C ".
			mapDBC('joinIdMovie').
			mapDBC('joinRatingMovie').
			"LEFT JOIN fileinfo D ON A.idFile = D.idFile WHERE A.idFile = B.idFile AND C.idPath = B.idPath AND idMovie = '".$id."' ";
		$row = fetchFromDB($SQL, false);

		if (empty($row)) { die('not found...'); }

		$SQL2     = "SELECT B.".mapDBC('strActor').", A.".mapDBC('strRole').", A.".mapDBC('idActor').", B.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC("actorlinkmovie")." A, ".mapDBC("actors")." B WHERE A.".mapDBC('idActor')." = B.".mapDBC('idActor')." AND A.media_type='movie' AND A.".mapDBC('idMovie')." = '".$id."' ORDER BY A.".mapDBC('iOrder').";";
		$result2  = querySQL($SQL2);
		$result2_ = querySQL($SQL2);

		$idFile   = $row['idFile'];
		$result3  = getStreamDetails($idFile);

		$SHOW_TRAILER = isset($GLOBALS['SHOW_TRAILER']) ? $GLOBALS['SHOW_TRAILER'] : false;
		$size         = $row['filesize'];
		$fps          = $row['fps'];
		$bit          = isset($row['bit']) ? $row['bit'] : 0;
		$bit10        = $bit >= 10;
		$source       = $row['src'];
		$idMovie      = $row['idMovie'];
		$path         = $row['path'];
		$filename     = $row['filename'];
		$watched      = $row['playCount'];
		$fnam         = $path.$filename;
		$titel        = trim($row['movieName']);
		$sndTitle     = trim($row['sndTitle']);
		$orTitel      = trim($row['orTitle']);
		$jahr         = $row['jahr'];
		$inhalt       = $row['desc'];
		$ar           = '';
		$arSpan       = '';
		$country      = getCountry($idMovie);
		$atmosx       = fetchAudioFormat($idFile, $path, $filename, $row['atmosx'], getPDO());
		$fps          = fetchFps($idFile, $path, $filename, array($bit, $fps), getPDO());
		if ($fps === null && $fps[0] === null) {
			$bit10 = preg_match_all('/\b1(0|2)bit\b/', $filename) > 0 ? true : false;
		}

		$resultAR = fetchFromDB("SELECT ratio FROM aspectratio WHERE idMovie = ".$idMovie.";");
		if (!empty($resultAR) && !empty($resultAR['ratio'])) {
			$ar = sprintf("%01.2f", round($resultAR['ratio'], 2));
			$arSpan = '<span style="font-style:italic;" title="aspect-ratio overridden">'.$ar.':1</span>';
		}

		$percent = null;
		$timeAt = null;
		$pausedAt = null;
		$timeTotal = null;
		if ($isAdmin) {
			$result    = fetchFromDB("SELECT timeInSeconds AS timeAt, totalTimeInSeconds AS timeTotal FROM bookmark WHERE idFile = '".$idFile."';");
			$timeAt    = isset($result['timeAt'])    ? $result['timeAt']    : null;
			$timeTotal = isset($result['timeTotal']) ? $result['timeTotal'] : null;
			if (!empty($timeAt) && !empty($timeTotal)) {
				$timeAt    = intval($timeAt);
				$timeTotal = intval($timeTotal);
				$percent   = round($timeAt / $timeTotal * 100);
				$pausedAt  = getPausedAt(round($percent * $timeTotal / 100));
			}
		}

		$fanart    = '';
		$covers    = getCovers($fnam, '', $idMovie);
		$cover     = getImageWrap($covers[0], $idMovie, 'movie', 1);
		$cover_big = getImageWrap($covers[1], $idMovie, 'movie', 2);

		$fanartExists = false;
		if ($DETAILFANART) {
			$fanart = getFanartCover($fnam, $idMovie);
			wrapItUp('fanart', $idMovie, $fanart);
			$fanartExists = !empty($fanart);
		}
?>
<body>
<?php
		if ($fanartExists) {
			$fanart  = getImageWrap($fanart, $idMovie, 'fanart', 0);
			echo '<div class="fanartBg"><img src="'.$fanart.'" style="width:100%; height:100%;"/></div>'."\r\n";
		}

		$f4Ke   = isFake4K($filename);
		$scaled = isUpscaled($filename);
		$is3D   = is3d($filename);
		if ($is3D) {
			$titel .= ' (3D)';
		}

		if (!empty($cover)) {
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
		$imdbLink = $ANONYMIZER.$FILMINFOSEARCH.$titel;
		if (!empty($imdbId)) {
			$imdbLink = $ANONYMIZER.$IMDBFILMTITLE.$imdbId;
		}
		echo '<a style="font-size:26px; font-weight:bold;" class="openImdbDetail" href="'.$imdbLink.'">'.$titel.' ('.$jahr.')'.'</a>';
		$trailer = $row['trailer'];
		if ($SHOW_TRAILER && !empty($trailer)) {
			echo ' <sup><a class="fancy_iframe3" href="'.$ANONYMIZER.$trailer.'"><img src="img/filmrolle.png" style="height:22px; border:0px; vertical-align:middle;"></a></sup>'."\r\n";
		}

		$cmpTitel = strtolower($titel);
		$cmpTitel = str_replace('and', '', $cmpTitel);
		$cmpTitel = str_replace('und', '', $cmpTitel);
		$cmpTitel = str_replace('&',   '', $cmpTitel);

		$cmpOrTit = strtolower($orTitel);
		$cmpOrTit = str_replace('and', '', $cmpOrTit);
		$cmpOrTit = str_replace('und', '', $cmpOrTit);
		$cmpOrTit = str_replace('&',   '', $cmpOrTit);


		if (!empty($sndTitle)) {
			#$checkDelta++;
			echo "\r\n";
			echo '<div class="originalTitle" style="top:2px; font-style:italic; color:dimgray;">';
			echo $row['sndTitle'];
			echo '</div>';
			echo "\r\n";
		}

		if (!empty($cmpOrTit) && $cmpTitel != $cmpOrTit) {
			echo "\r\n";
			echo '<div class="originalTitle" style="top:8px;">';
			echo '<b style="color:dimgray;">Original title</b>: '.$row['orTitle'];
			echo '</div>';
			echo "\r\n";
		}

		$studio = getStudio($idMovie);
		if (!empty($country) || !empty($studio)) {
			echo "\r\n";
			echo '<div class="originalTitle" style="top:8px;">';
			echo !empty($country) ? '<b style="color:dimgray;">Made in</b>: '.$country : '';
			echo !empty($country) && !empty($studio) ? ' / ' : '';
			echo !empty($studio) ? '<b style="color:dimgray;">Studio</b>: '.$studio : '';
			echo '</div>';
			echo "\r\n";
		}

		if ($isAdmin) {
			$hint = empty($percent) ? '' : ' title="'.$pausedAt.' ('.$percent.'%)"';
			echo '<span class="epCheckSpan fancy_movieset" href="./dbEdit.php?act=clearBookmark&idFile='.$idFile.'"'.$hint.' style="position:absolute; right:0px;">';
			if (!empty($percent)) {
				echo '<input type="text" class="knob-dyn" data-width="25" data-height="25" data-fgColor="#6CC829" data-angleOffset="180" data-thickness=".4" data-displayInput="false" data-readOnly="true" value="'.$percent.'" style="display:none;" />';
			} else if ($watched > 0) {
				echo '<img src="./img/check.png" class="icon32" title="watched" />';
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

			$inhalt = str_replace('\n\r', '<br/>', $inhalt);
			$inhalt = str_replace('\r',   '<br/>', $inhalt);

			$descHidden = '';
			$spProtect = isset($GLOBALS['SPOILPROTECTION']) ? $GLOBALS['SPOILPROTECTION'] : true;
			if ($spProtect && ($isAdmin && (empty($watched) && empty($percent)))) {
				$descHidden = ' style="display:none;"';
				echo '<div id="spoiler" style="padding-top:15px; color:red; cursor:pointer;" onclick="spoilIt(); return false;"><u><i><b>spoil it!</b></i></u></div>';
			}
			echo '<span id="movieDescription"'.$descHidden.'>'.$inhalt.'</span>';

		} else {
			echo '<span id="movieDescription"><i>Keine Beschreibung vorhanden.</i></span>';
		}

		echo "\r\n";
		echo '<div style="width:700px; height:2px;"></div>';
		echo "\r\n";

		$size1     = '';
		$minutes   = '';
		$hours     = '';
		$rating    = '';
		$stimmen   = '';
		$width     = '';
		$height    = '';
		$vCodec    = '';
		$hdrType   = '';
		$genre     = array();
		$aCodec    = array();
		$aChannels = array();
		$aLang     = array();
		$sLang     = array();
		$res       = array();
		$vRes      = array();
		$run       = 0;

		if ($row['movieName'] != $row['orTitle']) {
			$orTitle = $row['orTitle'];
			$run = 1;
		}
		if (!empty($row['seconds'])) {
			$secs    = $row['seconds'];
			$minutes = floor($secs/60);
			$hours   = floor($minutes/60).':'.sprintf ("%02d", $minutes % 60).'\'';
			$minutes = $minutes.'\'';
			$run = 1;
		}
		if (substr($row['rating'], 0, 1) != "0") {
			$rating  = formatRating($row['rating']);
			$rating  = '<a class="openImdbDetail detailLink" href="'.$imdbLink.'">'.$rating.'</a>';
			$stimmen = '<a class="openImdbDetail detailLink" href="'.$imdbLink.'">'.$row['votes'].'</a>';
			$run = 1;
		}
		if (!empty($row['genres'])) {
			$genre = explode(" / ", $row['genres']);
			$run = 1;
		}

		if (!empty($size)) {
			$size1 = _format_bytes($size);
		}

		foreach($result3 as $row3) {
			if (!empty($tmp = $row3['iVideoWidth']))  { $width  = $tmp; }
			if (!empty($tmp = $row3['iVideoHeight'])) { $height = $tmp; }
			if (!empty($tmp = $row3['strHdrType']) && !isDemo()) { $hdrType = trim($tmp); }
			if (!empty($tmp = $row3['strVideoCodec']) && !isDemo()) { $vCodec = trim($tmp); }
			if (!empty($tmp = $row3['strAudioCodec']) && !isDemo()) { $aCodec[] = strtoupper($tmp); }
			if (!empty($tmp = $row3['iAudioChannels']) && !isDemo()) { $aChannels[] = $tmp; }
			if (!empty($tmp = $row3['strAudioLanguage']) && !isDemo()) { $aLang[] = strtoupper($tmp); }
			if (!empty($tmp = $row3['strSubtitleLanguage']) && !isDemo()) { $sLang[] = strtoupper($tmp); }
			if (!empty($tmp = $row3['fVideoAspect']) && empty($ar)) {
				$ar = sprintf("%01.2f", round($tmp, 2));
				$arSpan = $ar.':1';
			}
			if (!empty($tmp = $row3['iVideoDuration'])) {
				$secs    = $tmp;
				$minutes = floor($secs/60);
				$hours   = floor($minutes/60).':'.sprintf ("%02d", $minutes % 60).'\'';
				$minutes = $minutes.'\'';
			}
			$run++;
		}

		$hdr = isHDR($filename, $hdrType);

		if (empty($ar) || intval($ar) == 0){
			$ar = sprintf("%01.2f", round($width/$height, 2));
			$arSpan = $ar.':1';
		}

		$sqlG = "SELECT * FROM genre";
		$resultG = querySQL($sqlG);
		$idGenre = array();
		foreach($resultG as $rowG) {
			if (!isset($rowG[mapDBC('strGenre')])) { continue; }
			$str = ucwords(strtolower(trim($rowG[mapDBC('strGenre')])));
			if (empty($str)) { continue; }
			if (!isset($rowG[mapDBC('idGenre')])) { continue; }

			$idGenre[$str] = $rowG[mapDBC('idGenre')];
		}

		/** @noinspection PhpNestedMinMaxCallInspection */
		$max = max(max(count($aCodec), count($aChannels), count($aLang), count($sLang), count($genre)), 2);
		$spalten = count($COLS);
		for ($g = 0; $g < $max; $g++) {
			for ($x = 0; $x < $spalten; $x++) {
				$res[$g][$x] = null;
			}

			if ($g < count($genre)) {
				$genreId = -1;
				$strGenre = ucwords(strtolower(trim($genre[$g])));
				if (!empty($strGenre) && isset($idGenre[$strGenre]))
					$genreId = $idGenre[$strGenre];

				$strGenre = shortenGenre($strGenre);
				if ($genreId != -1) {
					$res[$g][$COLS['GENRE']] = '<a href="?show=filme&which=genre&just='.$genreId.'&name='.$strGenre.'" target="_parent" class="detailLink" title="filter">'.$strGenre.'</a>';
				} else {
					$res[$g][$COLS['GENRE']] = $strGenre;
				}
			}

			if ($g < count($aLang)) {
				$res[$g][$COLS['AUDIO1']] = postEditLanguage($aLang[$g]);
			}

			if ($g < count($aCodec)) {
				$res[$g][$COLS['AUDIO2']] = postEditACodec($aCodec[$g], isset($atmosx[$g]) ? $atmosx[$g] : null);
			}

			if ($g < count($aChannels)) {
				$res[$g][$COLS['AUDIO3']] = postEditChannels($aChannels[$g]);
			}

			if ($g < count($sLang)) {
				$res[$g][$COLS['SUB']] = postEditLanguage($sLang[$g], false);
			}
		}

		$res[0][$COLS['DUR']]  = $hours;
		$res[1][$COLS['DUR']]  = $minutes;
		$res[0][$COLS['RATE']] = $rating;
		$res[1][$COLS['RATE']] = '<span title="votes">'.$stimmen.'</span>';
		$res[0][$COLS['YEAR']] = isset($jahr) ? '<a href="?show=filme&country=&mode=1&which=year&just='.$jahr.'&name='.$jahr.'" target="_parent" class="detailLink" title="filter">'.$jahr.'</a>' : '';
		if (!empty($width) && !empty($height)) {
			$vRes[0] = $width;
			$vRes[1] = $height;
			$resInfo  = $width.'x'.$height;
			$cols     = isset($GLOBALS['CODEC_COLORS']) ? $GLOBALS['CODEC_COLORS'] : null;
			$resPerf  = getResPerf($vRes, $hdr);
			$title    = $f4Ke   ? 'title="Fake 4K"; '     : '';
			$title    = $scaled ? 'title="Upscaled 4K"; ' : $title;
			$resColor = ($cols === null || $resPerf < 4 ? null : $cols[$resPerf]);
			$resStyle  = '';
			if (!empty($resColor) || $f4Ke || $scaled) {
				if ($f4Ke || $scaled) {
					$resStyle .= 'text-shadow: 0 0 2px rgba(222,0,0,1.75);';
				} else {
					$resStyle .= 'font-weight:bold;';
				}

				if (!empty($resColor)) {
					$resStyle .= ' color:'.$resColor.';';
				}
			}
			$resInfo  = '<span '.$title.'style="'.$resStyle.'">'.$resInfo.'</span>';
			$res[0][$COLS['VIDEO1']] = $resInfo;
		}
		$res[1][$COLS['VIDEO1']] = '<span style="cursor:pointer;" onclick="setAspectRatio('.$idFile.', '.$idMovie.', '.$ar.'); return false;">'.$arSpan.'</span>';

		if (!empty($fps)) {
			$bit10 = $fps[0] >= 10;

			$fPerf = getFpsPerf($fps[1]);
			$color = ($cols === null || $fPerf < 4 ? null : $cols[$fPerf]);
			$res[1][$COLS['VIDEO2']] = (!empty($color) ? '<span style="color:'.$color.'; font-weight:bold;">'.$fps[1].' fps</span>' : $fps[1].' fps');
		}

		if (!empty($hdrType)) {
			$hPerf  = getResPerf(null, true);
			$color = ($cols === null || $hPerf < 4 ? null : $cols[$hPerf]);
			if (!empty($color)) {
				$res[2][$COLS['VIDEO2']] = '<span style="color:'.$color.'; font-weight:bold;">'.postEditHdrType($hdrType).'</span>';
			}
		}

		$vCodec = postEditVCodec($vCodec);
		$cols   = isset($GLOBALS['CODEC_COLORS']) ? $GLOBALS['CODEC_COLORS'] : null;
		$perf   = (!empty($vCodec) ? decodingPerf($vCodec, $bit10) : 0);
		$color  = ($cols === null || $perf < 4 ? null : $cols[$perf]);
		$vCodec = (!empty($color) ? '<span style="color:'.$color.'; font-weight:bold;">'.$vCodec.($bit10 ? ' 10bit' : '').'</span>' : $vCodec);
		$res[0][$COLS['VIDEO2']] = $vCodec;

		$artist  = '';
		$actors  = 0;
		$actCnt  = 0;
		$counter = 1;
		$acLimit = 5;
		$acLimitImg = 15;
		$fetchedImages = 0;
		$schauspTblOut = array();
		$artCovers = fetchArtCovers($existArtTable);
		foreach($result2_ as $row2) { $actCnt++; }
		if ($actCnt-1 == $acLimit) { $acLimit++; }
		foreach($result2 as $row2) {
			$artist      = $row2[mapDBC('strActor')];
			$idActor     = $row2[mapDBC('idActor')];
			$actorpicURL = $row2['actorimage'];

			$actorimg = getActorThumb($artist, $actorpicURL, false);
			if ($fetchedImages < $acLimitImg) {
				if (isFile($actorimg) && $existArtTable) {
					$fetchedImages++;
					if (!empty($artCovers) && isset($artCovers['actor'][$idActor])) {
						$actorimg = $artCovers['actor'][$idActor]['cover'];
					} else {
						$row3 = fetchFromDB("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '$idActor';");
						$url = isset($row3['url']) ? $row3['url'] : null;
						if (!empty($url)) {
							$actorimg = getActorThumb($url, $url, true);
						}
					}
				}
			}

			wrapItUp('actor', $idActor, $actorimg);

			$schauspTblOut[$actors]  = '<tr'.($actors >= $acLimit ? ' name="artists" style="display:none;"' : '').'>';
			$schauspTblOut[$actors] .= '<td class="art">';
			$schauspTblOut[$actors] .= '<a href="?show=filme&which=artist&just='.$idActor.'&name='.$artist.'" target="_parent" ';
			if (isFile($actorimg)) {
				$schauspTblOut[$actors] .= ' class="hoverpic" rel="'.getImageWrap($actorimg, $idActor, 'actor', 0).'" title="'.$artist.'"';
			} else {
				$schauspTblOut[$actors] .= 'title="filter"';
			}
			$schauspTblOut[$actors] .= '>'.$artist.'</a>';

			$schauspTblOut[$actors] .= '</td>';
			$schauspTblOut[$actors] .= '<td class="role">';
			if (isset($row2[mapDBC('strRole')])) {
				$strRole = $row2[mapDBC('strRole')];
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
			echo '<tr>';
			echo '<th>';
			#echo '<span class="moreDots" style="margin-left:10px;" onclick="showHiddenTRs(\'doTr\', \'artists\', flag);">Actors</span>';
			echo '<span class="moreDots" onclick="showHiddenTRs(\'doTr\', \'artists\', flag);">Actor</span>';
			echo '</th>';
			echo '<th class="role">';
			echo '<span class="moreDots" onclick="showHiddenTRs(\'doTr\', \'artists\', flag);">Role</span>';
			echo '</th>';
			echo '</tr>';
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
			echo '</div>';
			echo "\r\n";
		}

		if ($run > 0) {
			echo '<div class="stream">';
			echo '<table cellspacing="0" class="streaminfo">';
			echo "\r\n";
			echo '<tr>';
			echo '<th>Duration</th><th>Rating</th><th>Year</th><th class="streaminfoGenreTH">Genre</th>';
			#<th>Votes</th>
			echo '<th class="streaminfoAV'.(count($aLang) > 0 ? '' : '2').'" colspan="2">Video</th>';
			if (!empty($aCodec)) {
				echo '<th class="streaminfoAV'.(count($aLang) > 0 ? '' : '3').'" colspan="3">Audio</th>';
			} else { $spalten--; }
			if (!empty($sLang)) {
				echo '<th class="streaminfoLasTD streaminfoBorderTH">Sub</th>';
			} else { $spalten--; }
			echo '</tr>';
			echo "\r\n";
			echo '<tr class="abstand"><td colspan="'.count($COLS).'"></td></tr>';
			echo "\r\n";

			$zeilen = 0;
			$hiddenGenres = count($genre)-2;
			$hiddenSubs   = count($sLang)-2;
			for ($i = 0; $i < $max; $i++) {
				echo '<tr'.($i >= 2 && ($hiddenGenres > 1 || $hiddenSubs > 1) ? ' name="genres" style="display:none;"' : '').'>';
				$emptyGenreFilled = false;
				for ($j = 0; $j < count($res[$i]); $j++) {
					$val = $res[$i][$j];
					if (count($genre) == 0 && $j == $COLS['GENRE']) {
						if ($emptyGenreFilled) { continue; }
					} else if ($val === null && $j > 0 && $j != $COLS['GENRE']+1) {
						continue;
					}

					echo '<td';
					$colspan = getNeededColspan($COLS, $res[$i], $j+1);
					if (count($genre) == 0 && $j == $COLS['YEAR']) {
						$colspan = 1;
					}
					if ($colspan > 1) {
						echo ' colspan="'.$colspan.'"';
					}

					switch ($j) {
						case $COLS['DUR']:
							if ($i > 0 && $i >= count($genre) && $colspan > 1) {
								echo ' class="streaminfoGenre"';
								$emptyGenreFilled = true;
							}
							break;

						case $COLS['RATE']:
							if (!$emptyGenreFilled && $i > 0 && $i >= count($genre)) {
								echo ' class="streaminfoGenre"';
								$emptyGenreFilled = true;
							}
							break;

						case $COLS['YEAR']:
							if (!$emptyGenreFilled && $colspan > 1) {
								echo ' class="streaminfoGenre"';
								$emptyGenreFilled = true;
							}
							break;

						case $COLS['GENRE']:
							if (!$emptyGenreFilled) {
								echo ' class="streaminfoGenre"';
							}
							break;

						case $COLS['VIDEO1']:
							echo ' class="streaminfoAV"';
							break;

						case $COLS['VIDEO2']:
							echo ' class="streaminfoAV"';
							break;

						case $COLS['AUDIO1']:
							echo ' class="streaminfoAV streaminfoBorder"';
							break;

						case $COLS['AUDIO2']:
							echo ' class="streaminfoAV"';
							break;

						case $COLS['AUDIO3']:
							echo ' class="'.(empty($sLang) ? 'streaminfoLasTD' : 'streaminfoAV').'"';
							break;

						case $COLS['SUB']:
							echo ' class="streaminfoLasTD streaminfoBorder"';
							break;
					}

					echo '>';
					echo ($val === null || $val == '' ? '&nbsp;' : $val);
					echo '</td>';
				}
				echo '</tr>';
				$zeilen++;
			}

			if ($zeilen > (empty($hdrType) ? 2 : 3) && ($hiddenGenres > 1 || $hiddenSubs > 1)) {
				echo '<tr id="genreDots">';
				echo '<td colspan="'.($COLS['GENRE']).'"></td>';
				echo '<td class="streaminfoGenre lefto">';
				if ($hiddenGenres >= 1) {
					echo '<span class="moreDots" onclick="showHiddenTRs(\'genreDots\', \'genres\', true);" title="mehr...">...</span>';
				}
				echo '</td>';
					echo '<td colspan="'.($hiddenSubs >= 1 ? count($COLS)-($COLS['GENRE']+2) : count($COLS)-($COLS['GENRE']+1)).'"></td>';
				if ($hiddenSubs >= 1) {
					echo '<td class="streaminfoLasTD streaminfoBorder"><span class="moreDots" onclick="showHiddenTRs(\'genreDots\', \'genres\', true);" title="mehr...">...</span></td>';
				}
				echo '</tr>';
				echo "\r\n";
			}

			$smb = (substr($filename, 0, 6) == 'smb://');
			$stacked = (substr($filename, 0, 8) == 'stack://');
			if ($smb || $stacked) {
				$filename = '';
			}

			echo '<tr class="abstand"><td colspan="'.count($COLS).'"></td></tr>';
			echo '<tr><td class="streaminfoLLine streaminfoLasTD" colspan="'.$spalten.'">';
			if (!isDemo()) {
				echo '<span class="filename lefto"'.($isAdmin ? ' title="'.$path.'"' : '').'>'.encodeString($filename).'</span>';
				echo '<span class="filesize righto" title="'.formatToDeNotation($size).'">'.$size1.'</span>';
			}
			echo '</td></tr>';
			echo "\r\n";

			if (!is_null($source) || isAdmin()) {
				echo '<tr><td style="padding-top:5px;" colspan="'.$spalten.'">';
				$href = '';
				if ($isAdmin) {
					$href .= ' <a class="fancy_movieset" href="./changeSource.php?idFile='.$idFile. '"><img style="border:0; height:9px;" src="img/edit-pen.png" title="change source" alt="change source"/></a>';
				}
				echo '<span class="filename lefto">Source: '.$SOURCE[$source].' '.$href.'</span>';
				echo '</td></tr>';
				echo "\r\n";
			}

			$SQL_SET = 'SELECT S.strSet, S.idSet FROM sets S, movie M WHERE M.idSet = S.idSet AND M.idMovie = '.$id.';';
			$result = querySQL($SQL_SET);
			if (!empty($result)) {
				echo '<tr><td class="streaminfoLasTD" style="padding-top:10px;" colspan="'.$spalten.'">';
				$row   = $result->fetch();
				$set   = isset($row['strSet']) ? $row['strSet'] : null;
				$idSet = isset($row['idSet'])  ? $row['idSet']  : null;
				$isSet = !empty($set) && isset($idSet);

				$href = ($isSet ? '<b>'.$set.'</b>' : '<i>Not in any set!</i>');
				if ($isSet) {
					$href = '<a href="?show=filme&which=set&sort=titlea&just='.$idSet.'&name='.$set.'" target="_parent">'.$href.'</a>';
				}
				if ($isAdmin) {
					$href .= ' <a class="fancy_movieset" href="./changeMovieSet.php?idMovie='.$id. '"><img style="border:0; height:9px;" src="img/edit-pen.png" title="change set" alt="change set"/></a>';
				}

				echo '<span class="filename lefto">'.$href.'</span>';
				echo '</td></tr>';
				echo "\r\n";
			}

			echo '</table>';
			echo '</div>';
			echo "\r\n";
		}

	unset( $_SESSION['show'], $_SESSION['idShow'] );


//- FUNCTIONS -//
	function getNeededColspan($COLS, $res, $spalte) {
		$colspan = 1;
		if ($spalte == $COLS['VIDEO1']) {
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
			return (min($colspan, $COLS['GENRE'] + 1));
		}

		return $colspan;
	}

	function getCovers($fnam, $cover, $idMovie) {
		$existArtTable = $GLOBALS['existArtTable'];

		$res = array();
		if (isFile(getCoverMid($fnam, $cover, false))) {
			$res[0] = getCoverMid($fnam, $cover, false); //cover
			$res[1] = getCoverBig($fnam, $cover, false); //cover_big

		} else if ($existArtTable) {
			$res2 = querySQL("SELECT url,type FROM art WHERE media_type = 'movie' AND (type = 'poster' OR type = 'thumb') AND media_id = '".$idMovie."';");
			foreach($res2 as $row2) {
				$type = isset($row2['type']) ? $row2['type'] : null;
				$url  = isset($row2['url']) ? $row2['url'] : null;
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

		$fanartExists = isFile($fanart);
		if ($fanartExists) {
			$ftime = '';
			try {
				$ftime = filemtime($fanart);
			} catch (Exception $e) { }

			$fanartThumb = "./img/fanart/".$crc."-fanart_".$ftime.".jpg";
			return getFanart0($fanart, $fanartThumb);

		} else if ($existArtTable) {
			$row2 = fetchFromDB("SELECT url FROM art WHERE media_type = 'movie' AND type = 'fanart' AND media_id = '".$idMovie."';");
			$url  = isset($row2['url']) ? $row2['url'] : null;
			if (!empty($url)) {
				return getFanart($url, $url, true);
			}
		}

		return null;
	}

	function getCountry($idMovie) {
		$SQL = "SELECT c.".mapDBC('strCountry')." FROM ".mapDBC('countrylinkmovie')." cl, country c, movie m WHERE m.idMovie = cl.".mapDBC('idMovie')." AND cl.".mapDBC('idCountry')." = c.".mapDBC('idCountry')." AND m.idMovie = '".$idMovie."';";
		$res = querySQL($SQL, false);
		$row = $res->fetch();
		return isset($row[mapDBC('strCountry')]) ? $row[mapDBC('strCountry')] : null;
	}

	function getStudio($idMovie) {
		$res = querySQL("SELECT name FROM studio WHERE studio_id = (SELECT studio_id FROM studio_link WHERE media_type = 'movie' AND media_id = '".$idMovie."');", false);
		$row = $res->fetch();
		return isset($row['name']) ? $row['name'] : null;

	}
//- FUNCTIONS -//
?>
