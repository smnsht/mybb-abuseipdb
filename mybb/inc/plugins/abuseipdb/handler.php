<?php
require_once __DIR__ . '/utils.php';

class AbuseIPDB_Exception extends Exception
{
	function __construct(string $message)
	{
		parent::__construct($message);
	}
}

class AbuseIPDB_Handler
{
	private $_response;
	private $_settings;

	public function __construct($response, $settings)
	{
		$this->_response = $response;
		$this->_settings = $settings;
	}

	public function check()
	{
		$response = $this->_response;
		$settings = $this->_settings;

		// skip all checks on whitelisted ip address
		if ($response["isWhitelisted"]) {
			return;
		}

		$abuseScore = intval($response["abuseConfidenceScore"]);
		$threshold = intval($settings['abuseipdb_min_abuse_confidence_score']);

		// check abuse score
		if ($abuseScore >= $threshold) {
			throw new AbuseIPDB_Exception($settings['abuseipdb_spam_rejection_notice']);
		}

		if (isset($response['countryCode'])) {
			$policy = intval($settings['abuseipdb_country_policy']);
			
			if($policy != 0) {
				// check country policy
				$countries_r  = array_map('trim', explode(',', strtolower($settings["abuseipdb_country_list"])));
				$country_code = strtolower($response['countryCode']);
				$found        = in_array($country_code, $countries_r);

				// whitelist = 1, // blacklist = -1
				if(($policy == 1 && !$found) || ($policy == -1 && $found)) {
					throw new AbuseIPDB_Exception($settings["abuseipdb_ip_country_rejected_notice"]);
				}
			}									
		}

		
		$setting_key = usage_to_setting_key($response['usageType']);

		// check usage
		if($settings[$setting_key] == 0) {
			throw new AbuseIPDB_Exception($settings["abuseipdb_ip_usage_type_rejected_notice"]);
		}
	}
}
?>