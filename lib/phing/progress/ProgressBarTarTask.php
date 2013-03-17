<?php
require_once 'phing/Task.php';
require_once __DIR__ . '/../../../installer/progress/ProgressBarProcess.php';

class ProgressBarTarTask extends Task
{
	const TAR_NAMELEN = 100;
	
	const WARN = "warn";
	const FAIL = "fail";
	const OMIT = "omit";
	
	/**
	 * @var string
	 */
	private $name;
	
	private $tarFile;
	private $baseDir;
	private $includeEmpty = true; // Whether to include empty dirs in the TAR
	

	private $longFileMode = "warn";
	
	private $fileset = null;
	private $filesets = array();
	private $fileSetFiles = array();
	
	/**
	 * Indicates whether the user has been warned about long files already.
	 */
	private $longWarningGiven = false;
	
	/**
	 * Compression mode.  Available options "gzip", "bzip2", "none" (null).
	 */
	private $compression = null;
	
	/**
	 * File path prefix in the tar archive
	 *
	 * @var string
	 */
	private $prefix = null;
	
	/**
	 * Ensures that PEAR lib exists.
	 */
	public function init()
	{
		include_once 'Archive/Tar.php';
		if(! class_exists('Archive_Tar'))
		{
			throw new BuildException("You must have installed the PEAR Archive_Tar class in order to use TarTask.");
		}
	}
	
	/**
	 * Add a new fileset
	 * @return FileSet
	 */
	public function createTarFileSet()
	{
		$this->fileset = new TarFileSet();
		$this->filesets[] = $this->fileset;
		return $this->fileset;
	}
	
	/**
	 * Add a new fileset.  Alias to createTarFileSet() for backwards compatibility.
	 * @return FileSet
	 * @see createTarFileSet()
	 */
	public function createFileSet()
	{
		$this->fileset = new TarFileSet();
		$this->filesets[] = $this->fileset;
		return $this->fileset;
	}
	
	/**
	 * Set is the name/location of where to create the tar file.
	 * @param PhingFile $destFile The output of the tar
	 */
	public function setDestFile(PhingFile $destFile)
	{
		$this->tarFile = $destFile;
	}
	
	/**
	 * This is the base directory to look in for things to tar.
	 * @param PhingFile $baseDir
	 */
	public function setBasedir(PhingFile $baseDir)
	{
		$this->baseDir = $baseDir;
	}
	
	/**
	 * Set the include empty dirs flag.
	 * @param  boolean  Flag if empty dirs should be tarred too
	 * @return void
	 * @access public
	 */
	public function setIncludeEmptyDirs($bool)
	{
		$this->includeEmpty = (boolean) $bool;
	}
	
	/**
	 * Set how to handle long files, those with a path&gt;100 chars.
	 * Optional, default=warn.
	 * <p>
	 * Allowable values are
	 * <ul>
	 * <li>  truncate - paths are truncated to the maximum length
	 * <li>  fail - paths greater than the maximim cause a build exception
	 * <li>  warn - paths greater than the maximum cause a warning and GNU is used
	 * <li>  gnu - GNU extensions are used for any paths greater than the maximum.
	 * <li>  omit - paths greater than the maximum are omitted from the archive
	 * </ul>
	 */
	public function setLongfile($mode)
	{
		$this->longFileMode = $mode;
	}
	
	/**
	 * Set compression method.
	 * Allowable values are
	 * <ul>
	 * <li>  none - no compression
	 * <li>  gzip - Gzip compression
	 * <li>  bzip2 - Bzip2 compression
	 * </ul>
	 */
	public function setCompression($mode)
	{
		switch($mode)
		{
			case "gzip":
				$this->compression = "gz";
				break;
			case "bzip2":
				$this->compression = "bz2";
				break;
			case "none":
				$this->compression = null;
				break;
			default:
				$this->log("Ignoring unknown compression mode: " . $mode, Project::MSG_WARN);
				$this->compression = null;
		}
	}
	
	/**
	 * Sets the file path prefix for file in the tar file.
	 *
	 * @param string $prefix Prefix
	 *
	 * @return void
	 */
	public function setPrefix($prefix)
	{
		$this->prefix = $prefix;
	}
	
	/**
	 * Execute the touch operation.
	 * @return void
	 */
	function main()
	{
		$progressBar = ProgressBarProcess::get($this->name);
		if(! $progressBar)
			$progressBar = new ProgressBarProcess($this->name);
		
		if($this->tarFile === null)
		{
			throw new BuildException("tarfile attribute must be set!", $this->getLocation());
		}
		
		if($this->tarFile->exists() && $this->tarFile->isDirectory())
		{
			throw new BuildException("tarfile is a directory!", $this->getLocation());
		}
		
		if($this->tarFile->exists() && ! $this->tarFile->canWrite())
		{
			throw new BuildException("Can not write to the specified tarfile!", $this->getLocation());
		}
		
		// shouldn't need to clone, since the entries in filesets
		// themselves won't be modified -- only elements will be added
		$savedFileSets = $this->filesets;
		
		try
		{
			if($this->baseDir !== null)
			{
				if(! $this->baseDir->exists())
				{
					throw new BuildException("basedir '" . (string) $this->baseDir . "' does not exist!", $this->getLocation());
				}
				if(empty($this->filesets))
				{ // if there weren't any explicit filesets specivied, then
					// create a default, all-inclusive fileset using the specified basedir.
					$mainFileSet = new TarFileSet($this->fileset);
					$mainFileSet->setDir($this->baseDir);
					$this->filesets[] = $mainFileSet;
				}
			}
			
			if(empty($this->filesets))
			{
				throw new BuildException("You must supply either a basedir " . "attribute or some nested filesets.", $this->getLocation());
			}
			
			// check if tar is out of date with respect to each fileset
			if($this->tarFile->exists())
			{
				$upToDate = true;
				foreach($this->filesets as $fs)
				{
					$files = $fs->getFiles($this->project, $this->includeEmpty);
					if(! $this->archiveIsUpToDate($files, $fs->getDir($this->project)))
					{
						$upToDate = false;
					}
					for($i = 0, $fcount = count($files); $i < $fcount; $i ++)
					{
						if($this->tarFile->equals(new PhingFile($fs->getDir($this->project), $files[$i])))
						{
							throw new BuildException("A tar file cannot include itself", $this->getLocation());
						}
					}
				}
				if($upToDate)
				{
					$this->log("Nothing to do: " . $this->tarFile->__toString() . " is up to date.", Project::MSG_INFO);
					return;
				}
			}
			
			$this->log("Building tar: " . $this->tarFile->__toString(), Project::MSG_INFO);
			
			$tar = new Archive_Tar($this->tarFile->getAbsolutePath(), $this->compression);
			
			if($tar->error_object instanceof Exception)
			{
				throw new BuildException($tar->error_object->getMessage());
			}
			
			$count = 0;
			foreach($this->filesets as $fs)
			{
				$files = $fs->getFiles($this->project, $this->includeEmpty);
				if(count($files) > 1 && strlen($fs->getFullpath()) > 0)
				{
					throw new BuildException("fullpath attribute may only " . "be specified for " . "filesets that specify a " . "single file.");
				}
				
				$count += count($files);
			}
			$progressBar->setMax($count);
			
			foreach($this->filesets as $fs)
			{
				$files = $fs->getFiles($this->project, $this->includeEmpty);
				if(count($files) > 1 && strlen($fs->getFullpath()) > 0)
				{
					throw new BuildException("fullpath attribute may only " . "be specified for " . "filesets that specify a " . "single file.");
				}
				$fsBasedir = $fs->getDir($this->project);
				$filesToTar = array();
				for($i = 0, $fcount = count($files); $i < $fcount; $i ++)
				{
					$f = new PhingFile($fsBasedir, $files[$i]);
					$progressBar->setTitle($f->getPath());
					$filesToTar[] = $f->getAbsolutePath();
					$this->log("Adding file " . $f->getPath() . " to archive.", Project::MSG_VERBOSE);
					$progressBar->increment();
				}
				$tar->addModify($filesToTar, $this->prefix, $fsBasedir->getAbsolutePath());
				
				if($tar->error_object instanceof Exception)
				{
					throw new BuildException($tar->error_object->getMessage());
				}
			}
		
		}
		catch(IOException $ioe)
		{
			$msg = "Problem creating TAR: " . $ioe->getMessage();
			$this->filesets = $savedFileSets;
			throw new BuildException($msg, $ioe, $this->getLocation());
		}
		
		$this->filesets = $savedFileSets;
		
		ProgressBarProcess::terminateByName($this->name);
	}
	
	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}
	
	/**
	 * @param array $files array of filenames
	 * @param PhingFile $dir
	 * @return boolean
	 */
	protected function archiveIsUpToDate($files, $dir)
	{
		$sfs = new SourceFileScanner($this);
		$mm = new MergeMapper();
		$mm->setTo($this->tarFile->getAbsolutePath());
		return count($sfs->restrict($files, $dir, null, $mm)) == 0;
	}

}
