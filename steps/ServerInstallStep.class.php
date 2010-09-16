<?php

class ServerInstallStep extends InstallStep
{
	public function install()
	{
		// copy app dir
		$result = FileUtils::fullCopy(PACKAGE_DIR.PACKAGE_APP, myConf::get('BASE_DIR'), true);
		
		// copy web dir
		if ($result === true) { $result = FileUtils::fullCopy(PACKAGE_DIR.PACKAGE_WEB, myConf::get('WEB_DIR'), true); }
		
		$replace_groups = parse_ini_file(PACKAGE_DIR.'../config/config_files_to_replace.ini', true);
		if ($result === true) { $result = FileUtils::replaceTokensForGroup(myConf::get('BASE_DIR').'/', $replace_groups['app']['files'], myConf::getAll()); }
			
		// adjust binary files
		if ($result === true) { $result = $this->adjustBinFiles(); }
		
		// chmod
		if ($result === true) { $result = $this->chmod();	}
		
		// create a symbolic link for the logrotate
		symlink(myConf::get('BASE_DIR').'/logrotate/kaltura_log_rotate', '/etc/logrotate.d/kaltura_log_rotate');
		
		if ($result !== true) {
			$this->addStepToError($result);
		}
		return $result;
	}
		
	private function chmod()
	{
		$result = FileUtils::chmod(myConf::get('BASE_DIR'), '+r -R');
		if ($result === true) { $result = FileUtils::chmod(myConf::get('WEB_DIR'), '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('BIN_DIR'), '775 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('TMP_DIR'), '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('LOG_DIR'), '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR'), '775 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR').'scripts/', '775 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR').'/cache/', '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR').'/api_v3/cache/', '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR').'/alpha/cache/', '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR').'/batch/cache/', '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR').'/generator/cache/', '777 -R'); }
		if ($result === true) { $result = FileUtils::chmod(myConf::get('APP_DIR').'/alpha/config/kConf.php', '777'); }
		
		return $result;
	}
	
	private function adjustBinFiles()
	{
		$os_name = 	InstallUtils::getOsName();
		$architecture = InstallUtils::getSystemArchitecture();
		
		if (ErrorObject::isErrorObject($os_name)) {
			return $os_name; //error
		}

		if (ErrorObject::isErrorObject($architecture)) {
			return $architecture; //error	
		}
		
		$bin_subdir = $os_name.'/'.$architecture;

		$result = FileUtils::fullCopy(myConf::get('BIN_DIR').'/'.$bin_subdir, myConf::get('BIN_DIR'), true);
		if ($result === true) $result = FileUtils::recursiveDelete(myConf::get('BIN_DIR').'/'.$os_name);

		symlink(myConf::get('BIN_DIR').'run/run-ffmpeg.sh', myConf::get('BIN_DIR').'ffmpeg');
		symlink(myConf::get('BIN_DIR').'run/run-mencoder.sh', myConf::get('BIN_DIR').'mencoder');
		symlink(myConf::get('BIN_DIR').'run/run-ffmpeg-aux.sh', myConf::get('BIN_DIR').'ffmpeg-aux');
		
		if ($result !== true) {
			$this->addStepToError($result);
		}
		return $result;		
	}
	
	
	public function prepareForRetry()
	{
		return true;
	}
			
		
}
