<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/OsUtils.class.php');
include_once('installer/UserInput.class.php');
include_once('installer/Log.php');
include_once('installer/InstallReport.class.php');
include_once('installer/AppConfig.class.php');
include_once('installer/Installer.class.php');
include_once('installer/InputValidator.class.php');
include_once('installer/phpmailer/class.phpmailer.php');

// should be called whenever the installation fails
// $error - the error to print to the user
// if $cleanup and there is something to cleanup it will prompt the user whether to cleanup
function installationFailed($what_happened, $description, $what_to_do, $cleanup = false) {
	global $report, $installer, $db_params, $user;

	if (isset($report)) {
		$report->reportInstallationFailed($what_happened."\n".$description);
	}
	if (!empty($what_happened)) logMessage(L_USER, $what_happened);
	if (!empty($description)) logMessage(L_USER, $description);
	if ($cleanup) {
		$leftovers = $installer->detectLeftovers(true, $db_params);
		if (isset($leftovers) && $user->getTrueFalse(null, "Do you want to cleanup?", 'y')) {
			$installer->detectLeftovers(false, $db_params);
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
define("FILE_INSTALL_SEQ_ID", "install_seq"); // this file is used to store a sequence of installations

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

date_default_timezone_set(@date_default_timezone_get());

// TODO: parameters - config name, debug level and force

// start the log
startLog(__DIR__ . "/install_log_".date("d.m.Y_H.i.s"));
logMessage(L_INFO, "Installation started");

// variables
$silentRun = false;
if($argc > 1 && $argv[1] == '-s') $silentRun = true;
$cleanupIfFail = true;
if($argc > 1 && $argv[1] == '-c') {
	$cleanupIfFail = false;
	$silentRun = true;
} 
$installer = new Installer();
$user = new UserInput();
$db_params = array();

// set the installation ids
AppConfig::set(AppConfigAttribute::INSTALLATION_UID, uniqid("IID")); // unique id per installation

// load or create installation sequence id
if (is_file(FILE_INSTALL_SEQ_ID)) {
	$install_seq = @file_get_contents(FILE_INSTALL_SEQ_ID);
	AppConfig::set(AppConfigAttribute::INSTALLATION_SEQUENCE_UID, $install_seq);
} else {
	$install_seq = uniqid("ISEQID"); // unique id per a set of installations
	AppConfig::set(AppConfigAttribute::INSTALLATION_SEQUENCE_UID, $install_seq); 
	file_put_contents(FILE_INSTALL_SEQ_ID, $install_seq);
}

// read package version
$packageDir = realpath('../package');
$version = parse_ini_file("$packageDir/version.ini");
AppConfig::set(AppConfigAttribute::KALTURA_VERSION, 'Kaltura '.$version['type'].' '.$version['number']);
AppConfig::set(AppConfigAttribute::KALTURA_PREINSTALLED, $version['preinstalled']);
AppConfig::set(AppConfigAttribute::KALTURA_VERSION_TYPE, $version['type']);
logMessage(L_INFO, "Installing Kaltura ".AppConfig::get(AppConfigAttribute::KALTURA_VERSION));
if (strcasecmp(AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE), K_TM_TYPE) !== 0) {
	$hello_message = "Thank you for installing Kaltura Video Platform - Community Edition";
	$fail_action = "For assistance, please upload the installation log file to the Kaltura CE forum at kaltura.org";
} else {
	$hello_message = "Thank you for installing Kaltura Video Platform";
	$fail_action = "For assistance, please contant the support team at support@kaltura.com with the installation log attached";
}

// start user interaction
@system('clear');
logMessage(L_USER, $hello_message);
echo PHP_EOL;

// If previous installation found and the user wants to use it
if ($user->hasInput()){ 
	if(($silentRun) || ($user->getTrueFalse(null, "A previous installation attempt has been detected, do you want to use the input you provided during you last installation?", 'y'))) {
		$user->loadInput();
	}
}

// if user wants or have to report
if (strcasecmp(AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE), K_TM_TYPE) == 0 || 
	$user->getTrueFalse('ASK_TO_REPORT', "In order to improve Kaltura Community Edition, we would like your permission to send system data to Kaltura.\nThis information will be used exclusively for improving our software and our service quality. I agree", 'y')) 
{	
	$report_message = "If you wish, please provide your email address so that we can offer you future assistance (leave empty to pass)";
	$report_error_message = "Email must be in a valid email format";
	$report_validator = InputValidator::createEmailValidator(true);		
	
	$email = $user->getInput(AppConfigAttribute::REPORT_ADMIN_EMAIL, $report_message, $report_error_message, $report_validator, null);
	AppConfig::set(AppConfigAttribute::REPORT_ADMIN_EMAIL, $email);
	AppConfig::set(AppConfigAttribute::TRACK_KDPWRAPPER,'true');
	AppConfig::set(AppConfigAttribute::USAGE_TRACKING_OPTIN,'true');	
	$report = new InstallReport($email, AppConfig::get(AppConfigAttribute::KALTURA_VERSION), AppConfig::get(AppConfigAttribute::INSTALLATION_SEQUENCE_UID), AppConfig::get(AppConfigAttribute::INSTALLATION_UID));
	$report->reportInstallationStart();
} 
else 
{
	AppConfig::set(AppConfigAttribute::REPORT_ADMIN_EMAIL, "");
	AppConfig::set(AppConfigAttribute::TRACK_KDPWRAPPER,'false');
	AppConfig::set(AppConfigAttribute::USAGE_TRACKING_OPTIN,'false');
}

// set to replace passwords on first activiation if this installation is preinstalled
AppConfig::set(AppConfigAttribute::REPLACE_PASSWORDS,AppConfig::get(AppConfigAttribute::KALTURA_PREINSTALLED));

// allow ui conf tab only for CE installation
if (strcasecmp(AppConfig::get(AppConfigAttribute::KALTURA_VERSION_TYPE), K_TM_TYPE) !== 0) 
	AppConfig::set(AppConfigAttribute::UICONF_TAB_ACCESS, 'SYSTEM_ADMIN_BATCH_CONTROL');


// verify that the installation can continue
//if (!OsUtils::verifyRootUser()) {
//	installationFailed("Installation cannot continue, you must have root privileges to continue with the installation process.", 
//					   null, null);
//}
if (!OsUtils::verifyOS()) {
	installationFailed("Installation cannot continue, Kaltura platform can only be installed on Linux OS at this time.", 
					   null, null);
}

if (!extension_loaded('mysqli')) {
	installationFailed("You must have PHP mysqli extension loaded to continue with the installation.", 
					   null, null);
}

// get the user input if needed
if ($user->isInputLoaded()) {
	logMessage(L_USER, "Skipping user input, previous installation input will be used.");	
} else {
	$user->getApplicationInput();
}

// get from kConf.php the latest versions of kmc , clipapp and HTML5
$kconf = file_get_contents("$packageDir/app/configurations/base.ini");
$latestVersions = array();
$latestVersions["KMC_VERSION"] = getVersionFromKconf($kconf,"kmc_version");
$latestVersions["CLIPAPP_VERSION"] = getVersionFromKconf($kconf,"clipapp_version");
$latestVersions["HTML5_VERSION"] = getVersionFromKconf($kconf,"html5_version");

// init the application configuration
AppConfig::initFromUserInput(array_merge((array)$user->getAll(), (array)$latestVersions));
$db_params['db_host'] = AppConfig::get(AppConfigAttribute::DB1_HOST);
$db_params['db_port'] = AppConfig::get(AppConfigAttribute::DB1_PORT);
$db_params['db_user'] = AppConfig::get(AppConfigAttribute::DB_ROOT_USER);
$db_params['db_pass'] = AppConfig::get(AppConfigAttribute::DB_ROOT_PASS);

// verify prerequisites
echo PHP_EOL;
logMessage(L_USER, "Verifing prerequisites");
@exec(sprintf("%s installer/Prerequisites.php %s %s %s %s %s 2>&1", AppConfig::get(AppConfigAttribute::PHP_BIN), AppConfig::get(AppConfigAttribute::HTTPD_BIN), $db_params['db_host'], $db_params['db_port'], $db_params['db_user'], $db_params['db_pass']), $output, $exit_value);
if ($exit_value !== 0) {
	$description = "   ".implode("\n   ", $output)."\n";
	echo PHP_EOL;
	installationFailed("One or more prerequisites required to install Kaltura failed:",
					   $description,
					   "Please resolve the issues and run the installation again.");
}

// verify that there are no leftovers from previous installations
echo PHP_EOL;
logMessage(L_USER, "Checking for leftovers from a previous installation");
$leftovers = $installer->detectLeftovers(true, $db_params);
if (isset($leftovers)) {
	logMessage(L_USER, $leftovers);
	if ($user->getTrueFalse(null, "Leftovers from a previouse Kaltura installation have been detected. In order to continue with the current installation these leftovers must be removed. Do you wish to remove them now?", 'n')) {
		$installer->detectLeftovers(false, $db_params);
	} else {
		installationFailed("Installation cannot continue because a previous installation of Kaltura was detected.", 
						   $leftovers,
						   "Please manually uninstall Kaltura before running the installation or select yes to remove the leftovers.");
	}
}

// run the installation
$install_output = $installer->install($db_params);
if ($install_output !== null) {
	installationFailed("Installation failed.", $install_output, $fail_action, $cleanupIfFail);
}

if (AppConfig::get(AppConfigAttribute::RED5_INSTALL))
{
	$installer->installRed5();	
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
