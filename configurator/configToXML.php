<?php
if($argc < 2)
	die("Configuration directory path must be supplied.");
	
$configPath = $argv[1];
$outputFilename = __DIR__ . DIRECTORY_SEPARATOR .  'out.xml';

if($argc > 2)
	$outputFilename = $argv[2];

function parseIniFile(SimpleXMLElement $xml, $path)
{
	echo "Handling ini file [$path]\n";
	
	$ini = parse_ini_file($path, true);
	if(!is_array($ini) || !count($ini))
		return;
		
	$data = parseIniArray($ini);
	writeIniData($xml, $data);
	
	foreach($ini as $field => $value)
	{
		if(is_array($value) && !isIniArray($field, $value))
		{
			$fieldName = $field;
			$inheritedSectionName = null;
			if(strpos($field, ':') > 0)
				list($fieldName, $inheritedSectionName) = explode(':', $field, 2);
				
			$sectionXml = $xml->addChild('section');
			$sectionXml->addAttribute('name', trim($fieldName));
			if($inheritedSectionName)
				$sectionXml->addAttribute('inheritedSectionName', trim($inheritedSectionName));
				
			$data = parseIniArray($value);
			writeIniData($sectionXml, $data);
		}
	}
}

// return true if it's not a section
function isIniArray($field, array $array)
{
	if(strpos($field, ':') || !count($array))
		return false;
		
	$keys = array_keys($array);
	$nextKey = 0;
	foreach($keys as $key)
	{
		if(!is_numeric($key) || $key != $nextKey)
			return false;
			
		$nextKey++;
	}
			
	return true;
}

function parseIniArray(array $ini)
{
	$data = array();
	
	foreach($ini as $field => $value)
	{
		if(is_array($value) && !isIniArray($field, $value))
			continue;
			
		$fieldLevels = explode('.', $field);
		setIniValue($data, $fieldLevels, $value);
	}
	
	return $data;
}

function writeIniData(SimpleXMLElement $xml, array $data)
{
	foreach($data as $field => $value)
	{
		unset($data[$field]);
		
		if(!is_array($value))
		{
			$valueXml = $xml->addChild('value', htmlspecialchars($value));
			$valueXml->addAttribute('name', $field);
			continue;
		}
		
		if(!isIniArray($field, $value))
		{
			$groupXml = $xml->addChild('group');
			$groupXml->addAttribute('name', $field);
			writeIniData($groupXml, $value);
			continue;
		}
		
		$arrayXml = $xml->addChild('array');
		$arrayXml->addAttribute('name', $field);
		foreach($value as $subValue)
		{
			if(is_array($subValue))
			{
				$itemXml = $arrayXml->addChild('item');
				writeIniData($itemXml, $subValue);
			}
			else
			{
				$itemXml = $arrayXml->addChild('item');
				$itemXml->addChild('data', htmlspecialchars($subValue));
			} 
		}
	}
}

function setIniValue(array &$data, array $fieldLevels, $value)
{
	$currentLevel = array_shift($fieldLevels);
	if(count($fieldLevels))
	{
		if(!isset($data[$currentLevel]))
			$data[$currentLevel] = array();
			
		setIniValue($data[$currentLevel], $fieldLevels, $value);
	}
	else
	{
		$data[$currentLevel] = $value;
	}
}

function parseSphinxFile(SimpleXMLElement $xml, $path)
{
	echo "Handling sphinx file [$path]\n";
	
	$lines = file($path);
	
	$currentXml = null;
	$prevLine = null;
	$inSection = false;
	foreach($lines as $index => $line)
	{
		$line = trim($line);
		
		if(!$line)
			continue;
		
		if($prevLine)
		{
			$line = "$prevLine $line";
			$prevLine = null;
		}
		
		$matches = null;
		if(preg_match('/^(.+)\\\\$/', $line, $matches))
		{
			$prevLine = trim($matches[1]);
			continue;
		}
		
		if(strpos($line, '#') === 0)
			continue;
		
		if($inSection)
		{
			if(preg_match('/^(\w[\w\d]+)\s+=\s+([^\s]+.*)$/', $line, $matches))
			{
				$valueXml = $currentXml->addChild('value', htmlspecialchars($matches[2]));
				$valueXml->addAttribute('name', $matches[1]);
				continue;
			}
			
			if($line == '}')
			{
				$currentXml = null;
				$inSection = false;
				continue;
			}
		}
		else
		{
			if($line == 'searchd')
			{
				$currentXml = $xml->addChild('searchd');
				continue;
			}
		
			if(preg_match('/^index\s+(\w[\w\d_]+)$/', $line, $matches))
			{
				$currentXml = $xml->addChild('index');
				$currentXml->addAttribute('name', $matches[1]);
				continue;
			}
			
			if($line == '{')
			{
				$inSection = true;
				continue;
			}
		}
		
		$errorXml = $currentXml->addChild('error', $line);
		$errorXml->addAttribute('file', $path);
		$errorXml->addAttribute('line', $index + 1);
		$errorXml->addAttribute('description', 'Unable to parse');
	}
}

function parseApacheFile(SimpleXMLElement $xml, $path)
{
	echo "Handling apache file [$path]\n";
	
	$lines = file($path);
	
	$currentXml = &$xml;
	$prevLine = null;
	foreach($lines as $index => $line)
	{
		$line = trim($line);
		
		if($prevLine)
		{
			$line = "$prevLine $line";
			$prevLine = null;
		}
	
		$matches = null;
		if(preg_match('/^(.+)\\\\$/', $line, $matches))
		{
			$prevLine = trim($matches[1]);
			continue;
		}
		
		if(!$line)
		{
			$newLineXml = $currentXml->addChild('new-line');
//			$newLineXml->addAttribute('line', $index + 1);
			continue;
		}
		
		if(strpos($line, '#') === 0)
		{
			$commentXml = $currentXml->addChild('comment', $line);
//			$commentXml->addAttribute('line', $index + 1);
			continue;
		}
		
		if(preg_match('/^(\w[\w\d]+)\s+([^\s]+.*)$/', $line, $matches))
		{
			$valueXml = $currentXml->addChild('value', htmlspecialchars($matches[2]));
//			$valueXml->addAttribute('line', $index + 1);
			$valueXml->addAttribute('name', $matches[1]);
			continue;
		}
		
		if(preg_match('/^<\/\w[\w\d]+>$/', $line))
		{
			$parentArray = $currentXml->xpath('..');
			$currentXml = reset($parentArray);
			continue;
		}
		
		if(preg_match('/^<(\w[\w\d]+)(.*)>$/', $line, $matches))
		{
			$currentXml = $currentXml->addChild('section');
//			$currentXml->addAttribute('line', $index + 1);
			$currentXml->addAttribute('name', $matches[1]);
			$arguments = trim($matches[2]);
			if($arguments)
				$currentXml->addAttribute('arguments', htmlspecialchars($arguments));
				
			continue;
		}
		
		$errorXml = $currentXml->addChild('error', $line);
		$errorXml->addAttribute('file', $path);
		$errorXml->addAttribute('line', $index + 1);
		$errorXml->addAttribute('description', 'Unable to parse');
	}
}

function parseDir(SimpleXMLElement $xml, $path, $ignore = array())
{
	echo "Handling path [$path]\n";
	
	$ignore[] = '.';
	$ignore[] = '..';
	$ignore[] = '.svn';
	
	$d = dir($path);
	while (false !== ($file = $d->read())) 
	{
		if(in_array($file, $ignore))
			continue;
			
		$filePath = $path . DIRECTORY_SEPARATOR . $file;	
		if(preg_match('/\.template\./', $file))
		{
			echo "Skipped file [$filePath]\n";
			continue;
		}
		
		if(is_dir($filePath))
		{
			$dirXml = $xml->addChild('directory');
			$dirXml->addAttribute('name', $file);
			parseDir($dirXml, $filePath);
			continue;
		}
		
		if(preg_match('/.+\.ini/', $file))
		{
			$fileXml = $xml->addChild('file');
			$fileXml->addAttribute('id', basename($file, '.ini'));
			$fileXml->addAttribute('name', $file);
			$fileXml->addAttribute('type', 'ini');
			parseIniFile($fileXml, $filePath);
			continue;
		}
	
		if(preg_match('/apache.+\.conf/i', $filePath))
		{
			echo "Skipped apache file [$filePath]\n";
//			$fileXml = $xml->addChild('file');
//			$fileXml->addAttribute('id', basename($file, '.conf'));
//			$fileXml->addAttribute('name', $file);
//			$fileXml->addAttribute('type', 'apache');
//			parseApacheFile($fileXml, $filePath);
			continue;
		}
	
		if(preg_match('/sphinx.+\.conf/i', $filePath))
		{
			echo "Skipped sphinx file [$filePath]\n";
//			$fileXml = $xml->addChild('file');
//			$fileXml->addAttribute('id', basename($file, '.conf'));
//			$fileXml->addAttribute('name', $file);
//			$fileXml->addAttribute('type', 'sphinx');
//			parseSphinxFile($fileXml, $filePath);
			continue;
		}
		
		echo "Unhandled file [$filePath]\n";
	}
	$d->close();
}

function parseHosts(SimpleXMLElement $xml, $path, $map = 'local')
{
	echo "Handling hosts [$path]\n";
	
	$ignore = array(
		'.', 
		'..', 
		'.svn'
	);

	$d = dir($path);
	while (false !== ($file = $d->read())) 
	{
		if(in_array($file, $ignore))
			continue;
			
		$filePath = $path . DIRECTORY_SEPARATOR . $file;	
		if(preg_match('/\.template\./', $file))
		{
			echo "Skipped file [$filePath]\n";
			continue;
		}
		
		if(is_dir($filePath))
		{
			parseHosts($xml, $filePath, $file);
			continue;
		}
			
		$hostName = basename($file, '.ini');
		$nodeName = 'host';
		$attributeName = 'name';
		$isGroup = false;
		if(strpos($file, '#') !== false)
		{
			$hostName = str_replace('#', '*', $hostName);
			$nodeName = 'hosts-group';
			$attributeName = 'pattern';
			$isGroup = true;
		}
		
		$searchNodes = $xml->xpath("{$nodeName}[@{$attributeName}='{$hostName}']");
		$hostXml = null;
		if(count($searchNodes))
		{
			$hostXml = reset($searchNodes);
		}
		else
		{
			$hostXml = $xml->addChild($nodeName);
			$hostXml->addAttribute($attributeName, $hostName);
		}
		
		$fileXml = $hostXml->addChild('override');
		$fileXml->addAttribute('fileId', $map);
		$fileXml->addAttribute('type', 'ini');
		parseIniFile($fileXml, $filePath);
	}
}

$xml = new SimpleXMLElement('<configurations xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:noNamespaceSchemaLocation="configuration.xsd" />');
$dirXml = $xml->addChild('default');
parseDir($dirXml, $configPath, array('hosts'));

$hostsPath = $configPath . DIRECTORY_SEPARATOR . 'hosts';
if(file_exists($hostsPath) && is_dir($hostsPath))
{
	$hostsXml = $xml->addChild('custom-hosts');
	parseHosts($hostsXml, $hostsPath);
}

$xmlContent = $xml->saveXML();
file_put_contents($outputFilename, $xmlContent);
