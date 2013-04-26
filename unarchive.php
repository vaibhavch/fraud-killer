/***
 * author @ 000vaibhav000@gmail.com
 ***/
 
<?
define('INMT', true);
define('CURRENT_PAGE', 'home');

include 'lib.php';

if ( isset($_POST['submit']) ) {
  $keys = array_keys($_POST['col']);
	if ( count($keys) > 0 ) {
		foreach($keys as $clid) {
			$fn = getCmpName($clid);
			if ( rename($fn . '.archived',$fn) ) {
				showMessage(true, 'Campaign [' . $clid . '] was recovered successfully.');
			} else {
				showMessage(false, 'Failed to unarchive campaign [' . $clid . '].');
			}
		}
	}
} else {
}

//get list of archived campaigns
$c = array();
$files = scandir(LOC_CAMP);
if ( $files !== false ) {
	foreach ( $files as $file ) {
		$pos = stripos($file, '.cmp.php.archived');
		if ( $pos !== false ) {
			$camp=array();
			include(LOC_CAMP . $file);
			$c[substr($file,0,$pos)] = $camp;
		}
	}
}


showHead();

?>
<h2>Unarchive Campaigns</h2>
<form method="post">
<?
if ( count($c) > 0 ) {
	foreach($c as $clid => $camp) {
		?>
	<input type="checkbox" name="col[<?= $clid ?>]">&nbsp;<?= $camp['info'] ?> (clid: <?= $clid ?>)<br/>
		<?
	}
	?>
	<br/>
	<input type="submit" name="submit" value="Unarchive">
	<br/>
	<?
} else {
	?>
	<p><b>No archived campaigns found</b></p>
	<?
}
?>
</form>
<?
showTail();

?>
