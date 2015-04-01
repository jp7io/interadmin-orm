<?php

namespace Jp7;

use Symfony\Component\DomCrawler\Crawler;
use Date, InvalidArgumentException;

class FormInspector {
	
	public function crawler($url, $filter) {
		$html = file_get_contents($url);
		return $this->crawlerFromHtml($html, $filter);
	}
	
	public function crawlerFromHtml($html, $filter) {
		$crawler = new Crawler($html);
		return $crawler->filter($filter);
	}
	
	public function info(Crawler $form) {
		$fields = [];
		
		$form->filter('input,select')->each(function($input) use (&$fields) {
			if ($name = $input->attr('name')) {
				$fields[$name] = $this->fieldInfo($input);
			}
		});
		return $fields;
	}
	
	public function fieldInfo(Crawler $input) {
		$info = (object) [
			'value' => '',
			'type' => ''
		];
		
		$info->tag = $input->getNode(0)->nodeName;
		if ($info->tag == 'select') {
			$select = $this->selectInfo($input);
			$info->options = $select->options;
			$info->value = $select->selected;			
		} else {
			$info->type = $input->attr('type');
			$info->value = $input->attr('value');
		}
		return $info;
	}
	
	public function selectInfo(Crawler $input) {
		$return = (object) [
			'options' => [],
			'selected' => null
		];
		
		$input->filter('option')->each(function($option, $i) use ($return) {
			if ($value = $option->attr('value')) {
				if ($option->attr('selected') || $i == 0) {
					$return->selected = $value;
				}
				$return->options[$value] = $option->text();
			}
		});
		
		return $return;
	}
		
	public function compare($ar1, $ar2, $ignoreList = []) {
		foreach ($ar2 as $name => $data) {
			if (!array_key_exists($name, $ar1)) {
				continue;
			}
			$other = $ar1[$name];
			$ignore = isset($ignoreList[$name]) ? $ignoreList[$name] : '';
			
			if ($data->value != $other->value && $ignore != 'value') {
				// Valor diferente
				if ($data->type == 'date' || $other->type == 'date') {
					if ($this->normalizeDate($data->value) != $this->normalizeDate($other->value)) {
						continue; // errado
					}
				} elseif ($data->type != 'checkbox' ) {
					continue; // errado
				}
			}
			if ($data->tag == 'select') {
				// Verificar options
				if ($other->tag != 'select') {
					$other->options = [];
				}
				if ($ignore == 'options' || array_keys($data->options) == array_keys($other->options)) {
					unset($ar2[$name]);
				} else {
					$data->options_diff = array_diff_key($data->options, $other->options);					
				}				
			} else {
				// Se chegou aqui eh input com value igual
				unset($ar2[$name]);
			}
		}
		return $ar2;
	}
	
	private function normalizeDate($value) {
		try {
			return Date::createFromFormat('d/m/Y', $value);
		} catch (InvalidArgumentException $e) {
			// proximo
		}
		try {
			return Date::createFromFormat('Y-m-d', $value);
		} catch (InvalidArgumentException $e) {
			return $value;
		}
	}
}