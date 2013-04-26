<?php
/*
 * This file is part of Trevorchan.
 *
 * Trevorchan is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * Trevorchan is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 *  You should have received a copy of the GNU General Public License along with
 * Trevorchan; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * +------------------------------------------------------------------------------+
 * Links to all boards for navigation
 * +------------------------------------------------------------------------------+
 * Boards will be listed, divided up by sections set in the manage panel.  IRC info
 * will also be displayed, if it is set.
 * +------------------------------------------------------------------------------+
 */
require('config.php');
require(TC_ROOTDIR.'lib/smarty.php');
require(TC_ROOTDIR.'inc/functions.php');

$smarty->assign('lang_frontpage', _('Front Page'));

if (isset($_GET['showdirs'])) {
	setcookie('tcshowdirs', 'yes', time()+604800);
	$_COOKIE['tcshowdirs'] = 'yes';
} elseif (isset($_GET['hidedirs'])) {
	setcookie('tcshowdirs', '', 0);
	$_COOKIE['tcshowdirs'] = '';
} else {
	$_COOKIE['tcshowdirs'] = '';
}

$tpl_showhidedirs = '<li><a target="_self" href="';
if ($_COOKIE['tcshowdirs']=='yes') {
    $tpl_showhidedirs .= '?hidedirs">['._('Hide Directories').']';
} else {
    $tpl_showhidedirs .= '?showdirs">['._('Show Directories').']';
}
$tpl_showhidedirs .= '</a></li>';
$smarty->assign('showhidedirs', $tpl_showhidedirs);

$tpl_boards = '';
$results_boardsexist = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."boards` LIMIT 1");
if (count($results_boardsexist)==0) {
	$tpl_boards .= '<ul><li>'._('No visible boards').'</li></ul>';
} else {
	$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."sections` ORDER BY `order` ASC");
	foreach($results AS $line) {
		$tpl_boards .= '<h2><span class="plus" onclick="toggle(this, \''.$line['abbreviation'].'\');" title="'._('Click to show/hide').'">';
		if ($line['hidden']==1) {
			$tpl_boards .= '+';
		} else {
			$tpl_boards .= '&minus;';
		}
		$tpl_boards .= '</span>'.$line['name'].'</h2><div id="'.$line['abbreviation'].'" style="';
		if ($line['hidden']==1) {
			$tpl_boards .= 'display: none;';
		}
		$tpl_boards .= '"><ul>';
		$resultsboard = $tc_db->GetAll("SELECT `name`, `desc`, `locked`, `trial`, `popular` FROM `".TC_DBPREFIX."boards` WHERE `section` = ".$line['id']." ORDER BY `order` ASC");
		if (count($resultsboard)>0) {
			foreach($resultsboard AS $lineboard) {
				$tpl_boards .= '<li><a href="'.TC_BOARDSPATH.'/'.$lineboard['name'].'/board.html">';
				if ($lineboard['trial']==1) { echo '<i>'; }
				if ($lineboard['popular']==1) { echo '<b>'; }
				if ($_COOKIE['tcshowdirs']=='yes') {
					$tpl_boards .= '/'.$lineboard['name'].'/ - ';
				}
				$tpl_boards .= $lineboard['desc'];
				if ($lineboard['locked']=="1") {
					$tpl_boards .= ' <img src="'.TC_BOARDSPATH.'/locked.gif" border="0" alt="Locked" />';
				}
				if ($lineboard['trial']==1) { echo '</i>'; }
				if ($lineboard['popular']==1) { echo '</b>'; }
				$tpl_boards .= '</a></li>';
			}
		} else {
			$tpl_boards .= '<li>'._('No visible boards').'</li>';
		}
		$tpl_boards .= '</ul></div>';
	}
}
$smarty->assign('boards', $tpl_boards);

$tpl_irc = '';
if (TC_IRC!='') {
	$tpl_irc .= '<h2>IRC</h2>
	<ul>
	<li>' . TC_IRC . '</li>
	</ul>';
}
if ($tc_config['is_trevorchan']) {
	$tpl_irc .= '<h2>Links</h2>
	<ul>
	<li><a href="http://code.google.com/p/trevorchan/" target="_top">Project page</a></li>
	</ul>
	
	<script type="text/javascript"><!--
	google_ad_client = "pub-6158454562572132";
	google_ad_width = 120;
	google_ad_height = 600;
	google_ad_format = "120x600_as";
	google_ad_type = "text_image";
	//2007-01-07: Trevorchan
	google_ad_channel = "7008956366";
	google_color_border = "FFFFEE";
	google_color_bg = "FFFFEE";
	google_color_link = "800000";
	google_color_text = "cb7e46";
	google_color_url = "800000";
	//--></script>
	<script type="text/javascript"
	src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
	</script>';
}
$smarty->assign('irc', $tpl_irc);

$smarty->display('menu.tpl');
?>