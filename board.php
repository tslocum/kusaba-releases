<?php
 
/*
     Trevorchan
	 Written by Trevor "tj9991" Slocum
	 http://www.tj9991.com/
	 tslocum@gmail.com
*/
require("config.php");
require($chan_rootdir."/inc/functions.php");

 
if (isset($_POST['board'])) {
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['board'])."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			$board_id = $line['id'];
			$board_dir = $line['name'];
			$board_desc = $line['desc'];
			$board_filetypes = explode('|',$line['filetypes']);
			$board_maximagesize = $line['maximagesize'];
			$board_maxage = $line['maxage'];
			$board_maxreplies = $line['maxreplies'];
			$board_maxpages = $line['maxpages'];
			$board_messagelength = $line['messagelength'];
			$board_locked = $line['locked'];
			$board_redirecttothread = $line['redirecttothread'];
			$board_forcedanon = $line['forcedanon'];
		}
		$glob_maxthumbwidth = config_getvalue('maxthumbwidth');
		$glob_maxthumbheight = config_getvalue('maxthumbheight');
	} else {
		echo '<meta http-equiv="refresh" content="0;url='.$chan_webpath.'/trevorchan.php" />';
		die();
	}
} else {
	echo '<meta http-equiv="refresh" content="0;url='.$chan_webpath.'/trevorchan.php" />';
	die();
}
removed_expired_bans();
 
if (isset($_POST['message'])||isset($_FILES['imagefile'])) {
	$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
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
	$result = mysql_query("SELECT `lastpost` FROM `iplist` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_assoc($result)) {
			if (time()-$line['lastpost']<=$chan_postdelay) {
				die($lang['wait to post again']);
			}
		}
	}
	$result = mysql_query("SELECT `id` FROM `posts` WHERE `IS_DELETED` = '0' AND  `ip` = '".$_SERVER['REMOTE_ADDR']."' AND `postedat` > '".(time()-60)."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>$chan_maxppm) {
		die($lang['wait to post again']);
	}
	if (strlen($_POST['message'])>8192) {
		printf($lang['message too long'],strlen($_POST['message']),$board_messagelength);
		die();
	}
	$thread_locked = '0';
	$isreplying = false;
	if (isset($_POST['replythread'])) {
		$isreplying = true;
		$result = mysql_query("SELECT `id`,`locked` FROM `posts` WHERE `IS_DELETED` = '0' AND `id` = '".mysql_escape_string($_POST['replythread'])."' AND `boardid` = '".$board_id."' AND `threadid` = '0'");
		if (mysql_num_rows($result)>0) {
			while ($line = mysql_fetch_assoc($result)) {
				$thread_locked = $line['locked'];
				$thread_replyto = $line['id'];
			}
		} else {
			die($lang['invalid thread id']);
		}
		$result = mysql_query("SELECT `id` FROM `posts` WHERE `IS_DELETED` = '0' AND `threadid` = '".mysql_escape_string($_POST['replythread'])."' AND `boardid` = '".$board_id."'");
		$thread_replies = mysql_num_rows($result);
	} else {
		$thread_replyto = "0";
	}
	$post_name = addslashes(strip_tags($_POST['name']));
	$post_email = addslashes(strip_tags($_POST['email']));
	$post_subject = addslashes(strip_tags($_POST['subject']));
	$user_authority = '0';
	if (isset($_POST['modpassword'])) {
		require($chan_rootdir."/inc/encryption.php");
		$result = mysql_query("SELECT `isadmin`,`boards` FROM `staff` WHERE `username` = '".md5_decrypt($_POST['modpassword'],$chan_randomseed)."'");
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_assoc($result)) {
				if ($line['isadmin']=="1") {
					$user_authority = '1';
				} else {
					if (in_array($board_dir,explode('|',$line['boards']))) {
						$user_authority = '2';
					}
				}
			}
		}
	}
	if ($_POST['postpassword']!="") {
		$post_password = $_POST['postpassword'];
	} else {
		$post_password = "";
	}
	if ($user_authority=='0') {
		if ($thread_locked=='1') {
			die($lang['thread is locked']);
		}
		require_once($chan_rootdir."/inc/parse.php");
		$post_message = parse_post($_POST['message'],$board_dir,$thread_replyto);
	} else {
		if (isset($_POST['rawhtml'])) {
			$post_message = addslashes($_POST['message']);
		} else {
			require_once($chan_rootdir."/inc/parse.php");
			$post_message = parse_post($_POST['message'],$board_dir,$thread_replyto);
		}
		if (isset($_POST['lockonpost'])) {
			$onpost_lock = true;
		}
		if (isset($_POST['stickyonpost'])) {
			$onpost_sticky = true;
		}
	}
	if ($isreplying) {
		if ($_FILES['imagefile']['name']==""&&$post_message=="") {
			die($lang['image/message required for reply']);
		}
	} else {
		if ($_FILES['imagefile']['name']=="") {
			die($lang['image required for thread']);
		}
	}
	if($board_locked=='0'||$user_authority>'0') {
		trimtopagelimit($board_dir);
		$result = mysql_query("LOCK TABLE `posts` WRITE;",$dblink);
		$imageused = false;
		$onpost_sticky = false;
		$onpost_lock = false;
		$thisimage_name = "";
		$filetype = "";
		$file_md5 = "";
		$post_id = getnextpostid($dblink,$board_id);
		if ($_FILES['imagefile']['name']!="") {
			if (strpos($_FILES['imagefile']['name'],',')!=false) {
				mysql_query("UNLOCK TABLES;",$dblink);
				die($lang['select one image']);
			}
			if (!file_exists($_FILES['imagefile']['tmp_name'])) {
				echo $lang['corrupt transfer'];
			}
			$file=$_FILES['imagefile']['name'];
			$file_md5 = md5_file($_FILES['imagefile']['tmp_name']);
			if (check_md5($file_md5,$board_id)) {
				mysql_query("UNLOCK TABLES;",$dblink);
				die($lang['duplicate file']);
			}
			$filetype=substr($file,-4);
			if ($filetype=="jpeg") { // Fix for the rarely used 4-char format
				$filetype = ".jpg";
			}
			$imageDim = getimagesize($_FILES['imagefile']['tmp_name']);
			$imgWidth = $imageDim[0];
			$imgHeight = $imageDim[1];
			$filetype = strtolower($filetype);
			if ($_FILES['imagefile']['size']>$board_maximagesize) {
				mysql_query("UNLOCK TABLES;",$dblink);
				printf($lang['image too big'],$board_maximagesize);
				die();
			} else {
				if (in_array(strtoupper(substr($filetype,1)),$board_filetypes)) {
					if ($filetype==".jpg"||$filetype==".png"||$filetype==".gif") {
						$thisimage_name = time().rand(1,99).$post_id;
						if (!move_uploaded_file($_FILES['imagefile']['tmp_name'],$chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype)) {
							mysql_query("UNLOCK TABLES;",$dblink);
							die($lang['could not copy']);
						}
						if ($_FILES['imagefile']['size']==filesize($chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype)) {
							if ($imgWidth>$glob_maxthumbwidth||$imgHeight>$glob_maxthumbheight) {
								if (!createthumb($chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype,$chan_boardsdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype,$glob_maxthumbwidth,$glob_maxthumbheight)) {
									mysql_query("UNLOCK TABLES;",$dblink);
									die($lang['could not create thumbnail']);
								}
							} else {
								if (!createthumb($chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype,$chan_boardsdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype,$imgWidth,$imgHeight)) {
									mysql_query("UNLOCK TABLES;",$dblink);
									die($lang['could not create thumbnail']);
								}
							}
							$imageDim_thumb = getimagesize($chan_boardsdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype);
							$imgWidth_thumb = $imageDim_thumb[0];
							$imgHeight_thumb = $imageDim_thumb[1];
							$imageused = true;
						} else {
							mysql_query("UNLOCK TABLES;",$dblink);
							die($lang['file not fully uploaded']);
						}
					} else if ($filetype==".swf") {
						$thisimage_name = time().rand(1,99).$post_id;
						if (!move_uploaded_file($_FILES['imagefile']['tmp_name'],$chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype)) {
							mysql_query("UNLOCK TABLES;",$dblink);
							die($lang['could not copy'] );
						}
						if ($_FILES['imagefile']['size']==filesize($chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype)) {
							$imageused = true;
						} else {
							mysql_query("UNLOCK TABLES;",$dblink);
							die($lang['corrupt transfer']);
						}
					} else {
						mysql_query("UNLOCK TABLES;",$dblink);
						die($lang['improper filetype']);
					}
				} else {
					mysql_query("UNLOCK TABLES;",$dblink);
					die($lang['filetype not allowed']);
				}
			}
		}
		if ($post_id=='0') { $post_id = '1'; }
		if ($board_forcedanon=='1') {
			if ($user_authority=='0') {
				$post_name = "";
			}
		}
		$query = "INSERT INTO `posts` ( `boardid` , `id` , `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `imagemd5` , `password` , `postedat` , `lastbumped` , `ip` , `posterauthority` , `stickied` , `locked` ) VALUES ( '".$board_id."', '".$post_id."', '".$thread_replyto."', ";
		if (strpos($post_name,"#")!=false||(substr($post_name,0,1)=="#"&&substr($post_name,1)!="")) {
			$tripcode_user = substr($post_name,0,strpos($post_name,"#"));
			$tripcode_password = substr($post_name,strpos($post_name,"#")+1);
			$tripcode = substr(md5($tripcode_password),0,10);
			$query .= "'".substr($tripcode_user,0,100)."', '".$tripcode."', ";
		} else {
			$query .= "'".substr($post_name,0,100)."', '', ";
		}
		$filetype_withoutdot = substr($filetype,1);
		$post_password_md5 = ($post_password=='') ? '' : md5($post_password);
		$query .= "'".substr($post_email,0,100)."', '".substr($post_subject,0,100)."', '".$post_message."', '".mysql_escape_string($thisimage_name)."', '".$filetype_withoutdot."', '".$file_md5."', '".$post_password_md5."' , '".time()."', '".time()."' , '".$_SERVER['REMOTE_ADDR']."' , '".$user_authority."'";
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
		if ((file_exists($chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype)&&file_exists($chan_boardsdir."/".$board_dir."/thumb/".$thisimage_name.'s'.$filetype))||($filetype==".swf"&&file_exists($chan_boardsdir."/".$board_dir."/src/".$thisimage_name.$filetype))||$imageused==false) {
			mysql_query($query,$dblink);
			if (isset($_POST['name'])) {
				setcookie("name",urldecode($_POST['name']),time()+31556926,"/");
			}
			if ($_POST['email']!="sage"&&$_POST['email']!="age") {
				setcookie("email",urldecode($_POST['email']),time()+31556926,"/");
			}
			setcookie("postpassword",urldecode($_POST['postpassword']),time()+31556926,"/");
		} else {
			mysql_query("UNLOCK TABLES;",$dblink);
			die($lang['could not copy']);
		}
		mysql_query("UNLOCK TABLES;",$dblink);
		if ($thread_replyto!="0"&&$post_email!="sage") {
			if ($thread_replies<=$board_maxreplies) {
				mysql_query("UPDATE `posts` SET `lastbumped` = '".time()."' WHERE `id` = '".$thread_replyto."' AND `boardid` = '".$board_id."'",$dblink);
			}
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
		die($lang['board is locked']);
	}
} else if (isset($_POST['delete'])&&isset($_POST['postpassword'])) {
	if ($_POST['postpassword']!="") {
		$result = mysql_query("SELECT `id`,`threadid`,`password`,`image`,`imagetype` FROM `posts` WHERE `id` = '".mysql_escape_string($_POST['delete'])."' AND `boardid` = '".$board_id."' AND `IS_DELETED` = '0'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_assoc($result)) {
				$deletepost_id = $line['id'];
				$deletepost_threadid = $line['threadid'];
				$deletepost_password = $line['password'];
				$deletepost_image = $line['image'];
				$deletepost_imagetype = $line['imagetype'];
			}
			if (md5($_POST['postpassword'])==$deletepost_password) {
				if ($_POST['fileonly']=="on") {
					if ($deletepost_image!=""&&$deletepost_image!="removed") {
						$result = mysql_query("UPDATE `posts` SET `image` = 'removed' WHERE `id` = '".$deletepost_id."' AND `boardid` = '".$board_id."' AND `IS_DELETED` = '0' LIMIT 1",$dblink);
						if ($result) {
							@unlink($chan_boardsdir.'/'.$board_dir.'/src/'.$deletepost_image.'.'.$deletepost_imagetype);
							@unlink($chan_boardsdir.'/'.$board_dir.'/thumb/'.$deletepost_image.'s.'.$deletepost_imagetype);
							regenerate_board($board_dir,true);
							if ($deletepost_threadid!='0') {
								regenerate_thread($board_dir,$deletepost_threadid);
							}
							echo $lang['image successfully deleted'];
							echo '<meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$board_dir.'/board.html" />';
						} else {
							echo 'Error: '.mysql_error($dblink);
						}
					} else {
						echo $lang['post doesnt have message'];
					}
				} else {
					if (delete_post($deletepost_id,$board_dir)) {
						if ($deletepost_image!=""&&$deletepost_image!="removed") {
							@unlink($chan_boardsdir.'/'.$board_dir.'/src/'.$deletepost_image.'.'.$deletepost_imagetype);
							@unlink($chan_boardsdir.'/'.$board_dir.'/thumb/'.$deletepost_image.'s.'.$deletepost_imagetype);
						}
						regenerate_board($board_dir,true);
						if ($deletepost_threadid!='0') {
							regenerate_thread($board_dir,$deletepost_threadid);
						}
						echo $lang['post successfully deleted'];
						echo '<meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$board_dir.'/board.html" />';
					} else {
						echo $lang['error deleting post'];
					}
				}
			} else {
				echo $lang['incorrect password'];
			}
		} else {
			echo '<meta http-equiv="refresh" content="0;url='.$chan_boardspath.'/'.$board_dir.'/board.html" />';
		}
	} else {
		echo '<meta http-equiv="refresh" content="0;url='.$chan_boardspath.'/'.$board_dir.'/board.html" />';
	}
	die();
} else {
	echo '<meta http-equiv="refresh" content="0;url='.$chan_boardspath.'/'.$board_dir.'/board.html" />';
	die();
}
 
regenerate_board($board_dir,true);
if ($thread_replyto=="0") {
	print($lang['thread successfully posted']);
} else {
	print($lang['reply successfully posted']);
}
if ($board_redirecttothread=='1') {
	if ($thread_replyto=="0") {
		echo '<meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$board_dir.'/res/'.$post_id.'.html" />';
	} else {
		echo '<meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$board_dir.'/res/'.$thread_replyto.'.html" />';
	}
} else {
	echo '<meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$board_dir.'/" />';
}

die(); //Just in case...
?>
