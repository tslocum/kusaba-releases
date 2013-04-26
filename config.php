<?php

/* Modify these :) */

$tc_config['dbhost'] = "sql.yourserver.yoursite.com"; //Your hostname (might be localhost)
$tc_config['dbdatabase'] = "tcdb"; //mySQL database
$tc_config['dbusername'] = "dbuser"; //mySQL username
$tc_config['dbpassword'] = "dbpassword"; //mySQL password
$tc_config['dbprefix'] = ''; //Table prefix
$tc_config['name'] = "Trevorchan"; //The name of your board
$tc_config['fancyname'] = "<font color=\"#00ccff\">Trevor</font><font color=\"#cc0000\">chan</font>"; //The name of your board including coloring and bolding to make it look pretty
$tc_config['rootdir'] = dirname($_SERVER['SCRIPT_FILENAME']); //Full system path of the folder containing trevorchan.php, without trailing slash;  defaults to dirname($_SERVER['SCRIPT_FILENAME']), but can be changed if desired
$tc_config['boardsdir'] = ""; //Full system path of the boards server, without trailing slash.  If your boards are in the same place as the rest of Trevorchan, leave it blank.
$tc_config['webfolder'] = "/"; //The path from the domain of the board to the folder which Trevorchan is in.  Example: "http://www.yoursite.com/misc/trevorchan/" would have a $chan_webfolder of "/misc/trevorchan"
$tc_config['boardsfolder'] = ""; //The path from the boards domain to the folder which the boards are in.  If your boards are in the same place as the rest of Trevorchan, leave it blank.
$tc_config['webpath'] = "http://www.yourchan.org"; //The path to the index folder of Trevorchan, without trailing slash
$tc_config['boardspath'] = ""; //The path to the server which boards are hosted on.  If your boards are in the same place as the rest of Trevorchan, leave it blank.
$tc_config['headerurl'] = "http://www.trevorchan.org/trevorchanheader_2c.png"; //Full URL to the header image (or rotation script) to be displayed, can be left blank for no image
$tc_config['maxppm'] = 7; //Maximum posts per minute a user can make
$tc_config['postdelay'] = 5; //Minimum time in seconds a user must wait before posting again
$tc_config['randomseed'] = "ENTER RANDOM LETTERS/NUMBERS HERE"; //Type a bunch of random letters/numbers here, any large amount (35+ characters) will do

/* DO NOT MODIFY BELOW THIS LINE OR ELSE SATAN WILL RISE FROM HELL AND TORTURE YOU FOR ETERNITY UNTIL YOU REALIZE WHAT A BAD MISTAKE IT WAS TO EDIT BELOW THIS LINE */
require($tc_config['rootdir']."/inc/lang/en.php");

$tc_config['version'] = '0.7';

global $tc_config;
if (!isset($tc_config['dblink'])) {
	$tc_config['dblink'] = mysql_connect($tc_config['dbhost'],$tc_config['dbusername'],$tc_config['dbpassword']);
	if (!$tc_config['dblink']) {
		die('Could not connect to database: '.mysql_error().'<br /><br /><font color="red">Maybe you haven\'t configured the database section of config.php properly?</font>');
	}
	mysql_select_db($tc_config['dbdatabase'],$tc_config['dblink']);
}

if ($tc_config['webfolder']=="/") {
	$tc_config['webfolder'] = "";
}
if ($tc_config['boardsfolder']=="/") {
	$tc_config['boardsfolder'] = "";
}
if ($tc_config['boardsdir']=="") {
	$tc_config['boardsdir'] = $tc_config['rootdir'];
}
if ($tc_config['boardsfolder']=="") {
	$tc_config['boardsfolder'] = $tc_config['webfolder'];
}
if ($tc_config['boardspath']=="") {
	$tc_config['boardspath'] = $tc_config['webpath'];
}

//Thanks Z
if (get_magic_quotes_gpc()) {
	foreach ($_GET as $key => $val) {
		$_GET[$key] = stripslashes($val);
	}
	foreach ($_POST as $key => $val) {
		$_POST[$key] = stripslashes($val);
	}
}
if (get_magic_quotes_runtime()) {
	set_magic_quotes_runtime(0);
}

?>
