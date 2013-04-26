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
 * AJAX thread watch handler
 * +------------------------------------------------------------------------------+
 * Returns a list of threads which are currently being watched
 * +------------------------------------------------------------------------------+
 */

require('config.php');
/* No need to waste effort if thread watching is disabled */
if (!TC_WATCHTHREADS) die();
require(TC_ROOTDIR . 'inc/functions.php');
require_once(TC_ROOTDIR . 'inc/operations.functions.php');
require_once(TC_ROOTDIR . 'inc/classes/board-post.class.php');

$output = '';
//$tc_db->debug = true;
if (isset($_GET['do'])) {
	if ($_GET['do'] == 'addthread') {
		$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		if ($viewing_thread_is_watched == 0) {
			$newestreplyid = $tc_db->GetOne('SELECT `id` FROM `'.TC_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `threadid` = '.mysql_real_escape_string($_GET['threadid']).' ORDER BY `id` DESC LIMIT 1');
			
			$tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "watchedthreads` ( `threadid` , `board` , `ip` , `lastsawreplyid` ) VALUES ( " . mysql_real_escape_string($_GET['threadid']) . " , '" . mysql_real_escape_string($_GET['board']) . "' , '" . $_SERVER['REMOTE_ADDR'] . "' , " . $newestreplyid . " )");
		}
	} elseif ($_GET['do'] == 'removethread') {
		$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		if ($viewing_thread_is_watched > 0) {
			$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		}
	}
} else {
	/* If the users is sending this request while viewing a thread, check if it is a thread they are watching, and if so, update it to show they have viewed all current replies */
	if ($_GET['threadid'] > 0) {
		$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		if ($viewing_thread_is_watched > 0) {
			$newestreplyid = $tc_db->GetOne('SELECT `id` FROM `'.TC_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `threadid` = '.mysql_real_escape_string($_GET['threadid']).' ORDER BY `id` DESC LIMIT 1');
			
			$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "watchedthreads` SET `lastsawreplyid` = " . $newestreplyid . " WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		}
	}
	
	$watched_threads = $tc_db->GetAll("SELECT `threadid` , `lastsawreplyid` FROM `" . TC_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' ORDER BY `lastsawreplyid` DESC");
	if (count($watched_threads) > 0) {
		foreach ($watched_threads as $watched_thread) {
			$threadinfo = $tc_db->GetAll('SELECT `subject` , `name` , `tripcode` FROM `'.TC_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `id` = ' . $watched_thread['threadid'] . ' LIMIT 1');
			
			$output .= '<a href="' . TC_BOARDSFOLDER . mysql_real_escape_string($_GET['board']) . '/res/' . $watched_thread['threadid'] . '.html">' . $watched_thread['threadid'] . '</a> - ';
			
			if ($threadinfo['subject'] != '') {
				$output .= '<span class="filetitle">' . stripslashes($threadinfo['subject']) . '</span> - ';
			}
			
			if ($threadinfo['user'] == '' && $threadinfo['tripcode'] == '') {
				$output .= TC_ANONYMOUS;
			} else if ($post_user==''&&$threadinfo['tripcode']!='') {
				/* Just display the tripcode, no added html */
			} else {
				$output .= stripslashes($threadinfo['user']);
			}
			
			if ($threadinfo['tripcode']!='') {
				$info_post .= '<span class="postertrip">!' . $threadinfo['tripcode'] . '</span>';
			}
			
			$output .= ': ';
	
			$numnewreplies = $tc_db->GetOne('SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.mysql_real_escape_string($_GET['board']).'` WHERE `IS_DELETED` = 0 AND `threadid` = ' . $watched_thread['threadid'] . ' AND `id` >  ' . $watched_thread['lastsawreplyid'] . ' LIMIT 1');
			
			if ($numnewreplies > 0) {
				$output .= '<a href="' . TC_BOARDSFOLDER . mysql_real_escape_string($_GET['board']) . '/res/' . $watched_thread['threadid'] . '.html#' . $watched_thread['lastsawreplyid'] . '"><b><font color="red">' . $numnewreplies . ' new repl';
				if ($numnewreplies != 1) {
					$output .= 'ies';
				} else {
					$output .= 'y';
				}
				$output .= '</font></b></a>';
			} else {
				$output .= '<b>0</b>';
			}
			
			$output .= ' &#91;<a href="#" onclick="javascript:removefromwatchedthreads(\'' . $watched_thread['threadid'] . '\', \'' . $_GET['board'] . '\');return false;" title="Un-watch">U</a>&#93;<br>';
		}
	} else {
		$output .= 'None.<br>';
	}
	
	
	
	$output .= '<br><a href="#" onclick="javascript:hidewatchedthreads();return false;" title="Hide the watched threads box">Hide</a>';
	
	if ($_GET['threadid'] > 0) {
		$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . mysql_real_escape_string($_GET['board']) . "' AND `threadid` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
		if ($viewing_thread_is_watched == 0) {
			$output .= ' - <a href="#" onclick="javascript:addtowatchedthreads(\'' . $_GET['threadid'] . '\', \'' . $_GET['board'] . '\');return false;">Watch this thread</a>';
		}
	}
}

echo $output;

?>