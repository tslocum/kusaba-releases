<?php
require("../config.php");
if ($_GET['board']!="") {
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_GET['board']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_id = $line['id'];
			$board_dir = $line['name'];
			$board_desc = $line['desc'];
		}
	} else {
		die("Invlaid board ID.");
	}
} else {
	die("Invlaid board ID.");
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
<title><?php echo '/'.$board_dir.'/ - '.$board_desc; ?></title>
<style type="text/css">
body { margin: 0; padding: 8px; margin-bottom: auto; } blockquote blockquote { margin-left: 0em } form { margin-bottom: 0px } form .trap { display:none } .postarea { text-align: center } .postarea table { margin: 0px auto; text-align: left } .thumb { border: none; float: left; margin: 2px 20px } .nothumb { float: left; background: #eee; border: 2px dashed #aaa; text-align: center; margin: 2px 20px; padding: 1em 0.5em 1em 0.5em; } .reply blockquote, blockquote :last-child { margin-bottom: 0em } .reflink a { color: inherit; text-decoration: none } .reply .filesize { margin-left: 20px } .userdelete { float: right; text-align: center; white-space: nowrap } .replypage .replylink { display: none }
</style>
<link rel="stylesheet" type="text/css" href="/css/futaba.css" title="Futaba" />
<link rel="shortcut icon" href="/favicon.ico" />
<meta http-equiv="cache-control" content="no-cache" />
<meta http-equiv="pragma" content="no-cache" />
<meta http-equiv="expires" content="-1" />
<script type="text/javascript" src="/javascript.js">
</script>
</head>
<body>
<div class="logo"><?php if ($chan_headerurl!="") { echo '<img src="'.$chan_headerurl.'" alt="Logo" /><br />'; } echo '/'.$board_dir.'/ - '.$board_desc; ?></div><hr />