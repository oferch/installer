<?php


$arguments = sprintf('-h %s -P %s -u %s -d %s', AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB1_PORT), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DWH_DIR));
if (AppConfig::get(AppConfigAttribute::DB_ROOT_PASS))
	$arguments .= ' -p ' . AppConfig::get(AppConfigAttribute::DB_ROOT_PASS);
	
if(AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
{
	Logger::logMessage(Logger::LEVEL_INFO, "Creating data warehouse");
	$cmd = sprintf("%s/setup/dwh_setup.sh $arguments", AppConfig::get(AppConfigAttribute::DWH_DIR));	
	if (!OsUtils::execute($cmd)){
		return "Failed running data warehouse initialization script";
	}
}
elseif(AppConfig::get(AppConfigAttribute::UPGRADE_FROM_VERSION))
{
	Logger::logMessage(Logger::LEVEL_INFO, "Upgrading data warehouse");
	$cmd = sprintf("%s/ddl/migrations/20130606_falcon_to_gemini/Falcon2Gemini.sh $arguments", AppConfig::get(AppConfigAttribute::DWH_DIR));
	if (!OsUtils::execute($cmd)){
		return "Failed running data warehouse upgrade script";
	}
}

return true;
