<?php

require("config.php");

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Frameset//EN" "http://www.w3.org/TR/html4/frameset.dtd">
<html>
<head>
<title><?php echo $chan_name; ?></title>
<link rel="shortcut icon" href="/favicon.ico" />
</head>
<frameset cols="15%,*" frameborder="0" border="0">
<frame src="menu.php" name="menu">
<frame src="news.php" name="main">
</frameset>

<noframes>
Your browser doesn't support frames, which <?php echo $chan_name; ?> requires.<br>
Please upgrade to something newer.
</noframes>
</html>