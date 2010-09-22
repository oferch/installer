<?php

class UserInputUtils
{		
	/**
	 * Get a y/n input from the user
	 * @param string $request_text text to display
	 * @param string $default should be y/n according to desired default when user input is empty
	 * @return boolean true/false according to input (y/n)
	 */
	public static function getTrueFalse($key, $request_text, $default)
	{	
		global $user_input;
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}

		$retrunVal = null;
		$input = self::getInput(null, $request_text);
		if ((strcasecmp('y',$input) === 0) || strcasecmp('yes',$input) === 0) {
			logMessage(LOG_INFO, "User selected: yes");
			$retrunVal = true;
		}
		else if (((strcasecmp('n',$input) === 0) || strcasecmp('no',$input) === 0)) {
			logMessage(LOG_INFO, "User selected: no");
			$retrunVal = false;
		}
		else {
			logMessage(LOG_INFO, "Using default value: $default");
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
	public static function getInput($key, $request_text, $default = '')
	{
		global $user_input;
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}
		
		logMessage(LOG_USER, $request_text.PHP_EOL.'> ');
  		$input = trim(fgets(STDIN));
		
		logMessage(LOG_INFO, "User input is $input");
		if ($input == '') {			
			$input = $default;
			logMessage(LOG_INFO, "No input, using default value: $default");
		}
		
		if (isset($key)) $user_input[$key] = $input;
  		return $input;
	}
	
	/**
	 * Execute 'which' on each of the given file names and first one found
	 * @param unknown_type $file_name
	 * @return string which output or null if none found
	 */
	private static function getFirstWhich($key, $file_name)
	{
		global $user_input;
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}
		
		$returnVal = null;
		if (!is_array($file_name)) {
			$file_name = array ($file_name);
		}
		foreach ($file_name as $file) {
			$which_path = FileUtils::exec("which $file");
			if (isset($which_path[0]) && trim($which_path[0]) != '') {
				$returnVal = $which_path[0];
				logMessage(LOG_INFO, "Found $file, using it");
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
	public static function getPathInput($key, $request_text, $must_exist, $is_dir, $which_name = null)
	{
		global $user_input;
		if (isset($key) && isset($user_input[$key])) {
			return $user_input[$key];
		}
		
		$input_ok = false;
		$which_path = false;
		
		while (!$input_ok) {
			logMessage(LOG_USER, $request_text);
			
			// execute 'which'
			if ($must_exist && $which_name) {				
				$which_path = self::getFirstWhich(null, $which_name);
				if (substr($which_path, 0, 1) != '/') {
					$which_path = false;
					logMessage(LOG_INFO, "Did not find any of the default binaries");
				}
				if ($which_path) {
					logMessage(LOG_USER, "Leave empty for [$which_path]");
				}
			}
			echo '> ';
			
			// get user input
			$input = trim(fgets(STDIN));
			
			// if input is empty, replace with which output
			if ($which_path && trim($input) == '') {
				logMessage(LOG_INFO, "No input, using default: $which_part");
				$input = $which_path;
			}
			
			$input = InstallUtils::fixPath($input, '/');
			logMessage(LOG_INFO, "User entered path $input");
			
			// check if not a path
			if (substr($input, 0, 1) != '/') {
				logMessage(LOG_USER, "The path you inserted is not full (should begin with a '/'). Please try again.");
			}
			// check if exists
			else if ($must_exist) {
				if ($is_dir) {
					if (!is_dir($input)) {
						logMessage(LOG_USER, "The path you inserted is not valid. Please try again.");
					}
					else {
						$input_ok = true;
					}
				}
				else {
					if (!is_file($input)) {
						logMessage(LOG_USER, "The path you inserted is not valid. Please try again.");
					}
					else {
						$input_ok = true;
					}
				}
			}
			else {
				$input_ok = true;
				logMessage(LOG_INFO, "Path is valid, using $input");
			}
		}
		
		if (isset($key)) $user_input[$key] = $input;		
		return $input;
	}	
}