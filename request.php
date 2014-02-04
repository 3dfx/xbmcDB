<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";
	
	if (isDemo()) { return; }
	
	$ids = getEscGPost('ids');
	if (empty($ids)) { return; }
	
	$isShow = isset($_POST['isShow']) || isset($_GET['isShow']);
	
	$copyAsScriptEnabled = isset($GLOBALS['COPYASSCRIPT_ENABLED'])        ? $GLOBALS['COPYASSCRIPT_ENABLED']        : false;
	$scriptCopyTo        = isset($GLOBALS['COPYASSCRIPT_COPY_TO'])        ? $GLOBALS['COPYASSCRIPT_COPY_TO']        : '/mnt/hdd/';
	$scriptCopyFrom      = isset($GLOBALS['COPYASSCRIPT_COPY_FROM'])      ? $GLOBALS['COPYASSCRIPT_COPY_FROM']      : null;
	$scriptCopyToShow    = isset($GLOBALS['COPYASSCRIPT_COPY_TO_SHOW'])   ? $GLOBALS['COPYASSCRIPT_COPY_TO_SHOW']   : '/mnt/hdd/';
	$scriptCopyFromShow  = isset($GLOBALS['COPYASSCRIPT_COPY_FROM_SHOW']) ? $GLOBALS['COPYASSCRIPT_COPY_FROM_SHOW'] : null;
	$scriptCopyWin       = isset($GLOBALS['COPYASSCRIPT_COPY_WIN'])       ? $GLOBALS['COPYASSCRIPT_COPY_WIN']       : false;
	$username            = isset($_SESSION['user'])                       ? $_SESSION['user']                       : null;
	$copyAsScript        = getEscGPost('copyAsScript', 0);
	$forOrder            = getEscGPost('forOrder', 0);
	
	$SQL = null;
	if (!$isShow) {
		$SQL =  "SELECT c00 AS filmname, B.strFilename AS filename, c.strPath AS path, d.filesize FROM movie a, files b, path c, fileinfo d ".
			"WHERE idMovie IN (".$ids.") AND A.idFile = B.idFile AND c.idPath = b.idPath AND a.idFile = d.idFile ".
			"ORDER BY filename";
	} else {
		$SQL = "SELECT strPath, c00 AS name FROM tvshowview WHERE idShow IN (".$ids.");";
	}
	
	$res = querySQL($SQL, false);
	if (empty($res)) {
		echo $forOrder == 1 ? '-1' : null;
		exit;
	}
	
	if ($forOrder == 1 && !empty($username)) {
		$exist = existsOrdersTable();
		
		//to prevent spamming, no minutes and seconds in filename!
		$fname   = './orders/'.date('Ymd_').$username.'.order';
		$append  = file_exists($fname);
		$content = $isShow ? doTheStuffTvShow($res, true) : doTheStuffMovie($res, true);
		
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
		fwrite($file, $content); 
		fclose($file);
		
		if ($exist) {
			$SQL = "REPLACE INTO orders VALUES('".$fname."', '".time()."', '".$username."', 1);";
			execSQL($SQL, false);
		}
		
		echo $append ? '2' : '1';
		exit;
	}
	
	echo $isShow ? doTheStuffTvShow($res) : doTheStuffMovie($res);
?>
<?php
	function doTheStuffTvShow($result, $forOrder = false, $append = false) {
		$scriptCopyTo        = $GLOBALS['scriptCopyToShow'];
		$scriptCopyFrom      = $GLOBALS['scriptCopyFromShow'];
		$copyAsScriptEnabled = isset($GLOBALS['copyAsScriptEnabled']) ? $GLOBALS['copyAsScriptEnabled'] : false;
		$copyAsScript        = isset($GLOBALS['copyAsScript']) ? $GLOBALS['copyAsScript'] : false;
		$scriptCopyWin       = isset($GLOBALS['scriptCopyWin']) ? $GLOBALS['scriptCopyWin'] : false;
		$tvShowDir           = isset($GLOBALS['TVSHOWDIR']) ? $GLOBALS['TVSHOWDIR'] : $scriptCopyFrom;
		
		$newLine = $forOrder ? "\n" : '<br />';
		$res = $scriptCopyWin && $forOrder && !$append ? 'chcp 1252'.$newLine : '';
		foreach($result as $row) {
			if ($forOrder || ($copyAsScript && $copyAsScriptEnabled)) {
				$path = $row['strPath'];
				$path = str_replace($tvShowDir, $scriptCopyFrom, $path);
				
				$newFoldername = '';
				if ($scriptCopyWin) {
					$newFoldername = str_replace($scriptCopyFrom, '', $path);
					if (substr($path, -1) == '/') {
						$path = substr($path, 0, strlen($path)-1);
					}
					
					$path = str_replace("/", "\\", $path);
					$newFoldername = str_replace("/", "\\", $newFoldername);
					
				} else {
					$newFoldername = str_replace($scriptCopyFrom, '', $path);
				}
				
				$path = $forOrder ? decodeString(encodeString($path)) : $path;
				
				$res .= $scriptCopyWin ? 'xcopy /S ' : 'cp -r ';
				$res .= '"'.$path.'" "'.$scriptCopyTo.($scriptCopyWin ? $newFoldername : '').'"'.$newLine;
			} else {
				$name = $row['name'];
				$res .= $name.$newLine;
			}
		}
		
		return $res;
	}
	
	function doTheStuffMovie($result, $forOrder = false, $append = false) {
		$scriptCopyTo        = $GLOBALS['scriptCopyTo'];
		$scriptCopyFrom      = $GLOBALS['scriptCopyFrom'];
		$copyAsScriptEnabled = isset($GLOBALS['copyAsScriptEnabled']) ? $GLOBALS['copyAsScriptEnabled'] : false;
		$copyAsScript        = isset($GLOBALS['copyAsScript']) ? $GLOBALS['copyAsScript'] : false;
		$scriptCopyWin       = isset($GLOBALS['scriptCopyWin']) ? $GLOBALS['scriptCopyWin'] : false;
		
		$oldPath   = '';
		$newLine = $forOrder ? "\n" : '<br />';
		$res = $scriptCopyWin && $forOrder && !$append ? 'chcp 1252'.$newLine : '';
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
					#$res .= getScriptString($path, $res, $formattedName);
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
					
					$res .= $scriptCopyWin ? 'copy ' : 'cp ';
					$res .= ($scriptCopyWin ? '"' : '').$path.$formattedName.($scriptCopyWin ? '"' : '').' '.$scriptCopyTo.' ';
					$res .= $newLine;
					$oldPath = $path;
					
				} else {
					$res .= $name.$newLine;
				}
			}
		}
		
		if (!$forOrder && !empty($totalsize)) {
			$s = _format_bytes($totalsize);
			$res .= $newLine.$newLine.'Total size: '.$s;
		}
		
		return $res;
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
