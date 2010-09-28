<?php

define('EMAIL_REGEX','/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i');
define('IP_REGEX','/^(([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])$/');
define('HOSTNAME_REGEX', '/^(([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9])$/');
define('WHITESPACE_REGEX', '/\s/');
define('YESNO_REGEX', '/^(y|yes|n|no)$/i');

class InputValidator {
	public $emptyIsValid = false;
	public $validateNumberRange = false;
	public $validateHostname = false;
	public $validateIp = false;
	public $validateEmail = false;
	public $validateFileExists = false;
	public $validateNoWhitespace = false;
	public $validateDirectory = false;
	public $validateYesNo = false;
	public $numberRange;
	
	public static function createNoWhitespaceValidator() {
		$validator = new InputValidator();
		$validator->emptyIsValid = true;
		$validator->validateUrl = true;
		return $validator;
	}
	
	public static function createEmailValidator() {
		$validator = new InputValidator();
		$validator->validateEmail = true;
		return $validator;
	}
	
	public static function createDirectoryValidator() {
		$validator = new InputValidator();
		$validator->validateDirectory = true;
		return $validator;
	}
	
	public static function createNonEmptyValidator() {
		$validator = new InputValidator();
		return $validator;
	}
	
	public static function createRangeValidator($from, $to) {
		$validator = new InputValidator();
		$validator->validateNumberRange = true;
		$validator->numberRange = array($from, $to);
		return $validator;
	}
	
	public static function createHostValidator() {
		$validator = new InputValidator();
		$validator->validateIp = true;
		$validator->validateHostname = true;
		return $validator;
	}
	
	public static function createYesNoValidator() {
		$validator = new InputValidator();
		$validator->emptyIsValid = true;
		$validator->validateYesNo = true;
		return $validator;
	}

	public static function createFileValidator($emptyIsValid) {
		$validator = new InputValidator();
		$validator->emptyIsValid = $emptyIsValid;
		$validator->validateFileExists = true;
		return $validator;
	}
	
	public function validateInput($input) {	
		if (empty($input)) {
			return $this->emptyIsValid;
		}
		
		$notValid = ($this->validateNumberRange && 
			(!is_numeric($input) || ($input < $this->numberRange[0]) || ($input > $this->numberRange[1]))) ||
					($this->validateHostname && !preg_match(HOSTNAME_REGEX, $input)) ||
					($this->validateIp && !preg_match(IP_REGEX, $input)) ||
					($this->validateEmail && !preg_match(EMAIL_REGEX, $input)) ||
					($this->validateFileExists && !is_file($input)) ||
					($this->validateNoWhitespace && preg_match(WHITESPACE_REGEX, $input)) ||
					($this->validateDirectory && !is_dir(dirname($input)) ||
					($this->validateYesNo && !preg_match(YESNO_REGEX, $input)));
		
		return !notValid;
	}
}	
