<?php

function connect(&$link, $host, $user, $pass, $db, $port) {
	// set mysqli to connect via tcp
	if ($host == 'localhost') {
		$host = '127.0.0.1';
	}
	if (trim($pass) == '') $pass = null;
	
	$link = @mysqli_init();
	$result = @mysqli_real_connect($link, $host, $user, $pass, $db, $port);	
	if (!$result) {
		return false;
	}
	return true;
}
	
function executeQuery($query, $host, $user, $pass, $db, $port, $link = null) {
	if (!$link && !connect($link, $host, $user, $pass, $db, $port)) return false;
	else if (isset($db) && !mysqli_select_db($link, $db)) return false;

	if (!mysqli_multi_query($link, $query) || $link->error != '') return false;		
	
	while (mysqli_more_results($link) && mysqli_next_result($link)) {
		$discard = mysqli_store_result($link);
	}
	$link->commit();
	return true;
}
	
function dropDb($db, $host, $user, $pass, $port) {
	$drop_db_query = "DROP DATABASE $db;";
	return executeQuery($drop_db_query, $host, $user, $pass, null, $port);
}
	
$config = parse_ini_file("uninstall.ini");
$success = true;

echo 'Stopping application scripts...\n';
@exec($config['BASE_DIR'].'/app/scripts/searchd.sh stop');
@exec($config['BASE_DIR'].'/app/scripts/serviceBatchMgr.sh stop');
echo "Removing /etc/logrotate.d/kaltura_log_rotate ...\n";
@exec("rm -rf /etc/logrotate.d/kaltura_log_rotate");
echo "Removing /etc/cron.d/kaltura_crontab ...\n";
@exec("rm -rf /etc/cron.d/kaltura_crontab");
echo 'Removing data warehouse ...\n';
@exec("sudo -u etl /home/etl/ddl/dwh_drop_databases.sh");
echo "Removing ".$config['ETL_HOME_DIR']." ...\n";
@exec("rm -rf ".$config['ETL_HOME_DIR'].'/*');
@exec("rm -rf ".$config['ETL_HOME_DIR'].'/.kettle');
echo "Removing Kaltura DB ... ";
if (!dropDb($config['DB1_NAME'], $config['DB1_HOST'], $config['DB1_USER'], $config['DB1_PASS'], $config['DB1_PORT'])) {
	$success = false;
	echo "Error\n";
} else {
	echo "\n";
}
echo "Removing Kaltura stats DB ... ";
if (!dropDb($config['DB_STATS_NAME'], $config['DB_STATS_HOST'], $config['DB_STATS_USER'], $config['DB_STATS_PASS'], $config['DB_STATS_PORT'])) {
	$success = false;
	echo "Error\n";
} else {
	echo "\n";
}
echo "Removing ".$config['BASE_DIR']." ...\n";
@exec("rm -rf ".$config['BASE_DIR']);
	
if ($success) echo 'Uninstall finished successfully\n';
else echo 'Some of the uninstall steps failed, please do them manually\n';
echo 'Please maually remove Kaltura related includes from your httpd.conf or httpd-vhosts.conf files\n';

