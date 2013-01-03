<?php
require_once "phing/Task.php";

class ExecSqlTask extends Task
{
	/**
	 * Server host
	 * @var string
	 */
	private $host = 'localhost';
	
	/**
	 * Server port
	 * @var int
	 */
	private $port = '3306';
	
	/**
	 * The privileged user username to login with
	 * @var string
	 */
	private $loginUsername;
	
	/**
	 * The privileged user password to login with
	 * @var string
	 */
	private $loginPassword;
	
	/**
	 * The database name to be created
	 * @var string
	 */
	private $name;
	
	/**
	 * Just log without real execution
	 * @var boolean
	 */
	private $dryRun = false;
	
	/**
	 * @var string
	 */
	private $sql;
	
	/**
	 * @var string
	 */
	private $mysql = 'mysql';
	
	/**
	 * @var PhingFile
	 */
	private $file;
	
	/**
	 * @var array<FileSet>
	 */
	private $filesets = array();
	
	/**
	 * The main entry point method.
	 */
	public function main()
	{
		if($this->sql === null && $this->file === null && empty($this->filesets))
			throw new BuildException("Specify at least one source - an sql, a file or a fileset.");
			
		if($this->sql)
		{
			$this->execSql($this->sql);
		}
	
		if($this->file)
		{
			$this->execFile($this->file);
		}
	
		// filesets
		foreach($this->filesets as $fileSet)
		{
			/* @var $fileSet FileSet */
			$ds = $fileSet->getDirectoryScanner($this->project);
			$fromDir = $fileSet->getDir($this->project);
			
			$srcFiles = $ds->getIncludedFiles();
			$filecount = count($srcFiles);
			$total_files = $total_files + $filecount;
			for($j = 0; $j < $filecount; $j++)
			{
				$this->execFile(new PhingFile($fromDir, $srcFiles[$j]));
			}
		}
	}

	protected function execFile(PhingFile $file)
	{
		$path = $file->getPath();
		$cmd = "\"{$this->mysql}\" -h{$this->host} -P{$this->port} -u{$this->loginUsername} -p{$this->loginPassword} {$this->name} < \"$path\"";
		$this->log("Executing: $cmd");
		
		$returnValue = null;
		passthru($cmd, $returnValue);
		if($returnValue != 0)
			throw new Exception("SQL execution failed.");
	}
	
	protected function execSql($sql)
	{
		$this->log("Executing: $sql");
		
		$dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->name};";
		$pdo = new PDO($dsn, $this->loginUsername, $this->loginPassword);
		
		if(!$this->dryRun && $pdo->exec($sql) === false)
		{
			/**
			 * $pdo->errorInfo()
			 * 0	SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
			 * 1	Driver-specific error code.
			 * 2	Driver-specific error message.
			 */
			$errInfo = $pdo->errorInfo();
			throw new Exception($errInfo[0] . ': ' . $errInfo[2], $errInfo[1], null);
		}
	}
	
	/**
	 * @param string $host
	 */
	public function setHost($host)
	{
		$this->host = $host;
	}

	/**
	 * @param int $port
	 */
	public function setPort($port)
	{
		$this->port = $port;
	}

	/**
	 * @param string $loginUsername
	 */
	public function setLoginUsername($loginUsername)
	{
		$this->loginUsername = $loginUsername;
	}

	/**
	 * @param string $loginPassword
	 */
	public function setLoginPassword($loginPassword)
	{
		$this->loginPassword = $loginPassword;
	}

	/**
	 * @param string $name
	 */
	public function setName($name)
	{
		$this->name = $name;
	}

	/**
	 * @param boolean $dryRun
	 */
	public function setDryRun($dryRun)
	{
		$this->dryRun = $dryRun;
	}
	
	/**
	 * Sets a sql code
	 */
	function setSql(string $sql)
	{
		$this->sql = $sql;
	}
	
	/**
	 * Sets the mysql binary
	 */
	function setMysql($mysql)
	{
		$this->mysql = $mysql;
	}
	
	/**
	 * Sets a single source file.
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
}
