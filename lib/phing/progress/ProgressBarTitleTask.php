<?php
require_once 'phing/Task.php';
require_once __DIR__ . '/../../../installer/progress/ProgressBarProcess.php';

class ProgressBarTitleTask extends Task
{
	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var string
	 */
	private $title;

	/**
	 * Execute the touch operation.
	 * @return void
	 */
	function main()
	{
		$this->log("Set progress bar [$this->name] with title [$this->title]", Project::MSG_INFO);
		ProgressBarProcess::setTitleByName($this->name, $this->title);
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
}
