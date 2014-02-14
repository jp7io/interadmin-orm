<?php
namespace Jp7;

class String {
	private $str;
	
	public static function create($str) {
		return new self($str);
	}
	
	function __construct($str) {
		$this->str = $str;
	}
	
	public function startsWith($str) {
		return startsWith($str, $this->str);
	}
	
	public function endsWith($str) {
		return endsWith($str, $this->str);
	}
	
	public function sub($start, $end) {
		return new self(substr($this->str, $start, $end));
	}
	
	public function ljust($pad_length, $pad_str = ' ') {
		return new self(str_pad($this->str, $pad_length, $pad_str));
	}
	
	public function rjust($pad_length, $pad_str = ' ') {
		return new self(str_pad($this->str, $pad_length, $pad_str, STR_PAD_LEFT));
	}
	
	public function length() {
		return strlen($this->str);
	}
	
	public function __toString() {
		return $this->str;
	}
}