<?php

// Disallow direct access to this file for security reasons
if (!defined('IN_MYBB')) {
	die("Direct initialization of this file is not allowed.");
}

require_once __DIR__ . '/abuseipdb/utils.php';
require_once __DIR__ . '/abuseipdb/client.php';
require_once __DIR__ . '/abuseipdb/handler.php';

if(defined('IN_ADMINCP'))
{

}
else
{	
	$plugins->add_hook('member_register_start', 'check');
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
	
	$table_name = TABLE_PREFIX . 'abuseapdb_cache';

	switch($db->type) {
		case "sqlite":
			$db->write_query("
				CREATE TABLE '$table_name' (
						'ip_address'	TEXT NOT NULL UNIQUE,
						'json'	TEXT NOT NULL,
						'created_at'	INTEGER NOT NULL,						
						PRIMARY KEY('ip_address')
					);
				");
			break;

		case 'mysql':
		case 'mysqli':
			$db->write_query("
				CREATE TABLE $table_name (
					`ip_address` VARCHAR(16) NOT NULL, 
					`json` VARCHAR(512) NOT NULL, 
					`created_at` INT NOT NULL, 
					PRIMARY KEY (`ip_address`)
				) ENGINE = MyISAM;
			");
			break;

		default:
			die('TODO');			
	}

	rebuild_settings();
}

function abuseipdb_is_installed()
{
	global $db;
		
	return $db->table_exists('abuseapdb_cache');	
}

function abuseipdb_uninstall()
{
	global $db;
		
	if($db->table_exists("abuseapdb_cache"))
	{		
		$db->drop_table("abuseapdb_cache", true);

		$db->delete_query('settings', "name like 'abuseipdb_%'");
		$db->delete_query('settinggroups', sprintf("name = '%s'", ABUSEIPDB_SETTINGS_GROUP_NAME));	
	}
}

function abuseipdb_activate()
{

}

function abuseipdb_deactivate()
{

}

function check()
{
	global $mybb, $session, $db;

	if($session->is_spider) {
		error("not allowed");
	}
	
	$query = $db->simple_select("abuseapdb_cache", "*", "ip_address = '$session->ipaddress'");	
	$row   = $db->fetch_array($query); 

	if(!$row) {
		$api_key = $mybb->settings["abuseipdb_api_key"];
		$client = new AbuseIPDB_Client($api_key);
		
		try {
			$response = $client->check($session->ipaddress);			
		} catch(AbuseIPDB_Exception $e) {
			trigger_error($e->getMessage(), E_USER_NOTICE);
			return;
		}		

		$db->insert_query("abuseapdb_cache", array(
			'ip_address' => $session->ipaddress,
			'json' => json_encode($response),
			'created_at' => time()
		));
		
	} else {
		$response = json_decode($row['json'], true);		
	}

	$handler = new AbuseIPDB_Handler($response, $mybb->settings);
	

	try {
		$handler->check();
	} catch(AbuseIPDB_Exception $e) {
		error($e->getMessage());
	}
}

?>