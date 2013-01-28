<?php
	include_once "check.php";

	include_once "template/functions.php";
	include_once "template/config.php";
	include_once "globals.php";
	
	startSession();
	if (!isAdmin()) { die; }
	
	$hostname = $_SERVER['HTTP_HOST'];
	$path = dirname($_SERVER['PHP_SELF']);
	
	$idFile       = -1;
	$idMovie      = -1;
	$idGenre      = -1;
	$id           = -1;
	$jahr         = '';
	$rating       = '';
	$dateAdded    = '';
	$file         = '';
	$name         = '';
	$title        = '';
	$act          = '';
	
	$idShow       = -1;
	$idTvdb       = -1;
	$idPath       = -1;
	$idEpisode    = -1;
	$strPath      = '';
	$showEpi      = '';
	$regie        = '';
	$gast_autor   = '';
	$airdate      = '';
	$rating       = '';
	$desc         = '';
	
	if (isset($_GET['act']))          { $act        = trim(SQLite3::escapeString($_GET['act']));          }
	if (isset($_GET['id']))           { $id         = trim(SQLite3::escapeString($_GET['id']));           }
	if (isset($_GET['idFile']))       { $idFile     = trim(SQLite3::escapeString($_GET['idFile']));       }
	if (isset($_GET['idMovie']))      { $idMovie    = trim(SQLite3::escapeString($_GET['idMovie']));      }
	if (isset($_GET['idGenre']))      { $idGenre    = trim(SQLite3::escapeString($_GET['idGenre']));      }
	if (isset($_GET['name']))         { $name       = trim(SQLite3::escapeString($_GET['name']));         }
	if (isset($_GET['title']))        { $title      = trim(SQLite3::escapeString($_GET['title']));        }
	if (isset($_GET['jahr']))         { $jahr       = trim(SQLite3::escapeString($_GET['jahr']));         }
	if (isset($_GET['dateAdded']))    { $dateAdded  = trim(SQLite3::escapeString($_GET['dateAdded']));    }
	if (isset($_GET['rating']))       { $rating     = trim(SQLite3::escapeString($_GET['rating']));       }
	if (isset($_GET['filename']))     { $file       = trim(SQLite3::escapeString($_GET['filename']));     }

	if (isset($_POST['idFile']))      { $idFile     = trim(SQLite3::escapeString($_POST['idFile']));      }
	if (isset($_POST['idShow']))      { $idShow     = trim(SQLite3::escapeString($_POST['idShow']));      }
	if (isset($_POST['idTvdb']))      { $idTvdb     = trim(SQLite3::escapeString($_POST['idTvdb']));      }
	if (isset($_POST['idEpisode']))   { $idEpisode  = trim(SQLite3::escapeString($_POST['idEpisode']));   }
	if (isset($_POST['idPath']))      { $idPath     = trim(SQLite3::escapeString($_POST['idPath']));      }
	if (isset($_POST['strPath']))     { $strPath    = trim(SQLite3::escapeString($_POST['strPath']));     }
	if (isset($_POST['showEpisode'])) { $showEpi    = trim(SQLite3::escapeString($_POST['showEpisode'])); }
	if (isset($_POST['regie']))       { $regie      = trim(SQLite3::escapeString($_POST['regie']));       }
	if (isset($_POST['title']))       { $title      = trim(SQLite3::escapeString($_POST['title']));       }
	if (isset($_POST['filename']))    { $file       = trim(SQLite3::escapeString($_POST['filename']));    }	
	if (isset($_POST['gast_autor']))  { $gast_autor = trim(SQLite3::escapeString($_POST['gast_autor']));  }
	if (isset($_POST['airdate']))     { $airdate    = trim(SQLite3::escapeString($_POST['airdate']));     }
	if (isset($_POST['rating']))      { $rating     = trim(SQLite3::escapeString($_POST['rating']));      }
	if (isset($_POST['desc']))        { $desc       = trim(SQLite3::escapeString($_POST['desc']));        }
	
	try {
		$dbh = new PDO($db_name);
		$dbh->beginTransaction();
		$SQL = '';

//		if ($act == 'setgenreinfo' && $idGenre != -1) {
//			if ($title != '') {
//				$dbh->exec('UPDATE genre set strGenre="'.$title.'" where idGenre = '.$idGenre.';');
//			}
//		}

		if ($act == 'setUnseen' && $idFile != -1) {
			$dbh->exec("UPDATE files SET playCount=0 WHERE idFile = ".$idFile.";");
			clearMovieCache();
			$_SESSION['overrideFetch'] = 1;
		
		} else if ($act == 'setSeen' && $idFile != -1) {
			$dbh->exec("UPDATE files SET playCount=1 WHERE idFile = ".$idFile.";");
			clearMovieCache();
			$_SESSION['overrideFetch'] = 1;
			
		} else if ($act == 'updateEpisode' && $idEpisode != -1 && $idPath != -1 && $idFile != -1 && !empty($file)) {
			$title = str_replace("'", "''", $title);
			$desc  = str_replace("'", "''", $desc);
			
			#CREATE TABLE episode ( idEpisode integer primary key, idFile integer,c00 text,c01 text,c02 text,c03 text,c04 text,c05 text,c06 text,c07 text,c08 text,c09 text,c10 text,c11 text,c12 varchar(24),c13 varchar(24),c14 text,c15 text,c16 text,c17 varchar(24),c18 text,c19 text,c20 text,c21 text,c22 text,c23 text);
			#"UPDATE episode VALUES([idEpisode],[idFile],'[TITLE]','[DESC]',NULL,[RATING],'[GUEST_AUTOR]',[AIRED],NULL,NULL,NULL,NULL,'[REGIE]',NULL,[SEASON],[EPISODE],NULL,-1,-1,-1,'[FULLFILENAME]',[idPath],NULL,NULL,NULL,NULL);";
			
			$SQLfile = "UPDATE files SET idPath=[idPath],strFilename='[FILENAME]' WHERE idFile=[idFile];";
			$SQLfile = str_replace('[idFile]', $idFile, $SQLfile);
			$SQLfile = str_replace('[idPath]', $idPath, $SQLfile);
			$SQLfile = str_replace('[FILENAME]', $file, $SQLfile);
			$dbh->exec($SQLfile);
			
			$SQLepi = "UPDATE episode SET c00='[TITLE]',c01='[DESC]',c03='[RATING]',c04='[GUEST_AUTOR]',c05='[AIRED]',c10='[REGIE]' WHERE idEpisode=[idEpisode];";
			$SQLepi = str_replace('[idEpisode]', $idEpisode, $SQLepi);
			$SQLepi = str_replace('[TITLE]', $title, $SQLepi);
			$SQLepi = str_replace('[DESC]', $desc, $SQLepi);
			$SQLepi = str_replace('[RATING]', $rating, $SQLepi);
			$SQLepi = str_replace('[GUEST_AUTOR]', $gast_autor, $SQLepi);
			$SQLepi = str_replace('[AIRED]', $airdate, $SQLepi);
			$SQLepi = str_replace('[REGIE]', $regie, $SQLepi);
			
			$_SESSION['overrideFetch'] = 1;
			$dbh->exec($SQLepi);
			
		} else if ($act == 'addEpisode' && $idShow != -1 && $idTvdb != -1 && $idPath != -1 && !empty($file)) {
			 ##################################
			##
			## INSERT INTO files VALUES([idFile],[idPath],'[FILENAME]',NULL,NULL);
			## INSERT INTO episode VALUES([idEpisode],[idFile],'[TITLE]','[DESC]',NULL,[RATING],'[GUEST_AUTOR]',[AIRED],NULL,NULL,NULL,NULL,'[REGIE]',NULL,[SEASON],[EPISODE],NULL,-1,-1,-1,'[FULLFILENAME]',[idPath],NULL,NULL,NULL,NULL);
			## INSERT INTO tvshowlinkepisode VALUES([idShow],[idEpisode]);
			##
			 #############################

			$GETID_SQL = 'SELECT idFile FROM files ORDER BY idFile DESC LIMIT 0, 1;';
			$result = $dbh->query($GETID_SQL);
			$row = $result->fetch();
			$lastId = $row['idFile'];
			$idFile = $lastId + 1;
			
			$SQLfile = "INSERT INTO files(idFile,idPath,strFilename) VALUES([idFile],[idPath],'[FILENAME]');";
			$SQLfile = str_replace('[idFile]', $idFile, $SQLfile);
			$SQLfile = str_replace('[idPath]', $idPath, $SQLfile);
			$SQLfile = str_replace('[FILENAME]', $file, $SQLfile);
			$dbh->exec($SQLfile);
			
			$GETID_SQL = 'SELECT idEpisode FROM episode ORDER BY idEpisode DESC LIMIT 0, 1;';
			$result = $dbh->query($GETID_SQL);
			$row = $result->fetch();
			$lastId = $row['idEpisode'];
			$idEpisode = $lastId + 1;
			
			$showEpi = explode('-', $showEpi);
			
			$title = str_replace("'", "''", $title);
			$desc  = str_replace("'", "''", $desc);
			
			$SQLepi = "INSERT INTO episode ".
				   "VALUES([idEpisode],[idFile],'[TITLE]','[DESC]',NULL,[RATING],'[GUEST_AUTOR]',[AIRED],NULL,NULL,NULL,NULL,'[REGIE]',NULL,[SEASON],[EPISODE],NULL,-1,-1,-1,'[FULLFILENAME]',[idPath],NULL,NULL,NULL,NULL,[idShow]);";
			
			$SQLepi = str_replace('[idEpisode]', $idEpisode, $SQLepi);
			$SQLepi = str_replace('[idFile]', $idFile, $SQLepi);
			$SQLepi = str_replace('[TITLE]', $title, $SQLepi);
			$SQLepi = str_replace('[DESC]', $desc, $SQLepi);
			$SQLepi = str_replace('[RATING]', $rating, $SQLepi);
			$SQLepi = str_replace('[GUEST_AUTOR]', $gast_autor, $SQLepi);
			$SQLepi = str_replace('[AIRED]', $airdate, $SQLepi);
			$SQLepi = str_replace('[REGIE]', $regie, $SQLepi);
			$SQLepi = str_replace('[SEASON]', $showEpi[0], $SQLepi);
			$SQLepi = str_replace('[EPISODE]', $showEpi[1], $SQLepi);
			$SQLepi = str_replace('[FULLFILENAME]', $strPath.$file, $SQLepi);
			$SQLepi = str_replace('[idPath]', $idPath, $SQLepi);
			$SQLepi = str_replace('[idShow]', $idShow, $SQLepi);
			$dbh->exec($SQLepi);
		
			/*
			//table in eden, no more in frodo!
			$SQLlink = "INSERT INTO tvshowlinkepisode VALUES([idShow],[idEpisode]);";
			$SQLlink = str_replace('[idShow]', $idShow, $SQLlink);
			$SQLlink = str_replace('[idEpisode]', $idEpisode, $SQLlink);
			$dbh->exec($SQLlink);
			*/

			$_SESSION['overrideFetch'] = 1;
		}
		
		if ($act == 'setmovieinfo' && $idMovie != -1 && $idFile != -1) {
			$params = null;
			if (!empty($title) || !empty($jahr) || !empty($rating)) {
				$title  = str_replace('_AND_', '&', $title);
				$rating = str_replace(',', '.', $rating);

				$params  = (!empty($title) ? 'c00="'.$title.'"' : '');
				$params .= (!empty($title) && !empty($jahr) ? ' AND ' : '');
				$params .= (!empty($jahr) ? 'c07="'.$jahr.'"' : '');
				$params .= (!empty($rating) ? 'c05="'.$rating.'"' : '');
				$dbh->exec('UPDATE movie SET '.$params.' WHERE idMovie = '.$idMovie.';');
				clearMovieCache();
				$_SESSION['overrideFetch'] = 1;
			}
			
			$params = null;
			if (!empty($file)) {
				$params = "strFilename='".$file."'";
				$dbh->exec('UPDATE files SET '.$params.' WHERE idFile = '.$idFile.';');
				clearMovieCache();
				$_SESSION['overrideFetch'] = 1;
			}
			
			if (!empty($dateAdded)) {
				$dateValue = strtotime($dateAdded);
				if (!empty($params)) { $params .= ', '.$params; }
				$dbh->exec("UPDATE filemap SET dateAdded = '".$dateAdded."', value = ".$dateValue.$params." WHERE idFile = ".$idFile.";");
				$dbh->exec("UPDATE files SET dateAdded = '".$dateAdded."' WHERE idFile = ".$idFile.";");
				clearMovieCache();
				$_SESSION['overrideFetch'] = 1;
			}
			//return;
		}

		if (($act == 'linkInsert' || $act == 'linkUpdate') && $id != -1 && $idMovie != -1) {
			$SQL = 'UPDATE movie SET idSet='.$id.' WHERE idMovie = '.$idMovie.';';
			$_SESSION['overrideFetch'] = 1;
		}
		
		if ($act == 'linkDelete' && $idMovie != -1) {
			$SQL = 'UPDATE movie SET idSet=NULL WHERE idMovie = '.$idMovie.';';
			$_SESSION['overrideFetch'] = 1;
		}
		
		if ($act == 'setname' && $id != -1 && !empty($name)) {
			$name = str_replace('_AND_', '&', $name);
			$SQL = 'UPDATE sets SET strSet="'.$name.'" WHERE idSet = '.$id.';';
			$_SESSION['overrideFetch'] = 1;
		}
		
		if ($act == 'setMoviesetCover' && $id != -1 && $idMovie != -1) {
			$url = '';

			/*** make it or break it ***/
			error_reporting(E_ALL);
			try {
				$db_name = $GLOBALS['db_name'];
				$dbh = new PDO($db_name);
				$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				
				$res = $dbh->query("SELECT url,type FROM art WHERE media_type = 'movie' AND media_id = '".$idMovie."';");
				$poster  = '';
				$fanart  = '';
				foreach($res as $row) {
					$type = $row['type'];
					$url = $row['url'];
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
				
				$dbh->beginTransaction();
				
				$dbh->exec("REPLACE INTO art (media_id, media_type, type, url) VALUES ('".$id."', 'set', 'poster', '".$poster."');");
				$dbh->exec("REPLACE INTO art (media_id, media_type, type, url) VALUES ('".$id."', 'set', 'fanart', '".$fanart."');");
				
				#$dbh->exec("UPDATE art SET url = (SELECT url FROM art WHERE media_id = ".$idMovie." AND media_type = 'movie' AND type = 'poster') WHERE media_id = ".$id." AND media_type = 'set' AND type = 'poster';");
				#$dbh->exec("UPDATE art SET url = (SELECT url FROM art WHERE media_id = ".$idMovie." AND media_type = 'movie' AND type = 'fanart') WHERE media_id = ".$id." AND media_type = 'set' AND type = 'fanart';");
				$dbh->commit();

			} catch(PDOException $e) {
				$dbh->rollBack();
				echo $e->getMessage();
				exit;
			}
			echo 'Setcover was set!<br/>';
			exit;

			if (!empty($url)) {
				$crc = thumbnailHash($url);
				$crcSet = thumbnailHash('videodb://1/7/'.$id.'/');
				
				$go = true;
				$movieCover = "/var/www/img/Thumbnails/".substr($crc, 0, 1)."/".$crc.".jpg";
				$setCover = "/var/www/img/Thumbnails/".substr($crcSet, 0, 1)."/".$crcSet.".jpg";

				if (!empty($setCover)) {
					if (is_file($setCover)) {
						if ( unlink($setCover) === false ) {
							$go = false;
							echo 'Old cover could NOT be deleted!<br/>';
						} else {
							echo 'Old cover was deleted!<br/>';
						}
					}

					if ($go) {
						if ( copy($movieCover, $setCover) or die ('DENiED!') ) {
							echo 'Setcover was set!<br/>';
						} else {
							echo 'Setcover was NOT set!<br/>';
						}
					}
				} //empty setCover
				
				echo '<br/>';
				
				#$movieFanart = "/var/www/img/Thumbnails/Video/Fanart/".$crc.".tbn";
				$movieFanart = "/var/www/img/Thumbnails/".$crc.".jpg";
				$setFanart = '';
				$go = true;
				if (!empty($movieFanart) && is_file($movieFanart)) {
					#$setFanart = "/var/www/img/Thumbnails/Video/Fanart/".$crcSet.".tbn";
					$setFanart = "/var/www/img/Thumbnails/".$crcSet.".jpg";

					if (!empty($setFanart)) {
						if (is_file($setFanart)) {
							if ( unlink($setFanart) === false ) {
								$go = false;
								echo 'Old fanart could NOT be deleted!<br/>';
							} else {
								echo 'Old fanart was deleted!<br/>';
							}
						}
						
						if ($go) {
							if ( copy($movieFanart, $setFanart) or die ('DENiED!') ) {
								echo 'Setfanart was set!<br/>';
							} else {
								echo 'Setfanart was NOT set!<br/>';
							}
						}
					} //empty setFanart
				} //empty movieFanart
				
				exit;
			} //empty path,file
		}

		if ($act == 'addset' && !empty($name)) {
			$GETID_SQL = 'select idSet from sets order by idSet desc limit 0, 1';
			$result = $dbh->query($GETID_SQL);
			$row = $result->fetch();
			$lastId = $row['idSet'];
			$id = $lastId + 1;
			
			$SQL = 'REPLACE INTO sets values ('.$id.', "'.$name.'");';
			$_SESSION['overrideFetch'] = 1;
		}

		if ($act == 'delete' && $id != -1) {
			$dbh->exec('DELETE FROM sets WHERE idSet = '.$id.';');
			$dbh->exec('UPDATE movie SET idSet=NULL WHERE idSet = '.$id.';');
			#$dbh->exec('DELETE FROM setlinkmovie WHERE idSet = '.$id.';');
			$_SESSION['overrideFetch'] = 1;
		}

		if (!empty($SQL)) {
			$dbh->exec($SQL);
			$_SESSION['overrideFetch'] = 1;
		}
		
		#echo 'commit';
		$dbh->commit();

		if ($act == 'setSeen' || $act == 'setUnseen') {
			echo '<span style="font:12px Verdana, Arial;">Episode is set to '.($act == 'setUnseen' ? 'not ' : '').'watched!</span>';
			
		} else if ($act == 'updateEpisode') {
			header('Location: '.($path == '/' ? '' : $path).'/addEpisode.php?update=1&idShow='.$idShow.'&idTvdb='.$idTvdb.'&idEpisode='.$idEpisode.'&closeFrame=1');

		} else if ($act == 'addEpisode') {
			header('Location: '.($path == '/' ? '' : $path).'/addEpisode.php?idShow='.$idShow.'&idTvdb='.$idTvdb.'&closeFrame=1');

		} else if ($act == 'linkInsert' || $act == 'linkUpdate' || $act == 'linkDelete') {
			header('Location: '.($path == '/' ? '' : $path).'/changeMovieSet.php?idMovie='.$idMovie.'&idSet='.$id.'&closeFrame=1');

		} else if ($act == 'setmovieinfo') {
			header('Location: '.($path == '/' ? '' : $path).'/nameEditor.php?change=movie&idMovie='.$idMovie.'&closeFrame=1');

		} else {
			header('Location: '.($path == '/' ? '' : $path).'/setEditor.php');
		}
		exit;

	} catch(PDOException $e) {
		$dbh->rollBack();
		echo $e->getMessage();
	}
?>