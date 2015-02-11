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

	public function __construct(\InterAdminTipo $type) {
		$this->rules = $type->getRules();
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
		return \Redirect::back()->withInput()->withErrors($this->validator);
	}

	public function all() {
		return $this->input;
	}

}