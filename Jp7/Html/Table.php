<?php
namespace Jp7\Html;

use HtmlObject\Element;

class Table extends \HtmlObject\Table {
	
	/**
	 * Set the table's rows
	 *
	 * @param array $rows
	 *
	 * @return $this
	 */
	public function rows(array $rows = array()) {
		// Cancel if no rows
		if (!$rows) {
			return $this;
		}
	
		// Create tbody
		$tbody = Element::create('tbody');
		foreach ($rows as $key => $row) {
			$tr = Element::create('tr');
			foreach ($row as $column => $value) {
				$td = $value instanceof Element ? $value : Element::create('td', $value);
				$tr->setChild($td);
			}
			$tbody->setChild($tr);
		}
		
		// Nest into table
		$this->nest(array(
			'tbody' => $tbody,
		));
	
		return $this;
	}
}
