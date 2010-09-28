<?php

define("FILE_INSTALL_CONFIG", "installer/installation.ini");

class InstallConfig {	
	private $install_config;

	public function __construct() {
		$this->install_config = parse_ini_file(FILE_INSTALL_CONFIG, true);
	}

	public function getTokenFiles() {
		return $this->install_config['token_files']['files'];
	}
	
	public function getChmodItems() {
		return $this->install_config['chmod_items']['items'];
	}	
	
	public function getSymLinks() {
		return $this->install_config['symlinks']['links'];
	}

	public function getDatabases() {
		return $this->install_config['databases']["dbs"];
	}		
}