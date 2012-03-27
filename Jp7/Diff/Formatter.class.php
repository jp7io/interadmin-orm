<?php

/**
 * A class to format Diffs
 *
 * This class formats the diff in classic diff format.
 * It is intended that this class be customized via inheritance,
 * to obtain fancier outputs.
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class Jp7_Diff_Formatter {
	/**
	 * Number of leading context "lines" to preserve.
	 *
	 * This should be left at zero for this class, but subclasses
	 * may want to set this to other values.
	 */
	var $leading_context_lines = 0;

	/**
	 * Number of trailing context "lines" to preserve.
	 *
	 * This should be left at zero for this class, but subclasses
	 * may want to set this to other values.
	 */
	var $trailing_context_lines = 0;

	/**
	 * Format a diff.
	 *
	 * @param $diff object A Diff object.
	 * @return string The formatted output.
	 */
	function format($diff) {
		//wfProfileIn( __METHOD__ );

		$xi = $yi = 1;
		$block = false;
		$context = array();

		$nlead = $this->leading_context_lines;
		$ntrail = $this->trailing_context_lines;

		$this->_start_diff();

		foreach ($diff->edits as $edit) {
			/*
			if ($edit->type == 'copy') {
				if (is_array($block)) {
					if (sizeof($edit->orig) <= $nlead + $ntrail) {
						$block[] = $edit;
					}
					else{
						if ($ntrail) {
							$context = array_slice($edit->orig, 0, $ntrail);
							$block[] = new _DiffOp_Copy($context);
						}
						$this->_block($x0, $ntrail + $xi - $x0,
						$y0, $ntrail + $yi - $y0,
						$block);
						$block = false;
					}
				}
				$context = $edit->orig;
			}
			else {
				*/			
				if (! is_array($block)) {
					$context = array_slice($context, sizeof($context) - $nlead);
					$x0 = $xi - sizeof($context);
					$y0 = $yi - sizeof($context);
					$block = array();
					if ($context)
					$block[] = new _DiffOp_Copy($context);
				}
				$block[] = $edit;
			//}

			if ($edit->orig)
			$xi += sizeof($edit->orig);
			if ($edit->closing)
			$yi += sizeof($edit->closing);
		}

		if (is_array($block))
		$this->_block($x0, $xi - $x0,
		$y0, $yi - $y0,
		$block);

		$end = $this->_end_diff();
		//wfProfileOut( __METHOD__ );
		return $end;
	}

	function _block($xbeg, $xlen, $ybeg, $ylen, &$edits) {
		//wfProfileIn( __METHOD__ );
		$this->_start_block($this->_block_header($xbeg, $xlen, $ybeg, $ylen));
		foreach ($edits as $edit) {
			if ($edit->type == 'copy')
			$this->_context($edit->orig);
			elseif ($edit->type == 'add')
			$this->_added($edit->closing);
			elseif ($edit->type == 'delete')
			$this->_deleted($edit->orig);
			elseif ($edit->type == 'change')
			$this->_changed($edit->orig, $edit->closing);
			else
			trigger_error('Unknown edit type', E_USER_ERROR);
		}
		$this->_end_block();
		//wfProfileOut( __METHOD__ );
	}

	function _start_diff() {
		ob_start();
	}

	function _end_diff() {
		$val = ob_get_contents();
		ob_end_clean();
		return $val;
	}

	function _block_header($xbeg, $xlen, $ybeg, $ylen) {
		if ($xlen > 1)
		$xbeg .= "," . ($xbeg + $xlen - 1);
		if ($ylen > 1)
		$ybeg .= "," . ($ybeg + $ylen - 1);

		return $xbeg . ($xlen ? ($ylen ? 'c' : 'd') : 'a') . $ybeg;
	}

	function _start_block($header) {
		echo $header . "\n";
	}

	function _end_block() {
	}

	function _lines($lines, $prefix = ' ') {
		foreach ($lines as $line)
		echo "$prefix $line\n";
	}

	function _context($lines) {
		$this->_lines($lines);
	}

	function _added($lines) {
		$this->_lines($lines, '>');
	}
	function _deleted($lines) {
		$this->_lines($lines, '<');
	}

	function _changed($orig, $closing) {
		$this->_deleted($orig);
		echo "---\n";
		$this->_added($closing);
	}
}