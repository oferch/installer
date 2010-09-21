<?php

class UserInputUtils
{	
	// singleton functions
	static $instance = null;
	
	public static function instance() 
	{
		if (!isset($instance)) {
			$instance = new UserInputUtils();
		}
		return $instance;
	}

	// UserInputUtils functions

	public function __construct()
	{
		$config = conf::newConfig();
	}
	
	public function setConfig(&$useConfig) 
	{
		$config = $useConfig;
	}
	
	public function getConfig() {
		return $config;
	}
	
	/**
	 * Get a y/n input from the user
	 * @param string $request_text text to display
	 * @param string $default should be y/n according to desired default when user input is empty
	 * @return boolean true/false according to input (y/n)
	 */
	public function getTrueFalse($key, $request_text, $default)
	{	
		if (isset($key) && isset($config[$key])) {
			return $config[$key];
		}

		$retrunVal = null;
		$input = self::getInput($request_text);
		if ((strcasecmp('y',$input) === 0) || strcasecmp('yes',$input) === 0) {
			$retrunVal = true;
		}
		else if (((strcasecmp('n',$input) === 0) || strcasecmp('no',$input) === 0)) {
			$retrunVal = false;
		}
		else {
			$retrunVal = ((strcasecmp('y',$default) === 0) || strcasecmp('yes',$default) === 0);			
		}
		
		if (isset($key)) $config[$key] = $retrunVal;
		return $retrunVal;		
	}	
	
	/**
	 * Get an input from the user
	 * @param string $request_text text to display
	 * @return string user input
	 */
	public function getInput($key, $request_text, $default = '')
	{
		if (isset($key) && isset($config[$key])) {
			return $config[$key];
		}
		
		echo $request_text.PHP_EOL.'> ';
  		$input = trim(fgets(STDIN));
  		echo PHP_EOL;
		
		if ($input == '') $input = $default;
		
		if (isset($key)) $config[$key] = $input;
  		return $input;
	}
	
	/**
	 * Execute 'which' on each of the given file names and first one found
	 * @param unknown_type $file_name
	 * @return string which output or null if none found
	 */
	private function getFirstWhich($key, $file_name)
	{
		if (isset($key) && isset($config[$key])) {
			return $config[$key];
		}
		
		$returnVal = null;
		if (!is_array($file_name)) {
			$file_name = array ($file_name);
		}
		foreach ($file_name as $file) {
			$which_path = FileUtils::exec("which $file");
			if (isset($which_path[0]) && trim($which_path[0]) != '') {
				$returnVal = $which_path[0];
			}
		}
		
		if (isset($key)) $config[$key] = $returnVal;
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
	public function getPathInput($key, $request_text, $must_exist, $is_dir, $which_name = null)
	{
		if (isset($key) && isset($config[$key])) {
			return $config[$key];
		}
		
		$input_ok = false;
		$which_path = false;
		
		while (!$input_ok) {
			echo $request_text.PHP_EOL;
			
			// execute 'which'
			if ($must_exist && $which_name) {
				$which_path = self::getFirstWhich($which_name);
				if (substr($which_path, 0, 1) != '/') {
					$which_path = false;
				}
				if ($which_path) {
					echo 'Leave empty for ['.$which_path.']'.PHP_EOL;
				}
			}
			echo '> ';
			
			// get user input
			$input = trim(fgets(STDIN));
			
			// if input is empty, replace with which output
			if ($which_path && trim($input) == '') {
				$input = $which_path;
			}
			
			$input = InstallUtils::fixPath($input, '/');
			
			// check if not a path
			if (substr($input, 0, 1) != '/') {
				echo PHP_EOL.InstallerTexts::getText('global', 'not_full_path').PHP_EOL;	
			}
			// check if exists
			else if ($must_exist) {
				if ($is_dir) {
					if (!is_dir($input)) {
						echo PHP_EOL.InstallerTexts::getText('global', 'path_not_valid').PHP_EOL;
					}
					else {
						$input_ok = true;
					}
				}
				else {
					if (!is_file($input)) {
						echo PHP_EOL.InstallerTexts::getText('global', 'path_not_valid').PHP_EOL;
					}
					else {
						$input_ok = true;
					}
				}
			}
			else {
				$input_ok = true;
			}
		}
		echo PHP_EOL;
		
		if (isset($key)) $config[$key] = $input;		
		return $input;
	}	
}