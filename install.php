<?php

include_once('installer/DatabaseUtils.class.php');
include_once('installer/OsUtils.class.php');
include_once('installer/Log.php');
include_once('installer/InstallReport.class.php');
include_once('installer/AppConfig.class.php');
include_once('installer/Installer.class.php');
include_once('installer/Validator.class.php');
include_once('installer/InputValidator.class.php');
include_once('installer/phpmailer/class.phpmailer.php');

// should be called whenever the installation fails
// $error - the error to print to the user
// if $cleanup and there is something to cleanup it will prompt the user whether to cleanup
function installationFailed($what_happened, $description, $what_to_do, $cleanup = false) {
	global $report, $installer;

	if (isset($report)) {
		$report->reportInstallationFailed($what_happened."\n".$description);
	}
	if (!empty($what_happened)) logMessage(L_USER, $what_happened);
	if (!empty($description)) logMessage(L_USER, $description);
	if ($cleanup) {
		$leftovers = $installer->detectLeftovers(true);
		if (isset($leftovers) && AppConfig::getTrueFalse(null, "Do you want to cleanup?", 'n')) {
			$installer->detectLeftovers(false);
		}
	}
	if (!empty($what_to_do)) logMessage(L_USER, $what_to_do);
	die(1);
}


function getVersionFromKconf($kconf, $label)
{
	if (preg_match("/".$label." = .*/", $kconf, $matches)) {
		$firstPos = stripos($matches[0],"=");
		return trim(substr($matches[0],1+$firstPos));
	}
}

// constants
define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

date_default_timezone_set(@date_default_timezone_get());

// start the log
startLog(__DIR__ . "/install_log_".date("d.m.Y_H.i.s"));
logMessage(L_INFO, "Installation started");

// variables

$cleanupIfFail = true;
if($argc > 1 && $argv[1] == '-c')
{
	$cleanupIfFail = false;
}

$components = '*';
if($argc > 2)
{
	foreach($argv as $arg)
	{
		if(is_array($components))
			$components[] = $arg;

		if($arg == '-C')
			$components = array();
	}
}

@system('clear');

AppConfig::init();

// read package version
logMessage(L_INFO, "Installing Kaltura ".AppConfig::get(AppConfigAttribute::KALTURA_VERSION));
if (strcasecmp(AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE), K_TM_TYPE) !== 0) {
	$hello_message = "Thank you for installing Kaltura Video Platform - Community Edition";
	$fail_action = "For assistance, please upload the installation log file to the Kaltura CE forum at kaltura.org";
} else {
	$hello_message = "Thank you for installing Kaltura Video Platform";
	$fail_action = "For assistance, please contant the support team at support@kaltura.com with the installation log attached";
}

// start user interaction
logMessage(L_USER, $hello_message);
echo PHP_EOL;

// if user wants or have to report
if (strcasecmp(AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE), K_TM_TYPE) == 0 ||
	AppConfig::getTrueFalse(null, "In order to improve Kaltura Community Edition, we would like your permission to send system data to Kaltura.\nThis information will be used exclusively for improving our software and our service quality. I agree", 'y'))
{
	$report_message = "If you wish, please provide your email address so that we can offer you future assistance (leave empty to pass)";
	$report_error_message = "Email must be in a valid email format";
	$report_validator = InputValidator::createEmailValidator(true);

	$email = AppConfig::getInput(AppConfigAttribute::REPORT_ADMIN_EMAIL, $report_message, $report_error_message, $report_validator, null);
	if($email)
	{
		AppConfig::set(AppConfigAttribute::REPORT_ADMIN_EMAIL, $email);
		AppConfig::set(AppConfigAttribute::TRACK_KDPWRAPPER, 'true');
		AppConfig::set(AppConfigAttribute::USAGE_TRACKING_OPTIN, 'true');
		$report = new InstallReport($email, AppConfig::get(AppConfigAttribute::KALTURA_VERSION), AppConfig::get(AppConfigAttribute::INSTALLATION_SEQUENCE_UID), AppConfig::get(AppConfigAttribute::INSTALLATION_UID));
		$report->reportInstallationStart();
	}
}

// verify prerequisites
echo PHP_EOL;
logMessage(L_USER, "Verifing prerequisites");

$validator = new Validator($components);
$prerequisites = $validator->validate();

if (count($prerequisites))
{
	$description = implode("\n", $prerequisites);
	installationFailed("One or more prerequisites required to install Kaltura failed:",
					   $description,
					   "Please resolve the issues and run the installation again.");
}

// verify that there are no leftovers from previous installations
echo PHP_EOL;
logMessage(L_USER, "Checking for leftovers from a previous installation");

$installer = new Installer($components);
$leftovers = $installer->detectLeftovers(true);
if (isset($leftovers)) {
	logMessage(L_USER, $leftovers);
	if (AppConfig::getTrueFalse(null, "Leftovers from a previouse Kaltura installation have been detected. In order to continue with the current installation these leftovers must be removed. Do you wish to remove them now?", 'n')) {
		$installer->detectLeftovers(false);
	} else {
		installationFailed("Installation cannot continue because a previous installation of Kaltura was detected.",
						   $leftovers,
						   "Please manually uninstall Kaltura before running the installation or select yes to remove the leftovers.");
	}
}

// run the installation
$install_output = $installer->install();
if ($install_output !== null) {
	installationFailed("Installation failed.", $install_output, $fail_action, $cleanupIfFail);
}

// send settings mail if possible
$msg = sprintf("Thank you for installing the Kaltura Video Platform\n\nTo get started, please browse to your kaltura start page at:\nhttp://%s/start\n\nYour ".AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE)." administration console can be accessed at:\nhttp://%s/admin_console\n\nYour Admin Console credentials are:\nSystem admin user: %s\nSystem admin password: %s\n\nPlease keep this information for future use.\n\nThank you for choosing Kaltura!", AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME), AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME), AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL), AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD)).PHP_EOL;
$mailer = new PHPMailer();
$mailer->CharSet = 'utf-8';
$mailer->IsHTML(false);
$mailer->AddAddress(AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL));
$mailer->Sender = "installation_confirmation@".AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME);
$mailer->From = "installation_confirmation@".AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME);
$mailer->FromName = AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME);
$mailer->Subject = 'Kaltura Installation Settings';
$mailer->Body = $msg;

if ($mailer->Send()) {
	logMessage(L_USER, "Post installation email cannot be sent");
} else {
	logMessage(L_USER, "Sent post installation settings email to ".AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL));
}

// print after installation instructions
echo PHP_EOL;
logMessage(L_USER, sprintf(
	"Installation Completed Successfully.\n" .
	"Your Kaltura Admin Console credentials:\n" .
	"System Admin user: %s\n" .
	"System Admin password: %s\n\n" .
	"Please keep this information for future use.\n",

	AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL),
	AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_PASSWORD)
));

$virtualHostName = AppConfig::get(AppConfigAttribute::KALTURA_VIRTUAL_HOST_NAME);
$appDir = realpath(AppConfig::get(AppConfigAttribute::APP_DIR));

logMessage(L_USER,
	"To start using Kaltura, please complete the following steps:\n" .
	"1. Add the following line to your /etc/hosts file:\n" .
		"\t127.0.0.1 $virtualHostName\n" .
	"2. Browse to your Kaltura start page at: http://$virtualHostName/start\n"
);

if (isset($report)) {
	$report->reportInstallationSuccess();
}

die(0);
