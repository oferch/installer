<?php
define('APP_DIR', realpath(dirname(__FILE__) . '/../app'));
define("FILE_CONFIG", "configurator/configuration.ini"); 
include_once('installer/DatabaseUtils.class.php');
include_once('installer/UserInput.class.php');
include_once('installer/Log.php');
include_once('installer/AppConfig.class.php');
include_once('installer/InputValidator.class.php');
include_once('installer/OsUtils.class.php');

require_once(APP_DIR . '/alpha/config/kConf.php');

define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");

$package_version = kConf::get("kaltura_version");
$pos = strpos($package_version, K_TM_TYPE);

if ($pos === false) {
    $type = K_CE_TYPE;
} else {
    $type = K_TM_TYPE;
}

//start user interaction
@system('clear');
echo PHP_EOL;

if (strcasecmp($type, K_TM_TYPE) !== 0) {
	$hello_message = "Thank you for installing Kaltura Video Platform - Community Edition";
	$report_message = "If you wish, please provide your email address so that we can offer you future assistance (leave empty to pass)";
	$report_error_message = "Email must be in a valid email format";
	$report_validator = InputValidator::createEmailValidator(true);		
	$fail_action = "For assistance, please upload the installation log file to the Kaltura CE forum at kaltura.org";
} else {
	$hello_message = "Thank you for installing Kaltura Video Platform";
	$report_message = "Please provide the name of your company or organization";
	$report_error_message = "Name cannot be empty";
	$report_validator = InputValidator::createNonEmptyValidator();	
	$fail_action = "For assistance, please contant the support team at support@kaltura.com with the installation log attached";
}

$user = new UserInput();
$app = new AppConfig();

startLog("configure_log_".date("d.m.Y_H.i.s"));
logMessage(L_INFO, "Configuration started");
logMessage(L_USER, $hello_message);
if ($result = ((strcasecmp($type, K_TM_TYPE) == 0) || 
	($user->getTrueFalse('ASK_TO_REPORT', "In order to improve Kaltura Community Edition, we would like your permission to send system data to Kaltura.\nThis information will be used exclusively for improving our software and our service quality. I agree", 'y')))) {	
	$email = $user->getInput('REPORT_MAIL', $report_message, $report_error_message, $report_validator, null);
	$app->set('REPORT_ADMIN_EMAIL', $email);
	$app->set('TRACK_KDPWRAPPER','true');
	$app->set('USAGE_TRACKING_OPTIN','true');	
} else {
	$app->set('REPORT_ADMIN_EMAIL', "");
	$app->set('TRACK_KDPWRAPPER','false');
	$app->set('USAGE_TRACKING_OPTIN','false');
}

$host_name = $user->getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', 
						"Please enter the domain name/virtual hostname that will be used for the Kaltura server (without http://)", 
						'Must be a valid hostname or ip, please enter again', 
						InputValidator::createHostValidator(), 
						null);
$app->set('KALTURA_FULL_VIRTUAL_HOST_NAME', $host_name);
						
$admin_email = $user->getInput('ADMIN_CONSOLE_ADMIN_MAIL', 
						"Your primary system administrator email address", 
						"Email must be in a valid email format, please enter again", 
						InputValidator::createEmailValidator(false), 
						null);
$app->set('ADMIN_CONSOLE_ADMIN_MAIL', $admin_email);

$password = $user->getInput('ADMIN_CONSOLE_PASSWORD', 
						"The password you want to set for your primary administrator", 
						"Password should not be empty and should not contain whitespaces, please enter again", 
						InputValidator::createNoWhitespaceValidator(), 
						null);
$app->set('ADMIN_CONSOLE_PASSWORD', $password);						
						
$install_config = parse_ini_file(FILE_CONFIG, true);

$app->defineConfigurationTokens();
foreach ($install_config['token_files'] as $file) {
	if (!$app->replaceTokensInFile($file)) {
		echo "Failed to replace tokens in $file";
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

echo PHP_EOL;
logMessage(L_USER, sprintf("Configuration Completed Successfully.\nYour Kaltura Admin Console credentials:\nSystem Admin user: %s\nSystem Admin password: %s\n\nPlease keep this information for future use.\n", $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')));
logMessage(L_USER, sprintf("To start using Kaltura, please complete the following steps:\n1. Add the following line to your /etc/hosts file:\n\t127.0.0.1 %s\n2. Add the following line to your Apache configurations file (Usually called httpd.conf or apache2.conf):\n\tInclude /opt/kaltura/app/configurations/apache/my_kaltura.conf\n3. Restart apache\n4. Browse to your Kaltura start page at: http://%s/start\n", $app->get("KALTURA_VIRTUAL_HOST_NAME"), $app->get("KALTURA_VIRTUAL_HOST_NAME")));


die(0);

	

