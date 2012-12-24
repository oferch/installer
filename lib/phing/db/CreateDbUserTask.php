<?php
require_once "phing/Task.php";

class CreateDbUserTask extends Task
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
	 * The new user username to be created
	 * @var string
	 */
	private $newUsername;
	
	/**
	 * The new user password to be created
	 * @var string
	 */
	private $newPassword;
	
	/**
	 * The host that the user will be able to connect from
	 * @var string
	 */
	private $fromHost = '%';
	
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
		
		$statement = "SELECT User FROM mysql.user WHERE User = '{$this->newUsername}' AND Host = '{$this->fromHost}'";
		$this->log("Executing: $statement");
		$pdoStatement = $pdo->query($statement);
		if($pdoStatement === false)
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
		
		if($pdoStatement->rowCount() == 1)
		{
			$this->log("User [{$this->newUsername}] already exists");
			$statement = "SET PASSWORD FOR '{$this->newUsername}'@'{$this->fromHost}' = PASSWORD('{$this->newPassword}');'";
		}
		else
		{
			$statement = "CREATE USER '{$this->newUsername}'@'{$this->fromHost}' IDENTIFIED BY '{$this->newPassword}'";
		}
		
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
		if(is_numeric($port))
			$this->port = intval($port);
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
	 * @param string $newUsername
	 */
	public function setNewUsername($newUsername)
	{
		$this->newUsername = $newUsername;
	}

	/**
	 * @param string $newPassword
	 */
	public function setNewPassword($newPassword)
	{
		$this->newPassword = $newPassword;
	}

	/**
	 * @param string $fromHost
	 */
	public function setFromHost($fromHost)
	{
		if($fromHost)
			$this->fromHost = $fromHost;
	}

	/**
	 * @param boolean $dryRun
	 */
	public function setDryRun($dryRun)
	{
		$this->dryRun = $dryRun;
	}
}
