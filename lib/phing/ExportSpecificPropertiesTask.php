<?php
require_once "phing/tasks/ext/ExportPropertiesTask.php";

class ExportSpecificPropertiesTask extends ExportPropertiesTask
{
	/**
	 * Include properties starting with these prefixes
	 * 
	 * @var array
	 * @access private
	 */
	private $_allowedPropertyPrefixes = array();
	
	/**
	 * Indicates that the allowed prefix should be removed
	 * 
	 * @var boolean
	 * @access private
	 */
	private $_removePrefix = array();
	
	/**
	 * setter for _allowedPropertyPrefixes
	 * 
	 * @access public
	 * @param string $prefixes comma seperated
	 * @return boolean
	 */
	public function setAllowedPropertyPrefixes($prefixes)
	{
		$this->_allowedPropertyPrefixes = explode(',', $prefixes);
		return true;
	}
	
	/**
	 * setter for _allowedPropertyPrefixes
	 * 
	 * @access public
	 * @param boolean $remove
	 * @return boolean
	 */
	public function setRemovePrefix($remove)
	{
		$this->_removePrefix = $remove;
		return true;
	}
	
	/* (non-PHPdoc)
	 * @see ExportPropertiesTask::isDisallowedPropery()
	 */
	protected function isAllowedPropery(&$propertyName)
	{
		if(parent::isDisallowedPropery($propertyName))
			return false;
		
		foreach($this->_allowedPropertyPrefixes as $property)
		{
			if(substr($propertyName, 0, strlen($property)) == $property)
			{
				if($this->_removePrefix)
					$propertyName = str_replace($property, '', $propertyName);
					
				return true;
			}
		}
		
		return false;
	}

    /* (non-PHPdoc)
     * @see ExportPropertiesTask::main()
     */
    public function main()
    {
        // Sets the currently declared properties
        $this->_properties = $this->getProject()->getProperties();
        
        if(is_array($this->_properties) && !empty($this->_properties) && null !== $this->_targetFile) {
            $propertiesString = '';
            foreach($this->_properties as $propertyName => $propertyValue) {
                if($this->isAllowedPropery($propertyName)) {
                    $propertiesString .= $propertyName . "=" . $propertyValue . PHP_EOL;
                }
            }
            
            if(!file_put_contents($this->_targetFile, $propertiesString)) {
                throw new BuildException('Failed writing to ' . $this->_targetFile);
            }
        }
    }
}
