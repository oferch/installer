<?php
define("FILE_PREREQUISITES_CONFIG", "installer/prerequisites.ini"); // this file contains the definitions of the prerequisites that are being checked

/*
* This class handles prerequisites verifications
*/
class Prerequisites {
	private $prerequisites_config;

	// crteate a new preqrequisites verifier, loads prerequisites definitions from file
	public function __construct() {
		$this->prerequisites_config = parse_ini_file(FILE_PREREQUISITES_CONFIG, true);
	}

	// verifies the prerequisites using the given $app_config (AppConfig) and $db_params
	// returns null if everything is OK or a string with the failing prerequisites	
	public function verifyPrerequisites($app_config, $db_params) {
		$prerequisites = "";		
		
		$this->checkPhpVersion($prerequisites);
		$this->checkPhpExtensions($prerequisites);
		
		if (!$this->mysqli_ext_exists) {
			$prerequisites .= "   Cannot check MySQL connection, version and settings because PHP mysqli extension is not loaded".PHP_EOL;
		} else if ($this->checkMySqlConnection($db_params, $prerequisites)) {
			$this->checkMysqlVersion($db_params, $prerequisites);
			$this->checkMySqlSettings($db_params, $prerequisites);		
		}
		$this->checkApacheModules($app_config->get('HTTPD_BIN'), $prerequisites);
		$this->checkBins($prerequisites);		
		$this->checkPentaho($prerequisites);		
	
		if (empty($prerequisites)) {
			logMessage(L_INFO, "All prerequisites checks passed");	
			return null;
		} else {	
			return $prerequisites;							
		}
	}
		
	// private functions
	
	// checks if needed php extensions exist
	private function checkPhpExtensions(&$prerequisites) {
		foreach ($this->prerequisites_config["php_extensions"] as $ext) {
			if (!extension_loaded($ext)) {
				$prerequisites .= "   Missing $ext PHP extension".PHP_EOL;
			} else {
				logMessage(L_INFO, "Preqrequisite passed: PHP extension $ext is loaded");
				if ($ext == 'mysqli') {
					$this->mysqli_ext_exists = true;
				}				
			}
		}
	}
		
	// checks that needed binary files exist (by using 'which')
	private function checkBins(&$prerequisites) {
		foreach ($this->prerequisites_config["binaries"] as $bin) {			
			$path = @exec("which $bin");
			if (trim($path) == '') {
				$prerequisites .= "   Missing $bin binary file".PHP_EOL;
			} else {
				logMessage(L_INFO, "Preqrequisite passed: Binary $bin found");
			}			
		}
	}	
	
	// Check that needed file paths exist
	private function checkPentaho(&$prerequisites) {
		$pentaho = $this->prerequisites_config["pentaho_path"];
		if (!is_file($pentaho)) {
			$prerequisites .= "   Missing pentaho at $pentaho".PHP_EOL;
		} else {
			logMessage(L_INFO, "Preqrequisite passed: Pentaho found at $pentaho");
		}
	}	
	
    // checks that needed apache modules exist, using the given $httpd_bin
	private function checkApacheModules($httpd_bin, &$prerequisites) {
		if (!OsUtils::execute($httpd_bin.' -t')) {
			$prerequisites .= "   Cannot check apache modules, please fix '$httpd_bin -t'".PHP_EOL;
		} else {		
			$current_modules = OsUtils::executeReturnOutput($httpd_bin.' -M');
				
			foreach ($this->prerequisites_config["apache_modules"] as $module) {
				$found = false;
				for ($i=0; !$found && $i<count($current_modules); $i++) {
					if (strpos($current_modules[$i],$module) !== false) {
						$found = true;
					}				
				}
				
				if (!$found) {
					$prerequisites .= "   Missing $module apache module".PHP_EOL;
				} else {
					logMessage(L_INFO, "Preqrequisite passed: Apache module %$module% found");
				}
			}
		}
	}

	private function checkMySqlConnection($db_params, &$prerequisites) {
		if (!DatabaseUtils::connect($link, $db_params, null)) {
			$prerequisites .= "   Failed to connect to DB ".$db_params['db_host'].":".$db_params['db_port']." user:".$db_params['db_user'].PHP_EOL;
			return false;
		} else {
			logMessage(L_INFO, "Preqrequisite passed: Successfully connected to DB ".$db_params['db_host'].":".$db_params['db_port']." user:".$db_params['db_user']);
			return true;
		}		
	}
	
	// check that mySQL settings are set as required using the given $db_params
	private function checkMySqlSettings($db_params, &$prerequisites) {
		if (!DatabaseUtils::connect($link, $db_params, null)) {
			$prerequisites .= "   Cannot check mysql settings, failed to connect to DB".PHP_EOL;
			return;
		}

		$this->checkMysqlSetting($link, "lower_case_table_names", $this->prerequisites_config["lower_case_table_names"], false, $prerequisites);
		$this->checkMysqlSetting($link, "thread_stack", $this->prerequisites_config["thread_stack"], true, $prerequisites);
	}
	
	// checks if the mysql settings $key is as $expected using the db $link
	// if $allow_greater it also checks if the value is greater the the $expected (not only equal)
	private function checkMysqlSetting(&$link, $key, $expected, $allow_greater, &$prerequisites) {
		if ($allow_greater) $op = ">=";
		else $op = "=";
		
		$result = mysqli_query($link, "SELECT @@$key;");
		if ($result === false) {
			$prerequisites .= "   Please set '$key $op $expected' in my.cnf and restart MySQL".PHP_EOL;
		}
		else {			
			$tmp = '@@'.$key;
			$current = $result->fetch_object()->$tmp;
			if ((intval($current) == intval($expected)) || 
				($allow_greater && (intval($current) > intval($expected)))) {
				logMessage(L_INFO, "Preqrequisite passed: MySQL setting $key=$current (should be $op $expected)");			
			} else {
				$prerequisites .= "   Please set '$key $op $expected' in my.cnf and restart MySQL (currently value is $current)".PHP_EOL;

			}
		}		
	}
	
	// check that the php version is ok
	private function checkPhpVersion(&$prerequisites) {	
		$php_min_version = $this->prerequisites_config["php_min_version"];
		if (!(intval(phpversion()) >= intval($php_min_version))) {
			$prerequisites .= "   PHP version should be >= $php_min_version (current version is ".phpversion().")".PHP_EOL;
		} else {
			logMessage(L_INFO, "Preqrequisite passed: PHP version is OK (".phpversion().")");			
		}
	}	
		
	// checks that the MYSQL version is ok
	private function checkMySqlVersion($db_params, &$prerequisites) {
		if (!DatabaseUtils::connect($link, $db_params, null)) {
			$prerequisites .= "   Cannot MySQL version, failed to connect to DB".PHP_EOL;
			return;
		}

		$key = "@@version";
		$result = mysqli_query($link, "SELECT $key;");
		if ($result === false) {
			$prerequisites .= "   Cannot find MySQL version".PHP_EOL;
			return;
		}

		$mysql_min_version = $this->prerequisites_config["mysql_min_version"];
		$current = $result->fetch_object()->$key;
		if (!(intval($current) >= intval($mysql_min_version))) {
			$prerequisites .= "   MySQL version should be >= $mysql_min_version (current version is $current)".PHP_EOL;
		} else {
			logMessage(L_INFO, "Preqrequisite passed: MySQL version is OK ($current)");
		}
	}
}