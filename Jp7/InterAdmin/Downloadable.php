<?php

namespace Jp7\Interadmin;

trait Downloadable {
	// For client side use
	public function getUrl() {
		return str_replace('../../upload', 'assets', $this->url);
	}

	// Absolute client side URL
	public function getAbsoluteUrl() {
		kd('Not implemented');
		$config = InterSite::config();
		global $jp7_app;
		
		if ($jp7_app && $jp7_app != 'interadmin') {
			return jp7_replace_beginning('../../upload/', 'http://' . $config->server->host . '/' . $config->name_id . '/' . $jp7_app . '/upload/', $this->url);
		} else {
			return jp7_replace_beginning('../../upload/', $config->url . 'upload/', $this->url);
		}
	}	
	
	// Server side file name
	public function getFilename() {
		return str_replace('../../', storage_path() . '/', $this->url);
	}

	/**
	 * Returns the extension of this file.
	 * 
	 * @return string Extension, such as 'jpg' or 'gif'.
	 */
	public function getExtension() {
		kd('Not implemented');
		$url = reset(explode('?', $this->url));
		return preg_replace('/(.*)\.(.*)$/', '\2', $url);
	}

	public function getSize() {
		return jp7_file_size($this->getFilename());
	}
}