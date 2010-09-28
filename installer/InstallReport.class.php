<?

define('INSTALL_REPORT_URL', 'http://kalstats.kaltura.com/index.php/events/installation_event');

class InstallReport {
	private $report_parameters = array();
	private $report_post_parameters = array();
	private $install_ids;
	
	public function __construct($email, $package_version, $install_seq_id, $install_id){	
		$report_parameters['email'] = $email;
		$report_parameters['client_type'] = 'PHP CLI';
		$report_parameters['server_ip'] = isset($_SERVER['SERVER_ADDR']) ? $_SERVER['SERVER_ADDR'] : null;
		$report_parameters['host_name'] = php_uname('n');			
		$report_parameters['operating_system'] = php_uname('s').' '.getOsLsb();
		$report_parameters['architecture'] = php_uname('m');
		$report_parameters['php_version'] = phpversion();
		$report_parameters['package_version'] = $package_version;
		$install_ids = $install_seq_id.';'.$install_id;
	}

	public function reportInstallationStart() {
		$report_parameters['step'] = "Install Started";
		$report_parameters['code'] = "";
		$report_post_parameters['data'] = $install_ids;
		$report_post_parameters['description'] = "";
		sendReport($report_parameters, $report_post_parameters);
	}
	
	public function reportInstallationFailed($failure_message) {
		$report_parameters['step'] = "Install Failed";
		$report_parameters['code'] = $failure_message;
		$report_post_parameters['data'] = $install_ids;
		$report_post_parameters['description'] = "";
		sendReport($report_parameters, $report_post_parameters);
	}
	
	public function reportInstallationSuccess() {
		$report_parameters['step'] = "Install Success";
		$report_parameters['code'] = "";
		$report_post_parameters['data'] = $install_ids;
		$report_post_parameters['description'] = "";
		sendReport($report_parameters, $report_post_parameters);
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