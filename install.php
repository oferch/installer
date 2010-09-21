<?php 

include_once('installer/DatabaseUtils.class.php');
include_once('installer/ConfigUtils.php');
include_once('installer/FileUtils.class.php');
include_once('installer/InstallUtils.class.php');
include_once('installer/UserInputUtils.class.php');
include_once('installer/Prerequisites.class.php');
include_once('installer/InstallationFunctions.php');

// installation might take a few minutes
$time_limit       = ini_get('max_execution_time');
$memory_limit     = ini_get('memory_limit');
$input_time_limit = ini_get('max_input_time');
ini_set('max_execution_time', 0);
ini_set('memory_limit', -1);
ini_set('max_input_time ', 0);

// clear terminal screen
@system('clear');

function adjust_path($path) {
	global $app_config;
	$new_path = str_replace("@BASE_DIR@",$app_config['BASE_DIR'],$path);
	$new_path = str_replace("@ETL_HOME_DIR@",$app_config['ETL_HOME_DIR'],$new_path);
	return $new_path;
}

function installationFailed($error) {
	global $app_config;
	echo "installation failed: $error".PHP_EOL;
	echo "cleaning leftovers".PHP_EOL;
	detectLeftovers(false);
	echo "what to do".PHP_EOL;
//	if ($shouldReport) {
//		reportInstallationFailure();
//	}	
	die(1);
}

function detectLeftovers($report_only) {
	global $app_config, $preq;
	$leftovers = null;
	if (is_dir($app_config['BASE_DIR'])) {
		$leftovers = $leftovers."Target directory ".$app_config['BASE_DIR']." exists".PHP_EOL;;
		if (!$report_only) {
			@exec($app_config['BASE_DIR'].'app/scripts/searchd.sh stop  2>&1');
			@exec($app_config['BASE_DIR'].'app/scripts/serviceBatchMgr.sh stop  2>&1');
			FileUtils::recursiveDelete($app_config['BASE_DIR']);			
		}
	}	
	if ((($files = @scandir($app_config['ETL_HOME_DIR'])) && count($files) <= 2)) {
		$leftovers = $leftovers."Target datawarehouse directory ".$app_config['ETL_HOME_DIR']." exists".PHP_EOL;;
		if (!$report_only) {
			FileUtils::execAsUser('/home/etl/ddl/dwh_drop_databases.sh' , 'etl');
			FileUtils::recursiveDelete($app_config['ETL_HOME_DIR'].'/*');
		}
	}
	if (is_file('/etc/logrotate.d/kaltura_log_rotate')) {
		$leftovers = $leftovers."kaltura_log_rotate symbolic link exists".PHP_EOL;;		
		if (!$report_only) FileUtils::recursiveDelete('/etc/logrotate.d/kaltura_log_rotate');
	}
	if (is_file('/etc/cron.d/kaltura_crontab')) {
		$leftovers = $leftovers."kaltura_crontab symbolic link exists".PHP_EOL;;	
		if (!$report_only) FileUtils::recursiveDelete('/etc/cron.d/kaltura_crontab');
	}
	$verify = $preq->checkDatabases($app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_PORT']);
	if (isset($verify))  {
		$leftovers = $leftovers.$verify.PHP_EOL;;
		if (!$report_only) {
			$preq->checkDatabases($app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_PORT'], true);
		}
	}	
	
	if (isset($leftovers)) {
		if ($report_only) echo $leftovers;
		return true;
	} else {
		return false;
	}	
}

// TODO: if loaded with config as parameter - use same input
// TODO: create unique installtion id

$app_config = array();
$install_phase = 0;

try {
	$version = parse_ini_file('package/version.ini');
	$app_config['KALTURA_VERSION'] = 'Kaltura '.$version['type'].' '.$version['number'];
	$app_config['KALTURA_VERSION_TYPE'] = $version['type'];
}
catch (Exception $e) {
	installationFailed('F1. package/version.ini not valid');
}

$user_input_filename = 'user_input.ini';
$user_input;
$should_user_input = true;

echo PHP_EOL.'1.Welcome to the installation of Kaltura'.PHP_EOL;

// If previous installation found and the user wants to use it
if (is_file($user_input_filename) && 
	UserInputUtils::getTrueFalse(null, '2.A previous installation found, do you want to use the same configuration?', 'y')) {
	$user_input = loadConfigFromFile($user_input_filename);	
	$should_user_input = false;
} else {
	$user_input = array();
}

if (!UserInputUtils::getTrueFalse('PROCEED_WITH_INSTALL', '3. Do you want to install Kaltura?', 'y')) installationFailed('F2. User does not want to install kaltura');

if ($result = ((strcasecmp($app_config['KALTURA_VERSION_TYPE'], 'TM') == 0) || 
	(UserInputUtils::getTrueFalse('ASK_TO_REPORT', '4. Do you want to report', 'y')))) {
	$email = UserInputUtils::getInput('REPORT_MAIL', "5.Please insert report email");
	$app_config['REPORT_ADMIN_EMAIL'] = $email;
	$app_config['TRACK_KDPWRAPPER'] = 'true';
	//reportInstallationStart();
} else {
	$app_config['TRACK_KDPWRAPPER'] = 'false';
}

if (!verifyRootUser()) installationFailed("F3. Installation must run under root user");
if (!verifyOS()) installationFailed("F4. Installation can only run on linux");

if ($should_user_input) echo PHP_EOL.'6.Getting user configuration input'.PHP_EOL.PHP_EOL;
else echo PHP_EOL.'30.Skipping user input, using previous configuration'.PHP_EOL.PHP_EOL;

// user input
UserInputUtils::getPathInput('HTTPD_BIN', '7.Please insert httpd bin', true, false, array('apachectl', 'apache2ctl'));
UserInputUtils::getPathInput('PHP_BIN', '8.Please insert php bin', true, false, 'php');
UserInputUtils::getInput('DB1_HOST', '9.Please insert db host','localhost');
UserInputUtils::getInput('DB1_PORT', '10.Please insert db port','3306');
$user_input['DB1_NAME'] = 'kaltura'; // Currently we do not support getting the DB name from the user because of the DWH implementation
UserInputUtils::getInput('DB1_USER', '11.Please insert db user');
UserInputUtils::getInput('DB1_PASS', '12.Please insert db pass');
$user_input['ETL_HOME_DIR'] = '/home/etl/'; // Currently the DWH must be installed in this location
UserInputUtils::getInput('KALTURA_FULL_VIRTUAL_HOST_NAME', '13.Please insert vhost name');
UserInputUtils::getPathInput('BASE_DIR',  '13.Please insert target directory', false, true);
if ($should_user_input) echo PHP_EOL.'14.Admin_console_welcome'.PHP_EOL;
UserInputUtils::getInput('ADMIN_CONSOLE_ADMIN_MAIL', '15.Please insert admin email');
UserInputUtils::getInput('ADMIN_CONSOLE_PASSWORD', '16. Please insert admin password');
UserInputUtils::getInput('XYMON_URL', '17.Please insert xymon url');
//UserInputUtils::getInput('XYMON_ROOT_DIR', );
if (!$should_user_input) writeConfigToFile($user_input, $user_input_filename);

echo PHP_EOL.'18.Starting prerequisites veification'.PHP_EOL.PHP_EOL;
copyConfig($user_input, $app_config);

// verify prerequisites
$preq = new Prerequisites();
if (!$preq->verifyPrerequisites($app_config)) installationFailed("F5. Please setup the preqrequisites listed and run the installation again");

defineInstallationTokens($app_config);

if (detectLeftovers(true)) {
	if (!UserInputUtils::getTrueFalse(null, '19. Installation found leftovers from previous installation of Kaltura. In order to advance forward the leftovers must be removed. Do you wish to remove them now?', 'n')) installationFailed("F6. Please cleanup the previous installation and run the installer again");
	else detectLeftovers(false);
}

// installation

echo PHP_EOL.'20.Starting installation'.PHP_EOL;
$installation_config = parse_ini_file('installer/installation.ini', true);

echo PHP_EOL.'21.Copying files'.PHP_EOL;
// copy files	
if (!FileUtils::fullCopy('package/app/', $app_config['BASE_DIR'], true)) installationFailed("F7. Cannot copy the application");
if (!FileUtils::fullCopy('package/dwh/', $app_config['ETL_HOME_DIR'], true)) installationFailed("F8. Cannot copy the data warehouse");

$install_phase = 1;

echo PHP_EOL.'22.Replacing tokens in configuration files'.PHP_EOL;
// replace tokens in configuration files
foreach ($installation_config['token_files']['files'] as $file) {
	$replace_file = adjust_path($file);
	$replace_file = FileUtils::copyTemplateFileIfNeeded($replace_file);
	if (!FileUtils::replaceTokensInFile($app_config, $replace_file)) installationFailed("F9. Failed to replace tokens in files");
}
	
// ajust to the system architecture
$os_name = 	InstallUtils::getOsName(); // Already verified that the OS is supported in the prerequisites
$architecture = InstallUtils::getSystemArchitecture();	
echo PHP_EOL."23.Adjusting binaries to the system architecture $os_name, $architecture".PHP_EOL;
$bin_subdir = $os_name.'/'.$architecture;
if (!FileUtils::fullCopy($app_config['BIN_DIR'].'/'.$bin_subdir, $app_config['BIN_DIR'], true)) installationFailed("F10. Failed to copy os specific binaries");
if (!FileUtils::recursiveDelete($app_config['BIN_DIR'].'/'.$os_name)) installationFailed("F11. Failed to delete binaries");

// chmod
echo PHP_EOL."24.chmod-ing files".PHP_EOL;
foreach ($installation_config['chmod_items']['items'] as $item) {
	$chmod_item = adjust_path($item);
	if (!FileUtils::chmod($chmod_item)) installationFailed("F12. Failed to chmod file");
}

// create databases
echo PHP_EOL."25.Setting up databases".PHP_EOL;
$sql_dir = "/app/deployment/base/sql/";
$sql_files = parse_ini_file($app_config['BASE_DIR'].$sql_dir.'create_kaltura_db.ini', true);

if (!DatabaseUtils::createDb($app_config['DB1_NAME'], $app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_PORT'])) installationFailed("F13. Failed to create db");
foreach ($sql_files['kaltura']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app_config['BASE_DIR'].$sql_dir.$sql, $app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_NAME'], $app_config['DB1_PORT'])) installationFailed("F14. Failed to initialize db");
}

// create stats database
if (!DatabaseUtils::createDb($app_config['DB_STATS_NAME'], $app_config['DB_STATS_HOST'], $app_config['DB_STATS_USER'], $app_config['DB_STATS_PASS'], $app_config['DB_STATS_PORT'])) installationFailed("F15. Failed to create stats db");
foreach ($sql_files['stats']['sql'] as $sql) {
	if (!DatabaseUtils::runScript($app_config['BASE_DIR'].$sql_dir.$sql, $app_config['DB_STATS_HOST'], $app_config['DB_STATS_USER'], $app_config['DB_STATS_PASS'], $app_config['DB_STATS_NAME'], $app_config['DB_STATS_PORT'])) installationFailed("F16. Failed to initialize stats db");
}
	
// create the data warehouse
echo PHP_EOL."26.Creating datawarehouse".PHP_EOL;
if (!FileUtils::chown($app_config['ETL_HOME_DIR'], 'etl')) installationFailed("F17. Failed chown to etl");
if (!DatabaseUtils::runScript("package/dwh_grants/grants.sql", $app_config['DB1_HOST'], $app_config['DB1_USER'], $app_config['DB1_PASS'], $app_config['DB1_NAME'], $app_config['DB1_PORT'])) installationFailed("F18. Failed running grant script");
if (!FileUtils::execAsUser($app_config['ETL_HOME_DIR'].'/ddl/dwh_ddl_install.sh', 'etl')) installationFailed("F19. Failed running dwh script");

// Create a symbolic link for the logrotate and crontab
echo PHP_EOL."26.Creating symblic links".PHP_EOL;
foreach ($installation_config['symlinks']['links'] as $slink) {
	$link_items = explode('^', adjust_path($slink));
	if (!symlink($link_items[0], $link_items[1])) installationFailed("F20. Failed creating symbloic link");
}

echo PHP_EOL."27.Configuring system".PHP_EOL;
InstallUtils::simMafteach($app_config['KALTURA_VERSION_TYPE'], $app_config['REPORT_ADMIN_EMAIL'], $app_config['APP_DIR'].'/alpha/config/kConf.php');
@exec($app_config['PHP_BIN'].' '.$app_config['APP_DIR'].'/deployment/base/scripts/populateSphinxEntries.php');

// post install
echo PHP_EOL."28.Creating uninstaller".PHP_EOL;
// copy installation files: install.log, install_config.ini, user_input.ini
// copy uninstaller files and create the uninstaller itself
echo PHP_EOL."29.Running the system".PHP_EOL;
@exec($app_config['APP_DIR'].'/scripts/serviceBatchMgr.sh start  2>&1');
@exec($app_config['APP_DIR'].'/scripts/searchd.sh start  2>&1');
//sendSuccessMail();
//printInstallationEndMessage();
echo PHP_EOL."30.Installation finished successfully".PHP_EOL;

//if ($shouldReport) {
//	reportInstallationSuccess();
//}

// resetting parameters
ini_set('max_execution_time', $time_limit);
ini_set('memory_limit', $memory_limit);
ini_set('max_input_time ', $input_time_limit);

die(0);



