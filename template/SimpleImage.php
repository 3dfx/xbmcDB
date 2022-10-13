<?php
/*
* File: SimpleImage.php
* Author: Simon Jarvis
* Copyright: 2006 Simon Jarvis
* Date: 08/11/06
* Link: http://www.white-hat-web-design.co.uk/articles/php-image-resizing.php
*
* This program is free software; you can redistribute it and/or
* modify it under the terms of the GNU General Public License
* as published by the Free Software Foundation; either version 2
* of the License, or (at your option) any later version.
*
* This program is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
* GNU General Public License for more details:
* http://www.gnu.org/licenses/gpl.html
*
*/

/** @noinspection PhpParamsInspection */
class SimpleImage {
	private $image	  = null;
	private $image_type = null;
	private bool $emptyImage = false;

	function load($filename, $DST = null) {
//		if (substr_count($filename, 'actors') > 0) {
//			return null;
//		}

		if (empty($filename)) { return null; }
		$image_info = null;
		try { $image_info = getimagesize($filename); }
		catch (Exception $e) { return false; }
		catch (Throwable $e) { return false; }

		if (empty($image_info)) {
			$img = imagecreatetruecolor(2, 1);
			imagejpeg($img, $DST, 1);
			$this->image = $img;
			$this->emptyImage = true;

			return false;
		}
		
		$this->image_type = $image_info[2];
		if ($this->image_type == IMAGETYPE_JPEG) {
			$this->image = imagecreatefromjpeg($filename);
		} elseif ($this->image_type == IMAGETYPE_GIF) {
			$this->image = imagecreatefromgif($filename);
		} elseif ($this->image_type == IMAGETYPE_PNG) {
			$this->image = imagecreatefrompng($filename);
		}

		return true;
	}
	
	function save($filename, $image_type = IMAGETYPE_JPEG, $compression = 75, $permissions = null) {
		if (empty($this->image)) { return; }
		if ($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image,$filename,$compression);
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif($this->image,$filename);
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng($this->image,$filename);
		}
		if ($permissions != null) {
			chmod($filename,$permissions);
		}
	}
	
	function output($image_type = IMAGETYPE_JPEG) {
		if (empty($this->image)) { return; }
		if ($image_type == IMAGETYPE_JPEG) {
			imagejpeg($this->image);
		} elseif ($image_type == IMAGETYPE_GIF) {
			imagegif($this->image);
		} elseif ($image_type == IMAGETYPE_PNG) {
			imagepng($this->image);
		}
	}
	
	function isEmpty() {
		return $this->emptyImage || empty($this->image);
	}
	
	function getWidth() {
		return imagesx($this->image);
	}
	
	function getHeight() {
		return imagesy($this->image);
	}

	function isEmptyGenerated() {
		return $this->getWidth() == 2 && $this->getHeight() == 1;
	}
	
	function resizeToHeight($height) {
		$ratio = $height / $this->getHeight();
		$width = $this->getWidth() * $ratio;
		$this->resize($width,$height);
	}
	
	function resizeToWidth($width) {
		$ratio = $width / $this->getWidth();
		$height = $this->getheight() * $ratio;
		$this->resize($width,$height);
	}
	
	function scale($scale) {
		$width = $this->getWidth() * $scale/100;
		$height = $this->getheight() * $scale/100;
		$this->resize($width,$height);
	}
	
	function resize($width, $height) {
		$new_image = imagecreatetruecolor((int) $width, (int) $height);
		imagecopyresampled($new_image, $this->image, 0, 0, 0, 0, (int) $width, (int) $height, (int) $this->getWidth(), (int) $this->getHeight());
		$this->image = $new_image;
	}
}
?>
