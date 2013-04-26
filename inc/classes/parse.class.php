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
* Parse class
* +------------------------------------------------------------------------------+
* A post's message text will be passed, which will then be formatted and cleaned
* before being returned.
* +------------------------------------------------------------------------------+
*/
class Parse {
	var $boardtype;
	var $parentid;
	
	function MakeClickable($txt) {
		/* Make http:// urls in posts clickable */
		$txt = preg_replace('#(http://|https://|ftp://)([^(\s<)]*)#', '<a href="\\1\\2">\\1\\2</a>', $txt);
		
		return $txt;
	} 
	
	function BBCode($string){
		$patterns = array(
			'`\[b\](.+?)\[/b\]`is', 
			'`\[i\](.+?)\[/i\]`is', 
			'`\[u\](.+?)\[/u\]`is', 
			'`\[s\](.+?)\[/s\]`is', 
			'`\[code\](.+?)\[/code\]`is', 
			'`\[aa\](.+?)\[/aa\]`is', 
			'`\[spoiler\](.+?)\[/spoiler\]`is', 
			);
		$replaces =  array(
			'<b>\\1</b>', 
			'<i>\\1</i>', 
			'<span style="border-bottom: 1px dotted">\\1</span>', 
			'<strike>\\1</strike>', 
			'<div style="whitespace: pre; font-family: monospace;">\\1</div>', 
			'<div style="font-family: monospace; font-family: Mona,\'MS PGothic\' !important; ">\\1</div>', 
			'<span class="spoiler" onmouseover="this.style.color=\'white\';" onmouseout="this.style.color=\'black\'">\\1</span>', 
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
	
	function ClickableQuote($buffer, $board, $boardtype, $parentid, $ispage = false) {
		global $thread_board_return;
		$thread_board_return = $board;
		
		/* Add html for links to posts in the board the post was made */
		$buffer = preg_replace_callback('/&gt;&gt;([0-9]+)/', array(&$this, 'InterthreadQuoteCheck'), $buffer);
		
		/* Add html for links to posts made in a different board */
		$buffer = preg_replace_callback('/&gt;&gt;\/([a-z]+)\/([0-9]+)/', array(&$this, 'InterboardQuoteCheck'), $buffer);
	
		return $buffer;
	}
	
	function InterthreadQuoteCheck($matches) {
		global $tc_db, $ispage, $thread_board_return;
		
		if ($this->boardtype != 1) {
			$query = "SELECT `parentid` FROM `".KU_DBPREFIX."posts_".mysql_real_escape_string($thread_board_return)."` WHERE `id` = '".mysql_real_escape_string($matches[1])."'";
			$result = $tc_db->GetOne($query);
			if ($result!='') {
				if ($result==0) {
					$realid = $matches[1];
				} else {
					$realid = $result;
				}
			} else {
				return $matches[0];
			}
		} else {
			$realid = $this->parentid;
		}
		
		$return = '<a href="'.KU_BOARDSFOLDER.$thread_board_return.'/res/'.$realid.'.html#'.$matches[1].'" onclick="javascript:highlight(\'' . $matches[1] . '\', true);">'.$matches[0].'</a>';
		
		return $return;
	}
	
	function InterboardQuoteCheck($matches) {
		global $tc_db;

		$result = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($matches[1])."'");
		if ($result==1) {
			$result2 = $tc_db->GetOne("SELECT `parentid` FROM `".KU_DBPREFIX."posts_".mysql_real_escape_string($matches[1])."` WHERE `id` = '".mysql_real_escape_string($matches[2])."'");
			if ($result2!='') {
				if ($result2==0) {
					$realid = $matches[2];
				} else {
					$realid = $result2;
				}
				return '<a href="'.KU_BOARDSFOLDER.$matches[1].'/res/'.$realid.'.html#'.$matches[2].'">'.$matches[0].'</a>';
			}
		}
		
		return $matches[0];
	}
	
	function Wordfilter($buffer, $board) {
		global $tc_db;
		
		$query = "SELECT * FROM `".KU_DBPREFIX."wordfilter`";
		$results = $tc_db->GetAll($query);
		foreach($results AS $line) {
			$array_boards = explode('|', $line['boards']);
			if (in_array($board, $array_boards)) {
				$replace_word = $line['word'];
				$replace_replacedby = $line['replacedby'];
				
				$buffer = ($line['regex'] == 1) ? preg_replace($replace_word, $replace_replacedby, $buffer) : str_ireplace($replace_word, $replace_replacedby, $buffer);
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
	
	/* From http://us.php.net/wordwrap */
	/*function CutWord($str, $maxLength, $char){
	    $wordEndChars = array(" ", "\n", "\r", "\f", "\v", "\0");
	    $count = 0;
	    $newStr = "";
	    $openTag = false;
	    for($i=0; $i<strlen($str); $i++){
	        $newStr .= $str{$i};   
			echo 'newstr: ' . $newStr . '<hr>' . "\n";
	        if($str{$i} == "<"){
	            $openTag = true;
	            continue;
	        }
	        if(($openTag) && ($str{$i} == ">")){
	            $openTag = false;
	            continue;
	        }
	       
	        if(!$openTag){
	            if(!in_array($str{$i}, $wordEndChars)){//If not word ending char
	                $count++;
	                if($count==$maxLength){//if current word max length is reached
	                    $newStr .= $char;//insert word break char
	                    $count = 0;
	                }
	            }else{//Else char is word ending, reset word char count
	                    $count = 0;
	            }
	        }
	       
	    }//End for   
	    die($newStr);
	    return $newStr;
	}*/
	
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
	
	function ParsePost($message, $board, $boardtype, $parentid, $ispage = false) {
		$this->boardtype = $boardtype;
		$this->parentid = $parentid;
		
		$message = trim($message);
		$message = $this->CutWord($message, KU_MAXCHAR, "\n");
		$message = htmlspecialchars($message, ENT_QUOTES);
		if (KU_MAKELINKS) {
			$message = $this->MakeClickable($message);
		}
		$message = $this->ClickableQuote($message, $board, $boardtype, $parentid, $ispage);
		$message = $this->ColoredQuote($message, $boardtype);
		$message = str_replace("\n", '<br>', $message);
		$message = $this->BBCode($message);
		$message = $this->Wordfilter($message, $board);
		$message = $this->CheckNotEmpty($message);
	
		return $message;
	}
}
?>