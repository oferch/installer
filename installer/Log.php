<?php
include_once(__DIR__ . '/phpmailer/class.phpmailer.php');

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
	 * @var string
	 */
	protected static $logFilePath = null;

	/**
	 * @var resource
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
	 * @var string
	 */
	protected static $email;

	/**
	 * @var string
	 */
	protected static $emailContent = null;

	/**
	 * @var array
	 */
	protected static $errors = array();

	/**
	 * Start a new log with the given $filename
	 * @param string $filename
	 */
	public static function init($filename, $verbose = false)
	{
		OsUtils::clearScreen();
		self::$logFile = fopen($filename, 'a');
		self::$logFilePath = $filename;
		self::$verbose = $verbose;
	}

	/**
	 * @param string $email
	 */
	public static function setEmail($email)
	{
		self::$email = $email;
	}

	/**
	 * Wrap text with color
	 * @param int $color
	 * @param string $message
	 * @return string
	 */
	public static function colorMessage($color, $message)
	{
		if(OsUtils::getOsName() == OsUtils::WINDOWS_OS)
			return $message;
			
		return "\033[{$color}m{$message}\033[0m";
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
	 * Start concatenating any log message in order to send later as mail body
	 * Recording stopped by calling clearEmail or sendEmail
	 */
	public static function recordEmail()
	{
		self::$emailContent = '';
	}

	/**
	 * Stop concatenating log messages
	 */
	public static function clearEmail()
	{
		self::$emailContent = null;
	}

	/**
	 * Send all recorded logs
	 */
	public static function sendErrors()
	{
		if(!count(self::$errors))
			return;
			
		self::$emailContent = "Installation completed with errors:\n\n" . implode("\n", self::$errors);
		self::sendEmail();
	}

	/**
	 * Send all recorded logs
	 */
	public static function sendEmail()
	{
		if(is_null(self::$emailContent) || is_null(self::$email))
			return;
			
		if(self::$email === false)
			self::$email = $virtualHostName = AppConfig::get(AppConfigAttribute::ADMIN_CONSOLE_ADMIN_MAIL);
			
		// send settings mail if possible
		$virtualHostName = AppConfig::get(AppConfigAttribute::KALTURA_FULL_VIRTUAL_HOST_NAME);

		$mailer = new PHPMailer();
		$mailer->CharSet = 'utf-8';
		$mailer->IsHTML(false);
		$mailer->AddAddress(self::$email);
		$mailer->Sender = "installation.results@$virtualHostName";
		$mailer->From = "installation.results@$virtualHostName";
		$mailer->FromName = AppConfig::get(AppConfigAttribute::ENVIRONMENT_NAME);
		$mailer->Subject = "Kaltura Installation Results [$virtualHostName]";
		$mailer->Body = self::$emailContent;

		if(file_exists(self::$logFilePath))
			$mailer->AddAttachment(self::$logFilePath, 'install.log', 'base64', 'text/plain');
			
		if(file_exists(OsUtils::getLogPath()))
			$mailer->AddAttachment(OsUtils::getLogPath(), 'details.log', 'base64', 'text/plain');
		
		if ($mailer->Send())
			Logger::logColorMessage(Logger::COLOR_LIGHT_GREEN, Logger::LEVEL_USER, "Results installation email sent to " . self::$email);
		else
			Logger::logColorMessage(Logger::COLOR_LIGHT_RED, Logger::LEVEL_USER, "Results installation email cannot be sent");

		self::clearEmail();
	}

	/**
	 * Log a message in the given level, will print to the screen according to the log level
	 * @param int $level
	 * @param string $message
	 * @param boolean $newLine
	 * @param boolean $returnChars number of backspace before logging the current message
	 */
	public static function logError($level, $message, $returnChars = 0)
	{
		self::$errors[] = $message;
		self::logColorMessage(Logger::COLOR_RED, $level, $message, true, $returnChars);
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
				
			if(!is_null(self::$emailContent))
			{
				if($returnChars)
					self::$emailContent = substr(self::$emailContent, 0, $returnChars * -1);
					
				self::$emailContent .= $message;
				
				if ($newLine)
					self::$emailContent .= PHP_EOL;
			}
		}
	
		if (!self::$logFile)
			return;

		if($returnChars)
			fwrite(self::$logFile, str_repeat(chr(8), $returnChars));

		$logLine = date(self::DATE_FORMAT).' '.$level.' '.$message.PHP_EOL;
		fwrite(self::$logFile, $logLine);
	}
}