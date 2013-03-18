<?php
require_once "phing/Task.php";

class CreateUserTask extends Task
{
	/**
	 * @var string
	 */
	private $username = null;
	
	/**
	 * @var int
	 */
	private $uid = null;
	
	/**
	 * Create the new user only if it doesn't exist already
	 * @var boolean
	 */
	private $ifNotExists = null;

	/**
	 * The new user will be created using $homeDir as the value for the user's login directory.
	 * The default is to append the login name to base directory and use that as the login directory name.
	 * The directory $homeDir does not have to exist but will not be created if it is missing.
	 *
	 * @var string
	 */
	private $homeDir = null;

	/**
	 * Create the user's home directory if it does not exist.
	 *
	 * @var boolean
	 */
	private $createHome = null;

	/**
	 * Any text string. It is generally a short description of the login, and is currently used as the field for the user's full name.
	 *
	 * @var string
	 */
	private $comment = null;

	/**
	 * The date on which the user account will be disabled. The date is specified in the format YYYY-MM-DD.
	 *
	 * @var string
	 */
	private $expireDate = null;

	/**
	 * The number of days after a password expires until the account is permanently disabled. A value of 0 disables the account as soon as the password has expired, and a value of -1 disables the feature.
	 *
	 * @var int
	 */
	private $inactive = null;

	/**
	 * The group name or number of the user's initial login group.
	 * A group number must refer to an already existing group.
	 *
	 * @var string
	 */
	private $group = null;

	/**
	 * A list of supplementary groups which the user is also a member of.
	 * A group number must refer to an already existing group.
	 * The default is for the user to belong only to the initial group.
	 *
	 * @var string comma seperated
	 */
	private $groups = null;

	/**
	 * Create a system account.
	 *
	 * @var boolean
	 */
	private $system = null;

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
		$this->createMissingGroups();

		if($this->ifNotExists)
		{
			$output = null;
			$returnedValue = null;
			$this->log("Checking if user [{$this->username}] exists", Project::MSG_VERBOSE);
			exec("id {$this->username}", $output, $returnedValue);
			if($returnedValue === 0)
			{
				$this->log("User [{$this->username}] already exists", Project::MSG_VERBOSE);
				$this->updateUser();
				return;
			}
			$this->log("User [{$this->username}] does not exist", Project::MSG_VERBOSE);
		}

		$this->createUser();
	}

	/**
	 * Make sure that all groups exist
	 */
	protected function createMissingGroups()
	{
		if($this->group)
			$this->createMissingGroup($this->group);

		if($this->groups)
		{
			$groups = explode(',', $this->groups);
			foreach($groups as $group)
				$this->createMissingGroup(trim($group));
		}
	}

	/**
	 * Make sure that the specified group exists
	 */
	protected function createMissingGroup($group)
	{
		$returnedValue = null;
		$count = system("grep -c '^$group:' /etc/group", $returnedValue);
		if($returnedValue === 0 && intval($count) > 0)
			return;

		system("groupadd -f '$group'");
	}

	/**
	 * The main entry point method.
	 */
	public function updateUser()
	{
		$commandArguments = array("usermod");

		if($this->homeDir)
			$commandArguments[] = "--home '{$this->homeDir}'";

		if($this->comment)
			$commandArguments[] = "--comment '{$this->comment}'";

		if($this->expireDate)
			$commandArguments[] = "--expiredate {$this->expireDate}";

		if($this->inactive)
			$commandArguments[] = "--inactive '{$this->inactive}'";

		if($this->group)
			$commandArguments[] = "--gid '{$this->group}'";

		if($this->groups)
		{
			$commandArguments[] = "--append";
			$commandArguments[] = "--groups '{$this->groups}'";
		}

		// there is nothing to update
		if(!count($commandArguments))
			return;

		$commandArguments[] = $this->username;

		$command = implode(' ', $commandArguments);
		$returnedValue = null;
		$this->log("Executing: $command", Project::MSG_VERBOSE);
		passthru($command, $returnedValue);

		if($returnedValue != 0)
			throw new Exception("Can't update existing user");
	}

	/**
	 * The main entry point method.
	 */
	public function createUser()
	{
		$commandArguments = array("useradd");

		if($this->uid)
			$commandArguments[] = "--uid $this->uid";
		
		if($this->homeDir)
			$commandArguments[] = "--home '{$this->homeDir}'";
			
		if($this->comment)
			$commandArguments[] = "--comment '{$this->comment}'";

		if($this->expireDate)
			$commandArguments[] = "--expiredate {$this->expireDate}";

		if($this->inactive)
			$commandArguments[] = "--inactive '{$this->inactive}'";

		if($this->system)
			$commandArguments[] = "--system";

		if($this->group)
			$commandArguments[] = "--gid '{$this->group}'";
		else
			$commandArguments[] = "--user-group";

		if($this->groups)
		{
			$commandArguments[] = "--groups '{$this->groups}'";
		}

		$commandArguments[] = $this->username;

		$command = implode(' ', $commandArguments);
		$returnedValue = null;
		$this->log("Executing: $command", Project::MSG_VERBOSE);
		passthru($command, $returnedValue);

		switch($returnedValue)
		{
			case 1:
				throw new Exception("Can't update password file");

			case 2:
				throw new Exception("Invalid command syntax");

			case 3:
				throw new Exception("Invalid argument to option");

			case 4:
				throw new Exception("UID already in use");

			case 6:
				throw new Exception("Specified group doesn't exist");

			case 9:
				throw new Exception("Username already in use");

			case 10:
				throw new Exception("Can't update group file");

			case 12:
				throw new Exception("Can't create home directory");

			case 13:
				throw new Exception("Can't create mail spool");
		}
	}

	/**
	 * @param string $username
	 */
	public function setUsername($username)
	{
		$this->username = $username;
	}

	/**
	 * @param int $uid
	 */
	public function setUid($uid)
	{
		$this->uid = $uid;
	}

	/**
	 * @param boolean $ifNotExists
	 */
	public function setIfNotExists($ifNotExists)
	{
		$this->ifNotExists = $ifNotExists;
	}

	/**
	 * @param string $homeDir
	 */
	public function setHomeDir($homeDir)
	{
		$this->homeDir = $homeDir;
	}

	/**
	 * @param boolean $createHome
	 */
	public function setCreateHome($createHome)
	{
		$this->createHome = $createHome;
	}

	/**
	 * @param string $comment
	 */
	public function setComment($comment)
	{
		$this->comment = $comment;
	}

	/**
	 * @param string $expireDate
	 */
	public function setExpireDate($expireDate)
	{
		$this->expireDate = $expireDate;
	}

	/**
	 * @param int $inactive
	 */
	public function setInactive($inactive)
	{
		$this->inactive = $inactive;
	}

	/**
	 * @param string $group
	 */
	public function setGroup($group)
	{
		$this->group = $group;
	}

	/**
	 * @param array $groups
	 */
	public function setGroups($groups)
	{
		$this->groups = $groups;
	}

	/**
	 * @param boolean $system
	 */
	public function setSystem($system)
	{
		$this->system = $system;
	}
}
