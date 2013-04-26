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
 * AJAX thread watch handler
 *
 * Returns a list of threads which are currently being watched
 * 
 * @package kusaba  
 */

/** 
 * Require the configuration file
 */ 
require 'config.php';

/* No need to waste effort if thread watching is disabled */
if (!KU_WATCHTHREADS) die();
require KU_ROOTDIR . 'inc/functions.php';
require KU_ROOTDIR . 'inc/classes/board-post.class.php';

$output = '';

if (isset($_GET['do'])) {
	switch($_GET['do']) {
		case 'addthread':
		$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . KU_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		if ($viewing_thread_is_watched == 0) {
			$newestreplyid = $tc_db->GetOne('SELECT `id` FROM `'.KU_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `parentid` = '.mysql_real_escape_string($_GET['threadid']).' ORDER BY `id` DESC LIMIT 1');
			$newestreplyid = max(0, $newestreplyid);
			
			$tc_db->Execute("INSERT INTO `" . KU_DBPREFIX . "watchedthreads` ( `threadid` , `board` , `ip` , `lastsawreplyid` ) VALUES ( " . mysql_real_escape_string($_GET['threadid']) . " , '" . mysql_real_escape_string($_GET['board']) . "' , '" . $_SERVER['REMOTE_ADDR'] . "' , " . $newestreplyid . " )");
			
			if (KU_APC) apc_delete('watchedthreads|' . $_GET['board'] . '|' . $_SERVER['REMOTE_ADDR']);
		}
		break;
		
	case 'removethread':
		$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . KU_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		if ($viewing_thread_is_watched > 0) {
			$tc_db->Execute("DELETE FROM `" . KU_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
			
			if (KU_APC) apc_delete('watchedthreads|' . $_GET['board'] . '|' . $_SERVER['REMOTE_ADDR']);
		}
		break;
		
	default:
		$output .= 'Invalid operation';
	}
} else {
	/* If the user is sending this request while viewing a thread, check if it is a thread they are watching, and if so, update it to show they have viewed all current replies */
	if ($_GET['threadid'] > 0) {
		$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . KU_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		if ($viewing_thread_is_watched > 0) {
			$newestreplyid = $tc_db->GetOne('SELECT `id` FROM `'.KU_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `parentid` = '.mysql_real_escape_string($_GET['threadid']).' ORDER BY `id` DESC LIMIT 1');
			$newestreplyid = max(0, $newestreplyid);
			
			$tc_db->Execute("UPDATE `" . KU_DBPREFIX . "watchedthreads` SET `lastsawreplyid` = " . $newestreplyid . " WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
			
			if (KU_APC) apc_delete('watchedthreads|' . $_GET['board'] . '|' . $_SERVER['REMOTE_ADDR']);
		}
	}
	
	$cached = false;
	if (KU_APC) {
		$cache_threadwatch = apc_fetch('watchedthreads|' . $_GET['board'] . '|' . $_SERVER['REMOTE_ADDR']);
		if ($cache_threadwatch !== false) {
			$cached = true;
			$output .= $cache_threadwatch;
		}
	}
	
	if (!$cached) {
		$watched_threads = $tc_db->GetAll("SELECT `threadid` , `lastsawreplyid` FROM `" . KU_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' ORDER BY `lastsawreplyid` DESC");
		if (count($watched_threads) > 0) {
			foreach ($watched_threads as $watched_thread) {
				$threadinfo = $tc_db->GetAll('SELECT `subject` , `name` , `tripcode` FROM `'.KU_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `id` = ' . $watched_thread['threadid'] . ' LIMIT 1');
				
				$threadinfo = $threadinfo[0];
				
				$output .= '<a href="' . KU_BOARDSFOLDER . mysql_real_escape_string($_GET['board']) . '/res/' . $watched_thread['threadid'] . '.html">' . $watched_thread['threadid'] . '</a> - ';
				
				if ($threadinfo['subject'] != '') {
					$output .= '<span class="filetitle">' . $threadinfo['subject'] . '</span> - ';
				}
				
				$output .= formatNameAndTrip($threadinfo['name'], '', $threadinfo['tripcode'], 'Anonymous');
		
				$numnewreplies = $tc_db->GetOne('SELECT COUNT(*) FROM `'.KU_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `parentid` = ' . $watched_thread['threadid'] . ' AND `id` >  ' . $watched_thread['lastsawreplyid'] . ' LIMIT 1');
				
				if ($numnewreplies > 0) {
					$output .= '<a href="' . KU_BOARDSFOLDER . mysql_real_escape_string($_GET['board']) . '/res/' . $watched_thread['threadid'] . '.html#' . $watched_thread['lastsawreplyid'] . '"><b><font color="red">' . $numnewreplies . ' new repl';
					if ($numnewreplies != 1) {
						$output .= 'ies';
					} else {
						$output .= 'y';
					}
					$output .= '</font></b></a>';
				} else {
					$output .= '<b>0</b>';
				}
				
				$output .= ' <a href="#" onclick="javascript:removefromwatchedthreads(\'' . $watched_thread['threadid'] . '\', \'' . $_GET['board'] . '\');return false;" title="Un-watch">X</a><br>';
			}
		} else {
			$output .= 'None.<br>';
		}	
		
		/*$output .= '<div id="watchedthreadsbuttons">
		<a href="#" onclick="javascript:hidewatchedthreads();return false;" title="Hide the watched threads box">
		<img src="' . KU_WEBPATH . '/lib/icons/arrow-upleft.gif" border="0" alt="hide">
		</a>&nbsp;
		<a href="#" onclick="javascript:getwatchedthreads(\'0\', \'' . $_GET['board'] . '\');return false;" title="Refresh watched threads">
		<img src="' . KU_WEBPATH . '/lib/icons/refresh.gif" border="0" alt="refresh">
		</a>';
		
		$output .= '</div>';*/
		if (KU_APC) {
			apc_store('watchedthreads|' . $_GET['board'] . '|' . $_SERVER['REMOTE_ADDR'], $output, 600);
		}
	}
}

echo $output;

?>