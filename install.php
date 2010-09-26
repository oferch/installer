<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/ConfigUtils.php');
include_once('installer/FileUtils.class.php');
include_once('installer/InstallUtils.class.php');
include_once('installer/UserInputUtils.class.php');
include_once('installer/Prerequisites.class.php');
include_once('installer/InstallationFunctions.php');
include_once('installer/Log.php');

define("K_TM_TYPE", "TM");
define("K_CE_TYPE", "CE");

function adjust_path($path) {
	global $app_config;
	$new_path = str_replace("@BASE_DIR@",$app_config['BASE_DIR'],$path);
	$new_path = str_replace("@ETL_HOME_DIR@",$app_config['ETL_HOME_DIR'],$new_path);
	return $new_path;
}

function installationFailed($error) {
	global $app_config, $texts;
	logMessage(L_USER, "An error has occured during installation: $error");
	logMessage(L_USER, "Cleaning leftovers...");	
	detectLeftovers(false);
//	if ($shouldReport) {
//		reportInstallationFailure();
//	}	
	logMessage(L_USER, $texts['flow']["install_fail"]);
	die(1);
}

/**
 * Checks that needed databases DO NOT exist
 */
function checkDatabases($db_host, $db_user, $db_pass, $db_port, $should_drop=false){
	global $installation_config;
	$verify = null;
	foreach ($installation_config["databases"]["dbs"] as $db) {
		$result = DatabaseUtils::dbExists($db, $db_host, $db_user, $db_pass, $db_port);
		
		if ($result === -1) {
			$verify = $verify."Error verifying if db exists $db".PHP_EOL;
		}
		else if ($result === true) {
			$verify = $verify."DB already exists $db";
			if ($should_drop) DatabaseUtils::dropDb($db, $db_host, $db_user, $db_pass, $db_port);
		}
		else {
			logMessage(L_INFO, "Preqrequisite passed: DB $db does not exists");
		}
	}
	return $verify;
}	
	
function detectLeftovers($report_only) {
	global $app_config;
	$leftovers = null;
	if (is_file('/etc/logrotate.d/kaltura_log_rotate')) {
		$leftovers = $leftovers."kaltura_log_rotate symbolic link exists".PHP_EOL;;		
		if (!$report_only) FileUtils::recursiveDelete('/etc/logrotate.d/kaltura_log_rotate');
	}
	if (is_file('/etc/cron.d/kaltura_crontab')) {
		$leftovers = $leftovers."kaltura_crontab symbolic link exists".PHP_EOL;;	
		if (!$report_only) FileUtils::recursiveDelete('/etc/cron.d/kaltura_crontab');
	}	
	if (is_dir($app_config['BASE_DIR'])) {
		$leftovers = $leftovers."Target directory ".$app_config['BASE_DIR']." exists".PHP_EOL;;
		if (!$report_only) {
			@exec($app_config['BASE_DIR'].'app/scripts/searchd.sh stop  2>&1');
			@exec($app_config['BASE_DIR'].'app/scripts/serviceBatchMgr.sh stop  2>&1');
			FileUtils::recursiveDelete($app_config['BASE_DIR']);			
		}
	}	
	if (($files = @scandir($app_config['ETL_HOME_DIR']."/")) && (count($files) > 5)) {
		$leftovers = $leftovers."Target datawarehouse directory ".$app_config['ETL_HOME_DIR']." exists".PHP_EOL;;
		if (!$report_only) {
			FileUtils::execAsUser('/home/etl/ddl/dwh_drop_databases.sh' , 'etl');
			FileUtils::recursiveDelete($app_config['ETL_HOME_DIR'].'/*');
		}
	}
	$verify = checkDatabases($app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_PORT']);
	if (isset($verify))  {
		$leftovers = $leftovers.$verify.PHP_EOL;;
		if (!$report_only) {
			checkDatabases($app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_PORT'], true);
		}
	}	
	
	if (isset($leftovers)) {
		if ($report_only) logMessage(L_USER, "Installation leftovers are: $leftovers");
		return true;
	} else {
		return false;
	}	
}

$logfilename = "install_log_".date("d.m.Y_H.i.s");
startLog($logfilename);
logMessage(L_INFO, "Installation started");
$texts = parse_ini_file('installer/texts.ini', true);

// installation might take a few minutes
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);
logMessage(L_INFO, "Installation might take a few minutes, set PHP ini values: max_execution_time=0; memory_limit=-1;max_input_time=0");

// clear terminal screen
@system('clear');

// TODO: if loaded with config as parameter - use same input
// TODO: create unique installtion id

$app_config = array();
$user_input_filename = 'user_input.ini';
$user_input;
$should_user_input = true;
$version = parse_ini_file('package/version.ini');
$installation_config = parse_ini_file('installer/installation.ini', true);

$app_config['KALTURA_VERSION'] = 'Kaltura '.$version['type'].' '.$version['number'];
$app_config['KALTURA_VERSION_TYPE'] = $version['type'];
logMessage(L_INFO, "Installing ".'Kaltura '.$version['type'].' '.$version['number']);

logMessage(L_USER, $texts['flow']['welcome_msg']);

// If previous installation found and the user wants to use it
if (is_file($user_input_filename) && 
	UserInputUtils::getTrueFalse(null, $texts['flow']['user_previous_input'], 'y')) {
	$user_input = loadConfigFromFile($user_input_filename);	
	$should_user_input = false;
} else {
	$user_input = array();
}

if (!UserInputUtils::getTrueFalse('PROCEED_WITH_INSTALL', $texts['flow']['proceed_with_install'], 'y')) installationFailed('Bye');

if ($result = ((strcasecmp($app_config['KALTURA_VERSION_TYPE'], K_TM_TYPE) == 0) || 
	(UserInputUtils::getTrueFalse('ASK_TO_REPORT', $texts['flow']['ask_to_report'], 'y')))) {
	$email = UserInputUtils::getInput('REPORT_MAIL', $texts['flow']['report_email']);	
	$app_config['REPORT_ADMIN_EMAIL'] = $email;
	$app_config['TRACK_KDPWRAPPER'] = 'true';
	//reportInstallationStart();
} else {
	$app_config['TRACK_KDPWRAPPER'] = 'false';
}

if (!verifyRootUser()) installationFailed($texts['flow']['user_not_root']);
if (!verifyOS()) installationFailed("Installation can only run on Linux");

if ($should_user_input) logMessage(L_USER, $texts['flow']['config_start']);
else logMessage(L_USER, $texts['flow']['skipping_input']);

// user input
UserInputUtils::getPathInput('HTTPD_BIN', $texts['input']['httpd_bin'], true, false, array('apachectl', 'apache2ctl'));
UserInputUtils::getPathInput('PHP_BIN', $texts['input']['php_bin'], true, false, 'php');
UserInputUtils::getInput('DB1_HOST', $texts['input']['db_host'],'localhost');
UserInputUtils::getInput('DB1_PORT', $texts['input']['db_port'],'3306');
$user_input['DB1_NAME'] = 'kaltura'; // Currently we do not support getting the DB name from the user because of the DWH implementation
UserInputUtils::getInput('DB1_USER', $texts['input']['db_user']);
UserInputUtils::getInput('DB1_PASS', $texts['input']['db_pass']);
$user_input['ETL_HOME_DIR'] = '/home/etl'; // Currently the DWH must be installed in this location
UserInputUtils::getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', $texts['input']['virtual_host_name']);
UserInputUtils::getPathInput('BASE_DIR',  $texts['input']['kaltura_base_dir'], false, true);
if ($should_user_input) logMessage(L_USER, $texts['input']['admin_console_welcome']);
UserInputUtils::getInput('ADMIN_CONSOLE_ADMIN_MAIL', $texts['input']['admin_email']);
UserInputUtils::getInput('ADMIN_CONSOLE_PASSWORD', $texts['input']['admin_password']);
UserInputUtils::getInput('XYMON_URL', $texts['input']['xymon_url']);
//UserInputUtils::getInput('XYMON_ROOT_DIR', );
if ($should_user_input) writeConfigToFile($user_input, $user_input_filename);

logMessage(L_USER, $texts['flow']['prereq_start']);
copyConfig($user_input, $app_config);

// verify prerequisites
$preq = new Prerequisites();
if (!$preq->verifyPrerequisites($app_config)) installationFailed("Please setup the preqrequisites listed and run the installation again");

defineInstallationTokens($app_config);
writeConfigToFile($app_config, "installation_config.ini");

if (detectLeftovers(true)) {
	if (!UserInputUtils::getTrueFalse(null, $texts['flow']["leftovers_found"], 'n')) installationFailed("Please cleanup the previous installation and run the installer again");
	else detectLeftovers(false);
} else {	
	logMessage(L_INFO, "No previous installation leftovers were found, installation can proceed");
}

// installation

logMessage(L_USER, $texts['flow']["starting_installation"]);

// copy files	
logMessage(L_USER, $texts['flow']["copying_files"]);
if (!FileUtils::fullCopy('package/app/', $app_config['BASE_DIR'], true)) installationFailed("Failed copying Kaltura application to target directory");
if (!FileUtils::fullCopy('package/dwh/*', $app_config['ETL_HOME_DIR']."/", true)) installationFailed("Failed copying data warehouse to target directory");

// replace tokens in configuration files
logMessage(L_USER, $texts['flow']["replacing_tokens"]);
foreach ($installation_config['token_files']['files'] as $file) {
	$replace_file = adjust_path($file);
	$replace_file = FileUtils::copyTemplateFileIfNeeded($replace_file);
	if (!FileUtils::replaceTokensInFile($app_config, $replace_file)) installationFailed("Failed to replace tokens in files");
}
	
// ajust to the system architecture
$os_name = 	InstallUtils::getOsName(); // Already verified that the OS is supported in the prerequisites
$architecture = InstallUtils::getSystemArchitecture();	
logMessage(L_USER, sprintf($texts['flow']["adjusting_architecture"], $os_name, $architecture));
$bin_subdir = $os_name.'/'.$architecture;
if (!FileUtils::fullCopy($app_config['BIN_DIR'].'/'.$bin_subdir, $app_config['BIN_DIR'], true)) installationFailed("Failed to copy architecture binaries");
if (!FileUtils::recursiveDelete($app_config['BIN_DIR'].'/'.$os_name)) installationFailed("Failed to delete non-architecture binaries");

// chmod
logMessage(L_USER, $texts['flow']["chmoding"]);
foreach ($installation_config['chmod_items']['items'] as $item) {
	$chmod_item = adjust_path($item);
	if (!FileUtils::chmod($chmod_item)) installationFailed("Failed to chmod file");
}

// create databases
logMessage(L_USER, $texts['flow']["database"]);
$sql_dir = "/app/deployment/base/sql/";
$sql_files = parse_ini_file($app_config['BASE_DIR'].$sql_dir.'create_kaltura_db.ini', true);

logMessage(L_INFO, "Setting-up Kaltura DB");
if (!DatabaseUtils::createDb($app_config['DB1_NAME'], $app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_PORT'])) installationFailed("Failed to create Kaltura db");
foreach ($sql_files['kaltura']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app_config['BASE_DIR'].$sql_dir.$sql, $app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_NAME'], $app_config['DB1_PORT'])) installationFailed("Failed to initialize Kaltura db");
}

// create stats database
logMessage(L_INFO, "Setting-up Kaltura stats DB");
if (!DatabaseUtils::createDb($app_config['DB_STATS_NAME'], $app_config['DB_STATS_HOST'], $app_config['DB_STATS_USER'], $app_config['DB_STATS_PASS'], $app_config['DB_STATS_PORT'])) installationFailed("Failed to create stats db");
foreach ($sql_files['stats']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app_config['BASE_DIR'].$sql_dir.$sql, $app_config['DB_STATS_HOST'], $app_config['DB_STATS_USER'], $app_config['DB_STATS_PASS'], $app_config['DB_STATS_NAME'], $app_config['DB_STATS_PORT'])) installationFailed("Failed to initialize stats db");
}
	
// create the data warehouse
logMessage(L_USER, $texts['flow']["dwh"]);
if (!FileUtils::chown($app_config['ETL_HOME_DIR'], 'etl')) installationFailed("Failed chown to etl");
if (!DatabaseUtils::runScript("package/dwh_grants/grants.sql", $app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_NAME'], $app_config['DB1_PORT'])) installationFailed("Failed running dwh grant script");
if (!FileUtils::execAsUser($app_config['ETL_HOME_DIR'].'/ddl/dwh_ddl_install.sh', 'etl')) installationFailed("Failed running dwh script");

// Create a symbolic link for the logrotate and crontab
logMessage(L_USER, $texts['flow']["symlinks"]);
foreach ($installation_config['symlinks']['links'] as $slink) {
	$link_items = explode('^', adjust_path($slink));	
	if (!symlink($link_items[0], $link_items[1])) installationFailed("Failed to create symblic link from $link_items[0] to $link_items[1]");
	else logMessage(L_INFO, "Created symblic link from $link_items[0] to $link_items[1]");
}

logMessage(L_USER, $texts['flow']["config_system"]);
InstallUtils::simMafteach($app_config['KALTURA_VERSION_TYPE'], $app_config['ADMIN_CONSOLE_ADMIN_MAIL'], $app_config['APP_DIR'].'/alpha/config/kConf.php');
@exec($app_config['PHP_BIN'].' '.$app_config['APP_DIR'].'/deployment/base/scripts/populateSphinxEntries.php');

// post install
logMessage(L_USER, $texts['flow']["uninstaller"]);
if (!FileUtils::fullCopy('installer/uninstall.php', $app_config['BASE_DIR']."/uninstaller/")) installationFailed("Failed copying uninstaller");
saveUninstallerConfig($app_config['BASE_DIR']."/uninstaller/uninstall.ini", $app_config);

logMessage(L_USER, $texts['flow']["run_system"]);
@exec($app_config['APP_DIR'].'/scripts/serviceBatchMgr.sh start  2>&1');
@exec($app_config['APP_DIR'].'/scripts/searchd.sh start  2>&1');
//sendSuccessMail();
logMessage(L_USER, sprintf($texts['flow']["install_success"], $app_config['ADMIN_CONSOLE_ADMIN_MAIL'], $app_config['ADMIN_CONSOLE_PASSWORD']));
logMessage(L_USER, sprintf($texts['flow']["after_install_steps"], $app_config["KALTURA_VIRTUAL_HOST_NAME"], $app_config["BASE_DIR"], $app_config["HTTPD_BIN"], $app_config["KALTURA_VIRTUAL_HOST_NAME"]));

//if ($shouldReport) {
//	reportInstallationSuccess();
//}

die(0);



