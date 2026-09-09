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
     * The xtra values renamed on 2026-09-06, per field type.
     *
     * ⚠ Keyed by field type because `S` meant SIX different things: MD5 on a password, no-time on
     * a date, HTML on a text, checked on a bool, and by-types on both selects. A sweep keyed on
     * the literal rather than on the field holding it gets every one of them wrong.
     *
     * Applied on the way OUT of both formats, so the app reads the new names whether or not
     * 2026_09_06_300000 has run on the tenant.
     */
    const XTRA_RENAMES = [
        'varchar' => ['telefone' => 'phone', 'cor' => 'color', 'hora' => 'time'],
        'password' => ['S' => 'md5'],
        'file' => ['imagens' => 'images'],
        'date' => ['S' => 'notime'],
        'text' => ['S' => 'html'],
        'bool' => ['S' => 'checked'],
        'float' => ['moeda' => 'currency'],
        'select' => [
            'S' => 'types', 'radio' => 'records_radio', 'ajax' => 'records_ajax',
            'radio_tipos' => 'types_radio', 'ajax_tipos' => 'types_ajax',
        ],
        'select_multi' => ['S' => 'types', 'X' => 'records_search', 'X_tipos' => 'types_search'],
        'special' => [
            'registros' => 'records', 'registros_multi' => 'records_multi',
            'tipos' => 'types', 'tipos_multi' => 'types_multi',
        ],
    ];

    /**
     * The field type a column belongs to, as Field\Factory classifies it: the first segment, plus
     * `_multi` for a select_multi. ⚠ Not the `_<n>`/`_key` suffix strip the type editor uses --
     * that leaves a custom table's named column (`special_produtos`) unclassified, and six of ci's
     * carry an xtra that has to move.
     */
    public static function baseType(string $column): string
    {
        $base = explode('_', $column)[0];

        return $base === 'select' && strpos($column, 'select_multi_') === 0 ? 'select_multi' : $base;
    }

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

        $rows = array_filter($rows, function (array $row): bool {
            return (string) ($row['type'] ?? '') !== '';
        });

        // Normalised on the way OUT, both formats alike, so the app reads the same values either
        // side of the migration and the two can be deployed in either order.
        return array_map([self::class, 'normalise'], $rows);
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
            $rows[] = self::normalise($row);
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
    private static function normalise(array $row): array
    {
        foreach (self::BOOLEAN_ATTRIBUTES as $attribute) {
            if (array_key_exists($attribute, $row)) {
                $row[$attribute] = (bool) $row[$attribute];
            }
        }

        if (array_key_exists('xtra', $row)) {
            // '0' and '' were two spellings of "no xtra"; '' is the one every field type but
            // select_multi already declared, and the one an emptied input posts.
            $xtra = (string) $row['xtra'];
            $row['xtra'] = $xtra === '0'
                ? ''
                : (self::XTRA_RENAMES[self::baseType($row['type'] ?? '')][$xtra] ?? $xtra);
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
     * `column => name_id`: the name a RECORD answers to, which is not the one the blob stores.
     * The ONE home for that derivation -- InterAdmin\Models\Type asks it of the same rows, and a
     * second implementation disagrees the way increment 16's three decoders did.
     * ⚠ The suffix lands on a STORED name_id too, so a select_ row saying `moeda` answers to
     * `moeda_id`; suffixing only the generated half drops 931 of ci's 936.
     * ⚠ $typeName is the one impure step, needed by 4 of ci's 10,391 rows.
     *
     * @param array $rows Field definitions, as decode() returns them.
     * @param callable $typeName Given a select_'s stored `name`, that type's own name.
     *
     * @return array
     */
    public static function aliases(array $rows, callable $typeName): array
    {
        $aliases = [];

        foreach ($rows as $row) {
            $column = $row['type'];
            $alias = $row['name_id'] ?? '';

            if (!$alias) {
                $alias = self::aliasSource($row, $column, $typeName);

                if (!$alias) {
                    throw new UnexpectedValueException('An alias was expected.');
                }

                $alias = to_slug($alias, '_');
            }

            $aliases[$column] = $alias.self::aliasSuffix($column, $row);
        }

        return $aliases;
    }

    /**
     * A select_ stores the RELATED TYPE's id in `name`, so its alias reads off the field's own
     * label or, failing that, off that type's name -- `all` being the one value naming no type.
     * ⚠ Loose `!=`, as the derivation this replaces has it: under PHP 8 a type id of 0 is not
     * `all`, and tightening the comparison is a behaviour change wearing a port's clothes.
     */
    private static function aliasSource(array $row, string $column, callable $typeName)
    {
        $name = $row['name'] ?? '';

        if (strpos($column, 'select_') === 0 && $name != 'all') {
            return empty($row['label']) ? $typeName($name) : $row['label'];
        }

        return $name;
    }

    /** ⚠ Loose in_array, matching the derivation this replaces. */
    private static function aliasSuffix(string $column, array $row): string
    {
        if (strpos($column, 'select_') === 0) {
            return strpos($column, 'select_multi_') === 0 ? '_ids' : '_id';
        }

        if (strpos($column, 'special_') === 0 && ($row['xtra'] ?? '')) {
            return in_array($row['xtra'], self::getSpecialMultiXtras()) ? '_ids' : '_id';
        }

        return '';
    }

    /**
     * A `tit_` or `func_` row: it renders on the form and backs no column, so it is not a name a
     * record answers to. Every caller asking "which fields does a RECORD have" excludes these.
     */
    public static function isVirtualField(string $column): bool
    {
        return strpos($column, 'tit_') === 0 || strpos($column, 'func_') === 0;
    }

    /**
     * The xtra values of select_ fields which store types.
     *
     * @return array
     */
    public static function getSelectTypeXtras(): array
    {
        return ['types', 'types_search', 'types_ajax', 'types_radio'];
    }
    /**
     * The xtra values of special_ fields which store types.
     *
     * @return array
     */
    public static function getSpecialTypeXtras(): array
    {
        return ['types_multi', 'types'];
    }
    /**
     * The xtras of the special_ fields that store multiple records.
     *
     * @return array
     */
    public static function getSpecialMultiXtras(): array
    {
        return ['records_multi', 'types_multi'];
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
