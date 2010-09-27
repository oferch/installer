<?php

DEFINE('TOKEN_CHAR', '@'); // This character is user to surround parameters that should be replaced with configurations in config files

class FileUtils
{
	public static function appendFile($filename, $newdata) {
		$f=fopen($filename,"a");
		fwrite($f,$newdata);
		fclose($f);  
	}
      
	public static function copyTemplateFileIfNeeded($file) {
		$return_file = $file;
		// Replacement in a template file, first copy to a non .template file
		if (strpos($file, ".template") !== false) {
			$return_file = str_replace(".template", "", $file);
			logMessage(L_INFO, "$file toekn file contains .template");
			self::fullCopy($file, $return_file);
		}
		return $return_file;
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
    
    /**
     * Replace tokens in given file
     * @param string[] $tokens array of key=>value replacements
     * @param string $file file path
     * @return true on success, ErrorObject on error
     */
	public static function replaceTokensInFile(&$tokens, $file)
	{
		$data = @file_get_contents($file);
		if (!$data) {
			logMessage(L_ERROR, "Cannot replace token in file $file");
			return false;			
		}
		else {
			foreach ($tokens as $key => $var) {
				$key = TOKEN_CHAR.$key.TOKEN_CHAR;
				$data = str_replace($key, $var, $data);		
			}
			if (!file_put_contents($file, $data)) {
				logMessage(L_ERROR, "Cannot replace token in file, cannot write to file $file");
				return false;							
			}
		}
		return true;
	}
	
	public static function executeAndReturn($command) {
		logMessage(L_INFO, "Executing $command");
		$result = @exec($command);
		if (trim($result) !== '') {
			logMessage(L_ERROR, "Executing command failed: $command");	
			return false;
		}
		return true;			
	}
	
	public static function fullCopy($source, $target)
	{
		return self::executeAndReturn("cp -r $source $target");
	}
	
	public static function recursiveDelete($path)
	{
		return self::executeAndReturn("rm -rf $path");
    }
	
	/**
	 * Chmod given $path to $chmod
	 * @param string $path directory/file path
	 * @param string $chmod
	 * @return true on success, ErrorObject on error
	 */
	public static function chmod($chmod)
	{
		return self::executeAndReturn("chmod $chmod");	
	}
	
	/**
	 * Change owner of given $path to $user
	 * @param string $path
	 * @param string $user user name
	 * @return true on success, ErrorObject on error
	 */
	public static function chown($path, $user)
	{
		return self::executeAndReturn("chown -R $user $path");	
	}
	
	/**
	 * Execute the given command, returning the output
	 * @param string $cmd command to execute
	 */
	public static function exec($cmd)
	{
		// 2>&1 is needed so the output will not display on the screen
		@exec($cmd . ' 2>&1', $output);
		return $output;
	}
	
	/**
	 * Execute the given command as the given user.
	 * @param string $cmd command to execute
	 * @param string $user username
	 */
	public static function execAsUser($cmd, $user)
	{
		$cmd = "sudo -u $user ".$cmd;
		@exec($cmd . ' 2>&1', $output, $result);
		if ($result == 0) {
			return true;
		}
		else {
			logMessage(L_ERROR, "Exec failed: sudo -u $user $cmd");
			return false;
		}
	} 
	
			
}