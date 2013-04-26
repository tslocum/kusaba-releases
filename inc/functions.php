<?php

//For PHP4 installations
if (!function_exists("file_get_contents")) {
    function file_get_contents($file) {
        return implode('',file($file));
    }
}
if (!function_exists("file_put_contents")) {
    define('FILE_APPEND', 1);
    function file_put_contents($n, $d, $flag = false) {
       $mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
       $f = @fopen($n, $mode);
       if ($f === false) {
           return 0;
       } else {
           if (is_array($d)) $d = implode($d);
           $bytes_written = fwrite($f, $d);
           fclose($f);
           return $bytes_written;
       }
    }
}
if(!function_exists('str_ireplace')) {
    function str_ireplace( $find, $replace, $string ) {
        // Case-insensitive str_replace()

        $parts = explode( strtolower($find), strtolower($string) );

        $pos = 0;

        foreach( $parts as $key=>$part ){
            $parts[ $key ] = substr($string, $pos, strlen($part));
            $pos += strlen($part) + strlen($find);
        }

        return( join( $replace, $parts ) );
    }
}

function microtime_float()
{
   list($usec, $sec) = explode(" ", microtime());
   return ((float)$usec + (float)$sec);
}

// <3 coda for this wonderful snippet
// print $contents to $filename by using a temporary file and renaming it
function print_page($filename,$contents,$board) {
    global $tc_config;
    $tempfile = tempnam($tc_config['boardsdir']."/".$board."/res", "tmp"); //note: THIS actually creates the file
    $fp = fopen($tempfile, "w");
    fwrite($fp, $contents);
    fclose($fp);
    rename($tempfile, $filename);
    chmod($filename, 0664); //it was created 0600
}
//Thanks milianw - php.net
function closeOpenTags($html){
    //put all opened tags into an array
    preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU",$html,$result);
    $openedtags=$result[1];

    //put all closed tags into an array
    preg_match_all("#</([a-z]+)>#iU",$html,$result);
    $closedtags=$result[1];
    $len_opened = count($openedtags);
    // all tags are closed
    if(count($closedtags) == $len_opened){
        return $html;
    }
    $openedtags = array_reverse($openedtags);
    //close tags
    for($i=0;$i<$len_opened;$i++) {
        if ($openedtags[$i]!='br') {
            if (!in_array($openedtags[$i],$closedtags)){
                $html .= '</'.$openedtags[$i].'>';
            } else {
                unset($closedtags[array_search($openedtags[$i],$closedtags)]);
            }
        }
    }
    return $html;
}

//Manage
function moderator_deleteandbanlinks($boardsfolder,$board_dir,$post_id, $is_thread) {
    global $tc_config;
    require_once($tc_config['rootdir']."/inc/encryption.php");
    $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}boards` WHERE `name` = '".$board_dir."'",$tc_config['dblink']);
    while ($line = mysql_fetch_assoc($result)) {
        $board_id = $line['id'];
    }
    $result = mysql_query("SELECT `ip` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND  `IS_DELETED` = 0 AND `boardid` = '".$board_id."' AND `id` = '".$post_id."'",$tc_config['dblink']);
    while ($line = mysql_fetch_assoc($result)) {
        $poster_ip = md5_decrypt($line['ip'],$tc_config['randomseed']);
    }
    if ($is_thread==true) {
        $post_threadorpost = 'thread';
    } else {
        $post_threadorpost = 'post';
    }
    return "<script type=\"text/javascript\">
    if (getCookie(\"tcmod\")==\"yes\") {
        document.write('&nbsp;&#91;<a href=\"".$tc_config['boardsfolder']."/manage.php?action=delposts&boarddir=".$board_dir."&del".$post_threadorpost."id=".$post_id."\" title=\"Delete\" onclick=\"return confirm(\\'Are you sure you want to delete this ".$post_threadorpost."?\\');\">D<\/a>&nbsp;<a href=\"".$tc_config['boardsfolder']."/manage.php?action=delposts&boarddir=".$board_dir."&del".$post_threadorpost."id=".$post_id."&postid=".$post_id."\" title=\"Delete &amp; Ban\" onclick=\"return confirm(\\'Are you sure you want to delete and ban the poster of this ".$post_threadorpost."?\\');\">&amp;<\/a>&nbsp;<a href=\"".$tc_config['boardsfolder']."/manage.php?action=bans&banboard=".$board_dir."&banpost=".$post_id."\" title=\"Ban\">B<\/a>&#93;');
    }
    </script>";
}


//Configuration
function config_getvalue($key) {
    global $tc_config;
    $result = mysql_query("SELECT `value` FROM `{$tc_config['dbprefix']}config` WHERE `key` = '$key' LIMIT 1",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    echo mysql_error($tc_config['dblink']);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            return $line['value'];
        }
    } else {
        return false;
    }
}
function config_setvalue($key,$value) {
    global $tc_config;
    $result = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}config` WHERE `key` = '".$key."' LIMIT 1",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        mysql_query("UPDATE `config` SET `value` = '".$value."' WHERE `key` = '".$key."'",$tc_config['dblink']);
        return true;
    } else {
        return false;
    }
}

function print_stylesheets($prefered_stylesheet = 'Burichan') {
    global $tc_config;
    if ($prefered_stylesheet!='Burichan'&&$prefered_stylesheet!='Futaba'&&$prefered_stylesheet!='Gurochan'&&$prefered_stylesheet!='Photon'&&$prefered_stylesheet!='Fuhrerchan') {
        $prefered_stylesheet = 'Burichan';
    }
    if ($prefered_stylesheet=='Burichan') {
        return '<link rel="stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/burichan.css" title="Burichan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/futaba.css" title="Futaba">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/gurochan.css" title="Gurochan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/photon.css" title="Photon">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/fuhrerchan.css" title="Fuhrerchan">';
    } else if ($prefered_stylesheet=='Futaba') {
        return '<link rel="stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/futaba.css" title="Futaba">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/burichan.css" title="Burichan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/gurochan.css" title="Gurochan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/photon.css" title="Photon">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/fuhrerchan.css" title="Fuhrerchan">';
    } else if ($prefered_stylesheet=='Gurochan') {
        return '<link rel="stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/gurochan.css" title="Gurochan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/burichan.css" title="Burichan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/futaba.css" title="Futaba">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/photon.css" title="Photon">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/fuhrerchan.css" title="Fuhrerchan">';
    } else if ($prefered_stylesheet=='Photon') {
        return '<link rel="stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/photon.css" title="Photon">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/burichan.css" title="Burichan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/futaba.css" title="Futaba">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/gurochan.css" title="Gurochan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/fuhrerchan.css" title="Fuhrerchan">';
    } else if ($prefered_stylesheet=='Fuhrerchan') {
        return '<link rel="stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/fuhrerchan.css" title="Fuhrerchan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/burichan.css" title="Burichan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/futaba.css" title="Futaba">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/gurochan.css" title="Gurochan">
    <link rel="alternate stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/photon.css" title="Photon">';
    }
}

function removeDir($path) {
   // Add trailing slash to $path if one is not there
   /*if (substr($path, -1, 1) != "/") {
       $path .= "/";
   }*/

   $normal_files = glob($path . "*");
   $hidden_files = glob($path . "\.?*");
   $all_files = array_merge($normal_files, $hidden_files);

   foreach ($all_files as $file) {
       # Skip pseudo links to current and parent dirs (./ and ../).
       if (preg_match("/(\.|\.\.)$/", $file))
       {
               continue;
       }

       if (is_file($file) === TRUE) {
           // Remove each file in this Directory
           unlink($file);
           echo "Removed File: " . $file . "<br>";
       }
       else if (is_dir($file) === TRUE) {
           // If this Directory contains a Subdirectory, run this Function on it
           removeDir($file);
       }
   }
   // Remove Directory once Files have been removed (If Exists)
   if (is_dir($path) === TRUE) {
       rmdir($path);
       echo "<br>Removed Directory: " . $path . "<br><br>";
   }
}
function remove_board($dir){
    global $tc_config;
    $dir = '/'.$dir;
    /*if (removeDir($tc_config['rootdir'].$tc_config['boardsfolder']."/".$dir)) {
        echo 'Directory removed!';
    } else {
        echo 'Error.';
    }*/
    
    define('loc1', $tc_config['boardsdir'], true);

    if(!isset($GLOBALS['remerror'])) {
        $GLOBALS['remerror'] = false;
    }

  if($handle = opendir(loc1.$dir)){          // if the folder exploration is sucsessful, continue
   while (false !== ($file = readdir($handle))){ // as long as storing the next file to $file is successful, continue
     $path = $dir . '/' . $file;

     if(is_file(loc1 . $path)){
       if(!unlink(loc1 . $path)){
         echo '<u><font color="red">"' . $path . '" could not be deleted. This may be due to a permissions problem.</u><br>Directory cannot be deleted until all files are deleted.</font><br>';
         $GLOBALS['remerror'] = true;
         return false;
       }
     } else
     if(is_dir(loc1 . $path) && substr($file, 0, 1) != '.'){
       remove_board($path);
       @rmdir(loc1 . $path);
     }
   }
   closedir($handle); // close the folder exploration
  }

  if(!$GLOBALS['remerror']) // if no errors occured, delete the now empty directory.
   if(!rmdir(loc1 . $dir)){
     echo '<b><font color="red">Could not remove directory "' . $dir . '". This may be due to a permissions problem.</font></b><br>'.$GLOBALS['remerror'];
     return false;
   } else
     return true;

  return false;
}

function trimtopagelimit($board) { //The name is deceiving, this function will trim the threads to the page limit AND delete posts which are older than limited
    global $tc_config;
    $result = mysql_query("SELECT `id`,`type`,`name`,`desc`,`maxage`,`maxpages`,`locked`,`redirecttothread`,`forcedanon` FROM `{$tc_config['dbprefix']}boards` WHERE `name` = '".$board."' LIMIT 1",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            $board_id = $line['id'];
            $board_type = $line['type'];
            $board_dir = $line['name'];
            $board_desc = $line['desc'];
            $board_maxage = $line['maxage'];
            $board_maxpages = $line['maxpages'];
            $board_locked = $line['locked'];
            $board_redirecttothread = $line['redirecttothread'];
            $board_forcedanon = $line['forcedanon'];
        }
        if ($board_maxage!='0') { //If the maximum thread age setting is not zero (do not delete old threads), find posts which are older than the limit, and delete them
            $result = mysql_query("SELECT `id`,`postedat` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND  `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0'",$tc_config['dblink']); //Fetch all non-deleted thread-starting posts from the selected board
            while ($line = mysql_fetch_assoc($result)) {
                if ($line['postedat']+($board_maxage*3600)<time()) { //If it is older than the limit
                        $post_class = new Post($line['id'],$board_id);
                    $post_class->Delete();
                }
            }
        }
        if ($board_maxpages!='0') { //If the maximum pages setting is not zero (do not limit pages), find posts which are over the limit, and delete them
            $result = mysql_query("SELECT `id`,`stickied` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND  `threadid` = '0' AND `boardid` = '".$board_id."'",$tc_config['dblink']); //Fetch all non-deleted thread-starting posts from the selected board
            $rows = mysql_num_rows($result);
            if (calculatenumpages($board_type,$rows)>=$board_maxpages) {
                $board_maxthreads = $board_maxpages * config_getvalue('numthreadsdisplayed');
                $numthreadsover = $rows - $board_maxthreads;
                if ($numthreadsover>0) {
                    $resultpost = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND  `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0' ORDER BY `lastbumped` ASC LIMIT ".$numthreadsover.'',$tc_config['dblink']);
                    while ($linepost = mysql_fetch_assoc($resultpost)) {
                        $oldthread_id = $linepost['id'];
                        $oldthread_stickied = $linepost['stickied'];
                        if ($oldthread_stickied=='0') {
                            $post_class = new Post($oldthread_id,$board_id);
                            $post_class->Delete();
                        }
                    }
                }
            }
        }
    } else {
        die("Invalid board.");
    }
}

function display_numuniqueposts($board = '') {
    global $tc_config;
    if ($board!='') {
        $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}boards` WHERE `name` = '".mysql_real_escape_string($board)."' LIMIT 1",$tc_config['dblink']);
        $rows = mysql_num_rows($result);
        if ($rows>0) {
            while ($line = mysql_fetch_assoc($result)) {
                $board_id = $line['id'];
            }
            $result = mysql_query("SELECT COUNT(DISTINCT `ipmd5`) FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND `boardid` = $board_id",$tc_config['dblink']);
            $numuniqueposts = mysql_fetch_row($result);
            return $numuniqueposts[0];
        } else {
            return '0';
        }
    } else {
        $result = mysql_query("SELECT COUNT(DISTINCT `ipmd5`) FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0",$tc_config['dblink']);
        $numuniqueposts = mysql_fetch_row($result);
        return $numuniqueposts[0];
    }
}

//Users
function ban_user($ip,$modname,$globalban,$duration,$boards,$reason,$type=0,$allowread=1) { //Add a ip/ip range ban
    global $tc_config;
    require_once($tc_config['rootdir']."/inc/encryption.php");
    $result = mysql_query("SELECT `ip` FROM `{$tc_config['dbprefix']}banlist` WHERE `type` = '".$type."' AND `ipmd5` = '".md5($ip)."' LIMIT 1",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows==0) {
        if ($duration>0) {
            $ban_globalban = '0';
        } else {
            $ban_globalban = '1';
        }
        if ($duration>0) {
            $ban_until = time()+$duration;
        } else {
            $ban_until = '0';
        }
        mysql_query("INSERT INTO `{$tc_config['dbprefix']}banlist` ( `ip` , `ipmd5` , `type` , `allowread` , `globalban` , `boards` , `by` , `at` , `until` , `reason` ) VALUES ( '".md5_encrypt($ip,$tc_config['randomseed'])."' , '".md5($ip)."' , '".$type."' , '".$allowread."' , '".$globalban."' , '".$boards."' , '".$modname."' , '".time()."' , '".$ban_until."' , '".$reason."' )",$tc_config['dblink']);
        update_htaccess();
        return true;
    } else {
        return true;
    }
}
function display_bannedmessage($globalban,$boards,$reason,$at,$until) { //Generate the 'You are banned!' page
    $output = "<title>YOU ARE BANNED!</title><div style=\"text-align: center;\"><img src=\"/youarebanned.jpg\" alt=\":'(\"><br><h1>YOU ARE BANNED :'(</h1><br>You are banned from posting on: ";
    if ($globalban=='1') {
        $output .= '<b>ALL BOARDS</b>';
    } else {
        $output .= $boards;
    }
    $output .= "<br><br>Reason: ".stripslashes($reason)."<br><br>Placed: ".$at."<br>Expires: ".$until."</div>";
    return $output;
}
function removed_expired_bans() {
    global $tc_config;
    $result = mysql_query("DELETE FROM `{$tc_config['dbprefix']}banlist` WHERE `until` != '0' AND `until` < '".time()."'",$tc_config['dblink']);
        if (mysql_affected_rows($tc_config['dblink'])>0) {
            update_htaccess();
        }
}

function update_htaccess() {
    global $tc_config;
    require_once($tc_config['rootdir']."/inc/encryption.php");
    $htaccess_contents = file_get_contents($tc_config['boardsdir'].'/.htaccess');
    $htaccess_contents_preserve = substr($htaccess_contents,0,strpos($htaccess_contents,'## !TC_BANS:')+12)."\n";
    
    $htaccess_contents_bans_iplist = '';
    $result = mysql_query("SELECT `ip` FROM `{$tc_config['dbprefix']}banlist` WHERE `allowread` = '0' ORDER BY `ip` ASC",$tc_config['dblink']);
    while ($line = mysql_fetch_assoc($result)) {
            $htaccess_contents_bans_iplist .= "RewriteCond %{REMOTE_ADDR} ".md5_decrypt($line['ip'],$tc_config['randomseed'])."\n";
    }
    if ($htaccess_contents_bans_iplist!='') {
        $htaccess_contents_bans_start = "<IfModule mod_rewrite.c>\nRewriteEngine On\n";
        $htaccess_contents_bans_end = "RewriteRule !^(banned.php|youarebanned.jpg)$ /banned.php [R,L]\n</IfModule>";
    } else {
        $htaccess_contents_bans_start = '';
        $htaccess_contents_bans_end = '';
    }
    $htaccess_contents_new = $htaccess_contents_preserve.$htaccess_contents_bans_start.$htaccess_contents_bans_iplist.$htaccess_contents_bans_end;
    file_put_contents($tc_config['boardsdir'].'/.htaccess',$htaccess_contents_new);
}

//Posting
function getnextpostid($boardid) { //Retrieve the id of the next post
    global $tc_config;
    $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `boardid` = '".$boardid."' ORDER BY `id` DESC LIMIT 1",$tc_config['dblink']);    
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            return $line['id']+1;
        }
    } else {
        return '1';
    }
}
function check_md5($md5,$boardid) { //Checks if the supplied md5 file hash is currently recorded inside of the database, attached to a non-deleted post.
    global $tc_config;
    $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `imagemd5` = '".mysql_real_escape_string($md5)."' AND `boardid` = '".mysql_real_escape_string($boardid)."' AND `IS_DELETED` = 0 LIMIT 1",$tc_config['dblink']);    
    $rows = mysql_num_rows($result);
    if ($rows>0) { //Yes, it is in there
        return true;
    } else { //No, it isn't
        return false;
    }
}

//Image handling
function createthumb($name,$filename,$new_w,$new_h) {
    $system=explode(".",$filename);
    $system = array_reverse($system);
    if (preg_match("/jpg|jpeg/",$system[0])) {
        $src_img=imagecreatefromjpeg($name);
    } else if (preg_match("/png/",$system[0])) {
        $src_img=imagecreatefrompng($name);
    } else if (preg_match("/gif/",$system[0])) {
        $src_img=imagecreatefromgif($name);
    } else {
        return false;
    }
    if (!$src_img) {
        echo '<br>Unable to open the uploaded image for thumbnailing.  Maybe its a different filetype, and has the wrong extension?';
        return false;
    }
    $old_x=imageSX($src_img);
    $old_y=imageSY($src_img);
    if ($old_x > $old_y) {
        $percent = $new_w / $old_x;
    } else {
        $percent = $new_h / $old_y;
    }
    $thumb_w = round($old_x * $percent);
    $thumb_h = round($old_y * $percent);
    $dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
    imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
    if (preg_match("/png/",$system[0])) {
        if (!imagepng($dst_img,$filename)) {
            echo 'unable to imagepng.';
            return false;
        }
    } else if (preg_match("/jpg|jpeg/",$system[0])) {
        if (!imagejpeg($dst_img,$filename)) {
            echo 'unable to imagejpg.';
            return false;
        }
    } else if (preg_match("/gif/",$system[0])) {
        if (!imagegif($dst_img,$filename)) { 
            echo 'unable to imagegif.';
            return false;
        }
    }
    imagedestroy($dst_img); 
    imagedestroy($src_img); 
    return true;
}

//Header
function display_boardlist($is_textboard = false) {
    $output = '';
    /*global $tc_config;
    $result = mysql_query("SELECT DISTINCT `section` FROM `{$tc_config['dbprefix']}boards` ORDER BY `section` ASC",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    $board_sections = array();
    while ($line = mysql_fetch_assoc($result)) {
        $board_sections = array_merge($board_sections,array($line['section']));
    }
    foreach ($board_sections as $board_section) {
        $board_this_section = '';
        $output .= '[';
        $result = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}boards` WHERE `section` = '".$board_section."' ORDER BY `order` ASC",$tc_config['dblink']);
        while ($line = mysql_fetch_assoc($result)) {
            $board_this_section .= ' <a title="'.$line['desc'].'" href="'.$tc_config['boardsfolder'].'/'.$line['name'].'/">'.$line['name'].'</a> /';
        }
        $board_this_section = substr($board_this_section,0,strlen($board_this_section)-1);
        $output .= $board_this_section;
        $output .= ']&nbsp;';
    }*/
    if ($is_textboard) {
        $div_name = 'topbar';
    } else {
        $div_name = 'navbar';
    }
    if (is_file("boards.html")) {
        return '<div class="'.$div_name.'">'.stripslashes(file_get_contents("boards.html")).'</div>';
    } else {
        return '<div class="'.$div_name.'">'.stripslashes(file_get_contents("../boards.html")).'</div>';
    }
    //return $output;
}
function display_textboard_threadlist($board_id,$liststart,$liststooutput,$ispage = false) {
    global $tc_config;
    $output = '<div class="head threadldiv"><a name="menu"></a>
    <table class="threads"><thead>
    <tr>
    <th width="10%">#</th>
    <th nowrap="nowrap" width="100%">Subject</th>
    <th>Posts</th>
    <th>Last Post</th>
    </tr>
    </thead>
    <tbody>';
    $query = "SELECT * FROM `{$tc_config['dbprefix']}posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '0' AND `IS_DELETED` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".$liststart.',';
    if ($listpage>=0&&$liststooutput>=0) {
        $query .= '40';
    } else {
        $query .= '15';
    }
    $result = mysql_query($query,$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        $relative_id = $liststart;
        while ($line = mysql_fetch_assoc($result)) {
            $result2 = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '".$line['id']."' AND `IS_DELETED` = 0",$tc_config['dblink']);
            $replies = mysql_num_rows($result2);
            $output .= '<tr><td><a href="res/'.$line['id'].'.html">'.($relative_id+1).'</a></td><td><a href="';
            if ($relative_id<15&&!$ispage) {
                $output .= '#'.$relative_id;
            } else {
                $output .= 'res/'.$line['id'].'.html';
            }
            $output .= '">'.stripslashes($line['subject']).'</a></td><td>'.($replies+1).'</td><td nowrap><small>'.date('j F Y H:i',$line['lastbumped']).'</small></td></tr>';
            $relative_id++;
        }
    } else {
        $output .= '<tr><td>N/A</td><td>There are currently no threads to display.</td><td>N/A</td><td>N/A</td></td>';
    }
    $output .= '</tbody></table>';
    if ($listpage<0||$liststooutput<0) {
        $output .= '<span class="threadlinks"><a href="#newthread">New Thread</a> | <a href="list.html">All Threads</a></span>';
    }
    $output .= '</div>';
    return $output;
}
function format_postboxnotice($notice,$board) {
    global $tc_config;
    $result = mysql_query("SELECT `maximagesize`, `filetypes` FROM `{$tc_config['dbprefix']}boards` WHERE `name` = '$board' LIMIT 1",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            $notice = str_replace('<!tc_maxthumbwidth />',config_getvalue('maxthumbwidth'),$notice);
            $notice = str_replace('<!tc_maxthumbheight />',config_getvalue('maxthumbheight'),$notice);
            $notice = str_replace('<!tc_uniqueposts />',display_numuniqueposts($board),$notice);
            $notice = str_replace('<!tc_maximagekb />',round($line['maximagesize']/1024),$notice);
            $filetypes = '';
            $filetypes_allowed = explode('|',$line['filetypes']);
            if ($filetypes_allowed=='') {
                $filetypes = 'NONE';
            } else {
                foreach ($filetypes_allowed as $filetype) {
                    $filetypes .= $filetype.', ';
                }
                $filetypes = substr($filetypes,0,strlen($filetypes)-2);
            }
            $notice = str_replace('<!tc_filetypes />',$filetypes,$notice);
        }
    }
    return $notice;
}

function boardid_to_dir($boardid) {
    global $tc_config;
    $result = mysql_query("SELECT `name` FROM `{$tc_config['dbprefix']}boards` WHERE `id` = '".mysql_real_escape_string($boardid)."' LIMIT 1",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            return $line['name'];
        }
    }
}
function calculatenumpages($boardtype,$numposts) {
    if ($boardtype=='1') {
        return (floor($numposts/15));
    } else {
        return (floor($numposts/config_getvalue('numthreadsdisplayed')));
    }
}

function ban_check($ip,$board = '',$force_display = false) {
    global $tc_config;
    require_once($tc_config['rootdir']."/inc/encryption.php");
    $result = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}banlist` WHERE `type` = '0' AND `ipmd5` = '".md5($ip)."'",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            if ($line['until']=='0') {
                $ban_until = '<font color="red">NEVER</font>';
            } else {
                $ban_until = date("F j, Y, g:i a",$line['until']);
            }
            if ($line['globalban']!=1) {
                if (in_array($board,explode('|',$line['boards']))) {
                    echo display_bannedmessage($line['globalban'],'<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;',$line['reason'],date("F j, Y, g:i a",$line['at']),$ban_until);
                    die();
                }
            } else {
                echo display_bannedmessage($line['globalban'],'<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;',$line['reason'],date("F j, Y, g:i a",$line['at']),$ban_until);
                die();
            }
        }
    }
    $result = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}banlist` WHERE `type` = '1'",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    if ($rows>0) {
        while ($line = mysql_fetch_assoc($result)) {
            if (eregi(md5_decrypt($line['ip'],$tc_config['randomseed']),$ip)) {
                if ($line['until']=='0') {
                    $ban_until = '<font color="red">NEVER</font>';
                } else {
                    $ban_until = date("F j, Y, g:i a",$line['until']);
                }
                echo display_bannedmessage($line['globalban'],'<b>/'.implode('/</b>, <b>/',explode('|',$line['boards'])).'/</b>&nbsp;',$line['reason'],date("F j, Y, g:i a",$line['at']),$ban_until);
                die();
            }
        }
    }
    if (config_getvalue('bantorusers')=='1') {
        /* Function borrowed from futallaby */
        function  proxy_connect($port) {
            $fp = @fsockopen ($ip, $port,$a,$b,2);
            if(!$fp){return 0;}else{return 1;}
        }
        if(proxy_connect(9001)){
            $query = "INSERT INTO `{$tc_config['dbprefix']}banlist` ( `ip` , `ipmd5` , `globalban` , `boards` , `by` , `at` , `until` , `reason` ) VALUES ( '".md5_encrypt($ip,$tc_config['randomseed'])."' , '".md5($ip)."' , '1' , '' , 'script' , '".time()."' , '0' , 'tor user' )";
            $result = mysql_query($query,$tc_config['dblink']);
            echo display_bannedmessage('1','','tor user',date("F j, Y, g:i a",time()),'0');
            die();
        }
    }
    if ($force_display) {
       echo '<title>YOU ARE NOT BANNED!</title><div align="center"><img src="/youarenotbanned.jpg"><br><br>Unable to find record of your IP being banned.</div>';
    } else {
       return true;
        }
}
?>
