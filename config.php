<?php
/*
 * This file is part of kusaba.
 *
 * kusaba is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * kusaba is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * kusaba; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
/** 
 * Script configuration
 *
 * Tells the script what to call itself, where the database and other things are
 * located, along with define what features to enable.
 * 
 * @package kusaba  
 */
/*
To enable a feature, change the value to true:
	define('KU_INSTANTREDIRECT', true);
To disable a feature, change the value to false:
	define('KU_INSTANTREDIRECT', false;

To change the text value of a configuration, edit the text in the single quotes:
	define('KU_NAME', 'kusaba');
Becomes:
	define('KU_NAME', 'Mychan');
Warning: Do not insert single quotes in the value yourself, or else you will cause problems.  To overcome this, you use what is called escaping, which is the process of adding a backslash before the single quote, to show it is part of the string:
	define('KU_NAME', 'Jason\'s chan');

The postbox is where you mix dynamic values with your own text.  The text from what you enter is then parsed and will be displayed under the postbox on each board page and thread page:
	define('KU_POSTBOX', '<ul><li>Supported file types are: <!tc_filetypes /></li><li>Maximum file size allowed is <!tc_maximagekb /> KB.</li><li>Images greater than <!tc_maxthumbwidth />x<!tc_maxthumbheight /> pixels will be thumbnailed.</li><li>Currently <!tc_uniqueposts /> unique user posts.<!tc_catalog /></li></ul>');
Will become (if you had my settings):
	* Supported file types are: GIF, JPG, PNG
	* Maximum file size allowed is 1000 KB.
	* Images greater than 200x200 pixels will be thumbnailed.
	* Currently 221 unique user posts. View catalog
Possible values you may use:
	<!tc_filetypes />
	<!tc_maximagekb />
	<!tc_maxthumbwidth />
	<!tc_maxthumbheight />
	<!tc_uniqueposts />
	<!tc_catalog />
*/
if (!headers_sent()) {
	header('Content-Type: text/html; charset=utf-8');
}

$cf = array();

/* Caching (this needs to be set at the start because if enabled, it skips the rest of the configuration process) */
	$cf['KU_APC'] = false;

$cache_loaded = false;
if ($cf['KU_APC']) {
	if (apc_load_constants('config')) {
		$cache_loaded = true;
	}
}

if (!$cache_loaded) {
	/* Database */
		$cf['KU_DBTYPE']          = 'mysql'; /* Database type (mysql is the only type tested, use others at own risk) */
		$cf['KU_DBHOST']          = 'localhost'; /* Database hostname */
		$cf['KU_DBDATABASE']      = 'kusaba'; /* Database... database */
		$cf['KU_DBUSERNAME']      = 'root'; /* Database username */
		$cf['KU_DBPASSWORD']      = ''; /* Database password */
		$cf['KU_DBPREFIX']        = ''; /* Database table prefix */
		$cf['KU_DBUSEPERSISTENT'] = false; /* Use persistent connection to database */
		
	/* Chan info */
		$cf['KU_NAME']      = 'kusaba'; /* The name of your site */
		$cf['KU_SLOGAN']    = '<em>"slogan!"</em>'; /* Site slogan, set to nothing to disable its display */
		$cf['KU_HEADERURL'] = 'http://www.kusaba.org/kusabaheader.png'; /* Full URL to the header image (or rotation script) to be displayed, can be left blank for no image */
		$cf['KU_IRC']       = ''; /* IRC info, which will be displayed in the menu.  Leave blank to remove it */
	
	/* Paths and URLs */
		/* Main installation directory */
			$cf['KU_ROOTDIR']   = 'CHANGEME'; /* Full system path of the folder containing kusaba.php, with trailing slash */
			$cf['KU_WEBFOLDER'] = 'CHANGEME'; /* The path from the domain of the board to the folder which kusaba is in, including the trailing slash.  Example: "http://www.yoursite.com/misc/kusaba/" would have a $cf['KU_WEBFOLDER'] of "/misc/kusaba/" */
			$cf['KU_WEBPATH']   = 'CHANGEME'; /* The path to the index folder of kusaba, without trailing slash */
			$cf['KU_DOMAIN']    = 'CHANGEME'; /* Used in cookies for the domain parameter.  Should be a period and then the top level domain, which will allow the cookies to be set for all subdomains.  For http://www.randomchan.org, the domain would be .randomchan.org; http://zachchan.freehost.com would be zach.freehost.com */
			
		/* Board subdomain/alternate directory (optional, change to enable) */
			/* DO NOT CHANGE THESE IF YOU DO NOT KNOW WHAT YOU ARE DOING!! */
			$cf['KU_BOARDSDIR']    = $cf['KU_ROOTDIR'];
			$cf['KU_BOARDSFOLDER'] = $cf['KU_WEBFOLDER'];
			$cf['KU_BOARDSPATH']   = $cf['KU_WEBPATH'];
		
		/* CGI subdomain/alternate directory (optional, change to enable) */
			/* DO NOT CHANGE THESE IF YOU DO NOT KNOW WHAT YOU ARE DOING!! */
			$cf['KU_CGIDIR']    = $cf['KU_BOARDSDIR'];
			$cf['KU_CGIFOLDER'] = $cf['KU_BOARDSFOLDER'];
			$cf['KU_CGIPATH']   = $cf['KU_BOARDSPATH'];
			
		/* Coralized URLs (optional, change to enable) */
			$cf['KU_WEBCORAL']    = ''; /* Set to the coralized version of your webpath to enable.  If not set to '', URLs which can safely be cached will be coralized, and will use the Coral Content Distribution Network.  Example: http://www.kusaba.org becomes http://www.kusaba.org.nyud.net, http://www.crapchan.org/kusaba becomes http://www.crapchan.org.nyud.net/kusaba */
			$cf['KU_BOARDSCORAL'] = '';
			
	/* Templates */
		$cf['KU_TEMPLATEDIR']       = $cf['KU_ROOTDIR'] . 'smarty/templates'; /* Smarty templates directory */
		$cf['KU_CACHEDTEMPLATEDIR'] = $cf['KU_ROOTDIR'] . 'smarty/templates_c'; /* Smarty compiled templates directory.  This folder MUST be writable (you may need to chmod it to 755).  Set to '' to disable template caching */
	
	/* CSS styles */
		$cf['KU_STYLES']        = 'burichan:futaba:photon'; /* Styles which are available to be used for the boards, separated by colons, in lower case.  These will be displayed next to [Home] [Manage] if KU_STYLESWIKUHER is set to true */
		$cf['KU_DEFAULTSTYLE']  = 'futaba'; /* If Default is selected in the style list in board options, it will use this style.  Should be lower case */
		$cf['KU_STYLESWITCHER'] = true; /* Whether or not to display the different styles in a clickable switcher at the top of the board */
		
		$cf['KU_TXTSTYLES']        = 'futatxt:buritxt:yotsuba:headline:pseud0ch'; /* Styles which are available to be used for the boards, separated by colons, in lower case */
		$cf['KU_DEFAULTTXTSTYLE']  = 'futatxt'; /* If Default is selected in the style list in board options, it will use this style.  Should be lower case */
		$cf['KU_TXTSTYLESWITCHER'] = true; /* Whether or not to display the different styles in a clickable switcher at the top of the board */
		
		$cf['KU_MENUTYPE']          = 'normal'; /* Type of display for the menu.  normal will add the menu styles and such as it normally would, plain will not use the styles, and will look rather boring */
		$cf['KU_MENUSTYLES']        = 'futaba:burichan'; /* Menu styles */
		$cf['KU_DEFAULTMENUSTYLE']  = 'futaba'; /* Default menu style */
		$cf['KU_MENUSTYLESWITCHER'] = true; /* Whether or not to display the different styles in a clickable switcher in the menu */
		
	/* Limitations */
		$cf['KU_NEWTHREADDELAY'] = 30; /* Minimum time in seconds a user must wait before posting a new thread again */
		$cf['KU_REPLYDELAY']     = 7; /* Minimum time in seconds a user must wait before posting a reply again */
		$cf['KU_LINELENGTH']     = 150; /* Used when cutting long post messages on pages and placing the message too long notification */
	
	/* Image handling */
		$cf['KU_THUMBWIDTH']       = 200; /* Maximum thumbnail width */
		$cf['KU_THUMBHEIGHT']      = 200; /* Maximum thumbnail height */
		$cf['KU_REPLYTHUMBWIDTH']  = 125; /* Maximum thumbnail width (reply) */
		$cf['KU_REPLYTHUMBHEIGHT'] = 125; /* Maximum thumbnail height (reply) */
		$cf['KU_CATTHUMBWIDTH']    = 50; /* Maximum thumbnail width (catalog) */
		$cf['KU_CATTHUMBHEIGHT']   = 50; /* Maximum thumbnail height (catalog) */
		$cf['KU_THUMBMETHOD']      = 'gd'; /* Method to use when thumbnailing images in jpg, gif, or png format.  Options available: gd, imagemagick */
		$cf['KU_ANIMATEDTHUMBS']   = false; /* Whether or not to allow animated thumbnails (only applies if using imagemagick) */
		
	/* Post handling */
		$cf['KU_NEWWINDOW']       = true; /* When a user clicks a thumbnail, whether to open the link in a new window or not */
		$cf['KU_MAKELINKS']       = true; /* Whether or not to turn http:// links into clickable links */
		$cf['KU_NOMESSAGETHREAD'] = ''; /* Text to set a message to if a thread is made with no text */
		$cf['KU_NOMESSAGEREPLY']  = ''; /* Text to set a message to if a reply is made with no text */
	
	/* Post display */
		$cf['KU_THREADS']         = 10; /* Number of threads to display on a board page */
		$cf['KU_THREADSTXT']      = 15; /* Number of threads to display on a text board front page */
		$cf['KU_REPLIES']         = 3; /* Number of replies to display on a board page */
		$cf['KU_REPLIESSTICKY']   = 1; /* Number of replies to display on a board page when a thread is stickied */
		$cf['KU_THUMBMSG']        = false; /* Whether or not to display the "Thumbnail displayed, click image for full size." message on posts with images */
		$cf['KU_BANMSG']          = '<br><font color="#FF0000"><b>(USER WAS BANNED FOR THIS POST)</b></font>'; /* The text to add at the end of a post if a ban is placed and "Add ban message" is checked */
		$cf['KU_TRADITIONALREAD'] = false; /* Whether or not to use the traditional style for multi-quote urls.  Traditional: read.php/board/thread/posts, Non-traditional: read.php?b=board&t=thread&p=posts */
		$cf['KU_YOUTUBEWIDTH']    = 200; /* Width to display embedded YouTube videos */
		$cf['KU_YOUTUBEHEIGHT']   = 164; /* Height to display embedded YouTube videos */
		
	/* Pages */
		$cf['KU_POSTBOX']   = '<ul style="margin-left: 0; margin-top: 0; margin-bottom: 0; padding-left: 0;"><li>Supported file types are: <!tc_filetypes /></li><li>Maximum file size allowed is <!tc_maximagekb /> KB.</li><li>Images greater than <!tc_maxthumbwidth />x<!tc_maxthumbheight /> pixels will be thumbnailed.</li><li>Currently <!tc_uniqueposts /> unique user posts.<!tc_catalog /></li></ul>'; /* Notice displayed under the post area */
		$cf['KU_FIRSTPAGE'] = 'board.html'; /* Filename of the first page of a board.  Only change this if you are willing to maintain the .htaccess files for each board directory (they are created with a DirectoryIndex board.html, change them if you change this) */
		$cf['KU_DIRTITLE']  = false; /* Whether or not to place the board directory in the board's title and at the top of the page.  true would render as "/b/ - Random", false would render as "Random" */
		
	/* File tagging */
		$cf['KU_TAGS'] = array('Japanese' => 'J',
		                       'Anime'    => 'A',
		                       'Game'     => 'G',
		                       'Loop'     => 'L',
		                       'Other'    => '*'); /* Used only in Upload imageboards.  These are the tags which a user may choose to use as they are posting a file.  If you wish to disable tagging on Upload imageboards, set this to '' */
	
	/* Special Tripcodes */
		$cf['KU_TRIPS'] = array('#changeme'  => 'changeme',
		                        '#changeme2' => 'changeme2'); /* Special tripcodes which can have a predefined output.  Do not include the initial ! in the output.  Maximum length for the output is 30 characters.  Set to array(); to disable */
	
	/* Extra features */
		$cf['KU_RSS']             = true; /* Whether or not to enable the generation of rss for each board and modlog */
		$cf['KU_EXPAND']          = true; /* Whether or not to add the expand button to threads viewed on board pages */
		$cf['KU_QUICKREPLY']      = true; /* Whether or not to add quick reply links on posts */
		$cf['KU_WATCHTHREADS']    = true; /* Whether or not to add thread watching capabilities */
		$cf['KU_FIRSTLAST']       = true; /* Whether or not to generate extra files for the first 100 posts/last 50 posts */
		$cf['KU_BLOTTER']         = true; /* Whether or not to enable the blotter feature */
		$cf['KU_SITEMAP']         = false; /* Whether or not to enable automatic sitemap generation (you will still need to link the search engine sites to the sitemap.xml file) */
		$cf['KU_APPEAL']          = ''; /* List of email addresses separated by colons to send ban appeal messages to.  Set to '' to disable the ban appeal system */
		$cf['KU_PINGBACK']        = ''; /* The password to use when making a ping to the chan directory.  Set to '' to disable */
		$cf['KU_PINGBACKDESC']    = ''; /* Description of site to send when making a ping to the chan directory.  This will have no effect if KU_PINGBACK is blank */
		
	/* Misc config */
		$cf['KU_MODLOGDAYS']        = 7; /* Days to keep modlog entries before removing them */
		$cf['KU_RANDOMSEED']        = 'ENTER RANDOM LETTERS/NUMBERS HERE'; /* Type a bunch of random letters/numbers here, any large amount (35+ characters) will do */
		$cf['KU_STATICMENU']        = false; /* Whether or not to generate the menu files as static files, instead of linking to menu.php.  Enabling this will reduce load, however some users have had trouble with getting the files to generate */
		$cf['KU_GENERATEBOARDLIST'] = true; /* Set to true to automatically make the board list which is displayed ad the top and bottom of the board pages, or false to use the boards.html file */
		
	/* Language / timezone / encoding */
		$cf['KU_LOCALE']  = 'en'; /* The locale of kusaba you would like to use.  Locales available: en, de, et, es, fi, pl, nl, nb, ru, it, ja */
		$cf['KU_CHARSET'] = 'UTF-8'; /* The character encoding to mark the pages as.  This must be the same in the .htaccess file (AddCharset charsethere .html and AddCharset charsethere .php) to function properly.  Only UTF-8 and Shift_JIS have been tested */
		putenv('TZ=US/Pacific'); /* The time zone which the server resides in */
		
	/* Debug */
		$cf['KU_DEBUG'] = false; /* When enabled, debug information will be printed (Warning: all queries will be shown publicly) */
	
	/* Post-configuration actions, don't modify these */
		$cf['KU_VERSION']    = '1.0.4';
		$cf['KU_TAGS']       = serialize($cf['KU_TAGS']);
		$cf['KU_TRIPS']      = serialize($cf['KU_TRIPS']);
		$cf['KU_LINELENGTH'] = $cf['KU_LINELENGTH'] * 15;
		
		if (substr($cf['KU_WEBFOLDER'], -2) == '//') { $cf['KU_WEBFOLDER'] = substr($cf['KU_WEBFOLDER'], 0, -1); }
		if (substr($cf['KU_BOARDSFOLDER'], -2) == '//') { $cf['KU_BOARDSFOLDER'] = substr($cf['KU_BOARDSFOLDER'], 0, -1); }
		if (substr($cf['KU_CGIFOLDER'], -2) == '//') { $cf['KU_CGIFOLDER'] = substr($cf['KU_CGIFOLDER'], 0, -1); }
		
		$cf['KU_WEBPATH'] = trim($cf['KU_WEBPATH'], '/');
		$cf['KU_BOARDSPATH'] = trim($cf['KU_BOARDSPATH'], '/');
		$cf['KU_CGIPATH'] = trim($cf['KU_CGIPATH'], '/');
		
		if ($cf['KU_APC']) {
			apc_define_constants('config', $cf);
		}
		while (list($key, $value) = each($cf)) {
			define($key, $value);
		}
		unset($cf);
}

/* DO NOT MODIFY BELOW THIS LINE UNLESS YOU KNOW WHAT YOU ARE DOING OR ELSE BAD THINGS MAY HAPPEN */
$modules_loaded = array();

require KU_ROOTDIR . 'lib/gettext/gettext.inc.php';
require KU_ROOTDIR . 'lib/adodb/adodb.inc.php';

/* Gettext */
_textdomain('kusaba');
_setlocale(LC_ALL, KU_LOCALE);
_bindtextdomain('kusaba', KU_ROOTDIR . 'inc/lang');
_bind_textdomain_codeset('kusaba', KU_CHARSET);

/* MySQL database */
if (!isset($tc_db) && !isset($preconfig_db_unnecessary)) {
	$tc_db = &NewADOConnection(KU_DBTYPE);
	if (KU_DBUSEPERSISTENT) {
		$tc_db->PConnect(KU_DBHOST, KU_DBUSERNAME, KU_DBPASSWORD, KU_DBDATABASE) or die('MySQL database connection error: ' . $tc_db->ErrorMsg());
	} else {
		$tc_db->Connect(KU_DBHOST, KU_DBUSERNAME, KU_DBPASSWORD, KU_DBDATABASE) or die('MySQL database connection error: ' . $tc_db->ErrorMsg());
	}
	
	/* MySQL debug */
	if (KU_DEBUG) {
		$tc_db->debug = true;
	}
	
	$results_events = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "events` WHERE `at` <= " . time());
	if (count($results_events) > 0) {
		if ($tc_db->ErrorMsg() == '') {
			foreach($results_events AS $line_events) {
				if ($line_events['name'] == 'pingback') {
					$tc_db->Execute("UPDATE `" . KU_DBPREFIX . "events` SET `at` = " . (time() + 43200) . " WHERE `name` = 'pingback'");
					if (KU_PINGBACK != '') {
						$daypostcount = 0;
						$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . KU_DBPREFIX . "boards` ORDER BY `name` ASC");
						if (count($results) > 0) {
							foreach ($results as $line) {
								$posts = $tc_db->GetOne("SELECT HIGH_PRIORITY COUNT(*) FROM `" . KU_DBPREFIX . "posts_" . $line['name'] . "` WHERE `postedat` > " . (time() - 86400) . "");
								
								$daypostcount += $posts;
							}
						}
						
						$totalpostcount = 0;
						$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . KU_DBPREFIX . "boards` ORDER BY `name` ASC");
						if (count($results) > 0) {
							foreach ($results as $line) {
								$posts = $tc_db->GetOne("SELECT HIGH_PRIORITY `id` FROM `" . KU_DBPREFIX . "posts_" . $line['name'] . "` ORDER BY `id` DESC LIMIT 1");
								
								$totalpostcount += $posts;
							}
						}
						
						$boards = '';
						/*
						For the above, if you wish to have your boards listed in the Directory, the format is as follows:
						boarddir|boarddesc,boarddir2|boarddesc2
						
						Or:
						boarddir|a long desc with spaces,boarddir2|another description with spaces
						
						A practical example:
						b|Random,c|Comments,sug|Suggestions,sup|Support,trans|Translation Efforts
						
						See http://code.google.com/p/kusaba/wiki/DirectoryBoards for a script to auto-generate the $boards value
						*/
						
						/* Because of DreamHost's WONDERFUL AND HELPFUL security measures, it will fail if I try and supply an urlencoded URL twice */
						$nohttpboardspath = str_replace('http://', '', KU_BOARDSPATH);
						
						$pingback = array(
							'name' => KU_NAME,
							'password' => KU_PINGBACK,
							'version' => KU_VERSION,
							'desc' => KU_PINGBACKDESC,
							'daypostcount' => $daypostcount,
							'postcount' => $totalpostcount,
							'boardspath' => $nohttpboardspath,
							'boards' => $boards,
							'url' => KU_WEBPATH
						);
						
						$ch = curl_init('http://www.kusaba.org/chans.php?dopingback');
						curl_setopt($ch, CURLOPT_POST, 1);
						curl_setopt($ch, CURLOPT_POSTFIELDS, $pingback);
						curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
						curl_setopt($ch, CURLOPT_HEADER, 0);
						curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
						@curl_exec($ch);
						curl_close($ch);
						unset($ch, $pingback, $nohttpboardspath, $boards, $daypostcount, $totalpostcount);
					}
				} elseif ($line_events['name'] == 'sitemap') {
					$tc_db->Execute("UPDATE `" . KU_DBPREFIX . "events` SET `at` = " . (time() + 21600) . " WHERE `name` = 'sitemap'");
					if (KU_SITEMAP) {
						$sitemap = '<?xml version="1.0" encoding="UTF-8"?' . '>' . "\n" .
						'<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n" . "\n";
						
						$results = $tc_db->GetAll("SELECT `name` FROM `" . KU_DBPREFIX . "boards` ORDER BY `name` ASC");
						if (count($results) > 0) {
							foreach($results AS $line) {
								$sitemap .= '	<url>' . "\n" .
								'		<loc>' . KU_BOARDSPATH . '/' . $line['name'] . '/</loc>' . "\n" .
								'		<lastmod>' . date('Y-m-d') . '</lastmod>' . "\n" .
								'		<changefreq>hourly</changefreq>' . "\n" .
								'	</url>' . "\n";
										
								$results2 = $tc_db->GetAll("SELECT `id`, `lastbumped` FROM `" . KU_DBPREFIX . "posts_" . $line['name'] . "` WHERE `parentid` = 0 AND `IS_DELETED` = 0 ORDER BY `lastbumped` DESC");
								if (count($results2) > 0) {
									foreach($results2 AS $line2) {
										$sitemap .= '	<url>' . "\n" .
										'		<loc>' . KU_BOARDSPATH . '/' . $line['name'] . '/res/' . $line2['id'] . '.html</loc>' . "\n" .
										'		<lastmod>' . date('Y-m-d', $line2['lastbumped']) . '</lastmod>' . "\n" .
										'		<changefreq>hourly</changefreq>' . "\n" .
										'	</url>' . "\n";
									}
								}
							}
						}
						
						$sitemap .= '</urlset>';
						
						$fp = fopen(KU_BOARDSDIR . 'sitemap.xml', 'w');
						fwrite($fp, $sitemap);
						fclose($fp);
						
						unset($sitemap, $fp);
					}
				}
			}
		}
		
		unset($results_events, $line_events);
	}
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
