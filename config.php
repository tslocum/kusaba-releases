<?php

/* Modify these :) */

$dbconnection_host = "sql.yourserver.yoursite.com"; //Your hostname (might be localhost)
$dbconnection_database = "yourdb"; //mySQL database
$dbconnection_user = "yourusername"; //mySQL username
$dbconnection_password = "yourpassword"; //mySQL password
$chan_prefix = ""; //mySQL prefix for all tables (If none, leave blank)
$chan_name = "Trevorchan"; //The name of your board
$chan_fancyname = "<font color=\"#00ccff\">Trevor</font><font color=\"#cc0000\">chan</font>"; //The name of your board including coloring or bolding to make it look pretty
$chan_rootdir = dirname($_SERVER['SCRIPT_FILENAME']); //Full system path of the folder containing trevorchan.php, without trailing slash;  defaults to dirname($_SERVER['SCRIPT_FILENAME']), but can be changed if desired if desired
$chan_boardsdir = ""; //Full system path of the boards server, without trailing slash.  If your boards are in the same place as the rest of Trevorchan, leave it blank.
$chan_webfolder = "/"; //The path from the domain of the board to the folder which Trevorchan is in.  Example: "http://www.yoursite.com/misc/trevorchan/" would have a $chan_webfolder of "/misc/trevorchan"
$chan_boardsfolder = ""; //The path from the boards domain to the folder which the boards are in.  If your boards are in the same place as the rest of Trevorchan, leave it blank.
$chan_webpath = "http://www.yourchan.org"; //The path to the index folder of Trevorchan, without trailing slash
$chan_boardspath = ""; //The path to the server which boards are hosted on.  If your boards are in the same place as the rest of Trevorchan, leave it blank.
$chan_headerurl = "http://www.trevorchan.org/trevorchanheader.jpg"; //Full URL to the header image (or rotation script) to be displayed, can be left blank for no image
$chan_maxppm = 7; //Maximum posts per minute a user can make
$chan_postdelay = 5; //Minimum time in seconds a user must wait before posting again
$chan_randomseed = "ENTER RANDOM LETTERS/NUMBERS HERE"; //Type a bunch of random letters/numbers here, any large amount (35+ characters) will do

/* DO NOT MODIFY BELOW THIS LINE OR ELSE SATAN WILL RISE FROM HELL AND TORTURE YOU FOR ETERNITY UNTIL YOU REALISE WHAT A BAD MISTAKE IT WAS TO EDIT BELOW THIS LINE */
require($chan_rootdir."/inc/lang/en.php");

$chan_version = "0.6";

global $dblink;
if (!isset($dblink)) {
	$dblink = mysql_connect($dbconnection_host,$dbconnection_user,$dbconnection_password);
	if (!$dblink) {
		die('Could not connect to database: '.mysql_error().'<br /><br /><font color="red">Maybe you haven\'t configured the database section of config.php properly?</font>');
	}
	mysql_select_db($dbconnection_database,$dblink);
}

if ($chan_webfolder=="/") {
	$chan_webfolder = "";
}
if ($chan_boardsfolder=="/") {
	$chan_boardsfolder = "";
}
if ($chan_boardsdir=="") {
	$chan_boardsdir = $chan_rootdir;
}
if ($chan_boardsfolder=="") {
	$chan_boardsfolder = $chan_webfolder;
}
if ($chan_boardspath=="") {
	$chan_boardspath = $chan_webpath;
}

?>