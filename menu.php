<?php

require("config.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $chan_name; ?> Navigation</title>
<style type="text/css">
body { font-family: sans-serif; font-size: 75%; background: #ffe }
a { text-decoration: none; color: #550 }
h1,h2 { margin: 0px; background: #fca }
h1 { font-size: 150% }
h2 { font-size: 100%; margin-top: 1em }
.hl { font-style: italic }
.plus { float: right; font-size: 8px; font-weight: normal; padding: 1px 4px 2px 4px; margin: 0px 0px; background: #eb9; color: #000; border: 1px solid #da8; cursor: hand; cursor: pointer }
.plus:hover { background: #da8; border: 1px solid #c97 }
ul { list-style: none; padding-left: 0px; margin: 0px }
li { margin: 0px }
li:hover { background: #fec; }
li a { display: block; width: 100%; }
</style>
<link rel="shortcut icon" href="/favicon.ico" />
<script type="text/javascript">
function toggle(button,area) {
	var tog=document.getElementById(area);
	if(tog.style.display)	{
		tog.style.display="";
	}	else {
		tog.style.display="none";
	}
	button.innerHTML=(tog.style.display)?'+':'&minus;';
	createCookie('nav_show_'+area, tog.style.display?'0':'1', 365);
}
</script>
</head>
<body>
<h1><?php echo $chan_name; ?></h1>
<ul>
<li><a href="<?php echo $chan_webpath; ?>" target="_top">Front Page</a></li>
 
</ul>

<h2><span class="plus" onclick="toggle(this,'img');" title="Click to show/hide">&minus;</span>Image Boards</h2>
<div id="img" style="">
<ul><?php
$result = mysql_query("SELECT * FROM `boards`",$dblink);
$rows = mysql_num_rows($result);
if ($rows>0) {
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo '<li><a href="'.$chan_webpath.'/'.$line['name'].'/board.html" target="main">'.$line['desc'];
		if ($line['locked']=="1") {
			echo ' <img src="/locked.gif" border="0" alt="Locked" />';
		}
		echo '</a></li>';
	}
} else {
	echo '<li>No visible boards</li>';
}
?>
</ul></div>

<h2>IRC</h2>
<ul>
<li><a href="irc://irc.fukt.us/trevorchan" title="#trevorchan IRC">#trevorchan @ irc.fukt.us</a></li>
</ul>
</body>
</html>