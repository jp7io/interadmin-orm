## 3.3
* UNDER DEVELOPMENT
* Several improvements for eager loading and performance
* Breaking changes:
  * getParent() on Record will not change the Record's Type `_parent` property anymore. This side-effect might have been relied upon on code written bettwen versions 1.0 to 2.0
  * Custom Joins order changed. It was before the automatic joins, now it's after them.
  * Removed Collection->split() to use Laravel 5.3 original method. If your project is Laravel 5.2, make sure you're not using this method
  * Use SQL bind by default instead of quote. Improve security by using the proper mechanism to avoid SQL injection. Calls to getOptionsArray() convert back to quoted and should be avoided.
  * Calls to getAttributes() and getAliasedAttributes() might return date_* and file_* attributes as string, not objects.

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
* Merged both ORMs: InterAdmin and Jp7/Interadmin/Record

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

### Changes to projects which used Jp7/Interadmin/Record
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
 * InterAdmin -> Jp7\Interadmin\Record
 * InterAdminAbstract -> Jp7\Interadmin\RecordAbstract
 * InterAdminTipo -> Jp7\Interadmin\Type
 * InterAdminArquivo -> Jp7\Interadmin\FileRecord
 * InterAdminArquivoBanco -> Jp7\Interadmin\FileDatabase
 * InterAdminLog -> Jp7\Interadmin\Log
 * InterAdminField -> Jp7\Interadmin\FieldUtil
 * InterAdminFieldFile -> Jp7\Interadmin\FileField
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

