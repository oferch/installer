<?php


class OperDatabaseStep extends InstallStep
{

	public function install()
	{	
		// create operational database
		$result = DatabaseUtils::createDb(myConf::get('DB1_NAME'), myConf::get('DB1_HOST'), myConf::get('DB1_USER'), myConf::get('DB1_PASS'), myConf::get('DB1_PORT'));
		if ($result !== true) {
			$this->addStepToError($result);
			return $result;
		}
		
		$result = DatabaseUtils::runScript(myConf::get('APP_DIR').'/app/deployment/base/sql/create_kaltura_db.sql', myConf::get('DB1_HOST'), myConf::get('DB1_USER'), myConf::get('DB1_PASS'), myConf::get('DB1_NAME'), myConf::get('DB1_PORT'));
		if ($result !== true) {
			$this->addStepToError($result);
			return $result;
		}				
		
		// create stats database
		$result = DatabaseUtils::createDb(myConf::get('DB_STATS_NAME'), myConf::get('DB_STATS_HOST'), myConf::get('DB_STATS_USER'), myConf::get('DB_STATS_PASS'), myConf::get('DB_STATS_PORT'));
		if ($result !== true) {
			$this->addStepToError($result);
			return $result;
		}
		
		$result = DatabaseUtils::runScript(myConf::get('APP_DIR').'/app/deployment/base/sql/create_stats_db.sql', myConf::get('DB_STATS_HOST'), myConf::get('DB_STATS_USER'), myConf::get('DB_STATS_PASS'), myConf::get('DB_STATS_NAME'), myConf::get('DB_STATS_PORT'));
		if ($result !== true) {
			$this->addStepToError($result);
			return $result;
		}
				
    	return true;	
	}
		
	public function prepareForRetry()
	{
		$result = DatabaseUtils::dropDb(myConf::get('DB1_NAME'), myConf::get('DB1_HOST'), myConf::get('DB1_USER'), myConf::get('DB1_PASS'), myConf::get('DB1_PORT'));
		
		if ($result === true) $result = DatabaseUtils::dropDb(myConf::get('DB_STATS_NAME'), myConf::get('DB_STATS_HOST'), myConf::get('DB_STATS_USER'), myConf::get('DB_STATS_PASS'), myConf::get('DB_STATS_PORT'));
		
		return ($result === true);
	}
		
}