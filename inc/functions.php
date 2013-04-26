<?php

//Management
function management_adminsonly() {
	require("config.php");
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
	//die("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."'".$dblink);
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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
function moderator_ismodofboard($board,$username) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$username."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
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

//Maintenance
function delunusedimages($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		$imagelist = array();
		$imagethumbnaillist = array();
		$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$lineboard['id']."'",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$imagelist = array_merge($imagelist,array($line['image'].'.'.$line['imagetype']));
			$imagethumbnaillist = array_merge($imagethumbnaillist,array($line['image'].'s.'.$line['imagetype']));
		}
		$dir = './'.$lineboard['name'].'/src';
		$files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $image) {
				if (in_array(substr($image,strrpos($image,'/')+1),$imagelist)==false) {
					if ($verbose==true) {
						echo $image.' NOT IN DATABASE! Deleted.<br />';
					}
					unlink($chan_rootdir.'/'.$lineboard['name'].'/src/'.substr($image,strrpos($image,'/')+1));
				}
			}
		}
		$dir = './'.$lineboard['name'].'/thumb';
		$files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $image) {
				if (in_array(substr($image,strrpos($image,'/')+1),$imagethumbnaillist)==false) {
					if ($verbose==true) {
						echo $image.' (thumb) NOT IN DATABASE! Deleted.<br />';
					}
					unlink($chan_rootdir.'/'.$lineboard['name'].'/thumb/'.substr($image,strrpos($image,'/')+1));
				}
			}
		}
	}
	return true;
}

//Boards
function regenerate_board($board,$pagesonly = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		$board_id = $lineboard['id'];
		$board_dir = $lineboard['name'];
		$board_desc = $lineboard['desc'];
		$board_locked = $lineboard['locked'];
		//Delete old pages
		$dir = $chan_rootdir.'/'.$board_dir;
		$files = glob ("$dir/{*.html}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $htmlfile) {
				if (preg_match("/[0-9+].html/",$htmlfile)) {
					unlink($htmlfile);
				}
			}
		}
		$buffer = preg_replace('/[>][>]([0-9]+)/', '<a href="/'.$board.'/res/'.$threadid.'.html#i\\1">&gt;&gt;\\1</a>', $buffer);
		//Rebuild pages
		$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC",$dblink);
		$numpostsleft = mysql_num_rows($result);
		$boardstooutput = floor(($numpostsleft-1) / 10);
		$boardpage = 0;
		if ($numpostsleft>0) {
			while ($numpostsleft>0) {
				ob_start();
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/header.php?board=".$board_dir."");
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_exec($ch);
				curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/postbox.php?board=".$board_dir."");
				curl_exec($ch);
				curl_close($ch);
				$query = "SELECT * FROM `posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".($boardpage*10).",10";
				$result_leftposts = mysql_query($query,$dblink);
				while ($line = mysql_fetch_array($result_leftposts, MYSQL_ASSOC)) {
					$thread_id = $line['id'];
					echo buildthread($board_dir,$thread_id,true);
				}
				echo '<table border="1"><tbody><tr><td>';
				if ($boardpage==0) {
					echo 'Previous';
				} else {
					if ($boardpage-1!=0) {
						echo '<form method="get" action="'.$chan_webpath.'/'.$board_dir.'/'.($boardpage-1).'.html"><input value="Previous" type="submit" /></form>';
					} else {
						echo '<form method="get" action="'.$chan_webpath.'/'.$board_dir.'/board.html"><input value="Previous" type="submit" /></form>';
					}
				}
				echo '</td><td>';
				$i = 0;
				while ($i <= $boardstooutput) {
					if ($boardpage==$i) {
						echo '&#91;'.$i.'&#93;';
					} else {
						if ($i!=0) {
							echo '&#91;<a href="'.$chan_rootdir.'/'.$board_dir.'/'.$i.'.html">'.$i.'</a>&#93;';
						} else {
							echo '&#91;<a href="'.$chan_rootdir.'/'.$board_dir.'/board.html">'.$i.'</a>&#93;';
						}
					}
					$i++;
				}
				echo '</td><td>';
				if ($boardpage==$boardstooutput) {
					echo "Next";
				} else {
					echo '<form method="get" action="'.$chan_webpath.'/'.$board_dir.'/'.($boardpage+1).'.html"><input value="Next" type="submit" /></form>';
				}
				echo '</td></tr></tbody></table>';
				$ch = curl_init();
				curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/footer.php");
				curl_setopt($ch, CURLOPT_HEADER, 0);
				curl_exec($ch);
				curl_close($ch);
				$page = ob_get_clean();
				if ($boardpage==0) {
					file_put_contents($chan_rootdir."/".$board_dir."/board.html",$page);
				} else {
					file_put_contents($chan_rootdir."/".$board_dir."/".$boardpage.".html",$page);
				}
				$page = "";
				$boardpage++;
				$numpostsleft -= 10;
			}
		} else { //Make a blank index
			$thread_page = '';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/header.php?board=".$board_dir);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			$thread_page .= $file_contents;
			$file_contents = '';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/postbox.php?board=".$board_dir);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			$thread_page .= $file_contents;
			$file_contents = '';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/footer.php");
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			$thread_page .= $file_contents;
			$file_contents = '';
			file_put_contents($chan_rootdir."/".$board_dir."/board.html",$thread_page);
		}
		//Rebuild /res/
		if ($pagesonly==false) {
			$res_threadlist = array();
			$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$lineboard['id']."' AND `threadid` = '0' ORDER BY `lastbumped` DESC",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$res_threadlist = array_merge($res_threadlist,array($line['id'].'.html'));
				regenerate_thread($lineboard['name'],$line['id']);
			}
			$dir = $chan_rootdir.'/'.$board_dir.'/res';
			$files = glob ("$dir/{*.html}",  GLOB_BRACE);
			if (is_array($files)) { 
				foreach ($files as $htmlfile) {
					if (in_array(substr($htmlfile,strrpos($htmlfile,'/')+1),$res_threadlist)==false) {
						unlink($htmlfile);
					}
				}
			}
		}
		delunusedimages();
	}
}
function regenerate_all_boards($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		regenerate_board($lineboard['name']);
		if ($verbose==true) {
			echo 'Regenerated /'.$lineboard['name'].'/<br />';
		}
	}
}

//Threads
function buildthread($board,$threadid,$page = false) {
	ob_start();
	require("config.php");
	if (!$threadid>0||$board=="") {
		die();
	}
	if ($board!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
				$board_dir = $line['name'];
				$board_desc = $line['desc'];
			}
		} else {
			die("Invlaid board ID.");
		}
	} else {
		die("Invlaid board ID.");
	}
	
	$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$threadid."' AND `boardid` = '".$board_id."' AND `threadid` = '0'",$dblink);
	$rows = mysql_num_rows($result);
	if (!$rows>0) {
		die();
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$thread_id = $line['id'];
		$imageDim = getimagesize($chan_rootdir."/".$board_dir."/src/".$line['image'].'.'.$line['imagetype']);
		$imgWidth = $imageDim[0];
		$imgHeight = $imageDim[1];
		$imageDim_thumb = getimagesize($chan_rootdir."/".$board_dir."/thumb/".$line['image'].'s.'.$line['imagetype']);
		$imgWidth_thumb = $imageDim_thumb[0];
		$imgHeight_thumb = $imageDim_thumb[1];
		$result2 = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."'");
		$numReplies = mysql_num_rows($result2);
		$numImageReplies = 0;
		if ($page==true) {
			if ($line['stickied']=="0") {
				if ($numReplies>3) {
					$result2 = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-3)."",$dblink);
					while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
						if ($line2['image']!="") {
							$numImageReplies++;
						}
					}
				}
			} else {
				if ($numReplies>1) {
					$result2 = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-1)."",$dblink);
					while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
						if ($line2['image']!="") {
							$numImageReplies++;
						}
					}
				}
			}
		}
		?>
		<span class="filesize">File: <a rel="external" href="<?php echo $chan_webfolder."/".$board_dir."/src/".$line['image']; ?>.<?php echo $line['imagetype']; ?>"><?php echo $line['image']; ?>.jpg</a> -(<em><?php
		echo filesize($chan_rootdir."/".$board_dir."/src/".$line['image'].'.'.$line['imagetype']).' B, '.$imgWidth.'x'.$imgHeight; ?>
		</em>)</span><span class="thumbnailmsg"> Thumbnail displayed, click image for full size.</span><br />
		<a rel="external" href="<?php echo $chan_webfolder."/".$board_dir."/src/".$line['image']; ?>.<?php echo $line['imagetype']; ?>">
		<img src="<?php echo $chan_webfolder."/".$board_dir."/thumb/".$line['image']; ?>s.<?php echo $line['imagetype']; ?>" width="<?php echo $imgWidth_thumb; ?>" height="<?php echo $imgHeight_thumb; ?>" alt="<?php echo $thread_id; ?>" class="thumb" /></a><a name="<?php echo $thread_id; ?>">
		</a><label><input type="checkbox" name="delete" value="<?php echo $thread_id; ?>" />&nbsp;
		<?php if ($line['subject']!="") { echo '<span class="filetitle">'.$line['subject'].'</span>&nbsp;'; } ?>
		<span class="postername"><?php
		if ($line['email']!="") {
			echo '<a href="mailto:'.$line['email'].'">';
		}
		if ($line['user']=="") {
			echo 'Anonymous';
		} else {
			echo stripslashes($line[user]);
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
		<span class="reflink"><a href="<?php echo $chan_webfolder."/".$board_dir."/res/".$thread_id; ?>.html#i<?php echo $thread_id; ?>" onclick="insert('>><?php echo $thread_id; ?>');">No.<?php echo $thread_id; ?></a><?php if ($line['stickied']=="1") { echo '<img style="border: 0;" src="/sticky.gif" alt="stickied" />'; } ?></span>
		<?php if ($page==true) { echo '&nbsp;&#91;<a href="'.$chan_webfolder.'/'.$board_dir.'/res/'.$thread_id.'.html">Reply</a>&#93;'; } ?>
		<?php echo '<script type="text/javascript">
		if (getCookie("tcmod")=="yes") {
			document.write(\'&nbsp;&#91;<a href="'.$chan_webfolder.'/manage.php?quickdel='.$thread_id.'&board='.$board_dir.'">D</a> <a href="'.$chan_webfolder.'/manage.php?quickdelban='.$thread_id.'&board='.$board_dir.'">&amp;</a> <a href="'.$chan_webfolder.'/manage.php?quickban='.$thread_id.'&board='.$board_dir.'">B</a>&#93;\');
		}
		</script>'; ?>
		<blockquote>
		<p><?php echo stripslashes($line['message']); ?></p>
		</blockquote>
		<?php
		if ($numReplies>0) {
			if ($page==false) {
				$result2 = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '".$thread_id."' AND `boardid` = '".$board_id."' ORDER BY `postedat` ASC",$dblink);
			} else {
				$query = "SELECT * FROM `posts` WHERE `threadid` = '".$thread_id."' AND `boardid` = '".$board_id."' ORDER BY `postedat` ASC LIMIT ";
				if ($line['stickied']=="0") {
					$query .= max($numReplies-3,0).",3";
					if ($numReplies>3) {
						echo '<span class="omittedposts"> '.($numReplies-3).' post';
						if ($numReplies-3!=1) {
							echo 's';
						}
						echo ' and '.$numImageReplies.' image';
						if ($numImageReplies!=1) {
							echo 's';
						}
						echo ' omitted. Click Reply to view.  </span>';
					}
				} else {
					$query .= max($numReplies-1,0).",1";
					if ($numReplies>1) {
						echo '<span class="omittedposts"> '.($numReplies-1).' post';
						if ($numReplies-1!=1) {
							echo 's';
						}
						echo ' and '.$numImageReplies.' image';
						if ($numImageReplies!=1) {
							echo 's';
						}
						echo ' omitted. Click Reply to view.  </span>';
					}
				}
				$result2 = mysql_query($query,$dblink);
			}
			while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
				$reply_id = $line2['id'];
				$reply_user = stripslashes($line2['user']);
				$reply_tripcode = $line2['tripcode'];
				$reply_email = stripslashes($line2['email']);
				$reply_subject = $line2['subject'];
				$reply_message = stripslashes($line2['message']);
				$reply_postedat = $line2['postedat'];
				$reply_image = $line2['image'];
				$reply_imagetype = $line2['imagetype'];
				$reply_posterauthority = $line2['posterauthority'];
				if ($reply_image!="") {
					$reply_imageDim = getimagesize($chan_rootdir."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype);
					$reply_imgWidth = $reply_imageDim[0];
					$reply_imgHeight = $reply_imageDim[1];
					$reply_imageDim_thumb = getimagesize($chan_rootdir."/".$board_dir."/thumb/".$reply_image.'s.'.$reply_imagetype);
					$reply_imgWidth_thumb = $reply_imageDim_thumb[0];
					$reply_imgHeight_thumb = $reply_imageDim_thumb[1];
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
				if ($reply_user=="") {
					echo 'Anonymous';
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
				echo '&nbsp;'.date("y/m/d(D)H:i",$reply_postedat); ?></label> <span class="reflink"><a href="<?php echo $chan_webfolder."/".$board_dir."/res/".$thread_id; ?>.html#<?php echo $reply_id; ?>" onclick="insert('>><?php echo $reply_id; ?>');">No.<?php echo $reply_id; ?></a></span>
				<?php echo '<script type="text/javascript">
				if (getCookie("tcmod")=="yes") {
					document.write(\'&nbsp;&#91;<a href="'.$chan_webfolder.'/manage.php?quickdel='.$reply_id.'&board='.$board_dir.'">D</a> <a href="'.$chan_webfolder.'/manage.php?quickdelban='.$reply_id.'&board='.$board_dir.'">&amp;</a> <a href="'.$chan_webfolder.'/manage.php?quickban='.$reply_id.'&board='.$board_dir.'">B</a>&#93;\');
				}
				</script>'; ?>
				&nbsp;<br />
				<?php
				if ($reply_image!="") {
					?>
					<span class="filesize">File: <a rel="external" href="<?php echo $chan_webfolder."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype; ?>"><?php echo $reply_image.'.'.$reply_imagetype; ?></a> -(<em><?php echo filesize($chan_rootdir."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype).' B, '.$reply_imgWidth_thumb.'x'.$reply_imgHeight_thumb; ?></em>)</span> <span class="thumbnailmsg">Thumbnail displayed, click image for full size.</span><br />
					<a rel="external" href="<?php echo $chan_webfolder."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype; ?>"><img src="<?php echo $chan_webfolder."/".$board_dir."/thumb/".$reply_image.'s.'.$reply_imagetype; ?>" width="<?php echo $reply_imgWidth_thumb; ?>" height="<?php echo $reply_imgHeight_thumb; ?>" alt="<?php echo $reply_id; ?>" class="thumb" /></a>
					<?php
				}
				?>
				<blockquote>
				<p><?php echo $reply_message; ?></p>
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
		$output = ob_get_clean();
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
function regenerate_thread($board,$threadid) {
	require("config.php");
	$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		unlink($chan_rootdir.'/'.$lineboard['name'].'/res/'.$threadid.'.html');
		$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$lineboard['id']."' AND `threadid` = '0' AND `id` = '".$threadid."'",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$thread_page = '';
			$post_id = $line['id'];
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/header.php?board=".$lineboard['name']);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			$thread_page .= $file_contents;
			$file_contents = '';
			$thread_page .= '&#91;<a href="'.$chan_webfolder.'/'.$lineboard['name'].'/board.html">Return</a>&#93; <div class="theader">Posting mode: Reply</div>';
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/postbox.php?board=".$lineboard['name']);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			$thread_page .= $file_contents;
			$file_contents = '';
			$thread_page .=  buildthread($lineboard['name'],$post_id);
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $chan_webpath."/inc/footer.php");
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			$file_contents = curl_exec($ch);
			curl_close($ch);
			$thread_page .= $file_contents;
			$file_contents = '';
			file_put_contents($lineboard['name']."/res/".$post_id.".html",$thread_page);
		}
	}
}
function delete_post($post,$board) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);	
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_id = $line['id'];
		}
		$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);	
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['threadid']=="0") {
				$post_is_thread = true;
			} else {
				$post_is_thread = false;
			}
		}
		if ($post_is_thread==true) {
			$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$post."' AND `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				$result = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if ($line['image']!="") {
						unlink($chan_rootdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
						unlink($chan_rootdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
					}
					mysql_query("DELETE FROM `posts` WHERE `id` = '".$line['id']."' AND `threadid` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
				}
				unlink($chan_rootdir.'/'.$board.'/res/'.$post.'.html');
				mysql_query("DELETE FROM `posts` WHERE `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
				regenerate_board($board);
			}
			return true;
		} else {
			$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if ($line['threadid']!="0") {
						if ($line['image']!="") {
							unlink($chan_rootdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
							unlink($chan_rootdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
						}
						regenerate_thread($board,$line['threadid']);
						mysql_query("DELETE FROM `posts` WHERE `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
						regenerate_board($board);
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

//Users
function ban_user($ip,$modname,$duration,$reason) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".$ip."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows==0) {
		if ($duration>0) {
			mysql_query("INSERT INTO `banlist` ( `ip` , `by` , `at` , `until` , `reason` ) VALUES ( '".$ip."' , '".$modname."' , '".time()."' , '".(time()+$duration)."' , '".$reason."' )",$dblink);
			return true;
		} else {
			return false;
		}
	} else {
		return true();
	}
}

//Posting
function getnextpostid($dblink,$boardid) {
	$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$boardid."' ORDER BY `id` DESC LIMIT 1",$dblink);	
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		return $line['id']+1;
	}
}

//Image handling
function createthumb($name,$filename,$new_w,$new_h) {
	$system=explode(".",$name);
	if (preg_match("/jpg|jpeg/",$system[1])){$src_img=imagecreatefromjpeg($name);}
	if (preg_match("/png/",$system[1])){$src_img=imagecreatefrompng($name);}
	if (preg_match("/gif/",$system[1])){$src_img=imagecreatefromgif($name);}
	$old_x=imageSX($src_img);
	$old_y=imageSY($src_img);
	if ($old_x > $old_y) 
	{
		$thumb_w=$new_w;
		$thumb_h=$old_y*($new_h/$old_x);
	}
	if ($old_x < $old_y) 
	{
		$thumb_w=$old_x*($new_w/$old_y);
		$thumb_h=$new_h;
	}
	if ($old_x == $old_y) 
	{
		$thumb_w=$new_w;
		$thumb_h=$new_h;
	}
	$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
	imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y); 
	if (preg_match("/png/",$system[1])) {
		imagepng($dst_img,$filename); 
	} else if (preg_match("/jpg|jpeg/",$system[1])) {
		imagejpeg($dst_img,$filename); 
	} else if (preg_match("/gif/",$system[1])) {
		imagegif($dst_img,$filename); 
	}
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
}

?>