<?php
session_start();

require('config.php');
require(TC_ROOTDIR.'lib/smarty.php');
require_once(TC_ROOTDIR . 'inc/functions.php');
require_once(TC_ROOTDIR . 'inc/operations.functions.php');
require_once(TC_ROOTDIR . 'inc/classes/manage.class.php');
require_once(TC_ROOTDIR . 'inc/encryption.php');
require_once(TC_ROOTDIR . 'inc/module.php');

$manage_class = new Manage();
$smarty->assign('lang_manageboards', _('Manage boards'));

$tpl_links = '';

if (!$manage_class->ValidateSession(true)) {
	$tpl_links .= '<li><a href="manage_page.php">Log in</a></li>';
} else {
	$manage_postpassword = md5_encrypt($_SESSION['manageusername'], TC_RANDOMSEED);
	
	$tpl_links .= '<li><a href="manage_page.php?action=logout">Logout</a></li>
	<li><span id="postingpassword"><a id="showpwd" href="manage_page.php#" onclick="javascript:document.getElementById(\'postingpassword\').innerHTML = \'<input type=text id=postingpasswordbox value=' . $manage_postpassword . '>\'; document.getElementById(\'postingpasswordbox\').select();">'._('Show Posting Password').'</a></span></li>';
	/* Home */
	$tpl_links .= section_html(_('Home'), 'home') .
	'<ul>
	<li><a href="manage_page.php?">'._('Posting rates (past hour)').'</a></li>
	</ul></div>';
	/* Administration */
	if ($manage_class->CurrentUserIsAdministrator()) {
		$tpl_links .= section_html(_('Administration'), 'administration') .
		'<ul>
		<li><a href="manage_page.php?action=news">' . _('News') . '</a></li>
		<li><a href="manage_page.php?action=addboard">' . _('Add board') . '</a></li>
		<li><a href="manage_page.php?action=delboard">' . _('Delete board') . '</a></li>
		<li><a href="manage_page.php?action=wordfilter">' . _('Wordfilter') . '</a></li>
		<li><a href="manage_page.php?action=checkversion">' . _('Check for new version') . '</a></li>
		<li><a href="manage_page.php?action=spaceused">' . _('Disk space used') . '</a></li>
		<li><a href="manage_page.php?action=viewdeletedthread">' . _('View deleted thread') . '</a></li>
		<li><a href="manage_page.php?action=cleanup">' . _('Cleanup') . '</a></li>
		<li><a href="manage_page.php?action=search">' . _('Search posts') . '</a></li>
		<li><a href="manage_page.php?action=staff">' . _('Staff') . '</a></li>
		<li><a href="manage_page.php?action=modlog">' . _('ModLog') . '</a></li>
		<li><a href="manage_page.php?action=editfiletypes">' . _('Edit filetypes') . '</a></li>
		<li><a href="manage_page.php?action=editsections">' . _('Edit sections') . '</a></li>
		<li><a href="manage_page.php?action=sql">' . _('SQL query') . '</a></li>
		<li><a href="manage_page.php?action=proxyban">' . _('Ban proxy list') . '</a></li>
		<li><a href="manage_page.php?action=rebuildall">' . _('Rebuild all html files') . '</a></li>
		</ul></div>';
		
		$tpl_links .= section_html(_('Modules'), 'modules') .
		'<ul>
		<li><a href="manage_page.php?action=modulesettings">' . _('Module settings') . '</a></li>';
		foreach (modules_list() as $module) {
			$tpl_links .= '<li><a href="manage_page.php?action=modulesettings&module=' . $module . '">' . $module . '</a></li>';
		}
		$tpl_links .= '</ul></div>';
	}
	/* Boards */
	$tpl_links .= section_html(_('Boards'), 'boards') .
	'<ul>
	<li><a href="manage_page.php?action=boardopts">' . _('Board options') . '</a></li>
	<li><a href="manage_page.php?action=stickypost">' . _('Manage stickies') . '</a></li>
	<li><a href="manage_page.php?action=lockpost">' . _('Manage locked threads') . '</a></li>
	<li><a href="manage_page.php?action=delposts">' . _('Delete thread/post') . '</a></li>
	</ul></div>';
	/* Moderation */
	$open_reports = $tc_db->GetAll("SELECT HIGH_PRIORITY COUNT(*) FROM `" . TC_DBPREFIX . "reports` WHERE `cleared` = '0'");
	$tpl_links .= section_html(_('Moderation') . '[' . $open_reports[0][0] . ']', 'moderation') .
	'<ul>
	<li><a href="manage_page.php?action=reports">' . 'View Reports' . '</a></li>
	<li><a href="manage_page.php?action=bans">' . _('View/Add/Remove bans') . '</a></li>
	<li><a href="manage_page.php?action=deletepostsbyip">' . _('Delete all posts by IP') . '</a></li>
	<li><a href="manage_page.php?action=changepwd">' . _('Change account password') . '</a></li>
	</ul></div>';
	
	$tpl_links .= '<br>' . _('Welcome') . ', <b>' . $_SESSION['manageusername'] . '</b>';
	if ($_SESSION['manageusername'] == 'admin' && $_SESSION['managepassword'] == md5('admin')) {
		$tpl_links .= '<font color="red"><b>' . _('NOTICE: You are using the default administrator account.  Anyone can log in to this account, so a second administrator account needs to be created.  Create another, log in to it, and delete this one.') . '</b></font><br>';
	}
	$tpl_links .= '<br>' . _('Staff rights') . ': <b>';
	if ($manage_class->CurrentUserIsAdministrator()) {
		$tpl_links .= _('Administrator');
	} else {
		$tpl_links .= _('Moderator');
	}
	$tpl_links .= '</b><br>';
	if (!$manage_class->CurrentUserIsAdministrator()) {
		$tpl_links .= _('Moderating boards') . ': ';
		$i = 0;
		$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
		foreach ($resultsboard as $lineboard) {
			$i++;
			if ($manage_class->CurrentUserIsModeratorOfBoard($lineboard['name'], $_SESSION['manageusername'])) {
				$tpl_links .= '<b>/' . $lineboard['name'] . '/</b>, ';
			}
		}
		if ($i == 0) {
			$tpl_links .= _('No boards');
		} else {
			$tpl_links .= $i . ' ' . _('Boards');
		}
	}
}

function section_html($section, $abbreviation) {
	return '<h2>
	<span class="plus" onclick="toggle(this, \'' . $abbreviation . '\');" title="Click to show/hide">
	&minus;
	</span>
	' . $section . '
	</h2>
	<div id="' . $abbreviation . '" style="">';
}

$smarty->assign('links', $tpl_links);
$smarty->display('manage_menu.tpl');
?>