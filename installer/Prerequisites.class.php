<?php

class Prerequisites
{
	public static $php_version = array('>=', '5.2.0');
	public static $mysql_version = array('>=', '5.1.33');
	public static $apache_version = array('>=', '2.2'); // Currently not checked
		
	public static $files = array (
		'pentaho kitchen.sh' => '/usr/local/pentaho/pdi/kitchen.sh',
	);
	
	public static $databases = array (
		'kaltura', 'kalturadw', 'kalturadw_ds', 'kalturadw_bisources', 'kalturalog', 'kaltura_stats',
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
	public function verifyPrerequisites($config)
	{
		$this->problems = array();				
		
		$httpd_bin = $config['HTTPD_BIN'];
		$etl_home = $config['ETL_HOME_DIR'];
		$db_host = $config['DB1_HOST'];
		$db_user = $config['DB1_USER']; 
		$db_pass = $config['DB1_PASS'];
		$db_port = $config['DB1_PORT'];
		
		// check prerequisites
		$this->checkPhpVersion();
		$this->checkBins();
		$this->checkPhpExtensions();
		$this->checkApacheModules($httpd_bin);
		$this->checkEtlUser($etl_home);
		$this->checkFiles();		
		
		if ($this->mysqli_ext_exists) {
			$this->checkMysqlVersion($db_host, $db_user, $db_pass, $db_port);
			$this->checkMySqlSettings($db_host, $db_user, $db_pass, $db_port);			
		}
		else {
			$this->problems['Product versions:'][] = sprintf('G3. No mysqli extension', 'mySQL version');
			$this->problems['mySQL settings:'][] =sprintf('G3. No mysqli extension', 'mySQL settings');
		}
	
		if (empty($this->problems)) {
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
			echo 'Missing prerequisites'.$error_description;
			return false;							
		}
	}
		
	/**
	 * Checks that :
	 * 1. User etl exists
	 * 2. /home/dir directory exists
	 */
	private function checkEtlUser($etl_home_dir)
	{
		if (!is_dir($etl_home_dir)) {
			$this->problems['Etl user:'][] = "G4. Missing etl home: $etl_home_dir";
		}
		@exec('id -u etl', $output, $result);
		if ($result != 0) {
			$this->problems['Etl user:'][] = "G5. Missing etl user";
		}
	}		
		
	/**
	 * Checks that needed php extensions exist
	 */
	private function checkPhpExtensions()
	{
		foreach (Prerequisites::$php_extensions as $ext) {
			if (!extension_loaded($ext)) {
				$this->problems['PHP extensions:'][] = "G6. Missing php ext $ext";
			}
			else if ($ext == 'mysqli') {
				$this->mysqli_ext_exists = true;
			}
		}
	}
		
	/**
	 * Checks that needed binary files exist (by using 'which')
	 */
	private function checkBins()
	{
		foreach (Prerequisites::$bins as $bin) {
			$path = @exec("which $bin");
			if (trim($path) == '') {
				$this->problems['Bins:'][] = "G7. Missing binary $bin";
			}
		}
	}	
	
	/**
	 * Check that needed file paths exist
	 */
	private function checkFiles()
	{
		foreach (Prerequisites::$files as $file) {
			if (!is_file($file)) {
				$this->problems['Files:'][] = "G8. Missing file $file";				
			}
		}
	}	
		
	/**
	 * Checks that needed databases DO NOT exist
	 */
	public function checkDatabases($db_host, $db_user, $db_pass, $db_port, $should_drop=false)
	{
		$verify = null;
		foreach (Prerequisites::$databases as $db) {
			$result = DatabaseUtils::dbExists($db, $db_host, $db_user, $db_pass, $db_port);
			
			if ($result === -1) {
				$verify = $verify."Error verifying if db exists $db".PHP_EOL;
			}
			else if ($result === true) {
				$verify = "G9. DB already exists $db".PHP_EOL;
				if ($should_drop) DatabaseUtils::dropDb($db, $db_host, $db_user, $db_pass, $db_port);
			}
		}
		return $verify;
	}	
	
	/**
	 * Checks that needed apache modules exist
	 */
	private function checkApacheModules($httpd_bin)
	{
		$apache_cmd = $httpd_bin.' -t -D DUMP_MODULES';
		$current_modules = FileUtils::exec($apache_cmd);
				
		foreach (Prerequisites::$apache_modules as $module) {
			$found = false;
			for ($i=0; !$found && $i<count($current_modules); $i++) {
				if (strpos($current_modules[$i],$module) !== false) {
					$found = true;
				}				
			}
			if (!$found) $this->problems['Apache modules:'][] = "G10. Missing apache module $module";
		}
	}

	/**
	 * Check that mySQL settings are set as required
	 */
	private function checkMySqlSettings($db_host, $db_user, $db_pass, $db_port)
	{
		if (!DatabaseUtils::connect($link, $db_host, $db_user, $db_pass, null, $db_port)) {
			$this->problems['mySQL settings:'][] = "Cannot connect to db";
			return;
		}

		foreach (Prerequisites::$mysql_settings as $key => $value) {
			$result = mysqli_query($link, "SELECT @@$key;");
			if ($result === false) {
				$this->problems['mySQL settings:'][] = "Cannot find mysql settings key: $key";
			}
			
			$tmp = '@@'.$key;
			$current = $result->fetch_object()->$tmp;
			if (!$this->compare($current, $value[1], $value[0])) {
				$this->problems['mySQL settings:'][] = "G12. Bad mysql settings for $key expected $value[0] actual $value[1]";
			}
		}
	}	
		
	private function checkPhpVersion() {
		
		if (!version_compare(phpversion(), Prerequisites::$php_version[1], Prerequisites::$php_version[0])) {
			$this->problems['Product versions:'][] = "G13. Bad PHP version expected $version[0] actual $version[1]";
		}	
	}	
		
	/**
	 * Check MYSQL version
	 */
	private function checkMySqlVersion($db_host, $db_user, $db_pass, $db_port)
	{
		if (!DatabaseUtils::connect($link, $db_host, $db_user, $db_pass, null, $db_port)) {
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
			$this->problems['Product versions:'][] = "G14. Bad mysql version, expected $check[0] actual $check[1]";
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