<?php

if(AppConfig::get(AppConfigAttribute::DB1_CREATE_NEW_DB) || !DatabaseUtils::dbExists(AppConfig::get(AppConfigAttribute::DWH_HOST), AppConfig::get(AppConfigAttribute::DWH_PORT), AppConfig::get(AppConfigAttribute::DWH_DATABASE_NAME)))
{
	Logger::logMessage(Logger::LEVEL_INFO, "Creating data warehouse");
	if (!OsUtils::execute(sprintf("%s/setup/dwh_setup.sh -h %s -P %s -u %s -p %s -d %s ", AppConfig::get(AppConfigAttribute::DWH_DIR), AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB1_PORT), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DB_ROOT_PASS), AppConfig::get(AppConfigAttribute::DWH_DIR)))) {
		return "Failed running data warehouse initialization script";
	}
}

return true;
