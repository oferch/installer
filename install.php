<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/ConfigUtils.php');
include_once('installer/FileUtils.class.php');
include_once('installer/InstallUtils.class.php');
include_once('installer/UserInputUtils.class.php');
include_once('installer/Prerequisites.class.php');
include_once('installer/InstallationFunctions.php');
include_once('installer/Log.php');

// constants
define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");

define("FILE_USER_INPUT", "user_input.ini");
define("FILE_APPLICATION_CONFIG", "app_config.ini");
define("FILE_INSTALL_CONFIG", "installer/installation.ini");
define("FILE_INSTALL_TEXTS", "installer/texts.ini"); // log user and error level texts are defined in here, info level is defined in the code

define("APP_SQL_SIR", "/app/deployment/base/sql/");

// variables
$app_config = array();
$user_input = array();
$should_user_input = true; 
$should_report = false;
$db_params = array();

// functions

function adjust_path($path) {
	global $app_config;
	$new_path = str_replace("@BASE_DIR@",$app_config['BASE_DIR'], $path);
	$new_path = str_replace("@ETL_HOME_DIR@",$app_config['ETL_HOME_DIR'], $new_path);
	return $new_path;
}

function installationFailed($error) {
	global $app_config, $texts, $should_report;
	echo PHP_EOL;
	logMessage(L_USER, "An error has occured during installation: $error");
	logMessage(L_USER, "Cleaning leftovers...");	
	detectLeftovers(false);
	if ($should_report) {
		reportInstallationFailure();
	}
	logMessage(L_USER, $texts['flow']["install_fail"]);
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
	global $app_config, $db_params;
	$leftovers = null;
	if (is_file('/etc/logrotate.d/kaltura_log_rotate')) {
		$leftovers = $leftovers."\tLeftovers found: kaltura_log_rotate symbolic link exists".PHP_EOL;;		
		if (!$report_only) FileUtils::recursiveDelete('/etc/logrotate.d/kaltura_log_rotate');
	}
	if (is_file('/etc/cron.d/kaltura_crontab')) {
		$leftovers = $leftovers."\tLeftovers found: kaltura_crontab symbolic link exists".PHP_EOL;;	
		if (!$report_only) FileUtils::recursiveDelete('/etc/cron.d/kaltura_crontab');
	}	
	if (is_dir($app_config['BASE_DIR'])) {
		$leftovers = $leftovers."\tLeftovers found: Target directory ".$app_config['BASE_DIR']." already exists".PHP_EOL;;
		if (!$report_only) {
			@exec($app_config['BASE_DIR'].'app/scripts/searchd.sh stop  2>&1');
			@exec($app_config['BASE_DIR'].'app/scripts/serviceBatchMgr.sh stop  2>&1');
			FileUtils::recursiveDelete($app_config['BASE_DIR']);			
		}
	}	
	if (($files = @scandir($app_config['ETL_HOME_DIR']."/")) && (count($files) > 5)) {
		$leftovers = $leftovers."\tLeftovers found: Datawarehouse in ".$app_config['ETL_HOME_DIR']." already exists".PHP_EOL;;
		if (!$report_only) {
			FileUtils::execAsUser('/home/etl/ddl/dwh_drop_databases.sh' , 'etl');
			FileUtils::recursiveDelete($app_config['ETL_HOME_DIR'].'/*');
		}
	}
	$verify = detectDatabases($db_params);
	if (isset($verify))  {
		$leftovers = $leftovers.$verify;
		if (!$report_only) {
			detectDatabases($db_params, true);
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

$texts = parse_ini_file(FILE_INSTALL_TEXTS, true);
$flow_texts = $texts['flow'];
$input_texts = $texts['input'];
$error_texts = $texts['error'];
$installation_config = parse_ini_file(FILE_INSTALL_CONFIG, true);
$app_config['INSTALLATION_UID'] = uniqid("IID"); // unique id per installation

// reading package version
$version = parse_ini_file('package/version.ini');
$app_config['KALTURA_VERSION'] = 'Kaltura '.$version['type'].' '.$version['number'];
$app_config['KALTURA_VERSION_TYPE'] = $version['type'];
logMessage(L_INFO, "Installing ".'Kaltura '.$version['type'].' '.$version['number']);

// start user interaction
@system('clear');
logMessage(L_USER, $flow_texts['welcome_msg']);
echo PHP_EOL;

// If previous installation found and the user wants to use it
if (is_file(FILE_USER_INPUT) && 
	UserInputUtils::getTrueFalse(null, $flow_texts['user_previous_input'], 'y')) {
	$user_input = loadConfigFromFile(FILE_USER_INPUT);	
	$should_user_input = false;
} else {	
	$user_input['INSTALLATION_SEQUENCE_UID'] = uniqid("ISEQID"); // unique id per installation sequence (using same config)
}

if (!UserInputUtils::getTrueFalse('PROCEED_WITH_INSTALL', $flow_texts['proceed_with_install'], 'y')) installationFailed($error_texts['user_does_not_want']);

if ($result = ((strcasecmp($app_config['KALTURA_VERSION_TYPE'], K_TM_TYPE) == 0) || 
	(UserInputUtils::getTrueFalse('ASK_TO_REPORT', $flow_texts['ask_to_report'], 'y')))) {
	$email = UserInputUtils::getInput('REPORT_MAIL', $flow_texts['report_email']);	
	$app_config['REPORT_ADMIN_EMAIL'] = $email;
	$app_config['TRACK_KDPWRAPPER'] = 'true';
	$should_report = true;
	reportInstallationStart();
} else {
	$app_config['TRACK_KDPWRAPPER'] = 'false';
}

InstallUtils::getOsLsb();

if (!verifyRootUser()) installationFailed($error_texts['user_not_root']);
if (!verifyOS()) installationFailed($error_texts['os_not_linux']);

if ($should_user_input) {
	echo PHP_EOL; 
	logMessage(L_USER, $flow_texts['config_start']);
} else logMessage(L_USER, $flow_texts['skipping_input']);

// user input
UserInputUtils::getPathInput('HTTPD_BIN', $input_texts['httpd_bin'], true, false, array('apachectl', 'apache2ctl'));
UserInputUtils::getPathInput('PHP_BIN', $input_texts['php_bin'], true, false, 'php');
UserInputUtils::getInput('DB1_HOST', $input_texts['db_host'],'localhost');
UserInputUtils::getInput('DB1_PORT', $input_texts['db_port'],'3306');
if ($should_user_input) $user_input['DB1_NAME'] = 'kaltura'; // currently we do not support getting the DB name from the user because of the DWH implementation
UserInputUtils::getInput('DB1_USER', $input_texts['db_user']);
UserInputUtils::getInput('DB1_PASS', $input_texts['db_pass']);
if ($should_user_input) $user_input['ETL_HOME_DIR'] = '/home/etl'; // currently the DWH must be installed in this location
UserInputUtils::getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', $input_texts['virtual_host_name']);
UserInputUtils::getPathInput('BASE_DIR',  $input_texts['kaltura_base_dir'], false, true, null, "/opt/kaltura");
if ($should_user_input) logMessage(L_USER, $input_texts['admin_console_welcome']);
UserInputUtils::getInput('ADMIN_CONSOLE_ADMIN_MAIL', $input_texts['admin_email']);
UserInputUtils::getInput('ADMIN_CONSOLE_PASSWORD', $input_texts['admin_password']);
UserInputUtils::getInput('XYMON_URL', $input_texts['xymon_url']);
if ($should_user_input) writeConfigToFile($user_input, FILE_USER_INPUT);

echo PHP_EOL;
logMessage(L_USER, $flow_texts['prereq_start']);

copyConfig($user_input, $app_config);
$db_params['db_host'] = $app_config['DB1_HOST'];
$db_params['db_port'] = $app_config['DB1_PORT'];
$db_params['db_user'] = $app_config['DB1_USER'];
$db_params['db_pass'] = $app_config['DB1_PASS'];

// verify prerequisites
$preq = new Prerequisites();
if (!$preq->verifyPrerequisites($app_config)) installationFailed($error_texts['prereq_failed']);

defineInstallationTokens($app_config);
writeConfigToFile($app_config, FILE_APPLICATION_CONFIG);

if (detectLeftovers(true)) {
	if (!UserInputUtils::getTrueFalse(null, $flow_texts["leftovers_found"], 'n')) installationFailed($error_texts['clean_leftovers']);
	else detectLeftovers(false);
}

// installation
echo PHP_EOL;
logMessage(L_USER, $flow_texts["starting_installation"]);

// copy files
echo PHP_EOL;
logMessage(L_USER, $flow_texts["copying_files"]);
if (!FileUtils::fullCopy('package/app/', $app_config['BASE_DIR'], true)) installationFailed($error_texts['failed_copy']);
if (!FileUtils::fullCopy('package/dwh/*', $app_config['ETL_HOME_DIR']."/", true)) installationFailed("Failed copying data warehouse to target directory");

// replace tokens in configuration files
logMessage(L_USER, $flow_texts["replacing_tokens"]);
foreach ($installation_config['token_files']['files'] as $file) {
	$replace_file = adjust_path($file);
	$replace_file = FileUtils::copyTemplateFileIfNeeded($replace_file);
	if (!FileUtils::replaceTokensInFile($app_config, $replace_file)) installationFailed($error_texts['failed_replacing_tokens']);
}
	
// ajust to the system architecture
$os_name = 	InstallUtils::getOsName(); // Already verified that the OS is supported in the prerequisites
$architecture = InstallUtils::getSystemArchitecture();	
logMessage(L_USER, sprintf($flow_texts["adjusting_architecture"], $os_name, $architecture));
$bin_subdir = $os_name.'/'.$architecture;
if (!FileUtils::fullCopy($app_config['BIN_DIR'].'/'.$bin_subdir, $app_config['BIN_DIR'], true)) installationFailed($error_texts['failed_architecture_copy');
if (!FileUtils::recursiveDelete($app_config['BIN_DIR'].'/'.$os_name)) installationFailed($error_texts['failed_architecture_delete']);

// chmod
logMessage(L_USER, $flow_texts["chmoding"]);
foreach ($installation_config['chmod_items']['items'] as $item) {
	$chmod_item = adjust_path($item);
	if (!FileUtils::chmod($chmod_item)) installationFailed($error_texts['failed_cmod'].' $chmod_item');
}

// create databases
logMessage(L_USER, $flow_texts["database"]);
$sql_files = parse_ini_file($app_config['BASE_DIR'].APP_SQL_SIR.'create_kaltura_db.ini', true);

logMessage(L_INFO, "Setting-up Kaltura DB");
if (!DatabaseUtils::createDb($db_params, $app_config['DB1_NAME'])) installationFailed($error_texts['failed_creating_kaltura_db']);
foreach ($sql_files['kaltura']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app_config['BASE_DIR'].APP_SQL_SIR.$sql, $db_params, $app_config['DB1_NAME'])) installationFailed($error_texts['failed_init_kaltura_db']);
}

// create stats database
logMessage(L_INFO, "Setting-up Kaltura stats DB");
if (!DatabaseUtils::createDb($db_params, $app_config['DB_STATS_NAME'])) installationFailed($error_texts['failed_creating_stats_db']);
foreach ($sql_files['stats']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app_config['BASE_DIR'].APP_SQL_SIR.$sql, $db_params, $app_config['DB_STATS_NAME'])) installationFailed($error_texts['failed_init_stats_db']);
}
	
// create the data warehouse
logMessage(L_USER, $flow_texts["dwh"]);
if (!FileUtils::chown($app_config['ETL_HOME_DIR'], 'etl')) installationFailed("Failed chown to etl");
if (!DatabaseUtils::runScript("package/dwh_grants/grants.sql", $db_params, $app_config['DB1_NAME'])) installationFailed($error_texts['failed_running_dwh_sql_script']);
if (!FileUtils::execAsUser($app_config['ETL_HOME_DIR'].'/ddl/dwh_ddl_install.sh', 'etl')) installationFailed($error_texts['failed_running_dwh_script']);

// Create a symbolic link for the logrotate and crontab
logMessage(L_USER, $flow_texts["symlinks"]);
foreach ($installation_config['symlinks']['links'] as $slink) {
	$link_items = explode('^', adjust_path($slink));	
	if (!symlink($link_items[0], $link_items[1])) installationFailed(sprintf($error_texts['failed_sym_link', $link_items[0], $link_items[1]);
	else logMessage(L_INFO, "Created symblic link from $link_items[0] to $link_items[1]");
}

logMessage(L_USER, $flow_texts["config_system"]);
InstallUtils::simMafteach($app_config['KALTURA_VERSION_TYPE'], $app_config['ADMIN_CONSOLE_ADMIN_MAIL'], $app_config['APP_DIR'].'/alpha/config/kConf.php');
@exec($app_config['PHP_BIN'].' '.$app_config['APP_DIR'].'/deployment/base/scripts/populateSphinxEntries.php');

// post install
logMessage(L_USER, $flow_texts["uninstaller"]);
if (!FileUtils::fullCopy('installer/uninstall.php', $app_config['BASE_DIR']."/uninstaller/")) installationFailed($error_texts['failed_creating_uninstaller']);
saveUninstallerConfig($app_config['BASE_DIR']."/uninstaller/uninstall.ini", $app_config);

logMessage(L_USER, $flow_texts["run_system"]);
@exec($app_config['APP_DIR'].'/scripts/serviceBatchMgr.sh start  2>&1');
@exec($app_config['APP_DIR'].'/scripts/searchd.sh start  2>&1');

// send settings mail
if (function_exists('mail')) {
	$msg = sprintf($flow_texts['finish_mail'], $app_config('KALTURA_VIRTUAL_HOST_NAME'),
			$app_config('KALTURA_VIRTUAL_HOST_NAME'), $app_config('ADMIN_CONSOLE_ADMIN_MAIL'),
			$app_config('ADMIN_CONSOLE_PASSWORD')).PHP_EOL;
	@mail('TO', 'Kaltura installation settings', $msg);
}
	
// print after installation instructions
echo PHP_EOL;
logMessage(L_USER, sprintf($flow_texts["install_success"], $app_config['ADMIN_CONSOLE_ADMIN_MAIL'], $app_config['ADMIN_CONSOLE_PASSWORD']));
logMessage(L_USER, sprintf($flow_texts["after_install_steps"], $app_config["KALTURA_VIRTUAL_HOST_NAME"], $app_config["BASE_DIR"], $app_config["HTTPD_BIN"], $app_config["KALTURA_VIRTUAL_HOST_NAME"]));

if ($should_report) {
	reportInstallationSuccess();
}

die(0);