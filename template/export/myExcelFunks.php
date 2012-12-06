<?php
include_once 'template/config.php';

	function logOut() {
		return isset($GLOBALS['importLogging']) ? $GLOBALS['importLogging'] : false;
	}


	
	/* EXPORT-FUNKZ [- START -] */
	function exportTablesDo($tables) {
		$objPHPExcel = initExport();
		
		$sheet = 0;
		for ($s = 0; $s < count($tables); $s++) {
			$export = (isset( $_SESSION['xP_'.$tables[$s]] ) && $_SESSION['xP_'.$tables[$s]] == 'on' ? true : false);
			if ($export) {
				exportTable($objPHPExcel, $tables[$s], $sheet++);
			}
		}

		finalizeExport($objPHPExcel, true);
	}

	function initExport() {
		/** Error reporting */
		if (logOut()) { error_reporting(E_ALL); }
		
		ini_set("memory_limit","500M");

		$cacheMethod = PHPExcel_CachedObjectStorageFactory::cache_to_phpTemp;
		$cacheSettings = array( 'memoryCacheSize' => '32MB');
		PHPExcel_Settings::setCacheStorageMethod($cacheMethod, $cacheSettings);
		
		// Create new PHPExcel object
		if (logOut()) { echo date('H:i:s') , " Create new PHPExcel object" , PHP_EOL , '<br/>'; }
		$objPHPExcel = new PHPExcel();
		
		// Set document properties
		if (logOut()) { echo date('H:i:s') , " Set document properties" , PHP_EOL , '<br/>'; }
		$objPHPExcel->getProperties()->setCreator("xbmcDB")
				 ->setLastModifiedBy("xbmcDB")
				 ->setTitle("xbmcDB - Tabellen vom ".strftime('%d.%m.%Y %H:%M', time()))
				 ->setSubject("xbmcDB - Tabellen")
				 ->setDescription("xbmcDB - Tabellen")
				 ->setKeywords("")
				 ->setCategory("");

		return $objPHPExcel;
	}

	function exportTable($objPHPExcel, $table, $sheet) {
		if ($sheet > 0) { $objPHPExcel->createSheet(); }
		
		$objPHPExcel->setActiveSheetIndex($sheet);
		$objPHPExcel->getActiveSheet()->setTitle($table);
		
		$res = fetchFromDB("PRAGMA TABLE_INFO(".$table.");");
		$i = 0;
		foreach($res as $row) {
			$objPHPExcel->getActiveSheet()->setCellValue(letterMap($i++).'1', $row[1]);
		}
		
		exportRes($objPHPExcel, $table, $i);
	}

	function exportRes($objPHPExcel, $table, $cols) {
		$index = 2;
		$res = fetchFromDB("SELECT * FROM ".$table.";");
		foreach ($res as $row) {
		#print_r( $row );
			exportRow($objPHPExcel, $row, $index++, $cols);
			unset( $row );
		}
	}

	function exportRow($objPHPExcel, $row, $index, $cols) {
		for ($i = 0; $i < $cols; $i++) {
		#echo letterMap($i).' '.$row[$i];
			$objPHPExcel->getActiveSheet()->setCellValue(letterMap($i).$index, encodeString($row[$i]));
			#$objPHPExcel->getActiveSheet()->setCellValue(letterMap($i).$index, $row[$i]);
		}
	}

	function letterMap($i) {
		$map = array(   'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 
				'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X', 'Y', 'Z' );
		return $map[$i];
	}

	function finalizeExport($objPHPExcel, $downloadExport) {
		// Set active sheet index to the first sheet, so Excel opens this as the first sheet
		$objPHPExcel->setActiveSheetIndex(0);

		// Save Excel 2007 file
		if (logOut()) { echo date('H:i:s') , " Write to Excel2007 format" , PHP_EOL , '<br/>'; }
		$objWriter = PHPExcel_IOFactory::createWriter($objPHPExcel, 'Excel2007');
		#$objWriter->save(str_replace('.php', '.xlsx', __FILE__));
		$fileName = './uploads/dbExport_'.strftime('%Y.%m.%d_%H.%M', time()).'.xlsx';
		$objWriter->save($fileName);
		if (logOut()) { echo date('H:i:s') , " File written to " , $fileName , PHP_EOL , '<br/>'; }

		// Echo memory peak usage
		if (logOut()) { echo date('H:i:s') , " Peak memory usage: " , (memory_get_peak_usage(true) / 1024 / 1024) , " MB" , PHP_EOL , '<br/>'; }
		// Echo done
		if (logOut()) { echo date('H:i:s') , " Done writing file" , PHP_EOL , '<br/>'; }
		
		if ($downloadExport) {
			echo date('H:i:s') , ' <a style="font-weight:bold;" href="./'.$fileName.'">Download exportierte Tabellen (Excel)</a>';
		}
	}
	/* EXPORT-FUNKZ [- END -] */



	/* IMPORT-FUNKZ [- START -] */
	function importTablesDo($xlFile) {
		$sheets = initImport($xlFile);
		$tablesInDB = getTableNames();
		
		/*** make it or break it ***/
		error_reporting(E_ALL);
		try {
			$db_name = $GLOBALS['db_name'];
			$dbh = new PDO($db_name);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			$dbh->beginTransaction();

			importTables($dbh, $sheets, $tablesInDB);
			$dbh->commit();

		} catch(PDOException $e) {
			$dbh->rollBack();
			echo $e->getMessage();
		}
	}
	
	function initImport($xlFile) {
		$reader = PHPExcel_IOFactory::createReader('Excel2007');
		$reader->setReadDataOnly(true);
		$objPHPExcel = $reader->load( $xlFile );

		return $objPHPExcel->getAllSheets();
	}
	
	function importTables($dbh, $sheets, $tablesInDB) {
		foreach ($sheets as $key => $sheet) {
			$table = $sheet->getTitle();

			if (!isset($tablesInDB[ $table ]) || $tablesInDB[ $table ] != 1) {
				echo 'Tabelle "'.$table.'" existiert nicht in der Datenbank und wird übersprungen!<br/>';
				echo '<hr/>';
				continue;
			}

			importTable($dbh, $table, $sheet);
			echo '<hr/>';
		}
	}
	
	function importTable($dbh, $table, $sheet) {
		echo 'Importiere Tabelle "'.$table.'".<br/>';
		
		$dbh->exec("DELETE FROM ".$table.";");
		$data = $sheet->toArray();
		for ($r = 1; $r < count($data); $r++) {
			#if ($r == 0) { continue; }
			$row  = $data[$r];
			$sql  = 'INSERT INTO '.$table.' VALUES(';
			for ($c = 0; $c < count($row); $c++) {
				$val  = '"'.decodeString($row[$c]).'"';
				$sql .= $val.($c < count($row)-1 ? ',' : '');
			}
			$sql .= ');';
			$dbh->exec($sql);
			if (logOut()) { echo $sql.'<br/>'; }
		}
	}
	/* IMPORT-FUNKZ [- END -] */
?>