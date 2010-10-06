<?php

define("FILE_USER_INPUT", "user_input.ini"); // this file contains the saved user input

/*
* This class handles all the user input
*/
class UserInput
{
	private $user_input;
	private $input_loaded = false;

	// return true if user input is already loaded
	public function hasInput() {
		return is_file(FILE_USER_INPUT);
	}
	
	// load input from user input file
	public function loadInput() {
		$this->user_input = parse_ini_file(FILE_USER_INPUT, true);
		$this->input_loaded = true;
	}
	
	// save the user input to input file
	public function saveInput() {
		OsUtils::writeConfigToFile($this->user_input, FILE_USER_INPUT);
	}		
	
	// get the input for the given $key
	public function get($key) {
		return $this->user_input[$key];
	}
	
	// set the input $value for the given $key
	public function set($key, $value) {
		return $this->user_input[$key] = $value;
	}
	
	// returns the user input array
	public function getAll() {
		return $this->user_input;
	}
	
	// returns true if user input is loaded
	public function isInputLoaded() {
		return $this->input_loaded;
	}
	
	// gets input from the user and returns it
	// if $key was already loaded from config it will be taked from there and user will not have to insert
	// $request text - text to show the user
	// $not_valid_text - text to show the user if the input is invalid (according to the validator)
	// $validator - the InputValidator to user (default is null, no validation)
	// $default - the default value (default's default is '' :))
	public function getInput($key, $request_text, $not_valid_text, $validator = null, $default = '') {
		if (isset($key) && isset($this->user_input[$key])) {
			return $this->user_input[$key];
		}
		
		if (isset($validator) && !empty($default)) $validator->emptyIsValid = true;
		
		logMessage(L_USER, $request_text);
			
		$inputOk = false;
		while (!$inputOk) {
			echo '> ';
			$input = trim(fgets(STDIN));
			logMessage(L_INFO, "User input is $input");
			
			if (isset($validator) && !$validator->validateInput($input)) {
				logMessage(L_USER, $not_valid_text);
			} else {			
				$inputOk = true;
				echo PHP_EOL;				
				if (empty($input) && !empty($default)) {
					$input = $default;
					logMessage(L_INFO, "Using default value: $default");
				}	
			}				
		}
		
		if (isset($key)) $this->user_input[$key] = $input;
  		return $input;	
	}
	
	// Get a y/n input from the user
	// if $key was already loaded from config it will be taked from there and user will not have to insert
	// $request text - text to show the user	
	// $default - the default value (show be 'y'/'n')
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
	
	// get all the user input for the installation
	public function getApplicationInput() {
		$httpd_bin_found = OsUtils::findBinary(array('apachectl', 'apache2ctl'));
		$httpd_bin_message = "The full pathname to your apachectl script";
		if (!empty($httpd_bin_found)) {
			$httpd_bin_message .= PHP_EOL."Installer found $httpd_bin_found, leave empty to use it";
		}
		$php_bin_found = OsUtils::findBinary('php');
		$php_bin_message = "The full pathname to your PHP binary file";
		if (!empty($php_bin_found)) {
			$php_bin_message .= PHP_EOL."Installer found $php_bin_found, leave empty to use it";
		}

		logMessage(L_USER, "Please provide the following information:");
		echo PHP_EOL;
		
		$this->getInput('HTTPD_BIN', 
						$httpd_bin_message, 
						'Invalid pathname for apachectl', 
						InputValidator::createFileValidator(), 
						$httpd_bin_found);		
		$this->getInput('PHP_BIN', 
						$php_bin_message, 
						'PHP binary must exist', 
						InputValidator::createFileValidator(), 
						$php_bin_found);
		$this->getInput('BASE_DIR', 
						"The full directory path for Kaltura application (Leave empty for /opt/kaltura)",
						"Target directory must be a valid directory path", 
						InputValidator::createDirectoryValidator(), 
						'/opt/kaltura');
		$this->getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', 
						"Please enter the domain name/virtual hostname that will be used for the Kaltura server (without http://)", 
						'Must be a valid hostname or ip', 
						InputValidator::createHostValidator(), 
						null);
		// TODO: remove? not printing: A primary system administrator user will be created with full access to the Kaltura Administration Console.\nAdministrator e-mail
		$this->getInput('ADMIN_CONSOLE_ADMIN_MAIL', 
						"Your primary system administrator email address", 
						"Email must be in a valid email format", 
						InputValidator::createEmailValidator(false), 
						null);
		$this->getInput('ADMIN_CONSOLE_PASSWORD', 
						"The password you want to set for your primary administrator", 
						"Password cannot be empty or contain whitespaces", 
						InputValidator::createNoWhitespaceValidator(), 
						null);
		$this->getInput('DB1_HOST', 
						"Database host (Leave empty for 'localhost')", 
						'Must be a valid hostname or ip', 
						InputValidator::createHostValidator(), 
						'localhost');
		$this->getInput('DB1_PORT', 
						"Database port (Leave empty for '3306')", 
						'Must be a valid port (1-65535)', 
						InputValidator::createRangeValidator(1, 65535), 
						'3306');
		$this->set('DB1_NAME','kaltura'); // currently we do not support getting the DB name from the user because of the DWH implementation
		$this->getInput('DB1_USER', 
						"Database username (With create & write privileges)", 
						"DB user cannot be empty", 
						InputValidator::createNonEmptyValidator(), 
						null);
		$this->getInput('DB1_PASS', 
						"Database password (Leave empty for no password)", 
						null, 
						null, 
						null);
		$this->getInput('XYMON_URL', 
						"The URL to your xymon/hobbit monitoring location.\nXymon is an optional installation. Leave empty to set manually later\nExamples:\nhttp://www.xymondomain.com/xymon/\nhttp://www.xymondomain.com/hobbit/", 
						null, 
						null, 
						null);
		$this->saveInput();	
	}
}