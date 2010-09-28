<?php

define("FILE_INSTALL_TEXTS", "installer/texts.ini"); // log user and error level texts are defined in here, info level is defined in the code

class TextsConfig {
	private $texts_config;

	public function __construct() {
		$this->texts_config = parse_ini_file(FILE_INSTALL_TEXTS, true);
	}

	public function getFlowText($key) {
		return $this->texts_config['flow'][$key];
	}
	
	public function getInputText($key) {
		return $this->texts_config['input'][$key];
	}	
	
	public function getErrorText($key) {
		return $this->texts_config['errors'][$key];
	}	
}