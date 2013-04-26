<?php
/*
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

require("config.php");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title><?php echo TC_NAME; ?></title>
<link rel="shortcut icon" href="/favicon.ico">
</head>
<frameset cols="15%,*" frameborder="0" style="border: 0;">
<frame src="menu.php" name="menu">
<frame src="news.php" name="main">
<noframes>
Your browser doesn't support frames, which <?php echo TC_NAME; ?> requires.<br>
Please upgrade to something newer.
</noframes>
</frameset>
</html>
