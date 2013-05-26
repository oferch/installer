<?php

include_once('installer/OsUtils.class.php');
include_once('installer/Log.php');
include_once('installer/AppConfig.class.php');
include_once('installer/Installer.class.php');

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

date_default_timezone_set(@date_default_timezone_get());

$options = getopt('hrv');
if(isset($options['h']))
{
	echo 'Usage is php ' . __FILE__ . ' [arguments]'.PHP_EOL;
	echo " -h - Show this help." . PHP_EOL;
	echo " -r - Reconfigure." . PHP_EOL;
	echo " -v - Verbose output." . PHP_EOL;
	
	echo PHP_EOL;
	echo "Examples:" . PHP_EOL;
	echo 'php ' . __FILE__ . ' -s' . PHP_EOL;

	exit(0);
}

$silentRun = !isset($options['r']);
$verbose = isset($options['v']);

// start the log
$logPath = __DIR__ . '/verify.' . date("Y.m.d_H.i.s") . '.log';
$detailsLogPath = null;
Logger::init($logPath, $verbose);
Logger::logMessage(Logger::LEVEL_INFO, "Command: " . __FILE__ . ' ' . implode(' ', $argv));
if(!$verbose)
{
	$detailsLogPath = __DIR__ . '/verify.' . date("Y.m.d_H.i.s") . '.details.log';
	OsUtils::setLogPath($detailsLogPath);
}

echo PHP_EOL;
Logger::logColorMessage(Logger::COLOR_LIGHT_BLUE, Logger::LEVEL_USER, "Kaltura Video Platform - Server Installation");

$packageDir = realpath(__DIR__ . '/../package');
if($packageDir)
	AppConfig::init($packageDir);

AppConfig::set(AppConfigAttribute::VERBOSE, $verbose);
AppConfig::configure($silentRun);

echo PHP_EOL;

$installer = new Installer();
Logger::logColorMessage(Logger::COLOR_YELLOW, Logger::LEVEL_USER, "Verifying Kaltura Server Installation");
if($installer->verifyInstallation(true))
{
	Logger::logColorMessage(Logger::COLOR_LIGHT_GREEN, Logger::LEVEL_USER, "Server Installation Verified Successfully");
}
else
{
	Logger::logMessage(Logger::LEVEL_USER, "Verification log files:");
	Logger::logMessage(Logger::LEVEL_USER, "\t - $logPath");

	if($detailsLogPath)
		Logger::logMessage(Logger::LEVEL_USER, "\t - $detailsLogPath");
	
	exit(1);
}

exit(0);
