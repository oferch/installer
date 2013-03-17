<?php

include_once(__DIR__ . '/AppConfig.class.php');
include_once(__DIR__ . '/Validator.class.php');


AppConfig::init();

if(isset($argv[1]))
	AppConfig::set(AppConfigAttribute::HTTPD_BIN, $argv[1]);
if(isset($argv[2]))
	AppConfig::set(AppConfigAttribute::DB1_HOST, $argv[2]);
if(isset($argv[3]))
	AppConfig::set(AppConfigAttribute::DB1_PORT, $argv[3]);
if(isset($argv[4]))
	AppConfig::set(AppConfigAttribute::DB_ROOT_USER, $argv[4]);
if(isset($argv[5]))
	AppConfig::set(AppConfigAttribute::DB_ROOT_PASS, $argv[5]);

$validator = new Validator();
$prerequisites = $validator->validate();

if (count($prerequisites))
{
	echo implode("\n", $prerequisites);
	exit(1);
}

exit(0);

