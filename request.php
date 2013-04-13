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
	$scriptCopyFrom      = isset($GLOBALS['COPYASSCRIPT_COPY_FROM']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM'] : null;
	$scriptCopyWin       = isset($GLOBALS['COPYASSCRIPT_COPY_WIN']) ? $GLOBALS['COPYASSCRIPT_COPY_WIN'] : false;
	$copyAsScript        = isset($_POST['copyAsScript']) ? $_POST['copyAsScript'] : (isset($_GET['copyAsScript']) ? $_GET['copyAsScript'] : 0);
	$forOrder            = isset($_POST['forOrder']) ? $_POST['forOrder'] : (isset($_GET['forOrder']) ? $_GET['forOrder'] : 0);
	$username            = isset($_SESSION['user']) ? $_SESSION['user'] : null;
	
	$sql = "SELECT c00, B.strFilename as filename, c.strPath as path, d.filesize from movie a, files b, path c, fileinfo d ".
		"where idMovie in (".$ids.") and A.idFile = B.idFile and c.idPath = b.idPath and a.idFile = d.idFile ".
		"order by filename";
	$res = fetchFromDB($sql, false);
	if (empty($res)) {
		echo $forOrder == 1 ? '-1' : null;
		exit;
	}
	
	if ($forOrder == 1 && !empty($username)) {
		$exist = existsOrdersTable();
		
		//to prevent spamming, no minutes and seconds in filename!
		$fname   = './orders/'.date('Ymd_').$username;
		$append  = file_exists($fname);
		$content = doTheStuff($res, true);
		
		if ($append) {
			$fsize = filesize($fname);
			$file  = fopen($fname, 'r');
			$content0 = fread($file, $fsize);
			fclose($file);
			
			$ar0 = explode("\n", $content0);
			$ar1 = explode("\n", $content);
			
			$ar = array_filter(array_unique(array_merge($ar0, $ar1)));
			sort($ar);
			$content = implode("\n", $ar);
		}
		
		$file = fopen($fname, 'w+');
		#fwrite($file, $content, strlen($content)); 
		fwrite($file, $content); 
		fclose($file);
		
		if ($exist) {
			$sql = "REPLACE INTO orders VALUES('".$fname."', '".time()."', '".$username."', 1);";
			execSQL($sql, false);
		}
		
		echo $append ? '2' : '1';
		exit;
	}
	
	echo encodeString(doTheStuff($res));
?>
<?php
	function doTheStuff($result, $forOrder = false, $append = false) {
		$copyAsScriptEnabled = $GLOBALS['copyAsScriptEnabled'];
		$scriptCopyTo        = $GLOBALS['scriptCopyTo'];
		$scriptCopyFrom      = $GLOBALS['scriptCopyFrom'];
		$copyAsScript        = $GLOBALS['copyAsScript'];
		$scriptCopyWin       = $GLOBALS['scriptCopyWin'];
		
		$oldPath   = '';
		$filenames = $scriptCopyWin && $forOrder && !$append ? 'chcp 1252'."\r\n" : '';
		$totalsize = 0;
		foreach($result as $row) {
			$path = $row['path'];
			$filename = $row['filename'];
			$size= $row['filesize'];
			$totalsize += $size;
			
			$pos1 = strpos($filename, 'smb://');
			$pos2 = strpos($filename, 'stack:');
			if ($pos1 > -1 || $pos2 > -1) {
				$filename = null;
			}
			
			if (!empty($filename) && ($forOrder || !isAdmin())) {
				if ($forOrder || ($copyAsScript && $copyAsScriptEnabled)) {
					#$filenames .= getScriptString($path, $filenames, $formattedName);
					if (!empty($scriptCopyFrom)) {
						$path = $scriptCopyFrom;
					}
					
					$formattedName = $filename;
					if (!$scriptCopyWin) {
						$formattedName = str_replace(" ", "\ ", $filename);
						$formattedName = str_replace("(", "\(", $formattedName);
						$formattedName = str_replace(")", "\)", $formattedName);
						$formattedName = str_replace("[", "\[", $formattedName);
						$formattedName = str_replace("]", "\]", $formattedName);
						
						$path = mapSambaDirs($path);
						$path = str_replace(' ', '\ ', $path);
					}
					
					#if ($oldPath != $path) { $filenames .= 'cd '.$path."\r\n"; }
					$filenames .= $scriptCopyWin ? 'copy ' : 'cp ';
					$filenames .= ($scriptCopyWin ? '"' : '').$path.$formattedName.($scriptCopyWin ? '"' : '').' '.$scriptCopyTo.' ';
					$filenames .= "\r\n";
					$oldPath = $path;
					
				} else {
					$filenames .= $filename;
					$filenames .= '<br>';
				}
			}
		}
		
		if (!$forOrder) {
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
