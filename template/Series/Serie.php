<?php
include_once "Staffel.php";

class Serie {
	private $serienname       = null;
	private $allEpisodeDeltas = 0;
	private $allEpisodeCount  = 0;
	private $epCountWatched   = 0;
	private $missingCount     = null;
	private $staffeln         = array();
	private $lastStNum        = 0;
	private $idShow           = -1;
	private $idTvdb           = -1;
	private $watched          = true;
	private $watchedAny       = false;
	private $desc             = null;
	private $size             = 0;
	private $fileIds          = null;
	private $rating           = null;
	private $fsk              = null;
	private $genre            = null;
	private $studio           = null;
	private $running          = false;
	private $nextAirDate      = null;
	private $showPath         = null;
	private $isSorted         = false;
	private $parted           = 0;
//		private $codecStats       = null;

	public function __toString() {
		return (string) strtolower($this->serienname);
	}

	function __construct($idShow) {
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
		if (!empty($this->rating)) { return $this->rating; }

		$rating    = 0;
		$calcCount = 0;
		foreach ($this->getStaffeln() as $staffel) {
			if (!is_object($staffel)) { continue; }
			$rating_ = $staffel->getRating();
			if (empty($rating_) || doubleval($rating_) == 0) { continue; }
			$rating += $rating_;
			$calcCount++;
		}

		if ($calcCount > 0) {
			$this->rating = doubleval($rating / $calcCount);
		} else {
			$this->rating = 0;
		}
		return $rating;
	}

	public function getRating() {
		return sprintf("%02.1f", round($this->getRatingVal(), 1));
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
		$res = count($this->staffeln);
		if ($this->getStaffel(0) == null || $this->getStaffel(0)->getStaffelNum() == 0)
			return $res-1;
		return $res;
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
		$ids = substr($ids, 0, strlen($ids)-1);

		$this->fileIds = $ids;
		return $ids;
	}

	public function hasMissingEpisodes() {
		return $this->getMissingCount() > 0;
	}

	public function getMissingCount() {
		if (!is_null($this->missingCount))
			return $this->missingCount;

		foreach ($this->getStaffeln() as $staffel)
			$this->missingCount += $staffel->getMissingCount();

		return $this->missingCount;
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
		return round($watched / $epCount * 100);
	}

	public function getStaffel($staffelNum) {
		$res = null;
		if (isset($this->staffeln[$staffelNum])) {
			$res = $this->staffeln[$staffelNum];
		}
		return $res;
	}

	public function getFirstStaffel() {
		for ($i = 0; $i < count($this->staffeln); $i++) {
			$res = $this->getStaffel($i);
			if (!empty($res) && $res->getStaffelNum() != 0)
				return $res;
		}
		return $res;
	}

	public function getLastStaffel() {
		$max = 0;
		for ($i = $this->lastStNum; $i >= 0; $i--) {
			$st = $this->getStaffel($i);
			$max = empty($st) ? $max : max($max, $st->getStaffelNum());
		}

		if (!empty($this->getStaffel($max)) && is_object($this->getStaffel($max)))
			return $this->getStaffel($max);
		return $this->getStaffel($max-1);
		#return $this->getStaffel($this->lastStNum - 1);
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

	public function addStaffel($staffel): Staffel {
		$sNum = $staffel->getStaffelNum();
		if (!isset($this->staffeln[$sNum])) {
			$this->staffeln[$sNum] = $staffel;
			$staffel->setSerie($this);
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
			$staffel->setIdSeason($episode->getIdSeason());
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
?>
