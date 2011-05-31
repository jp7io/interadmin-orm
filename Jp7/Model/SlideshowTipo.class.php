<?php
class Jp7_Model_SlideshowTipo extends Jp7_Model_TipoAbstract {
	public $attributes = array(
		'id_tipo' => 'Slideshow',
		'nome' => 'Slideshow',
		'campos' => 'varchar_key{,}Nome{,}{,}{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}name{;}file_1{,}Imagem{,}{,}{,}S{,}S{,}0{,}S{,}{,}{,}{,}{,}{,}{,}{,}image{;}varchar_1{,}Link{,}{,}{,}S{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}link{;}text_2{,}Título{,}{,}2{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}title{;}text_1{,}Texto{,}{,}2{,}{,}S{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}text{;}int_key{,}Ordem{,}{,}{,}{,}{,}0{,}{,}1{,}{,}{,}{,}{,}{,}{,}ordem{;}char_key{,}Mostrar{,}{,}{,}{,}{,}0{,}{,}{,}{,}{,}{,}{,}{,}{,}mostrar{;}',
		'children' => '',
		'arquivos_ajuda' => '',
		'arquivos' => '',
		'template' => '',
		'editpage' => '',
		'class' => '',
		'class_tipo' => '',
		'model_id_tipo' => 0,
		'tabela' => '',
		'editar' => 'S'
	);
}