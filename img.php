<?php
include_once "./template/functions.php";

	startSession();
	if (!isLoggedIn()) {
		header("HTTP/1.1 304 Not Modified");
		shoutImage();
	}

	$id       = getEscGet('img');
	$size     = getEscGet('size');
	$movie    = getEscGet('movie');
	$file     = getEscGet('file');
	$fanart   = getEscGet('fanart');
	$actor    = getEscGet('actor');
	$director = getEscGet('director');
	$banner   = getEscGet('banner');

	$arr = null;
	     if (isset($movie))    { $arr = 'cover';    }
	else if (isset($file))     { $arr = 'file';     }
	else if (isset($fanart))   { $arr = 'fanart';   }
	else if (isset($actor))    { $arr = 'actor';    }
	else if (isset($director)) { $arr = 'director'; }
	else if (isset($banner))   { $arr = 'banner';   }

	if (empty($id) || empty($arr)) { shoutImage(); }
	$img = empty($_SESSION['thumbs']) || empty($_SESSION['thumbs'][$arr]) || empty($_SESSION['thumbs'][$arr][$id]) ? null : $_SESSION['thumbs'][$arr][$id];

	if (!empty($movie)) {
		if ($movie == 1) {
			$img = str_replace('thumbs', 'covers', $img);
			$img = str_replace('thumb',  'cover',  $img);
		} else {
			$img = str_replace('thumbs', 'coversbig', $img);
			$img = str_replace('thumb',  'coverbig',  $img);
		}
	}

	$oldHandler = set_error_handler('handleError');
	shoutImage($img);
	if (!empty($oldHandler)) { set_error_handler($oldHandler); }

function shoutImage($img = null) {
	$type = 'Content-Type: image/jpeg';
	if (!empty($img) && str_ends_with($img, 'png')) {
		$type = 'Content-Type: image/png';
	}
	header($type);

	if (isFile($img)) {
		if (!setHeaders($img)) { exit; }
		try {
			readfile($img);
		} catch (Exception $e) {
			shoutImage();
		}
	} else {
		$img = imagecreatetruecolor(2, 1);
		imagejpeg($img, NULL, 1);
		imagedestroy($img);
		exit;
	}
}

function setHeaders($img) {
	$modSince_ = getEscServer('HTTP_IF_MODIFIED_SINCE');
	if (isset($modSince_)) {
		$if_modified_since = preg_replace('/;.*$/', '', $modSince_);
	} else {
		$if_modified_since = null;
	}

	header("Pragma: no-cache");
	header("Cache-Control: public, must-revalidate, max-age=86400");

	$docRoot = getEscServer('DOCUMENT_ROOT');
	$img = str_replace('./', $docRoot.'/', $img);
	$oldHandler = set_error_handler('handleError');
	$mtime = null;
	try {
		$mtime = isFile($img) ? filemtime($img) : null;
	} catch (Exception $e) {
		try {
			$mtime = empty($mtime) ? workaroundMTime($img) : null;
		} catch (Exception $e) { }
	}

	$scriptFName = getEscServer('SCRIPT_FILENAME');
	$mtime = empty($mtime) ? filemtime($scriptFName) : $mtime;

	$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime).' GMT';
	if (!empty($if_modified_since) && $if_modified_since == $gmdate_mod) {
		header("HTTP/1.1 304 Not Modified");
		return false;
	} else {
		header("Last-Modified: $gmdate_mod");
	}

	header('Expires: '.date("D, j M Y", strtotime("tomorrow")).' 02:00:00 GMT');
	return true;
}

?>
