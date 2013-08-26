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
	
	$sql =  "SELECT c00 AS filmname, B.strFilename AS filename, c.strPath AS path, d.filesize FROM movie a, files b, path c, fileinfo d ".
		"WHERE idMovie IN (".$ids.") AND A.idFile = B.idFile AND c.idPath = b.idPath AND a.idFile = d.idFile ".
		"ORDER BY filename";
	$res = fetchFromDB($sql, false);
	if (empty($res)) {
		echo $forOrder == 1 ? '-1' : null;
		exit;
	}
	
	if ($forOrder == 1 && !empty($username)) {
		$exist = existsOrdersTable();
		
		//to prevent spamming, no minutes and seconds in filename!
		$fname   = './orders/'.date('Ymd_').$username.'.order';
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
	
	#echo encodeString(doTheStuff($res));
	echo doTheStuff($res);
?>
<?php
	function doTheStuff($result, $forOrder = false, $append = false) {
		$copyAsScriptEnabled = $GLOBALS['copyAsScriptEnabled'];
		$scriptCopyTo        = $GLOBALS['scriptCopyTo'];
		$scriptCopyFrom      = $GLOBALS['scriptCopyFrom'];
		$copyAsScript        = $GLOBALS['copyAsScript'];
		$scriptCopyWin       = $GLOBALS['scriptCopyWin'];
		
		$oldPath   = '';
		$newLine   = "\n"; //$scriptCopyWin ? "\r\n" : "\n";
		$names = $scriptCopyWin && $forOrder && !$append ? 'chcp 1252'.$newLine : '';
		$totalsize = 0;
		foreach($result as $row) {
			$path       = $row['path'];
			$filename   = $row['filename'];
			$filmname   = $row['filmname'];
			$size       = $row['filesize'];
			$totalsize += $size;
			
			$name = $forOrder ? decodeString(encodeString($filename)) : $filmname;
			
			$pos1 = strpos($name, 'smb://');
			$pos2 = strpos($name, 'stack:');
			if ($pos1 > -1 || $pos2 > -1) {
				$name = null;
			}
			
			if (!empty($name) && ($forOrder || !isAdmin())) {
				if ($forOrder || ($copyAsScript && $copyAsScriptEnabled)) {
					#$names .= getScriptString($path, $names, $formattedName);
					if (!empty($scriptCopyFrom)) {
						$path = $scriptCopyFrom;
					}
					
					$formattedName = $name;
					if (!$scriptCopyWin) {
						$formattedName = str_replace(" ", "\ ", $formattedName);
						$formattedName = str_replace("(", "\(", $formattedName);
						$formattedName = str_replace(")", "\)", $formattedName);
						$formattedName = str_replace("[", "\[", $formattedName);
						$formattedName = str_replace("]", "\]", $formattedName);
						
						$path = mapSambaDirs($path);
						$path = str_replace(' ', '\ ', $path);
					}
					
					#if ($oldPath != $path) { $names .= 'cd '.$path.$newLine; }
					$names .= $scriptCopyWin ? 'copy ' : 'cp ';
					$names .= ($scriptCopyWin ? '"' : '').$path.$formattedName.($scriptCopyWin ? '"' : '').' '.$scriptCopyTo.' ';
					$names .= $newLine;
					$oldPath = $path;
					
				} else {
					$names .= $name;
					$names .= '<br>';
				}
			}
		}
		
		if (!$forOrder) {
			$s = _format_bytes($totalsize);
			$names .= '<br><br>Total size: '.$s;
		}
		
		return $names;
	}

	function getScriptString($path, $names, $formattedName) {
		$scriptCopyTo = $GLOBALS['scriptCopyTo'];
		$oldPath = '';
		
		$path = mapSambaDirs($path);
		$path = str_replace(' ', '\ ', $path);
		if ($oldPath != $path) {
			$names .= 'cd '.$path.'<br>';
		}
		$names .= 'cp ';
		$names .= $formattedName;
		$names .= ' '.$scriptCopyTo.' ';
		$names .= '<br>';
		$oldPath = $path;
		
		return $names;
	}
?>
