/***
 * author @ 000vaibhav000@gmail.com
 ***/

<?
define('CLIENT_VERSION','1.0.2');
define('CLIENT_VERSION_CMP','0.6.3');
if ( stripos($_SERVER['HTTP_HOST'],'client.webdev') !== false ) {
    define('API_DOMAIN','IP ADDRESS HERE'); //api.webdev
} else {
    define('API_DOMAIN','DOMAIN NAME HERE');
}

define('API_VERSION','1.0');
define('API_PATH','/1.0/');


//constants
define('IP_REAL',0);
define('IP_SHARE',1);
define('IP_PROXY',2);

//variables
define('DEF_DYN_VARS','country,city,region');
define('DEF_DYN_VARS_STR','country, city or region');

//memcached
define('APC_EXPIRY', 1800); //number of seconds

//curl
$curl_config = Array(
  CURLOPT_HEADER=>0,
	CURLOPT_RETURNTRANSFER=>1,
	CURLOPT_CONNECTTIMEOUT=>1,
	CURLOPT_TIMEOUT=>2,
	CURLOPT_DNS_CACHE_TIMEOUT=>120, //seconds
	CURLOPT_FORBID_REUSE=>0,
	CURLOPT_FRESH_CONNECT=>0
);
?>
