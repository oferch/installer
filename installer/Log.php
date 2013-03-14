<?php

class Logger
{
	const LEVEL_USER = 0; // user level logging constant
	const LEVEL_ERROR = 1; // error level logging constant
	const LEVEL_WARNING = 2; // warning level logging constant
	const LEVEL_INFO = 3; // info level logging constant
	const DATE_FORMAT = 'd.m.Y H:i:s'; // log file date format

	const COLOR_BLACK			= '0;30';
	const COLOR_DARK_GRAY		= '1;30';
	const COLOR_BLUE			= '0;34';
	const COLOR_LIGHT_BLUE		= '1;34';
	const COLOR_GREEN			= '0;32';
	const COLOR_LIGHT_GREEN		= '1;32';
	const COLOR_CYAN			= '0;36';
	const COLOR_LIGHT_CYAN		= '1;36';
	const COLOR_RED				= '0;31';
	const COLOR_LIGHT_RED		= '1;31';
	const COLOR_PURPLE			= '0;35';
	const COLOR_LIGHT_PURPLE	= '1;35';
	const COLOR_BROWN			= '0;33';
	const COLOR_YELLOW			= '1;33';
	const COLOR_LIGHT_GRAY		= '0;37';
	const COLOR_WHITE			= '1;37';

	/**
	 * @var resiurce
	 */
	protected static $logFile = null;

	/**
	 * @var int
	 */
	protected static $logPrintLevel = self::LEVEL_USER;

	/**
	 * @var int
	 */
	protected static $verbose;

	/**
	 * Start a new log with the given $filename
	 * @param string $filename
	 */
	public static function init($filename, $verbose = false)
	{
		OsUtils::clearScreen();
		self::$logFile = fopen($filename, 'a');
		self::$verbose = $verbose;
	}

	/**
	 * Log a message in the given level, will print to the screen according to the log level and according to the supplied color
	 * @param int $color
	 * @param int $level
	 * @param string $message
	 * @param boolean $newLine
	 * @param boolean $returnChars number of backspace before logging the current message
	 */
	public static function logColorMessage($color, $level, $message, $newLine = true, $returnChars = 0)
	{
		if(OsUtils::getOsName() != OsUtils::WINDOWS_OS)
		{
			if (self::$logPrintLevel >= $level && $returnChars)
				echo str_repeat(chr(8), $returnChars);

			$returnChars = 0;
			echo "\033[{$color}m";
		}

		self::logMessage($level, $message, $newLine, $returnChars);

		if(OsUtils::getOsName() != OsUtils::WINDOWS_OS)
			echo "\033[0m";
	}

	/**
	 * Log a message in the given level, will print to the screen according to the log level
	 * @param int $level
	 * @param string $message
	 * @param boolean $newLine
	 * @param boolean $returnChars number of backspace before logging the current message
	 */
	public static function logMessage($level, $message, $newLine = true, $returnChars = 0)
	{
		$message = str_replace("\\n", PHP_EOL, $message);
		$message = str_replace("\\t", "\t", $message);

		// print to screen according to log level
		if (self::$logPrintLevel >= $level || self::$verbose)
		{
			if($returnChars)
				echo str_repeat(chr(8), $returnChars);

			echo $message;

			if ($newLine)
				echo PHP_EOL;
		}

		if (!self::$logFile)
			return;

		if($returnChars)
			fwrite(self::$logFile, str_repeat(chr(8), $returnChars));

		$logLine = date(self::DATE_FORMAT).' '.$level.' '.$message.PHP_EOL;
		fwrite(self::$logFile, $logLine);
	}
}