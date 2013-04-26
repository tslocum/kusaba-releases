<?php

/* Modify these :) */

$dbconnection_host = "localhost"; //Your hostname
$dbconnection_database = "tchan"; //mySQL database
$dbconnection_user = "Id10t"; //mySQL username
$dbconnection_password = "weirdal"; //mySQL password
$chan_name = "Testchan"; //The name of your board
$chan_fancyname = "<font color=\"#00ccff\">Test</font><font color=\"#cc0000\">chan</font>"; //The name of your board including coloring and bolding to make it look pretty
$chan_rootdir = $_SERVER['DOCUMENT_ROOT']."/board"; //Full path of the folder containing board.php, without trailing slash;  defaults to $_SERVER['DOCUMENT_ROOT'], but can be changed if desired
$chan_webfolder = "/board";
$chan_webpath = "http://geocities.com".$chan_webfolder; //Web path for the image board, without trailing slash
$chan_headerurl = "http://trevorchan.tj9991.com/trevorchanheader.jpg"; //URL to the header image (or rotation script) to be displayed, can be left blank for no image
$chan_maxppm = 7; //Maximum posts per minute a user can make
$chan_postdelay = 5; //Minimum time in seconds a user must wait before posting again
$chan_randomseed = "ENTER RANDOM LETTERS/NUMBERS HERE"; //Type a bunch of random letters/numbers here, any large amount (35+ characters) will do

/* DO NOT MODIFY BELOW THIS LINE */

$chan_version = "0.1";

global $dblink;
if (!isset($dblink)) {
	$dblink = mysql_connect($dbconnection_host,$dbconnection_user,$dbconnection_password);
	mysql_select_db($dbconnection_database,$dblink);
}

?>