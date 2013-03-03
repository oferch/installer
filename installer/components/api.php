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

$this->restartApache();

return true;
