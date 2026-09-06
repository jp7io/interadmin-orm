<?php

namespace Jp7\InterAdmin;

use UnexpectedValueException;

/**
 * Generates the HTML output for a field based on its type, such as varchar, int or text.
 */
class FieldUtil
{
    public $id;
    public $type_id;
    public $field;

    /**
     * Construtor p￺úblico.
     *
     * @param array $field One row of the Type's field definitions [optional]
     *
     * @return
     */
    public function __construct($field = [])
    {
        $this->field = $field;
    }
    public function __toString(): string
    {
        return $this->field['type'];
    }

    /**
     * The names of a field definition's attributes, in the order the positional blob stored them.
     *
     * The one home for the list: InterAdmin's Field::FIELDS_ATTRIBUTES is this constant, and the
     * two decoders and the encoder read it here. Three hand-kept copies across two repositories is
     * what this replaces, one of which said in a comment that it had to be kept identical by hand.
     */
    const ATTRIBUTES = [
        'type', 'name', 'help', 'size', 'required', 'separator', 'xtra', 'list',
        'orderby', 'combo', 'readonly', 'form', 'label', 'permissions', 'default', 'name_id',
    ];

    /**
     * The attributes that are a FLAG and nothing else, stored as real booleans.
     *
     * ⚠ `xtra` is NOT one, and that is the whole trap: it holds 30 distinct values on ci and `'S'`
     * is one of their NAMES, on 1,988 rows. A sweep that reads `'S'` as a flag rather than asking
     * which attribute holds it destroys every one of them, and nothing errors.
     */
    const BOOLEAN_ATTRIBUTES = ['required', 'separator', 'list', 'combo', 'readonly', 'form'];

    /**
     * Read `types.fields` as definitions in the order the record form renders them, keyed by the
     * row's position so a caller's `order` is the position it always was.
     *
     * Two stored formats, told apart by the first character: JSON, and the positional
     * `{;}`/`{,}` blob every tenant held before 2026_09_06_300000. Reading both is what lets the
     * migration and the deploy carrying it land in either order, on any tenant.
     *
     * ⚠ A row keeps the attributes it stores rather than being padded to all 16. 96 rows on ci
     * predate `name_id` and carry as few as 9, and a reader asking isset() on a later attribute
     * has to get the answer it got before the format moved. encode() pads, so the first save of a
     * type normalises it, exactly as the positional writer already did.
     */
    public static function decode(?string $fields): array
    {
        $fields = (string) $fields;

        if (trim($fields) === '') {
            return [];
        }

        $rows = ltrim($fields)[0] === '['
            ? self::decodeJson($fields)
            : self::decodePositional($fields);

        $rows = array_filter($rows, function ($row) {
            return (string) ($row['type'] ?? '') !== '';
        });

        // Cast on the way OUT, both formats alike, so the app reads the same booleans either side
        // of the migration and the two can be deployed in either order.
        return array_map([self::class, 'castFlags'], $rows);
    }

    /**
     * Write field definitions as JSON, padded to every attribute and in the order given, because
     * field order IS the record form's field order.
     */
    public static function encode(iterable $fields): string
    {
        $rows = [];

        foreach ($fields as $field) {
            $field = (array) $field;

            if (!strlen((string) ($field['type'] ?? ''))) {
                continue;
            }

            $row = [];
            foreach (self::ATTRIBUTES as $attribute) {
                $row[$attribute] = (string) ($field[$attribute] ?? '');
            }
            $rows[] = self::castFlags($row);
        }

        // ⚠ A type with no fields stores the EMPTY STRING, never `[]`: `holds_records` and the
        // Refresh-the-cache sync ask strlen() whether a type configures any, and `[]` is 2.
        if (!$rows) {
            return '';
        }

        // Unescaped, so a tenant reading the column by hand sees the accents and the slashes it
        // typed. Both are legal JSON and json_decode reads them back identically.
        return json_encode($rows, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * ⚠ `(bool)` and not `=== 'S'`: the grid posts the string `S`, a JSON row already holds a real
     * boolean, and the empty string is what both formats spell false as. `'0'` is false too, and
     * that is right -- no flag on ci has ever held it.
     */
    private static function castFlags(array $row): array
    {
        foreach (self::BOOLEAN_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $row)) {
                $row[$attribute] = (bool) $row[$attribute];
            }
        }

        return $row;
    }

    private static function decodeJson(string $fields): array
    {
        $rows = json_decode($fields, true);

        if (!is_array($rows)) {
            throw new UnexpectedValueException('types.fields holds text that starts as JSON and does not parse.');
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    private static function decodePositional(string $fields): array
    {
        $rows = [];

        foreach (explode('{;}', $fields) as $i => $row) {
            $parameters = explode('{,}', $row);
            $mapped = [];

            foreach ($parameters as $j => $parameter) {
                if (isset(self::ATTRIBUTES[$j])) {
                    $mapped[self::ATTRIBUTES[$j]] = $parameter;
                }
            }

            $rows[$i] = $mapped;
        }

        return $rows;
    }

    /**
     * The xtra values of select_ fields which store types.
     *
     * @return array
     */
    public static function getSelectTypeXtras(): array
    {
        return ['S', 'X_tipos', 'ajax_tipos', 'radio_tipos'];
    }
    /**
     * The xtra values of special_ fields which store types.
     *
     * @return array
     */
    public static function getSpecialTypeXtras(): array
    {
        return ['tipos_multi', 'tipos'];
    }
    /**
     * The xtras of the special_ fields that store multiple records.
     *
     * @return array
     */
    public static function getSpecialMultiXtras(): array
    {
        return ['registros_multi', 'tipos_multi'];
    }
    /**
     * The field's value in the list header.
     *
     * @param array $field
     *
     * @return string
     */
    public static function getFieldHeader($field)
    {
        $key = $field['type'];
        if (strpos($key, 'special_') === 0 || strpos($key, 'func_') === 0) {
            if (!is_callable($field['name'])) {
                return 'Função '.$field['name'].' não encontrada.';
            }
            return call_user_func($field['name'], $field, '', 'header');
        }
        if (strpos($key, 'select_') === 0) {
            if ($field['label']) {
                return $field['label'];
            }
            // Type::getFields() resolves a select_'s `name` to a Type; only 'all' stays a string.
            return $field['name'] instanceof Type ? $field['name']->name : 'Tipos';
        }
        return $field['name'];
    }
}
