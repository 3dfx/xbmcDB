<?php
	include_once "functions.php";
	function fetchMVids($sessionKey, $sortBy = null) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
		#$overrideFetch = 1;
		
		$MVids = new MusicVideos();
		if (!empty($sortBy)) { $sessionKey .= '_ORDER_'.$sortBy; }
		if (isset($_SESSION['MVid'][$sessionKey]) && $overrideFetch == 0) {
			$MVids = unserialize($_SESSION['MVid'][$sessionKey]);
			
		} else {
			$SQL = "SELECT * FROM musicvideo";
			$SQL .= ' ORDER BY ';
			switch ($sortBy) {
				case 1:
				default:
					$MVids->turnSort(true);
					$SQL .= 'c10';
					break;
				case 2:
					$MVids->turnSort(false);
					$SQL .= 'c00';
					break;
				case 3:
					$MVids->turnSort(false);
					$SQL .= 'c09';
					break;
				case 4:
					$MVids->turnSort(false);
					$SQL .= 'idMVideo';
					break;
			}
			$SQL .= ' ASC;';
			
			$dbh = getPDO();
			$result = querySQL($SQL, false, $dbh);
			
			$count = 0;
			foreach($result as $row) {
				$item = array();
				$item['idFile']   = $row['idFile'];
				$item['idMVideo'] = $row['idMVideo'];
				$item['title']    = $row['c00'];
				$item['covers']   = $row['c01'];
				$item['album']    = $row['c09'];
				$item['artist']   = $row['c10'];
				$item['filename'] = $row['c13'];
				$item['idPath']   = $row['c14'];
				$MVids->addMVid(new MVid($item));
			}
			
			$_SESSION['MVid'][$sessionKey] = serialize($MVids);
			unset( $_SESSION['overrideFetch'] );
		}
		
		return $MVids;
	}
	
	class MusicVideos {
		private $MVids = array();
		private $count = 0;
		private $doSort = false;
		
		public function addMVid($MVid) {
			if (empty($MVid->getArtist()) && empty($MVid->getTitle()))
				return;
			$this->MVids[$this->count++] = $MVid;
		}
		
		public function getMVids() {
			$this->sort();
			return $this->MVids;
		}
		
		public function turnSort($val) {
			$this->doSort = $val;
		}

		public function sort() {
			if (!$this->doSort) { return; }
			usort($this->MVids, function($a, $b) {
				$strA = $a->getArtist().' '.$a->getTitle();
				$strB = $b->getArtist().' '.$b->getTitle();
				return strcasecmp($strA, $strB);
			});
		}
	}
	
	class MVid {
		private $idMVideo;
		private $idFile;
		private $idPath;
		private $filename;
		private $artist;
		private $title;
		private $feat;
		private $album;
		private $covers;
		
		public function MVid($ary) {
			$this->idMVideo = $ary['idMVideo'];
			$this->idFile   = $ary['idFile'];
			$this->idPath   = $ary['idPath'];
			$this->title    = ucwords(trim($ary['title']));
			$this->album    = ucwords(trim($ary['album']));
			$this->filename = $ary['filename'];
			$this->covers   = $ary['covers'];
			
			$artist = $ary['artist'];
			$artist = strtolower($artist);
			$artist = str_replace('(', '', $artist);
			$artist = str_replace(')', '', $artist);
			$artist = str_replace(' featuring ', '|', $artist);
			$artist = str_replace(' ft ', '|', $artist);
			$artist = str_replace(' ft. ', '|', $artist);
			$artist = str_replace(' feat ', '|', $artist);
			$artist = str_replace(' feat. ', '|', $artist);
			$artist = str_replace(' and ', '|', $artist);
			$artist = str_replace(' & ', '|', $artist);
			$artist = str_replace(' f ', '|', $artist);
			$artist = str_replace(' f. ', '|', $artist);
			$artist = str_replace(' vs ', '|', $artist);
			$artist = str_replace(' vs. ', '|', $artist);
			$artist = str_replace(' , ', '|', $artist);
			$artist = str_replace(', ', '|', $artist);
			$tmp = explode('|', $artist);
			
			$this->artist = ucwords(trim(substr($ary['artist'], 0, strlen($tmp[0]))));
			
			if (count($tmp) > 1) {
				unset($tmp[0]);
				$tmp = array_reverse($tmp);
				$this->feat = ucwords(trim(implode(', ', $tmp)));
			}
		}
		
		public function getIdMVideo() {
			return $this->idMVideo;
		}
		
		public function getIdFile() {
			return $this->idFile;
		}
		
		public function getIdPath() {
			return $this->idPath;
		}
		
		public function getFilename() {
			return $this->filename;
		}
		
		public function getArtist() {
			return $this->artist;
		}
		
		public function getFeat() {
			if (empty($this->feat)) { return null; }
			#return ' (feat. '.($this->feat).')';
			return $this->feat;
		}
		
		public function getTitle() {
			return $this->title;
		}
		
		public function getAlbum() {
			return $this->album;
		}
		
		public function getCovers() {
			return $this->covers;
		}
	}
?>