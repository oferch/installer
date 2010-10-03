<?php

define("FILE_INSTALL_CONFIG", "installer/installation.ini");

class Installer {	
	private $install_config;

	public function __construct() {
		$this->install_config = parse_ini_file(FILE_INSTALL_CONFIG, true);
	}

	public function getTokenFiles() {
		return $this->install_config['token_files']['files'];
	}
	
	public function getChmodItems() {
		return $this->install_config['chmod_items']['items'];
	}	
	
	public function getSymLinks() {
		return $this->install_config['symlinks']['links'];
	}

	public function getDatabases() {
		return $this->install_config['databases']["dbs"];
	}
	
	public function detectLeftovers($report_only, $app, $db_params) {
		$leftovers = null;		
		
		foreach ($this->getSymLinks() as $slink) {
			$link_items = explode('^', $app->replaceTokensInString($slink));	
			if (is_file($link_items[1])) {
				if ($report_only) $leftovers .= "\tLeftovers found: ".$link_items[1]." symbolic link exists".PHP_EOL;
				else OsUtils::recursiveDelete($link_items[1]);			
			}
		}
		
		$verify = $this->detectDatabases($db_params);
		if (isset($verify)) {
			if ($report_only) $leftovers .= $verify;
			else {			
				OsUtils::execute(sprintf('%s/ddl/dwh_drop_databases.sh -u %s -p %s -d %s', $app->get('DWH_DIR'), $app->get('DWH_USER'), $app_config['DWH_PASS'], $app_config['DWH_DIR']));
				$this->detectDatabases($db_params, true);
			}
		}	
		if (is_dir($app->get('BASE_DIR'))) {
			if ($report_only) $leftovers .= "\tLeftovers found: Target directory ".$app->get('BASE_DIR')." already exists".PHP_EOL;
			else {
				OsUtils::execute($app->get('BASE_DIR').'app/scripts/searchd.sh stop');
				OsUtils::execute($app->get('BASE_DIR').'app/scripts/serviceBatchMgr.sh stop');			
				OsUtils::recursiveDelete($app->get('BASE_DIR'));			
			}
		}
		
		return $leftovers;
	}	
	
	public function install($app, $db_params) {
		logMessage(L_USER, sprintf("Copying application files to %s", $app->get('BASE_DIR')));
		if (!OsUtils::fullCopy('package/app/', $app->get('BASE_DIR'))) {
			return "Failed copying Kaltura application to target directory";
		}		

		$os_name = 	OsUtils::getOsName(); // Already verified that the OS is supported in the prerequisites
		$architecture = OsUtils::getSystemArchitecture();	
		logMessage(L_USER, "Copying binaries for $os_name $architecture");
		if (!OsUtils::fullCopy("package/bin/$os_name/$architecture", $app->get('BIN_DIR'))) {
			return "Failed copying binaris for $os_name $architecture";
		}
				
		logMessage(L_USER, "Replacing configuration tokens in files");
		foreach ($this->getTokenFiles() as $file) {
			$replace_file = $app->replaceTokensInString($file);
			if (!$app->replaceTokensInFile($replace_file)) {
				return "Failed to replace tokens in $replace_file";
			}
		}		

		logMessage(L_USER, "Changing permissions of directories and files");
		foreach ($this->getChmodItems() as $item) {
			$chmod_item = $app->replaceTokensInString($item);
			if (!OsUtils::chmod($chmod_item)) {
				return "Failed changing permission for $chmod_item";
			}
		}		

		$sql_files = parse_ini_file($app->get('BASE_DIR').APP_SQL_SIR.'create_kaltura_db.ini', true);

		logMessage(L_USER, sprintf("Creating and initializing '%s' database", $app->get('DB1_NAME')));
		if (!DatabaseUtils::createDb($db_params, $app->get('DB1_NAME'))) {
			return "Failed creating ".$app->get('DB1_NAME')." DB";
		}
		foreach ($sql_files['kaltura']['sql'] as $sql) {
			$sql_file = $app->get('BASE_DIR').APP_SQL_SIR.$sql;
			if (!DatabaseUtils::runScript($sql_file, $db_params, $app->get('DB1_NAME'))) {
				return "Failed running DB script $sql_file";
			}
		}

		logMessage(L_USER, sprintf("Creating and initializing '%s' database", $app->get('DB_STATS_NAME')));
		if (!DatabaseUtils::createDb($db_params, $app->get('DB_STATS_NAME'))) {
			return "Failed creating ".$app->get('DB_STATS_NAME')." DB";
		}
		foreach ($sql_files['stats']['sql'] as $sql) {
			$sql_file = $app->get('BASE_DIR').APP_SQL_SIR.$sql;
			if (!DatabaseUtils::runScript($sql_file, $db_params, $app->get('DB_STATS_NAME'))) {
				return "Failed running DB script $sql_file";
			}
		}
			
		logMessage(L_USER, "Creating data warehouse");
		if (!DatabaseUtils::runScript("package/dwh_grants/grants.sql", $db_params, $app->get('DB1_NAME'))) {
			return "Failed running Data Warehouse permission initialization script";		
		}
		if (!OsUtils::execute(sprintf("%s/ddl/dwh_ddl_install.sh -u %s -p %s -d %s", $app->get('DWH_DIR'), $app->get('DWH_USER'), $app->get('DWH_PASS'), $app->get('DWH_DIR')))) {		
			return $error_texts['failed_running_dwh_script'];
		}

		logMessage(L_USER, "Creating system symbolic links");
		foreach ($this->getSymLinks() as $slink) {
			$link_items = explode('^', $app->replaceTokensInString($slink));	
			if (symlink($link_items[0], $link_items[1])) {
				logMessage(L_INFO, "Created symbolic link $link_items[0] -> $link_items[1]");
			} else {
				return sprintf("Failed to create symblic link from %s to %s", $link_items[0], $link_items[1]);
			}
		}

		if (strcasecmp($app->get('KALTURA_VERSION_TYPE'), K_CE_TYPE) == 0) {
			$app->simMafteach();
		}

		logMessage(L_USER, "Creating the uninstaller");
		if (!OsUtils::fullCopy('installer/uninstall.php', $app->get('BASE_DIR')."/uninstaller/")) {
			return "Failed creating the uninstaller";
		}
		$app->saveUninstallerConfig();

		logMessage(L_USER, "Running Kaltura");
		logMessage(L_USER, "Populating sphinx entries (executing '".$app->get('PHP_BIN').' '.$app->get('APP_DIR')."/deployment/base/scripts/populateSphinxEntries.php')");
		OsUtils::execute($app->get('PHP_BIN').' '.$app->get('APP_DIR').'/deployment/base/scripts/populateSphinxEntries.php');
		logMessage(L_USER, "Running the batch manager (executing '".$app->get('APP_DIR')."/scripts/serviceBatchMgr.sh start')");
		OsUtils::execute($app->get('APP_DIR').'/scripts/serviceBatchMgr.sh start');
		logMessage(L_USER, "Running the sphinx search deamon (executing '".$app->get('APP_DIR')."/scripts/searchd.sh start')");
		OsUtils::execute($app->get('APP_DIR').'/scripts/searchd.sh start');
		
		return null;
	}
	
	private function detectDatabases($db_params, $should_drop=false) {
		$verify = null;
		foreach ($this->getDatabases() as $db) {
			$result = DatabaseUtils::dbExists($db_params, $db);
			
			if ($result === -1) {
				$verify .= "\tLeftovers found: Error verifying if db exists $db".PHP_EOL;
			} else if ($result === true) {
				$verify .= "\tLeftovers found: DB already exists $db".PHP_EOL;
				if ($should_drop) DatabaseUtils::dropDb($db_params, $db);
			}
		}
		return $verify;
	}	
}