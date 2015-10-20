<?php
	$MenuSerienSQL   = "SELECT V.idShow AS idShow, V.idEpisode AS idEpisode, V.strTitle AS strTitle, V.c12 AS season, V.c13 AS episode, V.c00 AS title, V.playCount AS playCount, V.c03 AS rating, (SELECT COUNT(*) FROM episode E WHERE E.idShow = V.idShow AND E.c12 = V.c12) AS sCount FROM episodeviewMy V";
	$LastSerienSQL   = $MenuSerienSQL." ORDER BY idEpisode DESC LIMIT 30";
	$SearchSerienSQL = $MenuSerienSQL." WHERE lower(V.c04) LIKE '%[SEARCH]%' OR lower(V.c01) LIKE '%[SEARCH]%' OR lower(V.c00) LIKE '%[SEARCH]%' ORDER BY V.strTitle ASC";
	$SQLrunning      = "SELECT R.idShow AS idShow, R.running AS running, A.airdate AS airdate FROM tvshowrunning R LEFT JOIN nextairdate A ON R.idShow = A.idShow;";
	$SeasonDirSQL    = "SELECT idShow, strPath FROM seasonview;";
	$ShowCodecSQL    = "SELECT E.idShow AS idShow, SD.strVideoCodec AS codec, COUNT(SD.strVideoCodec) AS count FROM streamdetails AS SD, episode AS E WHERE SD.strVideoCodec IS NOT NULL AND SD.idfile = E.idFile AND E.idShow=[IDSHOW] GROUP BY E.idShow, SD.strVideoCodec ORDER BY count DESC;";
	$episodeLinkSQL  = "SELECT * FROM episodelinkepisode";
	$SerienSQL       = "SELECT V.*, ".
			   "V.c00 AS epName, ".
			   "V.c01 AS epDesc, ".
			   "V.c03 AS epRating, ".
			   "V.c04 AS guests, ".
			   "V.c05 AS airDate, ".
			   "V.c09 AS duration, ".
			   "V.c12 AS season, ".
			   "V.c13 AS episode, ".
			   #"T.c16 AS showPath, ".
			   "V.strTitle AS serie, ".
			   "V.idFile AS idFile, ".
			   "V.strFilename AS filename, ".
			   "V.strPath AS path, ".
			   "T.c01 AS showDesc, ".
			   "T.c08 AS genre, ".
			   "T.c12 AS idTvdb, ".
			   "T.c13 AS fsk, ".
			   "T.c14 AS studio, ".
			   "P.idPath AS idPath, ".
			   "F.filesize AS filesize ".
			   "FROM episodeview V, tvshow T ".
			   "LEFT JOIN fileinfo F ON V.idFile = F.idFile ".
			   "LEFT JOIN files FS ON V.idFile=FS.idFile ".
			   "LEFT JOIN path P ON P.idPath=FS.idPath ".
			   "WHERE T.idShow = V.idShow";
	
	function fetchSearchSerien($search) {
		$SQL = $GLOBALS['SearchSerienSQL'].';';
		$SQL = str_replace('[SEARCH]', $search, $SQL);
		return fetchSerienToArray($SQL, 'FSerien_'.$search);
	}
	
	function fetchLastSerien() {
		$SQL = $GLOBALS['LastSerienSQL'].';';
		return fetchSerienToArray($SQL, 'LSerien');
	}
	
	function fetchSerienToArray($SQL, $sessionKey) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
		
		$result = array();
		if (isset($_SESSION[$sessionKey]) && $overrideFetch == 0) {
			$result = unserialize($_SESSION[$sessionKey]);
			
		} else {
			$sqlResult = querySQL($SQL, false);
			foreach($sqlResult as $row) {
				$result[$row['strTitle']][] = array(
				'idShow' => $row['idShow'], 'serie'     => $row['strTitle'],  'episode' => $row['episode'],
				'season' => $row['season'], 'idEpisode' => $row['idEpisode'], 'title'   => $row['title'], 
				'sCount' => $row['sCount'], 'playCount' => $row['playCount'], 'rating'  => $row['rating']
				);
			}
			
			$_SESSION[$sessionKey] = serialize($result);
			unset( $_SESSION['overrideFetch'] );
		}
		
		return $result;
	}
	
	function fetchSerien($SQL, $id, $dbh = null) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
		
		if (empty($SQL)) { $SQL = $GLOBALS['SerienSQL'].';'; }
		if (empty($id))  { $id  = ''; }
		$SSerien = null;
		
		if (isset($_SESSION['SSerien'.$id]) && $overrideFetch == 0) {
			$SSerien = unserialize($_SESSION['SSerien'.$id]);
			
		} else {
			$SSerien = fetchSerien__($SQL, $dbh);
			$SSerien->getSize();
			$SSerien->fetchRating();
			$_SESSION['SSerien'.$id] = serialize($SSerien);
			unset( $_SESSION['overrideFetch'] );
		}
		return $SSerien;
	}

	function fetchSerien__($SQL, $dbh = null) {
		$runs   = array();
		$serien = new Serien();
		
		checkEpLinkEpTable($dbh);
		checkMyEpisodeView($dbh);
		checkNextAirDateTable($dbh);
		checkTvshowRunningTable($dbh);
		
		$SQLepLnk= $GLOBALS['episodeLinkSQL'];
		$resL    = querySQL_($dbh, $SQLepLnk, false);
		$links   = array();
		foreach($resL as $row) { $links[$row['idFile']] = $row['delta']; }
		
		$SQLrunning = $GLOBALS['SQLrunning'];
		$resF       = querySQL_($dbh, $SQLrunning, false);
		foreach($resF as $run_) {
			$idShow      = $run_['idShow'];
			$running     = isset($run_['running']) ? true : false;
			$nextAirDate = isset($run_['airdate']) ? $run_['airdate'] : null;
			$runs[$idShow]['running']     = $running;
			$runs[$idShow]['nextairdate'] = $nextAirDate;
		}
		
/*
		$SQLcodec = $GLOBALS['ShowCodecSQL'];
		$resC     = querySQL_($dbh, $SQLcodec, false);
		$codecs   = array();
		foreach($resC as $row) {
			$idShow = $row['idShow'];
			$codecs[$idShow][$row['codec']] = $row['count'];
		}
*/

		$SQLdirs = $GLOBALS['SeasonDirSQL'];
		$resD    = querySQL_($dbh, $SQLdirs, false);
		$dirs    = array();
		foreach($resD as $row) {
			$idShow  = $row['idShow'];
			$strPath = $row['strPath'];
			if (empty($dirs[$idShow])) { $dirs[$idShow] = $strPath; }
		}
		
		$result = querySQL_($dbh, $SQL, false);
		foreach($result as $row) {
			$idFile      = $row['idFile'];
			$idShow      = $row['idShow'];
			$idEpisode   = $row['idEpisode'];
			$idPath      = $row['idPath'];
			$airDate     = $row['airDate'];
			$serienname  = $row['serie'];
			$epName      = $row['epName'];
			$duration    = $row['duration'];
			$showDesc    = $row['showDesc'];
			$season      = $row['season'];
			$idTvdb      = $row['idTvdb'];
			$rating      = $row['epRating'];
			$playcount   = $row['playCount'];
			$genre       = $row['genre'];
			$fsk         = $row['fsk'];
			$studio      = $row['studio'];
			$episodeNum  = $row['episode'];
			$delta       = isset($links[$idFile]) ? $links[$idFile] : null;
			$filename    = $row['filename'];
			$path        = $row['path'];
			$showPath    = isset($dirs[$idShow]) ? $dirs[$idShow] : '';
			$filesize    = $row['filesize'];
			$epDesc      = '';
			$running     = (!empty($runs) && isset($runs[$idShow]['running']) ? true : false);
			$nextAirDate = (!empty($runs) && isset($runs[$idShow]['nextairdate']) ? $runs[$idShow]['nextairdate'] : null);
			
			$ep = new Episode($episodeNum, $delta, $season, $idShow, $idTvdb, $idEpisode, $idFile, $idPath, $path, $epName, $epDesc, $rating, $serienname, $airDate, $duration, $playcount, $filename, $filesize);
			if (is_object($serien) && is_object($ep)) {
				#$serien->addEpisode($ep, $fsk, $genre, $studio, $showDesc, $showPath, $running, $nextAirDate, $codecs[$idShow]);
				$serien->addEpisode($ep, $fsk, $genre, $studio, $showDesc, $showPath, $running, $nextAirDate);
			}
		}
		
		return $serien;
	}

	function fetchShowCodecs($idShow, $dbh = null) {
		$SQLcodec = $GLOBALS['ShowCodecSQL'];
		$SQLcodec = str_replace('[IDSHOW]', $idShow, $SQLcodec);
		$resC     = querySQL_($dbh, $SQLcodec, false);
		$codecs   = array();
		foreach($resC as $row) {
			#$idShow = $row['idShow'];
			$codecs[$row['codec']] = $row['count'];
		}
		
		return $codecs;
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
		private $path;
		private $episode;
		private $delta;
		private $season;
		private $duration;
		private $playcount;
		private $serienname;
		private $filename;
		private $airDate;
		private $filesize;
		private $size;
		
		public function Episode($episode, $delta, $season, $idShow, $idTvdb, $idEpisode, $idFile, $idPath, $path, $epName, $epDesc, $rating, $serienname, $airDate, $duration, $playcount, $filename, $filesize) {
			$pr = strtolower(substr($serienname, 0, 4));
			$pronoms = array('the ', 'der ', 'die ', 'das ');
			for ($prs = 0; $prs < count($pronoms); $prs++) {
				if ($pr == $pronoms[$prs]) {
					$serienname = strtoupper(substr($serienname, 4, 1)).substr($serienname, 5, strlen($serienname)).', '.substr($serienname, 0, 3);
				}
			}
			
			$this->episode    = $episode;
			$this->delta      = $delta;
			$this->season     = $season;
			$this->idShow     = $idShow;
			$this->idTvdb     = $idTvdb;
			$this->rating     = doubleval($rating);
			$this->idEpisode  = $idEpisode;
			$this->idFile     = $idFile;
			$this->idPath     = $idPath;
			$this->path       = $path;
			$this->name       = $epName;
			$this->desc       = $epDesc;
			$this->serienname = $serienname;
			$this->duration   = $duration;
			$this->playcount  = $playcount;
			$this->filename   = $filename;
			$this->airDate    = $airDate;
			$this->filesize   = $filesize;
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
		
		public function getDelta() {
			return empty($this->delta) ? null : (intval($this->episode) + intval($this->delta));
		}
		
		public function getDelta_() {
			return empty($this->delta) ? null : intval($this->delta);
		}
		
		public function getSeason() {
			return $this->season;
		}
		
		public function getSE($epDelta) {
			return getFormattedSE($this->season, $this->episode, $epDelta);
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
		
		public function getDuration() {
			return $this->duration;
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
			$size = fetchFileSize(
					$this->getIdFile(), 
					mapSambaDirs($this->getPath()), 
					$this->getFilename(), 
					$this->getFilesize(), 
					null
				);

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
		private $episoden       = array();
		private $staffelNum     = -1;
		private $deltas         = 0;
		private $parted         = 0;
		private $epCount        = 0;
		private $epCountWatched = 0;
		private $lastEpNum      = 0;
		private $idShow         = -1;
		private $idTvdb         = -1;
		private $watched        = true;
		private $watchedAny     = false;
		private $size           = 0;
		private $fileIds        = null;
		private $rating         = 0.0;
		private $ratingEps      = 0;
		private $isSorted       = false;

		public function Staffel($staffelNum) {
			$this->staffelNum = $staffelNum;
		}

		public function sortEpisoden() {
			if ($this->isSorted) { return; }
			usort($this->episoden, function($a, $b) {
				if($a->getEpNum() == $b->getEpNum()) return $this->compEpisodesByIdEpisode($a, $b);
				return ($a->getEpNum() < $b->getEpNum()) ? -1 : 1;
			});
			$this->isSorted = true;
		}

		public function compEpisodesByIdEpisode($a, $b) {
			if($a->getIdEpisode() == $b->getIdEpisode()) return 0;
			return ($a->getIdEpisode() < $b->getIdEpisode()) ? -1 : 1;
		}

		public function compEpisodesByAirDate($a, $b) {
			if($a->getAirDate() == $b->getAirDate()) return 0;
			return ($a->getAirDate() < $b->getAirDate()) ? -1 : 1;
		}

		public function getStaffelNum() {
			return $this->staffelNum;
		}

		public function getEpisodeCount() {
			return count($this->episoden) + $this->deltas - $this->parted;
		}
		
		public function getEpCountWatched() {
			return $this->epCountWatched;
		}
		
		public function getEpCountUnwatched() {
			return $this->getEpisodeCount() - $this->getEpCountWatched();
		}
		
		public function getWatchedPercent() {
			if (!$this->isWatchedAny()) { return null; }
			$watched = $this->getEpCountWatched();
			$epCount = $this->getEpisodeCount();
			return round($watched / $epCount * 100, 0);
		}
		
		public function getLastEpNum() {
			return $this->lastEpNum;
		}
		
		public function getParted() {
			return $this->parted;
		}
		
		public function getEpisoden() {
			#$this->sortEpisoden();
			return $this->episoden;
		}
		
		public function getIdShow() {
			return $this->idShow;
		}

		public function getIdTvdb() {
			return $this->idTvdb;
		}

		public function getRatingVal() {
			if ($this->ratingEps == 0) { return 0.0; }
			
			$rating = doubleval($this->rating / $this->ratingEps);
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
		
		public function getLastEpisode() {
			return $this->getEpisode($this->lastEpNum - 1);
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
		
		public function getIdFiles() {
			if ($this->fileIds != null) { return $this->fileIds; }
			
			$ids   = '';
			$count = 0;
			foreach ($this->getEpisoden() as $epi) {
				if (!is_object($epi)) { continue; }
				$ids .= $epi->getIdFile().',';
				$count++;
			}
			$ids = trim($ids);
			if ($count > 1)
				$ids = substr($ids, 0, count($ids)-2);
			
			$this->fileIds = $ids;
			return $ids;
		}

		public function addEpisode(&$episode) {
			if ($this->idShow == -1) {
				$this->idShow = $episode->getIdShow();
				$this->idTvdb = $episode->getIdTvdb();
			} else {
				if ($this->idShow != $episode->getIdShow()) { return; }
			}
			
			if (!$episode->isWatched()) {
				$this->watched = false;
			} else {
				$this->watchedAny = true;
				$this->epCountWatched++;
			}
			
			$delta = $episode->getDelta();
			if (!empty($delta))
				$this->deltas += $episode->getDelta_();
			
			if ($episode->getEpNum() > $this->lastEpNum) {
				$this->lastEpNum = $episode->getEpNum() + (empty($delta) ? 0 : $delta);
			}
			
			if (!empty($this->episoden[$episode->getEpNum()-1]) && 
			           $this->episoden[$episode->getEpNum()-1]->getEpNum() == $episode->getEpNum()) {
				$this->parted++;
			}
			
			$this->addRating($episode->getRatingVal());
			$this->episoden[$this->epCount++] = $episode;
		}
	}

	class Serie {
		private $serienname      = null;
		private $allEpisodeDeltas= 0;
		private $allEpisodeCount = 0;
		private $epCountWatched  = 0;
		private $staffeln        = array();
		private $lastStNum       = 0;
		private $idShow          = -1;
		private $idTvdb          = -1;
		private $watched         = true;
		private $watchedAny      = false;
		private $desc            = null;
		private $size            = 0;
		private $fileIds         = null;
		private $rating          = 0.0;
		private $fsk             = null;
		private $genre           = null;
		private $studio          = null;
		private $running         = false;
		private $nextAirDate     = null;
		private $showPath        = null;
		private $isSorted        = false;
		private $parted          = 0;
		#private $codecStats      = null;
		
		public function __toString() {
			return (string) strtolower($this->serienname);
		}

		public function Serie($idShow) {
			$this->idShow = $idShow;
			$this->staffeln = array();
		}

		public function sortStaffeln() {
			if ($this->isSorted) { return; }
			usort($this->staffeln, function($a, $b) {
				if($a->getStaffelNum() == $b->getStaffelNum()) return 0;
				return ($a->getStaffelNum() < $b->getStaffelNum()) ? -1 : 1;
			});
			$this->isSorted = true;
		}

		public function getIdShow() {
			return $this->idShow;
		}

		public function getIdTvdb() {
			return $this->idTvdb;
		}
		
		public function setRunning($running) {
			$this->running = $running;
		}
		
		public function isRunning() {
			return $this->running;
		}
		
		public function setNextAirDate($nextAirDate) {
			$this->nextAirDate = $nextAirDate;
		}
		
		public function getNextAirDate() {
			return $this->nextAirDate;
		}
		
		public function getNextAirDateStr() {
			$airDate = $this->getNextAirDate();
			return !empty($airDate) ? date('Y-m-d', $airDate) : null;
		}
		
		public function getRatingVal() {
			if ($this->rating > 0) { return $this->rating; }
			
			$rating = 0;
			foreach ($this->getStaffeln() as $staffel) {
				if (!is_object($staffel)) { continue; }
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

		public function getFsk() {
			return $this->fsk;
		}
		
		public function setFsk($fsk) {
			$this->fsk = $fsk;
		}

		public function getGenre() {
			return $this->genre;
		}

		public function setGenre($genre) {
			$this->genre = $genre;
		}

		public function getStudio() {
			return $this->studio;
		}

		public function setStudio($studio) {
			$this->studio = $studio;
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
				if (!is_object($staffel)) { continue; }
				$size += $staffel->getSize();
			}
			
			$this->size = $size;
			return $size;
		}
		
		public function getIdFiles() {
			if ($this->fileIds != null) { return $this->fileIds; }
			
			$ids   = '';
			$count = 0;
			foreach ($this->getStaffeln() as $staffel) {
				if (!is_object($staffel)) { continue; }
				$ids .= $staffel->getIdFiles().',';
				$count++;
			}
			$ids = trim($ids);
			if ($count > 1)
				$ids = substr($ids, 0, count($ids)-2);
			
			$this->fileIds = $ids;
			return $ids;
		}
		
		public function getAllEpisodeCount() {
			return $this->allEpisodeCount + $this->allEpisodeDeltas - $this->getPartedSize();
		}
		
		public function getEpCountWatched() {
			return $this->epCountWatched;
		}
		
		public function getEpCountUnwatched() {
			return $this->getAllEpisodeCount() - $this->getEpCountWatched();
		}
		
		public function getWatchedPercent() {
			if (!$this->isWatchedAny()) { return null; }
			$watched = $this->epCountWatched;
			$epCount = $this->allEpisodeCount;
			return round($watched / $epCount * 100, 0);
		}
		
		public function getStaffel($staffelNum) {
			$res = null;
			if (isset($this->staffeln[$staffelNum])) {
				$res = $this->staffeln[$staffelNum];
			}
			return $res;
		}
		
		public function getLastStaffel() {
			return $this->getStaffel($this->lastStNum - 1);
		}

		public function getLastEpisode() {
			return $this->getLastStaffel()->getLastEpisode();
		}

		public function setShowpath($path) {
			$this->showPath = $path;
		}
		
		public function getShowpath() {
			return $this->showPath;
		}
		
		public function findEpisode($idEpisode) {
			$res = null;
			foreach($this->staffeln as $staffel) {
				$episode = $staffel->findEpisode($idEpisode);
				if (!empty($episode)) {
					$res = $episode;
					break;
				}
			}
			return $res;
		}
		
		public function getPartedSize() {
			if ($this->parted > 0) { return $this->parted; }
			
			$parted = 0;
			foreach ($this->getStaffeln() as $staffel) {
				if (!is_object($staffel)) { continue; }
				$parted += $staffel->getParted();
			}
			
			$this->parted = $parted;
			return $parted;
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
			
			if ($sNum > $this->lastStNum) {
				$this->lastStNum = $sNum;
			}
			return $this->staffeln[$sNum];
		}

		public function addEpisode($episode, $showDesc) {
			if ($this->idShow != $episode->getIdShow()) {
				return;
			}

			if (empty($this->serienname)) {
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
				$this->epCountWatched++;
			}

			if ($this->idTvdb == -1) {
				$this->idTvdb = $episode->getIdTvdb();
			}

			if (empty($this->desc)) {
				$this->desc = $showDesc;
			}
			
			$delta = $episode->getDelta();
			if (!empty($delta))
				$this->allEpisodeDeltas += $episode->getDelta_();
			
			$staffel->addEpisode($episode);
			$this->allEpisodeCount++;
		}
		
		/*
		function addCodecStats($codecStats) {
			if ($this->codecStats == null)
				$this->codecStats = $codecStats;
		}
		
		function getCodecStats() {
			return $this->codecStats;
		}
		*/
	}
	
	class Serien {
		private $serien   = array();
		private $tvdbIds  = array();
		private $isSorted = false;
		private $size     = 0;
		private $o1       = -1;
		private $o2       = 1;
		
		public function Serien() { }

		public function getSerien() {
			return $this->serien;
		}

		public function sortSerien() {
			if ($this->isSorted) { return; }
			usort($this->serien, "strcasecmp");
			$this->isSorted = true;
			$_SESSION['SSerien'] = serialize($this);
		}
		
		public function sortSerienRatingAsc() {
			$this->sortSerienRating(1, -1);
		}
		
		public function sortSerienRatingDesc() {
			$this->sortSerienRating(-1, 1);
		}
		
		public function sortSerienRating($o1, $o2) {
			if ($this->isSorted) { return; }
			
			$this->o1 = $o1;
			$this->o2 = $o2;
			usort($this->serien, function($a, $b) {
				if($a->getRatingVal() == $b->getRatingVal()) return 0;
				return ($a->getRatingVal() < $b->getRatingVal()) ? $this->o1 : $this->o2;
			});
			
			$this->isSorted = true;
			$_SESSION['SSerien'] = serialize($this);
		}
		
		public function sortSerienAirdateAsc() {
			$this->sortSerienAirdate(-1, 1, 'asc');
		}
		
		public function sortSerienAirdateDesc() {
			$this->sortSerienAirdate(1, -1, 'desc');
		}
		
		public function sortSerienAirdate($o1, $o2, $order) {
			$this->o1 = $o1;
			$this->o2 = $o2;
			usort($this->serien, function($a, $b) {
				if($a->getNextAirDate() == $b->getNextAirDate()) return 0;
				return ($a->getNextAirDate() < $b->getNextAirDate()) ? $this->o1 : $this->o2;
			});
		}
		
		public function setUnsorted() {
			$this->isSorted = false;
		}

		public function getSerienCount() {
			return count($this->serien);
		}
		
		public function fetchRating() {
			$size = 0;
			foreach ($this->getSerien() as $serie) {
				if (!is_object($serie)) { continue; }
				$serie->getRatingVal();
			}
		}
		
		public function getSize() {
			if ($this->size > 0) { return $this->size; }
			
			$size = 0;
			foreach ($this->getSerien() as $serie) {
				if (!is_object($serie)) { continue; }
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
			$res = null;
			foreach($this->serien as $serie) {
				if ($serie->getIdShow() == $idShow) {
					$res = $serie;
					break;
				}
			}
			return $res;
		}

		public function getSerieIdByIdTvdb($idTvdb) {
			$res = -1;
			if (isset($this->tvdbIds[$idTvdb])) {
				$res = $this->tvdbIds[$idTvdb];
			}
			return $res;
		}
		
		public function addEpisode($episode, $fsk, $genre, $studio, $showDesc, $showPath, $running, $nextAirDate) {
		#public function addEpisode($episode, $fsk, $genre, $studio, $showDesc, $showPath, $running, $nextAirDate, $codecStats) {
			$idShow = $episode->getIdShow();
			$serienname = $episode->getSerienname();
			$season = $episode->getSeason();
			
			$idTvdb = $episode->getIdTvdb();
			$this->tvdbIds[$idTvdb] = $idShow;

			$serie = $this->getSerie($idShow);
			if (empty($serie)) {
				$serie = $this->addSerie($idShow, $serienname);
				$serie->setFsk($fsk);
				$serie->setGenre($genre);
				$serie->setStudio($studio);
				$serie->setRunning($running);
				$serie->setNextAirDate($nextAirDate);
				$serie->setShowpath($showPath);
			}
			$serie->addEpisode($episode, $showDesc);
			#$serie->addCodecStats($codecStats);
		}
	}
?>
