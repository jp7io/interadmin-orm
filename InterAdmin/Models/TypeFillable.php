<?php

namespace InterAdmin\Models;

class TypeFillable
{
    public const FILLABLE = [
        'type_id_string',
        'id_slug',
        'model_type_id',
        'parent_type_id',
        'redirect_type_id',
        'name',
        'name_en',
        'description',
        'class',
        'class_type',
        'icon',
        'template',
        'editpage',
        'template_insert',
        'table_name',
        'trigger_function',
        'fields',
        // The Botões block on the Filhos tab. A name here that no `types` table carries is
        // dropped on save in silence, so the list is the schema's and not the form's: the `_4`
        // pair exists on ci and the test tenant but not on jp7, and the tab only renders a
        // button whose column the tenant actually has.
        'files_1',
        'files_1_help',
        'files_2',
        'files_2_help',
        'files_3',
        'files_3_help',
        'files_4',
        'files_4_help',
        'links',
        'links_help',
        'children',
        'visible',
        'language',
        'menu',
        'search',
        'restricted',
        'admin',
        'edit',
        'single',
        'versions',
        'hits',
        'tags',
        'tags_list',
        'tags_type',
        'tags_records',
        'publish_type',
        'template_view',
        'layout',
        'layout_records',
        'position',
        'log',
        'inherited',
        'xtra_disabledfields',
        'xtra_disabledchildren',
    ];

    /**
     * The subset stored as a tinyint(1) boolean since 2026_09_03_000000.
     *
     * `template_view` is deliberately absent -- under `interadmin_ci` it is a varchar holding
     * either the flag or the name of a template, so it is a flag only half the time and
     * TypeController resolves it separately.
     */
    public const FLAGS = [
        'visible',
        'language',
        'menu',
        'search',
        'restricted',
        'admin',
        'edit',
        'single',
        'versions',
        'hits',
        'tags',
        'tags_list',
        'tags_type',
        'tags_records',
        'publish_type',
    ];
}
