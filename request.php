<?php
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "globals.php";

	if (isset($_POST['ids']))     { $ids = SQLite3::escapeString(trim($_POST['ids'])); }
	else if (isset($_GET['ids'])) { $ids = SQLite3::escapeString(trim($_GET['ids']));  }
	else { die('No ids given!'); }
	
	$copyAsScriptEnabled = isset($GLOBALS['COPYASSCRIPT_ENABLED']) ? $GLOBALS['COPYASSCRIPT_ENABLED'] : false;
	$scriptCopyTo        = isset($GLOBALS['COPYASSCRIPT_COPY_TO']) ? $GLOBALS['COPYASSCRIPT_COPY_TO'] : '/mnt/hdd/';
	$copyAsScript        = isset($_POST['copyAsScript']) ? $_POST['copyAsScript'] : (isset($_GET['copyAsScript']) ? $_GET['copyAsScript'] : 0);
	$forOrder            = isset($_POST['forOrder']) ? $_POST['forOrder'] : (isset($_GET['forOrder']) ? $_GET['forOrder'] : 0);
	
	$filenames = '';
	try {
		$dbh = new PDO($db_name);
		$sql = "SELECT c00, B.strFilename as filename, c.strPath as path, d.filesize from movie a, files b, path c, fileinfo d ".
		    	"where idMovie in (".$ids.") and A.idFile = B.idFile and c.idPath = b.idPath and a.idFile = d.idFile ".
		        "order by filename";
		
		$filenames = doTheStuff($dbh->query($sql));
		
	} catch(PDOException $e) {
		echo $e->getMessage();
	}
	
	echo $filenames;

function doTheStuff($result) {
	$copyAsScriptEnabled = $GLOBALS['copyAsScriptEnabled'];
	$scriptCopyTo        = $GLOBALS['scriptCopyTo'];
	$copyAsScript        = $GLOBALS['copyAsScript'];
	$forOrder            = $GLOBALS['forOrder'];
	
	$oldPath   = '';
	$filenames = '';
	$totalsize = 0;
	foreach($result as $row) {
		$path = $row['path'];
		$filename = $row['filename'];
		$size= $row['filesize'];
		$totalsize += $size;
		$formattedName = str_replace(" ", "\ ", $filename);
		$formattedName = str_replace("(", "\(", $formattedName);
		$formattedName = str_replace(")", "\)", $formattedName);
		$formattedName = str_replace("[", "\[", $formattedName);
		$formattedName = str_replace("]", "\]", $formattedName);

		$pos1 = strpos($filename, 'smb://');
		$pos2 = strpos($filename, 'stack:');
		if ($pos1 > -1 || $pos2 > -1) {
			$filename = '';
		}

		if ($forOrder || ($filename != '' && !isAdmin())) {
			if ($copyAsScript && $copyAsScriptEnabled) {
				#$filenames .= getScriptString($path, $filenames, $formattedName);
				$path = mapSambaDirs($path);
				$path = str_replace(' ', '\ ', $path);
				if ($oldPath != $path) {
					$filenames .= 'cd '.$path.'<br>';
				}
				$filenames .= 'cp ';
				$filenames .= $formattedName;
				$filenames .= ' '.$scriptCopyTo.' ';
				$filenames .= '<br>';
				$oldPath = $path;

			} else {
				$filenames .= $filename;
				$filenames .= '<br>';
			}
		}
	}

	if ($forOrder || !isAdmin()) {
		$s = _format_bytes($totalsize);
		$filenames .= '<br><br>Total size: '.$s;
	}
	
	return $filenames;
}
	
function getScriptString($path, $filenames, $formattedName) {
	$scriptCopyTo = $GLOBALS['scriptCopyTo'];
	$oldPath = '';
	
	$path = mapSambaDirs($path);
	$path = str_replace(' ', '\ ', $path);
	if ($oldPath != $path) {
		$filenames .= 'cd '.$path.'<br>';
	}
	$filenames .= 'cp ';
	$filenames .= $formattedName;
	$filenames .= ' '.$scriptCopyTo.' ';
	$filenames .= '<br>';
	$oldPath = $path;
	
	return $filenames;
}
?>
