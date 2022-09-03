<?php
include_once "./template/functions.php";

class StreamDetails {
	private $duration  = 0;
	private $ar         = null;
	private $arOR       = null;
	private $width      = null;
	private $height     = null;
	private $vCodec     = null;
	private $aCodec     = array();
	private $aChannels  = array();
	private $aLang      = array();
	private $subtitle   = array();
	private $hdrType    = null;
	private $fetchedFPS = null;
	private $fps        = null;
	private $bits       = null;
	private $fsize      = 0;

	public function __construct($idFile, $idEpisode, $path, $filename, $filesize) {
		$dbh = getPDO();

		$stream = getStreamDetails($idFile, $dbh);
		foreach($stream as $stRow) {
			$tmp = $stRow['fVideoAspect'];
			if (!empty($tmp)) {
				if ($tmp != '') {
					$tmp  = round($tmp, 2);
					if ($tmp-1 != 1 && $tmp-1 != 0) {
						$tmp .= (strlen($tmp) < 4 ? '0' : '');
					}
				}

				if (is_numeric($tmp)) {
					$this->ar = sprintf("%01.2f", round($tmp, 2));
				}
			}

			$tmp = $stRow['iVideoWidth'];
			if (!empty($tmp)) { $this->width = $tmp; }

			$tmp = $stRow['iVideoHeight'];
			if (!empty($tmp)) { $this->height = $tmp; }

			$tmp = $stRow['iVideoDuration'];
			if (!empty($tmp)) { $this->duration = $tmp; }

			$tmp = $stRow['strVideoCodec'];
			if (!empty($tmp)) { $this->vCodec = $tmp; }

			$tmp = $stRow['strAudioCodec'];
			if (!empty($tmp)) { $this->aCodec[] = strtoupper($tmp); }

			$tmp = $stRow['iAudioChannels'];
			if (!empty($tmp)) { $this->aChannels[] = $tmp; }

			$tmp = $stRow['strAudioLanguage'];
			if (!empty($tmp)) { $this->aLang[] = $tmp; }

			$tmp = $stRow['strSubtitleLanguage'];
			if (!empty($tmp)) { $this->subtitle[] = $tmp; }

			$tmp = $stRow['strHdrType'];
			if (!empty($tmp)) { $this->hdrType = $tmp; }
		}

		if (!empty($this->subtitle)) {
			$this->subtitle = array_unique($this->subtitle);
		}

		$this->fsize = _format_bytes(fetchFileSize($idFile, $path, $filename, $filesize, $dbh));
		$this->fetchedFPS = fetchFps($idFile, $path, $filename, array($this->bits, $this->fps), $dbh);
		if (!empty($this->fetchedFPS)) {
			$this->bits = $this->fetchedFPS[0];
			$this->fps  = $this->fetchedFPS[1];
		}

		$this->arOR = getOverrideAR($idFile, $idEpisode, $dbh);
		if (!empty($this->arOR)) {
			$this->arOR = sprintf("%01.2f", $this->arOR);
			$this->ar = null;
		}
	}

	public function getDuration(): int {
		return $this->duration;
	}

	public function getAr() {
		return $this->ar;
	}

	public function getArOR() {
		return $this->arOR;
	}

	public function getWidth() {
		return $this->width;
	}

	public function getHeight() {
		return $this->height;
	}

	public function getVCodec() {
		return $this->vCodec;
	}

	public function getACodec(): array {
		return $this->aCodec;
	}

	public function getAChannels(): array {
		return $this->aChannels;
	}

	public function getALang(): array {
		return $this->aLang;
	}

	public function getSubtitle(): array {
		return $this->subtitle;
	}

	public function getSubtitleCount() {
		return count($this->subtitle);
	}

	public function getFetchedFPS() {
		return $this->fetchedFPS;
	}

	public function getFps() {
		return $this->fps;
	}

	public function getHdrType() {
		return $this->hdrType;
	}

	public function getBits() {
		return $this->bits;
	}

	public function getFsize() {
		return $this->fsize;
	}
}
?>
