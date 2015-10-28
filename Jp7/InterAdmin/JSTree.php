<?php

class Jp7_InterAdmin_JSTree
{
    public $tree = array();
    public $tipos = array();
    public $options = array();
    protected static $permissionsLevel = 3;

    public function __construct($options = array(), $root_id_tipo = 0)
    {
        global $lang;

        $this->options = $options + ['permissions_level' => static::$permissionsLevel];

        if (!$options['static']) {
            $this->addTipo($this->tree, new InterAdminTipo($root_id_tipo));
        }
    }

    public static function setNivelPermissao($nivel)
    {
        static::$permissionsLevel = $nivel;
    }

    public static function getNivelPermissao()
    {
        return static::$permissionsLevel;
    }

    public function addTipo(&$tree, $parentTipo, $nivel = 0)
    {
        global $lang;

        $options = array(
            'fields' => array('nome', 'parent_id_tipo', 'model_id_tipo', 'icone'),
            'use_published_filters' => true,
            'class' => 'InterAdminTipo',
        );

        if ($nivel == 0) {
            $options['where'][] = ($this->options['admin']) ? "admin <> ''" : "admin = ''";
        }
        if ($nivel < $this->options['permissions_level']) {
            $options = InterAdmin::mergeOptions($this->options, $options);
        }
        if ($lang->prefix) {
            $options['fields'][] = 'nome'.$lang->prefix;
        }

        $tipos = $parentTipo->getChildren($options);
        foreach ($tipos as $tipo) {
            // Criando o Node JSON
            $nome_lang = ($lang->prefix && $tipo->{'nome'.$lang->prefix}) ? $tipo->{'nome'.$lang->prefix} : $tipo->nome;
            $node = $this->createTipoNode($nome_lang, $tipo);
            if (!$node) {
                continue;
            }
            $tree[] = $node;
            // Aqui entra a recursão
            $this->addTipo($node->children, $tipo, $nivel + 1);
            if (count($node->children) == 0) {
                unset($node->children); // Bug jsTree progressive_render
            }
        }
    }

    public function createTipoNode($nome_lang, $tipo)
    {
        $node = (object) [
            'text' => $nome_lang,
                'id' => $tipo->id_tipo,
            'data' => [
                //'id_tipo' => $tipo->id_tipo,
                'model_id_tipo' => $tipo->model_id_tipo,
                'class' => $tipo->class,
            ],
            'children' => [],
        ];
        if ($tipo->icone) {
            $node->icon = $this->getIconeUrl($tipo->icone);
        }

        return $node;
    }

    public function toJson()
    {
        return json_encode($this->tree);
    }

    public function addNode($label, $callback = '', $icone = '')
    {
        $node = $this->createNode($label, $callback, $icone);
        $this->tree[] = $node;

        return $node;
    }

    public function createNode($label, $callback = '', $icone = '')
    {
        $node = (object) [
            'text' => $label,
            'data' => [
                'callback' => $callback,
            ]
        ];
        if ($icone) {
            $node->icon = $this->getIconeUrl($icone);
        }

        return $node;
    }

    public function getIconeUrl($icone)
    {
        return DEFAULT_PATH.'img/icons/'.$icone.'.png';
    }
}
