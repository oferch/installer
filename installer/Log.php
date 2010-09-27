<?php

define("L_USER","USER");
define("L_ERROR","ERROR");
define("L_WARNING","WARNING");
define("L_INFO","INFO");
define("L_DATE_FORMAT","d.m.Y H:i:s");

$logFile;
$logPrintLevel=0;

function startLog($filename) {
	global $logFile;
	$logFile = $filename;
	OsUtils::writeFile($logFile, "");
}

function logMessage($level, $message, $no_new_line = false) {
	global $logFile, $logPrintLevel;
	$logLine = date(L_DATE_FORMAT).' '.$level.' '.$message.PHP_EOL;
	OsUtils::appendFile($logFile, $logLine);	
	
	// print to screen according to log level
	if ((($level === L_USER) && ($logPrintLevel >= 0)) ||
		(($level === L_ERROR) && ($logPrintLevel >= 1)) ||
		(($level === L_WARNING) && ($logPrintLevel >= 2)) ||
		(($level === L_INFO) && ($logPrintLevel >= 3))) {
		echo str_replace("\\n",PHP_EOL,$message);
		
		if (!$no_new_line)
			echo PHP_EOL;		
	}
}
