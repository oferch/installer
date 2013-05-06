<?php

Logger::logMessage(Logger::LEVEL_INFO, "Creating data warehouse");
if (!AppConfig::get(AppConfigAttribute::DB_ROOT_PASS)) {
	$cmd = sprintf("%s/setup/dwh_setup.sh -h %s -P %s -u %s -d %s ", AppConfig::get(AppConfigAttribute::DWH_DIR), AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB1_PORT), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DWH_DIR));
} else {
	$cmd = sprintf("%s/setup/dwh_setup.sh -h %s -P %s -u %s -p %s -d %s ", AppConfig::get(AppConfigAttribute::DWH_DIR), AppConfig::get(AppConfigAttribute::DB1_HOST), AppConfig::get(AppConfigAttribute::DB1_PORT), AppConfig::get(AppConfigAttribute::DB_ROOT_USER), AppConfig::get(AppConfigAttribute::DB_ROOT_PASS), AppConfig::get(AppConfigAttribute::DWH_DIR));
}
if (!OsUtils::execute($cmd)){
	return "Failed running data warehouse initialization script";
}

return true;
