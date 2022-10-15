<?php
include_once "Episode.php";

class Staffel {
	private $episoden       = array();
	private $sources        = array();
	private $serie          = null;
	private $staffelNum     = -1;
	private $deltas         = 0;
	private $parted         = 0;
	private $epCount        = 0;
	private $epCountWatched = 0;
	private $lastEpNum      = -1;
	private $missingCount   = 0;
	private $idShow         = -1;
	private $idSeason       = -1;
	private $idTvdb         = -1;
	private $watched        = true;
	private $watchedAny     = false;
	private $size           = 0;
	private $fileIds        = null;
	private $rating         = 0.0;
	private $ratingEps      = 0;
	private $isSorted       = false;

	function __construct($staffelNum) {
		$this->staffelNum = $staffelNum;
	}

	public function setSerie($serie) {
		$this->serie = $serie;
	}

	public function getSerie() {
		return $this->serie;
	}

	public function setIdSeason($idSeason) {
		$this->idSeason = $idSeason;
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
		if ($a->getIdEpisode() == $b->getIdEpisode()) return 0;
		return ($a->getIdEpisode() < $b->getIdEpisode()) ? -1 : 1;
	}

	public function compEpisodesByAirDate($a, $b) {
		if ($a->getAirDate() == $b->getAirDate()) return 0;
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
		return round($watched / $epCount * 100);
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
		if (true) { return 0.0; }

		if ($this->ratingEps == 0) { return 0.0; }

		$rating = doubleval($this->rating / $this->ratingEps);
		return $rating;
	}

	public function getRating() {
		return sprintf("%02.1f", round($this->getRatingVal(), 1));
	}

	public function getEpisode($epNum) {
		$res = null;
		if (isset($this->episoden[$epNum])) {
			$res = $this->episoden[$epNum];
		}
		return $res;
	}

	public function getFirstEpisode() {
		for ($i = 0; $i < count($this->episoden); $i++) {
			$res = $this->getEpisode($i);
			if ($res->getEpNum() == 1)
				return $res;
		}
		return null;
	}

	public function getLastEpisode() {
		for ($i = $this->lastEpNum-1; $i >= 0; $i--) {
			$res = $this->getEpisode($i);
			if ($res != null)
				return $res;
		}
		return null;
	}

	public function hasMissingEpisodes() {
		return $this->missingCount > 0;
	}

	public function getMissingCount() {
		return max($this->missingCount, 0);
	}

	public function addRating($rating) {
		if (empty($rating) || doubleval($rating) == 0) { return; }
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
		#if ($count > 1)
		$ids = substr($ids, 0, strrpos($ids, ','));

		$this->fileIds = $ids;
		return $ids;
	}

	public function getSources() {
		return $this->sources;
	}

	public function getSourceCount($key) {
		return $this->sources[$key];
	}

	public function addEpisode($episode) {
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

		$delta  = $episode->getDelta();
		$delta_ = $episode->getDelta_();
		if (!empty($delta))
			$this->deltas += $delta_;

		$thisEpNum = $episode->getEpNum();
		$lastEpNum = $this->lastEpNum;
		if ($thisEpNum > $this->lastEpNum) {
			$this->lastEpNum = $thisEpNum + (empty($delta_) ? 0 : $delta_);
		}

		if (!empty($this->episoden[$thisEpNum-1]) &&
			$this->episoden[$thisEpNum-1]->getEpNum() == $thisEpNum) {
			$this->parted++;
		}

		$this->addRating($episode->getRatingVal());
		$this->episoden[$this->epCount++] = $episode;

		if ($this->staffelNum != 0 && $lastEpNum != 0)
			$this->missingCount = $this->lastEpNum - $this->getEpisodeCount();

		$source = $episode->getSource();
		if ($source != null) {
			if (empty($this->sources[$source]))
				$this->sources[$source] = 1;
			else
				$this->sources[$source]++;
		}
	}
}
?>