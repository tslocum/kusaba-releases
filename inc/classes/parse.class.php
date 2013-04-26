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
* Parse class
* +------------------------------------------------------------------------------+
* A post's message text will be passed, which will then be formatted and cleaned
* before being returned.
* +------------------------------------------------------------------------------+
*/
class Parse {
	function MakeClickable($txt) {
		/* Make http:// urls in posts clickable */
		$txt = preg_replace('#(http://)([^(\s<)]*)#', '<a href="\\1\\2">\\1\\2</a>', $txt);
		
		return $txt;
	} 
	
	function BBCode($string){
		$patterns = array(
			'`\[b\](.+?)\[/b\]`is', 
			'`\[i\](.+?)\[/i\]`is', 
			'`\[u\](.+?)\[/u\]`is', 
			'`\[s\](.+?)\[/s\]`is', 
			'`\[code\](.+?)\[/code\]`is', 
			);
		$replaces =  array(
			'<strong>\\1</strong>', 
			'<em>\\1</em>', 
			'<span style="border-bottom: 1px dotted">\\1</span>', 
			'<strike>\\1</strike>', 
			'<div style="whitespace: pre; font-family: monospace;">\\1</div>', 
			);
		$string = preg_replace($patterns, $replaces , $string);
		
		return $string;
	}
	
	function ColoredQuote($buffer, $boardtype) {
		/* Add a \n to keep regular expressions happy */
		if (substr($buffer, -1, 1)!="\n") {
			$buffer .= "\n";
		}
	
		if ($boardtype==1) {
			/* The css for text boards use 'quote' as the class for quotes */
			$class = 'quote';
		} else {
			/* The css for imageboards use 'unkfunc' (???) as the class for quotes */
			$class = 'unkfunc';
		}
		$buffer = preg_replace('/^(&gt;[^>](.*))\n/m', '<span class="'.$class.'">\\1</span>' . "\n", $buffer);
		/* Remove the > from the quoted line if it is a text board */
		if ($boardtype==1) {
			$buffer = str_replace('<span class="'.$class.'">&gt;', '<span class="'.$class.'">', $buffer);
		}
	
		return $buffer;
	}
	
	function ClickableQuote($buffer, $board, $boardtype, $threadid, $ispage = false) {
		global $thread_board_return;
		$thread_board_return = $board;
		
		/* Add html for links to posts in the board the post was made */
		$buffer = preg_replace_callback('/&gt;&gt;([0-9]+)/', array(&$this, 'InterthreadQuoteCheck'), $buffer);
		
		/* Add html for links to posts made in a different board */
		$buffer = preg_replace_callback('/&gt;&gt;\/([a-z]+)\/([0-9]+)/', array(&$this, 'InterboardQuoteCheck'), $buffer);
	
		return $buffer;
	}
	
	function InterthreadQuoteCheck($matches) {
		global $tc_db, $thread_board_return;
	
		$query = "SELECT `threadid` FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($thread_board_return)."` WHERE `id` = '".mysql_real_escape_string($matches[1])."'";
		$result = $tc_db->GetOne($query);
		if ($result!='') {
			if ($result==0) {
				$realid = $matches[1];
			} else {
				$realid = $result;
			}
			return '<a href="'.TC_BOARDSFOLDER.$thread_board_return.'/res/'.$realid.'.html#'.$matches[1].'">'.$matches[0].'</a>';
		}
		
		return $matches[0];
	}
	
	function InterboardQuoteCheck($matches) {
		global $tc_db;
	
		$query = "SELECT COUNT(*) FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($matches[1])."'";
		$result = $tc_db->GetOne($query);
		if ($result==1) {
			$query = "SELECT `threadid` FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($matches[1])."` WHERE `id` = '".mysql_real_escape_string($matches[2])."'";
			$result2 = $tc_db->GetOne($query);
			if ($result2!='') {
				if ($result2==0) {
					$realid = $matches[2];
				} else {
					$realid = $result2;
				}
				return '<a href="'.TC_BOARDSFOLDER.$matches[1].'/res/'.$realid.'.html#'.$matches[2].'">'.$matches[0].'</a>';
			}
		}
		
		return $matches[0];
	}
	
	function Wordfilter($buffer, $board) {
		global $tc_db;
		
		$query = "SELECT * FROM `".TC_DBPREFIX."wordfilter`";
		$results = $tc_db->GetAll($query);
		foreach($results AS $line) {
			$array_boards = explode('|', $line['boards']);
			if (in_array($board, $array_boards)) {
				$replace_word = $line['word'];
				$replace_replacedby = $line['replacedby'];
				if ($line['regex']==1) {
					$buffer = preg_replace($replace_word, $replace_replacedby, $buffer);
				} else {
					$buffer = str_ireplace($replace_word, $replace_replacedby, $buffer);
				}
			}
		}
		
		return $buffer;
	}
	
	function CheckNotEmpty($buffer) {
		$buffer_temp = str_replace("\n", "", $buffer);
		$buffer_temp = str_replace("<br />", "", $buffer_temp);
		$buffer_temp = str_replace("<br>", "", $buffer_temp);
		$buffer_temp = str_replace(" ", "", $buffer_temp);
		
		if ($buffer_temp=="") {
			return "";
		} else {
			return $buffer;
		}
	}
	
	function EscapeQuotes($receive) {
		if (!is_array($receive)) {
			$thearray = array($receive);
		} else {
			$thearray = $receive;
		}
		
		foreach (array_keys($thearray) as $string) {
			$thearray[$string] = addslashes($thearray[$string]);
		}
		
		if (!is_array($receive)) {
			return $thearray[0];
		} else {
			return $thearray;
		}
	}
	
	function CutWord($txt, $where) {
		if (empty($txt)) return false;
		for ($c = 0, $a = 0, $g = 0; $c<strlen($txt); $c++) {
			$d[$c+$g]=$txt[$c];
			if ($txt[$c]!=' '&&$txt[$c]!=chr(10)) $a++;
			else if ($txt[$c]==' '||$txt[$c]==chr(10)) $a = 0;
			if ($a==$where) {
			$g++;
			$d[$c+$g]="\n";
			$a = 0;
			}
		}
		
		return implode("", $d);
	}
	
	function ParsePost($message, $board, $boardtype, $threadid, $ispage = false) {
		$message = $this->CutWord($message, TC_MAXCHAR);
		$message = trim($message);
		$message = htmlspecialchars($message, ENT_QUOTES);
		$message = $this->ClickableQuote($message, $board, $boardtype, $threadid, $ispage);
		$message = $this->ColoredQuote($message, $boardtype);
		if (TC_MAKELINKS) {
			$message = $this->MakeClickable($message);
		}
		$message = str_replace("\n", '<br>', $message);
		$message = $this->BBCode($message);
		$message = $this->Wordfilter($message, $board);
		$message = $this->CheckNotEmpty($message);
		$message = $this->EscapeQuotes($message);
	
		return $message;
	}
}
?>