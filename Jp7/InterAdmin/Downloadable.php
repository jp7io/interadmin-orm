<?php

namespace Jp7\Interadmin;

trait Downloadable {
	// For client side use
	public function getUrl() {
		$absolute = \Config::get('app.url') . 'upload/';
		
		$localPrefix = '../../upload/';
		// Fix absolute URL
		$url = str_replace($absolute, $localPrefix, $this->url);
		// SEO
		$slug = to_slug($this->getText());
		if ($slug && strpos($url, $localPrefix) === 0) {
			$url = dirname($url) . '/' . $slug . '-' . basename($url);
		}
		// Replace relative URL by assets
		return str_replace($localPrefix, '/assets/', $url);
	}
	
	// Absolute client side URL
	public function getAbsoluteUrl() {
		return \URL::to($this->getUrl());
	}
	
	// Server side file name
	public function getFilename() {
		$parsed = parse_url($this->url);
		if (!empty($parsed['host'])) {
			return false;
		}
		// remove query string
		$path = $parsed['path']; 
		return str_replace('../../upload/', storage_path('upload/'), $path);
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
		return human_filesize($this->getFilename());
	}
}