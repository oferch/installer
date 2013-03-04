<?php

include_once (realpath(dirname(__FILE__)) . '/DatabaseUtils.class.php');

class Validator
{
	/**
	 * Configuration
	 * @var array
	 */
	private $install_config;

	/**
	 * Array of the components that should be installed
	 * @var array
	 */
	private $components = array('all');

	/**
	 * Enter description here ...
	 * @var array
	 */
	private $prerequisites = array();

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

	private function validatePHP()
	{
		// check php version
		if(! $this->checkVersion(phpversion(), $this->install_config['all']["php_min_version"]))
			$this->prerequisites[] = "PHP version should be >= " . $this->install_config['all']["php_min_version"] . " (current version is " . phpversion() . ")";

		// check php extensions
		foreach($this->components as $component)
		{
			if(! isset($this->install_config[$component]["php_extensions"]) || ! is_array($this->install_config[$component]["php_extensions"]))
				continue;

			foreach($this->install_config[$component]["php_extensions"] as $ext)
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
		$pentaho = $this->install_config['dwh']["pentaho_path"];
		if(! is_file($pentaho))
			$this->prerequisites[] = "Missing pentaho at $pentaho";
	}

	private function validateMysql()
	{
		if(! in_array('db', $this->components))
			return;

		// check mysql
		$link = null;
		if(! extension_loaded('mysqli'))
		{
			$this->prerequisites[] = "Cannot check MySQL connection, version and settings because PHP mysqli extension is not loaded";
			return;
		}

		if(! DatabaseUtils::connect($link))
		{
			$this->prerequisites[] = "Failed to connect to database " . AppConfig::get(AppConfigAttribute::DB1_HOST) . ":" . AppConfig::get(AppConfigAttribute::DB1_PORT) . " user:" . AppConfig::get(AppConfigAttribute::DB_ROOT_USER) . ". Please check the database settings you provided and verify that MySQL is up and running.";
			return;
		}

		// check mysql version and settings
		$mysql_version = $this->getMysqlSetting($link, 'version'); // will always return the value
		if(! $this->checkVersion($mysql_version, $this->install_config['db']["mysql_min_version"]))
		{
			$this->prerequisites[] = "MySQL version should be >= " . $this->install_config['db']["mysql_min_version"] . " (current version is $mysql_version)";
		}

		$lower_case_table_names = $this->getMysqlSetting($link, 'lower_case_table_names');
		if(! isset($lower_case_table_names))
		{
			$this->prerequisites[] = "Please set\n'lower_case_table_names = " . $this->install_config['db']["lower_case_table_names"] . "\n' in my.cnf and restart MySQL";
		}
		else if(intval($lower_case_table_names) != intval($this->install_config['db']["lower_case_table_names"]))
		{
			$this->prerequisites[] = "Please set\n'lower_case_table_names = " . $this->install_config['db']["lower_case_table_names"] . "\n' in my.cnf and restart MySQL (current value is $lower_case_table_names)";
		}

		$thread_stack = $this->getMysqlSetting($link, 'thread_stack');
		if(! isset($thread_stack))
		{
			$this->prerequisites[] = "Please set 'thread_stack >= " . $this->install_config['db']["thread_stack"] . "' in my.cnf and restart MySQL";
		}
		else if(intval($thread_stack) < intval($this->install_config['db']["thread_stack"]))
		{
			$this->prerequisites[] = "Please set 'thread_stack >= " . $this->install_config['db']["thread_stack"] . "' in my.cnf and restart MySQL (current value is $thread_stack)";
		}
	}

	private function validateApache()
	{
		// check apache modules
		$httpdBin = AppConfig::get(AppConfigAttribute::HTTPD_BIN);
		exec("$httpdBin -M 2>&1", $currentModules, $exitCode);
		if($exitCode !== 0)
		{
			$this->prerequisites[] = "Cannot check apache modules, please make sure that '$httpdBin -t' command runs properly";
			return;
		}
		array_walk($currentModules, create_function('&$str', '$str = trim($str);'));

		foreach($this->components as $component)
		{
			if(! isset($this->install_config[$component]["apache_modules"]) || ! is_array($this->install_config[$component]["apache_modules"]))
				continue;

			foreach($this->install_config[$component]["apache_modules"] as $module)
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
	}

	private function validateBinaries()
	{
		foreach($this->components as $component)
		{
			if(! isset($this->install_config[$component]["binaries"]) || ! is_array($this->install_config[$component]["binaries"]))
				continue;

			foreach($this->install_config[$component]["binaries"] as $bin)
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

