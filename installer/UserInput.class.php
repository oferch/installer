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
		$user_input = parse_ini_file(FILE_USER_INPUT, true);
		$input_loaded = true;
	}
	
	public function saveInput() {
		writeConfigToFile($user_input, FILE_USER_INPUT);
	}		
	
	public function get($key) {
		return $user_input[$key];
	}
	
	public function set($key, $value) {
		return $user_input[$key] = $value;
	}
	
	public function getAll() {
		return $user_input;
	}
	
	public function isInputLoaded() {
		return $input_loaded;
	}
	
	/**
	 * Get a y/n input from the user
	 * @param string $request_text text to display
	 * @param string $default should be y/n according to desired default when user input is empty
	 * @return boolean true/false according to input (y/n)
	 */
	public function getTrueFalse($key, $request_text, $default) {	
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}

		$retrunVal = null;
		$request_text_with_default = $request_text;
		if ((strcasecmp('y', $default) === 0) || (strcasecmp('yes', $default) === 0)) {
			$request_text_with_default = $request_text_with_default.' (Y/n)';
		} else {
			$request_text_with_default = $request_text_with_default.' (y/N)';
		}
		
		$input = self::getInput(null, $request_text_with_default);
		if ((strcasecmp('y',$input) === 0) || strcasecmp('yes',$input) === 0) {
			logMessage(L_INFO, "User selected: yes");
			$retrunVal = true;
		} else if (((strcasecmp('n',$input) === 0) || strcasecmp('no',$input) === 0)) {
			logMessage(L_INFO, "User selected: no");
			$retrunVal = false;
		} else {
			logMessage(L_INFO, "Using default value: $default");
			$retrunVal = ((strcasecmp('y',$default) === 0) || strcasecmp('yes',$default) === 0);
		}
		
		if (isset($key)) $user_input[$key] = $retrunVal;
		return $retrunVal;		
	}	
	
	/**
	 * Get an input from the user
	 * @param string $request_text text to display
	 * @return string user input
	 */
	public function getInput($key, $request_text, $default = '') {
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}
		
		logMessage(L_USER, $request_text.PHP_EOL.'> ', true);
  		$input = trim(fgets(STDIN));
		
		logMessage(L_INFO, "User input is $input");
		if ($input == '') {			
			$input = $default;
			logMessage(L_INFO, "No input, using default value: $default");
		}
		
		if (isset($key)) $user_input[$key] = $input;
  		return $input;
	}
	
	/**
	 * Execute 'which' on each of the given file names and first one found
	 * @param unknown_type $file_name
	 * @return string which output or null if none found
	 */
	private function getFirstWhich($key, $file_name) {
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}
		
		$returnVal = null;
		if (!is_array($file_name)) {
			$file_name = array ($file_name);
		}
		foreach ($file_name as $file) {
			$which_path = OsUtils::executeReturnOutput("which $file");
			if (isset($which_path[0]) && trim($which_path[0]) != '') {
				$returnVal = $which_path[0];
				logMessage(L_INFO, "Found $file, using it");
				break;
			}
		}
		
		if (isset($key)) $user_input[$key] = $returnVal;
		return $returnVal;		
	}
	
	/**
	 * Get input of a directory/file path
	 * @param string $request_text
	 * @param boolean $must_exist true/false if the path must exist - if doesn't exist, it will be requested again
	 * @param boolean $is_dir true if is dir, false if file
	 * @param string or string[] $which_name file names to look for with 'which' and offer to the user as defaults when found
	 * @return string user input
	 */
	public function getPathInput($key, $request_text, $must_exist, $is_dir, $which_name = null, $default = null) {
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}
		
		$input_ok = false;
		$which_path = false;
			
		// execute 'which' to find binaries
		if ($must_exist && $which_name) {				
			$which_path = self::getFirstWhich(null, $which_name);
			if ($which_path && (substr($which_path, 0, 1) == '/')) {
				$request_text = $request_text." (installation found $which_path, leave empty to use it)";
			}
		} else if ($default) {
			$which_path = $default;
		}
		
		while (!$input_ok) {
			logMessage(L_USER, $request_text.PHP_EOL.'> ', true);
			
			// get user input
			$input = trim(fgets(STDIN));
			
			// if input is empty, replace with which output
			if ($which_path && trim($input) == '') {
				logMessage(L_INFO, "No input, using default: $which_path");
				$input = $which_path;
			}
			
			logMessage(L_INFO, "User entered path $input");
			
			// check if not a path
			if (substr($input, 0, 1) != '/') {
				logMessage(L_USER, "The path you inserted is not full (should begin with a '/'). Please try again.");
			}
			// check if exists
			else if ($must_exist) {
				if ($is_dir) {
					if (!is_dir($input)) {
						logMessage(L_USER, "The path you inserted is not valid. Please try again.");
					} else {
						$input_ok = true;
					}
				} else {
					if (!is_file($input)) {
						logMessage(L_USER, "The path you inserted is not valid. Please try again.");
					} else {
						$input_ok = true;
					}
				}
			} else {
				$input_ok = true;
				logMessage(L_INFO, "Path is valid, using $input");
			}
		}
		
		if (isset($key)) $user_input[$key] = $input;		
		return $input;
	}
}