<?php

define(LOG_USER,"USER");
define(LOG_ERROR,"ERROR");
define(LOG_WARNING,"WARNING");
define(LOG_INFO,"INFO");
define(LOG_DATE_FORMAT,"d.m.Y H:i:s");

$logFile;
$logPrintLevel=0;

function startLog($date) {
	global $logFile;
	$logFile = "installation_$date.log";	
	writeFile($logFile, "");
}

function logMessage($level, $message) {
	$logLine = date(LOG_DATE_FORMAT).' '.$level.' '.$message.PHP_EOL;
	appendFile($logFile, $logLine);	
	
		// print errors to screen
	if (($level === LOG_USER) && ($logPrintLevel >= 0)) {
		echo $message.PHP_EOL;
	}
	
	// print errors to screen
	if (($level === LOG_ERROR) && ($logPrintLevel >= 1)) {
		echo $logLine;
	}
	
	// print warnings to screen
	if (($level === LOG_WARNING) && ($logPrintLevel >= 2)) {
		echo $logLine;
	}
	
	// print info to screen
	if (($level === LOG_WARNING) && ($logPrintLevel >= 3)) {
		echo $logLine;
	}	
}
