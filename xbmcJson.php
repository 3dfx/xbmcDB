<?php
#include_once "auth.php";
#include_once "check.php";

include_once "template/config.php";
include_once "template/functions.php";

	if (!isAdmin()) {
		return;
		
	} else {
		$xbmControl = isset($GLOBALS['XBMCCONTROL_ENABLED']) ? $GLOBALS['XBMCCONTROL_ENABLED'] : false;
		if (!$xbmControl) { return; }
		
		$method = isset($_POST['method']) ? trim($_POST['method']) : null;
		if (empty($method)) { return; }

		if ($method == 'clearCache') {
			clearMediaCache();
			unset( $_SESSION['xbmcRunning'] );
			$_SESSION['overrideFetch'] = 1;

		} else if ($method == 'scanLib') {
			clearMediaCache();
			unset( $_SESSION['xbmcRunning'] );
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

		} else if ($method == 'play') {
			$file = isset($_POST['file']) ? trim($_POST['file']) : null;
			if (empty($file)) { return; }

			$file = decodeString($file);
			echo xbmcPlayFile($file);
		}
	}
?>
