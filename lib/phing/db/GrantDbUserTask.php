<?php
require_once "phing/Task.php";

class GrantDbUserTask extends Task
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
	 * The user to be granted
	 * @var string
	 */
	private $user;
	
	/**
	 * The database to be granted
	 * @var string
	 */
	private $database;
	
	/**
	 * Comma separated privileges to be granted
	 * @var string
	 */
	private $privileges;
	
	/**
	 * The host to be granted
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
		$dsn = "mysql:host={$this->host};port={$this->port};";
		$pdo = new PDO($dsn, $this->loginUsername, $this->loginPassword);
		
		$statement = "SELECT Host FROM mysql.user WHERE User = '{$this->user}'";
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
		
		if($pdoStatement->rowCount() == 0)
			throw new Exception("User [{$this->user}] not found", $errInfo[0], null);
		
		$statement = "GRANT {$this->privileges} ON {$this->database}.* TO '{$this->user}'@'{$this->fromHost}'";
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
		
		$statement = "FLUSH PRIVILEGES";
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
	 * @param string $user
	 */
	public function setUser($user)
	{
		$this->user = $user;
	}

	/**
	 * @param string $database
	 */
	public function setDatabase($database)
	{
		$this->database = $database;
	}

	/**
	 * @param string $privileges
	 */
	public function setPrivileges($privileges)
	{
		$this->privileges = $privileges;
	}

	/**
	 * @param string $fromHost
	 */
	public function setFromHost($fromHost)
	{
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
