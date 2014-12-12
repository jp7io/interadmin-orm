<?php

class Jp7_Image
{
	public $file = '';
	public $src = '';
	public $dst = '';
	public $image = '';
	public $compression = null;
	public $imageCompressionQuality = null;
	
	public function __construct($file)
	{
		$this->file = $file;
		$this->image = file_get_contents($this->file);
		$this->src = $this->file . '_src.tmp';
		$this->dst = $this->file . '_dst.png';
	}
	
	public function __toString()
	{
		return $this->image;	
	}
	
	public function __call($name, $arguments = '')
	{
		$method = '';
		if (strpos($name ,'set') === 0) {
			$method = 'set';
		}
		if (strpos($name ,'get') === 0) {
			$method = 'get';
		}
		if ($method) {
			$property = strtolower(substr($name, 3, 1)) . substr($name, 4);
			if ($method == 'set') {
				$this->$property = $arguments[0];
			} else {
				return $this->$property;
			}
		}
	}
	
	public function writeImage($dst)
	{
		if ($this->compression == 'JPEG') {
			$command = "convert " . $this->src . " -compress JPEG -quality " . $this->imageCompressionQuality . " " . $dst . '.jpg';
			$this->command($command);
			copy($dst . '.jpg', $dst);
			unlink($dst . '.jpg');
		} else {
			$fp = fopen($dst, 'w+');
			fwrite($fp, $this->image);
			fseek($fp, 0);
			fclose($fp);
		}
	}
	
	private function createTempFiles()
	{
		$fp = fopen($this->src, 'w+');
		fwrite($fp, $this->image);
		fseek($fp, 0);
		fclose($fp);
	}
	
	private function destroyTempFiles()
	{
		unlink($this->src);
		if (file_exists($this->dst)) {
			unlink($this->dst);
		}
	}
	
	private function command($command)
	{
		$this->createTempFiles();
		exec($command, $a, $b);
		$this->destroyTempFiles();
		return $a;
	}
	
	private function convert($command)
	{
		$this->createTempFiles();
		// Parser/Replace no command para compatibilidade Linux/Windows
		if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') {
			$command = str_replace('(', '\(', $command);
			$command = str_replace(')', '\)', $command);
		}
		passthru($command, $return);
		$image = file_get_contents($this->dst);
		$this->destroyTempFiles();
		return $image;
	}
	
	public function getImageProperties($pattern  = '')
	{
		$command = $command_path . 'identify -verbose ' . $this->src;
		$properties = $this->command($command);
		$return = array();
		foreach ($properties as $property) {
			$property = explode(': ', $property);
			$property [0] = trim($property [0]);
			$property [1] = trim($property [1]);
			if (!$pattern || $pattern == $property [0]) {
				$return[$property [0]] = $property [1];
			}
		}
		return $return;
	}
	
	public function getImageProperty($property)
	{
		return reset($this->getImageProperties($property));
	}
	
	public function getImageDimensions()
	{
		$geometry = $this->getImageProperty('Geometry');
		$geometry = explode('+', $geometry);
		$geometry = explode('x', $geometry[0]);
		return $geometry;	
	}
	
	public function getImageWidth()
	{
		$dimensions = $this->getImageDimensions();
		return $dimensions[0];
	}
	
	public function getImageHeight()
	{
		$dimensions = $this->getImageDimensions();
		return $dimensions[1];
	}
	
	public function roundCorners($radius = 10)
	{
		$command = "convert " . $this->src  .
			" ( +clone  -threshold -1" .
			" -draw \"fill black polygon 0,0 0,${radius} ${radius},0 fill white circle ${radius},${radius} ${radius},0\"" .
			" ( +clone -flip ) -compose Multiply -composite" .
			" ( +clone -flop ) -compose Multiply -composite" .
	     	" ) +matte -compose CopyOpacity -composite " . $this->dst;
		$this->image = $this->convert($command);
	}
		 
	public function resizeImage($w, $h)
	{
		// Param Parser
		if (is_array($q)) {
			$options = $q;
			if ($options['crop']) {
				$crop = $options['crop'];
			}
			if ($options['bgcolor']) {
				$bgcolor = $options['bgcolor'];
				if (is_string($bgcolor)) {
					$bgcolor = explode(',', $bgcolor);
				}
			}
			if ($options['enlarge']) {
				$enlarge = $options['enlarge'];
			}
		}
		// Check GD
		$enlarge = true;
		
		$c_gd = function_exists('imagecreatefromjpeg');
		//$command_path = '/usr/bin/';
		// Check Size and Orientation (Horizontal x Vertical)
		if ($c_gd && $forcegd) {
			// GD Get Size
			$src_w = imagesx($im_src);
			$src_h = imagesy($im_src);
		} else {
			// Magick Get Size
			$src_w = $this->getImageWidth();
			$src_h = $this->getImageHeight();
		}
		
		
		//echo $src_w;
		//echo $src_h;
		$crop = true;
		//die();
		
		// Source and destination with the same dimensions or the same proportions (just resize if needed)
		if (($src_w == $w && $src_h == $h) || ($src_w / $src_h == $w / $h)) {
			$dst_w = $w;
			$dst_h = $h;
		// Destination is square (with same width and height - crop if needed)
		} elseif ($w == $h) {
			$dst_w = $w;
			$dst_h = $h;
			if ($src_w > $src_h) $src_w = $src_h;
			else $src_h = $src_w;
		// The image is resized until it gets the maximum width or height (with crop)
		} elseif ($crop && $crop !== 'border') {
			$pre_dst_w = intval(round(($h * $src_w) / $src_h));
			$pre_dst_h = intval(round(($w * $src_h) / $src_w));
			if ($pre_dst_h > $h) {
				$dst_w = $w;
				$dst_h = $pre_dst_h;
				$dif_h = round(($h - $pre_dst_h) / 2);
			} else {
				$dst_h = $h;
				$dst_w = $pre_dst_w;
				$dif_w = round(($w - $pre_dst_w) / 2);
			}
			$new_w = $w;
			$new_h = $h;
			
			//echo $new_w . '</ br>';
			//echo $new_h . '</ br>';
			
		// The image is resized until it gets the maximum width or height (without crop)
		} else {
			$pre_dst_w = intval(round(($h * $src_w) / $src_h));
			$pre_dst_h = intval(round(($w * $src_h) / $src_w));
			if ($pre_dst_h <= $h){
				$dst_w = $w;
				$dst_h = $pre_dst_h;
			} else {
				$dst_h = $h;
				$dst_w = $pre_dst_w;
			}
			if ($crop === 'border') {
				$new_w = $w;
				$new_h = $h;
				$dif_w = ($new_w - $dst_w) / 2;
				$dif_h = ($new_h - $dst_h) / 2;
			}
		}
		// 
		if (!$new_w) {
			$new_w = $dst_w;
		}
		if (!$new_h) {
			$new_h = $dst_h;
		}
		// Checks if destination image is bigger than source image
		if ($dst_w >= $src_w && $dst_h >= $src_h && !$enlarge) {
			// No-Resize and Check Weight
			if (filesize($src) > $s) {
				$im_dst = $im_src;
				if ($c_gd) {
					// GD Convert Quality
					imagejpeg($im_dst, $dst, $q);
				} else {
					// Magick Convert Quality
					$command = $command_path . "convert " . $src . " -quality " . $q . " +profile '*' " . $dst;
					exec($command, $a, $b);
				}
			} else {
				if (jp7_extension($src)=="gif") {
					$dst = str_replace(".jpg", ".gif", $dst);
				}
				copy($src,$dst);
			}
		} else {
			if ($c_gd && $force_gd) {
				// GD Resize
				$im_dst = imagecreatetruecolor($new_w, $new_h);
				if ($crop === 'border') {
					if ($bgcolor) {
						$bg = imagecolorallocate($im_src, $bgcolor[0], $bgcolor[1], $bgcolor[2]);
					} else {
						$bg = imagecolorat($im_src, 1, 1);
					}
					imagefill($im_dst, 0, 0, $bg);
				}
				imagecopyresampled($im_dst, $im_src, $dif_w, $dif_h, 0, 0, $dst_w, $dst_h, $src_w, $src_h);
				if ($options['borderRadius']) {
					$im_dst = jp7_imageRoundedCorner($im_dst, $options['borderRadius'], $options['borderColor']);
					imagepng($im_dst, $dst, 9);
				} else {
					imagejpeg($im_dst, $dst, $q);
				}
				
				imagedestroy($im_dst);
			} else {
				// Magick Resize
				
				//echo $dst_w . '<br />';
				//echo $dst_h;
				//die();
				
				$command = $command_path . "convert " . $this->src . " -resize " . $new_w . " " . $this->dst;
				$this->image = $this->convert($command);
				
				if ($crop) {
					$command = $command_path . "convert " . $this->src . " -gravity Center -crop 180x120+0+0 " . $this->dst;
					$this->image = $this->convert($command);
				}
				

				
			}
		}
	 
	 }
	 
	 
}
