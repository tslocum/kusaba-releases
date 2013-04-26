<?php

session_start();

require("config.php");

?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="pl" lang="pl">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<title><?php echo $chan_name; ?></title>
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
</head>

<body>
<div style="text-align:center;"><h1><?php echo $chan_fancyname; ?></h1></div>
<div class="menu">

News | <a href="#">Blog</a> | <a href="#">FAQ</a> | <a href="#">Rules</a></div>
<?php
$result = mysql_query("SELECT * FROM `news` ORDER BY `postedat` DESC",$dblink);
while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
?>
<div class="content">
<h2><?php echo stripslashes($line['subject']); ?> by <?php if ($line['postedemail']!="") { echo '<a href="mailto:'.stripslashes($line['postedemail']).'">'; } echo stripslashes($line['postedby']); if ($line['postedemail']!="") { echo '</a>'; } ?> - <?php echo date("n/j/y @ g:iA T",$line['postedat']); ?></h2>

<?php echo stripslashes($line['message']); ?></div>
<?php } ?>

</body>
</html>
