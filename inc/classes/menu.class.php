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
 * You should have received a copy of the GNU General Public License along with
 * Trevorchan; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 * +------------------------------------------------------------------------------+
 * Menu class
 * +------------------------------------------------------------------------------+
 */
class Menu {

	function Generate() {
		global $tc_db, $smarty, $tc_config;
		
		require_once(TC_ROOTDIR.'lib/smarty.php');
		
		$smarty->assign('lang_frontpage', _gettext('Front Page'));
		
		$tpl_irc = '';
		if (TC_IRC!='') {
			$tpl_irc .= '<h2>IRC</h2>
			<ul>
			<li>' . TC_IRC . '</li>
			</ul>';
		}
		if (isset($tc_config)) {
			if ($tc_config['is_trevorchan']) {
				$tpl_irc .= '<h2>Links</h2>
				<ul>
				<li><a href="chans.php" target="main">Directory</a></li>
				<li><a href="http://code.google.com/p/trevorchan/w/list" target="_top">Wiki (Guides and more)</a></li>
				<li><a href="http://code.google.com/p/trevorchan/wiki/InstallationGuide" target="_top">&nbsp;-&nbsp;Installing</a></li>
				<li><a href="http://code.google.com/p/trevorchan/wiki/AdministrationGuide" target="_top">&nbsp;-&nbsp;Administration</a></li>
				<li><a href="http://code.google.com/p/trevorchan/wiki/StaffGuide" target="_top">&nbsp;-&nbsp;Staff</a></li>
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
src="http://pagead2.googlesyndication.com/pagead/show_ads.js">';
			}
		}
		$smarty->assign('irc', $tpl_irc);
		
		for ($i = 0; $i < 2; $i++) {
			$tpl_boards = '';
			$results_boardsexist = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."boards` LIMIT 1");
			if (count($results_boardsexist)==0) {
				$tpl_boards .= '<ul><li>'._gettext('No visible boards').'</li></ul>';
			} else {
				$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."sections` ORDER BY `order` ASC");
				foreach($results AS $line) {
					$tpl_boards .= '<h2><span class="plus" onclick="toggle(this, \''.$line['abbreviation'].'\');" title="'._gettext('Click to show/hide').'">';
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
							$tpl_boards .= '<li><a href="'.TC_BOARDSPATH.'/'.$lineboard['name'].'/">';
							if ($lineboard['trial']==1) { $tpl_boards .= '<i>'; }
							if ($lineboard['popular']==1) { $tpl_boards .= '<b>'; }
							if ($i == 1) {
								$tpl_boards .= '/'.$lineboard['name'].'/ - ';
							}
							$tpl_boards .= $lineboard['desc'];
							if ($lineboard['locked']=="1") {
								$tpl_boards .= ' <img src="'.TC_BOARDSPATH.'/locked.gif" border="0" alt="Locked" />';
							}
							if ($lineboard['trial']==1) { $tpl_boards .= '</i>'; }
							if ($lineboard['popular']==1) { $tpl_boards .= '</b>'; }
							$tpl_boards .= '</a></li>';
						}
					} else {
						$tpl_boards .= '<li>'._gettext('No visible boards').'</li>';
					}
					$tpl_boards .= '</ul></div>';
				}
			}
			$smarty->assign('boards', $tpl_boards);
		
			if ($i == 0) {
				$smarty->assign('redirscript', "\n" . 'if (getCookie(\'tcshowdirs\') == \'yes\') {' . "\n" .
				'	window.location = \'' . TC_WEBPATH . '/menu_dirs.html\';' . "\n" .
				'}' . "\n");
				$smarty->assign('showhidedirs', '<li><a target="_self" onclick="javascript:showdirs();" href="menu_dirs.html">['._gettext('Show Directories').']</a></li>');
				file_put_contents(TC_ROOTDIR . 'menu.html', $smarty->fetch('menu.tpl'));
			} else {
				$smarty->assign('redirscript', '');
				$smarty->assign('showhidedirs', '<li><a target="_self" onclick="javascript:hidedirs();" href="menu.html">['._gettext('Hide Directories').']</a></li>');
				file_put_contents(TC_ROOTDIR . 'menu_dirs.html', $smarty->fetch('menu.tpl'));
			}
		}
	}
}
?>