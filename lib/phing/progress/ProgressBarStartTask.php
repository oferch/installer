<?php
require_once 'phing/Task.php';
require_once __DIR__ . '/../../../installer/progress/ProgressBarProcess.php';

class ProgressBarStartTask extends Task
{
	/**
	 * @var string
	 */
	private $name = null;

	/**
	 * @var int
	 */
	private $max = null;

	/**
	 * @var int
	 */
	private $titleWidth = null;

	/**
	 * @var string
	 */
	private $parentName = null;

	/**
	 * @var int
	 */
	private $percentOfParent = null;

	/**
	 * @var string
	 */
	private $title = null;

	/**
	 * Execute the touch operation.
	 * @return void
	 */
	function main()
	{
		$this->log("Start new progress bar [$this->name]", Project::MSG_INFO);
		$progressBar = new ProgressBarProcess($this->name, $this->max, $this->titleWidth);
		
		if($this->parentName && $this->percentOfParent)
		{
			$this->log("Set progress bar [$this->name] as [{$this->percentOfParent}%] of parent [$this->parentName]", Project::MSG_INFO);
			$progressBar->setParentProgressBar($this->parentName, $this->percentOfParent);
		}
	
		if($this->title)
		{
			$this->log("Set progress bar [$this->name] title [$this->title]", Project::MSG_INFO);
			$progressBar->setTitle($this->title);
		}
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param int $max
	 */
	public function setMax($max)
	{
		$this->max = $max;
	}

	/**
	 * @param int $titleWidth
	 */
	public function setTitleWidth($titleWidth)
	{
		$this->titleWidth = $titleWidth;
	}
	
	/**
	 * @param string $parentName
	 */
	public function setParentName($parentName)
	{
		$this->parentName = $parentName;
	}

	/**
	 * @param int $percentOfParent
	 */
	public function setPercentOfParent($percentOfParent)
	{
		$this->percentOfParent = $percentOfParent;
	}

	/**
	 * @param string $title
	 */
	public function setTitle($title)
	{
		$this->title = $title;
	}
}
