<?php

class Jp7_InterAdmin_JSTree {
	public $tree = array();
	public $tipos = array();
	public $options = array();
	
	public function __construct($options = array()) {
		global $lang;
		
		$this->options = $options;
		
		if (!$options['static']) {
			$this->addTipo($this->tree, new InterAdminTipo(0));
		}
	}
		
	public function addTipo(&$tree, $parentTipo, $nivel = 0) {
		global $lang;
		
		$options = array(
			'fields' => array('nome', 'parent_id_tipo', 'model_id_tipo', 'icone'),
			'use_published_filters' => true,
			'class' => 'InterAdminTipo'
		);
		
		if ($nivel == 0) {
			$options['where'][] = ($this->options['admin']) ? "admin <> ''" : "admin = ''"; 
		}		
		if ($nivel < 3) {
			$options = InterAdmin::mergeOptions($this->options, $options);
		}	
		if ($lang->prefix) {
			$options['fields'][] = 'nome' . $lang->prefix; 
		}
		
		$tipos = $parentTipo->getChildren($options);
		foreach ($tipos as $tipo) {
			// Criando o Node JSON
			$nome_lang = ($lang->prefix && $tipo->{'nome' . $lang->prefix}) ? $tipo->{'nome' . $lang->prefix} : $tipo->nome;
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
	
	public function createTipoNode($nome_lang, $tipo) {
		$node = (object) array(
			'data' => array(
				'title' => utf8_encode($nome_lang)
			),
			'attr' => array(
				'id' => $tipo->id_tipo
			),
			'metadata' => array(
				//'id_tipo' => $tipo->id_tipo,
				'model_id_tipo' => $tipo->model_id_tipo
			),
			'children' => array()
		);
		if ($tipo->icone) {
			$node->data['icon'] = $this->getIconeUrl($tipo->icone);
		}
		return $node;
	}
	
	public function toJson(){
		return json_encode($this->tree);	
	}
	
	public function addNode($label, $callback = '', $icone = '') {
		$node = $this->createNode($label, $callback, $icone);
		$this->tree[] = $node;
		return $node;
	}
	
	public function createNode($label, $callback = '', $icone = '') {
		$node = (object) array(
			'data' => array(
				'title' => Jp7_Utf8::encode($label)
			),
			'metadata' => array(
				'callback' => utf8_encode($callback)
			)
		);
		if ($icone) {
			$node->data['icon'] = $this->getIconeUrl($icone);
		}
		return $node;
	}
	
	public function getIconeUrl($icone) {
		return '/_default/img/icons/' . $icone . '.png';
	}
}