<?php

define("FILE_INSTALL_CONFIG", "installer/installation.ini"); // this file contains the definitions of the installation itself
define("APP_SQL_DIR", "/app/deployment/final/sql/"); // this is the relative directory where the final sql files are
define("SYMLINK_SEPARATOR", "^"); // this is the separator between the two parts of the symbolic link definition

/*
* This class handles the installation itself. It has functions for installing and for cleaning up.
*/
class Installer {	
	private $install_config;

	// crteate a new installer, loads installation configurations from installation configuration file
	public function __construct() {
		$this->install_config = parse_ini_file(FILE_INSTALL_CONFIG, true);
	}
	
	// detects if there are leftovers of an installation
	// can be used both before installation to verify and when the installation failed for cleaning up
	// $report_only - if set to true only returns the leftovers found and does not removes them
	// $db_params - the database parameters array used for the installation ('db_host', 'db_user', 'db_pass', 'db_port')
	// returns null if no leftovers are found or it is not report only or a text containing all the leftovers found
	public function detectLeftovers($report_only, $db_params) {
		$leftovers = null;		
		
		// symbloic links leftovers
		foreach ($this->install_config['symlinks'] as $slink) {
			$link_items = explode(SYMLINK_SEPARATOR, AppConfig::replaceTokensInString($slink));	
			if (is_file($link_items[1]) && (strpos($link_items[1], AppConfig::get(AppConfigAttribute::BASE_DIR)) === false)) {
				if ($report_only) {
					$leftovers .= "   ".$link_items[1]." symbolic link exists".PHP_EOL;
				} else {
					logMessage(L_USER, "Removing symbolic link $link_items[1]");
					OsUtils::recursiveDelete($link_items[1]);
				}
			}
		}
		
		// database leftovers
		$verify = $this->detectDatabases($db_params);
		if (isset($verify)) {
			if(!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
			{
				//do nothing
			}
			else if ($report_only) {
				$leftovers .= $verify;
			}  
			else {			
				$this->detectDatabases($db_params, true);
			}
		}
		
		// application leftovers
		if (is_dir(AppConfig::get(AppConfigAttribute::BASE_DIR)) && (($files = @scandir(AppConfig::get(AppConfigAttribute::BASE_DIR))) && count($files) > 2)) {
			if ($report_only) {
				$leftovers .= "   Target directory ".AppConfig::get(AppConfigAttribute::BASE_DIR)." already exists".PHP_EOL;
			} else {
				logMessage(L_USER, "killing sphinx daemon if running");
				$currentWorkingDir = getcwd();
				chdir(AppConfig::get(AppConfigAttribute::APP_DIR).'/app/plugins/sphinx_search/scripts/');
				@exec(AppConfig::get(AppConfigAttribute::BASE_DIR).'/app/plugins/sphinx_search/scripts/watch.stop.sh -u kaltura');
				logMessage(L_USER, "Stopping sphinx if running");
				@exec(AppConfig::get(AppConfigAttribute::BASE_DIR).'/app/plugins/sphinx_search/scripts/searchd.sh stop 2>&1', $output, $return_var);
				logMessage(L_USER, "Stopping the batch manager if running");
				chdir(AppConfig::get(AppConfigAttribute::APP_DIR).'/scripts/');
				@exec(AppConfig::get(AppConfigAttribute::BASE_DIR).'/app/scripts/serviceBatchMgr.sh stop 2>&1', $output, $return_var);
				chdir($currentWorkingDir);
				logMessage(L_USER, "Deleting ".AppConfig::get(AppConfigAttribute::BASE_DIR));
				OsUtils::recursiveDelete(AppConfig::get(AppConfigAttribute::BASE_DIR));			
			}
		}
		
		return $leftovers;
	}	
	
	/**
	 * Installs the application according to the given parameters\
	 * @param unknown_type $db_params database parameters array used for the installation ('db_host', 'db_user', 'db_pass', 'db_port')
	 * @return string|NULL null if the installation succeeded or an error text if it failed
	 */
	public function install($db_params) {
		logMessage(L_USER, sprintf("Copying application files to %s", AppConfig::get(AppConfigAttribute::BASE_DIR)));
		logMessage(L_USER, sprintf("current working dir is %s", getcwd()));
		if (!OsUtils::rsync('../package/', AppConfig::get(AppConfigAttribute::BASE_DIR))) {
			return "Failed to copy application files to target directory";
		}

		logMessage(L_USER, "Creating the uninstaller");
		if (!OsUtils::fullCopy('installer/uninstall.php', AppConfig::get(AppConfigAttribute::BASE_DIR)."/uninstaller/")) {
			return "Failed to create the uninstaller";
		}
		//create uninstaller.ini with minimal definitions
		AppConfig::saveUninstallerConfig();
		
		//OsUtils::logDir definition
		OsUtils::$logDir = AppConfig::get(AppConfigAttribute::LOG_DIR);
		
		// if vmware installation copy configurator folders
		if (AppConfig::get(AppConfigAttribute::KALTURA_PREINSTALLED)) {
			mkdir(AppConfig::get(AppConfigAttribute::BASE_DIR).'/installer', 0777, true);
			if (!OsUtils::rsync('installer/', AppConfig::get(AppConfigAttribute::BASE_DIR).'/installer')) {
				return "Failed to copy installer files to target directory";
			}
			
			if (!OsUtils::fullCopy('configurator/', AppConfig::get(AppConfigAttribute::BASE_DIR).'/installer')) {
				return "Failed to copy configurator files to target directory";
			}
			
			if (!OsUtils::fullCopy('configure.php', AppConfig::get(AppConfigAttribute::BASE_DIR)."/installer/")) {
				return "Failed to copy configure.php file to target directory";
			}		
		}
		
		logMessage(L_USER, "Replacing configuration tokens in files");
		foreach ($this->install_config['token_files'] as $file) {
			$replace_file = AppConfig::replaceTokensInString($file);
			if (!AppConfig::replaceTokensInFile($replace_file)) {
				return "Failed to replace tokens in $replace_file";
			}
		}		

		$this->changeDirsAndFilesPermissions();
		$this->createdDatabases();
		
		if((!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB)) && (DatabaseUtils::dbExists($db_params, AppConfig::get(AppConfigAttribute::DWH_DATABASE_NAME)) === true))
		{		
			logMessage(L_USER, sprintf("Skipping '%s' database creation", AppConfig::get(AppConfigAttribute::DWH_DATABASE_NAME)));
		}
		else 
		{
			logMessage(L_USER, "Creating data warehouse");
			if (!OsUtils::execute(sprintf("%s/setup/dwh_setup.sh -h %s -P %s -u %s -p %s -d %s ", AppConfig::get(AppConfigAttribute::DWH_DIR), AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB1_PORT), AppConfig::get(AppConfigAttribute::DB1_USER), AppConfig::get(AppConfigAttribute::DB1_PASS), AppConfig::get(AppConfigAttribute::DWH_DIR)))) {		
				return "Failed running data warehouse initialization script";
			}
		}
		
		logMessage(L_USER, "Creating Dynamic Enums");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/installPlugins.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				logMessage(L_INFO, "Dynamic Enums created");
		} else {
			return "Failed to create dynamic enums";
		}
			
		logMessage(L_USER, "Create query cache triggers");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/createQueryCacheTriggers.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			logMessage(L_INFO, "sphinx Query Cache Triggers created");
		} else {
			return "Failed to create QueryCacheTriggers";
		}
		
		logMessage(L_USER, "Populate sphinx tables");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxEntries.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				logMessage(L_INFO, "sphinx entries log created");
		} else {
			return "Failed to populate sphinx log from entries";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxEntryDistributions.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				logMessage(L_INFO, "sphinx content distribution log created");
		} else {
			return "Failed to populate sphinx log from content distribution";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxCuePoints.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				logMessage(L_INFO, "sphinx cue points log created");
		} else {
			return "Failed to populate sphinx log from cue points";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxKusers.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			logMessage(L_INFO, "sphinx Kusers log created");
		} else {
			return "Failed to populate sphinx log from Kusers";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxTags.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			logMessage(L_INFO, "sphinx tags log created");
		} else {
			return "Failed to populate sphinx log from tags";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxCategories.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			logMessage(L_INFO, "sphinx Categoriess log created");
		} else {
			return "Failed to populate sphinx log from categories";
		}
		
		logMessage(L_USER, "Creating system symbolic links");
		foreach ($this->install_config['symlinks'] as $slink) {
			$link_items = explode(SYMLINK_SEPARATOR, AppConfig::replaceTokensInString($slink));	
			if (symlink($link_items[0], $link_items[1])) {
				logMessage(L_INFO, "Created symbolic link $link_items[0] -> $link_items[1]");
			} else {
				return sprintf("Failed to create symblic link from %s to %s", $link_items[0], $link_items[1]);
			}
		}
		
		//update uninstaller config
		AppConfig::updateUninstallerConfig($this->install_config['symlinks']);
		
		if (strcasecmp(AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE), K_CE_TYPE) == 0) {
			AppConfig::simMafteach();
		}
	
		logMessage(L_USER, "Deploying uiconfs in order to configure the application");
		foreach ($this->install_config['uiconfs_2'] as $uiconfapp) {
			$to_deploy = AppConfig::replaceTokensInString($uiconfapp);
			if (OsUtils::execute(sprintf("%s %s/deployment/uiconf/deploy_v2.php --ini=%s", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR), $to_deploy))) {
				logMessage(L_INFO, "Deployed uiconf $to_deploy");
			} else {
				return "Failed to deploy uiconf $to_deploy";
			}
		}
				
		logMessage(L_USER, "clear cache");
		if (!OsUtils::execute(sprintf("%s %s/scripts/clear_cache.php -y", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			return "Failed clear cache";
		}
		
		logMessage(L_USER, "Running the generate script");
		$currentWorkingDir = getcwd();
		chdir(AppConfig::get(AppConfigAttribute::APP_DIR).'/generator');
		if (!OsUtils::execute(AppConfig::get(AppConfigAttribute::APP_DIR).'/generator/generate.sh')) {
			return "Failed running the generate script";
		}
		
		logMessage(L_USER, "Running the batch manager");
		chdir(AppConfig::get(AppConfigAttribute::APP_DIR).'/scripts/');
		if (!OsUtils::execute(AppConfig::get(AppConfigAttribute::APP_DIR).'/scripts/serviceBatchMgr.sh start')) {
			return "Failed running the batch manager";
		}
		chdir($currentWorkingDir);
		
		logMessage(L_USER, "Running the sphinx search deamon");
		print("Executing sphinx dameon \n");
		OsUtils::executeInBackground('nohup '.AppConfig::get(AppConfigAttribute::APP_DIR).'/plugins/sphinx_search/scripts/watch.daemon.sh');
		OsUtils::executeInBackground('chkconfig sphinx_watch.sh on');
		
		OsUtils::execute('cp ../package/version.ini ' . AppConfig::get(AppConfigAttribute::APP_DIR) . '/configurations/');
		
		return null;
	}
	
	// detects if there are databases leftovers
	// can be used both for verification and for dropping the databases
	// $db_params - the database parameters array used for the installation ('db_host', 'db_user', 'db_pass', 'db_port')
	// $should_drop - whether to drop the databases that are found or not (default - false) 
	// returns null if no leftovers are found or a text containing all the leftovers found
	private function detectDatabases($db_params, $should_drop=false) {
		$verify = null;
		foreach ($this->install_config['databases'] as $db) {
			$result = DatabaseUtils::dbExists($db_params, $db);
			
			if ($result === -1) {
				$verify .= "   Cannot verify if '$db' database exists".PHP_EOL;
			} else if ($result === true) {
				if (!$should_drop) {
					$verify .= "   '$db' database already exists ".PHP_EOL;
				} else {
					logMessage(L_USER, "Dropping '$db' database");
					DatabaseUtils::dropDb($db_params, $db);
				}
			}
		}
		return $verify;
	}	
	
	private function changeDirsAndFilesPermissions()
	{
		logMessage(L_USER, "Changing permissions of directories and files");
		$baseDir = AppConfig::get(AppConfigAttribute::BASE_DIR);

		$originalDir = getcwd();
		chdir(__DIR__ . '/../directoryConstructor');
		$command = "phing -DBASE_DIR=$baseDir Update-Permissions";
		$returnedValue = null;
		passthru($command, $returnedValue);			
		chdir($originalDir);
	}	
	
	private function createdDatabases()
	{
		logMessage(L_USER, "Creating databases and database users");
		$baseDir = AppConfig::get(AppConfigAttribute::BASE_DIR);

		$originalDir = getcwd();
		chdir(__DIR__ . '/../dbSchema');
		
		$attributes = array(
			'-DBASE_DIR=' . $baseDir,
			'-Duser.attributes.kaltura.password=' . AppConfig::get(AppConfigAttribute::DB1_PASS),
			'-Duser.attributes.kaltura_sphinx.password=' . AppConfig::get(AppConfigAttribute::SPHINX_DB_PASS),
			'-Duser.attributes.kaltura_etl.password=' . AppConfig::get(AppConfigAttribute::DWH_PASS),
		);
		$command = "phing " . implode(' ', $attributes);
		$returnedValue = null;
		passthru($command, $returnedValue);			
		chdir($originalDir);
	
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertDefaults.php %s/deployment/base/scripts/init_data", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				logMessage(L_INFO, "Default content inserted");
		} else {
			return "Failed to insert default content";
		}
	}	
	
	public function installRed5 ()
	{
		OsUtils::execute("dos2unix " . AppConfig::get(AppConfigAttribute::BIN_DIR) ."/red5/red5");
		OsUtils::execute("ln -s ". AppConfig::get(AppConfigAttribute::BIN_DIR) ."/red5/red5 /etc/init.d/red5");
		OsUtils::execute("/etc/init.d/red5 start");
		OsUtils::executeInBackground('chkconfig red5 on');
		
		//Replace rtmp_url parameter in the local.ini configuration file
		$location = AppConfig::get(AppConfigAttribute::APP_DIR)."/configurations/local.ini";
		$localValues = parse_ini_file($location, true);
		$localValues['rtmp_url'] = 'rtmp://' . AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME) . '/oflaDemo'; 
		OsUtils::writeToIniFile($location, $localValues);
		
		//url-managers.ini change
		$location  = AppConfig::get(AppConfigAttribute::APP_DIR)."/configurations/url_managers.ini";
		$urlManagersValues = parse_ini_file($location);
		$red5Addition = array ('class' => 'kLocalPathUrlManager');
		$urlManagersValues[AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME)] = $red5Addition;
		OsUtils::writeToIniFile($location, $urlManagersValues);
		
		//Retrieve KCW uiconf ids
		$uiconfIds = $this->extractKCWUiconfIds();
		logMessage(L_USER, "If you are insterested in recording entries from webcam, please adjust the RTMP server URL in each of the following uiConfs:\r\n". implode("\r\n", $uiconfIds));
	    logMessage(L_USER, "By replacing 'rtmp://yoursite.com/oflaDemo' with 'rtmp://". AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME) . "/oflaDemo");
		
		OsUtils::execute("mv ". AppConfig::get(AppConfigAttribute::BIN_DIR) . "/red5/webapps/oflaDemo/streams " . AppConfig::get(AppConfigAttribute::BIN_DIR). "/red5/webapps/oflaDemo/streams_x");
		OsUtils::execute ("ln -s " .AppConfig::get(AppConfigAttribute::WEB_DIR). "/content/webcam " . AppConfig::get(AppConfigAttribute::BIN_DIR) ."/red5/webapps/oflaDemo/streams");
		OsUtils::execute ("ln -s " .AppConfig::get(AppConfigAttribute::WEB_DIR). "/content " . AppConfig::get(AppConfigAttribute::BIN_DIR) . "/red5/webapps/oflaDemo/streams");
	}
	
	private function extractKCWUiconfIds ()
	{
		$uiconfIds = array();
		$log = file_get_contents(AppConfig::get(AppConfigAttribute::LOG_DIR) . "/instlBkgrndRun.log");
		preg_match_all('/creating uiconf \[\d+\] for widget \w+ with default values \( \/flash\/kcw/', $log, $matches);
		foreach ($matches[0] as $match)
		{
			preg_match('/\[\d+\]/', $match, $bracketedId);
			$id = str_replace(array ('[' , ']'), array ('', ''), $bracketedId[0]);
			$uiconfIds[] = $id;
		}
		
		return $uiconfIds;
	}
}
