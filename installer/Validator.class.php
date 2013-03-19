<?php

include_once (__DIR__ . '/OsUtils.class.php');
include_once (__DIR__ . '/DatabaseUtils.class.php');

class Validator
{
	/**
	 * Configuration
	 * @var array
	 */
	private $installConfig;

	/**
	 * Array of the components that should be installed
	 * @var array
	 */
	private $components = array(Installer::BASE_COMPONENT);

	/**
	 * Enter description here ...
	 * @var array
	 */
	private $prerequisites = array();

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

	private function validatePHP()
	{
		// check php version
		if(! $this->checkVersion(phpversion(), $this->installConfig[Installer::BASE_COMPONENT]["php_min_version"]))
			$this->prerequisites[] = "PHP version should be >= " . $this->installConfig[Installer::BASE_COMPONENT]["php_min_version"] . " (current version is " . phpversion() . ")";

		// check php extensions
		foreach($this->components as $component)
		{
			if(! isset($this->installConfig[$component]["php_extensions"]) || ! is_array($this->installConfig[$component]["php_extensions"]))
				continue;

			foreach($this->installConfig[$component]["php_extensions"] as $ext)
			{
				if(! extension_loaded($ext))
					$this->prerequisites[] = "Missing $ext PHP extension";
			}
		}
	}

	// checks if the mysql settings $key is as $expected using the db $link
	// if $allow_greater it also checks if the value is greater the the $expected (not only equal)
	private function getMysqlSetting(&$link, $key)
	{
		$result = mysqli_query($link, "SELECT @@$key;");
		if($result === false)
			return null;

		/* @var $result mysqli_result */
		$tmp = '@@' . $key;
		$current = $result->fetch_object()->$tmp;
		return $current;
	}

	private function validateDWH()
	{
		if(! in_array('dwh', $this->components))
			return;

		// check pentaho exists
		$pentaho = $this->installConfig['dwh']["pentaho_path"];
		if(! is_file($pentaho))
			$this->prerequisites[] = "Missing pentaho at $pentaho";
	}

	private function validateMysql()
	{
		if(! in_array('db', $this->components))
		{
			$dbRequired = false;
			foreach($this->components as $component)
			{
				if(isset($this->installConfig[$component]['depends_on']) && in_array('db', $this->installConfig[$component]['depends_on']))
				{
					$dbRequired = true;
					break;
				}
			}
			return;
		}

		// check mysql
		$link = null;
		if(! extension_loaded('mysqli'))
		{
			$this->prerequisites[] = "Cannot check MySQL connection, version and settings because PHP mysqli extension is not loaded";
			return;
		}

		$hosts = array(
			AppConfigAttribute::DB1_HOST => AppConfigAttribute::DB1_PORT,
			AppConfigAttribute::DB2_HOST => AppConfigAttribute::DB2_PORT,
			AppConfigAttribute::DB3_HOST => AppConfigAttribute::DB3_PORT,
			AppConfigAttribute::DWH_HOST => AppConfigAttribute::DWH_PORT,
			AppConfigAttribute::SPHINX_DB_HOST => AppConfigAttribute::SPHINX_DB_PORT,
		);

		$checkedConnections = array();
		foreach ($hosts as $hostAttribute => $portAttribute)
		{
			$host = AppConfig::get($hostAttribute);
			$port = AppConfig::get($portAttribute);

			if(isset($checkedConnections["$host:$port"]))
				continue;

			$checkedConnections["$host:$port"] = true;

			$link = DatabaseUtils::connect($host, $port);
			if(!$link)
			{
				$this->prerequisites[] = "Failed to connect to database host $host:$port user:" . AppConfig::get(AppConfigAttribute::DB_ROOT_USER) . ". Please check the database settings you provided and verify that MySQL is up and running.";
				continue;
			}

			// check mysql version and settings
			$mysql_version = $this->getMysqlSetting($link, 'version'); // will always return the value
			if(! $this->checkVersion($mysql_version, $this->installConfig['db']["mysql_min_version"]))
			{
				$this->prerequisites[] = "MySQL host $host:$port version should be >= " . $this->installConfig['db']["mysql_min_version"] . " (current version is $mysql_version)";
			}

			foreach($this->installConfig['db']['mysql_settings'] as $field => $value)
			{
				$actualValue = $this->getMysqlSetting($link, $field);
				if(is_null($actualValue))
				{
					$this->prerequisites[] = "Please set MySQL host $host:$port\n'$field = $value'\n in my.cnf and restart MySQL";
				}
				else if($actualValue != $value)
				{
					$this->prerequisites[] = "Please set MySQL host $host:$port\n'$field = $value'\n in my.cnf and restart MySQL (current value is $actualValue)";
				}
			}
		}
	}

	private function validateApache()
	{
		$requiredModules = array();
		foreach($this->components as $component)
		{
			if(! isset($this->installConfig[$component]["apache_modules"]) || ! is_array($this->installConfig[$component]["apache_modules"]))
				continue;

			foreach($this->installConfig[$component]["apache_modules"] as $module)
				$requiredModules[$module] = true;
		}

		if(!count($requiredModules))
			return;

		$httpdBin = AppConfig::get(AppConfigAttribute::HTTPD_BIN);
		$modules1 = OsUtils::executeWithOutput("$httpdBin -M", true);
		$modules2 = OsUtils::executeWithOutput("$httpdBin -M", false);
		
		if(is_array($modules1) && is_array($modules2))
			$currentModules = array_merge($modules1, $modules2);
		elseif(is_array($modules1))
			$currentModules = $modules1;
		elseif(is_array($modules2))
			$currentModules = $modules2;
		else
		{
			$this->prerequisites[] = "Cannot check apache modules, please make sure that '$httpdBin -t' command runs properly";
			return;
		}
		$currentModules = array_map('trim', $currentModules);

		foreach($requiredModules as $module => $true)
		{
			$found = false;
			foreach($currentModules as $currentModule)
			{
				if(strpos($currentModule, $module) === 0)
				{
					$found = true;
					break;
				}
			}

			if(! $found)
				$this->prerequisites[] = "Missing $module Apache module";
		}
	}

	private function validateBinaries()
	{
		foreach($this->components as $component)
		{
			if(! isset($this->installConfig[$component]["binaries"]) || ! is_array($this->installConfig[$component]["binaries"]))
				continue;

			foreach($this->installConfig[$component]["binaries"] as $bin)
			{
				$bins = explode('|', $bin);
				$found = false;
				foreach($bins as $optionalBin)
				{
					system("which $optionalBin 2>/dev/null", $exitCode);
					if($exitCode === 0)
					{
						$found = true;
						break;
					}
				}

				if(!$found)
					$this->prerequisites[] = "Missing $bin binary file";
			}
		}
	}

	// check if the given $version is equal or bigger than the $expected
	// both $version and $expected are version strings which means that they are numbers separated by dots ('.')
	// if $version has less parts, the missing parts are treated as zeros
	private function checkVersion($version, $expected)
	{
		$version_parts = explode('.', $version);
		$expected_parts = explode('.', $expected);

		for($i = 0; $i < count($expected_parts); $i ++)
		{
			// allow the version to have less parts than the expected, fill the missing with zeros
			$comparison = 0;
			if($i < count($version_parts))
			{
				$comparison = intval($version_parts[$i]);
			}

			// if the part is smaller the version is not ok
			if($comparison < intval($expected_parts[$i]))
			{
				return false;

		// if the part is bigger the version is ok
			}
			else if($comparison > intval($expected_parts[$i]))
			{
				return true;
			}
		}

		return true;
	}

	public function validate()
	{
		if (!OsUtils::verifyOS())
			return array("Installation cannot continue, Kaltura platform can only be installed on Linux OS at this time.");

		$this->validatePHP();
		$this->validateMysql();
		$this->validateApache();
		$this->validateDWH();
		$this->validateBinaries();

		// Check that SELinux is not enabled (enforcing)
		exec("which getenforce 2>/dev/null", $out, $rc);
		if($rc === 0)
		{
			exec("getenforce", $out, $rc);
			if($out[1] === 'Enforcing')
				$this->prerequisites[] = "SELinux is Enabled, please edit file '/etc/sysconfig/selinux' and set SELINUX to permissive, to apply the change in current session execute 'setenforce permissive'.";
		}

		return $this->prerequisites;
	}
}

