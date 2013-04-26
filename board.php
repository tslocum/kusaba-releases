<?php

/*
     Trevorchan
     Written by Trevor "tj9991" Slocum
     http://www.tj9991.com/
     tslocum@gmail.com
*/
require("config.php");
require($tc_config['rootdir']."/inc/functions.php");
require_once($tc_config['rootdir']."/inc/classes.php");
$board_class = new Board;

if (isset($_GET['board'])) {
    $_POST['board'] = $_GET['board'];
}
 
if (isset($_POST['board'])) {    
    $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}boards` WHERE `name` = '".mysql_real_escape_string($_POST['board'])."'",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            $board_id = $line['id'];
        }
        $board_class->board_id = $board_id;
        $board_class->Init();
        $glob_maxthumbwidth = config_getvalue('maxthumbwidth');
        $glob_maxthumbheight = config_getvalue('maxthumbheight');
    } else {
        echo '<meta http-equiv="refresh" content="0;url='.$tc_config['webpath'].'/index.html" />';
        die();
    }
} else {
    echo '<meta http-equiv="refresh" content="0;url='.$tc_config['webpath'].'/index.html" />';
    die();
}
removed_expired_bans();
ban_check($_SERVER['REMOTE_ADDR'],$board_class->board_dir);
$is_oekaki = false;

if ($_POST['oekaki']!='') {
    if (is_file('tcdrawings/'.$_POST['oekaki'].'.png')&&$board_class->board_type=='2') {
        $is_oekaki = true;
        $oekaki = 'tcdrawings/'.$_POST['oekaki'].'.png';
    }
}
 
if ((isset($_POST['message'])||isset($_FILES['imagefile']))||$is_oekaki||($board_class->board_type=='1'&&isset($_POST['message']))) {
    $result = mysql_query("SELECT `lastpost` FROM `{$tc_config['dbprefix']}iplist` WHERE `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."'",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            if (time()-$line['lastpost']<=$tc_config['postdelay']) {
                die($lang['wait to post again']);
            }
        }
    }
    $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '0' AND  `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."' AND `postedat` > '".(time()-60)."'",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>$tc_config['maxppm']) {
        die($lang['wait to post again']);
    }
    if (strlen($_POST['message'])>8192) {
        printf($lang['message too long'],strlen($_POST['message']),$board_class->board_messagelength);
        die();
    }
    $thread_locked = '0';
    $isreplying = false;
    $thread_replyto = '0';
    if (isset($_POST['replythread'])) {
        if ($_POST['replythread']!='0') {
            $result = mysql_query("SELECT `id`,`locked` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '0' AND `id` = '".mysql_real_escape_string($_POST['replythread'])."' AND `boardid` = '".$board_id."' AND `threadid` = '0'");
            if (mysql_num_rows($result)>0) {
                while ($line = mysql_fetch_assoc($result)) {
                    $thread_locked = $line['locked'];
                    $thread_replyto = $line['id'];
                }
                $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '0' AND `threadid` = '".mysql_real_escape_string($_POST['replythread'])."' AND `boardid` = '".$board_id."'");
                $thread_replies = mysql_num_rows($result);
                $isreplying = true;
            } else {
                die($lang['invalid thread id']);
            }
        }
    }
    $post_name = addslashes(strip_tags($_POST['name']));
    $post_email = addslashes(strip_tags($_POST['email']));
    $post_subject = addslashes(strip_tags($_POST['subject']));
    $user_authority = 0;
    if (isset($_POST['modpassword'])) {
        require($tc_config['rootdir']."/inc/encryption.php");
        $result = mysql_query("SELECT `isadmin`,`boards` FROM `{$tc_config['dbprefix']}staff` WHERE `username` = '".md5_decrypt($_POST['modpassword'],$tc_config['randomseed'])."'");
        $rows = mysql_num_rows($result);
        if ($rows>0) {
            while ($line = mysql_fetch_assoc($result)) {
                if ($line['isadmin']==1) {
                    $user_authority = 1;
                } else {
                    if (in_array($board_class->board_dir,explode('|',$line['boards']))) {
                        $user_authority = 2;
                    }
                }
            }
        }
    }
    if ($_POST['postpassword']!='') {
        $post_password = $_POST['postpassword'];
    } else {
        $post_password = '';
    }
    if ($user_authority==0) {
        if ($thread_locked==1) {
            die($lang['thread is locked']);
        }
        require_once($tc_config['rootdir'].'/inc/parse.php');
        $post_message = parse_post($_POST['message'],$board_class->board_dir,$board_class->board_type,$thread_replyto);
    } else {
        if (isset($_POST['rawhtml'])) {
            $post_message = addslashes($_POST['message']);
        } else {
            require_once($tc_config['rootdir'].'/inc/parse.php');
            $post_message = parse_post($_POST['message'],$board_class->board_dir,$board_class->board_type,$thread_replyto);
        }
        if (isset($_POST['lockonpost'])) {
            $onpost_lock = true;
        }
        if (isset($_POST['stickyonpost'])) {
            $onpost_sticky = true;
        }
    }
    if ($isreplying) {
        if (($_FILES['imagefile']['name']==''&&!$is_oekaki)&&$post_message=='') {
            die($lang['image/message required for reply']);
        }
    } else {
        if ($_FILES['imagefile']['name']==""&&!$is_oekaki&&($board_class->board_type==0||$board_class->board_type==2)) {
            die($lang['image required for thread']);
        }
    }
    if ($board_class->board_type==1&&!$isreplying&&$post_subject=='') {
        die('A subject is required to make a new thread.');
    }
    if($board_class->board_locked==0||$user_authority>0) {
        trimtopagelimit($board_class->board_dir);
        $result = mysql_query("LOCK TABLE `{$tc_config['dbprefix']}posts` WRITE;",$tc_config['dblink']);
        $imageused = false;
        $onpost_sticky = false;
        $onpost_lock = false;
        $thisimage_name = "";
        $filetype = "";
        $file_md5 = "";
        $imgWidth = 0;
        $imgHeight = 0;
        $imgWidth_thumb = 0;
        $imgHeight_thumb = 0;
        $post_id = getnextpostid($board_id);
        if (!$is_oekaki) {
            if ($board_class->board_type=='0'||$board_class->board_type=='2') {
                if ($_FILES['imagefile']['name']!="") {
                    if (strpos($_FILES['imagefile']['name'],',')!=false) {
                        mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                        die($lang['select one image']);
                    }
                    if (!file_exists($_FILES['imagefile']['tmp_name'])) {
                        echo $lang['corrupt transfer'];
                    }
                    $file=$_FILES['imagefile']['name'];
                    $file_md5 = md5_file($_FILES['imagefile']['tmp_name']);
                    if (check_md5($file_md5,$board_id)) {
                        mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
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
                    if ($_FILES['imagefile']['size']>$board_class->board_maximagesize) {
                        mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                        printf($lang['image too big'],$board_class->board_maximagesize);
                        die();
                    } else {
                           $imgSize = $_FILES['imagefile']['size'];
                        if (in_array(strtoupper(substr($filetype,1)),$board_class->board_filetypes)) {
                            if ($filetype==".jpg"||$filetype==".png"||$filetype==".gif") {
                                $thisimage_name = time().rand(1,99).$post_id;
                                if (!move_uploaded_file($_FILES['imagefile']['tmp_name'],$tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                    mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                    die($lang['could not copy']);
                                }
                                if ($_FILES['imagefile']['size']==filesize($tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                    if ($imgWidth>$glob_maxthumbwidth||$imgHeight>$glob_maxthumbheight) {
                                        if (!createthumb($tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype,$tc_config['boardsdir']."/".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$glob_maxthumbwidth,$glob_maxthumbheight)) {
                                            mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                            die($lang['could not create thumbnail']);
                                        }
                                    } else {
                                        if (!createthumb($tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype,$tc_config['boardsdir']."/".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$imgWidth,$imgHeight)) {
                                            mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                            die($lang['could not create thumbnail']);
                                        }
                                    }
                                    $imageDim_thumb = getimagesize($tc_config['boardsdir']."/".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype);
                                    $imgWidth_thumb = $imageDim_thumb[0];
                                    $imgHeight_thumb = $imageDim_thumb[1];
                                    $imageused = true;
                                } else {
                                    mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                    die($lang['file not fully uploaded']);
                                }
                            } else if ($filetype==".swf") {
                                if (!is_file($tc_config['boardsdir'].'/'.$board_class->board_dir.'/src/'.$_FILES['imagefile']['name'])) {
                                    $thisimage_name = substr($_FILES['imagefile']['name'],0,-4);
                                    if (!move_uploaded_file($_FILES['imagefile']['tmp_name'],$tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                        mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                        die($lang['could not copy'] );
                                    }
                                    if ($_FILES['imagefile']['size']==filesize($tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                        $imageused = true;
                                    } else {
                                        mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                        die($lang['corrupt transfer']);
                                    }
                                } else {
                                    mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                    die("There is already a file with that name.");
                                }
                            } else {
                                mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                                die($lang['improper filetype']);
                            }
                        } else {
                            mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                            die($lang['filetype not allowed']);
                        }
                    }
                }
            }
        } else {
            $file_md5 = md5_file($oekaki);
            $filetype = '.png';
            $imgSize = filesize($oekaki);
            $imageDim = getimagesize($oekaki);
            $imgWidth = $imageDim[0];
            $imgHeight = $imageDim[1];
            $thisimage_name = time().rand(1,99).$post_id;
            if (!copy($oekaki,$tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                die($lang['could not copy']);
            }
            if ($imgWidth>$glob_maxthumbwidth||$imgHeight>$glob_maxthumbheight) {
                if (!createthumb($oekaki,$tc_config['boardsdir']."/".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$glob_maxthumbwidth,$glob_maxthumbheight)) {
                    mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                    die($lang['could not create thumbnail']);
                }
            } else {
                if (!createthumb($oekaki,$tc_config['boardsdir']."/".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$imgWidth,$imgHeight)) {
                    mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
                    die($lang['could not create thumbnail']);
                }
            }
            $imgDim_thumb = getimagesize($tc_config['boardsdir']."/".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype);
            $imgWidth_thumb = $imgDim_thumb[0];
            $imgHeight_thumb = $imgDim_thumb[1];
            unlink($oekaki);
        }
        if ($post_id=='0') { $post_id = '1'; }
        if ($board_class->board_forcedanon=='1') {
            if ($user_authority=='0') {
                $post_name = "";
            }
        }
        //$query = "INSERT INTO `posts` ( `boardid` , `id` , `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `imagemd5` , `password` , `postedat` , `lastbumped` , `ip` , `posterauthority` , `stickied` , `locked` ) VALUES ( '".$board_id."', '".$post_id."', '".$thread_replyto."', ";
        $name = '';
        $tripcode = '';
        //Thanks for the salted tripcode script, Futabally!
        if(ereg("(#|!)(.*)",$post_name,$regs)){
            $cap = $regs[2];
            $cap = strtr($cap,"&amp;", "&");
            $cap = strtr($cap,"&#44;", ",");
            $name = ereg_replace("(#|!)(.*)","",$post_name);
            $salt = substr($cap."H.",1,2);
            $salt = ereg_replace("[^\.-z]",".",$salt);
            $salt = strtr($salt,":;<=>?@[\\]^_`","ABCDEFGabcdef"); 
            $tripcode = substr(crypt($cap,$salt),-10)."";
        } else {
            $name = $post_name;
        }
        /*if ($name!=''||$tripcode!='') {
            $query .= "'".substr($name,0,100)."', '".$tripcode."', ";
        } else {
            $query .= "'".substr($post_name,0,100)."', '', ";
        }*/
        /*if (strpos($post_name,"#")!=false||(substr($post_name,0,1)=="#"&&substr($post_name,1)!="")) {
            $tripcode_user = substr($post_name,0,strpos($post_name,"#"));
            $tripcode_password = substr($post_name,strpos($post_name,"#")+1);
            $tripcode = substr(md5($tripcode_password),0,10);
            
        } else {
            $query .= "'".substr($post_name,0,100)."', '', ";
        }*/
        $filetype_withoutdot = substr($filetype,1);
        $post_password_md5 = ($post_password=='') ? '' : md5($post_password);
        //$query .= "'".substr($post_email,0,100)."', '".substr($post_subject,0,100)."', '".$post_message."', '".mysql_real_escape_string($thisimage_name)."', '".$filetype_withoutdot."', '".$file_md5."', '".$post_password_md5."' , '".time()."', '".time()."' , '".$_SERVER['REMOTE_ADDR']."' , '".$user_authority."'";
        if ($onpost_sticky==true) {
            if ($thread_replyto=='0') {
                $sticky = '1';
            } else {
                mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `stickied` = '1' WHERE `boardid` = '".$board_id."' AND `id` = '".$thread_replyto."'",$tc_config['dblink']);
                $sticky = '0';
            }
        } else {
            $sticky = '0';
        }
        if ($onpost_lock==true) {
            if ($thread_replyto=='0') {
                $lock = '1';
            } else {
                mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `locked` = '1' WHERE `boardid` = '".$board_id."' AND `id` = '".$thread_replyto."'",$tc_config['dblink']);
                $lock = '0';
            }
        } else {
            $lock = '0';
        }
        //$query .= " );";
        $post_class = new Post($post_id,$board_class->board_id,true);
        $post_class->Insert($thread_replyto,substr($name,0,100),$tripcode,substr($post_email,0,100),substr($post_subject,0,100),$post_message,mysql_real_escape_string($thisimage_name),$filetype_withoutdot,$file_md5,$imgWidth,$imgHeight,$imgSize,$imgWidth_thumb,$imgHeight_thumb,$post_password_md5,time(),time(),$_SERVER['REMOTE_ADDR'],$user_authority,$sticky,$lock);
        if ((file_exists($tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype)&&file_exists($tc_config['boardsdir']."/".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype))||($filetype==".swf"&&file_exists($tc_config['boardsdir']."/".$board_class->board_dir."/src/".$thisimage_name.$filetype))||$imageused==false) {
            mysql_query($query,$tc_config['dblink']);
            if (isset($_POST['name'])) {
                setcookie("name",urldecode($_POST['name']),time()+31556926,"/");
            }
            if ($_POST['email']!="sage"&&$_POST['email']!="age") {
                setcookie("email",urldecode($_POST['email']),time()+31556926,"/");
            }
            setcookie("postpassword",urldecode($_POST['postpassword']),time()+31556926,"/");
        } else {
            mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
            die($lang['could not copy']);
        }
        mysql_query("UNLOCK TABLES;",$tc_config['dblink']);
        if ($thread_replyto!="0"&&$post_email!="sage") {
            if ($thread_replies<=$board_class->board_maxreplies) {
                mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `lastbumped` = '".time()."' WHERE `id` = '".$thread_replyto."' AND `boardid` = '".$board_id."'",$tc_config['dblink']);
            }
        }
        $result = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}iplist` WHERE `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."'",$tc_config['dblink']);
        $rows = mysql_num_rows($result);
        if ($rows==0) {
            mysql_query("INSERT INTO `{$tc_config['dbprefix']}iplist` ( `ip` , `ipmd5` , `lastpost` ) VALUES ( '".md5_encrypt($_SERVER['REMOTE_ADDR'],$tc_config['randomseed'])."' , '".md5($_SERVER['REMOTE_ADDR'])."' , '".time()."' )",$tc_config['dblink']);
        } else {
            mysql_query("UPDATE `{$tc_config['dbprefix']}iplist` SET `lastpost` = '".time()."' WHERE `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."'",$tc_config['dblink']);
        }
        $board_class->board_id = $board_id;
        $board_class->Init();
        if ($thread_replyto=="0") {
            $board_class->RegenerateThread($post_id);
        } else {
            $board_class->RegenerateThread($thread_replyto);
        }
        $board_class->RegeneratePages();
    } else {
        die($lang['board is locked']);
    }
} else if (isset($_POST['delete'])&&isset($_POST['postpassword'])) {
    $post_class = new Post(mysql_real_escape_string($_POST['delete']),$board_id);
    if (isset($_POST['reportpost'])) {
        if ($board_class->board_enablereporting==1) {
            $post_reported = $post_class->post_isreported;
            if ($post_reported==='cleared') {
                echo 'That post has been cleared as not requiring any deletion.';
            } elseif ($post_reported) {
                echo 'That post is already in the report list.';
            } else {
                if ($post_class->Report()) {
                    echo 'Post successfully reported.';
                    echo '<meta http-equiv="refresh" content="1;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/board.html" />';
                } else {
                    echo 'Unable to report post.  Please go back and try again.';
                }
            }
        } else {
            echo 'This board does not allow post reporting!';
        }
    } else {
        if ($_POST['postpassword']!="") {
            if (md5($_POST['postpassword'])==$post_class->post_password) {
                if (isset($_POST['fileonly'])) {
                    if ($post_class->post_image!=''&&$post_class->post_image!='removed') {
                        $post_class->DeleteImage();
                        $board_class->RegeneratePages();
                        if ($post_class->post_threadid!='0') {
                            $board_class->RegenerateThread($post_class->post_threadid);
                        }
                        echo $lang['image successfully deleted'];
                        echo '<meta http-equiv="refresh" content="1;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/board.html" />';
                    } else {
                            echo $lang['post doesnt have message'];
                    }
                } else {
                    if ($post_class->Delete()) {
                        $board_class->RegeneratePages();
                        if ($post_class->post_threadid!='0') {
                            $board_class->RegenerateThread($post_class->post_threadid);
                        }
                        echo $lang['post successfully deleted'];
                        echo '<meta http-equiv="refresh" content="1;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/board.html" />';
                    } else {
                        echo $lang['error deleting post'];
                    }
                }
            } else {
                    echo $lang['incorrect password'];
            }
        } else {
            echo '<meta http-equiv="refresh" content="0;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/board.html" />';
        }
    }
    die();
} else if (isset($_GET['postoek'])) {
    ob_start();
    $executiontime_start = microtime_float();
    echo $board_class->PageHeader();
    echo $board_class->Postbox($_GET['replyto'],$_GET['postoek']);
    echo '<div style="text-align: center;">Your Image:<br /><img src="'.$tc_config['boardsfolder'].'/tcdrawings/'.$_GET['postoek'].'.png"></div>';
    $executiontime_stop = microtime_float();
    echo $board_class->Footer(false,($executiontime_stop-$executiontime_start));
    $page = ob_get_clean();
    echo $page;
    die();
} else {
    echo '<meta http-equiv="refresh" content="0;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/board.html" />';
    die();
}

if ($thread_replyto=="0") {
    print($lang['thread successfully posted']);
} else {
    print($lang['reply successfully posted']);
}
if ($board_class->board_redirecttothread=='1') {
    if ($thread_replyto=="0") {
        echo '<meta http-equiv="refresh" content="1;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/res/'.$post_id.'.html" />';
    } else {
        echo '<meta http-equiv="refresh" content="1;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/res/'.$thread_replyto.'.html" />';
    }
} else {
    echo '<meta http-equiv="refresh" content="1;url='.$tc_config['boardspath'].'/'.$board_class->board_dir.'/" />';
}

die(); //Just in case...
?>
