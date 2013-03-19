<?php
require_once __DIR__ . '/ProgressBarBase.php';

class ProgressBar extends ProgressBarBase
{
	const OS_WINDOWS = 'WINDOWS';
	const OS_LINUX = 'LINUX';

	/**
	 * @var string
	 */
	protected static $os = null;

	/**
	 * @var int
	 */
	protected static $height = null;

	/**
	 * @var int
	 */
	protected static $width = null;

	/**
	 * @var string
	 */
	protected static $buffer = '';

	/**
	 * @var array
	 */
	protected static $headers = array();

	public function __construct($name, $max, $titleWidth = null)
	{
		if(!count(self::$instances))
			ob_start();

		self::setOs();

		parent::__construct($name, $max, $titleWidth);
	}

	public function __destruct()
	{
		$index = array_search($this, self::$instances);
		if($index !== false)
			unset(self::$instances[$index]);

		if(!count(self::$instances))
			self::finish();
	}

	public static function terminateAll()
	{
		parent::terminateAll();
		self::finish();
	}

	public static function finish()
	{
		self::$buffer .= ob_get_clean();
		
		@ob_end_clean();
		echo "\n";
		
		if(self::$buffer)
			echo self::$buffer;

		self::$buffer = '';
		self::$instances = array();
	}
	
	protected function updateBar()
	{
		self::update();
	}

	protected function getLine()
	{
		$prefix = str_pad($this->percent, 3, ' ', STR_PAD_LEFT) . "% [";
		$sufix = "] ";
		if($this->title && $this->titleWidth)
		{
			if(strlen($this->title) > $this->titleWidth)
				$this->title = substr($this->title, 0, $this->titleWidth - 3) . '...';
			$sufix .= str_pad($this->title, $this->titleWidth, ' ', STR_PAD_RIGHT);
		}
		elseif($this->current && $this->max)
		{
			$sufix .= str_pad($this->current, strlen($this->max), ' ', STR_PAD_LEFT) . '/' . $this->max;
		}

		$width = self::$width - strlen($prefix . $sufix);
		$currentLength = max(0, round(($width - 2) / 100 * $this->percent));
		$leftLength = max(0, $width - $currentLength - 2);

		$currentString = str_repeat('=', $currentLength);
		$leftString = str_repeat(' ', $leftLength);
		$currentString .= $leftString ? '>' : '=';

		return $prefix . $currentString . $leftString . $sufix;
	}

	public static function removeHeader($name)
	{
		unset(self::$headers[$name]);
	}

	public static function setHeader($name, $text)
	{
		if(isset(self::$headers[$name]))
			self::$headers[$name] = $text;
	}

	public static function addHeader($text, $name = null)
	{
		if($name)
			self::$headers[$name] = $text;
		else
			self::$headers[] = $text;
	}

	protected static function update()
	{
		self::setWidth();

		if(self::$os == self::OS_WINDOWS)
		{
			echo str_repeat("\n", self::$height - count(self::$instances) - count(self::$headers));
		}
		else
		{
			system('clear 2>&1');
		}

		self::$buffer .= ob_get_clean();
		
		foreach(self::$headers as $header)
			echo "$header\n";

		foreach(self::$instances as $instance)
			echo $instance->getLine() . "\n";

		@ob_flush();
	}

	protected static function setOs()
	{
		if(self::$os)
			return;

		if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
		{
			self::$os = self::OS_WINDOWS;
		}
		elseif (strtoupper(substr(PHP_OS, 0, 5)) === 'LINUX')
		{
			self::$os = self::OS_LINUX;
		}
	}

	protected static function setWidth()
	{
		$output = null;
		$returnedValue = null;
		$matches = null;

		if(self::$os == self::OS_WINDOWS)
		{
			exec('mode CON', $output, $returnedValue);
			if($returnedValue)
				throw new Exception("Unable to find console width");

			foreach($output as $line)
			{
				if(preg_match('/Columns:\s*(\d+)/', $line, $matches))
					self::$width = intval($matches[1]);
				if(preg_match('/Lines:\s*(\d+)/', $line, $matches))
					self::$height = intval($matches[1]);
			}
			return;
		}

		if(self::$os == self::OS_LINUX)
		{
			exec('tput cols', $output, $returnedValue);
			if($returnedValue)
				throw new Exception("Unable to find console width");

			foreach($output as $line)
			{
				if(preg_match('/^(\d+)$/', $line, $matches))
				{
					self::$width = intval($matches[1]);
					return;
				}
			}
		}
	}
}

