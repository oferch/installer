<?php

define('TOKEN_CHAR', '@'); // this character is user to surround parameters that should be replaced with configurations in config files
define('TEMPLATE_FILE', '.template'); // how to recognize a tamplate file, template files are copyed to non-template and then the tokens are replaced
define('KCONF_LOCATION', '/alpha/config/kConf.php'); // the location of kConf
define('UNINSTALLER_LOCATION', '/uninstaller/uninstall.ini'); // the location where to save configuration for the uninstaller

/* 
* This class handles all the configuration of the application:
* Defining application configuration values according to user input, 
* replaceing configuration tokens in needed files and other application configuration actions 
*/
class AppConfig {
	private $app_config = array();
	
	// gets the application value set for the given key
	public function get($key) {
		return $this->app_config[$key];
	}
	
	// sets the application value for the given key
	public function set($key, $value) {
		$this->app_config[$key] = $value;
	}
	
	// init the application configuration values according to the user input
	public function initFromUserInput($user_input) {
		foreach ($user_input as $key => $value) {
			$this->app_config[$key] = $value;
		}
		$this->defineInstallationTokens();
	}		
	
	// replaces all tokens in the given string with the configuration values and returns the new string
	public function replaceTokensInString($string) {
		foreach ($this->app_config as $key => $var) {
			$key = TOKEN_CHAR.$key.TOKEN_CHAR;
			$string = str_replace($key, $var, $string);		
		}
		return $string;
	}
		
	// replaces all the tokens in the given file with the configuration values and returns true/false upon success/failure
	// will override the file if it is not a template file
	// if it is a template file it will save it to a non template file and then override it
	public function replaceTokensInFile($file) {		
		$newfile = $this->copyTemplateFileIfNeeded($file);
		$data = @file_get_contents($newfile);
		if (!$data) {
			logMessage(L_ERROR, "Cannot replace token in file $newfile");
			return false;			
		} else {
			$data = $this->replaceTokensInString($data);
			if (!file_put_contents($newfile, $data)) {
				logMessage(L_ERROR, "Cannot replace token in file, cannot write to file $newfile");
				return false;							
			} else {
				logMessage(L_INFO, "Replaced tokens in file $newfile");			
			}
		}
		return true;
	}	
	
	// saves the uninstaller config file, the values saved are the minimal values subset needed for the uninstaller to run
	public function saveUninstallerConfig() {
		$file = $this->app_config['BASE_DIR'].UNINSTALLER_LOCATION;
		$data = "BASE_DIR = ".$this->app_config["BASE_DIR"].PHP_EOL;	
		$data = $data."DB_HOST = ".$this->app_config["DB1_HOST"].PHP_EOL;
		$data = $data."DB_USER = ".$this->app_config["DB1_USER"].PHP_EOL;
		$data = $data."DB_PASS = ".$this->app_config["DB1_PASS"].PHP_EOL;
		$data = $data."DB_PORT = ".$this->app_config["DB1_PORT"].PHP_EOL;
		return OsUtils::writeFile($file, $data);
	}	
	
	// private functions
	
	// defines all the installation configuration values according to the user input and the default values
	private function defineInstallationTokens() {
		logMessage(L_INFO, "Defining installation tokens for config");
		// directories
		$this->app_config['APP_DIR'] = $this->app_config['BASE_DIR'].'/app';	
		$this->app_config['WEB_DIR'] = $this->app_config['BASE_DIR'].'/web';	
		$this->app_config['LOG_DIR'] = $this->app_config['BASE_DIR'].'/log';	
		$this->app_config['BIN_DIR'] = $this->app_config['BASE_DIR'].'/bin';	
		$this->app_config['TMP_DIR'] = $this->app_config['BASE_DIR'].'/tmp';
		$this->app_config['DWH_DIR'] = $this->app_config['BASE_DIR'].'/dwh';
		$this->app_config['ETL_HOME_DIR'] = $this->app_config['BASE_DIR'].'/dwh'; // For backward compatibility
		
		// databases (copy information collected during prerequisites
		$this->collectDatabaseCopier('DB1', 'DB2');
		$this->collectDatabaseCopier('DB1', 'DB3');
				
		// admin console defaults
		$this->app_config['ADMIN_CONSOLE_PARTNER_SECRET'] = $this->generateSecret();
		$this->app_config['ADMIN_CONSOLE_PARTNER_ADMIN_SECRET'] =  $this->generateSecret();
		$this->app_config['SYSTEM_USER_ADMIN_EMAIL'] = $this->app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
		$this->app_config['ADMIN_CONSOLE_PARTNER_ALIAS'] = md5('-2kaltura partner');
		$this->app_config['ADMIN_CONSOLE_KUSER_MAIL'] = 'admin_console@kaltura.com';	
		$this->generateSha1Salt($this->app_config['ADMIN_CONSOLE_PASSWORD'], $salt, $sha1);	
		$this->app_config['SYSTEM_USER_ADMIN_SALT'] = $salt;
		$this->app_config['ADMIN_CONSOLE_KUSER_SHA1'] = $salt;
		$this->app_config['SYSTEM_USER_ADMIN_SHA1'] = $sha1;
		$this->app_config['ADMIN_CONSOLE_KUSER_SALT'] = $sha1;
		//$this->app_config['XYMON_SERVER_MONITORING_CONTROL_SCRIPT'] = // Not set
		
		// stats DB
		$this->collectDatabaseCopier('DB1', 'DB_STATS');
		$this->app_config['DB_STATS_NAME'] = 'kaltura_stats';
		
		// data warehouse
		$this->app_config['DWH_HOST'] = $this->app_config['DB1_HOST'];
		$this->app_config['DWH_PORT'] = $this->app_config['DB1_PORT'];
		$this->app_config['DWH_DATABASE_NAME'] = 'kalturadw';
		$this->app_config['DWH_USER'] = 'etl';
		$this->app_config['DWH_PASS'] = 'etl';
		$this->app_config['DWH_SEND_REPORT_MAIL'] = $this->app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
		$this->app_config['DWH_SEND_REPORT_MAIL'] = $this->app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
				
		// default partners and kusers
		$this->app_config['TEMPLATE_PARTNER_MAIL'] = 'template@kaltura.com';
		$this->app_config['TEMPLATE_KUSER_MAIL'] = $this->app_config['TEMPLATE_PARTNER_MAIL'];
		$this->app_config['TEMPLATE_ADMIN_KUSER_SALT'] = $this->app_config['SYSTEM_USER_ADMIN_SALT'];
		$this->app_config['TEMPLATE_ADMIN_KUSER_SHA1'] = $this->app_config['SYSTEM_USER_ADMIN_SHA1'];		
		
		// batch
		$this->app_config['BATCH_ADMIN_MAIL'] = $this->app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
		$this->app_config['BATCH_KUSER_MAIL'] = 'batch@kaltura.com';
		$this->app_config['BATCH_HOST_NAME'] = OsUtils::getComputerName();
		$this->app_config['BATCH_PARTNER_SECRET'] = $this->generateSecret();
		$this->app_config['BATCH_PARTNER_ADMIN_SECRET'] = $this->generateSecret();
		$this->app_config['BATCH_PARTNER_PARTNER_ALIAS'] = md5('-1kaltura partner');
		
		// site settings
		$this->app_config['KALTURA_VIRTUAL_HOST_NAME'] = $this->removeHttp($this->app_config['KALTURA_FULL_VIRTUAL_HOST_NAME']);
		$this->app_config['CORP_REDIRECT'] = '';	
		$this->app_config['CDN_HOST'] = $this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
		$this->app_config['IIS_HOST'] = $this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
		$this->app_config['RTMP_URL'] = $this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
		$this->app_config['MEMCACHE_HOST'] = $this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
		$this->app_config['WWW_HOST'] = $this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
		$this->app_config['SERVICE_URL'] = 'http://'.$this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
		$this->app_config['ENVIRONEMTN_NAME'] = $this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
				
		// other configurations
		$this->app_config['APACHE_RESTART_COMMAND'] = $this->app_config['HTTPD_BIN'].' -k restart';
		$this->app_config['TIME_ZONE'] = date('e');
		$this->app_config['GOOGLE_ANALYTICS_ACCOUNT'] = 'UA-7714780-1';
		$this->app_config['INSTALLATION_TYPE'] = '';
		$this->app_config['PARTNERS_USAGE_REPORT_SEND_FROM'] = ''; 
		$this->app_config['PARTNERS_USAGE_REPORT_SEND_TO'] = '';
		$this->app_config['SYSTEM_PAGES_LOGIN_USER'] = '';
		$this->app_config['SYSTEM_PAGES_LOGIN_PASS'] = '123456';
		$this->app_config['KMC_BACKDOR_SHA1_PASS'] = '123456';
		$this->app_config['DC0_SECRET'] = '';
		$this->app_config['APACHE_CONF'] = '';		
		
		// storage profile related
		$this->app_config['DC_NAME'] = 'local';
		$this->app_config['DC_DESCRIPTION'] = 'local';
		$this->app_config['STORAGE_BASE_DIR'] = $this->app_config['WEB_DIR'];
		$this->app_config['DELIVERY_HTTP_BASE_URL'] = $this->app_config['SERVICE_URL'];
		$this->app_config['DELIVERY_RTMP_BASE_URL'] = $this->app_config['RTMP_URL'];
		$this->app_config['DELIVERY_ISS_BASE_URL'] = $this->app_config['SERVICE_URL'];	
		$this->app_config['ENVIRONMENT_NAME'] = $this->app_config['KALTURA_VIRTUAL_HOST_NAME'];
	}

	// copies DB parametes from one DB configuration to another
	private function collectDatabaseCopier($from_db, $to_db) {
		$this->app_config[$to_db.'_HOST'] = $this->app_config[$from_db.'_HOST'];
		$this->app_config[$to_db.'_PORT'] = $this->app_config[$from_db.'_PORT'];
		$this->app_config[$to_db.'_NAME'] = $this->app_config[$from_db.'_NAME'];
		$this->app_config[$to_db.'_USER'] = $this->app_config[$from_db.'_USER'];
		$this->app_config[$to_db.'_PASS'] = $this->app_config[$from_db.'_PASS'];
	}
		
	// generates a secret for Kaltura and returns it
	private function generateSecret() {
		logMessage(L_INFO, "Generating secret");
		$secret = md5(self::str_makerand(5,10,true, false, true));
		return $secret;
	}
	
	/**
	 * Generates sha1 and salt from a password
	 * @param string $password chosen password
	 * @param string $salt salt will be generated
	 * @param string $sha1 sha1 will be generated
	 * @return $sha1 & $salt by reference
	 */
	public static function generateSha1Salt($password, &$salt, &$sha1) {
		logMessage(L_INFO, "Generating sh1 and salf from password");
		$salt = md5(rand(100000, 999999).$password); 
		$sha1 = sha1($salt.$password);  
	}
	
	// puts a Kaltura CE activation key
	public function simMafteach() {
		$admin_email = $this->app_config['ADMIN_CONSOLE_ADMIN_MAIL']; 
		$kConfFile = $this->app_config['APP_DIR'].KCONF_LOCATION;
		logMessage(L_INFO, "Setting application key");
		$str = implode("|", array(md5($admin_email), '1', 'never', time()*rand(0,1)));
		$key = base64_encode($str);
		$data = @file_get_contents($kConfFile);
		$key_line = '/"kaltura_activation_key"(\s)*=>(\s)*(.+),/';
		$replacement = '"kaltura_activation_key" => "'.$key.'",';
		$data = preg_replace($key_line, $replacement ,$data);
		@file_put_contents($kConfFile, $data);
	}
	
	// removes http:// or https:// prefix from the string and returns it
	private function removeHttp($url = '') {
		$list = array('http://', 'https://');
		foreach ($list as $item) {
			if (strncasecmp($url, $item, strlen($item)) == 0)
				return substr($url, strlen($item));
		}
		return $url;
	}
	
	// checks if the given file is a template file and if so copies it to a non template file
	// returns the non template file if it was copied or the original file if it was not copied
	private function copyTemplateFileIfNeeded($file) {
		$return_file = $file;
		// Replacement in a template file, first copy to a non .template file
		if (strpos($file, TEMPLATE_FILE) !== false) {
			$return_file = str_replace(TEMPLATE_FILE, "", $file);
			logMessage(L_INFO, "$file toekn file contains ".TEMPLATE_FILE);
			OsUtils::fullCopy($file, $return_file);
		}
		return $return_file;
	}
	
	// creates a random key used to generate a secret
	private static function str_makerand($minlength, $maxlength, $useupper, $usespecial, $usenumbers) {
		$charset = "abcdefghijklmnopqrstuvwxyz";
		if ($useupper) $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if ($usenumbers) $charset .= "0123456789";
		if ($usespecial) $charset .= "~@#$%^*()_+-={}|]["; // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";
		if ($minlength > $maxlength) $length = mt_rand ($maxlength, $minlength);
		else $length = mt_rand ($minlength, $maxlength);
		$key = "";
		for ($i=0; $i<$length; $i++) $key .= $charset[(mt_rand(0,(strlen($charset)-1)))];
		return $key;
	}	
}