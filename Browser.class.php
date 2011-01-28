<?php
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category JP7
 * @package Browser
 */

/**
 * Checks browser, browser version, and whether it's a robot or not.
 *
 * @version (2005/11/18)
 * @package Browser
 */
class Browser{
	/**
	 * User Agent string.
	 * @var string
	 */
	public $userAgent;
	/**
	 * Browser: 'sa' for Safari/Chrome, 'op' for Opera, 'ie' for Internet Explorer, 'ns' for Netscape, 'mo' for Mozilla/Firefox, or 'robot' for bots.
	 * @var string
	 */
	public $browser;
	/**
	 * Version of the browser, -1 if its not detected.
	 * @var int
	 */
	public $v;
	/**
	 * Operating System: 'win' for Windows, 'mac' for Mac OS, 'unx' for Unix, 'lnx' for Linux and 'sol' for Solaris.
	 * @var string
	 */
	public $os;
	/**
	 * Name of the bot, such as wget, getright, yahoo, slurp, google or '' if its not a bot.
	 * @var string
	 */
	public $robot;
	/**
	 * Public Constructor. Checks browser, browser version, and whether it's a robot or not.
	 *
	 * @param string $useragent Browser information from $_SERVER['HTTP_USER_AGENT'].
	 * @return Browser
	 */	
	public function __construct($useragent){
		$this->userAgent = $useragent;
		$i = 0;
		if (strpos($useragent, 'Chrome') !== false) {
			$this->browser = 'ch';
			$this->v = 5;
		} elseif (strpos($useragent, 'Safari')) {
			$this->browser = 'sa';
			$this->v = 5;
		} elseif (strpos($useragent, 'Opera') !== false) {
			$this->browser = 'op';
			$i = strpos($useragent, 'Opera') + 6;
		} elseif (strpos($useragent, 'MSIE')) {
			$this->browser = 'ie';
			$i = strpos($useragent, 'MSIE') + 4;
		} elseif (strpos($useragent, 'Mozilla/') !== false && strpos($useragent, 'compatible') === false) {
			$this->browser = 'ns';
			$i = strpos($useragent, 'Mozilla/') + 8;
		} elseif(strpos($useragent, 'Mozilla/5.0') !== false) {
			$this->browser = 'mo';
			$this->v = 5;
		} else {
			$this->browser = $useragent;
			$this->v = -1;
		}
		$this->ch = ($this->browser == 'ch');
		$this->sa = ($this->browser == 'sa');
		$this->op = ($this->browser == 'op');
		$this->ie = ($this->browser == 'ie');
		$this->ns = ($this->browser == 'ns');
		$this->mo = ($this->browser == 'mo');
		$version = '';
		while (!$this->v) {
			$c = substr($useragent, $i++, 1);
			if (is_numeric($c) || $c == '.'|| $c == ' ') {
				$version .= "$c";
			} else {
				$this->v = ($version) ? doubleval($version) : -1;
			}
		}
		$this->ns4 = ($this->ns && $version < 5);
		
		if (strpos($useragent, 'Win')) {
			$this->os = 'win';
		} elseif (strpos($useragent, 'Mac')) {
			$this->os = 'mac';
		} elseif (strpos($useragent, 'Unix')) {
			$this->os = 'unx';
		} elseif (strpos($useragent, 'Linux')) {
			$this->os = 'lnx';
		} elseif (strpos($useragent, 'SunOS')) {
			$this->os = 'sol';
		} else {
			$this->os = null;
		}
		
		$this->win = ($this->os == 'win');
		$this->mac = ($this->os == 'mac');
		$this->unx = ($this->os == 'unx');
		$this->lnx = ($this->os == 'lnx');
		$this->sol = ($this->os == 'sol');
		// Robots	
		if ($this->browser == $useragent) {
			$robots = array(
				'wget',
				'getright',
				'yahoo',
				'altavista',
				'lycos',
				'infoseek',
				'lwp',
				'webcrawler',
				'linkexchange',
				'slurp',
				'google'
			);
			for ($i = 0; $i < count($robots); $i++) {
				if (strpos(strtolower($useragent), $robots[$i]) !== false) {
					$this->robot = $robots[$i];
					$this->browser = 'robot';
					break;
				}
			}
		}
	}
}
