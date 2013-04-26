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
 * Index page, which gives the frameset
 *
 * This is the first file called when someone visits the index page.  It
 * tells the browser to load the navigation (menu.php or menu.html) and news page (news.php) in
 * two frames.
 * 
 * @package kusaba  
 */

if (file_exists("install.php")) {
	die('You are seeing this message because either you haven\'t ran the install file yet, and can do so <a href="install.php">here</a>, or already have, and <b>must delete it</b>.');
}
if (!isset($_GET['info'])) {
	$preconfig_db_unnecessary = true;
}

/** 
 * Require the configuration file
 */ 
require 'config.php';

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title><?php echo KU_NAME; ?></title>
<link rel="shortcut icon" href="/favicon.ico">
<?php
if ($kusabaorg) {
	echo '<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
	</script>
	<script type="text/javascript">
	_uacct = "UA-71983-11";
	urchinTracker(document.referrer);
	</script>';
}
?>
</head>
<?php
if (isset($_GET['info'])) {
	require KU_ROOTDIR . 'inc/functions.php';
	
	echo '<body>';
	
	echo '<h1>General info:</h1><ul>';
	echo '<li>Version: kusaba ' . KU_VERSION . '</li>';
	$bans = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."banlist`");
	echo '<li>Active bans: ' . $bans . '</li>';
	$wordfilters = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."wordfilter`");
	echo '<li>Wordfilters: ' . $wordfilters . '</li>';
	echo '<li>Modules loaded: ';
	$modules = modules_list();
	if (count($modules) > 0) {
		$moduleslist = '';
		foreach ($modules as $module) {
			$moduleslist .= $module . ', ';
		}
		echo substr($moduleslist, 0, -2);
	} else {
		echo 'none';
	}
	echo '</li>';
	echo '</ul>';
	
	echo '</body></html>';
	
	die();
}

$menufile = (KU_STATICMENU) ? 'menu.html' : 'menu.php';
$menusize = (KU_MENUTYPE == 'normal') ? '18%' : '10%';
?>
<frameset cols="<?php echo $menusize; ?>,*" frameborder="0" border="0">
<frame src="<?php echo $menufile; ?>" name="menu" id="menu">
<frame src=news.php name="main" id="main">
<noframes>
Your browser doesn't support frames, which <?php echo KU_NAME; ?> requires.<br>
Please upgrade to something newer.
</noframes>
</frameset>
</html>
