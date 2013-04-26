<?php
if (!function_exists("chan_header")) {
	function chan_header($board) {
		require("config.php");
		$output = "";
		if ($board!="") {
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$board_id = $line['id'];
					$board_dir = $line['name'];
					$board_desc = $line['desc'];
					$board_image = $line['image'];
					$board_includeheader = $line['includeheader'];
				}
			} else {
				die("Invlaid board ID.");
			}
		} else {
			die("Invlaid board ID.");
		}
		$output .= '
		<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html>
		<head>
		<title>/'.$board_dir.'/ - '.$board_desc.'</title>
		<style type="text/css">
		body { margin: 0; padding: 8px; margin-bottom: auto; } blockquote blockquote { margin-left: 0em } form { margin-bottom: 0px } form .trap { display:none } .postarea { text-align: center } .postarea table { margin: 0px auto; text-align: left } .thumb { border: none; float: left; margin: 2px 20px } .nothumb { float: left; background: #eee; border: 2px dashed #aaa; text-align: center; margin: 2px 20px; padding: 1em 0.5em 1em 0.5em; } .reply blockquote, blockquote :last-child { margin-bottom: 0em } .reflink a { color: inherit; text-decoration: none } .reply .filesize { margin-left: 20px } .userdelete { float: right; text-align: center; white-space: nowrap } .replypage .replylink { display: none }
		.admin { color: purple;	font-weight:normal; }
		.mod { color: red; font-weight:normal; }
		</style>';
		$output .= print_stylesheets('Futaba');
		$output .= '<link rel="shortcut icon" href="'.$chan_webpath.'/favicon.ico">
		<meta http-equiv="cache-control" content="no-cache">
		<meta http-equiv="pragma" content="no-cache">
		<meta http-equiv="expires" content="-1">
		<script type="text/javascript">var style_cookie="tcstyle";</script>
		<script type="text/javascript" src="'.$chan_boardspath.'/javascript.js">
		</script>
		</head>
		<body>
		<div class="adminbar">[<a href="javascript:set_stylesheet(\'Burichan\')">Burichan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Futaba\')">Futaba</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Gurochan\')">Gurochan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Photon\')">Photon</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Fuhrerchan\')">Fuhrerchan</a>]&nbsp;-&nbsp;[<a href="'.$chan_webpath.'" target="_top">Home</a>]&nbsp;[<a href="'.$chan_boardspath.'/manage.php">Manage</a>]</div>';
		$output .= display_boardlist();
		$output .= '<div class="logo">';
		if ($board_image=="") {
			if ($chan_headerurl!="") {
				$output .= '<img src="'.$chan_headerurl.'" alt="Logo" /><br />';
			}
		} else if ($board_image!=""&&$board_image!="none") {
			$output .= '<img src="'.$board_image.'" alt="Logo" /><br />';
		}
		$output .= '/'.$board_dir.'/ - '.$board_desc.'</div>';
		$output .= stripslashes(stripslashes($board_includeheader));
		$output .= '<hr />';
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
?>