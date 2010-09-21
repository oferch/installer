<?php

DEFINE('TOKEN_CHAR', '@'); // This character is user to surround parameters that should be replaced with configurations in config files

class FileUtils
{
	/**
	 * File/folder names list to ignore in various functions - check below
	 * @var string[]
	 */
	private static $ignore_list = array ( '.svn' );
	
	/**
	 * @param string $path dir/file path
	 * @return boolean true if the path should be ignored according to $ignore_list, or false otherwise.
	 */
	private static function shouldIgnore($path)
	{
		$base = basename($path);
		return in_array($base, self::$ignore_list);
	}
	
	/**
	 * Copy source to target.
	 * - $path will be ignored if in $ignore_list -
	 * @param string $source source path
	 * @param string $target target path
	 * @param boolean $overwrite true/false - overwrite or not
	 * @return true on success, ErrorObject on error
	 */
	public static function fullCopy($source, $target, $overwrite = false)
	{
		$result = @exec("cp -r $source $target");
		if (trim($result) !== '') {
			echo "cpopy failed: cp -r $source $target";
			return false;
		}
		return true;		
	}
			
	/**
	 * Create a new directory
	 * - $path will be ignored if in $ignore_list -
	 * @param string $path directory path to create
	 * @return true on success, ErrorObject on error
	 */
	public static function mkDir($path)
	{
		$path = InstallUtils::fixPath($path);
		if (self::shouldIgnore($path)) {
			return true;
		}
		if (!is_dir($path)) {
			if (!@mkdir($path, 0777, true)) {
				$last_error = InstallUtils::getLastError();
				echo "cannot make directory: $path, $last_error";
				return false;														
			}
		}
		return true;
	}
			
	/**
	 * Completely delete the given path
	 * @param string $path path to delete
	 * @return true on success, ErrorObject on error
	 */
	public static function recursiveDelete($path)
	{
		$result = true;
		$path = InstallUtils::fixPath($path, '/');
		$onlyContents = (substr($path, strlen($path) - 2) == '/*');
		if ($onlyContents) {
			$path = substr($path, 0, strlen($path)-2);
		}
		if (is_file($path)){
            if (!@unlink($path)) {
            	$last_error = InstallUtils::getLastError();
				echo "cannot recursive delete: can't delete file $path, $last_error";
				return false;		
            }
        }
        else if (is_dir($path) || $onlyContents){
            $scan = @scandir($path);
            if ($scan === false) {
            	$last_error = InstallUtils::getLastError();
				echo "cannot recursive delete: can't read directory $path, $last_error";
				return false;						
            }
            foreach($scan as $index => $cur){
            	if ($cur != '.' && $cur != '..') {
	                $result = self::recursiveDelete($path.'/'.$cur);
	                if ($result !== true) {
	                	return $result;
	                }
            	}
            }
            
            if (!$onlyContents && !@rmdir($path)) {
            	$last_error = InstallUtils::getLastError();
				echo "cannot recursive delete: can't delete directory $path, $last_error";
				return false;										
            }
        }
        
        return $result;
    }
      
	public static function copyTemplateFileIfNeeded($file) {
		$return_file = $file;
		// Replacement in a template file, first copy to a non .template file
		if (strpos($file, ".template") !== false) {
			$return_file = str_replace(".template", "", $file);
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
    	$dir_name = dirname($filename);
    	if (is_dir($dir_name) && !self::mkDir($dir_name)) {
			return false;
    	}
    	
    	$fh = fopen($filename, 'w');
		if (!$fh) {
			$last_error = InstallUtils::getLastError();
			echo "cannot write file: $file, can't create file, $last_error";
			return false;										
		}
		if (!fwrite($fh, $data)) {
			$last_error = InstallUtils::getLastError();
			echo "cannot write file: $file, $last_error";
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
		$file = InstallUtils::fixPath($file);
		$data = @file_get_contents($file);
		if (!$data) {
			$last_error = InstallUtils::getLastError();
			echo "cannot replace tokens in file: $file, can't read the file";
			return false;			
		}
		else {
			foreach ($tokens as $key => $var) {
				$key = TOKEN_CHAR.$key.TOKEN_CHAR;
				$data = str_replace($key, $var, $data);		
			}
			if (!file_put_contents($file, $data)) {
				$last_error = InstallUtils::getLastError();
				echo "cannot replace tokens in file: $file, can't write to file";
				return false;							
			}
		}
		return true;
	}
	
	
	/**
	 * Chmod given $path to $chmod
	 * @param string $path directory/file path
	 * @param string $chmod
	 * @return true on success, ErrorObject on error
	 */
	public static function chmod ($chmod)
	{
		$result = @exec("chmod $chmod");
		if (trim($result) !== '') {
			echo "chmod failed: chmod $chmod";
			return false;
		}
		return true;
	}
	
	/**
	 * Change owner of given $path to $user
	 * @param string $path
	 * @param string $user user name
	 * @return true on success, ErrorObject on error
	 */
	public static function chown ($path, $user)
	{
		$result = @exec("chown -R $user $path");
		if (trim($result) !== '') {
			echo "chown failed: chown -R $user $path";
			return false;
		}
		return true;
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
			echo "exec failed: sudo -u $user ".$cmd;
			return false;
		}
	} 
	
			
}