<?php
include_once "Serie.php";

class Serien {
	private $serien   = array();
	private $tvdbIds  = array();
	private $isSorted = false;
	private $size     = 0;
	private $o1       = -1;
	private $o2       = 1;

	function __construct() { }
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
			if ($a->getNextAirDate() == $b->getNextAirDate()) return 0;
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
		#$season = $episode->getSeason();

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