<?

define('INSTALL_REPORT_URL', 'http://kalstats.kaltura.com/index.php/events/installation_event');

class InstallReport {
	private $report_parameters = array();
	private $report_post_parameters = array();
	private $install_ids;
	
	public function __construct($email, $package_version, $install_seq_id, $install_id){	
		$this->report_parameters['email'] = $email;
		$this->report_parameters['client_type'] = 'PHP CLI';
		$this->report_parameters['server_ip'] = null;
		$this->report_parameters['host_name'] = null;
		$this->report_parameters['operating_system'] = php_uname('s').' '.OsUtils::getOsLsb();
		$this->report_parameters['architecture'] = php_uname('m');
		$this->report_parameters['php_version'] = phpversion();
		$this->report_parameters['package_version'] = $package_version;
		$this->install_ids = $install_seq_id.';'.$install_id;
	}

	public function reportInstallationStart() {
		$this->report_parameters['step'] = "Install Started";
		$this->report_parameters['code'] = "";
		$this->report_post_parameters['data'] = $install_ids;
		$this->report_post_parameters['description'] = "";
		$this->sendReport($this->report_parameters, $this->report_post_parameters);
	}
	
	public function reportInstallationFailed($failure_message) {
		$this->report_parameters['step'] = "Install Failed";
		$this->report_parameters['code'] = $failure_message;
		$this->report_post_parameters['data'] = $install_ids;
		$this->report_post_parameters['description'] = "";
		$this->sendReport($this->report_parameters, $this->report_post_parameters);
	}
	
	public function reportInstallationSuccess() {
		$this->report_parameters['step'] = "Install Success";
		$this->report_parameters['code'] = "";
		$this->report_post_parameters['data'] = $install_ids;
		$this->report_post_parameters['description'] = "";
		$this->sendReport($this->report_parameters, $this->report_post_parameters);
	}	

	/**
	 * Send current event to kaltura
	 */
	private function sendReport($get_parameters, $post_parameters) {
		// create a new cURL resource
		$ch = curl_init();		
		$url = INSTALL_REPORT_URL . '?' . http_build_query($get_parameters);
		
		// set URL and other appropriate options
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		//curl_setopt($ch, CURLOPT_HTTPGET, true);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_TIMEOUT, 10); 
		curl_setopt($ch, CURLOPT_POST, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, $post_parameters);
		
		// grab URL and pass it to the browser
		$result = curl_exec($ch);
		if (!$result) {
			logMessage(L_ERROR, 'Failed sending install report '.curl_error($ch));
		}
		
		// close cURL resource, and free up system resources
		curl_close($ch);
	}
}