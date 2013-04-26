<?php
/*
 * This file is part of kusaba.
 *
 * kusaba is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * kusaba is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * kusaba; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
/** 
 * Manage menu
 *
 * Loaded when a user visits manage.php
 * 
 * @package kusaba  
 */

session_start();

require 'config.php';
require KU_ROOTDIR.'lib/smarty.php';
require KU_ROOTDIR . 'inc/functions.php';
require KU_ROOTDIR . 'inc/classes/manage.class.php';

$manage_class = new Manage();
$smarty->assign('lang_manageboards', _gettext('Manage boards'));

$tpl_links = '';

if (!$manage_class->ValidateSession(true)) {
	$tpl_links .= '<li><a href="manage_page.php">' . ucfirst(_gettext('log in')) . '</a></li>';
} else {
	$manage_postpassword = md5_encrypt($_SESSION['manageusername'], KU_RANDOMSEED);
	
	$tpl_links .= '<li><a href="manage_page.php?action=logout">' . ucfirst(_gettext('log out')) . '</a></li>
	<li><span id="postingpassword"><a id="showpwd" href="#" onclick="javascript:document.getElementById(\'postingpassword\').innerHTML = \'<input type=text id=postingpasswordbox value=' . $manage_postpassword . '>\'; document.getElementById(\'postingpasswordbox\').select(); return false;">'._gettext('Show Posting Password').'</a></span></li>';
	/* Home */
	$tpl_links .= section_html(_gettext('Home'), 'home') .
	'<ul>
	<li><a href="manage_page.php?">'._gettext('Posting rates (past hour)').'</a></li>
	<li><a href="manage_page.php?action=statistics">' . _gettext('Statistics') . '</a></li>';
	if ($manage_class->CurrentUserIsAdministrator() || $manage_class->CurrentUserIsModerator()) {
		$tpl_links .= '<li><a href="manage_page.php?action=changepwd">' . _gettext('Change account password') . '</a></li>';
	}
	$tpl_links .= '</ul></div>';
	/* Administration */
	if ($manage_class->CurrentUserIsAdministrator()) {
		$tpl_links .= section_html(_gettext('Site Administration'), 'siteadministration') .
		'<ul>
		<li><a href="manage_page.php?action=news">' . _gettext('News') . '</a></li>';
		if (KU_BLOTTER) {
			$tpl_links .= '<li><a href="manage_page.php?action=blotter">' . _gettext('Blotter') . '</a></li>';
		}
		$tpl_links .= '<li><a href="manage_page.php?action=spaceused">' . _gettext('Disk space used') . '</a></li>
		<li><a href="manage_page.php?action=checkversion">' . _gettext('Check for new version') . '</a></li>
		<li><a href="manage_page.php?action=staff">' . _gettext('Staff') . '</a></li>
		<li><a href="manage_page.php?action=modlog">' . _gettext('ModLog') . '</a></li>
		<li><a href="manage_page.php?action=proxyban">' . _gettext('Ban proxy list') . '</a></li>
		<li><a href="manage_page.php?action=sql">' . _gettext('SQL query') . '</a></li>
		<li><a href="manage_page.php?action=cleanup">' . _gettext('Cleanup') . '</a></li>' . "\n";
		if (KU_APC) {
			$tpl_links .= '<li><a href="manage_page.php?action=apc">APC</a></li>' . "\n";
		}
		$tpl_links .= '</ul></div>' .
		section_html(_gettext('Boards Administration'), 'boardsadministration') .
		'<ul>
		<li><a href="manage_page.php?action=addboard">' . _gettext('Add board') . '</a></li>
		<li><a href="manage_page.php?action=delboard">' . _gettext('Delete board') . '</a></li>
		<li><a href="manage_page.php?action=wordfilter">' . _gettext('Wordfilter') . '</a></li>
		<li><a href="manage_page.php?action=movethread">' . _gettext('Move thread') . '</a></li>
		<li><a href="manage_page.php?action=search">' . _gettext('Search posts') . '</a></li>
		<li><a href="manage_page.php?action=viewdeletedthread">' . _gettext('View deleted thread') . '</a></li>
		<li><a href="manage_page.php?action=editfiletypes">' . _gettext('Edit filetypes') . '</a></li>
		<li><a href="manage_page.php?action=editsections">' . _gettext('Edit sections') . '</a></li>
		<li><a href="manage_page.php?action=rebuildall">' . _gettext('Rebuild all html files') . '</a></li>' . "\n" .
		'</ul></div>';
		
		/*$tpl_links .= section_html(_gettext('Modules'), 'modules') .
		'<ul>
		<li><a href="manage_page.php?action=modulesettings">' . _gettext('Module settings') . '</a></li>';
		foreach (modules_list() as $module) {
			$tpl_links .= '<li><a href="manage_page.php?action=modulesettings&module=' . $module . '">' . $module . '</a></li>';
		}
		$tpl_links .= '</ul></div>';*/
	}
	/* Boards */
	$tpl_links .= section_html(_gettext('Boards'), 'boards') .
	'<ul>
	<li><a href="manage_page.php?action=boardopts">' . _gettext('Board options') . '</a></li>
	<li><a href="manage_page.php?action=stickypost">' . _gettext('Manage stickies') . '</a></li>
	<li><a href="manage_page.php?action=lockpost">' . _gettext('Manage locked threads') . '</a></li>
	<li><a href="manage_page.php?action=delposts">' . _gettext('Delete thread/post') . '</a></li>
	</ul></div>';
	/* Moderation */
	if ($manage_class->CurrentUserIsAdministrator() || $manage_class->CurrentUserIsModerator()) {
		$open_reports = $tc_db->GetAll("SELECT HIGH_PRIORITY COUNT(*) FROM `" . KU_DBPREFIX . "reports` WHERE `cleared` = '0'");
		$tpl_links .= section_html(_gettext('Moderation') . '[' . $open_reports[0][0] . ']', 'moderation') .
		'<ul>
		<li><a href="manage_page.php?action=reports">' . _gettext('View Reports') . '</a></li>
		<li><a href="manage_page.php?action=bans">' . _gettext('View/Add/Remove bans') . '</a></li>
		<li><a href="manage_page.php?action=deletepostsbyip">' . _gettext('Delete all posts by IP') . '</a></li>
		<li><a href="manage_page.php?action=recentimages">' . _gettext('Recently uploaded images') . '</a></li>
		</ul></div>';
	}
	
	$tpl_links .= '<br>' . _gettext('Welcome') . ', <b>' . $_SESSION['manageusername'] . '</b>';
	if ($_SESSION['manageusername'] == 'admin' && $_SESSION['managepassword'] == md5('admin')) {
		$tpl_links .= '<font color="red"><b>' . _gettext('NOTICE: You are using the default administrator account.  Anyone can log in to this account, so a second administrator account needs to be created.  Create another, log in to it, and delete this one.') . '</b></font><br>';
	}
	$tpl_links .= '<br>' . _gettext('Staff rights') . ': <b>';
	if ($manage_class->CurrentUserIsAdministrator()) {
		$tpl_links .= _gettext('Administrator');
	} elseif ($manage_class->CurrentUserIsModerator()) {
		$tpl_links .= _gettext('Moderator');
	} else {
		$tpl_links .= _gettext('Janitor');
	}
	$tpl_links .= '</b><br>';
	if (!$manage_class->CurrentUserIsAdministrator()) {
		$tpl_links .= _gettext('Moderating boards') . ': ';
		$i = 0;
		$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . KU_DBPREFIX . "boards`");
		foreach ($resultsboard as $lineboard) {
			if ($manage_class->CurrentUserIsModeratorOfBoard($lineboard['name'], $_SESSION['manageusername'])) {
				$i++;
				$tpl_links .= '<b>/' . $lineboard['name'] . '/</b>, ';
			}
		}
		if ($i == 0) {
			$tpl_links .= _gettext('No boards');
		} else {
			$tpl_links .= $i . ' ' . _gettext('Boards');
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