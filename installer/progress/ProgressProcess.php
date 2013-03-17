<?php

class ProgressProcess
{
	/**
	 * The full command line to be executed
	 * @var string
	 */
	protected $command;

	/**
	 * Name to be used in the logs
	 * @var string
	 */
	protected $name;

	/**
	 * The initial working dir for the command
	 * @var string
	 */
	protected $dir;

	/**
	 * File name to be used as standard input
	 * @var string
	 */
	protected $stdin;

	/**
	 * File name to be used as standard output
	 * @var string
	 */
	protected $stdout;

	/**
	 * File name to be used as standard error
	 * @var string
	 */
	protected $stderr;

	/**
	 * Handle to the process
	 * @var resource
	 */
	protected $process;

	public function __construct($command, $name, $dir = null)
	{
		$this->command = $command;
		$this->name = $name;
		$this->dir = $dir;
	}

	public function exec()
	{
		$descriptorspec = array();

		if($this->stdin)
			$descriptorspec[] = array('file', $this->stdin, 'r');
		else
			$descriptorspec[] = array('pipe', 'r');

		if($this->stdout)
			$descriptorspec[] = array('file', $this->stdout, 'a');
		else
			$descriptorspec[] = array('pipe', 'w');

		if($this->stderr)
			$descriptorspec[] = array('file', $this->stderr, 'a');
		else
			$descriptorspec[] = array('pipe', 'w');

		$pipes = null;
		$procs = array();

		$this->process = proc_open($this->command, $descriptorspec, $pipes, $this->dir);
	}

	public function isRunning()
	{
		$status = proc_get_status($this->process);
		if(!$status)
			return null;

		return (bool) $status['running'];
	}

	public function getExitCode()
	{
		$status = proc_get_status($this->process);
		if(!$status)
			return null;

		return intval($status['exitcode']);
	}

	/**
	 * @return the $name
	 */
	public function getName()
	{
		return $this->name;
	}

	/**
	 * @param string $stdin
	 */
	public function setStandardInput($stdin)
	{
		$this->stdin = $stdin;
	}

	/**
	 * @param string $stdout
	 */
	public function setStandardOutput($stdout)
	{
		$this->stdout = $stdout;
	}

	/**
	 * @param string $stderr
	 */
	public function setStandardError($stderr)
	{
		$this->stderr = $stderr;
	}
}

