<?php

class Prerequisites {
	public static $php_version = array('>=', '5.2.0');
	public static $mysql_version = array('>=', '5.1.33');
	public static $apache_version = array('>=', '2.2'); // Currently not checked
		
	public static $files = array (
		'pentaho kitchen.sh' => '/usr/local/pentaho/pdi/kitchen.sh',
	);	
	
	public static $bins = array (
		'curl',
		'mysql',
	);	
	
	public static $php_extensions = array ( 
		'gd',	
		'curl',
		'mysql',
		'mysqli',
		'exif',
		'ftp',
		'iconv',
		'json',
		'session',
		'SPL',
		'dom',
		'SimpleXML',
		'xml',
		'ctype',
	);
		
	public static $apache_modules = array (
		'rewrite_module',
		'headers_module',
		'expires_module', 
		'ext_filter_module',
		'deflate_module',
		'file_cache_module',
		'env_module',
		'proxy_module',
	);	
	
	public static $mysql_settings = array (
		'lower_case_table_names' => array ('=', '1'),
		'thread_stack' => array('>=', '262144'),	
	);
	
		/*
	* This function checks the preqrequisites
	* @config has all the values for verifiying prerequisites
	*/
	public function verifyPrerequisites($config, $db_params) {
		$this->problems = array();				
		
		$httpd_bin = $config['HTTPD_BIN'];
		
		// check prerequisites
		$this->checkPhpVersion();
		$this->checkBins();
		$this->checkPhpExtensions();
		$this->checkApacheModules($httpd_bin);
		$this->checkFiles();		
		
		if ($this->mysqli_ext_exists) {
			$this->checkMysqlVersion($db_params);
			$this->checkMySqlSettings($db_params);			
		}
		else {
			$this->problems['Product versions:'][] = "Cannot check MySQL version because php mysqli extension was not found";
			$this->problems['mySQL settings:'][] = "Cannot check MySQL settings because php mysqli extension was not found";
		}
	
		if (empty($this->problems)) {
			logMessage(L_INFO, "No prerequisites problems");	
			return true;
		}
		else{	
			$error_description = PHP_EOL;
			foreach ($this->problems as $title => $items) {
				$error_description .= $title.PHP_EOL;	
				foreach ($items as $item) {
					$error_description .= "  - $item".PHP_EOL;
				}
			}
			logMessage(L_USER, "Missing prerequisites: $error_description");	
			return false;							
		}
	}
		
	/**
	 * Checks that needed php extensions exist
	 */
	private function checkPhpExtensions() {		
		foreach (Prerequisites::$php_extensions as $ext) {
			if (!extension_loaded($ext)) {
				$this->problems['PHP extensions:'][] = "Missing $ext PHP extension";
			} 
			else {
				logMessage(L_INFO, "Preqrequisite passed: PHP extension $ext is loaded");
				if ($ext == 'mysqli') {
					$this->mysqli_ext_exists = true;
				}				
			}
		}
	}
		
	/**
	 * Checks that needed binary files exist (by using 'which')
	 */
	private function checkBins() {
		logMessage(L_INFO, "Checking binaries");
		foreach (Prerequisites::$bins as $bin) {			
			$path = @exec("which $bin");
			if (trim($path) == '') {
				$this->problems['Bins:'][] = "Missing $bin bin file";
			} 
			else {
				logMessage(L_INFO, "Preqrequisite passed: Binary $bin found");
			}			
		}
	}	
	
	/**
	 * Check that needed file paths exist
	 */
	private function checkFiles() {
		foreach (Prerequisites::$files as $file) {
			if (!is_file($file)) {
				$this->problems['Files:'][] = "Missing $file file";				
			} else {
				logMessage(L_INFO, "Preqrequisite passed: File $file found");
			}
		}
	}	
	
	/**
	 * Checks that needed apache modules exist
	 */
	private function checkApacheModules($httpd_bin) {
		$apache_cmd = $httpd_bin.' -t -D DUMP_MODULES';
		$current_modules = OsUtils::executeReturnOutput($apache_cmd);
				
		foreach (Prerequisites::$apache_modules as $module) {
			$found = false;
			for ($i=0; !$found && $i<count($current_modules); $i++) {
				if (strpos($current_modules[$i],$module) !== false) {
					$found = true;
				}				
			}
			
			if (!$found) $this->problems['Apache modules:'][] = "Apache $module module is missing";
			else logMessage(L_INFO, "Preqrequisite passed: Apache module %$module% found");
		}
	}

	/**
	 * Check that mySQL settings are set as required
	 */
	private function checkMySqlSettings($db_params) {
		if (!DatabaseUtils::connect($link, $db_params, null)) {
			$this->problems['mySQL settings:'][] = "Cannot connect to db";
			return;
		}

		foreach (Prerequisites::$mysql_settings as $key => $value) {
			$result = mysqli_query($link, "SELECT @@$key;");
			if ($result === false) {
				$this->problems['mySQL settings:'][] = "Cannot find mysql settings key: $key";
			}
			else {			
				$tmp = '@@'.$key;
				$current = $result->fetch_object()->$tmp;
				if (!$this->compare($current, $value[1], $value[0])) {
					$this->problems['mySQL settings:'][] = "MySQL setting $key=$current and not $value[0] $value[1] expected";
				}
				else {
					logMessage(L_INFO, "Preqrequisite passed: MySQL setting $key is set correctly $current, $value[1], $value[0]");
				}
			}
		}
	}	
		
	private function checkPhpVersion() {		
		if (!version_compare(phpversion(), Prerequisites::$php_version[1], Prerequisites::$php_version[0])) {
			$this->problems['Product versions:'][] = "PHP version not valid expected $version[0] actual $version[1]";
		} else {
			logMessage(L_INFO, "Preqrequisite passed: PHP version is OK (".phpversion().")");
		}
	}	
		
	/**
	 * Check MYSQL version
	 */
	private function checkMySqlVersion($db_params) {
		if (!DatabaseUtils::connect($link, $db_params, null)) {
			$this->problems['Product versions:'][] = "Cannot connect to db";
			return;
		}

		$key = "@@version";
		$result = mysqli_query($link, "SELECT $key;");
		if ($result === false) {
			$this->problems['Product versions:'][] = "Cannot find mysql version";
			return;
		}

		$current = $result->fetch_object()->$key;
		if (!version_compare($current, Prerequisites::$mysql_version[1], Prerequisites::$mysql_version[0])) {
			$this->problems['Product versions:'][] = "MySQL version not valid, expected $check[0] actual $check[1]";
		} else {
			logMessage(L_INFO, "Preqrequisite passed: MySQL version is OK ($current)");
		}
	}
		
	/**
	 * compare numbers according to given operator $op (note: assumes only numbers are passed as $val1 & $val2)
	 * @param string $val1 1st value
	 * @param string $val2 2nd value
	 * @param string $op   operator
	 */
	private function compare($val1, $val2, $op)
	{
		switch ($op) {
			case '=':
				return strtoupper($val1) === strtoupper($val2);				
			case '>':
				return intval($val1) > intval($val2);				
			case '>=':
				return intval($val1) >= intval($val2);				
			case '<':		
				return (intval($val1) < intval($val2));					
			case '<=':
				return intval($val1) <= intval($val2);				
		}
		return false;
	}		
}