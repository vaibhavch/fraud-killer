/***
 * author @ 000vaibhav000@gmail.com
 ***/

<?
define('INMT', true);
define('CURRENT_PAGE', 'login');

include 'lib.php';

if ( isset($_REQUEST['logout']) ) {
  unset($_SESSION['login_session']);
	unset($_SESSION['failed_login']);
	$loginMsg = 'You have been logged out safely.';
	$loginError = false;
}

//check whether the user has submitted a login form
if ( isset($_REQUEST['submit']) && $_REQUEST['action'] == 'login' ) {
	//user submitted login
	if ( $_REQUEST['ui'] == $userId && $_REQUEST['pw'] == $passWord ) {
		//user authorised
		$_SESSION['login_session'] = true;
		timedRedir('index.php?validate' . (isset($_POST['novalidate']) ? '&novalidate' : ''), 3);
		showHead();
		?>
	<h1>You have been Logged in</h1>
	<h2>Please wait for a couple of second while we validate your setup...</h2>
	<?
		showTail();
		Die();
	} else {
		$loginError = true;
		$_SESSION['failed_login']++;
		$loginMsg = "Incorrect username or password. Please try again. You have had " . $_SESSION['failed_login'] . ' failed attempts!';
		writeLog('[LOGIN ERROR] Incorrect login from IP ' . getIP() . ' id:' . $_REQUEST['ui'] . ' passwd:' . $_REQUEST['pw']);
	}
}

if ( $_SESSION['failed_login'] >= 3 ) {
	showMessage(false, 'Too many failed login attempts. App is locked!');
	showHead();
	showTail();
	Die();
}

if ( isset($_REQUEST['logout']) && !$loginError ) {
	showMessage(true, 'You have been logged out.');
}

if ( $loginError ) {
	showMessage(false, $loginMsg);
}

showHead();
?>
<div id="loginform">
	<h1>Please login</h1>

	<p>Failed attempts will be logged. After 3 failed attempts this application
		will be locked!</p>

	<form method="post" action="login.php">
		<label>Username:</label>
		<input type="text" name="ui"><br/><br/>
		<label>Password:</label>
		<input type="password" name="pw"><br/><br/>
		<input type="hidden" name="action" value="login">
		<input type="submit" name="submit">
		<? if ( isset($_GET['novalidate']) ): ?>
		<input type="hidden" name="novalidate" value="1">
		<? endif ?>
	</form>
</div>
<?
showTail();
?>
