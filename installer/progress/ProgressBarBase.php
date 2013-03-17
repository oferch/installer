<?php
abstract class ProgressBarBase
{
	/**
	 * @var array<ProgressBar>
	 */
	protected static $instances = array();

	/**
	 * @var int
	 */
	protected $max = null;

	/**
	 * @var int
	 */
	protected $current = 0;

	/**
	 * @var int
	 */
	protected $percent = 0;

	/**
	 * @var string
	 */
	protected $title = null;

	/**
	 * @var int
	 */
	protected $titleWidth = null;

	/**
	 * @var string
	 */
	protected $parentName = null;

	/**
	 * @var int
	 */
	protected $percentOfParent = null;

	/**
	 * @var int
	 */
	protected $parentPercentOffset = null;

	public function __construct($name, $max = null, $titleWidth = null)
	{
		self::$instances[$name] = &$this;

		$this->max = $max;
		$this->titleWidth = $titleWidth;
		
		$this->updateBar();
	}

	abstract protected function updateBar();

	public function setParentProgressBar($parentName, $percentOfParent = null)
	{
		if(!isset(self::$instances[$parentName]))
			return;
		
		$parent = self::$instances[$parentName];

		$this->parentName = $parentName;
		$this->parentPercentOffset = $parent->getPercent();
		
		if($percentOfParent)
			$this->percentOfParent = $percentOfParent;
		else
			$this->percentOfParent = 100 - $this->parentPercentOffset;
	}

	public function setTitle($title)
	{
		$this->title = $title;
		$this->updateBar();
	}

	public function setMax($max)
	{
		$this->max = $max;
		$this->updateBar();
	}

	public function setCurrent($current, $title = null)
	{
		$this->current = $current;
		$this->title = $title;
		$this->calcPercent();
	}

	public function calcPercent()
	{
		$percent = 0;
		if($this->max && $this->current)
			$percent = min(100, round((100 / $this->max) * $this->current));
			
		$this->setPercent($percent);
	}

	public function getPercent()
	{
		return $this->percent;
	}

	public function setPercent($percent)
	{
		$this->percent = $percent;

		if($this->parentName)
		{
			$parentPercent = $this->percent * $this->percentOfParent / 100;
			$parentPercent += $this->parentPercentOffset;
			self::setPercentByName($this->parentName, round($parentPercent));
		}

		$this->updateBar();
	}

	public function increment($offset = 1)
	{
		$this->current += $offset;
		$this->calcPercent();
	}

	public static function setTitleByName($name, $title)
	{
		if(!isset(self::$instances[$name]))
			return;

		$instance = self::$instances[$name];
		$instance->setTitle($title);
	}

	public static function setPercentByName($name, $percent)
	{
		if(!isset(self::$instances[$name]))
			return;

		$instance = self::$instances[$name];
		$instance->setPercent($percent);
	}

	public static function setMaxByName($name, $max)
	{
		if(!isset(self::$instances[$name]))
			return;

		$instance = self::$instances[$name];
		$instance->setMax($max);
	}

	public static function setCurrentByName($name, $current, $title = null)
	{
		if(!isset(self::$instances[$name]))
			return;

		$instance = self::$instances[$name];
		$instance->setCurrent($current, $title);
	}

	public static function incrementByName($name, $offset = 1)
	{
		if(!isset(self::$instances[$name]))
			return;

		$instance = self::$instances[$name];
		$instance->increment($offset);
	}

	public static function terminateByName($name)
	{
		if(!isset(self::$instances[$name]))
			return;

		$instance = self::$instances[$name];
		$instance->setPercent(100);
		
		unset($instance);
		unset(self::$instances[$name]);
	}

	public static function get($name)
	{
		if(isset(self::$instances[$name]))
			return self::$instances[$name];
			
		return null;
	}
}

