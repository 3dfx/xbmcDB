<?php
include_once "check.php";

include_once "./template/functions.php";
include_once "./template/config.php";
include_once "globals.php";

	startSession();
	if (!isAdmin()) { die; }

	$hostname = $_SERVER['HTTP_HOST'];
	$path = dirname($_SERVER['PHP_SELF']);
	$errMsg = null;

	$act        = getEscGPost('act',         '');
	$id         = getEscGPost('id',          -1);
	$idFile     = getEscGPost('idFile',      -1);
	$idFiles    = getEscGPost('idFiles',     '');
	$idMovie    = getEscGPost('idMovie',     -1);
	$keepMeta   = getEscGPost('keepMeta',     0);
	$idGenre    = getEscGPost('idGenre',     -1);
	$idShow     = getEscGPost('idShow',      -1);
	$idTvdb     = getEscGPost('idTvdb',      -1);
	$idEpisode  = getEscGPost('idEpisode',   -1);
	$idPath     = getEscGPost('idPath',      -1);
	$name       = getEscGPost('name',        '');
	$title      = getEscGPost('title',       '');
	$jahr       = getEscGPost('jahr',        '');
	$dateAdded  = getEscGPost('dateAdded',   '');
	$rating     = getEscGPost('rating',      '');
	$file       = getEscGPost('filename',    '');
	$genre      = getEscGPost('genre',       '');
	$strPath    = getEscGPost('strPath',     '');
	$strSeason  = getEscGPost('strSeason',   '');
	$showEpi    = getEscGPost('showEpisode', '');
	$regie      = getEscGPost('regie',       '');
	$gast_autor = getEscGPost('gast_autor',  '');
	$airdate    = getEscGPost('airdate',     '');
	$desc       = getEscGPost('desc',        '');
	$val        = getEscGPost('val',         -1);
	$clrStream  = getEscGPost('clrStream',    0);
	$clrSize    = getEscGPost('clrSize',      0);
	$source     = getEscGPost('source',      -1);
	$aRatio     = getEscGPost('aRatio',      -1);
	$tomParam   = getEscGPost('tomParam',   1.0);
	$noForward  = getEscGPost('noForward',    0);

	$dbh = getPDO();
	try {
		if (!empty($dbh) && !$dbh->inTransaction()) {
			$dbh->beginTransaction();
		}
		$SQL = '';

		if ($idFile != -1 && ($act == 'setSeen' || $act == 'setUnseen')) {
			$dbh->exec('UPDATE files SET playCount='.($act == 'setSeen' ? '1' : '0').' WHERE idFile = '.$idFile.';');
			clearMediaCache();

		} else if (!empty($idFile) && $act == 'setMovieSource') {
			$dbh->exec('UPDATE fileinfo SET src='.($source > 0 ? $source : 'NULL').' WHERE idFile='.$idFile.';');
			clearMediaCache();

		} else if (!empty($idFile) && $act == 'toggleAtmos') {
			$dbh->exec('UPDATE fileinfo SET atmosx = '.(empty($val) ? '' : 1).' WHERE idFile = '.$idFile.';');
			clearMediaCache();

		} else if (!empty($idFiles) && $act == 'clearFileSizes') {
			$dbh->exec('UPDATE fileinfo SET filesize=NULL,fps=NULL,bit=NULL,atmosx=NULL WHERE src IS NOT NULL AND idFile IN('.$idFiles.');');
			$dbh->exec('DELETE FROM fileinfo WHERE src IS NULL AND idFile IN('.$idFiles.');');
			if ($clrStream == 1) {
				$dbh->exec('DELETE FROM streamdetails WHERE idFile IN('.$idFiles.');');
			}

			clearMediaCache();

		} else if ($idFile != -1 && $act == 'clearFileSize') {
			$dbh->exec('UPDATE fileinfo SET filesize=NULL,fps=NULL,bit=NULL,atmosx=NULL WHERE src IS NOT NULL AND idFile='.$idFile.';');
			$dbh->exec('DELETE FROM fileinfo WHERE src IS NULL AND idFile='.$idFile.';');
			if ($clrStream == 1) {
				$dbh->exec('DELETE FROM streamdetails WHERE idFile='.$idFile.';');
			}

			clearMediaCache();

		} else if ($act == 'updateEpisode' && $idEpisode != -1 && $idPath != -1 && $idFile != -1 && !empty($file) && !empty($strPath)) {
			$strPath  = str_replace("''", "'", $strPath);
			$file     = str_replace("''", "'", $file);
			$title    = str_replace("''", "'", $title);
			$title    = str_replace('"',  "'", $title);
			$title    = str_replace("  ", " ", $title);
			$desc     = str_replace('"',  "'", $desc);
			$desc     = str_replace("''", "'", $desc);
			$desc     = str_replace("  ", " ", $desc);

			$SQLfile = 'UPDATE files SET idPath=[idPath],strFilename="[FILENAME]" WHERE idFile=[idFile];';
			$SQLfile = str_replace('[idFile]', $idFile, $SQLfile);
			$SQLfile = str_replace('[idPath]', $idPath, $SQLfile);
			$SQLfile = str_replace('[FILENAME]', $file, $SQLfile);
			$dbh->exec($SQLfile);

			$rating   = null;
			$idRating = null;
			if (!emptyRating($rating) || intval($rating) == 0) {
				$GETID_SQL = "SELECT c03 FROM episode WHERE idEpisode=".$idEpisode.";";
				$row       = fetchFromDB($GETID_SQL, false, $dbh);
				$idRating  = empty($row) ? null : $row['c03'];
				if ($idRating == null || $idRating == '' || $idRating == -1) {
					$idRating = getNextId('rating', 'rating_id', $dbh);
				}

				if (!empty($idRating)) {
					$dbh->exec(createRatingSQL('REPLACE', $idEpisode, 'episode', $idRating, $rating));
				}
			}

			$gast_autor = str_replace('"', "''", $gast_autor);
			$SQLepi = 'UPDATE episode SET c00="[TITLE]",c01="[DESC]",'.(!empty($idRating) ? 'c03="[RATING_ID]",' : '').'c04="[GUEST_AUTOR]",c05="[AIRED]",c10="[REGIE]",c18="[FILENAME]" WHERE idEpisode=[idEpisode];';
			$SQLepi = str_replace('[idEpisode]',   $idEpisode,  $SQLepi);
			$SQLepi = str_replace('[TITLE]',       $title,      $SQLepi);
			$SQLepi = str_replace('[DESC]',        $desc,       $SQLepi);
			$SQLepi = str_replace('[GUEST_AUTOR]', $gast_autor, $SQLepi);
			$SQLepi = str_replace('[AIRED]',       $airdate,    $SQLepi);
			$SQLepi = str_replace('[REGIE]',       $regie,      $SQLepi);
			$SQLepi = str_replace('[FILENAME]', ($strPath != '-1' ? $strPath : '').$file, $SQLepi);
			if (!empty($idRating)) {
				$SQLepi = str_replace('[RATING_ID]',   $idRating,   $SQLepi);
			}

			$dbh->exec($SQLepi);

			$source = $source > 0 ? $source : 'NULL';
			if ($clrSize == 1)
				$dbh->exec('UPDATE fileinfo SET filesize=NULL,fps=NULL,bit=NULL,src='.$source.' WHERE idFile='.$idFile.';');
			else
				$dbh->exec('UPDATE fileinfo SET src='.$source.' WHERE idFile='.$idFile.';');

			if ($clrStream == 1) {
				$dbh->exec('DELETE FROM streamdetails WHERE idFile='.$idFile.';');
			}

			clearMediaCache();

		} else if ($act == 'addEpisode' && $idShow != -1 && $idTvdb != -1 && $idPath != -1 && !empty($file) && !empty($strPath) && !empty($strSeason)) {
			 ##################################
			##
			## INSERT INTO files VALUES([idFile],[idPath],'[FILENAME]',NULL,NULL);
			## INSERT INTO episode VALUES([idEpisode],[idFile],'[TITLE]','[DESC]',NULL,[RATING],'[GUEST_AUTOR]',[AIRED],NULL,NULL,NULL,NULL,'[REGIE]',NULL,[SEASON],[EPISODE],NULL,-1,-1,-1,'[FULLFILENAME]',[idPath],NULL,NULL,NULL,NULL);
			## INSERT INTO tvshowlinkepisode VALUES([idShow],[idEpisode]);
			##
			 #############################

			$strPath  = str_replace("''", "'", $strPath);
			$file     = str_replace("''", "'", $file);
			$title    = str_replace('"',  "'", $title);
			$title    = str_replace("''", "'", $title);
			$title    = str_replace("  ", " ", $title);
			$desc     = str_replace('"',  "'", $desc);
			$desc     = str_replace("''", "'", $desc);
			$desc     = str_replace("  ", " ", $desc);

			$GETID_SQL = 'SELECT idFile FROM files ORDER BY idFile DESC LIMIT 0, 1;';
			$row       = fetchFromDB($GETID_SQL, false, $dbh);
			if (empty($row)) {
				return;
			}

			$lastId    = $row['idFile'];
			$idFile    = $lastId + 1;
			$added     = date("Y-m-d H:i:s", time());

			$SQLfile = 'INSERT INTO files(idFile,idPath,strFilename,dateAdded) VALUES([idFile],[idPath],"[FILENAME]","[ADDED]");';
			$SQLfile = str_replace('[idFile]',   $idFile, $SQLfile);
			$SQLfile = str_replace('[idPath]',   $idPath, $SQLfile);
			$SQLfile = str_replace('[FILENAME]', $file,   $SQLfile);
			$SQLfile = str_replace('[ADDED]',    $added,  $SQLfile);
			$dbh->exec($SQLfile);

			$idEpisode = getNextId('episode', 'idEpisode', $dbh);

			$rating   = null;
			$idRating = null;
			if (!emptyRating($rating)) {
				$idRating  = getNextId('rating',  'rating_id', $dbh);

				if (!empty($idRating)) {
					$dbh->exec(createRatingSQL('INSERT', $idEpisode, 'episode', $idRating, $rating));
				}
			}

			if (empty($idRating)) {
				$idRating = 'NULL';
			}

			$showEpi = explode('-', $showEpi);
			$SQLepi = 'INSERT INTO episode '.
				   'VALUES([idEpisode],[idFile],"[TITLE]","[DESC]",NULL,"[RATING_ID]","[GUEST_AUTOR]","[AIRED]",NULL,NULL,NULL,NULL,"[REGIE]",NULL,[SEASON],[EPISODE],NULL,-1,-1,-1,"[FULLFILENAME]",[idPath],NULL,NULL,NULL,NULL,[idShow],[U_RATING],[idSeason]);';
			$SQLepi = str_replace('[idEpisode]',    $idEpisode,     $SQLepi);
			$SQLepi = str_replace('[idFile]',       $idFile,        $SQLepi);
			$SQLepi = str_replace('[idSeason]',     $strSeason,     $SQLepi);
			$SQLepi = str_replace('[TITLE]',        $title,         $SQLepi);
			$SQLepi = str_replace('[DESC]',         $desc,          $SQLepi);
			$SQLepi = str_replace('[RATING_ID]',    $idRating,      $SQLepi);
			$SQLepi = str_replace('[U_RATING]',     intval($rating), $SQLepi);
			$SQLepi = str_replace('[GUEST_AUTOR]',  $gast_autor,    $SQLepi);
			$SQLepi = str_replace('[AIRED]',        $airdate,       $SQLepi);
			$SQLepi = str_replace('[REGIE]',        $regie,         $SQLepi);
			$SQLepi = str_replace('[SEASON]',       $showEpi[0],    $SQLepi);
			$SQLepi = str_replace('[EPISODE]',      $showEpi[1],    $SQLepi);
			$SQLepi = str_replace('[FULLFILENAME]', $strPath.$file, $SQLepi);
			$SQLepi = str_replace('[idPath]',       $idPath,        $SQLepi);
			$SQLepi = str_replace('[idShow]',       $idShow,        $SQLepi);
			$dbh->exec($SQLepi);

			if (checkAirDate()) {
				try {
					checkNextAirDateTable($dbh);
					clearAirdateInDb($idShow, $dbh);
					fetchAndUpdateAirdate($idShow, $dbh);
				} catch(Throwable $e) { }
			}

			clearMediaCache();

		} else if ($idFile != -1 && $act == 'clearBookmark') {
			$dbh->exec('DELETE FROM bookmark WHERE idFile='.$idFile.';');
		}

		if ($act == 'setmovieinfo' && $idMovie != -1 && $idFile != -1) {
			$params = null;
			$dbVer  = fetchDbVer();

			if (!empty($title) || !empty($jahr) || (!empty($rating)) || !empty($genre)) {
				$title  = str_replace('_AND_', '&', $title);
				$title  = str_replace("''",    "'", $title);
				$title  = str_replace('"',     "'", $title);
				$rating = str_replace(',',     '.', $rating);

				$params  = (!empty($title)  ? 'c00="'.$title.'"' : '');
				$params .= (!empty($genre)  ? (!empty($params) ? ', ' : '').'c14="'.$genre.'"'  : '');
				if ($dbVer < 107) {
					$params .= (!empty($rating) && !emptyRating($rating) ? (!empty($params) ? ', ' : '').'c05="'.$rating.'"' : '');
					$params .= (!empty($jahr)   ? (!empty($params) ? ', ' : '').'c07="'.$jahr.'"'   : '');
				} else {
					$params .= (!empty($jahr)   ? (!empty($params) ? ', ' : '').'premiered="'.$jahr.'-01-01"'   : '');
				}

				if ($params != '') {
					$dbh->exec('UPDATE movie SET '.$params.' WHERE idMovie = '.$idMovie.';');
				}
			}

			if (!empty($rating) && $dbVer >= 107) {
				$idRating = findRatingId($idMovie, 'movie', $dbh);
				if (empty($idRating)) {
					$idRating = getNextId('rating', 'rating_id', $dbh);
				}

				if (!empty($idRating)) {
					$dbh->exec('UPDATE movie SET c05='.$idRating.' WHERE idMovie = '.$idMovie.';');
					$dbh->exec(createRatingSQL('REPLACE', $idMovie, 'movie', $idRating, $rating));
				}
			}

			$params = '';
			if (!empty($file)) {
				$file     = str_replace("''", "'", $file);
				$params   = "strFilename='".$file."'";

				$SQLpth   = "SELECT strPath FROM path WHERE idPath=(SELECT idPath FROM files WHERE idFile=[idFile]);";
				$SQLpth   = str_replace('[idFile]', $idFile, $SQLpth);
				$row      = fetchFromDB($SQLpth, false, $dbh);
				$strPath  = empty($row) ? null : $row['strPath'];
				$fileName = empty($strPath) ? null : $strPath.$file;
				$isFile   = isFile($fileName);
				if ($isFile) {
					$dbh->exec('UPDATE files SET '.$params.' WHERE idFile='.$idFile.';');
					$dbh->exec('UPDATE movie SET c22="'.$fileName.'" WHERE idFile='.$idFile.';');

					if (!$keepMeta) {
						$dbh->exec('DELETE FROM streamdetails WHERE idFile='.$idFile.';');
						$dbh->exec('DELETE FROM fileinfo WHERE idFile='.$idFile.';');
						$dbh->exec('DELETE FROM filemap  WHERE idFile='.$idFile.';');
					}
				} else {
					$errMsg = '<span style="font:12px Verdana, Arial;">File: "'.$fileName.'" not found!<br/>Nothing was changed!</span>';
				}
			}

			if (!empty($dateAdded)) {
				$dateValue = strtotime($dateAdded);
				if (!empty($params)) { $params .= ', '.$params; }
				$dbh->exec('UPDATE filemap SET dateAdded = "'.$dateAdded.'", value = "'.$dateValue.$params.'" WHERE idFile = "'.$idFile.'";');
				$dbh->exec('UPDATE files SET dateAdded = "'.$dateAdded.'" WHERE idFile = "'.$idFile.'";');
			}

			clearMediaCache();
		}

		if ($act == 'setRunning' && $idShow != -1 && $val != -1) {
			if ($val == 1) {
				$dbh->exec('INSERT OR IGNORE INTO tvshowrunning VALUES('.$idShow.', 1);');
			} else {
				$dbh->exec('DELETE FROM tvshowrunning WHERE idShow = '.$idShow.';');
				$dbh->exec('DELETE FROM nextairdate WHERE idShow = '.$idShow.';');
			}
			clearMediaCache();
		}

		if ($act == 'clearAirdate' && $idShow != -1) {
			$dbh->exec('DELETE FROM nextairdate WHERE idShow = '.$idShow.';');
			clearMediaCache();
		}

		if (($act == 'linkInsert' || $act == 'linkUpdate') && $id != -1 && $idMovie != -1) {
			$SQL = 'UPDATE movie SET idSet='.$id.' WHERE idMovie = '.$idMovie.';';
			$dbh->exec($SQL);
			clearMediaCache();
		}

		if ($act == 'linkDelete' && $idMovie != -1) {
			$SQL = 'UPDATE movie SET idSet=NULL WHERE idMovie = '.$idMovie.';';
			$dbh->exec($SQL);
			clearMediaCache();
		}

		if ($act == 'setname' && $id != -1 && !empty($name)) {
			$name = str_replace('_AND_', '&', $name);
			$name = str_replace("''", "'", $name);
			$SQL = 'UPDATE sets SET strSet="'.$name.'" WHERE idSet = '.$id.';';
			$dbh->exec($SQL);
			clearMediaCache();
		}

		if ($act == 'setAspectRatio' && $idFile != -1 && $idMovie != -1) {
			$SQL = "";
			if (empty($aRatio)) {
				$SQL = "DELETE FROM aspectratio WHERE idFile = '".$idFile."' AND idMovie = '".$idMovie."';";
			} else {
				$SQL = "REPLACE INTO aspectratio VALUES('".$idFile."','".$idMovie."','".$aRatio."');";
			}
			$dbh->exec($SQL);
			//clearMediaCache();
		}

		if ($act == 'setToneMapParam' && $idFile != -1) {
			$SQL = "";
			$SQL = "UPDATE settings SET TonemapParam = ".$tomParam." WHERE idFile = '".$idFile."';";
			$dbh->exec($SQL);
		}

		if ($act == 'setMoviesetCover' && $id != -1 && $idMovie != -1) {
			$url = '';
			try {
				$res = $dbh->query('SELECT url,type FROM art WHERE media_type = "movie" AND media_id = "'.$idMovie.'";');
				$poster  = '';
				$fanart  = '';
				foreach($res as $row) {
					$type = $row['type'];
					$url  = $row['url'];
					if (!empty($url)) {
						if (
						    $type == 'poster' ||
						   ($type == 'thumb' && empty($poster))
						   ) {
							$poster = $url;
						}
						if ($type == 'fanart') {
							$fanart = $url;
						}
					}
				}

				$dbh->exec('DELETE FROM art WHERE media_type="set" AND media_id ="'.$id.'";');
				$dbh->exec('REPLACE INTO art (media_id, media_type, type, url) VALUES("'.$id.'", "set", "poster", "'.$poster.'");');
				$dbh->exec('REPLACE INTO art (media_id, media_type, type, url) VALUES("'.$id.'", "set", "fanart", "'.$fanart.'");');

			} catch(Throwable $e) {
				if (!empty($dbh) && $dbh->inTransaction()) { $dbh->rollBack(); }
				exit;
			}
			echo 'Setcover was set!<br />';
		}

		if ($act == 'addset' && !empty($name)) {
			$GETID_SQL = 'SELECT idSet FROM sets ORDER BY idSet DESC LIMIT 0, 1';
			$row = fetchFromDB($GETID_SQL, false);
			if (!empty($row)) {
				$lastId = $row['idSet'];
				$id = $lastId + 1;

				$SQL = 'REPLACE INTO sets (idSet, strSet) VALUES('.$id.', "'.$name.'");';
				$dbh->exec($SQL);
			}
		}

		if ($act == 'delete' && $id != -1) {
			$dbh->exec('DELETE FROM sets WHERE idSet = '.$id.';');
			$dbh->exec('UPDATE movie SET idSet=NULL WHERE idSet = '.$id.';');
		}

		if ($act == 'fixRunTime') {
			$FAILS_SQL = 'SELECT count(M.idFile) AS fails FROM movie M, streamdetails S WHERE S.iVideoDuration IS NOT NULL AND S.iVideoDuration != 0 AND S.iVideoDuration != M.c11 AND M.idFile = S.idFile;';

			$row = fetchFromDB($FAILS_SQL, false);
			$fails_1 = null;
			$fails_2 = null;
			$hasErr  = false;

			if (empty($row)) {
				$hasErr = true;

			} else {
				$fails_1 = $row['fails'];
			}

			if (!$hasErr) {
				if ($fails_1 != 0) {
					$dbh->exec('UPDATE movie SET c11=(SELECT iVideoDuration FROM streamdetails S WHERE S.iVideoDuration IS NOT NULL AND S.iVideoDuration != 0 AND S.iVideoDuration != movie.c11 AND movie.idFile = S.idFile);');
				}

				$row = fetchFromDB($FAILS_SQL, false);
				if (empty($row)) {
					$hasErr = true;

				} else {
					$fails_2 = $row['fails'];
				}
			}

			$fails = $hasErr || ($fails_1 != 0 && $fails_1 == $fails_2) ? null : $fails_1;
			if ($fails === null) {
				$hasErr = true;
			}

			if ($hasErr) {
				echo '<span style="font:12px Verdana, Arial;">Runtimes couldn\'t be fixed!</span>';
			} else {
				echo '<span style="font:12px Verdana, Arial;">'.($fails == 0 ? 'Everything was fine.' : $fails.' runtime'.($fails == 1 ? '' : 's').' fixed!').'</span>';
			}
		}

		if (!empty($dbh) && $dbh->inTransaction()) {
			$dbh->commit();
		}

		$clsFrame = false;
		if ($act == 'clearBookmark') {
			echo '<span style="font:12px Verdana, Arial;">Bookmark cleared!</span>';

		} else if ($act == 'clearFileSize' || $act == 'clearFileSizes') {
			echo '<span style="font:12px Verdana, Arial;">Streamdetails / Filesize(s) cleared!</span>';

		} else if ($act == 'setSeen' || $act == 'setUnseen') {
			echo '<span style="font:12px Verdana, Arial;">Episode is set to '.($act == 'setUnseen' ? 'not ' : '').'watched!</span>';

		} else if ($act == 'setRunning' && $idShow != -1 && $val != -1) {
			echo '<span style="font:12px Verdana, Arial;">TV-Show is set to '.($val == 1 ? 'running' : 'not running').'!</span>';

		} else if ($act == 'clearAirdate' && $idShow != -1) {
			echo '<span style="font:12px Verdana, Arial;">Next airdate was cleared!</span>';

		} else if ($act == 'toggleAtmos' && $idFile != -1) {
			echo '<span style="font:12px Verdana, Arial;">Atmos flag was toggled!</span>';

		} else if ($noForward != 1 && ($act == 'setAspectRatio' || $act == 'setToneMapParam')) {
			header('Location: ./?show=details&idMovie='.$idMovie);

		} else if ($act == 'addset' || $act == 'delete' || $act == 'setname' || $act == 'setMoviesetCover') {
			header('Location: '.($path == '/' ? '' : $path).'/setEditor.php');

		} else if ($act == 'setMovieSource' || $act == 'linkInsert' || $act == 'linkUpdate' || $act == 'linkDelete' || $act == 'addEpisode' || $act == 'updateEpisode' || $act == 'setmovieinfo') {
			$clsFrame = empty($errMsg);
		}

		if (!empty($errMsg)) {
			echo $errMsg;
		}

		if ($clsFrame) { header('Location: '.($path == '/' ? '' : $path).'/closeFrame.php'); }

	} catch(Throwable $e) {
		echo $e;
		if (!empty($dbh) && $dbh->inTransaction()) { $dbh->rollBack(); }
	}

	exit;

function findRatingId($mediaId, string $mediaType = 'movie', $dbh = null) {
	$GETID_SQL = "SELECT rating_id FROM rating WHERE media_id=".$mediaId." AND media_type='".$mediaType."';";
	$row       = fetchFromDB($GETID_SQL, false, $dbh);
	return !empty($row) && !empty($row['rating_id']) ? $row['rating_id'] : null;
}

function getNextId(string $table, string $column, $dbh = null) {
	$GETID_SQL = "SELECT ".$column." FROM ".$table." ORDER BY ".$column." DESC LIMIT 0, 1;";
	$row       = fetchFromDB($GETID_SQL, false, $dbh);
	if (empty($row)) {
		return null;
	}
	$lastId    = $row[$column];
	return $lastId + 1;
}

function createRatingSQL($command, $idMedia, $idType, $idRating, $rating) {
	$rating = empty($rating) ? 0 : $rating;

	$SQLrating = $command.' INTO rating (rating_id,media_id,media_type,rating_type,rating,votes) VALUES([RATING_ID],[ID_MEDIA],"[TYP_MEDIA]","[default]","[RATING]",0);';
	$SQLrating = str_replace('[ID_MEDIA]',  $idMedia,  $SQLrating);
	$SQLrating = str_replace('[TYP_MEDIA]', $idType,  $SQLrating);
	$SQLrating = str_replace('[RATING_ID]', $idRating,   $SQLrating);
	$SQLrating = str_replace('[RATING]',    $rating,     $SQLrating);

	return $SQLrating;
}
?>
