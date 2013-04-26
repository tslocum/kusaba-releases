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
 * Trevorchan - http://www.trevorchan.org/
 * Written by Trevor "tj9991" Slocum
 * http://www.tj9991.com/
 * tslocum@gmail.com
 * +------------------------------------------------------------------------------+
 * Board operations which are available to all users
 * +------------------------------------------------------------------------------+
 * This file serves the purpose of providing functionality for all users of the
 * boards.  This includes: posting, reporting posts, and deleting posts.
 * +------------------------------------------------------------------------------+
 */
session_start();

require('config.php');
require(TC_ROOTDIR . 'inc/functions.php');
require_once(TC_ROOTDIR . 'inc/operations.functions.php');
require_once(TC_ROOTDIR . 'inc/classes/board-post.class.php');
require_once(TC_ROOTDIR . 'inc/classes/bans.class.php');

$bans_class = new Bans();

/* Modules */
require_once(TC_ROOTDIR . 'inc/module.php');
modules_load_all();

if (TC_DNSBL) {
	require_once(TC_ROOTDIR . 'inc/classes/dnsbl.class.php');
	$dnsbl = new DNSBL();
	
	$dnsbl->dnsbl_list = array(
		'Tor' =>
		array(
			'url' => 'tor.dnsbl.sectoor.de',
			'returns' => array(
				'127.0.0.1'=>'tor exit node'
			)
		),
		'SORBS HTTP' =>
		array(
			'url' => 'http.dnsbl.sorbs.net',
			'returns' => array(
				'127.0.0.2'=>'http Proxy',
				'127.0.0.3'=>'socks Proxy',
				'127.0.0.4'=>'misc Proxy'
			)
		),
		'Abusive Hosts Blocklist' =>
		array(
			'url' => 'dnsbl.ahbl.org',
			'returns' => array(
				'127.0.0.2' => 'open relay - mail',
				'127.0.0.3' => 'open proxy',
				'127.0.0.6' => 'insecure website',
				'127.0.0.14' => 'Compromised System - ddos drone/bot infected',
				'127.0.0.15' => 'Compromised System - relay',
				'127.0.0.16' => 'Compromised System - autorooter/scanner',
				'127.0.0.17' => 'Compromised System - worm or mass mailing virus',
				'127.0.0.18' => 'Compromised System - misc virus',
				'127.0.0.19' => 'open proxy'
			)
		)
	);
	
	if ($dnsbl->Check(false) === false) {        
		$bans_class->BanUser($_SERVER['REMOTE_ADDR'], 'DNS Blocklist', 1, 0, '', '<strong>Open Proxy Detected</strong> ' . $dnsbl->blockreason . ' <!-- ' . $dnsbl->dnsbl_name . ' - ' . $dnsbl->returned_ip . ' -->');

		die($dnsbl->blockreason);
	}
}

if (isset($_POST['email'])) {
	if ($_POST['email']!= '') {
		die('Spam bot detected');
	}
}

/* In some cases, the board value is sent through post, others get.  Throw either of them into the post */
if (isset($_GET['board'])) {
	$_POST['board'] = $_GET['board'];
}

/* If the script was called using a board name: */
if (isset($_POST['board'])) {
	$board_name = $tc_db->GetOne("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['board']) . "'");
	if ($board_name!='') {
		$board_class = new Board($board_name);
	} else {
		die('<meta http-equiv="refresh" content="0;url=' . TC_WEBPATH . '">');
	}
} else {
	/* A board being supplied is required for this script to function */
	die('<meta http-equiv="refresh" content="0;url=' . TC_WEBPATH . '">');
}

$bans_class->RemoveExpiredBans();
$bans_class->BanCheck($_SERVER['REMOTE_ADDR'], $board_class->board_dir);

$is_oekaki = false;
/* If oekaki seems to be in the url... */
if (isset($_POST['oekaki'])) {
	/* See if it checks out and is a valid oekaki id */
	if ($_POST['oekaki'] != '' && is_file('tcdrawings/' . $_POST['oekaki'] . '.png') && $board_class->board_type == '2') {
		/* Set the variable to tell the script it is handling an oekaki posting, and the oekaki file which will be posted */
		$is_oekaki = true;
		$oekaki = 'tcdrawings/' . $_POST['oekaki'] . '.png';
	}
}

/* Check if the user sent a valid post (image for thread, image/message for reply, etc) */
if ((isset($_POST['message']) || isset($_FILES['imagefile'])) || $is_oekaki || ($board_class->board_type == '1' && isset($_POST['message']))) {
	/* Get the timestamp of the last time a reply was made by this IP address */
	$results = $tc_db->GetAll("SELECT `postedat` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `threadid` != 0 AND `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "'");
	/* If they have posted before and it was recorded... */
	foreach ($results as $line) {
	/* If the time was shorter than the minimum time distance */
		if (time() - $line['postedat'] <= TC_REPLYDELAY) {
			die(_('Error: please wait a moment before posting again.'));
		}
	}
	
	/* Get the timestamp of the last time a new thread was made by this IP address */
	$results = $tc_db->GetAll("SELECT `postedat` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `threadid` = 0 AND `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "'");
	/* If they have posted before and it was recorded... */
	foreach ($results as $line) {
	/* If the time was shorter than the minimum time distance */
		if (time() - $line['postedat'] <= TC_NEWTHREADDELAY) {
			die(_('Error: please wait a moment before posting again.'));
		}
	}
	
	/* If the length of the message is greater than the board's maxmimum message length... */
	if (strlen($_POST['message']) > $board_class->board_messagelength) {
		/* Kill the script, stopping the posting process */
		printf(_('Sorry, your message is too long.  Message length: %d, maxmimum allowed length: %d'), strlen($_POST['message']), $board_class->board_messagelength);
		die();
	}
	
	/* If the board has captcha's enabled... */
	if ($board_class->board_enablecaptcha == 1) {
		/* Check if they entered the correct code.  If not... */
		if ($_SESSION['security_code'] != strtolower($_POST['captcha']) || empty($_SESSION['security_code'])) {
			/* Kill the script, stopping the posting process */
			die(_('Error: Incorrect captcha entered.'));
		}
	}
	
	$badlinks = file(TC_ROOTDIR . 'spam.txt');
	$thread_locked = 0;
	$thread_replyto = 0;
	$user_authority = 0;
	$post_isreply = false;
	$post_imageused = false;
	$post_autosticky = false;
	$post_autolock = false;
	$post_displaystaffstatus = false;
	$file_is_special = false;
	
	/* If it appears this is a reply to a thread, and not a new thread... */
	if (isset($_POST['replythread'])) {
		if ($_POST['replythread'] != '0') {
			/* Check if the thread id supplied really exists */
			$results = $tc_db->GetAll("SELECT `id`,`locked` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `id` = '" . mysql_real_escape_string($_POST['replythread']) . "' AND `threadid` = '0'");
			/* If it does... */
			if (count($results) > 0) {
				/* Get the thread's info */
				foreach ($results as $line) {
					$thread_locked = $line['locked'];
					$thread_replyto = $line['id'];
				}
				/* Get the number of replies */
				$results = $tc_db->GetAll("SELECT `id` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `threadid` = '" . mysql_real_escape_string($_POST['replythread']) . "'");
				$thread_replies = count($results);
				/* Set the variable which will inform the script we are making a reply and not a new thread */
				$post_isreply = true;
			/* If it doesn't... */
			} else {
				/* Kill the script, stopping the posting process */
				die(_('Invalid thread ID.  This may have been caused by the thread recently being deleted'));
			}
		}
	}
	
	/* Fetch and process the name, email, and subject fields from the post data */
	$post_name = isset($_POST['name']) ? addslashes(htmlspecialchars($_POST['name'], ENT_QUOTES)) : '';
	$post_email = isset($_POST['em']) ? addslashes(str_replace('"', '', strip_tags($_POST['em']))) : '';
	/* If the user used a software function, don't store it in the database */
	if ($post_email == 'return' || $post_email == 'noko') $post_email = '';
	$post_subject = isset($_POST['subject']) ? addslashes(htmlspecialchars($_POST['subject'], ENT_QUOTES)) : '';
	
	
	if (isset($_POST['modpassword'])) {
		require(TC_ROOTDIR . 'inc/encryption.php');
		
		$results = $tc_db->GetOne("SELECT `isadmin`,`boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . md5_decrypt($_POST['modpassword'], TC_RANDOMSEED) . "'");
		
		if (count($results) > 0) {
			$flags = '';
				
			if (isset($_POST['displaystaffstatus'])) $flags .= 'D';
			if (isset($_POST['rawhtml'])) $flags .= 'RH';
			if (isset($_POST['lockonpost'])) $flags .= 'L';
			if (isset($_POST['stickyonpost'])) $flags .= 'S';
			
			if ($results[0] == 1) {
				$user_authority = 1;
			} elseif (in_array($board_class->board_dir, explode('|', $results[0][1]))) {
				$user_authority = 2;
			}
		}
	}
	
	/* If the user supplied a posting password, set it in a variable used later */
	if ($_POST['postpassword'] != '') {
		$post_password = $_POST['postpassword'];
	/* Otherwise, set it to an empty string */
	} else {
		$post_password = '';
	}
	
	foreach ($badlinks as $badlink) {
		if (strpos($_POST['message'], substr($badlink, 0, -1)) != false) {
			/* They included a blacklisted link in their post.  Ban them for an hour */
			$bans_class->BanUser($_SERVER['REMOTE_ADDR'], 'board.php', 1, 3600, '', _('Posting a blacklisted link.') . '(' . $badlink . ')');
			die('Blacklisted link ('.$badlink.') detected.');
		}
	}
	
	/* If they are just a normal user... */
	if ($user_authority == 0) {
		/* If the thread is locked */
		if ($thread_locked == 1) {
			/* Don't let the user post */
			die(_('Sorry, this thread is locked and can not be replied to.'));
		}
		
		require_once(TC_ROOTDIR . 'inc/classes/parse.class.php');
		$parse_class = new Parse();
		
		$post_message = $parse_class->ParsePost($_POST['message'], $board_class->board_dir, $board_class->board_type, $thread_replyto);
	/* Or, if they are a moderator/administrator... */
	} else {
		/* If they checked the D checkbox, set the variable to tell the script to display their staff status (Admin/Mod) on the post during insertion */
		if (isset($_POST['displaystaffstatus'])) {
			$post_displaystaffstatus = true;
		}
		
		/* If they checked the RH checkbox, set the variable to tell the script to insert the post as-is... */
		if (isset($_POST['rawhtml'])) {
			$post_message = addslashes($_POST['message']);
		/* Otherwise, parse it as usual... */
		} else {
			require_once(TC_ROOTDIR . 'inc/classes/parse.class.php');
			$parse_class = new Parse();
			
			$post_message = $parse_class->ParsePost($_POST['message'], $board_class->board_dir, $board_class->board_type, $thread_replyto);
		}
		
		/* If they checked the L checkbox, set the variable to tell the script to lock the post after insertion */
		if (isset($_POST['lockonpost'])) {
			$post_autolock = true;
		}
		
		/* If they checked the S checkbox, set the variable to tell the script to sticky the post after insertion */
		if (isset($_POST['stickyonpost'])) {
			$post_autosticky = true;
		}
		if (isset($_POST['anonymod'])) {
			$_POST['name'] = md5_decrypt($_POST['modpassword'], TC_RANDOMSEED);
			$post_name = md5_decrypt($_POST['modpassword'], TC_RANDOMSEED);
		}
	}
	
	if ($post_isreply) {
		if ($_FILES['imagefile']['name'] == '' && !$is_oekaki && $post_message == '') {
			die(_('An image, or message, is required for a reply.'));
		}
	} else {
		if ($_FILES['imagefile']['name'] == '' && !$is_oekaki && ((!isset($_POST['nofile'])&&$board_class->board_enablenofile==1) || $board_class->board_enablenofile==0) && ($board_class->board_type == 0 || $board_class->board_type == 2)) {
			if ($_POST['embed'] == '' && $board_class->board_uploadtype != 1) {
				die(_('An image is required for a new thread.') . '<br>Or, if supported, an embed ID.');
			}
		}
	}
	
	if (isset($_POST['nofile'])&&$board_class->board_enablenofile==1) {
		if ($post_message == '') {
			die('A message is required to post without a file.');
		}
	}
	
	if ($board_class->board_type == 1 && !$post_isreply && $post_subject == '') {
		die('A subject is required to make a new thread.');
	}
	
	if ($board_class->board_locked == 0 || $user_authority > 0) {
		$board_class->TrimToPageLimit();
		
		require_once(TC_ROOTDIR . 'inc/classes/upload.class.php');
		$upload_class = new Upload();

		if ((!isset($_POST['nofile'])&&$board_class->board_enablenofile==1)||$board_class->board_enablenofile==0) {
			$upload_class->HandleUpload();
		}
		
		if ($board_class->board_forcedanon == '1') {
			if ($user_authority == 0) {
				$post_name = '';
			}
		}
		
		$nameandtripcode = calculateNameAndTripcode($post_name);
		if (is_array($nameandtripcode)) {
			$name = $nameandtripcode[0];
			$tripcode = $nameandtripcode[1];
		} else {
			$name = $post_name;
			$tripcode = '';
		}
		
		$filetype_withoutdot = substr($upload_class->file_type, 1);
		$post_passwordmd5 = ($post_password == '') ? '' : md5($post_password);
		
		if ($post_autosticky == true) {
			if ($thread_replyto == 0) {
				$sticky = 1;
			} else {
				$result = $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` SET `stickied` = '1' WHERE `id` = '" . $thread_replyto . "'");
				$sticky = 0;
			}
		} else {
			$sticky = 0;
		}
		
		if ($post_autolock == true) {
			if ($thread_replyto == 0) {
				$lock = 1;
			} else {
				$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` SET `locked` = '1' WHERE `id` = '" . $thread_replyto . "'");
				$lock = 0;
			}
		} else {
			$lock = 0;
		}
		
		if (!$post_displaystaffstatus && $user_authority > 0) {
			$user_authority_display = 0;
		} elseif ($user_authority > 0) {
			$user_authority_display = $user_authority;
		} else {
			$user_authority_display = 0;
		}
		
		if ((file_exists(TC_BOARDSDIR . $board_class->board_dir . '/src/' . $upload_class->file_name . $upload_class->file_type) && file_exists(TC_BOARDSDIR . $board_class->board_dir . '/thumb/' . $upload_class->file_name . 's' . $upload_class->file_type)) || ($file_is_special && file_exists(TC_BOARDSDIR . $board_class->board_dir . '/src/' . $upload_class->file_name . $upload_class->file_type)) || $post_imageused == false) {
			$post = array();
			$post['name'] = substr($name, 0, 100);
			$post['tripcode'] = $tripcode;
			$post['email'] = substr($post_email, 0, 100);
			$post['subject'] = substr($post_subject, 0, 100);
			$post['message'] = $post_message;
			
			$post = hook_process('posting', $post);
			
			if ($is_oekaki) {
				if (file_exists(TC_BOARDSDIR . $board_class->board_dir . '/src/' . $upload_class->file_name . '.pch')) {
					$post['message'] .= '<br><small><a href="' . TC_BOARDSFOLDER . 'animation.php?board=' . $board_class->board_dir . '&id=' . $upload_class->file_name . '">View animation</a></small>';
				}
			}

			$post_class = new Post(0, $board_class->board_dir, true);
			$post_id = $post_class->Insert($thread_replyto, $post['name'], $post['tripcode'], $post['email'], $post['subject'], $post['message'], mysql_real_escape_string($upload_class->file_name), $filetype_withoutdot, $upload_class->file_md5, $upload_class->imgWidth, $upload_class->imgHeight, $upload_class->imgSize, $upload_class->imgWidth_thumb, $upload_class->imgHeight_thumb, $post_passwordmd5, time(), time(), $_SERVER['REMOTE_ADDR'], $user_authority_display, $sticky, $lock);
			
			if ($user_authority > 0) {
				$modpost_message = 'Modposted #<a href="' . TC_BOARDSFOLDER . $board_class->board_dir . '/res/';
				if ($post_isreply) {
					$modpost_message .= $thread_replyto;
				} else {
					$modpost_message .= $post_id;
				}
				$modpost_message .= '.html#' . $post_id . '">' . $post_id . '</a> in /'.$_POST['board'].'/ with flags: ' . $flags . '.';
				management_addlogentry($modpost_message, 1);
			}
			
			if (isset($_POST['name'])) {
				setcookie('name', urldecode($_POST['name']), time() + 31556926, '/');
			}
			
			/* First array is the converted form of the japanese characters meaning sage, second meaning age */
			if (strtolower($_POST['em']) != 'sage' && unistr_to_ords($_POST['em']) != array(19979, 12370) && strtolower($_POST['em']) != 'age' && unistr_to_ords($_POST['em']) != array(19978, 12370) && $_POST['em'] != 'return' && $_POST['em'] != 'noko') {
				setcookie('email', urldecode($_POST['em']), time() + 31556926, '/');
			}
			setcookie('postpassword', urldecode($_POST['postpassword']), time() + 31556926, '/');
		} else {
			die(_('Could not copy uploaded image.'));
		}
		
		/* If the user replied to a thread, and they weren't sage-ing it... */
		if ($thread_replyto != '0' && strtolower($_POST['em']) != 'sage' && unistr_to_ords($_POST['em']) != array(19979, 12370)) {
			/* And if the number of replies already in the thread are less than the maximum thread replies before perma-sage... */
			if ($thread_replies <= $board_class->board_maxreplies) {
				/* Bump the thread */
				$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` SET `lastbumped` = '" . time() . "' WHERE `id` = '" . $thread_replyto . "'");
			}
		}
		
		/* If the user replied to a thread he is watching, update it so it doesn't count his reply as unread */
		if (TC_WATCHTHREADS && $thread_replyto != '0') {
			$viewing_thread_is_watched = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "watchedthreads` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . $board_class->board_dir . "' AND `threadid` = '" . $thread_replyto . "'");
			if ($viewing_thread_is_watched > 0) {
				$newestreplyid = $tc_db->GetOne('SELECT `id` FROM `'.TC_DBPREFIX.'posts_'.$board_class->board_dir.'` WHERE `IS_DELETED` = 0 AND `threadid` = '.$thread_replyto.' ORDER BY `id` DESC LIMIT 1');
				
				$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "watchedthreads` SET `lastsawreplyid` = " . $newestreplyid . " WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' AND `board` = '" . $board_class->board_dir . "' AND `threadid` = '" . $thread_replyto . "'");
			}
		}
		
		/* Initialize the board class */
		$board_class = new Board($board_class->board_dir);
		
		if ($thread_replyto == '0') {
			/* If they started a new thread, regenerate it (Technically it isn't regeneration, as this is the first time it is being built) */
			$board_class->RegenerateThread($post_id);
		} else {
			/* If they made a reply to an existing thread, regenerate it */
			$board_class->RegenerateThread($thread_replyto);
		}
		/* Regenerate board pages */
		$board_class->RegeneratePages();
		
	} else {
		die(_('Sorry, this board is locked and can not be posted in.'));
	}
} elseif (isset($_POST['delete']) && isset($_POST['postpassword'])) {
	/* Initialize the post class */
	$post_class = new Post(mysql_real_escape_string($_POST['delete']), $board_class->board_dir);
	
	if (isset($_POST['reportpost'])) {
		/* They clicked the Report button */
		if ($board_class->board_enablereporting == 1) {
			$post_reported = $post_class->post_isreported;
			
			if ($post_reported === 'cleared') {
				echo _('That post has been cleared as not requiring any deletion.');
			} elseif ($post_reported) {
				echo _('That post is already in the report list.');
			} else {
				if ($post_class->Report()) {
					echo _('Post successfully reported.').'
					<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/'.TC_FIRSTPAGE.'">';
				} else {
					echo _('Unable to report post.  Please go back and try again.');
				}
			}
		} else {
			echo _('This board does not allow post reporting.');
		}
	} else {
		/* They clicked the Delete button */
		if ($_POST['postpassword'] != '') {
			if (md5($_POST['postpassword']) == $post_class->post_password) {
				if (isset($_POST['fileonly'])) {
					if ($post_class->post_image != '' && $post_class->post_image != 'removed') {
						$post_class->DeleteImage();
						$board_class->RegeneratePages();
						if ($post_class->post_threadid != 0) {
							$board_class->RegenerateThread($post_class->post_threadid);
						}
						echo _('Image successfully deleted from your post.').'
						<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/'.TC_FIRSTPAGE.'">';
					} else {
						echo _('Your post already doesn\'t have an image!');
					}
				} else {
					if ($post_class->Delete()) {
						if ($post_class->post_threadid != '0') {
							$board_class->RegenerateThread($post_class->post_threadid);
						}
						$board_class->RegeneratePages();
						
						echo _('Post successfully deleted.').'
						<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/'.TC_FIRSTPAGE.'">';
					} else {
						echo _('There was an error in trying to delete your post');
					}
				}
			} else {
				echo _('Incorrect password.');
			}
		} else {
			echo '<meta http-equiv="refresh" content="0;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/'.TC_FIRSTPAGE.'">';
		}
	}
	die();
} elseif (isset($_GET['postoek'])) {
	ob_start();
	$executiontime_start = microtime_float();
	
	echo $board_class->PageHeader() .
		$board_class->Postbox($_GET['replyto'], $_GET['postoek']) .
		'<div style="text-align: center;">' .
		_('Your Image:') .
		'<br>
		<img src="' . TC_BOARDSFOLDER . 'tcdrawings/' . $_GET['postoek'] . '.png">
		</div>';
	
	$executiontime_stop = microtime_float();
	echo $board_class->Footer(false, ($executiontime_stop - $executiontime_start));
	
	die(ob_get_clean());
} else {
	die('<meta http-equiv="refresh" content="0;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/'.TC_FIRSTPAGE.'">');
}

if (TC_RSS) {
	require_once(TC_ROOTDIR . 'inc/classes/rss.class.php');
	$rss_class = new RSS();
	
	print_page(TC_BOARDSDIR.$_POST['board'].'/rss.xml',$rss_class->GenerateRSS($_POST['board']),$_POST['board']);
}

if (!TC_INSTANTREDIRECT) {
	if ($thread_replyto == '0') {
		print(_('Thread successfully posted.  You are now being redirected.'));
	} else {
		print(_('Reply successfully posted.  You are now being redirected.'));
	}
}

if ($board_class->board_redirecttothread == 1 || $_POST['em'] == 'return' || $_POST['em'] == 'noko') {
	if ($thread_replyto == "0") {
		do_redirect(TC_BOARDSPATH . '/' . $board_class->board_dir . '/res/' . $post_id . '.html');
	} else {
		do_redirect(TC_BOARDSPATH . '/' . $board_class->board_dir . '/res/' . $thread_replyto . '.html');
	}
} else {
	do_redirect(TC_BOARDSPATH . '/' . $board_class->board_dir . '/');
}

/* Depending on the configuration, use either a meta refresh or a direct header */
function do_redirect($url) {
	global $board_class;
	
	if (TC_INSTANTREDIRECT) {
		header('Location: ' . $url);
		die();
	} else {
		echo '<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/">';
	}
}
?>