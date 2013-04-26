<?php
/*
* +------------------------------------------------------------------------------+
* Trevorchan - http://www.trevorchan.org/
* Written by Trevor "tj9991" Slocum
* http://www.tj9991.com/
* tslocum@gmail.com
* +------------------------------------------------------------------------------+
* Board operations which are available to all users
* +------------------------------------------------------------------------------+
* This file serves the purpose of providing functionality for all users of the
* boards.  This includes: posting, reporting posts, and deleting posts.
* +------------------------------------------------------------------------------+
*/
session_start();
require("config.php");
require(TC_ROOTDIR.'inc/functions.php');
require_once(TC_ROOTDIR.'inc/operations.functions.php');
require_once(TC_ROOTDIR.'inc/classes.php');

if ($_POST['email']!='') {
    die("Spam bot detected");
}

if (isset($_GET['board'])) {
    $_POST['board'] = $_GET['board'];
}

if (isset($_POST['board'])) {
    $results = $tc_db->GetAll("SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($_POST['board'])."'");
    if (count($results)>0) {
        foreach($results AS $line) {
            $board_name = $line['name'];
        }
        $board_class = new Board($board_name);
        $glob_maxthumbwidth = config_getvalue('maxthumbwidth');
        $glob_maxthumbheight = config_getvalue('maxthumbheight');
    } else {
        echo '<meta http-equiv="refresh" content="0;url='.TC_WEBPATH.'/index.html" />';
        die();
    }
} else {
    echo '<meta http-equiv="refresh" content="0;url='.TC_WEBPATH.'/index.html" />';
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
    $results = $tc_db->GetAll("SELECT `lastpost` FROM `".TC_DBPREFIX."iplist` WHERE `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."'");
    if (count($results)>0) {
        foreach($results AS $line) {
            if (time()-$line['lastpost']<=TC_POSTDELAY) {
                die($lang['wait to post again']);
            }
        }
    }
    $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$board_class->board_dir."` WHERE `IS_DELETED` = '0' AND  `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."' AND `postedat` > '".(time()-60)."'");
    if (count($results)>TC_MAXPPM) {
        die($lang['wait to post again']);
    }
    if (strlen($_POST['message'])>$board_class->board_messagelength) {
        printf($lang['message too long'],strlen($_POST['message']),$board_class->board_messagelength);
        die();
    }
    if ($board_class->board_enablecaptcha==1) {
        if (!(($_SESSION['security_code'] == strtolower($_POST['captcha'])) && (!empty($_SESSION['security_code'])))) {
            die("Incorrect captcha entered.");
        }
    }
    $thread_locked = '0';
    $isreplying = false;
    $thread_replyto = '0';
    $imageused = false;
    $onpost_sticky = false;
    $onpost_lock = false;
    if (isset($_POST['replythread'])) {
        if ($_POST['replythread']!='0') {
            $results = $tc_db->GetAll("SELECT `id`,`locked` FROM `".TC_DBPREFIX."posts_".$board_class->board_dir."` WHERE `IS_DELETED` = '0' AND `id` = '".mysql_real_escape_string($_POST['replythread'])."' AND `threadid` = '0'");
            if (count($results)>0) {
                foreach($results AS $line) {
                    $thread_locked = $line['locked'];
                    $thread_replyto = $line['id'];
                }
                $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$board_class->board_dir."` WHERE `IS_DELETED` = '0' AND `threadid` = '".mysql_real_escape_string($_POST['replythread'])."'");
                $thread_replies = count($results);
                $isreplying = true;
            } else {
                die($lang['invalid thread id']);
            }
        }
    }
    $post_name = addslashes(htmlentities($_POST['name'],ENT_QUOTES));
    $post_email = addslashes(str_replace('"','',strip_tags($_POST['em']))); /* Feb 22nd 07: Bug was found which allowed users to inject attributes by placing them beginning with a " in the mail field. */
    $post_subject = addslashes(htmlentities($_POST['subject'],ENT_QUOTES));
    $user_authority = 0;
    if (isset($_POST['modpassword'])) {
        require(TC_ROOTDIR."inc/encryption.php");
        $results = $tc_db->GetAll("SELECT `isadmin`,`boards` FROM `".TC_DBPREFIX."staff` WHERE `username` = '".md5_decrypt($_POST['modpassword'],TC_RANDOMSEED)."'");
        if (count($results)>0) {
            foreach($results AS $line) {
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
    $post_displaystaffstatus = false;
    $badlinks = file(TC_ROOTDIR.'spam.txt');
    foreach ($badlinks AS $badlink) {
        if (strpos($_POST['message'],substr($badlink,0,-1))!=false) {
            //They included a blacklisted link in their post.  Ban them for an hour
            ban_user($_SERVER['REMOTE_ADDR'],'board.php',1,3600,'','Posting a blacklisted link.');
            die();
        }
    }
    if ($user_authority==0) {
        if ($thread_locked==1) {
            die($lang['thread is locked']);
        }
        require_once(TC_ROOTDIR.'inc/parse.php');
        $post_message = parse_post($_POST['message'],$board_class->board_dir,$board_class->board_type,$thread_replyto);
    } else {
        if (isset($_POST['displaystaffstatus'])) {
            $post_displaystaffstatus = true;
        }
        if (isset($_POST['rawhtml'])) {
            $post_message = addslashes($_POST['message']);
        } else {
            require_once(TC_ROOTDIR.'inc/parse.php');
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
            if ($_POST['embed']==''&&$board_class->board_uploadtype!=1) {
                die($lang['image required for thread'].'<br>Or, if supported, an embed ID.');
            }
        }
    }
    if ($board_class->board_type==1&&!$isreplying&&$post_subject=='') {
        die('A subject is required to make a new thread.');
    }
    if($board_class->board_locked==0||$user_authority>0) {
        $board_class->TrimToPageLimit();
        $thisimage_name = "";
        $filetype = "";
        $file_md5 = "";
        $imgWidth = 0;
        $imgHeight = 0;
        $imgWidth_thumb = 0;
        $imgHeight_thumb = 0;
        if (!$is_oekaki) {
            if ($board_class->board_type==0||$board_class->board_type==2) {
                if ($_FILES['imagefile']['name']!='') {
                    if (strpos($_FILES['imagefile']['name'],',')!=false) {

                        die($lang['select one image']);
                    }
                    if (!file_exists($_FILES['imagefile']['tmp_name'])) {
                        echo $lang['corrupt transfer'];
                    }
                    $file=$_FILES['imagefile']['name'];
                    $file_md5 = md5_file($_FILES['imagefile']['tmp_name']);
                    $exists_thread = check_md5($file_md5,$board_class->board_dir);
                    if (is_array($exists_thread)) {
                        die($lang['duplicate file'].'<br><br>Already posted <a href="'.TC_BOARDSPATH.'/'.$board_class->board_dir.'/res/'.$exists_thread[0].'.html#'.$exists_thread[1].'">here</a>.');
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
                        printf($lang['image too big'],$board_class->board_maximagesize);
                        die();
                    } else {
                        $imgSize = $_FILES['imagefile']['size'];
                        if (in_array(strtoupper(substr($filetype,1)),$board_class->board_filetypes)) {
                            if ($filetype==".jpg"||$filetype==".png"||$filetype==".gif") {
                                $thisimage_name = time().mt_rand(1,99);
                                if (!move_uploaded_file($_FILES['imagefile']['tmp_name'],TC_BOARDSDIR.$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                    die($lang['could not copy']);
                                }
                                if ($_FILES['imagefile']['size']==filesize(TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                    if ($imgWidth>$glob_maxthumbwidth||$imgHeight>$glob_maxthumbheight) {
                                        if (!createthumb(TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype,TC_BOARDSDIR."".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$glob_maxthumbwidth,$glob_maxthumbheight)) {
                                            die($lang['could not create thumbnail']);
                                        }
                                    } else {
                                        if (!createthumb(TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype,TC_BOARDSDIR."".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$imgWidth,$imgHeight)) {
                                            die($lang['could not create thumbnail']);
                                        }
                                    }
                                    $imageDim_thumb = getimagesize(TC_BOARDSDIR."".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype);
                                    $imgWidth_thumb = $imageDim_thumb[0];
                                    $imgHeight_thumb = $imageDim_thumb[1];
                                    $imageused = true;
                                } else {
                                    die($lang['file not fully uploaded']);
                                }
                            } else if ($filetype==".swf") {
                                if (!is_file(TC_BOARDSDIR.''.$board_class->board_dir.'/src/'.$_FILES['imagefile']['name'])) {
                                    $thisimage_name = substr($_FILES['imagefile']['name'],0,-4);
                                    $thisimage_name = str_replace(' ','_',$thisimage_name);
                                    $thisimage_name = str_replace('#','(number)',$thisimage_name);
                                    $thisimage_name = str_replace('@','(at)',$thisimage_name);
                                    $thisimage_name = str_replace('/','(fwslash)',$thisimage_name);
                                    $thisimage_name = str_replace('\\','(bkslash)',$thisimage_name);
                                    if (!move_uploaded_file($_FILES['imagefile']['tmp_name'],TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                        die($lang['could not copy'] );
                                    }
                                    if ($_FILES['imagefile']['size']==filesize(TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                                        $imageused = true;
                                    } else {
                                        die($lang['corrupt transfer']);
                                    }
                                } else {
                                    die("There is already a file with that name.");
                                }
                            } else {
                                die($lang['improper filetype']);
                            }
                        } else {
                            die($lang['filetype not allowed']);
                        }
                    }
                } elseif ($_POST['embed']!='') {
                    require_once(TC_ROOTDIR.'inc/checklink.php');
                    $video_id = $_POST['embed'];
                    $thisimage_name = $video_id;
                    if ($video_id!=''&&strpos($video_id,'@')==false&&strpos($video_id,'&')==false) {
                        if ($_POST['embedtype']=='youtube') {
                            $videourl_start = 'http://www.youtube.com/watch?v=';
                            $filetype = '.you';
                        } elseif ($_POST['embedtype']=='google') {
                            $videourl_start = 'http://video.google.com/videoplay?docid=';
                            $filetype = '.goo';
                        } else {
                            die('Invalid video type.');
                        }
                        $results = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$board_class->board_dir."` WHERE `image` = '".mysql_real_escape_string($video_id)."' AND `IS_DELETED` = 0");
                        if ($results[0]==0) {
                            $video_check = check_link($videourl_start.$video_id);
                            switch ($video_check[1]) {
                                case 404:
                                    die('Unable to connect to: '.$videourl_start.$video_id);
                                    break;
                                case 303:
                                    die('Invalid video ID.');
                                    break;
                                case 200:
                                    //Continue
                                    break;
                                default:
                                    die('Invalid response code: '.$video_check[1]);
                                    break;
                            }
                        } else {
                            $results = $tc_db->SelectLimit("SELECT `id`,`threadid` FROM `".TC_DBPREFIX."posts_".$board_class->board_dir."` WHERE `image` = '".mysql_real_escape_string($video_id)."' AND `IS_DELETED` = 0",1);
                            foreach ($results AS $line) {
                                $real_threadid = ($line[1]==0) ? $line[0] : $line[1];
                                echo 'That video ID has already been posted <a href="'.TC_BOARDSFOLDER.'/'.$board_class->board_dir.'/res/'.$real_threadid.'.html#'.$line[1].'">here</a>';
                            }
                        }
                    } else {
                        die('Invalid ID');
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
            $thisimage_name = time().mt_rand(1,99);
            if (!copy($oekaki,TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype)) {
                die($lang['could not copy']);
            }
            if ($imgWidth>$glob_maxthumbwidth||$imgHeight>$glob_maxthumbheight) {
                if (!createthumb($oekaki,TC_BOARDSDIR."".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$glob_maxthumbwidth,$glob_maxthumbheight)) {
                    die($lang['could not create thumbnail']);
                }
            } else {
                if (!createthumb($oekaki,TC_BOARDSDIR."".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype,$imgWidth,$imgHeight)) {
                    die($lang['could not create thumbnail']);
                }
            }
            $imgDim_thumb = getimagesize(TC_BOARDSDIR."".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype);
            $imgWidth_thumb = $imgDim_thumb[0];
            $imgHeight_thumb = $imgDim_thumb[1];
            unlink($oekaki);
        }
        if ($board_class->board_forcedanon=='1') {
            if ($user_authority=='0') {
                $post_name = "";
            }
        }

        //Thanks for the salted tripcode script, Futabally!
        $nameandtripcode = calculateNameAndTripcode($post_name);
        if (is_array($nameandtripcode)) {
            $name = $nameandtripcode[0];
            $tripcode = $nameandtripcode[1];
        } else {
            $name = $post_name;
            $tripcode = '';
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
                $result = $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$board_class->board_dir."` SET `stickied` = '1' WHERE `id` = '".$thread_replyto."'");
                $sticky = '0';
            }
        } else {
            $sticky = '0';
        }
        if ($onpost_lock==true) {
            if ($thread_replyto=='0') {
                $lock = '1';
            } else {
                $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$board_class->board_dir."` SET `locked` = '1' WHERE `id` = '".$thread_replyto."'");
                $lock = '0';
            }
        } else {
            $lock = '0';
        }
        if (!$post_displaystaffstatus&&$user_authority>0) {
            $user_authority_display = 0;
        } elseif ($user_authority>0) {
            $user_authority_display = $user_authority;
        } else {
            $user_authority_display = 0;
        }
        //$query .= " );";
        if ((file_exists(TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype)&&file_exists(TC_BOARDSDIR."".$board_class->board_dir."/thumb/".$thisimage_name.'s'.$filetype))||($filetype==".swf"&&file_exists(TC_BOARDSDIR."".$board_class->board_dir."/src/".$thisimage_name.$filetype))||$imageused==false) {
            $post_class = new Post(0,$board_class->board_dir,true);
            $post_id = $post_class->Insert($thread_replyto,substr($name,0,100),$tripcode,substr($post_email,0,100),substr($post_subject,0,100),$post_message,mysql_real_escape_string($thisimage_name),$filetype_withoutdot,$file_md5,$imgWidth,$imgHeight,$imgSize,$imgWidth_thumb,$imgHeight_thumb,$post_password_md5,time(),time(),$_SERVER['REMOTE_ADDR'],$user_authority_display,$sticky,$lock);
            if ($thread_replyto!='0') {
                flushthreadrepliescache($thread_replyto,$board_class->board_dir);
            }
            if (isset($_POST['name'])) {
                setcookie("name",urldecode($_POST['name']),time()+31556926,"/");
            }
            if (strtolower($_POST['em'])!="sage"&&strtolower($_POST['em'])!="age") {
                setcookie("email",urldecode($_POST['em']),time()+31556926,"/");
            }
            setcookie("postpassword",urldecode($_POST['postpassword']),time()+31556926,"/");
        } else {
            die($lang['could not copy']);
        }
        if ($thread_replyto!="0"&&$post_email!="sage") {
            if ($thread_replies<=$board_class->board_maxreplies) {
                $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$board_class->board_dir."` SET `lastbumped` = '".time()."' WHERE `id` = '".$thread_replyto."'");
            }
        }
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."iplist` WHERE `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."'");
        if (count($results)==0) {
            $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."iplist` ( `ip` , `ipmd5` , `lastpost` ) VALUES ( '".md5_encrypt($_SERVER['REMOTE_ADDR'],TC_RANDOMSEED)."' , '".md5($_SERVER['REMOTE_ADDR'])."' , '".time()."' )");
        } else {
            $tc_db->Execute("UPDATE `".TC_DBPREFIX."iplist` SET `lastpost` = '".time()."' WHERE `ipmd5` = '".md5($_SERVER['REMOTE_ADDR'])."'");
        }
        $board_class = new Board($board_class->board_dir);
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
    $post_class = new Post(mysql_real_escape_string($_POST['delete']),$board_class->board_dir);
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
                    echo '<meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/board.html" />';
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
                        echo '<meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/board.html" />';
                    } else {
                            echo $lang['post doesnt have message'];
                    }
                } else {
                    if ($post_class->Delete()) {
                        if ($post_class->post_threadid!='0') {
                            flushthreadrepliescache($post_class->post_threadid,$board_class->board_dir);
                            $board_class->RegenerateThread($post_class->post_threadid);
                        }
                        $board_class->RegeneratePages();
                        echo $lang['post successfully deleted'];
                        echo '<meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/board.html" />';
                    } else {
                        echo $lang['error deleting post'];
                    }
                }
            } else {
                    echo $lang['incorrect password'];
            }
        } else {
            echo '<meta http-equiv="refresh" content="0;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/board.html" />';
        }
    }
    die();
} else if (isset($_GET['postoek'])) {
    ob_start();
    $executiontime_start = microtime_float();
    echo $board_class->PageHeader();
    echo $board_class->Postbox($_GET['replyto'],$_GET['postoek']);
    echo '<div style="text-align: center;">Your Image:<br /><img src="'.TC_BOARDSFOLDER.'tcdrawings/'.$_GET['postoek'].'.png"></div>';
    $executiontime_stop = microtime_float();
    echo $board_class->Footer(false,($executiontime_stop-$executiontime_start));
    $page = ob_get_clean();
    echo $page;
    die();
} else {
    echo '<meta http-equiv="refresh" content="0;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/board.html" />';
    die();
}

if ($thread_replyto=="0") {
    print($lang['thread successfully posted']);
} else {
    print($lang['reply successfully posted']);
}

if ($board_class->board_redirecttothread=='1') {
    if ($thread_replyto=="0") {
        echo '<meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/res/'.$post_id.'.html" />';
    } else {
        echo '<meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/res/'.$thread_replyto.'.html" />';
    }
} else {
    echo '<meta http-equiv="refresh" content="1;url='.TC_BOARDSPATH.'/'.$board_class->board_dir.'/" />';
}
?>