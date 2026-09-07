<?php

namespace Jp7\InterAdmin;

use UnexpectedValueException;

/**
 * `types.children`: the child types a record's form offers as tabs.
 *
 * Two stored formats, told apart by the first character: JSON, and the positional
 * `<type_id>{,}<name>{,}<help>{,}<grandchildren>{;}` blob every tenant held before
 * 2026_09_07_000000. Reading both is what lets the migration and the four deploys carrying it land
 * in either order, on any tenant.
 *
 * ⚠ The four names were the DECODER's invention -- the blob stored positions -- so this is their
 * one home, and InterAdmin's Type\ChildRows reads them from here instead of keeping a second copy.
 * That is the pairing the `fields` move retired for FieldUtil, and it is the same pairing.
 */
class ChildUtil
{
    /**
     * A child declaration's attributes, in the order the positional blob stored them.
     *
     * `help` follows FieldUtil::ATTRIBUTES, which spells a field's the same way; `grandchildren`
     * is what `types.children.allow_grandchildren` already called the flag in the form's own copy.
     */
    const ATTRIBUTES = ['type_id', 'name', 'help', 'grandchildren'];

    /**
     * The attribute that is a FLAG and nothing else, stored as a real boolean.
     *
     * It is the last `'S'` the 2026 arc leaves standing: increments 10 to 12 retyped every char(1)
     * flag in the schema, and this one survived only by living inside a blob.
     */
    const BOOLEAN_ATTRIBUTES = ['grandchildren'];

    /**
     * Read `types.children` as declarations in the order the tabs are drawn.
     *
     * ⚠ A row is padded to all four attributes, which is what the positional reader already did:
     * four of ci's 702 declarations carry three parameters, and a caller reading `help` on one has
     * to get the empty string it got before the format moved.
     *
     * @return array<int, array{type_id: string, name: string, help: string, grandchildren: bool}>
     */
    public static function decode(?string $children): array
    {
        $children = (string) $children;

        if (trim($children) === '') {
            return [];
        }

        $rows = ltrim($children)[0] === '['
            ? self::decodeJson($children)
            : self::decodePositional($children);

        $rows = array_filter($rows, function ($row) {
            return (string) ($row['type_id'] ?? '') !== '';
        });

        return array_values(array_map([self::class, 'normalise'], $rows));
    }

    /**
     * Write child declarations as JSON, in the order given, because declaration order IS the order
     * the record form draws the tabs.
     */
    public static function encode(iterable $children): string
    {
        $rows = [];

        foreach ($children as $child) {
            $child = (array) $child;

            if (!strlen((string) ($child['type_id'] ?? ''))) {
                continue;
            }

            $row = [];
            foreach (self::ATTRIBUTES as $attribute) {
                $row[$attribute] = $child[$attribute] ?? '';
            }
            $rows[] = self::normalise($row);
        }

        // ⚠ A type with no children stores the EMPTY STRING, never `[]`: the seeded fixtures and
        // Box\Model\TypeAbstract both ask whether the column is `''`, and `[]` is 2 characters of
        // yes. Same rule the `fields` move settled.
        if (!$rows) {
            return '';
        }

        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * ⚠ `<> ''` and never `=== 'S'`: the blob spelled the flag `S`, a JSON row already holds a real
     * boolean, and the form posts `1`. That is the cast increment 10 measured 7.73M values for.
     *
     * `type_id` stays a STRING, which is what the positional reader handed back -- every consumer
     * compares it loosely (`==`), and casting it here would be a change to the decoded array that
     * this move is otherwise careful not to make.
     */
    private static function normalise(array $row): array
    {
        foreach (self::ATTRIBUTES as $attribute) {
            $value = $row[$attribute] ?? '';
            $row[$attribute] = in_array($attribute, self::BOOLEAN_ATTRIBUTES, true)
                ? (bool) $value
                : (string) $value;
        }

        return $row;
    }

    /**
     * SQL matching the types whose `children` DECLARES $typeId, in either stored format.
     *
     * ⚠ There is no reading a JSON document with LIKE, so this is three patterns rather than one,
     * and it lives here because it is knowledge of the format and nothing else. The positional
     * arms stay for as long as any prefix is unmigrated: `%}<id>{%` is a declaration after the
     * first, `<id>{%` the first one.
     *
     * ⚠ Both formats over-match a NAME that is bare digits (`{,}364{,}` reads as a type_id, and so
     * does `"name":"364"`), which the positional predicate did before this and which the callers
     * have always tolerated: an extra type in a filter's option list, never a missing one.
     */
    public static function declaresSql(string $column, $typeId): string
    {
        $typeId = (int) $typeId;

        return '('.$column.' LIKE \'%"type_id":"'.$typeId.'"%\''
            .' OR '.$column.' LIKE \'%}'.$typeId.'{%\''
            .' OR '.$column.' LIKE \''.$typeId.'{%\')';
    }

    private static function decodeJson(string $children): array
    {
        $rows = json_decode($children, true);

        if (!is_array($rows)) {
            throw new UnexpectedValueException('types.children holds text that starts as JSON and does not parse.');
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * ⚠ The blob is separated AND terminated by `{;}`, so its final split is the empty tail. It is
     * dropped by the empty-`type_id` filter above rather than by stopping one short, which is the
     * same result on every terminated blob and keeps the last declaration of an unterminated one.
     */
    private static function decodePositional(string $children): array
    {
        $rows = [];

        foreach (explode('{;}', $children) as $entry) {
            $parameters = explode('{,}', $entry);
            $row = [];

            foreach (self::ATTRIBUTES as $position => $attribute) {
                $row[$attribute] = $parameters[$position] ?? '';
            }

            $rows[] = $row;
        }

        return $rows;
    }
}
