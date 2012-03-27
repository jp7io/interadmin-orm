<?php
/* Copyright (C) 2008 Guy Van den Broeck <guy@guyvdb.eu>
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, USA.
 * or see http://www.gnu.org/
 */

/**
 * Alternative representation of a set of changes, by the index
 * ranges that are changed.
 * 
 * @ingroup DifferenceEngine
 */
class Jp7_Diff_RangeDifference {

	public $leftstart;
	public $leftend;
	public $leftlength;

	public $rightstart;
	public $rightend;
	public $rightlength;

	function __construct($leftstart, $leftend, $rightstart, $rightend){
		$this->leftstart = $leftstart;
		$this->leftend = $leftend;
		$this->leftlength = $leftend - $leftstart;
		$this->rightstart = $rightstart;
		$this->rightend = $rightend;
		$this->rightlength = $rightend - $rightstart;
	}
}
