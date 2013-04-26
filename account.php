/***
 * author @ 000vaibhav000@gmail.com
 ***/
 
<?
define('INMT', true);
define('CURRENT_PAGE', 'account');

include 'lib.php';

$data = api_info();

showHead();

//PRINT CAMPS
?>
<h2>Account Status</h2>
<table width=100%>
  <thead>
	<tr>
		<th rowspan=2 width=150 class="left">Name</th>
		<th rowspan=2 width=150>Email</th>
		<th rowspan=2 width=50>Active</th>
		<th colspan=4>Clicks</th>
		<th rowspan=2 width=100>Credits Remaining</th>
	</tr>
	<tr>
		<th width=50>This Month</th>
		<th width=50>Last Month</th>
		<th width=50>YTD</th>
		<th width=50>Total</th>
	</tr>
	</thead>
	<tbody>
	<tr>
		<td class="left"><?= $data['account']['name'] ?></td>
		<td class="center"><?= $data['account']['email'] ?></td>
		<td class="center"><?= $data['account']['active'] ?></td>
		<td class="center"></td>
		<td class="center"></td>
		<td class="center"></td>
		<td class="center"><?= $data['data']['total'] ?></td>
		<td class="center"><?= 'BETA'; //$data['account']['credits']; ?></td>
	</tr>
	</tbody>
</table>
<br/><br/>

<?

showTail();

?>
