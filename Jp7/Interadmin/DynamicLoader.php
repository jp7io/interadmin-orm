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
            eval('?>'.$code);

            return true;
        }
        
        if (!$retry && App::environment('local') && starts_with($class, config('interadmin.namespace'))) {
            RecordClassMap::getInstance()->clearCache();
            TypeClassMap::getInstance()->clearCache();
            return self::load($class, true);
        }

        return false;
    }
    
    public static function generateRecordClass(Type $tipo)
    {
        $prefixoClasse = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');
        $nomeClasse = $tipo->class;

        $conteudo = <<<STR
<?php

class {$nomeClasse} extends {$prefixoClasse}Record {

}
STR;
        return $conteudo;
    }

    public static function generateTypeClass(Type $tipo)
    {
        $prefixoClasse = constant(Type::getDefaultClass().'::DEFAULT_NAMESPACE');
        $nomeClasse = $tipo->class_tipo;
        
        $conteudo = <<<STR
<?php

class {$nomeClasse} extends {$prefixoClasse}Type {
    const ID_TIPO = {$tipo->id_tipo};
}
STR;
        return $conteudo;
    }
}
