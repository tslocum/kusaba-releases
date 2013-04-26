<?php

require('config.php');
require($tc_config['rootdir'].'/inc/functions.php');

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
<h1><?php echo $tc_config['name']; ?></h1>
<ul>
<li><a href="<?php echo $tc_config['webpath']; ?>" target="_top">Front Page</a></li>
 
</ul>

<?php
$result_boardsexist = mysql_query("SELECT `id` FROM `".$tc_config['dbprefix']."boards` LIMIT 1",$tc_config['dblink']);
if (mysql_num_rows($result_boardsexist)==0) {
    echo '<ul><li>No visible boards</li></ul>';
} else {
    $result = mysql_query("SELECT `id`,`name`,`abbreviation` FROM `".$tc_config['dbprefix']."sections` ORDER BY `order` ASC",$tc_config['dblink']);
    while ($line = mysql_fetch_assoc($result)) {
        echo '<h2><span class="plus" onclick="toggle(this,\''.$line['abbreviation'].'\');" title="Click to show/hide">&minus;</span>'.$line['name'].'</h2><div id="'.$line['abbreviation'].'" style=""><ul>';
        $resultboard = mysql_query("SELECT `name`,`desc`,`locked` FROM `".$tc_config['dbprefix']."boards` WHERE `section` = ".$line['id']." ORDER BY `order` ASC",$tc_config['dblink']);
        $rows = mysql_num_rows($resultboard);
        if ($rows>0) {
            while ($lineboard = mysql_fetch_assoc($resultboard)) {
                echo '<li><a href="'.$tc_config['boardspath'].'/'.$lineboard['name'].'/board.html">'.$lineboard['desc'];
                if ($lineboard['locked']=="1") {
                    echo ' <img src="'.$tc_config['boardspath'].'/locked.gif" border="0" alt="Locked" />';
                }
                echo '</a></li>';
            }
        } else {
            echo '<li>No visible boards</li>';
        }
        echo '</ul></div>';
    }
}
?>

<?php if (config_getvalue('ircinfo')!='') { ?>
<h2>IRC</h2>
<ul>
<li><?php echo config_getvalue('ircinfo'); ?></li>
</ul>
<?php } ?>

</body>
</html>
