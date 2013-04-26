<?php
/*
* +------------------------------------------------------------------------------+
* Script configuration
* +------------------------------------------------------------------------------+
* Tells the script what to call itself, where the database and other things are
* located, and a few other configurations.
* +------------------------------------------------------------------------------+
*/
/* Modify these :) */

define('TC_DBTYPE','mysql'); //Database type (mysql, postgres, etc)
define('TC_DBHOST','sql.yourserver.yoursite.com'); //Database hostname
define('TC_DBDATABASE','tcdb'); //Database... database
define('TC_DBUSERNAME','dbuser'); //Database username
define('TC_DBPASSWORD','dbpassword'); //Database password
define('TC_DBPREFIX',''); //Database table prefix
define('TC_DBUSECACHE',true); //Enable/disable caching.  If you wish to use this, make sure the directory /inc/adodb/cache is chmodded to read+write
define('TC_DBCACHESECS',3600); //Seconds to keep the cache before re-caching.  If you have TC_DBUSECACHE set to false, this will not affect anything
define('TC_DBUSEPERSISTENT',false); //Use persistent connection to database
define('TC_NAME','Trevorchan'); //The name of your board
define('TC_FANCYNAME','<font color="#00ccff">Trevor</font><font color="#cc0000">chan</font>'); //The name of your board including coloring and bolding to make it look pretty
define('TC_ROOTDIR',dirname($_SERVER['SCRIPT_FILENAME']).'/'); //Full system path of the folder containing trevorchan.php, without trailing slash;  defaults to dirname($_SERVER['SCRIPT_FILENAME']).'/', but can be changed if desired
define('TC_BOARDSDIR',TC_ROOTDIR); //Full system path of the boards server, without trailing slash.  If your boards are in the same place as the rest of Trevorchan, leave it as TC_ROOTDIR.
define('TC_WEBFOLDER','/'); //The path from the domain of the board to the folder which Trevorchan is in, including the trailing slash.  Example: "http://www.yoursite.com/misc/trevorchan/" would have a TC_WEBFOLDER of "/misc/trevorchan/"
define('TC_BOARDSFOLDER',TC_WEBFOLDER); //The path from the boards domain to the folder which the boards are in.  If your boards are in the same place as the rest of Trevorchan, leave it as TC_WEBFOLDER.
define('TC_WEBPATH','http://www.yourchan.org'); //The path to the index folder of Trevorchan, without trailing slash
define('TC_BOARDSPATH',TC_WEBPATH); //The path to the server which boards are hosted on.  If your boards are in the same place as the rest of Trevorchan, leave it as TC_WEBPATH.
define('TC_HEADERURL','http://www.trevorchan.org/trevorchanheader_2c.png'); //Full URL to the header image (or rotation script) to be displayed, can be left blank for no image
define('TC_MAXPPM',7); //Maximum posts per minute a user can make
define('TC_POSTDELAY',5); //Minimum time in seconds a user must wait before posting again
define('TC_RANDOMSEED','ENTER RANDOM LETTERS/NUMBERS HERE'); //Type a bunch of random letters/numbers here, any large amount (35+ characters) will do

/* DO NOT MODIFY BELOW THIS LINE OR ELSE SATAN WILL RISE FROM HELL AND TORTURE YOU FOR ETERNITY UNTIL YOU REALIZE WHAT A BAD MISTAKE IT WAS TO EDIT BELOW THIS LINE */
require(TC_ROOTDIR.'inc/lang/en.php');
require(TC_ROOTDIR.'inc/adodb/adodb.inc.php');

define('TC_VERSION','0.8');

if (!isset($tc_db)) {
    $tc_db = &NewADOConnection(TC_DBTYPE);
    if (TC_DBUSEPERSISTENT) {
        $tc_db->PConnect(TC_DBHOST, TC_DBUSERNAME, TC_DBPASSWORD, TC_DBDATABASE);
    } else {
        $tc_db->Connect(TC_DBHOST, TC_DBUSERNAME, TC_DBPASSWORD, TC_DBDATABASE);
    }
    if (TC_DBUSECACHE) {
        $ADODB_CACHE_DIR = TC_ROOTDIR.'inc/adodb/cache';
        $tc_db->cacheSecs = TC_DBCACHESECS;
    }
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
