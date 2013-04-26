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
 * Menu class
 * 
 * @package kusaba 
 */
class Menu {

	function GetMenu($savetofile = false, $option = false) {
		global $tc_db, $smarty, $kusabaorg;
		
		require_once KU_ROOTDIR.'lib/smarty.php';
		
		$smarty->assign('lang_frontpage', _gettext('Front Page'));
		
		$smarty->assign('styles', printStylesheetsSite(KU_DEFAULTMENUSTYLE, true));
		
		if (KU_MENUSTYLESWITCHER) {
			$styles = explode(':', KU_MENUSTYLES);
			$styleswitcher = 'Styles: ';
			foreach ($styles as $style) {
				$styleswitcher .= '[<a href="#" onclick="javascript:set_stylesheet(\\\'' . ucfirst($style) . '\\\', false, true);reloadmain();" style="display: inline;">' . strtoupper(substr($style, 0, 1)) . '</a>] ';
			}
			$styleswitcher = substr($styleswitcher, 0, -1);
		} else {
			$styleswitcher = '';
		}
		$smarty->assign('styleswitcher', $styleswitcher);
		
		if ($savetofile) {
			$files = array('menu.html', 'menu_dirs.html');
		} else {
			$files = array('menu.php', 'menu.php');
		}
		
		$smarty->assign('menu_file_nodirs', $files[0]);
		$smarty->assign('menu_file_dirs', $files[1]);
		
		$tpl_irc = '';
		if (KU_IRC!='') {
			$tpl_irc .= '<h2>&nbsp;IRC</h2>' . "\n" .
			'<ul>' . "\n" .
			'	<li>' . KU_IRC . '</li>' . "\n" .
			'</ul>' . "\n";
		}
		if (isset($kusabaorg)) {
			$tpl_irc .= '<h2>&nbsp;Links</h2>
			<ul>
			<li><a href="chans.php" target="main">Directory</a></li>
			<li><a href="http://code.google.com/p/kusaba/w/list" target="_top">Wiki (Guides and more)</a></li>
			<li><a href="http://code.google.com/p/kusaba/wiki/InstallationGuide" target="_top">&nbsp;-&nbsp;Installing</a></li>
			<li><a href="http://code.google.com/p/kusaba/wiki/AdministrationGuide" target="_top">&nbsp;-&nbsp;Administration</a></li>
			<li><a href="http://code.google.com/p/kusaba/wiki/StaffGuide" target="_top">&nbsp;-&nbsp;Staff</a></li>
			<li><a href="http://code.google.com/p/kusaba/wiki/ModuleList" target="_top">&nbsp;-&nbsp;Modules</a></li>
			<li><a href="http://code.google.com/p/kusaba/" target="_top">Project page</a></li>
			<li><a href="http://kusaba.org/doc/index.html" target="main">Code Documentation</a></li>
			</ul>
			
			<script type="text/javascript"><!--
			google_ad_client = "pub-6158454562572132";
			google_ad_width = 120;
			google_ad_height = 600;
			google_ad_format = "120x600_as";
			google_ad_type = "text_image";
			//2007-08-22: Trevorchan
			google_ad_channel = "7008956366";
			google_color_border = "FFFFEE";
			google_color_bg = "FFFFEE";
			google_color_link = "800000";
			google_color_text = "CB7E46";
			google_color_url = "800000";
			google_ui_features = "rc:0";
			//-->
			</script>
			<script type="text/javascript"
			  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
			</script>';
		}
		$smarty->assign('irc', $tpl_irc);
		
		for ($i = 0; $i < 2; $i++) {
			$tpl_boards = '';
			$results_boardsexist = $tc_db->GetAll("SELECT `id` FROM `".KU_DBPREFIX."boards` LIMIT 1");
			if (count($results_boardsexist)==0) {
				$tpl_boards .= '<ul>' . "\n" .
				'	<li>'._gettext('No visible boards').'</li>' . "\n" .
				'</ul>' . "\n";
			} else {
				$results = $tc_db->GetAll("SELECT * FROM `".KU_DBPREFIX."sections` ORDER BY `order` ASC");
				foreach($results AS $line) {
					$tpl_boards .= '<h2><span class="plus" onclick="toggle(this, \''.$line['abbreviation'].'\');" title="'._gettext('Click to show/hide').'">';
					if ($line['hidden']==1) {
						$tpl_boards .= '+';
					} else {
						$tpl_boards .= '&minus;';
					}
					$tpl_boards .= '</span>&nbsp;'.$line['name'].'</h2>' . "\n" .
					'<div id="'.$line['abbreviation'].'" style="';
					if ($line['hidden']==1) {
						$tpl_boards .= 'display: none;';
					}
					$tpl_boards .= '">' . "\n" .
					'<ul>' . "\n";
					$resultsboard = $tc_db->GetAll("SELECT `name`, `desc`, `locked`, `trial`, `popular` FROM `".KU_DBPREFIX."boards` WHERE `section` = ".$line['id']." ORDER BY `order` ASC");
					if (count($resultsboard)>0) {
						foreach($resultsboard AS $lineboard) {
							$tpl_boards .= '	<li><a href="'.KU_BOARDSPATH.'/'.$lineboard['name'].'/" target="main">';
							if ($lineboard['trial']==1) { $tpl_boards .= '<i>'; }
							if ($lineboard['popular']==1) { $tpl_boards .= '<b>'; }
							if ($i == 1) {
								$tpl_boards .= '/'.$lineboard['name'].'/ - ';
							}
							$tpl_boards .= $lineboard['desc'];
							if ($lineboard['locked']=="1") {
								$tpl_boards .= ' <img src="' . KU_BOARDSPATH . '/css/locked.gif" border="0" alt="Locked">';
							}
							if ($lineboard['trial']==1) { $tpl_boards .= '</i>'; }
							if ($lineboard['popular']==1) { $tpl_boards .= '</b>'; }
							$tpl_boards .= '</a></li>' . "\n";
						}
					} else {
						$tpl_boards .= '	<li>' . "\n" .
						_gettext('No visible boards') . "\n" .
						'</li>' . "\n";
					}
					$tpl_boards .= '</ul>' . "\n" .
					'</div>' . "\n";
				}
			}
			$smarty->assign('boards', $tpl_boards);
			if (KU_MENUSTYLESWITCHER) {
				$showhidedirs = '<li id="sitestyles"><a onclick="javascript:showstyleswitcher();" href="#">[Site Styles]</a></li>'. "\n";
			} else {
				$showhidedirs = '';
			}
			
			if ($savetofile) {
				if ($i == 0) {
					$smarty->assign('redirscript', "\n" . 'if (getCookie(\'tcshowdirs\') == \'yes\') {' . "\n" .
					'	window.location = \'' . KU_WEBPATH . '/' . $files[1] . '\';' . "\n" .
					'}' . "\n");
					$smarty->assign('showhidedirs', $showhidedirs . '<li><a onclick="javascript:showdirs();" href="' . $files[1] . '">['._gettext('Show Directories').']</a></li>');
					file_put_contents(KU_ROOTDIR . $files[0], $smarty->fetch('menu.tpl'));
				} else {
					$smarty->assign('redirscript', '');
					$smarty->assign('showhidedirs', $showhidedirs . '<li><a onclick="javascript:hidedirs();" href="' . $files[0] . '">['._gettext('Hide Directories').']</a></li>');
					file_put_contents(KU_ROOTDIR . $files[1], $smarty->fetch('menu.tpl'));
				}
			} else {
				if ($i == 0) {
					$smarty->assign('redirscript', "\n" . 'if (getCookie(\'tcshowdirs\') == \'yes\') {' . "\n" .
					'	window.location = \'' . KU_WEBPATH . '/' . $files[1] . '\';' . "\n" .
					'}' . "\n");
					$smarty->assign('showhidedirs', $showhidedirs . '<li><a onclick="javascript:showdirs();" href="' . $files[1] . '">['._gettext('Show Directories').']</a></li>');
					$menu_nodirs = $smarty->fetch('menu.tpl');
					if ($option == 'nodirs') {
						return $menu_nodirs;
					}
				} else {
					$smarty->assign('redirscript', '');
					$smarty->assign('showhidedirs', $showhidedirs . '<li><a onclick="javascript:hidedirs();" href="' . $files[0] . '">['._gettext('Hide Directories').']</a></li>');
					$menu_dirs = $smarty->fetch('menu.tpl');
					if ($option == 'dirs') {
						return $menu_dirs;
					}
				}
			}
		}
		
		if (isset($menu_nodirs) && isset($menu_dirs)) {
			return array($menu_nodirs, $menu_dirs);
		}
	}

	function Generate() {
		return $this->GetMenu(true);
	}
	
	function PrintMenu($option = false) {
		if ($option != false) {
			return $this->GetMenu(false, $option);
		} else {
			return $this->GetMenu(false);
		}
	}
}
?>