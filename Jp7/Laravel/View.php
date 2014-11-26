<?php

namespace Jp7\Laravel;

class View {
	public function assign(array $array) {
		foreach ($array as $key => $value) {
			$this->$key = $value;
		}
	}
}
