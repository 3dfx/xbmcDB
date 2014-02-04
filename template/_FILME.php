<?php
	include_once "./template/functions.php";
	function fetchMovies($dbh, $SQL, $sessionKey) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
		
		$res = array();
		if (isset($_SESSION[$sessionKey]) && $overrideFetch == 0) {
			$res = unserialize($_SESSION[$sessionKey]);
			
		} else {
			$result = querySQL_($dbh, $SQL);
			$count = 0;
			foreach($result as $row) {
				$res[$count]['idFile']    = isset($row['idFile'])    ? $row['idFile']    : -1;
				$res[$count]['idMovie']   = isset($row['idMovie'])   ? $row['idMovie']   : -1;
				$res[$count]['c00']       = isset($row['c00'])       ? $row['c00']       : '';
				$res[$count]['playCount'] = isset($row['playCount']) ? $row['playCount'] : '';
				$res[$count]['thumb']     = isset($row['thumb'])     ? $row['thumb']     : '';
				$res[$count]['filename']  = isset($row['filename'])  ? $row['filename']  : '';
				$res[$count]['dateAdded'] = isset($row['dateAdded']) ? $row['dateAdded'] : '';
				$res[$count]["path"]      = isset($row["path"])      ? $row["path"]      : '';
				$res[$count]['jahr']      = isset($row['jahr'])      ? $row['jahr']      : '';
				$res[$count]['filesize']  = isset($row['filesize'])  ? $row['filesize']  : '';
				$res[$count]['playCount'] = isset($row['playCount']) ? $row['playCount'] : '';
				$res[$count]['trailer']   = isset($row['trailer'])   ? $row['trailer']   : '';
				$res[$count]['c05']       = isset($row['c05'])       ? $row['c05']       : '';
				$res[$count]['imdbId']    = isset($row['imdbId'])    ? $row['imdbId']    : '';
				$res[$count]['c14']       = isset($row['c14'])       ? $row['c14']       : '';
				$res[$count]['filename']  = isset($row['filename'])  ? $row['filename']  : '';
				$count++;
			}
			
			$_SESSION[$sessionKey] = serialize($res);
			unset( $_SESSION['overrideFetch'] );
		}
		return $res;
	}
?>