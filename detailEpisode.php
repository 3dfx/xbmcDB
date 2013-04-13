<?php
//	include_once "auth.php";
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "_SERIEN.php";

	$admin = isAdmin();

	$id = $_GET['id'];
	if ($id == null || $id <= 0) { die('No id given!'); }

	$SQL =  $GLOBALS['SerienSQL'].' AND V.idEpisode = '.$id.';';

	$title = '';
	$epDesc = '';
	$path = '';
	$coverP = '';
	$filename = '';
	$lastPlayed = '';
	$playCount = 0;
	$epRating = '';
	$season = '';
	$episode = '';
	$idFile = 0;
	$airDate = '';
	$filesize = 0;
	$fsize = 0;
	
	$ar        = '';
	$width     = '';
	$height    = '';
	$vCodec    = '';
	$aCodec    = array();
	$aChannels = array();
	
	$existArtTable = false;
	
	$stream = getStreamDetails($id);
	foreach($stream as $stRow) {
		$tmp = $stRow['fVideoAspect'];
		if (!empty($tmp)) {
			if ($tmp != '') {
				$tmp  = round($tmp, 2);
				$tmp .= (strlen($tmp) < 4 ? '0' : '').':1';
			}
			$ar = $tmp;
		}

		$tmp = $stRow['iVideoWidth'];
		if (!empty($tmp)) { $width = $tmp; }

		$tmp = $stRow['iVideoHeight'];
		if (!empty($tmp)) { $height = $tmp; }

		$tmp = $stRow['strVideoCodec'];
		if (!empty($tmp)) { $vCodec = strtoupper($tmp); }

		$tmp = $stRow['strAudioCodec'];
		if (!empty($tmp)) { $aCodec[count($aCodec)] = strtoupper($tmp); }

		$tmp = $stRow['iAudioChannels'];
		if (!empty($tmp)) { $aChannels[count($aChannels)] = $tmp; }
	}
	
	try {
		$db_name = $GLOBALS['db_name'];
		$dbh = new PDO($db_name);
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		
		$existArtTable = existsArtTable($dbh);
		
		$result = $dbh->query($SQL);
		foreach($result as $row) {
			$title = $row['epName'];
			$epDesc = trim($row['epDesc']);
			$path = $row['path'];
			$filename = $row['filename'];
			$lastPlayed = $row['lastPlayed'];
			$playCount = $row['playCount'];
			$epRating = $row['epRating'];
			$season = $row['season'];
			$episode = $row['episode'];
			$airDate = $row['airDate'];
			$idFile = $row['idFile'];
			$filesize = $row['filesize'];
		}

		$coverP = $path;
		
		if ($season < 10) { $season = '0'.$season; }
		if ($episode < 10) { $episode = '0'.$episode; }
		
		$path = mapSambaDirs($path);
		$fsize = _format_bytes(fetchFileSize($idFile, $path, $filename, $filesize, null));

	} catch(PDOException $e) {
		echo $e->getMessage();
	}
	
	$thumbImg = null;
	$thumbsUp = isset($GLOBALS['TVSHOW_THUMBS']) ? $GLOBALS['TVSHOW_THUMBS'] : false;
	$ENCODE = isset($GLOBALS['ENCODE_IMAGES_TVSHOW']) ? $GLOBALS['ENCODE_IMAGES_TVSHOW'] : true;
	
	if ($thumbsUp) {
		$fromSrc = isset($GLOBALS['TVSHOW_THUMBS_FROM_SRC']) ? $GLOBALS['TVSHOW_THUMBS_FROM_SRC'] : false;
		if ($fromSrc && empty($thumbImg)) {
			// READ MEDIAFIRE GENERATED THUMBS FROM SOURCE
			$DIRMAP_IMG = isset($GLOBALS['DIRMAP_IMG']) ? $GLOBALS['DIRMAP_IMG'] : null;
			$thumb = mapSambaDirs($path, $DIRMAP_IMG).substr($filename, 0, strlen($filename)-3).'tbn';
			$smb = (substr($thumb, 0, 6) == 'smb://');
			
			if (!$smb && file_exists($thumb) && $ENCODE) {
				$thumbImg = base64_encode_image($thumb);
			}
		}
		
		if (empty($thumbImg)) {
			$img = getTvShowThumb($coverP.$filename);
			$thumbImg = $ENCODE ? base64_encode_image($img) : $img;

			if (empty($thumbImg) && $existArtTable) {
				$res2 = $dbh->query("SELECT url FROM art WHERE url NOT NULL AND url NOT LIKE '' AND media_type = 'episode' AND type = 'thumb' AND media_id = '".$id."';");
				$row2 = $res2->fetch();
				$url = $row2['url'];
				#logc( $id.' - '.$url );
				if (!empty($url)) {
					$img = getTvShowThumb($url);
					#logc( $img );
					if (file_exists($img) && $ENCODE) {
						$thumbImg = base64_encode_image($img);
					}

				}
			}
		}

	}
	
	echo '<table id="epDescription" class="film" style="border-top:0px; width:350px; padding:0px; margin:0px; z-index:1;">';
	echo '<tr class="showDesc">';
	echo '<td colspan="3" style="padding:20px 25px; white-space:pre-line;">';
	echo '<div style="padding-bottom:'.(!empty($thumbImg) ? '2' : '15').'px;"><div><u><i><b>Title:</b></i></u></div><span>'.$title.' [ S'.$season.'.E'.$episode.' ]</span>';
	if ($admin && $playCount > 0) {
		echo '<span style="float:right;"><img src="./img/check.png" class="galleryImage" title="watched" style="width:9px !important; height:9px !important;" /></span>';
	}
	echo '</div>';
	
	if (!empty($thumbImg)) {
		echo '<div style="padding-bottom:15px;"><img src="'.$thumbImg.'" style="width:298px;" /></div>';
	}
	
	if ($epDesc != null && $epDesc != '') {
		$spProtect = isset($GLOBALS['SPOILPROTECTION']) ? $GLOBALS['SPOILPROTECTION'] : true;
		
		$tmp = '<div style="padding-bottom:15px; text-align:justify;"><u><i><b>Description:</b></i></u><br />'.$epDesc.'</div>';
		if (!$spProtect || !$admin || !empty($playCount)) {
			echo $tmp;

		} else if ($admin && empty($playCount)) {
			echo '<div id="epSpoiler" style="padding-bottom:15px;" onclick="spoilIt(); return false;"><u><i><b>Description:</b></i></u> <span style="color:red;">spoil it!</span></div>';
			echo '<span id="epDescr" style="display:none;">';
			echo $tmp;
			echo '</span>';
		}
	}

	$rating = substr($epRating, 0, 3);
	if ($rating != '0.0') {
		echo '<div'.($admin ? ' style="padding-bottom:15px;"' : '').'><span><u><i><b>Rating:</b></i></u></span><span style="float:right; text-align:right;">'.$rating.'</span></div>';
	}
	
	if (!empty($airDate)) {
		echo '<div><span><u><i><b>Airdate:</b></i></u></span><span style="float:right; text-align:right;">'.$airDate.'</span></div>';
	}
	
	if ($admin) {
		if (!empty($lastPlayed) && $playCount > 0) {
			echo '<div><span><u><i><b>Watched:</b></i></u></span><span style="float:right; text-align:right;">'.substr($lastPlayed, 0, 10).'</span></div>';
		}
		
		echo '<div style="padding-top:15px; padding-bottom:15px;"><span><u><i><b>Size:</b></i></u></span><span style="float:right; text-align:right;">'.$fsize.'</span></div>';
		
		echo '<div style="overflow-x:hidden;"><u><i><b>File:</b></i></u><br />'.$path.$filename.'</div>';
	}
	echo '</td>';
	echo '</tr>';
	echo '</table>';
?>