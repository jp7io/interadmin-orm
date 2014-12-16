<?php

namespace Jp7\Interadmin;

trait Downloadable {
	// For client side use
	public function getUrl() {
		return str_replace('../../upload', 'assets', $this->url);
	}
	
	// Absolute client side URL
	public function getAbsoluteUrl() {
		$config = \InterSite::config();
		return $config->url. $this->getUrl();
	}	
	
	// Server side file name
	public function getFilename() {
		$path = parse_url($this->url)['path']; // remove query string
		return str_replace('../../', storage_path() . '/', $path);
	}
	
	/**
	 * Returns the extension of this file.
	 * 
	 * @return string Extension, such as 'jpg' or 'gif'.
	 */
	public function getExtension() {
		return pathinfo($this->getFilename(), PATHINFO_EXTENSION);
	}

	public function getSize() {
		return jp7_file_size($this->getFilename());
	}
}