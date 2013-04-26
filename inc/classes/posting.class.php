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
 * +------------------------------------------------------------------------------+
 * Posting class
 * +------------------------------------------------------------------------------+
 */
class Posting {
	
	function CheckOekaki() {
		global $board_class;
		
		/* If oekaki seems to be in the url... */
		if (isset($_POST['oekaki'])) {
			/* See if it checks out and is a valid oekaki id */
			if ($_POST['oekaki'] != '' && is_file(KU_CGIDIR . 'kusabaoek/' . $_POST['oekaki'] . '.png') && $board_class->board_type == '2') {
				/* Set the variable to tell the script it is handling an oekaki posting, and the oekaki file which will be posted */
				return KU_CGIDIR . 'kusabaoek/' . $_POST['oekaki'] . '.png';
			}
		}
		
		return '';
	}
	
	function CheckReplyTime() {
		global $tc_db, $board_class;
		
		/* Get the timestamp of the last time a reply was made by this IP address */
		$results = $tc_db->GetAll("SELECT `postedat` FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `parentid` != 0 AND `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "' ORDER BY `postedat` DESC LIMIT 1");
		/* If they have posted before and it was recorded... */
		foreach ($results as $line) {
		/* If the time was shorter than the minimum time distance */
			if (time() - $line['postedat'] <= KU_REPLYDELAY) {
				exitWithErrorPage(_gettext('Please wait a moment before posting again.'), _gettext('You are currently posting faster than the configured minimum post delay allows.'));
			}
		}
	}
	
	function CheckNewThreadTime() {
		global $tc_db, $board_class;
		
		/* Get the timestamp of the last time a new thread was made by this IP address */
		$results = $tc_db->GetAll("SELECT `postedat` FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `parentid` = 0 AND `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "' ORDER BY `postedat` DESC LIMIT 1");
		/* If they have posted before and it was recorded... */
		foreach ($results as $line) {
			/* If the time was shorter than the minimum time distance */
			if (time() - $line['postedat'] <= KU_NEWTHREADDELAY) {
				exitWithErrorPage(_gettext('Please wait a moment before posting again.'), _gettext('You are currently posting faster than the configured minimum post delay allows.'));
			}
		}
	}
	
	function UTF8Strings() {
		if (function_exists('mb_convert_encoding') && function_exists('mb_check_encoding')) {
			if (isset($_POST['name']) && !mb_check_encoding($_POST['name'], 'UTF-8')) {
				$_POST['name'] = mb_convert_encoding($_POST['name'], 'UTF-8');
			}
			if (isset($_POST['em']) && !mb_check_encoding($_POST['em'], 'UTF-8')) {
				$_POST['em'] = mb_convert_encoding($_POST['em'], 'UTF-8');
			}
			if (isset($_POST['subject']) && !mb_check_encoding($_POST['subject'], 'UTF-8')) {
				$_POST['subject'] = mb_convert_encoding($_POST['subject'], 'UTF-8');
			}
			if (isset($_POST['message']) && !mb_check_encoding($_POST['message'], 'UTF-8')) {
				$_POST['message'] = mb_convert_encoding($_POST['message'], 'UTF-8');
			}
		}
	}
	
	function CheckValidPost($is_oekaki) {
		global $tc_db, $board_class;
		
		if ((isset($_POST['message']) || isset($_FILES['imagefile'])) || $is_oekaki || ($board_class->board_type == '1' && isset($_POST['message'])) || (($board_class->board_uploadtype == '1' || $board_class->board_uploadtype == '2') && isset($_POST['embed']))) {
			return true;
		} else {
			return false;
		}
	}
	
	function CheckMessageLength() {
		global $board_class;
		
		/* If the length of the message is greater than the board's maximum message length... */
		if (strlen($_POST['message']) > $board_class->board_messagelength) {
			/* Kill the script, stopping the posting process */
			exitWithErrorPage(printf(_gettext('Sorry, your message is too long.  Message length: %d, maximum allowed length: %d'), strlen($_POST['message']), $board_class->board_messagelength));
		}
	}
	
	function CheckCaptcha() {
		global $board_class;
		
		/* If the board has captcha's enabled... */
		if ($board_class->board_enablecaptcha == 1) {
			/* Check if they entered the correct code.  If not... */
			if ($_SESSION['security_code'] != strtolower($_POST['captcha']) || empty($_SESSION['security_code'])) {
				/* Kill the script, stopping the posting process */
				exitWithErrorPage(_gettext('Incorrect captcha entered.'));
			}
		}
	}
	
	function CheckBannedHash() {
		global $tc_db, $board_class, $bans_class;
		
		/* Banned file hash check */
		if (isset($_FILES['imagefile'])) {
			if ($_FILES['imagefile']['name'] != '') {
				$results = $tc_db->GetAll("SELECT `bantime` , `description` FROM `" . KU_DBPREFIX . "bannedhashes` WHERE `md5` = '" . mysql_real_escape_string(md5_file($_FILES['imagefile']['tmp_name'])) . "' LIMIT 1");
				if (count($results) > 0) {
					foreach ($results as $line) {
						$bans_class->BanUser($_SERVER['REMOTE_ADDR'], 'SERVER', '1', $line['bantime'], '', 'Posting a banned file.<br>' . $line['description'], 0, 0, 1);
						$bans_class->BanCheck($_SERVER['REMOTE_ADDR'], $board_class->board_dir);
						die();
					}
				}
			}
		}
	}
	
	function CheckIsReply() {
		global $tc_db, $board_class;
		
		/* If it appears this is a reply to a thread, and not a new thread... */
		if (isset($_POST['replythread'])) {
			if ($_POST['replythread'] != '0') {
				/* Check if the thread id supplied really exists */
				$results = $tc_db->GetOne("SELECT COUNT(*) FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `id` = '" . mysql_real_escape_string($_POST['replythread']) . "' AND `parentid` = '0' LIMIT 1");
				/* If it does... */
				if ($results > 0) {
					return true;
				/* If it doesn't... */
				} else {
					/* Kill the script, stopping the posting process */
					exitWithErrorPage(_gettext('Invalid thread ID.'), _gettext('That thread may have been recently deleted.'));
				}
			}
		}
		
		return false;
	}
	
	function CheckNotDuplicateSubject($subject) {
		global $tc_db, $board_class;
		
		$results = $tc_db->GetOne("SELECT COUNT(*) FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `subject` = '" . mysql_real_escape_string($subject) . "' AND `parentid` = '0' LIMIT 1");
		if ($results > 0) {
			exitWithErrorPage(_gettext('Duplicate thread subject'), _gettext('Text boards may have only one thread with a unique subject.  Please pick another.'));
		}
	}
	
	function GetThreadInfo($id) {
		global $tc_db, $board_class;
		
		/* Check if the thread id supplied really exists */
		$results = $tc_db->GetAll("SELECT `id`,`locked` FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `id` = '" . mysql_real_escape_string($id) . "' AND `parentid` = '0'");
		/* If it does... */
		if (count($results) > 0) {
			/* Get the thread's info */
			foreach ($results as $line) {
				$thread_locked = $line['locked'];
				$thread_replyto = $line['id'];
			}
			/* Get the number of replies */
			$results = $tc_db->GetAll("SELECT `id` FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `parentid` = '" . mysql_real_escape_string($id) . "'");
			$thread_replies = count($results);
			
			return array($thread_replies, $thread_locked, $thread_replyto);
		} else {
			/* Kill the script, stopping the posting process */
			exitWithErrorPage(_gettext('Invalid thread ID.'), _gettext('That thread may have been recently deleted.'));
		}
	}
	
	function GetFields() {
		/* Fetch and process the name, email, and subject fields from the post data */
		$post_name = isset($_POST['name']) ? htmlspecialchars($_POST['name'], ENT_QUOTES) : '';
		$post_email = isset($_POST['em']) ? str_replace('"', '', strip_tags($_POST['em'])) : '';
		/* If the user used a software function, don't store it in the database */
		if ($post_email == 'return' || $post_email == 'noko') $post_email = '';
		$post_subject = isset($_POST['subject']) ? htmlspecialchars($_POST['subject'], ENT_QUOTES) : '';
		
		return array($post_name, $post_email, $post_subject);
	}
	
	function GetUserAuthority() {
		global $tc_db, $board_class;
		
		$user_authority = 0;
		$flags = '';
		
		if (isset($_POST['modpassword'])) {
			
			$results = $tc_db->GetAll("SELECT `type`, `boards` FROM `" . KU_DBPREFIX . "staff` WHERE `username` = '" . md5_decrypt($_POST['modpassword'], KU_RANDOMSEED) . "' LIMIT 1");
			
			if (count($results) > 0) {
				if (isset($_POST['displaystaffstatus'])) $flags .= 'D';
				if (isset($_POST['lockonpost'])) $flags .= 'L';
				if (isset($_POST['stickyonpost'])) $flags .= 'S';
				if (isset($_POST['rawhtml'])) $flags .= 'RH';
				if (isset($_POST['usestaffname'])) $flags .= 'N';
				
				if ($results[0][0] == 1) {
					$user_authority = 1;
				} elseif ($results[0][0] == 2 && in_array($board_class->board_dir, explode('|', $results[0][1]))) {
					$user_authority = 2;
				}
			} else {
				$vip_valid = $tc_db->GetOne("SELECT COUNT(*) FROM `" . KU_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_POST['modpassword']) . "' AND `type` = '3' LIMIT 1");
			
				if ($vip_valid > 0) {
					$user_authority = 3;
				}
			}
		}
		
		return array($user_authority, $flags);
	}
	
	function CheckBadUnicode($post_name, $post_email, $post_subject, $post_message) {
		/* Check for bad characters which can cause the page to deform (right-to-left markers, etc) */
		$bad_ords = array(8235, 8238);
		
		$ords_name = unistr_to_ords($post_name);
		$ords_email = unistr_to_ords($post_email);
		$ords_subject = unistr_to_ords($post_subject);
		$ords_message = unistr_to_ords($post_message);
		$ords_filename = isset($_FILES['imagefile']) ? unistr_to_ords($_FILES['imagefile']['name']) : '';
		foreach ($bad_ords as $bad_ord) {
			if ($ords_name != '') {
				if (in_array($bad_ord, $ords_name)) { 
					exitWithErrorPage(_gettext('Your post contains one or more illegal characters.'));
				}
			}
			if ($ords_email != '') {
				if (in_array($bad_ord, $ords_email)) {
					exitWithErrorPage(_gettext('Your post contains one or more illegal characters.'));
				}
			}
			if ($ords_subject != '') {
				if (in_array($bad_ord, $ords_subject)) {
					exitWithErrorPage(_gettext('Your post contains one or more illegal characters.'));
				}
			}
			if ($ords_message != '') {
				if (in_array($bad_ord, $ords_message)) {
					exitWithErrorPage(_gettext('Your post contains one or more illegal characters.'));
				}
			}
			if ($ords_filename != '') {
				if (in_array($bad_ord, $ords_filename)) {
					exitWithErrorPage(_gettext('Your post contains one or more illegal characters.'));
				}
			}
		}
	}
	
	function GetPostTag() {
		global $board_class;
		
		/* Check for and parse tags if one was provided, and they are enabled */
		$post_tag = '';
		$tags = unserialize(KU_TAGS);
		if ($board_class->board_type == 3 && $tags != '' && $_POST['tag'] != '') {
			$validtag = false;
			while (list($tag, $tag_abbr) = each($tags)) {
				if ($tag_abbr == $_POST['tag']) {
					$validtag = true;
				}
			}
			if ($validtag) {
				$post_tag = $_POST['tag'];
			}
		}
		
		return $post_tag;
	}
	
	function CheckBlacklistedText() {
		global $bans_class;
		$badlinks = file(KU_ROOTDIR . 'spam.txt');
		
		foreach ($badlinks as $badlink) {
			if (strpos($_POST['message'], substr($badlink, 0, -1)) !== false) {
				/* They included a blacklisted link in their post.  Ban them for an hour */
				$bans_class->BanUser($_SERVER['REMOTE_ADDR'], 'board.php', 1, 3600, '', _gettext('Posting a blacklisted link.') . ' (' . substr($badlink, 0, -1) . ')');
				exitWithErrorPage('Blacklisted link ('.substr($badlink, 0, -1).') detected.');
			}
		}
	}
}

?>