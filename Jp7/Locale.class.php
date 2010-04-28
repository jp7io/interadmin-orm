<?php

/**
 * TODO
 * 
 * @category Jp7
 * @package Jp7_Locale
 */
class Jp7_Locale
{
	public $lang = '';
	public $prefix = '';
	public $path = '';

	public function __construct($language)
	{
		$config = Zend_Registry::get('config');
		
		$this->lang = $language;
		
		if ($language != $config->lang_default) {
			$this->path = $language . '/';
			$this->prefix = '_' . $language;
		}
	}
}