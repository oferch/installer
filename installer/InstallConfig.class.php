<?php

define("FILE_INSTALL_CONFIG", "installer/installation.ini");

class InstallConfig {	
	private $install_config;

	function __construct() {
		$install_config = parse_ini_file(FILE_INSTALL_CONFIG, true);
	}

	public function getTokenFiles() {
		return $install_config['token_files']['files'];
	}
	
	public function getChmodItems() {
		return $install_config['chmod_items']['items'];
	}	
	
	public function getSymLinks() {
		return $install_config['symlinks']['links'];
	}

	public function getDatabases() {
		return $install_config['databases']["dbs"];
	}		
}