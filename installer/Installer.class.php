<?php

define("SYMLINK_SEPARATOR", "^"); // this is the separator between the two parts of the symbolic link definition

/*
* This class handles the installation itself. It has functions for installing and for cleaning up.
*/
class Installer
{
	/**
	 * @var resource
	 */
	private $uninstallConfig;

	/**
	 * @var array
	 */
	private $installConfig;

	/**
	 * Array of tasks that should be done once during the installation, for different components
	 * @var array
	 */
	private $runOnce = array();

	/**
	 * Array of the components that should be installed
	 * @var array
	 */
	private $components = array('all');

	/**
	 * Crteate a new installer, loads installation configurations from installation configuration file
	 * @param array|string $components
	 */
	public function __construct($components = '*')
	{
		$this->installConfig = parse_ini_file(__DIR__ . '/installation.ini', true);

		if($components && is_array($components))
		{
			foreach($components as $component)
				if(isset($this->installConfig[$component]))
					$this->components[] = $component;
		}
		elseif($components == '*')
		{
			foreach($this->installConfig as $component => $config)
				if($component != 'all' && $config['install_by_default'])
					$this->components[] = $component;
		}
	}

	public function __destruct()
	{
		if($this->uninstallConfig)
			fclose($this->uninstallConfig);
	}

	/**
	 * Saves the uninstaller config file, the values saved are the minimal values subset needed for the uninstaller to run
	 */
	public function saveUninstallerConfig()
	{
		$uninstallerDir = AppConfig::get(AppConfigAttribute::BASE_DIR) . '/uninstaller';
		if(!file_exists($uninstallerDir))
			mkdir($uninstallerDir, 0750, true);

		$this->uninstallConfig = fopen("$uninstallerDir/uninstall.ini", 'w');

		fwrite($this->uninstallConfig, "BASE_DIR=" . AppConfig::get(AppConfigAttribute::BASE_DIR) . PHP_EOL);
		fwrite($this->uninstallConfig, "DB_HOST=" . AppConfig::get(AppConfigAttribute::DB1_HOST) . PHP_EOL);
		fwrite($this->uninstallConfig, "DB_USER=" . AppConfig::get(AppConfigAttribute::DB1_USER) . PHP_EOL);
		fwrite($this->uninstallConfig, "DB_PASS=" . AppConfig::get(AppConfigAttribute::DB1_PASS) . PHP_EOL);
		fwrite($this->uninstallConfig, "DB_PORT=" . AppConfig::get(AppConfigAttribute::DB1_PORT) . PHP_EOL);
		fwrite($this->uninstallConfig, PHP_EOL);
	}

	// detects if there are leftovers of an installation
	// can be used both before installation to verify and when the installation failed for cleaning up
	// $report_only - if set to true only returns the leftovers found and does not removes them
	// returns null if no leftovers are found or it is not report only or a text containing all the leftovers found
	public function detectLeftovers($report_only) {
		$leftovers = null;

		// symbloic links leftovers
		foreach($this->installConfig as $component => $config)
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
							Logger::logMessage(Logger::LEVEL_USER, "Removing symbolic link $link");
							OsUtils::recursiveDelete($link);
						}
					}
				}
			}

			if(isset($config['databases']) && is_array($config['databases']))
			{
				// database leftovers
				$verify = $this->detectDatabases($config['databases']);
				if (isset($verify)) {
					if(!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
					{
						//do nothing
					}
					else if ($report_only) {
						$leftovers .= $verify;
					}
					else {
						$this->detectDatabases($config['databases'], true);
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

					Logger::logMessage(Logger::LEVEL_USER, "Deleting ".AppConfig::get(AppConfigAttribute::BASE_DIR));
					OsUtils::recursiveDelete(AppConfig::get(AppConfigAttribute::BASE_DIR));
				}
			}
		}

		return $leftovers;
	}

	private function restartApache()
	{
		$this->runOnce[] = 'restartApache';
	}

	private function generateClients()
	{
		$this->runOnce[] = 'generateClients';
	}

	/**
	 * Installs the application according to the given parameters\
	 * @return string|NULL null if the installation succeeded or an error text if it failed
	 */
	public function install($packageDir = null)
	{
		AppConfig::set(AppConfigAttribute::KMC_VERSION, AppConfig::getServerConfig('kmc_version'));
		AppConfig::set(AppConfigAttribute::CLIPAPP_VERSION, AppConfig::getServerConfig('clipapp_version'));
		AppConfig::set(AppConfigAttribute::HTML5_VERSION, AppConfig::getServerConfig('html5_version'));

		$this->saveUninstallerConfig();

		Logger::logMessage(Logger::LEVEL_USER, sprintf("Current working dir is %s", getcwd()));
		Logger::logMessage(Logger::LEVEL_USER, sprintf("Copying application files to %s", AppConfig::get(AppConfigAttribute::BASE_DIR)));
		if ($packageDir && !OsUtils::rsync("$packageDir/", AppConfig::get(AppConfigAttribute::BASE_DIR), "--exclude web/content"))
			return "Failed to copy application files to target directory";

		if ($packageDir && AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
		{
			Logger::logMessage(Logger::LEVEL_USER, sprintf("Copying web content files to %s", AppConfig::get(AppConfigAttribute::WEB_DIR)));
			if (!OsUtils::rsync("$packageDir/web/content", AppConfig::get(AppConfigAttribute::WEB_DIR)))
				return "Failed to copy default content into ". AppConfig::get(AppConfigAttribute::WEB_DIR);
		}

		Logger::logMessage(Logger::LEVEL_USER, "Creating the uninstaller");
		if (!OsUtils::fullCopy('installer/uninstall.php', AppConfig::get(AppConfigAttribute::BASE_DIR)."/uninstaller/")) {
			return "Failed to create the uninstaller";
		}

		Logger::logMessage(Logger::LEVEL_USER, "Replacing configuration tokens in files");
		if(isset($this->installConfig['all']['token_files']) && is_array($this->installConfig['all']['token_files']))
		{
			foreach ($this->installConfig['all']['token_files'] as $tokenFile)
			{
				$files = glob(AppConfig::replaceTokensInString($tokenFile));
				foreach($files as $file)
				{
					if (!AppConfig::replaceTokensInFile($file))
						return "Failed to replace tokens in $file";
				}
			}
		}

		if((!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB)) && (DatabaseUtils::dbExists(AppConfig::get(AppConfigAttribute::DWH_DATABASE_NAME)) === true))
		{
			Logger::logMessage(Logger::LEVEL_USER, sprintf("Skipping '%s' database creation", AppConfig::get(AppConfigAttribute::DWH_DATABASE_NAME)));
		}
		else
		{
			Logger::logMessage(Logger::LEVEL_USER, "Creating data warehouse");
			if (!OsUtils::execute(sprintf("%s/setup/dwh_setup.sh -h %s -P %s -u %s -p %s -d %s ", AppConfig::get(AppConfigAttribute::DWH_DIR), AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB1_PORT), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DB_ROOT_PASS), AppConfig::get(AppConfigAttribute::DWH_DIR)))) {
				return "Failed running data warehouse initialization script";
			}
		}

		foreach($this->components as $component)
			$this->installComponentSymlinks($component);

		if (AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE) == AppConfig::K_CE_TYPE) {
			AppConfig::simMafteach();
		}

		foreach($this->components as $component)
			$this->installComponent($component);

		Logger::logMessage(Logger::LEVEL_USER, "Creating Dynamic Enums");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/installPlugins.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				Logger::logMessage(Logger::LEVEL_INFO, "Dynamic Enums created");
		} else {
			return "Failed to create dynamic enums";
		}

		if(!$this->createInitialContent())
			return "Failed to create initial content";

		Logger::logMessage(Logger::LEVEL_USER, "Create query cache triggers");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/createQueryCacheTriggers.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "sphinx Query Cache Triggers created");
		} else {
			return "Failed to create QueryCacheTriggers";
		}

		Logger::logMessage(Logger::LEVEL_USER, "Populate sphinx tables");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxEntries.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				Logger::logMessage(Logger::LEVEL_INFO, "sphinx entries log created");
		} else {
			return "Failed to populate sphinx log from entries";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxEntryDistributions.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				Logger::logMessage(Logger::LEVEL_INFO, "sphinx content distribution log created");
		} else {
			return "Failed to populate sphinx log from content distribution";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxCuePoints.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				Logger::logMessage(Logger::LEVEL_INFO, "sphinx cue points log created");
		} else {
			return "Failed to populate sphinx log from cue points";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxKusers.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "sphinx Kusers log created");
		} else {
			return "Failed to populate sphinx log from Kusers";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxTags.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "sphinx tags log created");
		} else {
			return "Failed to populate sphinx log from tags";
		}
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/populateSphinxCategories.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "sphinx Categoriess log created");
		} else {
			return "Failed to populate sphinx log from categories";
		}

		Logger::logMessage(Logger::LEVEL_USER, "Deploying uiconfs in order to configure the application");
		if(isset($this->installConfig['all']['uiconfs_2']) && is_array($this->installConfig['all']['uiconfs_2']))
		{
			foreach($this->installConfig['all']['uiconfs_2'] as $uiconfapp)
			{
				$to_deploy = AppConfig::replaceTokensInString($uiconfapp);
				if(OsUtils::execute(sprintf("%s %s/deployment/uiconf/deploy_v2.php --ini=%s", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR), $to_deploy)))
				{
					Logger::logMessage(Logger::LEVEL_INFO, "Deployed uiconf $to_deploy");
				}
				else
				{
					return "Failed to deploy uiconf $to_deploy";
				}
			}
		}

		if(in_array('generateClients', $this->runOnce))
		{
			Logger::logMessage(Logger::LEVEL_USER, "Generating client libraries");
			if (!OsUtils::execute(sprintf("%s/generator/generate.sh", AppConfig::get(AppConfigAttribute::APP_DIR)))) {
				return "Failed generating client libraries";
			}
		}

		if(!$this->changeDirsAndFilesPermissions())
			return "Failed to set files permissions";

		if(in_array('restartApache', $this->runOnce))
		{
			Logger::logMessage(Logger::LEVEL_USER, "Restarting apache http server");
			if (!OsUtils::execute(AppConfig::get(AppConfigAttribute::APACHE_RESTART_COMMAND))) {
				return "Failed restarting apache http server";
			}
		}

		foreach($this->components as $component)
			$this->installComponentServices($component);

		if(!$this->createTemplateContent())
			return "Failed to create template content";

		if($packageDir)
			OsUtils::execute("cp $packageDir/version.ini " . AppConfig::get(AppConfigAttribute::APP_DIR) . '/configurations/');

		Logger::logMessage(Logger::LEVEL_USER, "Verifying installation");
		if(!$this->verifyInstallation())
			return "Failed to verify installation";

		return null;
	}

	private function createSymlinks(array $symlinks)
	{
		Logger::logMessage(Logger::LEVEL_USER, "Creating system symbolic links");

		foreach ($symlinks as $slink)
		{
			list($target, $link) = explode(SYMLINK_SEPARATOR, AppConfig::replaceTokensInString($slink));

			if(!file_exists(dirname($link)))
				mkdir(dirname($link), 0755, true);

			if(file_exists($link))
				unlink($link);

			if (symlink($target, $link))
			{
				Logger::logMessage(Logger::LEVEL_INFO, "Created symbolic link $link -> $target");
			}
			else
			{
				Logger::logMessage(Logger::LEVEL_INFO, "Failed to create symbolic link from $link to $target, retyring..");
				unlink($link);
				symlink($target, $link);
			}

			fwrite($this->uninstallConfig, "symlinks[]=$link" . PHP_EOL);
		}

		return true;
	}

	private function startServices(array $services)
	{
		Logger::logMessage(Logger::LEVEL_USER, "Running kaltura services");
		foreach ($services as $service)
		{
			if (!OsUtils::startService($service))
				return "Failed starting service [$service]";

			fwrite($this->uninstallConfig, "chkconfig[]=$service" . PHP_EOL);
		}

		return true;
	}

	public function installComponentSymlinks($component)
	{
		if(!isset($this->installConfig[$component]))
			return "Component [$component] not found";

		if(!isset($this->installConfig[$component]['symlinks']))
			return true;

		$componentConfig = $this->installConfig[$component];
		Logger::logMessage(Logger::LEVEL_USER, "Installing component [$component]");

		$createSymlinks = $this->createSymlinks($componentConfig['symlinks']);
		if($createSymlinks !== true)
			return $createSymlinks;

		return true;
	}

	public function installComponent($component)
	{
		if(!isset($this->installConfig[$component]))
			return "Component [$component] not found";

		$componentConfig = $this->installConfig[$component];
		Logger::logMessage(Logger::LEVEL_USER, "Installing component [$component]");

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
		if(!isset($this->installConfig[$component]))
			return "Component [$component] not found";

		if(!isset($this->installConfig[$component]['chkconfig']))
			return true;

		$componentConfig = $this->installConfig[$component];
		Logger::logMessage(Logger::LEVEL_USER, "Installing component [$component] services");

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
			Logger::logMessage(Logger::LEVEL_ERROR, "Defaults sanity test files directory [$dirName] is not a valid directory");
			return false;
		}
		$dirName = realpath($dirName);

		$configPath = "$dirName/lib/config.ini";
		if(!file_exists($configPath) || !is_file($configPath) || !parse_ini_file($configPath, true))
		{
			Logger::logMessage(Logger::LEVEL_ERROR, "Sanity test configuration file [$configPath] is not a valid ini file");
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
				Logger::logMessage(Logger::LEVEL_ERROR, "Verification failed [$filePath]");
				return false;
			}
		}

		return true;
	}

	// detects if there are databases leftovers
	// can be used both for verification and for dropping the databases
	// $should_drop - whether to drop the databases that are found or not (default - false)
	// returns null if no leftovers are found or a text containing all the leftovers found
	private function detectDatabases(array $databases, $should_drop=false) {

		$verify = null;
		foreach ($databases as $db) {
			$result = DatabaseUtils::dbExists($db);

			if ($result === -1) {
				$verify .= "   Cannot verify if '$db' database exists".PHP_EOL;
			} else if ($result === true) {
				if (!$should_drop) {
					$verify .= "   '$db' database already exists ".PHP_EOL;
				} else {
					Logger::logMessage(Logger::LEVEL_USER, "Dropping '$db' database");
					DatabaseUtils::dropDb($db);
				}
			}
		}
		return $verify;
	}

	private function changeDirsAndFilesPermissions()
	{
		Logger::logMessage(Logger::LEVEL_USER, "Changing permissions of directories and files");
		$dir = __DIR__ . '/../directoryConstructor';
		return OsUtils::phing($dir, 'Update-Permissions');
	}

	private function installDB()
	{
		Logger::logMessage(Logger::LEVEL_USER, "Creating databases and database users");

		$dir = __DIR__ . '/../dbSchema';
		if(!OsUtils::phing($dir))
			return false;

		return true;
	}

	private function createInitialContent ()
	{
		Logger::logMessage(Logger::LEVEL_USER, "Creating databases initial content");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertDefaults.php %s/deployment/base/scripts/init_data", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "Default content inserted");
		} else {
			Logger::logMessage(Logger::LEVEL_ERROR, "Failed to insert default content");
			return false;
		}

		Logger::logMessage(Logger::LEVEL_USER, "Creating databases initial permissions");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertPermissions.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "Default permissions inserted");
		} else {
			Logger::logMessage(Logger::LEVEL_ERROR, "Failed to insert permissions");
			return false;
		}

		return true;
	}

	private function createTemplateContent ()
	{
		Logger::logMessage(Logger::LEVEL_USER, "Creating partner template content");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertContent.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "Default content inserted");
		} else {
			Logger::logMessage(Logger::LEVEL_ERROR, "Failed to insert content");
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
