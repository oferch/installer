<?php

if(AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT) != 80)
{
	$configFiles = glob(AppConfig::get(AppConfigAttribute::APP_DIR) . '/configurations/apache/kaltura*.conf');
	$configLine = 'Listen ' . AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT);
	foreach($configFiles as $configFile)
	{
		$content = $configLine . "\n" . file_get_contents($configFile);
		file_put_contents($configFile, $content);
	}
}

$this->restartApache();

return true;
