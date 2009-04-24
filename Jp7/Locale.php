<?php

class Jp7_Locale
{
	public $lang = '', $prefix = '', $path = '', $path_url = '', $path_2 = '';

	public function __construct($language)
	{
		$c_default_lang = 'pt-br';

		if (!in_array($language, array('de', 'en', 'es', 'fr', 'jp', 'pt', 'pt-br'))) {
			$language = $c_default_lang;
		}

		$this->lang = $language;
		if ($language != 'pt-br') {
			$this->prefix = '_' . $language;
			$this->path = $language . '/';
		}

		$this->path_url = $language . '/';
		$this->path_2 = $this->path_url;
	}
}