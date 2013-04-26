<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title>{$lang_manageboards}</title>
{literal}
<style type="text/css">
body { font-family: sans-serif; font-size: 75%; background: #ffe }
a { text-decoration: none; color: #550 }
h1, h2 { margin: 0px; background: #fca }
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
{/literal}
<link rel="shortcut icon" href="{$ku_webpath}/favicon.ico">
{literal}
<script type="text/javascript">
function toggle(button, area) {
	var tog=document.getElementById(area);
	if(tog.style.display)    {
		tog.style.display="";
	}    else {
		tog.style.display="none";
	}
	button.innerHTML=(tog.style.display)?'+':'&minus;';
	createCookie('nav_show_'+area, tog.style.display?'0':'1', 365);
}
</script>
{/literal}
<base target="manage_main" />
</head>
<body>
<h1>{$lang_manageboards}</h1>
<ul>
	{$links}
</ul>
</body>
</html>