<?php
	include_once "auth.php";
	include_once "check.php";
	
	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "globals.php";
	include_once "_SERIEN.php";

	$admin = isAdmin();
	if (!$admin) { die(';-)'); };
	
	$getSeason     = -1;
	$getEpisode    = -1;
	$idShow        = -1;
	$idEpisode     = -1;
	$idTvdb        = -1;
	$update        = 0;
	$closeFrame    = 0;
	
	$idSelected = false;
	$seasonSelected = false;
	if ($_SERVER['REQUEST_METHOD'] == 'POST') {
		if (isset($_POST['season'])) { $getSeason = trim($_POST['season']); }
		if (isset($_POST['episode'])) { $getEpisode = trim($_POST['episode']); }
		if (isset($_POST['idShow'])) { $idShow = trim($_POST['idShow']); }
		if (isset($_POST['idEpisode'])) { $idEpisode = trim($_POST['idEpisode']); }
		if (isset($_POST['idTvdb'])) { $idTvdb = trim($_POST['idTvdb']); }
		if (isset($_POST['update'])) { $update = trim($_POST['update']); }
		if (isset($_POST['closeFrame'])) { $closeFrame = trim($_POST['closeFrame']); }
		
		$idSelected = true;
		
	} else if ($_SERVER['REQUEST_METHOD'] == 'GET') {
		if (isset($_GET['season'])) { $getSeason = trim($_GET['season']); }
		if (isset($_GET['episode'])) { $getEpisode = trim($_GET['episode']); }
		if (isset($_GET['idShow'])) { $idShow = trim($_GET['idShow']); }
		if (isset($_GET['idEpisode'])) { $idEpisode = trim($_GET['idEpisode']); }
		if (isset($_GET['idTvdb'])) { $idTvdb = trim($_GET['idTvdb']); }
		if (isset($_GET['update'])) { $update = trim($_GET['update']); }
		if (isset($_GET['closeFrame'])) { $closeFrame = trim($_GET['closeFrame']); }
		
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
	if ($idSelected) { $episodes = getShowInfo($idTvdb); }
	if ($idSelected && $episodes == null) { die('NOTHING FOUND!'); }
	
	$item = null;
	if ($seasonSelected) { $item = getEpisodeInfo($episodes, $getSeason, $getEpisode); }
?>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<html>
	<head>
	<link rel="stylesheet" type="text/css" href="./class.css" />
	<link rel="stylesheet" type="text/css" href="./template/js/fancybox/jquery.fancybox.css" media="screen" />
	<!-- <link rel="stylesheet" type="text/css" href="./template/js/bootstrap/select/select2.css" media="screen" /> -->
	<script type="text/javascript" src="./template/js/jquery.min.js"></script>
	<script type="text/javascript" src="./template/js/fancybox/jquery.fancybox.js"></script>
	<!-- <script type="text/javascript" src="./template/js/bootstrap/select/select2.min.js"></script> -->
	<script type="text/javascript" src="./template/js/customSelect.jquery.js"></script>
	<script type="text/javascript">
		$(document).ready(function(){
			$('.styled-select').customStyle();
			//$('.styled-select').select2();
			//$('.select2-search').hide();
			
			showEpInfos();
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
		
		function retrieveShowInfos() {
			var sel = document.getElementById('showSelect');
			var url = 'addEpisode?idShow=' + sel.value;
			
			window.location.href=url;
		}

		function showEpInfos() {
			var sel = document.getElementById('showEpisode');
			var id = sel.value;
			for (var i = 0; i < episodes.length; i++) {
				var season = episodes[i];
				for (var j = 0; j < season.length; j++) {
					var episode = season[j];
					var snum = episode[0];
					var ep = episode[1];
					if (id == snum+'-'+ep) {
						var title = document.getElementById('title');
						var regie = document.getElementById('regie');
						var gast_autor = document.getElementById('gast_autor');
						var airdate = document.getElementById('airdate');
						var rating = document.getElementById('rating');
						var desc = document.getElementById('desc');

						title.value = episode[2];
						airdate.value = episode[3];
						rating.value = episode[4];
						regie.value = episode[5];
						gast_autor.value = episode[6];
						desc.value = descriptions[i][j];
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
	
<?php
	if ($idSelected) {
		postPathsJS();
		echo "\n\r\n\r";
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
		<td style="padding-left:5px;">Serie:</td>
		<td style="padding-left:7px; width:450px;">
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
		echo "\t\t".'<tr>';
		echo "\t\t".'<td style="padding-left:5px; font-weight:bold; text-align:center;" colspan="2">'.$serie->getName().'</td>';
		echo "\t\t".'</tr>';

?>
		<tr style="border-top:0px;">
		<td style="padding-left:5px;">Episode:</td>
		<td style="padding-left:7px; width:450px;">
			<div style="float:left; width:200px; padding:2px 0px 0px 0px;">
			<select id="showEpisode" name="showEpisode" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:195px !important; height:18px !important;" size="1" onchange="showEpInfos();" <?php echo ($update ? '!DISABLED ' : ''); ?>>
				<option value="-1"> </option>
<?php postEpisoden($GLOBALS['episodes']); ?>
			</select>
			</div>
		</td>
		</tr>

		<tr style="border-top:0px;">
		<td style="padding-left:5px;">Path:</td>
		<td style="padding-left:7px; width:450px;">
			<div style="float:left; width:450px; padding:2px 0px 0px 0px;">
			<?php /* <select id="idPath" name="idPath" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:445px !important; height:18px !important;" size="1" onchange="saveStrPath();" <?php echo ($update ? 'DISABLED ' : ''); ?>> */ ?>
			<select id="idPath" name="idPath" class="styled-select" style="position:absolute; opacity:0; font-size:10px !important; width:445px !important; height:18px !important;" size="1" onchange="saveStrPath();">
				<option value="-1"> </option>
<?php postPaths(); ?>
			</select>
			</div>
		</td>
		</tr>
<?php
		echo "\t\t".'<tr>';
		echo '<td style="padding-left:5px;">Filename:</td>';
		$filename = ($update ? $episodeToUp->getFilename() : '');
		echo '<td style="padding-left:5px; width:450px;">';
		/* echo '<input type="text" id="filename" name="filename" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="'.$filename.'" '.($update ? 'DISABLED ' : '').'/>'; */
		echo '<input type="text" id="filename" name="filename" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" value="'.$filename.'"/>';
		$idFile = ($update ? $episodeToUp->getIdFile() : -1);
		echo '<input type="hidden" id="idFile" name="idFile" value="'.$idFile.'" style="font-size:10px; width:20px;" />';
		echo '</td>';
		echo '</tr>';
?>

		<tr>
		<td style="padding-left:5px;">Titel:</td>
		<td style="padding-left:5px; width:450px;"><input type="text" id="title" name="title" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px;">Regie:</td>
		<td style="padding-left:5px; width:450px;"><input type="text" id="regie" name="regie" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px;">Gast:</td>
		<td style="padding-left:5px; width:450px;"><input type="text" id="gast_autor" name="gast_autor" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px;">Air-Date:</td>
		<td style="padding-left:5px; width:450px;"><input type="text" id="airdate" name="airdate" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px;">Rating:</td>
		<td style="padding-left:5px; width:450px;"><input type="text" id="rating" name="rating" class="key inputbox" style="width:450px;" onfocus="this.select();" onclick="this.select();" /></td>
		</tr>
		<tr>
		<td style="padding-left:5px;">Desc:</td>
		<td style="padding-left:5px; width:450px;"><textarea id="desc" name="desc" class="key inputbox" style="width:450px; height:150px;" onfocus="this.select();" onclick="this.select();"></textarea></td>
		</tr>
<?php
		echo "\t\t".'<tr>';
		echo "\t\t".'<td style="display:none;">';
		echo '<input type="text" id="idShow" name="idShow" style="width:10px;" value="'.$idShow.'"/>';
		echo '<input type="text" id="idEpisode" name="idEpisode" style="width:10px;" value="'.$idEpisode.'"/>';
		echo '<input type="text" id="idTvdb" name="idTvdb" style="width:10px;" value="'.$idTvdb.'" />';
		echo '<input type="text" id="strPath" name="strPath" style="width:10px;" value="-1" />';
		echo '</td>';
		echo "\t\t".'</tr>';
	}
	
	$value = ($update ? 'Update' : 'Add');
	echo '<tr><td colspan="2" class="righto" style="padding:10 0px !important;"><div style="float:right; padding:0px 15px;"><input type="submit" value="'.$value.'" class="key okButton" onclick="this.blur();"></div></tr>';
?>
	</table>
	</form>
	</body>
</html>
<?php
/*	FUNCTIONS	*/
function postSerien() {
	$serien = $GLOBALS['serien'];
	foreach ($serien->getSerien() as $serie) {
		postSerie($serie->getIdTvdb(), $serie->getName());
	}
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
	
	foreach($episodes as $xdex => $season) {
		foreach($season as $ydex => $episode) {
			$s = intval(trim($episode['SeasonNumber']));
			if ($s == '') { $s = 0; }
			$e = intval(trim($episode['EpisodeNumber']));
			$se = $s.'-'.$e;
			echo "\t\t\t".'<option value="'.$se.'"'.($se == $selSE ? ' SELECTED' : '').'>S'.$s.' - E'.$e.'</option>';
			echo "\r\n";
		}
	}
}

function postPaths() {
	$paths = fetchPaths();
	
	$idPath = -1;
	if ($GLOBALS['update']) {
		$episodeToUp = $GLOBALS['episodeToUp'];
		$idPath = $episodeToUp->getIdPath();
	}
	
	for ($i = 0; $i < count($paths); $i++) {
		$id = $paths[$i][0];
		$name = $paths[$i][1];
		echo "\t\t\t".'<option value="'.$id.'"'.($idPath == $id ? 'SELECTED' : '').'>'.$name.'</option>';
		echo "\r\n";
	}
}

function postPathsJS() {
	$paths = fetchPaths();

	echo 'var paths = new Array(';
	for ($i = 0; $i < count($paths); $i++) {
		$id = $paths[$i][0];
		$name = $paths[$i][1];
		echo "\n\r";
		echo 'new Array("'.$id.'",'.json_encode( $name ).')';
		if ($i < count($paths)-1) {
			echo ',';
		}
	}
	echo ');';
}

function postEpisodesJS($episodes) {
	foreach ($episodes as $xdex => $season) {}
	$cEpisodes = count($episodes);

	$index = 1;
	echo 'var episodes = new Array(';
	foreach ($episodes as $xdex => $season) {
		echo "\n\r";
		echo 'new Array(';
		$lastSeason = 0;
		$jndex = 1;
		foreach($season as $ydex => $episode) {
			$seas = $episode['SeasonNumber'];
			if ($seas == '') { $seas = 0; }
			$lastSeason = $seas;

			$gastStars = processCredits($episode['GuestStars'], 'Gast Star');
			$autoren = processCredits($episode['Writer'], 'Autor');
			$spl = ($gastStars != '' && $autoren != '' ? ' / ' : '');
			$gast_autor = $gastStars.$spl.$autoren;

			$regie = processCredits($episode['Director'], null);

			$rating = getRating($episode['Rating']);

			echo "\n\r";
			echo 'new Array(';
			echo '"'.$seas.'","'.$episode['EpisodeNumber'].'",'.json_encode( $episode['EpisodeName'] ).',"'.$episode['FirstAired'].'","'.$rating.'",'.json_encode( $regie ).','.json_encode( $gast_autor );
			echo ')';
			if ($jndex < count($season)) {
				echo ', ';
			}

			$jndex++;
		}
		echo ')';
		if ($index < $cEpisodes) {
			echo ', ';
		}
		$index++;
	}
	echo ');';
	echo "\n\r";
	echo "\n\r";

	$index = 1;
	echo 'var descriptions = new Array(';
	foreach ($episodes as $xdex => $season) {
		echo "\n\r";
		echo 'new Array(';
		$jndex = 1;
		foreach($season as $ydex => $episode) {
			echo "\n\r";
			echo 'new Array(';
			echo json_encode( $episode['Overview'] );
			echo ')';
			if ($jndex < count($season)) {
				echo ', ';
			}
			$jndex++;
		}
		echo ')';
		if ($index < $cEpisodes) {
			echo ', ';
		}
		$index++;
	}
	echo ');';
}

function processCredits($input, $role) {
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
