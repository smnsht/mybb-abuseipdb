<?php

// Disallow direct access to this file for security reasons
if (!defined('IN_MYBB')) {
	die("Direct initialization of this file is not allowed.");
}

define('ABUSEIPDB_SETTINGS_GROUP_NAME', 'abuseipdb_setting_group');

// AbuseIPDB ip usage types
define('ABUSEIPDB_USAGE_TYPE_COMMERCIAL', 'Commercial');
define('ABUSEIPDB_USAGE_TYPE_ORGANIZATION', 'Organization');
define('ABUSEIPDB_USAGE_TYPE_GOVERNMENT', 'Government');
define('ABUSEIPDB_USAGE_TYPE_MILITARY', 'Military');
define('ABUSEIPDB_USAGE_TYPE_EDU', 'University/College/School');
define('ABUSEIPDB_USAGE_TYPE_LIBRARY', 'Library');
define('ABUSEIPDB_USAGE_TYPE_CDN', 'Content Delivery Network');
define('ABUSEIPDB_USAGE_TYPE_FIXED_LINE', 'Fixed Line ISP');
define('ABUSEIPDB_USAGE_TYPE_MOBILE ISP', 'Mobile ISP');
define('ABUSEIPDB_USAGE_TYPE_PROXY', 'Data Center/Web Hosting/Transit');
define('ABUSEIPDB_USAGE_TYPE_SPIDER', 'Search Engine Spider');
define('ABUSEIPDB_USAGE_TYPE_RESERVED', 'Reserved');


if(defined('IN_ADMINCP'))
{

}
else
{	
	$plugins->add_hook('member_register_start', 'on_member_register_start');
	$plugins->add_hook('member_do_register_start', 'check');	
}

function abuseipdb_info()
{
	return array(
		"name" => "AbuseIPDB",
		"description" => "MyBB AbuseIPDB plugin",
		"website" => "https://www.abuseipdb.com/",
		"author" => "https://github.com/smnsht",
		"version" => "1.0",
		"guid" => "",
		"codename" => "abuseipdb",
		"compatibility" => "*"
	);
}

function abuseipdb_install()
{
	global $db;

	$setting_group = array(
		'name' => ABUSEIPDB_SETTINGS_GROUP_NAME,
		'title' => 'AbuseIPDB Plugin Settings',
		'description' => 'AbuseIPDB Plugin Settings',
		// The order your setting group will display
		//'disporder' => 5, 
		'isdefault' => 0
	);


	$gid = $db->insert_query("settinggroups", $setting_group);

	$setting_array = array(

		'abuseipdb_api_key' => array(
			'title' => 'API key',
			'description' => 'Enter your AbuseIPDB key:',
			'optionscode' => 'text',
			'value' => '???',
			'disporder' => 1
		),

		'abuseipdb_country_list' => array(
			'title' => 'Country list?',
			'description' => "Comma separated list or country iso codes, for instance:  US, CA, AU",
			'optionscode' => "text",
			'value' => '',
			'disporder' => 2
	 	),

		'abuseipdb_country_policy' => array(
			'title' => 'Country policy?',
			'description' => "How to treat selected countries: <b>whitelist</b> = allow, <b>blacklist</b> = block.\nSelect your country policy:",
			'optionscode' => "select\n0=Ignore\n1=Whitelist\n-1=Blacklist",
			'value' => 0,
			'disporder' => 3
	 	),
		
		'abuseipdb_ip_country_rejected_notice' => array(
			'title' => 'Country policy notice rejection for user?',
			'description' => "What notice to show to user if rejected by country policy",
			'optionscode' => "text",
			'value' => "Sorry, access to the forum is restricted for your location",
			'disporder' => 4
	 	),

		'abuseipdb_ip_usage_type_rejected_notice' => array(
			'title' => 'Not allowed ip notice to user?',
			'description' => "What notice to show to user if rejected by ip usage type",
			'optionscode' => "text",
			'value' => "There is a problem with your ip address.",
			'disporder' => 5
	 	),

		'abuseipdb_min_abuse_confidence_score' => array(
			'title' => 'Minimum abuse_confidence score',
			'description' => "Block any ip with abuse confidence score greater than this. Allowed value between 0 and 100.",
			'optionscode' => "numeric",
			'value' => 20,
			'disporder' => 6
	 	),

		'abuseipdb_spam_rejection_notice' => array(
			'title' => 'Spam rejection notice',
			'description' => "Rejection notice for user. Shown when ip address has abuse score above threshold.",
			'optionscode' => "text",
			'value' => "Fuck off",
			'disporder' => 7
	 	)
	);

	foreach ($setting_array as $name => $setting) {
		$setting['name'] = $name;
		$setting['gid'] = $gid;

		$db->insert_query('settings', $setting);
	}

	$disporder = 8;
	
	$usage_types = [
		'Commercial' => ['commercial', 1],
		'Organization' => ['organization', 1],
		'Government' => ['government', 1],
		'Military' => ['military', 1],
		'University/College/School' => ['edu', 1],
		'Library' => ['library', 1],
		'Content Delivery Network' => ['cdn', 0],
		'Fixed Line ISP' => ['fixed_line', 1],
		'Mobile ISP' => ['mobile', 1],
		'Data Center/Web Hosting/Transit' => ['proxy', 0],
		'Search Engine Spider' => ['spider', 0],
		'Reserved' => ['reserved', 1],
	];

	// add allow_$usage_type boolean settings
	foreach($usage_types as $usage_type => $cfg) {
		$setting = array(
			'gid' => $gid,
			'name' => "abuseipdb_$cfg[0]_policy",
			'title' => "Allow *$usage_type* ip usage type?",
			'description' => "Do you want to allow *$usage_type* ip usage type?",
			'optionscode' => 'yesno',			
			'value' => $cfg[1],
	 		'disporder' => $disporder
		);
		
		$disporder++;

		$db->insert_query('settings', $setting);
	}	

	rebuild_settings();
}

function abuseipdb_is_installed()
{
	global $db;

	$where = sprintf("name = '%s'", ABUSEIPDB_SETTINGS_GROUP_NAME);
	$query = $db->simple_select('settinggroups', '*', $where);
	$result = $db->fetch_array($query);	
	
	return is_array($result);
}

function abuseipdb_uninstall()
{
	global $db;
	
	$db->delete_query('settings', "name like 'abuseipdb_%'");
	$db->delete_query('settinggroups', sprintf("name = '%s'", ABUSEIPDB_SETTINGS_GROUP_NAME));
}

function abuseipdb_activate()
{

}

function abuseipdb_deactivate()
{

}

////////////////////////////////////////////////////////////////////////////////////////////

function on_member_register_start() 
{
	
}

function check()
{
	global $mybb, $session;

	if($session->is_spider) {

	}

	//my $ip = $session->ipaddress;
	$response = array(
		"ipAddress" => "118.25.6.39",
		"isPublic" => true,
		"ipVersion" =>  4,
		"isWhitelisted" => false,
		"abuseConfidenceScore" => 100,
		"countryCode" => "CN",
		"countryName" => "China",
		"usageType" => "Data Center/Web Hosting/Transit",
		"isp" =>  "Tencent Cloud Computing (Beijing) Co. Ltd",
		"domain" => "tencent.com",
		"hostnames" => [],
		"totalReports" => 1,
		"numDistinctUsers" => 1,
		"lastReportedAt" => "2018-12-20T20:55:14+00:00"
	);

	// 
	$abuseScore = intval($response["abuseConfidenceScore"]);
	$threshold = intval($mybb->settings['abuseipdb_min_abuse_confidence_score']);

	if($abuseScore >= $threshold) {
		die($mybb->settings['abuseipdb_spam_rejection_notice']);
	}
		
	switch($mybb->settings['abuseipdb_country_policy']) {
		case 1:
			break;

		case -1:
			break;

		default:
			break;
	}
}

?>