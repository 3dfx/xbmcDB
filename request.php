<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";
	
	if (isDemo()) { exit; }
	$ids = getEscGPost('ids');
	if (empty($ids)) { exit; }
	
	$saveOrderInDB = isset($GLOBALS['SAVE_ORDER_IN_DB']) ? $GLOBALS['SAVE_ORDER_IN_DB'] : false;
	$username      = isset($_SESSION['user'])            ? $_SESSION['user']            : null;
	$isShow        = isset($_POST['isShow']) || isset($_GET['isShow']);
	$copyAsScript  = getEscGPost('copyAsScript', 0);
	$forOrder      = getEscGPost('forOrder', 0);
	
	$res = getItemsForRequest($ids, $isShow);
	if (empty($res)) {
		echo $forOrder == 1 ? '-1' : null;
		return;
	}
	
	if ($forOrder == 0) {
		echo $isShow ? doTheStuffTvShow($res) : doTheStuffMovie($res);
		return;
	}
	
	if (empty($username)) { exit; }
	
	if ($saveOrderInDB && existsOrderzTable()) {
		$append  = isset($_SESSION['param_idOrder']);
		$idOrder = $append ? $_SESSION['param_idOrder'] : getIdOrder();
		$_SESSION['param_idOrder'] = $idOrder;
		
		$SQL = "INSERT OR IGNORE INTO orderz VALUES(".$idOrder.", '".time()."', '".$username."', 1);";
		execSQL($SQL, false);
		
		$idAr = explode(", ", $ids);
		foreach($idAr as $id) {
			$SQL = "INSERT OR IGNORE INTO orderItemz VALUES(".$idOrder.", ".$id.", ".($isShow ? 0 : 1).");";
			execSQL($SQL, false);
		}
		
		echo $append ? '2' : '1';
		
	} else if (existsOrdersTable()) {
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
		
		$SQL = "REPLACE INTO orders VALUES('".$fname."', '".time()."', '".$username."', 1);";
		execSQL($SQL, false);
		
		echo $append ? '2' : '1';
	}
?>
