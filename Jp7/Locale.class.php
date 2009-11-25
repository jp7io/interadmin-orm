<?php

/**
 * TODO
 * 
 * @category Jp7
 * @package Jp7_Locale
 */
class Jp7_Locale
{
	public $lang = '', $prefix = '', $path = '';

	public function __construct($language)
	{
		$c_default_lang = 'pt-br';

		if (!in_array($language, array('de', 'en', 'es', 'fr', 'jp', 'pt', 'pt-br'))) {
			$language = $c_default_lang;
		}

		$this->lang = $language;
		$this->path = '/' . $language . '/';

		if ($language != 'pt-br') {
			$this->prefix = '_' . $language;
		}
	}
}