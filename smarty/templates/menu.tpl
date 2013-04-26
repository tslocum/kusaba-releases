<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{$tc_name} Navigation</title>
<link rel="stylesheet" type="text/css" href="{$tc_boardspath}/css/site.css">
<link rel="stylesheet" type="text/css" href="{$tc_boardspath}/css/site_menu.css">
<link rel="shortcut icon" href="{$tc_webpath}/favicon.ico">
{literal}
<script type="text/javascript" src="{/literal}{$tc_webpath}{literal}/lib/javascript/trevorchan.js"></script>
<script type="text/javascript">
function toggle(button, area) {
	var tog=document.getElementById(area);
	if(tog.style.display)    {
		tog.style.display="";
	}    else {
		tog.style.display="none";
	}
	button.innerHTML=(tog.style.display)?'+':'&minus;';
	set_cookie('nav_show_'+area, tog.style.display?'0':'1', 30);
}
function hidedirs() {
	set_cookie('tcshowdirs', '', 30);
	window.location = '{/literal}{$tc_webpath}{literal}/menu.html';
}
function showdirs() {
	set_cookie('tcshowdirs', 'yes', 30);
	window.location = '{/literal}{$tc_webpath}{literal}/menu_dirs.html';
}{/literal}{$redirscript}{literal}
</script>
{/literal}
<base target="main">
</head>
<body>
<h1>{$tc_name}</h1>
<ul>
<li><a href="{$tc_webpath}" target="_top">{$lang_frontpage}</a></li>
{$showhidedirs}
</ul>
{$boards}
{$irc}
</body>
</html>