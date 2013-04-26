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
 * Miscellaneous functions used throughout the script.
 *
 * The functions script has no theme; any and all functions which are used globally
 * will be found in this file.
 * 
 * @package kusaba  
 */

function microtime_float() {
	return array_sum(explode(' ', microtime()));
}

function changeLocale($newlocale) {
	global $CURRENTLOCALE, $EMULATEGETTEXT, $text_domains;
	$CURRENTLOCALE = $newlocale;
	$EMULATEGETTEXT = 1;
	$path = KU_ROOTDIR . 'inc/lang/' . $CURRENTLOCALE . '/' . 'LC_MESSAGES' ."/kusaba.mo";
	$text_domains['kusaba']->path = $path;
	$input = new FileReader($path);
	$text_domains['kusaba']->l10n = new gettext_reader($input, false);
}

/* <3 coda for this wonderful snippet
print $contents to $filename by using a temporary file and renaming it */
function print_page($filename, $contents, $board) {
	global $tc_db;
	
	$tempfile = tempnam(KU_BOARDSDIR . $board . '/res', 'tmp'); /* Create the temporary file */
	$fp = fopen($tempfile, 'w');
	fwrite($fp, $contents);
	fclose($fp);
	/* If we aren't able to use the rename function, try the alternate method */
	if (!@rename($tempfile, $filename)) {
		copy($tempfile, $filename);
		unlink($tempfile);
	}
	
	chmod($filename, 0664); /* it was created 0600 */
}

/**
 * Print all available stylesheets
 *
 * Creates a <link> tag for each stylesheet defined in the config
 *  
 * @param string $prefered_stylesheet Prefered stylesheet, which will be given the type "stylesheet" instead of "alternate stylesheet".  Defaults to KU_DEFAULTSTYLE.
 * @return string HTML formatted stylesheet <link> tags
 */    
function printStylesheets($prefered_stylesheet = KU_DEFAULTSTYLE) {
	global $tc_db;
	$output_stylesheets = '';
	$styles = explode(':', KU_STYLES);
	
	if (!in_array($prefered_stylesheet, $styles)) {
		$prefered_stylesheet = KU_DEFAULTSTYLE;
	}
	
	foreach ($styles as $stylesheet) {
		$output_stylesheets .= '<link rel="';
		if ($stylesheet != $prefered_stylesheet) {
			$output_stylesheets .= 'alternate ';
		}
		$output_stylesheets .= 'stylesheet" type="text/css" href="' . KU_BOARDSPATH . '/css/' . $stylesheet . '.css" title="' . ucfirst($stylesheet) . '">' . "\n";
	}
	
	return $output_stylesheets;
}

function printStylesheetsSite($prefered_stylesheet = KU_DEFAULTMENUSTYLE, $menu = false) {
	global $tc_db;
	$output_stylesheets = '';
	$styles = explode(':', KU_MENUSTYLES);
	
	if (!in_array($prefered_stylesheet, $styles)) {
		$prefered_stylesheet = KU_DEFAULTMENUSTYLE;
	}
	
	foreach ($styles as $stylesheet) {
		$output_stylesheets .= '<link rel="';
		if ($stylesheet != $prefered_stylesheet) {
			$output_stylesheets .= 'alternate ';
		}
		$output_stylesheets .= 'stylesheet" type="text/css" href="' . KU_BOARDSPATH . '/css/site_' . $stylesheet . '.css" title="' . ucfirst($stylesheet) . '">' . "\n";
		if ($menu) {
			$output_stylesheets .= '<link rel="';
			if ($stylesheet != $prefered_stylesheet) {
				$output_stylesheets .= 'alternate ';
			}
			$output_stylesheets .= 'stylesheet" type="text/css" href="' . KU_BOARDSPATH . '/css/sitemenu_' . $stylesheet . '.css" title="' . ucfirst($stylesheet) . '">' . "\n";
		}
	}
	
	$output_stylesheets .= '<script type="text/javascript"><!--' . "\n" .
	'	var style_cookie_site = "kustyle_site";' . "\n" .
	'//--></script>' . "\n" .
	'<script type="text/javascript" src="' . KU_WEBPATH . '/lib/javascript/kusaba.js"></script>' . "\n";
	
	return $output_stylesheets;
}

/**
 * Check if the supplied md5 file hash is currently recorded inside of the database, attached to a non-deleted post
 */
function checkMd5($md5, $board) {
	global $tc_db;
	
	$matches = $tc_db->GetAll("SELECT `id`, `parentid` FROM `".KU_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `IS_DELETED` = 0 AND `filemd5` = '".mysql_real_escape_string($md5)."' LIMIT 1");
	if (count($matches) > 0) {
		$real_parentid = ($matches[0][1] == 0) ? $matches[0][0] : $matches[0][1];
		
		return array($real_parentid, $matches[0][0]);
	} else {
		return false;
	}
}

function cleanBoardName($board) {
	return trim(str_replace('/', '', str_replace('|', '', str_replace(' ', '', $board))));
}

function timeDiff($timestamp,$detailed=false, $max_detail_levels=8, $precision_level='second'){
    $now = time();

    #If the difference is positive "ago" - negative "away"
    ($timestamp >= $now) ? $action = '' : $action = 'ago';
   
    # Set the periods of time
    $periods = array("second", "minute", "hour", "day", "week", "month", "year", "decade");
    $lengths = array(1, 60, 3600, 86400, 604800, 2630880, 31570560, 315705600);

    $diff = ($action == '' ? $timestamp - $now : $now - $timestamp);
   
    $prec_key = array_search($precision_level,$periods);
   
    # round diff to the precision_level
    $diff = round(($diff/$lengths[$prec_key]))*$lengths[$prec_key];
   
    # if the diff is very small, display for ex "just seconds ago"
    if ($diff <= 10) {
        $periodago = max(0,$prec_key-1);
        $agotxt = $periods[$periodago].'s';
        return "$agotxt $action";
    }
   
    # Go from decades backwards to seconds
    $time = "";
    for ($i = (sizeof($lengths) - 1); $i>=0; $i--) {
    	if ($i > 0) {
	        if($diff > $lengths[$i-1] && ($max_detail_levels > 0)) {        # if the difference is greater than the length we are checking... continue
	            $val = floor($diff / $lengths[$i-1]);    # 65 / 60 = 1.  That means one minute.  130 / 60 = 2. Two minutes.. etc
	            $time .= $val ." ". $periods[$i-1].($val > 1 ? 's ' : ' ');  # The value, then the name associated, then add 's' if plural
	            $diff -= ($val * $lengths[$i-1]);    # subtract the values we just used from the overall diff so we can find the rest of the information
	            if(!$detailed) { $i = 0; }    # if detailed is turn off (default) only show the first set found, else show all information
	            $max_detail_levels--;
	        }
        }
    }
 
    # Basic error checking.
    if($time == "") {
        return "Error-- Unable to calculate time.";
    } else {
    	if ($action != '') {
        	return $time.$action;
        } else {
        	return $time;
		}
    }
}

function getBlotter($all = false) {
	global $tc_db;
	
	if (KU_APC) {
		if ($all) {
			$cache_blotter = apc_fetch('blotter|all');
		} else {
			$cache_blotter = apc_fetch('blotter|last4');
		}
		if ($cache_blotter !== false) {
			return $cache_blotter;
		}
	}
	$output = '';
	
	if ($all) {
		$limit = '';
	} else {
		$limit = ' LIMIT 4';
	}
	$results = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "blotter` ORDER BY `id` DESC" . $limit);
	if (count($results) > 0) {
		if ($all) {
			$output .= '<pre>';
		}
		foreach ($results as $line) {
			if ($all && $line['important'] == 1) {
				$output .= '<font style="color: red;">';
			} elseif (!$all) {
				$output .= '<li name="blotterentry" style="display: none;">' . "\n";
				if ($line['important'] == 1) {
					$output .= '	<span style="color: red;">' . "\n" . '	';
				}
				$output .= '	';
			}
			$output .= date('m/d/y', $line['at']) . ' - ' . $line['message'];
			if ($all && $line['important'] == 1) {
				$output .= '</font>' . "\n";
			} elseif (!$all) {
				$output .= "\n";
				if ($line['important'] == 1) {
					$output .= '	</span>' . "\n";
				}
				$output .= '</li>';
			} else {
				$output .= "\n";
			}
			$output .= "\n";
		}
		if ($all) {
			$output .= '</pre>';
		}
	}
	
	if (KU_APC) {
		if ($all) {
			apc_store('blotter|all', $output);
		} else {
			apc_store('blotter|last4', $output);
		}
	}
	
	return $output;
}

function getBlotterLastUpdated() {
	global $tc_db;
	
	return $tc_db->GetOne("SELECT `at` FROM `" . KU_DBPREFIX . "blotter` ORDER BY `id` DESC LIMIT 1");
}

function clearBlotterCache() {
	if (KU_APC) {
		apc_delete('blotter|all');
		apc_delete('blotter|last4');
	}
}

/**
 * Clear cache for the supplied post ID of the supplied board
 * 
 * @param integer $id Post ID
 * @param string $board Board name
 */    
function clearPostCache($id, $board) {
	if (KU_APC) {
		apc_delete('post|' . $board . '|' . $id);
	}
}

/**
 * Convert a board ID to a board name
 * 
 * @param integer $boardid Board ID
 * @return string Board directory
 */    
function boardid_to_dir($boardid) {
	global $tc_db;
	
	$query = "SELECT `name` FROM `".KU_DBPREFIX."boards` WHERE `id` = '".mysql_real_escape_string($boardid)."'";
	$results = $tc_db->SelectLimit($query, 1);
	if (count($results)>0) {
		foreach($results AS $line) {
			return $line['name'];
		}
	}
}

/**
 * Calculate the number of pages which will be needed for the supplied number of posts
 * 
 * @param integer $boardtype Board type
 * @param integer $numposts Number of posts
 * @return integer Number of pages required
 */      
function calculatenumpages($boardtype, $numposts) {
	if ($boardtype==1) {
		return (floor($numposts/15));
	} else {
		return (floor($numposts/KU_THREADS));
	}
}

/**
 * Gets information about the filetype provided, which is specified in the manage panel
 * 
 * @param string $filetype Filetype
 * @return array Filetype image, width, and height
 */
function getfiletypeinfo($filetype) {
	global $tc_db;
	
	$query = "SELECT * FROM `".KU_DBPREFIX."filetypes` WHERE `filetype` = '".mysql_real_escape_string($filetype)."' LIMIT 1";
	$results = $tc_db->GetAll($query);
	foreach($results AS $line) {
		return array($line['image'],$line['image_w'],$line['image_h']);
	}
	
	/* No info was found, return the generic icon */
	return array('generic.png',48,48);
}

/**
 * Add an entry to the modlog
 *
 * @param string $entry Entry text 
 * @param integer $category Category to file under.   0 - No category, 1 - Login, 2 - Cleanup/rebuild boards and html files, 3 - Board adding/deleting, 4 - Board updates, 5 - Locking/stickying, 6 - Staff changes, 7 - Thread deletion/post deletion, 8 - Bans, 9 - News, 10 - Global changes, 11 - Wordfilter
 * @param string $forceusername Username to force as the entry username
 */
function management_addlogentry($entry, $category = 0, $forceusername = '') {
	global $tc_db;
	
	$username = ($forceusername == '') ? $_SESSION['manageusername'] : $forceusername;
	
	if ($entry != '') {
		$tc_db->Execute("INSERT INTO `" . KU_DBPREFIX . "modlog` ( `entry` , `user` , `category` , `timestamp` ) VALUES ( '" . mysql_real_escape_string($entry) . "' , '" . $username . "' , '" . mysql_real_escape_string($category) . "' , '" . time() . "' )");
	}
	if (KU_RSS) {
		require_once(KU_ROOTDIR . 'inc/classes/rss.class.php');
		$rss_class = new RSS();
		
		print_page(KU_BOARDSDIR . 'modlogrss.xml', $rss_class->GenerateModLogRSS($entry), '');
	}
}

/**
 * Run a greater than zero check on each ID in the array
 * 
 * @param array $ids Array of thread IDs
 */   
function validateIds($ids) {
	foreach ($ids AS $id) {
		if ($id <= 0) {
			die("error.  parentid <= 0");
		}
	}
}

/**
 * Generate the form for post deletion
 * 
 * @param string $board Board directory
 * @return string Generated Form tag and board element
 */    
function deletionForm($board) {
	return '<form id="delform" action="' . KU_CGIPATH . '/board.php" method="post">' . "\n" .
	'<input type="hidden" name="board" value="' . $board . '">' . "\n";
}

/**
 * Generate the un-hide thread span
 * 
 * @param integer $id Thread ID 
 * @param string $board Board directory
 * @return string Generated span
 */   
function unHideThreadSpan($id, $board) {
	return '<span id="unhidethread' . $id . $board . '" style="display: none;">' . "\n" .
	'	Thread <a href="' . KU_BOARDSFOLDER . $board . '/res/' . $id . '.html">' . $id . '</a> hidden. ' . "\n" .
	'	<a href="#" onclick="javascript:togglethread(\'' . $id . $board . '\');return false;" title="Un-Hide Thread">' . "\n" .
	'		<img src="' . KU_BOARDSPATH . '/css/icons/blank.gif" border="0" class="unhidethread" alt="unhide">' . "\n" .
	'	</a>' . "\n" .
	'</span>' . "\n";
}

/**
 * Generate the list of pages, linking to each
 * 
 * @param integer $boardpage Current board page 
 * @param integer $pages Number of pages
 * @param string $board Board directory 
 * @return string Generated page list
 */   
function pageList($boardpage, $pages, $board) {
	$output = '<table border="1"><tbody><tr><td>';
					
	if ($boardpage == 0) {
		$output .= _gettext('Previous');
	} else {
		$output .= '<form method="get" action="' . KU_BOARDSFOLDER . $board . '/';
		
		if ($boardpage-1!=0) {
			$output .= ($boardpage - 1) . '.html';
		}
		
		$output .= '"><input value="' . _gettext('Previous') . '" type="submit"></form>';
	}
	
	$output .= '</td><td>';
	
	for ($i=0;$i<=$pages;$i++) {
		if ($boardpage == $i) {
			$output .= '&#91;'.$i.'&#93;';
		} else {
			$output .= '&#91;<a href="' . KU_BOARDSFOLDER . $board . '/';
			
			if ($i != 0) {
				$output .= $i . '.html';
			}
			
			$output .= '">' . $i . '</a>&#93;';
		}
		
		$output .= ' ';
	}
	
	/* Remove the unwanted space */
	$output = substr($output, 0, -1);
	
	$output .= '</td><td>';
	
	if ($boardpage == $pages) {
		$output .= _gettext('Next');
	} else {
		$output .= '<form method="get" action="' . KU_BOARDSFOLDER . $board . '/' . ($boardpage + 1) . '.html"><input value="' . _gettext('Next') . '" type="submit"></form>';
	}
	
	$output .= '</td></tr></tbody></table>';
	
	return $output;
}

/**
 * Format the display of the name and tripcode of a post
 * 
 * @param string $name Name used in post
 * @param string $email Email used in post
 * @param string $tripcode Tripcode used in post
 * @return string Generated name and tripcode html
 */  
function formatNameAndTrip($name, $email, $tripcode) {
	$output = '<span class="postername">' . "\n" . '	';
			
	if ($email != '') {
		$output .= '<a href="mailto:' . $email . '">';
	}
	
	if ($name == '' && $tripcode == '') {
		$output .= KU_ANONYMOUS;
	} else if ($name == '' && $tripcode != '') {
		/* Just display the tripcode, no added html */
	} else {
		$output .= $name;
	}
	
	if ($email != '') {
		$output .= '</a>';
	}
	
	$output .= "\n" . '</span>' . "\n";
	
	if ($tripcode != '') {
		$output .= '<span class="postertrip">' . "\n" .
		'	!' . $tripcode . "\n" .
		'</span>' . "\n";
	}
	
	return $output;
}

/**
 * Format a long message to be shortened if it exceeds the allowed length on a page
 * 
 * @param string $message Post message
 * @param string $board Board directory
 * @param integer $threadid Thread ID
 * @param boolean $page Is rendering for a page 
 * @return string The formatted message
 */ 
function formatLongMessage($message, $board, $threadid, $page) {
	$output = '';
	if ((strlen($message) > KU_LINELENGTH || count(explode('<br>', $message)) > 15) && $page) {
		$message_exploded = explode('<br>', $message);
		$message_shortened = '';
		for ($i = 0; $i <= 14; $i++) {
			if (isset($message_exploded[$i])) {
				$message_shortened .= $message_exploded[$i] . '<br>';
			}
		}
		if (strlen($message_shortened) > KU_LINELENGTH) {
			$message_shortened = substr($message_shortened, 0, KU_LINELENGTH);
		}
		$message_shortened = closeOpenTags($message_shortened);
		
		$output = $message_shortened . '<div class="abbrev">' . "\n" .
		'	' . sprintf(_gettext('Comment too long. Click %shere%s to view the full text.'), '<a href="' . KU_BOARDSFOLDER . $board . '/res/' . $threadid . '.html">', '</a>') . "\n" .
		'</div>' . "\n";
	} else {
		$output .= $message . "\n";
	}
	
	return $output;
}

/**
 * Create a single row for a thread, which will be displayed in the upload imageboard board pages
 * 
 * @param string $post Post data
 * @param string $board Board directory
 * @param integer $maxage Maximum thread age
 * @param integer $replies Number of replies to the thread 
 * @return string Thread row
 */ 
function uploadImageboardPageRow($post, $board, $maxage, $replies) {
	$output = '<tr';
	/* If the thread is two hours or less from being pruned, add the style for old rows */
	if (checkMarkedForDeletion($post, $maxage)) {
		$output .= ' class="replyhl"';
	}
	$output .= '>' . "\n" .
	'<td align="center">' . "\n" .
	'	' . $post['id'] . "\n" .
	'</td>' . "\n" .
	'<td>' . "\n" .
	formatNameAndTrip($post['name'], $post['email'], $post['tripcode']) .
	'</td>' . "\n" .
	'<td align="center">' . "\n" .
	'	[<a href="' . KU_BOARDSFOLDER . $board . '/src/' . $post['filename'] . '.' . $post['filetype'] . '" target="_blank">' . $post['filename'] . '.' . $post['filetype'] . '</a>]' . "\n" .
	'</td>' . "\n";
	
	if ($post['tag'] == '') {
		$post['tag'] = '*';
	}
	
	$output .= '<td align="center">' . "\n" .
	'	[' . $post['tag'] . ']' . "\n" .
	'</td>' . "\n" .
	'<td>' . "\n" .
	'	' . $post['subject'] . "\n" .
	'</td>' . "\n" .
	'<td align="center">' . "\n" .
	'	';
	
	if ($post['filesize_formatted'] == '') {
		$output .= ConvertBytes($post['filesize']);
	} else {
		$output .= $post['filesize_formatted'];
	}
	
	$output .= "\n" . '</td>' . "\n" .
	'<td>' . "\n" .
	'	<nobr>' . date("y/m/d(D)H:i", $post['postedat']) . '</nobr>' . "\n" .
	'</td>' . "\n" .
	'<td align="center">' . "\n" .
	'	' . $replies . "\n" .
	'</td>' . "\n" .
	'<td align="center">' . "\n" .
	'	[<a href="' . KU_BOARDSFOLDER . $board . '/res/' . $post['id'] . '.html">Reply</a>]' . "\n" .
	'</td>' . "\n" .
	'</tr>';
	
	return $output;
}

/**
 * Check if a thread is marked for deletion
 * 
 * @param string $post Post data
 * @param integer $maxage Maximum thread age
 * @return boolean Marked or not
 */ 
function checkMarkedForDeletion($post, $maxage) {
	if (!$post['stickied'] && $post['parentid'] == 0 && (($maxage > 0 && ($post['postedat']  + ($maxage * 3600)) < (time() + 7200)) || ($post['deletedat'] > 0 && $post['deletedat'] <= (time() + 7200)))) {
		return true;
	} else {
		return false;
	}
}

/**
 * Marked for deletion message
 *
 * @return string Marked for deletion (old).
 */ 
function markedForDeletionMessage() {
	return '<span class="oldpost">' . "\n" .
	'	' . _gettext('Marked for deletion (old).') . "\n" .
	'</span>' . "\n" .
	'<br>' . "\n";
}

/**
 * Create thread links which are displayed at the top of thread pages, sometimes on the bottom as well, and also displayed in the thread info row
 *
 * @param integer $type Link type
 * @param integer $threadid Thread ID
 * @param string $board Board directory
 * @param integer $type Board type
 * @param boolean $modifier_last50 Last 50 modifier in effect
 * @param boolean $modifier_first100 First 100 modifier in effect 
 * @param boolean $forcereplymodehide Force the Reply Mode to be hidden
 * @return string Thread links
 */ 
function threadLinks($type, $threadid, $board, $boardtype, $modifier_last50, $modifier_first100, $forcereplymodehide = false) {
	if ($boardtype != 1) {
		$leftbracket = '&#91;';
		$rightbracket = '&#93;';
	} else {
		$leftbracket = '';
		$rightbracket = '';
	}
	
	if ($type == 'return') {
		$output = $leftbracket . '<a href="' . KU_BOARDSFOLDER . $board . '/">' . _gettext('Return') . '</a>' . $rightbracket;
	} elseif ($type == 'page' && $boardtype == 1) {
		$output = '<p class="hidden">' . _gettext('The 5 newest replies are shown below.') . '<br>';
	} elseif ($type == 'page' && $boardtype != 1) {
		$output = $leftbracket . '<a href="' . KU_BOARDSFOLDER . $board . '/res/' . $threadid . '.html">' . _gettext('Reply') . '</a>' . $rightbracket;
	}
	
	if ((KU_FIRSTLAST && $modifier_last50) || $boardtype == 1) {
		if ($type == 'return') {
			$output .= ' ' . $leftbracket;
		}
		
		if ($type == 'return' || ($type == 'page' && $boardtype == 1)) {
			$output .= '<a href="' . KU_BOARDSFOLDER . $board . '/res/' . $threadid . '.html">';
			
			if ($type == 'return') {
				$output .= _gettext('Entire Thread');
			} elseif ($type == 'page') {
				$output .= _gettext('Read this thread from the beginning');
			}
			
			$output .= '</a>';
		}
		
		if ($type == 'return') {
			$output .= $rightbracket;
		}
		
		if ($modifier_first100) {
			$output .= ' ' . $leftbracket . '<a href="' . KU_BOARDSFOLDER . $board . '/res/' . $threadid . '-100.html">' . _gettext('First 100 posts') . '</a>' . $rightbracket;
		}
		
		if ($modifier_last50) {
			$output .= ' ' . $leftbracket . '<a href="' . KU_BOARDSFOLDER . $board . '/res/' . $threadid . '+50.html">' . _gettext('Last 50 posts') . '</a>' . $rightbracket;
		}
	}
	
	if ($boardtype == 1 && $type == 'return') {
		$output .= '<br><br>';
	}elseif ($type == 'page' && $boardtype == 1) {
		$output .= '</p>';
	} elseif ($type != 'page' && $boardtype != 1 && !$forcereplymodehide) {
		$output .= '<div class="replymode">' . _gettext('Posting mode: Reply') . '<!tc_postmodeinfo></div>';
	}
	
	return $output;
}

/**
 * Create the javascript which will check if the user has the thread hidden, and if so, hide the thread and show the un-hide thread span
 *
 * @param integer $id Thread ID
 * @param string $board Board directory
 * @return string Javascript
 */ 
function autoHideThreadJavascript($id, $board) {
	return '<script type="text/javascript"><!--' . "\n" .
	'if (hiddenthreads.toString().indexOf(\'' . $id . $board . '\')!==-1) {' . "\n" .
	'	document.getElementById(\'unhidethread' . $id . $board . '\').style.display = \'block\';' . "\n" .
	'	document.getElementById(\'thread' . $id . $board . '\').style.display = \'none\';' . "\n" .
	'}' . "\n" .
	'//--></script>' . "\n";
}

/**
 * Create the delete post box, displayed at the bottom left of pages in non text boards, or inside the thread in text boards
 *
 * @param integer $boardtype Board type
 * @param boolean $enablereporting Whether enable reporting is enabled
 * @return string Delete post box
 */ 
function deletePostBox($boardtype, $enablereporting) {
	$output = '<table class="';
	if ($boardtype == 1) {
		$output .= 'hborder';
	} else {
		$output .= 'userdelete';
	}
	$output .= '">' . "\n" .
	'<tbody>' . "\n" .
	'<tr>' . "\n" .
	'<td>' . "\n" .
	ucwords(_gettext('Delete post'));
	if ($boardtype == 1) {
		$output .= ':';
	}
	if ($boardtype != 1) {
		$output .= ' [<input type="checkbox" name="fileonly" id="fileonly" value="on"><label for="fileonly">' . _gettext('File Only') . '</label>]<br>' . _gettext('Password');
	}
	$output .= ' <input type="password" name="postpassword" size="8">&nbsp;<input name="deletepost" value="' . _gettext('Delete') . '" type="submit">' . "\n";

	if ($enablereporting == 1) {
		$output .= '<input name="reportpost" value="' . _gettext('Report') . '" type="submit">' . "\n";
	}
	
	$output .= '</td>' . "\n" .
	'</tr>' . "\n" .
	'</tbody>' . "\n" .
	'</table>' . "\n" .
	'</form>' . "\n";
	
	return $output;
}

/**
 * Create the javascript which fills the password boxe for the delete post form
 *
 * @return string Javascript
 */ 
function setDelPassJavascript() {
	return '<script type="text/javascript"><!--' . "\n" .
	'	set_delpass("delform");' . "\n" .
	'//--></script>' . "\n";
}

/**
 * Display the embedded video
 *
 * @param array $post Post data 
 * @return string Embedded video
 */ 
function embeddedVideoBox($post) {
	$output = '<span style="float: left;">';
				
	if ($post['filetype'] == 'you') {
		$output .= '<object width="200" height="164"><param name="movie" value="http://www.youtube.com/v/' . $post['filename'] . '"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/' . $post['filename'] . '" type="application/x-shockwave-flash" wmode="transparent" width="200" height="164"></embed></object>';
	} elseif ($post['filetype'] == 'goo') {
		$output .= '<embed style="width:200px; height:164px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=' . $post['filename'] . '&hl=en" flashvars=""></embed>';
	}
	
	$output .= '</span>&nbsp;' . "\n";
	
	return $output;
}

/**
 * Will check if there is already a cached version of the name string in the database, and if so, get it, instead of recalculating it
 *
 * @param string $post_name Text entered in the Name field 
 * @return array Name and tripcode
 */ 
function calculateNameAndTripcode($post_name) {
	global $tc_db;
	
	if(ereg("(#|!)(.*)", $post_name, $regs)){
		$results = $tc_db->GetAll("SELECT `name`, `tripcode` FROM `".KU_DBPREFIX."passcache` WHERE `md5` = '".md5($post_name)."' LIMIT 1");
		if (isset($results[0])) {
			return array($results[0][0], $results[0][1]);
		} else {
			$cap = $regs[2];
			$cap_full = '#' . $regs[2];
			
			// {{{ Special tripcode check
			
			$trips = unserialize(KU_TRIPS);
			if (count($trips) > 0) {
				if (isset($trips[$cap_full])) {
					$forcedtrip = $trips[$cap_full];
					return array(ereg_replace("(#)(.*)", "", $post_name), $forcedtrip);
				}
			}
			
			// }}}
			
			if (function_exists('mb_convert_encoding')) {
				$recoded_cap = mb_convert_encoding($cap, 'SJIS', 'UTF-8');
				if ($recoded_cap != '') {
					$cap = $recoded_cap;
				}
			}
			
			if (strpos($post_name, '#') === false) {
				$cap_delimiter = '!';
			} elseif (strpos($post_name, '!') === false) {
				$cap_delimiter = '#';
			} else {
				$cap_delimiter = (strpos($post_name, '#') < strpos($post_name, '!')) ? '#' : '!';
			}
			
			if (ereg("(.*)(" . $cap_delimiter . ")(.*)", $cap, $regs_secure)) {
				$cap = $regs_secure[1];
				$cap_secure = $regs_secure[3];
				$is_secure_trip = true;
			} else {
				$is_secure_trip = false;
			}
			
			$tripcode = '';
			if ($cap != '') {
				/* From Futabally */
				$cap = strtr($cap, "&amp;", "&");
				$cap = strtr($cap, "&#44;", ", ");
				$salt = substr($cap."H.", 1, 2);
				$salt = ereg_replace("[^\.-z]", ".", $salt);
				$salt = strtr($salt, ":;<=>?@[\\]^_`", "ABCDEFGabcdef"); 
				$tripcode = substr(crypt($cap, $salt), -10);
			}
			
			if ($is_secure_trip) {
				if ($cap != '') {
					$tripcode .= '!';
				}
				
				$secure_tripcode = md5($cap_secure . KU_RANDOMSEED);
				if (function_exists('base64_encode')) {
					$secure_tripcode = base64_encode($secure_tripcode);
				}
				if (function_exists('str_rot13')) {
					$secure_tripcode = str_rot13($secure_tripcode);
				}
				
				$secure_tripcode = substr($secure_tripcode, 2, 10);
				
				$tripcode .= '!' . $secure_tripcode;
			}
			
			$name = ereg_replace("(" . $cap_delimiter . ")(.*)", "", $post_name);
			
			$tc_db->Execute("INSERT INTO `".KU_DBPREFIX."passcache` ( `md5` , `name` , `tripcode` ) VALUES ( '" . md5($post_name) . "' , '" . $name . "' , '" . $tripcode . "' )");
			
			return array($name, $tripcode);
		}
	} else {
		return $post_name;
	}
}

/* Depending on the configuration, use either a meta refresh or a direct header */
function do_redirect($url) {
	global $board_class;
	
	if (KU_INSTANTREDIRECT) {
		header('Location: ' . $url);
		die();
	} else {
		echo '---> ---> ---><meta http-equiv="refresh" content="1;url=' . $url . '/">';
	}
}

/* Thanks milianw - php.net */
/**
 * Closes all HTML tags left open
 *
 * @param string $html HTML to be checked
 * @return string HTML with all tags closed 
 */ 
function closeOpenTags($html){
	/* Put all opened tags into an array */
	preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $result);
	$openedtags=$result[1];

	/* Put all closed tags into an array */
	preg_match_all("#</([a-z]+)>#iU", $html, $result);
	$closedtags=$result[1];
	$len_opened = count($openedtags);
	/* All tags are closed */
	if(count($closedtags) == $len_opened){
		return $html;
	}
	$openedtags = array_reverse($openedtags);
	/* Close tags */
	for($i=0;$i<$len_opened;$i++) {
		if ($openedtags[$i]!='br') {
			if (!in_array($openedtags[$i], $closedtags)){
				$html .= '</'.$openedtags[$i].'>';
			} else {
				unset($closedtags[array_search($openedtags[$i], $closedtags)]);
			}
		}
	}
	return $html;
}

/**
 * Recursively delete a directory
 *
 * @param string $path Intial path to delete
 */ 
function removeDir($path) {
	$normal_files = glob($path . "*");
	$hidden_files = glob($path . "\.?*");
	$all_files = array_merge($normal_files, $hidden_files);
	
	foreach ($all_files as $file) {
		/* Skip pseudo links to current and parent dirs (./ and ../). */
		if (preg_match("/(\.|\.\.)$/", $file))
		{
				continue;
		}
	
		if (is_file($file) === TRUE) {
			/* Remove each file in this Directory */
			unlink($file);
			echo "Removed File: " . $file . "<br>";
		}
		else if (is_dir($file) === TRUE) {
			/* If this Directory contains a Subdirectory, run this Function on it */
			removeDir($file);
		}
	}
	/* Remove Directory once Files have been removed (If Exists) */
	if (is_dir($path) === TRUE) {
		rmdir($path);
		echo "<br>Removed Directory: " . $path . "<br><br>";
	}
}

/**
 * Remove a board
 *
 * @param string $dir Directory to remove
 * @return boolean Result 
 */ 
function removeBoard($dir){
	global $tc_db;

	if(!isset($GLOBALS['remerror'])) {
		$GLOBALS['remerror'] = false;
	}

	if($handle = opendir(KU_BOARDSDIR . $dir)){ /* If the folder exploration is sucsessful, continue */
		while (false !== ($file = readdir($handle))){ /* As long as storing the next file to $file is successful, continue */
			$path = $dir . '/' . $file;
		
			if(is_file(KU_BOARDSDIR . $path)){
				if(!unlink(KU_BOARDSDIR . $path)){
					echo '<u><font color="red">"' . $path . '" could not be deleted. This may be due to a permissions problem.</u><br>Directory cannot be deleted until all files are deleted.</font><br>';
					$GLOBALS['remerror'] = true;
					return false;
				}
			} else
				if(is_dir(KU_BOARDSDIR . $path) && substr($file, 0, 1) != '.'){
					removeBoard($path);
					@rmdir(KU_BOARDSDIR . $path);
				}
		}
		closedir($handle); /* Close the folder exploration */
	}
	
	if(!$GLOBALS['remerror']) /* If no errors occured, delete the now empty directory */
		if(!rmdir(KU_BOARDSDIR . $dir)){
			echo '<b><font color="red">Could not remove directory "' . $dir . '". This may be due to a permissions problem.</font></b><br>'.$GLOBALS['remerror'];
			return false;
		} else
			return true;
	
	return false;
}

/* Image handling */
/**
 * Create a thumbnail
 *
 * @param string $name File to be thumbnailed
 * @param string $filename Path to place the thumbnail
 * @param integer $new_w Maximum width 
 * @param integer $new_h Maximum height
 * @return boolean Success/fail 
 */ 
function createThumbnail($name, $filename, $new_w, $new_h) {
	$system=explode(".", $filename);
	$system = array_reverse($system);
	if (preg_match("/jpg|jpeg/", $system[0])) {
		$src_img=imagecreatefromjpeg($name);
	} else if (preg_match("/png/", $system[0])) {
		$src_img=imagecreatefrompng($name);
	} else if (preg_match("/gif/", $system[0])) {
		$src_img=imagecreatefromgif($name);
	} else {
		return false;
	}
	
	if (!$src_img) {
		echo '<br>Unable to open the uploaded image for thumbnailing.  Maybe its a different filetype, and has the wrong extension?';
		return false;
	}
	$old_x = imageSX($src_img);
	$old_y = imageSY($src_img);
	if ($old_x > $old_y) {
		$percent = $new_w / $old_x;
	} else {
		$percent = $new_h / $old_y;
	}
	$thumb_w = round($old_x * $percent);
	$thumb_h = round($old_y * $percent);
	
	$dst_img = ImageCreateTrueColor($thumb_w, $thumb_h);
	fastImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
	
	if (preg_match("/png/", $system[0])) {
		if (!imagepng($dst_img, $filename)) {
			echo 'unable to imagepng.';
			return false;
		}
	} else if (preg_match("/jpg|jpeg/", $system[0])) {
		if (!imagejpeg($dst_img, $filename, 70)) {
			echo 'unable to imagejpg.';
			return false;
		}
	} else if (preg_match("/gif/", $system[0])) {
		if (!imagegif($dst_img, $filename)) { 
			echo 'unable to imagegif.';
			return false;
		}
	}
	
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
	
	return true;
}

/* Author: Tim Eckel - Date: 12/17/04 - Project: FreeRingers.net - Freely distributable. */
/**
 * Faster method than only calling imagecopyresampled()
 *
 * @return boolean Success/fail 
 */ 
function fastImageCopyResampled(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
	/*
	Optional "quality" parameter (defaults is 3).  Fractional values are allowed, for example 1.5.
	1 = Up to 600 times faster.  Poor results, just uses imagecopyresized but removes black edges.
	2 = Up to 95 times faster.  Images may appear too sharp, some people may prefer it.
	3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled.
	4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
	5 = No speedup.  Just uses imagecopyresampled, highest quality but no advantage over imagecopyresampled.
	*/
	
	if (empty($src_image) || empty($dst_image)) { return false; }

	if ($quality <= 1) {
		$temp = imagecreatetruecolor ($dst_w + 1, $dst_h + 1);
		imagecopyresized ($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
		imagecopyresized ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
		imagedestroy ($temp);
	} elseif ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
		
		$tmp_w = $dst_w * $quality;
		$tmp_h = $dst_h * $quality;
		$temp = imagecreatetruecolor ($tmp_w + 1, $tmp_h + 1);
		
		imagecopyresized ($temp, $src_image, $dst_x * $quality, $dst_y * $quality, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
		
		imagecopyresampled ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
		
		imagedestroy ($temp);
		
	} else {
		imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
	}

	
	return true;
}

/* By Darien Hager, Jan 2007 */
/**
 * Find the string value of a pair of ords
 *
 * @param string $ords Ords
 * @param string $encoding Encoding  
 * @return string String
 */ 
function ords_to_unistr($ords, $encoding = 'UTF-8'){
	/*  Turns an array of ordinal values into a string of unicode characters */
	$str = '';
	for($i = 0; $i < sizeof($ords); $i++){
		/* Pack this number into a 4-byte string
		(Or multiple one-byte strings, depending on context.) */
		$v = $ords[$i];
		$str .= pack("N",$v);
	}
	$str = mb_convert_encoding($str,$encoding,"UCS-4BE");
	return($str);           
}

/**
 * Find the ord value of a string
 *
 * @param string $str String
 * @param string $encoding Encoding  
 * @return array Ords
 */ 
function unistr_to_ords($str, $encoding = 'UTF-8'){       
	/* Turns a string of unicode characters into an array of ordinal values,
	Even if some of those characters are multibyte. */
	$str = mb_convert_encoding($str,"UCS-4BE",$encoding);
	$ords = array();

	/* Visit each unicode character */
	for($i = 0; $i < mb_strlen($str,"UCS-4BE"); $i++){       
		/* Now we have 4 bytes. Find their total numeric value */
		$s2 = mb_substr($str,$i,1,"UCS-4BE");                   
		$val = unpack("N",$s2);           
		$ords[] = $val[1];               
	}       
	return($ords);
}

/*
------------ lixlpixel recursive PHP functions -------------
recursive_directory_size( directory, human readable format )
expects path to directory and optional TRUE / FALSE
PHP has to have the rights to read the directory you specify
and all files and folders inside the directory to count size
if you choose to get human readable format,
the function returns the filesize in bytes, KB and MB
------------------------------------------------------------
to use this function to get the filesize in bytes, write:
recursive_directory_size('path/to/directory/to/count');
to use this function to get the size in a nice format, write:
recursive_directory_size('path/to/directory/to/count',TRUE);
*/

/**
 * Find the size of a directory, including any subdirectories
 *
 * @param string $directory Directory
 * @param boolean $format Format
 * @return array Size/number of files
 */ 
function recursive_directory_size($directory, $format=FALSE)
{
	$size = 0;
	$files = 0;

	/* If the path has a slash at the end we remove it here */
	if(substr($directory,-1) == '/')
	{
		$directory = substr($directory,0,-1);
	}

	/* If the path is not valid or is not a directory ... */
	if(!file_exists($directory) || !is_dir($directory) || !is_readable($directory))
	{
		/* ... We return -1 and exit the function */
		return -1;
	}
	/* We open the directory */
	if($handle = opendir($directory))
	{
		/* And scan through the items inside */
		while(($file = readdir($handle)) !== false)
		{
			/* We build the new path */
			$path = $directory.'/'.$file;

			/* If the filepointer is not the current directory or the parent directory */
			if($file != '.' && $file != '..')
			{
				/* If the new path is a file */
				if(is_file($path))
				{
					/* We add the filesize to the total size */
					$size += filesize($path);
					$files++;

				/* If the new path is a directory */
				}elseif(is_dir($path))
				{
					/* We call this function with the new path */
					$handlesize = recursive_directory_size($path);

					/* If the function returns more than zero */
					if($handlesize >= 0)
					{
						/* We add the result to the total size */
						$size += $handlesize;

					/* Else we return -1 and exit the function */
					}else{
						return -1;
					}
				}
			}
		}
		/* Close the directory */
		closedir($handle);
	}
	/* Return the total filesize in bytes */
	return array($size,$files);
}

/**
 * Format an amount of bytes to a better looking representation of the size
 *
 * @param integer $number Bytes
 * @return string Formatted amount
 */ 
function ConvertBytes($number) {
	$len = strlen($number);
	if($len < 4) {
		return sprintf("%dB", $number);
	} elseif($len <= 6) {
		return sprintf("%0.2fKB", $number/1024);
	} elseif($len <= 9) {
		return sprintf("%0.2fMB", $number/1024/1024);
	}

	return sprintf("%0.2fGB", $number/1024/1024/1024);
						
}

if (!function_exists('mime_content_type')) {
   function mime_content_type($f) {
       return trim(exec('file -bi ' . escapeshellarg ($f )));
   }
}

/* For PHP4 installations */
if (!function_exists('file_get_contents')) {
	function file_get_contents($file) {
		return implode('', file($file));
	}
}
if (!function_exists('file_put_contents')) {
	define('FILE_APPEND', 1);
	function file_put_contents($n, $d, $flag = false) {
		$mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
		$f = @fopen($n, $mode);
		if ($f === false) {
			return 0;
		} else {
			if (is_array($d)) $d = implode($d);
			$bytes_written = fwrite($f, $d);
			fclose($f);
			return $bytes_written;
		}
	}
}
if(!function_exists('str_ireplace')) {
	function str_ireplace( $find, $replace, $string ) {
		/* Case-insensitive str_replace() */
		$parts = explode( strtolower($find), strtolower($string) );
		$pos = 0;
		foreach( $parts as $key=>$part ) {
			$parts[ $key ] = substr($string, $pos, strlen($part));
			$pos += strlen($part) + strlen($find);
		}
		return( join( $replace, $parts ) );
	}
}

?>