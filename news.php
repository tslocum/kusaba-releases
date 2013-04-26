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
 * +------------------------------------------------------------------------------+
 * News display,which is the first page shown when a user visits a chan's index
 * +------------------------------------------------------------------------------+
 * Any news added by an administrator in the manage panel will show here,with the
 * newest entry on the top.
 * +------------------------------------------------------------------------------+
 */
require('config.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TC_NAME; ?></title>
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
<link rel="shortcut icon" href="/favicon.ico" />
</head>

<body>
<div style="text-align:center;"><h1><?php echo TC_FANCYNAME; ?></h1></div>

<?php
if (!isset($_GET['p'])) {
$_GET['p'] = '';
}

echo '<div class="menu">';

echo ($_GET['p']=='') ? 'News' : '<a href="news.php">News</a>';
echo ' | ';
echo ($_GET['p']=='faq') ? 'FAQ' : '<a href="news.php?p=faq">FAQ</a>';
echo ' | ';
echo ($_GET['p']=='rules') ? 'Rules' : '<a href="news.php?p=rules">Rules</a>';

echo '</div>';

if ($_GET['p']=='faq') {
echo file_get_contents(TC_ROOTDIR.'inc/pages/faq.html');
} else if ($_GET['p']=='rules') {
echo file_get_contents(TC_ROOTDIR.'inc/pages/rules.html');
} else {
/* Get all of the news entries, ordered with the newest one placed on top */
$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."news` ORDER BY `postedat` DESC");
foreach($results AS $line) {
	echo '<div class="content">
	<h2>'.stripslashes($line['subject']).' by ';
	/* If the message had an email attached to it, add the proper html to link to it */
	if ($line['postedemail']!="") {
		echo '<a href="mailto:'.stripslashes($line['postedemail']).'">';
	}
	echo stripslashes($line['postedby']);
	if ($line['postedemail']!="") {
		echo '</a>';
	}
	echo ' - '.date("n/j/y @ g:iA T", $line['postedat']);
	echo '</h2>
	'.stripslashes($line['message']).'</div>';
}
}

/* Don't worry about this, it only applies to my personal installation of Trevorchan */
if ($tc_config['is_trevorchan']) {
	echo '<script src="http://www.google-analytics.com/urchin.js" type="text/javascript">
	</script>
	<script type="text/javascript">
	_uacct = "UA-71983-8";
	urchinTracker();
	</script>';
}
?>
</body>
</html>