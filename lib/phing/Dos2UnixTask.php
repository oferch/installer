<?php
require_once 'phing/Task.php';
include_once 'phing/types/FileSet.php';

class Dos2UnixTask extends Task
{
	/**
	 * @var PhingFile
	 */
	private $file;
	
	/**
	 * @var array<FileSet>
	 */
	private $filesets = array();
	
	/**
	 * @var boolean
	 */
	private $verbose = true;
	
	/**
	 * @var string
	 */
	private $progressBarName = true;
	
	/**
	 * Set verbosity, which if set to false surpresses all but an overview of what happened.
	 */
	function setVerbose($bool)
	{
		$this->verbose = (bool)$bool;
	}
	
	/**
	 * Define progress bar name to be incremented
	 */
	function setProgressBarName($progressBarName)
	{
		$this->progressBarName = $progressBarName;
	}
	
	/**
	 * Sets a single source file to touch.  If the file does not exist
	 * an empty file will be created.
	 */
	function setFile(PhingFile $file)
	{
		$this->file = $file;
	}
	
	/**
	 * Nested creator, adds a set of files (nested fileset attribute).
	 */
	function createFileSet()
	{
		$num = array_push($this->filesets, new FileSet());
		return $this->filesets[$num - 1];
	}
	
	/**
	 * Execute the touch operation.
	 * @return void
	 */
	function main()
	{
		// Check Parameters
		$this->checkParams();
		$this->dos2Unix();
	}
	
	/**
	 * Ensure that correct parameters were passed in.
	 * @return void
	 */
	private function checkParams()
	{
		if($this->file === null && empty($this->filesets))
			throw new BuildException("Specify at least one source - a file or a fileset.");
	}
	
	/**
	 * Does the actual work.
	 * @return void
	 */
	private function dos2Unix()
	{
		// counters for non-verbose output
		$totalFiles = 0;
		
		// one file
		if($this->file !== null)
		{
			$totalFiles = 1;
			$this->dos2UnixFile($this->file);
		}
		
		foreach($this->filesets as $fileSet)
		{
			/* @var $fileSet FileSet */
			$ds = $fileSet->getDirectoryScanner($this->project);
			$totalFiles = $totalFiles + count($ds->getIncludedFiles());
		}
		if($this->progressBarName)
			ProgressBarProcess::setMaxByName($this->progressBarName, $totalFiles);
		
		// filesets
		foreach($this->filesets as $fileSet)
		{
			/* @var $fileSet FileSet */
			$ds = $fileSet->getDirectoryScanner($this->project);
			$fromDir = $fileSet->getDir($this->project);
			
			$srcFiles = $ds->getIncludedFiles();
			foreach($srcFiles as $srcFile)
			{
				if($this->progressBarName)
					ProgressBarProcess::setTitleByName($this->progressBarName, $srcFile);
					
				$this->dos2UnixFile(new PhingFile($fromDir, $srcFile));
				
				if($this->progressBarName)
					ProgressBarProcess::incrementByName($this->progressBarName);
			}
		}
		
		if(!$this->verbose)
			$this->log('Total files changed: ' . $totalFiles);
	
		ProgressBarProcess::setPercentByName($this->progressBarName, 100);
	}
	
	/**
	 * Actually change the file.
	 * @param PhingFile $file
	 */
	private function dos2UnixFile(PhingFile $file)
	{
		if(!$file->exists())
			throw new BuildException("The file " . $file->__toString() . " does not exist");
		
		try
		{
			passthru('dos2unix ' . $file->getPath());
			if($this->verbose)
				$this->log("Changed file '" . $file->__toString() . "'");
		}
		catch(Exception $e)
		{
			$this->log($e->getMessage(), Project::MSG_WARN);
		}
	}
}
