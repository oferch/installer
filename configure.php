<?php
define('APPLICATION_DIR', realpath(dirname(__FILE__) . '/../app'));
define("FILE_CONFIG", "configurator/configuration.ini");
define('SECRET_REPLACE_SCRIPT', APPLICATION_DIR . '/infra/general/secret_replace.php'); 
include_once('installer/DatabaseUtils.class.php');
include_once('installer/UserInput.class.php');
include_once('installer/Log.php');
include_once('installer/AppConfig.class.php');
include_once('installer/InputValidator.class.php');
include_once('installer/OsUtils.class.php');

define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");

$version = parse_ini_file('../package/version.ini');
$type = $version['type'];

//start user interaction
@system('clear');
echo PHP_EOL;

if (strcasecmp($type, K_TM_TYPE) !== 0) {
	$hello_message = "Thank you for installing Kaltura Video Platform - Community Edition";
	$fail_action = "For assistance, please upload the installation log file to the Kaltura CE forum at kaltura.org";
} else {
	$hello_message = "Thank you for installing Kaltura Video Platform";
	$fail_action = "For assistance, please contant the support team at support@kaltura.com with the installation log attached";
}

$user = new UserInput();

startLog("configure_log_".date("d.m.Y_H.i.s"));
logMessage(L_INFO, "Configuration started");
logMessage(L_USER, $hello_message);
if ($result = ((strcasecmp($type, K_TM_TYPE) == 0) || 
	($user->getTrueFalse('ASK_TO_REPORT', "In order to improve Kaltura Community Edition, we would like your permission to send system data to Kaltura.\nThis information will be used exclusively for improving our software and our service quality. I agree", 'y')))) {
		
	$report_message = "If you wish, please provide your email address so that we can offer you future assistance (leave empty to pass)";
	$report_error_message = "Email must be in a valid email format";
	$report_validator = InputValidator::createEmailValidator(true);		
		
	$email = $user->getInput(AppConfigAttribute::REPORT_ADMIN_EMAIL, $report_message, $report_error_message, $report_validator, null);
	AppConfig::set(AppConfigAttribute::REPORT_ADMIN_EMAIL, $email);
	AppConfig::set(AppConfigAttribute::TRACK_KDPWRAPPER,'true');
	AppConfig::set(AppConfigAttribute::USAGE_TRACKING_OPTIN,'true');	
} else {
	AppConfig::set(AppConfigAttribute::REPORT_ADMIN_EMAIL, "");
	AppConfig::set(AppConfigAttribute::TRACK_KDPWRAPPER,'false');
	AppConfig::set(AppConfigAttribute::USAGE_TRACKING_OPTIN,'false');
}

$host_name = $user->getInput(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME, 
						"Please enter the domain name/virtual hostname that will be used for the Kaltura server (without http://)", 
						'Must be a valid hostname or ip, please enter again', 
						InputValidator::createHostValidator(), 
						null);
AppConfig::set(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME, $host_name);
						
$admin_email = $user->getInput(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL, 
						"Your primary system administrator email address", 
						"Email must be in a valid email format, please enter again", 
						InputValidator::createEmailValidator(false), 
						null);
AppConfig::set(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL, $admin_email);

$password = $user->getInput(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD, 
						"The password you want to set for your primary administrator", 
						"Password should not be empty and should not contain whitespaces, please enter again", 
						InputValidator::createNoWhitespaceValidator(), 
						null, 
						true);
AppConfig::set(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD, $password);						
						
$install_config = parse_ini_file(FILE_CONFIG, true);

AppConfig::definePostInstallationConfigurationTokens();
foreach ($install_config['token_files'] as $tokenFile) 
{
	$files = glob(AppConfig::replaceTokensInString($tokenFile));
	foreach($files as $file)
	{
		if (!AppConfig::replaceTokensInFile($file))
			return "Failed to replace tokens in $file";
	}
}

$db_params = array();
$db_params['db_host'] = 'localhost';
$db_params['db_port'] = '3306';
$db_params['db_user'] = 'root';
$db_params['db_pass'] = null;

$sql_file = dirname(__FILE__).'/configurator/kaltura_configure_data.sql';
if (!DatabaseUtils::runScript($sql_file, $db_params, 'kaltura')) {
	echo "Failed running database script $sql_file";
}

if (strcasecmp($type, K_TM_TYPE) !== 0) {
	AppConfig::set(AppConfigAttribute::APP_DIR, APPLICATION_DIR);
	AppConfig::simMafteach();
	require_once(SECRET_REPLACE_SCRIPT);
}


echo PHP_EOL;
logMessage(L_USER, sprintf(
	"Configuration Completed Successfully.\nYour Kaltura Admin Console credentials:\n" . 
	"\tSystem Admin user: %s\n" . 
	"\tSystem Admin password: %s\n\n" . 
	"Please keep this information for future use.\n\n",
 
	AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL), 
	AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD)
));

$virtualHostName = AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME);
$appDir = realpath(AppConfig::get(AppConfigAttribute::APP_DIR));

logMessage(L_USER, 
	"To start using Kaltura, please complete the following steps:\n" .
	"1. Add the following line to your /etc/hosts file:\n" .
		"\t127.0.0.1 $virtualHostName\n" .
	"2. Locate your Apache conf.d directory (usually found under /etc/httpd/conf.d) and create there a symlink to $appDir/configurations/apache/kaltura.conf:\n" .
		"\tln -s $appDir/app/configurations/apache/kaltura.conf /etc/httpd/conf.d/kaltura.conf\n" . 
	"3. Locate your Log-Rotate conf.d directory (usually found under /etc/logrotate.d) and create there a symlink to $appDir/configurations/logrotate:\n" .
		"\tln -s $appDir/app/configurations/logrotate/kaltura_api /etc/logrotate.d/kaltura_api\n" .
		"\tln -s $appDir/app/configurations/logrotate/kaltura_apps /etc/logrotate.d/kaltura_apps\n" .
		"\tln -s $appDir/app/configurations/logrotate/kaltura_batch /etc/logrotate.d/kaltura_batch\n" .
		"\tln -s $appDir/app/configurations/logrotate/kaltura_cron /etc/logrotate.d/kaltura_cron\n" .
	"4. Restart apache by: \"/etc/init.d/httpd restart\"\n" .
	"5. Browse to your Kaltura start page at: http://$virtualHostName/start\n"
);

die(0);

	

