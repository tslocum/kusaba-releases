<?php

session_set_cookie_params(3600); 
session_start();

require("config.php");
require_once($chan_rootdir."/inc/functions.php");

if (isset($_GET['action'])) {
	if ($_GET['action']=="logout") { //Do this first to not get any header errors
		if (management_isadmin()) {
			$result = mysql_query("SELECT `name` FROM `boards`",$dblink);
			while ($line = mysql_fetch_assoc($result)) {
				setcookie("tcmod","",1,$chan_boardsfolder."/".$line['name']."/",$chan_boardspath);
			}
		} else {
			$result = mysql_query("SELECT `name` FROM `boards`",$dblink);
			while ($line = mysql_fetch_assoc($result)) {
				setcookie("tcmod","",1,$chan_boardsfolder."/".$line['name']."/",$chan_boardspath);
			}
		}
		session_destroy();
		unset($_SESSION['manageusername']);
		unset($_SESSION['managepassword']);
		echo $lang['successfully logged out'].'<meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/manage.php" />';
		die();
	}
}

if (isset($_POST['username'])&&isset($_POST['password'])) {
	mysql_query("DELETE FROM `loginattempts` WHERE `timestamp` < '".(time()-1200)."'",$dblink);
	$result = mysql_query("SELECT `ip` FROM `loginattempts` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."' LIMIT 6",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>5) {
		die($lang['locked out']);
	} else {
		$result = mysql_query("SELECT `username` FROM `staff` WHERE `username` = '".mysql_escape_string($_POST['username'])."' AND `password` = '".md5($_POST['password'])."' LIMIT 1",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			mysql_query("DELETE FROM `loginattempts` WHERE `ip` < '".$_SERVER['REMOTE_ADDR']."'",$dblink);
			$_SESSION['manageusername'] = $_POST['username'];
			$_SESSION['managepassword'] = md5($_POST['password']);
			management_addlogentry($lang['logged in'],1);
		} else {
			mysql_query("INSERT INTO `loginattempts` ( `username` , `ip` , `timestamp` ) VALUES ( '".mysql_escape_string($_POST['username'])."' , '".$_SERVER['REMOTE_ADDR']."' , '".time()."' )",$dblink);
			die($lang['incorrect username/password']);
		}
	}
}
if (isset($_SESSION['manageusername'])&&isset($_SESSION['managepassword'])) {
	$result = mysql_query("SELECT `username` FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."' AND `password` = '".mysql_escape_string($_SESSION['managepassword'])."' LIMIT 1",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows==0) {
		session_destroy();
		die($lang['invalid session']."<br /><br /><a href=\"manage.php\">".$lang['log in again']."</a>");
	} else {
		$result = mysql_query("SELECT `boards` FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."' LIMIT 1",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			if (management_isadmin()) {
				$resultboard = mysql_query("SELECT `name` FROM `boards`",$dblink);
				while ($lineboard = mysql_fetch_assoc($resultboard)) {
					setcookie("tcmod","yes",time()+3600,$chan_boardsfolder."/".$lineboard['name']."/");
				}
			} else {
				while ($line = mysql_fetch_assoc($result)) {
					$array_boards = explode('|',$line['boards']);
				}
				foreach ($array_boards as $this_board_name) {
					setcookie("tcmod","yes",time()+3600,$chan_boardsfolder."/".$this_board_name."/");
				}
			}
		} else {
			session_destroy();
			die($lang['invalid session']."<br /><br /><a href=\"manage.php\">".$lang['log in again']."</a>");
		}
	}
} else {
	echo '<script type="text/javascript">function sf(){document.managelogin.username.focus();}</script><body onload="sf();"></body><div style="text-align: center;"><img src="hardgay.gif" alt="Pikachu" title="PIKA PIKA! CHUUUUU~~~" /><br /><form action="manage.php" method="post" name="managelogin"><input type="text" name="username"><br /><input type="password" name="password"><br /><input type="submit" value="Submit"></form></div>';
	die();
}
$result = mysql_query("SELECT `boards` FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."' LIMIT 1",$dblink);
$rows = mysql_num_rows($result);
if ($rows>0) {
	if (management_isadmin()) {
		$resultboard = mysql_query("SELECT `name` FROM `boards`",$dblink);
		while ($lineboard = mysql_fetch_assoc($resultboard)) {
			setcookie("tcmod","yes",time()+3600,$chan_boardsfolder."/".$lineboard['name']."/",$chan_boardspath);
		}
	} else {
		while ($line = mysql_fetch_assoc($result)) {
			$array_boards = explode('|',$line['boards']);
		}
		foreach ($array_boards as $this_board_name) {
			setcookie("tcmod","yes",time()+3600,$chan_boardsfolder."/".$this_board_name."/",$chan_boardspath);
		}
	}
}
removed_expired_bans();

echo '<title>'.$lang['manage boards'].'</title>
<link rel="stylesheet" type="text/css" href="'.$chan_boardspath.'/css/manage.css" title="Manage" />
<div style="text-align: center;"><h1>Manage Boards</h1></div>';
if (is_file($chan_rootdir."/inc/modheader.html")) {
	include($chan_rootdir."/inc/modheader.html");
}
echo '<br />';
echo $lang['welcome'].', '.$_SESSION['manageusername'].' [<a href="?action=logout">'.$lang['log out'].'</a>]<br />';
if ($_SESSION['manageusername']=="admin"&&$_SESSION['managepassword']==md5("admin")) {
	echo '<font color="red"><b>'.$lang['default account notice'].'</b></font><br />';
}
echo $lang['staff rights'].': <b>';
if (management_isadmin()) {
	echo $lang['administrator'];
} else {
	echo $lang['moderator'];
}
echo '</b>';
if (!management_isadmin()) {
	echo '<br />'.$lang['moderating boards'].': ';
	$i = 0;
	$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
	while ($lineboard = mysql_fetch_assoc($resultboard)) {
		$i++;
		if (moderator_ismodofboard($lineboard['name'],$_SESSION['manageusername'])) {
			echo '<b>/'.$lineboard['name'].'/</b>, ';
		}
	}
	if ($i==0) {
		echo $lang['no boards'];
	} else {
		echo $i.' '.$lang['boards'];
	}
}
echo '<hr />';
echo '<div style="text-align: left;">';

if (management_isadmin()) {
	echo $lang['administration'].': <a href="?action=addnews">'.$lang['add news'].'</a> - <a href="?action=globopts">'.$lang['global options'].'</a> - <a href="?action=addboard">'.$lang['add board'].'</a> - <a href="?action=delboard">'.$lang['delete board'].'</a> - <a href="?action=wordfilter">'.$lang['wordfilter'].'</a> - <a href="?action=viewdeletedthread">'.$lang['view deleted thread'].'</a> - <a href="?action=rebuildall">'.$lang['rebuild all boards and html files'].'</a> - <a href="?action=cleanup">'.$lang['cleanup'].'</a> - <a href="?action=search">'.$lang['search posts'].'</a> - <a href="?action=staff">'.$lang['staff'].'</a> - <a href="?action=modlog">'.$lang['modlog'].'</a> - <a href="?action=sql">'.$lang['sql query'].'</a><br />';
}
echo $lang['boards'].': <a href="?action=boardopts">'.$lang['board options'].'</a> - <a href="?action=stickypost">'.$lang['manage stickies'].'</a> - <a href="?action=lockpost">'.$lang['manage locked threads'].'</a> - <a href="?action=delposts">'.$lang['delete thread/post'].'</a><br />';
echo $lang['moderation'].': <a href="?action=bans">'.$lang['view/add/remove bans'].'</a> - <a href="?action=deletepostsbyip">'.$lang['delete all posts by ip'].'</a> - <a href="?action=getpwd">'.$lang['get posting password'].'</a> - <a href="?action=changepwd">'.$lang['change account password'].'</a><br />';
echo $lang['misc'].': <a href="?">'.$lang['index'].'</a>';

echo '</div>';
echo '<hr />';

if (isset($_GET['action'])) {
	if ($_GET['action']=="rebuildall") {
		management_adminsonly();
		$time_start = time();
		regenerate_all_boards(true);
		printf($lang['rebuild complete'],time()-$time_start);
		management_addlogentry($lang['rebuilt all boards and threads'],2);
	} else if ($_GET['action']=="modlog") {
		management_adminsonly();
		mysql_query("DELETE FROM `modlog` WHERE `timestamp` < '".(time()-config_getvalue('modlogmaxdays')*86400)."'",$dblink);
		echo '<h2>ModLog</h2><table cellspacing="2" cellpadding="1" border="1"><tr><th>Time</th><th>User</th><th width="100%">Action</th></tr>';
		$result = mysql_query("SELECT * FROM `modlog` ORDER BY `timestamp` DESC",$dblink);
		while ($line = mysql_fetch_assoc($result)) {
			echo "<tr><td>".date("y/m/d(D)H:i",$line['timestamp'])."</td><td>".$line['user']."</td><td>".$line['entry']."</td></tr>";
		}
		echo '</table>';
	} else if ($_GET['action']=="sql") {
		management_adminsonly();
		echo '<h2>'.$lang['sql query'].'</h2>';
		if (isset($_POST['query'])) {
			echo '<hr />';
			$result = mysql_query($_POST['query'],$dblink);
			if ($result) {
				echo $lang['query executed successfully'];
			} else {
				echo 'Error: '.mysql_error($dblink);
			}
			echo '<hr />';
		}
		echo '<form method="post" action="?action=sql"><textarea name="query" rows="20" cols="60"></textarea><br /><input type="submit" value="'.$lang['inject'].'" /></form>';
	} else if ($_GET['action']=="addnews") {
		management_adminsonly();
		echo $lang['add news header'].'<br /><br />';
		if (isset($_POST['news'])&&isset($_POST['subject'])&&isset($_POST['email'])) {
			if ($_POST['news']!="") {
				echo '<hr />';
				if ($_POST['subject']!="") {
					mysql_query("INSERT INTO `news` ( `subject` , `message` , `postedat` , `postedby` , `postedemail` ) VALUES ( '".mysql_escape_string($_POST['subject'])."' , '".mysql_escape_string($_POST['news'])."' , '".time()."' , '".mysql_escape_string($_SESSION['manageusername'])."' , '".mysql_escape_string($_POST['email'])."' )",$dblink);
					echo '<h3>'.$lang['news add successful'].'</h3>';
					management_addlogentry($lang['added a news entry'],9);
				} else {
					echo $lang['enter a subject'];
				}
				echo '<hr />';
			}
		}
		echo '<form method="post" action="?action=addnews"><label for="subject">'.$lang['subject'].':</label><input type="text" name="subject" value="" /><div class="desc">'.$lang['can not be left blank'].'</div><br />
		<textarea name="news" rows="25" cols="80"></textarea><br /><label for="email">'.$lang['e-mail'].':</label><input type="text" name="email" value="" /><div class="desc">'.$lang['can be left blank'].'</div><br /><input type="submit" value="'.$lang['add'].'" /></form>';
	} else if ($_GET['action']=="globopts") {
		management_adminsonly();
		if (isset($_POST['imagesinnewwindow'])) {
			if (!$_POST['maxthumbwidth']>0||!$_POST['maxthumbheight']>0||$_POST['numrepliesdisplayed']<0||$_POST['numrepliesdisplayedsticky']<0||$_POST['numthreadsdisplayed']<=0) {
				die($lang['integer incorrect']);
			}
			if ($_POST['imagesinnewwindow']=='1') {
				config_setvalue('imagesinnewwindow','1');
			} else {
				config_setvalue('imagesinnewwindow','0');
			}
			config_setvalue('maxthumbwidth',$_POST['maxthumbwidth']);
			config_setvalue('maxthumbheight',$_POST['maxthumbheight']);
			config_setvalue('modlogmaxdays',$_POST['modlogmaxdays']);
			config_setvalue('numrepliesdisplayed',$_POST['numrepliesdisplayed']);
			config_setvalue('numrepliesdisplayedsticky',$_POST['numrepliesdisplayedsticky']);
			config_setvalue('numthreadsdisplayed',$_POST['numthreadsdisplayed']);
			config_setvalue('postboxnotice',$_POST['postboxnotice']);
			echo $lang['global configuration successfully updated'];
			management_addlogentry($lang['updated global configuration'],10);
			echo '<hr>';
		}
		$config_maxthumbwidth = config_getvalue('maxthumbwidth');
		$config_maxthumbheight = config_getvalue('maxthumbheight');
		$config_imagesinnewwindow = config_getvalue('imagesinnewwindow');
		$config_modlogmaxdays = config_getvalue('modlogmaxdays');
		$config_numrepliesdisplayed = config_getvalue('numrepliesdisplayed');
		$config_numrepliesdisplayedsticky = config_getvalue('numrepliesdisplayedsticky');
		$config_numthreadsdisplayed = config_getvalue('numthreadsdisplayed');
		$config_postboxnotice = config_getvalue('postboxnotice');
		?>
		<form action="?action=globopts" method="post">
		<label for="maxthumbwidth"><?php echo $lang['maxmimum thumbnail width']; ?>:</label><input type="text" name="maxthumbwidth" value="<?php echo $config_maxthumbwidth; ?>" /><br />
		<label for="maxthumbheight"><?php echo $lang['maxmimum thumbnail height']; ?>:</label><input type="text" name="maxthumbheight" value="<?php echo $config_maxthumbheight; ?>" /><br />
		<label for="imagesinnewwindow"><?php echo $lang['open images in new window']; ?>:</label><select name="imagesinnewwindow"><?php echo ($config_imagesinnewwindow=='1') ? '<option value="1">'.$lang['yes'].'</option><option value="0">'.$lang['no'].'</option>' : '<option value="0">'.$lang['no'].'</option><option value="1">'.$lang['yes'].'</option>'; ?></select><br />
		<label for="modlogmaxdays"><?php echo $lang['days to keep modlog entries']; ?>:</label><input type="text" name="modlogmaxdays" value="<?php echo $config_modlogmaxdays; ?>" /><br />
		<label for="numrepliesdisplayed"><?php echo $lang['replies displayed per thread']; ?>:</label><input type="text" name="numrepliesdisplayed" value="<?php echo $config_numrepliesdisplayed; ?>" /><br />
		<label for="numrepliesdisplayedsticky"><?php echo $lang['replies displayed per thread sticky']; ?>:</label><input type="text" name="numrepliesdisplayedsticky" value="<?php echo $config_numrepliesdisplayedsticky; ?>" /><br />
		<label for="numthreadsdisplayed"><?php echo $lang['threads displayed per thread']; ?>:</label><input type="text" name="numthreadsdisplayed" value="<?php echo $config_numthreadsdisplayed; ?>" /><br />
		<label for="postboxnotice"><?php echo $lang['postbox notice']; ?>:</label><textarea name="postboxnotice" rows="8" cols="60"><?php echo $config_postboxnotice; ?></textarea><br />
		<input type="submit" value="<?php echo $lang['update']; ?>">
		</form>
		<?php
	} else if ($_GET['action']=="boardopts") {
		if (isset($_GET['updateboard'])&&isset($_POST['order'])&&isset($_POST['maxpages'])&&isset($_POST['maxage'])&&isset($_POST['messagelength'])) {
			if (!moderator_ismodofboard($_GET['updateboard'],$_SESSION['manageusername'])) {
				die($lang['not a moderator']);
			}
			$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['updateboard'])."'",$dblink);
			$rows = mysql_num_rows($resultboard);
			if ($rows>0) {
				if ($_POST['order']>=0&&$_POST['maxpages']>0&&$_POST['maxage']>0&&$_POST['messagelength']>=0) {
					$filetypes = array();
					if (isset($_POST['filetype_gif'])) {
						$filetypes = array_merge($filetypes,array('GIF'));
					}
					if (isset($_POST['filetype_jpg'])) {
						$filetypes = array_merge($filetypes,array('JPG'));
					}
					if (isset($_POST['filetype_png'])) {
						$filetypes = array_merge($filetypes,array('PNG'));
					}
					if (isset($_POST['filetype_swf'])) {
						$filetypes = array_merge($filetypes,array('SWF'));
					}
					if (isset($_POST['locked'])) {
						$updateboard_locked = $_POST['locked']=="on" ? "1" : "0";
					} else {
						$updateboard_locked = "0";
					}
					mysql_query("UPDATE `boards` SET `order` = '".mysql_escape_string($_POST['order'])."' , `section` = '".mysql_escape_string($_POST['section'])."' , `desc` = '".mysql_escape_string($_POST['desc'])."' , `filetypes` = '".implode('|',$filetypes)."' , `locked` = '".$updateboard_locked."' , `maximagesize` = '".mysql_escape_string($_POST['maximagesize'])."' , `messagelength` = '".mysql_escape_string($_POST['messagelength'])."' , `maxpages` = '".mysql_escape_string($_POST['maxpages'])."' , `maxage` = '".mysql_escape_string($_POST['maxage'])."' , `maxreplies` = '".mysql_escape_string($_POST['maxreplies'])."' , `image` = '".mysql_escape_string($_POST['image'])."' , `includeheader` = '".mysql_escape_string($_POST['includeheader'])."' , `redirecttothread` = '".mysql_escape_string($_POST['redirecttothread'])."' , `forcedanon` = '".mysql_escape_string($_POST['forcedanon'])."' WHERE `name` = '".mysql_escape_string($_GET['updateboard'])."'",$dblink);
					echo $lang['update successful'] ;
					management_addlogentry($lang['updated board configuration']." - /".$_GET['updateboard']."/",4);
				} else {
					echo $lang['integer incorrect'];
				}
			} else {
				echo $lang['unable to locate board'].' <b>'.$_GET['updateboard'].'</b>.';
			}
		} else if (isset($_POST['board'])) {
			if (!moderator_ismodofboard($_POST['board'],$_SESSION['manageusername'])) {
				die($lang['not a moderator']);
			}
			$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['board'])."'",$dblink);
			$rows = mysql_num_rows($resultboard);
			if ($rows>0) {
				while ($lineboard = mysql_fetch_assoc($resultboard)) {
					?>
					<div class="container">
					<form action="?action=boardopts&updateboard=<?php echo $_POST['board']; ?>" method="post">
					<label for="board"><?php echo $lang['directory']; ?>:</label><input type="text" name="board" value="<?php echo $_POST['board']; ?>" disabled /><div class="desc"><?php echo $lang['directory desc']; ?></div><br />
					<label for="desc"><?php echo $lang['description']; ?>:</label><input type="text" name="desc" value="<?php echo $lineboard['desc']; ?>" /><div class="desc"><?php echo $lang['desc desc']; ?></div><br />
					<label for="order"><?php echo $lang['order']; ?>:</label><input type="text" name="order" value="<?php echo $lineboard['order']; ?>" /><div class="desc"><?php echo $lang['order desc']; ?> <b>0</b></div><br />
					<label for="section"><?php echo $lang['section']; ?>:</label><input type="text" name="section" value="<?php echo $lineboard['section']; ?>" /><div class="desc"><?php echo $lang['section desc']; ?></div><br />
					<label for="locked"><?php echo $lang['locked']; ?>: (<img src="<?php echo $chan_boardspath; ?>/locked.gif" alt="Lock" />)</label><input type="checkbox" name="locked" <?php if ($lineboard['locked']=="1") { echo'checked '; } ?>/><div class="desc"><?php echo $lang['locked desc']; ?></div><br />
					<label><?php echo $lang['allowed image types']; ?>:</label><div class="desc"><?php echo $lang['imagetypes desc']; ?></div><br /><label for="filetype_gif">GIF</label><input type="checkbox" name="filetype_gif" <?php if (in_array('GIF',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br /><label for="filetype_jpg">JPG</label><input type="checkbox" name="filetype_jpg" <?php if (in_array('JPG',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br /><label for="filetype_png">PNG</label><input type="checkbox" name="filetype_png" <?php if (in_array('PNG',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br /><label for="filetype_swf">SWF</label><input type="checkbox" name="filetype_swf" <?php if (in_array('SWF',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br />
					<label for="maximagesize"><?php echo $lang['maximum image size']; ?>:</label><input type="text" name="maximagesize" value="<?php echo $lineboard['maximagesize']; ?>" /><div class="desc"><?php echo $lang['maximagesize desc'].' '.$lang['default']; ?>: <b>1024000</b></div><br />
					<label for="messagelength"><?php echo $lang['maximum message length']; ?>:</label><input type="text" name="messagelength" value="<?php echo $lineboard['messagelength']; ?>" /><div class="desc"><?php echo $lang['default'];?>: <b>8192</b></div><br />
					<label for="maxpages"><?php echo $lang['maximum board pages']; ?>:</label><input type="text" name="maxpages" value="<?php echo $lineboard['maxpages']; ?>" /><div class="desc"><?php echo $lang['default']; ?>: <b>10</b></div><br />
					<label for="maxage"><?php echo $lang['maximum thread age hours']; ?>:</label><input type="text" name="maxage" value="<?php echo $lineboard['maxage']; ?>" /><div class="desc"><?php echo $lang['default']; ?>: <b>96</b></div><br />
					<label for="maxreplies"><?php echo $lang['maximum thread replies']; ?>:</label><input type="text" name="maxreplies" value="<?php echo $lineboard['maxreplies']; ?>" /><div class="desc"><?php echo $lang['maxreplies desc'].' '.$lang['default'];?>: <b>200</b></div><br />
					<label for="image"><?php echo $lang['header image']; ?>:</label><input type="text" name="image" value="<?php echo $lineboard['image']; ?>" /><div class="desc"><?php echo $lang['image desc']; ?></div><br />
					<label for="includeheader"><?php echo $lang['include header']; ?>:</label><textarea name="includeheader" rows="12" cols="80"><?php echo $lineboard['includeheader']; ?></textarea><div class="desc"><?php echo $lang['includeheader desc']; ?></div><br />
					<label for="redirecttothread"><?php echo $lang['redirect to thread']; ?>:</label><select name="redirecttothread"><?php echo ($lineboard['redirecttothread']=='1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>'; ?></select><div class="desc"><?php echo $lang['redirecttothread desc'].' '.$lang['default']; ?>: <b><?php echo $lang['no']; ?></b></div><br />
					<label for="forcedanon"><?php echo $lang['forced anonymous']; ?>:</label><select name="forcedanon"><?php echo ($lineboard['forcedanon']=='1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>'; ?></select><div class="desc"><?php echo $lang['forcedanon desc' ].' '.$lang['default']; ?>: <b><?php echo $lang['no']; ?></b></div><br />
					<input type="submit" name="submit" value="<?php echo $lang['update']; ?>" />
					</form>
					</div>
					<?php
				}
			} else {
				echo $lang['unable to locate board'].' <b>'.$_POST['board'].'</b>.';
			}
		} else {
			?>
			<form action="?action=boardopts" method="post">
			<label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?>
			<input type="submit" value="<?php echo $lang['go']; ?>">
			</form>
			<?php
		}
	} else if ($_GET['action']=="deletepostsbyip") {
		if (isset($_POST['ip'])) {
			if ($_POST['ip']!="") {
				$deletion_boards = array();
				$result = mysql_query("SELECT * FROM `boards`",$dblink);
				while ($line = mysql_fetch_assoc($result)) {
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
					die($lang['please select a board']);
				}
				$delete_boards = implode('|',$deletion_new_boards);
				foreach (explode('|',$delete_boards) as $board) {
					if (!moderator_ismodofboard($board,$_SESSION['manageusername'])) {
						die($lang['you can only delete posts from boards you moderate']);
					}
				}
				$i = 0;
				foreach ($deletion_new_boards as $deletion_board) {
					$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($deletion_board)."'",$dblink);
					while ($line = mysql_fetch_assoc($result)) {
						$board_id = $line['id'];
					}
					$result = mysql_query("UPDATE `posts` SET  `IS_DELETED` = '1' WHERE `is_deleted` = '0' AND `boardid` = '".$board_id."' AND `ip` = '".mysql_escape_string($_POST['ip'])."'",$dblink);
					if (!$result) {
						echo $lang['generic error'];
						echo mysql_error();
						die();
					}
					$i += mysql_affected_rows($dblink);
					regenerate_board($deletion_board);
				}
				echo $lang['all posts by ip deleted'].'<br />';
				echo '<hr />';
				management_addlogentry($lang['deleted posts by ip'].' '.$_POST['ip'],7);
			}
		}
		?>
		<form action="?action=deletepostsbyip" method="post">
		<label for="ip"><?php echo $lang['ip']; ?>:</label><input type="text" name="ip" value="<?php if (isset($_GET['ip'])) { echo $_GET['ip']; } ?>" /><br />
		<?php echo $lang['boards']; ?>:&nbsp;&nbsp;<?php make_boardlist_checkbox('deletefrom',moderator_boardlist($_SESSION['manageusername'])); ?><br />
		<input type="submit" value="<?php echo $lang['delete posts']; ?>" />
		</form>
		<?php
	} else if (substr($_GET['action'],0,6)=="sticky"||substr($_GET['action'],0,8)=="unsticky") {
		if ($_GET['action']=="stickypost"&&isset($_GET['postid'])&&isset($_GET['board'])) {
			if ($_GET['postid']>0&&$_GET['board']!="") {
				$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$_GET['board']."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
						die($lang['you are not a moderator of this board']);
					}
					while ($line = mysql_fetch_assoc($result)) {
						$sticky_board_id = $line['id'];
					}
					$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
					$rows = mysql_num_rows($result);
					if ($rows>0) {
						mysql_query("UPDATE `posts` SET `stickied` = '1' WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
						regenerate_board($_GET['board']);
						echo $lang['thread successfully stickied'];
						management_addlogentry($lang['stickied thread'].' #'.mysql_escape_string($_GET['postid']).' - /'.mysql_escape_string($_GET['board']).'/',5);
					} else {
						echo $lang['invalid thread id'];
					}
				} else {
					echo $lang['invalid board directory'];
				}
				echo '<hr />';
			}
		} else if ($_GET['action']=="unstickypost"&&isset($_GET['postid'])&&isset($_GET['board'])) {
			if ($_GET['postid']>0&&$_GET['board']!="") {
				$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
						die($lang['you are not a moderator of this board']);
					}
					while ($line = mysql_fetch_assoc($result)) {
						$sticky_board_id = $line['id'];
					}
					$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
					$rows = mysql_num_rows($result);
					if ($rows>0) {
						mysql_query("UPDATE `posts` SET `stickied` = '0' WHERE `boardid` = '".$sticky_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
						regenerate_board($_GET['board']);
						echo $lang['thread successfully unstickied'];
						management_addlogentry($lang['unstickied thread'].' #'.mysql_escape_string($_GET['postid']).' - /'.mysql_escape_string($_GET['board']).'/',5);
					} else {
						echo $lang['invalid thread id'];
					}
				} else {
					echo $lang['invalid board directory'];
				}
				echo '<hr />';
			}
		}
		?>
		<form action="manage.php" method="get"><input type="hidden" name="action" value="stickypost">
		<label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
		<label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['sticky']; ?>">
		</form>
		<br /><hr />
		<form action="manage.php" method="get"><input type="hidden" name="action" value="unstickypost">
		<label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
		<label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['unsticky']; ?>">
		</form>
		<?php
	} else if (substr($_GET['action'],0,4)=="lock"||substr($_GET['action'],0,6)=="unlock") {
		if ($_GET['action']=="lockpost"&&isset($_GET['postid'])&&isset($_GET['board'])) {
			if ($_GET['postid']>0&&$_GET['board']!="") {
				$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
						die($lang['you are not a moderator of this board']);
					}
					while ($line = mysql_fetch_assoc($result)) {
						$lock_board_id = $line['id'];
					}
					$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
					$rows = mysql_num_rows($result);
					if ($rows>0) {
						mysql_query("UPDATE `posts` SET `locked` = '1' WHERE `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
						regenerate_board($_GET['board']);
						echo $lang['thread successfully locked'];
						management_addlogentry($lang['locked thread'].' #'.mysql_escape_string($_GET['postid']).' - /'.mysql_escape_string($_GET['board']).'/',5);
					} else {
						echo $lang['invalid thread id'];
					}
				} else {
					echo $lang['invalid board directory'];
				}
				echo '<hr />';
			}
		} else if ($_GET['action']=="unlockpost"&&$_GET['postid']>0&&$_GET['board']!="") {
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
					die($lang['you are not a moderator of this board']);
				}
				while ($line = mysql_fetch_assoc($result)) {
					$lock_board_id = $line['id'];
				}
				$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					mysql_query("UPDATE `posts` SET `locked` = '0' WHERE `boardid` = '".$lock_board_id."' AND `threadid` = '0' AND `id` = '".mysql_escape_string($_GET['postid'])."'",$dblink);
					regenerate_board($_GET['board']);
					echo $lang['thread successfully unlocked'];
					management_addlogentry($lang['unlocked thread'].' #'.$_GET['postid'].' - /'.$_GET['board'].'/',5);
				} else {
					echo $lang['invalid thread id'];
				}
			} else {
				echo $lang['invalid board directory'];
			}
			echo '<hr />';
		}
		?>
		<form action="manage.php" method="get"><input type="hidden" name="action" value="lockpost">
		<label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
		<label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['lock']; ?>">
		</form>
		<br /><hr />
		<form action="manage.php" method="get"><input type="hidden" name="action" value="unlockpost">
		<label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
		<label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['unlock']; ?>">
		</form>
		<?php
	} else if ($_GET['action']=="cleanup") {
		management_adminsonly();
		delunusedimages(true);
		echo $lang['cleanup finished'];
		management_addlogentry($lang['ran cleanup'],2);
	} else if ($_GET['action']=="bans") {
		$ban_ip = "";
		if (isset($_POST['ip'])&&isset($_POST['seconds'])) {
			if ($_POST['ip']!="") {
				$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".mysql_escape_string($_POST['ip'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows==0) {
					if ($_POST['seconds']>=0) {
						$banning_boards = array();
						$result = mysql_query("SELECT * FROM `boards`",$dblink);
						while ($line = mysql_fetch_assoc($result)) {
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
							die($lang['please select a board']);
						}
						$ban_isglobalban = ($_POST['banfromall']=='on') ? '1' : '0';
						if ($ban_isglobalban=='0') {
							$ban_boards = implode('|',$banning_new_boards);
							foreach (explode('|',$ban_boards) as $board) {
								if (!moderator_ismodofboard($board,$_SESSION['manageusername'])) {
									die($lang['you can only ban for boards you moderate']);
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
							echo $lang['ban successfully placed'];
						} else {
							echo $lang['generic error'];
							echo mysql_error($dblink);
							die();
						}
						$logentry = $lang['banned'].' '.$_POST['ip'].' until ';
						if ($_POST['seconds']=='0') {
							$logentry .= $lang['forever'];
						} else {
							$logentry .= date('F j, Y, g:i a',time()+$_POST['seconds']);
						}
						$logentry .= ' - '.$lang['reason'].': '.$_POST['reason'].' - '.$lang['banned from'].': ';
						if ($ban_isglobalban=='1') {
							$logentry .= $lang['all boards'].' ';
						} else {
							$logentry .=  '/'.implode('/, /',explode('|',$ban_boards)).'/ ';
						}
						management_addlogentry($logentry,8);
						if ($_POST['quickbanboard']!=""&&$_POST['quickbanthreadid']!="") {
							echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$_POST['quickbanboard'].'/';
							if ($_POST['quickbanthreadid']!="0") {
								echo 'res/'.$_POST['quickbanthreadid'].'.html';
							}
							echo '" /><a href="'.$chan_boardspath.'/'.$_POST['quickbanboard'].'/';
							if ($_POST['quickbanthreadid']!="0") {
								echo 'res/'.$_POST['quickbanthreadid'].'.html';
							}
							echo '">'.$lang['redirecting'].'</a>...';
						}
					} else {
						echo $lang['invalid ban length'];
					}
				} else {
					echo $lang['that ip has already been banned'];
				}
				echo '<hr />';
			}
		} else if (isset($_GET['delban'])) {
			if ($_GET['delban']>0) {
				$result = mysql_query("SELECT * FROM `banlist` WHERE `id` = '".mysql_escape_string($_GET['delban'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_assoc($result)) {
						$unban_ip = $line['ip'];
					}
					mysql_query("DELETE FROM `banlist` WHERE `id` = '".mysql_escape_string($_GET['delban'])."'",$dblink);
					echo $lang['ban successfully removed'];
					management_addlogentry($lang['unbanned'].' '.$unban_ip,8);
				} else {
					echo $lang['invalid ban id'];
				}
				echo '<hr />';
			}
		}
		if (isset($_GET['banboard'])&&isset($_GET['banpost'])) {
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['banboard'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_assoc($result)) {
					$ban_board_id = $line['id'];
				}
				$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$ban_board_id."' AND `id` = '".mysql_escape_string($_GET['banpost'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_assoc($result)) {
						$ban_ip = $line['ip'];
						$ban_threadid = $line['threadid'];
					}
				} else {
					echo $lang['a post with that id does not exist'].'<hr />';
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
		<label for="ip"><?php echo $lang['ip']; ?>:</label><input type="text" name="ip" value="<?php echo $ban_ip; ?>" /><?php if ($ban_ip!="") { echo '&nbsp;&nbsp;<a href="?action=deletepostsbyip&ip='.$ban_ip.'" target="_blank">'.$lang['delete all posts by this ip'].'</a>'; } ?><br />
		<?php echo $lang['ban from']; ?>:&nbsp;
		<label for="banfromall"><b><?php echo $lang['all boards']; ?></b></label><input type="checkbox" name="banfromall" /><br />OR<br />
		<?php
		make_boardlist_checkbox('bannedfrom',moderator_boardlist($_SESSION['manageusername']));
		?>
		<br />
		<label for="seconds"><?php echo $lang['seconds']; ?>:</label><input type="text" name="seconds" /><div class="desc"><?php echo $lang['presets']; ?>:&nbsp;<a href="#" onclick="document.banform.seconds.value='3600';">1hr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='604800';">1w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='1209600';">2w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='2592000';">30d</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='31536000';">1yr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='0';">never</a></div><br />
		<label for="reason"><?php echo $lang['reason']; ?>:</label><input type="text" name="reason" /><div class="desc"><?php echo $lang['presets']; ?>:&nbsp;<a href="#" onclick="document.banform.reason.value='Child Pornography';">CP</a>&nbsp;<a href="#" onclick="document.banform.reason.value='Proxy';">Proxy</a></div><br />
		<input type="submit" value="<?php echo $lang['add ban']; ?>">
		</form>
		<hr /><br />
		<?php
		$result = mysql_query("SELECT * FROM `banlist` ORDER BY `id` DESC",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			echo '<table border="1"><tr><th>IP Address</th><th>Boards</th><th>Reason</th><th>Date Added</th><th>Expires</th><th>Added By</th><th>&nbsp;</th></tr>';
			while ($line = mysql_fetch_assoc($result)) {
				echo '<tr>';
				echo '<td>'.$line['ip'].'</td><td>';
				if ($line['globalban']=='1') {
					echo '<b>'.$lang['all boards'].'</b>';
				} else {
					if ($line['boards']!='') {
						echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;';
					}
				}
				echo '</td><td>'.stripslashes($line['reason']).'</td><td>'.date("F j, Y, g:i a",$line['at']).'</td><td>';
				if ($line['until']=='0') {
					echo '<b>'.$lang['forever'].'</b>';
				} else {
					echo date("F j, Y, g:i a",$line['until']);
				}
				echo '</td><td>'.$line['by'].'</td><td>[<a href="manage.php?action=bans&delban='.$line['id'].'">x</a>]</td>';
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo $lang['there are currently no bans'];
		}
	} else if ($_GET['action']=="delposts") {
		if (isset($_POST['boarddir'])||isset($_GET['boarddir'])) {
			if (isset($_GET['boarddir'])) {
				$isquickdel = true;
				$_POST['boarddir'] = $_GET['boarddir'];
				if (isset($_GET['delthreadid'])) {
					$_POST['delthreadid'] = $_GET['delthreadid'];
				}
				if (isset($_GET['delpostid'])) {
					$_POST['delpostid'] = $_GET['delpostid'];
				}
			}
			$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['boarddir'])."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				if (!moderator_ismodofboard($_POST['boarddir'],$_SESSION['manageusername'])) {
					die($lang['you are not a moderator of this board']);
				}
				while ($line = mysql_fetch_assoc($result)) {
					$board_id = $line['id'];
					$board_dir = $line['name'];
				}
				if ($_POST['delthreadid']>0) {
					$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_escape_string($_POST['delthreadid'])."' AND `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
					$rows = mysql_num_rows($result);
					if ($rows>0) {
						while ($line = mysql_fetch_assoc($result)) {
							$delthread_id = $line['id'];
						}
						$numposts_deleted = delete_post($delthread_id,$board_dir);
						regenerate_board($_POST['boarddir'],true);
						echo $lang['thread successfully deleted'];
						management_addlogentry($lang['deleted thread'].' #<a href="?action=viewdeletedthread&threadid='.$delthread_id.'&board='.$_POST['boarddir'].'">'.$delthread_id.'</a> ('.$numposts_deleted.' replies) - /'.$board_dir.'/',7);
						if ($_GET['postid']!="") {
							echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'" /><a href="'.$chan_boardspath.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'">'.$lang['redirecting'].'</a> to ban page...';
						} else if ($isquickdel) {
							echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$_GET['boarddir'].'/" /><a href="'.$chan_boardspath.'/'.$_GET['boarddir'].'/">'.$lang['redirecting'].'</a> back to board...';
						}
					} else {
						echo $lang['invalid thread id'];
					}
				} else if ($_POST['delpostid']>0) {
					$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_escape_string($_POST['delpostid'])."' AND `boardid` = '".$board_id."'",$dblink);
					$rows = mysql_num_rows($result);
					if ($rows>0) {
						while ($line = mysql_fetch_assoc($result)) {
							$delpost_id = $line['id'];
							$delpost_threadid = $line['threadid'];
						}
						delete_post($delpost_id,$board_dir);
						regenerate_thread($_POST['boarddir'],$delpost_threadid);
						regenerate_board($_POST['boarddir'],true);
						echo $lang['post successfully deleted'];
						management_addlogentry($lang['deleted post'].' #<a href="?action=viewdeletedthread&threadid='.$delpost_threadid.'&board='.$_POST['boarddir'].'#'.$delpost_id.'">'.$delpost_id.'</a> - /'.$board_dir.'/',7);
						if ($_GET['postid']!="") {
							echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'" /><a href="'.$chan_boardspath.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'">'.$lang['redirecting'].'</a> to ban page...';
						} else if ($isquickdel) {
							echo '<br /><br /><meta http-equiv="refresh" content="1;url='.$chan_boardspath.'/'.$_GET['boarddir'].'/res/'.$delpost_threadid.'.html" /><a href="'.$chan_boardspath.'/'.$_GET['boarddir'].'/res/'.$delpost_threadid.'.html">'.$lang['redirecting'].'</a> back to thread...';
						}
					} else {
						echo $lang['invalid thread id'];
					}
				}
			} else {
				echo $lang['invalid board directory'];
			}
			echo '<hr />';
		}	
		?>
		<form action="manage.php?action=delposts" method="post">
		<label for="boarddir"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('boarddir',moderator_boardlist($_SESSION['manageusername'])); ?><br />
		<label for="delthreadid"><?php echo $lang['thread']; ?>:</label><input type="text" name="delthreadid" /><br />
		<input type="submit" value="<?php echo $lang['delete thread']; ?>">
		</form>
		<br /><hr />
		<form action="manage.php?action=delposts" method="post">
		<label for="boarddir"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('boarddir',moderator_boardlist($_SESSION['manageusername'])); ?><br />
		<label for="delpostid"><?php echo $lang['post']; ?>:</label><input type="text" name="delpostid" /><br />
		<input type="submit" value="<?php echo $lang['delete post']; ?>">
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
					while ($line = mysql_fetch_assoc($result)) {
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
					echo $lang['word successfully added'];
					management_addlogentry("Added word to wordfilter: ".$_POST['word']." - Changes to: ".$_POST['replacedby']." - Boards: /".implode('/, /',explode('|',implode('|',$wordfilter_new_boards)))."/",11);
				} else {
					echo $land['that word already exists'];
				}
			} else {
				echo $lang['please fill in all required fields'];
			}
			echo '<hr />';
		} else if (isset($_GET['delword'])) {
			if ($_GET['delword']>0) {
				$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['delword'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_assoc($result)) {
						$del_word = $line['word'];
					}
					mysql_query("DELETE FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['delword'])."'",$dblink);
					echo $lang['word successfully removed'];
					management_addlogentry($lang['removed word from wordfilter'].': '.$del_word,11);
				} else {
					echo $lang['that id does not exist'];
				}
				echo '<hr />';
			}
		} else if (isset($_GET['delword'])) {
			if ($_GET['editword']>0) {
				$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['editword'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					if (!isset($_POST['replacedby'])) {
						while ($line = mysql_fetch_assoc($result)) {
							?>
							<form action="manage.php?action=wordfilter&editword=<?php echo $_GET['editword']; ?>" method="post">
							<label for="word"><?php echo $lang['word']; ?>:</label><input type="text" name="word" value="<?php echo $line['word']; ?>" disabled /><br />
							<label for="replacedby"><?php echo $lang['is replaced by']; ?>:</label><input type="text" name="replacedby" value="<?php echo $line['replacedby']; ?>" /><br />
							<label><?php echo $lang['boards']; ?>:</label><br />
							<?php
							$array_boards = array();
							$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
							while ($lineboard = mysql_fetch_assoc($resultboard)) {
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
							<input type="submit" value="<?php echo $lang['edit word']; ?>">
							</form>
							<?php
						}
					} else {
						$result = mysql_query("SELECT * FROM `wordfilter` WHERE `id` = '".mysql_escape_string($_GET['editword'])."'",$dblink);
						$rows = mysql_num_rows($result);
						if ($rows>0) {
							while ($line = mysql_fetch_assoc($result)) {
								$wordfilter_word = $line['word'];
							}
							$wordfilter_boards = array();
							$result = mysql_query("SELECT * FROM `boards`",$dblink);
							while ($line = mysql_fetch_assoc($result)) {
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
							echo $lang['word successfully updated'];
							management_addlogentry($lang['updated word on wordfilter'].': '.$wordfilter_word,11);
						} else {
							echo $lang['unable to locate that word'];
						}
					}
				} else {
					echo $lang['that id does not exist'];
				}
				echo '<hr />';
			}
		} else {
			?>
			<form action="manage.php?action=wordfilter" method="post">
			<label for="word"><?php echo $lang['word']; ?>:</label><input type="text" name="word" /><br />
			<label for="replacedby"><?php echo $lang['is replaced by']; ?>:</label><input type="text" name="replacedby" /><br />
			<label><?php echo $lang['boards']; ?>:</label><br />
			<?php
			$array_boards = array();
			$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
			while ($lineboard = mysql_fetch_assoc($resultboard)) {
				$array_boards = array_merge($array_boards,array($lineboard['name']));
			}
			make_boardlist_checkbox('wordfilter',$array_boards);
			?><br />
			<input type="submit" value="<?php echo $lang['add word']; ?>">
			</form>
			<hr />
			<?php
		}
		echo '<br />';
		$result = mysql_query("SELECT * FROM `wordfilter`",$dblink);
		while ($line = mysql_fetch_assoc($result)) {
			echo 'Word: '.$line['word'].' - Replaced by: '.$line['replacedby'].' - Boards: ';
			if (explode('|',$line['boards'])!="") {
				echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;';
			} else {
				echo $lang['no boards'];
			}
			echo '[<a href="manage.php?action=wordfilter&editword='.$line['id'].'">'.$lang['edit'].'</a>]&nbsp;[<a href="manage.php?action=wordfilter&delword='.$line['id'].'">del</a>]<br />';
		}
	} else if ($_GET['action']=="addboard") {
		management_adminsonly();
		if (isset($_POST['directory'])) {
			if ($_POST['directory']!=""&&$_POST['desc']!="") {
				$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_POST['directory'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows==0) {
					if (@mkdir($chan_boardsdir."/".$_POST['directory'], 0777)&&@mkdir($chan_boardsdir."/".$_POST['directory']."/res", 0777)&&@mkdir($chan_boardsdir."/".$_POST['directory']."/src", 0777)&&@mkdir($chan_boardsdir."/".$_POST['directory']."/thumb", 0777)) {
						file_put_contents($chan_boardsdir."/".$_POST['directory']."/.htaccess","DirectoryIndex board.html");
						mysql_query("INSERT INTO `boards` ( `name` , `desc` , `createdon` ) VALUES ( '".mysql_escape_string($_POST['directory'])."' , '".mysql_escape_string($_POST['desc'])."' , '".time()."' )",$dblink);
						regenerate_board($_POST['directory']);
						echo $lang['board successfully added'].'<br /><br /><a href="'.$chan_boardspath.'/'.$_POST['directory'].'/">/'.$_POST['directory'].'/</a>!';
						management_addlogentry($lang['added board'].': /'.$_POST['directory'].'/',3);
					} else {
						echo '<br />'.$lang['unable to create directories'];
					}
				} else {
					echo $lang['a board with that name already exists'];
				}
			} else {
				echo $lang['please fill in all required fields'];
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
				while ($line = mysql_fetch_assoc($result)) {
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
							management_addlogentry("Deleted board: /".$_POST['directory']."/",3);
						} else {
							//Error
							echo 'Unable to delete board.';
						}
					} else {
						echo 'Are you absolutely sure you want to delete /'.$board_dir.'/ ?<br />
						<form action="manage.php?action=delboard" method="post"><input type="hidden" name="directory" value="'.$_POST['directory'].'"><input type="hidden" name="confirmation" value="yes"><input type="submit" value="Continue"></form>';
					}
				} else {
					echo $lang['a board with that name does not exist'];
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
		if (isset($_POST['oldpwd'])&&isset($_POST['newpwd'])&&isset($_POST['newpwd2'])) {
			if ($_POST['oldpwd']!=""&&$_POST['newpwd']!=""&&$_POST['newpwd2']!="") {
				if ($_POST['newpwd']==$_POST['newpwd2']) {
					$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."'",$dblink);
					while ($line = mysql_fetch_assoc($result)) {
						$staff_passwordenc = $line['password'];
					}
					if (md5($_POST['oldpwd'])==$staff_passwordenc) {
						mysql_query("UPDATE `staff` SET `password` = '".md5($_POST['newpwd'])."' WHERE `username` = '".mysql_escape_string($_SESSION['manageusername'])."'",$dblink);
						$_SESSION['managepassword'] = md5($_POST['newpwd']);
						echo $lang['password successfully changed'];
					} else {
						echo $lang['old password did not match current'];
					}
				} else {
					echo $lang['the second password did not match the first'];
				}
			} else {
				echo $lang['please fill in all required fields'];
			}
			echo '<hr />';
		}
		?>
		<form action="manage.php?action=changepwd" method="post">
		<label for="oldpwd"><?php echo $lang['old password']; ?>:</label><input type="password" name="oldpwd" /><br />
		<label for="newpwd"><?php echo $lang['new password']; ?>:</label><input type="password" name="newpwd" /><br />
		<label for="newpwd2"><?php echo $lang['new password again']; ?>:</label><input type="password" name="newpwd2" /><br />
		<input type="submit" value="<?php echo $lang['change account password']; ?>">
		</form>
		<?php
	} else if ($_GET['action']=="staff") {
		management_adminsonly();
		if (isset($_POST['staffusername'])&&isset($_POST['staffpassword'])) {
			if ($_POST['staffusername']!=""&&$_POST['staffpassword']!="") {
				$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".mysql_escape_string($_POST['staffusername'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows==0) {
					if ($_POST['isadmin']=="on") {
						mysql_query("INSERT INTO `staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".mysql_escape_string($_POST['staffusername'])."' , '".md5($_POST['staffpassword'])."' , '1' , '".time()."' )",$dblink);
					} else {
						mysql_query("INSERT INTO `staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".mysql_escape_string($_POST['staffusername'])."' , '".md5($_POST['staffpassword'])."' , '0' , '".time()."' )",$dblink);
					}
					echo $lang['staff member successfully added'];
					$logentry = $lang['added staff member'].' - ';
					if ($_POST['isadmin']=="on") {
						$logentry .= $lang['administrator'];
					} else {
						$logentry .= $lang['moderator'];
					}
					$logentry .= ": ".$_POST['staffusername'];
					management_addlogentry($logentry,6);
				} else {
					echo $lang['a staff member with that username already exists'];
				}
				echo '<hr />';
			}
		} else if (isset($_GET['del'])) {
			if ($_GET['del']>0) {
				$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".mysql_escape_string($_GET['del'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					while ($line = mysql_fetch_assoc($result)) {
						$staff_username = $line['username'];
					}
					mysql_query("DELETE FROM `staff` WHERE `id` = '".mysql_escape_string($_GET['del'])."'",$dblink);
					echo $lang['staff successfully deleted'];
					management_addlogentry($lang['deleted staff member'].': '.$staff_username,6);
				} else {
					echo $lang['invalid staff id'];
				}
				echo '<hr />';
			}
		} else if (isset($_GET['edit'])) {
			if ($_GET['edit']>0) {
				$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".mysql_escape_string($_GET['edit'])."'",$dblink);
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					if (isset($_POST['submitting'])) {
						while ($line = mysql_fetch_assoc($result)) {
							$staff_username = $line['username'];
							$staff_isadmin = $line['isadmin'];
						}
						$staff_boards = array();
						$result = mysql_query("SELECT * FROM `boards`",$dblink);
						while ($line = mysql_fetch_assoc($result)) {
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
						echo $lang['staff successfully updated'].'<hr />';
						$logentry = $lang['updated staff member'].' - ';
						if ($_POST['isadmin']=='on') {
							$logentry .=$lang['administrator'];
						} else {
							$logentry .= $lang['moderator'];
						}
						$logentry .= ': '.$staff_username;
						if ($_POST['isadmin']!='on') {
							$logentry .= ' - '.$lang['moderates'].': /'.implode('/, /',$staff_new_boards).'/';
						}
						management_addlogentry($logentry,6);
					}
					$result = mysql_query("SELECT * FROM `staff` WHERE `id` = '".$_GET['edit']."'",$dblink);
					while ($line = mysql_fetch_assoc($result)) {
						$staff_username = $line['username'];
						$staff_isadmin = $line['isadmin'];
						$staff_boards = explode('|',$line['boards']);
					}
					?>
					<form action="manage.php?action=staff&edit=<?php echo $_GET['edit']; ?>" method="post">
					<label for="staffname"><?php echo $lang['username']; ?>:</label><input type="text" name="staffname" value="<?php echo $staff_username; ?>" disabled /><br />
					<label for="isadmin"><?php echo $lang['administrator']; ?>?</label><input type="checkbox" name="isadmin" <?php if ($staff_isadmin=="1") { echo 'checked '; } ?>/><br /><br />
					<?php
					echo $lang['moderates'].'<br />';
					$result = mysql_query("SELECT * FROM `boards`",$dblink);
					while ($line = mysql_fetch_assoc($result)) {
						echo '<label for="moderate'.$line['name'].'">'.$line['name'].'</label><input type="checkbox" name="moderate'.$line['name'].'" ';
						if (in_array($line['name'],$staff_boards)) {
							echo 'checked ';
						}
						echo '/><br />';
					}
					?>
					<input type="submit" value="<?php echo $lang['modify staff member']; ?>" name="submitting" />
					</form>
					<br />
					<?php
				} else {
					echo $lang['a staff member with that id does not appear to exist'];
				}
				echo '<hr />';
			}
		}
		?>
		<form action="manage.php?action=staff" method="post">
		<label for="username"><?php echo $lang['username']; ?>:</label><input type="text" name="staffusername" /><br />
		<label for="password"><?php echo $lang['password']; ?>:</label><input type="text" name="staffpassword" /><br />
		<label for="isadmin"><?php echo $lang['administrator']; ?>?</label><input type="checkbox" name="isadmin" /><br />
		<input type="submit" value="<?php echo $lang['add staff member']; ?>">
		</form>
		<hr /><br />
		<?php
		echo $lang['administrators'].':<br />';
		$result = mysql_query("SELECT * FROM `staff` WHERE `isadmin` = '1' ORDER BY `username` ASC",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			echo '<table border="1"><tr><th>Username</th><th>Added on</th><th>&nbsp;</th></tr>';
			while ($line = mysql_fetch_assoc($result)) {
				echo '<tr>';
				echo '<td>'.$line['username'].'</td><td>'.date("y/m/d(D)H:i",$line['addedon']).'</td><td>[<a href="?action=staff&edit='.$line['id'].'">'.$lang['edit'].'</a>]&nbsp;[<a href="?action=staff&del='.$line['id'].'">x</a>]</td>';
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo $lang['none'];
		}
		echo $lang['moderators'].':<br />';
		$result = mysql_query("SELECT * FROM `staff` WHERE `isadmin` = '0' ORDER BY `username` ASC",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			echo '<table border="1"><tr><th>'.$lang['username'].'</th><th>'.$lang['added on'].'</th><th>'.$lang['moderating boards'].'</th><th>&nbsp;</th></tr>';
			while ($line = mysql_fetch_assoc($result)) {
				echo '<tr>';
				echo '<td>'.$line['username'].'</td><td>'.date("y/m/d(D)H:i",$line['addedon']).'</td><td>';
				if ($line['boards']!="") {
					echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>';
				} else {
					echo $lang['no boards'];
				}
				echo '</td><td>[<a href="?action=staff&edit='.$line['id'].'">'.$lang['edit'].'</a>]&nbsp;[<a href="?action=staff&del='.$line['id'].'">x</a>]</td>';
				echo '</tr>';
			}
			echo '</table>';
		} else {
			echo $lang['none'];
		}
	} else if ($_GET['action']=="getpwd") {
		require_once($chan_rootdir."/inc/encryption.php");
		echo $lang['your posting password'].':<br /><b>'.md5_encrypt($_SESSION['manageusername'],$chan_randomseed).'</b>';
	} else if ($_GET['action']=="viewdeletedthread") {
		management_adminsonly();
		if (isset($_GET['threadid'])&&isset($_GET['board'])) {
			if ($_GET['threadid']>0) {
				$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".mysql_escape_string($_GET['board'])."'",$dblink);
				while ($line = mysql_fetch_assoc($result)) {
					$board_id = $line['id'];
					$board_dir = $line['name'];
				}
				$rows = mysql_num_rows($result);
				if ($rows>0) {
					$result = mysql_query("SELECT * FROM `posts` WHERE `id` = '".mysql_escape_string($_GET['threadid'])."' AND `boardid` = '".$board_id."'",$dblink);
					$rows = mysql_num_rows($result);
					if ($rows>0) {
						while ($line = mysql_fetch_assoc($result)) {
							$thread_isdeleted = $line['IS_DELETED'];
							$thread_threadid = $line['threadid'];
						}
						if ($thread_isdeleted=='1') {
							if ($thread_threadid=='0') {
								echo '<head><link rel="stylesheet" type="text/css" href="'.$chan_boardspath.'/css/burichan.css" title="Burichan" /></head>';
								echo buildthread($board_dir,$_GET['threadid'],false,true);
							} else {
								echo $lang['that id is for a reply not a thread'];
							}
						} else {
							echo $lang['that thread has yet to be deleted'];
						}
					} else {
						echo $lang['invalid thread id'];
					}
				}
			}
		} else {
			?>
			<form action="?" method="get">
			<input type="hidden" name="action" value="viewdeletedthread" />
			<label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br />
			<label for="threadid"><?php echo $lang['thread'].' '.$lang['id']; ?>:</label><input type="text" name="threadid" /><br />
			<input type="submit" value="<?php echo $lang['view deleted thread']; ?>">
			</form>
			<?php
		}
	} else if ($_GET['action']=="search" ) {
		management_adminsonly();
		if (isset($_GET['query'])) {
			$search_query = $_GET['query'] ;
			if (isset($_GET['s'])) {
				$s = $_GET['s'];
			} else {
				$s = 0;
			}
			$trimmed = trim($search_query);
			$limit=10; 
			if ($trimmed=="") {
				echo $lang['please enter a search query'];
				exit;
			}
			$query = "SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND `message` LIKE '%".$trimmed."%' ORDER BY `postedat` DESC";
			$numresults=mysql_query($query,$dblink);
			$numrows=mysql_num_rows($numresults);
			if ($numrows==0) {
				echo '<h4>'.$lang['results'].'</h4>';
				echo '<p>'.$lang['sorry your search returned zero results'].'</p>';
				die();
			}
			$query .= " LIMIT $s,$limit";
			$result = mysql_query($query,$dblink) or die($lang['generic error']);
			echo '<p>'.$lang['you searched for'].': &quot;'.$search_query.'&quot;</p>';
			echo $lang['results'].':<br /><br />';
			$count = 1 + $s ;
			while ($line=mysql_fetch_assoc($result)) {
				$board = boardid_to_dir($line['boardid']);
				echo $count.". Board: /".$board."/, Thread #<a href=\"";
				if ($line['threadid']=="0") {
					echo $chan_boardspath.'/'.$board.'/res/'.$line['id'].'.html">'.$line['id'].'</a>';
				} else {
					echo $chan_boardspath.'/'.$board.'/res/'.$line['threadid'].'.html#'.$line['id'].'">'.$line['threadid'].'</a>, Post #'.$line['id'];
				}
				echo '<fieldset>'.$line['message'].'</fieldset><br />' ;
				$count++;
			}
			$currPage = (($s/$limit) + 1);
			echo "<br />";
			if ($s>=1) {
				$prevs=($s-$limit);
				print "&nbsp;<a href=\"?action=search&s=$prevs&query=$search_query\">&lt;&lt; 
				Prev 10</a>&nbsp&nbsp;";
			}
			$pages=intval($numrows/$limit);
			if ($numrows%$limit) {
				$pages++;
			}
			if (!((($s+$limit)/$limit)==$pages) && $pages!=1) {
				$news=$s+$limit;
				echo "&nbsp;<a href=\"?action=search&s=$news&query=$search_query\">Next 10 &gt;&gt;</a>";
			}
			
			$a = $s + ($limit) ;
			if ($a > $numrows) { $a = $numrows ; }
			$b = $s + 1 ;
			echo '<p>'.$lang['results'].' '.$b.' &gt;&gt; '.$a.' of '.$numrows.'</p>';
			echo '<hr />';
		}
		?>
		<form action="?" method="get">
		<input type="hidden" name="action" value="search">
		<input type="hidden" name="s" value="0">
		<label for="query"><?php echo $lang['query']; ?>:</label><input type="text" name="query" value="<?php echo $_GET['query']; ?>"><br />
		<input type="submit" value="<?php echo $lang['search']; ?>">
		</form>
		<?php
	}
	
} else {
	echo '<h3>'.$lang['posting rates past hour'].'</h3><br />';
	$result = mysql_query("SELECT * FROM `boards` ORDER BY `order` ASC",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		echo '<table border="1" cellspacing="2" cellpadding="2"><tr><th>'.$lang['board'].'</th><th>'.$lang['threads'].'</th><th>'.$lang['replies'].'</th><th>'.$lang['posts'].'</th></tr>';
		while ($line = mysql_fetch_assoc($result)) {
			$result_threads = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$line['id']."' AND `threadid` = '0' AND `postedat` >= '".(time()-3600)."'",$dblink);
			$rows_threads = mysql_num_rows($result_threads);
			$result_replies = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$line['id']."' AND `threadid` != '0' AND `postedat` >= '".(time()-3600)."'",$dblink);
			$rows_replies = mysql_num_rows($result_replies);
			$rows_posts = $rows_threads+$rows_replies;
			$threads_perminute = $rows_threads;
			$replies_perminute = $rows_replies;
			$posts_perminute = $rows_posts;
			echo '<tr><td><b>'.$line['name'].'</b></td><td>'.$threads_perminute.'</td><td>'.$replies_perminute.'</td><td>'.$posts_perminute.'</td></tr>';
		}
		echo '</table>';
	} else {
		echo $lang['no boards'];
	}
}

echo '<br /><br />';

require($chan_rootdir."/inc/footer.php");
echo chan_footer(true);

?>
