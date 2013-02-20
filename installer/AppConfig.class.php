<?php

define('TOKEN_CHAR', '@'); // this character is user to surround parameters that should be replaced with configurations in config files
define('TEMPLATE_FILE', '.template'); // how to recognize a template file, template files are copyed to non-template and then the tokens are replaced
define('KCONF_LOCAL_LOCATION', '/configurations/local.ini'); // the location of kConf
define('UNINSTALLER_LOCATION', '/uninstaller/uninstall.ini'); // the location where to save configuration for the uninstaller

class AppConfigAttribute 
{
	const BASE_DIR								= 'BASE_DIR';
	const APP_DIR								= 'APP_DIR';
	const WEB_DIR								= 'WEB_DIR';
	const BIN_DIR								= 'BIN_DIR';
	const LOG_DIR								= 'LOG_DIR';
	const TMP_DIR								= 'TMP_DIR';
	const DWH_DIR								= 'DWH_DIR';
	const ETL_HOME_DIR							= 'ETL_HOME_DIR';
	
	const PHP_BIN								= 'PHP_BIN';
	const HTTPD_BIN								= 'HTTPD_BIN';	
	const LOG_ROTATE_BIN						= 'LOG_ROTATE_BIN';
	const IMAGE_MAGICK_BIN_DIR					= 'IMAGE_MAGICK_BIN_DIR';
	const CURL_BIN_DIR							= 'CURL_BIN_DIR';
	const SPHINX_BIN_DIR						= 'SPHINX_BIN_DIR';
	
	const OS_ROOT_USER							= 'OS_ROOT_USER';
	const OS_APACHE_USER						= 'OS_APACHE_USER';
	const OS_KALTURA_USER						= 'OS_KALTURA_USER';
	
	const OS_ROOT_GROUP							= 'OS_ROOT_GROUP';
	const OS_APACHE_GROUP						= 'OS_APACHE_GROUP';
	const OS_KALTURA_GROUP						= 'OS_KALTURA_GROUP';
	
	const DB_ROOT_USER							= 'DB_ROOT_USER';
	const DB_ROOT_PASS							= 'DB_ROOT_PASS';
	
	const DB1_HOST								= 'DB1_HOST';
	const DB1_PORT								= 'DB1_PORT';
	const DB1_USER								= 'DB1_USER';
	const DB1_PASS								= 'DB1_PASS';
	const DB1_NAME								= 'DB1_NAME';
	
	const SPHINX_SERVER							= 'SPHINX_SERVER';
	const SPHINX_DB_HOST						= 'SPHINX_DB_HOST';
	const SPHINX_DB_PORT						= 'SPHINX_DB_PORT';
	const SPHINX_DB_NAME						= 'SPHINX_DB_NAME';
	
	const DWH_HOST								= 'DWH_HOST';
	const DWH_PORT								= 'DWH_PORT';
	const DWH_USER								= 'DWH_USER';
	const DWH_PASS								= 'DWH_PASS';
	const DWH_DATABASE_NAME						= 'DWH_DATABASE_NAME';
	
	const DB1_CREATE_NEW_DB						= 'DB1_CREATE_NEW_DB';
	
	const EVENTS_LOGS_DIR						= 'EVENTS_LOGS_DIR';
	const EVENTS_WILDCARD						= 'EVENTS_WILDCARD';
	const EVENTS_FETCH_METHOD					= 'EVENTS_FETCH_METHOD';
	const ADMIN_CONSOLE_ADMIN_MAIL				= 'ADMIN_CONSOLE_ADMIN_MAIL';
	const ADMIN_CONSOLE_PASSWORD				= 'ADMIN_CONSOLE_PASSWORD';
	const REPORT_ADMIN_EMAIL					= 'REPORT_ADMIN_EMAIL';
	
	const BATCH_PARTNER_ADMIN_SECRET			= 'BATCH_PARTNER_ADMIN_SECRET';
	const PARTNER_ZERO_ADMIN_SECRET				= 'PARTNER_ZERO_ADMIN_SECRET';
	const ADMIN_CONSOLE_PARTNER_ADMIN_SECRET	= 'ADMIN_CONSOLE_PARTNER_ADMIN_SECRET';
	const HOSTED_PAGES_PARTNER_ADMIN_SECRET		= 'HOSTED_PAGES_PARTNER_ADMIN_SECRET';
	const TEMPLATE_PARTNER_ADMIN_SECRET			= 'TEMPLATE_PARTNER_ADMIN_SECRET';
	
	const PARTNERS_USAGE_REPORT_SEND_FROM		= 'PARTNERS_USAGE_REPORT_SEND_FROM';
	const PARTNERS_USAGE_REPORT_SEND_TO			= 'PARTNERS_USAGE_REPORT_SEND_TO';
	const DC0_SECRET							= 'DC0_SECRET';
	const STORAGE_BASE_DIR						= 'STORAGE_BASE_DIR';
	const DELIVERY_HTTP_BASE_URL				= 'DELIVERY_HTTP_BASE_URL';
	const DELIVERY_RTMP_BASE_URL				= 'DELIVERY_RTMP_BASE_URL';
	const DELIVERY_ISS_BASE_URL					= 'DELIVERY_ISS_BASE_URL';	
	
	const PROVISION_PROVIDE_USER				= 'PROVISION_PROVIDE_USER';
	const PROVISION_PROVIDE_PASS				= 'PROVISION_PROVIDE_PASS';
	const PROVISION_PROVIDE_CPCODE				= 'PROVISION_PROVIDE_CPCODE';
	const PROVISION_PROVIDE_EMAIL_ID			= 'PROVISION_PROVIDE_EMAIL_ID';
	const PROVISION_PROVIDE_PRIMARY_CONTACT		= 'PROVISION_PROVIDE_PRIMARY_CONTACT';
	const PROVISION_PROVIDE_SECONDARY_CONTACT	= 'PROVISION_PROVIDE_SECONDARY_CONTACT';
	
	const SERVICE_URL							= 'SERVICE_URL';
	const TIME_ZONE								= 'TIME_ZONE';
	const CDN_HOST								= 'CDN_HOST';
	const IIS_HOST								= 'IIS_HOST';
	const WWW_HOST								= 'WWW_HOST';
	const RTMP_URL								= 'RTMP_URL';
	const CORP_REDIRECT							= 'CORP_REDIRECT';
	const MEMCACHE_HOST							= 'MEMCACHE_HOST';
	const GLOBAL_MEMCACHE_HOST					= 'GLOBAL_MEMCACHE_HOST';
	const KALTURA_VIRTUAL_HOST_NAME				= 'KALTURA_VIRTUAL_HOST_NAME';
	const KALTURA_VIRTUAL_HOST_PORT				= 'KALTURA_VIRTUAL_HOST_PORT';
	const KALTURA_FULL_VIRTUAL_HOST_NAME		= 'KALTURA_FULL_VIRTUAL_HOST_NAME';
	const POST_INST_VIRTUAL_HOST_NAME			= 'POST_INST_VIRTUAL_HOST_NAME';
	const POST_INST_ADMIN_CONSOLE_ADMIN_MAIL	= 'POST_INST_ADMIN_CONSOLE_ADMIN_MAIL';
	const ENVIRONMENT_NAME						= 'ENVIRONMENT_NAME';
	const BATCH_HOST_NAME						= 'BATCH_HOST_NAME';
	const BATCH_PARTNER_PARTNER_ALIAS			= 'BATCH_PARTNER_PARTNER_ALIAS';
	const APACHE_RESTART_COMMAND				= 'APACHE_RESTART_COMMAND';
	const BASE_HOST_NO_PORT						= 'BASE_HOST_NO_PORT';
	const ENVIRONMENT_PROTOCOL					= 'ENVIRONMENT_PROTOCOL';
	
	const CONTACT_URL							= 'CONTACT_URL';
	const SIGNUP_URL							= 'SIGNUP_URL';
	const CONTACT_PHONE_NUMBER					= 'CONTACT_PHONE_NUMBER';
	const BEGINNERS_TUTORIAL_URL				= 'BEGINNERS_TUTORIAL_URL';
	const QUICK_START_GUIDE_URL					= 'QUICK_START_GUIDE_URL';
	const FORUMS_URLS							= 'FORUMS_URLS';
	const UNSUBSCRIBE_EMAIL_URL					= 'UNSUBSCRIBE_EMAIL_URL';
	const GOOGLE_ANALYTICS_ACCOUNT				= 'GOOGLE_ANALYTICS_ACCOUNT';
	const INSTALLATION_TYPE						= 'INSTALLATION_TYPE';
	const INSTALLATION_UID						= 'INSTALLATION_UID';
	const INSTALLATION_SEQUENCE_UID				= 'INSTALLATION_SEQUENCE_UID';
	
	const KALTURA_VERSION_TYPE					= 'KALTURA_VERSION_TYPE';
	const KALTURA_VERSION						= 'KALTURA_VERSION';
	
	const UICONF_TAB_ACCESS						= 'UICONF_TAB_ACCESS';
	
	const KALTURA_PREINSTALLED					= 'KALTURA_PREINSTALLED';
	const REPLACE_PASSWORDS						= 'REPLACE_PASSWORDS';
	const TRACK_KDPWRAPPER						= 'TRACK_KDPWRAPPER';
	const USAGE_TRACKING_OPTIN					= 'USAGE_TRACKING_OPTIN';
}

/**
 * This class handles all the configuration of the application:
 * Defining application configuration values according to user input, 
 * replaceing configuration tokens in needed files and other application configuration actions 
 */
class AppConfig 
{
	private static $app_config = array();
	private static $filePath = null;
	
	// gets the application value set for the given key
	public static function get($key) {
		if(!defined("AppConfigAttribute::$key"))
			throw new Exception("Configuration key [$key] not defined");
			
		return self::$app_config[$key];
	}
	
	// sets the application value for the given key
	public static function set($key, $value) {
		if(!defined("AppConfigAttribute::$key"))
			throw new Exception("Configuration key [$key] not defined");
			
		self::$app_config[$key] = $value;
	}
	
	// init the application configuration values according to the user input
	public static function initFromUserInput($user_input) {
		foreach ($user_input as $key => $value) {
			self::$app_config[$key] = $value;
		}
		self::defineInstallationTokens();
	}		
	
	// replaces all tokens in the given string with the configuration values and returns the new string
	public static function replaceTokensInString($string) {
		foreach (self::$app_config as $key => $var) {
			$key = TOKEN_CHAR.$key.TOKEN_CHAR;
			$string = str_replace($key, $var, $string);		
		}
		return $string;
	}
		
	// replaces all the tokens in the given file with the configuration values and returns true/false upon success/failure
	// will override the file if it is not a template file
	// if it is a template file it will save it to a non template file and then override it
	public static function replaceTokensInFile($file) {	
		logMessage(L_USER, "Replacing configuration tokens in file [$file]");	
		$newfile = self::copyTemplateFileIfNeeded($file);
		$data = @file_get_contents($newfile);
		if (!$data) {
			logMessage(L_ERROR, "Cannot replace token in file $newfile");
			return false;			
		} else {
			$data = self::replaceTokensInString($data);
			if (!file_put_contents($newfile, $data)) {
				logMessage(L_ERROR, "Cannot replace token in file, cannot write to file $newfile");
				return false;							
			} else {
				logMessage(L_INFO, "Replaced tokens in file $newfile");			
			}
		}
		return true;
	}	
	
	public static function getFilePath() 
	{
		if(!self::$filePath)
		{	
			self::$filePath = tempnam(sys_get_temp_dir(), 'kaltura.installer.');
			OsUtils::writeConfigToFile(self::$app_config, self::$filePath);
		}
		
		return self::$filePath;
	}		
	
	// saves the uninstaller config file, the values saved are the minimal values subset needed for the uninstaller to run
	public static function saveUninstallerConfig() {
		$file = self::$app_config[AppConfigAttribute::BASE_DIR].UNINSTALLER_LOCATION;
		$data = "BASE_DIR = ".self::$app_config[AppConfigAttribute::BASE_DIR].PHP_EOL;	
		$data = $data."DB_HOST = ".self::$app_config[AppConfigAttribute::DB1_HOST].PHP_EOL;
		$data = $data."DB_USER = ".self::$app_config[AppConfigAttribute::DB1_USER].PHP_EOL;
		$data = $data."DB_PASS = ".self::$app_config[AppConfigAttribute::DB1_PASS].PHP_EOL;
		$data = $data."DB_PORT = ".self::$app_config[AppConfigAttribute::DB1_PORT].PHP_EOL;
		return OsUtils::writeFile($file, $data);
	}	
	
	// update uninstaller config with symlinks definitions
	public static function updateUninstallerConfig($symlinks) {
		$file = self::$app_config[AppConfigAttribute::BASE_DIR].UNINSTALLER_LOCATION;
		$data ='';
		foreach ($symlinks as $slink) {
			$link_items = explode(SYMLINK_SEPARATOR, self::replaceTokensInString($slink));	
			if (is_file($link_items[1]) && (strpos($link_items[1], self::$app_config[AppConfigAttribute::BASE_DIR]) === false)) {
				$data = $data."symlinks[] = ".$link_items[1].PHP_EOL;
			}
		} 
		return OsUtils::appendFile($file, $data);
	}		
	
	// update uninstaller config with chkconfig definitions
	public static function updateUninstallerServices($chkconfig) {
		$data ='';
		foreach ($chkconfig as $service)
			$data .= "chkconfig[] = $service" . PHP_EOL;
			
		$file = self::$app_config[AppConfigAttribute::BASE_DIR].UNINSTALLER_LOCATION;
		return OsUtils::appendFile($file, $data);
	}	
	
	// private functions
	
	// defines all the installation configuration values according to the user input and the default values
	private static function defineInstallationTokens() {
		logMessage(L_INFO, "Defining installation tokens for config");
		// directories
		self::$app_config[AppConfigAttribute::APP_DIR] = self::$app_config[AppConfigAttribute::BASE_DIR].'/app';	
		self::$app_config[AppConfigAttribute::WEB_DIR] = self::$app_config[AppConfigAttribute::BASE_DIR].'/web';	
		self::$app_config[AppConfigAttribute::LOG_DIR] = self::$app_config[AppConfigAttribute::BASE_DIR].'/log';	
		self::$app_config[AppConfigAttribute::BIN_DIR] = self::$app_config[AppConfigAttribute::BASE_DIR].'/bin';	
		self::$app_config[AppConfigAttribute::TMP_DIR] = self::$app_config[AppConfigAttribute::BASE_DIR].'/tmp/';
		self::$app_config[AppConfigAttribute::DWH_DIR] = self::$app_config[AppConfigAttribute::BASE_DIR].'/dwh';
		self::$app_config[AppConfigAttribute::ETL_HOME_DIR] = self::$app_config[AppConfigAttribute::BASE_DIR].'/dwh'; // For backward compatibility
		self::$app_config[AppConfigAttribute::SPHINX_BIN_DIR] = self::$app_config[AppConfigAttribute::BIN_DIR].'/sphinx';
		
		self::$app_config[AppConfigAttribute::IMAGE_MAGICK_BIN_DIR] = "/usr/bin";
		self::$app_config[AppConfigAttribute::CURL_BIN_DIR] = "/usr/bin";
		
		
		// site settings
		if (strpos(self::$app_config[AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME], ":") !== false)
		{
			self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT] = parse_url(self::$app_config[AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME], PHP_URL_PORT);
		}
		else
		{
			self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT] = 80;
		}
		self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME] = self::removeHttp(self::$app_config[AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME]);
		self::$app_config[AppConfigAttribute::CORP_REDIRECT] = '';	
		self::$app_config[AppConfigAttribute::CDN_HOST] = self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::IIS_HOST] = self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::RTMP_URL] = 'rtmp://' . AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME) . '/oflaDemo';
		self::$app_config[AppConfigAttribute::BASE_HOST_NO_PORT] = self::extractHostName(self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME]);
		self::$app_config[AppConfigAttribute::MEMCACHE_HOST] = self::extractHostName(self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME]);
		self::$app_config[AppConfigAttribute::GLOBAL_MEMCACHE_HOST] = self::extractHostName(self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME]);
		self::$app_config[AppConfigAttribute::WWW_HOST] = self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::SERVICE_URL] = self::$app_config[AppConfigAttribute::ENVIRONMENT_PROTOCOL].'://'.self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::ENVIRONMENT_NAME] = self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME];
		
		// databases (copy information collected during prerequisites
		if (self::$app_config[AppConfigAttribute::DB1_HOST] == 'localhost') {
			self::$app_config[AppConfigAttribute::DB1_HOST] = '127.0.0.1';
		}
		self::$app_config[AppConfigAttribute::DB1_USER] = 'kaltura';
		self::$app_config[AppConfigAttribute::DB1_PASS] = 'kaltura';
		self::$app_config[AppConfigAttribute::DWH_USER] = 'kaltura_etl';
		self::$app_config[AppConfigAttribute::DWH_PASS] = 'kaltura_etl';
		
		self::collectDatabaseCopier('DB1', 'DB2');
		self::collectDatabaseCopier('DB1', 'DB3');

		//sphinx
		self::$app_config[AppConfigAttribute::SPHINX_SERVER] = self::$app_config[AppConfigAttribute::DB1_HOST];
		self::$app_config[AppConfigAttribute::SPHINX_DB_NAME] = 'kaltura_sphinx_log';
		self::$app_config[AppConfigAttribute::SPHINX_DB_HOST] = self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::SPHINX_DB_PORT] = self::$app_config[AppConfigAttribute::DB1_PORT];
		
		// admin console defaults
		self::$app_config[AppConfigAttribute::UICONF_TAB_ACCESS] = 'SYSTEM_ADMIN_BATCH_CONTROL';
		
		// data warehouse
		self::$app_config[AppConfigAttribute::DWH_HOST] = self::$app_config[AppConfigAttribute::DB1_HOST];
		self::$app_config[AppConfigAttribute::DWH_PORT] = self::$app_config[AppConfigAttribute::DB1_PORT];
		self::$app_config[AppConfigAttribute::DWH_DATABASE_NAME] = 'kalturadw';
		self::$app_config[AppConfigAttribute::EVENTS_LOGS_DIR] = self::$app_config[AppConfigAttribute::LOG_DIR];
		self::$app_config[AppConfigAttribute::EVENTS_WILDCARD] = '.*kaltura.*_apache_access.log-.*';
		self::$app_config[AppConfigAttribute::EVENTS_FETCH_METHOD] = 'local';
		
		// batch
		self::$app_config[AppConfigAttribute::BATCH_HOST_NAME] = OsUtils::getComputerName();
		self::$app_config[AppConfigAttribute::BATCH_PARTNER_PARTNER_ALIAS] = md5('-1kaltura partner');		
				
		// other configurations
		if(!isset(self::$app_config[AppConfigAttribute::HTTPD_BIN]))
			self::$app_config[AppConfigAttribute::HTTPD_BIN] = OsUtils::findBinary(array('apachectl', 'apache2ctl'));
		if(!isset(self::$app_config[AppConfigAttribute::PHP_BIN]))
			self::$app_config[AppConfigAttribute::PHP_BIN] = OsUtils::findBinary('php');
		if(!isset(self::$app_config[AppConfigAttribute::LOG_ROTATE_BIN]))
			self::$app_config[AppConfigAttribute::LOG_ROTATE_BIN] = OsUtils::findBinary('logrotate');
			
		// other configurations
		self::$app_config[AppConfigAttribute::APACHE_RESTART_COMMAND] = self::$app_config[AppConfigAttribute::HTTPD_BIN].' -k restart';
		date_default_timezone_set(self::$app_config[AppConfigAttribute::TIME_ZONE]);
		self::$app_config[AppConfigAttribute::GOOGLE_ANALYTICS_ACCOUNT] = 'UA-7714780-1';
		self::$app_config[AppConfigAttribute::INSTALLATION_TYPE] = '';
		self::$app_config[AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_FROM] = ''; 
		self::$app_config[AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_TO] = '';
		self::$app_config[AppConfigAttribute::DC0_SECRET] = '';
		
		// storage profile related
		self::$app_config[AppConfigAttribute::STORAGE_BASE_DIR] = self::$app_config[AppConfigAttribute::WEB_DIR];
		self::$app_config[AppConfigAttribute::DELIVERY_HTTP_BASE_URL] = self::$app_config[AppConfigAttribute::SERVICE_URL];
		self::$app_config[AppConfigAttribute::DELIVERY_RTMP_BASE_URL] = self::$app_config[AppConfigAttribute::RTMP_URL];
		self::$app_config[AppConfigAttribute::DELIVERY_ISS_BASE_URL] = self::$app_config[AppConfigAttribute::SERVICE_URL];	
		self::$app_config[AppConfigAttribute::ENVIRONMENT_NAME] = self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME];
		
		// set the usage tracking for Kaltura TM
		if (strcasecmp(self::$app_config[AppConfigAttribute::KALTURA_VERSION_TYPE], K_TM_TYPE) === 0) {
			self::$app_config[AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_FROM] = self::$app_config[AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL];	
			self::$app_config[AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_TO] = "on-prem-monthly@kaltura.com";
		}
		
		// mails configurations
		self::$app_config[AppConfigAttribute::FORUMS_URLS] = 'http://www.kaltura.org/forum';
		self::$app_config[AppConfigAttribute::CONTACT_URL] = 'http://corp.kaltura.com/contact';
		self::$app_config[AppConfigAttribute::CONTACT_PHONE_NUMBER] = '+1 800 871-5224';
		self::$app_config[AppConfigAttribute::BEGINNERS_TUTORIAL_URL] = 'http://corp.kaltura.com/about/dosignup';
		self::$app_config[AppConfigAttribute::QUICK_START_GUIDE_URL] = self::$app_config[AppConfigAttribute::ENVIRONMENT_PROTOCOL].'://'.self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME].'/content/docs/KMC_Quick_Start_Guide.pdf';
		self::$app_config[AppConfigAttribute::UNSUBSCRIBE_EMAIL_URL] = '"'.self::$app_config[AppConfigAttribute::ENVIRONMENT_PROTOCOL].'://'.self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME].'/index.php/extwidget/blockMail?e="';

		if(!isset(self::$app_config[AppConfigAttribute::DB1_CREATE_NEW_DB]))
			self::$app_config[AppConfigAttribute::DB1_CREATE_NEW_DB] = true;
		else
			self::$app_config[AppConfigAttribute::DB1_CREATE_NEW_DB] = ((strcasecmp('y',self::$app_config[AppConfigAttribute::DB1_CREATE_NEW_DB]) === 0) || (strcasecmp('yes',self::$app_config[AppConfigAttribute::DB1_CREATE_NEW_DB]) === 0));
	
		if (self::$app_config[AppConfigAttribute::DB1_CREATE_NEW_DB])
		{
			self::$app_config[AppConfigAttribute::PARTNER_ZERO_ADMIN_SECRET] = self::generateSecret();
			self::$app_config[AppConfigAttribute::BATCH_PARTNER_ADMIN_SECRET] = self::generateSecret();
			self::$app_config[AppConfigAttribute::ADMIN_CONSOLE_PARTNER_ADMIN_SECRET] =  self::generateSecret();
			self::$app_config[AppConfigAttribute::HOSTED_PAGES_PARTNER_ADMIN_SECRET] =  self::generateSecret();
			self::$app_config[AppConfigAttribute::TEMPLATE_PARTNER_ADMIN_SECRET] =  self::generateSecret();
		}
		else 
		{
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=0" | mysql -h'.self::$app_config[AppConfigAttribute::DB1_HOST]. ' -P'.self::$app_config[AppConfigAttribute::DB1_PORT] . ' -u'.self::$app_config[AppConfigAttribute::DB1_USER] . ' -p'. self::$app_config[AppConfigAttribute::DB1_PASS] . ' '. self::$app_config[AppConfigAttribute::DB1_NAME] . ' --skip-column-names' );
			self::$app_config[AppConfigAttribute::PARTNER_ZERO_ADMIN_SECRET] = $output[0];
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=-1" | mysql -h'.self::$app_config[AppConfigAttribute::DB1_HOST]. ' -P'.self::$app_config[AppConfigAttribute::DB1_PORT] . ' -u'.self::$app_config[AppConfigAttribute::DB1_USER] . ' -p'. self::$app_config[AppConfigAttribute::DB1_PASS] . ' '. self::$app_config[AppConfigAttribute::DB1_NAME] . ' --skip-column-names' );
			self::$app_config[AppConfigAttribute::BATCH_PARTNER_ADMIN_SECRET] = $output[0];
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=-2" | mysql -h'.self::$app_config[AppConfigAttribute::DB1_HOST]. ' -P'.self::$app_config[AppConfigAttribute::DB1_PORT] . ' -u'.self::$app_config[AppConfigAttribute::DB1_USER] . ' -p'. self::$app_config[AppConfigAttribute::DB1_PASS] . ' '. self::$app_config[AppConfigAttribute::DB1_NAME] . ' --skip-column-names' );
			self::$app_config[AppConfigAttribute::ADMIN_CONSOLE_PARTNER_ADMIN_SECRET] =  $output[0];
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=-3" | mysql -h'.self::$app_config[AppConfigAttribute::DB1_HOST]. ' -P'.self::$app_config[AppConfigAttribute::DB1_PORT] . ' -u'.self::$app_config[AppConfigAttribute::DB1_USER] . ' -p'. self::$app_config[AppConfigAttribute::DB1_PASS] . ' '. self::$app_config[AppConfigAttribute::DB1_NAME] . ' --skip-column-names' );
			self::$app_config[AppConfigAttribute::HOSTED_PAGES_PARTNER_ADMIN_SECRET] =  $output[0];
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=99" | mysql -h'.self::$app_config[AppConfigAttribute::DB1_HOST]. ' -P'.self::$app_config[AppConfigAttribute::DB1_PORT] . ' -u'.self::$app_config[AppConfigAttribute::DB1_USER] . ' -p'. self::$app_config[AppConfigAttribute::DB1_PASS] . ' '. self::$app_config[AppConfigAttribute::DB1_NAME] . ' --skip-column-names' );
			self::$app_config[AppConfigAttribute::TEMPLATE_PARTNER_ADMIN_SECRET] =  $output[0];
		}
		
		if (!isset(self::$app_config[AppConfigAttribute::OS_ROOT_USER]))
			self::$app_config[AppConfigAttribute::OS_ROOT_USER] = (isset($_SERVER['USER']) ? $_SERVER['USER'] : 'root');
			
		if (!isset(self::$app_config[AppConfigAttribute::OS_APACHE_USER]))
			self::$app_config[AppConfigAttribute::OS_APACHE_USER] = 'apache';
			
		if (!isset(self::$app_config[AppConfigAttribute::OS_KALTURA_USER]))
			self::$app_config[AppConfigAttribute::OS_KALTURA_USER] = 'kaltura';
			
		if (!isset(self::$app_config[AppConfigAttribute::OS_ROOT_GROUP]))
			self::$app_config[AppConfigAttribute::OS_ROOT_GROUP] = self::$app_config[AppConfigAttribute::OS_ROOT_USER];
			
		if (!isset(self::$app_config[AppConfigAttribute::OS_APACHE_GROUP]))
			self::$app_config[AppConfigAttribute::OS_APACHE_GROUP] = self::$app_config[AppConfigAttribute::OS_APACHE_USER];
			
		if (!isset(self::$app_config[AppConfigAttribute::OS_KALTURA_GROUP]))
			self::$app_config[AppConfigAttribute::OS_KALTURA_GROUP] = self::$app_config[AppConfigAttribute::OS_KALTURA_USER];
	}
	
	public static function definePostInstallationConfigurationTokens()
	{
		self::$app_config[AppConfigAttribute::POST_INST_VIRTUAL_HOST_NAME] = self::removeHttp(self::$app_config[AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME]);
		self::$app_config[AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME] = self::$app_config[AppConfigAttribute::POST_INST_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::DELIVERY_HTTP_BASE_URL] = self::$app_config[AppConfigAttribute::ENVIRONMENT_PROTOCOL].'://'.self::$app_config[AppConfigAttribute::POST_INST_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::DELIVERY_ISS_BASE_URL] = self::$app_config[AppConfigAttribute::ENVIRONMENT_PROTOCOL].'://'.self::$app_config[AppConfigAttribute::POST_INST_VIRTUAL_HOST_NAME];
		self::$app_config[AppConfigAttribute::DELIVERY_RTMP_BASE_URL] = self::$app_config[AppConfigAttribute::POST_INST_VIRTUAL_HOST_NAME];
		
		self::$app_config[AppConfigAttribute::POST_INST_ADMIN_CONSOLE_ADMIN_MAIL] = self::$app_config[AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL];		
	}

	// copies DB parametes from one DB configuration to another
	private static function collectDatabaseCopier($from_db, $to_db) {
		self::$app_config[$to_db.'_HOST'] = self::$app_config[$from_db.'_HOST'];
		self::$app_config[$to_db.'_PORT'] = self::$app_config[$from_db.'_PORT'];
		self::$app_config[$to_db.'_NAME'] = self::$app_config[$from_db.'_NAME'];
		self::$app_config[$to_db.'_USER'] = self::$app_config[$from_db.'_USER'];
		self::$app_config[$to_db.'_PASS'] = self::$app_config[$from_db.'_PASS'];
	}
		
	// generates a secret for Kaltura and returns it
	private static function generateSecret() {
		logMessage(L_INFO, "Generating secret");
		$secret = md5(self::makeRandomString(5,10,true, false, true));
		return $secret;
	}
	
	/**
	 * Generates sha1 and salt from a password
	 * @param string $password chosen password
	 * @param string $salt salt will be generated
	 * @param string $sha1 sha1 will be generated
	 * @return $sha1 & $salt by reference
	 */
	public static function generateSha1Salt($password, &$salt, &$sha1) {
		logMessage(L_INFO, "Generating sh1 and salf from password");
		$salt = md5(rand(100000, 999999).$password); 
		$sha1 = sha1($salt.$password);  
	}
	
	// puts a Kaltura CE activation key
	public static function simMafteach() {
		$admin_email = self::$app_config[AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL]; 
		$kConfLocalFile = self::$app_config[AppConfigAttribute::APP_DIR].KCONF_LOCAL_LOCATION;
		logMessage(L_INFO, "Setting application key");
		$token = md5(uniqid(rand(), true));
		$str = implode("|", array(md5($admin_email), '1', 'never', $token));
		$key = base64_encode($str);
		$data = @file_get_contents($kConfLocalFile);
		$key_line = '/kaltura_activation_key(\s)*=(\s)*(.+)/';
		$replacement = 'kaltura_activation_key = "' . $key . '"';
		$data = preg_replace($key_line, $replacement ,$data);
		@file_put_contents($kConfLocalFile, $data);
	}
	
	// removes http:// or https:// prefix from the string and returns it
	private static function removeHttp($url = '') {
		$list = array('http://', 'https://');
		foreach ($list as $item) {
			if (strncasecmp($url, $item, strlen($item)) == 0)
				return substr($url, strlen($item));
		}
		return $url;
	}
	
	// checks if the given file is a template file and if so copies it to a non template file
	// returns the non template file if it was copied or the original file if it was not copied
	private static function copyTemplateFileIfNeeded($file) {
		$return_file = $file;
		// Replacement in a template file, first copy to a non .template file
		if (strpos($file, TEMPLATE_FILE) !== false) {
			$return_file = str_replace(TEMPLATE_FILE, "", $file);
			logMessage(L_INFO, "$file token file contains ".TEMPLATE_FILE);
			OsUtils::fullCopy($file, $return_file);
		}
		return $return_file;
	}
	
	// creates a random key used to generate a secret
	private static function makeRandomString($minlength, $maxlength, $useupper, $usespecial, $usenumbers) {
		$charset = "abcdefghijklmnopqrstuvwxyz";
		if ($useupper) $charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if ($usenumbers) $charset .= "0123456789";
		if ($usespecial) $charset .= "~@#$%^*()_+-={}|]["; // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";
		if ($minlength > $maxlength) $length = mt_rand ($maxlength, $minlength);
		else $length = mt_rand ($minlength, $maxlength);
		$key = "";
		for ($i=0; $i<$length; $i++) $key .= $charset[(mt_rand(0,(strlen($charset)-1)))];
		return $key;
	}	
	
	private static function extractHostName($url)
	{
		if (strpos($url, ":"))
		{
			return parse_url($url, PHP_URL_HOST);
		}
		
		return $url;
	}
}