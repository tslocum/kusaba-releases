<?php
 
/*
     Trevorchan
	 Written by Trevor "tj9991" Slocum
	 http://www.tj9991.com/
	 tslocum@gmail.com
*/
 
require("config.php");
require("inc/functions.php");
 
if ($_POST['board']!="") {
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['board'])."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_id = $line['id'];
			$board_dir = $line['name'];
			$board_desc = $line['desc'];
			$board_maximagesize = $line['maximagesize'];
			$board_maxage = $line['maxage'];
			$board_maxpages = $line['maxpages'];
			$board_locked = $line['locked'];
			$board_redirecttothread = $line['redirecttothread'];
			$board_forcedanon = $line['forcedanon'];
		}
	} else {
		echo '<meta http-equiv="refresh" content="0;url='.$chan_webpath.$chan_webfolder.'/trevorchan.php" />';
		die();
	}
} else {
	echo '<meta http-equiv="refresh" content="0;url='.$chan_webpath.$chan_webfolder.'/trevorchan.php" />';
	die();
}
 
if (isset($_POST['message'])||isset($_FILES['imagefile'])) {
	$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if (time()>=$line['until']&&$line['until']!='0') {
				mysql_query("DELETE FROM `banlist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
			} else {
				if ($line['until']=='0') {
					$ban_until = '<font color="red">NEVER</font>';
				} else {
					$ban_until = date("F j, Y, g:i a",$line['until']);
				}
				if ($line['globalban']!='1') {
					if (in_array($board_dir,explode('|',$line['boards']))) {
						echo display_bannedmessage($line['globalban'],'<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;',$line['reason'],date("F j, Y, g:i a",$line['at']),$ban_until);
						die();
					}
				} else {
					echo display_bannedmessage($line['globalban'],'<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;',$line['reason'],date("F j, Y, g:i a",$line['at']),$ban_until);
					die();
				}
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
	$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `ip` = '".$_SERVER['REMOTE_ADDR']."' AND `postedat` > '".(time()-60)."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>$chan_maxppm) {
		die('Error: please wait a moment before posting.');
	}
	if (strlen($_POST['message'])>8192) {
		die("Sorry, your message is too long.  Message length: ".strlen($_POST['message']).", maxmimum allowed length: 8192");
	}
	if ($_POST['replythread']>0) {
		$isreplying = true;
		$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_escape_string($_POST['replythread'])."' AND `boardid` = '".$board_id."' AND `threadid` = '0'");
		if (mysql_num_rows($result)>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$thread_locked = $line['locked'];
				$thread_replyto = $line['id'];
			}
		} else {
			die("Invalid thread ID.  This may have been caused by the thread recently being deleted.");
		}
	} else {
		$thread_replyto = "0";
	}
	$post_name = addslashes(strip_tags($_POST['name']));
	$post_email = addslashes(strip_tags($_POST['email']));
	$post_subject = addslashes(strip_tags($_POST['subject']));
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
	if ($user_authority=='0') {
		if ($thread_locked=='1') {
			die('Sorry, this thread is locked and can not currently be replied to.');
		}
		require_once("inc/parse.php");
		$post_message = parse_post($_POST['message'],$board_dir,$thread_replyto);
	} else {
		if ($_POST['rawhtml']=='on') {
			$post_message = addslashes($_POST['message']);
		} else {
			require_once("inc/parse.php");
			$post_message = parse_post($_POST['message'],$board_dir,$thread_replyto);
		}
		if ($_POST['lockonpost']=='on') {
			$onpost_lock = true;
		}
		if ($_POST['stickyonpost']=='on') {
			$onpost_sticky = true;
		}
	}
	if ($isreplying) {
		if ($_FILES['imagefile']['name']==""&&preg_match("/[^\s]/",$post_message)==false) {
			die("An image, or message, is required for a reply.");
		}
	} else {
		if ($_FILES['imagefile']['name']=="") {
			die("An image is required for a new thread.");
		}
	}
	if($board_locked=='0'||$user_authority>'0') {
		trimtopagelimit($board_dir);
		mysql_query("LOCK TABLE `posts` WRITE;",$dblink);
		$imageused = false;
		$post_id = getnextpostid($dblink,$board_id);
		if ($_FILES['imagefile']['name']!="") {
			if (strpos($_FILES['imagefile']['name'],',')!=false) {
				mysql_query("UNLOCK TABLES;",$dblink);
				die("Please select only one image to upload.");
			}
			if (!file_exists($_FILES['imagefile']['tmp_name'])) {
				echo 'Error, it appears your file did not transfer properly.  Please go back and try again.';
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
				mysql_query("UNLOCK TABLES;",$dblink);
				die("Duplicate file entry detected.");
			}
			$filetype=substr($file,-4);
			$imageDim = getimagesize($_FILES['imagefile']['tmp_name']);
			$imgWidth = $imageDim[0];
			$imgHeight = $imageDim[1];
			//print(time()." Image height and width figured out, its: ".$imgWidth."x".$imgHeight."<br />");
			$filetype = strtolower($filetype);
			if ($_FILES['imagefile']['size']>$board_maximagesize) {
				mysql_query("UNLOCK TABLES;",$dblink);
				die("Please make sure your image is smaller than ".$board_maximagesize."B");
			} else {
				if ($filetype==".jpg"||$filetype==".png"||$filetype==".gif") {
					$thisimage_name = time().$post_id;
					//print(time()." Image name figured it, it will be: ".$thisimage_name.$filetype."<br />");
					if (!move_uploaded_file($_FILES['imagefile']['tmp_name'],$chan_rootdir."/".$board_dir."/src/".$thisimage_name.$filetype)) {
						mysql_query("UNLOCK TABLES;",$dblink);
						die("Could not copy uploaded image.");
					}
					if ($imgWidth>200||$imgHeight>200) {
						if (!createthumb($chan_rootdir."/".$board_dir."/src/".$thisimage_name.$filetype,$chan_rootdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype,200,200)) {
							mysql_query("UNLOCK TABLES;",$dblink);
							die("Could not create thumbnail.");
						}
					} else {
						if (!createthumb($chan_rootdir."/".$board_dir."/src/".$thisimage_name.$filetype,$chan_rootdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype,$imgWidth,$imgHeight)) {
							mysql_query("UNLOCK TABLES;",$dblink);
							die("Could not create thumbnail.");
						}
					}
					//print(time()." Copied and thumbnailed successfully.");
					$imageDim_thumb = getimagesize($chan_rootdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype);
					$imgWidth_thumb = $imageDim_thumb[0];
					$imgHeight_thumb = $imageDim_thumb[1];
					$imageused = true;
				} else {
					mysql_query("UNLOCK TABLES;",$dblink);
					die("Improper filetype.");
				}
			}
		}
		if ($post_id=='0') { $post_id = '1'; }
		if ($board_forcedanon=='1') {
			if ($user_authority=='0') {
				$post_name = "";
			}
		}
		$query = "INSERT INTO `posts` ( `boardid` , `id` , `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `postedat` , `lastbumped` , `ip` , `posterauthority` , `stickied` , `locked` ) VALUES ( '".$board_id."', '".$post_id."', '".$thread_replyto."', ";
		if (strpos($post_name,"#")!=false) {
			$tripcode_user = substr($post_name,0,strpos($post_name,"#"));
			$tripcode_password = substr($post_name,strpos($post_name,"#")+1);
			$tripcode = substr(md5($tripcode_password),0,10);
			$query .= "'".substr(strip_tags($tripcode_user,0,100))."', '".$tripcode."', ";
		} else {
			$query .= "'".substr($post_name,0,100)."', '', ";
		}
		$filetype_withoutdot = substr($filetype,1);
		$query .= "'".substr($post_email,0,100)."', '".substr($post_subject,0,100)."', '".$post_message."', '".mysql_escape_string($thisimage_name)."', '".$filetype_withoutdot."', '".time()."', '".time()."' , '".$_SERVER['REMOTE_ADDR']."' , '".$user_authority."'";
		if ($onpost_sticky==true) {
			if ($thread_replyto=='0') {
				$query .= " , '1'";
			} else {
				mysql_query("UPDATE `posts` SET `stickied` = '1' WHERE `boardid` = '".$board_id."' AND `id` = '".$thread_replyto."'",$dblink);
				$query .= " , '0'";
			}
		} else {
			$query .= " , '0'";
		}
		if ($onpost_lock==true) {
			if ($thread_replyto=='0') {
				$query .= " , '1'";
			} else {
				mysql_query("UPDATE `posts` SET `locked` = '1' WHERE `boardid` = '".$board_id."' AND `id` = '".$thread_replyto."'",$dblink);
				$query .= " , '0'";
			}
		} else {
			$query .= " , '0'";
		}
		$query .= " );";
		if ((file_exists($chan_rootdir."/".$board_dir."/src/".$thisimage_name.$filetype)&&file_exists($chan_rootdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype))||$imageused==false) {
			mysql_query($query,$dblink);
		} else {
			mysql_query("UNLOCK TABLES;",$dblink);
			die("Unable to copy selected image.  Please go back and try again.");
		}
		mysql_query("UNLOCK TABLES;",$dblink);
		if ($thread_replyto!="0"&&$post_email!="sage") {
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
			//print(time()." Regenerating thread: board - ".$board_dir." - post: ".$post_id."<br />");
			regenerate_thread($board_dir,$post_id);
		} else {
			//print(time()." Regenerating thread: board - ".$board_dir." - reply-to post: ".$thread_replyto."<br />");
			regenerate_thread($board_dir,$thread_replyto);
		}
	} else {
		die('Sorry, this board is locked and can not currently be replied to.');
	}
} else {
	echo '<meta http-equiv="refresh" content="0;url='.$chan_webpath.$chan_webfolder.'/'.$board_dir.'/board.html" />';
	die();
}
 
regenerate_board($board_dir,true);
if ($thread_replyto=="0") {
	print("Thread successfully posted.  You are now being redirected.");
} else {
	print("Reply successfully posted.  You are now being redirected.");
}
if ($board_redirecttothread=='1') {
	if ($thread_replyto=="0") {
		//echo '<a href="res/'.$post_id.'.html">Click me!</a>';
		echo '<meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/'.$board_dir.'/res/'.$post_id.'.html" />';
		//header("Location: res/".$post_id.".html");
	} else {
		//echo '<a href="res/'.$thread_replyto.'.html">Click me!</a>';
		echo '<meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/'.$board_dir.'/res/'.$thread_replyto.'.html" />';
		//header("Location: res/".$thread_replyto.".html");
	}
} else {
	echo '<meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/'.$board_dir.'/" />';
}

?>