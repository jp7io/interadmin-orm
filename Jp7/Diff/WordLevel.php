<?php

/**
 * @todo document
 * @private
 * @ingroup DifferenceEngine
 */
class Jp7_Diff_WordLevel extends Jp7_Diff_Mapped
{
    const MAX_LINE_LENGTH = 10000;

    public function __construct($orig_lines, $closing_lines)
    {
        //wfProfileIn( __METHOD__ );

        list($orig_words, $orig_stripped) = $this->_split($orig_lines);
        list($closing_words, $closing_stripped) = $this->_split($closing_lines);

        parent::__construct($orig_words, $closing_words,
        $orig_stripped, $closing_stripped);
        //wfProfileOut( __METHOD__ );
    }

    public function _split($lines)
    {
        //wfProfileIn( __METHOD__ );

        $words = [];
        $stripped = [];
        $first = true;
        foreach ($lines as $line) {
            # If the line is too long, just pretend the entire line is one big word
            # This prevents resource exhaustion problems
            if ($first) {
                $first = false;
            } else {
                $words[] = "\n";
                $stripped[] = "\n";
            }
            if (mb_strlen($line) > self::MAX_LINE_LENGTH) {
                $words[] = $line;
                $stripped[] = $line;
            } else {
                $m = [];
                if (preg_match_all('/ ( [^\S\n]+ | [0-9_A-Za-z\x80-\xff]+ | . ) (?: (?!< \n) [^\S\n])? /xs',
                $line, $m)) {
                    $words = array_merge($words, $m[0]);
                    $stripped = array_merge($stripped, $m[1]);
                }
            }
        }
        //wfProfileOut( __METHOD__ );
        return [$words, $stripped];
    }

    public function orig()
    {
        //wfProfileIn( __METHOD__ );
        $orig = new _HWLDF_WordAccumulator();

        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $orig->addWords($edit->orig);
            } elseif ($edit->orig) {
                $orig->addWords($edit->orig, 'del');
            }
        }
        $lines = $orig->getLines();
        //wfProfileOut( __METHOD__ );
        return $lines;
    }

    public function closing()
    {
        //wfProfileIn( __METHOD__ );
        $closing = new _HWLDF_WordAccumulator();

        foreach ($this->edits as $edit) {
            if ($edit->type == 'copy') {
                $closing->addWords($edit->closing);
            } elseif ($edit->closing) {
                $closing->addWords($edit->closing, 'ins');
            }
        }
        $lines = $closing->getLines();
        //wfProfileOut( __METHOD__ );
        return $lines;
    }
}
