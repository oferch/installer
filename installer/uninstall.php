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

echo 'Stopping application scripts... ';
@exec($config['BASE_DIR'].'/app/scripts/searchd.sh stop');
@exec($config['BASE_DIR'].'/app/scripts/serviceBatchMgr.sh stop');
echo 'OK'.PHP_EOL;

echo "Removing /etc/logrotate.d/kaltura_log_rotate... ";
@exec("rm -rf /etc/logrotate.d/kaltura_log_rotate");
echo 'OK'.PHP_EOL;

echo "Removing /etc/cron.d/kaltura_crontab... ";
@exec("rm -rf /etc/cron.d/kaltura_crontab");
echo 'OK'.PHP_EOL;

echo 'Removing data warehouse... ';
@exec($config['BASE_DIR']."/dwh/ddl/dwh_drop_databases.sh");
echo 'OK'.PHP_EOL;

echo "Removing Kaltura DB... ";
if (!dropDb($config['DB1_NAME'], $config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_PORT'])) {
	$success = false;
	echo "Error".PHP_EOL;
} else {
	echo 'OK'.PHP_EOL;
}
echo "Removing Kaltura stats DB... ";
if (!dropDb($config['DB_STATS_NAME'], $config['DB_HOST'], $config['DB_USER'], $config['DB_PASS'], $config['DB_PORT'])) {
	$success = false;
	echo 'Error'.PHP_EOL;
} else {
	echo 'OK'.PHP_EOL;
}
echo "Removing ".$config['BASE_DIR']."...";
@exec("rm -rf ".$config['BASE_DIR']);
echo 'OK'.PHP_EOL.PHP_EOL;
	
if ($success) echo 'Uninstall finished successfully'.PHP_EOL;
else echo 'Some of the uninstall steps failed, please do them manually'.PHP_EOL;

echo PHP_EOL;

echo 'Please maually remove Kaltura related includes from your httpd.conf or httpd-vhosts.conf files'.PHP_EOL;

