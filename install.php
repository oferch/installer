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

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

date_default_timezone_set(@date_default_timezone_get());

// start the log
startLog(__DIR__ . '/package.' . date("d.m.Y_H.i.s") . '.log');
logMessage(L_INFO, "Installation started");

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

$packageDir = realpath(__DIR__ . '/../package');
AppConfig::init($packageDir);
AppConfig::configure();

OsUtils::setLogPath(AppConfig::get(AppConfigAttribute::LOG_DIR) . DIRECTORY_SEPARATOR . 'kaltura_deploy.log');

logMessage(L_INFO, "Installing Kaltura " . AppConfig::get(AppConfigAttribute::KALTURA_VERSION));
if (AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE) == AppConfig::K_CE_TYPE) {
	logMessage(L_USER, "Thank you for installing Kaltura Video Platform - Community Edition");
} else {
	logMessage(L_USER, "Thank you for installing Kaltura Video Platform");
}
echo PHP_EOL;



$report = null;
// if user wants or have to report
if (AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE) == AppConfig::K_TM_TYPE ||
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
	$description = "One or more prerequisites required to install Kaltura failed:\n" . implode("\n", $prerequisites);

	if ($report)
		$report->reportInstallationFailed($description);

	logMessage(L_USER, $description);
	logMessage(L_USER, "Please resolve the issues and run the installation again.");
	exit(-1);
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

		$description = "Installation cannot continue because a previous installation of Kaltura was detected.\n" . $leftovers;
		if ($report)
			$report->reportInstallationFailed($description);

		logMessage(L_USER, "Please manually uninstall Kaltura before running the installation or select yes to remove the leftovers.");
		exit(-2);
	}
}

// run the installation
$install_output = $installer->install($packageDir);
if ($install_output !== null)
{
	$description = "Installation failed.\n" . $install_output;
	if ($report)
		$report->reportInstallationFailed($description);

	$leftovers = $installer->detectLeftovers(true);
	if (isset($leftovers) && AppConfig::getTrueFalse(null, "Do you want to cleanup?", 'n')) {
		$installer->detectLeftovers(false);
	}

	if (AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE) == AppConfig::K_CE_TYPE)
		logMessage(L_USER, "For assistance, please upload the installation log file to the Kaltura CE forum at kaltura.org");
	else
		logMessage(L_USER, "For assistance, please contant the support team at support@kaltura.com with the installation log attached");

	exit(1);
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

if ($report)
	$report->reportInstallationSuccess();

exit(0);
