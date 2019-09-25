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
    public static function load($class)
    {
        if (!App::bound('config')) {
            return false; // there was a problem with codeception after running all tests
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

        return false;
    }

    protected static function getPhpDocCampo($tipo, $campo)
    {
        if (strpos($campo['tipo'], 'special_') === 0 && $campo['xtra']) {
//            $isMulti = in_array($campo['xtra'], InterAdminField::getSpecialMultiXtras());
//            $isTipo = in_array($campo['xtra'], InterAdminField::getSpecialTipoXtras());
//            $retorno = self::_getCampoTypeClass($tipo->getCampoTipo($campo), $isTipo, $isMulti);
            $type = 'int';
        } elseif (strpos($campo['tipo'], 'select_') === 0) {
//            $isMulti = (strpos($campo['tipo'], 'select_multi') === 0);
//            $isTipo = in_array($campo['xtra'], InterAdminField::getSelectTipoXtras());
//            $retorno = self::_getCampoTypeClass($campo['nome'], $isTipo, $isMulti);
            $type = 'int';
        } elseif (strpos($campo['tipo'], 'int') === 0 || strpos($campo['tipo'], 'id') === 0) {
            $type = 'string';
        } elseif (strpos($campo['tipo'], 'char') === 0) {
            $type = 'string';
        } elseif (strpos($campo['tipo'], 'date') === 0) {
            $type = '\\Date';
        } else {
            $type = 'string';
        }
        return $type.' $'.$campo['nome_id'].' '.$campo['ajuda'];
    }

    public static function generateRecordClass(Type $type, $addPhpDoc = false)
    {
        $prefixClass = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');

        $phpdoc = '';
        if ($addPhpDoc) {
            $phpdoc = '/**'."\r\n";
            foreach ($type->getCampos() as $campo) {
                $phpdoc .= ' * @property '.self::getPhpDocCampo($type, $campo)."\r\n";
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

    public static function generateTypeClass(Type $type, $addPhpDoc = false)
    {
        $prefixClass = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');
        $phpdoc = $addPhpDoc ? '/** --- */' : '';
        return self::buildClass(
            $type->getTypeClass(),
            $prefixClass.'Type',
            "const ID_TIPO = {$type->id_tipo};",
            $phpdoc
        );
    }

    public static function getCode($class, $addPhpDoc = false)
    {
        if ($id_tipo = RecordClassMap::getInstance()->getClassIdTipo($class)) {
            $tipo = new Type($id_tipo);
            $tipo->class = $class;

            return self::generateRecordClass($tipo, $addPhpDoc);
        }
        if ($id_tipo = TypeClassMap::getInstance()->getClassIdTipo($class)) {
            $tipo = new Type($id_tipo);
            $tipo->class_tipo = $class;

            return self::generateTypeClass($tipo, $addPhpDoc);
        }
    }

    protected static function getNamespaceAndClass($className)
    {
        $namespace = explode('\\', $className);
        $className = array_pop($namespace);
        $namespace = implode('\\', $namespace);
        return [$namespace, $className];
    }

    protected static function buildClass($className, $parentClass, $classBody, $phpdoc)
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
