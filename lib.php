/***
 * author @ 000vaibhav000@gmail.com
 ***/

<?
if ( (isset($_COOKIE['debug']) && $_COOKIE['debug'] == 'on' && $_GET['debug'] != 'off') || $_GET['debug'] == 'on' ) {
  define('DEBUG', true);
	error_reporting(~E_ALL ^ ~E_NOTICE);
	ini_set("display_errors", 1);
	$debug_messages = Array();
} else {
	define('DEBUG', false);
}

if ( (isset($_COOKIE['adv']) && $_COOKIE['adv'] == 'on' && $_GET['adv'] != 'off') || $_GET['adv'] == 'on' ) {
	define('ADV_EDIT',true);
} else {
	define('ADV_EDIT',false);
}

if ( !file_exists('../config.php') ) {
	showHead();
	showMessage(false, '<b>Error:</b> Config File Not Found! Reinstall Application To Recreate The Config File');
	showTail();
	Die();
}

include 'config.php';
include 'constants.php';

if ( DEBUG ) {
	debugLog('$_REQUEST', $_REQUEST);
	debugLog('$_GET', $_GET);
	debugLog('$_POST', $_POST);
	debugLog('$_SERVER', $_SERVER);
}

session_start();

//make sure user is logged in:

if ( !isset($_SESSION['login_session']) && CURRENT_PAGE != 'login' ) {
	header("Location: " 'login.php');
	Die();
}

function shortenUrl($str) {
	$maxLen = 15;
	$halfLen = (int)$maxLen - 4 / 2;
	$str = str_ireplace('http://', '', $str);
	$str = str_ireplace('https://', '', $str);
	$str = str_ireplace('www.', '', $str);
	if ( strlen($str) > $maxLen ) {
		$str = substr($str, 0, $halfLen) . '....' . substr($str, strlen($str) - $halfLen, $halfLen);
	}
	return $str;
}

function showHead() {
	header("X-Robots-Tag: noindex nofollow");
	include_once('head.php');
	printStatus();
}

function showTail($callback = null) {
	include_once('tail.php');
}

function showMessage($status, $msg) {
	global $statusMsgs;

	$statusMsgs[] = Array('status' => $status, 'msg' => $msg);
}

function printStatus() {
	global $statusMsgs;

	if ( count($statusMsgs) > 0 ) {
		foreach ( $statusMsgs as $msg ) {
			$typeClass = $msg['status'] ? 'confirm' : 'error';
			$icon = $msg['status'] ? ICON_OK : ICON_ERR;
			?>
		<div class="message <?= $typeClass ?>">
			<img src="<?= $icon ?>" class="icon"><?= $msg['msg'] ?>
		</div>
		<?
		}
	}
}


function debugLog($msg, $var) {
	global $debug_messages;

	$debug_messages[] = Array(
		'msg' => $msg,
		'var' => $var
	);
}

//Compare two sets of versions, where major/minor/etc. releases are separated by dots.
//Returns 0 if both are equal, 1 if A > B, and -1 if B < A.
function version_compare2($a, $b) {
	$a = explode(".", rtrim($a, ".0")); //Split version into pieces and remove trailing .0
	$b = explode(".", rtrim($b, ".0")); //Split version into pieces and remove trailing .0
	foreach ( $a as $depth => $aVal )
	{ //Iterate over each piece of A
		if ( isset($b[$depth]) ) { //If B matches A to this depth, compare the values
			if ( $aVal > $b[$depth] ) return 1; //Return A > B
			else if ( $aVal < $b[$depth] ) return -1; //Return B > A
			//An equal result is inconclusive at this point
		}
		else
		{ //If B does not match A to this depth, then A comes after B in sort order
			return 1; //so return A > B
		}
	}
	//At this point, we know that to the depth that A and B extend to, they are equivalent.
	//Either the loop ended because A is shorter than B, or both are equal.
	return (count($a) < count($b)) ? -1 : 0;
}

function api_info() {
	global $menu, $curl_config;
	//GET INFO FROM WEBSERVICE
	$fn = 'API URL HERE ?api_key=' . APIKEY . '&api_secret=' . APISECRET . '&format='json';

	$ch = curl_init();
	$curl_config[CURLOPT_URL] = $fn;
	curl_setopt_array($ch, $curl_config);
	$data = json_decode(curl_exec($ch), true);
	$info = curl_getinfo($ch);
	$curl_error = curl_errno($ch) === 0 ? 'No errors' : 'Curl Error Reading Stats from API: ' . curl_errno($ch) . ' > ' . curl_error($ch);
	curl_close($ch);

	if ( $curl_error !== 'No errors' || $data === false || empty($data) ) {
		//curl returned error
		debugLog('Json Failed. Response: ', array('response data' => $data, 'curl info' => $info));
		writeLog('[ERROR] Failed To Access Webservice: ' . $curl_error);
		showMessage(false, $curl_error);
		return Array();
	} else {
		//ok data returned
		debugLog('API stats.php Response', array('response data' => $data, 'curl info' => $info));
		if ( $data['result'] === false || isset($data['error']) ) {
			$msg = implode("\n", $data['error']);
			showMessage(false, "Unable to access the noIPfraud webservice due to the following errors:\n$msg");
			writeLog("[ERROR] Unable to access the noIPfraud webservice due to the following errors:\n$msg");
		}

		//check version
		if ( version_compare($data['version'], CLIENT_VERSION) == 1 ) {
			showMessage(false, 'You are running an outdated Client (version ' . CLIENT_VERSION . '). Please <a href="' . $menu['support']['url'] . '">download & install</a> the latest version (' . $data[version] . ').');
		}

		//account active
		if ( isset($data['account']['active']) && $data['account']['active'] == 'No' ) {
			showMessage(false, 'Your account has been disabled! <a href="' . $menu['support']['url'] . '">Please contact support</a> to reactivate your account.');
		}

		//sufficient click balance
		if ( isset($data['account']['credits']) ) {
			if ( $data['account']['credits'] <= 0 ) {
				showMessage(false, 'You have run out of credits! <a href="' . $menu['support']['url'] . '">Please topup</a>.');
			} elseif ( $data['account']['credits'] <= $data['account']['lastday'] * 5 ) {
				$daysleft = round($data['account']['credits'] / $data['account']['lastday'], 1);
				showMessage(false, 'You are running low on click balance! At your current volume you will run out within ' . $daysleft . ' days. <a href="' . $menu['support']['url'] . '">Please topup</a>.');
			}
		}
		return $data;
	}
}

function check_webservice() {
	global $curl_config;
	//GET INFO FROM WEBSERVICE
	$fn = 'API URL HERE' . APIKEY . '&api_secret=' . APISECRET . '&a=check'. '&format='json';

	$ch = curl_init();
	$curl_config[CURLOPT_URL] = $fn;
	curl_setopt_array($ch, $curl_config);
	$data = json_decode(curl_exec($ch), true);
	$info = curl_getinfo($ch);
	$curl_error = curl_errno($ch) === 0 ? 'No errors' : 'Could not access webservice. Curl Error Checking Webserice Availability: ' . curl_errno($ch) . ' > ' . curl_error($ch);
	curl_close($ch);

	if ( $curl_error != 'No errors' || $data === false || empty($data) || $data['result'] === false ) {
		//curl returned error
		debugLog('Json Failed. Response: ', array('response data' => $data, 'curl info' => $info));
		showMessage(false, 'Webservice Check Failed. Please check your logs for more info.');
		writeLog('[ERROR] Webservice validation call:' . $fn);
		return false;
	} else {
		debugLog('API Check Response: ', $data);
		showMessage(true, "WebService Operational. Current Server Time: " . $data['timestamp']);
		return true;
	}
}

function writeLog($msg) {
	$fn = LOC_LOG . date('Ymd') . '.log';
	$fh = @fopen($fn, 'a');
	if ( $fh ) {
		fwrite($fh, date('Ymd H:i:s') . ' > ' . $msg . "\n");
		fclose($fh);
	}
}

function getIP() {
	if ( isset($_SERVER['HTTP_CLIENT_IP']) ) {
		//check ip from share internet
		$ip = $_SERVER['HTTP_CLIENT_IP'];
	} elseif ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) ) {
		//to check ip is pass from proxy
		$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
	} else {
		$ip = $_SERVER['REMOTE_ADDR'];
	}

	$ip_array = explode(',', $ip);
	return $ip_array[0];
}

function timedRedir($url, $sec) {
	define('TIMEDREDIR_URL', $url);
	define('TIMEDREDIR_S', $sec);
}

function randomAlphaNum($length) {

	$rangeMin = pow(36, $length - 1); //smallest number to give length digits in base 36
	$rangeMax = pow(36, $length) - 1; //largest number to give length digits in base 36
	$base10Rand = mt_rand($rangeMin, $rangeMax); //get the random number
	$newRand = base_convert($base10Rand, 10, 36); //convert it

	return $newRand; //spit it out

}

function getCampaign($clid) {
	$fn = getCmpName($clid);
	if ( file_exists($fn) ) {
		include $fn;
		return $camp;
	} else {
		return false;
	}
}

function getCmpName($clid) {
	return LOC_CAMP . $clid . '.cmp.php';
}

function getVars($url, $parse = true) {
	$u = parse_url($url);
	$vars = array();
	if ( isset($u['query']) ) {
		$query = explode('&', $u['query']);
		foreach ( $query as $q ) {
			if ( preg_match('!\[{2}.*\]{2}!', $q) === 1 ) {
				$data = explode('=', $q);
				if ( $parse ) {
					$data[1] = preg_replace('!\[|\]!', '', $data[1]);
					$vars[$data[1]] = strtoupper($data[0]);
				} else {
					$vars[$data[0]] = $data[1];
				}
			}
		}
	}

	if ( preg_match_all('!\[\[(.*?)\]\]!', $u['path'], $m) !== false ) {
		foreach ( $m[1] as $data ) {
			if ( $patch ) {
				$vars[$data] = strtoupper($data);
			} else {
				$vars[$data] = "[[$data]]";
			}

		}
	}

	return $vars;
}
?>
