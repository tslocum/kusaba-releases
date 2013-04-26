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
   * Links to all boards for navigation
   * +------------------------------------------------------------------------------+
   * Boards will be listed, divided up by sections set in the manage panel.  IRC info
   * will also be displayed, if it is set.
   * +------------------------------------------------------------------------------+
   */
require('config.php');
require(TC_ROOTDIR.'inc/functions.php');

if (isset($_GET['showdirs'])) {
    setcookie("tcshowdirs", "yes", time()+604800);
    $_COOKIE['tcshowdirs'] = 'yes';
} elseif (isset($_GET['hidedirs'])) {
    setcookie("tcshowdirs", "", 0);
    $_COOKIE['tcshowdirs'] = '';
} else {
    $_COOKIE['tcshowdirs'] = '';
}

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $chan_name; ?> Navigation</title>
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
<base target="main" />
</head>
<body>
<h1><?php echo TC_NAME; ?></h1>
<ul>
<li><a href="<?php echo TC_WEBPATH; ?>" target="_top"><?php echo _('Front Page'); ?></a></li>
<?php echo '<li><a target="_self" href="';
if ($_COOKIE['tcshowdirs']=='yes') {
    echo '?hidedirs">['._('Hide Directories').']';
} else {
    echo '?showdirs">['._('Show Directories').']';
}
echo '</a></li>'; ?>

</ul>

<?php
$results_boardsexist = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."boards` LIMIT 1");
if (count($results_boardsexist)==0) {
    echo '<ul><li>'._('No visible boards').'</li></ul>';
} else {
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."sections` ORDER BY `order` ASC");
    foreach($results AS $line) {
        echo '<h2><span class="plus" onclick="toggle(this, \''.$line['abbreviation'].'\');" title="'._('Click to show/hide').'">';
        if ($line['hidden']==1) {
            echo '+';
        } else {
            echo '&minus;';
        }
        echo '</span>'.$line['name'].'</h2><div id="'.$line['abbreviation'].'" style="';
        if ($line['hidden']==1) {
            echo 'display: none;';
        }
        echo '"><ul>';
        $resultsboard = $tc_db->GetAll("SELECT `name`, `desc`, `locked` FROM `".TC_DBPREFIX."boards` WHERE `section` = ".$line['id']." ORDER BY `order` ASC");
        if (count($resultsboard)>0) {
            foreach($resultsboard AS $lineboard) {
                echo '<li><a href="'.TC_BOARDSPATH.'/'.$lineboard['name'].'/board.html">';
                if ($_COOKIE['tcshowdirs']=='yes') {
                    echo '/'.$lineboard['name'].'/ - ';
                }
                echo $lineboard['desc'];
                if ($lineboard['locked']=="1") {
                    echo ' <img src="'.TC_BOARDSPATH.'/locked.gif" border="0" alt="Locked" />';
                }
                echo '</a></li>';
            }
        } else {
            echo '<li>'._('No visible boards').'</li>';
        }
        echo '</ul></div>';
    }
}
?>

<?php if (TC_IRC!='') { ?>
<h2>IRC</h2>
<ul>
<li><?php echo TC_IRC; ?></li>
</ul>
<?php } ?>

<?php if ($tc_config['is_trevorchan']) { ?>

<h2>Links</h2>
<ul>
<li><a href="http://code.google.com/p/trevorchan/" target="_top">Project page</a></li>
</ul>

<script type="text/javascript"><!--
google_ad_client = "pub-6158454562572132";
google_ad_width = 120;
google_ad_height = 600;
google_ad_format = "120x600_as";
google_ad_type = "text_image";
//2007-01-07: Trevorchan
google_ad_channel = "7008956366";
google_color_border = "FFFFEE";
google_color_bg = "FFFFEE";
google_color_link = "800000";
google_color_text = "cb7e46";
google_color_url = "800000";
//--></script>
<script type="text/javascript"
  src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
</script>
<?php } ?>

</body>
</html>
