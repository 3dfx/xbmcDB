<?php
include_once "auth.php";
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";
include_once "./template/_SERIEN.php";

	header("Content-Type: text/html; charset=UTF-8");

	$admin = isAdmin();
	if (!$admin) { die(';-)'); };

	$getSeason     = -1;
	$getEpisode    = -1;
	$idShow        = -1;
	$idSeason      = -1;
	$idEpisode     = -1;
	$idTvdb        = -1;
	$source        = null;
	$update        = 0;
	$closeFrame    = 0;

	$idSelected     = false;
	$seasonSelected = false;
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		$getSeason  = getEscPost('season');
		$getEpisode = getEscPost('episode');
		$idShow     = getEscPost('idShow');
		$idSeason   = getEscPost('idSeason');
		$idEpisode  = getEscPost('idEpisode');
		$idTvdb     = getEscPost('idTvdb');
		$update     = getEscPost('update');
		$source     = getEscPost('source');
		$closeFrame = getEscPost('closeFrame');
		$idSelected = true;

	} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		$getSeason  = getEscGet('season');
		$getEpisode = getEscGet('episode');
		$idShow     = getEscGet('idShow');
		$idSeason   = getEscGet('idSeason');
		$idEpisode  = getEscGet('idEpisode');
		$idTvdb     = getEscGet('idTvdb');
		$update     = getEscGet('update');
		$source     = getEscGet('source');
		$closeFrame = getEscGet('closeFrame');
		$idSelected = true;
	}

	$serien = fetchSerien(null, null);
	$serien->sortSerien();

	if ($idShow == -1) { $idShow = $serien->getSerieIdByIdTvdb($idTvdb); }
	if ($idShow == -1) { die('TV-Show not found!'); }
	$serie = $serien->getSerie($idShow);
	if ($serie == null) { die('TV-Show is null!'); }

	if ($update && $idEpisode == -1) { die('idEpisode not set!'); }
	$episodeToUp = $serie->findEpisode($idEpisode);
	if ($update && $episodeToUp == null) { die('Selected Episode is null!'); }

	if ($idTvdb == -1) { $idSelected = false; }
	if ($getSeason == -1 && $getEpisode == -1) { $seasonSelected = false; }

	$episodes = null;
	if ($idSelected) {
		$episodes = getShowInfo($idTvdb);
		if (empty($episodes)) { die("Couldn't fetch show info!"); }
	}

	$showPath = $serie->getShowpath();

	$item = null;
	if ($seasonSelected) { $item = getEpisodeInfo($episodes, $getSeason, $getEpisode); }
	
	$epDesc = null;
	$guests = null;
	$SQL = $GLOBALS['SerienSQL'].' AND V.idEpisode = '.$idEpisode.';';
	$result = querySQL($SQL);
	foreach($result as $row) {
		$epDesc = trimDoubles(trim($row['epDesc']));
		$guests = $row['guests'];
	}
	
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
	<link rel="stylesheet" type="text/css" href="./class.css" />
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.js"></script>
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			$('.styled-select').customStyle();
			showEpInfos();
<?php
//	if (!$update && $idEpisode != -1) {
?>
			$('#filename').focus();
<?php
//	}
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

		function fetchEpisodeInfo(idEp, returnResult) {
			var regieField  = document.getElementById('regie');
			var guestField  = document.getElementById('gast_autor');
			var ratingField = document.getElementById('rating');

			var result = null;
			$.ajax({
				url:    'tvdbEpisode.php?idEp='+idEp,
				type:   'GET',
				async:   returnResult ? false : true,
				timeout: 500,
				success: function(json) {
					if (json == null) { return null; }
					var obj = JSON.parse(json);
					if (obj == null) { return null; }

					ratingField.value = '';
					guestField.value  = '';
					regieField.value  = '';

					if (obj.data.siteRating != rating.value && obj.data.siteRating != 0) {
						rating.value = obj.data.siteRating;
					}

					guests  = obj.data.guestStars;
					guests_ = '';
					if (guests != null && guests.length != 0) {
						for (var i = 0; i < guests.length; i++) {
							guests_ = guests_ + guests[i] + ' (Gast Star)';
							if (i < guests.length-1)
								guests_ = guests_ + ' / ';
						}
					}

					writers  = obj.data.writers;
					if (writers != null && writers.length != 0) {
						writers_ = '';
						for (var i = 0; i < writers.length; i++) {
							writers_ = writers_ + writers[i] + ' (Autor)';
							if (i < writers.length-1)
								writers_ = writers_ + ' / ';
						}
						guestField.value = guests_ + (guests_.length == 0 || writers_.length == 0 ? '' : ' / ') + writers_;
					}

					directors  = obj.data.directors;
					if (directors != null && directors.length != 0) {
						directors_ = '';
						for (var i = 0; i < directors.length; i++) {
							directors_ = directors_ + directors[i];
							if (i < directors.length-1)
								directors_ = directors_ + ' / ';
						}
						regieField.value = directors_;
					}

					result = obj;
				},
				error: function (xhr, ajaxOptions, thrownError) {
					console.log( "TVdb error! / " + xhr.status + " / " + thrownError );
				}
			});

			return result;
		}

		function retrieveShowInfos() {
			var sel = document.getElementById('showSelect');
			var url = 'addEpisode?idShow=' + sel.value;

			window.location.href=url;
		}

		function showEpInfos() {
			var sel = document.getElementById('showEpisode');
			var id = sel.value;

			if (episodes == null) { return; }

			for (var i = 0; i < episodes.length; i++) {
				var season = episodes[i];
				for (var j = 0; j < season.length; j++) {
					var episode = season[j];
					var idEp = episode[0];
					var snum = episode[1];
					var ep   = episode[2];

					if (id == snum+'-'+ep) {
						var title     = document.getElementById('title');
						var airdate   = document.getElementById('airdate');
						var desc      = document.getElementById('desc');

						title.value   = episode[3];
						airdate.value = episode[4];
						desc.value    = descriptions == null ? "" : descriptions[i][j];
						fetchEpisodeInfo(idEp, false);
					}
				}
			}
		}

		function saveStrPath() {
			var sel = document.getElementById('idPath');
			var id = sel.value;
			var strPath = ''
			for (var i = 0; i < paths.length; i++) {
				var path = paths[i];
				if (id == path[0]) {
					strPath = path[1];
					break;
				}
			}

			var str = document.getElementById('strPath');
			str.value = strPath;
		}

		function saveStrSeason() {
			var sel = document.getElementById('idSeason');
			var id = sel.value;
			var strSeason = ''
			for (var i = 0; i < paths.length; i++) {
				var path = paths[i];
				if (id == path[0]) {
					strSeason = path[1];
					break;
				}
			}

			var str = document.getElementById('strSeason');
			str.value = strSeason;
		}

<?php
	if ($idSelected) {
		postPathsJS();
		echo "\r\n\r\n";
		postEpisodesJS($episodes);
	}
?>
	</script>
	</head>
	<body style="margin:4px 5px; padding:0px !important;">
	<form action="dbEdit.php?act=<?php echo ($update ? 'updateEpisode' : 'addEpisode'); ?>" name="episodefrm" style="height:430px;" method="post">
	<table id="showSelectTable" class="key film" style="width:550px; padding:0px; z-index:1; margin:0px !important;">
<?php
	if (!$idSelected) {
?>
		<tr>
		<td style="padding-left:5px; width:100px;">Serie:</td>
		<td style="padding-left:5px; zwidth:450px;">
			<div style="float:left; width:200px; padding:2px 0px 0px 0px;">
			<select id="showSelect" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:195px !important; height:18px !important;" size="1" onchange="retrieveShowInfos();">
				<option value="-1"> </option>
<?php postSerien(); ?>
			</select>
			</div>
		</td>
		</tr>
<?php
	}
	if ($idSelected) {
		echo "\t\t".'<tr>'."\r\n";
		echo "\t\t".'<td style="padding-left:5px; width:100px; font-weight:bold; text-align:center;" colspan="2">'.$serie->getName().'</td>'."\r\n";
		echo "\t\t".'</tr>'."\r\n";
?>

		<tr style="border-top:0px;">
		<td style="padding-left:5px; width:100px;">Season:</td>
		<td style="padding-left:5px; zwidth:450px;">
			<div style="float:left; zwidth:450px; padding:2px 0px 0px 0px;">
			<select id="idSeason" name="idSeason" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:435px !important; height:18px !important;" size="1" onchange="saveStrSeason();">
				<option value="-1"> </option>
<?php postSeasonIds($idShow); ?>
			</select>
			</div>
		</td>
		</tr>

		<tr style="border-top:0px;">
		<td style="padding-left:5px; width:100px;">Episode:</td>
		<td style="padding-left:5px; zwidth:450px;">
			<div style="float:left; zwidth:450px; padding:2px 0px 0px 0px;">
			<select id="showEpisode" name="showEpisode" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:435px !important; height:18px !important;" size="1" onchange="showEpInfos();" <?php echo ($update ? '!DISABLED ' : ''); ?>>
				<option value="-1"> </option>
<?php postEpisoden($GLOBALS['episodes']); ?>
			</select>
			</div>
		</td>
		</tr>
		
		<tr style="border-top:0px;">
		<td style="padding-left:5px; width:100px;">Path:</td>
		<td style="padding-left:5px; zwidth:450px;">
			<div style="float:left; zwidth:450px; padding:2px 0px 0px 0px;">
			<?php /* <select id="idPath" name="idPath" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:435px !important; height:18px !important;" size="1" onchange="saveStrPath();" <?php echo ($update ? 'DISABLED ' : ''); ?>> */ ?>
			<select id="idPath" name="idPath" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:435px !important; height:18px !important;" size="1" onchange="saveStrPath();">
				<option value="-1"> </option>
<?php postPaths(); ?>
			</select>
			</div>
		</td>
		</tr>

		<tr style="border-top:0px;">
		<td style="padding-left:5px; width:100px;">Source:</td>
		<td style="padding-left:5px; zwidth:450px;">
			<div style="float:left; zwidth:450px; padding:2px 0px 0px 0px;">
			<select id="source" name="source" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:435px !important; height:18px !important;" size="1">
				<option value="-1"> </option>
<?php
				$SOURCE = $GLOBALS['SOURCE'];
				$i = 0;
				for ($i = 1; $i < count($SOURCE); ++$i) {
					if (empty($SOURCE[$i]))
						continue;
					echo "\t\t\t\t".'<option value="'.$i.'"'.($source == $i ? ' SELECTED' : '').'>'.$SOURCE[$i].'</option>'."\r\n";
				}
?>
			</select>
			</div>
		</td>
		</tr>

<?php
		echo "\t\t".'<tr>';
		echo '<td style="padding-left:5px; width:100px;">Filename:</td>';
		$filename = ($update ? $episodeToUp->getFilename() : '');
		echo '<td style="padding-left:5px; zwidth:450px;">';
		/* echo '<input type="text" id="filename" name="filename" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="'.$filename.'" '.($update ? 'DISABLED ' : '').'/>'; */
		echo '<input type="text" id="filename" name="filename" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="'.$filename.'"/>';
		$idFile = ($update ? $episodeToUp->getIdFile() : -1);
		echo '<input type="hidden" id="idFile" name="idFile" value="'.$idFile.'" style="font-size:10px; width:20px;" />';
		echo '</td>';
		echo '</tr>'."\r\n";
?>
		<tr>
		<td style="padding-left:5px; width:100px;">Titel:</td>
		<td style="padding-left:5px; zwidth:450px;"><input type="text" id="title" name="title" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="<?php echo trimDoubles(trim($episodeToUp->getName())); ?>" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px; width:100px;">Air-Date:</td>
		<td style="padding-left:5px; zwidth:450px;"><input type="text" id="airdate" name="airdate" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="<?php echo trimDoubles(trim($episodeToUp->getAirDate())); ?>" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px; width:100px;">Regie:</td>
		<td style="padding-left:5px; zwidth:450px;"><input type="text" id="regie" name="regie" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px; width:100px;">Gast:</td>
		<td style="padding-left:5px; zwidth:450px;"><input type="text" id="gast_autor" name="gast_autor" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="<?php echo $guests; ?>" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px; width:100px;">Rating:</td>
		<td style="padding-left:5px; zwidth:450px;"><input type="text" id="rating" name="rating" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="<?php echo trimDoubles(trim($episodeToUp->getRating())); ?>" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px; width:100px;">Desc:</td>
		<td style="padding-left:5px; zwidth:450px;"><textarea id="desc" name="desc" class="key inputbox" style="width:450px; height:150px;" onfocus="this.select();" onclick="this.select();"><?php echo $epDesc; ?></textarea></td>
		</tr>
<?php
		echo "\t\t".'<tr>';
		echo "\t\t".'<td style="display:none;" colspan="2">';
		echo '<input type="text" id="idShow" name="idShow" style="width:10px;" value="'.$idShow.'"/>';
		echo '<input type="text" id="idEpisode" name="idEpisode" style="width:10px;" value="'.$idEpisode.'"/>';
		echo '<input type="text" id="idTvdb" name="idTvdb" style="width:10px;" value="'.$idTvdb.'" />';
		echo '<input type="text" id="strPath" name="strPath" style="width:10px;" value="-1" />';
		echo '<input type="text" id="strSeason" name="strSeason" style="width:10px;" value="-1"/>';
		echo '</td>';
		echo "\t\t".'</tr>'."\r\n";
	} //if ($idSelected)
	$value = ($update ? 'Update' : 'Add');
	echo '<tr><td colspan="2" class="righto" style="padding:10 0px !important;"><div style="float:right; padding:0px 15px;"><input type="submit" value="'.$value.'" class="key okButton" onclick="this.blur();" /></div></tr>';
?>
	</table>
	</form>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function postSerien() {
	$serie = $GLOBALS['serie'];
	postSerie($serie->getIdTvdb(), $serie->getName());
}

function postSerie($id, $name) {
	$idTvdb = $GLOBALS['idTvdb'];
	echo "\t\t\t".'<option value="'.$id.'"'.($idTvdb == $id ? ' SELECTED' : '').'>'.$name.'</option>';
	echo "\r\n";
}

function postEpisoden($episodes) {
	$selSE = '';
	if ($GLOBALS['update']) {
		$episodeToUp = $GLOBALS['episodeToUp'];
		$selSE = $episodeToUp->getSeason().'-'.$episodeToUp->getEpNum();
	}

	$lS = 0;
	$lE = 0;

	foreach($episodes as $xdex => $season) {
		foreach($season as $ydex => $episode) {
			if ($episode == null || get($episode, 'airedEpisodeNumber') == null)
				continue;
			$s = '';
			if (get($episode, 'airedSeason') != null)
				$s = intval(trim($episode['airedSeason']));
			if ($s == '') { $s = 0; }
			$e = intval(trim($episode['airedEpisodeNumber']));
			$se = sprintf("%d-%d", $s, $e);
			echo "\t\t\t\t".'<option value="'.$se.'"'.($se == $selSE ? ' SELECTED' : '').'>'.sprintf("S%02d E%02d", $s, $e).'</option>'."\r\n";

			$lS = $s;
			$lE = $e;
		}
	}

	$tmp = explode('-', $selSE);
    $selS = intval($tmp[0]);
    $selE = intval($tmp[1]);

    $firstRun = true;
    for ($s_ = $lS; $s_ <= $selS; $s_++) {
        for ($e_ = $lE; $e_ <= $selE; $e_++) {
            if ($firstRun) {
                $firstRun = false;
                continue;
            }
            $se = sprintf("%d-%d", $s_, $e_);
            echo "\t\t\t\t".'<option value="'.$se.'"'.($se == $selSE ? ' SELECTED' : '').'>'.sprintf("S%02d E%02d", $s_, $e_).' [x]</option>'."\r\n";
        }
    }
}

function postSeasonIds($idShow) {
	$ids = fetchSeasonIds($idShow);
	
	$idSeason = -1;
	if ($GLOBALS['update']) {
		$episodeToUp = $GLOBALS['episodeToUp'];
		$idSeason = $episodeToUp->getIdSeason();
	}
	#echo $idSeason;
	
	for ($i = 0; $i < count($ids); $i++) {
		$name = $ids[$i][1];
		$id   = $ids[$i][0];
		if ($name == "-1")
			continue;
		echo "\t\t\t\t".'<option value="'.$id.'"'.($idSeason == $id ? 'SELECTED' : '').'>'.sprintf("%02d", $name).'</option>';
		echo "\r\n";
	}
}

function postPaths() {
	$paths    = fetchPaths();
	$showPath = $GLOBALS['showPath'];
	
	$idPath = -1;
	if ($GLOBALS['update']) {
		$episodeToUp = $GLOBALS['episodeToUp'];
		$idPath = $episodeToUp->getIdPath();
	}
	
	for ($i = 0; $i < count($paths); $i++) {
		$name = $paths[$i][1];
		if (strpos($name, $showPath) === false) { continue; }
		
		$id   = $paths[$i][0];
		echo "\t\t\t\t".'<option value="'.$id.'"'.($idPath == $id ? 'SELECTED' : '').'>'.$name.'</option>';
		echo "\r\n";
	}
}

function postPathsJS() {
	$paths = fetchPaths();
	
	echo "\t\t";
	echo 'var paths = new Array(';
	for ($i = 0; $i < count($paths); $i++) {
		$id   = $paths[$i][0];
		$name = $paths[$i][1];
		if (empty($name)) { continue; }
		
		echo "\r\n\t\t\t";
		echo 'new Array("'.$id.'","'.$name.'")';
		if ($i < count($paths)-1) {
			echo ',';
		}
	}
	echo "\r\n\t\t";
	echo ');';
}

function postEpisodesJS($episodes) {
	foreach ($episodes as $xdex => $season) {}
	$cEpisodes = count($episodes);

	$arrCheck = 0;
	foreach ($episodes as $xdex => $season) {
		if (empty($season)) { continue; }
		foreach($season as $ydex => $episode) {
			if (empty($episode)) { continue; }
			$arrCheck++;
		}
	}
	#echo $arrCheck;
	
	$index = 1;
	echo "\t\t";
	echo 'var episodes = ';
	if (empty($arrCheck)) {
		echo 'null;'."\r\n";
	} else {
		echo 'new Array(';
		foreach ($episodes as $xdex => $season) {
			echo "\r\n\t\t\t";
			echo 'new Array(';
			$lastSeason = 0;
			$jndex = 1;
			foreach($season as $ydex => $episode) {
				$seas       = get($episode, 'airedSeason');
				if ($seas == null || $seas == '') { $seas = 0; }
				$lastSeason = $seas;
				$idEpisode  = get($episode, 'id');
				#$gastStars  = processCredits(get($episode, 'GuestStars'), 'Gast Star');
				#$autoren    = processCredits(get($episode, 'Writer'), 'Autor');
				#$spl        = ($gastStars != '' && $autoren != '' ? ' / ' : '');
				#$gast_autor = $gastStars.$spl.$autoren;
				#$regie      = processCredits(get($episode, 'Director'), null);
				#$rating     = getRating(get($episode, 'Rating'));

				echo "\r\n\t\t\t\t";
				echo 'new Array(';
				#echo '"'.$seas.'","'.get($episode, 'airedEpisodeNumber').'",'.json_encode( get($episode, 'episodeName') ).',"'.get($episode, 'firstAired').'","'.$rating.'",'.json_encode( $regie ).','.json_encode( $gast_autor );
				echo '"'.$idEpisode.'","'.$seas.'","'.get($episode, 'airedEpisodeNumber').'",'.json_encode( get($episode, 'episodeName') ).',"'.get($episode, 'firstAired').'"';
				echo ')';
				if ($jndex < count($season)) {
					echo ', ';
				} else {
					echo "\r\n\t\t\t";
				}

				$jndex++;
			}
			echo ')';
			if ($index < $cEpisodes) {
				echo ', ';
			}
			$index++;
		}
		echo "\r\n\t\t";
		echo ');';
		echo "\r\n";
		echo "\r\n";
	}
	
	$index = 1;
	echo "\t\t";
	echo 'var descriptions = ';
	if (empty($arrCheck)) {
		echo 'null;'."\r\n";
	} else {
		echo 'new Array(';
		foreach ($episodes as $xdex => $season) {
			echo "\r\n\t\t\t";
			echo 'new Array(';
			$jndex = 1;
			foreach($season as $ydex => $episode) {
				echo "\r\n\t\t\t\t";
				echo 'new Array(';
				echo json_encode( get($episode, 'overview') );
				echo ')';
				if ($jndex < count($season)) {
					echo ', ';
				}
				$jndex++;
			}
			echo "\r\n\t\t\t".')';
			if ($index < $cEpisodes) {
				echo ', ';
			}
			$index++;
		}
		echo "\r\n\t\t";
		echo ');';
	}
	echo "\r\n";
}

function get($episode, $key) {
	if ($episode == null || $key == null)
		return null;
	if (isset($episode[$key]))
		return $episode[$key];
	return null;
}

function processCredits($input, $role) {
	if ($input == null)
		return '';
	if (substr($input, 0, 1) == '|') { $input = substr($input, 1); }
	if (substr($input, -1) == '|') { $input = substr($input, 0, strlen($input)-1); }

	if (trim($input) == '') { return ''; }

	$tmp = explode("|", $input);
	$name = $role != null ? ' ('.$role.')' : '';
	if (count($tmp) > 1) { $input = implode($name.' / ', $tmp); }
	$input .= $name;
	
	return str_replace('  ', ' ', $input);
}

function getRating($rating) {
	if ($rating == null || trim($rating) == '') {
		return '0.000000';
	}
	
	if (strlen($rating) < 8) {
		if (!substr_count($rating, '.')) { $rating .= '.'; }
		do { $rating .= '0'; } while(strlen($rating) < 8);
	}
	
	return $rating;
}

?>
