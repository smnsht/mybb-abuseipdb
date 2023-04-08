<?php 
class AbuseIPDB_Client {

	const API_BASE_URL = 'https://api.abuseipdb.com/api/v2';
	private $_apiKey;


	public function __construct(string $apiKey) {
		$this->_apiKey = $apiKey;
	}

	public function check(string $ipAddress, int $maxAgeInDays=1) {
		if($maxAgeInDays < 1 || $maxAgeInDays > 360) {
			throw new InvalidArgumentException("$maxAgeInDays out of range!");
		}		

		$query_string = http_build_query(['ipAddress' => $ipAddress, 'maxAgeInDays' => $maxAgeInDays]);
		$request_url = sprintf('%s/check?%s', self::API_BASE_URL, $query_string);

		$key = $this->_apiKey;

		$opts = array(
			'http'=>array(
			  'method'=>"GET",			  
			  'header'=>"Accept: application/json\r\n" .
						"Key: $key\r\n"
			)
		);

		$context = stream_context_create($opts);

		$file = @file_get_contents($request_url, false, $context);		

		if(!$file) {
			$error = error_get_last();
			throw new Exception($error['message']);
		}

		$obj = json_decode($file, true);	

		return $obj['data'];
	}
}

?>