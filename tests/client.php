<?php 
require_once  dirname(__DIR__) .  '/mybb/inc/plugins/abuseipdb/client.php';


function checkLocalhost() {
	$api_key = file_get_contents("./api_key.txt");
	$client = new AbuseIPDB_Client(trim($api_key));

	$resp = $client->check("127.0.0.1");
	
	assert(is_array($resp), "response decoded");	
	assert($resp['ipAddress'] == "127.0.0.1", "data[ipAddress] == 127.0.0.1");

	print("OK check localhost\n");
}

function failedRequestFails() {
	$client = new AbuseIPDB_Client("1234");

	try {
		$client->check("127.0.0.1");
		print("Error! failedRequestFails() should throw!");
	}
	catch(Exception $e) {		
		print("got exception on request error OK\n");
	}
}

checkLocalhost();
failedRequestFails();
?>