<?php

session_set_cookie_params(3600); 
session_start();

require("config.php");
require_once("inc/functions.php");


if ($_GET['action']=="logout") { //Do this first to get no header errors
	if (management_isadmin()) {
		$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
		while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
			setcookie("tcmod","",time()+3600,$chan_webfolder."/".$lineboard['name']."/");
		}
	} else {
		$result = mysql_query("SELECT * FROM `boards`",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			setcookie("tcmod","",time()+3600,$chan_webfolder."/".$this_board_name."/");
		}
	}
	session_destroy();
	unset($_SESSION['manageusername']);
	unset($_SESSION['managepassword']);
	echo 'You have been successfully logged out.<meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/manage.php" />';
	die();
}

if ($_POST['username']!=""&&$_POST['password']!="") {
	mysql_query("DELETE FROM `loginattempts` WHERE `timestamp` < '".(time()-1200)."'",$dblink);
	$result = mysql_query("SELECT * FROM `loginattempts` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>5) {
		die("Sorry, because of your numerous failed logins, you have been locked out from logging in for 20 minutes.  Please wait and then try again.");
	} else {
		$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_POST['username'])."' AND `password` = '".md5($_POST['password'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			mysql_query("DELETE FROM `loginattempts` WHERE `ip` < '".$_SERVER['REMOTE_ADDR']."'",$dblink);
			$_SESSION['manageusername'] = $_POST['username'];
			$_SESSION['managepassword'] = md5($_POST['password']);
			management_addlogentry("Logged in");
		} else {
			mysql_query("INSERT INTO `loginattempts` ( `username` , `ip` , `timestamp` ) VALUES ( '".mysql_escape_string($_POST['username'])."' , '".$_SERVER['REMOTE_ADDR']."' , '".time()."' )",$dblink);
			die("Incorrect username/password.");
		}
	}
}
if (isset($_SESSION['manageusername'])&&isset($_SESSION['managepassword'])) {
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."' AND `password` = '".mysql_escape_string($_SESSION['managepassword'])."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows==0) {
		session_destroy();
		die("Invalid session.<br /><br /><a href=\"manage.php\">Log In Again</a>");
	} else {
		$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."'",$dblink);
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
		} else {
			session_destroy();
			die("Invalid session.<br /><br /><a href=\"manage.php\">Log In Again</a>");
		}
	}
} else {
	echo '<script type="text/javascript">function sf(){document.managelogin.username.focus();}</script><body onload="sf();"></body><div style="text-align: center;"><img src="hardgay.gif" alt="Pikachu" title="PIKA PIKA! CHUUUUU~~~" /><br /><form action="manage.php" method="post" name="managelogin"><input type="text" name="username"><br /><input type="password" name="password"><br /><input type="submit" value="Submit"></form></div>';
	die();
}
$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."'",$dblink);
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
removed_expired_bans();

echo '<title>Manage boards</title>
<!--<link rel="stylesheet" type="text/css" href="'.$chan_webfolder.'/css/burichan.css" title="Burichan" />-->
<link rel="stylesheet" type="text/css" href="'.$chan_webfolder.'/css/manage.css" title="Manage" />';
?>
<script type="text/javascript">
window.onload = function()
{
	if(document.getElementById)
	{
		var linkContainer = document.getElementById('linkContainer');
		var toggle = linkContainer.appendChild(document.createElement('a'));
		toggle.href = '#';
		toggle.appendChild(document.createTextNode('Remove optional fields?'));
		toggle.onclick = function()
		{
			var linkText = this.firstChild.nodeValue;
			this.firstChild.nodeValue = (linkText == 'Remove optional fields?') ? 'Display optional fields?' : 'Remove optional fields?';
			
			var tmp = document.getElementsByTagName('div');
			for (var i=0;i<tmp.length;i++)
			{
				if(tmp[i].className == 'fm-optional')
				{
					tmp[i].style.display = (tmp[i].style.display == 'none') ? 'block' : 'none';
				}
			}
			return false;
		}
	}
}
</script>
<?php
echo '<div style="text-align: center;"><h1>Manage Boards</h1></div>';
echo 'Welcome, '.$_SESSION['manageusername'].' [<a href="?action=logout">logout</a>]<br />';
if ($_SESSION['manageusername']=="admin"&&$_SESSION['managepassword']==md5("admin")) {
	echo '<font color="red"><b>NOTICE: You are using the default administrator account.  Anyone can log in to this account so a second administrator account needs to be created.  Create another, log in to it, and delete this one.</b></font><br />';
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
echo '<div style="text-align: left;">';

if (management_isadmin()) {
	echo 'Administration: <a href="?action=addnews">Add news</a> - <a href="?globopts">Global options</a> - <a href="?action=addboard">Add board</a> - <a href="?action=delboard">Delete board</a> - <a href="?action=wordfilter">Wordfilter</a> - <a href="?action=rebuildall">Rebuild all boards and html files</a> - <a href="?action=cleanup">Cleanup</a> - <a href="?action=staff">Staff</a> - <a href="?action=modlog">ModLog</a> - <a href="?action=sql">SQL Query</a><br />';
}
echo 'Boards: <a href="?boardopts">Board options</a> - <a href="?action=stickypost">Manage stickies</a> - <a href="?action=lockpost">Manage locked threads</a> - <a href="?action=delposts">Delete thread/post</a><br />';
echo 'Moderation: <a href="?action=bans">View/Add/Remove bans</a> - <a href="?action=deletepostsbyip">Delete all posts by IP</a> - <a href="?action=getpwd">Get posting password</a> - <a href="?action=changepwd">Change account password</a>';

echo '</div>';
echo '<hr />';
if ($_GET['test']=='1') {
	regenerate_board('test');
}
if ($_GET['action']=="rebuildall") {
	management_adminsonly();
	regenerate_all_boards(true);
	management_addlogentry("Rebuilt all boards and threads");
} else if ($_GET['action']=="modlog") {
	management_adminsonly();
	mysql_query("DELETE FROM `modlog` WHERE `timestamp` < '".(time()-config_getvalue(modlogmaxdays)*86400)."'",$dblink);
	echo '<h2>ModLog</h2><form><textarea rows="40" cols="80">';
	$result = mysql_query("SELECT * FROM `modlog` ORDER BY `timestamp` DESC",$dblink);
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		echo date("y/m/d(D)H:i",$line['timestamp'])." | ".$line['user']." | ".$line['entry'].chr(10);
	}
	echo '</textarea></form>';
} else if ($_GET['action']=="sql") {
	management_adminsonly();
	echo '<h2>SQL Query</h2>';
	if ($_POST['query']!="") {
		echo '<hr />';
		$result = mysql_query($_POST['query'],$dblink);
		if ($result) {
			echo 'Query executed successfully';
		} else {
			echo 'Error: '.mysql_error($dblink);
		}
		echo '<hr />';
	}
	echo '<form method="post" action="?action=sql"><textarea name="query" rows="20" cols="60"></textarea><br /><input type="submit" value="Inject" /></form>';
} else if ($_GET['action']=="addnews") {
	management_adminsonly();
	echo '<h2>Add News Post</h2>This message will be displayed as it is written, so make sure you add the proper HTML.<br /><br />';
	if ($_POST['news']!="") {
		echo '<hr />';
		if ($_POST['subject']!="") {
			mysql_query("INSERT INTO `news` ( `subject` , `message` , `postedat` , `postedby` , `postedemail` ) VALUES ( '".mysql_escape_string($_POST['subject'])."' , '".mysql_escape_string($_POST['news'])."' , '".time()."' , '".mysql_escape_string($_SESSION['manageusername'])."' , '".mysql_escape_string($_POST['email'])."' )",$dblink);
			echo '<h3>News entry successfully added.</h3>';
			management_addlogentry("Added a news entry");
		} else {
			echo 'You must enter a subject.';
		}
		echo '<hr />';
	}
	echo '<form method="post" action="?action=addnews"><label for="subject">Subject:</label><input type="text" name="subject" value="" /><div class="desc">Can <b>not</b> be left blank.</div><br />
	<textarea name="news" rows="25" cols="80">';
	echo 'News post goes here.<br />
<br />
Make sure to enter <br />\'s, not just line returns!';
	echo '</textarea><br /><label for="email">E-mail:</label><input type="text" name="email" value="" /><div class="desc">Can be left blank.</div><br /><input type="submit" value="Add!" /></form>';
} else if (isset($_GET['globopts'])) {
	management_adminsonly();
	if (isset($_POST['imagesinnewwindow'])) {
		if (!$_POST['maxthumbwidth']>0||!$_POST['maxthumbheight']>0) {
			die("Maximum thumbnail width/height must be greater than zero.");
		}
		if ($_POST['imagesinnewwindow']=='1') {
			config_setvalue('imagesinnewwindow','1');
		} else {
			config_setvalue('imagesinnewwindow','0');
		}
		config_setvalue('maxthumbwidth',$_POST['maxthumbwidth']);
		config_setvalue('maxthumbheight',$_POST['maxthumbheight']);
		config_setvalue('modlogmaxdays',$_POST['modlogmaxdays']);
		config_setvalue('postboxnotice',$_POST['postboxnotice']);
		echo 'Global configuration successfully updated.';
		management_addlogentry("Updated global configuration");
		echo '<hr>';
	}
	$config_maxthumbwidth = config_getvalue('maxthumbwidth');
	$config_maxthumbheight = config_getvalue('maxthumbheight');
	$config_imagesinnewwindow = config_getvalue('imagesinnewwindow');
	$config_modlogmaxdays = config_getvalue('modlogmaxdays');
	$config_postboxnotice = config_getvalue('postboxnotice');
	?>
	<form action="?globopts" method="post">
	<label for="maxthumbwidth">Maxmimum thumbnail width:</label><input type="text" name="maxthumbwidth" value="<?php echo $config_maxthumbwidth; ?>" /><br />
	<label for="maxthumbheight">Maxmimum thumbnail height:</label><input type="text" name="maxthumbheight" value="<?php echo $config_maxthumbheight; ?>" /><br />
	<label for="imagesinnewwindow">Open images in new window:</label><select name="imagesinnewwindow"><?php echo ($config_imagesinnewwindow=='1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>'; ?></select><br />
	<label for="modlogmaxdays">Days to keep modlog entries:</label><input type="text" name="modlogmaxdays" value="<?php echo $config_modlogmaxdays; ?>" /><br />
	<label for="postboxnotice">Postbox Notice:</label><textarea name="postboxnotice" rows="8" cols="60"><?php echo $config_postboxnotice; ?></textarea><br />
	<input type="submit" value="Update">
	</form>
	<?php
} else if (isset($_GET['boardopts'])) {
	if ($_GET['updateboard']!="") {
		if (!moderator_ismodofboard($_GET['updateboard'],$_SESSION['manageusername'])) {
			die("You are not a moderator of this board.");
		}
		$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['updateboard'])."'",$dblink);
		$rows = mysql_num_rows($resultboard);
		if ($rows>0) {
			if ($_POST['order']>=0&&$_POST['maxpages']>0&&$_POST['maxage']>0&&$_POST['messagelength']>=0) {
				$filetypes = array();
				if ($_POST['filetype_gif']=='on') {
					$filetypes = array_merge($filetypes,array('GIF'));
				}
				if ($_POST['filetype_jpg']=='on') {
					$filetypes = array_merge($filetypes,array('JPG'));
				}
				if ($_POST['filetype_png']=='on') {
					$filetypes = array_merge($filetypes,array('PNG'));
				}
				$updateboard_locked = $_POST['locked']=="on" ? "1" : "0";
				mysql_query("UPDATE `boards` SET `order` = '".mysql_escape_string($_POST['order'])."' , `section` = '".mysql_escape_string($_POST['section'])."' , `desc` = '".mysql_escape_string($_POST['desc'])."' , `filetypes` = '".implode('|',$filetypes)."' , `locked` = '".$updateboard_locked."' , `maximagesize` = '".mysql_escape_string($_POST['maximagesize'])."' , `messagelength` = '".mysql_escape_string($_POST['messagelength'])."' , `maxpages` = '".mysql_escape_string($_POST['maxpages'])."' , `maxage` = '".mysql_escape_string($_POST['maxage'])."' , `maxreplies` = '".mysql_escape_string($_POST['maxreplies'])."' , `image` = '".mysql_escape_string($_POST['image'])."' , `includeheader` = '".mysql_escape_string($_POST['includeheader'])."' , `redirecttothread` = '".mysql_escape_string($_POST['redirecttothread'])."' , `forcedanon` = '".mysql_escape_string($_POST['forcedanon'])."' WHERE `name` = '".mysql_escape_string($_GET['updateboard'])."'",$dblink);
				echo 'Update successful.';
				management_addlogentry("Updated board configuration - /".$_GET['updateboard']."/");
			} else {
				echo 'Integer values must be entered correctly.';
			}
		} else {
			echo 'Unable to locate a board named <b>'.$_GET['updateboard'].'</b>.';
		}
	} else if ($_POST['board']!="") {
		if (!moderator_ismodofboard($_POST['board'],$_SESSION['manageusername'])) {
			die("You are not a moderator of this board.");
		}
		$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['board'])."'",$dblink);
		$rows = mysql_num_rows($resultboard);
		if ($rows>0) {
			while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
				?>
				<div class="container">
				<form action="manage.php?boardopts&updateboard=<?php echo $_POST['board']; ?>" method="post">
				<label for="board">Directory:</label><input type="text" name="board" value="<?php echo $_POST['board']; ?>" disabled /><div class="desc">The directory of the board.</div><br />
				<label for="desc">Description:</label><input type="text" name="desc" value="<?php echo $lineboard['desc']; ?>" /><div class="desc">The name of the board.</div><br />
				<label for="order">Order:</label><input type="text" name="order" value="<?php echo $lineboard['order']; ?>" /><div class="desc">Order to show board in menu's list, in ascending order.  Default: <b>0</b></div><br />
				<label for="section">Section:</label><input type="text" name="section" value="<?php echo $lineboard['section']; ?>" /><div class="desc">The section the board is in.  This is used for displaying the list of boards on the top and bottom of pages.</div><br />
				<label for="locked">Locked: (<img src="locked.gif" alt="Lock" />)</label><input type="checkbox" name="locked" <?php if ($lineboard['locked']=="1") { echo'checked '; } ?>/><div class="desc">Only moderators of the board and admins can make new posts/replies</div><br />
				<label>Allowed Image Types:</label><div class="desc">What filetypes users are allowed to use for images</div><br /><label for="filetype_gif">GIF</label><input type="checkbox" name="filetype_gif" <?php if (in_array('GIF',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br /><label for="filetype_jpg">JPG</label><input type="checkbox" name="filetype_jpg" <?php if (in_array('JPG',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br /><label for="filetype_png">PNG</label><input type="checkbox" name="filetype_png" <?php if (in_array('PNG',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br />
				<label for="maximagesize">Maximum Image Size:</label><input type="text" name="maximagesize" value="<?php echo $lineboard['maximagesize']; ?>" /><div class="desc">Maxmimum size of uploaded images, in <b>bytes</b>.  Default: <b>1024000</b></div><br />
				<label for="messagelength">Maximum Message Length:</label><input type="text" name="messagelength" value="<?php echo $lineboard['messagelength']; ?>" /><div class="desc">Default: <b>8192</b></div><br />
				<label for="maxpages">Maximum Board Pages:</label><input type="text" name="maxpages" value="<?php echo $lineboard['maxpages']; ?>" /><div class="desc">Default: <b>10</b></div><br />
				<label for="maxage">Maximum Thread Age: (Hours)</label><input type="text" name="maxage" value="<?php echo $lineboard['maxage']; ?>" /><div class="desc">Default: <b>96</b></div><br />
				<label for="maxreplies">Maximum Thread Replies:</label><input type="text" name="maxreplies" value="<?php echo $lineboard['maxreplies']; ?>" /><div class="desc">The number of replies a thread can have before autosaging to the back of the board.  Default: <b>200</b></div><br />
				<label for="image">Header Image:</label><input type="text" name="image" value="<?php echo $lineboard['image']; ?>" /><div class="desc">Overrides the header set in the config file.  Leave blank to use configured global header image.  Needs to be a full url including http://.  Set to <b>none</b> to show no header image.</div><br />
				<label for="includeheader">Include Header:</label><textarea name="includeheader" rows="12" cols="80"><?php echo $lineboard['includeheader']; ?></textarea><div class="desc">Raw HTML which will be inserted at the top of each page of the board.  Default: <b>blank</b></div><br />
				<label for="redirecttothread">Redirect to Thread:</label><select name="redirecttothread"><?php echo ($lineboard['redirecttothread']=='1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>'; ?></select><div class="desc">If set to yes, users will be redirected to the thread they replied to/posted after posting.  If set to no, users will be redirected to the first page of the board.  Default: <b>No</b></div><br />
				<label for="forcedanon">Forced Anonymous:</label><select name="forcedanon"><?php echo ($lineboard['forcedanon']=='1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>'; ?></select><div class="desc">If set to yes, users will not be allowed to enter a name, making everyone appear as <b>Anonymous</b>.  Default: <b>No</b></div><br />
				<input type="submit" name="submit" value="Update Board" />
				</form>
				</div>
				<?php
			}
		} else {
			echo 'Unable to locate a board named <b>'.$_POST['board'].'</b>.';
		}
	} else {
		?>
		<form action="manage.php?boardopts" method="post">
		<label for="board">Board Directory:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?>
		<input type="submit" value="Go">
		</form>
		<?php
	}
} else if ($_GET['action']=="deletepostsbyip") {
	if ($_POST['ip']!="") {
		$deletion_boards = array();
		$result = mysql_query("SELECT * FROM `boards`",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$deletion_boards = array_merge($deletion_boards,array($line['name']));
		}
		$deletion_changed_boards = array();
		$deletion_new_boards = array();
		while (list($postkey, $postvalue) = each($_POST)) {
			if (substr($postkey,0,10)=="deletefrom") {
				$deletion_changed_boards = array_merge($deletion_changed_boards,array(substr($postkey,10)));
			}
		}
		while (list(, $deletion_thisboard_name) = each($deletion_boards)) {
			if (in_array($deletion_thisboard_name,$deletion_changed_boards)) {
				$deletion_new_boards = array_merge($deletion_new_boards,array($deletion_thisboard_name));
			}
		}
		if ($deletion_new_boards==array()) {
			die("You did not select any boards!");
		}
		$delete_boards = implode('|',$deletion_new_boards);
		foreach (explode('|',$delete_boards) as $board) {
			if (!moderator_ismodofboard($board,$_SESSION['manageusername'])) {
				die("You can only delete posts from boards you moderate!");
			}
		}
		$i = 0;
		foreach ($deletion_new_boards as $deletion_board) {
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($deletion_board)."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
			}
			$result = mysql_query("UPDATE `posts` SET  `IS_DELETED` = '1' WHERE `is_deleted` = '0' AND `boardid` = '".$board_id."' AND `ip` = '".mysql_escape_string($_POST['ip'])."'",$dblink);
			if (!$result) {
				echo 'Error: '.mysql_error();
				die();
			}
			$i += mysql_affected_rows($dblink);
			regenerate_board($deletion_board);
		}
		echo 'All threads/posts by this IP ('.$i.') in selected boards successfully deleted!<br />';
		echo '<hr />';
		management_addlogentry("Deleted posts by IP ".$_POST['ip']."");
	}
	?>
	<form action="?action=deletepostsbyip" method="post">
	<label for="ip">IP:</label><input type="text" name="ip" value="<?php echo $_GET['ip']; ?>" /><br />
	Boards:&nbsp;&nbsp;<?php make_boardlist_checkbox('deletefrom',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<input type="submit" value="Delete posts" />
	</form>
	<?php
} else if (substr($_GET['action'],0,6)=="sticky"||substr($_GET['action'],0,8)=="unsticky") {
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
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				mysql_query("UPDATE `posts` SET `stickied` = '1' WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
				regenerate_board($_GET['board']);
				echo 'Thread has successfully been stickied!';
				management_addlogentry("Stickied thread #".mysql_escape_string($_GET['postid'])." - /".mysql_escape_string($_GET['board'])."/");
			} else {
				echo 'Sorry, a thread with that ID doesn\'t seem to exist.';
			}
		} else {
			echo 'Invalid board directory.';
		}
		echo '<hr />';
	} else if ($_GET['action']=="unstickypost"&&$_GET['postid']>0&&$_GET['board']!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
				die("You are not a moderator of this board.");
			}
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$sticky_board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				mysql_query("UPDATE `posts` SET `stickied` = '0' WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
				regenerate_board($_GET['board']);
				echo 'Thread has successfully been un-stickied!';
				management_addlogentry("Un-stickied thread #".mysql_escape_string($_GET['postid'])." - /".mysql_escape_string($_GET['board'])."/");
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
	<label for="board">Board Directory:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<label for="postid">Thread ID:</label><input type="text" name="postid"><input type="submit" value="Sticky!">
	</form>
	<br /><hr />
	<form action="manage.php" method="get"><input type="hidden" name="action" value="unstickypost">
	<label for="board">Board Directory:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<label for="postid">Thread ID:</label><input type="text" name="postid"><input type="submit" value="Un-Sticky!">
	</form>
	<?php
} else if (substr($_GET['action'],0,4)=="lock"||substr($_GET['action'],0,6)=="unlock") {
	if ($_GET['action']=="lockpost"&&$_GET['postid']>0&&$_GET['board']!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
				die("You are not a moderator of this board.");
			}
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$lock_board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				mysql_query("UPDATE `posts` SET `locked` = '1' WHERE `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
				regenerate_board($_GET['board']);
				echo 'Thread has successfully been locked!';
				management_addlogentry("Locked thread #".mysql_escape_string($_GET['postid'])." - /".mysql_escape_string($_GET['board'])."/");
			} else {
				echo 'Sorry, a thread with that ID doesn\'t seem to exist.';
			}
		} else {
			echo 'Invalid board directory.';
		}
		echo '<hr />';
	} else if ($_GET['action']=="unlockpost"&&$_GET['postid']>0&&$_GET['board']!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
				die("You are not a moderator of this board.");
			}
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$lock_board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				mysql_query("UPDATE `posts` SET `locked` = '0' WHERE `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
				regenerate_board($_GET['board']);
				echo 'Thread has successfully been un-locked!';
				management_addlogentry("Un-ocked thread #".$_GET['postid']." - /".$_GET['board']."/");
			} else {
				echo 'Sorry, a thread with that ID doesn\'t seem to exist.';
			}
		} else {
			echo 'Invalid board directory.';
		}
		echo '<hr />';
	}
	?>
	<form action="manage.php" method="get"><input type="hidden" name="action" value="lockpost">
	<label for="board">Board Directory:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<label for="postid">Thread ID:</label><input type="text" name="postid"><input type="submit" value="Lock!">
	</form>
	<br /><hr />
	<form action="manage.php" method="get"><input type="hidden" name="action" value="unlockpost">
	<label for="board">Board Directory:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<label for="postid">Thread ID:</label><input type="text" name="postid"><input type="submit" value="Un-lock!">
	</form>
	<?php
} else if ($_GET['action']=="cleanup") {
	management_adminsonly();
	delunusedimages(true);
	echo 'Cleanup finished!';
	management_addlogentry("Ran cleanup");
} else if ($_GET['action']=="bans") {
	if ($_POST['ip']!="") {
		$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".mysql_escape_string($_POST['ip'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows==0) {
			if ($_POST['seconds']>=0) {
				$banning_boards = array();
				$result = mysql_query("SELECT * FROM `boards`",$dblink);
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$banning_boards = array_merge($banning_boards,array($line['name']));
				}
				$banning_changed_boards = array();
				$banning_new_boards = array();
				while (list($postkey, $postvalue) = each($_POST)) {
					if (substr($postkey,0,10)=="bannedfrom") {
						$banning_changed_boards = array_merge($banning_changed_boards,array(substr($postkey,10)));
					}
				}
				while (list(, $banning_thisboard_name) = each($banning_boards)) {
					if (in_array($banning_thisboard_name,$banning_changed_boards)) {
						$banning_new_boards = array_merge($banning_new_boards,array($banning_thisboard_name));
					}
				}
				if ($banning_new_boards==array()&&$_POST['banfromall']!='on') {
					die("You did not select any boards!");
				}
				$ban_isglobalban = ($_POST['banfromall']=='on') ? '1' : '0';
				if ($ban_isglobalban=='0') {
					$ban_boards = implode('|',$banning_new_boards);
					foreach (explode('|',$ban_boards) as $board) {
						if (!moderator_ismodofboard($board,$_SESSION['manageusername'])) {
							die("You can only make board specific bans to boards which you moderate!");
						}
					}
				}
				$query = "INSERT INTO `banlist` ( `ip` , `globalban` , `boards` , `by` , `at` , `until` , `reason` ) VALUES ( '".mysql_escape_string($_POST['ip'])."' , '".$ban_isglobalban."' , '".$ban_boards."' , '".mysql_escape_string($_SESSION['manageusername'])."' , '".time()."' , '";
				if ($_POST['seconds']=='0') {
					$query .= '0';
				} else {
					$query .= mysql_escape_string(time()+$_POST['seconds']);
				}
				$query .= "' , '".mysql_escape_string($_POST['reason'])."' )";
				$result = mysql_query($query,$dblink);
				if ($result) {
					echo 'Ban successfully placed!';
				} else {
					echo 'Error: '.mysql_error($dblink);
					die();
				}
				$logentry = "Banned ".$_POST['ip']." until ";
				if ($_POST['seconds']=='0') {
					$logentry .= 'forever';
				} else {
					$logentry .= date("F j, Y, g:i a",time()+$_POST['seconds']);
				}
				$logentry .= " - Reason: ".$_POST['reason']." - Banned from: ";
				if ($ban_isglobalban=='1') {
					$logentry .= "all boards ";
				} else {
					$logentry .=  '/'.implode('/, /',explode('|',$ban_boards)).'/ ';
				}
				management_addlogentry($logentry);
				if ($_POST['quickbanboard']!=""&&$_POST['quickbanthreadid']!="") {
					echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/'.$_POST['quickbanboard'].'/';
					if ($_POST['quickbanthreadid']!="0") {
						echo 'res/'.$_POST['quickbanthreadid'].'.html';
					}
					echo '" /><a href="'.$chan_webpath.$chan_webfolder.'/'.$_POST['quickbanboard'].'/';
					if ($_POST['quickbanthreadid']!="0") {
						echo 'res/'.$_POST['quickbanthreadid'].'.html';
					}
					echo '">Redirecting</a>...';
				}
			} else {
				echo 'Please enter a positive amount of seconds, or zero for permaban.';
			}
		} else {
			echo 'That IP has already been banned!';
		}
		echo '<hr />';
	} else if ($_GET['delban']>0) {
		$result = mysql_query("SELECT * FROM `banlist` WHERE `id` = '".mysql_escape_string($_GET['delban'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$unban_ip = $line['ip'];
			}
			mysql_query("DELETE FROM `banlist` WHERE `id` = '".mysql_escape_string($_GET['delban'])."'",$dblink);
			echo 'Ban successfully removed!';
			management_addlogentry("Unbanned ".$unban_ip);
		} else {
			echo 'That is an invalid ban ID.';
		}
		echo '<hr />';
	}
	if ($_GET['banboard']!=""&&$_GET['banpost']!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['banboard'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$ban_board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$ban_board_id."' AND `id` = '".mysql_escape_string($_GET['banpost'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$ban_ip = $line['ip'];
					$ban_threadid = $line['threadid'];
				}
			} else {
				echo 'Error, couldn\'t find a post with that ID :(<hr />';
			}
		}
	}
	?>
	<form action="manage.php?action=bans" method="post" name="banform">
	<?php
	if ($ban_ip!="") {
		echo '<input type="hidden" name="quickbanboard" value="'.$_GET['banboard'].'" /><input type="hidden" name="quickbanthreadid" value="'.$ban_threadid.'" />';
	}
	?>
	<label for="ip">IP:</label><input type="text" name="ip" value="<?php echo $ban_ip; ?>" /><?php if ($ban_ip!="") { echo '&nbsp;&nbsp;<a href="?action=deletepostsbyip&ip='.$ban_ip.'" target="_blank">Delete all posts by this IP</a>'; } ?><br />
	Ban from:&nbsp;
	<label for="banfromall"><b>ALL BOARDS</b></label><input type="checkbox" name="banfromall" /><br />OR<br />
	<?php
	make_boardlist_checkbox('bannedfrom',moderator_boardlist($_SESSION['manageusername']));
	?>
	<br />
	<label for="seconds">Seconds:</label><input type="text" name="seconds" /><div class="desc">Presets:&nbsp;<a href="#" onclick="document.banform.seconds.value='3600';">1hr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='604800';">1w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='1209600';">2w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='2592000';">30d</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='31536000';">1yr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='0';">never</a></div><br />
	<label for="reason">Reason:</label><input type="text" name="reason" /><div class="desc">Presets:&nbsp;<a href="#" onclick="document.banform.reason.value='Child Pornography';">CP</a>&nbsp;<a href="#" onclick="document.banform.reason.value='Proxy';">Proxy</a></div><br />
	<input type="submit" value="Add Ban">
	</form>
	<hr /><br />
	<?php
	$result = mysql_query("SELECT * FROM `banlist`",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		echo '<table border="1"><tr><th>IP Address</th><th>Boards</th><th>Reason</th><th>Date Added</th><th>Expires</th><th>Added By</th><th>&nbsp;</th></tr>';
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo '<tr>';
			echo '<td>'.$line['ip'].'</td><td>';
			if ($line['globalban']=='1') {
				echo '<b>all boards</b>';
			} else {
				if ($line['boards']!='') {
					echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;';
				}
			}
			echo '</td><td>'.stripslashes($line['reason']).'</td><td>'.date("F j, Y, g:i a",$line['at']).'</td><td>';
			if ($line['until']=='0') {
				echo '<b>forever</b>';
			} else {
				echo date("F j, Y, g:i a",$line['until']);
			}
			echo '</td><td>'.$line['by'].'</td><td>[<a href="manage.php?action=bans&delban='.$line['id'].'">x</a>]</td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo 'There are currently no bans.';
	}
} else if ($_GET['action']=="delposts") {
	if ($_POST['boarddir']!=""||$_GET['boarddir']!="") {
		if ($_GET['boarddir']!="") {
			$isquickdel = true;
			$_POST['boarddir'] = $_GET['boarddir'];
			$_POST['delthreadid'] = $_GET['delthreadid'];
			$_POST['delpostid'] = $_GET['delpostid'];
		}
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['boarddir'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (!moderator_ismodofboard($_POST['boarddir'],$_SESSION['manageusername'])) {
				die("You are not a moderator of this board.");
			}
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
				$board_dir = $line['name'];
			}
			if ($_POST['delthreadid']>0) {
				$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_escape_string($_POST['delthreadid'])."' AND `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$delthread_id = $line['id'];
					}
					$numthreads_deleted = delete_post($delthread_id,$board_dir);
					/*
					$i = 0;
					$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".mysql_escape_string($delthread_id)."' AND `boardid` = '".$board_id."'",$dblink);
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$i++;
						if ($line['image']!="") {
							@unlink($chan_rootdir.'/'.$board_dir.'/src/'.$line['image'].'.'.$line['imagetype']);
							@unlink($chan_rootdir.'/'.$board_dir.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
						}
						mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$line['id']."' AND `threadid` = '".mysql_escape_string($delthread_id)."' AND `boardid` = '".$board_id."'",$dblink);
					}
					unlink($chan_rootdir.'/'.$_POST['boarddir'].'/res/'.$delthread_id.'.html');
					mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$delthread_id."' AND `boardid` = '".$board_id."'",$dblink);*/
					regenerate_board($_POST['boarddir'],true);
					echo 'Thread successfully deleted!';
					management_addlogentry("Deleted thread #".$delthread_id." (".$numthreads_deleted." replies) - /".$board_dir."/");
					if ($_GET['postid']!="") {
						echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'" /><a href="'.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'">Redirecting</a> to ban page...';
					} else if ($isquickdel) {
						echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/'.$_GET['boarddir'].'/" /><a href="'.$chan_webpath.$chan_webfolder.'/'.$_GET['boarddir'].'/">Redirecting</a> back to board...';
					}
				} else {
					echo 'A thread with that ID doesn\'t exist!';
				}
			} else if ($_POST['delpostid']>0) {
				$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_escape_string($_POST['delpostid'])."' AND `boardid` = '".$board_id."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$delpost_id = $line['id'];
						$delpost_threadid = $line['threadid'];
					}
					delete_post($delpost_id,$board_dir);
					/*
					$i = 0;
					$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".mysql_escape_string($delthread_id)."' AND `boardid` = '".$board_id."'",$dblink);
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$i++;
						if ($line['image']!="") {
							@unlink($chan_rootdir.'/'.$board_dir.'/src/'.$line['image'].'.'.$line['imagetype']);
							@unlink($chan_rootdir.'/'.$board_dir.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
						}
						mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$line['id']."' AND `threadid` = '".mysql_escape_string($delthread_id)."' AND `boardid` = '".$board_id."'",$dblink);
					}
					unlink($chan_rootdir.'/'.$_POST['boarddir'].'/res/'.$delthread_id.'.html');
					mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$delthread_id."' AND `boardid` = '".$board_id."'",$dblink);*/
					regenerate_thread($_POST['boarddir'],$delpost_threadid);
					regenerate_board($_POST['boarddir'],true);
					echo 'Post successfully deleted!';
					management_addlogentry("Deleted post #".$delpost_id." - /".$board_dir."/");
					if ($_GET['postid']!="") {
						echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'" /><a href="'.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'">Redirecting</a> to ban page...';
					} else if ($isquickdel) {
						echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/'.$_GET['boarddir'].'/res/'.$delpost_threadid.'.html" /><a href="'.$chan_webpath.$chan_webfolder.'/'.$_GET['boarddir'].'/res/'.$delpost_threadid.'.html">Redirecting</a> back to thread...';
					}
				} else {
					echo 'A thread with that ID doesn\'t exist!';
				}
				
				/*
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						if ($line['threadid']!="0") {
							if ($line['image']!="") {
								unlink($chan_rootdir.'/'.$_POST['boarddir'].'/src/'.$line['image'].'.'.$line['imagetype']);
								unlink($chan_rootdir.'/'.$_POST['boarddir'].'/thumb/'.$line['image'].'s.'.$line['imagetype']);
							}
							mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$line['id']."' AND `boardid` = '".$board_id."'",$dblink);
							regenerate_thread($_POST['boarddir'],$line['threadid']);
							regenerate_board($_POST['boarddir']);
							echo 'Post successfully deleted!';
							management_addlogentry("Deleted post #".$_POST['delpostid']." - /".$_POST['boarddir']."/");
							if ($_GET['postid']!="") {
								echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'" /><a href="'.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'">Redirecting</a> to ban page...';
							} else if ($isquickdel) {
								echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/'.$_GET['boarddir'].'/res/'.$line['threadid'].'.html" /><a href="'.$chan_webpath.$chan_webfolder.'/'.$_GET['boarddir'].'/res/'.$line['threadid'].'.html">Redirecting</a> back to thread...';
							}
						} else {
							echo 'That post appears to be a thread.  Pleas use the delete thread feature instead.';
						}
					}
				} else {
					echo 'A post with that ID doesn\'t exist!';
				}*/
			}
		} else {
			echo 'Invalid board directory.';
		}
		echo '<hr />';
	}	
	?>
	<form action="manage.php?action=delposts" method="post">
	<label for="boarddir">Board dir:</label><?php echo make_boardlist_dropdown('boarddir',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<label for="delthreadid">Thread ID:</label><input type="text" name="delthreadid" /><br />
	<input type="submit" value="Delete Thread">
	</form>
	<br /><hr />
	<form action="manage.php?action=delposts" method="post">
	<label for="boarddir">Board dir:</label><?php echo make_boardlist_dropdown('boarddir',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<label for="delpostid">Post ID:</label><input type="text" name="delpostid" /><br />
	<input type="submit" value="Delete Post">
	</form>
	<?php
} else if ($_GET['action']=="wordfilter") {
	management_adminsonly();
	if (isset($_POST['word'])) {
		if ($_POST['word']!=""&&$_POST['replacedby']!="") {
			$result = mysql_query("SELECT * FROM `wordfilter` WHERE `word` = '".mysql_escape_string($_POST['word'])."'",$dblink);
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
				mysql_query("INSERT INTO `wordfilter` ( `word` , `replacedby` , `boards` , `time` ) VALUES ( '".mysql_escape_string($_POST['word'])."' , '".mysql_escape_string($_POST['replacedby'])."' , '".mysql_escape_string(implode('|',$wordfilter_new_boards))."' , '".time()."' )",$dblink);
				echo 'Word successfully added to the wordfilter!';
				management_addlogentry("Added word to wordfilter: ".$_POST['word']." - Changes to: ".$_POST['replacedby']." - Boards: /".implode('/, /',explode('|',implode('|',$wordfilter_new_boards)))."/");
			} else {
				echo 'That word already exists in the wordfilter!';
			}
		} else {
			echo 'You must enter both fields!';
		}
		echo '<hr />';
	} else if ($_GET['delword']>0) {
		$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['delword'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$del_word = $line['word'];
			}
			mysql_query("DELETE FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['delword'])."'",$dblink);
			echo 'Word successfully removed from the wordfilter!';
			management_addlogentry("Removed word from wordfilter: ".$del_word);
		} else {
			echo 'That ID doesn\'t seem to exist.';
		}
		echo '<hr />';
	} else if ($_GET['editword']>0) {
		$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['editword'])."'",$dblink);
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
						echo '/><br />';
					}
					?><br />
					<input type="submit" value="Edit Word">
					</form>
					<?php
				}
			} else {
				$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['editword'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$wordfilter_word = $line['word'];
					}
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
					mysql_query("UPDATE `wordfilter` SET `replacedby` = '".mysql_escape_string($_POST['replacedby'])."' , `boards` = '".mysql_escape_string(implode('|',$wordfilter_new_boards))."' WHERE `id` = '".mysql_escape_string($_GET['editword'])."'",$dblink);
					echo 'Word successfully updated.';
					management_addlogentry("Updated word on wordfilter: ".$wordfilter_word);
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
		$array_boards = array();
		$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
		while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
			$array_boards = array_merge($array_boards,array($lineboard['name']));
		}
		make_boardlist_checkbox('wordfilter',$array_boards);
		?><br />
		<input type="submit" value="Add Word to Filter">
		</form>
		<hr />
		<?php
	}
	echo '<br />';
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
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['directory'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows==0) {
				if (@mkdir($chan_rootdir."/".$_POST['directory'], 0777)&&@mkdir($chan_rootdir."/".$_POST['directory']."/res", 0777)&&@mkdir($chan_rootdir."/".$_POST['directory']."/src", 0777)&&@mkdir($chan_rootdir."/".$_POST['directory']."/thumb", 0777)) {
					file_put_contents($chan_rootdir."/".$_POST['directory']."/.htaccess","DirectoryIndex board.html");
					mysql_query("INSERT INTO `boards` ( `name` , `desc` , `createdon` ) VALUES ( '".mysql_escape_string($_POST['directory'])."' , '".mysql_escape_string($_POST['desc'])."' , '".time()."' )",$dblink);
					regenerate_board($_POST['directory']);
					echo 'Board successfully added!  Visit it <a href="'.$chan_webpath.$chan_webfolder.'/'.$_POST['directory'].'/">here</a>!';
					management_addlogentry("Added board: /".$_POST['directory']."/");
				} else {
					echo '<br />Error creating board: couldn\'t make directories.';
				}
			} else {
				echo 'A board with that name already exists!';
			}
		} else {
			echo 'Both fields must be filled in!';
		}
	}
	?>
	<form action="manage.php?action=addboard" method="post">
	<label for="directory">Directory:</label><input type="text" name="directory" /><div class="desc">The directory of the board.  <b>Only put in the letter(s) of the board directory, no slashes!</b></div><br />
	<label for="desc">Description:</label><input type="text" name="desc" /><div class="desc">The name of the board.</div><br />
	<input type="submit" value="Add Board">
	</form>
	<?php
} else if ($_GET['action']=="delboard") {
	management_adminsonly();
	if (isset($_POST['directory'])) {
		if ($_POST['directory']!="") {
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['directory'])."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
				$board_dir = $line['name'];
			}
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				if ($_POST['confirmation']=='yes') {
					if (remove_board($board_dir)) {
						mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `boardid` = '".$board_id."'",$dblink);
						mysql_query("DELETE FROM `boards` WHERE `id` = '".$board_id."'",$dblink);
						echo 'Board successfully deleted!';
						management_addlogentry("Deleted board: /".$_POST['directory']."/");
					} else {
						//Error
						echo 'Unable to delete board.';
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
	<label for="directory">Directory:</label><?php echo make_boardlist_dropdown('directory',moderator_boardlist($_SESSION['manageusername'])); ?><br />
	<input type="submit" value="Delete Board">
	</form>
	<?php
} else if ($_GET['action']=="changepwd") {
	if ($_POST['oldpwd']!="") {
		if ($_POST['newpwd']==$_POST['newpwd2']) {
			$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$staff_passwordenc = $line['password'];
			}
			if (md5($_POST['oldpwd'])==$staff_passwordenc) {
				mysql_query("UPDATE `staff` SET `password` = '".md5($_POST['newpwd'])."' WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."'",$dblink);
				$_SESSION['managepassword'] = md5($_POST['newpwd']);
				echo 'Password successfully changed!';
			} else {
				echo 'Sorry, the old password you provided does not match your current password.';
			}
		} else {
			echo 'Your new password confirmation did not match the first.';
		}
		echo '<hr />';
	}
	?>
	<form action="manage.php?action=changepwd" method="post">
	<label for="oldpwd">Old Password:</label><input type="password" name="oldpwd" /><br />
	<label for="newpwd">New Password:</label><input type="password" name="newpwd" /><br />
	<label for="newpwd2">New Password Again:</label><input type="password" name="newpwd2" /><br />
	<input type="submit" value="Change Password">
	</form>
	<?php
} else if ($_GET['action']=="staff") {
	management_adminsonly();
	if ($_POST['staffusername']!=""&&$_POST['staffpassword']!="") {
		$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_POST['staffusername'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows==0) {
			if ($_POST['isadmin']=="on") {
				mysql_query("INSERT INTO `staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".mysql_escape_string($_POST['staffusername'])."' , '".md5($_POST['staffpassword'])."' , '1' , '".time()."' )",$dblink);
			} else {
				mysql_query("INSERT INTO `staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".mysql_escape_string($_POST['staffusername'])."' , '".md5($_POST['staffpassword'])."' , '0' , '".time()."' )",$dblink);
			}
			echo 'Staff member succesfully added!';
			$logentry = "Added staff member - ";
			if ($_POST['isadmin']=="on") {
				$logentry .= "administrator";
			} else {
				$logentry .= "moderator";
			}
			$logentry .= ": ".$_POST['staffusername'];
			management_addlogentry($logentry);
		} else {
			echo 'A staff member with that username already exists!';
		}
		echo '<hr />';
	} else if ($_GET['del']>0) {
		$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".mysql_escape_string($_GET['del'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$staff_username = $line['username'];
			}
			mysql_query("DELETE FROM `staff` WHERE `id` = '".mysql_escape_string($_GET['del'])."'",$dblink);
			echo 'Staff member successfully deleted.';
			management_addlogentry("Deleted staff member: ".$staff_username);
		} else {
			echo 'That is an invalid staff id.';
		}
		echo '<hr />';
	} else if ($_GET['edit']>0) {
		$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".mysql_escape_string($_GET['edit'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (isset($_POST['submitting'])) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					$staff_username = $line['username'];
					$staff_isadmin = $line['isadmin'];
				}
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
				mysql_query("UPDATE `staff` SET `boards` = '".mysql_escape_string(implode('|',$staff_new_boards))."' WHERE `id` = '".mysql_escape_string($_GET['edit'])."'",$dblink);
				echo 'Staff successfully updated!<hr />';
				$logentry = "Updated staff member - ";
				if ($_POST['isadmin']=='on') {
					$logentry .= "administrator";
				} else {
					$logentry .= "moderator";
				}
				$logentry .= ": ".$staff_username;
				if ($_POST['isadmin']!='on') {
					$logentry .= " - Moderates: /".implode('/, /',$staff_new_boards)."/";
				}
				management_addlogentry($logentry);
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
			<br />
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
	<hr /><br />
	Admins:<br />
	<?php
	$result = mysql_query("SELECT * FROM `staff` WHERE `isadmin` = '1' ORDER BY `username` ASC",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		echo '<table border="1"><tr><th>Username</th><th>Added on</th><th>&nbsp;</th></tr>';
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo '<tr>';
			echo '<td>'.$line['username'].'</td><td>'.date("y/m/d(D)H:i",$line['addedon']).'</td><td>[<a href="?action=staff&edit='.$line['id'].'">edit</a>]&nbsp;[<a href="?action=staff&del='.$line['id'].'">x</a>]</td>';
			echo '</tr>';
		}
		echo '</table>';
	} else {
		echo 'None!';
	}
	echo 'Mods:<br />';
	$result = mysql_query("SELECT * FROM `staff` WHERE `isadmin` = '0' ORDER BY `username` ASC",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		echo '<table border="1"><tr><th>Username</th><th>Added on</th><th>Moderates</th><th>&nbsp;</th></tr>';
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			echo '<tr>';
			echo '<td>'.$line['username'].'</td><td>'.date("y/m/d(D)H:i",$line['addedon']).'</td><td>';
			if ($line['boards']!="") {
				echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>';
			} else {
				echo 'no boards';
			}
			echo '</td><td>[<a href="?action=staff&edit='.$line['id'].'">edit</a>]&nbsp;[<a href="?action=staff&del='.$line['id'].'">x</a>]</td>';
			echo '</tr>';
		}
		echo '</table>';
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
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_escape_string($_GET['quickban'])."' AND `boardid` = '".$board_id."'",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$poster_ip = $line['ip'];
				$post_threadid = $line['threadid'];
				$post_message = $line['message'];
			}
			echo '<meta http-equiv="refresh" content="1;url='.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banip='.$poster_ip.'" /><a href="'.$chan_webpath.$chan_webfolder.'/manage.php?action=bans&banip='.$poster_ip.'">Redirecting...</a>';
		} else {
			echo 'Invalid board.';
		}
	} else {
		echo 'You are not a moderator of that board!';
	}
} else if ($_GET['quickdelban']>0&&$_GET['board']!="") {
	if (moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
			}
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_escape_string($_GET['quickdelban'])."' AND `boardid` = '".$board_id."'",$dblink);
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
}

echo '<br /><br />';

require("inc/footer.php");
echo chan_footer(true);

?>