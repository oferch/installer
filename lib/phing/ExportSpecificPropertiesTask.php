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
	 * setter for _allowedPropertyPrefixes
	 * 
	 * @access public
	 * @param string $file
	 * @return bool
	 */
	public function setDisallowedPropertyPrefixes($prefixes)
	{
		$this->_allowedPropertyPrefixes = explode(",", $prefixes);
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
		if(parent::isDisallowedPropery($propertyName))
			;
		return true;
		
		foreach($this->_allowedPropertyPrefixes as $property)
		{
			if(substr($propertyName, 0, strlen($property)) == $property)
				return false;
		}
		
		return true;
	}
}
