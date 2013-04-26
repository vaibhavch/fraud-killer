/***
 * author @ 000vaibhav000@gmail.com
 ***/
 
<? if ( DEBUG ): ?>
<p></p>
<p><a href="#" id="debug_show"
      onclick="$('#debug_show').hide(); $('#debug').show(); return false;">Show
  Debug Info</a></p>
<div id="debug" style="display:none">
	<?
	global $debug_messages;

	foreach ( $debug_messages as $d ) {
		echo "<h2>{$d['msg']}</h2>\n";
		echo "<pre>\n";
		print_r($d['var']);
		echo "\n</pre>\n";
	}
	?>
</div>
<? endif ?>
</div>
</div> <!--! end of #container -->
<footer>
	&copy 2011 <a href="FRAUD KILLER OFFICIAL WEBSITE(yet to launch)" target="_blank">fruadkiller</a>
</footer>

<script src="//ajax.googleapis.com/ajax/libs/jquery/1.7.1/jquery.min.js"></script>
<script>window.jQuery || document.write('<script src="js/libs/jquery-1.7.1.min.js"><\/script>')</script>
<script src="js/datatables/media/js/jquery.dataTables.min.js"></script>
<!-- scripts concatenated and minified via ant build script-->
<script src="js/plugins.js?v=<?= CLIENT_VERSION ?>"></script>
<script src="js/libs/jquery.tools.min.js"></script>
<script src="js/libs/jquery-ui-1.8.18.custom.min.js"></script>

<script src="js/syntaxhighlighter/scripts/shCore.js?v=<?= CLIENT_VERSION ?>"></script>
<script src="js/syntaxhighlighter/scripts/shBrushPhp.js?v=<?= CLIENT_VERSION ?>"></script>
<script src="js/jqueryFileTree/jqueryFileTree.js?v=<?= CLIENT_VERSION ?>"></script>

<script>var docRoot = '<?= $_SERVER['DOCUMENT_ROOT'] ?>';</script>

<script src="js/plugins.js?v=<?= CLIENT_VERSION ?>"></script>
<script src="js/script.js?v=<?= CLIENT_VERSION ?>"></script>

<!-- end scripts-->

<!-- runtime scripts -->
<?
if ( $callback !== null & function_exists($callback) ) {
	call_user_func($callback);
}
?>
<!-- end runtime scripts -->
</body>
</html>
