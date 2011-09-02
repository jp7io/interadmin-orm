<?php

class Jp7_InterAdmin_JSTree {
	public $tree = array();
	public $tipos = array();
	
	public function __construct($options = array()) {
		global $lang;
		
		if (!$options['static']) {
			$options = InterAdmin::mergeOptions(array(
				'fields' => array('nome', 'parent_id_tipo', 'model_id_tipo', 'admin'),
				'use_published_filters' => true,
				'class' => 'InterAdminTipo'	
			), $options);
			
			if ($lang->prefix) {
				$options['fields'][] = 'nome' . $lang->prefix; 
			}
			
			$all = InterAdminTipo::findTipos($options);
									
			$this->tipos = self::groupByParent($all);
		}
	}
	
	public static function groupByParent($all) {
		$tipos = array();
		foreach ($all as $one) {
			$tipos[$one->parent_id_tipo][] = $one;
		}
		return $tipos;
	}
	
	public function addTipo(&$tree, $tipo, $nivel = 0) {
		global $tipos, $lang;
		
		$nome_lang = ($lang->prefix && $tipo->{'nome' . $lang->prefix}) ? $tipo->{'nome' . $lang->prefix} : $tipo->nome;
		$node = (object) array(
			'data' => utf8_encode($nome_lang),
			'attr' => array(
				'id' => $tipo->id_tipo
			),
			'metadata' => array(
				//'id_tipo' => $tipo->id_tipo,
				'model_id_tipo' => $tipo->model_id_tipo
			),
			'children' => array()
		);
		/*
		if (in_array($tipo->id_tipo, $s_session['tree_opened'])) {
			$node->state = 'open';	
		}
		*/
		
		$children = $this->tipos[$tipo->id_tipo];
		if ($children) {
			$nivel++;
			foreach ($children as $childTipo) {
				$this->addTipo($node->children, $childTipo, $nivel);
			}
		}
		
		if (!$node->children) {
			unset($node->children); // Bug jsTree progressive_render
		}
		
		$tree[] = $node;
	}
	
	public function createTree() {
		if (!$this->tree) {
			if ($this->tipos[0]) {
				foreach ($this->tipos[0] as $tipo) {
					$this->addTipo($this->tree, $tipo);
				}
			}
		}
		return $this->tree;
	}
	
	public function toJson(){
		return json_encode($this->createTree());	
	}
	
	public function addNode($nome, $callback = array()) {
		$node = $this->createNode($nome, $callback);
		$this->tree[] = $node;
		return $node;
	}
	
	public function createNode($nome, $callback = array()) {
		return (object) array(
			'data' => Jp7_Utf8::encode($nome),
			'metadata' => array(
				'callback' => utf8_encode($callback)
			)
		);
	}
}