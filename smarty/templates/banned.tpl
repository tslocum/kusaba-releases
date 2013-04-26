<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<title>{$title}</title>
{literal}
<style type="text/css">
body { background: #ffe; color: #000; font: normal small Verdana,Tahoma,sans-serif; margin: 0 }
a { text-decoration: none; color: #550; }
h1 { text-align: center; margin-top: 1em; color: #800; }
h2 { font-size: small; color: #800; background: #ea8; margin: 0px; margin-top: 1em; }
</style>
{/literal}
<link rel="shortcut icon" href="{$ku_webpath}/favicon.ico">
</head>
<h1>{$ku_name}</h1>
<div style="margin: 3em;">
	<h2>{$youarebanned}</h2>
	<img src="{$ku_boardspath}/youarebanned.jpg" style="float: right;" alt=":'(">
	You have been banned from posting on <b>{$boards}</b> for the following reason:<br><br>
	<b>{$reason}</b><br><br>
	Your ban was placed on <b>{$at}</b>, and {$expires}.<br><br>
	Your IP address is <b>{$ip}</b>.<br><br>
	{$appeal}
</div>
</body>
</html>