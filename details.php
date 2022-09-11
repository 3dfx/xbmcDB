<?php
include_once "check.php";
include_once "globals.php";
include_once "./template/config.php";
include_once "./template/functions.php";

	$id = isset($_SESSION['idMovie']) ? $_SESSION['idMovie'] : null;
	#if (empty($id)) { $id = getEscGPost('idMovie'); }
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
			if (answer === null) { return; }
			if (answer.trim() === "") { ar = ""; }
			else { answer = answer.replace(",", "."); }

			if (answer !== "" && !isNaN(answer)) {
				ar = Number.parseFloat(answer);
			}

			window.location.href='./dbEdit.php?act=setAspectRatio&idFile=' + idFile + '&idMovie=' + idMovie + '&' + 'aRatio=' + ar;
		}

		function setToneMapParam(idFile, idMovie, par = "") {
			if (idFile === null || idMovie === null) { return; }

			let answer = prompt("Enter ToneMap Param", par);
			if (answer === null) { return; }
			if (answer.trim() === "") { par = ""; }
			else { answer = answer.replace(",", "."); }

			if (answer !== "" && !isNaN(answer)) {
				par = Number.parseFloat(answer);
			}

			window.location.href='./dbEdit.php?act=setToneMapParam&idFile=' + idFile + '&idMovie=' + idMovie + '&' + 'tomParam=' + par;
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
		$isAdmin = isAdmin();
		$dbh = getPDO();

		$row = fetchFromDB(
			"SELECT ".
			"c00 AS movieName, c01 AS desc, A.c03 AS sndTitle, idMovie, ".mapDBC('A.c07')." AS jahr, c08 AS thumb, ".mapDBC('A.c09')." AS imdbId, ".
			"B.strFilename AS filename, A.c19 AS trailer, ".mapDBC('A.c04')." AS votes, ".mapDBC('A.c05')." AS rating, c14 AS genres, c16 AS orTitle, ".
			"C.strPath AS path, D.filesize, D.fps, D.bit, D.atmosx, D.src, A.idFile, B.playCount AS playCount , B.lastPlayed AS lastPlayed ".
			"FROM movie A, files B, path C ".
			mapDBC('joinIdMovie').
			mapDBC('joinRatingMovie').
			"LEFT JOIN fileinfo D ON A.idFile = D.idFile ".
			"WHERE idMovie = '".$id."' AND A.idFile = B.idFile AND C.idPath = B.idPath;",
		false, $dbh);

		if (empty($row)) { die('not found...'); }

		$ANONYMIZER       = $GLOBALS['ANONYMIZER'];
		$IMDB             = $GLOBALS['IMDB'];
		$PERSONINFOSEARCH = $GLOBALS['PERSONINFOSEARCH'];
		$IMDBFILMTITLE    = $GLOBALS['IMDBFILMTITLE'];
		$FILMINFOSEARCH   = $GLOBALS['FILMINFOSEARCH'];

		$existArtTable = existsArtTable();

		$SHOW_TRAILER = isset($GLOBALS['SHOW_TRAILER']) ? $GLOBALS['SHOW_TRAILER'] : false;
		$idFile       = $row['idFile'];
		$size         = $row['filesize'];
		$bit          = isset($row['bit']) ? $row['bit'] : 0;
		$fps          = $row['fps'];
		$bit10        = $bit >= 10;
		$source       = $row['src'];
		$idMovie      = $row['idMovie'];
		$path         = $row['path'];
		$filename     = $row['filename'];
		$playCount    = $row['playCount'];
		$lastPlayed   = $row['lastPlayed'];
		$fnam         = $path.$filename;
		$titel        = trim($row['movieName']);
		$sndTitle     = trim($row['sndTitle']);
		$orTitel      = trim($row['orTitle']);
		$jahr         = $row['jahr'];
		$inhalt       = $row['desc'];
		$imdbId       = $row['imdbId'];
		$trailer      = $row['trailer'];
		$hours        = null;
		$minutes      = null;
		$run          = 0;
		$width        = '';
		$height       = '';
		$vCodec       = '';
		$hdrType      = '';
		$arSpan       = '';
		$ar           = null;
		$arOR         = null;
		$aCodec       = array();
		$aChannels    = array();
		$aLang        = array();
		$sLang        = array();
		$res          = array();
		$imdbLink     = $ANONYMIZER.(empty($imdbId) ? $FILMINFOSEARCH.$titel : $IMDBFILMTITLE.$imdbId);
		$genres       = empty($row['genres']) ? array() : explode(" / ", $row['genres']);
		$rating       = empty($row['rating']) ? '' : '<a class="openImdbDetail detailLink" href="'.$imdbLink.'">'.formatRating($row['rating']).'</a>';
		$votes        = '<span title="votes"><a class="openImdbDetail detailLink" href="'.$imdbLink.'">'.$row['votes'].'</a></span>';
		$country      = getCountry($idMovie, $dbh);
		$atmosx       = fetchAudioFormat($idFile, $path, $filename, $row['atmosx'], $dbh);
		$fps          = fetchFps($idFile, $path, $filename, array($bit, $fps), $dbh);
		if ($fps === null && $fps[0] === null) {
			$bit10 = preg_match_all('/\b1(0|2)bit\b/', $filename) > 0 ? true : false;
		}
		$row = null;
		unset($row);

		$percent   = null;
		$timeAt    = null;
		$pausedAt  = null;
		$timeTotal = null;
		if ($isAdmin) {
			$result    = fetchFromDB("SELECT timeInSeconds AS timeAt, totalTimeInSeconds AS timeTotal FROM bookmark WHERE idFile = '".$idFile."';", false, $dbh);
			$timeAt    = isset($result['timeAt'])    ? $result['timeAt']    : null;
			$timeTotal = isset($result['timeTotal']) ? $result['timeTotal'] : null;
			$result = null;
			unset($result);
			if (!empty($timeAt) && !empty($timeTotal)) {
				$timeAt    = intval($timeAt);
				$timeTotal = intval($timeTotal);
				$percent   = round($timeAt / $timeTotal * 100);
				$pausedAt  = getPausedAt(round($percent * $timeTotal / 100));
			}
		}

		$fanart    = '';
		$covers    = getCovers($fnam, '', $idMovie, $dbh);
		$cover     = getImageWrap($covers[0], $idMovie, 'movie', 1);
		$cover_big = getImageWrap($covers[1], $idMovie, 'movie', 2);

		$fanartExists = false;
		if ($DETAILFANART) {
			$fanart = getFanartCover($fnam, $idMovie, $dbh);
			wrapItUp('fanart', $idMovie, $fanart);
			$fanartExists = !empty($fanart);
		}
?>
<body>
<?php
		if ($fanartExists) {
			$fanart = getImageWrap($fanart, $idMovie, 'fanart', 0);
			echo '<div class="fanartBg"><img src="'.$fanart.'" style="width:100%; height:100%;"/></div>'."\r\n";
		}

		$scaled = isUpscaled($filename);
		$f4Ke   = isFake4K($filename);
		if (is3d($filename)) {
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

		echo '<a style="font-size:26px; font-weight:bold;" class="openImdbDetail" href="'.$imdbLink.'">'.$titel.' ('.$jahr.')'.'</a>';
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
			echo $sndTitle;
			echo '</div>';
			echo "\r\n";
		}

		if (!empty($cmpOrTit) && $cmpTitel != $cmpOrTit) {
			echo "\r\n";
			echo '<div class="originalTitle" style="top:8px;">';
			echo '<b style="color:dimgray;">Original title</b>: '.$orTitel;
			echo '</div>';
			echo "\r\n";
		}

		$studio = getStudio($idMovie, $dbh);
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

			} else if ($playCount > 0) {
				$when = toEuropeanDateFormat($lastPlayed, false);
				if ($playCount > 1) {
					$when = $playCount.'x: '.$when;
				}
				echo '<img src="./img/check.png" class="icon32" title="'.$when.'" />';
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
			if ($spProtect && ($isAdmin && (empty($playCount) && empty($percent)))) {
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

		if ($titel != $orTitel || !empty($seconds) || !empty($genres) || !empty($rating)) {
			$run = 1;
		}

		$result = getStreamDetails($idFile, $dbh);
		foreach($result as $detail) {
			if (!empty($tmp = $detail['iVideoWidth']))  { $width  = $tmp; }
			if (!empty($tmp = $detail['iVideoHeight'])) { $height = $tmp; }
			if (!empty($tmp = $detail['strHdrType']) && !isDemo()) { $hdrType = trim($tmp); }
			if (!empty($tmp = $detail['strVideoCodec']) && !isDemo()) { $vCodec = trim($tmp); }
			if (!empty($tmp = $detail['strAudioCodec']) && !isDemo()) { $aCodec[] = strtoupper($tmp); }
			if (!empty($tmp = $detail['iAudioChannels']) && !isDemo()) { $aChannels[] = $tmp; }
			if (!empty($tmp = $detail['strAudioLanguage']) && !isDemo()) { $aLang[] = strtoupper($tmp); }
			if (!empty($tmp = $detail['strSubtitleLanguage']) && !isDemo()) { $sLang[] = strtoupper($tmp); }
			if (!empty($tmp = $detail['fVideoAspect']) && empty($ar)) {
				$ar = sprintf("%01.2f", round($tmp, 2));
				$arSpan = $ar.':1';
			}
			if (!empty($tmp = $detail['iVideoDuration'])) {
				$converted = convertSecondsToHM($tmp);
				$hours   = $converted['hrs'];
				$minutes = $converted['min'];
			}
			$run++;
		}
		$result = null;
		unset($result);

		$arOR = getOverrideAR($idFile, $idMovie, $dbh);
		if (!empty($arOR)) {
			$ar = $arOR = sprintf("%01.2f", $arOR);
			$arSpan = '<span style="font-style:italic;" title="aspect-ratio // overridden">'.$ar.':1</span>';
		}

		if (empty($ar) || intval($ar) == 0) {
			$ar = sprintf("%01.2f", round($width/$height, 2));
			$arSpan = $ar.':1';
		}

		$genreIDs = fetchGenresIDs($genres, $dbh);

		$max = max(count($aCodec), count($aChannels), count($aLang), count($sLang), count($genres), (empty($hdrType) ? 2 : 3));
		$spalten = count(DETAIL_COLS);
		for ($g = 0; $g < $max; $g++) {
			for ($x = 0; $x < $spalten; $x++) {
				$res[$g][$x] = null;
			}

			if ($g < count($genres)) {
				$genreId = -1;
				$strGenre = ucwords(strtolower(trim($genres[$g])));
				if (!empty($strGenre) && isset($genreIDs[$strGenre])) {
					$genreId = $genreIDs[$strGenre];
				}

				$strGenre = shortenGenre($strGenre);
				$res[$g][DETAIL_COLS['GENRE']] = $genreId != -1 ?
					'<a href="?show=filme&which=genre&just='.$genreId.'&name='.$strGenre.'" target="_parent" class="detailLink" title="filter">'.$strGenre.'</a>' :
					$strGenre;
			}

			if ($g < count($aLang)) {
				$res[$g][DETAIL_COLS['AUDIO1']] = postEditLanguage($aLang[$g]);
			}

			if ($g < count($aCodec)) {
				$res[$g][DETAIL_COLS['AUDIO2']] = postEditACodec($aCodec[$g], isset($atmosx[$g]) ? $atmosx[$g] : null);
			}

			if ($g < count($aChannels)) {
				$res[$g][DETAIL_COLS['AUDIO3']] = postEditChannels($aChannels[$g]);
			}

			if ($g < count($sLang)) {
				$res[$g][DETAIL_COLS['SUB']] = postEditLanguage($sLang[$g], false);
			}
		}

		$res[0][DETAIL_COLS['DUR']]  = $hours;
		$res[1][DETAIL_COLS['DUR']]  = $minutes;
		$res[0][DETAIL_COLS['RATE']] = $rating;
		$res[1][DETAIL_COLS['RATE']] = $votes;
		$res[0][DETAIL_COLS['YEAR']] = isset($jahr) ? '<a href="?show=filme&country=&mode=1&which=year&just='.$jahr.'&name='.$jahr.'" target="_parent" class="detailLink" title="filter">'.$jahr.'</a>' : '';

		if (!empty($width) && !empty($height)) {
			$hdr      = isHDR($filename, $hdrType);
			$resPerf  = getResPerf([$width, $height], $hdr);
			$title    = $f4Ke   ? 'Fake 4K'     : '';
			$title    = $scaled ? 'Upscaled 4K' : $title;
			$resColor = ($resPerf < 4 ? null : CODEC_COLORS[$resPerf]);
			$resStyle = ($f4Ke || $scaled ? 'text-shadow: 0 0 2px rgba(222,0,0,1.75);' : 'font-weight:bold;').(!empty($resColor) ? ' color:'.$resColor.';' : '');
			$resInner = $width.'x'.$height;
			$resSP1   = '';
			$resSP2   = '';

			if (!empty($arOR)) {
				if (!empty($title)) {
					$title .= ' // ';
				}
				$title .= 'overridden';
				$resSP1 = '<span style="font-style:italic;">';
				$resSP2 = '</span>';

				$height = intval($width / $arOR);
			}

			$title = empty($title) ? '' : 'title="'.$title.'"; ';
			$res[0][DETAIL_COLS['VIDEO1']] = '<span '.$title.'style="'.$resStyle.'">'.$resSP1.$resInner.$resSP2.'</span>';
		}
		$res[1][DETAIL_COLS['VIDEO1']] = '<span style="cursor:pointer;" onclick="setAspectRatio('.$idFile.', '.$idMovie.', '.$ar.'); return false;">'.$arSpan.'</span>';

		if (!empty($fps)) {
			$bit10 = $fps[0] >= 10;

			$fPerf = getFpsPerf($fps[1]);
			$color = ($fPerf < 4 ? null : CODEC_COLORS[$fPerf]);
			$res[1][DETAIL_COLS['VIDEO2']] = (!empty($color) ? '<span style="color:'.$color.'; font-weight:bold;">'.$fps[1].' fps</span>' : $fps[1].' fps');
		}

		if (!empty($hdrType)) {
			$tone  = getToneMapping($idFile, $dbh);
			$hPerf = getResPerf(null, true);
			$color = ($hPerf < 4 ? null : CODEC_COLORS[$hPerf]);
			if (!empty($color)) {
				$param = isset($tone['Param']) ? $tone['Param'] : 1.0;
				$res[2][DETAIL_COLS['VIDEO2']]  = '<span class="hdrType" style="cursor:pointer; color:'.$color.'; font-weight:bold;" onclick="setToneMapParam('.$idFile.', '.$idMovie.', '.$param.'); return false;">';
				$res[2][DETAIL_COLS['VIDEO2']] .= postEditHdrType($hdrType);
				if (!empty($tone)) {
					$res[2][DETAIL_COLS['VIDEO2']] .= '<span class="tonemap" title="'.$tone['Method'].': '.$tone['Param'].'">';
					$res[2][DETAIL_COLS['VIDEO2']] .= '(i)';
					$res[2][DETAIL_COLS['VIDEO2']] .= '</span>';
				}
				$res[2][DETAIL_COLS['VIDEO2']] .= '</span>';
			}
		}

		$vCodec = postEditVCodec($vCodec);
		$perf   = (!empty($vCodec) ? decodingPerf($vCodec, $bit10) : 0);
		$color  = ($perf < 4 ? null : CODEC_COLORS[$perf]);
		$vCodec = (!empty($color) ? '<span style="color:'.$color.'; font-weight:bold;">'.$vCodec.($bit10 ? ' 10bit' : '').'</span>' : $vCodec);
		$res[0][DETAIL_COLS['VIDEO2']] = $vCodec;

		$counter       = 0;
		$fetchedImages = 0;
		$acLimitImg    = 15;
		$schauspTblOut = array();
		$artCovers = fetchArtCovers($existArtTable, $dbh);

		$SQL_ACTORS = "SELECT B.".mapDBC('strActor').", A.".mapDBC('strRole').", A.".mapDBC('idActor').", B.".mapDBC('strThumb')." AS actorimage FROM ".mapDBC("actorlinkmovie")." A, ".
			mapDBC("actors")." B WHERE A.".mapDBC('idActor')." = B.".mapDBC('idActor')." AND A.media_type='movie' AND A.".mapDBC('idMovie')." = '".$id."' ORDER BY A.".mapDBC('iOrder').";";

		$acLimit = getActorLimitCount($SQL_ACTORS, 5, $dbh);
		$actors  = querySQL($SQL_ACTORS, false, $dbh);
		foreach($actors as $actor) {
			$artist      = $actor[mapDBC('strActor')];
			$idActor     = $actor[mapDBC('idActor')];
			$actorpicURL = $actor['actorimage'];

			$actorimg = null;
			if ($fetchedImages < $acLimitImg) {
				$actorimg = getActorThumb($artist, $actorpicURL, false);
				if (isFile($actorimg) && $existArtTable) {
					$fetchedImages++;
					if (!empty($artCovers) && isset($artCovers['actor'][$idActor])) {
						$actorimg = $artCovers['actor'][$idActor]['cover'];

					} else {
						$detail = fetchFromDB("SELECT url FROM art WHERE media_type = 'actor' AND type = 'thumb' AND media_id = '$idActor';", false, $dbh);
						$url = isset($detail['url']) ? $detail['url'] : null;
						if (!empty($url)) {
							$actorimg = getActorThumb($url, $url, true);
						}
					}
				}

				wrapItUp('actor', $idActor, $actorimg);
			}

			$schauspTblOut[$counter]  = '<tr'.($counter >= $acLimit ? ' name="artists" style="display:none;"' : '').'>';
			$schauspTblOut[$counter] .= '<td class="art">';
			$schauspTblOut[$counter] .= '<a href="?show=filme&which=artist&just='.$idActor.'&name='.$artist.'" target="_parent" ';
			$schauspTblOut[$counter] .= !isFile($actorimg) ? 'title="filter"' : ' class="hoverpic" rel="'.getImageWrap($actorimg, $idActor, 'actor', 0).'" title="'.$artist.'"';
			$schauspTblOut[$counter] .= '>'.$artist.'</a>';
			$schauspTblOut[$counter] .= '</td>';
			$schauspTblOut[$counter] .= '<td class="role">';
			$schauspTblOut[$counter] .= isset($actor[mapDBC('strRole')]) ? str_replace('/', ' / ', $actor[mapDBC('strRole')]) : '&nbsp;';
			$schauspTblOut[$counter] .= '</td>';
			$schauspTblOut[$counter] .= '</tr>';
			$schauspTblOut[$counter] .= "\r\n";

			$counter++;
		}
		$actors = null;
		unset($actors);

		if ($counter > 0) {
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

			if ($counter > $acLimit) {
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
			echo '<th class="streaminfoAV'.(count($aLang) > 0 ? '' : '2').'" colspan="2">Video</th>';
			if (!empty($aCodec)) {
				echo '<th class="streaminfoAV'.(count($aLang) > 0 ? '' : '3').'" colspan="3">Audio</th>';
			} else { $spalten--; }
			if (!empty($sLang)) {
				echo '<th class="streaminfoLasTD streaminfoBorderTH">Sub</th>';
			} else { $spalten--; }
			echo '</tr>';
			echo "\r\n";
			echo '<tr class="abstand"><td colspan="'.count(DETAIL_COLS).'"></td></tr>';
			echo "\r\n";

			$zeilen = 0;
			$colspan = 0;
			$hiddenGenres = count($genres)-2;
			$hiddenSubs   = count($sLang)-2;
			for ($i = 0; $i < $max; $i++) {
				echo '<tr'.($i >= 2 && ($hiddenGenres > 1 || $hiddenSubs > 1) ? ' name="genres" style="display:none;"' : '').'>';
				for ($j = 0; $j < count($res[$i]); $j++) {
					$val = $res[$i][$j];
					$val = (empty($val) ? '' : trim($val));

					$tdClass = '';
					switch ($j) {
						case DETAIL_COLS['DUR']:
							if ($i > 0 && $i >= count($genres) && $colspan > 1) {
								$tdClass = ' class="streaminfoGenre"';
							}
							break;

						case DETAIL_COLS['AUDIO1']:
						case DETAIL_COLS['VIDEO1']:
							$tdClass = ' class="streaminfoAV streaminfoBorder"';
							break;

						case DETAIL_COLS['AUDIO2']:
						case DETAIL_COLS['VIDEO2']:
							$tdClass = ' class="streaminfoAV"';
							break;

						case DETAIL_COLS['AUDIO3']:
							$tdClass = ' class="'.(empty($sLang) ? 'streaminfoLasTD' : 'streaminfoAV').'"';
							break;

						case DETAIL_COLS['SUB']:
							$tdClass = ' class="streaminfoLasTD streaminfoBorder"';
							break;

						case DETAIL_COLS['GENRE']:
						case DETAIL_COLS['YEAR']:
						case DETAIL_COLS['RATE']:
						default:
							break;
					}

					echo '<td'.$tdClass.'>'.$val.'</td>';
				}

				echo '</tr>';
				$zeilen++;
			}

			if ($zeilen > 2 && ($hiddenGenres > 1 || $hiddenSubs > 1)) {
				echo '<tr id="genreDots">';
				echo '<td colspan="'.(DETAIL_COLS['GENRE']).'"></td>';
				echo '<td class="streaminfoGenre lefto">';
				if ($hiddenGenres >= 1) {
					echo '<span class="moreDots" onclick="showHiddenTRs(\'genreDots\', \'genres\', true);" title="mehr...">...</span>';
				}
				echo '</td>';
					echo '<td colspan="'.($hiddenSubs >= 1 ? count(DETAIL_COLS)-(DETAIL_COLS['GENRE']+2) : count(DETAIL_COLS)-(DETAIL_COLS['GENRE']+1)).'"></td>';
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

			echo '<tr class="abstand"><td colspan="'.count(DETAIL_COLS).'"></td></tr>';
			echo '<tr><td class="streaminfoLLine streaminfoLasTD" colspan="'.$spalten.'">';
			if (!isDemo()) {
				echo '<span class="filename lefto"'.($isAdmin ? ' title="'.$path.'"' : '').'>'.encodeString($filename).'</span>';
				echo '<span class="filesize righto" title="'.formatToDeNotation($size).'">'._format_bytes($size).'</span>';
			}
			echo '</td></tr>';
			echo "\r\n";

			if (!is_null($source) || isAdmin()) {
				echo '<tr><td style="padding-top:5px;" colspan="'.$spalten.'">';
				$href = '';
				if ($isAdmin) {
					$href .= ' <a class="fancy_movieset" href="./changeSource.php?idFile='.$idFile. '"><img style="border:0; height:9px;" src="img/edit-pen.png" title="change source" alt="change source"/></a>';
				}
				echo '<span class="filename lefto">Source: '.SOURCE[$source].' '.$href.'</span>';
				echo '</td></tr>';
				echo "\r\n";
			}

			$result = fetchFromDB("SELECT S.strSet, S.idSet FROM sets S, movie M WHERE M.idSet = S.idSet AND M.idMovie = ".$id.";", false, $dbh);
			if (!empty($result)) {
				echo '<tr><td class="streaminfoLasTD" style="padding-top:10px;" colspan="'.$spalten.'">';
				$set   = isset($result['strSet']) ? $result['strSet'] : null;
				$idSet = isset($result['idSet'])  ? $result['idSet']  : null;
				$isSet = !empty($set) && isset($idSet);
				$result = null;
				unset($result);

				$href = ($isSet ? '<b>'.$set.'</b>' : '<i>Not in any set!</i>');
				if ($isSet) {
					$href = '<a style="color:dimgray;" href="?show=filme&which=set&sort=titlea&just='.$idSet.'&name='.$set.'" target="_parent">'.$href.'</a>';
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

	unset( $_SESSION['show'], $_SESSION['idMovie'] );


//- FUNCTIONS -//
	function getNeededColspan($res, $spalte) {
		$colspan = 1;
		if ($spalte == DETAIL_COLS['VIDEO1'] || $spalte == DETAIL_COLS['AUDIO1'] || $spalte == DETAIL_COLS['SUB']) {
			return $colspan;
		}

		for ($j = $spalte; $j < count($res); $j++) {
			$val = empty($res[$j]) ? null : trim($res[$j]);
			if (!empty($val)) {
				break;
			}

			$colspan++;
		}

		if ($spalte == 1) {
			return (min($colspan, DETAIL_COLS['GENRE'] + 1));
		}

		return $colspan;
	}

	function getCovers($fnam, $cover, $idMovie, $dbh = null) {
		$existArtTable = $GLOBALS['existArtTable'];

		$res = array();
		if (isFile(getCoverMid($fnam, $cover, false))) {
			$res[0] = getCoverMid($fnam, $cover, false); //cover
			$res[1] = getCoverBig($fnam, $cover, false); //cover_big

		} else if ($existArtTable) {
			$res2 = querySQL("SELECT url,type FROM art WHERE media_type = 'movie' AND (type = 'poster' OR type = 'thumb') AND media_id = '".$idMovie."';", false, $dbh);
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

	function getFanartCover($fnam, $idMovie, $dbh = null) {
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
			$row2 = fetchFromDB("SELECT url FROM art WHERE media_type = 'movie' AND type = 'fanart' AND media_id = '".$idMovie."';", false, $dbh);
			$url  = isset($row2['url']) ? $row2['url'] : null;
			if (!empty($url)) {
				return getFanart($url, $url, true);
			}
		}

		return null;
	}

	function getCountry($idMovie, $dbh = null) {
		$SQL = "SELECT c.".mapDBC('strCountry')." FROM ".mapDBC('countrylinkmovie')." cl, country c, movie m WHERE m.idMovie = cl.".mapDBC('idMovie')." AND cl.".mapDBC('idCountry')." = c.".mapDBC('idCountry')." AND m.idMovie = '".$idMovie."';";
		$res = querySQL($SQL, false, $dbh);
		$row = $res->fetch();

		return isset($row[mapDBC('strCountry')]) ? $row[mapDBC('strCountry')] : null;
	}

	function getStudio($idMovie, $dbh = null) {
		$res = querySQL("SELECT name FROM studio WHERE studio_id = (SELECT studio_id FROM studio_link WHERE media_type = 'movie' AND media_id = '".$idMovie."');", false, $dbh);
		$row = $res->fetch();

		return isset($row['name']) ? $row['name'] : null;
	}

	function convertSecondsToHM($seconds) {
		$result = array('hrs' => null, 'min' => null);
		if (!empty($seconds)) {
			$minutes = floor($seconds/60);
			$hours   = floor($minutes/60).':'.sprintf ("%02d", $minutes % 60).'\'';
			$result['hrs'] = $hours;
			$result['min'] = $minutes.'\'';
		}

		return $result;
	}

	function getActorLimitCount($SQL, $acLimit, $dbh = null) {
		$result = querySQL($SQL, false, $dbh);

		$actCnt  = 0;
		foreach($result as $tmp) { $actCnt++; }
		if ($actCnt-1 == $acLimit) { $acLimit++; }

		$result = null;
		unset($result);

		return $acLimit;
	}

	function fetchGenresIDs($genres, $dbh = null) {
		for ($g = 0; $g < count($genres); $g++) {
			$genres[$g] = "'".$genres[$g]."'";
		}

		$resultG = querySQL("SELECT * FROM genre WHERE ".mapDBC('strGenre')." IN (".implode(',', $genres).");", false, $dbh);
		$idGenre = array();
		foreach ($resultG as $rowG) {
			if (!isset($rowG[mapDBC('strGenre')])) {
				continue;
			}
			$str = ucwords(strtolower(trim($rowG[mapDBC('strGenre')])));
			if (empty($str)) {
				continue;
			}
			if (!isset($rowG[mapDBC('idGenre')])) {
				continue;
			}

			$idGenre[$str] = $rowG[mapDBC('idGenre')];
		}

		return $idGenre;
	}
//- FUNCTIONS -//
?>
