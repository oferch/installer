<?php
require_once 'phing/Task.php';
require_once __DIR__ . '/../../../installer/progress/ProgressBarProcess.php';

class ProgressBarEndTask extends Task
{
	/**
	 * @var string
	 */
	private $name = null;

	/**
	 * Execute the touch operation.
	 * @return void
	 */
	function main()
	{
		$this->log("End progress bar [$this->name]", Project::MSG_INFO);
		ProgressBarProcess::terminateByName($this->name);
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
}
