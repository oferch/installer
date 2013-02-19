<?php

include_once(__DIR__ . '/Validator.class.php');

$usage_string = 'Usage is php '.__FILE__.' <apachectl> <db host> <db port> <db user> <db pass>'.PHP_EOL;
$usage_string .= 'Prints all the missing prerequisites and exits with code 0 if all verifications passes and 1 otherwise'.PHP_EOL;

if (count($argv) < 5) {
	echo $usage_string;
	exit(1);
}

// get user arguments
$db_params = array();
$httpd_bin = trim($argv[1]);
$db_params['db_host'] = trim($argv[2]);
$db_params['db_port'] = trim($argv[3]);
$db_params['db_user'] = trim($argv[4]);
if (count($argv) > 5) $db_params['db_pass'] = trim($argv[5]);
else $db_params['db_pass'] = "";

$validator = new Validator();
$prerequisites = $validator->validate($db_params, $httpd_bin);

if (count($prerequisites))
{
	echo implode("\n", $prerequisites);
	exit(1);
}

exit(0);

