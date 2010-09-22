<?php

class InstallUtils
{
	const WINDOWS_OS = 'Windows';
	const LINUX_OS   = 'linux';
	
	/**
	 * @return string current operating system name
	 */
	public static function getOsName()
	{		
		logMessage(LOG_INFO, "OS: ".PHP_OS);
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return self::WINDOWS_OS;
		}
		else if (strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
			return self::LINUX_OS;
		}
		else {
			logMessage(LOG_WARNING, "OS not recognized: ".PHP_OS);
			return "";
		}
	}
	
	/**
	 * @return string 32bit/64bit according to current system architecture - if not found, default is 32bit
	 */
	public static function getSystemArchitecture()
	{		
		$arch = php_uname('m');
		logMessage(LOG_INFO, "OS architecture: ".$arch);
		if ($arch && (stristr($arch, 'x86_64') || stristr($arch, 'amd64'))) {
			return '64bit';
		} 
		else {
			// stristr($arch, 'i386') || stristr($arch, 'i486') || stristr($arch, 'i586') || stristr($arch, 'i686') ||
			// return 32bit as default when not recognized
			return '32bit';		
		}
	}
	
	/**
	 * @return string current computer name
	 */
	public static function getComputerName() {
		if(isset($_ENV['COMPUTERNAME'])) {
			logMessage(LOG_INFO, "Host name: ".$_ENV['COMPUTERNAME']);
	    	return $_ENV['COMPUTERNAME'];
		}
		else if (isset($_ENV['HOSTNAME'])) {
			logMessage(LOG_INFO, "Host name: ".$_ENV['HOSTNAME']);
			return $_ENV['HOSTNAME'];
		}
		else if (function_exists('gethostname')) {
			logMessage(LOG_INFO, "Host name: ".gethostname());
			return gethostname();
		}
		else {
			logMessage(LOG_WARNING, "Host name unkown");
			return 'unknown';
		}
	}	
	
	/**
	 * @return string secret string, like the one generated in kaltura
	 */
	public static function generateSecret() {
		logMessage(LOG_INFO, "Generating secret");
		$secret = md5(self::str_makerand(5,10,true, false, true));
		return $secret;
	}
	
	/**
	 * @return string random password
	 */
	public static function generatePassword() {
		logMessage(LOG_INFO, "Generating password");
		$password = self::str_makerand(5,10,true, false, true);
		return $password;
	}
	
	/**
	 * Generates sha1 and salt from a password
	 * @param string $password chosen password
	 * @param string $salt salt will be generated
	 * @param string $sha1 sha1 will be generated
	 * @return $sha1 & $salt by reference
	 */
	public static function generateSha1Salt($password, &$salt, &$sha1)
	{
		logMessage(LOG_INFO, "Generating sh1 and salf from password");
		$salt = md5(rand(100000, 999999).$password); 
		$sha1 = sha1($salt.$password);  
	}
		
	/**
	 * @return string last error happened (or null if error_get_last function wasn't found)
	 */
	public static function getLastError() {
		if (function_exists('error_get_last')) {
			return error_get_last();
		}
		return null;
	}
	
	/**
	 * tavin levad :)
	 * $version_type - myConf::get('KALTURA_VERSION_TYPE'), 'CE')
	 * $admin_email - myConf::get('REPORT_ADMIN_EMAIL')
	 * $kConfFile - myConf::get('APP_DIR').KCONF_FILE_LOC
	 */
	public static function simMafteach($version_type, $admin_email, $kConfFile) {
		if (strcasecmp($version_type, 'CE') == 0) {
			logMessage(LOG_INFO, "Setting application key");
			$str = implode("|", array(md5($admin_email), '1', 'never', time()*rand(0,1)));
			$key = base64_encode($str);
			$data = @file_get_contents($kConfFile);
			$key_line = '/"kaltura_activation_key"(\s)*=>(\s)*(.+),/';
			$replacement = '"kaltura_activation_key" => "'.$key.'",';
			$data = preg_replace($key_line, $replacement ,$data);
			@file_put_contents($kConfFile, $data);
		}
	}
	
	private static function str_makerand ($minlength, $maxlength, $useupper, $usespecial, $usenumbers) {
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