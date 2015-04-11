<?php

namespace Jp7\Former;

/**
 * Handles validation and redirection
 */
class FormRequest {
	protected $validator;
	
	protected $input;
	protected $model;
	
	public function __construct(\InterAdmin $model) {
		$this->model = $model;
	}
	
	public function save() {
		if (!$this->validator()->fails()) {
			return $this->model
		  		->fill($this->input())
		  		->save();	
		}
	}
	
	public function redirect() {
		return \Redirect::route($this->backRoute())
			->withInput()
			->withErrors($this->validator());
	}
	
	public function input() {
		if (is_null($this->input)) {
			$this->input = \Input::all();
		}
		return $this->input;
	}
	
	public function validator() {
		if (is_null($this->validator)) {
			$this->validator = \Validator::make(
				$this->input(),
				$this->model->getRules(),
				[] // messages
			);
		}
		return $this->validator;
	}
	
	protected function backRoute() {
		$route = \Route::getCurrentRoute()->getAction()['as'];
		$parts = explode('.', $route);

		$action = array_pop($parts);
		if ($action === 'store') {
			array_push($parts, 'create');
		} elseif ($action === 'update') {
			array_push($parts, 'edit');
		}
		return implode('.', $parts);
	}
}