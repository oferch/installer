<?php

class Logger
{
	const LEVEL_USER = 0; // user level logging constant
	const LEVEL_ERROR = 1; // error level logging constant
	const LEVEL_WARNING = 2; // warning level logging constant
	const LEVEL_INFO = 3; // info level logging constant
	const DATE_FORMAT = 'd.m.Y H:i:s'; // log file date format

	/**
	 * @var resiurce
	 */
	protected static $logFile = null;

	/**
	 * @var int
	 */
	protected static $logPrintLevel = self::LEVEL_USER;

	/**
	 * Start a new log with the given $filename
	 * @param string $filename
	 */
	public static function init($filename)
	{
		self::$logFile = fopen($filename, 'a');
	}

	/**
	 * Log a message in the given level, will print to the screen according to the log level
	 * @param int $level
	 * @param string $message
	 * @param boolean $newLine
	 * @param boolean $returnChars number of backspace before logging the current message
	 */
	public static function logMessage($level, $message, $newLine = true, $returnChars = 0) {

		if (!self::$logFile)
			return;

		if($returnChars)
			fwrite(self::$logFile, str_repeat(chr(8), $returnChars));

		$message = str_replace("\\n", PHP_EOL, $message);
		$message = str_replace("\\t", "\t", $message);
		$logLine = date(self::DATE_FORMAT).' '.$level.' '.$message.PHP_EOL;
		fwrite(self::$logFile, $logLine);

		// print to screen according to log level
		if (self::$logPrintLevel >= $level)
		{
			if($returnChars)
				echo str_repeat(chr(8), $returnChars);

			echo $message;

			if ($newLine)
				echo PHP_EOL;
		}
	}
}