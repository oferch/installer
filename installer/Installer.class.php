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
		
		foreach ($install->getSymLinks() as $slink) {
			$link_items = explode('^', $app->replaceTokensInString($slink));	
			if (is_file($link_items[1])) {
				if ($report_only) $leftovers .= "\tLeftovers found: ".$link_items[1]." symbolic link exists".PHP_EOL;
				else OsUtils::recursiveDelete($link_items[1]);			
			}
		}
		
		$verify = detectDatabases($db_params);
		if (isset($verify)) {
			if ($report_only) $leftovers .= $verify;
			else {			
				@exec(sprintf('%s/ddl/dwh_drop_databases.sh -u %s -p %s -d %s', $app->get('DWH_DIR'), $app->get('DWH_USER'), $app_config['DWH_PASS'], $app_config['DWH_DIR']));
				detectDatabases($db_params, true);
			}
		}	
		if (is_dir($app->get('BASE_DIR'))) {
			if ($report_only) $leftovers .= "\tLeftovers found: Target directory ".$app_config->get('BASE_DIR')." already exists".PHP_EOL;
			else {
				@exec($app->get('BASE_DIR').'app/scripts/searchd.sh stop  2>&1');
				@exec($app->get('BASE_DIR').'app/scripts/serviceBatchMgr.sh stop  2>&1');			
				OsUtils::recursiveDelete($app_config->get('BASE_DIR'));			
			}
		}
		
		return $leftovers;
	}	
	
	public function install($app, $db_params, $texts) {
		logMessage(L_USER, sprintf("Copying application files to %s", $app->get('BASE_DIR')));
		if (!OsUtils::fullCopy('package/app/', $app->get('BASE_DIR'), true)) return $texts->getErrorText('failed_copy');
		logMessage(L_USER, "Finished copying application files");

		logMessage(L_USER, "Replacing configuration tokens in files");
		foreach ($this->getTokenFiles() as $file) {
			$replace_file = $app->replaceTokensInString($file);
			if (!$app->replaceTokensInFile($replace_file)) return $texts->getErrorText('failed_replacing_tokens');
			else logMessage(L_USER, "\t$replace_file");
		}
		logMessage(L_USER, "Finished replacing configuration tokens");

		$os_name = 	OsUtils::getOsName(); // Already verified that the OS is supported in the prerequisites
		$architecture = OsUtils::getSystemArchitecture();	
		logMessage(L_USER, "Adjusting binaries to system architecture: $os_name $architecture");
		$bin_subdir = $os_name.'/'.$architecture;
		if (!OsUtils::fullCopy($app->get('BIN_DIR')."/$bin_subdir", $app->get('BIN_DIR'), true)) return $texts->getErrorText('failed_architecture_copy');
		if (!OsUtils::recursiveDelete($app->get('BIN_DIR')."/$os_name")) return $texts->getErrorText('failed_architecture_delete');
		logMessage(L_USER, "Finished adjusting binaries to system architecture");

		logMessage(L_USER, "Changing permissions of directories and files");
		foreach ($this->getChmodItems() as $item) {
			$chmod_item = $app->replaceTokensInString($item);
			if (!OsUtils::chmod($chmod_item)) logMessage(L_USER, "\t$chmod_item");
			else return $texts->getErrorText('failed_cmod')." $chmod_item";
		}
		logMessage(L_USER, "Finished changing permissions");

		logMessage(L_USER, "Creating Kaltura databases");
		$sql_files = parse_ini_file($app->get('BASE_DIR').APP_SQL_SIR.'create_kaltura_db.ini', true);

		logMessage(L_USER, sprintf("Creating and initializing %s DB", $app->get('DB1_NAME')));
		if (!DatabaseUtils::createDb($db_params, $app->get('DB1_NAME'))) return $texts->getErrorText('failed_creating_kaltura_db');
		foreach ($sql_files['kaltura']['sql'] as $sql) {
			if (!DatabaseUtils::runScript($app->get('BASE_DIR').APP_SQL_SIR.$sql, $db_params, $app->get('DB1_NAME'))) return $texts->getErrorText('failed_init_kaltura_db');
		}

		logMessage(L_USER, "Creating and initializing %s DB", $app->get('DB_STATS_NAME'));
		if (!DatabaseUtils::createDb($db_params, $app->get('DB_STATS_NAME'))) return $texts->getErrorText('failed_creating_stats_db');
		foreach ($sql_files['stats']['sql'] as $sql) {
			if (!DatabaseUtils::runScript($app->get('BASE_DIR').APP_SQL_SIR.$sql, $db_params, $app->get('DB_STATS_NAME'))) return $texts->getErrorText('failed_init_stats_db');
		}
		logMessage(L_USER, "Finished creating Kaltura databases");
			
		logMessage(L_USER, "Creating Data Warehouse");
		if (!DatabaseUtils::runScript("package/dwh_grants/grants.sql", $db_params, $app->get('DB1_NAME'))) return $texts->getErrorText('failed_running_dwh_sql_script');		
		if (!@exec(sprintf("%s/ddl/dwh_ddl_install.sh -u %s -p %s -d %s", $app->get('DWH_DIR'), $app->get('DWH_USER'), $app->get('DWH_PASS'), $app->get('DWH_DIR')))) return $error_texts['failed_running_dwh_script'];
		logMessage(L_USER, "Finsihed creating Data Warehouse");

		logMessage(L_USER, "Creating system symbolic links");
		foreach ($install->getSymLinks() as $slink) {
			$link_items = explode('^', $app->replaceTokensInString($slink));	
			if (symlink($link_items[0], $link_items[1])) logMessage(L_USER, "\tCreated symblic link from $link_items[0] to $link_items[1]");
			else return sprintf($texts->getErrorText('failed_sym_link'), $link_items[0], $link_items[1]);
		}
		logMessage(L_USER, "Finished creating system symbolic links");

		if (strcasecmp($this->get('KALTURA_VERSION_TYPE'), K_CE_TYPE) == 0) {
			$app->simMafteach();
		}

		logMessage(L_USER, "Creating the uninstaller");
		if (!OsUtils::fullCopy('installer/uninstall.php', $app->get('BASE_DIR')."/uninstaller/")) return $texts->getErrorText('failed_creating_uninstaller');
		$app->saveUninstallerConfig();
		logMessage(L_USER, "Finished creating the uninstaller");

		logMessage(L_USER, "Running Kaltura");
		logMessage(L_USER, "Populating sphinx entries (executing '".$app->get('PHP_BIN').' '.$app->get('APP_DIR')."/deployment/base/scripts/populateSphinxEntries.php')");
		@exec($app->get('PHP_BIN').' '.$app->get('APP_DIR').'/deployment/base/scripts/populateSphinxEntries.php');
		logMessage(L_USER, "Running the batch manager (executing '".$app->get('APP_DIR')."/scripts/serviceBatchMgr.sh start 2>&1')");
		@exec($app->get('APP_DIR').'/scripts/serviceBatchMgr.sh start 2>&1');
		logMessage(L_USER, "Running the sphinx search deamon (executing '".$app->get('APP_DIR')."/scripts/searchd.sh start  2>&1')");
		@exec($app->get('APP_DIR').'/scripts/searchd.sh start  2>&1');
		
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