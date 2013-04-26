/***
 * author @ 000vaibhav000@gmail.com
 ***/
 
<?
define('INMT', true);
define('CURRENT_PAGE', 'config');

include 'lib.php';

if ( isset($_GET['debug']) ) {
  if ( $_GET['debug'] == 'on' ) {
		//switch debug on
		setcookie('debug', 'on', time() + 60 * 60 * 24 * 30, ADMIN_PATH);
		define('DEBUG', true);
	} else {
		//switch debug off
		setcookie('debug', 'off', time() + 60 * 60 * 24 * 30, ADMIN_PATH);
		define('DEBUG', false);
	}
}

if ( isset($_GET['adv']) ) {
	if ( $_GET['adv'] == 'on' ) {
		//switch debug on
		setcookie('adv', 'on', time() + 60 * 60 * 24 * 30, ADMIN_PATH);
		define('ADV_EDIT', true);
	} else {
		//switch debug off
		setcookie('adv', 'off', time() + 60 * 60 * 24 * 30, ADMIN_PATH);
		define('ADV_EDIT', false);
	}
}

if ( isset($_POST['submit']) ) {
	$keys = implode('|',array_keys($_POST['col']));
	setcookie('columns',$keys,time() + 60 * 60 * 24 * 30, ADMIN_PATH);
} else {
	$keys = isset($_COOKIE['columns']) ? $_COOKIE['columns'] : 'tit|ts|ref|tot|br';
}
foreach($showCol as $key => $col) {
	$showCol[$key]['show']=false;
}
foreach(explode('|',$keys) as $key) {
	$showCol[$key]['show'] = true;
}

showHead();

?>
<p>Note: your settings are saved in cookies. Clear your cookies to return to the default.</p>
<h2>Dafault Campaign Options</h2>
<p>Use the link below to setup or change your default campaign layout.</p>
<? if ( file_exists(getCmpName('default')) ): ?>
<a href="index.php?action=edit&clid=default" title="Edit Campaign Defaults">Edit
	Campaign Defaults</a> | <a href="index.php?action=del&clid=default"
                               title="Delete Campaign Defaults">Delete
	Defaults</a>
<? else: ?>
<a href="index.php?action=create&clid=default" title="Create Campaign Defaults">Create
	Campaign Defaults</a>
<? endif ?>

<h2>Campaign Overview </h2>
<p>Select which columns you want to display in the Campaign Overview screen.<br/>This preference is saved as a cookie so will reset if your clear your cookies.</p>
<form method="post">
	<? foreach($showCol as $key => $col): ?>
	<input type="checkbox" name="col[<?= $key ?>]"<?= $col['show'] ? ' checked="checked"' : '' ?>>&nbsp;<?= $col['head'] ?><br/>
	<? endforeach ?>
	<input type="submit" name="submit" value="Save Preferences">
</form>
<h2>Advanced campaign editor</h2>
<p>Switching this on will enable the following features</p>
<ul>
	<li>File inclusion in addition to redirects</li>
	<li>URL Query String Filtering</li>
</ul>
<? if ( ADV_EDIT ): ?>
<p><a href="?adv=off" title="Switch Advanced Editor Off">Use Simple Editor</a></p>
<? else: ?>
<p><a href="?adv=on" title="Switch Advanced Editor On">Use Advanced Editor</a></p>
<? endif?>

<h2>Debugging</h2>
<? if ( DEBUG ): ?>
<p><a href="?debug=off" title="Switch debugging off">Switch it Off</a></p>
<? else: ?>
<p><a href="?debug=on" title="Switch debugging on">Switch it On</a></p>
<?
endif;

showTail();

?>
