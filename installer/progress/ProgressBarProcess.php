<?php
require_once __DIR__ . '/ProgressBar.php';
require_once(__DIR__ . '/ProgressProcess.php');

class ProgressBarProcess extends ProgressBarBase
{
	protected $name = null;

	protected static $clientSocket = null;

	public function __construct($name, $max, $titleWidth = null)
	{
		$this->name = getmypid() . ":$name";
		$arguments = array(
			$this->name,
			$max,
			$titleWidth
		);
		$this->tell(__FUNCTION__, $arguments);

		parent::__construct($name, $max, $titleWidth);
	}

	public function __destruct()
	{
		$this->tell(__FUNCTION__);
	}

	protected function updateBar()
	{
		if($this->current)
		{
			$this->tell('setCurrent', array(
				$this->current,
				$this->title
			));
		}
		elseif($this->percent)
		{
			$this->tell('setPercent', array(
				$this->percent
			));
		}
		elseif($this->title)
		{
			$this->tell('setTitle', array(
				$this->title
			));
		}
	}

	public function setMax($max)
	{
		parent::setMax($max);
		self::tell(__FUNCTION__, func_get_args());
	}

	public static function removeHeader($name)
	{
		self::send(null, __FUNCTION__, func_get_args());
	}

	public static function setHeader($name, $text)
	{
		self::send(null, __FUNCTION__, func_get_args());
	}

	public static function addHeader($text, $name = null)
	{
		self::send(null, __FUNCTION__, func_get_args());
	}

	public static function terminateAll()
	{
		parent::terminateAll();
		self::send(null, __FUNCTION__);
	}

	protected function tell($method, array $arguments = array())
	{
		self::send($this->name, $method, $arguments);
	}

	protected static function send($name, $method, array $arguments = array(), $port = 6006)
	{
		if(!self::$clientSocket)
			self::$clientSocket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if(!self::$clientSocket)
			return;

		$data = array(
			'method' => $method,
			'arguments' => $arguments,
		);

		if($name)
			$data['name'] = $name;

		$msg = json_encode($data) . "\n";
		socket_sendto(self::$clientSocket, $msg, strlen($msg), 0, '127.0.0.1', $port);
	}

	public static function listen(array $processes, $port = 6006, $timeout = 10)
	{
		$success = true;

		$socket = socket_create(AF_INET, SOCK_DGRAM, SOL_UDP);
		if(!$socket)
		{
			Logger::logMessage(Logger::LEVEL_ERROR, 'Socket create failed: ' . socket_strerror(socket_last_error()));
			return false;
		}

		if(! socket_bind($socket, "0.0.0.0", $port))
		{
			socket_close($socket);
			Logger::logMessage(Logger::LEVEL_ERROR, 'Socket bind failed: ' . socket_strerror(socket_last_error()));
			return false;
		}
		socket_set_nonblock($socket);


		$reflectionClass = new ReflectionClass('ProgressBar');
		while(true)
		{
			foreach($processes as $index => $process)
			{
				/* @var $process ProgressProcess */
				if(!$process->isRunning())
				{
					if($process->getExitCode() !== 0)
					{
						Logger::logMessage(Logger::LEVEL_ERROR, "Process failed, exit code [" . $process->getExitCode() . "]");
						$success = false;
					}

					unset($processes[$index]);
				}
			}

			if(!@socket_recv($socket, $data, 1024, 0))
			{
				if(!count($processes))
				{
					socket_close($socket);
					return $success;
				}

				usleep(100000);
				continue;
			}

			$method = json_decode($data);

			switch ($method->method)
			{
				case '__construct':
					$reflectionClass->newInstanceArgs($method->arguments);
					break;

				case '__destruct':
					unset(self::$instances[$method->name]);
					break;

				default:
					
					if(isset($method->name))
					{
						call_user_func_array(array(self::$instances[$method->name], $method->method), $method->arguments);
					}
					else
					{
						call_user_func_array(array('ProgressBar', $method->method), $method->arguments);
					}

					break;
			}
		}
		socket_close($socket);

		return $success;
	}
}

