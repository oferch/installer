<?php
require_once "phing/Task.php";

class ExportSpecificPropertiesTask extends Task
{
    /**
     * Array of project properties
     * 
     * (default value: null)
     * 
     * @var array
     * @access private
     */
    private $_properties = null;
    
    /**
     * Target file for saved properties
     * 
     * (default value: null)
     * 
     * @var string
     * @access private
     */
    private $_targetFile = null;
    
    /**
     * Exclude properties starting with these prefixes
     * 
     * @var array
     * @access private
     */
    private $_disallowedPropertyPrefixes = array(
        'host.',
        'phing.',
        'os.',
        'php.',
        'line.',
        'env.',
        'user.'
    );
    
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
	private $_removePrefix = false;

    /**
     * setter for _targetFile
     * 
     * @access public
     * @param string $file
     * @return bool
     */
    public function setTargetFile($file)
    {   
        $this->_targetFile = $file;
        return true;
    }
    
    /**
     * setter for _disallowedPropertyPrefixes
     * 
     * @access public
     * @param string $file
     * @return bool
     */
    public function setDisallowedPropertyPrefixes($prefixes)
    {
        $this->_disallowedPropertyPrefixes = explode(",", $prefixes);
        return true;
    }  
	
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
    
    /**
     * Checks if a property name is disallowed
     * 
     * @access protected
     * @param string $propertyName
     * @return bool
     */
    protected function isDisallowedPropery($propertyName)
    {
        foreach($this->_disallowedPropertyPrefixes as $property) {
            if(substr($propertyName, 0, strlen($property)) == $property) {
                return true;
            }
        }
        
        return false;
    }
	
    /**
     * Checks if a property name is allowed
     * 
     * @access protected
     * @param string $propertyName
     * @return bool
     */
	protected function isAllowedPropery(&$propertyName)
	{
		if($this->isDisallowedPropery($propertyName))
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
        if(!is_dir(dirname($this->_targetFile))) {
            throw new BuildException("Parent directory of target file doesn't exist");
        }
        
        if(!is_writable(dirname($this->_targetFile)) && (file_exists($this->_targetFile) && !is_writable($this->_targetFile))) {
            throw new BuildException("Target file isn't writable");
        }
        
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
