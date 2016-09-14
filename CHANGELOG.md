## development
* TODO
## 3.0
* Merged both ORMs: InterAdmin and Jp7/Interadmin/Record
* Changes to projects which used InterAdmin/InterAdminTipo:
 * InterAdmin::__construct receives an array now
 * Calling select_* without alias won't bring objects: ->relationFromColumn() can be used if the alias is not known
 * ->attributes is not public anymore - Use ->getAttributes()
 * ->getCampoTipo() can only be overwritten on a Type
 * Replace setFieldsValues() -> updateAttributes()
 * Fields are eager and lazy loaded, ->getFieldsValues() and getByAlias() are not needed anymore
 * Default aliases are generated in snake_case now (if empty). To use old aliases you must manually define them.
 * ORM depends on new configuration: /config/interadmin.php and /resources/lang/pt-BR/interadmin.php
* Changes to projects which used Jp7/Interadmin/Record:
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
* Changes to projects which used branch laravel:
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
