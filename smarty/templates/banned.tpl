<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{$title}</title>
<link rel="stylesheet" type="text/css" href="{$ku_boardspath}/css/site_futaba.css" title="Futaba">
<link rel="shortcut icon" href="{$ku_webpath}/favicon.ico">
</head>
<h1>{$ku_name}</h1>
<h3>{$ku_slogan}</h3>
<div style="margin: 3em;">
	<h2>&nbsp;{$youarebanned}</h2>
	<img src="{$ku_boardspath}/youarebanned.jpg" style="float: right;" alt=":'(">
	You have been banned from posting on <b>{$boards}</b> for the following reason:<br><br>
	<b>{$reason}</b><br><br>
	Your ban was placed on <b>{$at}</b>, and {$expires}.<br><br>
	Your IP address is <b>{$ip}</b>.<br><br>
	{$appeal}
</div>
</body>
</html>