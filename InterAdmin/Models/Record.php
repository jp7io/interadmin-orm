<?php

namespace InterAdmin\Models;

use DateTimeInterface;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\Relation;
use InterAdmin\Models\Relations\ChildRecords;
use InterAdmin\Models\Relations\SelectMulti;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use InterAdmin\Models\Type;
use InterAdmin\Models\File;
use Illuminate\Routing\Route;
use Jp7\InterAdmin\Field\RecordInterface;
use Jp7\InterAdmin\Record as OrmRecord;
use Jp7\InterAdmin\Schema\PublishedFilterSql;
use Jp7\InterAdmin\Schema\RecordColumns;
use Jp7\InterAdmin\RecordClassMap;
use Jp7\Laravel\RecordUrl;
use Jp7\TryMethod;
use InvalidArgumentException;
use LogicException;
use RuntimeException;
use UnexpectedValueException;
use ReflectionClass;

/**
 * ⚠ SYSTEM columns only. Which `varchar_*`/`bool_*`/`date_*` slots a type declares is DATA, and
 * enumerating those is REFACTORING #44's `bool_6` mistake -- `varchar_key` is named because the
 * framework itself reads it (getName(), Helpers::recordTitle()), not as the start of a list.
 * A UNION type hint (Record\Ancestry) is where an undeclared one stops being a baseline entry
 * and becomes an error, Eloquent's __get() carrying no type PHPStan can read.
 *
 * @property int $id
 * @property int $type_id
 * @property int $parent_id
 * @property int $parent_type_id
 * @property ?string $varchar_key
 * @property int $publish
 * @property int $bool_key
 * @property ?\Illuminate\Support\Carbon $created_at
 * @property ?\Illuminate\Support\Carbon $publish_at
 * @property ?\Illuminate\Support\Carbon $expire_at
 * @property ?\Illuminate\Support\Carbon $deleted_at
 * @property ?string $id_slug
 * @property ?string $log
 * @property string $log_user
 * @property int $version
 */
class Record extends Model implements RecordInterface
{
    use TryMethod;

    /** The global scope narrowing a bound class to its own type's rows. */
    public const BOUND_TYPE_SCOPE = 'interadminBoundType';

    /** The global scope standing in for the ORM's automatic filter while its switch is on. */
    public const PUBLISHED_SCOPE = 'interadminPublished';

    /** The ORM's getInterAdminsAdminAttributes(), rehoused: the columns every record has beside its fields. */
    public const ADMIN_COLUMNS = [
        'id_slug', 'id_string', 'parent_id', 'parent_type_id', 'publish_at', 'created_at', 'expire_at',
        'updated_at', 'log', 'publish', 'deleted_at', 'hits',
    ];

    // Default table for generic types. Custom-table types (e.g. intranet) rebind this
    // per-instance via Type::records() (which setTable()s the type's own table).
    protected $table = 'records';
    protected $primaryKey = 'id';

    /**
     * Request-scoped memo over Models\Type::getFieldAliases(), which holds the cache ENTRY --
     * the ORM's own. A second key over that derivation is increment 16's mistake in a new place.
     *
     * @var array<int, array<string, string>>
     */
    private static array $aliasesByType = [];

    /**
     * Memo over the class map. ⚠ array_key_exists, never ??=: null IS the answer for most types,
     * and ??= would re-resolve it -- an autoload attempt per row rather than one per type.
     * @var array<int, ?class-string<self>>
     */
    private static array $boundClasses = [];

    /**
     * The other direction, per CLASS: what `Ci\Loja::where()` has to scope to.
     * @var array<class-string, ?int>
     */
    private static array $boundTypeIds = [];

    /** The bound type's own records table, so a custom-table class does not query `records`. */
    private static array $boundTables = [];

    private ?Type $typeModel = null;

    /** A subclass naming a class here reads its `file_` columns as that object, as the ORM read every one. */
    protected static ?string $fileFieldClass = null;

    /** A subclass setting this has its queries end in its type's own ORDER BY, as every ORM query did. */
    protected static bool $ordersByType = false;

    /** Read by Model::newEloquentBuilder(), so every record query is one. */
    protected static string $builder = RecordBuilder::class;

    public static function ordersByType(): bool
    {
        return static::$ordersByType;
    }

    public function type()
    {
        return $this->belongsTo(Type::class, 'type_id');
    }

    public function files()
    {
        return $this->hasMany(File::class, 'id');
    }

    /**
     * The tags table's rows for this record, only where no relationship or child claims the name:
     * the ORM's whereHas() asked those first, and InterMail's e-mail products declare Tags.
     */
    public function tags(): Relation
    {
        return $this->fieldRelation('tags') ?? $this->hasMany(Tag::class, 'parent_id', 'id');
    }

    /**
     * Every record table's date columns; a TYPE's own `date_*` slots are merged per row instead.
     * ⚠ `deleted_at` is here and OUT of the ORM's SYSTEM_DATES for the opposite reason: an absent
     * date mutates there to a truthy \Date, so `!$r->deleted_at` reads as deleted. A null cannot.
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return array_fill_keys([...RecordColumns::SYSTEM_DATES, 'deleted_at'], 'datetime');
    }

    /**
     * Hydration is where a row's own date columns become known, and merging the casts HERE rather
     * than computing getCasts() per read keeps it O(row) instead of O(row x attribute).
     */
    public function setRawAttributes(array $attributes, $sync = false)
    {
        parent::setRawAttributes($attributes, $sync);

        return $this->mergeCasts($this->dateCasts(array_keys($attributes)));
    }

    /**
     * ⚠ getFieldAliases(), never Models\Type::getFields(): that is the EDITOR's view and derives
     * nothing, so a map built from it drops every relation alias. Both objects run
     * FieldDefinitions::aliases() over the same rows and share its cache entry.
     * @return array<string, string>
     */
    public function fieldAliases(): array
    {
        if (!$typeId = $this->typeId()) {
            return [];
        }

        // A type_id naming no row answers [], as the ORM's does: an empty `fields` decodes to
        // no rows, and a record of a type that is gone has no aliases rather than an error.
        return self::$aliasesByType[$typeId] ??= $this->typeModel()?->getFieldAliases() ?? [];
    }

    /**
     * The same shape for Type::relationships(), and cleared by the same call: this is the
     * DERIVED map, where Type::find()'s identity map holds the row it derives from.
     *
     * @var array<int, array<string, array<string, mixed>>>
     */
    private static array $relationsByType = [];

    /** @var array<int, array<string, int>> Models\Type::childTypeIds(), memoised as the two above */
    private static array $childrenByType = [];

    public static function forgetTypeDerivations(): void
    {
        self::$aliasesByType = [];
        self::$relationsByType = [];
        self::$childrenByType = [];
    }

    /** ⚠ Guards the falsy id only: Type::find() is the identity map, memoised nulls included. */
    private static function relatedType(?int $typeId): ?Type
    {
        return $typeId ? Type::find($typeId) : null;
    }

    /**
     * ⚠ BOTH of the ORM's arms, where this read the column alone: the row's `type_id`, else the
     * type this CLASS binds, else a throw. A bound blank threw here and answers its type there.
     */
    public function getType(): Type
    {
        return $this->typeModel() ?? throw new UnexpectedValueException(
            'Could not find type_id for record. Class: '.static::class.' - ID: '.$this->id
        );
    }

    /**
     * This record's type as an Eloquent model, memoised.
     * ⚠ Never `$this->type`: a tenant may alias a column to `type`, and getAttribute() would then
     * answer that column instead of the relation. Every internal read here goes through find().
     */
    private function typeModel(): ?Type
    {
        $typeId = $this->typeId();

        return $typeId ? ($this->typeModel ??= Type::find($typeId)) : null;
    }

    /**
     * ⚠ The bound class's own type is the fallback, and eager loading is why: Eloquent builds a
     * relation off a blank `newInstance()`, where `Ci\Loja::with('<alias>')` has no other answer.
     */
    private function typeId(): ?int
    {
        return ((int) ($this->attributes['type_id'] ?? 0)) ?: static::boundTypeId();
    }

    /**
     * The class `types.class` binds this type's records to, or null when it binds none usable.
     * ⚠ A binding on the ORM's tree counts as NO binding: one class name cannot serve both
     * object models, the ORM building a record with `new $class(['id' => $id], $type)`.
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
        $class = RecordClassMap::getInstance()->getClass($typeId);

        // ⚠ ONE call, and it is doing three things. is_subclass_of() AUTOLOADS, which is what
        // makes DynamicLoader declare the 345 marker subclasses the map keys off -- and a marker
        // is generated `extends <tenant>\Record`, so the whole tenant tree changes tree with its
        // base class. It answers false for an undeclarable name without a fatal, so neither
        // class_exists() nor isDeclarable() adds anything: disarming each of those bit nothing.
        if (!$class || !is_subclass_of($class, self::class)) {
            return null;
        }

        // ⚠ The CANONICAL name: an aliased one (the admin's `Ci_*`) never equals static::class, so
        // newFromBuilder() kept hydrating through the alias until memory ran out.
        return (new ReflectionClass($class))->getName();
    }

    public static function forgetBoundClasses(): void
    {
        self::$boundClasses = [];
        self::$boundTypeIds = [];
        self::$boundTables = [];
    }

    /**
     * The type THIS class is bound to, or null for the base model and any unbound subclass.
     * ⚠ Memoised per class: unmemoised it is an array_search over ci's 1,060 entries per record.
     * The base-model arm is COST, not correctness -- the map holds no such row either way, it
     * just saves building one. booted() carried a second copy of it and disarming that bit nothing.
     */
    public static function boundTypeId(): ?int
    {
        if (static::class === self::class) {
            return null;
        }

        if (!array_key_exists(static::class, self::$boundTypeIds)) {
            $typeId = RecordClassMap::getInstance()->getClassTypeId(static::class);
            self::$boundTypeIds[static::class] = $typeId === false ? null : (int) $typeId;
        }

        return self::$boundTypeIds[static::class];
    }

    /** The type this class binds as a Models\Type, where the ORM's static type() answered its own. */
    public static function boundType(): ?Type
    {
        $typeId = static::boundTypeId();

        return $typeId ? Type::find($typeId) : null;
    }

    /**
     * ⚠ Added for EVERY class, base included, and reads the binding LATE inside the closure:
     * Eloquent boots a class once, so a type_id captured here is the binding as it stood at the
     * first query -- stale for the rest of a request that saves a type and then reads one.
     */
    protected static function booted(): void
    {
        static::addGlobalScope(self::BOUND_TYPE_SCOPE, function (Builder $query): void {
            // A bound TEMPLATE names its own type: the one a class shared by several types means.
            $model = $query->getModel();
            if (static::class !== self::class
                && $typeId = ((int) ($model->attributes['type_id'] ?? 0) ?: static::boundTypeId())) {
                $query->where($model->getTable().'.type_id', $typeId);
            }
        });

        // The ORM's filter is ON by default and read per query; the admin turns it off at boot.
        static::addGlobalScope(self::PUBLISHED_SCOPE, function (Builder $query): void {
            if (static::isPublishedFiltersEnabled()) {
                $query->getModel()->applyPublishedFilter($query);
            }
        });
    }

    /**
     * ⚠ A bound class knows its own table, or `Ci\Loja::where()` reads `records` for a type whose
     * rows are in `intranet` and answers an empty list. newInstance() overwrites this with the
     * template's, which is right: there the TYPE that built the query is what knows.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if ($typeId = static::boundTypeId()) {
            $this->setTable(self::$boundTables[static::class] ??= Type::find($typeId)?->recordsTable() ?? $this->getTable());
        }
    }

    /**
     * ⚠ Resolved per ROW, where the ORM resolves once per query off the TYPE. Identical for a
     * relation, which constrains type_id; right rather than merely equal for a query spanning
     * types. It is safe only because RecordQuery keeps type_id on a partial SELECT.
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $class = static::boundClass($this->typeIdFor((array) $attributes));

        $instance = $class === null || $class === static::class
            ? parent::newInstance($attributes, $exists)
            : $this->boundTemplate($class)->newInstance($attributes, $exists);

        // ⚠ The type rides along, or `$type->records()->with('<alias>')` has no map to read the
        // name in: Builder::getRelation() asks a BLANK newInstance() for the relation object.
        // Harmless downstream -- newFromBuilder() and buildRecord() both setRawAttributes() over it.
        if ($typeId = $this->typeIdFor((array) $attributes)) {
            $instance->setAttribute('type_id', $typeId);
        }

        return $instance;
    }

    /**
     * Hydration. ⚠ Overridden BESIDE newInstance() rather than instead of it: newFromBuilder()
     * calls newInstance([]) -- empty -- so the row's type_id is readable here and nowhere else.
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $class = static::boundClass($this->typeIdFor((array) $attributes));

        return $class === null || $class === static::class
            ? parent::newFromBuilder($attributes, $connection)
            : $this->boundTemplate($class)->newFromBuilder($attributes, $connection);
    }

    /**
     * A blank $class carrying this model's table, connection and casts, which Eloquent's own
     * newInstance()/newFromBuilder() then run on. It resolves to ITSELF, ending the recursion.
     * ⚠ mergeCasts() mirrors their line and no test reaches it, a record merging the row's own
     * date casts: unreached by data, as recordsOrder()'s func_ arm is. The hop stays transparent.
     */
    private function boundTemplate(string $class): self
    {
        $template = new $class;
        $template->setConnection($this->getConnectionName());
        $template->setTable($this->getTable());
        $template->mergeCasts($this->casts);

        return $template;
    }

    /** The row's own type_id, else this template's -- a partial SELECT that aggregated has none. */
    private function typeIdFor(array $attributes): ?int
    {
        return ((int) ($attributes['type_id'] ?? $this->attributes['type_id'] ?? 0)) ?: null;
    }

    /**
     * ⚠ The alias layer reaches the QUERY from here, resolved LATE: this runs once per builder
     * while the map is read per clause, so a type set after newQuery() still translates.
     */
    protected function newBaseQueryBuilder()
    {
        return (new RecordQuery($this->getConnection()))->forRecord($this);
    }

    /**
     * How this record reads when another one points AT it: the combo columns joined, so a city
     * with `state` flagged combo is "Curitiba - Paraná". ⚠ The id when the type flags none, which
     * is what the ORM answers and is still something a picker can show.
     */
    public function getStringValue(): string
    {
        if (!$columns = $this->getType()->getComboFieldNames()) {
            return (string) $this->id;
        }

        $parts = [];

        foreach ($columns as $column) {
            if (str_starts_with($column, 'file_')) {
                continue;
            }

            $parts[] = str_starts_with($column, 'select_')
                ? $this->relationFromColumn($column)?->getName()
                : $this->getAttribute($column);
        }

        return implode(' - ', array_filter($parts));
    }

    /** What the ORM's getName() answers, and what a tab's title attribute reads. */
    public function getName(): string
    {
        return (string) $this->varchar_key;
    }

    /**
     * The route this record answers on, as its type declares it.
     * ⚠ Three of ci's classes override this and two of them call parent::, which is the whole
     * reason it is here: without it that `parent::` is Eloquent's __call and a BadMethodCall.
     */
    public function getRoute(string $action = 'index'): ?Route
    {
        return $this->typeModel()?->getRoute($action);
    }

    /**
     * The ancestors a route's variables stand for, outermost first, and SHORT when the tree runs
     * out -- RecordUrl compares the counts and throws, so that is a message, not a wrong URL.
     * @param  array<int, string>  $variables
     * @return array<int, self>
     */
    public function getUrlParameters(array $variables): array
    {
        $parameters = [];
        $parent = $this;

        foreach ($variables as $variable) {
            if (!$parent = $parent->getParent()) {
                break;
            }
            $parameters[] = $parent;
        }

        return array_reverse($parameters);
    }

    /**
     * ⚠ RecordUrl, never a second copy: which variable the slug fills is 30 lines of it, and two
     * implementations disagree the way increment 16's decoders did. Its hint had to widen to a
     * docblock rather than a union -- jp7io/classes sits BELOW the app declaring this class.
     */
    public function getUrl(string $action = 'show'): string
    {
        return RecordUrl::getRecordUrl($this, $action);
    }

    /**
     * The record this one hangs off, or null at the top.
     * ⚠ Throws on a HALF-LINKED row as the ORM does: a parent_id with no parent_type_id is a row
     * this admin has written, and Ancestry::parentOf() is the only caller that may swallow it.
     */
    public function getParent(): ?self
    {
        $this->loadAbsentParentColumns();

        if (!$this->parent_id) {
            return null;
        }

        if (!$this->parent_type_id) {
            throw new RuntimeException('Field parent_type_id is required. Id: '.$this->id);
        }

        return Type::find($this->parent_type_id)?->records()->find($this->parent_id);
    }

    /**
     * ⚠ A partial SELECT carries no parent columns -- GraphQL selects what a query names -- and the
     * ORM lazy-loaded an absent one where this reads null: `parent_id` would answer "no parent" and
     * `parent_type_id` would throw. Read from the row, as save() reads an absent `log`.
     */
    private function loadAbsentParentColumns(): void
    {
        $absent = array_values(array_diff(['parent_id', 'parent_type_id'], array_keys($this->attributes)));

        if (!$absent || !$this->exists) {
            return;
        }

        $row = (array) $this->getConnection()->table($this->getTable())
            ->where($this->getKeyName(), $this->getKey())->first($absent);

        foreach ($absent as $column) {
            $this->attributes[$column] = $row[$column] ?? null;
        }
        $this->syncOriginalAttributes($absent);
    }

    /** @return array<string, string> column => alias, the name the ORM's getAttributesAliases() has. */
    public function getAttributesAliases(): array
    {
        return $this->fieldAliases();
    }

    /**
     * This record keyed by ALIAS rather than by column, values cast. The ORM builds the same map
     * off `$_aliases` and `getMutatedAttribute()`; here the casts do the second half.
     * @return array<string, mixed>
     */
    public function getAliasedAttributes(): array
    {
        $aliases = $this->fieldAliases();
        $out = [];

        foreach (array_keys($this->attributes) as $column) {
            $out[$aliases[$column] ?? $column] = $this->getAttribute($column);
        }

        return $out;
    }

    /** The PREFIXED table this record lives in, which is what the ORM's getTableName() answers. */
    public function getTableName(): string
    {
        return $this->getConnection()->getTablePrefix().$this->getTable();
    }

    /**
     * ⚠ Shares the ORM's cache KEY rather than opening a second one over the same question --
     * `columns,<db>,<prefixed table>`, and an empty listing is never cached, both being
     * RecordAbstract::getColumns()'s rules and its reasons.
     * @return array<int, string>
     */
    public function getColumns(): array
    {
        $table = $this->getTableName();
        $key = 'columns,,'.$table;

        if ($columns = Cache::get($key)) {
            return $columns;
        }

        $columns = $this->getConnection()->getSchemaBuilder()->getColumnListing($this->getTable());

        if ($columns) {
            Cache::put($key, $columns, Type::CACHE_TTL);
        }

        return $columns;
    }

    /** Column types whose empty value is 0 rather than '', as RecordAbstract has them. */
    private const NUMERIC_TYPES = ['tinyint', 'smallint', 'mediumint', 'int', 'integer', 'bigint',
        'decimal', 'numeric', 'float', 'double', 'year', 'bit'];

    /**
     * What these values go into the database as: _convertForDatabase() minus the two branches that
     * MEASURE as no-ops here, PDO already stringifying an object and no FileField being able to
     * reach a class that never wraps a `file_` column. Keys arrive by alias or column, as there.
     *
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    public function valuesForDatabase(array $values): array
    {
        $converted = [];

        foreach ($values as $key => $value) {
            $column = $this->aliasToColumn($key);

            $converted[$column] = match (true) {
                is_array($value) => implode(',', $value),
                $value === null || $value === '' || $this->isAbsentDate($column, $value)
                    => $this->absentValueFor($column),
                default => $value,
            };
        }

        return $converted;
    }

    /**
     * ⚠ All four spellings one date column carries: NULL, '', the sentinel, and the year -0001 a
     * Date reads the first two back as. One let through is ERROR 1292 under the app's own modes
     * and a silent re-zero without them.
     */
    private function isAbsentDate(string $column, mixed $value): bool
    {
        if (!RecordColumns::isDate($column)) {
            return false;
        }

        if ($value instanceof DateTimeInterface) {
            return (int) $value->format('Y') < 1;
        }

        return is_string($value) && (int) substr($value, 0, 4) < 1;
    }

    /**
     * ⚠ The nullable list is consulted for a DATE column ALONE. Everywhere else this schema spells
     * empty as '', so a nullable varchar still takes '' -- writesNull()'s rule, and `deleted_at`
     * is the one column standing outside it.
     */
    private function absentValueFor(string $column): string|int|null
    {
        $nullableDate = RecordColumns::isDate($column)
            && in_array($column, $this->getNullableColumns(), true);

        if ($column === 'deleted_at' || $nullableDate) {
            return null;
        }

        return in_array($column, $this->getNumericColumns(), true) ? 0 : '';
    }

    /** @return array<int, string> */
    public function getNullableColumns(): array
    {
        return $this->schemaColumns('nullable');
    }

    /** @return array<int, string> */
    public function getNumericColumns(): array
    {
        return $this->schemaColumns('numeric');
    }

    /**
     * ⚠ Shares the ORM's cache KEYS as getColumns() does, and unlike it caches an EMPTY answer:
     * no nullable column is every table before increment 14, not a failed read. One schema read
     * fills both entries, so the second list costs nothing.
     *
     * @return array<int, string>
     */
    private function schemaColumns(string $which): array
    {
        if (is_array($cached = Cache::get($which.',,'.$this->getTableName()))) {
            return $cached;
        }

        $all = $this->getConnection()->getSchemaBuilder()->getColumns($this->getTable());

        if (!$all) {
            return [];
        }

        $lists = ['nullable' => [], 'numeric' => []];

        foreach ($all as $column) {
            if (!empty($column['nullable'])) {
                $lists['nullable'][] = $column['name'];
            }
            if (in_array($column['type_name'] ?? '', self::NUMERIC_TYPES, true)) {
                $lists['numeric'][] = $column['name'];
            }
        }

        foreach ($lists as $name => $list) {
            Cache::put($name.',,'.$this->getTableName(), $list, Type::CACHE_TTL);
        }

        return $lists[$which];
    }

    /**
     * ⚠ Through the ELOQUENT Type: the ORM's setParent() hints RecordAbstract, so it cannot take
     * an Eloquent record at all. Instances come from the identity map, so setParent() aliases --
     * mirrored rather than corrected.
     * @return Type[] each already scoped to this record, which is what records() reads
     */
    public function getChildrenTypes(): array
    {
        $types = [];

        foreach ($this->typeModel()?->childTypeIds() ?? [] as $childTypeId) {
            if ($childType = Type::find($childTypeId)) {
                $childType->setParent($this);
                $types[] = $childType;
            }
        }

        return $types;
    }

    /**
     * The related record a `select_` column points at, reached by the relation's name.
     * ⚠ The alias decides the name, not the column: `select_key` aliased to `loja_id` is the
     * `loja` relation, so the suffix comes off the ALIAS as the ORM's own version has it.
     */
    public function relationFromColumn(string $column): mixed
    {
        $alias = $this->fieldAliases()[$column] ?? $column;

        if (str_starts_with($column, 'select_multi_')) {
            $relation = substr($alias, 0, -4);
        } elseif (str_starts_with($column, 'select_')) {
            $relation = substr($alias, 0, -3);
        } else {
            throw new InvalidArgumentException('$column must start with select_ or select_multi_.');
        }

        return $this->$relation;
    }

    /** Every other record of this type. */
    public function siblings()
    {
        return Type::find($this->type_id)->records()->where('id', '<>', $this->id);
    }

    /** ⚠ Slots 1 and 2 ONLY, as the ORM has it -- FileSlots::of() answers all four, so it is not
     *  the same question. */
    public function hasFilesTab(): bool
    {
        $type = $this->typeModel();

        return (bool) ($type?->files_1 || $type?->files_2);
    }

    /**
     * The calendar in PHP, and it must agree row for row with the published() scope below.
     * ⚠ An absent date means ALWAYS, which the ORM says by reading a \Date of year -0001 and this
     * says by testing for null -- `NULL <= now()` being the arm that cost ci 6,290 live rows.
     */
    public function isPublished(): bool
    {
        return (bool) $this->bool_key
            && !$this->deleted_at
            && ($this->parent_id || $this->publish || !config('interadmin.preview'))
            && (!$this->publish_at || $this->publish_at->getTimestamp() <= self::getTimestamp())
            && (!$this->expire_at || $this->expire_at->getTimestamp() >= self::getTimestamp());
    }

    /**
     * The process-wide context a save and the calendar read: who and what a `log` line names, the
     * clock, and the published-filter switch. ⚠ Each SLOT stays the ORM's static -- ci-intranet and
     * intermail set it directly, so a copy here would stamp a save with whoever it last heard of.
     */
    public static function getLogUser(): string
    {
        return (string) OrmRecord::getLogUser();
    }

    public static function setLogUser(string $user): string
    {
        return (string) OrmRecord::setLogUser($user);
    }

    public static function getLogAction(): string
    {
        return (string) OrmRecord::getLogAction();
    }

    public static function setLogAction(string $action): string
    {
        return (string) OrmRecord::setLogAction($action);
    }

    public static function getTimestamp(): int
    {
        return (int) OrmRecord::getTimestamp();
    }

    public static function setPublishedFiltersEnabled(bool $enabled): bool
    {
        return OrmRecord::setPublishedFiltersEnabled($enabled);
    }

    public static function isPublishedFiltersEnabled(): bool
    {
        return (bool) OrmRecord::isPublishedFiltersEnabled();
    }

    /** The ORM's clock and the preview config, into the one builder both ORMs call. */
    public static function getPublishedFilters(string $table, string $alias): ?string
    {
        $preview = (bool) config('interadmin.preview');

        return PublishedFilterSql::build($table, $alias, self::getTimestamp(), $preview);
    }

    /**
     * ⚠ The log line is FROZEN at `d/m/Y H:i` whatever the locale: it goes INTO the `log` column
     * and Record\LogHistory parses it back out, so a localized entry corrupts existing history.
     * The actor and the action come off the ORM's own statics rather than a second copy of them.
     */
    public function save(array $options = [])
    {
        if (empty($this->attributes['type_id'])) {
            throw new RuntimeException('Saving a record without type_id.');
        }

        if (array_key_exists('varchar_key', $this->attributes)
            && in_array('id_slug', $this->getColumns(), true)
            && !$this->id_slug) {
            $this->id_slug = $this->generateSlug();
        }

        // ⚠ A row loaded without `log` would write this one line over its whole history: the
        // ORM lazy-loads an absent column to prepend to, where this model reads null.
        $history = $this->exists && !array_key_exists('log', $this->attributes)
            ? $this->getConnection()->table($this->getTable())->where($this->getKeyName(), $this->getKey())->value('log')
            : $this->log;

        $this->log = date('d/m/Y H:i').' - '.
            self::getLogUser().' - '.
            (self::getLogAction() ? self::getLogAction().' - ' : '').
            request()->ip().
            chr(13).
            $history;

        // Eloquent owns the timestamps here, where the ORM stamps updated_at by hand.
        return parent::save($options);
    }

    /**
     * Writes just these columns: no log line and no `updated_at`, the whole of what the ORM's
     * updateRawAttributes() did differently from save(). Keys arrive by alias or by column.
     * ⚠ toBase(), or Eloquent's own update() stamps `updated_at` back on. And newModelQuery(), as
     * save() uses: a global scope on this UPDATE makes a shared class's other types match nothing.
     * @param  array<string, mixed>  $values
     */
    public function updateWithoutLog(array $values): void
    {
        // An empty SET is a syntax error, and a trigger restamping the same second changes nothing.
        if (!$values) {
            return;
        }

        $this->forceFill($values);

        $this->newModelQuery()->toBase()
            ->where($this->getKeyName(), $this->getKey())
            ->update($values);

        $this->syncOriginalAttributes(array_map($this->aliasToColumn(...), array_keys($values)));
    }

    /**
     * The ORM's saveRaw(): what changed since the row was read, with no log line and no
     * `updated_at`. ⚠ An UPDATE only -- saveRaw() inserted an unsaved row, and no caller left does.
     */
    public function saveWithoutLog(): void
    {
        if (!$this->exists) {
            throw new LogicException('saveWithoutLog() updates a saved row; insert through save().');
        }

        $this->updateWithoutLog($this->getDirty());
    }

    /**
     * Soft, as the ORM's is: the stamp, then save() and its log line. ⚠ Without it this is
     * Eloquent's HARD delete and nothing fails -- LayoutSaver's reuse pool just stays empty.
     */
    public function delete(): bool
    {
        $this->deleted_at = now();

        return $this->save();
    }

    /** This row, physically. ⚠ Declared, or __call forwards the name to an UNSCOPED builder. */
    public function forceDelete(): bool
    {
        return (bool) parent::delete();
    }

    /** Undelete. Not SoftDeletes' restore(): this model deliberately declines that trait. */
    public function restore(): bool
    {
        $this->deleted_at = null;

        return $this->save();
    }

    /**
     * ⚠ Deduped with ONE query, not a loop: the REGEXP finds the highest numbered sibling and the
     * suffix is derived from it. Length first in the ORDER BY, or `foo9` sorts above `foo10`.
     */
    public function generateSlug(): string
    {
        if (!trim((string) $this->varchar_key)) {
            return '';
        }

        $slug = to_slug($this->varchar_key);
        if (is_numeric($slug)) {
            $slug = '--'.$slug;
        }

        if ($this->siblings()->where('id_slug', $slug)->exists()) {
            $max = $this->siblings()
                ->where('id_slug', 'REGEXP', '^'.$slug.'[0-9]*$')
                ->orderByRaw('LENGTH(id_slug) DESC, id_slug DESC')
                ->value('id_slug');

            $max = Str::replaceStart($slug, '', (string) $max) ?: 1;
            $slug .= $max + 1;
        }

        return $slug;
    }

    /**
     * Laravel validation rules derived from the type's form fields, keyed by ALIAS because that
     * is what the form posts.
     * @return array<string, array<int, string>>
     */
    public function getRules(): array
    {
        $rules = [];

        foreach ($this->typeModel()?->fieldDefinitions() ?? [] as $field) {
            if (!$field['form']) {
                continue;
            }

            $alias = $field['name_id'];

            if ($field['required']) {
                $rules[$alias][] = 'required';
            }
            if ($field['xtra'] === 'email' || $field['xtra'] === 'id_email') {
                $rules[$alias][] = 'email';
            }
            if (str_starts_with($field['type'], 'int_')) {
                $rules[$alias][] = 'integer';
            }
            if (str_starts_with($field['type'], 'date_')) {
                $rules[$alias][] = 'date_format:Y-m-d';
            }
        }

        return $rules;
    }

    /** A `unique:` rule scoped to this type, and to this record's own id on an edit. */
    public function getUniqueRule(string $column, array $whereHash = []): string
    {
        $params = [
            $this->getTable(),
            array_search($column, $this->fieldAliases(), true) ?: $column,
            $this->id,
            'id',
            'type_id', $this->type_id,
        ];

        foreach ($whereHash as $where => $value) {
            $params[] = $where;
            $params[] = $value;
        }

        return 'unique:'.implode(',', $params);
    }

    /**
     * The publishing calendar, as the ORM's own predicate rather than a second copy of it.
     * ⚠ UNCONDITIONAL where the ORM applies its filter only while enabled, and that IS parity:
     * BaseQuery::published(true) sets `use_published_filters` explicitly, beating the flag the
     * admin turns off at boot (Tenant::setupDatabase) -- honouring it would be a no-op here.
     */
    #[Scope]
    protected function published(Builder $query): void
    {
        // The switch's own copy goes first, or the predicate reaches MySQL twice.
        $query->withoutGlobalScope(self::PUBLISHED_SCOPE);
        $this->applyPublishedFilter($query);
    }

    /** The ORM's `published(false)`: this query without the switch's automatic filter. */
    #[Scope]
    protected function withUnpublished(Builder $query): void
    {
        $query->withoutGlobalScope(self::PUBLISHED_SCOPE);
    }

    /** The ORM's taggedWith(): tagged with every one of $tags, one EXISTS each. */
    #[Scope]
    protected function taggedWith(Builder $query, ...$tags): void
    {
        foreach ($tags as $tag) {
            $query->whereHas('tags', fn (Builder $tagged) => $tagged->where($tag->getTagFilters()));
        }
    }

    /** A record tags another as itself: its own id under its type. */
    public function getTagFilters(): array
    {
        return ['id' => $this->id, 'type_id' => (int) $this->type_id];
    }

    private function applyPublishedFilter(Builder $query): void
    {
        // getPublishedFilters(), not PublishedFilterSql::build(): it is where this model feeds the
        // builder the ORM's clock and the preview config.
        $table = $this->getConnection()->getTablePrefix().$this->getTable();
        $sql = self::getPublishedFilters($table, $table);

        if ($sql === null) {
            return;
        }

        // ⚠ Every predicate ends in ' AND ' deliberately, an ORM caller concatenating it onto the
        // front of its own clause. A builder does not, so the joiner comes off ANCHORED: rtrim()
        // takes a character LIST and would eat a trailing D, N or A of a value.
        $query->whereRaw(preg_replace('/ AND $/', '', $sql));
    }

    /**
     * ⚠ A #[Scope] method is NOT a relation and Eloquent cannot tell: isRelation() asks
     * method_exists(), so reading `$record->published` calls the scope with no Builder and dies
     * with ArgumentCountError where every other unknown name answers null. A tenant aliases a
     * column to any word it likes, so the collision is data rather than a name to avoid here.
     */
    public function isRelation($key)
    {
        return !static::isScopeMethodWithAttribute($key)
            && (parent::isRelation($key)
                || isset($this->fieldRelationships()[$key])
                || (bool) $this->childType($key));
    }

    public function getAttribute($key)
    {
        // A present column, then a get<Key>Attribute mutator, then the alias map: the order
        // Jp7\InterAdmin\Record::&__get() resolves in. The first two arms are not defensive --
        // a tenant may alias one column to ANOTHER column's name, so translating before looking
        // would answer the wrong slot, and a mutator named for the alias has to win or defining
        // one silently stops it being called.
        $column = array_key_exists($key, $this->attributes) || $this->hasFieldMutator('get', $key)
            ? $key
            : $this->aliasToColumn($key);
        $value = parent::getAttribute($column);

        return static::$fileFieldClass && $value && str_starts_with($column, 'file_') && !str_contains($column, '_text')
            ? $this->fileField($column, $value)
            : $value;
    }

    /** The ORM's FileField over one `file_` value, captioned by the `_text` column beside it. */
    private function fileField(string $column, $url): object
    {
        $file = new (static::$fileFieldClass)($url, (string) parent::getAttribute($column.'_text'));
        $file->setParent($this);

        return $file;
    }

    public function setAttribute($key, $value)
    {
        // ⚠ ASYMMETRIC WITH THE READ PATH, and mirrored rather than corrected.
        // Jp7\InterAdmin\Record::__set() checks the mutator and then the ALIAS, with no
        // "is this already a column" arm, so where a tenant aliases one column to another
        // column's name the ORM READS the column and WRITES the alias's slot. Step 1 is parity;
        // RecordAliasParityTest pins the asymmetry so closing it is a deliberate later change.
        if ($this->hasFieldMutator('set', $key)) {
            return parent::setAttribute($key, $value);
        }

        $column = $this->aliasToColumn($key);

        // A slot written on a model hydration never reached, so setRawAttributes() never saw it.
        $this->mergeCasts($this->dateCasts([$column]));

        return parent::setAttribute($column, $value);
    }

    /**
     * @param  array<int, string>  $columns
     * @return array<string, string>
     */
    private function dateCasts(array $columns): array
    {
        return array_fill_keys(array_filter($columns, RecordColumns::isDate(...)), 'datetime');
    }

    private function hasFieldMutator(string $prefix, string $key): bool
    {
        return method_exists($this, $prefix.Str::studly($key).'Attribute');
    }

    /**
     * The column holding $key, or $key itself when it names no alias.
     * Public because RecordQuery asks it of the same map: one derivation, so a WHERE and a read
     * of the same name cannot resolve to different columns.
     */
    public function aliasToColumn(string $key): string
    {
        $column = array_search($key, $this->fieldAliases(), true);

        return $column === false ? $key : $column;
    }

    public function getRelationValue($key)
    {
        $shape = $this->relationLoaded($key) ? null : ($this->fieldRelationships()[$key] ?? null);

        // ⚠ Two shapes have NO query behind them and so cannot be an Eloquent relation: an id
        // naming a TYPE rather than a record (13 of ci's 936 names, answered from the type cache),
        // and one naming a type whose row is gone -- null there, never a BadMethodCall.
        if ($shape && ($shape['holds_type'] || !self::relatedType($shape['type_id']))) {
            $this->setRelation($key, $shape['holds_type']
                ? $this->relatedTypes($key, $shape['multi'])
                : ($shape['multi'] ? new Collection : null));
        }

        return parent::getRelationValue($key);
    }

    /**
     * ⚠ A record serializes its COLUMNS and never its relations: the ORM's own toArray() is
     * getAliasedAttributes() and nothing else, and Eloquent's bag is where one lives now.
     * @return array<string, mixed>
     */
    public function relationsToArray()
    {
        return [];
    }

    /** @return array<string, array<string, mixed>> */
    private function fieldRelationships(): array
    {
        return self::$relationsByType[(int) $this->typeId()] ??= $this->typeModel()?->relationships() ?? [];
    }

    /**
     * ⚠ Kept in the STORED order, where a related RECORD list comes back in the related type's
     * own: there is no query here to carry an ORDER BY, which is the ORM's shape too.
     * @return Collection<int, Type>|Type|null
     */
    private function relatedTypes(string $key, bool $multi): Collection|Type|null
    {
        $types = array_values(array_filter(array_map(
            fn ($id) => self::relatedType((int) $id),
            array_filter(explode(',', (string) $this->getAttribute($key.($multi ? '_ids' : '_id'))))
        )));

        return $multi ? new Collection($types) : ($types[0] ?? null);
    }

    /**
     * ⚠ A DECLARED CHILD and a `select_`, the ORM's two arms. Eloquent's own must still answer
     * everything after them: `where` and its family reach a record only through `__call`.
     */
    public function __call($method, $parameters)
    {
        return $this->fieldRelation($method) ?? parent::__call($method, $parameters);
    }

    /**
     * The relation OBJECT behind one of those names, which is what makes `with()`, `load()` and
     * Eloquent's collection autoloading answer the N+1 the ORM answers with a private registry.
     * ⚠ Relationships BEFORE children, the order Record::_lazyLoadAttribute() reads in: a type
     * declaring a child under one of its own select_ aliases resolves to the select_ on both.
     */
    private function fieldRelation(string $name): ?Relation
    {
        $shape = $this->fieldRelationships()[$name] ?? null;

        if ($shape && !$shape['holds_type'] && $related = self::relatedType($shape['type_id'])) {
            return $this->selectRelation($name, $related, $shape['multi']);
        }

        return ($type = $this->childType($name)) ? $this->childRelation($type) : null;
    }

    /** ⚠ ucfirst(), as `_findChild()` has it: the map is studly and the call site is camel. */
    private function childType(string $method): ?Type
    {
        $children = self::$childrenByType[(int) $this->typeId()] ??= $this->typeModel()?->childTypeIds() ?? [];

        return self::relatedType($children[ucfirst($method)] ?? null);
    }

    /**
     * ⚠ `parent_type_id` as well as `parent_id`: on the shared table the child type's rows hang
     * off every type that declares it, so the id alone lists another parent's children too.
     * ChildRecords applies it per path: a list of parents can span several types.
     */
    private function childRelation(Type $type): ChildRecords
    {
        $related = $type->recordTemplate();
        $table = $related->getTable();

        $query = $related->newQuery()->where($table.'.type_id', $type->type_id);
        /** @var RecordQuery $base */
        $base = $query->getQuery();
        $base->orderByType($type->recordsOrder());

        return new ChildRecords($query, $this->keylessWhenUnsaved(), $table.'.parent_id', 'id', $type, $this->typeId());
    }

    /**
     * ⚠ A BUILT record carries id 0, and `parent_id = 0` is the child type's ORPHANS listed under
     * a create screen -- where the ORM emits a clause matching nothing. Only addConstraints()
     * reads a relation's parent, so eager loading, which reads the models it is given, cannot see
     * this: measured at 0 rows over ci's 685 declared parent/child pairs, and written by the test.
     */
    private function keylessWhenUnsaved(): self
    {
        if ($this->id) {
            return $this;
        }

        $twin = clone $this;
        unset($twin->attributes['id']);

        return $twin;
    }

    /**
     * ⚠ ORDERED by the related type's own, which the ORM gets for free -- it reaches these through
     * findMany() on a records() query, and every one of those carries that ORDER BY there.
     */
    private function selectRelation(string $name, Type $related, bool $multi): Relation
    {
        $template = $related->recordTemplate();
        $table = $template->getTable();
        $column = $this->aliasToColumn($name.($multi ? '_ids' : '_id'));

        $query = $template->newQuery()->where($table.'.type_id', $related->type_id);
        /** @var RecordQuery $base */
        $base = $query->getQuery();
        $base->orderByType($related->recordsOrder());

        return $multi
            ? new SelectMulti($query, $this, $column)
            : new BelongsTo($query, $this, $column, 'id', $name);
    }
}
