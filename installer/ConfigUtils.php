<?php

/**
 * Write configurations to file as key = value
 * @param string $filename file name to write
 * @return true on success, or ErrorObject on failure
 */
function writeConfigToFile($config, $filename) {
	logMessage(L_INFO, "Writing config to file $filename");
	$data = '';
	foreach ($config as $key => $value) {
		$data = $data . $key.' = '.$value.PHP_EOL;
	}
	return FileUtils::writeFile($filename, $data);
}

/**
 * Load configurations from file
 * @param $filename file name to read from
 */
function loadConfigFromFile($filename) {	
	if (is_file($filename)) {
		$config = parse_ini_file($filename);
		logMessage(L_INFO, "Loaded config to file $filename");
		return $config;			
	}
	return null;
}

function copyConfig(&$config_source, &$config_target) {
	foreach ($config_source as $key => $value) {
		$config_target[$key] = $value;
	}
}
