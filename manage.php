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
 */
/** 
 * Manage panel frameset
 *
 * Tells the browser to load the menu and main page
 * 
 * @package Trevorchan  
 */

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title>Manage Boards</title>
<link rel="shortcut icon" href="/favicon.ico">
</head>
<frameset cols="15%,*" frameborder="0" border="0">
<frame src="manage_menu.php" name="manage_menu">
<frame src="manage_page.php" name="manage_main">
<noframes>
Your browser doesn't support frames, which Trevorchan requires.<br>
Please upgrade to something newer.
</noframes>
</frameset>
</html>