<?php 
/**
 * JP7's PHP Functions 
 * 
 * Contains the main custom functions and classes.
 * @author JP7
 * @copyright Copyright 2002-2008 JP7 (http://jp7.com.br)
 * @category Jp7
 * @package Jp7_Date
 */

/**
 * Helper for date utils.
 * 
 * @package Jp7_Date
 */
class Jp7_Date extends DateTime {
	/**
	 * @var string
	 */
	private $_serializedValue;
	
	/**
	 * Retorna string da diferença de tempo, ex: '3 dias atrás'.
	 * O valor é arredondado: 2 anos e 4 meses retorna '2 anos atrás'.
	 * Diferenças menores de 1 minuto retornam 'agora'.
	 * 
	 * Static: 		humanDiff($timeStamp = false)
	 * Instance: 	humanDiff()
	 * 
	 * @param int|string $timeStamp [only with static calls] Timestamp ou Datetime. 
	 * @return string
	 */
	public function humanDiff($timeStamp = false) {
		if (isset($this) && $this instanceof self) {
			$timeStamp = $this;
		}
		$timeStamp = self::_toTime($timeStamp);
		$currentTime = time();
		$units = array(
			'ano' => 31556926,
			'mês' => 2629743,
			'semana' => 604800,
			'dia' => 86400,
			'hora' => 3600,
			'minuto' => 60
		);
		$seconds = $currentTime - $timeStamp;
		if ($seconds <= 60) {
			return 'agora';
		} elseif ($seconds >= $units['dia'] && $seconds < $units['dia'] * 2) {
			return 'ontem';
		}
		foreach ($units as $unit => $seconds_in_period) {
			if ($seconds >= $seconds_in_period) {
				$count = floor($seconds / $seconds_in_period);
				return Jp7_Inflector::plural($unit, $count) . ' atrás';
			}
		}
	}
	
	/**
	 * Returns the age based on the birthdate and the current date.
	 * 
	 * Static: 		yearsDiff($from, $to = null)
	 * Instance: 	yearsDiff($to = false)
	 * 
	 * @param string|int $from [only with static calls] Datetime (string) or Timestamp (int).
	 * @param string|int $to [optional]
	 * @return int Age in years.
	 */
	public function yearsDiff($to = false) {
		if (isset($this) && $this instanceof self) {
			$from = $this;
		} else {
			$from = $to; 
			$to = @func_get_arg(1);
		}
		$from = self::_toTime($from);
		if ($to === false) { 
			$to = time();
		} else {
			$to = self::_toTime($to);
		}
		// Function itself
		list($y, $m, $d) = explode('-', date('Y-m-d', $from));
		$years = date('Y', $to) - $y;
		if (date('md', $to) < $m . $d) {
			$years--;
		}
		return $years;
	}
	
	/**
	 * Difference of days between 2 timestamps.
	 * 
	 * Static: 		daysDiff($from, $to = null)
	 * Instance: 	daysDiff($to = false)
	 * 
	 * @param int $from [only with static calls] 
	 * @param int $to [optional]
	 * @return int
	 */
	public function daysDiff($to = false) {
		if (isset($this) && $this instanceof self) {
			$from = $this;
		} else {
			$from = $to; 
			$to = @func_get_arg(1);
		}
		$from = self::_toTime($from);
		if ($to === false) { 
			$to = time();
		} else {
			$to = self::_toTime($to);
		}
		// Function itself
		$diff = $to - $from;
		$days = round($diff / 86400);
		return $days;
	}
	
	/**
	 * Converts string to time if needed.
	 * 
	 * @param string $datetime
	 * @return int 
	 */
	protected static function _toTime($datetime) {
		if (!is_int($datetime)) {
			$datetime = strtotime($datetime);
		}
		return $datetime;
	}
	
	/**
	 * Returns the difference between two Jp7_Date objects.
	 * 
	 * @param Jp7_Date $datetime
	 * @return DateInterval|object
     */
	public function diff(Jp7_Date $datetime) {
		if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
			// Versão 5.3 já possui método
			$retorno = parent::diff($datetime);
		} else {
			// Versões antigas precisam fazer "manualmente"
			if ($this < $datetime){
				$d1 = $datetime;
				$d2 = $this;
			} else {
			    $d1 = $this;
				$d2 = $datetime;
			}
			$temp = $d1->getTimestamp();
			$keys = array('y', 'm', 'd', 'h', 'i', 's', '_', '_', '_', '_', '_', '_');
			$d1 = (object) array_combine($keys, date_parse($d1->format('Y-m-d H:i:s')));
			$d2 = (object) array_combine($keys, date_parse($d2->format('Y-m-d H:i:s')));
			if ($d1->s >= $d2->s) {
				$diff->s = $d1->s - $d2->s;
			} else {
				$d1->i--;
				$diff->s = 60 - $d2->s + $d1->s;
			}
			if ($d1->i >= $d2->i) {
				$diff->i = $d1->i - $d2->i;
			} else {
				$d1->h--;
				$diff->i = 60 - $d2->i + $d1->i;
			}
			if ($d1->h >= $d2->h) {
				$diff->h = $d1->h - $d2->h;
			} else {
				$d1->d--;
				$diff->h = 24 - $d2->h + $d1->h;
			}
			if ($d1->d >= $d2->d) {
				$diff->d = $d1->d - $d2->d;
			} else {
				$d1->m--;
				$diff->d = date('t', $temp) - $d2->d + $d1->d;
			}
			if ($d1->m >= $d2->m) {
				$diff->m = $d1->m - $d2->m;
			} else {
				$d1->y--;
				$diff->m = 12 - $d2->m + $d1->m;
			}
			$diff->y = $d1->y - $d2->y;
			$retorno = $diff;
		}
		//$retorno->days; // Bugado até mesmo na 'oficial' PHP 5.3
		return $retorno;
	}
	
	/**
	 * Gets the Unix timestamp
	 * 
	 * @return int Returns Unix timestamp representing the date. 
	 */
	public function getTimestamp() {
		if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
			return parent::getTimestamp();
		} else {
			return $this->format('U');
		}
	}
	
	/**
	 * Returns date formatted according to given format.
	 * 
	 * @param string $format Format accepted by date().
	 * @return 
	 */
	public function format($format) {
		// Bug PHP para datas zeradas
		if (parent::format('Y') === '-0001') {
			// Switch usado para performance, default: é o tratamento completo do erro
			switch ($format) {
				case 'd':
					return '00';
				case 'm':
					return '00';
				case 'Y':
					return '0000';
				case 'Y-m-d H:i:s':
					return  '0000-00-00 00:00:00';
				default:
					$format = preg_replace('/(?<!\\\\)Y/', '0000', $format);
					$format = preg_replace('/(?<!\\\\)(d|m|y)/', '00', $format);
					$format = preg_replace('/(?<!\\\\)c/', '0000-00-00\T00:00:00', $format);
			}
		}
		
		if (strpos($format, 'M') !== false) {
			$format = preg_replace('/(?<!\\\\)M/', addcslashes(jp7_date_month($this->format('m'), true), 'A..z'), $format);
		}
		if (strpos($format, 'F') !== false) {
			$format = preg_replace('/(?<!\\\\)F/', addcslashes(jp7_date_month($this->format('m')), 'A..z'), $format);
		}
		
		return parent::format($format);		
	}
	
	public function __toString() {
		return $this->format('Y-m-d H:i:s');
	}
	
	/**
	 * DateTime does not support serialization by default.
	 * 
	 * @todo Retirar quando migrar para PHP 5.3
	 * @return 
	 */
	public function __wakeUp() {
		parent::__construct($this->_serializedValue);
		unset($this->_serializedValue);
	}
	
	/** 
	 * DateTime does not support serialization by default.
	 * 
	 * @todo Retirar quando migrar para PHP 5.3
	 * @return void
	 */
	public function __sleep() {
		$this->_serializedValue = $this->__toString();
		return array('_serializedValue');
	}
	
	public function minute() {
		return $this->format('i');
	}
	public function hour() {
		return $this->format('H');
	}
	public function day() {
		return $this->format('d');
	}
	public function month() {
		return $this->format('m');
	}
	public function quarter() {
		return ceil($this->format('m') / 3);
	}
	public function year() {
		return $this->format('Y');
	}
	
	/**
	 * Checks if its not an invalid date such as '0000-00-00 00:00:00'.
	 * 
	 * @return bool
	 */
	public function isValid() {
		return parent::format('Y') !== '-0001';
	}
}