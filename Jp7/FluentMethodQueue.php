<?php

namespace Jp7;

class FluentMethodQueue extends MethodQueue {
	public function __call($method, $arguments) {
		parent::__call($method, $arguments);

		return $this;
	}
}