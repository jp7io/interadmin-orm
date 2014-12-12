<?php

class Jp7_Cache_Recordset extends Jp7_Cache_Data {
	
	protected static $_cachedir = './cache/_mysql/';
	
	public function __construct($id, $options = array()) {
		$options = $options + array(
			'lifetime' => '1327770882',
			'cache_dir' => self::$_cachedir
		);
		if (!is_dir(self::$_cachedir)) {
			mkdir(self::$_cachedir);
			@chmod(self::$_cachedir, 0777);	
		}
		parent::__construct($id, $options);
	}
	
	public function load() {
		$rows = parent::load();
		if ($rows) {
			return new Jp7_Cache_Recordset_Fake($rows);
		}
	}
	
	public function save($rs) {
		$rows = array();
		while ($row = $rs->FetchNextObj()) {
			$rows[] = $row;
		}
		parent::save($rows);
		return new Jp7_Cache_Recordset_Fake($rows);
	}
	
}