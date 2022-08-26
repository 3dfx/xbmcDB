<?php
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
	private $source;
	private $size;
	private $atmosx;

	function __construct($episode, $delta, $season, $idShow, $idTvdb, $idEpisode, $idSeason, $idFile, $idPath, $path, $epName, $epDesc, $rating, $serienname, $airDate, $duration, $playcount, $filename, $filesize, $source, $atmosx) {
		$serienname = switchPronoms($serienname);

		$this->episode     = $episode;
		$this->delta       = $delta;
		$this->season      = $season;
		$this->idShow      = $idShow;
		$this->idSeason    = $idSeason;
		$this->idTvdb      = $idTvdb;
		$this->rating      = doubleval($rating);
		$this->idEpisode   = $idEpisode;
		$this->idFile      = $idFile;
		$this->idPath      = $idPath;
		$this->path        = $path;
		$this->name        = $epName;
		$this->desc        = $epDesc;
		$this->serienname  = $serienname;
		$this->duration    = $duration;
		$this->playcount   = $playcount;
		$this->filename    = $filename;
		$this->filesize    = $filesize;
		$this->source      = $source;
		$this->airDate     = $airDate;
		$this->atmosx 	   = $atmosx;
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

	public function getIdSeason() {
		return $this->idSeason;
	}

	public function getRatingVal() {
		return doubleval($this->rating);
	}

	public function getRating() {
		return sprintf("%02.1f", round($this->rating, 1));
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

	public function getSource() {
		return $this->source;
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

	public function getAtmosx() {
		return $this->atmosx;
	}
}
?>
