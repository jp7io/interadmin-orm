## Unreleased
* Faster than the legacy half on every request, measured page by page against it:
  * A type's list lookups (children, a child by slug, the types built on a model, menu children) read `InterAdmin\Models\TypeIndex`, ONE type-tag entry of every row's tree columns, which any type write forgets whole. Each was a query per call. `Type::listedChild()` answers `listedChildTypes()->where(<id or slug>)->first()` from it.
  * `getParent()` is memoised per record, as the legacy `_parent` was, and `Record::primeParents()` loads a list's parents in one query per parent type. A child list read off its parent, eager or not, hands each child that parent, as `deprecatedFind()` did, so a list drawing each row's URL asks nothing per row.
  * `RecordBuilder::keySubquery()` hands a query's keys to `whereIn()` as a subquery; `pluck()` of the key column builds no model per value.
  * `getColumns()` is memoised per process, a cached row hydrates without building a query, and a column qualified by the record's own table, or by Laravel's self-join alias, is never read as a relation path: the alias names no table, so asking its columns went to `information_schema` on every request.
* Removed the legacy `Jp7\InterAdmin` half, its 24 classes and its `Jp7\InterAdmin\` psr-4 entry: `InterAdmin\Models` is the ORM.
* Breaking changes:
  * What survived lives in jp7io/classes, on the same prefix: `Schema\DynamicLoader`, `Schema\RecordClassMap` and `Schema\TypeClassMap`, and `Schema\FieldDefinitions`, `Schema\ChildDeclarations` and `Schema\PublishedFilterSql` in place of `FieldUtil`, `ChildUtil` and `PublishedFilter`.
  * `doctrine/sql-formatter` is no longer required: the legacy query dumper was its only user.
  * The package keeps no PHPStan of its own; the admin's `composer analyse` covers `InterAdmin/Models`.

## 4.0
* Removed the pre-namespace shim: `InterAdmin`, `InterAdminTipo`, `InterAdminArquivo`, `InterAdminArquivoBanco` and `InterAdminAbstract`, with the `legacy` classmap entry and DynamicLoader's `X_Record -> X_InterAdmin` name bridge.
* Breaking changes:
  * `new InterAdminTipo($id)` -> `Jp7\InterAdmin\Type::getInstance($id)`, or `new Tenant\Type($id)` where the tenant subclass matters.
  * `new InterAdmin($id, $options)` -> `Jp7\InterAdmin\Record::__construct(array $attributes = [], $type = null)`. The 2.x signature has been a TypeError since 3.0, so any surviving call was already fatal.
  * `$tipo->getInterAdmins($options)` -> `$tipo->records()`; `$record->setFieldsValues($attrs)` -> `updateAttributes($attrs)`. Both were removed in 3.0 and have thrown BadMethodCallException ever since — the shim kept the class name alive, never these methods.
  * **A class name held as a STRING is a dependency.** `->class('InterAdmin')`, `'class' => 'InterAdmin'` and `unserialize(..., ['allowed_classes' => ['InterAdmin']])` all reach `new $className`, and no grep for `new`/`extends` finds them. Sweep for quoted names too.
  * Hydrating as `Record` rather than `InterAdmin` changes `DEFAULT_NAMESPACE` from `''`, so relations resolve to objects: `relationFromColumn()` returns a `Collection` where the shim returned a plain array.
  * Anything serialized by the 2.x classes is unreadable regardless of this release — it uses the `C:`/`Serializable` format, which `unserialize()` rejects on PHP 8.5.

## 3.3
* Several improvements for eager loading and performance
* Breaking changes:
  * getParent() on Record will not change the Record's Type `_parent` property anymore. This side-effect might have been relied upon on code written bettwen versions 1.0 to 2.0
  * Removed Collection->split() to use Laravel 5.3 original method. If your project is Laravel 5.2, make sure you're not using this method
  * Use SQL bind by default instead of quote. Improve security by using the proper mechanism to avoid SQL injection. Calls to getOptionsArray() convert back to quoted and should be avoided.
  * Calls to getAttributes() might return date_* and file_* attributes as string, not objects.

## 3.2.2
* Small fixes for Laravel 5.7

## 3.2.1
* Small fixes, support for Laravel Socialite login

## 3.2
* Split into 3 packages: classes, classes-deprecated and interadmin-orm

## 3.1
* Fixed bugs after the merge of the ORM
* Performance fixes for aliases
* Add type of password field using Laravel Hash
* Add commands to generate seeds from InterAdmin database
* Log that the Laravel queue is running
* Fixes for HTTPS
* Use .env values for e-mails, DB and storage
* Move getUrl() out of the ORM

## 3.0
* Merged both ORMs: InterAdmin and Jp7/InterAdmin/Record

### Changes to projects which used InterAdmin/InterAdminTipo:
 * Removed methods deprecated on 2.1.1 (like getInterAdmins)
 * `InterAdmin::__construct` receives an array now
 * Calling select_* without alias won't bring objects: ->relationFromColumn() can be used if the alias is not known
 * ->attributes is not public anymore - Use ->getAttributes()
 * ->getCampoTipo() can only be overwritten on a Type
 * Replace setFieldsValues() -> updateAttributes()
 * Fields are eager and lazy loaded, ->getFieldsValues() and getByAlias() are not needed anymore
 * Default aliases are generated in snake_case now (if empty). To use old aliases you must manually define them.
 * ORM depends on new configuration: /config/interadmin.php and /resources/lang/pt-BR/interadmin.php

### Changes to projects which used Jp7/InterAdmin/Record
 * Attributes are stored internally without alias / use getAliasedAttributes() if needed

## 2.7
* Branch laravel was reintegrated to master
* Dependencies removed from classes, each client must require them as needed:
  * "zendframework/zendframework1": "1.12.0"
  * "phpoffice/phpexcel": "~1.8.1"
  * "werkint/jsmin": "~1.0.0”
* Replace Jp7_InterAdmin by Jp7_Interadmin
* Replace InterAdmin_ by Interadmin_
* Replace startsWith($needle, $haystack) by starts_with($haystack, $needle)
* Replace endsWith($needle, $haystack) by ends_with($haystack, $needle)
* Replace jp7_replace_beginning() by replace_prefix()
* Main table is interadmin_CLIENT_registros, it was interadmin_CLIENT
  * To prevent problems with legacy projects a VIEW named interadmin_CLIENT_registros was created

### Changes to projects which used branch laravel:
 * InterSite -> Jp7\Intersite
 * InterAdmin -> Jp7\InterAdmin\Record
 * InterAdminAbstract -> Jp7\InterAdmin\RecordAbstract
 * InterAdminTipo -> Jp7\InterAdmin\Type
 * InterAdminArquivo -> Jp7\InterAdmin\FileRecord
 * InterAdminArquivoBanco -> Jp7\InterAdmin\FileDatabase
 * InterAdminLog -> Jp7\InterAdmin\Log
 * InterAdminField -> Jp7\InterAdmin\FieldUtil
 * InterAdminFieldFile -> Jp7\InterAdmin\FileField
 * Change config suffix in resources/lang/en/interadmin.php from \_en to en\_

## 2.6
* ...

## 2.1.1
### Deprecate the following methods, replaced by new names:
* getFirstInterAdmin -> findFirst
* getInterAdminById -> findById
* getInterAdminByIdString -> findByIdString
* getInterAdmins -> find
* getInterAdminsByTags -> findByTags
* getInterAdminsCount -> count

## 2.0 
* Add parent_id_tipo to conditionals (important if the parents are on different tables, two records with the same parent_id, might have different parent_id_tipo)
* Add "inherited" as a pre-calculated field to InterAdminTipo to avoid long recursive searches for all the values inherited  from models.
