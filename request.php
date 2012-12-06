<?php
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "globals.php";

	if (isset($_POST['ids'])) { $ids = SQLite3::escapeString(trim($_POST['ids'])); }
	else { die('No ids given!'); }
	
	$copyAsScriptEnabled = isset($GLOBALS['COPYASSCRIPT_ENABLED']) ? $GLOBALS['COPYASSCRIPT_ENABLED'] : false;
	$scriptCopyTo = isset($GLOBALS['COPYASSCRIPT_COPY_TO']) ? $GLOBALS['COPYASSCRIPT_COPY_TO'] : '/media/usb/Filme/';
	$copyAsScript = isset($_POST['copyAsScript']) ? $_POST['copyAsScript'] : 0;
	
	$filenames = "";
	try {
		$dbh = new PDO($db_name);
		$sql = "SELECT c00, B.strFilename as filename, c.strPath as path, d.filesize from movie a, files b, path c, fileinfo d ".
		    	"where idMovie in (".$ids.") and A.idFile = B.idFile and c.idPath = b.idPath and a.idFile = d.idFile ".
		        "order by filename";
		$result = $dbh->query($sql);

		$oldPath = '';
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

			if ($filename != '' && !isAdmin()) {
				if ($copyAsScript && $copyAsScriptEnabled) {
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
		
		$s = _format_bytes($totalsize);
		$filenames .= '<br><br>Total size: '.$s;
		
	} catch(PDOException $e) {
		echo $e->getMessage();
	}
	
	echo $filenames;
?>
