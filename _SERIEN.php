<?php
/*
	$SerienSQL =  "SELECT tvshow.idShow as idShow, tvshow.c00 as serie, episode.idEpisode as idEpisode, episode.c12 as season, episode.c13 as episode, ".
			" episode.idFile as idFile, episode.c00 as epName, files.strFilename as filename, path.strPath as path \n".
			"FROM episode, tvshow, tvshowlinkepisode, tvshowlinkpath, path, files \n".
			"WHERE tvshow.idShow = tvshowlinkepisode.idShow AND ".
			" tvshow.idShow = tvshowlinkpath.idShow AND ".
			" files.idPath = path.idPath AND ".
			" tvshowlinkepisode.idEpisode = episode.idEpisode AND ".
			" files.idFile = episode.idFile;";
*/

	$SerienSQL =	"SELECT V.*, ".
					"V.c00 as epName, ".
					"V.c01 as epDesc, ".
					"V.c03 as epRating, ".
					"V.c05 as airDate, ".
					"V.c12 as season, ".
					"V.c13 as episode, ".
					"V.strTitle as serie, ".
					"V.idFile as idFile, ".
					"V.strFilename as filename, ".
					"V.strPath as path, ".
					"T.c01 as showDesc, ".
					"T.c12 as idTvdb, ".
					"P.idPath as idPath, ".
					"F.filesize as filesize ".
					"FROM episodeview V".
					", tvshow T ".
					"LEFT JOIN fileinfo F on V.idFile = F.idFile ".
					"LEFT JOIN files FS on V.idFile=FS.idFile ".
					"LEFT JOIN path P on P.idPath=FS.idPath ".
					"WHERE T.idSHow = V.idShow";
	
	function fetchSerien($SQL, $id) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
//$overrideFetch = true;
		
		if ($SQL == null) { $SQL = $GLOBALS['SerienSQL'].';'; }
		if ($id == null) { $id = ''; }
		$SSerien = null;
		
		if (isset($_SESSION['SSerien'.$id]) && $overrideFetch == 0) {
			$SSerien = unserialize($_SESSION['SSerien'.$id]);

		} else {
			$SSerien = fetchSerien__($SQL);
			$SSerien->getSize();
			$SSerien->fetchRating();
			$_SESSION['SSerien'.$id] = serialize($SSerien);
			unset( $_SESSION['overrideFetch'] );
		}

		return $SSerien;
	}

	function fetchSerien__($SQL) {
		/*** make it or break it ***/
		error_reporting(E_ALL);

		$serien = new Serien();
		try {
			$db_name = $GLOBALS['db_name'];
			$dbh = new PDO($db_name);
			$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
			
			$dbh->beginTransaction();
			checkMyEpisodeView($dbh);

			$result = $dbh->query($SQL);
			foreach($result as $row) {
				$idFile = $row['idFile'];
				$idShow = $row['idShow'];
				$idEpisode = $row['idEpisode'];
				$idPath = $row['idPath'];
				$airDate = $row['airDate'];

				$serienname = $row['serie'];
				$epName = $row['epName'];
				$epDesc = ''; //$row['epDesc'];
				$showDesc = $row['showDesc'];
				$season = $row['season'];
				$idTvdb = $row['idTvdb'];
				$rating = $row['epRating'];
				$playcount = $row['playCount'];
				$episodeNum = $row['episode'];
				$filename = $row['filename'];
				$path = $row['path'];
				$filesize = $row['filesize']; //(isset($filesizes[$idFile]) ? $filesizes[$idFile] : 0);

				$ep = new Episode($episodeNum, $season, $idShow, $idTvdb, $idEpisode, $idFile, $idPath, $epName, $epDesc, $rating, $serienname, $airDate, $playcount, $filename, $path, $filesize);
				if (is_object($serien) && is_object($ep)) {
					$serien->addEpisode($ep, $showDesc);
				}
			}
			
			$dbh->commit();

		} catch(PDOException $e) {
			$dbh->rollBack();
			echo $e->getMessage();
		}

		return $serien;
	}

	class Episode {
		private $name;
		private $desc;
		private $idFile;
		private $idShow;
		private $idTvdb;
		private $rating;
		private $idEpisode;
		private $idPath;
		private $episode;
		private $season;
		private $playcount;
		private $serienname;
		private $filename;
		private $path;
		private $airDate;
		private $filesize;
		private $size;

		public function Episode($episode, $season, $idShow, $idTvdb, $idEpisode, $idFile, $idPath, $epName, $epDesc, $rating, $serienname, $airDate, $playcount, $filename, $path, $filesize) {
			$pr = strtolower(substr($serienname, 0, 4));
			$pronoms = array('the ', 'der ', 'die ', 'das ');
			for ($prs = 0; $prs < count($pronoms); $prs++) {
				if ($pr == $pronoms[$prs]) {
					$serienname = strtoupper(substr($serienname, 4, 1)).substr($serienname, 5, strlen($serienname)).', '.substr($serienname, 0, 3);
				}
			}

			$this->episode = $episode;
			$this->season = $season;
			$this->idShow = $idShow;
			$this->idTvdb = $idTvdb;
			$this->rating = doubleval($rating);
			$this->idEpisode = $idEpisode;
			$this->idPath = $idPath;
			$this->idFile = $idFile;
			$this->name = $epName;
			$this->desc = $epDesc;
			$this->serienname = $serienname;
			$this->playcount = $playcount;
			$this->filename = $filename;
			$this->path = $path;
			$this->airDate = $airDate;
			$this->filesize = $filesize;
		}

		public function getName() {
			return $this->name;
		}

		public function getDesc() {
			return $this->desc;
		}

		public function getEpNum() {
			return $this->episode;
		}

		public function getSeason() {
			return $this->season;
		}

		public function getIdShow() {
			return $this->idShow;
		}
		
		public function getRatingVal() {
			return doubleval($this->rating);
		}
		
		public function getRating() {
			$val = round($this->rating, 1);
			if (strlen($val) == 1) { $val .= '.0'; }
			return $val;
		}

		public function getIdPath() {
			return $this->idPath;
		}
		
		public function getIdTvdb() {
			return $this->idTvdb;
		}

		public function getIdEpisode() {
			return $this->idEpisode;
		}

		public function getSerienname() {
			return $this->serienname;
		}

		public function getFilename() {
			return $this->filename;
		}

		public function getAirDate() {
			return $this->airDate;
		}

		public function getPath() {
			return $this->path;
		}

		public function getFilesize() {
			return $this->filesize;
		}
		
		public function getSize() {
			if ($this->size > 0) { return $this->size; }
			
			$size = 0;
			$idFile = $this->getIdFile();
			$filesize = $this->getFilesize();
			$path = mapSambaDirs($this->getPath());
			$filename = $this->getFilename();
			$size = fetchFileSize($idFile, $path, $filename, $filesize, null);

			$this->size = $size;
			return $size;
		}
		
		public function getIdFile() {
			return $this->idFile;
		}

		public function isWatched() {
			return $this->playcount >= 1 ? true : false;
		}
	}

	class Staffel {
		private $episoden = array();
		private $staffelNum = -1;
		private $epCount = 0;
		private $idShow = -1;
		private $idTvdb = -1;
		private $watched = true;
		private $watchedAny = false;
		private $size = 0;
		private $rating = 0.0;
		private $ratingEps = 0;

		public function Staffel($staffelNum) {
			$this->staffelNum = $staffelNum;
		}

		public function sortEpisoden() {
			usort($this->episoden, function($a, $b) {
				if($a->getEpNum() == $b->getEpNum()) return 0;
				return ($a->getEpNum() < $b->getEpNum()) ? -1 : 1;
			});
		}

		public function getStaffelNum() {
			return $this->staffelNum;
		}

		public function getEpisodeCount() {
			return count($this->episoden);
		}

		public function getEpisoden() {
			$this->sortEpisoden();
			return $this->episoden;
		}

		public function getIdShow() {
			return $this->idShow;
		}

		public function getIdTvdb() {
			return $this->idTvdb;
		}

		public function getRatingVal() {
			#if ($this->rating > 0) { return $this->rating; }
			
			if ($this->ratingEps == 0) { return 0.0; }
			
			$rating = doubleval($this->rating / $this->ratingEps);
			#$this->rating = $rating;
			return $rating;
		}
		
		public function getRating() {
			$val = round($this->getRatingVal(), 1);
			if (strlen($val) == 1) { $val .= '.0'; }
			return $val;
		}
		
		public function getEpisode($epNum) {
			$res = null;
			if (isset($this->episoden[$epNum])) {
				$res = $this->episoden[$epNum];
			}
			return $res;
		}
		
		public function addRating($rating) {
			if (doubleval($rating) == 0) { return; }
			$this->rating += $rating;
			$this->ratingEps++;
		}
		
		public function findEpisode($idEpisode) {
			$res = null;
			foreach($this->episoden as $episode) {
				if ($idEpisode == $episode->getIdEpisode()) {
					$res = $episode;
					break;
				}
				
			}
			return $res;
		}

		public function isWatched() {
			return $this->watched;
		}

		public function isWatchedAny() {
			return $this->watchedAny;
		}
		
		public function getSize() {
			if ($this->size > 0) { return $this->size; }
			
			$size = 0;
			foreach ($this->getEpisoden() as $epi) {
				if (!is_object($epi)) { continue; }
				$size += $epi->getSize();
			}
			
			$this->size = $size;
			return $size;
		}

		public function addEpisode(&$episode) {
			if ($this->idShow == -1) {
				$this->idShow = $episode->getIdShow();
				$this->idTvdb = $episode->getIdTvdb();
			} else {
				if ($this->idShow != $episode->getIdShow()) {
					return;
				}
			}

			if (!$episode->isWatched()) {
				$this->watched = false;
			} else {
				$this->watchedAny = true;
			}
			
			$this->addRating($episode->getRatingVal());
			$this->episoden[$this->epCount++] = $episode;
		}
	}

	class Serie {
		private $serienname = null;
		private $allEpisodeCount = 0;
		private $staffeln = array();
		private $idShow = -1;
		private $idTvdb = -1;
		private $watched = true;
		private $watchedAny = false;
		private $desc = null;
		private $size = 0;
		private $rating = 0.0;

		public function __toString() {
			return (string) strtolower($this->serienname);
		}

		public function Serie($idShow) {
			$this->idShow = $idShow;
			$this->staffeln = array();
		}

		public function sortStaffeln() {
			usort($this->staffeln, function($a, $b) {
				if($a->getStaffelNum() == $b->getStaffelNum()) return 0;
				return ($a->getStaffelNum() < $b->getStaffelNum()) ? -1 : 1;
			});
		}

		public function getIdShow() {
			return $this->idShow;
		}

		public function getIdTvdb() {
			return $this->idTvdb;
		}
		
		public function getRatingVal() {
			if ($this->rating > 0) { return $this->rating; }
			
			$rating = 0;
			foreach ($this->getStaffeln() as $staffel) {
				if (!is_object($staffel)) {
					continue;
				}
				
				$rating += $staffel->getRating();
			}
			
			$this->rating = doubleval($rating / $this->getStaffelCount());
			return $rating;
		}
		
		public function getRating() {
			$val = round($this->getRatingVal(), 1);
			if (strlen($val) == 1) { $val .= '.0'; }
			return $val;
		}

		public function getName() {
			return $this->serienname;
		}

		public function getDesc() {
			return $this->desc;
		}

		public function getStaffeln() {
			$this->sortStaffeln();
			return $this->staffeln;
		}

		public function getStaffelCount() {
			return count($this->staffeln);
		}
		
		public function getSize() {
			if ($this->size > 0) { return $this->size; }
			
			$size = 0;
			foreach ($this->getStaffeln() as $staffel) {
				if (!is_object($staffel)) {
					continue;
				}
				
				$size += $staffel->getSize();
			}
			
			$this->size = $size;
			return $size;
		}

		public function getAllEpisodeCount() {
			return $this->allEpisodeCount;
		}

		public function getStaffel($staffelNum) {
			$res = null;
			if (isset($this->staffeln[$staffelNum])) {
				$res = $this->staffeln[$staffelNum];
			}
			return $res;
		}
		
		public function findEpisode($idEpisode) {
			$res = null;
			foreach($this->staffeln as $staffel) {
				$episode = $staffel->findEpisode($idEpisode);
				if ($episode != null) {
					$res = $episode;
					break;
				}
			}

			return $res;
		}

		public function isWatched() {
			return $this->watched;
		}

		public function isWatchedAny() {
			return $this->watchedAny;
		}

		public function addStaffel(&$staffel) {
			$sNum = $staffel->getStaffelNum();
			if (!isset($this->staffeln[$sNum])) {
				$this->staffeln[$sNum] = $staffel;
			}
			return $this->staffeln[$sNum];
		}

		public function addEpisode($episode, $showDesc) {
			if ($this->idShow != $episode->getIdShow()) {
				return;
			}

			if ($this->serienname == null) {
				$this->serienname = $episode->getSerienname();
			}

			$season = $episode->getSeason();
			$staffel = null;
			if (!isset($this->staffeln[$season])) {
				$staffel = $this->addStaffel(new Staffel($season));
			} else {
				$staffel = $this->getStaffel($season);
			}

			if (!$episode->isWatched()) {
				$this->watched = false;
			} else {
				$this->watchedAny = true;
			}

			if ($this->idTvdb == -1) {
				$this->idTvdb = $episode->getIdTvdb();
			}

			if ($this->desc == null) {
				$this->desc = $showDesc;
			}
			
			$staffel->addEpisode($episode);
			$this->allEpisodeCount++;
		}
	}
	
	class Serien {
		private $serien = array();
		private $tvdbIds = array();
		private $size = 0;

		public function Serien() { }

		public function getSerien() {
			return $this->serien;
		}

		public function sortSerien() {
			usort($this->serien, "strcasecmp");
		}

		public function getSerienCount() {
			return count($this->serien);
		}
		
		public function fetchRating() {
			$size = 0;
			foreach ($this->getSerien() as $serie) {
				if (!is_object($serie)) {
					continue;
				}
				
				$serie->getRatingVal();
			}
		}
		
		public function getSize() {
			if ($this->size > 0) { return $this->size; }
			
			$size = 0;
			foreach ($this->getSerien() as $serie) {
				if (!is_object($serie)) {
					continue;
				}
				
				$size += $serie->getSize();
			}
			
			$this->size = $size;
			return $size;
		}

		public function addSerie($idShow, $serienname) {
			$this->serien[$idShow] = new Serie($idShow, $serienname);
			return $this->serien[$idShow];
		}

		public function getSerie($idShow) {
#print_r( $this->serien );
			$res = null;
			foreach($this->serien as $serie) {
				if ($serie->getIdShow() == $idShow) {
					$res = $serie;
					break;
				}
			}
			return $res;
			
			/*
			$res = null;
			if (isset($this->serien[$idShow])) {
				$res = $this->serien[$idShow];
			}
			return $res;
			*/
		}

		public function getSerieIdByIdTvdb($idTvdb) {
#print_r( $this->tvdbIds );
			$res = -1;
			if (isset($this->tvdbIds[$idTvdb])) {
				$res = $this->tvdbIds[$idTvdb];
			}
			return $res;
		}

		public function addEpisode($episode, $showDesc) {
			$idShow = $episode->getIdShow();
			$serienname = $episode->getSerienname();
			$season = $episode->getSeason();
			
			$idTvdb = $episode->getIdTvdb();
			$this->tvdbIds[$idTvdb] = $idShow;

			$serie = $this->getSerie($idShow);
			if ($serie == null) {
				$serie = $this->addSerie($idShow, $serienname);
			}
			$serie->addEpisode($episode, $showDesc);
		}
	}
?>
