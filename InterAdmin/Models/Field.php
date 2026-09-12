<?php

namespace InterAdmin\Models;

class Field
{
    /** Defined in the ORM, which decodes `types.fields`; named here because app code reads it. */
    const FIELDS_ATTRIBUTES = \Jp7\InterAdmin\Schema\FieldDefinitions::ATTRIBUTES;

    /**
     * stored value => TRANSLATION KEY of the label. Type::xtraOptions() resolves it; the keys
     * beside them are `fields` data and may not be renamed (resources/lang/en/fields.php).
     */
    const FIELDS_OPTIONS = [
        'varchar' => [
            'default' => ['fields.normal'],
            'id' => ['fields.id'],
            'id_email' => ['fields.id_email'],
            'email' => ['fields.email'],
            'num' => ['fields.number'],
            'cep' => ['fields.cep'],
            'cpf' => ['fields.cpf'],
            'cnpj' => ['fields.cnpj'],
            'phone' => ['fields.phone'],
            'll' => ['fields.lat_long'],
            'url' => ['fields.url'],
            'color' => ['fields.hex_color'],
            'time' => ['fields.time'],
        ],
        'password' => [
            'default' => ['fields.plain_do_not_use'],
            'md5' => ['fields.md5'],
            'hash' => ['fields.hash'],
        ],
        'file' => [
            'default' => ['fields.normal'],
            'images' => ['fields.images_only'],
            'docs' => ['fields.docs_only'],
            'trigger' => ['fields.fill_name'],
            'notext' => ['fields.no_caption'],
        ],
        'date' => [
            'default' => ['fields.normal'],
            'notime' => ['fields.no_time'],
            'calendar_datetime' => ['fields.calendar'],
            'calendar_date' => ['fields.calendar_no_time'],
            'nocombo_datetime' => ['fields.no_combo'],
            'nocombo_date' => ['fields.no_combo_no_time'],
            'calendar_nocombo_datetime' => ['fields.calendar_no_combo'],
            'calendar_nocombo_date' => ['fields.calendar_no_combo_no_time'],
        ],
        'time' => [
            'default' => ['fields.normal'],
        ],
        'select' => [
            'default' => ['fields.by_records'],
            'records_radio' => ['fields.by_records_radio'],
            'records_ajax' => ['fields.by_records_ajax'],
            'types' => ['fields.by_types'],
            'types_radio' => ['fields.by_types_radio'],
            'types_ajax' => ['fields.by_types_ajax'],
        ],
        'select_multi' => [
            // ⚠ Labels per SelectMultiField's own XTRA_* constants, which the ORM's
            // getSelectTypeXtras() agrees with: types is checkboxes over types, records_search is
            // a record search. The two labels were swapped here, so the editor offered "With
            // Types" for the option that renders a record search and vice versa.
            'default' => ['fields.by_records'],
            'types' => ['fields.by_types'],
            'records_search' => ['fields.by_records_search'],
            'types_search' => ['fields.by_types_search'],
        ],
        'text' => [
            'default' => ['fields.text'],
            'html' => ['fields.html'],
            'html_light' => ['fields.html_light'],
        ],
        'int' => [
            'default' => ['fields.normal'],
        ],
        'float' => [
            'default' => ['fields.normal'],
            'currency' => ['fields.currency'],
        ],
        'bool' => [
            'default' => ['fields.unchecked'],
            'checked' => ['fields.checked'],
        ],
        'special' => [
            'default' => ['fields.normal'],
            'records' => ['fields.by_records'],
            'records_multi' => ['fields.multi_records'],
            'types' => ['fields.by_types'],
            'types_multi' => ['fields.multi_types'],
        ],
        'tit' => [
            'default' => ['fields.visible'],
            'hidden' => ['fields.hidden'],
        ],
        'func' => [
            'default' => ['fields.normal'],
        ],
    ];

    /**
     * The `{type}_*` input suffix each positional parameter is edited under, on the types
     * form (resources/views/types/form/fields_item.blade.php). `type` has no entry: it is
     * the input-name PREFIX rather than a value, which is how a row is identified at all.
     *
     * A map rather than a str_replace, because most of these are English and `required` is not.
     */
    const FIELDS_INPUTS = [
        'name' => '_field',
        'help' => '_help',
        'size' => '_size',
        'required' => '_obrigatorio',
        'separator' => '_separator',
        'xtra' => '_xtra',
        'list' => '_list',
        'orderby' => '_orderby',
        'combo' => '_combo',
        'readonly' => '_readonly',
        'form' => '_form',
        'label' => '_label',
        'permissions' => '_permissions',
        'default' => '_default',
        'name_id' => '_name_id',
    ];

    /**
     * Encode field definitions for the `types.fields` column, the inverse of the decode in
     * FieldDefinitions, which is where both halves of the format live.
     */
    public static function serialize(iterable $fields): string
    {
        return \Jp7\InterAdmin\Schema\FieldDefinitions::encode($fields);
    }

    /**
     * Pick the field definitions out of a types-form submission.
     *
     * ⚠ Row order follows the request, i.e. DOM order, which is what the reorder arrows and the row
     * drag manipulate -- so iterating the input names is how the ordering survives, where iterating
     * a known column list would silently discard it. A row is included only when its `_field` is
     * non-empty, keeping the record table's ~200 unconfigured columns out of the blob, and absent
     * inputs read as '' because an unchecked checkbox posts nothing and the blade omits some.
     */
    public static function fromRequest(array $input): array
    {
        $fields = [];

        foreach (array_keys($input) as $name) {
            // `_field` is the sentinel because it is rendered on every row and is also the
            // value that decides inclusion.
            if (!str_ends_with($name, '_field')) {
                continue;
            }

            $type = substr($name, 0, -strlen('_field'));

            // ...but `_field` alone cannot identify a row: `$request->all()` merges the QUERY
            // STRING, and four other screens have inputs ending that way, so
            // `PUT /types/5?orderby_field=x` would silently write a junk row into the schema.
            // `{type}_orderby` is the discriminator -- the grid renders one per row, and nothing
            // in this repo or the five mounted tenants ends that way otherwise.
            if (!array_key_exists($type.'_orderby', $input)) {
                continue;
            }

            if (!strlen((string) ($input[$name] ?? ''))) {
                continue;
            }

            $field = ['type' => $type];
            foreach (self::FIELDS_INPUTS as $attribute => $suffix) {
                $value = $input[$type.$suffix] ?? '';
                // Guards against an array arriving from a repeated input name: every
                // parameter is a scalar, and a stray array would fatal in implode().
                $field[$attribute] = is_scalar($value) ? (string) $value : '';
            }

            $fields[] = $field;
        }

        return $fields;
    }
}
