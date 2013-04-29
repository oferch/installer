<?php
require_once "phing/Task.php";

class CreateDbTask extends Task
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
	 * The init method: Do init steps.
	 */
	public function init()
	{
	}
	
	/**
	 * The main entry point method.
	 */
	public function main()
	{
		$dsn = "mysql:host={$this->host};port={$this->port}";
		$pdo = new PDO($dsn, $this->loginUsername, $this->loginPassword);
		
		$statement = "CREATE DATABASE IF NOT EXISTS {$this->name}";
		$this->log("Executing: $statement");
		if(!$this->dryRun && $pdo->exec($statement) === false)
		{
			/**
			 * $pdo->errorInfo()
			 * 0	SQLSTATE error code (a five characters alphanumeric identifier defined in the ANSI SQL standard).
			 * 1	Driver-specific error code.
			 * 2	Driver-specific error message.
			 */
			$errInfo = $pdo->errorInfo();
			throw new BuildException($errInfo[0] . ': ' . $errInfo[2], $errInfo[1], null);
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
}
