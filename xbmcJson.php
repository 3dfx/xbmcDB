<?php
include_once "./template/config.php";
include_once "./template/functions.php";

	if (!isAdmin()) { return; }
	
	$xbmControl = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
	if (!$xbmControl) { return; }
	
	$method = getEscGPost('method');
	if (empty($method)) { return; }
	
	if ($method == 'clearCache') {
		#unset( $_SESSION['xbmcRunning'] );
		clearMediaCache();
	}
	
	if (!xbmcRunning()) { return; }
	if ($method == 'scanLib') {
		clearMediaCache();
		#unset( $_SESSION['xbmcRunning'] );
		echo xbmcScanLibrary() ? '1' : '0';

	} else if ($method == 'state') {
		$state = xbmcGetPlayerstate();
		echo $state == null ? 'null' : $state;

	} else if ($method == 'nowPlaying') {
		$ply  = cleanedPlaying(xbmcGetNowPlaying());
		echo $ply == null ? 'null' : $ply;

	} else if ($method == 'pausePlay') {
		echo xbmcPlayPause() ? '1' : '0';

	} else if ($method == 'stop') {
		echo xbmcStop() ? '1' : '0';

	} else if ($method == 'next') {
		echo xbmcPlayNext() ? '1' : '0';

	} else if ($method == 'prev') {
		echo xbmcPlayPrev() ? '1' : '0';

	} else if ($method == 'playExt') {
		$file = getEscGPost('file');
		if (empty($file)) { echo ''; }
		
		$url   = null;
		$vidId = null;
		if (substr_count($file, 'youtu') > 0) {
			if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|.*[?&]v=)|youtu\.be/)([^"&?/ ]{11})%i', $file, $match)) {
				$vidId = $match[1];
			}
			
			if (empty($vidId)) { echo ''; }
			$url = "plugin://plugin.video.youtube/?action=play_video&videoid=".$vidId;
			
		} else if (substr_count($file, 'vimeo') > 0) {
			if (preg_match('/^http:\/\/(www\.)?vimeo\.com\/(clip\:)?(\d+).*$/', $file, $match)) {
				$vidId = $match[3];
			}
			
			if (empty($vidId)) { echo ''; }
			$url = "plugin://plugin.video.vimeo/?action=play_video&videoid=".$vidId;
		} else {
			$url = $file;
		}
		
		if (!empty($url)) {
			echo xbmcPlayFile($url);
		} else {
			echo '';
		}
		
	} else if ($method == 'play') {
		$file = getEscGPost('file');
		if (empty($file)) { return; }
		
		$file = str_replace ("_AND_", "&", stripslashes($file));
		echo xbmcPlayFile(decodeString(trimDoubles($file)));
	}
?>
