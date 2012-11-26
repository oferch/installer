<?php
require_once "phing/Task.php";

class ForEachXmlElementTask extends Task
{
	/**
	 * prefix of all parameters to pass to callee for XML element
	 * @var string
	 */
	private $elementPrefix;
	
	/**
	 * Name of parameter to pass to callee for XML element xPath
	 * @var string
	 */
	private $xPathParam;
	
	/**
	 * Indicates that root node name should be included in the xPath
	 * @var bool
	 */
	private $xPathSkipRoot;
	
	/**
	 * Indicates that root node should be skipped
	 * @var bool
	 */
	private $skipRoot;
	
	/**
	 * The path to the XML file.
	 * @var string
	 */
	private $file = null;
	
	/**
	 * PhingCallTask that will be invoked w/ calleeTarget.
	 * @var PhingCallTask
	 */
	private $callee;
	
	/**
	 * Target to execute.
	 * @var string
	 */
	private $calleeTarget;
	
	/**
	 * The init method: Do init steps.
	 */
	public function init()
	{
		$this->callee = $this->project->createTask("phingcall");
		$this->callee->setOwningTarget($this->getOwningTarget());
		$this->callee->setTaskName($this->getTaskName());
		$this->callee->setLocation($this->getLocation());
		$this->callee->init();
	}
	
	/**
	 * The main entry point method.
	 */
	public function main()
	{
		if (is_null($this->file)) {
			throw new BuildException("You must supply a file path to the XML.");
		}
		if (!file_exists($this->file)) {
			throw new BuildException("Supplied file path doesn't exist.");
		}
		if (is_null($this->calleeTarget)) {
			throw new BuildException("You must supply a target to perform");
		}
		
		$callee = $this->callee;
		$callee->setTarget($this->calleeTarget);
		$callee->setInheritAll(true);
		$callee->setInheritRefs(true);
		
		$xml = new SimpleXMLElement(file_get_contents($this->file));
		
		if($this->skipRoot)
		{
			$this->foreachElement($xml);
		}
		else
		{
			$this->eachElement($xml);
		}
	}
	
	public function foreachElement(SimpleXMLElement $xml, $xPath = '')
	{
		foreach($xml as $nodeName => $node)
			$this->eachElement($node, "$xPath/$nodeName");
	}
	
	public function eachElement(SimpleXMLElement $xml, $xPath = '')
	{
		$nodeId = uniqid();
		$nodeName = $xml->getName();
		$elementChildrenCount = $xml->count();
		
		$this->log("Setting param '$this->elementPrefix.id' to value '$nodeId'", Project::MSG_VERBOSE);
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.id");
		$prop->setValue($nodeId);
		
		$this->log("Setting param '$this->elementPrefix.name' to value '$nodeName'", Project::MSG_VERBOSE);
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.name");
		$prop->setValue($nodeName);
		
		$this->log("Setting param '$this->elementPrefix.count' to value '$elementChildrenCount'", Project::MSG_VERBOSE);
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.count");
		$prop->setValue($elementChildrenCount);
		
		$elementContent = "$xml";
		$this->log("Setting param '$this->elementPrefix.content' to value '$elementContent'", Project::MSG_VERBOSE);
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.content");
		$prop->setValue($elementContent);
	
		foreach($xml->attributes() as $attributeName => $attributeValue)
		{
			$elementAttributesParam = "$this->elementPrefix.attributes.$nodeId.$attributeName";
			$this->log("Setting param '$elementAttributesParam' to value '$attributeValue'", Project::MSG_VERBOSE);
			$prop = $this->callee->createProperty();
			$prop->setOverride(true);
			$prop->setName($elementAttributesParam);
			$prop->setValue("$attributeValue");
		}
		
		if (!is_null($this->xPathParam))
		{
			$this->log("Setting param '$this->xPathParam' to value '$xPath'", Project::MSG_VERBOSE);
			$prop = $this->callee->createProperty();
			$prop->setOverride(true);
			$prop->setName($this->xPathParam);
			$prop->setValue($xPath);
		}
		
		$this->callee->main();
		
		if($elementChildrenCount)
			$this->foreachElement($xml, $xPath);		
	}
	
	/**
	 * @param string $elementPrefix
	 */
	public function setElementPrefix($elementPrefix)
	{
		$this->elementPrefix = $elementPrefix;
	}

	/**
	 * @param string $xPathParam
	 */
	public function setXPathParam($xPathParam)
	{
		$this->xPathParam = $xPathParam;
	}

	/**
	 * @param string $xPathSkipRoot
	 */
	public function setXPathSkipRoot($xPathSkipRoot)
	{
		$this->xPathSkipRoot = $xPathSkipRoot;
	}

	/**
	 * @param bool $skipRoot
	 */
	public function setSkipRoot($skipRoot)
	{
		$this->skipRoot = $skipRoot;
	}

	/**
	 * @param string $file
	 */
	public function setFile($file)
	{
		$this->file = $file;
	}

	/**
	 * @param string $calleeTarget
	 */
	public function setTarget($calleeTarget)
	{
		$this->calleeTarget = $calleeTarget;
	}
}
