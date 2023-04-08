<?php 
require_once  dirname(__DIR__) .  '/mybb/inc/plugins/abuseipdb/handler.php';
require_once  dirname(__DIR__) . '/mybb/inc/plugins/abuseipdb/utils.php';

$response = array(
	"ipAddress" => "127.0.0.1",
	"isPublic" => true,
	"ipVersion" =>  4,
	"isWhitelisted" => true,
	"abuseConfidenceScore" => 0,
	"countryCode" => null,
	"countryName" => null,
	"usageType" => "Reserved",
	"isp" =>  "Loopback",
	"domain" => null,
	"hostnames" => [],
	"totalReports" => 1,
	"numDistinctUsers" => 1,
	"lastReportedAt" => "2018-12-20T20:55:14+00:00"
);

$settings = array(
	'abuseipdb_min_abuse_confidence_score' => 20,
	'abuseipdb_spam_rejection_notice' => 'fuck off',
	'abuseipdb_country_list' => 'mu, zu',
	'abuseipdb_country_policy' => 0,
	'abuseipdb_ip_country_rejected_notice' => 'not allowed in your country',
	'abuseipdb_ip_usage_type_rejected_notice' => 'There is a problem with your ip address.',
	'abuseipdb_commercial_policy' => 1,
	'abuseipdb_organization_policy' => 1,
	'abuseipdb_government_policy'=> 1,
	'abuseipdb_military_policy'=> 1,
	'abuseipdb_edu_policy'=> 1,
	'abuseipdb_library_policy'=> 1,
	'abuseipdb_cdn_policy'=> 1,
	'abuseipdb_fixed_line_policy'=> 1,
	'abuseipdb_mobile_policy'=> 1,
	'abuseipdb_proxy_policy'=> 1,
	'abuseipdb_spider_policy'=> 1,
	'abuseipdb_reserved_policy'=> 1
);

function whitelisted() {
	global $response, $settings;

	$my_res      = $response;
	$my_settings = $settings;	

	$my_res["isWhitelisted"] = true;	

	$handler = new AbuseIPDB_Handler($my_res, $my_settings);

	// should not throw
	$handler->check();

	print("OK - whitelisted ip not throws\n");
}

function abuseScoreAboveThreshold() {
	global $response, $settings;

	$my_res      = $response;
	$my_settings = $settings;	

	$my_res['isWhitelisted'] = false;
	$my_res['abuseConfidenceScore'] = 21;
	$settings['abuseipdb_min_abuse_confidence_score'] = 20;

	$handler = new AbuseIPDB_Handler($my_res, $my_settings);
	
	try {
		$handler->check();
		die("line above should trow");
	} catch(AbuseIPDB_Exception $e) {
		//print("$e\n");
		print("OK - abuseScoreAboveThreshold()\n");
	}
}


function abuseScoreBelowThreshold() {
	global $response, $settings;

	$my_res      = $response;
	$my_settings = $settings;	

	$my_res['isWhitelisted'] = false;
	$my_res['abuseConfidenceScore'] = 10;
	$settings['abuseipdb_min_abuse_confidence_score'] = 20;

	$handler = new AbuseIPDB_Handler($my_res, $my_settings);
	
	try {
		$handler->check();
		print("OK - abuseScoreBelowThreshold()\n");
	} catch(AbuseIPDB_Exception $e) {
		die("abuseScoreBelowThreshold()");		
	}
}


function countryWhitelist() {
	global $response, $settings;

	$my_res      = $response;
	$my_settings = $settings;	

	$my_res['isWhitelisted'] = false;
	$my_res['abuseConfidenceScore'] = 0;
	$my_res['countryCode'] = 'mu';

	$my_settings['abuseipdb_min_abuse_confidence_score'] = 20;
	$my_settings['abuseipdb_country_policy'] = 1; // whitelist
	$my_settings['abuseipdb_country_list'] = 'mu, nu, zu';

	$handlerAllow = new AbuseIPDB_Handler($my_res, $my_settings);
	
	try {
		$handlerAllow->check();
		print("OK - countryWhitelist() allow 'mu' \n");
	} catch(AbuseIPDB_Exception $e) {
		die("countryWhitelist()");		
	}

	// 'pu' is not in the list
	$my_res['countryCode'] = 'pu';

	$handlerDeny = new AbuseIPDB_Handler($my_res, $my_settings);
	try {
		$handlerDeny->check();
		die("pu is not in the list");		
	} catch(AbuseIPDB_Exception $e) {
		assert($e->getMessage() == $settings["abuseipdb_ip_country_rejected_notice"], "abuseipdb_ip_country_rejected_notice");		
		print("OK - countryWhitelist() deny 'pu' \n");
	}
}

function usage() {
	global $response, $settings;

	$my_res      = $response;
	$my_settings = $settings;	

	$my_res['isWhitelisted'] = false;
	$my_res['abuseConfidenceScore'] = 0;
	

	$my_settings['abuseipdb_proxy_policy'] = 0;
	$my_settings['abuseipdb_commercial_policy'] = 1;	

	try {
		$my_res['usageType'] = ABUSEIPDB_USAGE_TYPE_PROXY;
		(new AbuseIPDB_Handler($my_res, $my_settings))->check();
		die("line above should throw");
	} catch(AbuseIPDB_Exception $e) {
		assert($e->getMessage() == $my_settings['abuseipdb_ip_usage_type_rejected_notice'], 'abuseipdb_ip_usage_type_rejected_notice');
		print("OK - usage() deny proxy\n");
	}

	try {
		$my_res['usageType'] = ABUSEIPDB_USAGE_TYPE_COMMERCIAL;
		(new AbuseIPDB_Handler($my_res, $my_settings))->check();
		print("OK - usage() allow commercial\n");
	} catch(AbuseIPDB_Exception $e) {
		die("should allow commercial usage");
	}
}


whitelisted();
abuseScoreAboveThreshold();
abuseScoreBelowThreshold();
countryWhitelist();
usage();

?>