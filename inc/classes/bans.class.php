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
* Bans Class
* +------------------------------------------------------------------------------+
* Assorted banning-related functions placed into class format
* +------------------------------------------------------------------------------+
*/
class Bans {

	/* If a ban's expiration timestamp is before the current timestamp, remove it */
	function RemoveExpiredBans() {
		global $tc_db;
		
		$results = $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."banlist` WHERE `until` != 0 AND `until` < ".time());
		if ($tc_db->Affected_Rows()>0) {
			$this->UpdateHtaccess();
		}
	}
	
	/* Perform a check for a ban record for a specified IP address */
	function BanCheck($ip, $board = '', $force_display = false) {
		global $tc_db;
		require_once(TC_ROOTDIR . 'inc/encryption.php');
		
		if (!isset($_COOKIE['tc_previousip'])) {
			$_COOKIE['tc_previousip'] = '';
		}
		$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."banlist` WHERE `type` = '0' AND ( `ipmd5` = '" . md5($ip) . "' OR `ipmd5` = '". md5($_COOKIE['tc_previousip']) . "' ) LIMIT 1");
		if (count($results)>0) {
			foreach($results AS $line) {
				if ($line['globalban']!=1) {
					if (in_array($board, explode('|', $line['boards']))) {
						echo $this->DisplayBannedMessage($line['globalban'], '<b>/'.implode('/</b>, <b>/', explode('|', $line['boards'])).'/</b>&nbsp;', $line['reason'], $line['at'], $line['until']);
						die();
					}
				} else {
					echo $this->DisplayBannedMessage($line['globalban'], '<b>/'.implode('/</b>, <b>/', explode('|', $line['boards'])).'/</b>&nbsp;', $line['reason'], $line['at'], $line['until']);
					die();
				}
			}
		}
		$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."banlist` WHERE `type` = '1'");
		if (count($results)>0) {
			foreach($results AS $line) {
				if (eregi(md5_decrypt($line['ip'], TC_RANDOMSEED), $ip)) {
					echo $this->DisplayBannedMessage($line['globalban'], '<b>/'.implode('/</b>, <b>/', explode('|', $line['boards'])).'/</b>&nbsp;', $line['reason'], $line['at'], $line['until']);
					die();
				}
			}
		}

		if ($force_display) {
			/* Instructed to display a page whether banned or not, so we will inform them today is their rucky day */
			echo '<title>'._gettext('YOU ARE NOT BANNED!').'</title><div align="center"><img src="/youarenotbanned.jpg"><br><br>'._gettext('Unable to find record of your IP being banned.').'</div>';
		} else {
			return true;
		}
	}

	/* Add a ip/ip range ban */
	function BanUser($ip, $modname, $globalban, $duration, $boards, $reason, $type=0, $allowread=1) {
		global $tc_db;
		
		require_once(TC_ROOTDIR.'inc/encryption.php');
		$result = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."banlist` WHERE `type` = '".$type."' AND `ipmd5` = '".md5($ip)."'");
		if ($result[0]==0) {
			if ($duration>0) {
				$ban_globalban = '0';
			} else {
				$ban_globalban = '1';
			}
			if ($duration>0) {
				$ban_until = time()+$duration;
			} else {
				$ban_until = '0';
			}
			
			$tc_db->Execute("INSERT INTO `".TC_DBPREFIX."banlist` ( `ip` , `ipmd5` , `type` , `allowread` , `globalban` , `boards` , `by` , `at` , `until` , `reason` ) VALUES ( '".md5_encrypt($ip, TC_RANDOMSEED)."' , '".md5($ip)."' , '".$type."' , '".$allowread."' , '".$globalban."' , '".$boards."' , '".$modname."' , '".time()."' , '".$ban_until."' , '".$reason."' )");
			
			$this->UpdateHtaccess();
			
			return true;
		} else {
			return false;
		}
	}
	
	/* Return the page which will inform the user a quite unfortunate message */
	function DisplayBannedMessage($globalban, $boards, $reason, $at, $until) {
		/* Set a cookie with the users current IP address in case they use a proxy to attempt to make another post */
		setcookie('tc_previousip', $_SERVER['REMOTE_ADDR'], (time() + 604800), TC_BOARDSFOLDER);
		
		require_once(TC_ROOTDIR . 'lib/smarty.php');
		
		$smarty->assign('title', _gettext('YOU ARE BANNED') . '!');
		$smarty->assign('youarebanned', _gettext('YOU ARE BANNED') . ' :\'(');
		if ($globalban==1) {
			$smarty->assign('boards', strtolower(_gettext('All boards')));
		} else {
			$smarty->assign('boards', $boards);
		}
		$smarty->assign('reason', $reason);
		$smarty->assign('at', date("F j, Y, g:i a", $at));
		if ($until > 0) {
			$smarty->assign('expires', 'will expire on <b>' . date("F j, Y, g:i a", $until) . '</b>');
		} else {
			$smarty->assign('expires', '<b>will not expire</b>');
		}
		$smarty->assign('ip', $_SERVER['REMOTE_ADDR']);
		
		return $smarty->fetch('banned.tpl');
	}
	
	function UpdateHtaccess() {
		global $tc_db;
		
		require_once(TC_ROOTDIR."inc/encryption.php");
		$htaccess_contents = file_get_contents(TC_BOARDSDIR.'.htaccess');
		$htaccess_contents_preserve = substr($htaccess_contents, 0, strpos($htaccess_contents, '## !TC_BANS:')+12)."\n";
	
		$htaccess_contents_bans_iplist = '';
		$results = $tc_db->GetAll("SELECT `ip` FROM `".TC_DBPREFIX."banlist` WHERE `allowread` = 0 AND `type` = 0 ORDER BY `ip` ASC");
		foreach($results AS $line) {
				$htaccess_contents_bans_iplist .= "RewriteCond %{REMOTE_ADDR} ".md5_decrypt($line['ip'], TC_RANDOMSEED)."\n";
		}
		if ($htaccess_contents_bans_iplist!='') {
			$htaccess_contents_bans_start = "<IfModule mod_rewrite.c>\nRewriteEngine On\n";
			$htaccess_contents_bans_end = "RewriteRule !^(banned.php|youarebanned.jpg)$ /banned.php [L]\n</IfModule>";
		} else {
			$htaccess_contents_bans_start = '';
			$htaccess_contents_bans_end = '';
		}
		$htaccess_contents_new = $htaccess_contents_preserve.$htaccess_contents_bans_start.$htaccess_contents_bans_iplist.$htaccess_contents_bans_end;
		file_put_contents(TC_BOARDSDIR.'.htaccess', $htaccess_contents_new);
	}
}

?>