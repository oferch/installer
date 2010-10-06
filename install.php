<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/OsUtils.class.php');
include_once('installer/UserInput.class.php');
include_once('installer/Prerequisites.class.php');
include_once('installer/Log.php');
include_once('installer/InstallReport.class.php');
include_once('installer/AppConfig.class.php');
include_once('installer/Installer.class.php');
include_once('installer/InputValidator.class.php');

// should be called whenever the installation fails
// $error - the error to print to the user
// if $cleanup and there is something to cleanup it will prompt the user whether to cleanup
function installationFailed($what_happened, $description, $what_to_do, $cleanup = false) {
	global $report, $installer, $app, $db_params, $user;

	if (isset($report)) {
		$report->reportInstallationFailed($what_happened."\n".$description);
	}
	if (!empty($what_happened)) logMessage(L_USER, $what_happened);
	if (!empty($description)) logMessage(L_USER, $description);
	if ($cleanup) {
		$leftovers = $installer->detectLeftovers(true, $app, $db_params);
		if (isset($leftovers) && $user->getTrueFalse(null, "Do you want to cleanup?", 'y')) {
			$installer->detectLeftovers(false, $app, $db_params);
		}	
	}
	if (!empty($what_to_do)) logMessage(L_USER, $what_to_do);		
	die(1);
}

// constants
define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");
define("FILE_INSTALL_SEQ_ID", "install_seq"); // this file is used to store a sequence of installations

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

// TODO: parameters - config name, debug level and force

// start the log
startLog("install_log_".date("d.m.Y_H.i.s"));
logMessage(L_INFO, "Installation started");

// variables
$app = new AppConfig();
$installer = new Installer();
$preq = new Prerequisites();
$user = new UserInput();
$db_params = array();

// set the installation ids
$app->set('INSTALLATION_UID', uniqid("IID")); // unique id per installation

// load or create installation sequence id
if (is_file(FILE_INSTALL_SEQ_ID)) {
	$install_seq = @file_get_contents($newfile);
	$app->set('INSTALLATION_SEQUENCE_UID', $install_seq);
} else {
	$install_seq = uniqid("ISEQID"); // unique id per a set of installations
	$app->set('INSTALLATION_SEQUENCE_UID', $install_seq); 
	file_put_contents(FILE_INSTALL_SEQ_ID, $install_seq);
}

// read package version
$version = parse_ini_file('package/version.ini');
logMessage(L_INFO, "Installing Kaltura ".$version['type'].' '.$version['number']);
$app->set('KALTURA_VERSION', 'Kaltura '.$version['type'].' '.$version['number']);
$app->set('KALTURA_VERSION_TYPE', $version['type']);

// start user interaction
@system('clear');
logMessage(L_USER, "Thank you for using Kaltura video platform");
echo PHP_EOL;

// If previous installation found and the user wants to use it
if ($user->hasInput() && 
	$user->getTrueFalse(null, "A previous installation found, do you want to use the same configuration?", 'y')) {
	$user->loadInput();
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

// verify that the installation can continue
if (!OsUtils::verifyRootUser()) {
	installationFailed("Installation cannot continue", 
					   "You must have root privileges to install Kaltura", 
					   "Please run the installation again from a root user");
}
if (!OsUtils::verifyOS()) {
	installationFailed("Installation cannot continue", 
					   "Kaltura can only run on Linux systems at the current time", 
					   "Please run the installation on a different machine");
}

// get the user input if needed
if ($user->isInputLoaded()) {
	logMessage(L_USER, "Skipping user input, using previous configuration");	
} else {
	$user->getApplicationInput();
}

// init the application configuration
$app->initFromUserInput($user->getAll());
$db_params['db_host'] = $app->get('DB1_HOST');
$db_params['db_port'] = $app->get('DB1_PORT');
$db_params['db_user'] = $app->get('DB1_USER');
$db_params['db_pass'] = $app->get('DB1_PASS');

// verify prerequisites
echo PHP_EOL;
logMessage(L_USER, "Verifing prerequisites");
$prereq_desc = $preq->verifyPrerequisites($app, $db_params);
if ($prereq_desc !== null) {
	installationFailed("Installation cannot continue because some of the prerequisites checks failed", 
					   $prereq_desc, 
					   "Please fix the prerequisites and then run the installation again.");
}

// verify that there are no leftovers from previous installations
echo PHP_EOL;
logMessage(L_USER, "Verifing that there are no leftovers from previous installation of Kaltura");
$leftovers = $installer->detectLeftovers(true, $app, $db_params);
if (isset($leftovers)) {
	logMessage(L_USER, $leftovers);
	if ($user->getTrueFalse(null, "Installation found leftovers from previous installation of Kaltura. In order to advance forward the leftovers must be removed. Do you wish to remove them now?", 'n')) {
		$installer->detectLeftovers(false, $app, $db_params);
	} else {
		installationFailed("Installation cannot continue because a previous installation of Kaltura was found", 
						   $leftovers, 
						   "Please manually uninstall Kaltura before running the installation again or aprove removing the leftovers.");
	}
}

// last chance to stop the installation
echo PHP_EOL;
if ($user->getTrueFalse('PROCEED_WITH_INSTALL', "Installation is now ready to begin, start the installation?", 'y')) {
	$user->saveInput();
} else {
	echo "Bye".PHP_EOL;
	die(1);	
}

// run the installation
$install_output = $installer->install($app, $db_params);
if ($install_output !== null) {
	installationFailed("Installation failed", 
					   "Critical errors occurred during the installation process", 
					   "For assistance, please upload the installation log file to the Kaltura CE forum at kaltura.org", true);
}

// send settings mail if possible
if (!function_exists('mail')) {
	logMessage(L_USER, "Skipped sending settings mail");
} else {
	logMessage(L_USER, "Sending settings mail to ".$app->get('ADMIN_CONSOLE_ADMIN_MAIL'));
	$msg = sprintf("Thank you for installing the Kaltura Video Platform\n\nTo get started, please browse to your kaltura start page at:\nhttp://%s/start\n\nYour kaltura administration console can be accessed at:\nhttp://%s/admin_console\n\nYour Admin Console credentials are:\nSystem admin user: %s\nSystem admin password: %s\n\nPlease keep this information for future use.\n\nThank you for choosing Kaltura!", $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')).PHP_EOL;
	@mail('TO', 'Kaltura installation settings', $msg);	
}
	
// print after installation instructions
echo PHP_EOL;
logMessage(L_USER, sprintf("Installation Completed Successfully.\nYour Kaltura Admin Console credentials:\nSystem Admin user: %s\nSystem Admin password: %s\n\nPlease keep this information for future use.\nAssuming your mail server is up, the above \ninformation will also be sent to your email.", $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')));
logMessage(L_USER, sprintf("To start using Kaltura, please do the following steps:\n1. Add the following line to your /etc/hosts file:\n\t127.0.0.1 %s\n2. Add the following line to your Apache configurations file (Usually called httpd.conf or apache2.conf):\n\tInclude %s/app/configurations/apache/my_kaltura.conf\n3. Restart apache by executing the following command:\t%s\n4. Browse to your Kaltura start page at: http://%s/start\n", $app->get("KALTURA_VIRTUAL_HOST_NAME"), $app->get("BASE_DIR"), $app->get("HTTPD_BIN"), $app->get("KALTURA_VIRTUAL_HOST_NAME")));

if (isset($report)) {
	$report->reportInstallationSuccess();
}

die(0);