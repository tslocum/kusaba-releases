<?php
/*
* +------------------------------------------------------------------------------+
* Manage panel for administrative/moderator operations
* +------------------------------------------------------------------------------+
* The manage panel is used for changing configurations, adding/modifying/deleting
* boards, locking/stickying/deleting posts, banning users, and more.  The manage
* panel is able to be logged in to by both administrators and moderators, however
* moderators will be restricted to only the boards which they moderate, and cannot
* perform any actions on the "Administration:" link-line.
* +------------------------------------------------------------------------------+
*/
session_set_cookie_params(60*60*24*100); // 100 days
session_start();

require('config.php');
require_once(TC_ROOTDIR.'inc/functions.php');
require_once(TC_ROOTDIR.'inc/operations.functions.php');
require_once(TC_ROOTDIR.'inc/classes.php');
require_once(TC_ROOTDIR.'inc/encryption.php');

/* Do these tasks each time manage.php is loaded */
removed_expired_bans();    // Doesn't matter if this is run by a non-mod
delete_accepted_reports(); // Doesn't matter if this is run by a non-mod
set_mod_cookies();         // Does nothing if the user isn't logged in

/* Decide what needs to be done */
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'posting_rates';
switch($action) {
case 'logout':
    logout(); break;
case 'showlogin':
    show_login(); break;
case 'login':
    checklogin();
    session_validate(); // Halts execution if not validated
    manage_page();
    break;
default:
    session_validate(); // Halts execution if not validated
    manage_page($action);
    break;
}

/* Show a particular manage function */
function manage_page($action = 'posting_rates') {
    manage_header();
    $actions = array('news', 'globopts', 'addboard', 'clearcache', 'delboard', 'wordfilter', 'misc', 'editsections', 'rebuildall', 'viewdeletedthread', 'cleanup', 'search', 'staff', 'modlog', 'sql', 'boardopts', 'stickypost', 'unstickypost', 'lockpost', 'unlockpost', 'delposts', 'reports', 'bans', 'deletepostsbyip', 'changepwd', 'posting_rates');
    if (in_array($action, $actions)) {
        $action();
    }
    else {
        echo "$action not implemented";
    }
    manage_footer();
}

/* Log current user out */
function logout() {
    global $tc_db, $lang;

    if (management_isadmin()) {
        $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards`");
        foreach($results AS $line) {
            setcookie("tcmod","",1,TC_BOARDSFOLDER.$line['name']."/",TC_BOARDSPATH);
        }
    } else {
        $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards`");
        foreach($results AS $line) {
            setcookie("tcmod","",1,TC_BOARDSFOLDER.$line['name']."/",TC_BOARDSPATH);
        }
    }
    session_destroy();
    unset($_SESSION['manageusername']);
    unset($_SESSION['managepassword']);
    echo $lang['successfully logged out'].'<meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/manage.php" />';
}

/* Check login names and create session if user/pass is correct */
function checklogin() {
    global $tc_db, $lang;

    $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."loginattempts` WHERE `timestamp` < '".(time()-1200)."'");
    $results = $tc_db->GetAll("SELECT `ip` FROM `".TC_DBPREFIX."loginattempts` WHERE `ip` = '".$_SERVER['REMOTE_ADDR']."' LIMIT 6");
    if (count($results)>5) {
        die($lang['locked out']);
    } else {
        $results = $tc_db->GetAll("SELECT `username` FROM `".TC_DBPREFIX."staff` WHERE `username` = '".mysql_real_escape_string($_POST['username'])."' AND `password` = '".md5($_POST['password'])."' LIMIT 1");
        if (count($results)>0) {
            $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."loginattempts` WHERE `ip` < '".$_SERVER['REMOTE_ADDR']."'");
            $_SESSION['manageusername'] = $_POST['username'];
            $_SESSION['managepassword'] = md5($_POST['password']);
            set_mod_cookies();
            management_addlogentry($lang['logged in'],1);
        } else {
            $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."loginattempts` ( `username` , `ip` , `timestamp` ) VALUES ( '".mysql_real_escape_string($_POST['username'])."' , '".$_SERVER['REMOTE_ADDR']."' , '".time()."' )");
            die($lang['incorrect username/password']);
        }
    }
}

/* Set mod cookies for boards */
function set_mod_cookies() {
    global $tc_db;

    if (isset($_SESSION['manageusername'])) {
        $results = $tc_db->GetAll("SELECT `boards` FROM `".TC_DBPREFIX."staff` WHERE `username` = '".mysql_real_escape_string($_SESSION['manageusername'])."' LIMIT 1");
        if (count($results)>0) {
            if (management_isadmin()) {
                $resultsboard = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards`");
                foreach($resultsboard AS $lineboard) {
                    setcookie("tcmod","yes",time()+3600,TC_BOARDSFOLDER.$lineboard['name']."/");
                }
            } else {
                foreach($results AS $line) {
                    $array_boards = explode('|',$line['boards']);
                }
                foreach ($array_boards as $this_board_name) {
                    setcookie("tcmod","yes",time()+3600,TC_BOARDSFOLDER.$this_board_name."/");
                }
            }
        }
    }
}

/* Show the login form and halt execution */
function show_login() {
    global $tc_db;
    require(TC_ROOTDIR.'inc/manage_login.php');
    die();
}

/* Validate the current session */
function session_validate() {
    global $tc_db;

    if (isset($_SESSION['manageusername'])&&isset($_SESSION['managepassword'])) {
        $results = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."staff` WHERE `username` = '".mysql_real_escape_string($_SESSION['manageusername'])."' AND `password` = '".mysql_real_escape_string($_SESSION['managepassword'])."'");
        if ($results[0]==0) {
            session_destroy();
            die($lang['invalid session']."<br><br><a href=\"manage.php\">".$lang['log in again']."</a>");
        }
    } else {
        show_login();
    }
}

/* Deletes reports that have the reported post has been deleted */
function delete_accepted_reports() {
/*    global $tc_db;
    $results = $tc_db->GetAll("SELECT ".TC_DBPREFIX."reports.id FROM ".TC_DBPREFIX."reports JOIN ".TC_DBPREFIX."posts ON ".TC_DBPREFIX."reports.postid = ".TC_DBPREFIX."posts.id AND ".TC_DBPREFIX."reports.boardid = ".TC_DBPREFIX."posts.boardid WHERE ".TC_DBPREFIX."posts.IS_DELETED = 1");
    if (count($results)>0) {
        foreach($results AS $line) {
            $tc_db->Execute("DELETE FROM ".TC_DBPREFIX."reports WHERE id = ".$line['id']."");
        }
    }*/
} //Devnote:  Broken, needs to be fixed

/* Show the header of the manage page */
function manage_header() {
    global $tc_db, $lang;
    echo '<title>'.$lang['manage boards'].'</title>
        <link rel="stylesheet" type="text/css" href="'.TC_BOARDSPATH.'/css/manage.css" title="Manage" />
        <div style="text-align: center;"><h1>Manage Boards</h1></div>';
    if (is_file(TC_ROOTDIR."inc/modheader.html")) {
        include(TC_ROOTDIR."inc/modheader.html");
    }
    require_once(TC_ROOTDIR."inc/encryption.php");
    $manage_postpassword = md5_encrypt($_SESSION['manageusername'],TC_RANDOMSEED);
    echo '<br>';
    echo $lang['welcome'].', '.$_SESSION['manageusername'].' [<a href="?action=logout">'.$lang['log out'].'</a>]<br>';
    if ($_SESSION['manageusername']=="admin"&&$_SESSION['managepassword']==md5("admin")) {
        echo '<font color="red"><b>'.$lang['default account notice'].'</b></font><br>';
    }
    echo $lang['staff rights'].': <b>';
    if (management_isadmin()) {
        echo $lang['administrator'];
    } else {
        echo $lang['moderator'];
    }
    echo '</b>';
    if (!management_isadmin()) {
        echo '<br>'.$lang['moderating boards'].': ';
        $i = 0;
        $resultsboard = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
        foreach($resultsboard AS $lineboard) {
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
    echo ' - Posting password: <span id="postingpassword"></span> <button id="showpwd" onclick="javascript:document.getElementById(\'postingpassword\').innerHTML = \'<b>'.$manage_postpassword.'</b>\';javascript:document.getElementById(\'showpwd\').style.display = \'none\';">Show</button><hr>';
    echo '<div style="text-align: left;">';

    if (management_isadmin()) {
        echo $lang['administration'].': <a href="?action=news">News</a> - <a href="?action=globopts">'.$lang['global options'].'</a> - <a href="?action=addboard">'.$lang['add board'].'</a> - <a href="?action=delboard">'.$lang['delete board'].'</a> - <a href="?action=wordfilter">'.$lang['wordfilter'].'</a> - <a href="?action=misc">'.'Misc'.'</a><br>';
    }
    echo $lang['boards'].': <a href="?action=boardopts">'.$lang['board options'].'</a> - <a href="?action=stickypost">'.$lang['manage stickies'].'</a> - <a href="?action=lockpost">'.$lang['manage locked threads'].'</a> - <a href="?action=delposts">'.$lang['delete thread/post'].'</a><br>';
    echo $lang['moderation'].': <a href="?action=reports">View reports</a>[<b>';
    $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."reports` WHERE `cleared` = '0'");
    if (count($results)==0) {
        echo '0';
    } else {
        echo '<font color="red">'.count($results).'</font>';
    }
    echo '</b>] - <a href="?action=bans">'.$lang['view/add/remove bans'].'</a> - <a href="?action=deletepostsbyip">'.$lang['delete all posts by ip'].'</a> - <a href="?action=changepwd">'.$lang['change account password'].'</a><br>';
    echo $lang['misc'].': <a href="?">'.$lang['index'].'</a>';

    echo '</div>';
    echo '<hr />';

}
/* Show the footer of the manage page */
function manage_footer() {
    global $tc_db;

    echo '<br><br>';

    $board_class = new Board('');
    echo $board_class->Footer(true);
}

function editsections() {
    global $tc_db, $lang;
    management_adminsonly();
    if ($_GET['do']=='addsection') {
        if ($_POST['name']!=''&&$_POST['abbreviation']!='') {
            $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."sections` ( `name` , `abbreviation` , `order` , `hidden` ) VALUES ( '".mysql_real_escape_string($_POST['name'])."' , '".mysql_real_escape_string($_POST['abbreviation'])."' , '".mysql_real_escape_string($_POST['order'])."' , '".(isset($_POST['hidden']) ? '1' : '0')."' )");
            echo 'Section added.';
        } else {
            echo '<form action="?action=editsections&do=addsection" method="post">
            <label for="name">Name:</label><input type="text" name="name"><div class="desc">The name of the section</div><br>
            <label for="abbreviation">Abbreviation:</label><input type="text" name="abbreviation"><div class="desc">Abbreviation (less then 10 characters)</div><br>
            <label for="order">Order:</label><input type="text" name="order"><div class="desc">Order to show this section with others, in ascending order</div><br>
            <label for="hidden">Hidden:</label><input type="checkbox" name="hidden" ><div class="desc">If checked, this section will be collapsed by default when a user visits the site.</div><br>
            <input type="submit" value="Add">
            </form>';
        }
        echo '<br><hr>';
    }
    if ($_GET['do']=='editsection'&&$_GET['sectionid']>0) {
        if ($_POST['name']!=''&&$_POST['abbreviation']!='') {
            $tc_db->Execute("UPDATE `".TC_DBPREFIX."sections` SET `name` = '".mysql_real_escape_string($_POST['name'])."' , `abbreviation` = '".mysql_real_escape_string($_POST['abbreviation'])."' , `order` = '".mysql_real_escape_string($_POST['order'])."' , `hidden` = '".(isset($_POST['hidden']) ? '1' : '0')."' WHERE `id` = '".$_POST['id']."'");
            echo 'Section updated.';
        } else {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."sections` WHERE `id` = '".mysql_real_escape_string($_GET['sectionid'])."'");
            if (count($results)>0) {
                foreach($results AS $line) {
                    echo '<form action="?action=editsections&do=editsection&sectionid='.$_GET['sectionid'].'" method="post">
                    <input type="hidden" name="id" value="'.$_GET['sectionid'].'">
                    <label for="name">Name:</label><input type="text" name="name" value="'.$line['name'].'"><div class="desc">The name of the section</div><br>
                    <label for="abbreviation">Abbreviation:</label><input type="text" name="abbreviation" value="'.$line['abbreviation'].'"><div class="desc">Abbreviation (less then 10 characters)</div><br>
                    <label for="order">Order:</label><input type="text" name="order" value="'.$line['order'].'"><div class="desc">Order to show this section with others, in ascending order</div><br>
                    <label for="hidden">Hidden:</label><input type="checkbox" name="hidden" '.($line['hidden']==0 ? '' : 'checked').'><div class="desc">If checked, this section will be collapsed by default when a user visits the site.</div><br>
                    <input type="submit" value="Edit">
                    </form>';
                }
            } else {
                echo 'Unable to locate a section with that ID.';
            }
        }
        echo '<br><hr>';
    }
    if ($_GET['do']=='deletesection'&&$_GET['sectionid']>0) {
        $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."sections` WHERE `id` = '".mysql_real_escape_string($_GET['sectionid'])."'");
        echo 'Section deleted.';
        echo '<br><hr>';
    }
    echo '<a href="?action=editsections&do=addsection">Add section</a><br><br>';
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."sections` ORDER BY `order` ASC");
    if (count($results)>0) {
        echo '<table border="1"><tr><th>ID</th><th>Order</th><th>Abbreviation</th><th>Name</th><th>Edit/Delete</th></tr>';
        foreach($results AS $line) {
            echo '<tr><td>'.$line['id'].'</td><td>'.$line['order'].'</td><td>'.$line['abbreviation'].'</td><td>'.$line['name'].'</td><td><a href="?action=editsections&do=editsection&sectionid='.$line['id'].'">Edit</a>&nbsp;<a href="?action=editsections&do=deletesection&sectionid='.$line['id'].'">Delete</a></td></tr>';
        }
        echo '</table>';
    } else {
        echo 'There are currently no sections.';
    }
}

function rebuildall() {
    global $tc_db, $lang;
    management_adminsonly();
    $time_start = time();
    $results = $tc_db->GetAll("SELECT `id`,`name` FROM `".TC_DBPREFIX."boards`");
    foreach($results AS $line) {
        $board_class = new Board($line['name']);
        $board_class->RegenerateAll();
        echo 'Regenerated /'.$line['name'].'/<br>';
        flush();
    }
    printf($lang['rebuild complete'],time()-$time_start);
    management_addlogentry($lang['rebuilt all boards and threads'],2);
}

function modlog() {
    global $tc_db, $lang;
    management_adminsonly();
    $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."modlog` WHERE `timestamp` < '".(time()-config_getvalue('modlogmaxdays')*86400)."'");
    echo '<h2>ModLog</h2><table cellspacing="2" cellpadding="1" border="1"><tr><th>Time</th><th>User</th><th width="100%">Action</th></tr>';
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."modlog` ORDER BY `timestamp` DESC");
    foreach($results AS $line) {
        echo "<tr><td>".date("y/m/d(D)H:i",$line['timestamp'])."</td><td>".$line['user']."</td><td>".$line['entry']."</td></tr>";
    }
    echo '</table>';
}

function sql() {
    global $tc_db, $lang;
    management_adminsonly();
    echo '<h2>'.$lang['sql query'].'</h2>';
    if (isset($_POST['query'])) {
        echo '<hr />';
        $result = $tc_db->Execute($_POST['query']);
        if ($result) {
            echo $lang['query executed successfully'];
        } else {
            echo 'Error: '.$tc_db->Error();
        }
        echo '<hr />';
    }
    echo '<form method="post" action="?action=sql"><textarea name="query" rows="20" cols="60"></textarea><br><input type="submit" value="'.$lang['inject'].'" /></form>';
}

function news() {
    global $tc_db, $lang;

    management_adminsonly();
    if ($_GET['edit']>0) {
        if (isset($_POST['news'])) {
            $tc_db->Execute("UPDATE `".TC_DBPREFIX."news` SET `subject` = '".$_POST['subject']."', `message` = '".$_POST['news']."', `postedemail` = '".$_POST['postedemail']."' WHERE `id` = '".mysql_real_escape_string($_GET['edit'])."'");
            echo '<h3>News post edited</h3>';
        }
        echo '<h1>Edit news post</h1>';
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."news` WHERE `id` = '".mysql_real_escape_string($_GET['edit'])."'");
        foreach($results AS $line) {
            echo '<form method="post" action="?action=news&edit='.$_GET['edit'].'"><label for="subject">'.$lang['subject'].':</label><input type="text" name="subject" value="'.$line['subject'].'" /><div class="desc">'.$lang['can not be left blank'].'</div><br>
                <textarea name="news" rows="25" cols="80">'.$line['message'].'</textarea><br><label for="email">'.$lang['e-mail'].':</label><input type="text" name="email" value="'.$line['postedemail'].'" /><div class="desc">'.$lang['can be left blank'].'</div><br><input type="submit" value="Edit" /></form>';
        }
    } elseif ($_GET['delete']>0) {
        $results = $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."news` WHERE `id` = '".mysql_real_escape_string($_GET['delete'])."'");
        echo '<h3>News post deleted</h3>';
    } else {
        echo $lang['add news header'].'<br><br>';
        if (isset($_POST['news'])&&isset($_POST['subject'])&&isset($_POST['email'])) {
            if ($_POST['news']!="") {
                echo '<hr />';
                if ($_POST['subject']!="") {
                    $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."news` ( `subject` , `message` , `postedat` , `postedby` , `postedemail` ) VALUES ( '".mysql_real_escape_string($_POST['subject'])."' , '".mysql_real_escape_string($_POST['news'])."' , '".time()."' , '".mysql_real_escape_string($_SESSION['manageusername'])."' , '".mysql_real_escape_string($_POST['email'])."' )");
                    echo '<h3>'.$lang['news add successful'].'</h3>';
                    management_addlogentry($lang['added a news entry'],9);
                } else {
                    echo $lang['enter a subject'];
                }
                echo '<hr />';
            }
        }
        echo '<form method="post" action="?action=news"><label for="subject">'.$lang['subject'].':</label><input type="text" name="subject" value="" /><div class="desc">'.$lang['can not be left blank'].'</div><br>
            <textarea name="news" rows="25" cols="80"></textarea><br><label for="email">'.$lang['e-mail'].':</label><input type="text" name="email" value="" /><div class="desc">'.$lang['can be left blank'].'</div><br><input type="submit" value="'.$lang['add'].'" /></form>';
        echo '<br><hr><h1>Edit/Delete News</h1>';
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."news`");
        if (count($results)>0) {
            echo '<table border="1"><tr><th>Date Added</th><th>Subject</th><th>Message</th><th>Edit/Delete</th></tr>';
            foreach($results AS $line) {
                echo '<tr><td>'.date('F j, Y, g:i a',$line['postedat']).'</td><td>'.$line['subject'].'</td><td>'.$line['message'].'</td><td><a href="?action=news&edit='.$line['id'].'">Edit</a>/<a href="?action=news&delete='.$line['id'].'">Delete</a></td></tr>';
            }
            echo '</table>';
        } else {
            echo 'No news posts yet.';
        }
    }
}

function clearcache() {
    global $tc_db, $lang;

    management_adminsonly();
    if (TC_DBUSECACHE) {
        $tc_db->CacheFlush();
        echo 'Cache successfully flushed.';
    } else {
        echo 'Unable to clear cache: you do not have caching enabled.';
    }
}

function globopts() {
    global $tc_db, $lang;

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
        if ($_POST['makeurlsclickable']=='1') {
            config_setvalue('makeurlsclickable','1');
        } else {
            config_setvalue('makeurlsclickable','0');
        }
        config_setvalue('maxthumbwidth',$_POST['maxthumbwidth']);
        config_setvalue('maxthumbheight',$_POST['maxthumbheight']);
        config_setvalue('modlogmaxdays',$_POST['modlogmaxdays']);
        config_setvalue('numrepliesdisplayed',$_POST['numrepliesdisplayed']);
        config_setvalue('numrepliesdisplayedsticky',$_POST['numrepliesdisplayedsticky']);
        config_setvalue('numthreadsdisplayed',$_POST['numthreadsdisplayed']);
        config_setvalue('postboxnotice',$_POST['postboxnotice']);
        config_setvalue('ircinfo',$_POST['ircinfo']);
        echo $lang['global configuration successfully updated'];
        management_addlogentry($lang['updated global configuration'],10);
        echo '<hr>';
        $tc_db->CacheFlush();
    }
    $config_maxthumbwidth = config_getvalue('maxthumbwidth');
    $config_maxthumbheight = config_getvalue('maxthumbheight');
    $config_imagesinnewwindow = config_getvalue('imagesinnewwindow');
    $config_modlogmaxdays = config_getvalue('modlogmaxdays');
    $config_numrepliesdisplayed = config_getvalue('numrepliesdisplayed');
    $config_numrepliesdisplayedsticky = config_getvalue('numrepliesdisplayedsticky');
    $config_numthreadsdisplayed = config_getvalue('numthreadsdisplayed');
    $config_postboxnotice = config_getvalue('postboxnotice');
    $config_makeurlsclickable = config_getvalue('makeurlsclickable');
    $config_ircinfo = config_getvalue('ircinfo');
?>
    <form action="?action=globopts" method="post">
    <label for="makeurlsclickable">Make URLs clickable:</label><select name="makeurlsclickable"><?php echo ($config_makeurlsclickable=='1') ? '<option value="1">'.$lang['yes'].'</option><option value="0">'.$lang['no'].'</option>' : '<option value="0">'.$lang['no'].'</option><option value="1">'.$lang['yes'].'</option>'; ?></select><br>
    <label for="maxthumbwidth"><?php echo $lang['maxmimum thumbnail width']; ?>:</label><input type="text" name="maxthumbwidth" value="<?php echo $config_maxthumbwidth; ?>" /><br>
    <label for="maxthumbheight"><?php echo $lang['maxmimum thumbnail height']; ?>:</label><input type="text" name="maxthumbheight" value="<?php echo $config_maxthumbheight; ?>" /><br>
    <label for="imagesinnewwindow"><?php echo $lang['open images in new window']; ?>:</label><select name="imagesinnewwindow"><?php echo ($config_imagesinnewwindow=='1') ? '<option value="1">'.$lang['yes'].'</option><option value="0">'.$lang['no'].'</option>' : '<option value="0">'.$lang['no'].'</option><option value="1">'.$lang['yes'].'</option>'; ?></select><br>
    <label for="modlogmaxdays"><?php echo $lang['days to keep modlog entries']; ?>:</label><input type="text" name="modlogmaxdays" value="<?php echo $config_modlogmaxdays; ?>" /><br>
    <label for="numrepliesdisplayed"><?php echo $lang['replies displayed per thread']; ?>:</label><input type="text" name="numrepliesdisplayed" value="<?php echo $config_numrepliesdisplayed; ?>" /><br>
    <label for="numrepliesdisplayedsticky"><?php echo $lang['replies displayed per thread sticky']; ?>:</label><input type="text" name="numrepliesdisplayedsticky" value="<?php echo $config_numrepliesdisplayedsticky; ?>" /><br>
    <label for="numthreadsdisplayed"><?php echo $lang['threads displayed per thread']; ?>:</label><input type="text" name="numthreadsdisplayed" value="<?php echo $config_numthreadsdisplayed; ?>" /><br>
    <label for="postboxnotice"><?php echo $lang['postbox notice']; ?>:</label><textarea name="postboxnotice" rows="8" cols="60"><?php echo $config_postboxnotice; ?></textarea><br>
    <label for="ircinfo">IRC Info:</label><textarea name="ircinfo" rows="8" cols="60"><?php echo $config_ircinfo; ?></textarea><br>
    <input type="submit" value="<?php echo $lang['update']; ?>">
    </form>
<?php
}

function boardopts() {
    global $tc_db, $lang;
    management_adminsonly();
    if (isset($_GET['updateboard'])&&isset($_POST['order'])&&isset($_POST['maxpages'])&&isset($_POST['maxage'])&&isset($_POST['messagelength'])) {
        if (!moderator_ismodofboard($_GET['updateboard'],$_SESSION['manageusername'])) {
            die($lang['not a moderator']);
        }
        $resultsboard = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_GET['updateboard'])."'");
        if (count($resultsboard)>0) {
            if ($_POST['order']>=0&&$_POST['maxpages']>=0&&$_POST['maxage']>=0&&$_POST['messagelength']>=0&&($_POST['enablereporting']=='0'||$_POST['enablereporting']=='1')&&($_POST['enablecaptcha']=='0'||$_POST['enablecaptcha']=='1')) {
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
                if (($_POST['type']=='0'||$_POST['type']=='1'||$_POST['type']=='2')&&($_POST['uploadtype']=='0'||$_POST['uploadtype']=='1'||$_POST['uploadtype']=='2')) {
                    $tc_db->Execute("UPDATE `".TC_DBPREFIX."boards` SET `type` = '".mysql_real_escape_string($_POST['type'])."' , `uploadtype` = '".mysql_real_escape_string($_POST['uploadtype'])."' , `order` = '".mysql_real_escape_string($_POST['order'])."' , `section` = '".mysql_real_escape_string($_POST['section'])."' , `desc` = '".mysql_real_escape_string($_POST['desc'])."' , `filetypes` = '".implode('|',$filetypes)."' , `locked` = '".$updateboard_locked."' , `maximagesize` = '".mysql_real_escape_string($_POST['maximagesize'])."' , `messagelength` = '".mysql_real_escape_string($_POST['messagelength'])."' , `maxpages` = '".mysql_real_escape_string($_POST['maxpages'])."' , `maxage` = '".mysql_real_escape_string($_POST['maxage'])."' , `maxreplies` = '".mysql_real_escape_string($_POST['maxreplies'])."' , `image` = '".mysql_real_escape_string($_POST['image'])."' , `includeheader` = '".mysql_real_escape_string($_POST['includeheader'])."' , `redirecttothread` = '".mysql_real_escape_string($_POST['redirecttothread'])."' , `forcedanon` = '".mysql_real_escape_string($_POST['forcedanon'])."' , `enablereporting` = '".mysql_real_escape_string($_POST['enablereporting'])."' , `enablecaptcha` = '".mysql_real_escape_string($_POST['enablecaptcha'])."' WHERE `name` = '".mysql_real_escape_string($_GET['updateboard'])."'");
                    echo $lang['update successful'] ;
                    management_addlogentry($lang['updated board configuration']." - /".$_GET['updateboard']."/",4);
                } else {
                    echo $lang['generic error'];
                }
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
        $resultsboard = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_POST['board'])."'");
        if (count($resultsboard)>0) {
            foreach($resultsboard AS $lineboard) {
?>
                    <div class="container">
                    <form action="?action=boardopts&updateboard=<?php echo $_POST['board']; ?>" method="post">
                    <label for="board"><?php echo $lang['directory']; ?>:</label><input type="text" name="board" value="<?php echo $_POST['board']; ?>" disabled /><div class="desc"><?php echo $lang['directory desc']; ?></div><br>
                    <label for="type">Board type:</label><select name="type"><option value="0"<?php if ($lineboard['type']=='0') { echo ' selected'; } ?>>Normal Imageboard</option><option value="1"<?php if ($lineboard['type']=='1') { echo ' selected'; } ?>>Text Board</option><option value="2"<?php if ($lineboard['type']=='2') { echo ' selected'; } ?>>Oekaki Imageboard</option></select><div class="desc"><?php echo 'The type of posts which will be accepted on this board.  A normal imageboard will feature image and extended format posts, a text board will have no images, and an Oekaki board will allow users to draw images and use them in their posts.'.' '.$lang['default']; ?>: <b>Normal Imageboard</b></div><br>
                    <label for="uploadtype">Upload type:</label><select name="uploadtype"><option value="0"<?php if ($lineboard['uploadtype']=='0') { echo ' selected'; } ?>>No Embedding</option><option value="1"<?php if ($lineboard['uploadtype']=='1') { echo ' selected'; } ?>>Images and Embedding</option><option value="2"<?php if ($lineboard['uploadtype']=='2') { echo ' selected'; } ?>>Embedding Only</option></select><div class="desc"><?php echo 'Whether or not to allow embedding of videos.'.' '.$lang['default']; ?>: <b>No Embedding</b></div><br>
                    <label for="desc"><?php echo $lang['description']; ?>:</label><input type="text" name="desc" value="<?php echo $lineboard['desc']; ?>" /><div class="desc"><?php echo $lang['desc desc']; ?></div><br>
                    <label for="order"><?php echo $lang['order']; ?>:</label><input type="text" name="order" value="<?php echo $lineboard['order']; ?>" /><div class="desc"><?php echo $lang['order desc']; ?> <b>0</b></div><br>
                    <label for="section"><?php echo $lang['section']; ?>:</label><input type="text" name="section" value="<?php echo $lineboard['section']; ?>" /><div class="desc"><?php echo $lang['section desc']; ?><br>If this is set to 0, <b>it will not be shown in the menu</b>.</div><br>
                    <label for="locked"><?php echo $lang['locked']; ?>: (<img src="<?php echo TC_BOARDSPATH; ?>/locked.gif" alt="Lock" />)</label><input type="checkbox" name="locked" <?php if ($lineboard['locked']=="1") { echo'checked '; } ?>/><div class="desc"><?php echo $lang['locked desc']; ?></div><br>
                    <label for="enablereporting">Enable reporting:</label><select name="enablereporting"><option value="0"<?php if ($lineboard['enablereporting']=='0') { echo ' selected'; } ?>><?php echo $lang['no']; ?></option><option value="1"<?php if ($lineboard['enablereporting']=='1') { echo ' selected'; } ?>><?php echo $lang['yes']; ?></option></select><div class="desc"><?php echo 'Reporting allows users to report posts, adding the post to the report list.'.' '.$lang['default']; ?>: <b><?php echo $lang['yes']; ?></b></div><br>
                    <label for="enablecaptcha">Enable captcha:</label><select name="enablecaptcha"><option value="0"<?php if ($lineboard['enablecaptcha']=='0') { echo ' selected'; } ?>><?php echo $lang['no']; ?></option><option value="1"<?php if ($lineboard['enablecaptcha']=='1') { echo ' selected'; } ?>><?php echo $lang['yes']; ?></option></select><div class="desc"><?php echo 'Enable/disable captcha system for this board.  If captcha is enabled, in order for a user to post, they must first correctly enter the text on an image.'.' '.$lang['default']; ?>: <b><?php echo $lang['no']; ?></b></div><br>
                    <label><?php echo $lang['allowed image types']; ?>:</label><div class="desc"><?php echo $lang['imagetypes desc']; ?></div><br><label for="filetype_gif">GIF</label><input type="checkbox" name="filetype_gif" <?php if (in_array('GIF',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br><label for="filetype_jpg">JPG</label><input type="checkbox" name="filetype_jpg" <?php if (in_array('JPG',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br><label for="filetype_png">PNG</label><input type="checkbox" name="filetype_png" <?php if (in_array('PNG',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br><label for="filetype_swf">SWF</label><input type="checkbox" name="filetype_swf" <?php if (in_array('SWF',explode('|',$lineboard['filetypes']))) { echo'checked '; } ?>/><br>
                    <label for="maximagesize"><?php echo $lang['maximum image size']; ?>:</label><input type="text" name="maximagesize" value="<?php echo $lineboard['maximagesize']; ?>" /><div class="desc"><?php echo $lang['maximagesize desc'].' '.$lang['default']; ?>: <b>1024000</b></div><br>
                    <label for="messagelength"><?php echo $lang['maximum message length']; ?>:</label><input type="text" name="messagelength" value="<?php echo $lineboard['messagelength']; ?>" /><div class="desc"><?php echo $lang['default'];?>: <b>8192</b></div><br>
                    <label for="maxpages"><?php echo $lang['maximum board pages']; ?>:</label><input type="text" name="maxpages" value="<?php echo $lineboard['maxpages']; ?>" /><div class="desc"><?php echo $lang['default']; ?>: <b>10</b></div><br>
                    <label for="maxage"><?php echo $lang['maximum thread age hours']; ?>:</label><input type="text" name="maxage" value="<?php echo $lineboard['maxage']; ?>" /><div class="desc"><?php echo $lang['default']; ?>: <b>96</b></div><br>
                    <label for="maxreplies"><?php echo $lang['maximum thread replies']; ?>:</label><input type="text" name="maxreplies" value="<?php echo $lineboard['maxreplies']; ?>" /><div class="desc"><?php echo $lang['maxreplies desc'].' '.$lang['default'];?>: <b>200</b></div><br>
                    <label for="image"><?php echo $lang['header image']; ?>:</label><input type="text" name="image" value="<?php echo $lineboard['image']; ?>" /><div class="desc"><?php echo $lang['image desc']; ?></div><br>
                    <label for="includeheader"><?php echo $lang['include header']; ?>:</label><textarea name="includeheader" rows="12" cols="80"><?php echo $lineboard['includeheader']; ?></textarea><div class="desc"><?php echo $lang['includeheader desc']; ?></div><br>
                    <label for="redirecttothread"><?php echo $lang['redirect to thread']; ?>:</label><select name="redirecttothread"><?php echo ($lineboard['redirecttothread']=='1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>'; ?></select><div class="desc"><?php echo $lang['redirecttothread desc'].' '.$lang['default']; ?>: <b><?php echo $lang['no']; ?></b></div><br>
                    <label for="forcedanon"><?php echo $lang['forced anonymous']; ?>:</label><select name="forcedanon"><?php echo ($lineboard['forcedanon']=='1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>'; ?></select><div class="desc"><?php echo $lang['forcedanon desc' ].' '.$lang['default']; ?>: <b><?php echo $lang['no']; ?></b></div><br>
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
}

function deletepostsbyip() {
    global $tc_db, $board_class, $lang;

    if (isset($_POST['ip'])) {
        if ($_POST['ip']!="") {
            $deletion_boards = array();
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
            foreach($results AS $line) {
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
                $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($deletion_board)."'");
                foreach($results AS $line) {
                    $board_name = $line['name'];
                }
                $result = $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$board_name."` SET `IS_DELETED` = '1' WHERE `is_deleted` = '0' AND `ipmd5` = '".md5($_POST['ip'])."'");
                if (!$result) {
                    echo $lang['generic error'];
                    echo mysql_error();
                    die();
                }
                $i += $tc_db->Affected_Rows();
                $board_class = new Board($board_name);
                $board_class->RegenerateAll();
            }
            echo $lang['all posts by ip deleted'].'<br>';
            echo '<hr />';
            management_addlogentry($lang['deleted posts by ip'].' '.$_POST['ip'],7);
        }
    }
?>
        <form action="?action=deletepostsbyip" method="post">
        <label for="ip"><?php echo $lang['ip']; ?>:</label><input type="text" name="ip" value="<?php if (isset($_GET['ip'])) { echo $_GET['ip']; } ?>" /><br>
        <?php echo $lang['boards']; ?>:&nbsp;&nbsp;<?php make_boardlist_checkbox('deletefrom',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <input type="submit" value="<?php echo $lang['delete posts']; ?>" />
        </form>
<?php
}

function unstickypost() {
    global $tc_db, $board_class, $lang;

    if (isset($_GET['postid'])&&isset($_GET['board'])) {
        if ($_GET['postid']>0&&$_GET['board']!="") {
            $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_GET['board'])."'");
            if (count($results)>0) {
                if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
                    die($lang['you are not a moderator of this board']);
                }
                foreach($results AS $line) {
                    $sticky_board_name = $line['name'];
                }
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$sticky_board_name."` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
                if (count($results)>0) {
                    $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$sticky_board_name."` SET `stickied` = '0' WHERE `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
                    $board_class = new Board($sticky_board_name);
                    $board_class->RegenerateAll();
                    echo $lang['thread successfully unstickied'];
                    management_addlogentry($lang['unstickied thread'].' #'.mysql_real_escape_string($_GET['postid']).' - /'.mysql_real_escape_string($_GET['board']).'/',5);
                } else {
                    echo $lang['invalid thread id'];
                }
            } else {
                echo $lang['invalid board directory'];
            }
            echo '<hr />';
        }
    }
    stickyforms();
}

function stickypost() {
    global $tc_db, $board_class, $lang;

    if (isset($_GET['postid'])&&isset($_GET['board'])) {
        if ($_GET['postid']>0&&$_GET['board']!="") {
            $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `name` = '".$_GET['board']."'");
            if (count($results)>0) {
                if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
                    die($lang['you are not a moderator of this board']);
                }
                foreach($results AS $line) {
                    $sticky_board_name = $line['name'];
                }
                $result = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$sticky_board_name."` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
                if ($result>0) {
                    $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$sticky_board_name."` SET `stickied` = '1' WHERE `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
                    $board_class = new Board($sticky_board_name);
                    $board_class->RegenerateAll();
                    echo $lang['thread successfully stickied'];
                    management_addlogentry($lang['stickied thread'].' #'.mysql_real_escape_string($_GET['postid']).' - /'.mysql_real_escape_string($_GET['board']).'/',5);
                } else {
                    echo $lang['invalid thread id'];
                }
            } else {
                echo $lang['invalid board directory'];
            }
            echo '<hr />';
        }
    }
    stickyforms();
}

function stickyforms() {
    global $lang;
?>
        <form action="manage.php" method="get"><input type="hidden" name="action" value="stickypost">
        <label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['sticky']; ?>">
        </form>
        <br><hr />
        <form action="manage.php" method="get"><input type="hidden" name="action" value="unstickypost">
        <label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['unsticky']; ?>">
        </form>
<?php
}

function lockpost() {
    global $tc_db, $board_class, $lang;

    if (isset($_GET['postid'])&&isset($_GET['board'])) {
        if ($_GET['postid']>0&&$_GET['board']!="") {
            $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_GET['board'])."'");
            if (count($results)>0) {
                if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
                    die($lang['you are not a moderator of this board']);
                }
                foreach($results AS $line) {
                    $lock_board_name = $line['name'];
                }
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$lock_board_name."` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
                if (count($results)>0) {
                    $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$lock_board_name."` SET `locked` = '1' WHERE `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
                    $board_class = new Board($lock_board_name);
                    $board_class->RegenerateAll();
                    echo $lang['thread successfully locked'];
                    management_addlogentry($lang['locked thread'].' #'.mysql_real_escape_string($_GET['postid']).' - /'.mysql_real_escape_string($_GET['board']).'/',5);
                } else {
                    echo $lang['invalid thread id'];
                }
            } else {
                echo $lang['invalid board directory'];
            }
            echo '<hr />';
        }
    }
    lockforms();
}

function unlockpost() {
    global $tc_db, $board_class, $lang;

    if ($_GET['postid']>0&&$_GET['board']!="") {
        $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_GET['board'])."'");
        if (count($results)>0) {
            if (!moderator_ismodofboard($_GET['board'],$_SESSION['manageusername'])) {
                die($lang['you are not a moderator of this board']);
            }
            foreach($results AS $line) {
                $lock_board_name = $line['name'];
            }
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$lock_board_name."` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
            if (count($results)>0) {
                $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$lock_board_name."` SET `locked` = '0' WHERE `threadid` = '0' AND `id` = '".mysql_real_escape_string($_GET['postid'])."'");
                $board_class = new Board($lock_board_name);
                $board_class->RegenerateAll();
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
    lockforms();
}

function lockforms() {
    global $lang;

?>
        <form action="manage.php" method="get"><input type="hidden" name="action" value="lockpost">
        <label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['lock']; ?>">
        </form>
        <br><hr />
        <form action="manage.php" method="get"><input type="hidden" name="action" value="unlockpost">
        <label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <label for="postid"><?php echo $lang['thread']; ?>:</label><input type="text" name="postid"><input type="submit" value="<?php echo $lang['unlock']; ?>">
        </form>
<?php
}

function cleanup() {
    global $lang;
    management_adminsonly();
    delorphanreplies(true);
    echo '<hr>Deleting unused images.<hr>';
    flush();
    delunusedimages_beta(true);
    echo $lang['cleanup finished'];
    management_addlogentry($lang['ran cleanup'],2);
}

function bans() {
    global $tc_db, $lang;
    $ban_ip = "";
    if (isset($_POST['ip'])&&isset($_POST['seconds'])) {
        if ($_POST['ip']!="") {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."banlist` WHERE `ipmd5` = '".md5($_POST['ip'])."'");
            if (count($results)==0) {
                if ($_POST['seconds']>=0) {
                    $banning_boards = array();
                    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
                    foreach($results AS $line) {
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
                    $ban_globalban = ($_POST['banfromall']=='on') ? '1' : '0';
                    $ban_allowread = ($_POST['allowread']=='1') ? '1' : '0';
                    if ($ban_globalban=='0') {
                        $ban_boards = implode('|',$banning_new_boards);
                        foreach (explode('|',$ban_boards) as $board) {
                            if (!moderator_ismodofboard($board,$_SESSION['manageusername'])) {
                                die($lang['you can only ban for boards you moderate']);
                            }
                        }
                    } else {
                        $ban_boards = '';
                    }
                    if ($_POST['seconds']=='0') {
                        $ban_duration = '0'; //Permanent ban
                    } else {
                        $ban_duration = mysql_real_escape_string($_POST['seconds']); //Timed ban
                    }
                    if ($_POST['type']=='0') {
                        $ban_type = '0'; //Normal IP address ban
                    } else {
                        $ban_type = '1'; //IP range ban
                    }
                    if (ban_user(mysql_real_escape_string($_POST['ip']),$_SESSION['manageusername'],$ban_globalban,$ban_duration,$ban_boards,mysql_real_escape_string($_POST['reason']),$ban_type,$ban_allowread)) {
                        echo $lang['ban successfully placed'];
                    } else {
                        echo $lang['generic error'];
                        die();
                    }
                    $logentry = $lang['banned'].' '.$_POST['ip'].' until ';
                    if ($_POST['seconds']=='0') {
                        $logentry .= $lang['forever'];
                    } else {
                        $logentry .= date('F j, Y, g:i a',time()+$_POST['seconds']);
                    }
                    $logentry .= ' - '.$lang['reason'].': '.$_POST['reason'].' - '.$lang['banned from'].': ';
                    if ($ban_globalban=='1') {
                        $logentry .= $lang['all boards'].' ';
                    } else {
                        $logentry .=  '/'.implode('/, /',explode('|',$ban_boards)).'/ ';
                    }
                    management_addlogentry($logentry,8);
                    if ($_POST['quickbanboard']!=""&&$_POST['quickbanthreadid']!="") {
                        echo '<br><br><meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$_POST['quickbanboard'].'/';
                        if ($_POST['quickbanthreadid']!="0") {
                            echo 'res/'.$_POST['quickbanthreadid'].'.html';
                        }
                        echo '" /><a href="'.TC_BOARDSPATH.'/'.$_POST['quickbanboard'].'/';
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
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."banlist` WHERE `id` = '".mysql_real_escape_string($_GET['delban'])."'");
            if (count($results)>0) {
                foreach($results AS $line) {
                    $unban_ip = md5_decrypt($line['ip'],TC_RANDOMSEED);
                }
                $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."banlist` WHERE `id` = '".mysql_real_escape_string($_GET['delban'])."'");
                update_htaccess();
                echo $lang['ban successfully removed'];
                management_addlogentry($lang['unbanned'].' '.$unban_ip,8);
            } else {
                echo $lang['invalid ban id'];
            }
            echo '<hr />';
        }
    }
    if (isset($_GET['banboard'])&&isset($_GET['banpost'])) {
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_GET['banboard'])."'");
        if (count($results)>0) {
            foreach($results AS $line) {
                $ban_board_name = $line['name'];
            }
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$ban_board_name."` WHERE `id` = '".mysql_real_escape_string($_GET['banpost'])."'");
            if (count($results)>0) {
                foreach($results AS $line) {
                    $ban_ip = md5_decrypt($line['ip'],TC_RANDOMSEED);
                    $ban_threadid = $line['threadid'];
                }
            } else {
                echo $lang['a post with that id does not exist'].'<hr />';
            }
        }
    }
    flush();
    echo '<form action="manage.php?action=bans" method="post" name="banform">';
    if ($ban_ip!="") {
        echo '<input type="hidden" name="quickbanboard" value="'.$_GET['banboard'].'" /><input type="hidden" name="quickbanthreadid" value="'.$ban_threadid.'" />';
    } elseif (isset($_GET['ip'])) {
        $ban_ip = $_GET['ip'];
    }
?>
        <label for="ip"><?php echo $lang['ip']; ?>:</label><input type="text" name="ip" value="<?php echo $ban_ip; ?>" /><?php if ($ban_ip!="") { echo '&nbsp;&nbsp;<a href="?action=deletepostsbyip&ip='.$ban_ip.'" target="_blank">'.$lang['delete all posts by this ip'].'</a>'; } ?><br>
        <label for="allowread">Allow read:</label><select name="allowread" /><option value="1">Yes</option><option value="0">No</option></select><div class="desc">Whether or not the user(s) affected by this ban will be allowed to read the boards.<br><b>Warning</b>: Selecting No will prevent any reading of any page on the level of the boards on the server.<br>Changing this option to No will provide a global ban, whether or not you set the option below.</div><br>
        <label for="type">Type:</label><select name="type" /><option value="0">Single IP</option><option value="1">IP Range</option></select><div class="desc">The type of the ban.  A single IP can be banned by providing the full address, or an IP range can be banned by providing the range you wish to ban.</div><br>
        <?php echo $lang['ban from']; ?>:&nbsp;
        <label for="banfromall"><b><?php echo $lang['all boards']; ?></b></label><input type="checkbox" name="banfromall" /><br>OR<br>
<?php
    make_boardlist_checkbox('bannedfrom',moderator_boardlist($_SESSION['manageusername']));
?>
        <br>
        <label for="seconds"><?php echo $lang['seconds']; ?>:</label><input type="text" name="seconds" /><div class="desc"><?php echo $lang['presets']; ?>:&nbsp;<a href="#" onclick="document.banform.seconds.value='3600';">1hr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='604800';">1w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='1209600';">2w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='2592000';">30d</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='31536000';">1yr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='0';">never</a></div><br>
        <label for="reason"><?php echo $lang['reason']; ?>:</label><input type="text" name="reason" /><div class="desc"><?php echo $lang['presets']; ?>:&nbsp;<a href="#" onclick="document.banform.reason.value='Child Pornography';">CP</a>&nbsp;<a href="#" onclick="document.banform.reason.value='Proxy';">Proxy</a></div><br>
        <input type="submit" value="<?php echo $lang['add ban']; ?>">
        </form>
        <hr /><br>
<?php
    for ($i = 1; $i >= 0; $i--) {
        if ($i==1) {
            echo '<b>IP Range bans:</b><br>';
        } else {
            echo '<br><b>Single IP bans:</b><br>';
        }

        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."banlist` WHERE `type` = '".$i."' ORDER BY `id` DESC");
        if (count($results)>0) {
            echo '<table border="1"><tr><th>';
            if ($i==1) {
                echo 'IP Range';

            } else {
                echo 'IP Address';
            }
            echo '</th><th>Boards</th><th>Reason</th><th>Date Added</th><th>Expires</th><th>Added By</th><th>&nbsp;</th></tr>';
            foreach($results AS $line) {
                echo '<tr>';
                echo '<td><a href="?action=bans&ip='.md5_decrypt($line['ip'],TC_RANDOMSEED).'">'.md5_decrypt($line['ip'],TC_RANDOMSEED).'</a></td><td>';
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
    }
}

function delposts() {
    global $tc_db, $board_class, $lang;
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
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_POST['boarddir'])."'");
        if (count($results)>0) {
            if (!moderator_ismodofboard($_POST['boarddir'],$_SESSION['manageusername'])) {
                die($lang['you are not a moderator of this board']);
            }
            foreach($results AS $line) {
                $board_id = $line['id'];
                $board_dir = $line['name'];
            }
            if ($_POST['delthreadid']>0) {
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$board_dir."` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_real_escape_string($_POST['delthreadid'])."' AND `threadid` = '0'");
                if (count($results)>0) {
                    foreach($results AS $line) {
                        $delthread_id = $line['id'];
                    }
                    $post_class = new Post($delthread_id,$board_dir);
                    $numposts_deleted = $post_class->Delete();
                    $board_class = new Board($board_dir);
                    $board_class->RegenerateAll();
                    echo $lang['thread successfully deleted'];
                    management_addlogentry($lang['deleted thread'].' #<a href="?action=viewdeletedthread&threadid='.$delthread_id.'&board='.$_POST['boarddir'].'">'.$delthread_id.'</a> ('.$numposts_deleted.' replies) - /'.$board_dir.'/',7);
                    if ($_GET['postid']!="") {
                        echo '<br><br><meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'" /><a href="'.TC_BOARDSPATH.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'">'.$lang['redirecting'].'</a> to ban page...';
                    } else if ($isquickdel) {
                        echo '<br><br><meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$_GET['boarddir'].'/" /><a href="'.TC_BOARDSPATH.'/'.$_GET['boarddir'].'/">'.$lang['redirecting'].'</a> back to board...';
                    }
                } else {
                    echo $lang['invalid thread id'];
                }
            } else if ($_POST['delpostid']>0) {
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$board_dir."` WHERE `IS_DELETED` = '0' AND  `id` = '".mysql_real_escape_string($_POST['delpostid'])."'");
                if (count($results)>0) {
                    foreach($results AS $line) {
                        $delpost_id = $line['id'];
                        $delpost_threadid = $line['threadid'];
                    }
                    $post_class = new Post($delpost_id,$board_dir);
                    $post_class->Delete();
                    flushthreadrepliescache($delpost_threadid,$board_dir);
                    $board_class = new Board($board_dir);
                    $board_class->RegenerateThread($delpost_threadid);
                    $board_class->RegeneratePages();
                    echo $lang['post successfully deleted'];
                    management_addlogentry($lang['deleted post'].' #<a href="?action=viewdeletedthread&threadid='.$delpost_threadid.'&board='.$_POST['boarddir'].'#'.$delpost_id.'">'.$delpost_id.'</a> - /'.$board_dir.'/',7);
                    if ($_GET['postid']!="") {
                        echo '<br><br><meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'" /><a href="'.TC_BOARDSPATH.'/manage.php?action=bans&banboard='.$_GET['boarddir'].'&banpost='.$_GET['postid'].'">'.$lang['redirecting'].'</a> to ban page...';
                    } else if ($isquickdel) {
                        echo '<br><br><meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$_GET['boarddir'].'/res/'.$delpost_threadid.'.html" /><a href="'.TC_BOARDSPATH.'/'.$_GET['boarddir'].'/res/'.$delpost_threadid.'.html">'.$lang['redirecting'].'</a> back to thread...';
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
        <label for="boarddir"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('boarddir',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <label for="delthreadid"><?php echo $lang['thread']; ?>:</label><input type="text" name="delthreadid" /><br>
        <input type="submit" value="<?php echo $lang['delete thread']; ?>">
        </form>
        <br><hr />
        <form action="manage.php?action=delposts" method="post">
        <label for="boarddir"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('boarddir',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <label for="delpostid"><?php echo $lang['post']; ?>:</label><input type="text" name="delpostid" /><br>
        <input type="submit" value="<?php echo $lang['delete post']; ?>">
        </form>
<?php
}

function wordfilter() {
    global $tc_db, $lang;

    management_adminsonly();
    if (isset($_POST['word'])) {
        if ($_POST['word']!=""&&$_POST['replacedby']!="") {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."wordfilter` WHERE `word` = '".mysql_real_escape_string($_POST['word'])."'");
            if (count($results)==0) {
                $wordfilter_boards = array();
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
                foreach($results AS $line) {
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
                $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."wordfilter` ( `word` , `replacedby` , `boards` , `time` ) VALUES ( '".mysql_real_escape_string($_POST['word'])."' , '".mysql_real_escape_string($_POST['replacedby'])."' , '".mysql_real_escape_string(implode('|',$wordfilter_new_boards))."' , '".time()."' )");
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
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."wordfilter` WHERE `id` = '".mysql_real_escape_string($_GET['delword'])."'");
            if (count($results)>0) {
                foreach($results AS $line) {
                    $del_word = $line['word'];
                }
                $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."wordfilter` WHERE `id` = '".mysql_real_escape_string($_GET['delword'])."'");
                echo $lang['word successfully removed'];
                management_addlogentry($lang['removed word from wordfilter'].': '.$del_word,11);
            } else {
                echo $lang['that id does not exist'];
            }
            echo '<hr />';
        }
    } else if (isset($_GET['editword'])) {
        if ($_GET['editword']>0) {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."wordfilter` WHERE `id` = '".mysql_real_escape_string($_GET['editword'])."'");
            if (count($results)>0) {
                if (!isset($_POST['replacedby'])) {
                    foreach($results AS $line) {
?>
                            <form action="manage.php?action=wordfilter&editword=<?php echo $_GET['editword']; ?>" method="post">
                            <label for="word"><?php echo $lang['word']; ?>:</label><input type="text" name="word" value="<?php echo $line['word']; ?>" disabled /><br>
                            <label for="replacedby"><?php echo $lang['is replaced by']; ?>:</label><input type="text" name="replacedby" value="<?php echo $line['replacedby']; ?>" /><br>
                            <label><?php echo $lang['boards']; ?>:</label><br>
<?php
                        $array_boards = array();
                        $resultsboard = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
                        foreach($resultsboard AS $lineboard) {
                            $array_boards = array_merge($array_boards,array($lineboard['name']));
                        }
                        foreach ($array_boards as $this_board_name) {
                            echo '<label for="wordfilter'.$this_board_name.'">'.$this_board_name.'</label><input type="checkbox" name="wordfilter'.$this_board_name.'" ';
                            if (in_array($this_board_name,explode("|",$line['boards']))&&explode("|",$line['boards'])!="") {
                                echo 'checked ';
                            }
                            echo '/><br>';
                        }
?><br>
                            <input type="submit" value="<?php echo $lang['edit word']; ?>">
                            </form>
<?php
                    }
                } else {
                    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."wordfilter` WHERE `id` = '".mysql_real_escape_string($_GET['editword'])."'");
                    if (count($results)>0) {
                        foreach($results AS $line) {
                            $wordfilter_word = $line['word'];
                        }
                        $wordfilter_boards = array();
                        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
                        foreach($results AS $line) {
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
                        $tc_db->Execute("UPDATE `wordfilter` SET `replacedby` = '".mysql_real_escape_string($_POST['replacedby'])."' , `boards` = '".mysql_real_escape_string(implode('|',$wordfilter_new_boards))."' WHERE `id` = '".mysql_real_escape_string($_GET['editword'])."'");
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
            <label for="word"><?php echo $lang['word']; ?>:</label><input type="text" name="word" /><br>
            <label for="replacedby"><?php echo $lang['is replaced by']; ?>:</label><input type="text" name="replacedby" /><br>
            <label><?php echo $lang['boards']; ?>:</label><br>
<?php
        $array_boards = array();
        $resultsboard = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
        foreach($resultsboard AS $lineboard) {
            $array_boards = array_merge($array_boards,array($lineboard['name']));
        }
        make_boardlist_checkbox('wordfilter',$array_boards);
?><br>
            <input type="submit" value="<?php echo $lang['add word']; ?>">
            </form>
            <hr />
<?php
    }
    echo '<br>';
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."wordfilter`");
    foreach($results AS $line) {
        echo 'Word: '.$line['word'].' - Replaced by: '.$line['replacedby'].' - Boards: ';
        if (explode('|',$line['boards'])!="") {
            echo '<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;';
        } else {
            echo $lang['no boards'];
        }
        echo '[<a href="manage.php?action=wordfilter&editword='.$line['id'].'">'.$lang['edit'].'</a>]&nbsp;[<a href="manage.php?action=wordfilter&delword='.$line['id'].'">del</a>]<br>';
    }
}

function addboard() {
    global $tc_db, $board_class, $lang;

    management_adminsonly();
    if (isset($_POST['directory'])) {
        if ($_POST['directory']!=""&&$_POST['desc']!="") {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_POST['directory'])."'");
            if (count($results)==0) {
                if (mkdir(TC_BOARDSDIR."".$_POST['directory'], 0777)&&mkdir(TC_BOARDSDIR."".$_POST['directory']."/res", 0777)&&mkdir(TC_BOARDSDIR."".$_POST['directory']."/src", 0777)&&mkdir(TC_BOARDSDIR."".$_POST['directory']."/thumb", 0777)) {
                    file_put_contents(TC_BOARDSDIR."".$_POST['directory']."/.htaccess","DirectoryIndex board.html");
                    $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."boards` ( `name` , `desc` , `createdon` ) VALUES ( '".mysql_real_escape_string($_POST['directory'])."' , '".mysql_real_escape_string($_POST['desc'])."' , '".time()."' )");
                    if ($_POST['firstpostid']<1) {
                        $_POST['firstpostid'] = 1;
                    }
                    $tc_db->Execute("CREATE TABLE `".TC_DBPREFIX."posts_".mysql_real_escape_string($_POST['directory'])."` (
                    `id` int(10) NOT NULL auto_increment,
                    `threadid` int(10) NOT NULL default '0',
                    `user` varchar(255) NOT NULL,
                    `tripcode` varchar(10) NOT NULL,
                    `email` varchar(255) NOT NULL,
                    `subject` varchar(255) NOT NULL,
                    `message` text NOT NULL,
                    `image` varchar(20) NOT NULL,
                    `imagetype` varchar(5) NOT NULL,
                    `imagemd5` text NOT NULL,
                    `image_w` smallint(5) NOT NULL default '0',
                    `image_h` smallint(5) NOT NULL default '0',
                    `image_size` int(10) NOT NULL default '0',
                    `thumb_w` smallint(5) NOT NULL default '0',
                    `thumb_h` smallint(5) NOT NULL default '0',
                    `password` varchar(255) NOT NULL,
                    `postedat` int(20) NOT NULL,
                    `lastbumped` int(20) NOT NULL default '0',
                    `ip` varchar(75) NOT NULL,
                    `ipmd5` varchar(200) NOT NULL,
                    `stickied` tinyint(1) NOT NULL default '0',
                    `locked` tinyint(1) NOT NULL default '0',
                    `posterauthority` tinyint(1) NOT NULL default '0',
                    `IS_DELETED` tinyint(1) NOT NULL default '0',
                    UNIQUE KEY `id` (`id`),
                    KEY `threadid` (`threadid`),
                    KEY `lastbumped` (`lastbumped`)
                    ) ENGINE=MyISAM AUTO_INCREMENT=".mysql_real_escape_string($_POST['firstpostid'])." ;");
                    $board_class = new Board(mysql_real_escape_string($_POST['directory']));
                    $board_class->RegenerateAll();
                    echo $lang['board successfully added'].'<br><br><a href="'.TC_BOARDSPATH.'/'.$_POST['directory'].'/">/'.$_POST['directory'].'/</a>!';
                    management_addlogentry($lang['added board'].': /'.$_POST['directory'].'/',3);
                } else {
                    echo '<br>'.$lang['unable to create directories'];
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
        <label for="directory">Directory:</label><input type="text" name="directory" /><div class="desc">The directory of the board.  <b>Only put in the letter(s) of the board directory, no slashes!</b></div><br>
        <label for="desc">Description:</label><input type="text" name="desc" /><div class="desc">The name of the board.</div><br>
        <label for="firstpostid">First Post ID:</label><input type="text" name="firstpostid" value="1" /><div class="desc">The first post of this board will recieve this ID.</div><br>
        <input type="submit" value="Add Board">
        </form>
<?php
}

function delboard() {
    global $tc_db, $lang;

    management_adminsonly();
    if (isset($_POST['directory'])) {
        if ($_POST['directory']!="") {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_POST['directory'])."'");
            foreach($results AS $line) {
                $board_id = $line['id'];
                $board_dir = $line['name'];
            }
            if (count($results)>0) {
                if ($_POST['confirmation']=='yes') {
                    if (remove_board($board_dir)) {
                        $tc_db->Execute("DROP TABLE `".TC_DBPREFIX."posts_".$board_dir."`");
                        $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."boards` WHERE `id` = '".$board_id."'");
                        echo 'Board successfully deleted!';
                        management_addlogentry("Deleted board: /".$_POST['directory']."/",3);
                    } else {
                        //Error
                        echo 'Unable to delete board.';
                    }
                } else {
                    echo 'Are you absolutely sure you want to delete /'.$board_dir.'/ ?<br>
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
        <label for="directory">Directory:</label><?php echo make_boardlist_dropdown('directory',moderator_boardlist($_SESSION['manageusername'])); ?><br>
        <input type="submit" value="Delete Board">
        </form>
<?php
}

function changepwd() {
    global $tc_db, $lang;
    if (isset($_POST['oldpwd'])&&isset($_POST['newpwd'])&&isset($_POST['newpwd2'])) {
        if ($_POST['oldpwd']!=""&&$_POST['newpwd']!=""&&$_POST['newpwd2']!="") {
            if ($_POST['newpwd']==$_POST['newpwd2']) {
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."staff` WHERE `username` = '".mysql_real_escape_string($_SESSION['manageusername'])."'");
                foreach($results AS $line) {
                    $staff_passwordenc = $line['password'];
                }
                if (md5($_POST['oldpwd'])==$staff_passwordenc) {
                    $tc_db->Execute("UPDATE `".TC_DBPREFIX."staff` SET `password` = '".md5($_POST['newpwd'])."' WHERE `username` = '".mysql_real_escape_string($_SESSION['manageusername'])."'");
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
        <label for="oldpwd"><?php echo $lang['old password']; ?>:</label><input type="password" name="oldpwd" /><br>
        <label for="newpwd"><?php echo $lang['new password']; ?>:</label><input type="password" name="newpwd" /><br>
        <label for="newpwd2"><?php echo $lang['new password again']; ?>:</label><input type="password" name="newpwd2" /><br>
        <input type="submit" value="<?php echo $lang['change account password']; ?>">
        </form>
<?php
}

function staff() {
    global $tc_db, $lang;
    management_adminsonly();
    if (isset($_POST['staffusername'])&&isset($_POST['staffpassword'])) {
        if ($_POST['staffusername']!=""&&$_POST['staffpassword']!="") {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."staff` WHERE `username` = '".mysql_real_escape_string($_POST['staffusername'])."'");
            if (count($results)==0) {
                if ($_POST['isadmin']=="on") {
                    $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".mysql_real_escape_string($_POST['staffusername'])."' , '".md5($_POST['staffpassword'])."' , '1' , '".time()."' )");
                } else {
                    $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '".mysql_real_escape_string($_POST['staffusername'])."' , '".md5($_POST['staffpassword'])."' , '0' , '".time()."' )");
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
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."staff` WHERE `id` = '".mysql_real_escape_string($_GET['del'])."'");
            if (count($results)>0) {
                foreach($results AS $line) {
                    $staff_username = $line['username'];
                }
                $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."staff` WHERE `id` = '".mysql_real_escape_string($_GET['del'])."'");
                echo $lang['staff successfully deleted'];
                management_addlogentry($lang['deleted staff member'].': '.$staff_username,6);
            } else {
                echo $lang['invalid staff id'];
            }
            echo '<hr />';
        }
    } else if (isset($_GET['edit'])) {
        if ($_GET['edit']>0) {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."staff` WHERE `id` = '".mysql_real_escape_string($_GET['edit'])."'");
            if (count($results)>0) {
                if (isset($_POST['submitting'])) {
                    foreach($results AS $line) {
                        $staff_username = $line['username'];
                        $staff_isadmin = $line['isadmin'];
                    }
                    $staff_boards = array();
                    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
                    foreach($results AS $line) {
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
                    $tc_db->Execute("UPDATE `".TC_DBPREFIX."staff` SET `boards` = '".mysql_real_escape_string(implode('|',$staff_new_boards))."' WHERE `id` = '".mysql_real_escape_string($_GET['edit'])."'");
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
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."staff` WHERE `id` = '".$_GET['edit']."'");
                foreach($results AS $line) {
                    $staff_username = $line['username'];
                    $staff_isadmin = $line['isadmin'];
                    $staff_boards = explode('|',$line['boards']);
                }
?>
                    <form action="manage.php?action=staff&edit=<?php echo $_GET['edit']; ?>" method="post">
                    <label for="staffname"><?php echo $lang['username']; ?>:</label><input type="text" name="staffname" value="<?php echo $staff_username; ?>" disabled /><br>
                    <label for="isadmin"><?php echo $lang['administrator']; ?>?</label><input type="checkbox" name="isadmin" <?php if ($staff_isadmin=="1") { echo 'checked '; } ?>/><br><br>
<?php
                echo $lang['moderates'].'<br>';
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
                foreach($results AS $line) {
                    echo '<label for="moderate'.$line['name'].'">'.$line['name'].'</label><input type="checkbox" name="moderate'.$line['name'].'" ';
                    if (in_array($line['name'],$staff_boards)) {
                        echo 'checked ';
                    }
                    echo '/><br>';
                }
?>
                    <input type="submit" value="<?php echo $lang['modify staff member']; ?>" name="submitting" />
                    </form>
                    <br>
<?php
            } else {
                echo $lang['a staff member with that id does not appear to exist'];
            }
            echo '<hr />';
        }
    }
?>
        <form action="manage.php?action=staff" method="post">
        <label for="username"><?php echo $lang['username']; ?>:</label><input type="text" name="staffusername" /><br>
        <label for="password"><?php echo $lang['password']; ?>:</label><input type="text" name="staffpassword" /><br>
        <label for="isadmin"><?php echo $lang['administrator']; ?>?</label><input type="checkbox" name="isadmin" /><br>
        <input type="submit" value="<?php echo $lang['add staff member']; ?>">
        </form>
        <hr /><br>
<?php
    echo $lang['administrators'].':<br>';
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."staff` WHERE `isadmin` = '1' ORDER BY `username` ASC");
    if (count($results)>0) {
        echo '<table border="1"><tr><th>Username</th><th>Added on</th><th>&nbsp;</th></tr>';
        foreach($results AS $line) {
            echo '<tr>';
            echo '<td>'.$line['username'].'</td><td>'.date("y/m/d(D)H:i",$line['addedon']).'</td><td>[<a href="?action=staff&edit='.$line['id'].'">'.$lang['edit'].'</a>]&nbsp;[<a href="?action=staff&del='.$line['id'].'">x</a>]</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo $lang['none'];
    }
    echo $lang['moderators'].':<br>';
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."staff` WHERE `isadmin` = '0' ORDER BY `username` ASC");
    if (count($results)>0) {
        echo '<table border="1"><tr><th>'.$lang['username'].'</th><th>'.$lang['added on'].'</th><th>'.$lang['moderating boards'].'</th><th>&nbsp;</th></tr>';
        foreach($results AS $line) {
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
}

function viewdeletedthread() {
    global $tc_db, $lang;

    management_adminsonly();
    if (isset($_GET['threadid'])&&isset($_GET['board'])) {
        if ($_GET['threadid']>0) {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_GET['board'])."'");
            foreach($results AS $line) {
                $board_id = $line['id'];
                $board_dir = $line['name'];
            }
            if (count($results)>0) {
                $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$board_dir."` WHERE `id` = '".mysql_real_escape_string($_GET['threadid'])."'");
                if (count($results)>0) {
                    foreach($results AS $line) {
                        $thread_isdeleted = $line['IS_DELETED'];
                        $thread_threadid = $line['threadid'];
                    }
                    if ($thread_isdeleted=='1') {
                        if ($thread_threadid=='0') {
                            echo '<head><link rel="stylesheet" type="text/css" href="'.TC_BOARDSPATH.'/css/burichan.css" title="Burichan" /></head>';
                            echo 'Broke for now, will return soon.'; //Devnote:  fix viewing deleted threads
                            //echo buildthread($board_id,$_GET['threadid'],false,true);
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
            <label for="board"><?php echo $lang['board']; ?>:</label><?php echo make_boardlist_dropdown('board',moderator_boardlist($_SESSION['manageusername'])); ?><br>
            <label for="threadid"><?php echo $lang['thread'].' '.$lang['id']; ?>:</label><input type="text" name="threadid" /><br>
            <input type="submit" value="<?php echo $lang['view deleted thread']; ?>">
            </form>
<?php
    }
}

function search() {
    global $tc_db, $lang;
    management_adminsonly();
    die("Broken for now due to the new table system."); //Devnote: fix searching
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
        $query = "SELECT * FROM `".TC_DBPREFIX."posts` WHERE `IS_DELETED` = '0' AND `message` LIKE '%".$trimmed."%' ORDER BY `postedat` DESC";
        $numresults = $tc_db->GetAll($query);
        $numrows = count($numresults);
        if ($numrows==0) {
            echo '<h4>'.$lang['results'].'</h4>';
            echo '<p>'.$lang['sorry your search returned zero results'].'</p>';
            die();
        }
        $query .= " LIMIT $s,$limit";
        $results = $tc_db->GetAll($query);
        echo '<p>'.$lang['you searched for'].': &quot;'.$search_query.'&quot;</p>';
        echo $lang['results'].':<br><br>';
        $count = 1 + $s ;
        foreach($results AS $line) {
            $board = boardid_to_dir($line['boardid']);
            echo $count.". Board: /".$board."/, Thread #<a href=\"";
            if ($line['threadid']=="0") {
                echo TC_BOARDSPATH.'/'.$board.'/res/'.$line['id'].'.html">'.$line['id'].'</a>';
            } else {
                echo TC_BOARDSPATH.'/'.$board.'/res/'.$line['threadid'].'.html#'.$line['id'].'">'.$line['threadid'].'</a>, Post #'.$line['id'];
            }
            echo '<fieldset>'.$line['message'].'</fieldset><br>' ;
            $count++;
        }
        $currPage = (($s/$limit) + 1);
        echo "<br>";
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
        <label for="query"><?php echo $lang['query']; ?>:</label><input type="text" name="query" value="<?php echo $_GET['query']; ?>"><br>
        <input type="submit" value="<?php echo $lang['search']; ?>">
        </form>
<?php
}

function reports() {
    global $tc_db, $lang;

    if (isset($_GET['clear'])) {
        $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."reports` WHERE `id` = '".mysql_real_escape_string($_GET['clear'])."' LIMIT 1");
        if (count($results)>0) {
            $tc_db->Execute("UPDATE `".TC_DBPREFIX."reports` SET `cleared` = '1' WHERE `id` = '".mysql_real_escape_string($_GET['clear'])."' LIMIT 1");
            echo 'Report successfully cleared.<hr>';
        }
    }
    $query = "SELECT ".TC_DBPREFIX."posts.id, ".TC_DBPREFIX."posts.threadid, ".TC_DBPREFIX."posts.image, ".TC_DBPREFIX."posts.imagetype, ".TC_DBPREFIX."posts.message,".TC_DBPREFIX."boards.name,".TC_DBPREFIX."reports.id as reportid,".TC_DBPREFIX."reports.ip as reporterip FROM ".sqlboardlist()." JOIN ".TC_DBPREFIX."reports ON ".TC_DBPREFIX."reports.postid = ".TC_DBPREFIX."posts.id AND ".TC_DBPREFIX."reports.boardid = ".TC_DBPREFIX."posts.boardid JOIN ".TC_DBPREFIX."boards ON ".TC_DBPREFIX."posts.boardid = ".TC_DBPREFIX."boards.id WHERE ".TC_DBPREFIX."posts.IS_DELETED = 0 AND ".TC_DBPREFIX."reports.cleared = 0";
    if (!management_isadmin()) {
        $boardlist = moderator_boardlist($_SESSION['manageusername']);
        if (!empty($boardlist)) {
            $query .= ' AND (';
            foreach ($boardlist as $board) {
                $query .= ' boards.name = \''.$board.'\' OR';
            }
            $query = substr($query,0,-3).')';
        } else {
            echo 'You do not moderate any boards :(';
        }
    }
    $results = $tc_db->GetAll($query);
    if (count($results)>0) {
        echo '<table border="1"><tr><th>Board</th><th>Post</th><th>Picture</th><th>Message</th><th>Reporter IP</th><th>Action</th></tr>';
        foreach($results AS $line) {
            echo '<tr><td>/'.$line['name'].'/</td><td><a href="'.TC_BOARDSFOLDER.''.$line['name'].'/res/';
            if ($line['threadid']=='0') {
                echo $line['id'];
                $post_threadorpost = 'thread';
            } else {
                echo $line['threadid'];
                $post_threadorpost = 'post';
            }
            echo '.html#'.$line['id'].'">'.$line['id'].'</a></td><td>';
            if ($line['image']=='removed') {
                echo 'removed';
            } elseif ($line['image']=='') {
                echo 'none';
            } else {
                echo '<a href="'.TC_BOARDSFOLDER.''.$line['name'].'/src/'.$line['image'].'.'.$line['imagetype'].'"><img src="'.TC_BOARDSFOLDER.''.$line['name'].'/thumb/'.$line['image'].'s.'.$line['imagetype'].'" border="0"></a>';
            }
            echo '</td><td>';
            if ($line['message']!='') {
                echo stripslashes($line['message']);
            } else {
                echo '&nbsp;';
            }
            echo '</td><td>'.md5_decrypt($line['reporterip'],TC_RANDOMSEED).'</td><td><a href="?action=reports&clear='.$line['reportid'].'">Clear</a>&nbsp;&#91;<a href="?action=delposts&boarddir='.$line['name'].'&del'.$post_threadorpost.'id='.$line['id'].'" title="Delete" onclick="return confirm(\'Are you sure you want to delete this thread/post?\');">D</a>&nbsp;<a href="'.TC_BOARDSFOLDER.'manage.php?action=delposts&boarddir='.$line['name'].'&del'.$post_threadorpost.'id='.$line['id'].'&postid='.$line['id'].'" title="Delete &amp; Ban" onclick="return confirm(\'Are you sure you want to delete and ban this poster?\');">&amp;</a>&nbsp;<a href="?action=bans&banboard='.$line['name'].'&banpost='.$line['id'].'" title="Ban">B</a>&#93;</td></tr>';
        }
        echo '</table>';
    } else {
        echo 'No reports to show.';
    }
}

function misc() {
    global $tc_db, $lang;
    echo '<a href="?action=rebuildall">'.$lang['rebuild all boards and html files'].'</a><br><a href="?action=viewdeletedthread">'.$lang['view deleted thread'].'</a><br><a href="?action=cleanup">'.$lang['cleanup'].'</a><br><a href="?action=search">'.$lang['search posts'].'</a><br><a href="?action=staff">'.$lang['staff'].'</a><br><a href="?action=modlog">'.$lang['modlog'].'</a><br><a href="?action=editsections">'.'Edit sections'.'</a><br><a href="?action=sql">'.$lang['sql query'].'</a>';
    if (TC_DBUSECACHE) {
        echo '<br><a href="?action=clearcache">Clear query cache</a>';
    }
}

function posting_rates() {
    global $tc_db, $lang;
    echo '<h3>'.$lang['posting rates past hour'].'</h3><br>';
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` ORDER BY `order` ASC");
    if (count($results)>0) {
        echo '<table border="1" cellspacing="2" cellpadding="2"><tr><th>'.$lang['board'].'</th><th>'.$lang['threads'].'</th><th>'.$lang['replies'].'</th><th>'.$lang['posts'].'</th></tr>';
        foreach($results AS $line) {
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$line['name']."` WHERE `threadid` = 0 AND `postedat` >= ".(time()-3600)."");
            $rows_threads = count($results);
            $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$line['name']."` WHERE `threadid` != 0 AND `postedat` >= ".(time()-3600)."");
            $rows_replies = count($results);
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

/* Secondary functions */

function management_adminsonly() {
    global $tc_db;
    if (!management_isadmin()) {
        die('That page is for admins only.');
    }
}

function management_isadmin() {
    global $tc_db;
    $results = $tc_db->GetAll("SELECT `isadmin` FROM `".TC_DBPREFIX."staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."' LIMIT 1");
    foreach($results AS $line) {
        if ($line['isadmin']==1) {
            return true;
        } else {
            return false;
        }
    }
    session_destroy();
    die('Invalid session, please log in again.');
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
    global $tc_db;
    if ($entry!='') {
        $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."modlog` ( `entry` , `user` , `category` , `timestamp` ) VALUES ( '".mysql_real_escape_string($entry)."' , '".$_SESSION['manageusername']."' , '".mysql_real_escape_string($category)."' , '".time()."' )");
    }
}

function moderator_ismodofboard($board,$username) {
    global $tc_db;
    $results = $tc_db->GetAll("SELECT `isadmin`,`boards` FROM `".TC_DBPREFIX."staff` WHERE `username` = '".$username."' LIMIT 1");
    if (count($results)>0) {
        foreach($results AS $line) {
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
    global $tc_db;
    $staff_boardsmoderated = array();
    $results = $tc_db->GetAll("SELECT `boards` FROM `".TC_DBPREFIX."staff` WHERE `username` = '".$username."' LIMIT 1");
    if (count($results)>0) {
        if (management_isadmin()) {
            $resultsboard = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards`");
            foreach($resultsboard AS $lineboard) {
                $staff_boardsmoderated = array_merge($staff_boardsmoderated,array($lineboard['name']));
            }
        } else {
            foreach($results AS $line) {
                $array_boards = explode('|',$line['boards']);
            }
            foreach ($array_boards as $this_board_name) {
                $staff_boardsmoderated = array_merge($staff_boardsmoderated,array($this_board_name));
            }
        }
    }
    return $staff_boardsmoderated;
}

function sqlboardlist() {
    $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards` ORDER BY `name` ASC");
    $sqlboards = '';
    foreach($results AS $lineboard) {
        $sqlboards .= 'posts_'.$line['name'].', ';
    }
    return substr($sqlboards,0,-2);
}

function make_boardlist_dropdown($name,$boards) {
    echo '<select name="'.$name.'"><option value="">Select a Board</option>';
    if ($boards!='') {
        foreach ($boards as $board) {
            echo '<option value="'.$board.'">/'.$board.'/</option>';
        }
    }
    echo '</select>';
}

function make_boardlist_checkbox($prefix,$boards) {
    if ($boards!='') {
        foreach ($boards as $board) {
            echo '<label for="'.$prefix.$board.'">'.$board.'</label><input type="checkbox" name="'.$prefix.$board.'" /> ';
        }
    }
}

function delunusedimages($verbose = false) {
    global $tc_db;
    $resultsboard = $tc_db->GetAll("SELECT `id`,`name` FROM `".TC_DBPREFIX."boards`");
    foreach($resultsboard AS $lineboard) {
        $imagelist = array();
        $imagethumbnaillist = array();
        $results = $tc_db->GetAll("SELECT `image`,`imagetype` FROM `".TC_DBPREFIX."posts_".$lineboard['name']."` WHERE `IS_DELETED` = 0");
        foreach($results AS $line) {
            $imagelist = array_merge($imagelist,array($line['image'].'.'.$line['imagetype']));
            $imagethumbnaillist = array_merge($imagethumbnaillist,array($line['image'].'s.'.$line['imagetype']));
        }
        $dir = './'.$lineboard['name'].'/src';
        $files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
        if (is_array($files)) { 
            foreach ($files as $image) {
                if (in_array(basename($image),$imagelist)==false) {
                    if (time()-filemtime(TC_BOARDSDIR.$lineboard['name'].'/src/'.basename($image))>120) {
                        if ($verbose==true) {
                            echo $image.' NOT IN DATABASE! Deleted.<br>';
                        }
                        unlink(TC_BOARDSDIR.$lineboard['name'].'/src/'.basename($image));
                    }
                }
            }
        }
        $dir = './'.$lineboard['name'].'/thumb';
        $files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
        if (is_array($files)) { 
            foreach ($files as $image) {
                if (in_array(basename($image),$imagethumbnaillist)==false) {
                    if (time()-filemtime(TC_BOARDSDIR.$lineboard['name'].'/thumb/'.basename($image))>120) {
                        if ($verbose==true) {
                            echo $image.' (thumb) NOT IN DATABASE! Deleted.<br>';
                        }
                        unlink(TC_BOARDSDIR.$lineboard['name'].'/thumb/'.basename($image));
                    }
                }
            }
        }
    }
    return true;
}

function delunusedimages_beta($verbose = false) {
    global $tc_db;
    $resultsboard = $tc_db->GetAll("SELECT `id`,`name` FROM `".TC_DBPREFIX."boards`");
    foreach($resultsboard AS $lineboard) {
        if ($verbose) {
            echo '<b>Looking for unused images in /'.$lineboard['name'].'/</b><br>';
        }
        $imagemd5list = array();
        $results = $tc_db->GetAll("SELECT `imagemd5` FROM `".TC_DBPREFIX."posts_".$lineboard['name']."` WHERE `IS_DELETED` = 0 AND `image` != '' AND `image` != 'removed' AND `imagemd5` != ''");
        foreach($results AS $line) {
            $imagemd5list[] = $line['imagemd5'];
        }
        $dir = './'.$lineboard['name'].'/src';
        $files = glob ("$dir/{*.jpg,*.png,*.gif,*.swf}",  GLOB_BRACE);
        if (is_array($files)) { 
            foreach ($files as $image) {
                if (in_array(md5_file(TC_BOARDSDIR.$lineboard['name'].'/src/'.basename($image)),$imagemd5list)==false) {
                    if (time()-filemtime(TC_BOARDSDIR.$lineboard['name'].'/src/'.basename($image))>120) {
                        if ($verbose==true) {
                            echo $image.' NOT IN DATABASE! Deleted.<br>';
                        }
                        unlink(TC_BOARDSDIR.$lineboard['name'].'/src/'.basename($image));
                        unlink(TC_BOARDSDIR.$lineboard['name'].'/thumb/'.substr(basename($image),0,-4).'s'.substr(basename($image),strlen(basename($image))-4));
                    }
                }
            }
        }
    }
    return true;
}

function delorphanreplies($verbose = false) {
    global $tc_db;
    $resultsboard = $tc_db->GetAll("SELECT `id`,`name` FROM `".TC_DBPREFIX."boards`");
    foreach($resultsboard AS $lineboard) {
        if ($verbose) {
            echo '<b>Looking for orphans in /'.$lineboard['name'].'/</b><br>';
        }
        $results = $tc_db->GetAll("SELECT `id`,`threadid` FROM `".TC_DBPREFIX."posts_".$lineboard['name']."` WHERE `threadid` != '0' AND `IS_DELETED` = 0");
        foreach($results AS $line) {
            $exists_rows = $tc_db->GetAll("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$lineboard['name']."` WHERE `id` = '".$line['threadid']."' AND `IS_DELETED` = 0",1);
            if ($exists_rows[0]==0) {
                $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$lineboard['name']."` SET `IS_DELETED` = '1' WHERE `id` = '".$line['id']."' LIMIT 1");
                if ($verbose) {
                    echo 'Reply #'.$line['id'].'\'s thread (#'.$line['threadid'].') does not exist!  It has been deleted.<br>';
                }
            }
        }
    }
    return true;
}

?>
