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
 * Miscellaneous functions used throughout the script.
 * +------------------------------------------------------------------------------+
 * The functions script has no theme; any and all functions which are used globally
 * will be found in this file.
 * +------------------------------------------------------------------------------+
 */

function microtime_float() {
	return array_sum(explode(' ', microtime()));
}

/* <3 coda for this wonderful snippet
print $contents to $filename by using a temporary file and renaming it */
function print_page($filename, $contents, $board) {
	global $tc_db;
	
	$tempfile = tempnam(TC_BOARDSDIR . $board . '/res', 'tmp'); /* Create the temporary file */
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

function print_stylesheets($prefered_stylesheet = TC_DEFAULTSTYLE) {
	global $tc_db;
	$output_stylesheets = '';
	$styles = explode(':', TC_STYLES);
	
	if (!in_array($prefered_stylesheet, $styles)) {
		$prefered_stylesheet = TC_DEFAULTSTYLE;
	}
	
	foreach ($styles as $stylesheet) {
		$output_stylesheets .= '<link rel="';
		if ($stylesheet != $prefered_stylesheet) {
			$output_stylesheets .= 'alternate ';
		}
		$output_stylesheets .= 'stylesheet" type="text/css" href="' . TC_BOARDSPATH . '/css/' . $stylesheet . '.css" title="' . ucfirst($stylesheet) . '">' . "\n";
	}
	
	return $output_stylesheets;
}

/* Checks if the supplied md5 file hash is currently recorded inside of the database, attached to a non-deleted post */
function check_md5($md5, $board) {
	global $tc_db;

	$num_matches = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `filemd5` = '".mysql_real_escape_string($md5)."' AND `IS_DELETED` = 0 LIMIT 1");
	if ($num_matches > 0) {
		$results = $tc_db->GetAll("SELECT `id`, `parentid` FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `filemd5` = '".mysql_real_escape_string($md5)."' AND `IS_DELETED` = 0 LIMIT 1");
		/* We want the first (and only) row */
		$results = $results[0];
		$real_parentid = ($results[1]==0) ? $results[0] : $results[1];
		
		return array($real_parentid, $results[0]);
	} else {
		return false;
	}
}

function clearpostcache($id, $board) {
	if (TC_APC) {
		apc_delete('post|' . $board . '|' . $id);
	}
}

function boardid_to_dir($boardid) {
	global $tc_db;
	
	$query = "SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `id` = '".mysql_real_escape_string($boardid)."'";
	$results = $tc_db->SelectLimit($query, 1);
	if (count($results)>0) {
		foreach($results AS $line) {
			return $line['name'];
		}
	}
}

function calculatenumpages($boardtype, $numposts) {
	if ($boardtype==1) {
		return (floor($numposts/15));
	} else {
		return (floor($numposts/TC_THREADS));
	}
}

/* Gets information about the filetype provided, which is specified in the manage panel */
function getfiletypeinfo($filetype) {
	global $tc_db;
	
	$query = "SELECT * FROM `".TC_DBPREFIX."filetypes` WHERE `filetype` = '".mysql_real_escape_string($filetype)."' LIMIT 1";
	$results = $tc_db->GetAll($query);
	foreach($results AS $line) {
		return array($line['image'],$line['image_w'],$line['image_h']);
	}
	
	/* No info was found, return the generic icon */
	return array('generic.png',48,48);
}

/* Add an entry to the modlog */
function management_addlogentry($entry, $category = 0, $forceusername = '') {
	/* Categories
	0 - no category
	1 - login
	2 - cleanup/rebuild boards and html files
	3 - board adding/deleting
	4 - board updates
	5 - locking/stickying
	6 - staff changes
	7 - thread deletion/post deletion
	8 - bans
	9 - news
	10 - global changes
	11 - wordfilter */
	global $tc_db;
	
	$username = ($forceusername == '') ? $_SESSION['manageusername'] : $forceusername;
	
	if ($entry != '') {
		$tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "modlog` ( `entry` , `user` , `category` , `timestamp` ) VALUES ( '" . mysql_real_escape_string($entry) . "' , '" . $username . "' , '" . mysql_real_escape_string($category) . "' , '" . time() . "' )");
	}
	if (TC_RSS) {
		require_once(TC_ROOTDIR . 'inc/classes/rss.class.php');
		$rss_class = new RSS();
		
		print_page(TC_BOARDSDIR . 'modlogrss.xml', $rss_class->GenerateModLogRSS($entry), '');
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