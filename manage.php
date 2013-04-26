<?php

session_set_cookie_params(3600); 
session_start();

require("config.php");
require_once("inc/functions.php");

echo '<title>Manage boards</title><style type="text/css">
body { margin: 0; padding: 8px; margin-bottom: auto; } blockquote blockquote { margin-left: 0em } form { margin-bottom: 0px } form .trap { display:none } .postarea { text-align: center } .postarea table { margin: 0px auto; text-align: left } .thumb { border: none; float: left; margin: 2px 20px } .nothumb { float: left; background: #eee; border: 2px dashed #aaa; text-align: center; margin: 2px 20px; padding: 1em 0.5em 1em 0.5em; } .reply blockquote, blockquote :last-child { margin-bottom: 0em } .reflink a { color: inherit; text-decoration: none } .reply .filesize { margin-left: 20px } .userdelete { float: right; text-align: center; white-space: nowrap } .replypage .replylink { display: none }
</style>
<link rel="stylesheet" type="text/css" href="/css/futaba.css" title="Futaba" />';

if ($_POST['username']!=""&&$_POST['password']!="") {
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_POST['username']."' AND `password` = '".md5($_POST['password'])."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		$_SESSION['manageusername'] = $_POST['username'];
		$_SESSION['managepassword'] = md5($_POST['password']);
	} else {
		die("Incorrect username/password.");
	}
}
if (isset($_SESSION['manageusername'])&&isset($_SESSION['managepassword'])) {
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows==0) {
		session_destroy();
		die("Incorrect username/password.");
	} else {
		$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (management_isadmin()) {
				$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
				while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
					setcookie("tcmod","yes",time()+3600,$chan_webfolder."/".$lineboard['name']."/");
				}
			} else {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$array_boards = explode('|',$line['boards']);
				}
				foreach ($array_boards as $this_board_name) {
					setcookie("tcmod","yes",time()+3600,$chan_webfolder."/".$this_board_name."/");
				}
			}
		}
	}
} else {
	echo '<div style="text-align: center;"><form action="manage.php" method="post"><input type="password" name="username"><br /><input type="password" name="password"><br /><input type="submit" value="Submit"></form></div>';
	die();
}

echo '<div class="logo">Manage Boards</div>';
echo 'Welcome, '.$_SESSION['manageusername'].' [<a href="?action=logout">logout</a>]<br />';
if ($_SESSION['manageusername']=="admin"&&$_SESSION['managepassword']==md5("admin")) {
	echo '<font color="red"><b>NOTICE: You are using the default administrator account.  Anyone can log in to this account so a second administrator account needs to be created.  Create another then log into it, and delete this one.</b></font><br />';
}
echo 'Staff rights: <b>';
if (management_isadmin()) {
	echo 'Administrator';
} else {
	echo 'Moderator';
}
echo '</b>';
if (!management_isadmin()) {
	echo '<br />Moderating boards: ';
	$i = 0;
	$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		$i++;
		if (moderator_ismodofboard($lineboard['name'],$_SESSION['manageusername'])) {
			echo '<b>/'.$lineboard['name'].'/</b>, ';
		}
	}
	if ($i==0) {
		echo 'No boards.';
	} else {
		echo $i.' boards.';
	}
}
echo '<hr />';

if (management_isadmin()) {
	echo 'Administration: <a href="?globopts">Global options</a> - <a href="?action=addboard">Add board</a> - <a href="?action=delboard">Delete board</a> - <a href="?action=wordfilter">Wordfilter</a> - <a href="?action=rebuildall">Rebuild all boards and html files</a> - <a href="?action=cleanup">Cleanup</a> - <a href="?action=staff">Staff</a><br />';
}
echo 'Boards: <a href="?boardopts">Board options</a> - <a href="?action=stickypost">Manage stickies</a> - <a href="?action=delposts">Delete thread/post</a><br />';
echo 'Moderation: <a href="?action=bans">View/Add/Remove bans</a> - <a href="?action=getpwd">Get posting password</a>';

echo '<hr />';

if ($_GET['action']=="rebuildall") {
	management_adminsonly();
	regenerate_all_boards(true);
} else if (isset($_GET['globopts'])) {
	echo 'Coming soon.';
} else if (isset($_GET['boardopts'])) {
	echo 'Coming soon.';
} else if (substr($_GET['action'],0,6)=="sticky") {
	if ($_GET['action']=="stickypost"&&$_GET['postid']>0&&$_GET['board']!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_GET['board']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
				die("You are not a moderator of this board.");
			}
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$sticky_board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".$_GET['postid']."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				mysql_query("UPDATE `posts` SET `stickied` = '1' WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".$_GET['postid']."'",$dblink);
				regenerate_board($_GET['board']);
				echo 'Thread has successfully been stickied!';
			} else {
				echo 'Sorry, a thread with that ID doesn\'t seem to exist.';
			}
		} else {
			echo 'Invalid board directory.';
		}
		echo '<hr />';
	} else if ($_GET['action']=="unstickypost"&&$_GET['postid']>0&&$_GET['board']!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_GET['board']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
				die("You are not a moderator of this board.");
			}
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$sticky_board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".$_GET['postid']."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				mysql_query("UPDATE `posts` SET `stickied` = '0' WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".$_GET['postid']."'",$dblink);
				regenerate_board($_GET['board']);
				echo 'Thread has successfully been un-stickied!';
			} else {
				echo 'Sorry, a thread with that ID doesn\'t seem to exist.';
			}
		} else {
			echo 'Invalid board directory.';
		}
		echo '<hr />';
	}
	?>
	<form action="manage.php" method="get"><input type="hidden" name="action" value="stickypost">
	<label for="board">Board Directory:</label><input type="text" name="board"><br />
	<label for="postid">Post ID:</label><input type="text" name="postid"><input type="submit" value="Sticky!">
	</form>
	<hr />
	<form action="manage.php" method="get"><input type="hidden" name="action" value="unstickypost">
	<label for="board">Board Directory:</label><input type="text" name="board"><br />
	<label for="postid">Post ID:</label><input type="text" name="postid"><input type="submit" value="Un-Sticky!">
	</form>
	<?php
} else if ($_GET['action']=="cleanup") {
	management_adminsonly();
	delunusedimages(true);
	echo 'Cleanup finished!';
} else if ($_GET['action']=="bans") {
	if ($_POST['ip']!="") {
		$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".$_POST['ip']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows==0) {
			if ($_POST['seconds']>0) {
				mysql_query("INSERT INTO `banlist` ( `ip` , `by` , `at` , `until` , `reason` ) VALUES ( '".$_POST['ip']."' , '".$_POST['name']."' , '".time()."' , '".(time()+$_POST['seconds'])."' , '".$_POST['reason']."' )",$dblink);
				echo 'Ban successfully placed!';
			} else {
				echo 'Please enter a positive amount of seconds.';
			}
		} else {
			echo 'That IP has already been banned!';
		}
		echo '<hr />';
	} else if ($_GET['delban']>0) {
		$result = mysql_query("SELECT * FROM `banlist` WHERE `id` = '".$_GET['delban']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			mysql_query("DELETE FROM `banlist` WHERE `id` = '".$_GET['delban']."'",$dblink);
			echo 'Ban successfully removed!';
		} else {
			echo 'That is an invalid ban ID.';
		}
		echo '<hr />';
	}
	?>
	<form action="manage.php?action=bans" method="post">
	<label for="ip">IP:</label><input type="text" name="ip" /><br />
	<label for="seconds">Seconds:</label><input type="text" name="seconds" /><br />
	<label for="reason">Reason:</label><input type="text" name="reason" /><br />
	<label for="name">Your username:</label><input type="text" name="name" /><br />
	<input type="submit" value="Add Ban">
	</form>
	<hr />
	<?php
	$result = mysql_query("SELECT * FROM `banlist`",$dblink);
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		if (time()>=$line['until']) {
			mysql_query("DELETE FROM `banlist` WHERE `ip` = '".$line['ip']."'",$dblink);
		} else {
			echo $line['ip'].' - '.$line['reason'].' - At: '.date("F j, Y, g:i a",$line['at']).' - Until: '.date("F j, Y, g:i a",$line['until']).' - By: '.$line['by'].' - [<a href="manage.php?action=bans&delban='.$line['id'].'">x</a>]<br />';
		}
	}
} else if ($_GET['action']=="delposts") {
	if ($_POST['boarddir']!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_POST['boarddir']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!moderator_ismodofboard($_POST['boarddir'],$_SESSION['manageusername'])) {
				die("You are not a moderator of this board.");
			}
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
			}
			if ($_POST['delthreadid']>0) {
				$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$_POST['delthreadid']."' AND `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					$result = mysql_query("SELECT * FROM `posts` WHERE `threadid` = '".$_POST['delthreadid']."' AND `boardid` = '".$board_id."'",$dblink);
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						if ($line['image']!="") {
							unlink($chan_rootdir.'/'.$_POST['boarddir'].'/src/'.$line['image'].'.'.$line['imagetype']);
							unlink($chan_rootdir.'/'.$_POST['boarddir'].'/thumb/'.$line['image'].'s.'.$line['imagetype']);
						}
						mysql_query("DELETE FROM `posts` WHERE `id` = '".$line['id']."' AND `threadid` = '".$_POST['delthreadid']."' AND `boardid` = '".$board_id."'",$dblink);
					}
					unlink($chan_rootdir.'/'.$_POST['boarddir'].'/res/'.$line['id'].'.html');
					mysql_query("DELETE FROM `posts` WHERE `id` = '".$_POST['delthreadid']."' AND `boardid` = '".$board_id."'",$dblink);
					regenerate_board($_POST['boarddir']);
					echo 'Thread successfully deleted!';
				} else {
					echo 'A thread with that ID doesn\'t exist!';
				}
			} else if ($_POST['delpostid']>0) {
				$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$_POST['delthreadid']."' AND `boardid` = '".$board_id."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						if ($line['threadid']=="0") {
							if ($line['image']!="") {
								unlink($chan_rootdir.'/'.$_POST['boarddir'].'/src/'.$line['image'].'.'.$line['imagetype']);
								unlink($chan_rootdir.'/'.$_POST['boarddir'].'/thumb/'.$line['image'].'s.'.$line['imagetype']);
							}
							regenerate_thread($_POST['boarddir'],$line['threadid']);
							mysql_query("DELETE FROM `posts` WHERE `id` = '".$line['id']."' AND `boardid` = '".$board_id."'",$dblink);
							regenerate_board($_POST['boarddir']);
							echo 'Post successfully deleted!';
						} else {
							echo 'That post appears to be a thread.  Pleas use the delete thread feature instead.';
						}
					}
				} else {
					echo 'A post with that ID doesn\'t exist!';
				}
			}
		} else {
			echo 'Invalid board directory.';
		}
		echo '<hr />';
	}	
	?>
	<form action="manage.php?action=delposts" method="post">
	<label for="boarddir">Board dir:</label><input type="text" name="boarddir" /><br />
	<label for="delthreadid">Thread ID:</label><input type="text" name="delthreadid" /><br />
	<input type="submit" value="Delete Thread">
	</form>
	<hr />
	<form action="manage.php?action=delposts" method="post">
	<label for="boarddir">Board dir:</label><input type="text" name="boarddir" /><br />
	<label for="delpostid">Post ID:</label><input type="text" name="delpostid" /><br />
	<input type="submit" value="Delete Post">
	</form>
	<?php
} else if ($_GET['action']=="wordfilter") {
	management_adminsonly();
	if (isset($_POST['word'])) {
		if ($_POST['word']!=""&&$_POST['replacedby']!="") {
			$result = mysql_query("SELECT * FROM `wordfilter` WHERE `word` = '".$_POST['word']."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows==0) {
				$wordfilter_boards = array();
				$result = mysql_query("SELECT * FROM `boards`",$dblink);
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$wordfilter_boards = array_merge($wordfilter_boards,array($line['name']));
				}
				$wordfilter_changed_boards = array();
				$wordfilter_new_boards = array();
				while (list($postkey, $postvalue) = each($_POST)) {
					if (substr($postkey,0,10)=="wordfilter") {
						$wordfilter_changed_boards = array_merge($wordfilter_changed_boards,array(substr($postkey,10)));
					}
				}
				while (list(, $wordfilter_thisboard_name) = each($wordfilter_boards)) {
					if (in_array($wordfilter_thisboard_name,$wordfilter_changed_boards)) {
						$wordfilter_new_boards = array_merge($wordfilter_new_boards,array($wordfilter_thisboard_name));
					}
				}
				mysql_query("INSERT INTO `wordfilter` ( `word` , `replacedby` , `boards` , `time` ) VALUES ( '".$_POST['word']."' , '".$_POST['replacedby']."' , '".implode('|',$wordfilter_new_boards)."' , '".time()."' )",$dblink);
				echo 'Word successfully added to the wordfilter!';
			} else {
				echo 'That word already exists in the wordfilter!';
			}
		} else {
			echo 'You must enter both fields!';
		}
		echo '<hr />';
	} else if ($_GET['delword']>0) {
		$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".$_GET['delword']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			mysql_query("DELETE FROM `wordfilter` WHERE `id` = '".$_GET['delword']."'",$dblink);
			echo 'Word successfully removed from the wordfilter!';
		} else {
			echo 'That ID doesn\'t seem to exist.';
		}
		echo '<hr />';
	} else if ($_GET['editword']>0) {
		$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".$_GET['editword']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!isset($_POST['replacedby'])) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					?>
					<form action="manage.php?action=wordfilter&editword=<?php echo $_GET['editword']; ?>" method="post">
					<label for="word">Word:</label><input type="text" name="word" value="<?php echo $line['word']; ?>" disabled /><br />
					<label for="replacedby">Is replaced by:</label><input type="text" name="replacedby" value="<?php echo $line['replacedby']; ?>" /><br />
					<label>Boards:</label><br />
					<?php
					$array_boards = array();
					$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
					while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
						$array_boards = array_merge($array_boards,array($lineboard['name']));
					}
					foreach ($array_boards as $this_board_name) {
						echo '<label for="wordfilter'.$this_board_name.'">'.$this_board_name.'</label><input type="checkbox" name="wordfilter'.$this_board_name.'" ';
						if (in_array($this_board_name,explode("|",$line['boards']))&&explode("|",$line['boards'])!="") {
							echo 'checked ';
						}
						echo '/>';
					}
					?><br />
					<input type="submit" value="Edit Word">
					</form>
					<?php
				}
			} else {
				$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".$_GET['editword']."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					$wordfilter_boards = array();
					$result = mysql_query("SELECT * FROM `boards`",$dblink);
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$wordfilter_boards = array_merge($wordfilter_boards,array($line['name']));
					}
					$wordfilter_changed_boards = array();
					$wordfilter_new_boards = array();
					while (list($postkey, $postvalue) = each($_POST)) {
						if (substr($postkey,0,10)=="wordfilter") {
							$wordfilter_changed_boards = array_merge($wordfilter_changed_boards,array(substr($postkey,10)));
						}
					}
					while (list(, $wordfilter_thisboard_name) = each($wordfilter_boards)) {
						if (in_array($wordfilter_thisboard_name,$wordfilter_changed_boards)) {
							$wordfilter_new_boards = array_merge($wordfilter_new_boards,array($wordfilter_thisboard_name));
						}
					}
					mysql_query("UPDATE `wordfilter` SET `replacedby` = '".$_POST['replacedby']."' , `boards` = '".implode('|',$wordfilter_new_boards)."' WHERE `id` = '".$_GET['editword']."'",$dblink);
					echo 'Word successfully updated.';
				} else {
					echo 'That word doesn\'t seem to exist.';
				}
			}
		} else {
			echo 'That ID doesn\'t seem to exist.';
		}
		echo '<hr />';
	} else {
		?>
		<form action="manage.php?action=wordfilter" method="post">
		<label for="word">Word:</label><input type="text" name="word" /><br />
		<label for="replacedby">Is replaced by:</label><input type="text" name="replacedby" /><br />
		<label>Boards:</label><br />
		<?php
		$result = mysql_query("SELECT * FROM `boards`",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo '<label for="wordfilter'.$line['name'].'">'.$line['name'].'</label><input type="checkbox" name="wordfilter'.$line['name'].'" />';
		}
		?><br />
		<input type="submit" value="Add Word to Filter">
		</form>
		<hr />
		<?php
	}
	$result = mysql_query("SELECT * FROM `wordfilter`",$dblink);
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo 'Word: '.$line['word'].' - Replaced by: '.$line['replacedby'].' - Boards: ';
		if (explode('|',$line['boards'])!="") {
			echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;';
		} else {
			echo 'No boards.';
		}
		echo '[<a href="manage.php?action=wordfilter&editword='.$line['id'].'">edit</a>]&nbsp;[<a href="manage.php?action=wordfilter&delword='.$line['id'].'">del</a>]<br />';
	}
} else if ($_GET['action']=="addboard") {
	management_adminsonly();
	if (isset($_POST['directory'])) {
		if ($_POST['directory']!=""&&$_POST['desc']!="") {
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_POST['directory']."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows==0) {
				mkdir($chan_rootdir."/".$_POST['directory'], 0755);
				mkdir($chan_rootdir."/".$_POST['directory']."/res", 0755);
				mkdir($chan_rootdir."/".$_POST['directory']."/src", 0755);
				mkdir($chan_rootdir."/".$_POST['directory']."/thumb", 0755);
				mysql_query("INSERT INTO `boards` ( `name` , `desc` , `createdon` ) VALUES ( '".$_POST['directory']."' , '".$_POST['desc']."' , '".time()."' )",$dblink);
				regenerate_board($_POST['directory']);
				echo 'Board successfully added!  Visit it <a href="'.$chan_webpath.'/'.$_POST['directory'].'/board.html">here</a>!';
			} else {
				echo 'A board with that name already exists!';
			}
		} else {
			echo 'Both fields must be filled in!';
		}
	}
	?>
	<form action="manage.php?action=addboard" method="post">
	<label for="directory">Directory:</label><input type="text" name="directory" /><br />
	<label for="desc">Description:</label><input type="text" name="desc" /><br />
	<input type="submit" value="Add Board">
	</form>
	<?php
} else if ($_GET['action']=="delboard") {
	management_adminsonly();
	if (isset($_POST['directory'])) {
		if ($_POST['directory']!="") {
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_POST['directory']."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
				$board_dir = $line['name'];
			}
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				if ($_POST['confirmation']=='yes') {
					if (remove_board($chan_rootdir.'/'.$board_dir)) {
						mysql_query("DELETE FROM `posts` WHERE `boardid` = '".$board_id."'",$dblink);
						mysql_query("DELETE FROM `boards` WHERE `id` = '".$board_id."'",$dblink);
						echo 'Board successfully deleted!';
					} else {
						//Error
					}
				} else {
					echo 'Are you absolutely sure you want to delete /'.$board_dir.'/ ?<br />
					<form action="manage.php?action=delboard" method="post"><input type="hidden" name="directory" value="'.$_POST['directory'].'"><input type="hidden" name="confirmation" value="yes"><input type="submit" value="Continue"></form>';
				}
			} else {
				echo 'A board with that directory doesn\'t exist!';
			}
		}
		echo '<hr />';
	}
	?>
	<form action="manage.php?action=delboard" method="post">
	<label for="directory">Directory:</label><input type="text" name="directory" /><br />
	<input type="submit" value="Delete Board">
	</form>
	<?php
} else if ($_GET['action']=="staff") {
	management_adminsonly();
	if ($_POST['staffusername']!=""&&$_POST['staffpassword']!="") {
		$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_POST['staffusername']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows==0) {
			if ($_POST['isadmin']=="on") {
				mysql_query("INSERT INTO `staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".$_POST['staffusername']."' , '".md5($_POST['staffpassword'])."' , '1' , '".time()."' )",$dblink);
			} else {
				mysql_query("INSERT INTO `staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".$_POST['staffusername']."' , '".md5($_POST['staffpassword'])."' , '0' , '".time()."' )",$dblink);
			}
			echo 'Staff member succesfully added!';
		} else {
			echo 'A staff member with that username already exists!';
		}
		echo '<hr />';
	} else if ($_GET['del']>0) {
		$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".$_GET['del']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			mysql_query("DELETE FROM `staff` WHERE `id` = '".$_GET['del']."'",$dblink);
			echo 'Staff member successfully deleted.';
		} else {
			echo 'That is an invalid staff id.';
		}
		echo '<hr />';
	} else if ($_GET['edit']>0) {
		$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".$_GET['edit']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (isset($_POST['submitting'])) {
				$staff_boards = array();
				$result = mysql_query("SELECT * FROM `boards`",$dblink);
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$staff_boards = array_merge($staff_boards,array($line['name']));
				}
				$staff_changed_boards = array();
				$staff_new_boards = array();
				while (list($postkey, $postvalue) = each($_POST)) {
					if (substr($postkey,0,8)=="moderate") {
						$staff_changed_boards = array_merge($staff_changed_boards,array(substr($postkey,8)));
					}
				}
				while (list(, $staff_thisboard_name) = each($staff_boards)) {
					if (in_array($staff_thisboard_name,$staff_changed_boards)) {
						$staff_new_boards = array_merge($staff_new_boards,array($staff_thisboard_name));
					}
				}
				mysql_query("UPDATE `staff` SET `boards` = '".implode('|',$staff_new_boards)."' WHERE `id` = '".$_GET['edit']."'",$dblink);
				echo 'Staff successfully updated!<hr />';
			}
			$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".$_GET['edit']."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$staff_username = $line['username'];
				$staff_isadmin = $line['isadmin'];
				$staff_boards = explode('|',$line['boards']);
			}
			?>
			<form action="manage.php?action=staff&edit=<?php echo $_GET['edit']; ?>" method="post">
			<label for="staffname">Username:</label><input type="text" name="staffname" value="<?php echo $staff_username; ?>" disabled /><br />
			<label for="isadmin">Is Administrator:</label><input type="checkbox" name="isadmin" <?php if ($staff_isadmin=="1") { echo 'checked '; } ?>/><br /><br />
			Moderates:<br />
			<?php
			$result = mysql_query("SELECT * FROM `boards`",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				echo '<label for="moderate'.$line['name'].'">'.$line['name'].'</label><input type="checkbox" name="moderate'.$line['name'].'" ';
				if (in_array($line['name'],$staff_boards)) {
					echo 'checked ';
				}
				echo '/><br />';
			}
			?>
			<input type="submit" value="Modify Staff Member" name="submitting" />
			</form>
			<?php
		} else {
			echo 'A staff member with that id doesn\'t appear to exist.';
		}
		echo '<hr />';
	}
	?>
	<form action="manage.php?action=staff" method="post">
	<label for="username">Username:</label><input type="text" name="staffusername" /><br />
	<label for="password">Password:</label><input type="text" name="staffpassword" /><br />
	<label for="isadmin">Is Administrator:</label><input type="checkbox" name="isadmin" /><br />
	<input type="submit" value="Add Staff Member">
	</form>
	<hr />
	Admins:<br />
	<?php
	$result = mysql_query("SELECT * FROM `staff` WHERE `isadmin` = '1' ORDER BY `username` ASC",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo $line['username'].' - Added on: '.date("y/m/d(D)H:i",$line['addedon']).' [<a href="?action=staff&edit='.$line['id'].'">edit</a>] [<a href="?action=staff&del='.$line['id'].'">x</a>]<br />';
		}
	} else {
		echo 'None!';
	}
	echo '<hr />
	Mods:<br />';
	$result = mysql_query("SELECT * FROM `staff` WHERE `isadmin` = '0' ORDER BY `username` ASC",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo $line['username'].' - Added on: '.date("y/m/d(D)H:i",$line['addedon']).' - Moderates: ';
			if ($line['boards']!="") {
				echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>';
			} else {
				echo 'no boards';
			}
			echo ' [<a href="?action=staff&edit='.$line['id'].'">edit</a>] [<a href="?action=staff&del='.$line['id'].'">x</a>]<br />';
		}
	} else {
		echo 'None!';
	}
} else if ($_GET['quickdel']>0&&$_GET['board']!="") {
	if (moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
		if ($_GET['confirm']=='1') {
			if (delete_post($_GET['quickdel'],$_GET['board'])) {
				header("Location: ".$chan_webfolder."/".$_GET['board']."/");
			} else {
				echo 'Unable to delete thread/post, maybe it doesn\'t exist anymore?';
			}
		} else {
			echo 'You are about to delete the thread/post <strong>#'.$_GET['quickdel'].'</strong>.<br /><br /><form action="?quickdel='.$_GET['quickdel'].'&board='.$_GET['board'].'&confirm=1" method="post"><input type="submit" value="Continue"></form>';
		}
	}
} else if ($_GET['quickban']>0&&$_GET['board']!="") {
	if (moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_GET['board']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$_GET['quickban']."' AND `boardid` = '".$board_id."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$poster_ip = $line['ip'];
				$post_threadid = $line['threadid'];
				$post_message = $line['message'];
			}
			if ($_GET['confirm']=='1') {
				if (ban_user($poster_ip,$_SESSION['manageusername'],$_POST['duration']*60,$_POST['reason'])) {
					if ($_POST['addbannedmsg']=="on") {
						$post_message = $post_message.'<br /><br /><b><font color="red">(USER WAS BANNED FOR THIS POST)</font></b>';
						mysql_query("UPDATE `posts` SET `message` = '".$post_message."' WHERE `id` = '".$_GET['quickban']."' AND `boardid` = '".$board_id."'",$dblink);
						regenerate_thread($_GET['board'],$post_threadid);
						regenerate_board($_GET['board'],true);
					}
					header("Location: ".$chan_webfolder."/".$_GET['board']."/");
				} else {
					echo 'Unable to ban user.';
				}
			} else {
				echo 'You are about to ban the poster of thread/post <strong>#'.$_GET['quickdel'].'</strong>.<br /><br /><form action="?quickban='.$_GET['quickban'].'&board='.$_GET['board'].'&confirm=1" method="post"><label for="ip">IP:</label><input type="text" name="ip" value="'.$poster_ip.'" disabled /><br /><label for="duration">Duration (Minutes):</label><input type="text" name="duration" value="1440" /><br /><label for="reason">Reason:</label><input type="text" name="reason" value="No reason." /><br /><label for="reason">Append <b><font color="red">(USER WAS BANNED FOR THIS POST)</font></b>:</label><input type="checkbox" name="addbannedmsg" checked /><br /><input type="submit" value="Continue"></form>';
			}
		} else {
			echo 'Invalid board.';
		}
	}
} else if ($_GET['quickdelban']>0&&$_GET['board']!="") {
	if (moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_GET['board']."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".$_GET['quickdelban']."' AND `boardid` = '".$board_id."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$poster_ip = $line['ip'];
				$post_threadid = $line['threadid'];
				$post_message = $line['message'];
			}
			if ($_GET['confirm']=='1') {
				if (ban_user($poster_ip,$_SESSION['manageusername'],$_POST['duration']*60,$_POST['reason'])) {
					//Yay, it worked
				} else {
					die("Unable to ban user.");
				}
				if (delete_post($_GET['quickdelban'],$_GET['board'])) {
					header("Location: ".$chan_webfolder."/".$_GET['board']."/");
				} else {
					echo 'Unable to delete thread/post, maybe it doesn\'t exist anymore?';
				}
			} else {
				echo 'You are about to delete the thread/post <strong>#'.$_GET['quickdel'].'</strong>, then <b>ban</b> the poster.<br /><br /><form action="?quickdelban='.$_GET['quickdelban'].'&board='.$_GET['board'].'&confirm=1" method="post"><label for="ip">IP:</label><input type="text" name="ip" value="'.$poster_ip.'" disabled /><br /><label for="duration">Duration (Minutes):</label><input type="text" name="duration" value="1440" /><br /><label for="reason">Reason:</label><input type="text" name="reason" value="No reason." /><br /><input type="submit" value="Continue"></form>';
			}
		} else {
			echo 'Invalid board.';
		}
	}
} else if ($_GET['action']=="getpwd") {
	require_once("inc/encryption.php");
	echo 'Your posting password:<br /><b>'.md5_encrypt($_SESSION['manageusername'],$chan_randomseed).'</b>';
} else if ($_GET['action']=="logout") {
	if (management_isadmin()) {
		$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
		while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
			setcookie("tcmod","",time()+3600,$chan_webfolder."/".$lineboard['name']."/");
		}
	} else {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$array_boards = explode('|',$line['boards']);
		}
		foreach ($array_boards as $this_board_name) {
			setcookie("tcmod","",time()+3600,$chan_webfolder."/".$this_board_name."/");
		}
	}
	session_destroy();
	header("Location: manage.php");
	die();
}

require("inc/footer.php");
echo chan_footer();

?>