<?php

define("FILE_INSTALL_TEXTS", "installer/texts.ini"); // log user and error level texts are defined in here, info level is defined in the code

class TextsConfig {
	private $texts_config;

	function __construct() {
		$texts_config = parse_ini_file(FILE_INSTALL_TEXTS, true);
	}

	public function getFlowText($key) {
		return $texts_config['flow'][$key];
	}
	
	public function getInputText($key) {
		return $texts_config['input'][$key];;
	}	
	
	public function getErrorText($key) {
		return $texts_config['errors'][$key];
	}	
}