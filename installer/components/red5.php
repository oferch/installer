<?php

//url-managers.ini change
$location  = AppConfig::get(AppConfigAttribute::APP_DIR)."/configurations/url_managers.ini";
$urlManagersValues = parse_ini_file($location);
$red5Addition = array ('class' => 'kLocalPathUrlManager');
$urlManagersValues[AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME)] = $red5Addition;
OsUtils::writeToIniFile($location, $urlManagersValues);

//Retrieve KCW uiconf ids
$uiconfIds = $this->extractKCWUiconfIds();
Logger::logMessage(Logger::LEVEL_USER, "If you are insterested in recording entries from webcam, please adjust the RTMP server URL in each of the following uiConfs:\r\n". implode("\r\n", $uiconfIds));
Logger::logMessage(Logger::LEVEL_USER, "By replacing 'rtmp://yoursite.com/oflaDemo' with 'rtmp://". AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME) . "/oflaDemo");

return true;
