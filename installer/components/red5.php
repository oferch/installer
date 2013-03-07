<?php

//url-managers.ini change
$location  = AppConfig::get(AppConfigAttribute::APP_DIR)."/configurations/url_managers.ini";
$urlManagersValues = parse_ini_file($location);
$red5Addition = array ('class' => 'kLocalPathUrlManager');
$urlManagersValues[AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME)] = $red5Addition;
OsUtils::writeToIniFile($location, $urlManagersValues);

return true;
