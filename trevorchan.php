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
   * Index page, which gives the frameset
   * +------------------------------------------------------------------------------+
   * This is the first file called when someone visits your *chan's index page.  It
   * tells the browser to load the navigation (menu.php) and news page (news.php) in
   * two frames.
   * +------------------------------------------------------------------------------+
   */
if (file_exists("install.php")) {
	die('You are seeing this message because either you haven\'t ran the install file yet, and can do so <a href="install.php">here</a>, or already have, and <b>must delete it</b>.');
}
$preconfig_db_unnecessary = true;
require('config.php');

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title><?php echo TC_NAME; ?></title>
<link rel="shortcut icon" href="/favicon.ico">
</head>
<frameset cols="15%,*" frameborder="0" border="0">
<frame src="menu.php" name="menu">
<frame src="news.php" name="main">
<noframes>
Your browser doesn't support frames, which <?php echo TC_NAME; ?> requires.<br>
Please upgrade to something newer.
</noframes>
</frameset>
</html>
