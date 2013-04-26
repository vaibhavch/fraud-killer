/***
 * author @ 000vaibhav000@gmail.com
 ***/

<?
define('INMT', true);
define('CURRENT_PAGE', 'stats');

include 'lib.php';

//$data = api_info();

showHead();
$qry = http_build_query(array(
  'apikey' => APIKEY,
	'secret' => APISECRET
));
//PRINT CAMPS
?>

<iframe width="100%" height="2000" frameborder=0 scrolling=no
        id="frame1"></iframe>
<form method="post" target="frame1"
      action="SUPPORT URL HERE" id="supportform">
	<input type="hidden" name="apikey" value="<?= APIKEY ?>">
	<input type="hidden" name="secret" value="<?= APISECRET ?>">
	<input type="hidden" name="version" value="<?= CLIENT_VERSION ?>">
	<? if ( DEBUG ): ?>
	<input type="hidden" name="debug" value="true">
	<? endif ?>
	<? if ( isset($_GET['clid']) && !empty($_GET['clid']) ): ?>
	<input type="hidden" name="clid" value="<?= $_GET['clid'] ?>">
	<? endif ?>
</form>

<?

function addScript() {
	?>
<script>
	$().ready(function () {
		$('#supportform').submit();
	});
</script>
<?
}

showTail('addScript');

?>
