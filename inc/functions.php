<?php

//For PHP4 installations
if (!function_exists("file_put_contents")) {
	define('FILE_APPEND', 1);
	function file_put_contents($n, $d, $flag = false) {
	   $mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
	   $f = @fopen($n, $mode);
	   if ($f === false) {
		   return 0;
	   } else {
		   if (is_array($d)) $d = implode($d);
		   $bytes_written = fwrite($f, $d);
		   fclose($f);
		   return $bytes_written;
	   }
	}
}
function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}
// <3 coda for this wonderful snippet
// print $contents to $filename by using a temporary file and renaming it
function print_page($filename,$contents,$board) {
	require("config.php");
	$tempfile = tempnam($chan_boardsdir."/".$board."/res", "tmp"); //note: THIS actually creates the file
	$fp = fopen($tempfile, "w");
	fwrite($fp, $contents);
	fclose($fp);
	rename($tempfile, $filename);
	chmod($filename, 0664); //it was created 0600
}
if(!function_exists('str_ireplace')) {
	function str_ireplace( $find, $replace, $string ) {
		// Case-insensitive str_replace()
		
		$parts = explode( strtolower($find), strtolower($string) );
		
		$pos = 0;
		
		foreach( $parts as $key=>$part ){
		$parts[ $key ] = substr($string, $pos, strlen($part));
		$pos += strlen($part) + strlen($find);
		}
		
		return( join( $replace, $parts ) );
	}
}
//Thanks milianw - php.net
function closeOpenTags($html){
	//put all opened tags into an array
	preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU",$html,$result);
	$openedtags=$result[1];
	
	//put all closed tags into an array
	preg_match_all("#</([a-z]+)>#iU",$html,$result);
	$closedtags=$result[1];
	$len_opened = count($openedtags);
	// all tags are closed
	if(count($closedtags) == $len_opened){
		return $html;
	}
	$openedtags = array_reverse($openedtags);
	//close tags
	for($i=0;$i<$len_opened;$i++) {
		if (!in_array($openedtags[$i],$closedtags)){
			$html .= '</'.$openedtags[$i].'>';
		} else {
			unset($closedtags[array_search($openedtags[$i],$closedtags)]);
		}
	}
	return $html;
}

//Header
function print_stylesheets($prefered_stylesheet = 'Burichan') {
	require("config.php");
	if ($prefered_stylesheet!='Burichan'&&$prefered_stylesheet!='Futaba'&&$prefered_stylesheet!='Gurochan'&&$prefered_stylesheet!='Photon'&&$prefered_stylesheet!='Fuhrerchan') {
		$prefered_stylesheet = 'Burichan';
	}
	if ($prefered_stylesheet=='Burichan') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_boardspath.'/css/burichan.css" title="Burichan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/futaba.css" title="Futaba">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/gurochan.css" title="Gurochan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/photon.css" title="Photon">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/fuhrerchan.css" title="Fuhrerchan">';
	} else if ($prefered_stylesheet=='Futaba') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_boardspath.'/css/futaba.css" title="Futaba">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/burichan.css" title="Burichan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/gurochan.css" title="Gurochan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/photon.css" title="Photon">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/fuhrerchan.css" title="Fuhrerchan">';
	} else if ($prefered_stylesheet=='Gurochan') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_boardspath.'/css/gurochan.css" title="Gurochan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/burichan.css" title="Burichan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/futaba.css" title="Futaba">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/photon.css" title="Photon">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/fuhrerchan.css" title="Fuhrerchan">';
	} else if ($prefered_stylesheet=='Photon') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_boardspath.'/css/photon.css" title="Photon">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/burichan.css" title="Burichan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/futaba.css" title="Futaba">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/gurochan.css" title="Gurochan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/fuhrerchan.css" title="Fuhrerchan">';
	} else if ($prefered_stylesheet=='Fuhrerchan') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_boardspath.'/css/fuhrerchan.css" title="Fuhrerchan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/burichan.css" title="Burichan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/futaba.css" title="Futaba">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/gurochan.css" title="Gurochan">
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_boardspath.'/css/photon.css" title="Photon">';
	}
	
}

//Management
function management_adminsonly() {
	require("config.php");
	$result = mysql_query("SELECT `isadmin` FROM `".$chan_prefix."staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			if ($line['isadmin']!="1") {
				die();
			}
		}
	} else {
		die();
	}
}
function management_isadmin() {
	require("config.php");
	$result = mysql_query("SELECT `isadmin` FROM `".$chan_prefix."staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			if ($line['isadmin']=="1") {
				return true;
			} else {
				return false;
			}
		}
	} else {
		die();
	}
}
function management_addlogentry($entry,$category = 0) {
	// Categories
	// 0 - no category
	// 1 - login
	// 2 - cleanup/rebuild boards and html files
	// 3 - board adding/deleting
	// 4 - board updates
	// 5 - locking/stickying
	// 6 - staff changes
	// 7 - thread deletion/post deletion
	// 8 - bans
	// 9 - news
	// 10 - global changes
	// 11 - wordfilter
	require("config.php");
	if ($entry!="") {
		mysql_query("INSERT INTO `".$chan_prefix."modlog` ( `entry` , `user` , `category` , `timestamp` ) VALUES ( '".mysql_escape_string($entry)."' , '".$_SESSION['manageusername']."' , '".mysql_escape_string($category)."' , '".time()."' )",$dblink);
	}
}
function moderator_ismodofboard($board,$username) {
	require("config.php");
	$result = mysql_query("SELECT `isadmin`,`boards` FROM `".$chan_prefix."staff` WHERE `username` = '".$username."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			if ($line['isadmin']=="1") {
				return true;
			} else {
				$array_boards = explode('|',$line['boards']);
				if (in_array($board,$array_boards)) {
					return true;
				} else {
					return false;
				}
			}
		}
	} else {
		return false;
	}
}
function moderator_boardlist($username) {
	is_file("config.php") ? require("config.php") : require("../config.php");
	$staff_boardsmoderated = array();
	$result = mysql_query("SELECT `boards` FROM `".$chan_prefix."staff` WHERE `username` = '".$username."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		if (management_isadmin()) {
			$resultboard = mysql_query("SELECT `name` FROM `".$chan_prefix."boards`",$dblink);
			while ($lineboard = mysql_fetch_assoc($resultboard)) {
				$staff_boardsmoderated = array_merge($staff_boardsmoderated,array($lineboard['name']));
			}
		} else {
			while ($line = mysql_fetch_assoc($result)) {
				$array_boards = explode('|',$line['boards']);
			}
			foreach ($array_boards as $this_board_name) {
				$staff_boardsmoderated = array_merge($staff_boardsmoderated,array($this_board_name));
			}
		}
	}
	return $staff_boardsmoderated;
}
function moderator_deleteandbanlinks($chan_boardsfolder,$board_dir,$post_id, $is_thread) {
	is_file("config.php") ? require("config.php") : require("../config.php");
	$result = mysql_query("SELECT `id` FROM `".$chan_prefix."boards` WHERE `name` = '".$board_dir."'",$dblink);
	while ($line = mysql_fetch_assoc($result)) {
		$board_id = $line['id'];
	}
	$result = mysql_query("SELECT `ip` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `IS_DELETED` = '0' AND `boardid` = '".$board_id."' AND `id` = '".$post_id."'",$dblink);
	while ($line = mysql_fetch_assoc($result)) {
		$poster_ip = $line['ip'];
	}
	if ($is_thread==true) {
		$post_threadorpost = 'thread';
	} else {
		$post_threadorpost = 'post';
	}
	return "<script type=\"text/javascript\">
	if (getCookie(\"tcmod\")==\"yes\") {
		document.write('&nbsp;&#91;<a href=\"".$chan_boardsfolder."/manage.php?action=delposts&boarddir=".$board_dir."&del".$post_threadorpost."id=".$post_id."\" title=\"Delete\" onclick=\"return confirm(\\'Are you sure you want to delete this ".$post_threadorpost."?\\');\">D<\/a>&nbsp;<a href=\"".$chan_boardsfolder."/manage.php?action=delposts&boarddir=".$board_dir."&del".$post_threadorpost."id=".$post_id."&postid=".$post_id."\" title=\"Delete &amp; Ban\" onclick=\"return confirm(\\'Are you sure you want to delete and ban the poster of this ".$post_threadorpost."?\\');\">&amp;<\/a>&nbsp;<a href=\"".$chan_boardsfolder."/manage.php?action=bans&banboard=".$board_dir."&banpost=".$post_id."\" title=\"Ban\">B<\/a>&#93;');
	}
	</script>";
}
function make_boardlist_dropdown($name,$boards) {
	echo '<select name="'.$name.'"><option value="">Select a Board</option>';
	if ($boards!="") {
		foreach ($boards as $board) {
			echo '<option value="'.$board.'">/'.$board.'/</option>';
		}
	}
	echo '</select>';
}
function make_boardlist_checkbox($prefix,$boards) {
	if ($boards!="") {
		foreach ($boards as $board) {
			echo '<label for="'.$prefix.$board.'">'.$board.'</label><input type="checkbox" name="'.$prefix.$board.'" /> ';
		}
	}
}

//Configuration
function config_getvalue($key) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `".$chan_prefix."config` WHERE `key` = '".$key."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			return $line['value'];
		}
	} else {
		return false;
	}
}
function config_setvalue($key,$value) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `".$chan_prefix."config` WHERE `key` = '".$key."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		mysql_query("UPDATE `config` SET `value` = '".$value."' WHERE `key` = '".$key."'",$dblink);
		return true;
	} else {
		return false;
	}
}

//Maintenance
function delunusedimages($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT `id`,`name` FROM `".$chan_prefix."boards`",$dblink);
	while ($lineboard = mysql_fetch_assoc($resultboard)) {
		$imagelist = array();
		$imagethumbnaillist = array();
		$result = mysql_query("SELECT `image`,`imagetype` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lineboard['id']."'",$dblink);
		while ($line = mysql_fetch_assoc($result)) {
			$imagelist = array_merge($imagelist,array($line['image'].'.'.$line['imagetype']));
			$imagethumbnaillist = array_merge($imagethumbnaillist,array($line['image'].'s.'.$line['imagetype']));
		}
		$dir = './'.$lineboard['name'].'/src';
		$files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $image) {
				if (in_array(basename($image),$imagelist)==false) {
					if (time()-filemtime($chan_boardsdir.'/'.$lineboard['name'].'/src/'.basename($image))>120) {
						if ($verbose==true) {
							echo $image.' NOT IN DATABASE! Deleted.<br />';
						}
						unlink($chan_boardsdir.'/'.$lineboard['name'].'/src/'.basename($image));
					}
				}
			}
		}
		$dir = './'.$lineboard['name'].'/thumb';
		$files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $image) {
				if (in_array(basename($image),$imagethumbnaillist)==false) {
					if (time()-filemtime($chan_boardsdir.'/'.$lineboard['name'].'/thumb/'.basename($image))>120) {
						if ($verbose==true) {
							echo $image.' (thumb) NOT IN DATABASE! Deleted.<br />';
						}
						unlink($chan_boardsdir.'/'.$lineboard['name'].'/thumb/'.basename($image));
					}
				}
			}
		}
	}
	return true;
}
function delunusedimages_beta($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT `id`,`name` FROM `".$chan_prefix."boards`",$dblink);
	while ($lineboard = mysql_fetch_assoc($resultboard)) {
		if ($verbose) {
			echo '<b>Looking for unused images in /'.$lineboard['name'].'/</b><br>';
		}
		$imagemd5list = array();
		$result = mysql_query("SELECT `imagemd5` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lineboard['id']."' AND `image` != '' AND `image` != 'removed' AND `imagemd5` != ''",$dblink);
		while ($line = mysql_fetch_assoc($result)) {
			$imagemd5list = array_merge($imagemd5list,array($line['imagemd5']));
		}
		$dir = './'.$lineboard['name'].'/src';
		$files = glob ("$dir/{*.jpg,*.png,*.gif,*.swf}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $image) {
				if (in_array(md5_file($chan_boardsdir.'/'.$lineboard['name'].'/src/'.basename($image)),$imagemd5list)==false) {
					if (time()-filemtime($chan_boardsdir.'/'.$lineboard['name'].'/src/'.basename($image))>120) {
						if ($verbose==true) {
							echo $image.' NOT IN DATABASE! Deleted.<br />';
						}
						unlink($chan_boardsdir.'/'.$lineboard['name'].'/src/'.basename($image));
						unlink($chan_boardsdir.'/'.$lineboard['name'].'/thumb/'.substr(basename($image),0,-4).'s'.substr(basename($image),strlen(basename($image))-4));
					}
				}
			}
		}
	}
	return true;
}
function delorphanreplies($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT `id`,`name` FROM `".$chan_prefix."boards`",$dblink);
	while ($lineboard = mysql_fetch_assoc($resultboard)) {
		if ($verbose) {
			echo '<b>Looking for orphans in /'.$lineboard['name'].'/</b><br>';
		}
		$result = mysql_query("SELECT `id`,`threadid` FROM `".$chan_prefix."posts` WHERE `boardid` = '".$lineboard['id']."' AND `threadid` != '0' AND `IS_DELETED` = '0'",$dblink);
		while ($line = mysql_fetch_assoc($result)) {
			$exists_result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `boardid` = '".$lineboard['id']."' AND `id` = '".$line['threadid']."' AND `IS_DELETED` = '0' LIMIT 1",$dblink);
			$exists_rows = mysql_num_rows($exists_result);
			if ($exists_rows=='0') {
				mysql_query("UPDATE `".$chan_prefix."posts` SET `IS_DELETED` = '1' WHERE `boardid` = '".$lineboard['id']."' AND `id` = '".$line['id']."' LIMIT 1",$dblink);
				if ($verbose) {
					echo 'Reply #'.$line['id'].'\'s thread (#'.$line['threadid'].') does not exist!  It has been deleted.<br>';
				}
			}
		}
	}
	return true;
}

//Boards
function regenerate_board($board,$pagesonly = false) {
	is_file("config.php") ? require("config.php") : require("../config.php");
	$config_numthreadsdisplayed = config_getvalue("numthreadsdisplayed");
	$resultboard = mysql_query("SELECT * FROM `".$chan_prefix."boards` WHERE `name` = '".$board."'",$dblink);
	while ($lineboard = mysql_fetch_assoc($resultboard)) {
		$board_id = $lineboard['id'];
		$board_type = $lineboard['type'];
		$board_dir = $lineboard['name'];
		$board_desc = $lineboard['desc'];
		$board_locked = $lineboard['locked'];
		//Rebuild pages
		$result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC",$dblink);
		$numpostsleft = mysql_num_rows($result);
		if ($board_type=='1') {
			$numthreadsdisplayed = '15';
		} else {
			$numthreadsdisplayed = $config_numthreadsdisplayed;
		}
		$boardstooutput = floor(($numpostsleft-1) / $numthreadsdisplayed);
		$boardpage = 0;
		if ($board_type=='1') {
			$hide_extra = true;
		} else {
			$hide_extra = false;
		}
		if ($numpostsleft>0) {
			while ($numpostsleft>0) {
				if (!ob_start()) {
					die("Unable to initiate the output buffering!");
				}
				$executiontime_start = microtime_float();
				require_once("header.php");
				echo chan_header($board_dir);
				require_once("postbox.php");
				echo chan_postbox($board_dir);
				if ($board_type!='1') {
					echo '<form id="delform" action="'.$chan_boardsfolder.'/board.php" method="post"><input type="hidden" name="board" value="'.$board_dir.'" />';
				}
				$query = "SELECT `id` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".($boardpage*$numthreadsdisplayed).','.$numthreadsdisplayed;
				$result_leftposts = mysql_query($query,$dblink);
				$thread_relative_id = 0;
				while ($line = mysql_fetch_assoc($result_leftposts)) {
					$thread_id = $line['id'];
					echo buildthread($board_dir,$thread_id,true,false,$thread_relative_id);
					$thread_relative_id++;
				}
				if ($board_type!='1') {
					echo '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br />Password <input type="password" name="postpassword" size="8" />&nbsp;<input value="Delete" type="submit" /></td></tr></tbody></table></form>';
					echo '<script type="text/javascript">set_delpass("delform")</script>';
					echo '<table border="1"><tbody><tr><td>';
					if ($boardpage==0) {
						echo 'Previous';
					} else {
						if ($boardpage-1!=0) {
							echo '<form method="get" action="'.$chan_boardsfolder.'/'.$board_dir.'/'.($boardpage-1).'.html"><input value="Previous" type="submit" /></form>';
						} else {
							echo '<form method="get" action="'.$chan_boardsfolder.'/'.$board_dir.'/board.html"><input value="Previous" type="submit" /></form>';
						}
					}
					echo '</td><td>';
					$i = 0;
					while ($i <= $boardstooutput) {
						if ($boardpage==$i) {
							echo '&#91;'.$i.'&#93;';
						} else {
							if ($i!=0) {
								echo '&#91;<a href="'.$chan_boardsfolder.'/'.$board_dir.'/'.$i.'.html">'.$i.'</a>&#93;';
							} else {
								echo '&#91;<a href="'.$chan_boardsfolder.'/'.$board_dir.'/board.html">'.$i.'</a>&#93;';
							}
						}
						$i++;
					}
					echo '</td><td>';
					if ($boardpage==$boardstooutput) {
						echo "Next";
					} else {
						echo '<form method="get" action="'.$chan_boardsfolder.'/'.$board_dir.'/'.($boardpage+1).'.html"><input value="Next" type="submit" /></form>';
					}
					echo '</td></tr></tbody></table>';
				}
				$executiontime_stop = microtime_float();
				require_once("footer.php");
				echo chan_footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
				$page = ob_get_clean();
				if ($boardpage==0) {
					print_page($chan_boardsdir."/".$board_dir."/board.html",$page,$board_dir);
				} else {
					print_page($chan_boardsdir."/".$board_dir."/".$boardpage.".html",$page,$board_dir);
				}
				$page = "";
				$boardpage++;
				if ($board_type=='1') {
					$numpostsleft = 0;
				} else {
					$numpostsleft -= $numthreadsdisplayed;
				}
			}
		} else { //Make a blank index
			ob_start();
			$executiontime_start = microtime_float();
			require_once("header.php");
			echo chan_header($board_dir);
			require_once("postbox.php");
			echo chan_postbox($board_dir);
			require("footer.php");
			$executiontime_stop = microtime_float();
			echo chan_footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
			$page = ob_get_clean();
			print_page($chan_boardsdir."/".$board_dir."/board.html",$page,$board_dir);
		}
		//If text board, rebuild thread list html files
		if ($board_type=='1') {
			$result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC",$dblink);
			$numpostsleft = mysql_num_rows($result);
			$liststooutput = floor(($numpostsleft-1) / 40);
			$listpage = 0;
			$currentpostwave = 0;
			while ($numpostsleft>0) {
				ob_start();
				$executiontime_start = microtime_float();
				require_once("header.php");
				echo chan_header($board_dir,0,$currentpostwave,$listpage,$liststooutput);
				require("footer.php");
				$executiontime_stop = microtime_float();
				echo chan_footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
				$page = ob_get_clean();
				if ($listpage==0) {
					print_page($chan_boardsdir.'/'.$board_dir.'/list.html',$page,$board_dir);
				} else {
					print_page($chan_boardsdir.'/'.$board_dir.'/list'.$listpage.'.html',$page,$board_dir);
				}
				$currentpostwave += 40;
				$numpostsleft -= 40;
				$listpage++;
			}
		}
		//Rebuild /res/
		if ($pagesonly==false) {
			$res_threadlist = array();
			$result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lineboard['id']."' AND `threadid` = '0' ORDER BY `lastbumped` DESC",$dblink);
			while ($line = mysql_fetch_assoc($result)) {
				$res_threadlist = array_merge($res_threadlist,array($line['id'].'.html'));
				regenerate_thread($lineboard['name'],$line['id']);
			}
			$dir = $chan_boardsdir.'/'.$board_dir.'/res';
			$files = glob ("$dir/{*.html}",  GLOB_BRACE);
			if (is_array($files)) { 
				foreach ($files as $htmlfile) {
					if (!in_array(basename($htmlfile),$res_threadlist)) {
						unlink($htmlfile);
					}
				}
			}
		}
		//Delete old pages
		$dir = $chan_boardsdir.'/'.$board_dir;
		$files = glob ("$dir/{*.html}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $htmlfile) {
				if (preg_match("/[0-9+].html/",$htmlfile)) {
					if (substr(basename($htmlfile),0,strpos(basename($htmlfile),'.html'))>$boardstooutput) {
						unlink($htmlfile);
					}
				}
				if (preg_match("/list[0-9+].html/",$htmlfile)) {
					if (substr(basename($htmlfile),4,strpos(basename($htmlfile),'.html'))>$liststooutput) {
						unlink($htmlfile);
					}
				}
			}
		}
	}
}
function regenerate_all_boards($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT `name` FROM `".$chan_prefix."boards`",$dblink);
	while ($lineboard = mysql_fetch_assoc($resultboard)) {
		regenerate_board($lineboard['name']);
		if ($verbose==true) {
			echo 'Regenerated /'.$lineboard['name'].'/<br />';
		}
	}
}
function removeDir($path) {
   // Add trailing slash to $path if one is not there
   /*if (substr($path, -1, 1) != "/") {
       $path .= "/";
   }*/

   $normal_files = glob($path . "*");
   $hidden_files = glob($path . "\.?*");
   $all_files = array_merge($normal_files, $hidden_files);

   foreach ($all_files as $file) {
       # Skip pseudo links to current and parent dirs (./ and ../).
       if (preg_match("/(\.|\.\.)$/", $file))
       {
               continue;
       }

       if (is_file($file) === TRUE) {
           // Remove each file in this Directory
           unlink($file);
           echo "Removed File: " . $file . "<br>";
       }
       else if (is_dir($file) === TRUE) {
           // If this Directory contains a Subdirectory, run this Function on it
           removeDir($file);
       }
   }
   // Remove Directory once Files have been removed (If Exists)
   if (is_dir($path) === TRUE) {
       rmdir($path);
       echo "<br>Removed Directory: " . $path . "<br><br>";
   }
}
function remove_board($dir){
	require("config.php");
	$dir = '/'.$dir;
	/*if (removeDir($chan_rootdir.$chan_boardsfolder."/".$dir)) {
		echo 'Directory removed!';
	} else {
		echo 'Error.';
	}*/
	
	define('loc1', $chan_boardsdir, true);

	if(!isset($GLOBALS['remerror'])) {
		$GLOBALS['remerror'] = false;
	}

  if($handle = opendir(loc1.$dir)){          // if the folder exploration is sucsessful, continue
   while (false !== ($file = readdir($handle))){ // as long as storing the next file to $file is successful, continue
     $path = $dir . '/' . $file;

     if(is_file(loc1 . $path)){
       if(!unlink(loc1 . $path)){
         echo '<u><font color="red">"' . $path . '" could not be deleted. This may be due to a permissions problem.</u><br>Directory cannot be deleted until all files are deleted.</font><br>';
         $GLOBALS['remerror'] = true;
         return false;
       }
     } else
     if(is_dir(loc1 . $path) && substr($file, 0, 1) != '.'){
       remove_board($path);
       @rmdir(loc1 . $path);
     }
   }
   closedir($handle); // close the folder exploration
  }

  if(!$GLOBALS['remerror']) // if no errors occured, delete the now empty directory.
   if(!rmdir(loc1 . $dir)){
     echo '<b><font color="red">Could not remove directory "' . $dir . '". This may be due to a permissions problem.</font></b><br />'.$GLOBALS['remerror'];
     return false;
   } else
     return true;

  return false;
}

//Threads
function buildthread($board,$threadid,$page = false,$resurrect = false,$thread_relative_id = '0') {
	ob_start();
	require("config.php");
	if (!$threadid>0||$board=="") {
		die("Invalid arguments sent to function buildthread()");
	}
	if ($board!="") {
		$result = mysql_query("SELECT `id`,`type`,`name`,`desc` FROM `".$chan_prefix."boards` WHERE `name` = '".$board."' LIMIT 1",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_assoc($result)) {
				$board_id = $line['id'];
				$board_type = $line['type'];
				$board_dir = $line['name'];
				$board_desc = $line['desc'];
			}
		} else {
			die("Invlaid board ID.");
		}
	} else {
		die("Invlaid board ID.");
	}
	if ($resurrect) {
		$isdeleted_check = '1';
	} else {
		$isdeleted_check = '0';
	}
	if ($board_type=='1') {
		$result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied`,`lastbumped` DESC LIMIT 15",$dblink);
		$num_threads_onfrontpage = mysql_num_rows($result);
	}
	$config_numrepliesdisplayed = config_getvalue("numrepliesdisplayed");
	$config_numrepliesdisplayedsticky = config_getvalue("numrepliesdisplayedsticky");
	$config_numthreadsdisplayed = config_getvalue("numthreadsdisplayed");
	$result = mysql_query("SELECT * FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `id` = '".$threadid."' AND `boardid` = '".$board_id."' AND `threadid` = '0'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows==0) {
		die('No posts in thread to build from.');
	}
	if (!$page) {
		echo '<form id="delform" action="'.$chan_boardsfolder.'/board.php" method="post"><input type="hidden" name="board" value="'.$board_dir.'" />';
	}
	while ($line = mysql_fetch_assoc($result)) {
		$thread_id = $line['id'];
		if ($resurrect==false) {
			if ($line['image']=="removed") {
				$imgWidth = '0';
				$imgHeight = '0';
				$imgWidth_thumb = '189';
				$imgHeight_thumb = '16';
			} else if ($line['imagetype']=="swf") {
				$imgWidth = '0';
				$imgHeight = '0';
				$imgWidth_thumb = '86';
				$imgHeight_thumb = '86';
			} else if ($line['image']!='') {
				$imageDim = getimagesize($chan_boardsdir."/".$board_dir."/src/".$line['image'].'.'.$line['imagetype']);
				$imgWidth = $imageDim[0];
				$imgHeight = $imageDim[1];
				$imageDim_thumb = getimagesize($chan_boardsdir."/".$board_dir."/thumb/".$line['image'].'s.'.$line['imagetype']);
				$imgWidth_thumb = $imageDim_thumb[0];
				$imgHeight_thumb = $imageDim_thumb[1];
			}
		}
		$result2 = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."'");
		$numReplies = mysql_num_rows($result2);
		if ($board_type=='0'||$board_type=='2') {
			$numImageReplies = 0;
			if ($page==true) {
				if ($line['stickied']=="0") {
					if ($numReplies>$config_numrepliesdisplayed) {
						$result2 = mysql_query("SELECT `image` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-$config_numrepliesdisplayed)."",$dblink);
						while ($line2 = mysql_fetch_assoc($result2)) {
							if ($line2['image']!="") {
								$numImageReplies++;
							}
						}
					}
				} else {
					if ($numReplies>$config_numrepliesdisplayedsticky) {
						$result2 = mysql_query("SELECT `image` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-$config_numrepliesdisplayedsticky)."",$dblink);
						while ($line2 = mysql_fetch_assoc($result2)) {
							if ($line2['image']!="") {
								$numImageReplies++;
							}
						}
					}
				}
			}
			?>
			<span class="filesize">File: <a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($line['image']=="removed") { echo $chan_boardsfolder."/imageremoved"; } else { echo $chan_boardsfolder."/".$board_dir."/src/".$line['image']; }?>.<?php if ($line['image']=="removed") { echo 'png'; } else { echo $line['imagetype']; } ?>"><?php if ($line['image']=="removed") { echo 'imageremoved.png'; } else { echo $line['image'].'.'.$line['imagetype']; } ?></a> -(<em><?php
			if ($line['image']=="removed"||$resurrect==true) { echo '&nbsp'; } else { echo round(filesize($chan_boardsdir."/".$board_dir."/src/".$line['image'].'.'.$line['imagetype'])/1024).' KB, '.$imgWidth.'x'.$imgHeight; } ?>
			</em>)</span><span class="thumbnailmsg"> Thumbnail displayed, click image for full size.</span><br />
			<?php if ($resurrect==false) { ?><a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($line['image']=="removed") { echo $chan_boardsfolder."/imageremoved"; } else { echo $chan_boardspath."/".$board_dir."/src/".$line['image']; } ?>.<?php if ($line['image']=="removed") { echo "png"; } else { echo $line['imagetype']; } ?>">
			<img src="<?php if ($line['image']=="removed") { echo $chan_boardsfolder."/imageremoved"; } else if ($line['imagetype']=="swf") { echo $chan_boardspath."/flash"; } else { echo $chan_boardspath."/".$board_dir."/thumb/".$line['image']."s"; } ?>.<?php if ($line['image']=="removed"||$line['imagetype']=="swf") { echo "png"; } else { echo $line['imagetype']; } ?>" width="<?php echo $imgWidth_thumb; ?>" height="<?php echo $imgHeight_thumb; ?>" alt="<?php echo $thread_id; ?>" class="thumb" /></a><?php } ?><a name="<?php echo $thread_id; ?>">
			</a><label><input type="checkbox" name="delete" value="<?php echo $thread_id; ?>" />&nbsp;
			<?php if ($line['subject']!="") { echo '<span class="filetitle">'.stripslashes($line['subject']).'</span>&nbsp;'; } ?>
			<span class="postername"><?php
			if ($line['email']!="") {
				echo '<a href="mailto:'.$line['email'].'">';
			}
			if ($line['user']==""&&$line['tripcode']=="") {
				echo 'Anonymous';
			} else if ($line['user']==""&&$line['tripcode']!="") {
				echo ''; // If they have a tripcode, just display the tripcode
			} else {
				echo stripslashes($line['user']);
			}
			if ($line['email']!="") {
				echo '</a>';
			}
			echo '</span>';
			if ($line['tripcode']!="") {
				echo '<span class="postertrip">!'.$line['tripcode'].'</span>';
			}
			if ($line['posterauthority']>0) {
				if ($line['posterauthority']=="1") {
					echo '&nbsp;<span class="admin">##&nbsp;Admin&nbsp;##</span>';
				} else if ($line['posterauthority']=="2") {
					echo '&nbsp;<span class="mod">##&nbsp;Mod&nbsp;##</span>';
				}
			}
			echo '&nbsp;'.date("y/m/d(D)H:i",$line['postedat']); ?></label>&nbsp;
			<span class="reflink"><a href="<?php echo $chan_boardsfolder."/".$board_dir."/res/".$thread_id; ?>.html#<?php echo $thread_id; ?>">No.</a><a href="<?php echo $chan_boardspath."/".$board_dir."/res/".$thread_id; ?>.html#i<?php echo $thread_id; ?>" <?php if ($page==false) { echo ' onclick="insert(\'>>'.$thread_id.'\');"'; } ?>><?php echo $thread_id; ?></a><?php if ($line['locked']=="1") { echo '&nbsp;<img style="border: 0;" src="'.$chan_boardspath.'/locked.gif" alt="locked" />'; } ?><?php if ($line['stickied']=="1") { echo '<img style="border: 0;" src="'.$chan_boardspath.'/sticky.gif" alt="stickied" />'; } ?></span>
			<?php if ($page==true) { echo '&nbsp;&#91;<a href="'.$chan_boardspath.'/'.$board_dir.'/res/'.$thread_id.'.html">Reply</a>&#93;'; } ?>
			<?php echo moderator_deleteandbanlinks($chan_boardsfolder,$board_dir,$thread_id,true); ?>
			<blockquote>
			<p><?php if (count(explode(chr(10), $line['message']))>15&&$page==true) {
					$message_exploded = explode(chr(10), stripslashes($line['message']));
					$message_shortened = '';
					for ($i = 0; $i <= 14; $i++) {
						$message_shortened .= $message_exploded[$i];
					}
					$message_shortened = preg_replace('/<a href="\/'.$board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $message_shortened);
					$message_shortened = closeOpenTags($message_shortened);
					echo $message_shortened;
					echo '<div class="abbrev">Comment too long. Click <a href="'.$chan_boardsfolder.'/'.$board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
				} else {
					echo stripslashes($line['message']);
				}
			?>
			</blockquote>
			<?php
			if ($numReplies>0) {
				if ($page==false) {
					$result2 = mysql_query("SELECT * FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `threadid` = '".$thread_id."' AND `boardid` = '".$board_id."' ORDER BY `postedat` ASC",$dblink);
				} else {
					$query = "SELECT * FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `threadid` = '".$thread_id."' AND `boardid` = '".$board_id."' ORDER BY `postedat` ASC LIMIT ";
					if ($line['stickied']=="0") {
						$query .= max($numReplies-$config_numrepliesdisplayed,0).','.$config_numrepliesdisplayed;
						if ($numReplies>$config_numrepliesdisplayed) {
							echo '<span class="omittedposts"> '.($numReplies-$config_numrepliesdisplayed).' post';
							if ($numReplies-$config_numrepliesdisplayed!=1) {
								echo 's';
							}
							if ($numImageReplies>0) {
								echo ' and '.$numImageReplies.' image';
								if ($numImageReplies!=1) {
									echo 's';
								}
							}
							echo ' omitted. Click Reply to view.  </span>';
						}
					} else {
						$query .= max($numReplies-$config_numrepliesdisplayedsticky,0).','.$config_numrepliesdisplayedsticky;
						if ($numReplies>$config_numrepliesdisplayedsticky) {
							echo '<span class="omittedposts"> '.($numReplies-$config_numrepliesdisplayedsticky).' post';
							if ($numReplies-$config_numrepliesdisplayedsticky!=1) {
								echo 's';
							}
							if ($numImageReplies>0) {
								echo ' and '.$numImageReplies.' image';
								if ($numImageReplies!=1) {
									echo 's';
								}
							}
							echo ' omitted. Click Reply to view.  </span>';
						}
					}
					$result2 = mysql_query($query,$dblink);
				}
				while ($line2 = mysql_fetch_assoc($result2)) {
					$reply_id = $line2['id'];
					$reply_user = stripslashes($line2['user']);
					$reply_tripcode = $line2['tripcode'];
					$reply_email = stripslashes($line2['email']);
					$reply_subject = stripslashes($line2['subject']);
					$reply_message = stripslashes($line2['message']);
					$reply_postedat = $line2['postedat'];
					$reply_image = $line2['image'];
					$reply_imagetype = $line2['imagetype'];
					$reply_posterauthority = $line2['posterauthority'];
					if ($reply_image!="") {
						if ($resurrect==false) {
							if ($reply_image=="removed") {
								$reply_imgWidth = '0';
								$reply_imgHeight = '0';
								$reply_imgWidth_thumb = '189';
								$reply_imgHeight_thumb = '16';
							} else if ($reply_imagetype=="swf") {
								$reply_imgWidth = '0';
								$reply_imgHeight = '0';
								$reply_imgWidth_thumb = '86';
								$reply_imgHeight_thumb = '86';
							} else if ($reply_image!='') {
								$reply_imageDim = getimagesize($chan_boardsdir."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype);
								$reply_imgWidth = $reply_imageDim[0];
								$reply_imgHeight = $reply_imageDim[1];
								$reply_imageDim_thumb = getimagesize($chan_boardsdir."/".$board_dir."/thumb/".$reply_image.'s.'.$reply_imagetype);
								$reply_imgWidth_thumb = $reply_imageDim_thumb[0];
								$reply_imgHeight_thumb = $reply_imageDim_thumb[1];
							}
						}
					}
					?>
					<table>
					<tbody>
					<tr>
					<td class="doubledash">&gt;&gt;</td>
					<td class="reply" id="reply<?php echo $reply_id; ?>"><a name="<?php echo $reply_id; ?>"></a>
					<label><input type="checkbox" name="delete" value="<?php echo $reply_id; ?>" />&nbsp;
					<?php if ($reply_subject!="") { echo '<span class="filetitle">'.$reply_subject.'</span>&nbsp;'; } ?>
					<span class="commentpostername">
					<?php
					if ($reply_email!="") {
						echo '<a href="';
						if ($reply_email!="age"&&$reply_email!="sage") {
							echo 'mailto:';
						}
						echo $reply_email.'">';
					}
					if ($reply_user==""&&$reply_tripcode=="") {
						echo 'Anonymous';
					} else if ($reply_user==""&&$line['tripcode']!="") {
						echo ''; // If they have a tripcode, just display the tripcode
					} else {
						echo $reply_user;
					}
					if ($reply_email!="") {
						echo '</a>';
					}
					echo '</span>';
					if ($reply_tripcode!="") {
						echo '<span class="postertrip">!'.$reply_tripcode.'</span>';
					}
					if ($reply_posterauthority) {
						if ($reply_posterauthority=="1") {
							echo '&nbsp;<span class="admin">##&nbsp;Admin&nbsp;##</span>';
						} else if ($reply_posterauthority=="2") {
							echo '&nbsp;<span class="mod">##&nbsp;Mod&nbsp;##</span>';
						}
					}
					echo '&nbsp;'.date("y/m/d(D)H:i",$reply_postedat); ?></label> <span class="reflink"><a href="<?php echo $chan_boardsfolder."/".$board_dir."/res/".$thread_id; ?>.html#<?php echo $reply_id; ?>">No.</a><a href="<?php echo $chan_boardsfolder."/".$board_dir."/res/".$thread_id; ?>.html#i<?php echo $reply_id; ?>" <?php if ($page==false) { echo ' onclick="insert(\'>>'.$reply_id.'\');"'; } ?>><?php echo $reply_id; ?></a></span>
					<?php echo moderator_deleteandbanlinks($chan_boardsfolder,$board_dir,$reply_id,false); ?>
					&nbsp;<br />
					<?php
					if ($reply_image!="") {
						?>
						<?php if ($resurrect==false) { ?><span class="filesize">File: <a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($reply_image=="removed") { echo $chan_boardsfolder."/imageremoved.png"; } else { echo $chan_boardsfolder."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype; } ?>"><?php if ($reply_image=="removed") { echo 'imageremoved.png'; } else { echo $reply_image.'.'.$reply_imagetype; } ?></a> -(<em><?php if ($reply_image=="removed") { echo '&nbsp'; } else { echo round(filesize($chan_boardsdir."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype)/1024).' KB, '.$reply_imgWidth.'x'.$reply_imgHeight; }  ?></em>)</span> <span class="thumbnailmsg">Thumbnail displayed, click image for full size.</span><br />
						<a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($reply_image=="removed") { echo $chan_webpath."/imageremoved.png"; } else {echo $chan_boardspath."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype; } ?>"><img src="<?php if ($reply_image=="removed") { echo $chan_webpath."/imageremoved.png"; } else if ($reply_imagetype=="swf") { echo $chan_boardsfolder."/flash.png"; } else { echo $chan_boardspath."/".$board_dir."/thumb/".$reply_image.'s.'.$reply_imagetype; } ?>" width="<?php echo $reply_imgWidth_thumb; ?>" height="<?php echo $reply_imgHeight_thumb; ?>" alt="<?php echo $reply_id; ?>" class="thumb" /></a><?php } ?>
						<?php
					}
					?>
					<blockquote>
					<p><?php if (count(explode(chr(10), $reply_message))>15&&$page==true) {
						$message_exploded = explode(chr(10), $reply_message);
						$message_shortened = '';
						for ($i = 0; $i <= 14; $i++) {
							$message_shortened .= $message_exploded[$i];
						}
						$message_shortened = preg_replace('/<a href="\/'.$board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $message_shortened);
						$message_shortened = closeOpenTags($message_shortened);
						echo $message_shortened;
						echo '<div class="abbrev">Comment too long. Click <a href="'.$chan_boardsfolder.'/'.$board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
					} else {
						//echo '/[\<a href\="\/b\/res\/'.$thread_id.'\.html#i]([0-9]+)["\>&gt;&gt;]/'.'<br /><br />'.'<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;\\1</a>'.'<br /><br />';
						if ($page==true) {
							echo preg_replace('/<a href="\/'.$board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $reply_message);
						} else {
							echo preg_replace('/<a href="\/'.$board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1" onclick="highlight(\'\\1\');">&gt;&gt;', $reply_message);
						}
						//echo preg_replace('/'.preg_quote('<a href="\/b\/res\/'.$thread_id.'.html#i').'([0-9]+)'.preg_quote('">&gt;&gt;').'/', '<a href="/b/res/'.$thread_id.'.html#\\1">&gt;&gt;',stripslashes($line['message']));
					}
					?>
					</blockquote>
					</td>
					</tr>
					</tbody>
					</table>
					<?php
				}
			}
			?>
			<br clear="left" />
			<hr />
			<?php
			if (!$page) {
				echo '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br />Password <input type="password" name="postpassword" size="8" />&nbsp;<input value="Delete" type="submit" /></td></tr></tbody></table></form>';
			}
		} else {
			if ($page) {
				echo '<div class="border"><div class="thread">';
			} else {
				echo '<hr>';
			}
			echo '<a name="'.$thread_relative_id.'"></a>';
			if ($page) {
				echo '<span class="navlinks"><a href="#';
				if (($thread_relative_id-1)==-1) {
					echo ($num_threads_onfrontpage-1);
				} else {
					echo ($thread_relative_id-1);
				}
				echo '">&uarr;</a>&nbsp;<a href="#';
				if (($thread_relative_id+1)==$num_threads_onfrontpage) {
					echo '0';
				} else {
					echo ($thread_relative_id+1);
				}
				echo '">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>';
			}
			if ($page) {
				echo '<a href="res/'.$thread_id.'.html">';
				echo '<h2>';
			} else {
				echo '<h3>';
			}
			echo $line['subject'];
			if ($page) {
				echo '</a>';
			}
			echo '<span class="replies">('.$numReplies.')</span>';
			if ($page) {
				echo '</h2>';
				if ($thread_relative_id%2==0) {
					echo '<div class="post even">';
				} else {
					echo '<div class="post odd">';
				}
			} else {
				echo '</h3>';
				echo '<div class="post even">';
			}
			echo '<span class="postnum"><a href="javascript:quote(1,\'post'.$line['id'].'\');">1</a></span>&nbsp;';
			echo '<span class="postinfo">Name:&nbsp;<span class="postername">';
			if ($line['name']==''&&$line['tripcode']=='') {
				echo 'Anonymous';
			} else if ($line['name']==''&&$line['tripcode']!='') {
				echo ''; // If they have a tripcode, just display the tripcode
			} else {
				echo stripslashes($line['name']);
			}
			echo '</span><span class="postertrip">';
			if ($line['tripcode']!='') {
				echo stripslashes($line['tripcode']);
			}
			echo '</span> @ ';
			echo date("Y-m-d H:i",$line['postedat']);
			echo moderator_deleteandbanlinks($chan_boardsfolder,$board_dir,$thread_id,true);
			echo '<span class="id"></span></span><blockquote>';
			echo stripslashes($line['message']);
			echo '</blockquote></div>';
			$is_even = false;
			$reply_relative_id = 1;
			$result2 = mysql_query("SELECT * FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `threadid` = '".$thread_id."' AND `boardid` = '".$board_id."' ORDER BY `postedat` ASC",$dblink);
			while ($line2 = mysql_fetch_assoc($result2)) {
				$reply_relative_id++;
				if ($is_even) {
					echo '<div class="post even">';
				} else {
					echo '<div class="post odd">';
				}
				echo '<span class="postnum"><a href="javascript:quote('.$reply_relative_id.',\'post'.$line['id'].'\');">'.$reply_relative_id.'</a></span>&nbsp;
				<span class="postinfo">Name:&nbsp;<span class="postername">';
				if ($line2['name']==''&&$line2['tripcode']=='') {
					echo 'Anonymous';
				} else if ($line2['name']==''&&$line2['tripcode']!='') {
					echo ''; // If they have a tripcode, just display the tripcode
				} else {
					echo stripslashes($line2['name']);
				}
				echo '</span><span class="postertrip">';
				echo stripslashes($line2['tripcode']);
				echo '</span> @ ';
				echo date("Y-m-d H:i",$line2['postedat']);
				echo moderator_deleteandbanlinks($chan_boardsfolder,$board_dir,$line2['id'],false);
				echo '<span class="id"></span></span>
				<blockquote>';
				echo stripslashes($line2['message']);
				echo '</blockquote></div>';
				$is_even = !$is_even;
			}
			echo '
			<form name="post'.$line['id'].'" action="'.$chan_boardsfolder.'/board.php" method="post">
      			<input type="hidden" name="board" value="'.$board_dir.'" />
      			<input type="hidden" name="replythread" value="'.$thread_id.'" />
			<table class="postform">
			<tr>
			<td class="label">Name:</td>
			<td><input name="name" size="25" /></td>
			<td class="label">Email:</td>
			<td><input name="email" size="25" /></td>
			<td><input type="submit" value="Reply" class="submit" /></td>
			</tr>
			<tr>
			<td class="postfieldleft"><span class="postnum">'.($numReplies+2).'</span></td>
			<td colspan="4">
			<textarea name="message" rows="8" cols="64"></textarea></td>
			</tr>
			<tr>
			<td></td>
			</tr>
			</table>
			</form>';
			if ($page) {
				echo '</div></div>';
			} else {
				echo '<hr>';
			}
		}
		$output = ob_get_clean();
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
function regenerate_thread($board,$threadid) {
	require("config.php");
	$resultboard = mysql_query("SELECT `id`,`type`,`name` FROM `".$chan_prefix."boards` WHERE `name` = '".$board."' LIMIT 1",$dblink);
	while ($lineboard = mysql_fetch_assoc($resultboard)) {
		if (file_exists($chan_boardsdir.'/'.$lineboard['name'].'/res/'.$threadid.'.html')) {
			unlink($chan_boardsdir.'/'.$lineboard['name'].'/res/'.$threadid.'.html');
		}
		$result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lineboard['id']."' AND `threadid` = '0' AND `id` = '".$threadid."' LIMIT 1",$dblink);
		while ($line = mysql_fetch_assoc($result)) {
			$executiontime_start = microtime_float();
			$thread_page = '';
			$post_id = $line['id'];
			require_once("header.php");
			$thread_page .= chan_header($lineboard['name'],$threadid);
			if ($lineboard['type']=='1') {
				$thread_page .= '<a href="'.$chan_boardspath.'/'.$lineboard['name'].'/board.html">Return</a><br><br>';
			} else {
				$thread_page .= '&#91;<a href="'.$chan_boardspath.'/'.$lineboard['name'].'/board.html">Return</a>&#93; <div class="theader">Posting mode: Reply</div>';
			}
			require_once("postbox.php");
			$thread_page .= chan_postbox($lineboard['name'],$threadid);
			$thread_page .= buildthread($lineboard['name'],$post_id);
			require_once("footer.php");
			$executiontime_stop = microtime_float();
			if ($lineboard['type']=='1') {
				$hide_extra = true;
			} else {
				$hide_extra = false;
			}
			$thread_page .= chan_footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
			print_page($chan_boardsdir."/".$lineboard['name']."/res/".$post_id.".html",$thread_page,$board);
		}
	}
}
function delete_post($post,$board) {
	require("config.php");
	$query = "SELECT `id` FROM `".$chan_prefix."boards` WHERE `name` = '".$board."' LIMIT 1";
	$result = mysql_query($query,$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			$board_id = $line['id'];
		}
		$result = mysql_query("SELECT `threadid` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `id` = '".$post."' AND `boardid` = '".$board_id."' LIMIT 1",$dblink);	
		while ($line = mysql_fetch_assoc($result)) {
			if ($line['threadid']=="0") {
				$post_is_thread = true;
			} else {
				$post_is_thread = false;
			}
		}
		$i = 0;
		if ($post_is_thread==true) {
			$result = mysql_query("SELECT `image`,`imagetype` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `id` = '".$post."' AND `threadid` = '0' AND `boardid` = '".$board_id."' LIMIT 1",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_assoc($result)) {
					if ($line['image']!=""&&$line['image']!="removed") {
						unlink($chan_boardsdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
						unlink($chan_boardsdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
					}
				}
				$result = mysql_query("SELECT `id`,`image`,`imagetype` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
				while ($line = mysql_fetch_assoc($result)) {
					$i++;
					if ($line['image']!=""&&$line['image']!="removed") {
						unlink($chan_boardsdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
						unlink($chan_boardsdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
					}
					mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$line['id']."' AND `threadid` = '".$post."' AND `boardid` = '".$board_id."' LIMIT 1",$dblink);
				}
				unlink($chan_boardsdir.'/'.$board.'/res/'.$post.'.html');
				mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$post."' AND `boardid` = '".$board_id."' LIMIT 1",$dblink);
			}
			return $i.' ';
		} else {
			$result = mysql_query("SELECT `threadid`,`image`,`imagetype` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_assoc($result)) {
					if ($line['threadid']!="0") {
						if ($line['image']!="") {
							unlink($chan_boardsdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
							unlink($chan_boardsdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
						}
						mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$post."' AND `boardid` = '".$board_id."' LIMIT 1",$dblink);
						return true;
					} else {
						return false;
					}
				}
			}
			return true;
		}
	} else {
		return false;
	}
}
function trimtopagelimit($board) { //The name is deceiving, this function will trim the threads to the page limit AND delete posts which are older than limited
	require("config.php");
	$result = mysql_query("SELECT `id`,`name`,`desc`,`maxage`,`maxpages`,`locked`,`redirecttothread`,`forcedanon` FROM `".$chan_prefix."boards` WHERE `name` = '".$board."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			$board_id = $line['id'];
			$board_type = $line['type'];
			$board_dir = $line['name'];
			$board_desc = $line['desc'];
			$board_maxage = $line['maxage'];
			$board_maxpages = $line['maxpages'];
			$board_locked = $line['locked'];
			$board_redirecttothread = $line['redirecttothread'];
			$board_forcedanon = $line['forcedanon'];
		}
		if ($board_maxage!='0') { //If the maximum thread age setting is not zero (do not delete old threads), find posts which are older than the limit, and delete them
			$result = mysql_query("SELECT `id`,`postedat` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0'",$dblink); //Fetch all non-deleted thread-starting posts from the selected board
			while ($line = mysql_fetch_assoc($result)) {
				if ($line['postedat']+($board_maxage*3600)<time()) { //If it is older than the limit
					delete_post($line['id'],$board_dir); //Delete it
				}
			}
		}
		if ($board_maxpages!='0') { //If the maximum pages setting is not zero (do not limit pages), find posts which are over the limit, and delete them
			$result = mysql_query("SELECT `id`,`stickied` FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink); //Fetch all non-deleted thread-starting posts from the selected board
			$rows = mysql_num_rows($result);
			if (calculatenumpages($board_type,$rows)>=$board_maxpages) {
				$board_maxthreads = $board_maxpages * config_getvalue('numthreadsdisplayed');
				$numthreadsover = $rows - $board_maxthreads;
				if ($numthreadsover>0) {
					$resultpost = mysql_query("SELECT * FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0' ORDER BY `lastbumped` ASC LIMIT ".$numthreadsover."",$dblink);
					while ($linepost = mysql_fetch_assoc($resultpost)) {
						$oldthread_id = $linepost['id'];
						$oldthread_stickied = $linepost['stickied'];
						if ($oldthread_stickied=='0') {
							$result = mysql_query("SELECT * FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".$oldthread_id."' AND `boardid` = '".$board_id."'",$dblink);
							while ($line = mysql_fetch_assoc($result)) {
								delete_post($line['id'],$board_dir);
							}
							delete_post($oldthread_id,$board_dir);
						}
					}
				}
			}
		}
	} else {
		die("Invalid board.");
	}
}

function display_numuniqueposts($board = "") {
	require("config.php");
	if ($board!="") {
		$result = mysql_query("SELECT `id` FROM `".$chan_prefix."boards` WHERE `name` = '".$board."' LIMIT 1",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_assoc($result)) {
				$board_id = $line['id'];
			}
			$result = mysql_query("SELECT COUNT(DISTINCT `ip`) FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."'",$dblink);
			$numuniqueposts = mysql_fetch_row($result);
			return $numuniqueposts[0];
		} else {
			return '0';
		}
	} else {
		$result = mysql_query("SELECT COUNT(DISTINCT `ip`) FROM `".$chan_prefix."posts` WHERE `IS_DELETED` = '0'",$dblink);
		$numuniqueposts = mysql_fetch_row($result);
		return $numuniqueposts[0];
	}
}

//Users
function ban_user($ip,$modname,$globalban,$duration,$boards,$reason,$type='0') { //Add a ip/ip range ban
	require("config.php");
	$result = mysql_query("SELECT `ip` FROM `".$chan_prefix."banlist` WHERE `type` = '".$type."' AND `ip` = '".$ip."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows==0) {
		if ($duration>0) {
			$ban_globalban = '0';
		} else {
			$ban_globalban = '1';
		}
		mysql_query("INSERT INTO `".$chan_prefix."banlist` ( `ip` , `type` , `globalban` , `boards` , `by` , `at` , `until` , `reason` ) VALUES ( '".$ip."' , '".$type."' , '".$globalban."' , '".$boards."' , '".$modname."' , '".time()."' , '".(time()+$duration)."' , '".$reason."' )",$dblink);
		return true;
	} else {
		return true;
	}
}
function display_bannedmessage($globalban,$boards,$reason,$at,$until) { //Generate the 'You are banned!' page
	$output = "<title>YOU ARE BANNED!</title><div style=\"text-align: center;\"><img src=\"/youarebanned.jpg\" alt=\":'(\"><br /><h1>YOU ARE BANNED :'(</h1><br />You are banned from posting on: ";
	if ($globalban=='1') {
		$output .= '<b>ALL BOARDS</b>';
	} else {
		$output .= $boards;
	}
	$output .= "<br /><br />Reason: ".stripslashes($reason)."<br /><br />Placed: ".$at."<br />Expires: ".$until."</div>";
	return $output;
}
function removed_expired_bans() {
	require("config.php");
	mysql_query("DELETE FROM `".$chan_prefix."banlist` WHERE `until` != '0' AND `until` < '".time()."'",$dblink);
}

//Posting
function getnextpostid($dblink,$boardid) { //Retrieve the id of the next post
	$result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `boardid` = '".$boardid."' ORDER BY `id` DESC LIMIT 1",$dblink);	
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			return $line['id']+1;
		}
	} else {
		return '1';
	}
}
function check_md5($md5,$boardid) { //Checks if the supplied md5 file hash is currently recorded inside of the database, attached to a non-deleted post.
	require("config.php");
	$result = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `imagemd5` = '".mysql_escape_string($md5)."' AND `boardid` = '".mysql_escape_string($boardid)."' AND `IS_DELETED` = '0' LIMIT 1",$dblink);	
	$rows = mysql_num_rows($result);
	if ($rows>0) { //Yes, it is in there
		return true;
	} else { //No, it isn't
		return false;
	}
}
function insert_post($boardid,$id,$threadid,$user,$tripcode,$email,$subject,$message,$image,$imagetype,$imagemd5,$password,$postedat,$lastbumped,$ip,$posterauthority,$stickied,$locked) { //Insert a new post into the database
	require("config.php");
	$query = "INSERT INTO `".$chan_prefix."posts` ( `boardid` , `id` , `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `imagemd5` , `password` , `postedat` , `lastbumped` , `ip` , `posterauthority` , `stickied` , `locked` ) VALUES ( '".$boardid."','".$id."','".$threadid."','".$user."','".$tripcode."','".$email."','".$subject."','".$message."','".$image."','".$imagetype."','".$imagemd5."','".$password."','".$postedat."','".$lastbumped."','".$ip."','".$posterauthority."','".$stickied."','".$locked."' )";
	return mysql_query($query,$dblink);
}

//Image handling
function createthumb($name,$filename,$new_w,$new_h) {
	$system=explode(".",$filename);
	$system = array_reverse($system);
	if (preg_match("/jpg|jpeg/",$system[0])) {
		$src_img=imagecreatefromjpeg($name);
	} else if (preg_match("/png/",$system[0])) {
		$src_img=imagecreatefrompng($name);
	} else if (preg_match("/gif/",$system[0])) {
		$src_img=imagecreatefromgif($name);
	} else {
		return false;
	}
	if (!$src_img) {
		echo '<br />Unable to open the uploaded image for thumbnailing.  Maybe its a different filetype, and has the wrong extension?';
		return false;
	}
	$old_x=imageSX($src_img);
	$old_y=imageSY($src_img);
	if ($old_x > $old_y) {
		$percent = $new_w / $old_x;
	} else {
		$percent = $new_h / $old_y;
	}
	$thumb_w = round($old_x * $percent);
	$thumb_h = round($old_y * $percent);
	$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
	imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
	if (preg_match("/png/",$system[0])) {
		if (!imagepng($dst_img,$filename)) {
			echo 'unable to imagepng.';
			return false;
		}
	} else if (preg_match("/jpg|jpeg/",$system[0])) {
		if (!imagejpeg($dst_img,$filename)) {
			echo 'unable to imagejpg.';
			return false;
		}
	} else if (preg_match("/gif/",$system[0])) {
		if (!imagegif($dst_img,$filename)) { 
			echo 'unable to imagegif.';
			return false;
		}
	}
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
	return true;
}

//Header
function display_boardlist($is_textboard = false) {
	$output = '';
	/*require("config.php");
	$result = mysql_query("SELECT DISTINCT `section` FROM `".$chan_prefix."boards` ORDER BY `section` ASC",$dblink);
	$rows = mysql_num_rows($result);
	$board_sections = array();
	while ($line = mysql_fetch_assoc($result)) {
		$board_sections = array_merge($board_sections,array($line['section']));
	}
	foreach ($board_sections as $board_section) {
		$board_this_section = '';
		$output .= '[';
		$result = mysql_query("SELECT * FROM `".$chan_prefix."boards` WHERE `section` = '".$board_section."' ORDER BY `order` ASC",$dblink);
		while ($line = mysql_fetch_assoc($result)) {
			$board_this_section .= ' <a title="'.$line['desc'].'" href="'.$chan_boardsfolder.'/'.$line['name'].'/">'.$line['name'].'</a> /';
		}
		$board_this_section = substr($board_this_section,0,strlen($board_this_section)-1);
		$output .= $board_this_section;
		$output .= ']&nbsp;';
	}*/
	if ($is_textboard) {
		$div_name = 'topbar';
	} else {
		$div_name = 'navbar';
	}
	if (is_file("boards.html")) {
		return '<div class="'.$div_name.'">'.stripslashes(file_get_contents("boards.html")).'</div>';
	} else {
		return '<div class="'.$div_name.'">'.stripslashes(file_get_contents("../boards.html")).'</div>';
	}
	//return $output;
}
function display_textboard_threadlist($board_id,$liststart,$liststooutput) {
	require('config.php');
	$output = '<div class="head threadldiv"><a name="menu"></a>
	<table class="threads"><thead>
	<tr>
	<th width="10%">#</th>
	<th nowrap="nowrap" width="100%">Subject</th>
	<th>Posts</th>
	<th>Last Post</th>
	</tr>
	</thead>
	<tbody>';
	$query = "SELECT * FROM `".$chan_prefix."posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '0' AND `IS_DELETED` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".$liststart.',';
	if ($listpage>=0&&$liststooutput>=0) {
		$query .= '40';
	} else {
		$query .= '15';
	}
	$result = mysql_query($query,$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		$relative_id = $liststart;
		while ($line = mysql_fetch_assoc($result)) {
			$result2 = mysql_query("SELECT `id` FROM `".$chan_prefix."posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '".$line['id']."' AND `IS_DELETED` = '0'",$dblink);
			$replies = mysql_num_rows($result2);
			$output .= '<tr><td><a href="res/'.$line['id'].'.html">'.($relative_id+1).'</a></td><td><a href="';
			if ($relative_id<15) {
				$output .= '#'.$relative_id;
			} else {
				$output .= 'res/'.$line['id'].'.html';
			}
			$output .= '">'.stripslashes($line['subject']).'</a></td><td>'.$replies.'</td><td nowrap><small>'.date('j F Y H:i',$line['lastbumped']).'</small></td></td></tr>';
			$relative_id++;
		}
	} else {
		$output .= '<tr><td>N/A</td><td>There are currently no threads to display.</td><td>N/A</td><td>N/A</td></td>';
	}
	$output .= '</tbody></table>';
	if ($listpage<0||$liststooutput<0) {
		$output .= '<span class="threadlinks"><a href="#newthread">New Thread</a> | <a href="list.html">All Threads</a></span>';
	}
	$output .= '</div>';
	return $output;
}
function format_postboxnotice($notice,$board) {
	require("config.php");
	$result = mysql_query("SELECT `maximagesize`, `filetypes` FROM `".$chan_prefix."boards` WHERE `name` = '".$board."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			$notice = str_replace('<!tc_maxthumbwidth />',config_getvalue('maxthumbwidth'),$notice);
			$notice = str_replace('<!tc_maxthumbheight />',config_getvalue('maxthumbheight'),$notice);
			$notice = str_replace('<!tc_uniqueposts />',display_numuniqueposts($board),$notice);
			$notice = str_replace('<!tc_maximagekb />',round($line['maximagesize']/1024),$notice);
			$filetypes = '';
			$filetypes_allowed = explode('|',$line['filetypes']);
			if ($filetypes_allowed=='') {
				$filetypes = 'NONE';
			} else {
				foreach ($filetypes_allowed as $filetype) {
					$filetypes .= $filetype.', ';
				}
				$filetypes = substr($filetypes,0,strlen($filetypes)-2);
			}
			$notice = str_replace('<!tc_filetypes />',$filetypes,$notice);
		}
	}
	return $notice;
}

function boardid_to_dir($boardid) {
	require("config.php");
	$result = mysql_query("SELECT `name` FROM `".$chan_prefix."boards` WHERE `id` = '".mysql_escape_string($boardid)."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			return $line['name'];
		}
	}
}
function calculatenumpages($boardtype,$numposts) {
	if ($boardtype=='1') {
		return (floor($numposts/15));
	} else {
		return (floor($numposts/config_getvalue('numthreadsdisplayed')));
	}
}
?>
