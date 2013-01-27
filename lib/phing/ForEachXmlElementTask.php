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
	 * Sets the start point in the XML
	 * @var string
	 */
	private $xPathStart = null;
	
	/**
	 * Indicates that root node should be skipped
	 * @var bool
	 */
	private $skipRoot;
	
	/**
	 * Indicates that child nodes should be processed
	 * @var bool
	 */
	private $recursive = true;
	
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
		
		if($this->xPathStart)
		{
			$this->log("Loading element by xPath [$this->xPathStart]", Project::MSG_INFO);
			$xmlNodes = $xml->xpath($this->xPathStart);
			if($xmlNodes && count($xmlNodes))
			{
				foreach($xmlNodes as $xmlNode)
					$this->eachElement($xmlNode);
			}
			else
			{
				$this->log("No element found for the xPath", Project::MSG_WARN);
			}
		}
		elseif($this->skipRoot)
		{
			$this->log("Loading all child elements", Project::MSG_INFO);
			$this->foreachElement($xml);
		}
		else
		{
			$this->log("Loading all elements", Project::MSG_INFO);
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
		if(isset($xml['id']))
			$nodeId = $xml['id'];
		$nodeId = $this->project->replaceProperties($nodeId);
			
		$nodeName = $xml->getName();
		$elementChildrenCount = $xml->count();
		
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.id");
		$prop->setValue($nodeId);
		$this->log("Setting param '$this->elementPrefix.id' to value '$nodeId'", Project::MSG_VERBOSE);
		
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.name");
		$prop->setValue($nodeName);
		$this->log("Setting param '$this->elementPrefix.name' to value '$nodeName'", Project::MSG_VERBOSE);
		
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.count");
		$prop->setValue($elementChildrenCount);
		$this->log("Setting param '$this->elementPrefix.count' to value '$elementChildrenCount'", Project::MSG_VERBOSE);
		
		$elementContent = "$xml";
		$prop = $this->callee->createProperty();
		$prop->setOverride(true);
		$prop->setName("$this->elementPrefix.content");
		$prop->setValue($elementContent);
		$this->log("Setting param '$this->elementPrefix.content' to value '$elementContent'", Project::MSG_VERBOSE);
	
		foreach($xml->attributes() as $attributeName => $attributeValue)
		{
			$attributeValue = $this->project->replaceProperties($attributeValue);
			$elementAttributesParam = "$this->elementPrefix.attributes.$nodeId.$attributeName";
			$prop = $this->callee->createProperty();
			$prop->setOverride(false);
			$prop->setName($elementAttributesParam);
			$prop->setValue("$attributeValue");
			$this->log("Setting param '$elementAttributesParam' to value '$attributeValue'", Project::MSG_VERBOSE);
		}
		
		if (!is_null($this->xPathParam))
		{
			$prop = $this->callee->createProperty();
			$prop->setOverride(true);
			$prop->setName($this->xPathParam);
			$prop->setValue($xPath);
			$this->log("Setting param '$this->xPathParam' to value '$xPath'", Project::MSG_VERBOSE);
		}
		
		$this->callee->main();
		
		if(!$this->recursive)
			return;
			
		if($elementChildrenCount)
		{
			$this->foreachElement($xml, $xPath);
		}
		elseif(isset($xml['sourcePath']))
		{
			$sourcePath = strval($xml['sourcePath']);
			if (!file_exists($sourcePath)) 
				throw new BuildException("Supplied file path [$sourcePath] doesn't exist.");
			
			$sourceXml = new SimpleXMLElement(file_get_contents($sourcePath));
			$this->foreachElement($sourceXml, $xPath);
		}
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
	 * @param string $xPathStart
	 */
	public function setXPathStart($xPathStart)
	{
		$this->xPathStart = $xPathStart;
	}

	/**
	 * @param bool $recursive
	 */
	public function setRecursive($recursive)
	{
		$this->recursive = $recursive;
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
