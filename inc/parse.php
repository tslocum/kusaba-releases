<?php
/*function make_clickable($buffer) {
	$buffer = eregi_replace("(^|[ \n\r\t])((http(s?)://)(www\.)?([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $buffer);
	$buffer = eregi_replace("(^|[ \n\r\t])((ftp://)(www\.)?([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $buffer);
	$buffer = eregi_replace("([a-z_-][a-z0-9\._-]*@[a-z0-9_-]+(\.[a-z0-9_-]+)+)","<a href=\"mailto:\\1\">\\1</a>", $buffer);
	$buffer = eregi_replace("(^|[ \n\r\t])(www\.([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $buffer);
	$buffer = eregi_replace("(^|[ \n\r\t])(ftp\.([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"ftp://\\2\" target=\"_blank\">\\2</a>", $buffer);

	return $buffer;
}*/
function make_clickable($txt) {
  $txt = preg_replace('#(http://)([^(\s<)]*)#', '<a href="\\1\\2">\\1\\2</a>', $txt);

  return $txt;
} 
function bbcode($string){
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
										'<font face="Times New Roman"><pre>\\1</pre></font>',
										);
	$string = preg_replace($patterns, $replaces , $string);
	
	return $string;
}
function colored_quote($buffer) {
	if (substr($buffer,strlen($buffer)-1)!="\n") {
		$buffer .= "\n";
	}
	$buffer = preg_replace('/^(&gt;[^>](.*))\n/m', '<blockquote class="unkfunc">\\1</blockquote>', $buffer);
	$buffer = str_replace('<blockquote class="unkfunc">>','<blockquote class="unkfunc">&gt;',$buffer);
	
	return $buffer;
}
function clickable_quote($buffer,$board,$threadid,$ispage = false) {
	//require_once("dbconnection.php");
	if ($ispage==false) {
		$buffer = preg_replace('/&gt;&gt;([0-9]+)/', '<a href="/'.$board.'/res/'.$threadid.'.html#i\\1">&gt;&gt;\\1</a>', $buffer);
	} else {
		$buffer = preg_replace('/&gt;&gt;([0-9]+)/', '<a href="/'.$board.'/res/'.$threadid.'.html#\\1">&gt;&gt;\\1</a>', $buffer);
	}
	/*$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_id = $line['id'];
		}
		$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".preg_replace('/>>([0-9]+)/', '\\1', $buffer)."' AND `boardid` = '".$board_id."' AND `threadid` = '".$threadid."' OR `threadid` = '0'",$dblink);
		$rows2 = mysql_num_rows($result);
		if ($rows2>0) {
			die('LINK FOUND!');
			$buffer = preg_replace('/^>>([0-9]+)/', '&gt;&gt;<a href="/'.$board.'/res/'.$threadid.'.html#i\\1">\\1</a>', $buffer);
		}
	}*/

	return $buffer;
}
function parse_wordfilter($buffer,$board) {
	global $dblink;
	$result = mysql_query("SELECT * FROM `wordfilter`",$dblink);
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$array_boards = explode('|',$line['boards']);
		if (in_array($board,$array_boards)) {
			$replace_word = $line['word'];
			$replace_replacedby = $line['replacedby'];
			$buffer = str_ireplace($replace_word,$replace_replacedby,$buffer);
		}
	}
	return $buffer;
}
function replace_brackets($buffer) {
	$buffer = str_ireplace('<','&lt;',$buffer);
	$buffer = str_ireplace('>','&gt;',$buffer);
	return $buffer;
}
function check_notempty($buffer) {
	$buffer_temp = str_replace("\n","",$buffer);
	$buffer_temp = str_replace("<br />","",$buffer_temp);
	$buffer_temp = str_replace(" ","",$buffer_temp);
	if ($buffer_temp=="") {
		return "";
	} else {
		return $buffer;
	}
}
function escape_quotes($receive) {
   if (!is_array($receive))
       $thearray = array($receive);
   else
       $thearray = $receive;
  
   foreach (array_keys($thearray) as $string) {
       $thearray[$string] = addslashes($thearray[$string]);
       //$thearray[$string] = preg_replace("/[\\/]+/","/",$thearray[$string]);
   }
  
   if (!is_array($receive))
       return $thearray[0];
   else
       return $thearray;
}
function cut_word($txt, $where) {
   if (empty($txt)) return false;
   for ($c = 0, $a = 0, $g = 0; $c<strlen($txt); $c++) {
	   $d[$c+$g]=$txt[$c];
	   if ($txt[$c]!=" ") $a++;
	   else if ($txt[$c]==" ") $a = 0;
	   if ($a==$where) {
	   $g++;
	   $d[$c+$g]="\n";
	   $a = 0;
	   }
   }
   return implode("", $d);
}
function parse_post($message,$board,$threadid,$ispage = false) {
	$message = str_replace("&","&amp;",$message);
	$message = replace_brackets($message);
	$message = trim($message);
	//$message = str_replace(chr(9),"&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;",$message);
	$message = cut_word($message, 100);
	if ($threadid!='0') {
		$message = clickable_quote($message,$board,$threadid,$ispage);
		$message = colored_quote($message);
	}
	//$message = make_clickable($message);
	$message = nl2br($message);
	$message = bbcode($message);
	$message = parse_wordfilter($message,$board);
	$message = check_notempty($message);
	$message = str_replace("\\","\\\\",$message);
	$message = escape_quotes($message);
	
	return $message;
}
?>
