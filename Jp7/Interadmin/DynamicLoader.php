<?php

namespace Jp7\Interadmin;

use App;

class DynamicLoader
{
    private static $registered = false;

    public static function register()
    {
        spl_autoload_register([self::class, 'load']);
        self::$registered = true;
    }

    public static function isRegistered()
    {
        return self::$registered;
    }

    // Cria classes cadastradas no InterAdmin sem a necessidade de criar um arquivo para isso
    public static function load($class, $retry = false)
    {
        $code = null;
        if ($id_tipo = RecordClassMap::getInstance()->getClassIdTipo($class)) {
            $tipo = new Type($id_tipo);
            $tipo->class = $class;

            $code = self::generateRecordClass($tipo);
        } elseif ($id_tipo = TypeClassMap::getInstance()->getClassIdTipo($class)) {
            $tipo = new Type($id_tipo);
            $tipo->class_tipo = $class;

            $code = self::generateTypeClass($tipo);
        }
        if ($code) {
            try {
                eval('?>'.$code);
            } catch (\Throwable $e) {
                throw new \RuntimeException($e->getMessage().' - Code: '.$code, 0, $e);
            }
            return true;
        }

        // Support legacy ORM class names
        if (str_contains($class, '_')) {
            $last = last(explode('_', $class));
            $replacements = [
                'Record' => 'InterAdmin',
                'Type' => 'InterAdminTipo',
                'FileField' => 'InterAdminFieldFile',
                'FileRecord' => 'InterAdminArquivo',
            ];
            if (array_key_exists($last, $replacements)) {
                $replacedClass = str_replace($last, $replacements[$last], $class);
                if (class_exists($replacedClass)) {
                    class_alias($replacedClass, $class);
                }
            }
            return true;
        }
        
        if (!$retry && App::environment('local') && starts_with($class, config('interadmin.namespace'))) {
            RecordClassMap::getInstance()->clearCache();
            TypeClassMap::getInstance()->clearCache();
            return self::load($class, true);
        }

        return false;
    }

    public static function generateRecordClass(Type $type)
    {
        $prefixClass = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');
        return self::buildClass(
            $type->getRecordClass(),
            $prefixClass.'Record',
            ''
        );
    }

    public static function generateTypeClass(Type $type)
    {
        $prefixClass = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');
        return self::buildClass(
            $type->getTypeClass(),
            $prefixClass.'Type',
            "const ID_TIPO = {$type->id_tipo};"
        );
    }

    protected static function getNamespaceAndClass($className)
    {
        $namespace = explode('\\', $className);
        $className = array_pop($namespace);
        $namespace = implode('\\', $namespace);
        return [$namespace, $className];
    }

    protected static function buildClass($className, $parentClass, $classBody)
    {
        list($namespace, $className) = self::getNamespaceAndClass($className);
        if ($namespace) {
            $namespace = 'namespace '.$namespace.';';
        }
        return <<<STR
<?php

{$namespace}

class {$className} extends \\{$parentClass} {
    {$classBody}
}
STR;
    }
}
