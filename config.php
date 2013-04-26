<?php

/* Modify these :) */

$dbconnection_host = "localhost"; //Your mySQL server hostname
$dbconnection_database = "trevorchan"; //mySQL database
$dbconnection_user = "leetd00d"; //mySQL username
$dbconnection_password = "i4ml33t"; //mySQL password
$chan_name = "Trevorchan"; //The name of your board
$chan_fancyname = "<font color=\"#00ccff\">Trevor</font><font color=\"#cc0000\">chan</font>"; //The name of your board including coloring and bolding to make it look pretty
$chan_rootdir = $_SERVER['DOCUMENT_ROOT']; //Full path of the folder containing board.php, without trailing slash;  (e.g. /home/leetd00d/webhtml/board) - defaults to $_SERVER['DOCUMENT_ROOT'], but can be changed if desired
$chan_webfolder = "/board"; //From the base domain (e.g. http://www.leetd00d.com) how to get to the board; if your board is in the root directory, use "/", or if it is in the 'board' directory, put "/board", or if it is in 'stuff/board', put "/stuff/board", etc
$chan_webpath = "http://www.leetd00d.com/board"; //Web path for the image board, without trailing slash
$chan_headerurl = "http://trevorchan.tj9991.com/trevorchanheader.jpg"; //URL to the header image (or rotation script) to be displayed, can be left blank for no image
$chan_maxppm = 7; //Maximum posts per minute a user can make
$chan_postdelay = 5; //Minimum time in seconds a user must wait before posting again
$chan_randomseed = "ENTER RANDOM LETTERS/NUMBERS HERE"; //Type a bunch of random letters/numbers here, any large amount (35+ characters) will do

/* DO NOT MODIFY BELOW THIS LINE */

$chan_version = "0.2";

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