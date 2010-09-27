<?php

class DatabaseUtils
{	
	/**
	 * Connect to mySQL database
	 * @param mysqli $link mysqli link
	 * @param string $host database host
	 * @param string $user database username
	 * @param string $pass database password
	 * @param string $db database name
	 * @param int $port database port
	 * @return true on success, ErrorObject on failure + $link object by reference
	 */
	public static function connect(&$link, $db_params, $db_name)
	{
		// set mysqli to connect via tcp
		if ($host == 'localhost') {
			$host = '127.0.0.1';
		}
		logMessage(L_INFO, sprintf("Connect to db: %s, %s, %s, %s",$db_params['db_host'], $db_params['db_user'], $db_params['db_pass'], $db_params['db_port']));
		if (trim($pass) == '') {
			$pass = null;
		}
		$link = @mysqli_init();
		$result = @mysqli_real_connect($link, $db_params['db_host'], $db_params['db_user'], $db_params['db_pass'], $db_name, $db_params['db_port']);
		if (!$result) {
			logMessage(L_ERROR, sprintf("Cannot connect to db: %s, %s, %s", $db_params['db_host'], $db_params['db_user'], $link->error));
			return false;
		}
		return true;
	}
		
	/**
	 * Execute a mySQL query or multi queries
	 * @param string $query mySQL query, or multiple queries seperated by a ';'
	 * @param string $host database host
	 * @param string $user database username
	 * @param string $pass database password
	 * @param string $db database name
	 * @param int $port database port
	 * @param mysqli $link mysqli link
	 * @return true on success, ErrorObject on failure
	 */
	public static function executeQuery($query, $db_params, $db_name, $link = null)
	{
		logMessage(L_INFO, "Execute query: $query");		
		// connect if not yet connected
		if (!$link && !self::connect($link, $db_params, $db_name)) {
			return false;
		}
		
		// use desired database
		else if (isset($db_name) && !mysqli_select_db($link, $db_name)) {
			logMessage(L_ERROR, "Cannot execute query: could not find the db: $db");
			return false;
		}
		
		// execute all queries
		if (!mysqli_multi_query($link, $query) || $link->error != '') {
			logMessage(L_ERROR, "Cannot execute query: error with query: $query");
			return false;		
		}
		// flush
		while (mysqli_more_results($link) && mysqli_next_result($link)) {
			$discard = mysqli_store_result($link);
		}
		$link->commit();
		
		return true;
	}
		
	/**
	 * Create a new mySQL database
	 * @param string $db database name
	 * @param string $host database host
	 * @param string $user database username
	 * @param string $pass database password
	 * @param int $port database port
	 * @return true on success, ErrorObject on failure
	 */
	public static function createDb($db_params, $db_name)
	{
		logMessage(L_INFO, "Creating database $db_name");	
		$create_db_query = "CREATE DATABASE $db_name;";
		return self::executeQuery($create_db_query, $db_params, null);
	}
		
	/**
	 * Drop a mySQL database
	 * @param string $db database name
	 * @param string $host database host
	 * @param string $user database username
	 * @param string $pass database password
	 * @param int $port database port
	 * @return true on success, ErrorObject on failure
	 */
	public static function dropDb($db_params, $db_name)
	{
		logMessage(L_INFO, "Dropping database $db_name");	
		$drop_db_query = "DROP DATABASE $db_name;";
		return self::executeQuery($drop_db_query, $db_params, null);
	}
		
	/**
	 * Check if a mySQL database exists
	 * @param string $db database name
	 * @param string $host database host
	 * @param string $user database username
	 * @param string $pass database password
	 * @param int $port database port
	 * @return true/false according to existence
	 */
	public static function dbExists($db_params, $db_name)
	{
		logMessage(L_INFO, "Check database exists $db_name");	
		if (!self::connect($link, $db_params, null)) {
			logMessage(L_ERROR, "Could not database $db_name: could not connect to host");	
			return -1;
		}
		return mysqli_select_db($link, $db_name);
	}
			
	/**
	 * Execute mySQL queries from a given sql file
	 * @param string $file sql file
	 * @param string $host database host
	 * @param string $user database user
	 * @param string $pass database password
	 * @param string $db database name
	 * @param int $port database port
	 * @return true on success, ErrorObject on failure
	 */
	public static function runScript($file, $db_params, $db_name)
	{
		if (!is_file($file)) {
			logMessage(L_ERROR, "Could not run script: script not found $file");	
			return false;
		}
		
		$data = trim(file_get_contents($file));
		if (!$data) {
			logMessage(L_ERROR, "Could not run script: can't read $file");	
			return false;		
		}
		
		return self::executeQuery($data, $db_params, $db_name);
	}				
}
