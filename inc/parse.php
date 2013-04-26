<?php
function make_clickable($buffer) {
	$buffer = eregi_replace("(^|[ \n\r\t])((http(s?)://)(www\.)?([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $buffer);
	$buffer = eregi_replace("(^|[ \n\r\t])((ftp://)(www\.)?([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"\\2\" target=\"_blank\">\\2</a>", $buffer);
	$buffer = eregi_replace("([a-z_-][a-z0-9\._-]*@[a-z0-9_-]+(\.[a-z0-9_-]+)+)","<a href=\"mailto:\\1\">\\1</a>", $buffer);
	$buffer = eregi_replace("(^|[ \n\r\t])(www\.([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"http://\\2\" target=\"_blank\">\\2</a>", $buffer);
	$buffer = eregi_replace("(^|[ \n\r\t])(ftp\.([a-z0-9_-]+(\.[a-z0-9_-]+)+)(/[^/ \n\r]*)*)","\\1<a href=\"ftp://\\2\" target=\"_blank\">\\2</a>", $buffer);

	return $buffer;
}
function bbcode($string){
	$patterns = array(
										'`\[b\](.+?)\[/b\]`is',
										'`\[i\](.+?)\[/i\]`is',
										'`\[u\](.+?)\[/u\]`is',
										'`\[strike\](.+?)\[/strike\]`is',
										'`\[quote\](.+?)\[/quote\]`is',
										'`\[indent](.+?)\[/indent\]`is',
									    );
	$replaces =  array(
										'<strong>\\1</strong>',
										'<em>\\1</em>',
										'<span style="border-bottom: 1px dotted">\\1</span>',
										'<strike>\\1</strike>',
										'<strong>Quote:</strong><div style="margin:0px 10px;padding:5px;background-color:#F7F7F7;border:1px dotted #CCCCCC;width:80%;"><em>\1</em></div>',
										'<pre>\\1</pre>',
										);
	$string = preg_replace($patterns, $replaces , $string);
	
	return $string;
}
function colored_quote($buffer) {
	if (substr($buffer,strlen($buffer)-1)!="\n") {
		$buffer .= "\n";
	}
	$buffer = preg_replace('/^(>[^>](.*))\n/m', '<blockquote class="unkfunc">\\1</blockquote>', $buffer);
	$buffer = str_replace('<blockquote class="unkfunc">>','<blockquote class="unkfunc">&gt;',$buffer);
	
	return $buffer;
}
function clickable_quote($buffer,$board,$threadid) {
	//require_once("dbconnection.php");
	$buffer = preg_replace('/[>][>]([0-9]+)/', '<a href="/'.$board.'/res/'.$threadid.'.html#i\\1">&gt;&gt;\\1</a>', $buffer);
	
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
function parse_post($message,$board,$threadid) {
	$message = strip_tags($message);
	if ($threadid!='0') {
		$message = clickable_quote($message,$board,$threadid);
		$message = colored_quote($message);
	}
	$message = str_replace(chr(13),"<br />",$message);
	$message = make_clickable($message);
	$message = bbcode($message);
	$message = parse_wordfilter($message,$board);
	$message = addslashes($message);
	
	return $message;
}
?>