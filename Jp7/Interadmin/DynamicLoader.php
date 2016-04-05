<?php

namespace Jp7\Interadmin;

class DynamicLoader
{
    // Cria classes cadastradas no InterAdmin sem a necessidade de criar um arquivo para isso
    public static function load($class)
    {
        $cm = ClassMap::getInstance();

        $code = null;
        if ($id_tipo = $cm->getClassIdTipo($class)) {
            $tipo = new Type($id_tipo);
            $tipo->class = $class;
            
            $code = self::generateRecordClass($tipo);
        } elseif ($id_tipo = $cm->getClassTipoIdTipo($class)) {
            $tipo = new Type($id_tipo);
            $tipo->class_tipo = $class;
            
            $code = self::generateTypeClass($tipo);
        }
        if ($code) {
            eval('?>'.$code);

            return true;
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
