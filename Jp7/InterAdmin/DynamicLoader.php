<?php

namespace Jp7\InterAdmin;

use App;

class DynamicLoader
{
    /**
     * The names PHP refuses with an E_COMPILE_ERROR — which `try`/`catch` never sees, so for
     * these the check in isDeclarable() is not a nicety, it is the only thing that can prevent
     * the fatal. Every OTHER reserved word is a ParseError, which the probe there catches.
     * Re-derive on a PHP upgrade: eval("class X {}") per candidate in a process of its own and
     * keep the ones that exit 255.
     */
    private const RESERVED_CLASS_NAMES = [
        'bool', 'false', 'float', 'int', 'iterable', 'mixed', 'never', 'null', 'object',
        'parent', 'self', 'string', 'true', 'void',
    ];

    /** @var array<string, bool> memoized, because the misses are asked about per record. */
    private static array $declarable = [];

    private static bool $registered = false;

    /**
     * Whether PHP can declare a class by this name at all.
     *
     * `types.class` becomes a class name by a literal `_` → `\` swap, so a content type named
     * "Include" asks for `class Include {}` and a type named "String" asks for something worse.
     * Callers treat such a binding as NO binding: nothing can make the name exist, so the only
     * question is whether they degrade to the default class or take the request down with them.
     */
    public static function isDeclarable($class)
    {
        if (!isset(self::$declarable[$class])) {
            $short = last(explode('\\', $class));
            try {
                token_get_all('<?php class '.$short.' {}', TOKEN_PARSE);
                self::$declarable[$class] = !in_array(strtolower($short), self::RESERVED_CLASS_NAMES, true);
            } catch (\ParseError $e) {
                self::$declarable[$class] = false;
            }
        }
        return self::$declarable[$class];
    }

    public static function register(): void
    {
        spl_autoload_register([self::class, 'load']);
        self::$registered = true;
    }

    public static function isRegistered(): bool
    {
        return self::$registered;
    }

    // Cria classes cadastradas no InterAdmin sem a necessidade de criar um arquivo para isso
    public static function load($class): bool
    {
        if (!App::bound('config')) {
            return false; // there was a problem with codeception after running all tests
        }
        // An autoloader that cannot provide a name has to return quietly. This one used to
        // generate the class anyway and let the eval below blow up — as a RuntimeException for a
        // keyword, and as an uncatchable fatal for `self`/`int`/`string` and the eleven like them.
        if (!self::isDeclarable($class)) {
            return false;
        }
        $code = self::getCode($class);
        if ($code) {
            try {
                eval('?>'.$code);
            } catch (\Throwable $e) {
                throw new \RuntimeException($e->getMessage().' - Code: '.$code, 0, $e);
            }
            return true;
        }

        return false;
    }

    protected static function getPhpDocField($type, array $field): string
    {
        if (strpos($field['type'], 'special_') === 0 && $field['xtra']) {
            $phpDocType = 'int';
        } elseif (strpos($field['type'], 'select_') === 0) {
            $phpDocType = 'int';
        } elseif (strpos($field['type'], 'int') === 0 || strpos($field['type'], 'id') === 0) {
            $phpDocType = 'string';
        } elseif (strpos($field['type'], 'char') === 0) {
            $phpDocType = 'string';
        } elseif (strpos($field['type'], 'date') === 0) {
            $phpDocType = '\\Date';
        } else {
            $phpDocType = 'string';
        }
        return $phpDocType.' $'.$field['name_id'].' '.$field['help'];
    }

    public static function generateRecordClass(Type $type, $addPhpDoc = false): string
    {
        $prefixClass = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');

        $phpdoc = '';
        if ($addPhpDoc) {
            $phpdoc = '/**'."\r\n";
            foreach ($type->getFields() as $field) {
                $phpdoc .= ' * @property '.self::getPhpDocField($type, $field)."\r\n";
            }
            $phpdoc .= ' * @property \\Date $date_publish'."\r\n";
            $phpdoc .= ' */';
        }

        return self::buildClass(
            $type->getRecordClass(),
            $prefixClass.'Record',
            '',
            $phpdoc
        );
    }

    public static function generateTypeClass(Type $type, $addPhpDoc = false): string
    {
        $prefixClass = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');
        $phpdoc = $addPhpDoc ? '/** --- */' : '';
        return self::buildClass(
            $type->getTypeClass(),
            $prefixClass.'Type',
            "const TYPE_ID = {$type->type_id};",
            $phpdoc
        );
    }

    public static function getCode($class, $addPhpDoc = false)
    {
        if ($type_id = RecordClassMap::getInstance()->getClassTypeId($class)) {
            $type = new Type($type_id);
            $type->class = $class;

            return self::generateRecordClass($type, $addPhpDoc);
        }
        if ($type_id = TypeClassMap::getInstance()->getClassTypeId($class)) {
            $type = new Type($type_id);
            $type->class_type = $class;

            return self::generateTypeClass($type, $addPhpDoc);
        }
    }

    protected static function getNamespaceAndClass($className): array
    {
        $namespace = explode('\\', $className);
        $className = array_pop($namespace);
        $namespace = implode('\\', $namespace);
        return [$namespace, $className];
    }

    protected static function buildClass($className, $parentClass, $classBody, $phpdoc): string
    {
        list($namespace, $className) = self::getNamespaceAndClass($className);
        if ($namespace) {
            $namespace = 'namespace '.$namespace.';';
        }
        return <<<STR
<?php
// THIS IS A GENERATED FILE, BE CAREFUL TO EDIT THIS
{$namespace}
{$phpdoc}
class {$className} extends \\{$parentClass}
{
    {$classBody}
}
STR;
    }
}
