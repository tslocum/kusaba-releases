<?php
  /*
   * This file is part of Trevorchan.
   *
   * Trevorchan is free software; you can redistribute it and/or modify it under the
   * terms of the GNU General Public License as published by the Free Software
   * Foundation; either version 2 of the License, or (at your option) any later
   * version.
   *
   * Trevorchan is distributed in the hope that it will be useful, but WITHOUT ANY
   * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
   * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
   *
   * You should have received a copy of the GNU General Public License along with
   * Trevorchan; if not, write to the Free Software Foundation, Inc.,
   * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
   * +------------------------------------------------------------------------------+
   * Miscellaneous functions used throughout the script.
   * +------------------------------------------------------------------------------+
   * The functions script has no theme; any and all functions which are used globally
   * will be found in this file.
   * +------------------------------------------------------------------------------+
   */

function microtime_float() {
    return array_sum(explode(' ', microtime()));
}

// <3 coda for this wonderful snippet
// print $contents to $filename by using a temporary file and renaming it
function print_page($filename, $contents, $board) {
    global $tc_db;
    
    /*if (config_getvalue('tidyhtml')==1) {
        $file = rand(0, 10000); // Give us a new random filename
        file_put_contents("/tmp/$file", $contents);
        shell_exec("tidy -m --wrap 0 --indent yes /tmp/$file");
        $contents = file_get_contents("/tmp/$file");
        unlink("/tmp/$file"); // Clean up after ourselves
    }*/
    
    $tempfile = tempnam(TC_BOARDSDIR.$board."/res", "tmp"); //note: THIS actually creates the file
    $fp = fopen($tempfile, "w");
    fwrite($fp, $contents);
    fclose($fp);
    if (strtoupper(substr(PHP_OS, 0, 3))=='WIN') { //Windows systems refuse to rename a file to a file which already exists, so we have to delete the old file first.  This creates the problem of sporadic 404 errors, since the file doesn't exist for a short amount of time
        unlink($filename);
    }
    rename($tempfile, $filename);
    chmod($filename, 0664); //it was created 0600
}

function print_stylesheets($prefered_stylesheet = 'Burichan') {
    global $tc_db;
    $output_stylesheets = '';
    if ($prefered_stylesheet!='Burichan'&&$prefered_stylesheet!='Futaba'&&$prefered_stylesheet!='Gurochan'&&$prefered_stylesheet!='Photon'&&$prefered_stylesheet!='Fuhrerchan') {
        $prefered_stylesheet = 'Burichan';
    }
    $stylesheets = array(array('burichan','Burichan'),array('futaba','Futaba'),array('gurochan','Gurochan'),array('photon','Photon'),array('fuhrerchan','Fuhrerchan'));
    foreach ($stylesheets as $stylesheet) {
        $output_stylesheets .= '<link rel="';
        if ($stylesheet[1]!=$prefered_stylesheet) {
            $output_stylesheets .= 'alternate ';
        }
        $output_stylesheets .= 'stylesheet" type="text/css" href="'.TC_BOARDSPATH.'/css/'.$stylesheet[0].'.css" title="'.$stylesheet[1].'">
';
    }
    return $output_stylesheets;
}

function display_numuniqueposts($board = '') {
    global $tc_db;
    if ($board!='') {
        $result = $tc_db->GetOne("SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($board)."'",1);
        if ($result[0]!='') {
            $board = $result;
            $result = $tc_db->GetOne("SELECT COUNT(DISTINCT `ipmd5`) FROM `".TC_DBPREFIX."posts_".$board."` WHERE `IS_DELETED` = 0");
            return $result;
        } else {
            return '0';
        }
    } else {
        /*$results = $tc_db->GetAll("SELECT COUNT(DISTINCT `ipmd5`) FROM `".TC_DBPREFIX."posts_` WHERE `IS_DELETED` = 0");
        $numuniqueposts = mysql_fetch_row($result);
        return $numuniqueposts[0];*/ //Devnote: Broke when switched to multiple table spanning posts, might fix later
    }
}

//Users
function ban_user($ip, $modname, $globalban, $duration, $boards, $reason, $type=0, $allowread=1) { //Add a ip/ip range ban
    global $tc_db;
    require_once(TC_ROOTDIR."inc/encryption.php");
    $result = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."banlist` WHERE `type` = '".$type."' AND `ipmd5` = '".md5($ip)."'");
    if ($result[0]==0) {
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
        $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."banlist` ( `ip` , `ipmd5` , `type` , `allowread` , `globalban` , `boards` , `by` , `at` , `until` , `reason` ) VALUES ( '".md5_encrypt($ip, TC_RANDOMSEED)."' , '".md5($ip)."' , '".$type."' , '".$allowread."' , '".$globalban."' , '".$boards."' , '".$modname."' , '".time()."' , '".$ban_until."' , '".$reason."' )");
        update_htaccess();
        return true;
    } else {
        return true;
    }
}
function display_bannedmessage($globalban, $boards, $reason, $at, $until) { //Generate the 'You are banned!' page
    $output = "<title>"._('YOU ARE BANNED')."!</title><div style=\"text-align: center;\"><img src=\"/youarebanned.jpg\" alt=\":'(\"><br><h1>"._('YOU ARE BANNED')." :'(</h1><br>"._('You are banned from posting on:')." ";
    if ($globalban=='1') {
        $output .= '<b>'.strtoupper(_('All boards')).'</b>';
    } else {
        $output .= $boards;
    }
    $output .= "<br><br>"._('Reason:')." ".stripslashes($reason)."<br><br>"._('Placed:')." ".$at."<br>"._('Expires:')." ".$until."</div>";
    return $output;
}
function removed_expired_bans() {
    global $tc_db;
    $results = $tc_db->Execute("DELETE FROM `".TC_DBPREFIX."banlist` WHERE `until` != 0 AND `until` < ".time());
    if ($tc_db->Affected_Rows()>0) {
        update_htaccess();
    }
}

function update_htaccess() {
    global $tc_db;
    require_once(TC_ROOTDIR."inc/encryption.php");
    $htaccess_contents = file_get_contents(TC_BOARDSDIR.'.htaccess');
    $htaccess_contents_preserve = substr($htaccess_contents, 0, strpos($htaccess_contents, '## !TC_BANS:')+12)."\n";

    $htaccess_contents_bans_iplist = '';
    $results = $tc_db->GetAll("SELECT `ip` FROM `".TC_DBPREFIX."banlist` WHERE `allowread` = 0 AND `type` = 0 ORDER BY `ip` ASC");
    foreach($results AS $line) {
            $htaccess_contents_bans_iplist .= "RewriteCond %{REMOTE_ADDR} ".md5_decrypt($line['ip'], TC_RANDOMSEED)."\n";
    }
    if ($htaccess_contents_bans_iplist!='') {
        $htaccess_contents_bans_start = "<IfModule mod_rewrite.c>\nRewriteEngine On\n";
        $htaccess_contents_bans_end = "RewriteRule !^(banned.php|youarebanned.jpg)$ /banned.php [L]\n</IfModule>";
    } else {
        $htaccess_contents_bans_start = '';
        $htaccess_contents_bans_end = '';
    }
    $htaccess_contents_new = $htaccess_contents_preserve.$htaccess_contents_bans_start.$htaccess_contents_bans_iplist.$htaccess_contents_bans_end;
    file_put_contents(TC_BOARDSDIR.'.htaccess', $htaccess_contents_new);
}

//Posting
function getnextpostid($board) { //Retrieve the id of the next post
    global $tc_db;
    $results = $tc_db->SelectLimit("SELECT `id` FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` ORDER BY `id` DESC", 1);
    foreach($results AS $line) {
        return $line['id']+1;
    }
    //If it makes it this far, there were no ID's returned
    return '1';
}
function check_md5($md5, $board) { //Checks if the supplied md5 file hash is currently recorded inside of the database, attached to a non-deleted post.
    global $tc_db;
    $results = $tc_db->GetAssoc("SELECT `id`, `threadid` FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `imagemd5` = '".mysql_real_escape_string($md5)."' AND `IS_DELETED` = 0", 1);
    if (!empty($results)) {
        foreach($results AS $line) {
            $real_threadid = ($line['threadid']==0) ? $line['id'] : $line['threadid'];
            return array($real_threadid, $line['id']);
        }
    } else {
        return false;
    }
}

//Header
function display_boardlist($is_textboard = false) {
    $output = '';
    /*global $tc_db;
    $results = $tc_db->GetAll("SELECT DISTINCT `section` FROM `".TC_DBPREFIX."boards` ORDER BY `section` ASC");
    $rows = mysql_num_rows($result);
    $board_sections = array();
    foreach($results AS $line) {
        $board_sections = array_merge($board_sections, array($line['section']));
    }
    foreach ($board_sections as $board_section) {
        $board_this_section = '';
        $output .= '[';
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards` WHERE `section` = '".$board_section."' ORDER BY `order` ASC");
        foreach($results AS $line) {
            $board_this_section .= ' <a title="'.$line['desc'].'" href="'.TC_BOARDSFOLDER.''.$line['name'].'/">'.$line['name'].'</a> /';
        }
        $board_this_section = substr($board_this_section, 0, strlen($board_this_section)-1);
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
function display_textboard_threadlist($board, $liststart, $liststooutput, $ispage = false) {
    global $tc_db;
    $output = '<div class="hborder"><div class="head threadldiv"><a name="menu"></a>
    <table class="threads"><thead>
    <tr>
    <th width="10%">#</th>
    <th nowrap="nowrap" width="100%">Subject</th>
    <th>Posts</th>
    <th>Last Post</th>
    </tr>
    </thead>
    <tbody>';
    if ($listpage>=0&&$liststooutput>=0) {
        $startrecord = 40;
    } else {
        $startrecord = 15;
    }
    $query = "SELECT * FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `threadid` = '0' AND `IS_DELETED` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT $liststart,$startrecord";
    $results = $tc_db->GetAll($query);
    if (count($results)>0) {
        $relative_id = $liststart;
        foreach($results AS $line) {
            $results2 = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `threadid` = '".$line['id']."' AND `IS_DELETED` = 0");
            $replies = count($results2);
            $output .= '<tr><td><a href="res/'.$line['id'].'.html">'.($relative_id+1).'</a></td><td><a href="';
            if ($relative_id<15&&!$ispage) {
                $output .= '#'.$relative_id;
            } else {
                $output .= 'res/'.$line['id'].'.html';
            }
            $output .= '">'.stripslashes($line['subject']).'</a></td><td>'.($replies+1).'</td><td nowrap><small>'.date('j F Y H:i', $line['lastbumped']).'</small></td></tr>';
            $relative_id++;
        }
    } else {
        $output .= '<tr><td>N/A</td><td>There are currently no threads to display.</td><td>N/A</td><td>N/A</td></td>';
    }
    $output .= '</tbody></table>';
    if ($listpage<0||$liststooutput<0) {
        $output .= '<span class="threadlinks"><a href="#newthread">New Thread</a> | <a href="list.html">All Threads</a></span>';
    }
    $output .= '</div></div>';
    return $output;
}
function format_postboxnotice($notice, $board) {
    global $tc_db;
    $query = "SELECT `maximagesize`, `filetypes` FROM `".TC_DBPREFIX."boards` WHERE `name` = '$board' LIMIT 1";
    $results = (TC_DBUSECACHE) ? $tc_db->CacheGetAll($query) : $tc_db->GetAll($query);
    foreach($results AS $line) {
        $filetypes = '';
        $filetypes_allowed = explode('|', $line['filetypes']);
        if ($filetypes_allowed=='') {
            $filetypes = 'NONE';
        } else {
            foreach ($filetypes_allowed as $filetype) {
                $filetypes .= $filetype.', ';
            }
            $filetypes = substr($filetypes, 0, strlen($filetypes)-2);
        }
        $patterns = array ('/\<\!tc_maxthumbwidth \/\>/', '/\<\!tc_maxthumbheight \/\>/', '/\<\!tc_uniqueposts \/\>/', '/\<\!tc_maximagekb \/\>/', '/\<\!tc_filetypes \/\>/');
        $replace = array (TC_THUMBWIDTH, TC_THUMBHEIGHT, display_numuniqueposts($board), round($line['maximagesize']/1024), $filetypes);
        $notice = preg_replace($patterns, $replace, $notice);
    }
    return $notice;
}

function boardid_to_dir($boardid) {
    global $tc_db;
    $query = "SELECT `name` FROM `".TC_DBPREFIX."boards` WHERE `id` = '".mysql_real_escape_string($boardid)."'";
    $results = (TC_DBUSECACHE) ? $tc_db->CacheSelectLimit($query, 1) : $tc_db->SelectLimit($query, 1);
    if (count($results)>0) {
        foreach($results AS $line) {
            return $line['name'];
        }
    }
}
function calculatenumpages($boardtype, $numposts) {
    if ($boardtype==1) {
        return (floor($numposts/15));
    } else {
        return (floor($numposts/TC_THREADS));
    }
}

function ban_check($ip, $board = '', $force_display = false) {
    global $tc_db;
    require_once(TC_ROOTDIR."inc/encryption.php");
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."banlist` WHERE `type` = '0' AND `ipmd5` = '".md5($ip)."'");
    if (count($results)>0) {
        foreach($results AS $line) {
            if ($line['until']=='0') {
                $ban_until = '<font color="red">NEVER</font>';
            } else {
                $ban_until = date("F j, Y, g:i a", $line['until']);
            }
            if ($line['globalban']!=1) {
                if (in_array($board, explode('|', $line['boards']))) {
                    echo display_bannedmessage($line['globalban'], '<b>/'.implode('/</b>, <b>/', explode('|', $line['boards'])).'/</b>&nbsp;', $line['reason'], date("F j, Y, g:i a", $line['at']), $ban_until);
                    die();
                }
            } else {
                echo display_bannedmessage($line['globalban'], '<b>/'.implode('/</b>, <b>/', explode('|', $line['boards'])).'/</b>&nbsp;', $line['reason'], date("F j, Y, g:i a", $line['at']), $ban_until);
                die();
            }
        }
    }
    $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."banlist` WHERE `type` = '1'");
    if (count($results)>0) {
        foreach($results AS $line) {
            if (eregi(md5_decrypt($line['ip'], TC_RANDOMSEED), $ip)) {
                if ($line['until']=='0') {
                    $ban_until = '<font color="red">NEVER</font>';
                } else {
                    $ban_until = date("F j, Y, g:i a", $line['until']);
                }
                echo display_bannedmessage($line['globalban'], '<b>/'.implode('/</b>, <b>/', explode('|', $line['boards'])).'/</b>&nbsp;', $line['reason'], date("F j, Y, g:i a", $line['at']), $ban_until);
                die();
            }
        }
    }
    if (false) { //Disabled for now... didn't look like it was working
        /* Function borrowed from futallaby */
        function  proxy_connect($port) {
            $fp = @fsockopen ($ip, $port, $a, $b, 2);
            if(!$fp){return 0;}else{return 1;}
        }
        if(proxy_connect(9001)){
            $query = "INSERT INTO `".TC_DBPREFIX."banlist` ( `ip` , `ipmd5` , `globalban` , `boards` , `by` , `at` , `until` , `reason` ) VALUES ( '".md5_encrypt($ip, TC_RANDOMSEED)."' , '".md5($ip)."' , '1' , '' , 'script' , '".time()."' , '0' , 'tor user' )";
            $tc_db->Execute($query);
            echo display_bannedmessage('1', '', 'tor user', date("F j, Y, g:i a", time()), '0');
            die();
        }
    }
    if ($force_display) {
       echo '<title>YOU ARE NOT BANNED!</title><div align="center"><img src="/youarenotbanned.jpg"><br><br>Unable to find record of your IP being banned.</div>';
    } else {
       return true;
    }
}

function flushthreadrepliescache($threadid, $board) {
    global $tc_db;
    if (TC_DBUSECACHE) {
        $tc_db->CacheFlush('SELECT * FROM `'.TC_DBPREFIX.'posts_'.mysql_real_escape_string($board).'` WHERE `IS_DELETED` = 0 AND `threadid` = '.mysql_real_escape_string($threadid).' ORDER BY `postedat` ASC');
        $tc_db->CacheFlush('SELECT * FROM `'.TC_DBPREFIX.'posts_'.mysql_real_escape_string($board).'` WHERE `IS_DELETED` = 0 AND `threadid` = '.mysql_real_escape_string($threadid).' ORDER BY `postedat` DESC LIMIT '.TC_REPLIES);
        $tc_db->CacheFlush('SELECT * FROM `'.TC_DBPREFIX.'posts_'.mysql_real_escape_string($board).'` WHERE `IS_DELETED` = 0 AND `threadid` = '.mysql_real_escape_string($threadid).' ORDER BY `postedat` DESC LIMIT '.TC_REPLIESSTICKY);
    }
}

/* Gets information about the filetype provided, which is specified in the manage panel */
function getfiletypeinfo($filetype) {
    global $tc_db;
    $query = "SELECT * FROM `".TC_DBPREFIX."filetypes` WHERE `filetype` = '".mysql_real_escape_string($filetype)."'";
    $results = (TC_DBUSECACHE) ? $tc_db->CacheSelectLimit($query, 1) : $tc_db->SelectLimit($query, 1);
    foreach($results AS $line) {
        return array($line['image'],$line['image_w'],$line['image_h']);
    }
    
    /* No info was found, return the generic icon */
    return array('generic.png',48,48);
}

//For PHP4 installations
if (!function_exists("file_get_contents")) {
    function file_get_contents($file) {
        return implode('', file($file));
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
        foreach( $parts as $key=>$part ) {
            $parts[ $key ] = substr($string, $pos, strlen($part));
            $pos += strlen($part) + strlen($find);
        }
        return( join( $replace, $parts ) );
    }
}
?>
