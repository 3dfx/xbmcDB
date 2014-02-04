<?php
	include_once "functions.php";
	function fetchMVids($sessionKey, $sortBy = null) {
		$overrideFetch = isset($_SESSION['overrideFetch']) ? 1 : 0;
		$overrideFetch = 1;
		
		#$res = array();
		$MVids = new MusicVideos();
		if (!empty($sortBy)) { $sessionKey .= '_ORDER_'.$sortBy; }
		if (isset($_SESSION[$sessionKey]) && $overrideFetch == 0) {
			$MVids = unserialize($_SESSION[$sessionKey]);
			
		} else {
			$SQL = "SELECT * FROM musicvideo";
			if (!empty($sortBy)) {
				$SQL .= ' ORDER BY ';
				switch ($sortBy) {
					case 1:
						$SQL .= 'c10';
					break;

					case 2:
						$SQL .= 'c00';
					break;
					
					case 3:
						$SQL .= 'c09';
					break;
					
					default:
						$SQL .= 'idMVideo';
				}
			}
			$SQL .= ';';
			
			$dbh = getPDO();
			$result = fetchFromDB_($dbh, $SQL);
			
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
			
			$_SESSION[$sessionKey] = serialize($MVids);
			unset( $_SESSION['overrideFetch'] );
		}
		
		return $MVids;
	}
	
	class MusicVideos {
		private $MVids = array();
		private $count = 0;
		
		public function addMVid(&$MVid) {
			$this->MVids[$this->count++] = $MVid;
		}
		
		public function getMVids() {
			$this->sort();
			return $this->MVids;
		}

		public function sort() {
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