<?php

define('TOKEN_CHAR', '@'); // this character is user to surround parameters that should be replaced with configurations in config files
define('TEMPLATE_FILE', '.template'); // how to recognize a template file, template files are copyed to non-template and then the tokens are replaced
define('KCONF_LOCAL_LOCATION', '/configurations/local.ini'); // the location of kConf


class AppConfigAttribute
{
	const BASE_DIR = 'BASE_DIR';
	const APP_DIR = 'APP_DIR';
	const WEB_DIR = 'WEB_DIR';
	const BIN_DIR = 'BIN_DIR';
	const LOG_DIR = 'LOG_DIR';
	const TMP_DIR = 'TMP_DIR';
	const DWH_DIR = 'DWH_DIR';
	const ETL_HOME_DIR = 'ETL_HOME_DIR';

	const PHP_BIN = 'PHP_BIN';
	const HTTPD_BIN = 'HTTPD_BIN';
	const LOG_ROTATE_BIN = 'LOG_ROTATE_BIN';
	const IMAGE_MAGICK_BIN_DIR = 'IMAGE_MAGICK_BIN_DIR';
	const CURL_BIN_DIR = 'CURL_BIN_DIR';
	const SPHINX_BIN_DIR = 'SPHINX_BIN_DIR';

	const OS_ROOT_USER = 'OS_ROOT_USER';
	const OS_APACHE_USER = 'OS_APACHE_USER';
	const OS_KALTURA_USER = 'OS_KALTURA_USER';

	const OS_ROOT_GROUP = 'OS_ROOT_GROUP';
	const OS_APACHE_GROUP = 'OS_APACHE_GROUP';
	const OS_KALTURA_GROUP = 'OS_KALTURA_GROUP';

	const DB_ROOT_USER = 'DB_ROOT_USER';
	const DB_ROOT_PASS = 'DB_ROOT_PASS';

	const DB1_HOST = 'DB1_HOST';
	const DB1_PORT = 'DB1_PORT';
	const DB1_USER = 'DB1_USER';
	const DB1_PASS = 'DB1_PASS';
	const DB1_NAME = 'DB1_NAME';

	const DB2_HOST = 'DB2_HOST';
	const DB2_PORT = 'DB2_PORT';
	const DB2_USER = 'DB2_USER';
	const DB2_PASS = 'DB2_PASS';
	const DB2_NAME = 'DB2_NAME';

	const DB3_HOST = 'DB3_HOST';
	const DB3_PORT = 'DB3_PORT';
	const DB3_USER = 'DB3_USER';
	const DB3_PASS = 'DB3_PASS';
	const DB3_NAME = 'DB3_NAME';

	const SPHINX_SERVER = 'SPHINX_SERVER';
	const SPHINX_DB_HOST = 'SPHINX_DB_HOST';
	const SPHINX_DB_PORT = 'SPHINX_DB_PORT';
	const SPHINX_DB_NAME = 'SPHINX_DB_NAME';

	const DWH_HOST = 'DWH_HOST';
	const DWH_PORT = 'DWH_PORT';
	const DWH_USER = 'DWH_USER';
	const DWH_PASS = 'DWH_PASS';
	const DWH_DATABASE_NAME = 'DWH_DATABASE_NAME';

	const DB1_CREATE_NEW_DB = 'DB1_CREATE_NEW_DB';

	const EVENTS_LOGS_DIR = 'EVENTS_LOGS_DIR';
	const EVENTS_WILDCARD = 'EVENTS_WILDCARD';
	const EVENTS_FETCH_METHOD = 'EVENTS_FETCH_METHOD';
	const ADMIN_CONSOLE_ADMIN_MAIL = 'ADMIN_CONSOLE_ADMIN_MAIL';
	const ADMIN_CONSOLE_PASSWORD = 'ADMIN_CONSOLE_PASSWORD';
	const REPORT_ADMIN_EMAIL = 'REPORT_ADMIN_EMAIL';

	const BATCH_PARTNER_ADMIN_SECRET = 'BATCH_PARTNER_ADMIN_SECRET';
	const PARTNER_ZERO_ADMIN_SECRET = 'PARTNER_ZERO_ADMIN_SECRET';
	const ADMIN_CONSOLE_PARTNER_ADMIN_SECRET = 'ADMIN_CONSOLE_PARTNER_ADMIN_SECRET';
	const HOSTED_PAGES_PARTNER_ADMIN_SECRET = 'HOSTED_PAGES_PARTNER_ADMIN_SECRET';
	const TEMPLATE_PARTNER_ADMIN_SECRET = 'TEMPLATE_PARTNER_ADMIN_SECRET';

	const PARTNERS_USAGE_REPORT_SEND_FROM = 'PARTNERS_USAGE_REPORT_SEND_FROM';
	const PARTNERS_USAGE_REPORT_SEND_TO = 'PARTNERS_USAGE_REPORT_SEND_TO';
	const DC0_SECRET = 'DC0_SECRET';
	const STORAGE_BASE_DIR = 'STORAGE_BASE_DIR';
	const DELIVERY_HTTP_BASE_URL = 'DELIVERY_HTTP_BASE_URL';
	const DELIVERY_RTMP_BASE_URL = 'DELIVERY_RTMP_BASE_URL';
	const DELIVERY_ISS_BASE_URL = 'DELIVERY_ISS_BASE_URL';

	const PROVISION_PROVIDE_USER = 'PROVISION_PROVIDE_USER';
	const PROVISION_PROVIDE_PASS = 'PROVISION_PROVIDE_PASS';
	const PROVISION_PROVIDE_CPCODE = 'PROVISION_PROVIDE_CPCODE';
	const PROVISION_PROVIDE_EMAIL_ID = 'PROVISION_PROVIDE_EMAIL_ID';
	const PROVISION_PROVIDE_PRIMARY_CONTACT = 'PROVISION_PROVIDE_PRIMARY_CONTACT';
	const PROVISION_PROVIDE_SECONDARY_CONTACT = 'PROVISION_PROVIDE_SECONDARY_CONTACT';

	const SERVICE_URL = 'SERVICE_URL';
	const TIME_ZONE = 'TIME_ZONE';
	const CDN_HOST = 'CDN_HOST';
	const IIS_HOST = 'IIS_HOST';
	const WWW_HOST = 'WWW_HOST';
	const RTMP_URL = 'RTMP_URL';
	const CORP_REDIRECT = 'CORP_REDIRECT';
	const MEMCACHE_HOST = 'MEMCACHE_HOST';
	const GLOBAL_MEMCACHE_HOST = 'GLOBAL_MEMCACHE_HOST';
	const KALTURA_VIRTUAL_HOST_NAME = 'KALTURA_VIRTUAL_HOST_NAME';
	const KALTURA_VIRTUAL_HOST_PORT = 'KALTURA_VIRTUAL_HOST_PORT';
	const KALTURA_FULL_VIRTUAL_HOST_NAME = 'KALTURA_FULL_VIRTUAL_HOST_NAME';
	const POST_INST_VIRTUAL_HOST_NAME = 'POST_INST_VIRTUAL_HOST_NAME';
	const POST_INST_ADMIN_CONSOLE_ADMIN_MAIL = 'POST_INST_ADMIN_CONSOLE_ADMIN_MAIL';
	const ENVIRONMENT_NAME = 'ENVIRONMENT_NAME';
	const BATCH_HOST_NAME = 'BATCH_HOST_NAME';
	const BATCH_PARTNER_PARTNER_ALIAS = 'BATCH_PARTNER_PARTNER_ALIAS';
	const APACHE_RESTART_COMMAND = 'APACHE_RESTART_COMMAND';
	const BASE_HOST_NO_PORT = 'BASE_HOST_NO_PORT';
	const ENVIRONMENT_PROTOCOL = 'ENVIRONMENT_PROTOCOL';

	const CONTACT_URL = 'CONTACT_URL';
	const SIGNUP_URL = 'SIGNUP_URL';
	const CONTACT_PHONE_NUMBER = 'CONTACT_PHONE_NUMBER';
	const BEGINNERS_TUTORIAL_URL = 'BEGINNERS_TUTORIAL_URL';
	const QUICK_START_GUIDE_URL = 'QUICK_START_GUIDE_URL';
	const FORUMS_URLS = 'FORUMS_URLS';
	const UNSUBSCRIBE_EMAIL_URL = 'UNSUBSCRIBE_EMAIL_URL';
	const GOOGLE_ANALYTICS_ACCOUNT = 'GOOGLE_ANALYTICS_ACCOUNT';
	const INSTALLATION_TYPE = 'INSTALLATION_TYPE';
	const INSTALLATION_UID = 'INSTALLATION_UID';
	const INSTALLATION_SEQUENCE_UID = 'INSTALLATION_SEQUENCE_UID';

	const KALTURA_VERSION_TYPE = 'KALTURA_VERSION_TYPE';
	const KALTURA_VERSION = 'KALTURA_VERSION';

	const UICONF_TAB_ACCESS = 'UICONF_TAB_ACCESS';

	const REPLACE_PASSWORDS = 'REPLACE_PASSWORDS';
	const TRACK_KDPWRAPPER = 'TRACK_KDPWRAPPER';
	const USAGE_TRACKING_OPTIN = 'USAGE_TRACKING_OPTIN';

	const KMC_VERSION = 'KMC_VERSION';
	const CLIPAPP_VERSION = 'CLIPAPP_VERSION';
	const HTML5_VERSION = 'HTML5_VERSION';
}

/**
 * This class handles all the configuration of the application:
 * Defining application configuration values according to user input,
 * replaceing configuration tokens in needed files and other application configuration actions
 */
class AppConfig
{
	const K_TM_TYPE = 'TM';
	const K_CE_TYPE = 'CE';

	/**
	 * @var string
	 */
	private static $hostname = null;

	/**
	 * @var array
	 */
	private static $config = array();

	/**
	 * Configuration file path, used as configuration for installer sub-processes
	 * @var string
	 */
	private static $tempFilePath = null;

	/**
	 * Configuration file path, as supplied to the installer, the wizard output will be saved to the same path for future installtaions
	 * @var string
	 */
	private static $inputFilePath = null;

	/**
	 * @var array
	 */
	private static $kConf = null;

	/**
	 * @var string
	 */
	private static $packageDir = null;

	/**
	 * Initialize all configuration variables from ini file or from wizard
	 */
	public static function init($packageDir)
	{
		self::$packageDir = $packageDir;
		if(OsUtils::getOsName() == OsUtils::LINUX_OS)
			system('clear');

		self::$inputFilePath = realpath(__DIR__ . '/../') . '/user_input.ini';
		if(file_exists(self::$inputFilePath) && self::getTrueFalse(null, "Installation configuration has been detected, do you want to use it?", 'y'))
			self::$config = parse_ini_file(self::$inputFilePath, true);
	}

	/**
	 * Initialize all configuration variables from ini file or from wizard
	 */
	public static function configure()
	{
		$hostname = self::getHostname();

		self::getInput(AppConfigAttribute::TIME_ZONE, "Default time zone for Kaltura application (leave empty to use system timezone: " . date_default_timezone_get() . " )", "Timezone must be a valid timezone, please enter again", InputValidator::createTimezoneValidator(), date_default_timezone_get());

		self::getInput(AppConfigAttribute::BASE_DIR, "Full target directory path for Kaltura application (leave empty for /opt/kaltura)", "Target directory must be a valid directory path, please enter again", InputValidator::createDirectoryValidator(), '/opt/kaltura');

		self::getInput(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME, "Please enter the domain name/virtual hostname that will be used for the Kaltura server (without http://)", 'Must be a valid hostname or ip, please enter again', InputValidator::createHostValidator(), null);

		self::getInput(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL, "Your primary system administrator email address", "Email must be in a valid email format, please enter again", InputValidator::createEmailValidator(false), null);

		self::getInput(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD, "The password you want to set for your primary administrator", "Password should not be empty and should not contain whitespaces, please enter again", InputValidator::createNoWhitespaceValidator(), null, true);

		self::getInput(AppConfigAttribute::DB1_HOST, "Database host (leave empty for 'localhost')", "Must be a valid hostname or ip, please enter again (leave empty for 'localhost')", InputValidator::createHostValidator(), $hostname);

		self::getInput(AppConfigAttribute::DB1_PORT, "Database port (leave empty for '3306')", "Must be a valid port (1-65535), please enter again (leave empty for '3306')", InputValidator::createRangeValidator(1, 65535), '3306');

		self::set(AppConfigAttribute::DB1_NAME, 'kaltura'); // currently we do not support getting the DB name from the user because of the DWH implementation
		self::getInput(AppConfigAttribute::DB_ROOT_USER, "Database username (with create & write privileges)", "Database username cannot be empty, please enter again", InputValidator::createNonEmptyValidator(), 'root');

		self::getInput(AppConfigAttribute::DB_ROOT_PASS, "Database password (leave empty for no password)", null, null, 'root');

		self::getTrueFalse(AppConfigAttribute::DB1_CREATE_NEW_DB, "Would you like to create a new kaltura database or use an exisiting one?", 'y');

		self::getInput(AppConfigAttribute::SPHINX_DB_HOST, "Sphinx host (leave empty if Sphinx is running on this machine).", null, InputValidator::createHostValidator(), ($hostname == 'localhost' ? '127.0.0.1' : $hostname));

		self::getInput(AppConfigAttribute::ENVIRONMENT_PROTOCOL, "Environment protocol - enter http/https", null, null, 'http');

		OsUtils::writeConfigToFile(self::$config, self::$inputFilePath);

		self::set(AppConfigAttribute::INSTALLATION_UID, uniqid("IID")); // unique id per installation

		$versionPath = self::$packageDir . "/version.ini";
		if(file_exists($versionPath))
		{
			$version = parse_ini_file($versionPath);
			self::set(AppConfigAttribute::KALTURA_VERSION, 'Kaltura ' . $version['type'] . ' ' . $version['number']);
			self::set(AppConfigAttribute::KALTURA_VERSION_TYPE, $version['type']);
		}

		// set to replace passwords on first activiation if this installation is preinstalled
		self::initField(AppConfigAttribute::REPLACE_PASSWORDS, 'false');

		// load or create installation sequence id
		$installSeqFilePath = __DIR__ . '/../install_seq';
		if(file_exists($installSeqFilePath) && is_file($installSeqFilePath))
		{
			self::set(AppConfigAttribute::INSTALLATION_SEQUENCE_UID, file_get_contents($installSeqFilePath));
		}
		else
		{
			$install_seq = uniqid("ISEQID"); // unique id per a set of installations
			self::set(AppConfigAttribute::INSTALLATION_SEQUENCE_UID, $install_seq);
			file_put_contents($installSeqFilePath, $install_seq);
		}

		self::set(AppConfigAttribute::KMC_VERSION, self::getServerConfig('kmc_version'));
		self::set(AppConfigAttribute::CLIPAPP_VERSION, self::getServerConfig('clipapp_version'));
		self::set(AppConfigAttribute::HTML5_VERSION, self::getServerConfig('html5_version'));

		// allow ui conf tab only for CE installation
		if (self::get(AppConfigAttribute::KALTURA_VERSION_TYPE) == AppConfig::K_TM_TYPE)
			self::set(AppConfigAttribute::UICONF_TAB_ACCESS, 'SYSTEM_ADMIN_BATCH_CONTROL');

		// directories
		self::initField(AppConfigAttribute::APP_DIR, self::get(AppConfigAttribute::BASE_DIR) . '/app');
		self::initField(AppConfigAttribute::WEB_DIR, self::get(AppConfigAttribute::BASE_DIR) . '/web');
		self::initField(AppConfigAttribute::LOG_DIR, self::get(AppConfigAttribute::BASE_DIR) . '/log');
		self::initField(AppConfigAttribute::BIN_DIR, self::get(AppConfigAttribute::BASE_DIR) . '/bin');
		self::initField(AppConfigAttribute::TMP_DIR, self::get(AppConfigAttribute::BASE_DIR) . '/tmp/');
		self::initField(AppConfigAttribute::DWH_DIR, self::get(AppConfigAttribute::BASE_DIR) . '/dwh');
		self::initField(AppConfigAttribute::ETL_HOME_DIR, self::get(AppConfigAttribute::BASE_DIR) . '/dwh'); // For backward compatibility
		self::initField(AppConfigAttribute::SPHINX_BIN_DIR, self::get(AppConfigAttribute::BIN_DIR) . '/sphinx');

		self::initField(AppConfigAttribute::IMAGE_MAGICK_BIN_DIR, "/usr/bin");
		self::initField(AppConfigAttribute::CURL_BIN_DIR, "/usr/bin");

		// site settings
		self::initField(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT, 80);
		if(strpos(self::get(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME), ":") !== false)
			self::initField(AppConfigAttribute::KALTURA_VIRTUAL_HOST_PORT, parse_url(self::get(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME), PHP_URL_PORT));

		self::initField(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME, preg_replace('/^https?:\/\//', '', self::get(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME)));
		self::initField(AppConfigAttribute::CORP_REDIRECT, '');
		self::initField(AppConfigAttribute::CDN_HOST, self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME));
		self::initField(AppConfigAttribute::IIS_HOST, self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME));
		self::initField(AppConfigAttribute::RTMP_URL, 'rtmp://' . self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME) . '/oflaDemo');
		self::initField(AppConfigAttribute::BASE_HOST_NO_PORT, self::extractHostName(self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME)));
		self::initField(AppConfigAttribute::MEMCACHE_HOST, self::extractHostName(self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME)));
		self::initField(AppConfigAttribute::GLOBAL_MEMCACHE_HOST, self::extractHostName(self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME)));
		self::initField(AppConfigAttribute::WWW_HOST, self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME));
		self::initField(AppConfigAttribute::SERVICE_URL, self::get(AppConfigAttribute::ENVIRONMENT_PROTOCOL) . '://' . self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME));
		self::initField(AppConfigAttribute::ENVIRONMENT_NAME, self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME));

		// databases (copy information collected during prerequisites
		if(self::get(AppConfigAttribute::DB1_HOST) == 'localhost')
			self::set(AppConfigAttribute::DB1_HOST, '127.0.0.1');

		self::initField(AppConfigAttribute::DB1_USER, 'kaltura');
		self::initField(AppConfigAttribute::DB1_PASS, 'kaltura');
		self::initField(AppConfigAttribute::DWH_USER, 'kaltura_etl');
		self::initField(AppConfigAttribute::DWH_PASS, 'kaltura_etl');

		self::initField(AppConfigAttribute::DB2_HOST, self::get(AppConfigAttribute::DB1_HOST));
		self::initField(AppConfigAttribute::DB2_PORT, self::get(AppConfigAttribute::DB1_PORT));
		self::initField(AppConfigAttribute::DB2_NAME, self::get(AppConfigAttribute::DB1_NAME));
		self::initField(AppConfigAttribute::DB2_USER, self::get(AppConfigAttribute::DB1_USER));
		self::initField(AppConfigAttribute::DB2_PASS, self::get(AppConfigAttribute::DB1_PASS));

		self::initField(AppConfigAttribute::DB3_HOST, self::get(AppConfigAttribute::DB1_HOST));
		self::initField(AppConfigAttribute::DB3_PORT, self::get(AppConfigAttribute::DB1_PORT));
		self::initField(AppConfigAttribute::DB3_NAME, self::get(AppConfigAttribute::DB1_NAME));
		self::initField(AppConfigAttribute::DB3_USER, self::get(AppConfigAttribute::DB1_USER));
		self::initField(AppConfigAttribute::DB3_PASS, self::get(AppConfigAttribute::DB1_PASS));

		//sphinx
		self::initField(AppConfigAttribute::SPHINX_SERVER, self::get(AppConfigAttribute::DB1_HOST));
		self::initField(AppConfigAttribute::SPHINX_DB_NAME, 'kaltura_sphinx_log');
		self::initField(AppConfigAttribute::SPHINX_DB_HOST, self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME));
		self::initField(AppConfigAttribute::SPHINX_DB_PORT, self::get(AppConfigAttribute::DB1_PORT));

		// admin console defaults
		self::initField(AppConfigAttribute::UICONF_TAB_ACCESS, 'SYSTEM_ADMIN_BATCH_CONTROL');

		// data warehouse
		self::initField(AppConfigAttribute::DWH_HOST, self::get(AppConfigAttribute::DB1_HOST));
		self::initField(AppConfigAttribute::DWH_PORT, self::get(AppConfigAttribute::DB1_PORT));
		self::initField(AppConfigAttribute::DWH_DATABASE_NAME, 'kalturadw');
		self::initField(AppConfigAttribute::EVENTS_LOGS_DIR, self::get(AppConfigAttribute::LOG_DIR));
		self::initField(AppConfigAttribute::EVENTS_WILDCARD, '.*kaltura.*_apache_access.log-.*');
		self::initField(AppConfigAttribute::EVENTS_FETCH_METHOD, 'local');

		// batch
		self::initField(AppConfigAttribute::BATCH_HOST_NAME, OsUtils::getComputerName());
		self::initField(AppConfigAttribute::BATCH_PARTNER_PARTNER_ALIAS, md5('-1kaltura partner'));

		// other configurations
		self::initField(AppConfigAttribute::HTTPD_BIN, OsUtils::findBinary(array('apachectl', 'apache2ctl')));
		self::initField(AppConfigAttribute::PHP_BIN, OsUtils::findBinary('php'));
		self::initField(AppConfigAttribute::LOG_ROTATE_BIN, OsUtils::findBinary('logrotate'));

		// other configurations
		self::initField(AppConfigAttribute::APACHE_RESTART_COMMAND, self::get(AppConfigAttribute::HTTPD_BIN) . ' -k restart');
		date_default_timezone_set(self::get(AppConfigAttribute::TIME_ZONE));
		self::initField(AppConfigAttribute::GOOGLE_ANALYTICS_ACCOUNT, 'UA-7714780-1');
		self::initField(AppConfigAttribute::INSTALLATION_TYPE, '');
		self::initField(AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_FROM, '');
		self::initField(AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_TO, '');
		self::initField(AppConfigAttribute::DC0_SECRET, '');

		// storage profile related
		self::initField(AppConfigAttribute::STORAGE_BASE_DIR, self::get(AppConfigAttribute::WEB_DIR));
		self::initField(AppConfigAttribute::DELIVERY_HTTP_BASE_URL, self::get(AppConfigAttribute::SERVICE_URL));
		self::initField(AppConfigAttribute::DELIVERY_RTMP_BASE_URL, self::get(AppConfigAttribute::RTMP_URL));
		self::initField(AppConfigAttribute::DELIVERY_ISS_BASE_URL, self::get(AppConfigAttribute::SERVICE_URL));
		self::initField(AppConfigAttribute::ENVIRONMENT_NAME, self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME));

		// set the usage tracking for Kaltura TM
		if(self::get(AppConfigAttribute::KALTURA_VERSION_TYPE) == AppConfig::K_TM_TYPE)
		{
			self::initField(AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_FROM, self::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL));
			self::initField(AppConfigAttribute::PARTNERS_USAGE_REPORT_SEND_TO, "on-prem-monthly@kaltura.com");
		}

		// mails configurations
		self::initField(AppConfigAttribute::FORUMS_URLS, 'http://www.kaltura.org/forum');
		self::initField(AppConfigAttribute::CONTACT_URL, 'http://corp.kaltura.com/contact');
		self::initField(AppConfigAttribute::CONTACT_PHONE_NUMBER, '+1 800 871-5224');
		self::initField(AppConfigAttribute::BEGINNERS_TUTORIAL_URL, 'http://corp.kaltura.com/about/dosignup');
		self::initField(AppConfigAttribute::QUICK_START_GUIDE_URL, self::get(AppConfigAttribute::ENVIRONMENT_PROTOCOL) . '://' . self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME) . '/content/docs/KMC_Quick_Start_Guide.pdf');
		self::initField(AppConfigAttribute::UNSUBSCRIBE_EMAIL_URL, '"' . self::get(AppConfigAttribute::ENVIRONMENT_PROTOCOL) . '://' . self::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME) . '/index.php/extwidget/blockMail?e="');

		if(self::get(AppConfigAttribute::DB1_CREATE_NEW_DB))
		{
			self::initField(AppConfigAttribute::PARTNER_ZERO_ADMIN_SECRET, self::generateSecret());
			self::initField(AppConfigAttribute::BATCH_PARTNER_ADMIN_SECRET, self::generateSecret());
			self::initField(AppConfigAttribute::ADMIN_CONSOLE_PARTNER_ADMIN_SECRET, self::generateSecret());
			self::initField(AppConfigAttribute::HOSTED_PAGES_PARTNER_ADMIN_SECRET, self::generateSecret());
			self::initField(AppConfigAttribute::TEMPLATE_PARTNER_ADMIN_SECRET, self::generateSecret());
		}
		else
		{
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=0" | mysql -h' . self::get(AppConfigAttribute::DB1_HOST) . ' -P' . self::get(AppConfigAttribute::DB1_PORT) . ' -u' . self::get(AppConfigAttribute::DB1_USER) . ' -p' . self::get(AppConfigAttribute::DB1_PASS) . ' ' . self::get(AppConfigAttribute::DB1_NAME) . ' --skip-column-names');
			self::initField(AppConfigAttribute::PARTNER_ZERO_ADMIN_SECRET, $output[0]);
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=-1" | mysql -h' . self::get(AppConfigAttribute::DB1_HOST) . ' -P' . self::get(AppConfigAttribute::DB1_PORT) . ' -u' . self::get(AppConfigAttribute::DB1_USER) . ' -p' . self::get(AppConfigAttribute::DB1_PASS) . ' ' . self::get(AppConfigAttribute::DB1_NAME) . ' --skip-column-names');
			self::initField(AppConfigAttribute::BATCH_PARTNER_ADMIN_SECRET, $output[0]);
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=-2" | mysql -h' . self::get(AppConfigAttribute::DB1_HOST) . ' -P' . self::get(AppConfigAttribute::DB1_PORT) . ' -u' . self::get(AppConfigAttribute::DB1_USER) . ' -p' . self::get(AppConfigAttribute::DB1_PASS) . ' ' . self::get(AppConfigAttribute::DB1_NAME) . ' --skip-column-names');
			self::initField(AppConfigAttribute::ADMIN_CONSOLE_PARTNER_ADMIN_SECRET, $output[0]);
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=-3" | mysql -h' . self::get(AppConfigAttribute::DB1_HOST) . ' -P' . self::get(AppConfigAttribute::DB1_PORT) . ' -u' . self::get(AppConfigAttribute::DB1_USER) . ' -p' . self::get(AppConfigAttribute::DB1_PASS) . ' ' . self::get(AppConfigAttribute::DB1_NAME) . ' --skip-column-names');
			self::initField(AppConfigAttribute::HOSTED_PAGES_PARTNER_ADMIN_SECRET, $output[0]);
			$output = OsUtils::executeReturnOutput('echo "select admin_secret from partner where id=99" | mysql -h' . self::get(AppConfigAttribute::DB1_HOST) . ' -P' . self::get(AppConfigAttribute::DB1_PORT) . ' -u' . self::get(AppConfigAttribute::DB1_USER) . ' -p' . self::get(AppConfigAttribute::DB1_PASS) . ' ' . self::get(AppConfigAttribute::DB1_NAME) . ' --skip-column-names');
			self::initField(AppConfigAttribute::TEMPLATE_PARTNER_ADMIN_SECRET, $output[0]);
		}

		self::initField(AppConfigAttribute::OS_ROOT_USER, (isset($_SERVER['USER']) ? $_SERVER['USER'] : 'root'));
		self::initField(AppConfigAttribute::OS_APACHE_USER, 'apache');
		self::initField(AppConfigAttribute::OS_KALTURA_USER, 'kaltura');
		self::initField(AppConfigAttribute::OS_ROOT_GROUP, self::get(AppConfigAttribute::OS_ROOT_USER));
		self::initField(AppConfigAttribute::OS_APACHE_GROUP, self::get(AppConfigAttribute::OS_APACHE_USER));
		self::initField(AppConfigAttribute::OS_KALTURA_GROUP, self::get(AppConfigAttribute::OS_KALTURA_USER));


		self::initField(AppConfigAttribute::REPORT_ADMIN_EMAIL, '');
		self::initField(AppConfigAttribute::TRACK_KDPWRAPPER, 'false');
		self::initField(AppConfigAttribute::USAGE_TRACKING_OPTIN, 'false');
	}

	private static function getServerConfig($field, $section = null)
	{
		if(!self::$kConf)
		{
			if(!file_exists(self::$packageDir . '/app/configurations/base.ini'))
				return null;

			self::$kConf = parse_ini_file(self::$packageDir . '/app/configurations/base.ini', true);
		}

		$config = self::$kConf;
		if($section)
		{
			if(!isset(self::$kConf[$section]))
				return null;

			$config = self::$kConf[$section];
		}

		if(isset($config[$field]))
			return $config[$field];

		return null;
	}

	private static function getHostname()
	{
		if(self::$hostname)
			return self::$hostname;

		if(isset($_SERVER['HOSTNAME']))
			self::$hostname = $_SERVER['HOSTNAME'];

		if(is_null(self::$hostname))
			self::$hostname = gethostname();

		if(is_null(self::$hostname))
			self::$hostname = $_SERVER['SERVER_NAME'];

		if(is_null(self::$hostname))
			self::$hostname = 'localhost';

		return self::$hostname;
	}

	/**
	 * Sets the application value for the given key, only if it's not set yet
	 * @param string $key
	 * @param string $value
	 * @throws Exception
	 */
	private static function initField($key, $value)
	{
		if(! defined("AppConfigAttribute::$key"))
			throw new Exception("Configuration key [$key] not defined");

		self::$config[$key] = $value;
	}

	/**
	 * Gets the application value set for the given key
	 * @param string $key
	 * @throws Exception
	 * @return string
	 */
	public static function get($key)
	{
		if(! defined("AppConfigAttribute::$key"))
			throw new Exception("Configuration key [$key] not defined");

		return self::$config[$key];
	}

	/**
	 * Sets the application value for the given key
	 * @param string $key
	 * @param string $value
	 * @throws Exception
	 */
	public static function set($key, $value)
	{
		if(! defined("AppConfigAttribute::$key"))
			throw new Exception("Configuration key [$key] not defined");

		self::$config[$key] = $value;
	}

	// replaces all tokens in the given string with the configuration values and returns the new string
	public static function replaceTokensInString($string)
	{
		foreach(self::$config as $key => $var)
		{
			$key = TOKEN_CHAR . $key . TOKEN_CHAR;
			$string = str_replace($key, $var, $string);
		}
		return $string;
	}

	// replaces all the tokens in the given file with the configuration values and returns true/false upon success/failure
	// will override the file if it is not a template file
	// if it is a template file it will save it to a non template file and then override it
	public static function replaceTokensInFile($file)
	{
		logMessage(L_USER, "Replacing configuration tokens in file [$file]");
		$newfile = self::copyTemplateFileIfNeeded($file);
		$data = @file_get_contents($newfile);
		if(! $data)
		{
			logMessage(L_ERROR, "Cannot replace token in file $newfile");
			return false;
		}
		else
		{
			$data = self::replaceTokensInString($data);
			if(! file_put_contents($newfile, $data))
			{
				logMessage(L_ERROR, "Cannot replace token in file, cannot write to file $newfile");
				return false;
			}
			else
			{
				logMessage(L_INFO, "Replaced tokens in file $newfile");
			}
		}
		return true;
	}

	public static function getFilePath()
	{
		if(! self::$tempFilePath)
		{
			self::$tempFilePath = tempnam(sys_get_temp_dir(), 'kaltura.installer.');
			OsUtils::writeConfigToFile(self::$config, self::$tempFilePath);
		}

		return self::$tempFilePath;
	}

	/**
	 * Generates a secret for Kaltura and returns it
	 * @return string
	 */
	private static function generateSecret()
	{
		logMessage(L_INFO, "Generating secret");
		$secret = md5(self::makeRandomString(5, 10, true, false, true));
		return $secret;
	}

	// puts a Kaltura CE activation key
	public static function simMafteach()
	{
		$admin_email = self::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL);
		$kConfLocalFile = self::get(AppConfigAttribute::APP_DIR) . KCONF_LOCAL_LOCATION;
		logMessage(L_INFO, "Setting application key");
		$token = md5(uniqid(rand(), true));
		$str = implode("|", array(md5($admin_email), '1', 'never', $token));
		$key = base64_encode($str);
		$data = @file_get_contents($kConfLocalFile);
		$key_line = '/kaltura_activation_key(\s)*=(\s)*(.+)/';
		$replacement = 'kaltura_activation_key = "' . $key . '"';
		$data = preg_replace($key_line, $replacement, $data);
		@file_put_contents($kConfLocalFile, $data);
	}

	// checks if the given file is a template file and if so copies it to a non template file
	// returns the non template file if it was copied or the original file if it was not copied
	private static function copyTemplateFileIfNeeded($file)
	{
		$return_file = $file;
		// Replacement in a template file, first copy to a non .template file
		if(strpos($file, TEMPLATE_FILE) !== false)
		{
			$return_file = str_replace(TEMPLATE_FILE, "", $file);
			logMessage(L_INFO, "$file token file contains " . TEMPLATE_FILE);
			OsUtils::fullCopy($file, $return_file);
		}
		return $return_file;
	}

	/**
	 * Creates a random key used to generate a secret
	 * @param int $minlength
	 * @param int $maxlength
	 * @param boolean $useupper
	 * @param boolean $usespecial
	 * @param boolean $usenumbers
	 * @return string
	 */
	private static function makeRandomString($minlength, $maxlength, $useupper, $usespecial, $usenumbers)
	{
		$charset = "abcdefghijklmnopqrstuvwxyz";
		if($useupper)
			$charset .= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
		if($usenumbers)
			$charset .= "0123456789";
		if($usespecial)
			$charset .= "~@#$%^*()_+-={}|]["; // Note: using all special characters this reads: "~!@#$%^&*()_+`-={}|\\]?[\":;'><,./";
		if($minlength > $maxlength)
			$length = mt_rand($maxlength, $minlength);
		else
			$length = mt_rand($minlength, $maxlength);
		$key = "";
		for($i = 0; $i < $length; $i ++)
			$key .= $charset[(mt_rand(0, (strlen($charset) - 1)))];
		return $key;
	}

	/**
	 * Extract the host name out of URL
	 * @param string $url
	 * @return string
	 */
	private static function extractHostName($url)
	{
		if(strpos($url, ":"))
			return parse_url($url, PHP_URL_HOST);

		return $url;
	}

	/**
	 * gets input from the user and returns it
	 * if $key was already loaded from config it will be taked from there and user will not have to insert
	 *
	 * @param string $key
	 * @param string $request_text text to show the user
	 * @param string $not_valid_text text to show the user if the input is invalid (according to the validator)
	 * @param InputValidator $validator the input validator to user (default is null, no validation)
	 * @param string $default the default value (default's default is '' :))
	 * @param bool $hideValue do not show the value on the screen, in case it's password for example
	 * @return string
	 */
	public static function getInput($key, $request_text, $not_valid_text, InputValidator $validator = null, $default = '', $hideValue = false)
	{
		if($key && isset(self::$config[$key]))
			return self::$config[$key];

		if(isset($validator) && ! empty($default))
			$validator->emptyIsValid = true;

		logMessage(L_USER, $request_text);

		$inputOk = false;
		while(! $inputOk)
		{
			echo '> ';
			$input = trim(fgets(STDIN));

			if($hideValue)
			{
				logMessage(L_INFO, "User input accepted");
			}
			else
			{
				logMessage(L_INFO, "User input is $input");
			}

			if(isset($validator) && ! $validator->validateInput($input))
			{
				logMessage(L_USER, $not_valid_text);
			}
			else
			{
				$inputOk = true;
				echo PHP_EOL;

				if(empty($input) && ! empty($default))
				{
					$input = $default;
					if($hideValue)
						logMessage(L_INFO, "Using default value");
					else
						logMessage(L_INFO, "Using default value: $default");
				}
			}
		}

		if(isset($key))
			self::$config[$key] = $input;

		return $input;
	}

	/**
	 * Get a y/n input from the user
	 *
	 * @param string $key if already loaded from config it will be taken from there and user will not have to insert
	 * @param string $request_text text to show the user
	 * @param string $default the default value (show be 'y'/'n')
	 * @return boolean
	 */
	public static function getTrueFalse($key, $request_text, $default)
	{
		if($key && isset(self::$config[$key]))
		{
			return self::$config[$key];
		}

		$default = strtolower(trim($default));
		if($default == 'y' || $default == 'yes')
		{
			$request_text .= ' (Y/n)';
		}
		else
		{
			$request_text .= ' (y/N)';
		}

		$validator = InputValidator::createYesNoValidator();
		$input = self::getInput(null, $request_text, "Input must be yes (y) or no (n)", $validator, $default);
		$input = strtolower(trim($input));
		$input = ($input == 'y' || $input == 'yes');

		if(isset($key))
			self::$config[$key] = $input;

		return $input;
	}
}