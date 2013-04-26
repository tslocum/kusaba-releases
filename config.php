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
 * Script configuration
 * +------------------------------------------------------------------------------+
 * Tells the script what to call itself, where the database and other things are
 * located, and a few other configurations.
 * +------------------------------------------------------------------------------+
 */
 
/*
To enable a feature, change the value to true:
	define('TC_INSTANTREDIRECT', true);
To disable a feature, change the value to false:
	define('TC_INSTANTREDIRECT', false);

To change the text value of a configuration, edit the text in the single quotes:
	define('TC_NAME', 'Trevorchan');
Becomes:
	define('TC_NAME', 'Mychan');
Warning: Do not insert single quotes in the value yourself, or else you will cause problems.  To overcome this, you use what is called escaping, which is the process of adding a backslash before the single quote, to show it is part of the string:
	define('TC_NAME', 'Jason\'s chan');

The postbox is where you mix dynamic values with your own text.  The text from what you enter is then parsed and will be displayed under the postbox on each board page and thread page:
	define('TC_POSTBOX', '<ul><li>Supported file types are: <!tc_filetypes /></li><li>Maximum file size allowed is <!tc_maximagekb /> KB.</li><li>Images greater than <!tc_maxthumbwidth />x<!tc_maxthumbheight /> pixels will be thumbnailed.</li><li>Currently <!tc_uniqueposts /> unique user posts.</li></ul>');
Will become (if you had my settings):
	* Supported file types are: GIF, JPG, PNG
	* Maximum file size allowed is 1000 KB.
	* Images greater than 200x200 pixels will be thumbnailed.
	* Currently 221 unique user posts.
Possible values you may use:
	<!tc_filetypes />
	<!tc_maximagekb />
	<!tc_maxthumbwidth />
	<!tc_maxthumbheight />
	<!tc_uniqueposts />
/*
	

/* Database */
	define('TC_DBTYPE', 'mysql'); /* Database type (mysql is the only type tested, use others at own risk) */
	define('TC_DBHOST', 'sql.yourserver.yoursite.com'); /* Database hostname */
	define('TC_DBDATABASE', 'tcdb'); /* Database... database */
	define('TC_DBUSERNAME', 'dbuser'); /* Database username */
	define('TC_DBPASSWORD', 'dbpassword'); /* Database password */
	define('TC_DBPREFIX', ''); /* Database table prefix */
	define('TC_DBUSEPERSISTENT', false); /* Use persistent connection to database */

/* Chan info */
	define('TC_NAME', 'Trevorchan'); /* The name of your site */
	define('TC_FANCYNAME', '<font color="#00ccff">Trevor</font><font color="#cc0000">chan</font>'); /* The name of your site including coloring and bolding to make it look pretty */
	define('TC_HEADERURL', 'http://www.trevorchan.org/trevorchanheader_2c.png'); /* Full URL to the header image (or rotation script) to be displayed, can be left blank for no image */
	define('TC_IRC', '<a href="irc://irc.zirc.org/trevorchan" title="#trevorchan IRC">#trevorchan @ irc.zirc.org</a>'); /* IRC info, which will be displayed in the menu.  Leave blank to remove it */

/* Paths and URLs */
	define('TC_ROOTDIR', dirname(__FILE__).'/'); /* Full system path of the folder containing trevorchan.php, with trailing slash;  defaults to dirname(__FILE__).'/', but can be changed if desired */
	define('TC_BOARDSDIR', TC_ROOTDIR); /* Full system path of the boards server, with trailing slash.  If your boards are in the same place as the rest of Trevorchan, leave it as TC_ROOTDIR. */
	define('TC_WEBFOLDER', '/'); /* The path from the domain of the board to the folder which Trevorchan is in, including the trailing slash.  Example: "http://www.yoursite.com/misc/trevorchan/" would have a TC_WEBFOLDER of "/misc/trevorchan/" */
	define('TC_BOARDSFOLDER', TC_WEBFOLDER); //The path from the domain of the board to the folder which the boards are in.  If your boards are in the same place as the rest of Trevorchan, leave it as TC_WEBFOLDER. */
	define('TC_WEBPATH', 'http://www.yourchan.org'); /* The path to the index folder of Trevorchan, without trailing slash */
	define('TC_BOARDSPATH', TC_WEBPATH); /* The path to the server which boards are hosted on.  If your boards are in the same place as the rest of Trevorchan, leave it as TC_WEBPATH. */

/* Limitations */
	define('TC_NEWTHREADDELAY', 5); /* Minimum time in seconds a user must wait before posting a new thread again */
	define('TC_REPLYDELAY', 5); /* Minimum time in seconds a user must wait before posting a reply again */
	define('TC_MAXCHAR', 100); /* Maximum number of characters in a row before forcing a linebreak in a post */

/* Image handling */
	define('TC_THUMBWIDTH', 200); /* Maximum thumbnail width */
	define('TC_THUMBHEIGHT', 200); /* Maximum thumbnail height */

/* Post handling */
	define('TC_NEWWINDOW', true); /* When a user clicks a thumbnail, whether to open the link in a new window or not */
	define('TC_MAKELINKS', true); /* Whether or not to turn http:// links into clickable links */
	define('TC_ANONYMOUS', 'Anonymous'); /* Name to display in case of the user not entering a name, or the board being set to forced-anonymous */

/* Post display */
	define('TC_THREADS', 10); /* Number of threads to display on a board page */
	define('TC_REPLIES', 3); /* Number of replies to display on a board page */
	define('TC_REPLIESSTICKY', 1); /* Number of replies to display on a board page when a thread is stickied */

/* Pages */
	define('TC_POSTBOX', '<ul><li>Supported file types are: <!tc_filetypes /></li><li>Maximum file size allowed is <!tc_maximagekb /> KB.</li><li>Images greater than <!tc_maxthumbwidth />x<!tc_maxthumbheight /> pixels will be thumbnailed.</li><li>Currently <!tc_uniqueposts /> unique user posts.</li></ul>'); /* Notice displayed under the post area */
	define('TC_FIRSTPAGE', 'board.html'); /* Filename of the first page of a board.  Only change this if you are willing to maintain the .htaccess files for each board directory (they are created with a DirectoryIndex board.html, change them if you change this) */

/* Extra features */
	define('TC_ADDBANMSG', true); /* Whether or not to add (USER WAS BANNED FOR THIS POST) in red text to a message when a user is banned for it */
	define('TC_INSTANTREDIRECT', true); /* Whether or not to instantly redirect the user when posting.  If set to false, the user will be redirected after seeing a notification page */
	define('TC_RSS', true); /* Whether or not to enable the generation of rss for each board and modlog */
	define('TC_DNSBL', true); /* Whether or not to enable DNS Block */
	define('TC_EXPAND', true); /* Whether or not to add the expand button to threads viewed on board pages */
	define('TC_QUICKREPLY', true); /* Whether or not to add quick reply links on posts */
	define('TC_WATCHTHREADS', true); /* Whether or not to add thread watching capabilities */

/* Misc config */
	define('TC_MODLOGDAYS', 7); /* Days to keep modlog entries before removing them */
	define('TC_RANDOMSEED', 'ENTER RANDOM LETTERS/NUMBERS HERE'); /* Type a bunch of random letters/numbers here, any large amount (35+ characters) will do */

/* Language / timezone */
	define('TC_LOCALE', 'en'); /* The locale of Trevorchan you would like to use.  Locales available: en, de, no, es */
	putenv('TZ=US/Pacific'); /* The time zone which the server resides in */
	
/* Debug */
	define('TC_DEBUG', false); /* When enabled, debug information will be printed (Warning: all queries will be shown publicly) */

/* DO NOT MODIFY BELOW THIS LINE UNLESS YOU KNOW WHAT YOU ARE DOING OR ELSE BAD THINGS MAY HAPPEN */
define('PROJECT_DIR', realpath('./'));
define('LOCALE_DIR', TC_ROOTDIR.'inc/lang');
define('DEFAULT_LOCALE', TC_LOCALE);
$modules_loaded = array();

require(TC_ROOTDIR.'lib/gettext/gettext.inc.php');
require(TC_ROOTDIR.'lib/adodb/adodb.inc.php');

define('TC_VERSION', '0.9.4');

$supported_locales = array('en', 'de', 'no', 'es');
$encoding = 'UTF-8';
$locale = TC_LOCALE;
T_setlocale(LC_MESSAGES, $locale);
$domain = 'trevorchan';
bindtextdomain($domain, LOCALE_DIR);
if (function_exists('bind_textdomain_codeset')) {
	bind_textdomain_codeset($domain, $encoding);
}
textdomain($domain);

if (!isset($tc_db) && !isset($preconfig_db_unnecessary)) {
	$tc_db = &NewADOConnection(TC_DBTYPE);
	if (TC_DBUSEPERSISTENT) {
		$tc_db->PConnect(TC_DBHOST, TC_DBUSERNAME, TC_DBPASSWORD, TC_DBDATABASE);
	} else {
		$tc_db->Connect(TC_DBHOST, TC_DBUSERNAME, TC_DBPASSWORD, TC_DBDATABASE);
	}
}

if (TC_DEBUG) {
	$tc_db->debug = true;
}

/* Thanks Z */
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
