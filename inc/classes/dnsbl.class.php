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
* DNS Block Class (Created by N3X15 <http://nehq.servebeer.com/>)
* +------------------------------------------------------------------------------+
* When enabled, DNS Block will recognize tor and other proxy connections, allowing
* the script to take action when these conditions are found, such as banning the
* user to prevent ban evasion.   
* +------------------------------------------------------------------------------+
*/
define('DNSBL_OTHER', 2);
define('DNSBL_BLACKLIST', 0);
define('DNSBL_WHITELIST', 1);

class DNSBL {
	var $dnsbl_list = array();
	var $whitelist = array();
	
	function DNSBL() {
		$this->ip = $_SERVER['REMOTE_ADDR'];

		$this->debug[]= 'Initialized.';
	}
	
	function Block() {
		global $tc_db;
		
		if ($this->no_silence===true) {
			echo '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
			<html>
			<head>
			<title>OPEN PROXY :: BANNED</title>
			<style type="text/css">
			body {
				color:maroon;
				font-family:sans-serif;
				font-size:small;
			}
			h1 {
				text-align:center;
				color:white;
				background:red;
				border:1px solid black;
			}
			</style>
			</head>
			<body>
			<h1>IP AutoBlocked</h1>
			<p>The IP <input type="text" value="' . $this->ip . '" readonly="readonly" /> has been banned for: &quot;' . $this->blockreason . '&quot;.  If this is in error, please contact the site admin for whitelist addition.</p><p>Please do not use proxies on this site.</p>
			</body>
			</html>';
		}
		exit();
	}
	
	function Whitelist($ip,$reason) {
		global $tc_db;

		$tc_db->Execute("INSERT INTO " . TC_DBPREFIX . "dnsbl SET ip='" . $ip . "',list=2,proxy=0,reason='" . $reason . "',date=UNIX_TIMESTAMP()");
	}
	
	function Check($autoblock=true) {
		global $tc_db;
		$this->debug[] = 'Checking ' . $this->ip . '...<br />';

		if (in_array($this->ip,$this->whitelist)) return true;
		
		$ipd = $tc_db->GetAll("SELECT * FROM " . TC_DBPREFIX . "dnsbl WHERE ip='" . $this->ip. "'");
		$ipd = $ipd[0];
		$this->ipd = $ipd;
		if (count($ipd)>0) {
			if ($ipd['proxy']>0 && $ipd['list']==0) {
				$this->debug[] = 'FAILED.  ' . $this->ip . ' is a ' . $ipd['proxy'];
				$this->blockreason = $ipd['reason'];
				if($autoblock===true) $this->Block(); /* Already in cache.  Block it. */
				
				return false;
			}
			$this->debug[] = 'Passed.';
			
			return true;
		} else {
			$ipa = explode('.',$this->ip);
			if ($ipa[0] . $ipa[1]!= '192168'){
				$rip = $ipa[3]. '.'. $ipa[2] . '.' . $ipa[1] . '.' . $ipa[0];
				$proxy = 0;
				foreach ($this->dnsbl_list as $name=>$dnsbl) {
					$qstr = $rip . '.' . $dnsbl['url'];
					$tor = gethostbyname($qstr);
					
					if ($tor==$qstr) {
						$this->debug[] = 'Not a proxy. ' . $tor;
					} else {
						if ($dnsbl['returns'][$tor]!='') {
							$this->blockreason = $dnsbl['returns'][$tor];
							$this->returned_ip = $tor;
							$this->dnsbl_name = $name;
							$this->debug[] = 'PROXY FOUND. ' . $this->blockreason;
							$proxy=1;
						}
					}
				}
				
				$qry = "INSERT INTO " . TC_DBPREFIX . "dnsbl SET ip='%s',date=%d,proxy=%d,reason='%s',list=" . DNSBL_BLACKLIST;
				$q = sprintf($qry,$this->ip,time(),$proxy,$this->blockreason);
				
				$tc_db->Execute($q);
				
				$this->debug[] = 'SQL: '.$q;
				
				if ($proxy>0 && $autoblock===true) $this->Block();
				if ($proxy>0) return false;
				
				$this->debug[] = 'Passed.';
				
				return true;
			}
			return true;
		}
	}
	
	function FetchList($list_type=NULL) {
		global $tc_db;
		
		switch ($list_type) {
			case DNSBL_WHITELIST:
				$lt=1;
			break;
			case DNSBL_BLACKLIST:
				$lt=0;
			break;
			case DNSBL_OTHER:
				$lt=2;
			break;
			case NULL:
				$none=1;
			default:
				die('Invalid blacklist request. (' . $list_type . ')');
		}
		
		if($list_type==NULL) {
			$r= $tc_db->GetAssoc("SELECT * FROM " . TC_DBPREFIX . "dnsbl");
		} else {
			$r= $tc_db->GetAssoc("SELECT * FROM " . TC_DBPREFIX . "dnsbl WHERE `proxy` > 0");
		}
		
		return $r;
	}
}

?>