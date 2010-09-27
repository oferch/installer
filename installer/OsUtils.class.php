<?php

class OsUtils {
	const WINDOWS_OS = 'Windows';
	const LINUX_OS   = 'linux';

	public static function verifyRootUser() {
		@exec('id -u', $output, $result);
		logMessage(L_INFO, "User: $output");
		return (isset($output[0]) && $output[0] == '0' && $result == 0);
	}

	public static function verifyOS() {
		logMessage(L_INFO, "OS: ".OsUtils::getOsName());
		return (OsUtils::getOsName() === OsUtils::LINUX_OS);
	}

	public static function getComputerName() {
		if(isset($_ENV['COMPUTERNAME'])) {
			logMessage(L_INFO, "Host name: ".$_ENV['COMPUTERNAME']);
	    	return $_ENV['COMPUTERNAME'];
		}
		else if (isset($_ENV['HOSTNAME'])) {
			logMessage(L_INFO, "Host name: ".$_ENV['HOSTNAME']);
			return $_ENV['HOSTNAME'];
		}
		else if (function_exists('gethostname')) {
			logMessage(L_INFO, "Host name: ".gethostname());
			return gethostname();
		}
		else {
			logMessage(L_WARNING, "Host name unkown");
			return 'unknown';
		}
	}	
	
	public static function getOsName()
	{		
		logMessage(L_INFO, "OS: ".PHP_OS);
		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
			return self::WINDOWS_OS;
		}
		else if (strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX') {
			return self::LINUX_OS;
		}
		else {
			logMessage(L_WARNING, "OS not recognized: ".PHP_OS);
			return "";
		}
	}
	
	public static function getOsLsb()
	{		
		$dist = OsUtils::executeReturnOutput("lsb_release -d");		
		logMessage(L_INFO, "Distribution: ".$dist);
		return $dist;
	}
	
	/**
	 * @return string 32bit/64bit according to current system architecture - if not found, default is 32bit
	 */
	public static function getSystemArchitecture()
	{		
		$arch = php_uname('m');
		logMessage(L_INFO, "OS architecture: ".$arch);
		if ($arch && (stristr($arch, 'x86_64') || stristr($arch, 'amd64'))) {
			return '64bit';
		} 
		else {
			// stristr($arch, 'i386') || stristr($arch, 'i486') || stristr($arch, 'i586') || stristr($arch, 'i686') ||
			// return 32bit as default when not recognized
			return '32bit';		
		}
	}

	public static function appendFile($filename, $newdata) {
		$f=fopen($filename,"a");
		fwrite($f,$newdata);
		fclose($f);  
	}
      	
    /**
     * Write $data to $filename
     * @param string $filename file name to write to
     * @param string $data data to write
     */
    public static function writeFile($filename, $data)
    {   	
    	$fh = fopen($filename, 'w');
		if (!$fh) {
			// File errors cannot be logged because it could cause an infinite loop			
			return false;										
		}
		if (!fwrite($fh, $data)) {
			// File errors cannot be logged because it could cause an infinite loop
			return false;										
		}
		fclose($fh);
		return true;
    }      
	
	public static function execute($command) {
		logMessage(L_INFO, "Executing $command");
		$result = @exec($command);
		if (trim($result) !== '') {
			logMessage(L_ERROR, "Executing command failed: $command");	
			return false;
		}
		return true;			
	}

		/**
	 * Execute the given command, returning the output
	 * @param string $cmd command to execute
	 */
	public static function executeReturnOutput($cmd)
	{
		// 2>&1 is needed so the output will not display on the screen
		@exec($cmd . ' 2>&1', $output);
		return $output;
	}
	
	public static function fullCopy($source, $target)
	{
		return self::execute("cp -r $source $target");
	}
	
	public static function recursiveDelete($path)
	{
		return self::execute("rm -rf $path");
    }
	
	public static function chmod($chmod)
	{
		return self::execute("chmod $chmod");	
	}
}