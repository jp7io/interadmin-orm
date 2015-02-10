<?php

namespace Jp7\Former;
use HtmlObject\Traits\Tag;

class Row extends Tag {

	protected $element = 'div';
	protected $isSelfClosing = true;

	public function __construct() {
		$this->addClass('row');
	}
	
}