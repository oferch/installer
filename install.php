<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/ConfigUtils.php');
include_once('installer/OsUtils.class.php');
include_once('installer/UserInput.class.php');
include_once('installer/Prerequisites.class.php');
include_once('installer/Log.php');
include_once('installer/AppConfig.class.php');

// constants
define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");
define("APP_SQL_SIR", "/app/deployment/base/sql/");

// variables
$app; $install; $texts; $user;
$should_report = false;
$db_params = array();

// functions

function installationFailed($error, $cleanup = true) {
	global $texts, $should_report;
	logMessage(L_USER, "Installation could not continue: $error");
	
	if ($cleanup) {
		logMessage(L_USER, "Cleaning leftovers...");
		detectLeftovers(false);
	}
	//if ($should_report) reportInstallationFailure();
	logMessage(L_USER, $texts->getFlowText("install_fail"));
	die(1);
}

function detectDatabases($db_params, $should_drop=false){
	global $installation_config;
	$verify = null;
	foreach ($installation_config["databases"]["dbs"] as $db) {
		$result = DatabaseUtils::dbExists($db_params, $db);
		
		if ($result === -1) {
			$verify = $verify."\tLeftovers found: Error verifying if db exists $db".PHP_EOL;
		} else if ($result === true) {
			$verify = $verify."\tLeftovers found: DB already exists $db".PHP_EOL;
			if ($should_drop) DatabaseUtils::dropDb($db_params, $db);
		}
	}
	return $verify;
}	
	
function detectLeftovers($report_only) {
	global $app, $db_params;
	$leftovers = null;
	if (is_file('/etc/logrotate.d/kaltura_log_rotate')) {
		$leftovers = $leftovers."\tLeftovers found: kaltura_log_rotate symbolic link exists".PHP_EOL;;		
		if (!$report_only) OsUtils::recursiveDelete('/etc/logrotate.d/kaltura_log_rotate');
	}
	if (is_file('/etc/cron.d/kaltura_crontab')) {
		$leftovers = $leftovers."\tLeftovers found: kaltura_crontab symbolic link exists".PHP_EOL;;	
		if (!$report_only) OsUtils::recursiveDelete('/etc/cron.d/kaltura_crontab');
	}	
	$verify = detectDatabases($db_params);
	if (isset($verify))  {		
		$leftovers = $leftovers.$verify;
		if (!$report_only) {
			//FileUtils::execAsUser('/home/etl/ddl/dwh_drop_databases.sh' , 'etl');
			detectDatabases($db_params, true);
		}
	}	
	if (is_dir($app->get('BASE_DIR'))) {
		$leftovers = $leftovers."\tLeftovers found: Target directory ".$app->get('BASE_DIR')." already exists".PHP_EOL;;
		if (!$report_only) {
			@exec($app->get('BASE_DIR').'app/scripts/searchd.sh stop  2>&1');
			@exec($app->get('BASE_DIR').'app/scripts/serviceBatchMgr.sh stop  2>&1');			
			OsUtils::recursiveDelete($app->get('BASE_DIR'));			
		}
	}
	
	if (isset($leftovers)) {
		if ($report_only) logMessage(L_USER, "Installation found some previous installation leftovers:".PHP_EOL.$leftovers);
		return true;
	} else {
		return false;
	}	
}

// installation script start

// TODO: parameters - config name and debug level

// initialize the installation
$logfilename = "install_log_".date("d.m.Y_H.i.s");
startLog($logfilename);
logMessage(L_INFO, "Installation started");

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);
logMessage(L_INFO, "Installation might take a few minutes, set PHP ini values: max_execution_time=0; memory_limit=-1;max_input_time=0");

$texts = new TextsConfig();
$app = new AppConfig();
$install = new InstallConfig();
$user = new UserInput();
$app->set('INSTALLATION_UID', uniqid("IID")); // unique id per installation

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
} else {	
	$user->set('INSTALLATION_SEQUENCE_UID', uniqid("ISEQID")); // unique id per installation sequence (using same config)
}

if (!$user->getTrueFalse('PROCEED_WITH_INSTALL', $texts->getFlowText('proceed_with_install'), 'y')) {
	echo $error_texts['user_does_not_want'].PHP_EOL;
	die(1);
}

if ($result = ((strcasecmp($app->get('KALTURA_VERSION_TYPE'), K_TM_TYPE) == 0) || 
	($user->getTrueFalse('ASK_TO_REPORT', $texts->getFlowText('ask_to_report'), 'y')))) {
	$email = $user->getInput('REPORT_MAIL', $texts->getFlowText('report_email'));	
	$app->set('REPORT_ADMIN_EMAIL', $email);
	$app->set('TRACK_KDPWRAPPER','true');
	$should_report = true;
	//reportInstallationStart();
} else {
	$app->set('TRACK_KDPWRAPPER','false');
}

OsUtils::getOsLsb();

if (!OsUtils::verifyRootUser()) installationFailed($texts->getErrorText('user_not_root'), false);
if (!OsUtils::verifyOS()) installationFailed($texts->getErrorText('os_not_linux'), false);

if (!$user->isInputLoaded()) {
	echo PHP_EOL; 
	logMessage(L_USER, $texts->getFlowText('config_start'));
} else logMessage(L_USER, $texts->getFlowText('skipping_input'));

// user input
$user->getPathInput('HTTPD_BIN', $texts->getInputText('httpd_bin'), true, false, array('apachectl', 'apache2ctl'));
$user->getPathInput('PHP_BIN', $texts->getInputText('php_bin'), true, false, 'php');
$user->getInput('DB1_HOST', $texts->getInputText('db_host'),'localhost');
$user->getInput('DB1_PORT', $texts->getInputText('db_port'),'3306');
if (!$user->isInputLoaded()) $user->set('DB1_NAME','kaltura'); // currently we do not support getting the DB name from the user because of the DWH implementation
$user->getInput('DB1_USER', $texts->getInputText('db_user'));
$user->getInput('DB1_PASS', $texts->getInputText('db_pass'));
$user->getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', $texts->getInputText('virtual_host_name'));
$user->getPathInput('BASE_DIR', $texts->getInputText('kaltura_base_dir'), false, true, null, "/opt/kaltura");
if (!$user->isInputLoaded()) logMessage(L_USER, $texts->getInputText('admin_console_welcome'));
$user->getInput('ADMIN_CONSOLE_ADMIN_MAIL', $texts->getInputText('admin_email'));
$user->getInput('ADMIN_CONSOLE_PASSWORD', $texts->getInputText('admin_password'));
$user->getInput('XYMON_URL', $texts->getInputText('xymon_url'));
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
	
// ajust to the system architecture
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
	$link_items = explode('^', adjust_path($slink));	
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
@exec($app->get('APP_DIR').'/scripts/serviceBatchMgr.sh start  2>&1');
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

if ($should_report) {
	//reportInstallationSuccess();
}

die(0);