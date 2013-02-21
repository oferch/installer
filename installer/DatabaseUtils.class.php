<?php

/*
* This class is a static class with database utility functions
*/
class DatabaseUtils
{
	/**
	 * Connect to mySQL database
	 * @param mysqli $link mysqli link
	 * @param string $db_name database name
	 * @return true on success, false otherwise
	 */
	public static function connect(&$link, $db_name)
	{
		// set mysqli to connect via tcp
		$password = trim(AppConfig::get(AppConfigAttribute::DB_ROOT_PASS));
		if (!$password) {
			$password = null;
		}
		$link = @mysqli_init();
		/* @var $link mysqli */
		$result = @mysqli_real_connect($link, AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), $password, $db_name, AppConfig::get(AppConfigAttribute::DB1_PORT));
		if (!$result) {
			logMessage(L_ERROR, sprintf("Cannot connect to db: %s, %s, %s", AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), $link->error));
			return false;
		}
		return true;
	}

	/**
	 * Execute a mySQL query or multi queries
	 * @param string $query mySQL query, or multiple queries seperated by a ';'
	 * @param string $db_name database name
	 * @param mysqli $link mysqli link
	 * @return true on success, false otherwise
	 */
	public static function executeQuery($query, $db_name = null, $link = null)
	{
		// connect if not yet connected
		if (!$link && !self::connect($link, $db_name)) {
			return false;
		}

		// use desired database
		else if ($db_name && !mysqli_select_db($link, $db_name)) {
			logMessage(L_ERROR, "Cannot execute query: could not find the db: $db_name");
			return false;
		}

		// execute all queries
		if (!mysqli_multi_query($link, $query) || $link->error != '') {
			logMessage(L_ERROR, "Cannot execute query: error with query: $query, error: ".$link->error);
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
	 * @param string $db_name database name
	 * @return true on success, false otherwise
	 */
	public static function createDb($db_name)
	{
		logMessage(L_INFO, "Creating database $db_name");
		$create_db_query = "CREATE DATABASE $db_name;";
		return self::executeQuery($create_db_query);
	}

	/**
	 * Drop a mySQL database
	 * @param string $db_name database name
	 * @return true on success, false otherwise
	 */
	public static function dropDb($db_name)
	{
		logMessage(L_INFO, "Dropping database $db_name");
		$drop_db_query = "DROP DATABASE $db_name;";
		return self::executeQuery($drop_db_query);
	}

	/**
	 * Check if a mySQL database exists
	 * @param string $db_name database name
	 * @return true/false according to existence
	 */
	public static function dbExists($db_name)
	{
		$link = null;
		if (!self::connect($link)) {
			logMessage(L_ERROR, "Could not database $db_name: could not connect to host");
			return -1;
		}
		return mysqli_select_db($link, $db_name);
	}

	/**
	 * Execute mySQL queries from a given sql file
	 * @param string $file sql file
	 * @param string $db_name database name
	 * @return true on success, false otherwise
	 */
	public static function runScript($file, $db_name) {
		if (!is_file($file)) {
			logMessage(L_ERROR, "Could not run script: script not found $file");
			return false;
		}

		if (empty(AppConfig::get(AppConfigAttribute::DB_ROOT_PASS))) {
			$cmd = sprintf("mysql -h%s -u%s -P%s %s < %s", AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DB1_PORT), $db_name, $file);
		} else {
			$cmd = sprintf("mysql -h%s -u%s -p%s -P%s %s < %s", AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DB_ROOT_PASS), AppConfig::get(AppConfigAttribute::DB1_PORT), $db_name, $file);
		}
		logMessage(L_INFO, "Executing $cmd");
		@exec($cmd . ' 2>&1', $output, $return_var);
		if ($return_var === 0) {
			return true;
		} else {
			logMessage(L_ERROR, "Executing command failed: ".implode("\n",$output));
			return false;
		}
	}
}
