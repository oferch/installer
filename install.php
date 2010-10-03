<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/ConfigUtils.php');
include_once('installer/OsUtils.class.php');
include_once('installer/UserInput.class.php');
include_once('installer/Prerequisites.class.php');
include_once('installer/Log.php');
include_once('installer/InstallReport.class.php');
include_once('installer/AppConfig.class.php');
include_once('installer/Installer.class.php');
include_once('installer/InputValidator.class.php');

// constants
define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");
define("APP_SQL_SIR", "/app/deployment/base/sql/");
define("FILE_INSTALL_SEQ_ID", "install_seq"); // this file is used to store a sequence of installations

// variables
$app; $install; $user; $report;
$db_params = array();

// functions

function installationFailed($error, $cleanup = true) {
	global $texts, $report, $installer, $app, $db_params;
	logMessage(L_USER, "Installation could not continue: $error");
	
	if ($cleanup) {
		$installer->detectLeftovers(false, $app, $db_params);
	}
	if (isset($report)) {
		$report->reportInstallationFailed($error);
	}	
	logMessage(L_USER, "Installation failed.\nCritical errors occurred during the installation process.\nFor assistance, please upload the installation log file to the Kaltura CE forum at kaltura.org");
	die(1);
}

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

// TODO: parameters - config name, debug level and force

// initialize the installation
$logfilename = "install_log_".date("d.m.Y_H.i.s");
startLog($logfilename);
logMessage(L_INFO, "Installation started");

$app = new AppConfig();
$installer = new Installer();
$preq = new Prerequisites();
$user = new UserInput();

$app->set('INSTALLATION_UID', uniqid("IID")); // unique id per installation

// Load or create installation sequence id
if (is_file(FILE_INSTALL_SEQ_ID)) {
	$install_seq = @file_get_contents($newfile);
	$app->set('INSTALLATION_SEQUENCE_UID', $install_seq);
} else {
	$install_seq = uniqid("ISEQID"); // unique id per a set of installations
	$app->set('INSTALLATION_SEQUENCE_UID', $install_seq); 
	file_put_contents(FILE_INSTALL_SEQ_ID, $install_seq);
}

// reading package version
$version = parse_ini_file('package/version.ini');
$app->set('KALTURA_VERSION', 'Kaltura '.$version['type'].' '.$version['number']);
$app->set('KALTURA_VERSION_TYPE', $version['type']);
logMessage(L_INFO, "Installing ".'Kaltura '.$version['type'].' '.$version['number']);

// start user interaction
@system('clear');
logMessage(L_USER, "Thank you for using Kaltura video platform");
echo PHP_EOL;

// If previous installation found and the user wants to use it
if ($user->hasInput() && 
	$user->getTrueFalse(null, "A previous installation found, do you want to use the same configuration?", 'y')) {
	$user->loadInput();
}

if (!$user->getTrueFalse('PROCEED_WITH_INSTALL', "Do you want to start installation now?", 'y')) {
	echo "Bye".PHP_EOL;
	die(1);
}

// if user wants or have to report
if ($result = ((strcasecmp($app->get('KALTURA_VERSION_TYPE'), K_TM_TYPE) == 0) || 
	($user->getTrueFalse('ASK_TO_REPORT', "In order to improve Kaltura CE, we would like your permission to send system data from your server to Kaltura.\nThis information will not be used for any purpose other than improving service quality. I agree", 'y')))) {
	$email = $user->getInput('REPORT_MAIL', "If you wish, please provide your email address so that we can offer you future assistance (Leave empty to pass)", "Email must be in a valid email format", InputValidator::createEmailValidator(true), null);
	$app->set('REPORT_ADMIN_EMAIL', $email);
	$app->set('TRACK_KDPWRAPPER','true');
	$report = new InstallReport($email, $app->get('KALTURA_VERSION'), $app->get('INSTALLATION_SEQUENCE_UID'), $app->get('INSTALLATION_UID'));
	$report->reportInstallationStart();
} else {
	$app->set('TRACK_KDPWRAPPER','false');
}

if (!OsUtils::verifyRootUser()) {
	installationFailed("You must have root privileges to install Kaltura", false);
}
if (!OsUtils::verifyOS()) {
	installationFailed("Installation can only run on Linux", false);
}

if ($user->isInputLoaded()) {
	logMessage(L_USER, "Skipping user input, using previous configuration");	
} else {
	logMessage(L_USER, "User input:");
}

// user input
$httpd_bin_found = OsUtils::findBinary(array('apachectl', 'apache2ctl'));
$httpd_bin_message = "The full pathname to your Apache apachectl/apache2ctl";
if (!empty($httpd_bin_found)) {
	$httpd_bin_message .= PHP_EOL."Installer found $httpd_bin_found, leave empty to use it";
}
$php_bin_found = OsUtils::findBinary('php');
$php_bin_message = "The full pathname to your PHP binary file";
if (!empty($php_bin_found)) {
	$php_bin_message .= PHP_EOL."Installer found $php_bin_found, leave empty to use it";
}

$user->getInput('HTTPD_BIN', $httpd_bin_message, 'Httpd binary must exist', InputValidator::createFileValidator(), $httpd_bin_found);
$user->getInput('PHP_BIN', $php_bin_message, 'PHP binary must exist', InputValidator::createFileValidator(), $php_bin_found);
$user->getInput('BASE_DIR', "The full directory path for Kaltura application (Leave empty for /opt/kaltura)", "Target directory must be a valid directory path", InputValidator::createDirectoryValidator(), '/opt/kaltura');
$user->getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', "Please enter the domain name/virtual hostname that will be used for the kaltura server (without http://)", 'Must be a valid hostname or ip', InputValidator::createHostValidator(), null);
// not printing: A primary system administrator user will be created with full access to the Kaltura Administration Console.\nAdministrator e-mail
$user->getInput('ADMIN_CONSOLE_ADMIN_MAIL', "Your primary system administrator email address (A real email address is required in order to recieve system auto-generated emails)", "Email must be in a valid email format", InputValidator::createEmailValidator(false), null);
$user->getInput('ADMIN_CONSOLE_PASSWORD', "The password you want to set for your primary administrator", "Password cannot be empty or contain whitespaces", InputValidator::createNoWhitespaceValidator(), null);
$user->getInput('DB1_HOST', "Database host (Leave empty for 'localhost')", 'Must be a valid hostname or ip', InputValidator::createHostValidator(), 'localhost');
$user->getInput('DB1_PORT', "Database port (Leave empty for '3306')", 'Must be a valid port (1-65535)', InputValidator::createRangeValidator(1, 65535), '3306');
if (!$user->isInputLoaded()) $user->set('DB1_NAME','kaltura'); // currently we do not support getting the DB name from the user because of the DWH implementation
$user->getInput('DB1_USER', "Database username (With create & write privileges)", "Db user cannot be empty", InputValidator::createNonEmptyValidator(), null);
$user->getInput('DB1_PASS', "Database password (Leave empty for no password)", null, null, null);
$user->getInput('XYMON_URL', "The URL to your xymon/hobbit monitoring location.\nXymon is an optional installation. Leave empty to set manually later\nExamples:\nhttp://www.xymondomain.com/xymon/\nhttp://www.xymondomain.com/hobbit/", null, null, null);
if (!$user->isInputLoaded()) $user->saveInput();

$app->initFromUserInput($user->getAll());
$db_params['db_host'] = $app->get('DB1_HOST');
$db_params['db_port'] = $app->get('DB1_PORT');
$db_params['db_user'] = $app->get('DB1_USER');
$db_params['db_pass'] = $app->get('DB1_PASS');

echo PHP_EOL;

logMessage(L_USER, "Verifing prerequisites");
$prereq_desc = $preq->verifyPrerequisites($app, $db_params);
if ($prereq_desc !== null) {
	installationFailed("Please setup the preqrequisites listed and run the installation again\n$prereq_desc", false);
}

logMessage(L_USER, "Verifing that there are no leftovers from previous installation of Kaltura");
$leftovers = $installer->detectLeftovers(true, $app, $db_params);
if (isset($leftovers)) {
	logMessage(L_USER, $leftovers);
	if ($user->getTrueFalse(null, "Installation found leftovers from previous installation of Kaltura. In order to advance forward the leftovers must be removed. Do you wish to remove them now?", 'n')) {
		$installer->detectLeftovers(false, $app, $db_params);
	} else {
		installationFailed("Please cleanup the previous installation and run the installer again\n$leftovers", false);		
	}
}

echo PHP_EOL;
logMessage(L_USER, "Installing Kaltura on your system");
echo PHP_EOL;

$install_output = $installer->install($app, $db_params);
if ($install_output !== null) {
	installationFailed($install_output, true);
}

if (function_exists('mail')) {
	logMessage(L_USER, "Sending settings mail to ".$app->get('ADMIN_CONSOLE_ADMIN_MAIL'));
	$msg = sprintf("Thank you for installing the Kaltura Video Platform\n\nTo get started, please browse to your kaltura start page at:\nhttp://%s/start\n\nYour kaltura administration console can be accessed at:\nhttp://%s/admin_console\n\nYour Admin Console credentials are:\nSystem admin user: %s\nSystem admin password: %s\n\nPlease keep this information for future use.\n\nThank you for choosing Kaltura!", $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')).PHP_EOL;
	@mail('TO', 'Kaltura installation settings', $msg);
} else {
	logMessage(L_USER, "Skipped sending settings mail");
}
	
// print after installation instructions
echo PHP_EOL;
logMessage(L_USER, sprintf("Installation Completed Successfully.\nYour Kaltura Admin Console credentials:\nSystem Admin user: %s\nSystem Admin password: %s\n\nPlease keep this information for future use.\nAssuming your mail server is up, the above \ninformation will also be sent to your email.", $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')));
logMessage(L_USER, sprintf("To start using Kaltura, please do the following steps:\n1. Add the following line to your /etc/hosts file:\n\t127.0.0.1 %s\n2. Add the following line to your Apache configurations file (Usually called httpd.conf or apache2.conf):\n\tInclude %s/app/configurations/apache/my_kaltura.conf\n3. Restart apache by executing the following command:\t%s\n4. Browse to your Kaltura start page at: http://%s/start\n", $app->get("KALTURA_VIRTUAL_HOST_NAME"), $app->get("BASE_DIR"), $app->get("HTTPD_BIN"), $app->get("KALTURA_VIRTUAL_HOST_NAME")));

if (isset($report)) {
	$report->reportInstallationSuccess();
}

die(0);