<?php

define("FILE_USER_INPUT", "user_input.ini");

class UserInput
{
	private $user_input;
	private $input_loaded = false;
	
	public function hasInput() {
		return is_file(FILE_USER_INPUT);
	}
	
	public function loadInput() {
		$this->user_input = parse_ini_file(FILE_USER_INPUT, true);
		$this->input_loaded = true;
	}
	
	public function saveInput() {
		writeConfigToFile($this->user_input, FILE_USER_INPUT);
	}		
	
	public function get($key) {
		return $this->user_input[$key];
	}
	
	public function set($key, $value) {
		return $this->user_input[$key] = $value;
	}
	
	public function getAll() {
		return $this->user_input;
	}
	
	public function isInputLoaded() {
		return $this->input_loaded;
	}
	
	public function getInput($key, $request_text, $not_valid_text, $validator = null, $default = '') {
		if (isset($key) && isset($this->user_input[$key])) {
			return $this->user_input[$key];
		}
		
		if (isset($validator) && !empty($default)) $validator->emptyIsValid = true;
		
		$inputOk = false;
		while (!$inputOk) {
			logMessage(L_USER, $request_text, true);
			echo PHP_EOL.'> ';
			$input = trim(fgets(STDIN));
			echo PHP_EOL;
			logMessage(L_INFO, "User input is $input");
			
			if (isset($validator) && !$validator->validateInput($input)) {
				logMessage(L_USER, $not_valid_text);
			} else {			
				$inputOk = true;
				if (empty($input) && !empty($default)) {
					$input = $default;
					logMessage(L_INFO, "Using default value: $default");
				}	
			}				
		}
		
		if (isset($key)) $this->user_input[$key] = $input;
  		return $input;	
	}
	
	/**
	 * Get a y/n input from the user
	 * @param string $request_text text to display
	 * @param string $default should be y/n according to desired default when user input is empty
	 * @return boolean true/false according to input (y/n)
	 */
	public function getTrueFalse($key, $request_text, $default) {	
		if (isset($key) && isset($this->user_input[$key])) {
			return $this->user_input[$key];
		}			
		
		if ((strcasecmp('y', $default) === 0) || (strcasecmp('yes', $default) === 0)) {
			$request_text .= ' (Y/n)';
		} else {
			$request_text .= ' (y/N)';
		}
		
		$validator = InputValidator::createYesNoValidator();
		$input = $this->getInput(null, $request_text, "Input is not valid", $validator, $default);
		$retrunVal = ((strcasecmp('y',$input) === 0) || (strcasecmp('yes',$input) === 0));
		
		if (isset($key)) $this->user_input[$key] = $retrunVal;
		return $retrunVal;		
	}		
}