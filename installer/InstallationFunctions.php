<?php

/**
 * Checks that current user is root
 */
function verifyRootUser() {
	@exec('id -u', $output, $result);
	logMessage(L_INFO, "User: $output");
	return (isset($output[0]) && $output[0] == '0' && $result == 0);
}

function verifyOS() {
	logMessage(L_INFO, "OS: ".InstallUtils::getOsName());
	return (InstallUtils::getOsName() === InstallUtils::LINUX_OS);
}

function defineInstallationTokens(&$app_config) {
	logMessage(L_INFO, "Defining installation tokens for config");
	// directories
	$app_config['APP_DIR'] = $app_config['BASE_DIR'].'/app/';	
	$app_config['WEB_DIR'] = $app_config['BASE_DIR'].'/web/';	
	$app_config['LOG_DIR'] = $app_config['BASE_DIR'].'/log/';	
	$app_config['BIN_DIR'] = $app_config['BASE_DIR'].'/bin/';	
	$app_config['TMP_DIR'] = $app_config['BASE_DIR'].'/tmp/';
	
	// databases (copy information collected during prerequisites
	collectDatabaseCopier($app_config, '1', '2');
	collectDatabaseCopier($app_config, '1', '3');
			
	// admin console defaults
	$app_config['ADMIN_CONSOLE_PARTNER_SECRET'] = InstallUtils::generateSecret();
	$app_config['ADMIN_CONSOLE_PARTNER_ADMIN_SECRET'] =  InstallUtils::generateSecret();
	$app_config['SYSTEM_USER_ADMIN_EMAIL'] = $app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
	$app_config['ADMIN_CONSOLE_PARTNER_ALIAS'] = md5('-2kaltura partner');
	$app_config['ADMIN_CONSOLE_KUSER_MAIL'] = 'admin_console@kaltura.com';	
	InstallUtils::generateSha1Salt($app_config['ADMIN_CONSOLE_PASSWORD'], $salt, $sha1);	
	$app_config['SYSTEM_USER_ADMIN_SALT'] = $salt;
	$app_config['ADMIN_CONSOLE_KUSER_SHA1'] = $salt;
	$app_config['SYSTEM_USER_ADMIN_SHA1'] = $sha1;
	$app_config['ADMIN_CONSOLE_KUSER_SALT'] = $sha1;
	//$app_config['XYMON_SERVER_MONITORING_CONTROL_SCRIPT'] = // Not set
	
	// stats DB
	collectDatabaseCopier($app_config, '1', '_STATS');
	$app_config['DB_STATS_NAME'] = 'kaltura_stats';
	
	// data warehouse
	$app_config['DWH_HOST'] = $app_config['DB1_HOST'];
	$app_config['DWH_PORT'] = $app_config['DB1_PORT'];
	$app_config['DWH_DATABASE_NAME'] = 'kalturadw';
	$app_config['DWH_USER'] = 'etl';
	$app_config['DWH_PASS'] = 'etl';
	$app_config['DWH_SEND_REPORT_MAIL'] = $app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
	$app_config['DWH_SEND_REPORT_MAIL'] = $app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
			
	// default partners and kusers
	$app_config['TEMPLATE_PARTNER_MAIL'] = 'template@kaltura.com';
	$app_config['TEMPLATE_KUSER_MAIL'] = $app_config['TEMPLATE_PARTNER_MAIL'];
	$app_config['TEMPLATE_ADMIN_KUSER_SALT'] = $app_config['SYSTEM_USER_ADMIN_SALT'];
	$app_config['TEMPLATE_ADMIN_KUSER_SHA1'] = $app_config['SYSTEM_USER_ADMIN_SHA1'];		
	
	// batch
	$app_config['BATCH_ADMIN_MAIL'] = $app_config['ADMIN_CONSOLE_ADMIN_MAIL'];
	$app_config['BATCH_KUSER_MAIL'] = 'batch@kaltura.com';
	$app_config['BATCH_HOST_NAME'] = InstallUtils::getComputerName();
	$app_config['BATCH_PARTNER_SECRET'] = InstallUtils::generateSecret();
	$app_config['BATCH_PARTNER_ADMIN_SECRET'] = InstallUtils::generateSecret();
	$app_config['BATCH_PARTNER_PARTNER_ALIAS'] = md5('-1kaltura partner');
	
	// site settings
	$app_config['KALTURA_VIRTUAL_HOST_NAME'] = removeHttp($app_config['KALTURA_FULL_VIRTUAL_HOST_NAME']);
	$app_config['CORP_REDIRECT'] = '';	
	$app_config['CDN_HOST'] = $app_config['KALTURA_VIRTUAL_HOST_NAME'];
	$app_config['IIS_HOST'] = $app_config['KALTURA_VIRTUAL_HOST_NAME'];
	$app_config['RTMP_URL'] = $app_config['KALTURA_VIRTUAL_HOST_NAME'];
	$app_config['MEMCACHE_HOST'] = $app_config['KALTURA_VIRTUAL_HOST_NAME'];
	$app_config['WWW_HOST'] = $app_config['KALTURA_VIRTUAL_HOST_NAME'];
	$app_config['SERVICE_URL'] = 'http://'.$app_config['KALTURA_VIRTUAL_HOST_NAME'];
	$app_config['ENVIRONEMTN_NAME'] = $app_config['KALTURA_VIRTUAL_HOST_NAME'];
			
	// other configurations
	$app_config['APACHE_RESTART_COMMAND'] = $app_config['HTTPD_BIN'].' -k restart';
	$app_config['TIME_ZONE'] = date('e');
	$app_config['GOOGLE_ANALYTICS_ACCOUNT'] = 'UA-7714780-1';
	$app_config['INSTALLATION_TYPE'] = '';
	$app_config['PARTNERS_USAGE_REPORT_SEND_FROM'] = ''; 
	$app_config['PARTNERS_USAGE_REPORT_SEND_TO'] = '';
	$app_config['SYSTEM_PAGES_LOGIN_USER'] = '';
	$app_config['SYSTEM_PAGES_LOGIN_PASS'] = '123456';
	$app_config['KMC_BACKDOR_SHA1_PASS'] = '123456';
	$app_config['DC0_SECRET'] = '';
	$app_config['APACHE_CONF'] = '';		
	
	// storage profile related
	$app_config['DC_NAME'] = 'local';
	$app_config['DC_DESCRIPTION'] = 'local';
	$app_config['STORAGE_BASE_DIR'] = $app_config['WEB_DIR'];
	$app_config['DELIVERY_HTTP_BASE_URL'] = $app_config['SERVICE_URL'];
	$app_config['DELIVERY_RTMP_BASE_URL'] = $app_config['RTMP_URL'];
	$app_config['DELIVERY_ISS_BASE_URL'] = $app_config['SERVICE_URL'];	
	$app_config['ENVIRONMENT_NAME'] = $app_config['KALTURA_VIRTUAL_HOST_NAME'];
}

function collectDatabaseCopier(&$config, $fromNum, $toNum) {
	$config['DB'.$toNum.'_HOST'] = $config['DB'.$fromNum.'_HOST'];
	$config['DB'.$toNum.'_PORT'] = $config['DB'.$fromNum.'_PORT'];
	$config['DB'.$toNum.'_NAME'] = $config['DB'.$fromNum.'_NAME'];
	$config['DB'.$toNum.'_USER'] = $config['DB'.$fromNum.'_USER'];
	$config['DB'.$toNum.'_PASS'] = $config['DB'.$fromNum.'_PASS'];
}
	
function removeHttp($url = '') {
	$list = array('http://', 'https://');
	foreach ($list as $item) {
		if (strncasecmp($url, $item, strlen($item)) == 0)
			return substr($url, strlen($item));
	}
	return $url;
}

function saveUninstallerConfig($file, $config) {
	$data = "BASE_DIR = ".$config["BASE_DIR"].PHP_EOL;
	$data = $data."ETL_HOME_DIR = ".$config["ETL_HOME_DIR"].PHP_EOL;
	$data = $data."DB1_NAME = ".$config["DB1_NAME"].PHP_EOL;
	$data = $data."DB1_HOST = ".$config["DB1_HOST"].PHP_EOL;
	$data = $data."DB1_USER = ".$config["DB1_USER"].PHP_EOL;
	$data = $data."DB1_PASS = ".$config["DB1_PASS"].PHP_EOL;
	$data = $data."DB1_PORT = ".$config["DB1_PORT"].PHP_EOL;
	$data = $data."DB_STATS_NAME = ".$config["DB_STATS_NAME"].PHP_EOL;
	$data = $data."DB_STATS_HOST = ".$config["DB_STATS_HOST"].PHP_EOL;
	$data = $data."DB_STATS_USER = ".$config["DB_STATS_USER"].PHP_EOL;
	$data = $data."DB_STATS_PASS = ".$config["DB_STATS_PASS"].PHP_EOL;
	$data = $data."DB_STATS_PORT = ".$config["DB_STATS_PORT"].PHP_EOL;	
	return FileUtils::writeFile($file, $data);
}