<?php

class Jp7_WordPress_Blog extends Jp7_WordPress_RecordAbstract {
	public function getFirstPost($options = array()) {
		return reset($this->getPosts(array('limit' => 1) + $options));
	}
	
	public function getPosts($options = array()) {
		if (!$this->blog_id) {
			throw new Exception('Field "blog_id" is empty.');
		}
		
		$options += array(
			'from' => Jp7_WordPress::getPrefix() . (($this->blog_id > 1) ? $this->blog_id . '_' : '') . 'posts'
		);
		return self::retrieveObjects($this->_db, $options, 'Jp7_WordPress_Post');
	}	
}