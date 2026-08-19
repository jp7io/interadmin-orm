<?php

namespace Jp7\Interadmin;

/**
 * Rewrites a boolean literal compared against a char_ column into the emptiness test that
 * column actually stores.
 *
 * InterAdmin's char_ columns hold 'S' or '' rather than 1/0, so `where('flag', true)`
 * cannot reach SQL as `= true`. The compiler rewrites the comparison in place:
 *
 *     main.char_1 = true    ->  main.char_1 != ''
 *     main.char_1 = false   ->  main.char_1 = ''
 *     main.char_1 <> true   ->  main.char_1 = ''
 *     main.char_1 <> false  ->  main.char_1 != ''
 *
 * Extracted verbatim from RecordAbstract::_resolveSql (REFACTORING #5), where it was one
 * of four responsibilities tangled into a single 240-line while-loop. It is a pure string
 * transform over the clause being compiled, so unlike the rest of that loop it needs
 * neither the record, the type, nor a database -- which is why it comes out first.
 *
 * @see RecordAbstract::_resolveSql()
 */
class CharBooleanRewriter
{
    /** Is this token one this rewriter handles? */
    public static function handles($term): bool
    {
        return strtolower($term) === 'true' || strtolower($term) === 'false';
    }

    /**
     * Rewrites the comparison ENDING at the literal just matched.
     *
     * Only the head of the clause (everything up to and including the literal) is
     * rewritten, so a `true` appearing later is left for the next pass -- which is why
     * this returns the offset the caller must resume its scan from.
     *
     * @param string $clause The whole clause being compiled
     * @param string $term   The literal matched ('true'/'false', any case)
     * @param int    $pos    Byte offset of $term within $clause
     *
     * @return array [$clause, $offset] rewritten clause + where to resume scanning
     */
    public static function rewrite($clause, $term, $pos): array
    {
        // ['', '!'] for true, reversed for false: index 0 negates a <>/!= comparison,
        // index 1 negates an = comparison.
        $negations = ['', '!'];
        if (strtolower($term) === 'false') {
            $negations = array_reverse($negations);
        }

        $head = substr($clause, 0, $pos + strlen($term));

        // `char_x <> true` -> `char_x = ''` (anchored: the literal ends the head)
        $rewritten = preg_replace(
            '/(\.char_[[:alnum:] ]*)(<>|!=)([ ]*)'.$term.'$/i',
            '$1'.$negations[0]."=$3''",
            $head,
            1,
            $count
        );

        // otherwise `char_x = true` -> `char_x != ''`
        if (!$count) {
            $rewritten = preg_replace(
                '/(\.char_[^=]*)=([ ]*)'.$term.'/i',
                '$1'.$negations[1]."=$2''",
                $head,
                1
            );
        }

        return [
            $rewritten.substr($clause, $pos + strlen($term)),
            strlen($rewritten),
        ];
    }
}
