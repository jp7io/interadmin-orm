<?php

namespace Jp7;

class NullObject {
	public function __get($var) {
		
	}
	
	public function _try($attribute) {
		return $this;
	}
}