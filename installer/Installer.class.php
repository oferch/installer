<?php

define("SYMLINK_SEPARATOR", "^"); // this is the separator between the two parts of the symbolic link definition

/*
* This class handles the installation itself. It has functions for installing and for cleaning up.
*/
class Installer {	
	private $install_config;
	
	/**
	 * Array of tasks that should be done once during the installation, for different components
	 * @var array
	 */
	private $run_once = array();
	
	/**
	 * Array of the components that should be installed
	 * @var array
	 */
	private $components = array('all');
	
	// crteate a new installer, loads installation configurations from installation configuration file
	public function __construct($components = '*') 
	{
		$this->install_config = parse_ini_file(__DIR__ . '/installation.ini', true);
		
		if($components && is_array($components))
		{
			foreach($components as $component)
				if(isset($this->install_config[$component]))
					$this->components[] = $component;
		}
		elseif($components == '*')
		{
			foreach($this->install_config as $component => $config)
				if($component != 'all' && $config['install_by_default'])
					$this->components[] = $component;
		}
	}
	
	// detects if there are leftovers of an installation
	// can be used both before installation to verify and when the installation failed for cleaning up
	// $report_only - if set to true only returns the leftovers found and does not removes them
	// $db_params - the database parameters array used for the installation ('db_host', 'db_user', 'db_pass', 'db_port')
	// returns null if no leftovers are found or it is not report only or a text containing all the leftovers found
	public function detectLeftovers($report_only, $db_params) {
		$leftovers = null;		
		
		// symbloic links leftovers
		foreach($this->install_config as $component => $config)
		{
			if(isset($config['symlinks']) && is_array($config['symlinks']))
			{
				foreach ($config['symlinks'] as $slink) 
				{
					list($target, $link) = explode(SYMLINK_SEPARATOR, AppConfig::replaceTokensInString($slink));	
					if (is_file($link) && (strpos($link, AppConfig::get(AppConfigAttribute::BASE_DIR)) === false)) 
					{
						if ($report_only) 
						{
							$leftovers .= "   ".$link." symbolic link exists".PHP_EOL;
						} 
						else 
						{
							logMessage(L_USER, "Removing symbolic link $link");
							OsUtils::recursiveDelete($link);
						}
					}
				}
			}
			
			if(isset($config['databases']) && is_array($config['databases']))
			{
				// database leftovers
				$verify = $this->detectDatabases($config['databases'], $db_params);
				if (isset($verify)) {
					if(!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
					{
						//do nothing
					}
					else if ($report_only) {
						$leftovers .= $verify;
					}  
					else {			
						$this->detectDatabases($config['databases'], $db_params, true);
					}
				}
			}
			
			// application leftovers
			if (is_dir(AppConfig::get(AppConfigAttribute::BASE_DIR)) && (($files = @scandir(AppConfig::get(AppConfigAttribute::BASE_DIR))) && count($files) > 2)) {
				if ($report_only) {
					$leftovers .= "   Target directory ".AppConfig::get(AppConfigAttribute::BASE_DIR)." already exists".PHP_EOL;
				} else {
					
					if(isset($config['chkconfig']) && is_array($config['chkconfig']))
						foreach ($config['chkconfig'] as $service)
							OsUtils::stopService($service);
					
					logMessage(L_USER, "Deleting ".AppConfig::get(AppConfigAttribute::BASE_DIR));
					OsUtils::recursiveDelete(AppConfig::get(AppConfigAttribute::BASE_DIR));			
				}
			}
		}
		
		return $leftovers;
	}
	
	private function restartApache()
	{
		$this->run_once[] = 'restartApache';
	}
	
	private function generateClients()
	{
		$this->run_once[] = 'generateClients';
	}
	
	/**
	 * Installs the application according to the given parameters\
	 * @param unknown_type $db_params database parameters array used for the installation ('db_host', 'db_user', 'db_pass', 'db_port')
	 * @return string|NULL null if the installation succeeded or an error text if it failed
	 */
	public function install($db_params) {
		logMessage(L_USER, sprintf("Current working dir is %s", getcwd()));
		logMessage(L_USER, sprintf("Copying application files to %s", AppConfig::get(AppConfigAttribute::BASE_DIR)));
		if (!OsUtils::rsync('../package/', AppConfig::get(AppConfigAttribute::BASE_DIR), "--exclude web/content"))
			return "Failed to copy application files to target directory";
			
		if (AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
		{
			logMessage(L_USER, sprintf("Copying web content files to %s", AppConfig::get(AppConfigAttribute::WEB_DIR)));
			if (!OsUtils::rsync("../package/web/content", AppConfig::get(AppConfigAttribute::WEB_DIR)))
				return "Failed to copy default content into ". AppConfig::get(AppConfigAttribute::WEB_DIR);
		}		

		logMessage(L_USER, "Creating the uninstaller");
		if (!mkdir(AppConfig::get(AppConfigAttribute::BASE_DIR)."/uninstaller/", 0750, true) || !OsUtils::fullCopy('installer/uninstall.php', AppConfig::get(AppConfigAttribute::BASE_DIR)."/uninstaller/")) {
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
		}
		
		logMessage(L_USER, "Replacing configuration tokens in files");
		if(isset($this->install_config['all']['token_files']) && is_array($this->install_config['all']['token_files']))
		{
			foreach ($this->install_config['all']['token_files'] as $tokenFile) 
			{
				$files = glob(AppConfig::replaceTokensInString($tokenFile));
				foreach($files as $file)
				{
					if (!AppConfig::replaceTokensInFile($file))
						return "Failed to replace tokens in $file";
				}
			}
		}
	
		if((!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB)) && (DatabaseUtils::dbExists($db_params, AppConfig::get(AppConfigAttribute::DWH_DATABASE_NAME)) === true))
		{		
			logMessage(L_USER, sprintf("Skipping '%s' database creation", AppConfig::get(AppConfigAttribute::DWH_DATABASE_NAME)));
		}
		else 
		{
			logMessage(L_USER, "Creating data warehouse");
			if (!OsUtils::execute(sprintf("%s/setup/dwh_setup.sh -h %s -P %s -u %s -p %s -d %s ", AppConfig::get(AppConfigAttribute::DWH_DIR), AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB1_PORT), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DB_ROOT_PASS), AppConfig::get(AppConfigAttribute::DWH_DIR)))) {		
				return "Failed running data warehouse initialization script";
			}
		}
		
		foreach($this->components as $component)
			$this->installComponentSymlinks($component);
		
		if (strcasecmp(AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE), K_CE_TYPE) == 0) {
			AppConfig::simMafteach();
		}
				
		foreach($this->components as $component)
			$this->installComponent($component, $db_params);
		
		logMessage(L_USER, "Creating Dynamic Enums");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/installPlugins.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				logMessage(L_INFO, "Dynamic Enums created");
		} else {
			return "Failed to create dynamic enums";
		}
			
		if(!$this->createInitialContent())
			return "Failed to create initial content";
		
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
		
		logMessage(L_USER, "Deploying uiconfs in order to configure the application");
		if(isset($this->install_config['all']['uiconfs_2']) && is_array($this->install_config['all']['uiconfs_2']))
		{
			foreach($this->install_config['all']['uiconfs_2'] as $uiconfapp)
			{
				$to_deploy = AppConfig::replaceTokensInString($uiconfapp);
				if(OsUtils::execute(sprintf("%s %s/deployment/uiconf/deploy_v2.php --ini=%s", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR), $to_deploy)))
				{
					logMessage(L_INFO, "Deployed uiconf $to_deploy");
				}
				else
				{
					return "Failed to deploy uiconf $to_deploy";
				}
			}
		}
			
		if(in_array('generateClients', $this->run_once))
		{			
			logMessage(L_USER, "Running the generate script");
			if (!OsUtils::execute(sprintf("%s/generator/generate.sh", AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				return "Failed running the generate script";
			}
		}
		
		if(!$this->changeDirsAndFilesPermissions())
			return "Failed to set files permissions";
			
		if(in_array('restartApache', $this->run_once))
		{			
			logMessage(L_USER, "Restarting apache http server");
			if (!OsUtils::execute(AppConfig::get(AppConfigAttribute::APACHE_RESTART_COMMAND))) {
				return "Failed restarting apache http server";
			}
		}
		
		foreach($this->components as $component)
			$this->installComponentServices($component);
		
		if(!$this->createTemplateContent())
			return "Failed to create template content";
		
		OsUtils::execute('cp ../package/version.ini ' . AppConfig::get(AppConfigAttribute::APP_DIR) . '/configurations/');
		
		logMessage(L_USER, "Verifying installation");
		if(!$this->verifyInstallation())
			return "Failed to verify installation";
		
		return null;
	}
	
	private function createSymlinks(array $symlinks) 
	{
		logMessage(L_USER, "Creating system symbolic links");
		
		foreach ($symlinks as $slink) 
		{
			list($target, $link) = explode(SYMLINK_SEPARATOR, AppConfig::replaceTokensInString($slink));
			
			if(!file_exists(dirname($link)))
				mkdir(dirname($link), 0755, true);
				
			if(file_exists($link))
				unlink($link);
					
			if (symlink($target, $link)) 
			{
				logMessage(L_INFO, "Created symbolic link $link -> $target");
			} 
			else 
			{
				logMessage(L_INFO, "Failed to create symbolic link from $link to $target, retyring..");
				unlink($link);
				symlink($target, $link);
			}
		}
		
		AppConfig::updateUninstallerConfig($symlinks);
		
		return true;
	}
	
	private function startServices(array $services) 
	{
		logMessage(L_USER, "Running kaltura services");
		foreach ($services as $service)
		{
			if (!OsUtils::startService($service))
				return "Failed starting service [$service]";
		}
		
		//update uninstaller config
		AppConfig::updateUninstallerServices($services);
		
		return true;
	}
	
	public function installComponentSymlinks($component) 
	{
		if(!isset($this->install_config[$component]))
			return "Component [$component] not found";
		
		if(!isset($this->install_config[$component]['symlinks']))
			return true;
		
		$componentConfig = $this->install_config[$component];
		logMessage(L_USER, "Installing component [$component]");
	
		$createSymlinks = $this->createSymlinks($componentConfig['symlinks']);
		if($createSymlinks !== true)
			return $createSymlinks;
		
		return true;
	}
	
	public function installComponent($component, $db_params) 
	{
		if(!isset($this->install_config[$component]))
			return "Component [$component] not found";
		
		$componentConfig = $this->install_config[$component];
		logMessage(L_USER, "Installing component [$component]");
	
		$includeFile = __DIR__ . "/components/$component.php";
		if(file_exists($includeFile))
		{
			$include = require($includeFile);
			if($include !== true)
				return $include;
		}
		
		return true;
	}
	
	public function installComponentServices($component) 
	{
		if(!isset($this->install_config[$component]))
			return "Component [$component] not found";
		
		if(!isset($this->install_config[$component]['chkconfig']))
			return true;
		
		$componentConfig = $this->install_config[$component];
		logMessage(L_USER, "Installing component [$component] services");
	
		$startServices = $this->startServices($componentConfig['chkconfig']);
		if($startServices !== true)
			return $startServices;
		
		return true;
	}
	
	private function verifyInstallation()
	{
		$dirName = AppConfig::get(AppConfigAttribute::APP_DIR) . '/tests/sanity';
		if(!file_exists($dirName) || !is_dir($dirName))
		{
			logMessage(L_ERROR, "Defaults sanity test files directory [$dirName] is not a valid directory");
			return false;
		}
		$dirName = realpath($dirName);
		
		$configPath = "$dirName/lib/config.ini";
		if(!file_exists($configPath) || !is_file($configPath) || !parse_ini_file($configPath, true))
		{
			logMessage(L_ERROR, "Sanity test configuration file [$configPath] is not a valid ini file");
			return false;
		}
		
		$dir = dir($dirName);
		/* @var $dir Directory */
		
		$fileNames = array();
		$errors = array();
		while (false !== ($fileName = $dir->read())) 
		{
			if(preg_match('/^\d+\.\w+\.php$/', $fileName))
				$fileNames[] = $fileName;
		}
		$dir->close();
		sort($fileNames);
		
		$returnValue = null;
		foreach($fileNames as $fileName)
		{
			$filePath = realpath("$dirName/$fileName");
		
			if (!OsUtils::execute(AppConfig::get(AppConfigAttribute::PHP_BIN) . " $filePath $configPath")) {
				logMessage(L_ERROR, "Verification failed [$filePath]");
				return false;
			}
		}
		
		return true;
	}
	
	// detects if there are databases leftovers
	// can be used both for verification and for dropping the databases
	// $db_params - the database parameters array used for the installation ('db_host', 'db_user', 'db_pass', 'db_port')
	// $should_drop - whether to drop the databases that are found or not (default - false) 
	// returns null if no leftovers are found or a text containing all the leftovers found
	private function detectDatabases(array $databases, $db_params, $should_drop=false) {
		
		$verify = null;
		foreach ($databases as $db) {
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
		$dir = __DIR__ . '/../directoryConstructor';
		return OsUtils::phing($dir, 'Update-Permissions');
	}		
	
	private function installDB()
	{
		logMessage(L_USER, "Creating databases and database users");
		
		$dir = __DIR__ . '/../dbSchema';
		if(!OsUtils::phing($dir))
			return false;
			
		return true;
	}	
	
	private function createInitialContent ()
	{
		logMessage(L_USER, "Creating databases initial content");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertDefaults.php %s/deployment/base/scripts/init_data", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			logMessage(L_INFO, "Default content inserted");
		} else {
			logMessage(L_ERROR, "Failed to insert default content");
			return false;
		}
			
		logMessage(L_USER, "Creating databases initial permissions");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertPermissions.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			logMessage(L_INFO, "Default permissions inserted");
		} else {
			logMessage(L_ERROR, "Failed to insert permissions");
			return false;
		}
		
		return true;
	}	
	
	private function createTemplateContent ()
	{
		logMessage(L_USER, "Creating partner template content");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertContent.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			logMessage(L_INFO, "Default content inserted");
		} else {
			logMessage(L_ERROR, "Failed to insert content");
			return false;
		}
		
		return true;
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
