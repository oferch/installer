<?php

define('EMAIL_REGEX','/^([\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+\.)*[\w\!\#$\%\&\'\*\+\-\/\=\?\^\`{\|\}\~]+@((((([a-z0-9]{1}[a-z0-9\-]{0,62}[a-z0-9]{1})|[a-z])\.)+[a-z]{2,6})|(\d{1,3}\.){3}\d{1,3}(\:\d{1,5})?)$/i');
define('HOSTNAME_IP_REGEX','/^((([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5])\.){3}([0-9]|[1-9][0-9]|1[0-9]{2}|2[0-4][0-9]|25[0-5]))|((([a-zA-Z0-9]|[a-zA-Z0-9][a-zA-Z0-9\-]*[a-zA-Z0-9])\.)*([A-Za-z]|[A-Za-z][A-Za-z0-9\-]*[A-Za-z0-9]))$/');
define('WHITESPACE_REGEX', '/\s/');

/*
* This class validates input according to the validation parameters set
*/
class InputValidator {
	public $emptyIsValid = false;
	public $validateNumberRange = false;
	public $validateHostnameOrIp = false;
	public $validateEmail = false;
	public $validateFileExists = false;
	public $validateNoWhitespace = false;
	public $validateDirectory = false;
	public $validateRegex = false;
	public $numberRange;
	public $validateTimezone = false;

	// validates the input according to the validations set, returns true if input is valid, false otherwise
	// please note that currently it is not possible to run multiple validations
	public function validateInput($input) {
		if (empty($input)) {
			return $this->emptyIsValid;
		}

		$valid = true;
		if ($this->validateNumberRange) $valid = is_numeric($input) && ($input >= $this->numberRange[0]) && ($input <= $this->numberRange[1]);
		else if ($this->validateHostnameOrIp) $valid = (preg_match(HOSTNAME_IP_REGEX, parse_url($input, PHP_URL_HOST) ? parse_url($input, PHP_URL_HOST) : $input) === 1);
		else if ($this->validateEmail) $valid = (preg_match(EMAIL_REGEX, $input) === 1);
		else if ($this->validateFileExists) $valid = is_file($input);
		else if ($this->validateNoWhitespace) $valid = (preg_match(WHITESPACE_REGEX, $input) === 0);
		else if ($this->validateDirectory) $valid = is_dir(dirname($input));
		else if ($this->validateRegex) $valid = (preg_match($this->validateRegex, $input) === 1);
		else if ($this->validateTimezone) $valid = $this->isValidTimezone($input);

		return $valid;
	}

	// static validators creation functions

	public static function createNoWhitespaceValidator() {
		$validator = new InputValidator();
		$validator->validateNoWhitespace = true;
		return $validator;
	}

	public static function createEmailValidator($emptyIsValid) {
		$validator = new InputValidator();
		$validator->emptyIsValid = $emptyIsValid;
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
		$validator->validateHostnameOrIp = true;
		return $validator;
	}

	public static function createEnumValidator(array $values, $enableMultipleChoice = false) {
		$validator = new InputValidator();
		$values = array_map('trim', $values);
		$values = array_map('strtolower', $values);
		$values = array_unique($values);

		$options = '(' . implode('|', $values) . ')';
		if($enableMultipleChoice)
			$validator->validateRegex = "/^$options$/";
		else
			$validator->validateRegex = "/^$options(,$options)*$/";

		return $validator;
	}

	public static function createCharactersValidator(array $chars, $maxChars = null) {
		$validator = new InputValidator();
		$chars = array_unique($chars);
		$validator->validateRegex = '/^[' . implode('', $chars) . ']{1,$maxChars}$/';
		return $validator;
	}

	public static function createYesNoValidator() {
		$validator = self::createEnumValidator(array('y', 'yes', 'n', 'no'));
		$validator->emptyIsValid = true;
		return $validator;
	}

	public static function createFileValidator() {
		$validator = new InputValidator();
		$validator->validateFileExists = true;
		return $validator;
	}

	public static function createTimezoneValidator() {
		$validator = new InputValidator();
		$validator->validateTimezone = true;
		return $validator;
	}

	private function isValidTimezone($timezoneId)
	{
		$savedZone = date_default_timezone_get();
  		$res = $savedZone == $timezoneId;
  		if (!$res)
  		{
    		@date_default_timezone_set($timezoneId);
    		$res = date_default_timezone_get() == $timezoneId;
    	}
  		date_default_timezone_set($savedZone);
  		return $res;
	}
}
