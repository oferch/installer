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
include_once('installer/InstallConfig.class.php');
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
	global $texts, $report;
	logMessage(L_USER, "Installation could not continue: $error");
	
	if ($cleanup) {
		logMessage(L_USER, "Cleaning leftovers...");
		detectLeftovers(false);
	}
	if (isset($report)) {
		$report->reportInstallationFailed($error);
	}	
	logMessage(L_USER, $texts->getFlowText("install_fail"));
	die(1);
}

function detectDatabases($db_params, $should_drop=false){
	global $install;
	$verify = null;
	foreach ($install->getDatabases() as $db) {
		$result = DatabaseUtils::dbExists($db_params, $db);
		
		if ($result === -1) {
			$verify .= "\tLeftovers found: Error verifying if db exists $db".PHP_EOL;
		} else if ($result === true) {
			$verify .= "\tLeftovers found: DB already exists $db".PHP_EOL;
			if ($should_drop) DatabaseUtils::dropDb($db_params, $db);
		}
	}
	return $verify;
}	
	
function detectLeftovers($report_only) {
	global $app, $db_params;
	$leftovers = null;
	if (is_file('/etc/logrotate.d/kaltura_log_rotate')) {
		$leftovers .= "\tLeftovers found: kaltura_log_rotate symbolic link exists".PHP_EOL;;		
		if (!$report_only) OsUtils::recursiveDelete('/etc/logrotate.d/kaltura_log_rotate');
	}
	if (is_file('/etc/cron.d/kaltura_crontab')) {
		$leftovers .= "\tLeftovers found: kaltura_crontab symbolic link exists".PHP_EOL;;	
		if (!$report_only) OsUtils::recursiveDelete('/etc/cron.d/kaltura_crontab');
	}	
	$verify = detectDatabases($db_params);
	if (isset($verify))  {		
		$leftovers .= $verify;
		if (!$report_only) {
			//FileUtils::execAsUser('/home/etl/ddl/dwh_drop_databases.sh' , 'etl');
			detectDatabases($db_params, true);
		}
	}	
	if (is_dir($app->get('BASE_DIR'))) {
		$leftovers .= "\tLeftovers found: Target directory ".$app->get('BASE_DIR')." already exists".PHP_EOL;;
		if (!$report_only) {
			@exec($app->get('BASE_DIR').'app/scripts/searchd.sh stop  2>&1');
			@exec($app->get('BASE_DIR').'app/scripts/serviceBatchMgr.sh stop  2>&1');			
			OsUtils::recursiveDelete($app->get('BASE_DIR'));			
		}
	}
	
	if (isset($leftovers)) {
		if ($report_only) logMessage(L_USER, $leftovers);
		return true;
	} else {
		return false;
	}	
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
$install = new InstallConfig();
$user = new UserInput();

$app->set('INSTALLATION_UID', uniqid("IID")); // unique id per installation
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

OsUtils::getOsLsb();

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
logMessage(L_USER, $texts->getFlowText('prereq_start'));

$app->initFromUserInput($user->getAll());
$db_params['db_host'] = $app->get('DB1_HOST');
$db_params['db_port'] = $app->get('DB1_PORT');
$db_params['db_user'] = $app->get('DB1_USER');
$db_params['db_pass'] = $app->get('DB1_PASS');

// verify prerequisites
$preq = new Prerequisites();
if (!$preq->verifyPrerequisites($app, $db_params)) installationFailed($texts->getErrorText('prereq_failed'), false);

logMessage(L_USER, "Verifing that the machine is clean for the installation");
if (detectLeftovers(true)) {
	if (!$user->getTrueFalse(null, $texts->getFlowText("leftovers_found"), 'n')) installationFailed($texts->getErrorText('clean_leftovers'), false);
	else detectLeftovers(false);
}

// installation
echo PHP_EOL;
logMessage(L_USER, $texts->getFlowText("starting_installation"));

// copy files
echo PHP_EOL;
logMessage(L_USER, $texts->getFlowText("copying_files"));
if (!OsUtils::fullCopy('package/app/', $app->get('BASE_DIR'), true)) installationFailed($texts->getErrorText('failed_copy'));

// replace tokens in configuration files
logMessage(L_USER, $texts->getFlowText("replacing_tokens"));
foreach ($install->getTokenFiles() as $file) {
	$replace_file = $app->replaceTokensInString($file);
	if (!$app->replaceTokensInFile($replace_file)) installationFailed($texts->getErrorText('failed_replacing_tokens'));
}

// adjust to the system architecture
$os_name = 	OsUtils::getOsName(); // Already verified that the OS is supported in the prerequisites
$architecture = OsUtils::getSystemArchitecture();	
logMessage(L_USER, sprintf($texts->getFlowText("adjusting_architecture"), $os_name, $architecture));
$bin_subdir = $os_name.'/'.$architecture;
if (!OsUtils::fullCopy($app->get('BIN_DIR').'/'.$bin_subdir, $app->get('BIN_DIR'), true)) installationFailed($texts->getErrorText('failed_architecture_copy'));
if (!OsUtils::recursiveDelete($app->get('BIN_DIR').'/'.$os_name)) installationFailed($texts->getErrorText('failed_architecture_delete'));

// chmod
logMessage(L_USER, $texts->getFlowText("chmoding"));
foreach ($install->getChmodItems() as $item) {
	$chmod_item = $app->replaceTokensInString($item);
	if (!OsUtils::chmod($chmod_item)) installationFailed($texts->getErrorText('failed_cmod').' $chmod_item');
}

// create databases
logMessage(L_USER, $texts->getFlowText("database"));
$sql_files = parse_ini_file($app->get('BASE_DIR').APP_SQL_SIR.'create_kaltura_db.ini', true);

logMessage(L_INFO, "Setting-up Kaltura DB");
if (!DatabaseUtils::createDb($db_params, $app->get('DB1_NAME'))) installationFailed($texts->getErrorText('failed_creating_kaltura_db'));
foreach ($sql_files['kaltura']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app->get('BASE_DIR').APP_SQL_SIR.$sql, $db_params, $app->get('DB1_NAME'))) installationFailed($texts->getErrorText('failed_init_kaltura_db'));
}

// create stats database
logMessage(L_INFO, "Setting-up Kaltura stats DB");
if (!DatabaseUtils::createDb($db_params, $app->get('DB_STATS_NAME'))) installationFailed($texts->getErrorText('failed_creating_stats_db'));
foreach ($sql_files['stats']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app->get('BASE_DIR').APP_SQL_SIR.$sql, $db_params, $app->get('DB_STATS_NAME'))) installationFailed($texts->getErrorText('failed_init_stats_db'));
}
	
// create the data warehouse
logMessage(L_USER, $texts->getFlowText("dwh"));
if (!DatabaseUtils::runScript("package/dwh_grants/grants.sql", $db_params, $app->get('DB1_NAME'))) installationFailed($texts->getErrorText('failed_running_dwh_sql_script'));
//if (!FileUtils::execAsUser($app_config['BASE_DIR'].'dwh/ddl/dwh_ddl_install.sh')) installationFailed($error_texts['failed_running_dwh_script']);

// Create a symbolic link for the logrotate and crontab
logMessage(L_USER, $texts->getFlowText("symlinks"));
foreach ($install->getSymLinks() as $slink) {
	$link_items = explode('^', $app->replaceTokensInString($slink));	
	if (!symlink($link_items[0], $link_items[1])) installationFailed(sprintf($texts->getErrorText('failed_sym_link'), $link_items[0], $link_items[1]));
	else logMessage(L_INFO, "Created symblic link from $link_items[0] to $link_items[1]");
}

logMessage(L_USER, $texts->getFlowText("config_system"));
$app->simMafteach();
@exec($app->get('PHP_BIN').' '.$app->get('APP_DIR').'/deployment/base/scripts/populateSphinxEntries.php');

// post install
logMessage(L_USER, $texts->getFlowText("uninstaller"));
if (!OsUtils::fullCopy('installer/uninstall.php', $app->get('BASE_DIR')."/uninstaller/")) installationFailed($texts->getErrorText('failed_creating_uninstaller'));
$app->saveUninstallerConfig();

logMessage(L_USER, $texts->getFlowText("run_system"));
@exec($app->get('APP_DIR').'/scripts/serviceBatchMgr.sh start 2>&1');
@exec($app->get('APP_DIR').'/scripts/searchd.sh start  2>&1');

// send settings mail
if (function_exists('mail')) {
	$msg = sprintf($texts->getFlowText('finish_mail'), $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('KALTURA_VIRTUAL_HOST_NAME'), $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')).PHP_EOL;
	@mail('TO', 'Kaltura installation settings', $msg);
}
	
// print after installation instructions
echo PHP_EOL;
logMessage(L_USER, sprintf($texts->getFlowText("install_success"), $app->get('ADMIN_CONSOLE_ADMIN_MAIL'), $app->get('ADMIN_CONSOLE_PASSWORD')));
logMessage(L_USER, sprintf($texts->getFlowText("after_install_steps"), $app->get("KALTURA_VIRTUAL_HOST_NAME"), $app->get("BASE_DIR"), $app->get("HTTPD_BIN"), $app->get("KALTURA_VIRTUAL_HOST_NAME")));

if (isset($report)) {
	$report->reportInstallationSuccess();
}

die(0);