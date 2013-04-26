/***
 * author @ 000vaibhav000@gmail.com
 ***/
 
<!doctype html>
<!--[if lt IE 7]> <html class="no-js lt-ie9 lt-ie8 lt-ie7" lang="en"> <![endif]-->
<!--[if IE 7]>    <html class="no-js lt-ie9 lt-ie8" lang="en"> <![endif]-->
<!--[if IE 8]>    <html class="no-js lt-ie9" lang="en"> <![endif]-->
<!--[if gt IE 8]><!--> <html class="no-js" lang="en"> <!--<![endif]-->
<head>
  <meta charset="utf-8">
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="robots" content="noindex nofollow"/>
	<title>Fraud Killer</title>
	<meta name="viewport" content="width=device-width">
	<? if ( defined('TIMEDREDIR_URL') ): ?>
	<meta http-equiv="refresh"
	      content="<?= TIMEDREDIR_S ?>;url=<?= TIMEDREDIR_URL ?>">
	<? endif ?>
	<link rel="stylesheet" href="css/smoothness/jquery-ui-1.8.18.custom.css">
	<link rel="stylesheet" href="js/datatables/media/css/demo_table_jui.css">
	<link rel="stylesheet" href="js/jqueryFileTree/jqueryFileTree.css">
	<link rel="stylesheet" href="js/syntaxhighlighter/styles/shCore.css">
	<link rel="stylesheet" href="js/syntaxhighlighter/styles/shThemeDefault.css">
	<link rel="stylesheet" href="css/style.css?v=<?= CLIENT_VERSION ?>">
	<script src="js/libs/modernizr-2.5.3.min.js"></script>
	<script>
		var docRoot = '<?= $_SERVER['DOCUMENT_ROOT'] ?>';
		var advEdit = <?= ADV_EDIT ? 'true' : 'false' ?>;
	</script>
</head>
<body>
<!--[if lt IE 7]><p class=chromeframe>Your browser is <em>ancient!</em> <a href="http://browsehappy.com/">Upgrade to a different browser</a> or <a href="http://www.google.com/chromeframe/?redirect=true">install Google Chrome Frame</a> to experience this site.</p><![endif]-->
<div id="container">
	<header>
		<div id="brand">
			<h2><a href="<?= ADMIN_PATH ?>">Fraud Killer</a></h2>
		</div>
		<span id="version">Client: v.<?= CLIENT_VERSION ?> | Api: v.<?= API_VERSION ?></span>
	</header>
<?
global $menu;

if ( isset($_SESSION['login_session']) && $_SESSION['login_session'] ) {
	echo "<div id=\"mainmenu\"><ul>";
	foreach ( $menu as $t => $m ) {
		echo "<li>\n";
		if ( $t == CURRENT_PAGE ) {
			?>
			<a href="<?= $m['url'] ?>"
			   title="<?= $m['info'] . ' - ' . $t . ' - ' . CURRENT_PAGE ?>"
			   class="menuitem current"><?= $m['title'] ?></a>
			<?
		} else {
			?>
			<a href="<?= $m['url'] ?>"
			   title="<?= $m['info'] . ' - ' . $t . ' - ' . CURRENT_PAGE ?>"
			   class="menuitem"><?= $m['title'] ?></a>
			<?
		}
		echo "</li>";
	}
	?>
	<li>
		<a href="login.php?logout" title="Logout of Fraud Killer">logout</a>
	</li>
	</ul>
</div>
				<?
}
?>
<div id="main" role="main">
