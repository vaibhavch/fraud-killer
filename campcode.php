<?
//Make sure the user is logged in
session_start();
if ( !isset($_SESSION['login_session']) ) {
  Die('Direct access not allowed. Your IP has been logged!');
}

//load config
include('../config.php');

//define the APPLOC
$str = $_SERVER['DOCUMENT_ROOT'].$_SERVER['PHP_SELF'];
$str = preg_replace('!(^.*/)(.+?/.+?.php)$!i','$1',$str);
define('APPLOC',$str);

//set variables
if ( !isset($_GET['clid']) ) {
	Die('Campaign ID not provided');
}

$clid = $_GET['clid'];

//get campaign file
$fn = LOC_CAMP.$clid.'.cmp.php';
if ( !file_exists($fn) ) {
	Die("Campaign File [$fn] does not exist!");
}

include($fn);

//define text
$code = <<<EOD
<?php
/***
 * author @ 000vaibhav000@gmail.com
 ***/

//define critical variables - dont change!
define('APPLOC','{APPLOC}');
\$_GET['clid'] = '{CLID}';

//load the fraudkiller logic
include('go.php');

//show the user the right landing page
fraudkiller();
?>
EOD;

$adv_code = <<<EOD
<?php
//ADVANCED FRAUDKILLER TEMPLATE
//ONLY USE WHEN YOU KNOW WHAT YOUR DOING

//define critical variables - dont change!
define('APPLOC','{APPLOC}');
\$_GET['clid'] = '{CLID}';

//load the fraudkiller logic
include('go.php');

//decide what to do
if ( \$isItSafe ) {
	//ok - alls safe - do your stuff
	noIpFraud();
}

//include your safe landing page below this comment
?>
EOD;

$code = str_ireplace('{APPLOC}', APPLOC, $code);
$code = str_ireplace('{CLID}', $clid, $code);

$adv_code = str_ireplace('{APPLOC}', APPLOC, $adv_code);
$adv_code = str_ireplace('{CLID}', $clid, $adv_code);

//decide whether to download or show
if ( isset($_GET['dwl']) ) {
	header('Content-Description: File Transfer');
	header('Content-Type: application/octet-stream');
	header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	header('Content-Disposition: attachment; filename="index.php";');
	header('Content-Length: '.strlen($code));
	echo $code;
	die();
}

if ( isset($_GET['txt']) ) {
	header('Content-Type:text/plain');
	//header('Content-Description: File Transfer');
	//header('Content-Type: application/octet-stream');
	//header('Content-Transfer-Encoding: binary');
	header('Expires: 0');
	header('Cache-Control: must-revalidate');
	header('Pragma: public');
	//header('Content-Disposition: attachment; filename="code.txt";');
	//header('Content-Length: '.strlen($code));
	echo isset($_GET['adv']) ? $adv_code : $code;
	die();
}
//$code = str_ireplace('<','&lt;', $code);
//$adv_code = str_ireplace('<','&lt;', $adv_code);
?>
<style>
.var {
	display: inline-block;
	padding: 3px 5px;
	margin: 3px 10px;
	border: 1px solid #95C0FF;
	background: #DEF3FF;
}
.var:first-child {
	margin-left:0px;
}
.center {
	text-align: center;
}
#linkform h3 {
	font-size: 14px;
	margin-bottom: 0px;
	border: 1px solid #ddd;
	padding: 5px 10px;
	margin: 20px 0px;
	background: #eee;
}
.example {
	display:block;
	clear:both;
	margin-top:11px;
	color: darkGray;
	text-align:left;
}
#linkform #setupchoices h3 {
	margin: 0px 0px 5px;
}
.syntaxhighlighter .bar {
	display: block !important;
}
</style>
<p>We have removed the option to use go.php URL for your new campaigns. Links were getting indexed by google and provided a footprint for
	the traffic networks to see you were using a cloaker. <span style="color:green">Your existing campaigns <strong>WILL</strong> work without updating the URL!</span></p>


<div id="setupchoices">
	<ul>
		<h3 class="ui-widget-header ui-corner-all">STEP 1: Setup your campaign file</h3>
		<li><a href="#download">Download</a></li>
		<li><a href="#copypaste">Copy & Paste</a></li>
		<li><a href="#customembed">Custom Embed</a></li>
	</ul>
	<div id="download">
		<p><b>Download by clicking the button below</b>. Then FTP the downloaded index.php file into your
		webserver in a dedicated campaign folder, ie /diets/uk/. This campaign folder can be anywhere
		on your server as long as its on the <strong>same domain</strong> where you installed fraudkiller.</p>
		<p style="text-align:center"><a href="<?= $_SERVER['REQUEST_URI'] ?>&dwl" class="button">Download your Campaign File</a></p>
	</div>
	<div id="copypaste">
		<p>Click in the textbox below and press ctrl/cmd+C to copy. Then Save it in a php file in any location for your
			current domain that noipfraud client is hosted on.</p>
		<textarea onclick="this.select();"><?= $code ?></textarea>
	</div>
	<div id="customembed">
		<p><b>For advanced users only!</b> If you want to embed fraudkiller within an existing landing page, you will need to insert the following code at the top.
		This will only work if your landing pages are PHP.</p>
		<p>This code will only execute the fraudkiller redirect/include to the primary page when its safe to do so ($isItSafe == true).</p>
		<p><b>Click in the textbox below</b> then press CTRL/CMD + C to copy.</p>
		<textarea onclick="this.select();"><?= $adv_code ?></textarea>
	</div>
</div>

<h3 class="ui-widget-header ui-corner-all">STEP 2: Include your dynamic Variables</h3>

<? if ( !empty($camp['dynvar']) ): ?>
<p>You have defined a number of dynamic variables. Just copy and paste the URL query string below and append it to your URL:</p>
<p class="center">
	<?
	foreach($camp['dynvar'] as $key => $val) {
		$qry[] = $key. ( !empty($val) ? '='.$val:'' ); //urlencode removed - bug logged on 21/1/2012
	}
	$qrystr = '?'.implode('&',$qry);
	?>
	<input value="<?= $qrystr ?>" readonly onclick="this.select();"/>
	<span class="example"><b>For example:</b><br/>http://yourdomain.com/path/page.php<b></b><?= $qrystr ?></b></span>
</p>
<? else: ?>
<p>You have not defined any URL variables</p>
<? endif ?>

<h3 class="ui-widget-header ui-corner-all">STEP 3: Check Your URL for Query String Keywords</h3>

<? if ( !empty($camp['urlkeyword']) ): ?>
<p>Make sure any of the following keywords are present in your URL query string:</p>
<p class="center">
	<?
	foreach(explode('|',$camp['urlkeyword']) as $val) {
		?>
		<span class="var"><?= $val ?></span>
		<?
	}
?>
</p>
<span class="example"><b>The query string is the area in bold in the URL below:</b><br/>http://yourdomain.com/path/page.php<b>?var1=test&var2=test</b></span>
<? else: ?>
<p>You have No URL Query String Filters Set.</p>
<? endif ?>
<hr size=1 />
<p class="small">If you really want to check your legacy URL for this campaign <a href="#" onclick="alert('We seriously recommend you DO NOT use the legacy campaign URLs for new campaigns.\n\nThey are being indexed by google and flagged by Facebook.\n\nLegacy URL:\n\n'+'<?= $_GET['legurl'] ?>'); return false;">click here</a></p>
</div>
