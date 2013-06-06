<?php

require_once(__DIR__ . '/installer/DatabaseUtils.class.php');
require_once(__DIR__ . '/installer/OsUtils.class.php');
require_once(__DIR__ . '/installer/InstallReport.class.php');
require_once(__DIR__ . '/installer/AppConfig.class.php');
require_once(__DIR__ . '/installer/Installer.class.php');
require_once(__DIR__ . '/installer/Validator.class.php');

$options = getopt('hsvat:');
if($argc < 2 || isset($options['h']))
{
	echo 'Usage is php ' . __FILE__ . ' [arguments]'.PHP_EOL;
	echo " -h - Show this help." . PHP_EOL;
	echo " -s - Silent mode, no questions will be asked." . PHP_EOL;
	echo " -v - Verbose output." . PHP_EOL;
	echo " -t - Type TM/CE." . PHP_EOL;
	
	// don't tell anyone it's possible
	// echo "-a - Auto-generate activation key." . PHP_EOL;
	
	echo PHP_EOL;
	echo "Examples:" . PHP_EOL;
	echo 'php ' . __FILE__ . PHP_EOL;

	if(isset($options['h']))
		exit(0);

	exit(-1);
}

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

date_default_timezone_set(@date_default_timezone_get());

$silentRun = isset($options['s']);
$verbose = isset($options['v']);
$autoGenerateKey = isset($options['a']);

// start the log
$logPath = __DIR__ . '/configure.' . date("Y.m.d_H.i.s") . '.log';
$detailsLogPath = null;
Logger::init($logPath, $verbose);
Logger::logMessage(Logger::LEVEL_INFO, "Command: " . implode(' ', $argv));
if(!$verbose)
{
	$detailsLogPath = __DIR__ . '/configure.' . date("Y.m.d_H.i.s") . '.details.log';
	OsUtils::setLogPath($detailsLogPath);
}

OsUtils::setLogPath($detailsLogPath);

echo PHP_EOL;
Logger::logColorMessage(Logger::COLOR_LIGHT_BLUE, Logger::LEVEL_USER, "Kaltura Video Platform - Server Installation Configurator");

$type = AppConfig::K_TM_TYPE;
if(isset($options['t']))
	$type = $options['t'];
AppConfig::init(__DIR__, $type);
AppConfig::set(AppConfigAttribute::VERBOSE, $verbose);
if($autoGenerateKey)
	AppConfig::set(AppConfigAttribute::ACTIVATION_KEY, true);

AppConfig::configure($silentRun, true);

Logger::logColorMessage(Logger::COLOR_LIGHT_GREEN, Logger::LEVEL_USER, "Configuration available at " . AppConfig::getUserInputFilePath());
exit(0);
