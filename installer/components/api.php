<?php

if(AppConfig::get(AppConfigAttribute::ENVIRONMENT_PROTOCOL) == 'http' && AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT) != 80)
{
	$configFile = AppConfig::get(AppConfigAttribute::APP_DIR) . '/configurations/apache/kaltura.conf';
	if(file_exists($configFile))
	{
		$configLine = 'Listen ' . AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT);
		$content = $configLine . "\n" . file_get_contents($configFile);
		file_put_contents($configFile, $content);
	}
}

if(AppConfig::get(AppConfigAttribute::ENVIRONMENT_PROTOCOL) == 'https' && AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT) != 443)
{
	$configFile = AppConfig::get(AppConfigAttribute::APP_DIR) . '/configurations/apache/kaltura.ssl.conf';
	if(file_exists($configFile))
	{
		$configLine = 'Listen ' . AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT);
		$content = $configLine . "\n" . file_get_contents($configFile);
		file_put_contents($configFile, $content);
	}
}

$this->restartApache();

return true;
