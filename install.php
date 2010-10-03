<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/ConfigUtils.php');
include_once('installer/OsUtils.class.php');
include_once('installer/UserInput.class.php');
include_once('installer/Prerequisites.class.php');
include_once('installer/Log.php');
include_once('installer/InstallReport.class.php');
include_once('installer/AppConfig.class.php');
include_once('installer/TextsConfig.class.php');
include_once('installer/Installer.class.php');
include_once('installer/InputValidator.class.php');

// constants
define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");
define("APP_SQL_SIR", "/app/deployment/base/sql/");
define("FILE_INSTALL_SEQ_ID", "install_seq"); // this file is used to store a sequence of installations

// variables
$app; $install; $texts; $user; $report;
$db_params = array();

// functions

function installationFailed($error, $cleanup = true) {
	global $texts, $report, $install;
	logMessage(L_USER, "Installation could not continue: $error");
	
	if ($cleanup) {
		$install->detectLeftovers(false);
	}
	if (isset($report)) {
		$report->reportInstallationFailed($error);
	}	
	logMessage(L_USER, $texts->getFlowText("install_fail"));
	die(1);
}

// installation script start

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

// TODO: parameters - config name, debug level and force

// initialize the installation
$logfilename = "install_log_".date("d.m.Y_H.i.s");
startLog($logfilename);
logMessage(L_INFO, "Installation started");

$texts = new TextsConfig();
$app = new AppConfig();
$install = new Installer();
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
logMessage(L_USER, $texts->getFlowText('welcome_msg'));
echo PHP_EOL;

// If previous installation found and the user wants to use it
if ($user->hasInput() && $user->getTrueFalse(null, $texts->getFlowText('user_previous_input'), 'y')) {
	$user->loadInput();
}

if (!$user->getTrueFalse('PROCEED_WITH_INSTALL', $texts->getFlowText('proceed_with_install'), 'y')) {
	echo $texts->getErrorText('user_does_not_want').PHP_EOL;
	die(1);
}

// if user wants or have to report
if ($result = ((strcasecmp($app->get('KALTURA_VERSION_TYPE'), K_TM_TYPE) == 0) || 
	($user->getTrueFalse('ASK_TO_REPORT', $texts->getFlowText('ask_to_report'), 'y')))) {
	$email = $user->getInput('REPORT_MAIL', $texts->getFlowText('report_email'), "Email must be in a valid email format", InputValidator::createEmailValidator(true), null);
	$app->set('REPORT_ADMIN_EMAIL', $email);
	$app->set('TRACK_KDPWRAPPER','true');
	$report = new InstallReport($email, $app->get('KALTURA_VERSION'), $app->get('INSTALLATION_SEQUENCE_UID'), $app->get('INSTALLATION_UID'));
	$report->reportInstallationStart();
} else {
	$app->set('TRACK_KDPWRAPPER','false');
}

if (!OsUtils::verifyRootUser()) installationFailed($texts->getErrorText('user_not_root'), false);
if (!OsUtils::verifyOS()) installationFailed($texts->getErrorText('os_not_linux'), false);

if (!$user->isInputLoaded()) {
	echo PHP_EOL; 
	logMessage(L_USER, $texts->getFlowText('config_start'));
} else {
	logMessage(L_USER, $texts->getFlowText('skipping_input'));
}

// user input
$httpd_bin_found = OsUtils::findBinary(array('apachectl', 'apache2ctl'));
$httpd_bin_message = $texts->getInputText('httpd_bin');
if (!empty($httpd_bin_found)) {
	$httpd_bin_message .= PHP_EOL."Installer found $httpd_bin_found, leave empty to use it";
}
$php_bin_found = OsUtils::findBinary('php');
$php_bin_message = $texts->getInputText('php_bin');
if (!empty($php_bin_found)) {
	$php_bin_message .= PHP_EOL."Installer found $php_bin_found, leave empty to use it";
}

$user->getInput('HTTPD_BIN', $httpd_bin_message, 'Httpd binary must exist', InputValidator::createFileValidator(), $httpd_bin_found);
$user->getInput('PHP_BIN', $php_bin_message, 'PHP binary must exist', InputValidator::createFileValidator(), $php_bin_found);
$user->getInput('BASE_DIR', $texts->getInputText('kaltura_base_dir'), "Target directory must be a valid directory path", InputValidator::createDirectoryValidator(), '/opt/kaltura');
$user->getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', $texts->getInputText('virtual_host_name'), 'Must be a valid hostname or ip', InputValidator::createHostValidator(), null);
$user->getInput('ADMIN_CONSOLE_ADMIN_MAIL', $texts->getInputText('admin_email'), "Email must be in a valid email format", InputValidator::createEmailValidator(false), null);
$user->getInput('ADMIN_CONSOLE_PASSWORD', $texts->getInputText('admin_password'), "Password cannot be empty or contain whitespaces", InputValidator::createNoWhitespaceValidator(), null);
$user->getInput('DB1_HOST', $texts->getInputText('db_host'), 'Must be a valid hostname or ip', InputValidator::createHostValidator(), 'localhost');
$user->getInput('DB1_PORT', $texts->getInputText('db_port'), 'Must be a valid port (1-65535)', InputValidator::createRangeValidator(1, 65535), '3306');
if (!$user->isInputLoaded()) $user->set('DB1_NAME','kaltura'); // currently we do not support getting the DB name from the user because of the DWH implementation
$user->getInput('DB1_USER', $texts->getInputText('db_user'), "Db user cannot be empty", InputValidator::createNonEmptyValidator(), null);
$user->getInput('DB1_PASS', $texts->getInputText('db_pass'), null, null, null);
$user->getInput('XYMON_URL', $texts->getInputText('xymon_url'), null, null, null);
if (!$user->isInputLoaded()) $user->saveInput();

echo PHP_EOL;

// init the application configuration
$app->initFromUserInput($user->getAll());
$db_params['db_host'] = $app->get('DB1_HOST');
$db_params['db_port'] = $app->get('DB1_PORT');
$db_params['db_user'] = $app->get('DB1_USER');
$db_params['db_pass'] = $app->get('DB1_PASS');

// verify prerequisites
$prereq_desc = $preq->verifyPrerequisites($app, $db_params);
if ($prereq_desc !== null) installationFailed($texts->getErrorText('prereq_failed')."\n".$prereq_desc, false);
else logMessage(L_USER, "All prerequisites verifications passed");

// verify the machine is ready for installation
$leftovers = $installer->detectLeftovers(true, $app, $db_params);
if (isset($leftovers)) {
	logMessage(L_USER, $leftovers);
	if (!$user->getTrueFalse(null, $texts->getFlowText("leftovers_found"), 'n')) installationFailed($texts->getErrorText('clean_leftovers')."\n".$leftovers, false);
	else $installer->detectLeftovers(false, $app, $db_params);
} else {
	logMessage(L_USER, "Finished verifing that the machine is clean for installation");
}

// installation
echo PHP_EOL;
logMessage(L_USER, $texts->getFlowText("starting_installation"));
echo PHP_EOL;

$install_output = $installer->install($app, $db_params, $texts);
if ($install_output !== null) {
	installationFailed($install_output, true);
}

// send settings mail
if (function_exists('mail')) {
	logMessage(L_USER, "Sending settings mail to ".$app->get('ADMIN_CONSOLE_ADMIN_MAIL'));
	$msg = sprintf($texts->getFlowText('finish_mail'), $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')).PHP_EOL;
	@mail('TO', 'Kaltura installation settings', $msg);
} else {
	logMessage(L_USER, "Skipped sending settings mail");
}
	
// print after installation instructions
echo PHP_EOL;
logMessage(L_USER, sprintf($texts->getFlowText("install_success"), $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')));
logMessage(L_USER, sprintf($texts->getFlowText("after_install_steps"), $app->get("KALTURA_VIRTUAL_HOST_NAME"), $app->get("BASE_DIR"), $app->get("HTTPD_BIN"), $app->get("KALTURA_VIRTUAL_HOST_NAME")));

if (isset($report)) {
	$report->reportInstallationSuccess();
}

die(0);