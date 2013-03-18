<?php

define("SYMLINK_SEPARATOR", "^"); // this is the separator between the two parts of the symbolic link definition

/*
* This class handles the installation itself. It has functions for installing and for cleaning up.
*/
class Installer
{
	const BASE_COMPONENT = 'base';

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
	private $components = array(Installer::BASE_COMPONENT);

	/**
	 * Crteate a new installer, loads installation configurations from installation configuration file
	 * @param array|string $components
	 */
	public function __construct()
	{
		$this->installConfig = parse_ini_file(__DIR__ . '/installation.ini', true);

		$components = AppConfig::getCurrentMachineComponents();
		foreach($components as $component)
		{
			if(isset($this->installConfig[$component]))
			{
				$this->components[] = $component;
			}
			elseif ($component == '*')
			{
				foreach($this->installConfig as $component => $config)
					if(isset($config['install_by_default']) && $config['install_by_default'])
						$this->components[] = $component;
			}
		}
		$this->components = array_unique($this->components);
	}

	public function __destruct()
	{
		if($this->uninstallConfig)
			fclose($this->uninstallConfig);
	}

	private function createOpertingSystemUsers()
	{
		Logger::logMessage(Logger::LEVEL_USER, "Creating operting system users");
		$dir = __DIR__ . '/../directoryConstructor';
		return OsUtils::phing($dir, 'Create-Users');
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

		Logger::logMessage(Logger::LEVEL_USER, "Removing symbolic links");
		foreach($this->installConfig as $component => $config)
		{
			if(!isset($config['symlinks']) || !is_array($config['symlinks']))
				continue;

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
						Logger::logMessage(Logger::LEVEL_INFO, "Removing symbolic link $link");
						OsUtils::recursiveDelete($link);
					}
				}
			}
		}

		if(in_array('db', $this->components))
		{
			foreach($this->installConfig as $component => $config)
			{
				if(!isset($config['databases']) || !is_array($config['databases']))
					continue;
	
				$verify = $this->detectDatabases($config['databases']);
				if (!isset($verify))
					continue;
	
				if(!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
					continue;
	
				if ($report_only)
				{
					$leftovers .= $verify;
				}
				else {
					$this->detectDatabases($config['databases'], true);
				}
			}
		}

		if(!$report_only)
		{
			foreach($this->installConfig as $component => $config)
			{
				if(isset($config['chkconfig']) && is_array($config['chkconfig']))
					foreach ($config['chkconfig'] as $service)
						OsUtils::stopService($service);
			}
		}

		if (is_dir(AppConfig::get(AppConfigAttribute::BASE_DIR)) && (($files = @scandir(AppConfig::get(AppConfigAttribute::BASE_DIR))) && count($files) > 2))
		{
			if ($report_only)
			{
				$leftovers .= "   Target directory ".AppConfig::get(AppConfigAttribute::BASE_DIR)." already exists".PHP_EOL;
			}
			elseif(AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
			{
				Logger::logMessage(Logger::LEVEL_USER, "Deleting ".AppConfig::get(AppConfigAttribute::BASE_DIR));
				OsUtils::recursiveDelete(AppConfig::get(AppConfigAttribute::BASE_DIR));
			}
			else
			{
				Logger::logMessage(Logger::LEVEL_USER, "Deleting ".AppConfig::get(AppConfigAttribute::BASE_DIR) . " excluding web content");
				OsUtils::recursiveDelete(AppConfig::get(AppConfigAttribute::BASE_DIR), 'web/content');
			}
		}

		return $leftovers;
	}

	private function stopApache()
	{
		if(!in_array('api', $this->components) && !in_array('apps', $this->components) && !in_array('admin', $this->components) && !in_array('var', $this->components))
			return true;
		
		Logger::logMessage(Logger::LEVEL_USER, "Stopping apache http server");
		return OsUtils::execute("service " . AppConfig::get(AppConfigAttribute::APACHE_SERVICE) . " stop");
	}

	private function restartApache($now = false)
	{
		if(!in_array('api', $this->components) && !in_array('apps', $this->components) && !in_array('admin', $this->components) && !in_array('var', $this->components))
			return true;
	
		if($now)
		{
			if(in_array('generateClients', $this->runOnce))
			{
				Logger::logMessage(Logger::LEVEL_USER, "Restarting apache http server");
				return OsUtils::execute("service " . AppConfig::get(AppConfigAttribute::APACHE_SERVICE) . " restart");
			}
			return true;
		}

		$this->runOnce[] = 'restartApache';
		return true;
	}

	private function generateClients($now = false)
	{
		if(!in_array('api', $this->components) && !in_array('batch', $this->components) && !in_array('admin', $this->components) && !in_array('var', $this->components))
			return true;
		
		if($now)
		{
			if(in_array('generateClients', $this->runOnce))
			{
				Logger::logMessage(Logger::LEVEL_USER, "Generating client libraries");
				return OsUtils::execute(sprintf("%s/generator/generate.sh", AppConfig::get(AppConfigAttribute::APP_DIR)));
			}
			return true;
		}

		$this->runOnce[] = 'generateClients';
		return true;
	}

	/**
	 * Installs the application according to the given parameters\
	 * @return string|NULL null if the installation succeeded or an error text if it failed
	 */
	public function install($packageDir = null, $dontValidate = false)
	{
		AppConfig::set(AppConfigAttribute::KMC_VERSION, AppConfig::getServerConfig('kmc_version'));
		AppConfig::set(AppConfigAttribute::CLIPAPP_VERSION, AppConfig::getServerConfig('clipapp_version'));
		AppConfig::set(AppConfigAttribute::HTML5_VERSION, AppConfig::getServerConfig('html5_version'));

		$this->stopApache();

		$this->createOpertingSystemUsers();

		$this->saveUninstallerConfig();

		Logger::logMessage(Logger::LEVEL_INFO, sprintf("Current working dir is %s", getcwd()));
		if ($packageDir)
		{
			Logger::logMessage(Logger::LEVEL_USER, sprintf("Copying application files to %s", AppConfig::get(AppConfigAttribute::BASE_DIR)));

			if (!OsUtils::rsync("$packageDir/", AppConfig::get(AppConfigAttribute::BASE_DIR), "--exclude web/content"))
				return "Failed to copy application files to target directory";

			$copyWebContnet = false;
			if(AppConfig::get(AppConfigAttribute::MULTIPLE_SERVER_ENVIRONMENT))
			{
				if(in_array('db', $this->components))
				{
					$config = AppConfig::getCurrentMachineConfig();
					if($config && isset($config[AppConfigAttribute::DB1_CREATE_NEW_DB]) && $config[AppConfigAttribute::DB1_CREATE_NEW_DB])
						$copyWebContnet = true;
				}
			}
			elseif(AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
			{
				$copyWebContnet = true;
			}

			if ($copyWebContnet)
			{
				Logger::logMessage(Logger::LEVEL_USER, sprintf("Copying web content files to %s", AppConfig::get(AppConfigAttribute::WEB_DIR)));
				if (!OsUtils::rsync("$packageDir/web/content", AppConfig::get(AppConfigAttribute::WEB_DIR)))
					return "Failed to copy default content into ". AppConfig::get(AppConfigAttribute::WEB_DIR);
			}
		}

		Logger::logMessage(Logger::LEVEL_USER, "Creating the uninstaller");
		if (!OsUtils::fullCopy( __DIR__ . '/uninstall.php', AppConfig::get(AppConfigAttribute::BASE_DIR)."/uninstaller/")) {
			return "Failed to create the uninstaller";
		}

		Logger::logMessage(Logger::LEVEL_USER, "Replacing configuration tokens in files");
		if(isset($this->installConfig[Installer::BASE_COMPONENT]['token_files']) && is_array($this->installConfig[Installer::BASE_COMPONENT]['token_files']))
		{
			foreach ($this->installConfig[Installer::BASE_COMPONENT]['token_files'] as $tokenFile)
			{
				$files = glob(AppConfig::replaceTokensInString($tokenFile));
				foreach($files as $file)
				{
					if (!AppConfig::replaceTokensInFile($file))
						return "Failed to replace tokens in $file";
				}
			}
		}

		Logger::logMessage(Logger::LEVEL_USER, "Creating symbolic links");
		foreach($this->components as $component)
			$this->installComponentSymlinks($component);

		foreach($this->components as $component)
			$this->installComponent($component);

		if(!$this->createDynamicEnums())
			return "Failed to create plugins dynamic enumerations";

		if(!$this->createInitialContent())
			return "Failed to create initial content";

		if(!$this->createQueryCacheTriggers())
			return "Failed to create query cache triggers";

		if(!$this->createDeployKMC())
			return "Failed to deploy KMC";

		if(!$this->generateClients(true))
			return "Failed generating client libraries";

		if(!$this->changeDirsAndFilesPermissions())
			return "Failed to set files permissions";

		if(!$this->restartApache(true))
			return "Failed restarting apache http server";

		Logger::logMessage(Logger::LEVEL_USER, "Starting services");
		foreach($this->components as $component)
			$this->installComponentServices($component);

		if(!$this->createTemplateContent())
			return "Failed to create template content";

		if($packageDir)
			OsUtils::execute("cp $packageDir/version.ini " . AppConfig::get(AppConfigAttribute::APP_DIR) . '/configurations/');

		if(!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
			$dontValidate = true;

		if($dontValidate)
		{
			Logger::logMessage(Logger::LEVEL_USER, "Skipping installation verification");
		}
		else
		{
			$this->verifyInstallation();
		}

		$this->done();

		return null;
	}

	private function createSymlinks(array $symlinks)
	{
		foreach ($symlinks as $slink)
		{
			list($target, $link) = explode(SYMLINK_SEPARATOR, AppConfig::replaceTokensInString($slink), 2);

			if(!file_exists($target))
			{
				Logger::logColorMessage(Logger::COLOR_RED, Logger::LEVEL_USER, "Failed to create symbolic link [$link], target [$target] does not exist.");
				continue;
			}

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

			clearstatcache();
			if(file_exists($link))
				chgrp($link, AppConfig::get(AppConfigAttribute::OS_KALTURA_GROUP));

			fwrite($this->uninstallConfig, "symlinks[]=$link" . PHP_EOL);
		}

		return true;
	}

	private function startServices(array $services)
	{
		foreach ($services as $service)
		{
			if (!OsUtils::startService($service))
				return "Failed starting service [$service]";

			fwrite($this->uninstallConfig, "chkconfig[]=$service" . PHP_EOL);
		}

		return true;
	}

	private function installComponentSymlinks($component)
	{
		if(!isset($this->installConfig[$component]))
			return "Component [$component] not found";

		if(!isset($this->installConfig[$component]['symlinks']))
			return true;

		$componentConfig = $this->installConfig[$component];
		Logger::logMessage(Logger::LEVEL_INFO, "Installing component [$component] symbolic links");

		$createSymlinks = $this->createSymlinks($componentConfig['symlinks']);
		if($createSymlinks !== true)
			return $createSymlinks;

		return true;
	}

	private function installComponent($component)
	{
		if(!isset($this->installConfig[$component]))
			return "Component [$component] not found";

		$componentConfig = $this->installConfig[$component];
		Logger::logMessage(Logger::LEVEL_USER, "Installing " . $componentConfig['title'] . " component");

		$includeFile = __DIR__ . "/components/$component.php";
		if(file_exists($includeFile))
		{
			$include = require($includeFile);
			if($include !== true)
				return $include;
		}

		return true;
	}

	private function installComponentServices($component)
	{
		if(!isset($this->installConfig[$component]))
			return "Component [$component] not found";

		if(!isset($this->installConfig[$component]['chkconfig']))
			return true;

		$componentConfig = $this->installConfig[$component];
		Logger::logMessage(Logger::LEVEL_INFO, "Installing component [$component] services");

		$startServices = $this->startServices($componentConfig['chkconfig']);
		if($startServices !== true)
			return $startServices;

		return true;
	}

	private function done()
	{
		echo PHP_EOL;
		Logger::logColorMessage(Logger::COLOR_LIGHT_GREEN, Logger::LEVEL_USER, "Installation Completed Successfully.");
		
		if(AppConfig::get(AppConfigAttribute::MULTIPLE_SERVER_ENVIRONMENT))
		{
			$config = AppConfig::getCurrentMachineConfig();
			if(!$config || !isset($config[AppConfigAttribute::VERIFY_INSTALLATION]) || !$config[AppConfigAttribute::VERIFY_INSTALLATION])
			{
				return true;
			}
		}
		elseif(!AppConfig::get(AppConfigAttribute::VERIFY_INSTALLATION))
			return true;

		// send settings mail if possible
		$virtualHostName = AppConfig::get(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME);
		$url = AppConfig::get(AppConfigAttribute::ENVIRONMENT_PROTOCOL) . '://' . AppConfig::get(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME);
		$versionType = AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE);
		$adminMail = AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL);
		$adminPassword = AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD);

		$msg = "Thank you for installing the Kaltura Video Platform\n\n";
		$msg .= "To get started, please browse to your kaltura start page at:\n";
		$msg .= "$url/start\n\n";
		$msg .= "Your $versionType administration console can be accessed at:\n";
		$msg .= "$url/admin_console\n\n";
		$msg .= "Your Admin Console credentials are:\n";
		$msg .= "System admin user: $adminMail\n";
		$msg .= "System admin password: $adminPassword\n\n";
		$msg .= "Please keep this information for future use.\n\n";
		$msg .= "Thank you for choosing Kaltura!";

		$mailer = new PHPMailer();
		$mailer->CharSet = 'utf-8';
		$mailer->IsHTML(false);
		$mailer->AddAddress($adminMail);
		$mailer->Sender = "installation_confirmation@$virtualHostName";
		$mailer->From = "installation_confirmation@$virtualHostName";
		$mailer->FromName = AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME);
		$mailer->Subject = 'Kaltura Installation Settings';
		$mailer->Body = $msg;

		if ($mailer->Send()) {
			Logger::logColorMessage(Logger::COLOR_LIGHT_GREEN, Logger::LEVEL_USER, "Sent post installation settings email to ".AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL));
		} else {
			Logger::logColorMessage(Logger::COLOR_LIGHT_RED, Logger::LEVEL_USER, "Post installation email cannot be sent");
		}

		if(in_array('admin', $this->components))
		{
			Logger::logMessage(Logger::LEVEL_USER,
				"Your Kaltura Admin Console credentials:\n" .
				"System Admin user: $adminMail\n" .
				"Please keep this information for future use."
			);
			echo PHP_EOL;
		}

		if(in_array('api', $this->components))
		{
			Logger::logMessage(Logger::LEVEL_USER,
				"To start using Kaltura, please complete the following steps:\n" .
				"1. Add the following line to your /etc/hosts file:\n" .
					"\t127.0.0.1 $virtualHostName\n" .
				"2. Browse to your Kaltura start page at: $url/start\n"
			);
		}
	}

	private function verifyInstallation()
	{
		if(!in_array('api', $this->components))
			return true;

		if(AppConfig::get(AppConfigAttribute::MULTIPLE_SERVER_ENVIRONMENT))
		{
			$config = AppConfig::getCurrentMachineConfig();
			if($config && isset($config[AppConfigAttribute::VERIFY_INSTALLATION]) && !$config[AppConfigAttribute::VERIFY_INSTALLATION])
				return true;
		}
		elseif(!AppConfig::get(AppConfigAttribute::VERIFY_INSTALLATION))
			return true;

		Logger::logMessage(Logger::LEVEL_USER, "Verifying installation");
			
		$dirName = AppConfig::get(AppConfigAttribute::APP_DIR) . '/tests/sanity';
		if(!file_exists($dirName) || !is_dir($dirName))
		{
			Logger::logColorMessage(Logger::COLOR_RED, Logger::LEVEL_USER, "Defaults sanity test files directory [$dirName] is not a valid directory");
			return false;
		}
		$dirName = realpath($dirName);

		$configPath = "$dirName/lib/config.ini";
		if(!file_exists($configPath) || !is_file($configPath) || !parse_ini_file($configPath, true))
		{
			Logger::logColorMessage(Logger::COLOR_RED, Logger::LEVEL_USER, "Sanity test configuration file [$configPath] is not a valid ini file");
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
				Logger::logColorMessage(Logger::COLOR_RED, Logger::LEVEL_USER, "Verification failed [$filePath]");
				return false;
			}
		}

		return true;
	}

	// detects if there are databases leftovers
	// can be used both for verification and for dropping the databases
	// $should_drop - whether to drop the databases that are found or not (default - false)
	// returns null if no leftovers are found or a text containing all the leftovers found
	private function detectDatabases(array $databases, $should_drop=false)
	{
		$hosts = array(
			AppConfigAttribute::DB1_HOST => AppConfigAttribute::DB1_PORT,
			AppConfigAttribute::DB2_HOST => AppConfigAttribute::DB2_PORT,
			AppConfigAttribute::DB3_HOST => AppConfigAttribute::DB3_PORT,
			AppConfigAttribute::DWH_HOST => AppConfigAttribute::DWH_PORT,
			AppConfigAttribute::SPHINX_DB_HOST => AppConfigAttribute::SPHINX_DB_PORT,
		);

		$verify = null;
		$checkedDatabases = array();
		foreach ($hosts as $hostAttribute => $portAttribute)
		{
			$host = AppConfig::get($hostAttribute);
			$port = AppConfig::get($portAttribute);

			foreach ($databases as $db)
			{
				if(isset($checkedDatabases["$host:$port:$db"]))
					continue;

				$checkedDatabases["$host:$port:$db"] = true;

				$result = DatabaseUtils::dbExists($host, $port, $db);

				if ($result === -1)
				{
					$verify .= "   Cannot verify if '$db' database exists on host '$host' with port $port".PHP_EOL;
				}
				else if ($result === true)
				{
					if (!$should_drop)
					{
						$verify .= "   '$db' database already exists on host '$host' with port $port".PHP_EOL;
					}
					else
					{
						Logger::logMessage(Logger::LEVEL_USER, "Dropping '$db' database on host '$host' with port $port...", false);
						if(DatabaseUtils::dropDb($host, $port, $db))
							Logger::logColorMessage(Logger::COLOR_GREEN, Logger::LEVEL_USER, " - done.", true, 3);
						else
							Logger::logColorMessage(Logger::COLOR_RED, Logger::LEVEL_USER, " - failed.", true, 3);
					}
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
		if(!AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
			return true;

		Logger::logMessage(Logger::LEVEL_INFO, "Creating databases and database users");

		$dir = __DIR__ . '/../dbSchema';
		if(!OsUtils::phing($dir))
			return false;

		return true;
	}

	private function createDynamicEnums()
	{
		if(!in_array('api', $this->components))
			return true;

		if(AppConfig::get(AppConfigAttribute::MULTIPLE_SERVER_ENVIRONMENT))
		{
			$config = AppConfig::getCurrentMachineConfig();
			if($config && isset($config[AppConfigAttribute::VERIFY_INSTALLATION]) && !$config[AppConfigAttribute::VERIFY_INSTALLATION])
				return true;
		}
		elseif(!AppConfig::get(AppConfigAttribute::VERIFY_INSTALLATION))
			return true;
			
		Logger::logMessage(Logger::LEVEL_USER, "Creating plugins dynamic enumerations");
		return OsUtils::execute(sprintf("%s %s/deployment/base/scripts/installPlugins.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)));
	}

	private function createQueryCacheTriggers()
	{
		Logger::logMessage(Logger::LEVEL_USER, "Creating query cache triggers");
		return OsUtils::execute(sprintf("%s %s/deployment/base/scripts/createQueryCacheTriggers.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)));
	}

	private function createDeployKMC()
	{
		if(!in_array('api', $this->components))
			return true;

		Logger::logMessage(Logger::LEVEL_USER, "Deploying user interface configuration files (KMC ui-confs)");
		if(isset($this->installConfig[Installer::BASE_COMPONENT]['uiconfs_2']) && is_array($this->installConfig[Installer::BASE_COMPONENT]['uiconfs_2']))
		{
			foreach($this->installConfig[Installer::BASE_COMPONENT]['uiconfs_2'] as $uiconfapp)
			{
				$to_deploy = AppConfig::replaceTokensInString($uiconfapp);
				if(OsUtils::execute(sprintf("%s %s/deployment/uiconf/deploy_v2.php --ini=%s", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR), $to_deploy)))
				{
					Logger::logMessage(Logger::LEVEL_INFO, "Deployed user interface configuration files $to_deploy");
				}
				else
				{
					return false;
				}
			}
		}
		return true;
	}

	private function createInitialContent ()
	{
		if(!in_array('api', $this->components))
			return true;

		if(AppConfig::get(AppConfigAttribute::MULTIPLE_SERVER_ENVIRONMENT))
		{
			$config = AppConfig::getCurrentMachineConfig();
			if($config && isset($config[AppConfigAttribute::VERIFY_INSTALLATION]) && !$config[AppConfigAttribute::VERIFY_INSTALLATION])
				return true;
		}
		elseif(!AppConfig::get(AppConfigAttribute::VERIFY_INSTALLATION))
			return true;
			
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
		if(!in_array('api', $this->components))
			return true;

		if(AppConfig::get(AppConfigAttribute::MULTIPLE_SERVER_ENVIRONMENT))
		{
			$config = AppConfig::getCurrentMachineConfig();
			if($config && isset($config[AppConfigAttribute::VERIFY_INSTALLATION]) && !$config[AppConfigAttribute::VERIFY_INSTALLATION])
				return true;
		}
		elseif(!AppConfig::get(AppConfigAttribute::VERIFY_INSTALLATION))
			return true;
			
		Logger::logMessage(Logger::LEVEL_USER, "Creating partner template content");
		if (OsUtils::execute(sprintf("%s %s/deployment/base/scripts/insertContent.php", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::APP_DIR)))) {
			Logger::logMessage(Logger::LEVEL_INFO, "Default content inserted");
		} else {
			Logger::logMessage(Logger::LEVEL_ERROR, "Failed to insert content");
			return false;
		}

		return true;
	}
}
