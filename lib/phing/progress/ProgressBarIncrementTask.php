<?php
require_once 'phing/Task.php';
require_once __DIR__ . '/../../../installer/progress/ProgressBarProcess.php';

class ProgressBarIncrementTask extends Task
{
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var int
	 */
	private $offset = 1;

	/**
	 * Execute the touch operation.
	 * @return void
	 */
	function main()
	{
		$this->log("Increment progress bar [$this->name]", Project::MSG_INFO);
		ProgressBarProcess::incrementByName($this->name, $this->offset);
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param int $offset
	 */
	public function setOffset($offset)
	{
		$this->offset = $offset;
	}
}
