<?php

/*
     Trevorchan
	 Written by Trevor "tj9991" Slocum
	 http://www.tj9991.com/
	 tslocum@gmail.com
*/

require("config.php");
require("inc/functions.php");

if ($_GET['board']!="") {
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_GET['board']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_id = $line['id'];
			$board_dir = $line['name'];
			$board_desc = $line['desc'];
			$board_maxage = $line['maxage'];
			$board_maxpages = $line['maxpages'];
			$board_locked = $line['locked'];
		}
	} else {
		header("Location: trevorchan.php");
		die();
	}
} else {
	header("Location: trevorchan.php");
	die();
}

if (isset($_POST['message'])||isset($_FILES['imagefile'])) {
	$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (time()>=$line['until']) {
				mysql_query("DELETE FROM `banlist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
			} else {
				die("<title>YOU ARE BANNED!</title><div style=\"text-align: center;\"><img src=\"/youarebanned.jpg\" alt=\":'(\"><br /><h1>YOU ARE BANNED :'(</h1><br />Reason: ".$line['reason']."<br /><br />Placed: ".date("F j, Y, g:i a",$line['at'])."<br />Expires: ".date("F j, Y, g:i a",$line['until'])."</div>");
			}
		}
	}
	$result = mysql_query("SELECT * FROM `iplist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (time()-$line['lastpost']<=$chan_postdelay) {
				die('Error: please wait a moment before posting again.');
			}
		}
	}
	$result = mysql_query("SELECT * FROM `posts` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."' AND `postedat` > '".(time()-60)."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>$chan_maxppm) {
		die('Error: please wait a moment before posting.');
	}
	$reffer_threadid = substr($_SERVER['HTTP_REFERER'],strpos($_SERVER['HTTP_REFERER'],'res/')+4);
	$reffer_threadid = substr($reffer_threadid,0,strpos($reffer_threadid,'.html'));
	if ($reffer_threadid>0) {
		$isreplying = true;
		$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$reffer_threadid."' AND `threadid` = '0'");
		if (mysql_num_rows($result)>0) {
			$thread_replyto = $reffer_threadid;
		} else {
			die("Invalid thread ID.  This may have been caused by it recently being deleted.");
		}
	} else {
		$thread_replyto = "0";
	}
	$_POST['name'] = addslashes(strip_tags($_POST['name']));
	$_POST['email'] = addslashes(strip_tags($_POST['email']));
	$_POST['subject'] = addslashes(strip_tags($_POST['subject']));
	require_once("inc/parse.php");
	$_POST['message'] = parse_post($_POST['message'],$board_dir,$thread_replyto);
	if ($isreplying) {
		if ($_FILES['imagefile']['name']==""&&preg_match("/[^\s]/",$_POST['message'])==false) {
			die("An image, or message, is required for a reply.");
		}
	} else {
		if ($_FILES['imagefile']['name']=="") {
			die("An image is required for a new thread.");
		}
	}
	$user_authority = '0';
	if ($_POST['password']!="") {
		require("inc/encryption.php");
		$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".md5_decrypt($_POST['password'],$chan_randomseed)."'");
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$result2 = mysql_query("SELECT * FROM `staff` WHERE `username` = '".md5_decrypt($_POST['password'],$chan_randomseed)."'",$dblink);
				while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
					$staff_boards = explode('|',$line2['boards']);
				}
				if ($line['isadmin']=="1") {
					$user_authority = '1';
				} else {
					if (in_array($board_dir,$staff_boards)) {
						$user_authority = '2';
					}
				}
			}
		}
	}
	if($board_locked=='0'||$user_authority=='1') {
		if ($_FILES['imagefile']['name']!="") {
			if (strpos($_FILES['imagefile']['name'],',')!=false) {
				die("Please select only one image to upload.");
			}
			$file_isduplicate = false;
			$file=$_FILES['imagefile']['name'];
			$file_md5 = md5_file($_FILES['imagefile']['tmp_name']);
			$dir = './'.$board_dir.'/src';
			$files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
			if (is_array($files)) { 
				foreach ($files as $image) {
					if (md5_file($image)==$file_md5) {
						$file_isduplicate = true;
					}
				}
			}
			if ($file_isduplicate) {
				die("Duplicate file entry detected.");
			}
			$filetype=substr($file,-4);
			$imageDim = getimagesize($_FILES['imagefile']['tmp_name']);
			$imgWidth = $imageDim[0];
			$imgHeight = $imageDim[1];
			$filetype = strtolower($filetype);
			if ($_FILES['imagefile']['size']>1024000) {
				die("Please make sure your image is smaller than 1000KB.");
			} else {
				if ($filetype==".jpg"||$filetype==".png"||$filetype==".gif") {
					$thisimage_name = time();
					while (file_exists($chan_rootdir."/".$board_dir."/src/".$thisimage_name.$filetype)) {
						$thisimage_name++;
					}
					copy($_FILES['imagefile']['tmp_name'],$chan_rootdir."/".$board_dir."/src/".$thisimage_name.$filetype) or die("Could not copy");
					if ($imgWidth>200||$imgHeight>200) {
						createthumb($board_dir."/src/".$thisimage_name.$filetype,$board_dir."/thumb/".$thisimage_name.'s'.$filetype,200,200);
					} else {
						createthumb($board_dir."/src/".$thisimage_name.$filetype,$board_dir."/thumb/".$thisimage_name.'s'.$filetype,$imgWidth,$imgHeight);
					}
					$imageDim_thumb = getimagesize($chan_rootdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype);
					$imgWidth_thumb = $imageDim_thumb[0];
					$imgHeight_thumb = $imageDim_thumb[1];
					$imageused = true;
				} else {
					die("Improper filetype.");
				}
			}
		}
		mysql_query("LOCK TABLE `posts` WRITE;",$dblink);
		$result = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows/10>=$board_maxpages) {
			$result = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0' ORDER BY `lastbumped` ASC LIMIT 1",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$oldthread_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '".$oldthread_id."' AND `boardid` = '".$board_id."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				if ($line['image']!="") {
					unlink($chan_rootdir.'/'.$board_dir.'/src/'.$line['image'].'.'.$line['imagetype']);
					unlink($chan_rootdir.'/'.$board_dir.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
				}
				mysql_query("DELETE FROM `posts` WHERE `id` = '".$line['id']."' AND `threadid` = '".$oldthread_id."' AND `boardid` = '".$board_id."'",$dblink);
			}
			mysql_query("DELETE FROM `posts` WHERE `id` = '".$oldthread_id."' AND `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
		}
		$post_id = getnextpostid($dblink,$board_id);
		if ($post_id==0) { $post_id = '1'; }
		$query = "INSERT INTO `posts` ( `boardid` , `id` , `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `postedat` , `lastbumped` , `ip` , `posterauthority` ) VALUES ( '".$board_id."', '".$post_id."', '".$thread_replyto."', ";
		if (strpos($_POST['name'],"#")!=false) {
			$tripcode_user = substr($_POST['name'],0,strpos($_POST['name'],"#"));
			$tripcode_password = substr($_POST['name'],strpos($_POST['name'],"#")+1);
			$tripcode = substr(md5($tripcode_password),0,10);
			$query .= "'".$tripcode_user."', '".$tripcode."', ";
		} else {
			$query .= "'".$_POST['name']."', '', ";
		}
		$query .= "'".$_POST['email']."', '".$_POST['subject']."', '".$_POST['message']."', '".$thisimage_name."', '".substr($filetype,1)."', '".time()."', '".time()."' , '".$_SERVER['REMOTE_ADDR']."' , '".$user_authority."' );";
		mysql_query($query,$dblink);
		$result = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0'",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$resultboard = mysql_query("SELECT * FROM `boards` WHERE `id` = '".$line['boardid']."'",$dblink);
			while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
				if ($line['postedat']+($board_maxage*3600)<time()) {
					$result2 = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '".$line['id']."' AND `boardid` = '".$board_id."'",$dblink);
					while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
						if ($line2['image']!="") {
							unlink($chan_rootdir.'/'.$board_dir.'/src/'.$line2['image'].'.'.$line2['imagetype']);
							unlink($chan_rootdir.'/'.$board_dir.'/thumb/'.$line2['image'].'s.'.$line2['imagetype']);
						}
						mysql_query("DELETE FROM `posts` WHERE `id` = '".$line2['id']."' AND `threadid` = '".$line['id']."' AND `boardid` = '".$board_id."'",$dblink);
					}
					mysql_query("DELETE FROM `posts` WHERE `id` = '".$line['id']."' AND `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
				}
			}
		}
		mysql_query("UNLOCK TABLES;",$dblink);
		if ($thread_replyto!="0"&&$_POST['email']!="sage") {
			mysql_query("UPDATE `posts` SET `lastbumped` = '".time()."' WHERE `id` = '".$thread_replyto."'",$dblink);
		}
		$result = mysql_query("SELECT * FROM `iplist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows==0) {
			mysql_query("INSERT INTO `iplist` ( `ip` , `lastpost` ) VALUES ( '".$_SERVER['REMOTE_ADDR']."' , '".time()."' )",$dblink);
		} else {
			mysql_query("UPDATE `iplist` SET `lastpost` = '".time()."' WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
		}
		if ($thread_replyto=="0") {
			regenerate_thread($board_dir,$post_id);
		} else {
			regenerate_thread($board_dir,$thread_replyto);
		}
	} else {
		die('Sorry, this board is locked and can not be posted in by normal users.');
	}
}

regenerate_board($board_dir,true);

header("Location: board.html");
die();

?>