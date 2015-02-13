<?php

namespace Jp7\Former;

/**
 * Handles validation and redirection
 */
class FormRequest {
	protected $rules = [];
	
	protected $validator;
	/**
	 * @var array
	 */
	protected $messages = [];

	protected $input;

	public function __construct(\InterAdmin $model) {
		$this->rules = $model->getRules();
		$this->input = \Input::all();
	}

	public function fails() {
		$this->validator = \Validator::make($this->input, $this->rules, $this->messages);
		
	   	return $this->validator->fails();
	}

	public function getRules() {
		return $this->rules;
	}

	public function setRules(array $rules) {
		$this->rules = $rules;
	}

	public function getMessages() {
		return $this->messages;
	}

	public function setMessages(array $messages) {
		$this->messages = $messages;
	}

	public function redirect() {
		return \Redirect::route($this->getRouteBack())
			->withInput()
			->withErrors($this->validator);
	}
	
	public function all() {
		return $this->input;
	}
	
	protected function getRouteBack() {
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