<?php


class DataWarehouseStep extends InstallStep
{
	public function install()
	{		
		// copy data warehouse files to /home/etl
		$result = FileUtils::fullCopy(PACKAGE_DIR.PACKAGE_DWH, myConf::get('ETL_HOME_DIR'), true);
		
		$replace_groups = parse_ini_file('../config/config_files_to_replace.ini', true);
		if ($result === true) { $result = FileUtils::replaceTokens(myConf::get('ETL_HOME_DIR'), $replace_groups['dwh']['files'], myConf::getAll()); }
			
		// chmod
		if ($result === true) { $result = FileUtils::chmod(myConf::get('ETL_HOME_DIR'), '-R 700'); }
		
		// chown to etl user
		if ($result === true) { $result = FileUtils::chown(myConf::get('ETL_HOME_DIR'), 'etl'); }
		
		// create dataase user etl@localhost
		if ($result === true) { $result = DatabaseUtils::executeQuery(
			"GRANT ALL ON *.* TO 'etl'@'localhost' IDENTIFIED BY 'etl';",
			myConf::get('DB1_HOST'), myConf::get('DB1_USER'), myConf::get('DB1_PASS'), null, myConf::get('DB1_PORT')
		); }
		// create dataase user etl@%
		if ($result === true) { $result = DatabaseUtils::executeQuery(
			"GRANT ALL ON *.* TO 'etl'@'%' IDENTIFIED BY 'etl';",
			myConf::get('DB1_HOST'), myConf::get('DB1_USER'), myConf::get('DB1_PASS'), null, myConf::get('DB1_PORT')
		); }

		// crate database user kaltura_read
		if ($result === true) { $result = DatabaseUtils::executeQuery(
			"GRANT SELECT ON *.* TO 'kaltura_read'@'localhost' IDENTIFIED BY 'kaltura_read';",
			myConf::get('DB1_HOST'), myConf::get('DB1_USER'), myConf::get('DB1_PASS'), null, myConf::get('DB1_PORT')
		); }
		
		// flush priviliges
		if ($result === true) { $result = DatabaseUtils::executeQuery(
			"flush privileges;",
			myConf::get('DB1_HOST'), myConf::get('DB1_USER'), myConf::get('DB1_PASS'), null, myConf::get('DB1_PORT')
		); }

		// execute installation
		if ($result === true) { $result = FileUtils::execAsUser(myConf::get('ETL_HOME_DIR').DIRECTORY_SEPARATOR.'ddl'.DIRECTORY_SEPARATOR.'dwh_ddl_install.sh', 'etl'); }
						
		return $result;		
	}	
		
	public function prepareForRetry()
	{
		$result = FileUtils::execAsUser('/home/etl/ddl/dwh_drop_databases.sh' , 'etl');
		if ($result === true) {
			return true;
		}
		return false;
	}			
}
