<?php
include_once "template/functions.php";
	
	startSession();
	if (!isLogedIn()) { shoutImage(); }
	
	$id       = isset($_GET['img'])      ? $_GET['img']      : null;
	$size     = isset($_GET['size'])     ? $_GET['size']     : null;
	$movie    = isset($_GET['movie'])    ? $_GET['movie']    : null;
	$file     = isset($_GET['file'])     ? $_GET['file']     : null;
	$fanart   = isset($_GET['fanart'])   ? $_GET['fanart']   : null;
	$actor    = isset($_GET['actor'])    ? $_GET['actor']    : null;
	$director = isset($_GET['director']) ? $_GET['director'] : null;
	$banner   = isset($_GET['banner'])   ? $_GET['banner']   : null;

	$arr = null;
	     if (isset($movie))    { $arr = 'cover';    }
	else if (isset($file))     { $arr = 'file';     }
	else if (isset($fanart))   { $arr = 'fanart';   }
	else if (isset($actor))    { $arr = 'actor';    }
	else if (isset($director)) { $arr = 'director'; }
	else if (isset($banner))   { $arr = 'banner';   }
	
	if (empty($id) || empty($arr)) { shoutImage(); }
	$img = $_SESSION['thumbs'][$arr][$id];
	
	if (!empty($movie)) {
		if ($movie == 1) {
			$img = str_replace('thumbs', 'covers', $img);
			$img = str_replace('thumb', 'cover', $img);
		} else {
			$img = str_replace('thumbs', 'coversbig', $img);
			$img = str_replace('thumb', 'coverbig', $img);
		}
	}
	
	shoutImage($img);
	
function shoutImage($img = null) {
	header('Content-Type: image/jpeg');
	if (isset($img) && file_exists($img)) {
		setHeaders($img);
		readfile($img);
		
	} else {
		$img = imagecreatetruecolor(1, 1);
		imagejpeg($img, NULL, 1);
		imagedestroy($im);
		exit;
	}
}

function setHeaders($img) {
	if (isset($_SERVER['HTTP_IF_MODIFIED_SINCE'])) {
		$if_modified_since = preg_replace('/;.*$/', '',   $_SERVER['HTTP_IF_MODIFIED_SINCE']);
	} else {
		$if_modified_since = '';
	}

	$mtime = isset($img) && file_exists($img) ? filemtime($img) : filemtime($_SERVER['SCRIPT_FILENAME']);
	$gmdate_mod = gmdate('D, d M Y H:i:s', $mtime) . ' GMT';

	if ($if_modified_since == $gmdate_mod) {
		header("HTTP/1.0 304 Not Modified");
	} else {
		header("Last-Modified: $gmdate_mod");
	}
	
	header("Pragma: cache");
	header("Cache-Control: public, must-revalidate");
	header('Expires: ' . date("D, j M Y", strtotime("tomorrow")) . ' 02:00:00 GMT');
}
?>