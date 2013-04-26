/***
 * author @ 000vaibhav000@gmail.com
 ***/
 
<?php
//start timer
$timeStart = microtime(true);

//config settings
$debug_msg = Array();

//check debug
if ( isset($_GET['debug']) ) {
  define('DEBUG', true);
	error_reporting( ~E_ALL ^ ~E_NOTICE );
	ini_set("display_errors", 1);
} else {
	define('DEBUG', false);
}

//includes
include('config.php');
include('constants.php');

//check cloaker camp id
$_GET['clid'] = isset($_GET['clid']) ? $_GET['clid'] : '';

//use apc if supported
$camp = array();
if (function_exists('apc_exists') && function_exists('apc_store')) {
	$debug_msg['apc'][] = 'apc enabled';
	if ( apc_exists('noipfraud-'.$_GET['clid']) ) {
		$camp = apc_fetch('noipfraud-'.$_GET['clid'], $result);
		if ( !$result ) {
			$debug_msg['apc'][] = 'Failed to retrieve stored clid: '.$_GET['clid'];
			$camp = array();
		} else {
			$debug_msg['apc'][] = 'Read from store. clid: '.$_GET['clid'];
		}
	}

	if ( empty($camp) ) {
		$debug_msg['apc'][] = 'Clid '.$_GET['clid'].' not available. Loading from file.';
		require($fn);
		if ( !apc_store('noipfraud-'.$_GET['clid'], $camp, APC_EXPIRY) ) {
			$debug_msg['apc'][] = 'Failed to store clid '.$_GET['clid'];
		} else {
			$debug_msg['apc'][] = 'Stored clid '.$_GET['clid'];
		}
	}
} else {
	$debug_msg['apc'][] = 'Apc unavailable. Reading from file clid '.$_GET['clid'];
	require($fn);
}

//get ip
if (isset($_SERVER['HTTP_CLIENT_IP'])) {
	//check ip from share internet
	$realIP=$_SERVER['HTTP_CLIENT_IP'];
	$fakeIP=$_SERVER['REMOTE_ADDR'];
	$ipType = IP_SHARE;
} elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
	//to check ip is pass from proxy
	$realIP=$_SERVER['HTTP_X_FORWARDED_FOR'];
	$fakeIP=$_SERVER['REMOTE_ADDR'];
	$ipType = IP_PROXY;
} else {
	$realIP=$_SERVER['REMOTE_ADDR'];
	$fakeIP=$_SERVER['REMOTE_ADDR'];
	$ipType = IP_REAL;
}

//get browser/platform data
$ua = getBrowser($_SERVER['HTTP_USER_AGENT']);

//check for debug request
$debug = isset($_GET['debug']) ? '&debug' : '';
$test = isset($_GET['dummy']) ? '&dummy' : '';

//check if this is a noipfraud user, and pass as variable
$vid = isset($_COOKIE['vid']) ? (int) $_COOKIE['vid'] : 0;

//check if user tracking cookie has been set
$cookiedur = time()+60*60*24*365;
$cookiedomain = '.'.$_SERVER['HTTP_HOST'];
$utrck = isset($_COOKIE['__utnz']) ? $_COOKIE['__utnz'] : NULL;
if ( $utrck == NULL ) {
	//set cookie
	$utrck = md5('just@r@nd0ms@lt'.date('U').mt_rand());
	setcookie('__utnz',$utrck,$cookiedur,'/',$cookiedomain);
}

//check if fingerprint has been set
$fngr = isset($_COOKIE['__utny']) ? $_COOKIE['__utny'] : NULL;
if ( $fngr == NULL ) {
	$fngr = md5($camp['traffic'].$_SERVER['HTTP_USER_AGENT'].$_SERVER['HTTP_ACCEPT'].$_SERVER['HTTP_ACCEPT_ENCODING'].$_SERVER['HTTP_ACCEPT_LANGUAGE'].$_SERVER['HTTP_ACCEPT_CHARSET']);
	setcookie('__utny',$fngr,$cookiedur,'/',$cookiedomain);
}

//process local filters
$isItSafe=true;
$querystr = http_build_query($_GET);

if ( $camp['active'] == 0 ) {
	//inactive, always go to safe URL
	$isItSafe = false;
	$content = "not active";
} elseif  ( $camp['maxrisk'] == 0 ) {
	//inactive, always go to safe URL
	$isItSafe = false;
	$content = "maxrisk 0";
} elseif ( $camp['maxrisk'] == 10 ) {
	//always go to real URL
	$isItSafe = true;
	$content = "maxrisk 10";
} elseif ( $campArchived ) {
	//camp archived always to fake
	$isItSafe = false;
	$content = "camp archived";
} elseif ( !empty($camp['urlkeyword']) && !preg_match('!'.$camp['urlkeyword'].'!i', $querystr) ) {
	//no matches - fake page
	$isItSafe = false;
	$content = "url keyword not present in query string: $querystr";
}

//$hoststr = gethostbyaddr($realIP);
$url = 'API URL HERE'.http_build_query(Array(
	'clid'=>$_GET['clid'],
	'api_key'=>APIKEY,
	'api_secret'=>APISECRET,
	'format'=>'json',
	'ts'=>$camp['traffic'],
	'ref'=>strtolower($_SERVER['HTTP_REFERER']),
	'ua'=>strtolower($_SERVER['HTTP_USER_AGENT']),
	'browser'=>$ua['name'],
	'platform'=>$ua['platform'],
	'fip'=>$fakeIP,
	'rip'=>$realIP,
	'ipt'=>$ipType,
	'cok'=>$camp['allowedcountries'],
	'okref'=>$camp['allowedref'],
	'vid'=>$vid,
	'status'=>$camp['active'],
	'trk'=>$utrck,
	'fgr'=>$fngr,
	'dev'=>$camp['device'],
)).$debug.$test;

//get result
$ch = curl_init();
$curl_config[CURLOPT_URL] = $url;
curl_setopt_array($ch, $curl_config);
$info = curl_getinfo($ch);
$json = json_decode(curl_exec($ch));
if ( $json == null ) {
	//error with data
	$result = false;
	$geodata = null;
} else {
	$result = (int) $json->result;
	$geodata = get_object_vars($json->data);
}
$curl_error = curl_errno($ch) === 0 ? 'No errors' : 'Curl Error: '.curl_errno($ch).' > '.curl_error($ch);

if (  $curl_error != 'No errors' || $result === false || ( empty($result) && $result !== 0 ) ) {
	//curl returned error - always go to safe page
	$isItSafe = false;
	writeLog('[FATAL ERROR] Curl Failed In go.php: '.$_SERVER['REQUEST_URI'].' - '.$curl_error);
} else {
	//ok data returned
	$isItSafe = $result > 0 ? $isItSafe : false;
}
curl_close($ch);

//set goto
$goto = $isItSafe ? $camp['realurl'][chooseUrl($camp['realurl'])]['url']: $camp['fakeurl'];

//add original URL parameters
if (preg_match_all('!\[{2}(.*?)\]{2}!', $goto, $matches) > 0) {
	foreach($matches[1] as $v) {
		if ( stripos(DEF_DYN_VARS,$v) !== false ) {
			$goto = str_ireplace("[[$v]]", isset($geodata[$v]) ? urlencode($geodata[$v]) : '', $goto);
		} else {
			$goto = str_ireplace("[[$v]]", isset($_GET[$v]) ? urlencode($_GET[$v]) : '', $goto);
		}
	}
}

//check if included
if ( __FILE__ == $_SERVER['SCRIPT_FILENAME'] ) {
	//go.php is called direct so process as well
	noIpFraud();
}

function fraudkiller() {
	global $goto, $timeStart, $debug_msg, $camp, $vid, $ua,$fakeIP, $realIP, $url, $json, $content, $info, $curl_error,$param, $result,$isItSafe, $campArchived;

	$doRedir = ( stripos($goto,'http://') === 0 || stripos($goto,'https://') === 0 );
	$dur = microtime(true) - $timeStart;

	if ( DEBUG ) {
		$debug_msg['vars']['API_DOMAIN'] = API_DOMAIN;
		$debug_msg['vars']['API_PATH'] = API_PATH;
		$debug_msg['vars']['clid'] = $_GET['clid'];
		$debug_msg['vars']['camp'] = $camp;
		$debug_msg['vars']['visitorid'] = $vid;

		$debug_msg['vars']['referrer'] = trim(strtolower($_SERVER['HTTP_REFERER']));
		$debug_msg['vars']['useragent'] = trim(strtolower($_SERVER['HTTP_USER_AGENT']));
		$debug_msg['vars']['browser'] = $ua['name'];
		$debug_msg['vars']['platform'] = $ua['platform'];
		$debug_msg['vars']['fakeip'] = $fakeIP;
		$debug_msg['vars']['realip'] = $realIP;

		$debug_msg['curl']['url'] = $url;
		$debug_msg['curl']['json'] = $json;
		$debug_msg['curl']['content'] = $content;
		$debug_msg['curl']['info'] = $info;
		$debug_msg['curl']['error'] = $curl_error;

		$debug_msg['vars']['param'] = $param;
		$debug_msg['vars']['goto'] = $goto;
		$debug_msg['vars']['redir'] = $doRedir ? 'Redir' : 'Include';
		$debug_msg['time']['total'] = $dur;
		$debug_msg['time']['webservice'] = $info['total_time'];
		$debug_msg['server'] = $_SERVER;
		$debug_msg['result'] = $result;

		if ( function_exists('apc_cache_info') ) {
			$apcinfo = apc_cache_info();
			$debug_msg['apc']['status'] = array(
				'slots'=>$apcinfo['num_slots'],
				'hits'=>$apcinfo['num_hits'],
				'misses'=>$apcinfo['num_misses'],
				'inserts'=>$apcinfo['num_inserts'],
				'slots'=>$apcinfo['num_slots'],
				'starttime'=>date('r',$apcinfo['start_time']),
				'memsize'=>$apcinfo['mem_size'],
				'entries cached'=>$apcinfo['num_entries']
			);
		}

		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		?>
	<h1>Debugging</h1>
	<h2>Result (1 is Real Page, 0 is Fake Page)</h2>
	<p><?= $result.' / '.( $isItSafe ? 'safe' : 'not safe' ) ?></p>
	<p><?= $doRedir ? 'Redir' : 'Include' ?>: <?= $goto ?></p>
	<?
		if ( !empty($url) && $result == 0 ) {
			echo "<h2>Reason:</h2><p>{$json->debug->trapped_data}</p>";
		}
	?>
	<a href="<?= $debug_msg['curl']['url'] ?>">Check server response</a>
	<h2>Debug data</h2>
	<pre><? var_dump($debug_msg) ?></pre>
	<?
	} elseif ( isset($_GET['test']) ) {
		$stats = array();
		header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
		header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
		for($i=1;$i<=10000;$i++) {
			$stats[chooseUrl($camp['realurl'])]++;
		}
		?>
	<html>
	<head>
		<title>Testing Fraud Killer campaign url</title>
	</head>
	<body>
	<h1>Testing Campaign: <?= $camp['info'] ?></h1>
	<p>Clid: <?= $_GET['clid'] ?></p>
	<p><b>Primary page (10,000 rotation tests):</b><br/>
		<?
		$sum = 0;
		foreach($camp['realurl'] as $i => $u) {
			?>
			<?= $u['url'] ?> weight: <?= $u['perc'] ?> choosen: <?= $stats[$i] ?> = <?= round($stats[$i]/100) ?>%</br>
			<?
			$sum = $sum + $stats[$i];
		}
		?>
		Total matched: <?= $sum ?>
	</p>

	<p><b>Alternative page:</b> <?= $camp['fakeurl'] ?></p>
	<h2>Result</h2>
		<? if ( $campArchived ): ?>
	<p><b>Campaign is Archived.</b></p>
		<? else: ?>
		<? if ( $camp['active']==-1 ): ?>
		<p><b>Campaign is Under Review.</b></p>
			<? elseif ( $camp['active']===0 ): ?>
		<p><b>Campaign is Paused.</b></p>
			<? else: ?>
		<p><b>Campaign is Active</b></p>
			<? endif ?>
		<? endif ?>
	<p><b>Response:</b> <?= $isItSafe ? 'show primary page' : 'show alternative page' ?> </p>
	<p><b><?= $doRedir ? 'Redir' : 'Include'?>:</b> <?= $goto ?></p>

	<h2>Webservice Status</h2>
	<p><b>Errors:</b> <?= $camp['active']==0 ? 'Camp not active. Webservice not called.':$curl_error ?></p>
	<p><b>Load Time:</b> <?= round($info['total_time']*1000,3); ?> milliseconds</p>
	</body>
	</html>
	<?
	} else {
		if ( $doRedir ) {
			if(!headers_sent()) {
				//If headers not sent yet... then do php redirect
				header("Cache-Control: no-cache, must-revalidate"); // HTTP/1.1
				header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
				header("X-Robots-Tag: noindex nofollow");
				header('Location: '.$goto, true, 301);
			} else {
				?>
			<html>
			<head>
				<title>Redirecting...</title>
				<meta name="robots" content="noindex nofollow" />
				<script type="text/javascript">
					window.location.replace="<?= $goto ?>";
				</script>
				<noscript>
					<meta http-equiv="refresh" content="0;url='.$goto.'" />
				</noscript>
			</head>
			<body>
			Your being redirected to <a href="<?= $goto ?>" target="_top">the correct page</a>.
			<script type="text/javascript">
				window.location.replace="<?= $goto ?>";
			</script>
			</body>
			</html>
			<?
			}
		} else {
			//get url vars and put back into get
			$tmp = explode('?',$goto);
			if ( count($tmp) > 1 ) {
				parse_str($tmp[1],$getArr);
				$_GET = array_merge($_GET,$getArr);
			}
			include "$tmp[0]";
		}
	}
	die();
}

function getBrowser($u_agent) {
    $u_agent = empty($u_agent) ? $_SERVER['HTTP_USER_AGENT'] : $u_agent;
    $bname = 'Unknown';
    $platform = 'Unknown';
    $version= "";

    //First get the platform?
    if (preg_match('/linux/i', $u_agent)) {
        $platform = 'linux';
    }
    elseif (preg_match('/macintosh|mac os x/i', $u_agent)) {
        $platform = 'mac';
    }
    elseif (preg_match('/windows|win32/i', $u_agent)) {
        $platform = 'windows';
    }

    // Next get the name of the useragent yes seperately and for good reason
    if(preg_match('/MSIE/i',$u_agent) && !preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Internet Explorer';
        $ub = "MSIE";
    }
    elseif(preg_match('/Firefox/i',$u_agent))
    {
        $bname = 'Mozilla Firefox';
        $ub = "Firefox";
    }
    elseif(preg_match('/Chrome/i',$u_agent))
    {
        $bname = 'Google Chrome';
        $ub = "Chrome";
    }
    elseif(preg_match('/Safari/i',$u_agent))
    {
        $bname = 'Apple Safari';
        $ub = "Safari";
    }
    elseif(preg_match('/Opera/i',$u_agent))
    {
        $bname = 'Opera';
        $ub = "Opera";
    }
    elseif(preg_match('/Netscape/i',$u_agent))
    {
        $bname = 'Netscape';
        $ub = "Netscape";
    }

    // finally get the correct version number
    $known = array('Version', $ub, 'other');
    $pattern = '#(?<browser>' . join('|', $known) .
    ')[/ ]+(?<version>[0-9.|a-zA-Z.]*)#';
    if (!preg_match_all($pattern, $u_agent, $matches)) {
        // we have no matching number just continue
    }

    // see how many we have
    $i = count($matches['browser']);
    if ($i != 1) {
        //we will have two since we are not using 'other' argument yet
        //see if version is before or after the name
        if (strripos($u_agent,"Version") < strripos($u_agent,$ub)){
            $version= $matches['version'][0];
        }
        else {
            $version= $matches['version'][1];
        }
    }
    else {
        $version= $matches['version'][0];
    }

    // check if we have a number
    if ($version==null || $version=="") {$version="?";}

    return array(
        'userAgent' => strtolower($u_agent),
        'name'      => strtolower($bname),
        'version'   => strtolower($version),
        'platform'  => strtolower($platform),
        'pattern'    => strtolower($pattern)
    );
}

function chooseUrl($url) {
    $r = mt_rand(1, 100);
    foreach ($url as $i => $u) {
    	$weight = $u['perc'];
		$item = $u['url'];
        if  ($weight >= $r) {
        	return $i;
		}
        $r -= $weight;
    }
	//echo "r:$r i:$i w:$weight item:$item<br/>";
}

function writeLog($msg) {
	$fn = LOC_LOG . date('Ymd') . '.log';
	$fh = @fopen($fn, 'a');
	if ( $fh ) {
		fwrite($fh, date('Ymd H:i:s') . ' > ' . $msg . "\n");
		fclose($fh);
	}
}
?>
