<?php

/* Modify these :) */

$dbconnection_host = "sql.yourserver.yoursite.com"; //Your hostname (might be localhost)
$dbconnection_database = "yourdb"; //mySQL database
$dbconnection_user = "yourusername"; //mySQL username
$dbconnection_password = "yourpassword"; //mySQL password
$chan_name = "Trevorchan"; //The name of your board
$chan_fancyname = "<font color=\"#00ccff\">Trevor</font><font color=\"#cc0000\">chan</font>"; //The name of your board including coloring and bolding to make it look pretty
$chan_rootdir = dirname($_SERVER['SCRIPT_FILENAME']); //Full system path of the folder containing board.php, without trailing slash;  defaults to dirname($_SERVER['SCRIPT_FILENAME']), but can be changed if desired
$chan_webfolder = "/trevorchan"; //From the webpath below, the rest of the url to get to the directory trevorchan is in, without trailing slash
$chan_webpath = "http://trevorchan.yoursite.com"; //Web path for the image board, without trailing slash
$chan_headerurl = "http://trevorchan.yoursite.com/trevorchanheader.jpg"; //URL to the header image (or rotation script) to be displayed, can be left blank for no image
$chan_maxppm = 7; //Maximum posts per minute a user can make
$chan_postdelay = 5; //Minimum time in seconds a user must wait before posting again
$chan_randomseed = "RANDOM NUMBERS/LETTERS HERE ELKFORKJEGORKTEORPDKFRg435894095HERDJetertgioerj"; //Type a bunch of random letters/numbers here, any large amount (35+ characters) will do

/* DO NOT MODIFY BELOW THIS LINE OR ELSE SATAN WILL RISE FROM HELL AND TORTURE YOU FOR ETERNITY UNTIL YOU REALISE WHAT A BAD MISTAKE IT WAS TO EDIT BELOW THIS LINE */

$chan_version = "0.3";

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

?>