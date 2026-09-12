<?php

namespace InterAdmin\Models;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use InterAdmin\Models\TypeFillable;
use InterAdmin\Models\Field;
use Illuminate\Routing\Route;
use Jp7\InterAdmin\Field\TypeInterface;
use Jp7\InterAdmin\Schema\ChildDeclarations;
use Jp7\InterAdmin\Schema\FieldDefinitions;
use Jp7\InterAdmin\Schema\TypeCache;
use Jp7\InterAdmin\Type as OrmType;
use Jp7\InterAdmin\TypeClassMap;
use Jp7\Laravel\RouterFacade;
use BadMethodCallException;
use InvalidArgumentException;
use ReflectionMethod;

/**
 * @property int $type_id
 * @property string $table_name
 * @property string $fields
 * @property string $model_type_id
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property string $name
 * @property string $icon
 * @property string $log
 * @property int $parent_type_id
 * @property int $language
 * @property string $children
 * @property string $files_1
 * @property string $files_2
 * @property string $template_view
 * @property string $template_insert
 * @property string $editpage
 * @property ?string $trigger_function
 * @property string $class
 * @property string $class_type
 * @property string $xtra_disabledfields
 * @property string $xtra_disabledchildren
 * @property int $visible
 * @property int $admin
 * @property int $tags
 * @property int $tags_list
 * @property int $versions
 * @property int $hits
 * @property int $single
 * @property int $position
 * @property string $description
 * @property string $inherited
 * @property string $template
 * @property int $edit
 * @property int $layout
 * @property int $layout_records
 * @method static static create(array $attributes = [])
 * @method static \Illuminate\Database\Eloquent\Builder where(mixed $column, mixed $operator = null, mixed $value = null)
 * @method static \Illuminate\Database\Eloquent\Builder published()
 */
class Type extends Model implements TypeInterface
{
    /** The one store both Types write, @see TypeCache::TAG. */
    const CACHE_TAG = TypeCache::TAG;

    const CACHE_TTL = TypeCache::TTL;

    /**
     * ⚠ `types` has no created_at and never had a date_insert, so this cannot go the way UPDATED_AT
     * did: dropped, Eloquent would write its own `created_at` on every INSERT and 42S22.
     */
    const CREATED_AT = 'updated_at';

    /** The actions getRoute() will resolve, as the ORM's own getRoute() lists them. */
    private const ROUTE_ACTIONS = ['index', 'show', 'create', 'store', 'update', 'destroy', 'edit'];

    protected $table = 'types';
    protected $primaryKey = 'type_id';

    // No SoftDeletes trait: see the note in TypeController::destroy().
    protected $casts = ['deleted_at' => 'datetime'];

    private ?self $parentType = null;

    /** The record records() is scoped to, set by setParent(). Never the parent TYPE. */
    private ?Record $parentRecord = null;

    /** @var array<string, ?static> The request-scoped identity map find() answers from. */
    private static array $instances = [];

    /**
     * Memo over the class map, as Record's is. ⚠ array_key_exists, never ??=: null IS the answer
     * for every type ci binds today, and ??= would retry the autoload once per call.
     * @var array<int, ?class-string<self>>
     */
    private static array $boundClasses = [];

    /**
     * ⚠ The ORM's memo is PERSISTENT where this is per-request: `getInstance()` reads its row
     * from a `_tag_type` cache entry, so a warm store answers with ZERO queries. Not copied --
     * find() also loads the row update() and destroy() WRITE, and a model hydrated from a stale
     * cache computes isDirty() against stale originals, i.e. it writes the wrong columns.
     * @return static|null The single row; an array of ids answers an undeclared Collection.
     */
    public static function find(mixed $id, array $columns = ['*']): mixed
    {
        // A partial row must never answer a later whole-row read, and neither it nor an array
        // of ids is the single row this map keys. Declaring the Collection in the return type
        // is what makes every property read off find() an error, so it is left out.
        if ($columns !== ['*'] || is_array($id) || $id instanceof Arrayable) {
            return static::query()->find($id, $columns);
        }

        // Keyed by CLASS as well: find() returns `static`, so one map across two subclasses
        // would hand back whichever asked first.
        $key = static::class.':'.$id;

        // ⚠ array_key_exists, never ??=: a type_id naming no row would re-query on every call.
        if (!array_key_exists($key, self::$instances)) {
            self::$instances[$key] = static::query()->find($id);
        }

        return self::$instances[$key];
    }

    /**
     * Through find(), so ONE door fills the map: a row loaded here and the same row loaded
     * there must not be two objects, or the reader's is stale the moment the writer saves.
     */
    public static function findOrFail(mixed $id, array $columns = ['*']): static
    {
        return static::find($id, $columns)
            ?? throw (new ModelNotFoundException)->setModel(static::class, [$id]);
    }

    /** Empties the identity map. Called on every app boot, which is the request boundary. */
    public static function forgetInstances(): void
    {
        self::$instances = [];
    }

    /**
     * Loads many rows in ONE query, INTO the identity map, and hands them back keyed by id.
     * ⚠ A bulk load that skips the map is worse than no bulk load: the caller holds the rows
     * while every collaborator resolving the same type through find() queries it again -- 33
     * extra on one ci search page, measured. Misses are remembered too, as find() does.
     * @param array<int, int|string> $ids
     * @return array<int, static>
     */
    public static function prime(array $ids): array
    {
        $ids = array_unique(array_filter($ids));
        $wanted = array_filter($ids, fn ($id) => !array_key_exists(static::class.':'.$id, self::$instances));

        // whereKey, not whereIn: PHPStan models the whereIn forward as answering the QUERY
        // builder, so the chain reads as stdClass rows and no model. Runtime is fine either way.
        if ($wanted) {
            foreach (static::query()->whereKey($wanted)->get() as $row) {
                self::$instances[static::class.':'.$row->getKey()] = $row;
            }
        }

        $found = [];

        foreach ($ids as $id) {
            $key = static::class.':'.$id;
            self::$instances[$key] ??= null;

            if (self::$instances[$key]) {
                $found[(int) $id] = self::$instances[$key];
            }
        }

        return $found;
    }

    /**
     * The class `types.class_type` binds this type to, or null when it binds none usable.
     * ⚠ A binding on the ORM's tree counts as NO binding, exactly as on Record: all 591 of
     * ci's extend the ORM's Type, and one name cannot serve both object models.
     * @return class-string<self>|null
     */
    public static function boundClass(?int $typeId): ?string
    {
        if (!$typeId) {
            return null;
        }

        if (!array_key_exists($typeId, self::$boundClasses)) {
            self::$boundClasses[$typeId] = self::resolveBoundClass($typeId);
        }

        return self::$boundClasses[$typeId];
    }

    /** @return class-string<self>|null */
    private static function resolveBoundClass(int $typeId): ?string
    {
        $class = TypeClassMap::getInstance()->getClass($typeId);

        // is_subclass_of() AUTOLOADS, which is what declares the generated markers. Record's
        // twin carries why neither class_exists() nor isDeclarable() adds anything to it.
        return $class && is_subclass_of($class, self::class) ? $class : null;
    }

    public static function forgetBoundClasses(): void
    {
        self::$boundClasses = [];
    }

    /**
     * Hydration, and the ONLY hook: `types.type_id` is the primary key, so a blank template
     * names no type and every door into a bound class is a ROW.
     * ⚠ No global scope and no retabling either, where Record carries both: the ORM's
     * `Type::query()` takes no provider, so a bound class searches every type there.
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $attributes = (array) $attributes;
        $class = static::boundClass((int) ($attributes['type_id'] ?? 0));

        // The bound instance resolves to ITSELF, which is what ends the recursion.
        return $class === null || $class === static::class
            ? parent::newFromBuilder($attributes, $connection)
            : (new $class)->newFromBuilder($attributes, $connection);
    }

    /**
     * The numeric columns of the types table, from the schema.
     *
     * ⚠ Asked, never listed. `model_type_id` is smallint on ci and varchar on the seeded tenant,
     * where it holds a Box model NAME - a list would coerce that name to 0 and unbind every box.
     */
    public static function numericColumns(): array
    {
        return Cache::remember('numeric-columns,types', self::CACHE_TTL, function () {
            $numeric = [];
            foreach ((new self())->getConnection()->getSchemaBuilder()->getColumns('types') as $column) {
                if (in_array($column['type_name'] ?? '', self::NUMERIC_TYPES, true)) {
                    $numeric[] = $column['name'];
                }
            }

            return $numeric;
        });
    }

    private const NUMERIC_TYPES = ['tinyint', 'smallint', 'mediumint', 'int', 'bigint'];

    /** What a type takes from its Modelo, in the ORM's own order. */
    private const INHERITED_COLUMNS = [
        'class', 'class_type', 'icon', 'layout', 'layout_records', 'table_name',
        'template', 'children', 'fields', 'language', 'edit', 'single', 'trigger_function',
        'editpage', 'template_view', 'template_insert', 'tags_list', 'hits', 'description',
        'xtra_disabledfields', 'xtra_disabledchildren', 'files_1',
    ];

    /** Copied whatever the type holds, where the rest defer to a value the type already has. */
    private const COPIED_COLUMNS = ['children', 'fields'];

    protected $fillable = TypeFillable::FILLABLE;

    const DEFAULT_FIELDS = [
        'varchar_key' => [
            'order' => 1,
            'type' => 'varchar_key',
            'name' => 'Título'
        ],
        'int_key' => [
            'order' => 2,
            'type' => 'int_key',
            'name' => 'Ordem',
            'orderby' => '1',
        ],
        'bool_key' => [
            'order' => 3,
            'type' => 'bool_key',
            'name' => 'Mostrar',
            'xtra' => 'S'
        ],
    ];

    /**
     * The only thing keeping system columns out of the fields editor, which is what `fields`
     * is rebuilt from on save -- so a system column rendered as a blank row is one typed name
     * away from becoming a field definition.
     */
    private const FIELD_COLUMN_PREFIXES = [
        'bool_', 'date_', 'file_', 'float_', 'int_', 'password_',
        'select_', 'special_', 'text_', 'time_', 'tit_', 'varchar_',
    ];

    /**
     * `_text` alone, and the other three retired themselves when the system dates left the `date_`
     * prefix: `created_at`, `updated_at` and `publish_at` carry no field prefix at all now, so the
     * first test already rejects them. `_text` is not a date - it is the `file_*_text` twin.
     */
    private const SYSTEM_COLUMN_SUFFIXES = ['_text'];

    /** No column backs these, so the schema cannot discover them: they are offered by count. */
    private const VIRTUAL_FIELD_PREFIXES = ['tit_', 'func_'];

    private const VIRTUAL_FIELD_COUNT = 11;

    /**
     * MySQL's fingerprint of the whole table, shown on the Info screen.
     *
     * ⚠ `config('dbPrefix')` and not the connection's, because CHECKSUM TABLE goes out as a raw
     * statement and the builder never gets to prepend one.
     */
    public static function checksum(): string
    {
        $rows = DB::select('CHECKSUM TABLE '.config('dbPrefix').'_types');

        return (string) $rows[0]->Checksum;
    }

    /**
     * A blank record of this type on its own table, a custom-table type's ids being per-table.
     * ⚠ The type BEFORE any newQuery(): the query's alias layer reads it off the instance, and a
     * template carries no row, so without it a `where('<alias>')` reaches a column of that name.
     * ⚠ The type's BOUND class where it has one, or a tenant scope called on records() finds no method.
     */
    public function recordTemplate(): Record
    {
        $class = Record::boundClass($this->type_id) ?? Record::class;
        $instance = (new $class)->setTable($this->recordsTable());
        $instance->setRawAttributes(['type_id' => $this->type_id]);

        return $instance;
    }

    public function records($parent_type_id = null, $parent_id = null)
    {
        $relation = $this->everyRecord();

        if ($this->parentRecord) {
            $parent_type_id ??= $this->parentRecord->type_id;
            $parent_id ??= $this->parentRecord->id;

            // ⚠ An id-less parent has no children, which the ORM spells `parent_id = NULL` --
            // matching nothing. Left unconstrained here it would hand back every record of the
            // type, so it is refused rather than translated into a wrong answer.
            if (!$parent_id) {
                throw new BadMethodCallException('setParent() needs a saved record, type '.$this->type_id);
            }
        }

        if ($parent_type_id && $parent_id) {
            $relation->where('parent_type_id', $parent_type_id)->where('parent_id', $parent_id);
        }

        return $relation;
    }

    /** Every record of this type, whatever setParent() scoped: the relation records() narrows. */
    private function everyRecord(): HasMany
    {
        $instance = $this->recordTemplate();

        return new HasMany($instance->newQuery(), $this, $instance->getTable().'.type_id', $this->getKeyName());
    }

    /**
     * Scopes records() to one parent RECORD, as the ORM's setParent() does. ⚠ NOT getParent()'s
     * pair: that walks the type TREE, where the ORM overloads one `_parent` for both.
     */
    public function setParent(?Record $parent = null): void
    {
        $this->parentRecord = $parent;
    }

    /**
     * The record of this type with this id. `fields_alias` is a no-op, a record answering by
     * alias whichever columns it selected. ⚠ `class` THROWS rather than being ignored: its two
     * values name ORM-tree classes, so ignoring it hands back a differently-classed object.
     *
     * @param array{fields?: string|array<int, string>, fields_alias?: bool} $options
     */
    public function findById(mixed $id, array $options = []): ?Record
    {
        if (isset($options['class'])) {
            throw new BadMethodCallException('findById() resolves the bound class on hydration; drop `class`.');
        }

        return $this->records()->where('id', (int) $id)->first((array) ($options['fields'] ?? '*'));
    }

    /**
     * Type and id 0 alone -- `Record::getInstance(0, [], $type)`. ⚠ Not buildRecord(): an insert
     * hands this to a trigger_function as the row BEFORE the save, where a `publish` default reads
     * as already published. Through newInstance(), so a reparented tenant class keeps its overrides.
     */
    public function blankRecord(): Record
    {
        $record = $this->recordTemplate()->newInstance();
        $record->setRawAttributes(['type_id' => $this->type_id, 'id' => 0]);

        return $record;
    }

    /**
     * A record as an INSERT form binds it -- the ORM's `records()->build()`, whose defaults ARE
     * what a create screen renders, `id` 0 included: SavePipeline and the screen both test for it.
     * forceFill, because a model with no $fillable is guarded and fill() would throw.
     */
    public function buildRecord(array $attributes = []): Record
    {
        $record = $this->blankRecord();

        if ($visible = $this->getFieldAliases()['bool_key'] ?? null) {
            $record->{$visible} = true;
        }

        $record->publish_at = date('c');
        $record->created_at = date('c');
        $record->publish = true;
        $record->log = '';

        // setParent()'s record reaches a BUILT row too, as it does records(): the box editor
        // builds each box off a type already scoped to its column, and an unparented one is
        // stored where no listing looks for it.
        if ($this->parentRecord) {
            $record->parent_id = $this->parentRecord->id;
            $record->parent_type_id = $this->parentRecord->type_id;
        }

        return $record->forceFill($attributes);
    }

    /** The type's display name, which on axis C is a translated column and everywhere else `name`. */
    public function getName(): string
    {
        return (string) ($this->{'name'.Lang::get('interadmin.suffix')} ?: $this->name);
    }

    /** ⚠ Memoised: find() is a query, and getAncestors() walks this once per level. */
    public function getParent(): ?self
    {
        if (!$this->parent_type_id) {
            return null;
        }

        return $this->parentType ??= Type::find($this->parent_type_id);
    }

    /**
     * The child TYPES of this one, which is the question the ORM's children() answered.
     * @return HasMany<self, $this>
     */
    public function childTypes(): HasMany
    {
        return $this->hasMany(self::class, 'parent_type_id', 'type_id');
    }

    /**
     * The child types as the ORM's children() listed them: `position, name` order, and only the
     * visible, undeleted ones while the published switch is on. ⚠ Not named children(): that is
     * a column here, and a relation of the same name answers whichever a SELECT left out.
     * @return HasMany<self, $this>
     */
    public function listedChildTypes(): HasMany
    {
        $relation = $this->childTypes()->orderBy('position')->orderBy('name');

        if (Record::isPublishedFiltersEnabled()) {
            $relation->where('visible', 1)->whereNull('deleted_at');
        }

        return $relation;
    }

    /** classes' Routable::getChildrenMenu(), which the ORM Type used. ⚠ Untyped: ci's Ci\Type overrides it. */
    public function getChildrenMenu()
    {
        return $this->listedChildTypes()->where('menu', true)->get();
    }

    /**
     * The types built on this one as their Modelo, keyed by id, and THIS type last, as the ORM's:
     * so modelRecords() spans the model's own rows too. Visible and undeleted alone while the
     * published switch is on, which its type queries honour.
     * @return array<int, self>
     */
    public function getTypesUsingThisModel(): array
    {
        $query = self::query()->where('model_type_id', $this->type_id)->orderBy('type_id');

        if (Record::isPublishedFiltersEnabled()) {
            $query->where('visible', 1)->whereNull('deleted_at');
        }

        $types = $query->get()->keyBy('type_id')->all();
        $types[$this->type_id] = $this;

        return $types;
    }

    /**
     * Every record of those types in one query, the ORM's TypelessQuery: on THIS type's template,
     * so its tenant scopes answer, with the bound class's single-type scope lifted.
     */
    public function modelRecords(): Builder
    {
        $template = $this->recordTemplate();

        return $template->newQuery()
            ->withoutGlobalScope(Record::BOUND_TYPE_SCOPE)
            ->whereIn($template->getTable().'.type_id', array_keys($this->getTypesUsingThisModel()));
    }

    /** A type tags a record as itself, `id` 0 marking the whole type rather than a record of it. */
    public function getTagFilters(): array
    {
        return ['type_id' => $this->type_id, 'id' => 0];
    }

    /**
     * The first child type built on this model. ⚠ `0` is the column's default, so a falsy model
     * would match every type declaring none -- the guard InterAdminTipo::getChildrenByModel()
     * spelled as a second `!= '0'` clause.
     */
    public function getFirstChildByModel(string $model): ?self
    {
        if (!$model || $model === '0') {
            return null;
        }

        return $this->childTypes()
            ->where('model_type_id', $model)
            ->whereNull('deleted_at')
            ->orderBy('position')->orderBy('name')
            ->first();
    }

    /**
     * Recopies every value this type's Modelo decides onto it, and saves. ⚠ Sync and save are
     * ONE unit: the ORM spelled them syncInheritance() + saveRaw() and no caller ever ran the
     * first alone. Timestamps stay off because refreshing the cache is not an edit -- with them
     * on, one visit backdates every type's Modificado to today.
     */
    public function syncInheritance(): void
    {
        foreach (array_filter(explode(',', (string) $this->inherited)) as $column) {
            $this->$column = $this->emptyValueFor($column);
        }

        $inherited = [];
        $model = $this->modelType();

        foreach ($model ? self::INHERITED_COLUMNS : [] as $column) {
            if (($model->$column && !$this->$column) || in_array($column, self::COPIED_COLUMNS, true)) {
                $inherited[] = $column;
                $this->$column = $model->$column ?? $this->emptyValueFor($column);
            }
        }

        $this->inherited = implode(',', $inherited);

        $this->timestamps = false;
        $this->save();
        $this->timestamps = true;
    }

    /**
     * ⚠ 0 on a numeric column, never '': the flags are tinyint since 2026_09_03 and the app's
     * connection is strict, where '' into one is ERROR 1366. The ORM coerced the same pair in
     * its write layer, which is the half an Eloquent save does not have.
     */
    private function emptyValueFor(string $column): int|string
    {
        return in_array($column, self::numericColumns(), true) ? 0 : '';
    }

    /**
     * The child type declared under this name, or null where this type declares none.
     * ⚠ The DECLARED name (`children`), not the child's own -- `Dados Pessoais` is the key.
     */
    public function getInterAdminsChildrenType(string $name): ?self
    {
        return Type::find($this->childTypeIds()[$name] ?? null);
    }

    /**
     * One `children` declaration, found by the type it points at rather than by its key.
     * @return ?array<string, mixed>
     */
    public function getInterAdminsChildrenData($typeId): ?array
    {
        foreach ($this->getInterAdminsChildren() as $declaration) {
            if ($declaration['type_id'] == $typeId) {
                return $declaration;
            }
        }

        return null;
    }

    /**
     * ⚠ Through Record's predicate, never a second copy: its types branch keys off the PREFIXED
     * table name, so a bare `types` would be filtered on the records calendar.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        $table = $this->getConnection()->getTablePrefix().$this->getTable();

        // Anchored, not rtrim(): every predicate ends in ' AND ' for an ORM caller concatenating
        // it, and a character list would eat a trailing D, N or A.
        $query->whereRaw(preg_replace('/ AND $/', '', (string) Record::getPublishedFilters($table, $table)));
    }

    /** An UNSAVED child type on this model, which is what the caller then names and saves. */
    public function createChild(string $model): self
    {
        $child = new self();
        $child->parent_type_id = $this->type_id;
        $child->model_type_id = $model;
        $child->visible = true;

        return $child;
    }

    /**
     * The section path this type hangs off, outermost first.
     * ⚠ Uncapped, exactly as the ORM has it: a cycle in `parent_type_id` hangs the request on
     * either object, and capping it here would be a behaviour change wearing a port's clothes.
     * @return array<int, self>
     */
    public function getAncestors(): array
    {
        $parents = [];
        $parent = $this;

        while (($parent = $parent->getParent()) && $parent->type_id) {
            array_unshift($parents, $parent);
        }

        return $parents;
    }

    /** This table, prefixed -- what the ORM's getTableName() answers. */
    public function getTableName(): string
    {
        return $this->getConnection()->getTablePrefix().$this->getTable();
    }

    /**
     * The named route this type's records answer on. ⚠ A type the tenant's router does not map
     * THROWS (RouteException) where an action the mapped prefix lacks answers null -- the ORM's
     * two outcomes, kept because RecordUrl tests only the second. Every type throws in the admin,
     * nothing here registering a map.
     */
    public function getRoute(string $action = 'index'): ?Route
    {
        if (!in_array($action, self::ROUTE_ACTIONS, true)) {
            throw new BadMethodCallException(
                'Invalid action "'.$action.'", valid actions: '.implode(', ', self::ROUTE_ACTIONS)
            );
        }

        return RouterFacade::getRouteByTypeId($this->type_id, $action);
    }

    /** Where this type's RECORDS live, prefixed. The ORM spells it getInterAdminsTableName(). */
    public function getInterAdminsTableName(): string
    {
        return $this->getConnection()->getTablePrefix().$this->recordsTable();
    }

    /**
     * The records table UNPREFIXED, for setTable().
     * ⚠ The language prefix rides along: axis C puts a flagged type's records in `<lang>records`,
     * and records() ignored it until now -- inert while nothing calls App::setLocale(), and a
     * silently different table the day something does.
     */
    public function recordsTable(): string
    {
        return $this->tableLang().($this->table_name ?: 'records');
    }

    /** Where this type's ATTACHMENTS live, prefixed -- one table for every type, unlike records. */
    public function getFilesTableName(): string
    {
        return $this->getConnection()->getTablePrefix().$this->tableLang().'files';
    }

    /** The `<lang>` an axis-C flagged type's tables carry, empty everywhere else. */
    private function tableLang(): string
    {
        return $this->language ? Lang::get('interadmin.prefix') : '';
    }

    /**
     * The ORDER BY the ORM puts on every non-aggregate query of this type's records, which is
     * what `first()` means there. ⚠ APPLIED BY THE CALLER here, not by records(): an implicit
     * ORDER BY makes a flipped call site answer a different row for no visible reason.
     * ⚠ ONE cache entry with the ORM, its own: both sides derive the same string off the rows.
     */
    public function recordsOrder(): string
    {
        return Cache::tag(self::CACHE_TAG)->remember(
            'order,,'.$this->type_id,
            self::CACHE_TTL,
            function () {
                $order = [];

                foreach ($this->fieldDefinitions() as $column => $row) {
                    // ⚠ `func_` only: a `tit_` row is deliberately NOT excluded, as the ORM has
                    // it, so an `orderby` on one is a 42S22 on both objects rather than on one.
                    if (!$row['orderby'] || str_starts_with($column, 'func_')) {
                        continue;
                    }
                    $order[$row['orderby']] = $column.($row['orderby'] < 0 ? ' DESC' : '');
                }

                ksort($order);
                $order[] = 'publish_at DESC';

                return implode(',', $order);
            }
        );
    }

    /**
     * What a RECORD of this type is described by, keyed by column, in form order -- ⚠ NOT
     * editorFields(), which pads every schema column and derives nothing.
     * ⚠ Its OWN cache entry, not the ORM's: selectTypeId() says why.
     * @return array<string, array<string, mixed>>
     */
    public function fieldDefinitions(): array
    {
        // ⚠ An id-less type -- InterMail's Sub-Itens form builds one per part -- has no row to key
        // an entry by, and `eloquent_field_definitions,,` would hand each the first one's fields.
        if (!$this->type_id) {
            return $this->decodeFieldDefinitions();
        }

        return Cache::tag(self::CACHE_TAG)->remember(
            'eloquent_field_definitions,,'.$this->type_id,
            self::CACHE_TTL,
            fn () => $this->decodeFieldDefinitions()
        );
    }

    /** @return array<string, array<string, mixed>> */
    private function decodeFieldDefinitions(): array
    {
        $rows = FieldDefinitions::decode($this->fields);
        // A select_ names the related TYPE, so the resolver is a query -- one that runs
        // for 4 of ci's 10,391 rows, the rest carrying a name_id or a label already.
        $aliases = FieldDefinitions::aliases(
            $rows,
            fn ($typeId) => (string) Type::find($typeId)?->name
        );

        $definitions = [];

        foreach ($rows as $i => $row) {
            $column = $row['type'];
            // `order` is the row's POSITION, which is what the record form orders by.
            $definitions[$column] = ['order' => $i + 1] + $row;
            $definitions[$column]['name_id'] = $aliases[$column];
        }

        return $definitions;
    }

    /**
     * What the FIELD LAYER reads: fieldDefinitions() with a `select_`'s `name` resolved to its type,
     * the shape Jp7\InterAdmin\Type::getFields() answers. ⚠ A type that is gone resolves to a blank
     * one carrying its id, as getInstance() had it: a null there is a select that throws on render.
     * @return array<string, array<string, mixed>>
     */
    public function getFields(): array
    {
        $fields = $this->fieldDefinitions();

        foreach ($fields as $column => $row) {
            if (strpos($column, 'select_') === 0 && $row['name'] != 'all') {
                $fields[$column]['name'] = self::forFieldLayer($row['name']) ?? self::blank($row['name']);
            }
        }

        return $fields;
    }

    /**
     * records() as the ORM's query hands them out, for jp7io/classes' select fields: RecordQuery
     * keeps the identity columns on any SELECT, and this type's ORDER BY lands after the caller's.
     * ⚠ At EXECUTION, through beforeQuery(): the field layer adds its own ORDER BY after this returns.
     * ⚠ NEVER setParent()'s scope: find()'s identity map shares this object, getInstance() did not.
     */
    public function selectableRecords(): HasMany
    {
        $relation = $this->everyRecord();
        $order = $this->recordsOrder();

        $relation->getQuery()->getQuery()->beforeQuery(fn (QueryBuilder $query) => $query->orderByRaw($order));

        return $relation;
    }

    /** A cached selectableRecords() row, its bound class resolved as on any hydration. */
    public function recordFromAttributes(array $attributes): Record
    {
        return $this->recordTemplate()->newFromBuilder($attributes);
    }

    /** The type a select renders its options off, from an id or from either Type object. */
    public static function forFieldLayer(mixed $type): ?self
    {
        if ($type instanceof self) {
            return $type;
        }

        $id = $type instanceof TypeInterface ? $type->getKey() : $type;

        return is_numeric($id) ? Type::find((int) $id) : null;
    }

    /** A type that is gone, as the ORM's getInstance() answers it: its id and nothing else. */
    public static function blank(mixed $id): self
    {
        $type = new self;
        $type->setRawAttributes(['type_id' => $id]);

        return $type;
    }

    /**
     * The type a `select_` points at, or null where it names none. ⚠ The ORM's getFields() keeps
     * a whole Type OBJECT in the row's `name` and this keeps the id: two CLASSES under one
     * question cannot share a cache entry, and a caller handed the wrong one calls ->records() on
     * the wrong object model.
     */
    public function selectTypeId(string $column): ?int
    {
        $row = $this->fieldDefinitions()[$column] ?? null;
        $name = $row['name'] ?? '';

        // ⚠ ONE guard, not two: `all` -- the value a select_ carries when it points at every type
        // rather than one -- is already non-numeric, so testing it separately reads as a second
        // rule and is a branch no input can reach.
        if (!$row || strpos($column, 'select_') !== 0 || !is_numeric($name)) {
            return null;
        }

        return (int) $name;
    }

    /**
     * The type one getFields() row points at, or null where it names none. ⚠ Where the ORM keeps
     * a whole Type OBJECT in the row's `name`, this keeps the id and resolves it -- selectTypeId()
     * says why the two cannot share a cache entry. A tenant Type subclass OVERRIDES this.
     *
     * @param array<string, mixed> $field
     */
    public function getFieldType(array $field): ?self
    {
        $name = $field['name'] ?? null;

        // Type::, never self:: -- a subclass inheriting this would take another type's row as itself.
        return $name === 'all' ? new self : (is_numeric($name) ? Type::find((int) $name) : null);
    }

    /**
     * The ORM's answer while this class answers no `special_` itself, as every ci type does:
     * FuncField::searchOptions() reaches it for one, and ci's 419 are overrides on ORM-tree
     * classes. A class overriding getFieldType() answers here, as graphql and the field layer read it.
     * @return array<string, mixed>
     */
    public function getRelationshipData(string $relationship): array
    {
        if (!$this->answersSpecials()) {
            return OrmType::getInstance($this->type_id)->getRelationshipData($relationship);
        }

        if ($shape = $this->relationships()[$relationship] ?? null) {
            return $this->relationshipData(
                'select', $relationship, $shape['type_id'], $shape['multi'], $shape['holds_type']
            );
        }

        if ($childTypeId = $this->childTypeIds()[ucfirst($relationship)] ?? null) {
            return $this->relationshipData('children', $relationship, $childTypeId, true, false);
        }

        throw new InvalidArgumentException('Unknown relationship: '.$relationship);
    }

    /** The ORM's shape but for its `query`, which nothing reading this tree asks for. */
    private function relationshipData(string $kind, string $name, ?int $typeId, bool $multi, bool $hasType): array
    {
        return [
            'type' => $kind,
            'tipo' => ($typeId ? Type::find($typeId) : null) ?? self::blank((int) $typeId),
            'name' => $name,
            'alias' => true,
            'multi' => $multi,
            'has_type' => $hasType,
        ];
    }

    /**
     * What a RECORD of this type answers to as a RELATION: its `select_` fields, keyed by the
     * alias with its `_id`/`_ids` suffix off, so `select_3` aliased `status_id` is `status`.
     * ⚠ A `special_` joins them only where this class overrides getFieldType(): the type one
     * points at is that override, and ci's 419 live on ORM-tree classes this model cannot see.
     * @return array<string, array{type_id: ?int, multi: bool, holds_type: bool}>
     */
    public function relationships(): array
    {
        $relationships = [];

        foreach ($this->fieldDefinitions() as $column => $row) {
            $shape = strpos($column, 'select_') === 0
                ? $this->selectRelationship($column, $row)
                : $this->specialRelationship($column, $row);

            if ($shape) {
                $relationships[substr($row['name_id'], 0, $shape['multi'] ? -4 : -3)] = $shape;
            }
        }

        return $relationships;
    }

    /** @return array{type_id: ?int, multi: bool, holds_type: bool} */
    private function selectRelationship(string $column, array $row): array
    {
        return [
            'type_id' => $this->selectTypeId($column),
            'multi' => strpos($column, 'select_multi_') === 0,
            // The xtras under which the stored id is a TYPE's rather than a record's.
            'holds_type' => in_array($row['xtra'], FieldDefinitions::getSelectTypeXtras(), true),
        ];
    }

    /** @return array{type_id: ?int, multi: bool, holds_type: bool}|null */
    private function specialRelationship(string $column, array $row): ?array
    {
        $xtra = $row['xtra'] ?? '';

        if (strpos($column, 'special_') !== 0 || !$xtra || !$this->answersSpecials()) {
            return null;
        }

        $type = $this->getFieldType($row);

        return $type ? [
            'type_id' => ((int) $type->getKey()) ?: null,
            'multi' => in_array($xtra, FieldDefinitions::getSpecialMultiXtras(), true),
            'holds_type' => in_array($xtra, FieldDefinitions::getSpecialTypeXtras(), true),
        ] : null;
    }

    /**
     * ⚠ The override, never the class: a reparented class inheriting the base getFieldType() would
     * resolve a special_'s numeric `name`, where the ORM's base resolves no special_ at all.
     */
    private function answersSpecials(): bool
    {
        return (new ReflectionMethod($this, 'getFieldType'))->getDeclaringClass()->getName() !== self::class;
    }

    /**
     * The child types a record of this type declares, keyed as the ORM's `_findChild()` reads
     * them -- the studly declared name, so `Dados Pessoais` answers `$record->dadosPessoais()`.
     * @return array<string, int>
     */
    public function childTypeIds(): array
    {
        return array_map(fn (array $child) => (int) $child['type_id'], $this->getInterAdminsChildren());
    }

    /**
     * The same declarations WHOLE, which is the shape `getInterAdminsChildren()` names them in.
     * ⚠ Uncached where the ORM memoises it into `_tag_type`: this is a json_decode, and a second
     * writer of a shared entry is the stale-blob trap increments 10 to 12 paid for twice.
     *
     * @return array<string, array<string, mixed>>
     */
    /**
     * The columns that make up a record's display string: whatever is flagged `combo`, plus the
     * title column itself. @see Record::getStringValue()
     * @return array<int, string>
     */
    public function getComboFieldNames(): array
    {
        return array_keys(array_filter(
            $this->fieldDefinitions(),
            fn (array $row) => (bool) $row['combo'] || $row['type'] === 'varchar_key'
        ));
    }

    public function getInterAdminsChildren(): array
    {
        $children = [];

        foreach (ChildDeclarations::decode($this->children) as $child) {
            $children[Str::studly(to_slug($child['name']))] = $child;
        }

        return $children;
    }

    /**
     * The names a RECORD answers to: fieldDefinitions()' aliases, minus the virtual rows.
     * ⚠ ONE cache entry with the ORM, its own -- shareable where the definitions are not, both
     * sides storing strings. `_db` is empty here, which is the key the ORM writes there too.
     * @return array<string, string>
     */
    public function getFieldAliases(): array
    {
        // The id-less twin of fieldDefinitions()' guard: one `,,` entry would serve every such type.
        if (!$this->type_id) {
            return $this->deriveFieldAliases();
        }

        return Cache::tag(self::CACHE_TAG)->remember(
            'field_definitions_alias,,'.$this->type_id,
            self::CACHE_TTL,
            fn () => $this->deriveFieldAliases()
        );
    }

    /** @return array<string, string> */
    private function deriveFieldAliases(): array
    {
        $aliases = [];

        foreach ($this->fieldDefinitions() as $column => $row) {
            if (!FieldDefinitions::isVirtualField($column)) {
                $aliases[$column] = $row['name_id'];
            }
        }

        return $aliases;
    }

    /**
     * The columns this type's `fields` CONFIGURES, virtual rows out.
     * ⚠ Off getFieldAliases(), never getFields(): that one is the EDITOR's view and pads every
     * schema column, which is 68 names here against the 6 the ORM answers.
     *
     * @return array<int, string>
     */
    public function getFieldNames(): array
    {
        return array_keys($this->getFieldAliases());
    }

    /**
     * What the ORM selects for one of this type's records: its keys, the admin columns and the
     * declared fields. ⚠ Not `*`: a shared table's row carries OTHER types' slots and a save
     * counter, and a copy taking them carries that junk and a `version` it never had.
     * @return list<string>
     */
    public function recordColumns(): array
    {
        return array_values(array_unique(['id', 'type_id', ...Record::ADMIN_COLUMNS, ...$this->getFieldNames()]));
    }

    public function editorFields()
    {
        $model = $this->modelType();
        $disabled = $model ? 'disabled' : null;
        $fieldsBlob = $model ? $model->fields : $this->fields;

        $columns = [];

        foreach ($this->fieldColumns() as $value) {
            $columns[$value] = (object) array_fill_keys([
                ...Field::FIELDS_ATTRIBUTES,
                'disabled',
                // form_permissoes: custom-permission <optgroup> from interadmin.tipos_permissoes.
                // Deferred (not FIELDS_OPTIONS-sourced). See docs plan §2.C.
                'form_permissoes',
            ], null);
            $columns[$value]->type = $value;
            $columns[$value]->disabled = $disabled;
            $columns[$value]->xtraArray = self::xtraOptions($value);
        }

        $configured = [];
        $blank = array_fill_keys(Field::FIELDS_ATTRIBUTES, null);

        foreach (FieldDefinitions::decode($fieldsBlob) as $row) {
            // A row predating a later attribute stores fewer than 16 of them -- name_id was the
            // last added and ci still holds 96 such rows across 20 types. The editor renders every
            // attribute as a property, and the fields-save JS only re-serializes rendered rows, so
            // an unpadded row vanished from the grid and the next save DELETED it.
            $mapped = (object) array_merge($blank, $row);
            $mapped->disabled = $disabled;
            $mapped->form_permissoes = null; // see note above (custom perms; deferred)
            $mapped->xtraArray = self::xtraOptions($mapped->type);
            $configured[$mapped->type] = $mapped;
        }

        // Configured fields first, in the order `fields` stores them; the untouched columns follow
        // in schema order. Not cosmetic: the fields-save JS re-serializes the rows in DOM order
        // (resources/js/pages/type-form-fields.js), and field order IS the
        // record form's field order -- so rendering everything in schema order, as this did,
        // silently reordered every configured type's form on save.
        return $configured + $columns;
    }

    /**
     * The Modelo this type inherits from, or null. The values it hands down are stored on the
     * type as a cache "Refresh the Types Cache" rewrites, which is why the editor renders
     * them read-only: an edit to the copy diverges it, and the next sync discards the edit.
     */
    public function modelType(): ?self
    {
        if (!$this->model_type_id) {
            return null;
        }

        if (is_numeric($this->model_type_id)) {
            return Type::find($this->model_type_id);
        }

        $resolver = self::$templateModelResolver;
        $class = $resolver ? $resolver((string) $this->model_type_id) : null;

        return $class ? new $class : null;
    }

    /** @var (\Closure(string): ?class-string<self>)|null */
    private static ?\Closure $templateModelResolver = null;

    /**
     * Names the class a non-numeric `model_type_id` stands for. The admin app registers its Box
     * templates; this package cannot name them, so without a resolver such a type has no model.
     */
    public static function resolveTemplateModelsUsing(?\Closure $resolver): void
    {
        self::$templateModelResolver = $resolver;
    }

    /**
     * The Modelo at the end of this type's model chain, or this type when it names none.
     * ⚠ Uncapped as the ORM's is, and it can answer a Box\Model\*Type rather than a Type: that
     * is the branch PageContent tests for with `instanceof`.
     */
    public function getModel(): object
    {
        return $this->modelType()?->getModel() ?? $this;
    }

    /**
     * The type's OWN table, not `records`: a custom-table type (ci has 384, all on `intranet`)
     * has its own columns, and reading the generic table offered three that do not exist there
     * while hiding eighteen that do. A `table_name` naming no table (ci has one) yields [].
     */
    private function fieldColumns(): array
    {
        $table = trim((string) $this->table_name) ?: (new Record)->getTable();

        $columns = array_values(array_filter(
            Schema::getColumnListing($table),
            self::isFieldColumn(...)
        ));

        foreach (self::VIRTUAL_FIELD_PREFIXES as $prefix) {
            for ($i = 1; $i <= self::VIRTUAL_FIELD_COUNT; $i++) {
                $columns[] = $prefix.$i;
            }
        }

        return $columns;
    }

    private static function isFieldColumn(string $column): bool
    {
        // Truthy, not !== false: a leading underscore disqualifies as surely as none at all.
        $firstUnderscore = strpos($column, '_');

        return $firstUnderscore
            && in_array(substr($column, 0, $firstUnderscore + 1), self::FIELD_COLUMN_PREFIXES, true)
            && !in_array(substr($column, strrpos($column, '_')), self::SYSTEM_COLUMN_SUFFIXES, true);
    }

    /**
     * The xtra sub-variant options (stored value => label) for a field, sourced from
     * Field::FIELDS_OPTIONS. The field's base type drives the set (varchar_1 -> varchar,
     * bool_key -> char, select_multi_2 -> select_multi); system columns (id, created_at, ...)
     * have no match and return [] so the editor falls back to a plain xtra text input.
     *
     * ⚠ $stored is needed because "no xtra" has TWO spellings in the data -- ci writes '0' for
     * 6,998 of its 10,403 fields and '' for 58 -- so a list offering only '' renders a '0' field
     * with nothing selected, and such a select submits its first option: opening and saving one
     * type would rewrite the field. The default option therefore carries whichever falsy spelling
     * the field already has.
     */
    private static function xtraOptions(string $type): array
    {
        $base = preg_replace('/_(\d+|key)$/', '', $type);
        $options = [];
        foreach (Field::FIELDS_OPTIONS[$base] ?? [] as $key => $def) {
            $options[$key === 'default' ? '' : $key] = __($def[0]);
        }
        return $options;
    }
}
