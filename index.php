/***
 * author @ 000vaibhav000@gmail.com
 ***/

<?
define('INMT', true);
define('CURRENT_PAGE', 'home');

include 'lib.php';

if ( isset($_REQUEST['validate']) ) {
  if ( !validateInstall() ) {
		showHead();
		showTail();
		Die();
	}
}

$keys = isset($_COOKIE['columns']) ? $_COOKIE['columns'] : 'tit|ts|cntr|ref|tot|br';
foreach($showCol as $key => $col) {
	$showCol[$key]['show']=false;
}
foreach(explode('|',$keys) as $key) {
	$showCol[$key]['show'] = true;
}
if ( !isset($_REQUEST['action']) && !isset($_REQUEST['submit']) && !isset($_REQUEST['cancel']) ) {
	$campdata = api_info();

	//set user cookie
	$host_names = explode(".", $_SERVER['HTTP_HOST']);
	$domain = '.' . $host_names[count($host_names) - 2] . "." . $host_names[count($host_names) - 1];
	setcookie('vid', $campdata['account']['id'], time() + 2593000, '/', $domain);
	setcookie('un', $campdata['account']['name'], time() + 2593000, '/', $domain);
	setcookie('um', $campdata['account']['email'], time() + 2593000, '/', $domain);
	setcookie('uc', $campdata['account']['credits'], time() + 2593000, '/', $domain);
	setcookie('cv', CLIENT_VERSION, time() + 2593000, '/', $domain);

	if ( !isset($campdata['account']['id']) ) {
		$userid = '#ERR';
	} else {
		$userid = $campdata['account']['id'];
	}

	if ( !isset($campdata['account']['credits']) ) {
		$campdata['account']['credits'] = '#APIERR';
	}
}

//reset APC
if ( function_exists('apc_delete') ) {
	apc_delete('noipfraud-'.$_REQUEST['clid']);
}

//CHECK EDITOR COMMANDS
if ( isset($_REQUEST['action']) && !isset($_REQUEST['submit']) && !isset($_REQUEST['cancel']) ) {
	//ensure action is valid
	if ( stripos('edit copy del create pause active review', $_REQUEST['action']) === false ) {
		//unknown command
		showMessage(false, "Unknown action: " . $_REQUEST['action']);
		$action = false;
	} else {
		//validate clid
		$clid = (!isset($_REQUEST['clid']) || empty($_REQUEST['clid'])) ? '' : $_REQUEST['clid'];
		if ( $_REQUEST['action'] !== 'create' && (!isset($_REQUEST['clid']) || empty($_REQUEST['clid']) || !file_exists(LOC_CAMP . $_REQUEST['clid'] . '.cmp.php')) ) {
			showMessage(false, 'Invalid Campaign ID [' . $clid . '] Provided. Can not proceed with command!');
		} else {
			if ( $clid == 'default' ) {
				if ( $_REQUEST['action'] == 'edit' ):
					$s = 'are editing';
				elseif ( $_REQUEST['action'] == 'create' ):
					$s = 'are creating';
				elseif ( $_REQUEST['action'] == 'del' ):
					$s = 'have deleted';
				endif;
				showMessage(true, 'You ' . $s . ' your default campaign setup!');
			}

			switch ( $_REQUEST['action'] ) {
				case 'create':
					showHead();
					createCamp($clid);
					break;
				case 'copy':
					$data = getCampaign($clid);
					showHead();
					copyCamp($data);
					break;
				case 'edit':
					$data = getCampaign($clid);
					showHead();
					editCamp($clid, $data);
					break;
				case 'del':
					delCamp($clid);
					showHead();
					listCampaigns();
					break;
				case 'pause':
				case 'active':
				case 'review':
					changeStatus($clid, $_REQUEST['action']);
					showHead();
					listCampaigns();
					break;
			}
			showTail();
			Die();
		}
	}
}

//PROCESS ACTIONS SUBMITTED
if ( isset($_REQUEST['submit']) && stripos('create edit copy pause active review', $_REQUEST['action']) !== false ) {

	switch ( $_REQUEST['action'] ) {
		case 'pause':
		case 'active':
		case 'review':
			changeStatus($_POST['select'], $_REQUEST['action']);
			break;
		case 'create':
		case 'edit':
		case 'copy':
			$validForm = true;
			if ( $_REQUEST['action'] != 'edit' && file_exists(getCmpName($_REQUEST['clid'])) ) {
				showMessage(false, 'Campaign already exists [' . $_REQUEST['clid'] . ']. Please correct and submit to save!');
				$validForm = false;
			}

			if ( preg_match('=[.\s/&#:\?\*{}\\;\n]=', $_REQUEST['clid']) ) {
				showMessage(false, 'You used an invalid character in your Campaign name: ' . $_REQUEST['clid'] . ' Only use letters,numbers,dash or underscore. Dont use any spaces.');
				$validForm = false;
			}

			if ( stripos($_POST['info'], "'") !== false || stripos($_POST['info'], '"') !== false ) {
				showMessage(false, 'You used an invalid character in your Campaign title: ' . $_POST['info'] . ' Only use letters,numbers,dash or underscore. Dont use \' or "');
				$validForm = false;
			}
			if ( stripos($_POST['customref'], "'") !== false || stripos($_POST['customref'], '"') !== false ) {
				showMessage(false, 'You used an invalid character in your Custom referrer: ' . $_POST['customref'] . ' Only use letters,numbers,dash or underscore. Dont use \' or "');
				$validForm = false;
			}
			if ( stripos($_POST['allowedcountries'], "'") !== false || stripos($_POST['allowedcountries'], '"') !== false ) {
				showMessage(false, 'You used an invalid character in your Allowed countries: ' . $_POST['allowedcountries'] . ' Only use letters,numbers,dash or underscore. Dont use \' or "');
				$validForm = false;
			}
			if ( stripos($_POST['fakeurl'], "'") !== false || stripos($_POST['fakeurl'], '"') !== false ) {
				showMessage(false, 'You used an invalid character in your Fake URL: ' . $_POST['fakeurl'] . ' Only use letters,numbers,dash or underscore. Dont use \' or "');
				$validForm = false;
			}

			if ( empty($_REQUEST['clid']) || $_REQUEST['clid'] == "#N/A" ) {
				showMessage(false, 'Your CLID can not be empty. Please choose a unique CLID using only letters,numbers,dash, underscore and no spaces.');
				$validForm = false;
			}

			if ( !isset($_POST['traffic']) ) {
				showMessage(false, 'Please select your traffic source before saving your campaign');
				$validForm = false;
			}

			//check fakepage
			if ( empty($_POST['fakeurl']) ) {
				showMessage(false, 'Your fake url is empty. Make sure you enter a valid destination page in both fields.');
				$validForm = false;
			}

			//check in case of filepaths that files exist
			foreach ( $_POST['realurl'] as $i => $u ) {
				if ( empty($u) ) {
					showMessage(false, 'One of your primary page locations is empty. Ive removed that page.');
					$validForm = false;
					Break;
				}
				if ( stripos($u, "'") !== false || stripos($u, '"') !== false ) {
					showMessage(false, 'You used an invalid character in your Primary URL: ' . $u . ' Only use letters,numbers,dash or underscore. Dont use \' or "');
					$validForm = false;
					Break;
				}
				if ( stripos($u,'http://') !== 0 && stripos($u,'https://') !== 0 ) {
					//not a URL - direct file - check if it exists
					$tmp = explode('?',$u);
					if ( !file_exists($tmp[0]) || !is_readable($tmp[0]) ) {
						showMessage(false, 'Primary page ['.$tmp[0].'] is not accessible on your webserver.');
						$validForm = false;
					}
				}
			}

			if ( stripos($_POST['fakeurl'],'http://') !== 0 && stripos($_POST['fakeurl'],'https://') !== 0 ) {
				//not a URL - direct file - check if it exists
				$tmp = explode('?',$_POST['fakeurl']);
				if ( !file_exists($tmp[0]) || !is_readable($tmp[0]) ) {
					showMessage(false, 'Alternative page ['.$tmp[0].'] is not accessible on your webserver.');
					$validForm = false;
				}
			}

			//check real page
			$totperc = 0;
			$varsInUrl = false;
			$realurl = array();
			foreach ( $_REQUEST['realurl'] as $i => $u ) {
				if ( !empty($u) ) {
					$realurl[] = array('url' => $u, 'perc' => $_REQUEST['realperc'][$i]);
					$perc = (int)$_REQUEST['realperc'][$i];
					if ( empty($perc) ) {
						$perc = 0;
						$_REQUEST['realperc'][$i] = 0;
					}
					$totperc = $totperc + $perc;
				}
			}

			$dynvar = array();
			if ( count($_REQUEST['varname']) > 0 ) {
				foreach ( $_REQUEST['varname'] as $i => $v ) {
					if ( !empty($v) ) {
						//check whether build in variables have been used
						if ( stripos(DEF_DYN_VARS,$v) !== false ) {
							showMessage(false, 'You can not define build in variable names as dynamic variables: '.DEF_DYN_VARS);
							$validForm = false;
						}
						if ( stripos($_REQUEST['varval'][$i], "'") !== false || stripos($_REQUEST['varval'][$i], '"') !== false ) {
							showMessage(false, 'You used an invalid character in your Variable Value: ' . $_REQUEST['varval'][$i] . ' Only use letters,numbers,dash or underscore. Dont use \' or "');
							$validForm = false;
							Break;
						}
						if ( stripos($v, "'") !== false || stripos($v, '"') !== false ) {
							showMessage(false, 'You used an invalid character in your Variable Name: ' . $v . ' Only use letters,numbers,dash or underscore. Dont use \' or "');
							$validForm = false;
							Break;
						}

						$dynvar[$v] = $_REQUEST['varval'][$i];
					}
				}
			} else {
				$dynvar = Array();
			}

			if ( count($realurl) == 0 ) {
				showMessage(false, 'You have not entered any primary destination pages.');
				$validForm = false;
			}

			if ( $totperc != 100 ) {
				showMessage(false, 'Your total % of primary destination pages is not equal to 100');
				$validForm = false;
			}

			switch ( $_REQUEST['reftype'] ) {
				case 'all':
					$reftype = '*';
					break;
				case 'blank':
					$reftype = '';
					break;
				case 'noblank':
					$reftype = '-';
					break;
				case 'custom':
					$reftype = $_REQUEST['customref'];
					break;
				default:
					$reftype = '*';
			}

			$data = array(
				'maxrisk' => 5,
				'info' => $_REQUEST['info'],
				'fakeurl' => $_REQUEST['fakeurl'],
				'realurl' => $realurl,
				'dynvar' => $dynvar,
				'allowedcountries' => $_REQUEST['allowedcountries'],
				'allowedref' => $reftype,
				'urlkeyword' => $_REQUEST['urlkeyword'],
				'active' => $_REQUEST['active'],
				'device' => $_REQUEST['device'],
				'traffic' => $_REQUEST['traffic']);

			if ( !$validForm ) {
				showHead();
				if ( $_REQUEST['action'] == 'edit' ) {
					editCamp($_POST['clid'], $data);
				} else {
					createCamp($_POST['clid'], $data);
				}
				showTail();
				Die();
			}

			if ( !saveCampaign($_REQUEST['clid'], $data) ) {
				showMessage(false, 'Failed to write campaign to: ' . $fn);
			} else {
				if ( $_REQUEST['clid'] == 'default' ) {
					showMessage(true, 'Default Campaign Settings successfully saved.');
				} else {
					showMessage(true, 'Campaign successfully saved. You can copy your campaign URL from the table below.');
				}
			}
			break;
	}
}
showHead();
listCampaigns();
showTail();

//functions

function validateInstall() {

	global $vldMsgs;

	//Check php version
	if ( version_compare(PHP_VERSION, MIN_PHP_VERSION) < 0 ) {
		//Version not correct
		$vldMsgs[] = 'This App requires PHP Version ' . MIN_PHP_VERSION . ' or higher. Your server is running ' . PHP_VERSION;
	}

	//Required modules available (curl)
	if ( !extension_loaded('curl') ) {
		$vldMsgs[] = 'This App requires the CURL PHP Extension';
	}
	if ( !extension_loaded('json') ) {
		$vldMsgs[] = 'This App requires the JSON PHP Extension';
	}

	//Check if install file still present
	if ( file_exists('../install.php') && !isset($_GET['novalidate']) ) {
		$vldMsgs[] = 'Please remove the Install file: install.php';
	}

	if ( file_exists('../update.php') && !isset($_GET['novalidate']) ) {
		$vldMsgs[] = 'Please remove the Update file: update.php';
	}

	//check with robots txt is root of site
	if ( !file_exists($_SERVER['DOCUMENT_ROOT'] . '/robots.txt') ) {
		$vldMsgs[] = 'No robots.txt file in your domain root. To stop google from indexing your campaigns, copy the robots.txt file from the install package to the following location: ' . $_SERVER['DOCUMENT_ROOT'] . '/robots.txt';
	}

	//Campaign & log folders exist & are writable
	if ( !defined('LOC_CAMP') || !defined('LOC_LOG') ) {
		$vldMsgs[] = 'Folder location of Campaigns and Logs not defined in config file.';
	}

	if ( !is_writable(LOC_CAMP) ) {
		$vldMsgs[] = 'Campaign folder does not exist or is not writable: ' . LOC_CAMP;
	}

	if ( !is_writable(LOC_LOG) ) {
		$vldMsgs[] = 'Log folder does not exist or is not writable: ' . LOC_LOG;
	}

	//check webservice is running
	if ( $msg = !check_webservice() ) {
		$vldMsgs[] = 'Server Curl Error or WebService Not Operational.';
	}

	if ( count($vldMsgs) == 0 ) {
		//no validation errors
		showMessage(true, 'Validation successful. No errors found.');
		return true;
	} else {
		//output validation errors
		writeLog('[VALIDATION ERRORS]:' . implode(' - ', $vldMsgs));
		$s = '';
		foreach ( $vldMsgs as $m ) {
			$s .= "<li>$m</li>\n";
		}
		showMessage(false, "Validation FAILED. Please correct the errors below and <a href=\"?validate\">re-validate</a>.\n<ul>$s</ul>");
		unset($_SESSION['failed_login']);
		return false;
	}
}

function listCampaigns() {
	global $campdata,$showCol;
	$versionfail = false;
	//LOAD CAMPS INTO ARRAY
	$c = array();
	$files = scandir(LOC_CAMP);
	if ( $files !== false ) {
		foreach ( $files as $file ) {
			if ( stripos($file, '.cmp.php') !== false && stripos($file, '.cmp.php.archived') === false && $file !== 'default.cmp.php' ) {
				$camp=array();
				include(LOC_CAMP . $file);
				$c[$file] = $camp;
			}
		}
	}


	//VALIDATE CAMPS
	if ( !isset($campdata['data']) ) {
		$data = api_info();
	} else {
		$data = $campdata;
	}

	//Check if account active / no errors in retrieving data
	if ( isset($data['error']) ) {
		showMessage(false, implode("\n", $data['error']));
	}

	$today = strtotime('now');
	$totaladweight = 0;
	if ( isset($data['promo']) ) {
		foreach ( $data['promo'] as $ad ) {
			if ( $today >= strtotime($ad['from']) && $today <= strtotime($ad['to']) ) {
				$ads[] = $ad;
				$totaladweight = $totaladweight + $ad['weight'];
			}
		}
	} else {
		$ads = array();
	}
	if ( count($ads) > 0 ) {
		$ad = getAd($ads, $totaladweight);
		?>
	<div id="adslot">
		<iframe
			src="<?= (strpos($ad['url'], '?') !== false ? $ad['url'] . '&' : $ad['url'] . '?') . 'id=' . $data['account']['id'] . '&key=' . APIKEY ?>"
			title="<?= $ad['title'] ?>" width=960 height=150 frameborder=0
			scroll=no></iframe>
	</div>
	<?
	} else {
		$ad = null;
	}
	//PRINT CAMPS
	?>
<div id="linkform" title="Get Your Campaign Links/Setup">
</div>
<div id="loading">
</div>
<div id="changestatus" title="Confirmation Required">

</div>
<div id="results">
	<form method="post" action="<?= ADMIN_PATH ?>">
		<fieldset>
			<p style="float:left"><select name="action" id="selectAction"
			                              disabled>
				<option value="" disabled>Select required action</option>
				<option value="pause">Pause campaigns</option>
				<option value="active">Activate campaigns</option>
			</select>
				<input type="submit" name="submit" value="Submit"
				       id="submitButton" disabled>
				<a href="?action=create" class="button">New Campaign</a></p>
			<p style="float:right"><a href="unarchive.php" class="button">Unarchive</a></p>
			<table id="campaigns" class="display">
				<thead>
				<tr>
					<th class="nohvr" width="25"><input type="checkbox"
					                                    name="select"
					                                    value="all"
					                                    class="checkall"></th>
					<th class="nohvr" width="55">Status</th>
					<? foreach($showCol as $key => $col): ?>
					<th class="hvr col<?= $key.(!$col['show'] ? ' hide' : '') ?>"<?= isset($col['width']) ? " width={$col['width']}" : '' ?>><?= $col['head'] ?></th>
					<? endforeach ?>
					<th class="nohvr" width="125">Actions</span></th>
				</tr>
				</thead>
				<tbody>
					<?
					if ( count($c) > 0 ) {
						foreach ( $c as $f => $camp ) {
							//clid
							$clid = substr($f, 0, stripos($f, '.cmp.php'));

							if ( !isset($camp['cv']) || version_compare($camp['cv'], CLIENT_VERSION_CMP) < 0 ) {
								?>
							<tr>
								<td colspan=10>
									<center>
										<span
											style="color:red">Campaign: <b><?= $f ?></b> was saved by another version (<?= $camp['cv'] ?>
											) which is lower then the minimum version (<?= CLIENT_VERSION_CMP ?>
											).</span><br/>Please run update.php
										to update to the current version or
										delete by clicking the X on the right.
									</center>
								</td>
								<td class="center">
									<a href="?action=del&clid=<?= $clid ?>&auth=<?= md5($clid . DEL_AUTH_KEY) ?>"
									   title="Delete Campaign"
									   class="confirmdel"><img
										src="img/del.png"></a>
								</td>
							</tr>
								<?
							} else {

								//referrer setting
								if ( empty($camp['allowedref']) ) {
									$refStr = 'Blank';
								} elseif ( $camp['allowedref'] == '-' ) {
									$refStr = 'Not Blank';
								} elseif ( $camp['allowedref'] == '*' ) {
									$refStr = 'All';
								} else {
									$refStr = $camp['allowedref'];
								}

								$gourl = 'http://' . $_SERVER["HTTP_HOST"] . GO_PATH . '?clid=' . $clid;

								//build go.php
								if ( !empty($camp['dynvar']) ) {
									foreach ( $camp['dynvar'] as $var => $val ) {
										$gourl .= "&$var=$val";
									}
								}

								$gotesturl = $gourl . '&test';

								//bleedrate
								$clickReal = empty($data['data'][$clid][1]) ? 0 : $data['data'][$clid][1];
								$clickFake = empty($data['data'][$clid][0]) ? 0 : $data['data'][$clid][0];
								$clickTotal = $clickReal + $clickFake;
								$clickBR = $clickTotal > 0 ? round($clickFake / $clickTotal * 100) : 0;

								//camp active
								switch ( $camp['active'] ) {
									case -1:
										$status = 'review';
										$status_lcmd = 'active';
										$status_rcmd = 'pause';
										break;
									case 1:
										$status = 'active';
										$status_lcmd = 'pause';
										$status_rcmd = 'review';
										break;
									default:
										$camp['active'] = 0;
										$status = 'pause';
										$status_lcmd = 'active';
										$status_rcmd = 'review';
								}
								?>
							<tr>
								<td class="center">
									<input type="checkbox" name="select[]" value="<?= $clid ?>" class="uncheckall" <?= $camp['active'] == -1 ? 'disabled' : '' ?>>
								</td>
								<td class="statusactions">
									<a href="?action=<?= $status_lcmd ?>&clid=<?= $clid ?>" class="actions status <?= $status_lcmd ?> hidden<?= $status == 'review' ? ' statusconfirm' : '' ?>" title="Set campaign to <?= $status_lcmd ?>"></a>
									<span class="status <?= $status ?> current" title="Current status: <?= $status ?>"></span>
									<a href="?action=<?= $status_rcmd ?>&clid=<?= $clid ?>" class="actions status <?= $status_rcmd ?> hidden<?= $status == 'review' ? ' statusconfirm' : '' ?>" title="Set campaign to <?= $status_rcmd ?>"></a>
								</td>
								<td onclick="getCampCode('<?= $clid ?>', '<?= $gourl ?>')"
								    class="getlink">
									<b><?= empty($camp['info']) ? $clid : $camp['info'] ?></b>
								</td>
								<td onclick="getCampCode('<?= $clid ?>', '<?= $gourl ?>')"
								    class="getlink">
									<?= $clid ?>
								</td>
								<td class="center"><?= $camp['traffic'] ?></td>
								<td class="center"><?= empty($camp['allowedcountries']) ? 'Any' : str_ireplace('|', ' ', $camp['allowedcountries']) ?></td>
								<td class="center"><?= $refStr ?></td>
								<td class="center"><?= $clickTotal ?></td>
								<td class="center"><?= $clickReal ?></td>
								<td class="center"><?= $clickFake ?></td>
								<td class="center"><?= $clickBR ?> %</td>
								<td class="center campaignactions">
									<img src="img/dwl.png" onclick="getCampCode('<?= $clid ?>', '<?= $gourl ?>')" class="getlink" title="Get Your Campaign Code">&nbsp;&nbsp;
									<a href="<?= $gotesturl ?>" target="_blank"
									   title="Test Campaign Link"><img
										src="img/test.png"></a>&nbsp;&nbsp;
									<a href="stats.php?clid=<?= $clid ?>" title="View historic stats"><img src="img/stats.png" /></a>&nbsp;&nbsp;
									<a href="?action=edit&clid=<?= $clid ?>"
									   title="Edit Campaign"><img
										src="img/edit.png"></a>&nbsp;&nbsp;
									<a href="?action=copy&clid=<?= $clid ?>"
									   title="Copy Campaign"><img
										src="img/copy.png"></a>&nbsp;&nbsp;
									<a href="?action=del&clid=<?= $clid ?>&auth=<?= md5($clid . DEL_AUTH_KEY) ?>"
									   title="Delete Campaign"
									   class="confirmdel"><img
										src="img/del.gif"></a>
								</td>
							</tr>
								<?
							}
						}
					}
					?>
				</tbody>
			</table>
		</fieldset>
	</form>
</div>
<p class="small"><b>Click stats are for today only.</b><span class="flright">Server time: <?= $data['timestamp'] ?></span></p>

<? if ( isset($data['status']) ): ?>
	<div id="defcon">
		<span class="black head">Network Wide Filter Status (hover over title for more info):<span
			style="float:right; font-size:10px; box-shadow:none; color: black; margin:0px; padding:0px;">Last updated: <?= $data['updated'] ?></span></span>
		<?
		foreach ( $data['status'] as $t => $v ) {
			?>
			<span class="<?= $v['s'] ?>">
				<a title="<?= $v['i'] ?>"><?= $t ?></a><br/>
				<span class="info">cl:<?= $v['c'] ?> br:<?= $v['br'] ?>
					al:<?= $v['al'] ?></span>
			</span>
			<?
		}
		?>
	</div>
	<? endif ?>
<?
}

function showEditor($action, $clid, $data) {
	global $traffic;
	switch ( $data['allowedref'] ) {
		case '':
			$ref = 'blank';
			break;
		case '-':
			$ref = 'noblank';
			break;
		case '*':
			$ref = 'all';
			break;
		default:
			$ref = $data['allowedref'];
	}

	?>
<div id="filebrowse" title="Select the file to include">
	<div id="fileTree"></div>
	<input id="filePath" width=100% onclick="$(this).select()" disabled>
</div>
<div id="campedit">
	<form method="post" action="index.php" name="edit"
	      onsubmit="return validate(this);">
		<table id='newcamp'>
			<tr>
				<td colspan=2 class="head">Basic Information</td>
			</tr>
			<? if ( $action == 'edit' ): ?>
			<input type="hidden" name="clid" value="<?= $clid ?>">
			<? else:
			if ( $clid != 'default' ) {
				do {
					$clid = randomAlphaNum(8);
					$fn = getCmpName($clid);
				} while ( file_exists($fn) );
			}
			?>
			<tr>
				<td class="label">Campaign ID <img class="forinfo"
				                                   title="We have randomized and locked the CLID so you are not tempted to enter '<b>FYFB</b>'."
				                                   src="img/info.png"></td>
				<td class="input"><input type="text" name="clid" width="100%"
				                         value="<?= $clid ?>" readonly></td>
			</tr>
			<? endif ?>
			<tr>
				<td class="label">Private Title <img class="forinfo"
				                                     title="Include here a title to help you identify your campaign. It will not be passed in your campaign URL."
				                                     src="img/info.png"></td>
				<td class="input"><input type="text" name="info" width="100%"
				                         value="<?= $data['info'] ?>"></td>
			</tr>

			<tr>
				<td class="label">Status <img class="forinfo"
				                              title="Set the campaign to under review, active or paused. Paused and Under Review campaigns will always redirect to the alternative page."
				                              src="img/info.png"></td>
				<td class="input">
					<? if ( stripos('create copy',$action) !== false || $clid == 'default' ): ?>
					<input type="radio" name="active" value="-1" checked> Under Review (New campaigns always default to this status)<br/>
					<input type="radio" name="active" value="1" disabled> Active<br/>
					<input type="radio" name="active" value="0" disabled> Paused<br/>
					<? else: ?>
					<input type="radio" name="active" value="-1" <?= $data['active'] == -1 || !isset($data['active']) ? 'checked' : '' ?>> Under review<br/>
					<?= $data['active'] == -1 ? '<p><b>Only change the status when your traffic source has approved your campaign/changes</b></p>' : '' ?>
					<input type="radio" name="active"
					       value="1" <?= $data['active'] == 1 || !isset($data['active']) ? 'checked' : '' ?>>
					Active<br/>
					<input type="radio" name="active"
					       value="0" <?= $data['active'] == 0 ? 'checked' : '' ?>>
					Paused
					<? endif ?>
				</td>
			</tr>
			<tr>
				<td class="label">Traffic Source <img class="forinfo"
				                                      title="Select the source of the traffic."
				                                      src="img/info.png"></td>
				<td class="input">
					<select name="traffic">
						<?
						foreach ( $traffic as $tid => $tsource ) {
							?>
							<option
								value="<?= $tid ?>" <?= ($tid == '') ? 'disabled' : '' ?> <?= ($tid == $data['traffic']) ? 'selected' : '' ?>><?= $tsource ?></option>
							<?
						}
						?>
					</select>
				</td>
			</tr>
			<tr>
				<td colspan=2 class="head">Target Pages
				<a id="filepath"
				   class="button low advEdit" style="float:right">click to copy path to include files</a></td>

			</tr>
			<tr>
				<td class="label">Dynamic variables <img class="forinfo"
				                                         title="Pass information from your traffic source to your URLs by inserting [[variable name]]. Dont use: <?= DEF_DYN_VARS_STR ?>"
				                                         src="img/info.png"></td>
				<td class="input">
					<span class="headvar">Variable</span><span class="headval">Default value</span>
					<?
					foreach ( $data['dynvar'] as $var => $val ) {
						?>
						<span class="varitem"><input name="varname[]"
						                             class="varname"
						                             value="<?= $var ?>"><input
							name="varval[]" class="varval"
							value="<?= $val ?>"><img src="img/delbl.png"
						                             class="delvar"
						                             onclick="delVar(this)"
						                             title="Remove Variable"></span>
						<?
					}
					?>
					<a id="varend" onclick="addVar()"
					   class="button low"><b>+</b> add variable</a>
					<img src="img/undel.png" onclick="unDelVar()" id="undelvar"
					     class="rfloat" title="Recover Deleted Variables">
				</td>
			</tr>
			<tr>
				<td class="label">Primary page: <img class="forinfo"
				                                     title="Rotate through multiple URLs. Pass variables with [[name]]. You can also use the build in ones: <?= DEF_DYN_VARS_STR ?>"
				                                     src="img/info.png"></td>
				<td class="input">
					<span class="headUrl">Destination URLs/Filepath</span><span
					class="headPerc">%Split</span>
					<?
					foreach ( $data['realurl'] as $i => $u ) {
						?>
						<span class="realitem"><input name="realurl[]"
						                              class="realurl"
						                              value="<?= $u['url'] ?>"><input
							name="realperc[]" class="realperc"
							value="<?= $u['perc'] ?>"><img src="img/delbl.png"
						                                   class="delpage"
						                                   onclick="delPage(this)"
						                                   title="Remove Url"></span>
						<?
					}
					?>
					<a id="realurlend" onclick="addRealUrl()"
					   class="button low"><b>+</b> add destination</a>
					<img src="img/undel.png" onclick="unDel()" id="undel"
					     class="rfloat" title="Recover Deleted Urls">
					<a onclick="distUrl()" id="disturl"
					   class="button low rfloat"
					   title="Distribute Pages Equally"><b>==</b></a>
				</td>
			</tr>
			<tr>
				<td class="label">Alternative page <img class="forinfo"
				                                        title="Visitors and bots that have been filtered will be directed to this URL."
				                                        src="img/info.png"></td>
				<td class="input">
					<input name="fakeurl" value="<?= $data['fakeurl'] ?>"
					       class="fakeurl">
				</td>
			</tr>
			<tr>
				<td colspan=2 class="head">Filters</td>
			</tr>
			<tr class="advEdit">
				<td class="label">URL Query String <img class="forinfo"
				                                    title="Only allow access to your primary page if ANY of these keywords are present in the query string. Separate keywords with | symbol."
				                                    src="img/info.png"></td>
				<td class="input"><input type="text" name="urlkeyword"
				                         value="<?= $data['urlkeyword'] ?>">
				</td>
			</tr>
			<tr class="advEdit">
				<td class="label">Mobile Filter <img class="forinfo"
				                                    title="Filter based on whether the visitor is using a mobile device."
				                                    src="img/info.png"></td>
				<td class="input"><select name="device">
					<option value="none"<?= !isset($data['device']) || $data['device'] == 'none' ? ' selected' : ''?>>Allow mobile & non mobile</option>
					<option value="mobile"<?= $data['device'] == 'mobile' ? ' selected' : ''?>>Block mobile traffic</option>
					<option value="nonmobile"<?= $data['device'] == 'nonmobile' ? ' selected' : ''?>>Block non-mobile traffic</option>
				</select></td>
			</tr>
			<tr>
				<td class="label">Accepted Referrers <img class="forinfo"
				                                          title="Visitors will only be shown the Primary Page if the selected referrer setting applies."
				                                          src="img/info.png">
				</td>
				<td class="input">
					<input type="radio" name="reftype"
					       value="blank" <?= $ref == 'blank' ? 'checked' : '' ?>
					       onclick="customref.disabled = true;">Blank referrers
					only</br>
					<input type="radio" name="reftype"
					       value="all" <?= $ref == 'all' ? 'checked' : '' ?>
					       onclick="customref.disabled = true;">Accept All
					referrers</br>
					<input type="radio" name="reftype"
					       value="noblank" <?= $ref == 'noblank' ? 'checked' : '' ?>
					       onclick="customref.disabled = true;">All refferrers
					but not blank</br>
					<input type="radio" name="reftype"
					       value="custom" <?= $ref == 'custom' ? 'checked' : '' ?>
					       onclick="customref.disabled = false; customref.focus();">Custom
					referrer string</br>
					<input type="text"
					       name="customref" <?= $ref == 'custom' ? '' : 'disabled' ?>
					       value="<?= $ref == 'custom' ? $data['allowedref'] : '' ?>">
				</td>
			</tr>
			<tr>
				<td class="label">Accepted Countries <img class="forinfo"
				                                          title="Allow visitors from these countries. Use abbreviations like US & UK. Separate countries with the | chatacter."
				                                          src="img/info.png">
				</td>
				<td class="input"><input type="text" name="allowedcountries"
				                         value="<?= $data['allowedcountries'] ?>">
				</td>
			</tr>
		</table>
		<input class="formsubmit" type="submit" value="Submit" name="submit">
		<input class="formsubmit" type="submit" name="cancel" value="Cancel"
		       onclick="return confirm('Are you sure you want to Cancel editing this campaign? You will loose the changes you made.');">
</div>
<input type="hidden" name="action" value="<?= $action ?>">
</form>
<? if ( $action != 'edit' ): ?>
	<script>
		document.forms['edit'].elements['info'].focus();
	</script>
	<? endif ?>
<p></p>
<?
}

function createCamp($clid = '', $data = null) {
	if ( empty($clid) || $data == null ) {
		if ( file_exists(getCmpName('default')) ) {
			$data = getCampaign('default');
		} else {
			$data = array(
				'info' => '',
				'fakeurl' => 'http://google.com/search?q=fakingit',
				'realurl' => array(array('url' => 'http://google.com/search?q=[[c1]]', 'perc' => 100)),
				'dynvar' => array('c1' => '%KEYWORD%'),
				'allowedcountries' => 'US|UK',
				'allowedref' => '*',
				'active' => -1,
				'traffic' => 'FB'
			);
		}
	}
	?>
<h2>Create Campaign</h2>
<p>Complete the fields below and submit to save. Click cancel to go back to the
	Campaign overview screen.</p>
<?
	showEditor('create', $clid, $data);
}

function copyCamp($data) {
	?>
<h2>Copy Campaign</h2>
<p>Campaign details have been copied below. Please enter a new Campaign ID, make
	changes, and Submit. The data will not be saved unless you submit.</p>
<?
	$data['active'] = -1;
	showEditor('copy', '', $data);
}

function editCamp($clid, $data) {
	?>
<h2>Edit Campaign: <?= $clid ?></h2>
<p>Make changes and submit to save</p>
<?
	showEditor('edit', $clid, $data);
}

function delCamp($clid) {
	if ( (!isset($_REQUEST['auth']) || $_REQUEST['auth'] != md5($clid . DEL_AUTH_KEY)) && $clid != 'default' ) {
		//invalid del command issued
		showMessage(false, 'Unauthorised Archive command issued. Cancelling operation.');
	} else {
		$fn = getCmpName($clid);
		if ( rename($fn, $fn . '.archived') ) {
			showMessage(true, 'Campaign [' . $clid . '] was archived successfully.');
		} else {
			showMessage(false, 'Failed to archive campaign [' . $clid . '].');
		}
	}
}

function changeStatus($clid, $action) {
	$statuscodes['active'] = 1;
	$statuscodes['pause'] = 0;
	$statuscodes['review'] = -1;

	if ( !is_array($clid) ) {
		$clid = Array(
			0 => $clid
		);
	}
	$success = true;
	$statusMsg = '';
	foreach ( $clid as $id ) {
		if ( file_exists(getCmpName($id)) ) {
			$camp = getCampaign($id);
			$camp['active'] = $statuscodes[$action];
			if ( !saveCampaign($id, $camp) ) {
				$success = false;
				$statusMsg .= empty($statusMsg) ? '<ul><li>Campaign ' . $id . ' save error</li>' : '<li>Campaign ' . $id . ' save error</li>';
			}
		} else {
			$success = false;
			$statusMsg .= empty($statusMsg) ? '<ul><li>Campaign ' . $id . ' could not be read</li>' : '<li>Campaign ' . $id . ' could not be read</li>';
		}
	}


	if ( !$success ) {
		$statusMsg .= '</ul>';
		showMessage(false, 'Failed to change status to '.$action.':'.$statusMsg);
	} else {
		showMessage(true, 'Campaign status successfully changed.');
	}
}

function saveCampaign($clid, $camp) {
	$s = "";
	foreach ( $camp['realurl'] as $url ) {
		$url['perc'] = empty($url['perc']) ? '0' : $url['perc'];
		$s .= empty($s) ? "Array(\n" : ",\n";
		$s .= "\t\tArray('url'=>'{$url['url']}','perc'=>{$url['perc']})";
	}
	$s .= ")";

	$t = '';
	if ( count($camp['dynvar']) > 0 ) {
		foreach ( $camp['dynvar'] as $k => $v ) {
			$t .= empty($t) ? "Array(\n" : ",\n";
			$t .= "\t\t'$k'=>'$v'";
		}
	}
	$t .= empty($t) ? "Array()" : ")";

	if ( !isset($camp['device']) ) {
		$camp['device'] = 'none';
	}

	$data = "<?php\n\$camp = Array(
	'cv'=>'" . CLIENT_VERSION . "',
	'maxrisk'=>{$camp['maxrisk']},
	'info'=>'{$camp['info']}',
	'fakeurl'=>'{$camp['fakeurl']}',
	'realurl'=>$s,
	'dynvar'=>$t,
	'allowedcountries'=>'{$camp['allowedcountries']}',
	'allowedref'=>'{$camp['allowedref']}',
	'urlkeyword'=>'{$camp['urlkeyword']}',
	'active'=>{$camp['active']},
	'device'=>{$camp['device']},
	'traffic'=>'{$camp['traffic']}');\n?>";

	$fn = getCmpName($clid);

	if ( @file_put_contents($fn, $data) === false ) {
		$err = error_get_last();
		writeLog('[ERROR] Failed To Write Campaign File [' . $fn . ']. Error type: ' . $err['type'] . ' Message: ' . $err['message']);
	} else {
		if ( !is_writable($fn) || !chmod($fn, 0664) ) {
			writeLog('[WARNING] Failed to make the campaign file [' . $fn . '] writable for user & group. noIPfraud should still function correctly, but you will not be able to edit files directly');
		}
		return true;
	}
}

function getAd($ads, $totweight) {
	$r = mt_rand(1, $totweight);
	foreach ( $ads as $i => $ad ) {
		$weight = $ad['weight'];
		if ( $weight >= $r ) {
			return $ad;
		}
		$r -= $weight;
	}
}

?>
