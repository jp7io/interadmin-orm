<?php

class Jp7_WordPress_User extends Jp7_WordPress_RecordAbstract {
	const PK = 'ID';
	const LEVEL_ADMINISTRATOR = 10;
	const LEVEL_CONTRIBUTOR = 1;
	const LEVEL_SUBSCRIBER = 0;
	
	public function getMetaByKey($key, $options = array()) {
		$options['where'][] = "meta_key = '" . $key . "'";
		return $this->getFirstMeta($options);
	}
	
	public function getFirstMeta($options = array()) {
		return reset($this->getMetas(array('limit' => 1) + $options));
	}
	
	public function getMetas($options = array()) {
		if (!$this->ID) {
			throw new Exception('Field "ID" is empty.');
		}
		
		$options += array(
			'from' => Jp7_WordPress::getPrefix() . 'usermeta',
			'fields' => '*'
		);
		$options['where'][] = 'user_id = ' . $this->ID;
		
		return self::retrieveObjects($this->_db, $options, get_class($this) . 'Meta');
	}
	
	/**
	 * Creates a UserMeta.
	 * @param string $key
	 * @param string $value
	 * @return Jp7_WordPress_UserMeta
	 */
	public function createMeta($key, $value) {
		$className = get_class($this) . 'Meta';
		
		$meta = new $className($this->_db, Jp7_WordPress::getPrefix() . 'usermeta');
		$meta->setAttributes(array(
			'user_id' => $this->ID,
			'meta_key' => $key,
			'meta_value' => $value
		));
		return $meta;
	}
	
	public function addMetas($metas = array()) {
		if (!$this->ID) {
			throw new Exception('Field "ID" is empty.');
		}
		
		$metas = $metas + array(
			'jabber' => '',
			'yim' => '',
			'aim' => '',
			'show_admin_bar_admin' => 'true',
			'show_admin_bar_front' => 'true',
			'use_ssl' => '0',
			'admin_color' => 'fresh',
			'comment_shortcuts' => 'false',
			'rich_editing' => 'true',
			'description' => '',
			'nickname' => '',
			'last_name' => '',
			'first_name' => ''
		);
		
		foreach ($metas as $key => $value) {
			$obj = $this->createMeta($key, $value);
			$obj->save();
		}
	}
	
	public function getCapabilities(Jp7_WordPress_Blog $blog) {
		$key = $blog->getPrefix() . 'capabilities';
		return $this->getMetaByKey($key);
	}
	
	public function getUserLevel(Jp7_WordPress_Blog $blog) {
		$key = $blog->getPrefix() . 'user_level';
		return $this->getMetaByKey($key);
	}
	
	public function addTo(Jp7_WordPress_Blog $blog, $user_level) {
		$capArray = array(
			self::LEVEL_ADMINISTRATOR => 'administrator',
			self::LEVEL_CONTRIBUTOR => 'contributor',
			self::LEVEL_SUBSCRIBER => 'subscriber'
		);
		
		$cap = $capArray[$user_level];
		
		$capabilities = $this->createMeta($blog->getPrefix() . 'capabilities',  array($cap => 1));
		$capabilities->save();
		
		$userLevel = $this->createMeta($blog->getPrefix() . 'user_level', $user_level);
		$userLevel->save();
	}	
	
}