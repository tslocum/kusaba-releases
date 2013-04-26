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
 */
/** 
 * News display, which is the first page shown when a user visits a chan's index
 *
 * Any news added by an administrator in the manage panel will show here, with
 * the newest entry on the top.
 * 
 * @package Trevorchan  
 */   

/** 
 * Require the configuration file
 */ 
require('config.php');

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo TC_NAME; ?></title>
<link rel="stylesheet" type="text/css" href="<?php echo TC_BOARDSPATH; ?>/css/site.css">
<link rel="shortcut icon" href="/favicon.ico">
</head>

<body>
<?php
if (!isset($_GET['p'])) {
	$_GET['p'] = '';
}

echo '<h1>' . TC_NAME . '</h1>';
if (TC_SLOGAN != '') {
	echo '<h3>"' . TC_SLOGAN . '"</h3>';
}

echo '<div class="menu">';

echo ($_GET['p']=='') ? 'News' : '<a href="news.php">News</a>';
echo ' | ';
if (isset($tc_config)) {
	echo '<a href="download.html">Download</a> | ';
}
echo ($_GET['p']=='faq') ? 'FAQ' : '<a href="news.php?p=faq">FAQ</a>';
echo ' | ';
echo ($_GET['p']=='rules') ? 'Rules' : '<a href="news.php?p=rules">Rules</a>';

/* Don't worry about this, it only applies to my personal installation of Trevorchan */
if ($tc_config['is_trevorchan']) {
	echo '<br><script type="text/javascript"><!--
	google_ad_client = "pub-6158454562572132";
	google_ad_width = 728;
	google_ad_height = 90;
	google_ad_format = "728x90_as";
	google_ad_type = "text_image";
	//2007-08-12: Trevorchan
	google_ad_channel = "7008956366";
	google_color_border = "FFFFEE";
	google_color_bg = "FFFFEE";
	google_color_link = "800000";
	google_color_text = "CB7E46";
	google_color_url = "800000";
	google_ui_features = "rc:6";
	//-->
	</script>
	<script type="text/javascript"
	  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
	</script>';
}

echo '</div>';

if ($_GET['p']=='faq') {
	echo file_get_contents(TC_ROOTDIR.'inc/pages/faq.html');
} else if ($_GET['p']=='rules') {
	echo file_get_contents(TC_ROOTDIR.'inc/pages/rules.html');
} else {
	if (isset($tc_config)) {
		echo '<div class="content" style=""><span style="font-size: 1.5em;"><b>Current release:</b> 0.9.8 - <a href="http://rel.trevorchan.org/Releasev098.zip">Quick Download</a> - For more information, click the Download link above.</span></div>';
	}
	$entries = 0;
	/* Get all of the news entries, ordered with the newest one placed on top */
	$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."news` ORDER BY `postedat` DESC");
	foreach($results AS $line) {
		$entries++;
		echo '<div class="content">
		<h2><span class="newssub">'.stripslashes($line['subject']).' by ';
		/* If the message had an email attached to it, add the proper html to link to it */
		if ($line['postedemail']!="") {
			echo '<a href="mailto:'.stripslashes($line['postedemail']).'">';
		}
		echo stripslashes($line['postedby']);
		if ($line['postedemail']!="") {
			echo '</a>';
		}
		echo ' - '.date("n/j/y @ g:iA T", $line['postedat']);
		echo '</span><span class="permalink"><a href="#' . $line['id'] . '" name="' . $line['id'] . '" title="permalink">#</a></span></h2>
		'.stripslashes($line['message']).'</div><br>';
		if ($entries == 8) {
			if (isset($tc_config)) {
				echo '<div class="content">
				<h2><div class="newssub">Advertisement</div><div class="permalink">&nbsp;</div></h2>
				<div style="text-align: center;">
				<script type="text/javascript"><!--
				google_ad_client = "pub-6158454562572132";
				google_ad_width = 728;
				google_ad_height = 90;
				google_ad_format = "728x90_as";
				google_cpa_choice = "CAEaCGnW6F1dtL5nUDRQLVAMUANQCFAFUARQQw";
				google_ad_channel = "0185701305";
				google_color_border = "FFFFEE";
				google_color_bg = "FFFFEE";
				google_color_link = "800000";
				google_color_text = "CB7E46";
				google_color_url = "800000";
				//-->
				</script>
				<script type="text/javascript" src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
				</script>
				</div>
				</div><br>';
			}
		}
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