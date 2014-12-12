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
	
	const DURATION_LOWERISO = 0;
	const DURATION_ISO = 1;
	const DURATION_HUMAN = 2;
	
	/**
	 * Returns new Jp7_Date object formatted according to the specified format.
	 * @param string $format
	 * @param string $time
	 * @param DateTimeZone $timezone
	 * @return Jp7_Date
	 */
	public static function createFromFormat($format, $time, $timezone = null) {
		if ($timezone) { 
			$date = parent::createFromFormat($format, $time, $timezone); 
		} else {
			$date = parent::createFromFormat($format, $time);
		}
		if ($date) {
			return new static($date->format('c'), $date->getTimezone());
		} else {
			return $date;
		}
	}
	
	public static function createFromString($time, $formats = array('d/m/Y', 'Y-m-d'), DateTimeZone $timezone = null) {
		foreach ($formats as $format) {
			if ($date = static::createFromFormat($format, $time, $timezone)) {
				return $date;
			}
		}
	}
	
	public function cloneAndModify($string) {
		$copy = clone $this;
		return $copy->modify($string);
	}
	
	/**
	 * Retorna string da diferen�a de tempo, ex: '3 dias atr�s'.
	 * O valor � arredondado: 2 anos e 4 meses retorna '2 anos atr�s'.
	 * Diferen�as menores de 1 minuto retornam 'agora'.
	 * 
	 * Static: 		humanDiff($timeStamp = false)
	 * Instance: 	humanDiff()
	 * 
	 * @param int|string $timeStamp [only with static calls] Timestamp ou Datetime. 
	 * @return string
	 */
	public function humanDiff($timeStamp = false) {
		global $lang;
		switch ($lang->lang) {
			case 'en':
				$units_names = array('years' => 'year', 'months' => 'month', 'weeks' => 'week', 'days' => 'day', 'hours' => 'hour', 'minutes' => 'minute');
				$now = 'now';
				$yesterday = 'yesterday';
				$ago = 'ago';
				break;
			default:
				$units_names = array('anos' => 'ano', 'meses' => 'm�s', 'semanas' => 'semana', 'dias' => 'dia', 'horas' => 'hora', 'minutos' => 'minuto');
				$now = 'agora';
				$yesterday = 'ontem';
				$ago = 'atr�s';
		}
		if (isset($this) && $this instanceof self) {
			$timeStamp = $this;
		}
		$timeStamp = self::_toTime($timeStamp);
		$currentTime = time();
		$units = array_combine($units_names, array(31556926, 2629743, 604800, 86400, 3600, 60));
		$seconds = $currentTime - $timeStamp;
		if ($seconds <= 60) {
			return $now;
		} elseif ($seconds < 86400 * 2 && date('d', $currentTime) - 1 == date('d', $timeStamp)) {
			return $yesterday; // ontem
		} elseif ($seconds > 86400 && $seconds < 604800) {
			$seconds = round($seconds / 86400) * 86400; // dias
		}
		foreach ($units as $unit => $seconds_in_period) {
			if ($seconds >= $seconds_in_period) {
				$count = floor($seconds / $seconds_in_period);
				return $count . ' ' . (($count > 1) ? array_search($unit, $units_names) : $unit) . ' ' . $ago;
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
	 * TODO needs refactoring
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
	 * Static: 		daysDiff($from, $to = null, $min = false)
	 * Instance: 	daysDiff($to = false, $min = false)
	 * 
	 * @param int $from [only with static calls] 
	 * @param int $to [optional]
	 * @return int
	 */
	public function daysDiff($to = false, $min = false) {
		if (isset($this) && $this instanceof self) {
			$from = $this;
		} else {
			$from = $to; 
			$to = $min;
			$min = @func_get_arg(2);
		}
		$from = self::_toTime($from);
		if ($to === false) { 
			$to = time();
		} else {
			$to = self::_toTime($to);
		}
		// Function itself
		$diff = $to - $from;

		$days = $min == false ? round($diff / 86400) : $diff / 86400;
		return $days;
	}

	public function hoursDiff( $to = false, $min = false ){
		$daydiff = $this->daysDiff($to, true);

		return $min == false ? floor($daydiff * 24) : $daydiff * 24;
	}

	public function minutesDiff( $to = false, $sec = false ){
		$hoursDiff = $this->hoursDiff($to, true);

		return $sec == false ? floor($hoursDiff * 60) : $hoursDiff * 60;
	}

	public function secondsDiff( $to = false ){
		$minutesDiff = $this->minutesDiff($to, true);

		return floor($minutesDiff * 60);
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
	 * Returns date formatted according to given format.
	 * 
	 * @param string $format Format accepted by date().
	 * @return 
	 */
	public function format($format) {
		// Bug PHP para datas zeradas
		if (parent::format('Y') === '-0001') {
			// Switch usado para performance, default: � o tratamento completo do erro
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
		// Tratamento de nomes para m�ltiplas l�nguas
		if (strpos($format, 'D') !== false) {
			$format = preg_replace('/(?<!\\\\)D/', addcslashes(jp7_date_week(intval($this->format('w')), true), 'A..z'), $format);
		}
		if (strpos($format, 'l') !== false) {
			$format = preg_replace('/(?<!\\\\)l/', addcslashes(jp7_date_week(intval($this->format('w'))), 'A..z'), $format);
		}
		if (strpos($format, 'M') !== false) {
			$format = preg_replace('/(?<!\\\\)M/', addcslashes(jp7_date_month($this->format('m'), true), 'A..z'), $format);
		}
		if (strpos($format, 'F') !== false) {
			$format = preg_replace('/(?<!\\\\)F/', addcslashes(jp7_date_month($this->format('m')), 'A..z'), $format);
		}
		// Format padr�o
		return parent::format($format);		
	}
	
	public function short() {
		global $lang;
		if ($lang->lang == 'en') {
			return $this->format('m/d/Y');	
		} else {
			return $this->format('d/m/Y');
		}
	}
	
	public function long() {
		global $lang;
		if ($lang->lang == 'en') {
			return $this->format('F d, Y');	
		} else {
			return $this->format('d \d\e F \d\e Y');
		}
	}
	
	public function __toString() {
		return $this->format('Y-m-d H:i:s');
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
	
	/**
	 * Returns the duration between two Jp7_Date objects.
	 * 
	 * @param Jp7_Date $datetime
	 * @param $iso Retorna no formato iso ou num formato mais comum como 4h30m.
	 * @return string
     */
	public function duration(Jp7_Date $datetime, $iso = Jp7_Date::DURATION_ISO) {
		$diff = $this->diff($datetime);
		
		if ($iso == Jp7_Date::DURATION_ISO) {
			$duration = 'P';
		} else {
			$duration = '';
		}
		
		if ($diff->y) {
			if ($iso == Jp7_Date::DURATION_HUMAN) {
				$duration .= $diff->y . (($diff->y == 1) ? ' ano ' : ' anos ');
			} else {
				$duration .= $diff->y . 'Y';
			}
		}
		if ($diff->m) {
			if ($iso == Jp7_Date::DURATION_HUMAN) {
				$duration .= $diff->m . (($diff->m == 1) ? ' m�s ' : ' meses ');
			} else {
				$duration .= $diff->m . 'M';
			}
		}
		if ($diff->d) {
			if ($iso == Jp7_Date::DURATION_HUMAN) {
				$duration .= $diff->d . (($diff->d == 1) ? ' dia ' : ' dias ');
			} else {
				$duration .= $diff->d . 'D';
			}
		}
		if ($diff->h || $diff->i || $diff->s) {
			if ($iso == Jp7_Date::DURATION_ISO) {
				$duration .= 'T';
			}
			
			if ($diff->h) {
				if ($iso == Jp7_Date::DURATION_HUMAN) {
					$duration .= $diff->h . (($diff->h == 1) ? ' hora ' : ' horas ');
				} else {
					$duration .= $diff->h . 'H';
				}
			}
			if ($diff->i) {
				if ($iso == Jp7_Date::DURATION_HUMAN) {
					$duration .= $diff->i . (($diff->i == 1) ? ' minuto ' : ' minutos ');
				} else {
					$duration .= $diff->i . 'M';
				}
			}
			if ($diff->s) {
				if ($iso == Jp7_Date::DURATION_HUMAN) {
					$duration .= $diff->s . (($diff->s == 1) ? ' segundo ' : ' segundos ');
				} else {
					$duration .= $diff->s . 'S';
				}
			}
		}
		
		if ($iso == Jp7_Date::DURATION_LOWERISO) {
			$duration = strtolower($duration);
		}
		
		return $duration;
	}
}