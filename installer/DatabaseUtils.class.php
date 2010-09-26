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
	public static function connect(&$link, $host, $user, $pass, $db, $port)
	{
		// set mysqli to connect via tcp
		if ($host == 'localhost') {
			$host = '127.0.0.1';
		}
		logMessage(L_INFO, "Connect to db: $host, $user, $db, $port");		
		if (trim($pass) == '') {
			$pass = null;
		}
		$link = @mysqli_init();
		$result = @mysqli_real_connect($link, $host, $user, $pass, $db, $port);
		if (!$result) {
			logMessage(L_ERROR, "Cannot connect to db: $host, $user, $link->error");
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
	public static function executeQuery($query, $host, $user, $pass, $db, $port, $link = null)
	{
		logMessage(L_INFO, "Execute query: $query");		
		// connect if not yet connected
		if (!$link && !self::connect($link, $host, $user, $pass, $db, $port)) {
			return false;
		}
		
		// use desired database
		else if (isset($db) && !mysqli_select_db($link, $db)) {
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
	public static function createDb($db, $host, $user, $pass, $port)
	{
		logMessage(L_INFO, "Creating database $db");	
		$create_db_query = "CREATE DATABASE $db;";
		return self::executeQuery($create_db_query, $host, $user, $pass, null, $port);
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
	public static function dropDb($db, $host, $user, $pass, $port)
	{
		logMessage(L_INFO, "Dropping database $db");	
		$drop_db_query = "DROP DATABASE $db;";
		return self::executeQuery($drop_db_query, $host, $user, $pass, null, $port);
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
	public static function dbExists($db, $host, $user, $pass, $port)
	{
		logMessage(L_INFO, "Check database exists $db");	
		if (!self::connect($link, $host, $user, $pass, null, $port)) {
			return -1;
		}
		return mysqli_select_db($link, $db);
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
	public static function runScript($file, $host, $user, $pass, $db, $port)
	{
		logMessage(L_INFO, "Run sql script $file");	
		if (!is_file($file)) {
			logMessage(L_ERROR, "Could not run script: script not found $file");	
			return false;
		}
		
		$data = trim(file_get_contents($file));
		if (!$data) {
			logMessage(L_ERROR, "Could not run script: can't read $file");	
			return false;		
		}
		
		return self::executeQuery($data, $host, $user, $pass, $db, $port);
	}				
}
