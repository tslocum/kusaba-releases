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
* RSS class
* +------------------------------------------------------------------------------+
* Generates latest posts RSS, as well as ModLog RSS
* +------------------------------------------------------------------------------+
*/
class RSS {
	function GenerateRSS($rssboard) {
		if (isset($rssboard)) {
			global $tc_db;
			
			$details = '<?xml version="1.0" encoding="UTF-8" '.'?'.'>
			<rss version="2.0">
			<channel>
			<title>'.TC_NAME.' - '.$rssboard .'</title>
			<link>'.TC_BOARDSPATH.'/'. $rssboard .'</link>
			<description>Live RSS feed for '.TC_BOARDSPATH.'/'.$rssboard.'</description>
			<language>'. TC_LOCALE .'</language>';
			$items = '';
			$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$rssboard. "` WHERE `IS_DELETED` = '0' ORDER BY `id` DESC LIMIT 0,15");
			foreach($results AS $row){
				$items .= '<item>
				<title>'.$row['id'].'</title>
				<link>';
				if ($row['threadid']!='0') {
					$items .= TC_BOARDSPATH.'/'.$rssboard.'/res/'.$row['threadid'] .'.html#'.$row['id'].'</link>';
				} else {
					$items .= TC_BOARDSPATH.'/'.$rssboard.'/res/'.$row['id'].'.html</link>';
				}
				$items .= '<description><![CDATA[';
				if ($row['image']!='') $items .= '['.TC_BOARDSPATH.'/'.$rssboard.'/src/'.$row['image'].'.'.$row['imagetype'].'] <br /><br>';
				if (trim($row['message'])!='') {
					$items .= stripslashes($row['message']).'<br>';
				}
				$items .= ']]></description>
				</item>';
			}
			$items .= '</channel>
			</rss>';
		}
		$rss_complete = $details.$items;
		
		return $rss_complete;
	}
	
	function GenerateModLogRSS($entry) {
		global $tc_db;
		
		$details = '<?xml version="1.0" encoding="UTF-8" '.'?'.'>
		<rss version="2.0">
		<channel>
		<title>'.TC_NAME.' - Modlog</title>
		<link>'.TC_WEBPATH.'</link>
		<description>Live view of all moderative actions on '.TC_WEBPATH.'</description>
		<language>'. TC_LOCALE .'</language>';
		$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."modlog` ORDER BY `timestamp` DESC LIMIT 0,15");
		$items = '';
		foreach($results AS $line) {
			$items .= '
			<item>
			<title>'.date("D m/d H:i",$line['timestamp']).'</title>
			<description><![CDATA['.$line['user'].' - '.$line['entry'].']]></description>
			</item>';
		}
		$items .= '
		</channel>
		</rss>';
		$rss_complete = $details.$items;
		
		return($rss_complete);
	}
}
?>