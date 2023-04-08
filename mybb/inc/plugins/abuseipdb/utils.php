<?php 

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
define('ABUSEIPDB_USAGE_TYPE_MOBILE_ISP', 'Mobile ISP');
define('ABUSEIPDB_USAGE_TYPE_PROXY', 'Data Center/Web Hosting/Transit');
define('ABUSEIPDB_USAGE_TYPE_SPIDER', 'Search Engine Spider');
define('ABUSEIPDB_USAGE_TYPE_RESERVED', 'Reserved');

function usage_to_setting_key(string $usage) {
	switch($usage) {
		case ABUSEIPDB_USAGE_TYPE_COMMERCIAL:
		case ABUSEIPDB_USAGE_TYPE_ORGANIZATION:
		case ABUSEIPDB_USAGE_TYPE_GOVERNMENT:
		case ABUSEIPDB_USAGE_TYPE_MILITARY:
		case ABUSEIPDB_USAGE_TYPE_LIBRARY:
		case ABUSEIPDB_USAGE_TYPE_RESERVED:
			return sprintf('abuseipdb_%s_policy', strtolower($usage));
			
		case ABUSEIPDB_USAGE_TYPE_EDU:
			return 'abuseipdb_edu_policy';
		
		case ABUSEIPDB_USAGE_TYPE_CDN:
			return 'abuseipdb_cdn_policy';
		
		case ABUSEIPDB_USAGE_TYPE_FIXED_LINE:
			return 'abuseipdb_fixed_line_policy';
		
		case ABUSEIPDB_USAGE_TYPE_MOBILE_ISP:
			return 'abuseipdb_mobile_policy';
		
		case ABUSEIPDB_USAGE_TYPE_PROXY:
			return 'abuseipdb_proxy_policy';
		
		case ABUSEIPDB_USAGE_TYPE_SPIDER:
			return 'abuseipdb_spider_policy';
		
		default:
			throw new Exception("unknown usage key $usage!");
	}
}

?>