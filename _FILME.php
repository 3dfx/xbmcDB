<?php
	include_once "template/functions.php";
	function fetchMovies($dbh, $SQL, $sessionKey) {
		#if (isAdmin()) { echo '<pre>'.$sessionKey.'</pre>'; }
		#if (isAdmin()) { foreach ($_SESSION as $key => $value) { echo $key.'<br/>'; } }
		
		$res = array();
		//$overrideFetch = isset($_SESSION['overrideFetch']) ? $_SESSION['overrideFetch'] : 0;
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
		
		if (isset($_SESSION[$sessionKey]) && $overrideFetch == 0) {
			$res = unserialize($_SESSION[$sessionKey]);
			
		} else {
			$result = fetchFromDB_($dbh, $SQL);
			$count = 0;
			foreach($result as $row) {
				$res[$count]['idMovie']   = $row['idMovie'];
				$res[$count]['idMovie']   = $row['idMovie'];
				$res[$count]['c00']       = $row['c00'];
				$res[$count]['playCount'] = $row['playCount'];
				$res[$count]['thumb']     = $row['thumb'];
				$res[$count]['idFile']    = $row['idFile'];
				$res[$count]['filename']  = $row['filename'];
				$res[$count]['dateAdded'] = $row['dateAdded'];
				$res[$count]["path"]      = $row["path"];
				$res[$count]['jahr']      = $row['jahr'];
				$res[$count]['filesize']  = $row['filesize'];
				$res[$count]['playCount'] = $row['playCount'];
				$res[$count]['trailer']   = $row['trailer'];
				$res[$count]['c05']       = $row['c05'];
				$res[$count]['imdbId']    = $row['imdbId'];
				$res[$count]['c14']       = $row['c14'];
				$res[$count]['filename']  = $row['filename'];
				$count++;
			}
			
			#echo '<pre>'.$SQL.'</pre>';
			
			$_SESSION[$sessionKey] = serialize($res);
			unset( $_SESSION['overrideFetch'] );
		}
		
		return $res;
	}
?>