<?php
class Board {
    var $board_id;

    function Init() {
        global $tc_config;
        $resultboard = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}boards` WHERE `id` = '".mysql_real_escape_string($this->board_id)."' LIMIT 1",$tc_config['dblink']);

        while ($line = mysql_fetch_assoc($resultboard)) {
            $this->board_id = $line['id'];
            $this->board_type = $line['type'];
            $this->board_dir = $line['name'];
            $this->board_desc = $line['desc'];
            $this->board_enablereporting = $line['enablereporting'];
            $this->board_image = $line['image'];
            $this->board_includeheader = $line['includeheader'];
            $this->board_forcedanon = $line['forcedanon'];
            $this->board_filetypes = explode('|',$line['filetypes']);
            $this->board_maximagesize = $line['maximagesize'];
            $this->board_maxage = $line['maxage'];
            $this->board_maxreplies = $line['maxreplies'];
            $this->board_maxpages = $line['maxpages'];
            $this->board_messagelength = $line['messagelength'];
            $this->board_locked = $line['locked'];
            $this->board_redirecttothread = $line['redirecttothread'];
        }
        $this->config_postboxnotice = format_postboxnotice(config_getvalue('postboxnotice'),$this->board_dir);
        $this->config_numrepliesdisplayed = config_getvalue("numrepliesdisplayed");
        $this->config_numrepliesdisplayedsticky = config_getvalue("numrepliesdisplayedsticky");
        $this->config_numthreadsdisplayed = config_getvalue("numthreadsdisplayed");
    }

    //Regenerate all board and thread pages
    function RegenerateAll() {
        $this->RegeneratePages();
        $this->RegenerateThreads();
    }

    function RegeneratePages() {
        global $tc_config;
        global $clock;
        $clock[] = 'Regenerate pages start: '.microtime_float();
        $clock[] = '1: '.microtime_float();
        $result = mysql_query("SELECT COUNT(*) FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND  `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC",$tc_config['dblink']);
        $numpostsleft = mysql_fetch_row($result);
        $numpostsleft = $numpostsleft[0];
        if ($this->board_type==1) {
            $numthreadsdisplayed = '15';
            $hide_extra = true;
        } else {
            $numthreadsdisplayed = $this->config_numthreadsdisplayed;
            $hide_extra = false;
        }
        $clock[] = '2: '.microtime_float();
        $boardstooutput = floor(($numpostsleft-1) / $numthreadsdisplayed);
        $boardpage = 0;
        if ($numpostsleft>0) {
            while ($numpostsleft>0) {
                $clock[] = 'RUN 1: '.microtime_float();
                if (!ob_start()) {
                    die("Unable to initiate the output buffering!");
                }
                $executiontime_start = microtime_float();
                echo $this->PageHeader($this->board_dir);
                echo $this->Postbox(0, '', $this->config_postboxnotice);
                if ($this->board_type!=1) {
                    echo '<form id="delform" action="'.$tc_config['boardsfolder'].'/board.php" method="post"><input type="hidden" name="board" value="'.mysql_real_escape_string($this->board_dir).'" />';
                }
                $query = "SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".($boardpage*$numthreadsdisplayed).','.$numthreadsdisplayed;
                $result_leftposts = mysql_query($query,$tc_config['dblink']);
                $thread_relative_id = 0;
                while ($line = mysql_fetch_assoc($result_leftposts)) {
                    echo $this->BuildThread($line['id'],true,false,$thread_relative_id);
                    $thread_relative_id++;
                }
                $clock[] = 'RUN 2: '.microtime_float();
                if ($this->board_type!=1) {
                    echo '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br>Password <input type="password" name="postpassword" size="8" />&nbsp;<input name="deletepost" value="Delete" type="submit" />';
                    if ($this->board_enablereporting==1) {
                        echo '<input name="reportpost" value="Report" type="submit" />';
                    }
                    echo '</td></tr></tbody></table></form><script type="text/javascript">set_delpass("delform")</script><table border="1"><tbody><tr><td>';
                    if ($boardpage==0) {
                        echo 'Previous';
                    } else {
                        if ($boardpage-1!=0) {
                            echo '<form method="get" action="'.$tc_config['boardsfolder'].'/'.$this->board_dir.'/'.($boardpage-1).'.html"><input value="Previous" type="submit" /></form>';
                        } else {
                            echo '<form method="get" action="'.$tc_config['boardsfolder'].'/'.$this->board_dir.'/board.html"><input value="Previous" type="submit" /></form>';
                        }
                    }
                    echo '</td><td>';
                    $i = 0;
                    while ($i <= $boardstooutput) {
                        if ($boardpage==$i) {
                            echo '&#91;'.$i.'&#93;';
                        } else {
                            if ($i!=0) {
                                echo '&#91;<a href="'.$tc_config['boardsfolder'].'/'.$this->board_dir.'/'.$i.'.html">'.$i.'</a>&#93;';
                            } else {
                                echo '&#91;<a href="'.$tc_config['boardsfolder'].'/'.$this->board_dir.'/board.html">'.$i.'</a>&#93;';
                            }
                        }
                        $i++;
                    }
                    echo '</td><td>';
                    if ($boardpage==$boardstooutput) {
                        echo 'Next';
                    } else {
                        echo '<form method="get" action="'.$tc_config['boardsfolder'].'/'.$this->board_dir.'/'.($boardpage+1).'.html"><input value="Next" type="submit" /></form>';
                    }
                    echo '</td></tr></tbody></table>';
                }
                $clock[] = 'RUN 3: '.microtime_float();
                $executiontime_stop = microtime_float();
                echo $this->Footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
                $page = ob_get_clean();
                if ($boardpage==0) {
                    print_page($tc_config['boardsdir']."/".$this->board_dir."/board.html",$page,$this->board_dir);
                } else {
                    print_page($tc_config['boardsdir']."/".$this->board_dir."/".$boardpage.".html",$page,$this->board_dir);
                }
                $page = '';
                $boardpage++;
                if ($this->board_type==1) {
                    $numpostsleft = 0;
                } else {
                    $numpostsleft -= $numthreadsdisplayed;
                }
                $clock[] = 'RUN 4: '.microtime_float();
            }
        } else { //Make a blank index
            ob_start();
            $executiontime_start = microtime_float();
            echo $this->PageHeader();
            echo $this->Postbox(0, '', $this->config_postboxnotice);
            $executiontime_stop = microtime_float();
            echo $this->Footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
            $page = ob_get_clean();
            print_page($tc_config['boardsdir']."/".$this->board_dir."/board.html",$page,$this->board_dir);
        }
        $clock[] = '3: '.microtime_float();
        //If text board, rebuild thread list html files
        if ($this->board_type==1)
        {
            $result = mysql_query("SELECT COUNT(*) FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC",$tc_config['dblink']);
            $numpostsleft = mysql_num_rows($result);
            $numpostsleft = $numpostsleft[0];
            $liststooutput = floor(($numpostsleft-1) / 40);
            $listpage = 0;
            $currentpostwave = 0;
            while ($numpostsleft>0)
            {
                ob_start();
                $executiontime_start = microtime_float();
                echo $this->PageHeader(0, $currentpostwave, $listpage, $liststooutput);
                $executiontime_stop = microtime_float();
                echo $this->Footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
                $page = ob_get_clean();
                if ($listpage==0) {
                    print_page($tc_config['boardsdir'].'/'.$this->board_dir.'/list.html',$page,$this->board_dir);
                } else {
                    print_page($tc_config['boardsdir'].'/'.$this->board_dir.'/list'.$listpage.'.html',$page,$this->board_dir);
                }
                $currentpostwave += 40;
                $numpostsleft -= 40;
                $listpage++;
            }
        }
        $clock[] = '4: '.microtime_float();
        //Delete old pages
        $dir = $tc_config['boardsdir'].'/'.$this->board_dir;
        $files = glob ("$dir/{*.html}",  GLOB_BRACE);
        if (is_array($files)) { 
            foreach ($files as $htmlfile) {
                if (preg_match("/[0-9+].html/",$htmlfile)) {
                    if (substr(basename($htmlfile),0,strpos(basename($htmlfile),'.html'))>$boardstooutput) {
                        unlink($htmlfile);
                    }
                }
                if (preg_match("/list[0-9+].html/",$htmlfile)) {
                    if (substr(basename($htmlfile),4,strpos(basename($htmlfile),'.html'))>$liststooutput) {
                        unlink($htmlfile);
                    }
                }
            }
        }
        $clock[] = 'Regenerate pages end: '.microtime_float();
    }
    
    function RegenerateThreads() {
        global $tc_config;
        $res_threadlist = array();
        $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND  `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '0' ORDER BY `lastbumped` DESC",$tc_config['dblink']);
        while ($line = mysql_fetch_assoc($result)) {
            $res_threadlist = array_merge($res_threadlist,array($line['id'].'.html'));
            $this->RegenerateThread($line['id']);
        }
        $dir = $tc_config['boardsdir'].'/'.$this->board_dir.'/res';
        $files = glob ("$dir/{*.html}",  GLOB_BRACE);
        if (is_array($files)) { 
            foreach ($files as $htmlfile) {
                if (!in_array(basename($htmlfile),$res_threadlist)) {
                    unlink($htmlfile);
                }
            }
        }
    }

    function RegenerateThread($threadid) {
        global $tc_config;
        
        $resultboard = mysql_query("SELECT `id`,`type`,`name` FROM `{$tc_config['dbprefix']}boards` WHERE `id` = '".mysql_real_escape_string($this->board_id)."' LIMIT 1",$tc_config['dblink']);
        while ($lineboard = mysql_fetch_assoc($resultboard)) {
            $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND  `boardid` = '".mysql_real_escape_string($lineboard['id'])."' AND `threadid` = '0' AND `id` = '".mysql_real_escape_string($threadid)."' LIMIT 1",$tc_config['dblink']);
            while ($line = mysql_fetch_assoc($result)) {
                $executiontime_start = microtime_float();
                $thread_page = '';
                $this->post_id_id = $line['id'];
                $thread_page .= $this->PageHeader($threadid, '', $this->config_postboxnotice);
                if ($lineboard['type']==1) {
                    $thread_page .= "<a href=\"{$tc_config['boardspath']}/{$lineboard['name']}/board.html\">Return</a><br><br>";
                    $hide_extra = true;
                } else {
                    $thread_page .= "&#91;<a href=\"{$tc_config['boardspath']}/{$lineboard['name']}/board.html\">Return</a>&#93; <div class=\"theader\">Posting mode: Reply</div>";
                    $hide_extra = false;
                }
                $thread_page .= $this->Postbox($threadid,'',$this->config_postboxnotice);
                $thread_page .= $this->BuildThread($this->post_id_id);
                $executiontime_stop = microtime_float();
                $thread_page .= $this->Footer(false,($executiontime_stop-$executiontime_start),$hide_extra);
                print_page("{$tc_config['boardsdir']}/{$lineboard['name']}/res/$threadid.html",$thread_page,$lineboard['name']);
            }
        }
    }
    
    function BuildThread($threadid, $page = false, $resurrect = false, $thread_relative_id = 0) {
        global $tc_config;
        global $clock;
        $clock[] = 'BuildThread start: '.microtime_float();
        if (!is_array($threadid)) {
            $threadid = array($threadid);
        }
        ob_start();
        if (!$threadid>0||$this->board_id<=0) {
            die("Invalid arguments sent to function buildthread()");
        }
        if ($resurrect) {
            $isdeleted_check = '1';
        } else {
            $isdeleted_check = '0';
        }
        if ($this->board_type==1) {
            $result = mysql_query("SELECT COUNT(*) FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '0' ORDER BY `stickied`,`lastbumped` DESC LIMIT 15",$tc_config['dblink']);
            $num_threads_onfrontpage = mysql_fetch_row($result);
            $num_threads_onfrontpage = $num_threads_onfrontpage[0];
        }
        $query_idsegment = '';
        foreach ($threadid as $this_threadid) {
            $query_idsegment .= '`id` = '.mysql_real_escape_string($this_threadid).' OR ';
        }
        $query_idsegment = substr($query_idsegment,0,-4);
        $result = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = ".$isdeleted_check." AND  ($query_idsegment) AND `boardid` = ".mysql_real_escape_string($this->board_id)." AND `threadid` = 0",$tc_config['dblink']);
        $rows = mysql_num_rows($result);
        if ($rows==0) {
            die('No posts in thread to build from.');
        }
        if (!$page&&$this->board_type!=1) {
            echo '<form id="delform" action="'.$tc_config['boardsfolder'].'/board.php" method="post"><input type="hidden" name="board" value="'.$this->board_dir.'" />';
        }
        $clock[] = 'BuildThread 1: '.microtime_float();
        while ($line = mysql_fetch_assoc($result)) {
            $clock[] = 'BuildThread Line 1: '.microtime_float();
            $thread_id = $line['id'];
            if ($resurrect==false) {
                if ($line['image']=="removed") {
                    $imgWidth = '0';
                    $imgHeight = '0';
                    $imgWidth_thumb = '189';
                    $imgHeight_thumb = '16';
                } else if ($line['imagetype']=="swf") {
                    $imgWidth = '0';
                    $imgHeight = '0';
                    $imgWidth_thumb = '86';
                    $imgHeight_thumb = '86';
                } else if ($line['image']!='') {
                    $imgWidth = $line['image_w'];
                    $imgHeight = $line['image_h'];
                    $imgWidth_thumb = $line['thumb_w'];
                    $imgHeight_thumb = $line['thumb_h'];
                }
            }
            $clock[] = 'BuildThread Line 2: '.microtime_float();
            $result2 = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '".mysql_real_escape_string($thread_id)."'");
            $numReplies = mysql_num_rows($result2);
            if ($this->board_type==0||$this->board_type==2) {
                $numImageReplies = 0;
                if ($page==true) {
                    if ($line['stickied']==0) {
                        if ($numReplies>$this->config_numrepliesdisplayed) {
                            $result2 = mysql_query("SELECT `image` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '".mysql_real_escape_string($thread_id)."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-$this->config_numrepliesdisplayed).'',$tc_config['dblink']);
                            while ($line2 = mysql_fetch_assoc($result2)) {
                                if ($line2['image']!='') {
                                    $numImageReplies++;
                                }
                            }
                        }
                    } else {
                        if ($numReplies>$this->config_numrepliesdisplayedsticky) {
                            $result2 = mysql_query("SELECT `image` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `threadid` = '".mysql_real_escape_string($thread_id)."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-$this->config_numrepliesdisplayedsticky).'',$tc_config['dblink']);
                            while ($line2 = mysql_fetch_assoc($result2)) {
                                if ($line2['image']!='') {
                                    $numImageReplies++;
                                }
                            }
                        }
                    }
                }
                echo '<span class="filesize">File: <a ';
                if (config_getvalue('imagesinnewwindow')==1) {
                    echo 'target="_blank" ';
                }
                echo 'href="';
                if ($line['image']=='removed') {
                    echo $tc_config['boardsfolder'].'/imageremoved';
                } else {
                    echo "{$tc_config['boardsfolder']}/{$this->board_dir}/src/{$line['image']}";
                }
                echo '.';
                if ($line['image']=='removed') {
                    echo 'png';
                } else {
                    echo $line['imagetype'];
                }
                echo '">';
                if ($line['image']=='removed') {
                    echo 'imageremoved.png';
                } else {
                    echo "{$line['image']}.{$line['imagetype']}";
                }
                echo '</a> -(<em>';
                if ($line['image']=='removed'||$resurrect) {
                    echo '&nbsp';
                } else {
                    echo round($line['image_size']/1024)." KB, {$imgWidth}x$imgHeight";
                }
                echo '</em>)</span><span class="thumbnailmsg"> Thumbnail displayed, click image for full size.</span><br>';
                if ($resurrect==false) {
                    echo '<a ';
                    if (config_getvalue('imagesinnewwindow')==1) {
                        echo 'target="_blank" ';
                    }
                    echo 'href="';
                    if ($line['image']=='removed') {
                        echo $tc_config['boardsfolder'].'/imageremoved';
                    } else {
                        echo "{$tc_config['boardspath']}/{$this->board_dir}/src/{$line['image']}";
                    }
                    echo '.';
                    if ($line['image']=='removed') {
                        echo "png";
                    } else {
                        echo $line['imagetype'];
                    }
                    echo '"><img src="';
                    if ($line['image']=='removed') {
                        echo $tc_config['boardsfolder']."/imageremoved";
                    } else if ($line['imagetype']=="swf") {
                        echo $tc_config['boardspath']."/flash";
                    } else {
                        echo "{$tc_config['boardspath']}/{$this->board_dir}/thumb/{$line['image']}s";
                    }
                    echo '.';
                    if ($line['image']=='removed'||$line['imagetype']=='swf') {
                        echo 'png';
                    } else { 
                        echo $line['imagetype'];
                    }
                    echo "\" width=\"$imgWidth_thumb\" height=\"$imgHeight_thumb\" alt=\"$thread_id\" class=\"thumb\" /></a>";
                }
                echo "<a name=\"$thread_id\"></a><label><input type=\"checkbox\" name=\"delete\" value=\"$thread_id\" />&nbsp;";
                if ($line['subject']!='') {
                    echo '<span class="filetitle">'.stripslashes($line['subject']).'</span>&nbsp;';
                }
                echo '<span class="postername">';
                if ($line['email']!='') {
                    echo '<a href="mailto:'.$line['email'].'">';
                }
                if ($line['user']==''&&$line['tripcode']=='') {
                    echo 'Anonymous';
                } else if ($line['user']==''&&$line['tripcode']!='')
                {
                    echo ''; // If they have a tripcode, just display the tripcode
                } else {
                    echo stripslashes($line['user']);
                }
                if ($line['email']!='') {
                    echo '</a>';
                }
                echo '</span>';
                if ($line['tripcode']!='') {
                    echo '<span class="postertrip">!'.$line['tripcode'].'</span>';
                }
                if ($line['posterauthority']>0) {
                    if ($line['posterauthority']=="1") {
                        echo '&nbsp;<span class="admin">##&nbsp;Admin&nbsp;##</span>';
                    } else if ($line['posterauthority']=="2") {
                        echo '&nbsp;<span class="mod">##&nbsp;Mod&nbsp;##</span>';
                    }
                }
                echo '&nbsp;'.date("y/m/d(D)H:i",$line['postedat']).'</label>&nbsp;<span class="reflink"><a href="';
                echo "{$tc_config['boardsfolder']}/{$this->board_dir}/res/$thread_id.html#$thread_id;\">No.</a><a href=\"{$tc_config['boardspath']}/{$this->board_dir}/res/$thread_id.html#i$thread_id\"";
                if (!$page) {
                    echo ' onclick="insert(\'>>'.$thread_id.'\');"';
                }
                echo ">$thread_id</a>";
                if ($line['locked']==1) {
                    echo '&nbsp;<img style="border: 0;" src="'.$tc_config['boardspath'].'/locked.gif" alt="locked" />';
                }
                if ($line['stickied']==1) {
                    echo '<img style="border: 0;" src="'.$tc_config['boardspath'].'/sticky.gif" alt="stickied" />';
                }
                echo '</span>';
                if ($page) {
                    echo "&nbsp;&#91;<a href=\"{$tc_config['boardspath']}/{$this->board_dir}/res/$thread_id.html\">Reply</a>&#93;";
                }
                echo moderator_deleteandbanlinks($tc_config['boardsfolder'],$this->board_dir,$thread_id,true);
                echo '<blockquote><p>';
                if (count(explode(chr(10), $line['message']))>15&&$page)
                {
                    $message_exploded = explode(chr(10), stripslashes($line['message']));
                    $message_shortened = '';
                    for ($i = 0; $i <= 14; $i++)
                    {
                        $message_shortened .= $message_exploded[$i];
                    }
                    $message_shortened = preg_replace('/<a href="\/'.$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $message_shortened);
                    $message_shortened = closeOpenTags($message_shortened);
                    echo $message_shortened;
                    echo '<div class="abbrev">Comment too long. Click <a href="'.$tc_config['boardsfolder'].'/'.$this->board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
                } else {
                    echo stripslashes($line['message']);
                }
                echo '</blockquote>';
                if ($numReplies>0) {
                    if ($page==false) {
                        $result2 = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `threadid` = '".mysql_real_escape_string($thread_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' ORDER BY `postedat` ASC",$tc_config['dblink']);
                    } else {
                        $query = "SELECT * FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `threadid` = '".mysql_real_escape_string($thread_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' ORDER BY `postedat` ASC LIMIT ";
                        if ($line['stickied']=="0") {
                            $query .= max($numReplies-$this->config_numrepliesdisplayed,0).','.$this->config_numrepliesdisplayed;
                            if ($numReplies>$this->config_numrepliesdisplayed) {
                                echo '<span class="omittedposts"> '.($numReplies-$this->config_numrepliesdisplayed).' post';
                                if ($numReplies-$this->config_numrepliesdisplayed!=1) {
                                    echo 's';
                                }
                                if ($numImageReplies>0) {
                                    echo ' and '.$numImageReplies.' image';
                                    if ($numImageReplies!=1) {
                                        echo 's';
                                    }
                                }
                                echo ' omitted. Click Reply to view.  </span>';
                            }
                        } else {
                            $query .= max($numReplies-$this->config_numrepliesdisplayedsticky,0).','.$this->config_numrepliesdisplayedsticky;
                            if ($numReplies>$this->config_numrepliesdisplayedsticky) {
                                echo '<span class="omittedposts"> '.($numReplies-$this->config_numrepliesdisplayedsticky).' post';
                                if ($numReplies-$this->config_numrepliesdisplayedsticky!=1) {
                                    echo 's';
                                }
                                if ($numImageReplies>0) {
                                    echo ' and '.$numImageReplies.' image';
                                    if ($numImageReplies!=1) {
                                        echo 's';
                                    }
                                }
                                echo ' omitted. Click Reply to view.  </span>';
                            }
                        }
                        $result2 = mysql_query($query,$tc_config['dblink']);
                    }
                    while ($line2 = mysql_fetch_assoc($result2)) {
                        $reply_id = $line2['id'];
                        $reply_user = stripslashes($line2['user']);
                        $reply_tripcode = $line2['tripcode'];
                        $reply_email = stripslashes($line2['email']);
                        $reply_subject = stripslashes($line2['subject']);
                        $reply_message = stripslashes($line2['message']);
                        $reply_postedat = $line2['postedat'];
                        $reply_image = $line2['image'];
                        $reply_imagetype = $line2['imagetype'];
                        $reply_posterauthority = $line2['posterauthority'];
                        $reply_imgSize = $line2['image_size'];
                        if ($reply_image!='') {
                            if ($resurrect==false) {
                                if ($reply_image=="removed") {
                                    $reply_imgWidth = '0';
                                    $reply_imgHeight = '0';
                                    $reply_imgWidth_thumb = '189';
                                    $reply_imgHeight_thumb = '16';
                                } else if ($reply_imagetype=="swf") {
                                    $reply_imgWidth = '0';
                                    $reply_imgHeight = '0';
                                    $reply_imgWidth_thumb = '86';
                                    $reply_imgHeight_thumb = '86';
                                } else if ($reply_image!='') {
                                    $reply_imgWidth = $line2['image_w'];
                                    $reply_imgHeight = $line2['image_h'];
                                    $reply_imgWidth_thumb = $line2['thumb_w'];
                                    $reply_imgHeight_thumb = $line2['thumb_h'];
                                }
                            }
                        }
                        echo "<table><tbody><tr>
                        <td class=\"doubledash\">&gt;&gt;</td>
                        <td class=\"reply\" id=\"reply$reply_id\"><a name=\"$reply_id\"></a>
                        <label><input type=\"checkbox\" name=\"delete\" value=\"$reply_id\" />&nbsp;";
                        if ($reply_subject!='') { echo '<span class="filetitle">'.$reply_subject.'</span>&nbsp;'; }
                        echo '<span class="commentpostername">';
                        if ($reply_email!='') {
                            echo '<a href="';
                            if ($reply_email!="age"&&$reply_email!="sage") {
                                echo 'mailto:';
                            }
                            echo $reply_email.'">';
                        }
                        if ($reply_user==''&&$reply_tripcode=='') {
                            echo 'Anonymous';
                        } else if ($reply_user==''&&$line['tripcode']!='') {
                            // If they have a tripcode, just display the tripcode
                        } else {
                            echo $reply_user;
                        }
                        if ($reply_email!='') {
                            echo '</a>';
                        }
                        echo '</span>';
                        if ($reply_tripcode!='') {
                            echo '<span class="postertrip">!'.$reply_tripcode.'</span>';
                        }
                        if ($reply_posterauthority) {
                            if ($reply_posterauthority==1) {
                                echo '&nbsp;<span class="admin">##&nbsp;Admin&nbsp;##</span>';
                            } else if ($reply_posterauthority==2) {
                                echo '&nbsp;<span class="mod">##&nbsp;Mod&nbsp;##</span>';
                            }
                        }
                        echo '&nbsp;'.date("y/m/d(D)H:i",$reply_postedat);
                        echo "</label> <span class=\"reflink\"><a href=\"{$tc_config['boardsfolder']}/{$this->board_dir}/res/$thread_id.html#$reply_id\">No.</a><a href=\"{$tc_config['boardsfolder']}/{$this->board_dir}/res/$thread_id.html#i$reply_id\"";
                        if (!$page) {
                            echo ' onclick="insert(\'>>'.$reply_id.'\');"';
                        }
                        echo ">$reply_id</a></span>".moderator_deleteandbanlinks($tc_config['boardsfolder'],$this->board_dir,$reply_id,false).'&nbsp;<br>';
                        if ($reply_image!='') {
                            if (!$resurrect) {
                                echo '<span class="filesize">File: <a ';
                                if (config_getvalue('imagesinnewwindow')==1) {
                                    echo 'target="_blank" ';
                                }
                                echo 'href="';
                                if ($reply_image=='removed') {
                                    echo $tc_config['boardsfolder'].'/imageremoved.png';
                                } else {
                                    echo "{$tc_config['boardsfolder']}/{$this->board_dir}/src/$reply_image.$reply_imagetype";
                                }
                                echo '">';
                                if ($reply_image=='removed') {
                                    echo 'imageremoved.png';
                                } else {
                                    echo "$reply_image.$reply_imagetype";
                                }
                                echo '</a> -(<em>';
                                if ($reply_image=='removed') {
                                    echo '&nbsp';
                                } else {
                                    echo round($reply_imgSize/1024)." KB, {$reply_imgWidth}x$reply_imgHeight";
                                }
                                echo '</em>)</span> <span class="thumbnailmsg">Thumbnail displayed, click image for full size.</span><br><a ';
                                if (config_getvalue("imagesinnewwindow")=="1")
                                {
                                    echo 'target="_blank" ';
                                }
                                echo 'href="';
                                if ($reply_image=="removed") {
                                    echo $tc_config['webpath']."/imageremoved.png";
                                } else {
                                    echo "{$tc_config['boardspath']}/{$this->board_dir}/src/$reply_image.$reply_imagetype";
                                }
                                echo '"><img src="';
                                if ($reply_image=="removed") {
                                    echo $tc_config['webpath'].'/imageremoved.png';
                                } else if ($reply_imagetype=="swf") {
                                    echo "{$tc_config['boardsfolder']}/flash.png";
                                } else {
                                    echo "{$tc_config['boardspath']}/{$this->board_dir}/thumb/{$reply_image}s.$reply_imagetype";
                                }
                                echo "\" width=\"$reply_imgWidth_thumb\" height=\"$reply_imgHeight_thumb\" alt=\"$reply_id\" class=\"thumb\" /></a>";
                            }
                        }
                        echo '<blockquote><p>';
                        if (count(explode(chr(10), $reply_message))>15&&$page) {
                            $message_exploded = explode(chr(10), $reply_message);
                            $message_shortened = '';
                            for ($i = 0; $i <= 14; $i++) {
                                $message_shortened .= $message_exploded[$i];
                            }
                            $message_shortened = preg_replace('/<a href="\/'.$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $message_shortened);
                            $message_shortened = closeOpenTags($message_shortened);
                            echo $message_shortened;
                            echo '<div class="abbrev">Comment too long. Click <a href="'.$tc_config['boardsfolder'].'/'.$this->board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
                        } else {
                            //echo '/[\<a href\="\/b\/res\/'.$thread_id.'\.html#i]([0-9]+)["\>&gt;&gt;]/'.'<br><br>'.'<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;\\1</a>'.'<br><br>';
                            if ($page==true) {
                                echo preg_replace('/<a href="\/'.$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $reply_message);
                            } else {
                                echo preg_replace('/<a href="\/'.$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1" onclick="highlight(\'\\1\');">&gt;&gt;', $reply_message);
                            }
                            //echo preg_replace('/'.preg_quote('<a href="\/b\/res\/'.$thread_id.'.html#i').'([0-9]+)'.preg_quote('">&gt;&gt;').'/', '<a href="/b/res/'.$thread_id.'.html#\\1">&gt;&gt;',stripslashes($line['message']));
                        }
                        echo '</blockquote></td></tr></tbody></table>';
                    }
                }
                echo '<br clear="left" /><hr />';
                if (!$page) {
                    echo '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br>Password <input type="password" name="postpassword" size="8" />&nbsp;<input name="deletepost" value="Delete" type="submit" />';
                    if ($this->board_enablereporting==1) {
                        echo '<input name="reportpost" value="Report" type="submit" />';
                    }
                    echo '</td></tr></tbody></table></form>';
                }
            } else {
                if ($page) {
                    echo '<div class="border"><div class="thread">';
                } else {
                    echo '<hr>';
                }
                echo '<a name="'.$thread_relative_id.'"></a>';
                if ($page) {
                    echo '<span class="navlinks"><a href="#';
                    if (($thread_relative_id-1)==-1) {
                        echo ($num_threads_onfrontpage-1);
                    } else {
                        echo ($thread_relative_id-1);
                    }
                    echo '">&uarr;</a>&nbsp;<a href="#';
                    if (($thread_relative_id+1)==$num_threads_onfrontpage) {
                        echo '0';
                    } else {
                        echo ($thread_relative_id+1);
                    }
                    echo '">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>';
                }
                if ($page) {
                    echo '<h2>';
                    echo '<a href="res/'.$thread_id.'.html">';
                } else {
                    echo '<h3>';
                }
                echo $line['subject'];
                if ($page) {
                    echo '</a>';
                }
                if ($page) {
                    echo '<span class="replies">&nbsp;('.$numReplies.')</span></h2>';
                    if ($thread_relative_id%2==0) {
                        echo '<div class="post even">';
                    } else {
                        echo '<div class="post odd">';
                    }
                } else {
                    echo '<span class="replies">&nbsp;('.$numReplies.')</span></h3>';
                    echo '<div class="post even">';
                }
                echo '<span class="postnum"><a href="javascript:quote(1,\'post'.$line['id'].'\');"';
                if (!$page)
                {
                    echo ' name="1"';
                }
                echo '>1</a></span>&nbsp;';
                echo '<span class="postinfo">Name:&nbsp;<span class="postername">';
                if ($line['user']==''&&$line['tripcode']=='') {
                    echo 'Anonymous';
                } else if ($line['user']==''&&$line['tripcode']!='') {
                    echo ''; // If they have a tripcode, just display the tripcode
                } else {
                    echo stripslashes($line['user']);
                }
                echo '</span><span class="postertrip">';
                if ($line['tripcode']!='') {
                    echo '!'.stripslashes($line['tripcode']);
                }
                echo '</span> @ ';
                echo date("Y-m-d H:i",$line['postedat']);
                echo moderator_deleteandbanlinks($tc_config['boardsfolder'],$this->board_dir,$thread_id,true);
                echo '<span class="id"></span></span><blockquote>';
                echo stripslashes($line['message']);
                echo '</blockquote></div>';
                $is_even = false;
                
                $query = "SELECT * FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = '".$isdeleted_check."' AND  `threadid` = '".mysql_real_escape_string($thread_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' ORDER BY `postedat` ASC";
                if ($page) {
                    $query .= ' LIMIT '.max(0,$numReplies-5).',5';
                }
                $result2 = mysql_query($query,$tc_config['dblink']);
                if ($numReplies>5&&$page) {
                    echo '<p class="hidden">The 5 newest replies are shown below.<br/><a href="res/'.$line['id'].'.html">Read this thread from the beginning</a></p>';
                    $reply_relative_id = $numReplies-4;
                } else {
                    $reply_relative_id = 1;
                }
                while ($line2 = mysql_fetch_assoc($result2)) {
                    $reply_relative_id++;
                    if ($is_even) {
                        echo '<div class="post even">';
                    } else {
                        echo '<div class="post odd">';
                    }
                    echo '<span class="postnum"><a href="javascript:quote('.$reply_relative_id.',\'post'.$line['id'].'\');"';
                    if (!$page)
                    {
                        echo ' name="'.$reply_relative_id.'"';
                    }
                    echo '>'.$reply_relative_id.'</a></span>&nbsp;
                    <span class="postinfo">Name:&nbsp;<span class="postername">';
                    if ($line2['user']==''&&$line2['tripcode']=='') {
                        echo 'Anonymous';
                    } else if ($line2['user']==''&&$line2['tripcode']!='') {
                        echo ''; // If they have a tripcode, just display the tripcode
                    } else {
                        echo stripslashes($line2['user']);
                    }
                    echo '</span><span class="postertrip">';
                    if ($line2['tripcode']!='') {
                        echo '!'.stripslashes($line2['tripcode']);
                    }
                    echo '</span> @ ';
                    echo date("Y-m-d H:i",$line2['postedat']);
                    echo moderator_deleteandbanlinks($tc_config['boardsfolder'],$this->board_dir,$line2['id'],false);
                    echo '<span class="id"></span></span>
                    <blockquote>';
                    echo stripslashes($line2['message']);
                    echo '</blockquote></div>';
                    $is_even = !$is_even;
                }
                echo '
                <form name="post'.$line['id'].'" action="'.$tc_config['boardsfolder'].'/board.php" method="post">
                <input type="hidden" name="board" value="'.$this->board_dir.'" />
                <input type="hidden" name="replythread" value="'.$thread_id.'" />
                <table class="postform">
                <tr>
                <td class="label">Name:</td>
                <td><input name="name" size="25" /></td>
                <td class="label">Email:</td>
                <td><input name="email" size="25" /></td>
                <td><input type="submit" value="Reply" class="submit" /></td>
                </tr>
                <tr>
                <td class="postfieldleft"><span class="postnum">'.($numReplies+2).'</span></td>
                <td colspan="4">
                <textarea name="message" rows="8" cols="64"></textarea></td>
                </tr>
                <tr>
                <td></td>
                </tr>
                </table>
                </form>';
                if ($page) {
                    echo '</div></div>';
                } else {
                    echo '<hr>';
                }
            }
            $clock[] = 'BuildThread Line 3: '.microtime_float();
            $output = ob_get_clean();
            $output = str_replace(chr(9),'',$output);
            $output = str_replace(chr(10),'',$output);
            $output = str_replace(chr(13),'',$output);
            return $output;
        }
        $clock[] = 'BuildThread end: '.microtime_float();
    }
    
    function PageHeader($replythread = '0',$liststart = '0',$listpage = '-1',$liststooutput = '-1') {
        global $tc_config;
        global $clock;
        $output = "";
        $clock[] = 'Pageheader 1: '.microtime_float();
        $output .= '
        <!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
        <html>
        <head>
        <title>';
        if ($tc_config['is_7chan']) {
          $output .= $this->board_desc.' @ 7chan';
        } else {
          $output .= '/'.$this->board_dir.'/ - '.$this->board_desc;
        }
        $output .= '</title><script type="text/javascript">var style_cookie="tcstyle";</script>
        <script type="text/javascript" src="'.$tc_config['boardsfolder'].'/javascript.js">
        </script>';
        if ($this->board_type==0||$this->board_type==2) {
            $output .= '
            <style type="text/css">
            body { margin: 0; padding: 8px; margin-bottom: auto; } blockquote blockquote { margin-left: 0em } form { margin-bottom: 0px } form .trap { display:none } .postarea { text-align: center } .postarea table { margin: 0px auto; text-align: left } .thumb { border: none; float: left; margin: 2px 20px } .nothumb { float: left; background: #eee; border: 2px dashed #aaa; text-align: center; margin: 2px 20px; padding: 1em 0.5em 1em 0.5em; } .reply blockquote, blockquote :last-child { margin-bottom: 0em } .reflink a { color: inherit; text-decoration: none } .reply .filesize { margin-left: 20px } .userdelete { float: right; text-align: center; white-space: nowrap } .replypage .replylink { display: none }
            .admin { color: purple;    font-weight:normal; }
            .mod { color: red; font-weight:normal; }
            </style>';
            if ($tc_config['is_7chan']) {
             $output .= '<link rel="stylesheet" type="text/css" href="'.$tc_config['boardspath'].'/css/burichan.css" title="Burichan">';
            } else {
             $output .= print_stylesheets('Futaba');
            }
        } else {
            $output .= '<link rel="stylesheet" href="'.$tc_config['boardsfolder'].'/css/global.css">';
            $output .= '<link rel="stylesheet" href="'.$tc_config['boardsfolder'].'/css/0ch.css" title="Pseud0ch" media="screen">';
            $output .= '<style type="text/css">body.board {background-image: url('.$tc_config['boardsfolder'].'/ba.gif);}</style>';
        }
        $output .= '<link rel="shortcut icon" href="'.$tc_config['webpath'].'/favicon.ico">
        <meta http-equiv="cache-control" content="no-cache">
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="expires" content="-1">';
        if ($tc_config['is_trevorchan']) {
                    //Remove from release
                    $output .= '<style type="text/css">
                    .adText {
                            font:normal 8pt sans-serif;
                            text-decoration: none;
                            color:#000000;
                    }
                    #ad
                    {
                            width: 150px;
                    
                            margin: 0;
                            padding: 0;
                    
                            position: absolute;
                            right: 1em;
                    
                            border: 1px solid #D6DAF0;
                    
                            font-family: arial,helvetica, sans-serif;
                            font-size: 11px;
                    
                    }
                    
                    #ad div
                    {
                            margin: 0;
                            padding: 0.4em;
                    
                    }
                    
                    #ad div.ad-title
                    {
                            padding: 0em;
                            background: #D6DAF0;
                            color: #800000;
                            font-size: 11px;
                    }
                    
                    #ad div.ad-title a
                    {
                            font-family: arial,helvetica, sans-serif;
                            color: #34345C;
                    }
                    </style>';
        }
        $output .= '</head>';
        if ($this->board_type=='1') {
            if ($replythread=='0') {
                $output .= '<body class="board">';
            } else {
                $output .= '<body class="read">';
            }
        } else {
            $output .= '<body>';
        }
        $clock[] = 'Pageheader 2: '.microtime_float();
        if ($this->board_type=='0'||$this->board_type=='2') {
            $output .= '<div class="adminbar">';
            if (!$tc_config['is_7chan']) {
             $output .= '[<a href="javascript:set_stylesheet(\'Burichan\')">Burichan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Futaba\')">Futaba</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Gurochan\')">Gurochan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Photon\')">Photon</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Fuhrerchan\')">Fuhrerchan</a>]&nbsp;-&nbsp;';
            }
            $output .= '[<a href="'.$tc_config['webpath'].'" target="_top">Home</a>]&nbsp;';
            if (!$tc_config['is_7chan']) {
             $output .= '[<a href="'.$tc_config['boardspath'].'/manage.php">Manage</a>]';
            }
            $output .= '</div>';
            $output .= display_boardlist(false);
        } else {
            $output .= display_boardlist(true);
        }
            if ($tc_config['is_7chan']) {
                $ad_top = '195';
                if ($replythread!='0') {
                    $ad_top += '50';
                }
                // Linjutsu Ad
                if ($board_type != 2) {
                    $output .= '<!-- Begin: Linjutsu --><div id="linjutsu-ad" style="top:'.$ad_top.'px;left:5px;"><a href="http://www.linjutsu.net" target="_new"><img src="http://www.7chan.org/donotblock/linjutsu.jpg" border="0" alt="Linjutsu Hosting"></a></div><!-- End: Linjutsu -->';
                    // Adbrite Ads
                    $output .= '<!-- Begin: AdBrite --><div id="ad" style="top:'.$ad_top.'px;"> <div class="ad-title">Advertisements</div><script type="text/javascript" src="http://ads.adbrite.com/mb/text_group.php?sid=195242&amp;br=1&amp;dk=72656c6174696f6e73686970735f335f325f776562"></script><div><a class="adHeadline" target="_top" href="http://www.adbrite.com/mb/commerce/purchase_form.php?opid=195242&amp;afsid=1">Advertise Here</a></div></div><!-- End: AdBrite -->';
                }
            } elseif ($tc_config['is_trevorchan']) {
        $ad_top = 185;
        $ad_right = 25;
        if ($board_type==1)  {
            $ad_top -= 50;
        } else {
            if ($replythread!=0) {
                $ad_top += 50;
            }
        }
        if ($board_type==2) {
            $ad_top += 30;
        }
        if ($board_type!=1) {
            $output .=  '<div id="ad" style="position: absolute;top:'.$ad_top.'px;right:'.$ad_right.'px">
            <!-- Begin: AdBrite -->
            <script type="text/javascript" src="http://ads.adbrite.com/mb/text_group.php?sid=195328&amp;br=1">
            </script>
            <div><a class="adHeadline" target="_top" href="http://www.adbrite.com/mb/commerce/purchase_form.php?opid=195328&amp;afsid=1">Your Ad Here</a></div>
            <!-- End: AdBrite -->
            </div>';
        }
            } 
        if ($this->board_type==0||$this->board_type==2) {
            $output .= '<div class="logo">';
            if ($this->board_image=="") {
                if ($tc_config['headerurl']!="") {
                    $output .= '<img src="'.$tc_config['headerurl'].'" alt="Logo" /><br />';
                }
            } else if ($this->board_image!=""&&$this->board_image!="none") {
                $output .= '<img src="'.$this->board_image.'" alt="Logo" /><br />';
            }
            if ($tc_config['is_7chan']) {
                            $output .= $this->board_desc.' @ 7chan';
                        } else {
                           $output .= '/'.$this->board_dir.'/ - '.$this->board_desc;
                        }
            $output .= '</div>';
            $output .= stripslashes($this->board_includeheader);
            $output .= '<hr />';
        } else if ($this->board_type==1&&$replythread==0) {
            //Text board - header
            $output .= '<div class="hborder">
                <div class="head">';
                if ($listpage<0||$liststooutput<0) {
                    $output .= '<span class="navlinks"><a href="#0">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>';
                }
                $output .= '<h1>'.$this->board_desc.'</h1>
                </div>
                </div>';
            if ($listpage>=0&&$liststooutput>=0) {
                $output .= 'Pages:&nbsp;<a href="board.html">Front</a>';
                for ($i = 0; $i <= $liststooutput; $i++) {
                    $output .= '&nbsp;<a href="list';
                    if ($i!=0) {
                        $output .= $i;
                    }
                    $output .= '.html">'.($i+1).'</a>';
                }
                $output .= '<br>';
            }
            $is_page = ($listpage<0||$liststooutput<0) ? false : true;
            $output .= display_textboard_threadlist($this->board_id,$liststart,$liststooutput,$is_page);
        }
        $clock[] = 'Pageheader 3: '.microtime_float();
        $output = str_replace(chr(9),"",$output);
        $output = str_replace(chr(10),"",$output);
        $output = str_replace(chr(13),"",$output);
        return $output;
    }
    
    function Postbox($replythread = 0,$oekaki = '',$postboxnotice = '') {
        global $tc_config;
        global $clock;
        $clock[] = 'Postbox 1: '.microtime_float();
        $output = '';
        if ($this->board_type=='1'&&$replythread!='0') {
            //Do nothing
        } else {
            if ($this->board_type==0||$this->board_type==2) {
                $output .= '<div class="postarea">';
                $label_class = 'postblock';
            } else {
                $output .= '<div class="hborder head newthread"><a id="newthread"></a><h2>New Thread</h2>';
                $label_class = 'label';
            }
            if ($this->board_type==2&&$oekaki=='') {
                $output .= '<form action="'.$tc_config['boardsfolder'].'/paint.php" method="post">
                <input type="hidden" name="board" value="'.$this->board_dir.'">
                <input type="hidden" name="replyto" value="'.$replythread.'">
                <label for="applet">Paint with:&nbsp;</label><select name="applet"><option value="shipainter">Shi-Painter</option><option value="shipainterpro">Shi-Painter Pro</option></select>&nbsp;
                <label for="width">Width:&nbsp;<input type="text" name="width" size="3" value="300">&nbsp;<label for="height">Height:&nbsp;<input type="text" name="height" size="3" value="300">&nbsp;';
                if ($replythread!=0) {
                    $output .= '<label for="replyimage">Source:&nbsp;</label><select name="replyimage"><option value="0">New Image</option>';
                    $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `boardid` = '$this->board_id' AND `id` = '$replythread' AND `image` != '' AND `image` != 'removed' AND `imagetype` != 'swf' AND `IS_DELETED` = '0'",$tc_config['dblink']);
                    while ($line = mysql_fetch_assoc($result)) {
                        $output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
                    }
                    $result = mysql_query("SELECT `id` FROM `{$tc_config['dbprefix']}posts` WHERE `boardid` = '$this->board_id' AND `threadid` = '$replythread' AND `image` != '' AND `image` != 'removed' AND `imagetype` != 'swf' AND `IS_DELETED` = '0'",$tc_config['dblink']);
                    while ($line = mysql_fetch_assoc($result)) {
                        $output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
                    }
                    $output .= '</select>&nbsp;';
                }
                $output .= '<input type="submit" value="Paint!"></form><hr>';
            }
            if (($this->board_type==2&&$oekaki!=''&&$replythread==0)||($this->board_type==2&&$replythread!=0)||($this->board_type==0||$this->board_type==1)) {
                $output .= '<form id="postform" action="'.$tc_config['boardspath'].'/board.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="board" value="'.$this->board_dir.'" />';
                if ($replythread!=0) {
                    $output .= '
                    <input type="hidden" name="replythread" value="'.$replythread.'" />';
                }
                $output .= '
                <p>
                <table class="postform"><tbody>';
                if ($this->board_forcedanon!=1) {
                    $output .= '<tr>
                    <td class="'.$label_class.'">Name</td><td><input type="text" name="name" size="28" maxlength="75" /></td>
                    </tr>';
                }
                $output .= '<tr>
                <td class="'.$label_class.'">E-mail</td><td><input type="text" name="email" size="28" maxlength="75" /></td>
                </tr>
                <tr>
                <td class="'.$label_class.'">Subject</td><td><input type="text" name="subject" size="35" maxlength="75" />&nbsp;<input type="submit" value="Submit" /></td>
                </tr>
                <tr>
                <td class="'.$label_class.'">Message</td><td><textarea name="message" cols="48" rows="4"></textarea></td>
                </tr>';
                if ($this->board_type==0||$this->board_type==2) {
                    $output .= '<tr>
                    <td class="'.$label_class.'">File<a href="#" onclick="togglePassword();" style="text-decoration: none;">&nbsp;</a></td><td>';
                    if ($oekaki=='') {
                        $output .= '
                        <input type="file" name="imagefile" size="35" />';
                    } else {
                        $output .= 'Shown Below';
                        $output .= '<input type="hidden" name="oekaki" value="'.$oekaki.'" />';
                    }
                    $output .= '</td>
                    </tr>
                    <tr>
                    <td class="'.$label_class.'">Password</td><td><input type="password" name="postpassword" size="8" />&nbsp;(for post and file deletion)</td>
                    </tr>
                    <tr id="passwordbox">
                    <td></td><td></td>
                    </tr>';
                }
                if ($this->board_type==0||$this->board_type==2) {
                    $output .= '<tr>
                    <td colspan="2"><div class="rules">'.$postboxnotice .'</div></td>
                    </tr>';
                }
                $output .= '</tbody></table></form>';
                if ($this->board_type==0||$this->board_type==2) {
                    $output .= '<hr>';
                }
            }
            $output .= '</div><script type="text/javascript">set_inputs("postform")</script>';
        }
        $clock[] = 'Postbox 2: '.microtime_float();
        $output = str_replace(chr(9),'',$output);
        $output = str_replace(chr(10),'',$output);
        $output = str_replace(chr(13),'',$output);
        return $output;
    }
    
    function Footer($noboardlist = false,$executiontime = 0,$hide_extra = false) {
        global $tc_config;
        if (!$hide_extra) {
            if ($noboardlist==false) {
                $output = '<br />'.display_boardlist();
            } else {
                $output = '';
            }
        }
        if ($hide_extra) {
            $output .= '<div class="legal">';
        } else {
            $output .= '<p class="footer">';
        }
        $output .= '- <a href="http://www.trevorchan.org/" target="_top">Trevorchan</a> v'.$tc_config['version'].' + Created by <a href="http://www.tj9991.com/" target="_top">tj9991</a> -';
        if ($executiontime>0) {
            $output .= '<br>Took '.round($executiontime,2).'s';
        }
        if ($hide_extra) {
            $output .= '<br><a href="'.$tc_config['boardspath'].'/manage.php">Manage Boards</a></div>';
        } else {
            $output .= '</p>';
        }
        $output .= '</body>
        </html>';
        $output = str_replace(chr(9),"",$output);
        $output = str_replace(chr(10),"",$output);
        $output = str_replace(chr(13),"",$output);
        return $output;
    }
}

class Post extends Board {
    function Post($postid,$boardid,$is_inserting = false) {
        global $tc_config;
        $result = mysql_query("SELECT * FROM `{$tc_config['dbprefix']}posts` WHERE `id` = '".mysql_real_escape_string($postid)."' AND `boardid` = '".mysql_real_escape_string($boardid)."' LIMIT 1",$tc_config['dblink']);
        $rows = mysql_num_rows($result);
        if ($rows==0&&!$is_inserting) {
            die("Invalid post ID.");
        } elseif ($is_inserting) {
            $this->post_id = $postid;
            $this->board_id = $boardid;
        } else {
            while ($line = mysql_fetch_assoc($result)) {
                $this->post_id = $line['id'];
                $this->post_threadid = $line['threadid'];
                $this->post_boardid = $line['boardid'];
                $this->post_image = $line['image'];
                $this->post_imagetype = $line['imagetype'];
                $this->post_password = $line['password'];
            }
            $this->board_id = $this->post_boardid;
            $result = mysql_query("SELECT `cleared` FROM `{$tc_config['dbprefix']}reports` WHERE `boardid` = '".mysql_real_escape_string($this->board_id)."' AND `postid` = '".mysql_real_escape_string($this->post_id)."' LIMIT 1",$tc_config['dblink']);
            $rows = mysql_num_rows($result);
            if ($rows>0) {
                while ($line = mysql_fetch_assoc($result)) {
                    if ($line['cleared']=='0') {
                        $this->post_isreported = true;
                    } else {
                        $this->post_isreported = 'cleared';
                    }
                }
            } else {
                $this->post_isreported = false;
            }
            if ($this->post_threadid==0) {
                $this->post_isthread = true;
            } else {
                $this->post_isthread = false;
            }
            $this->Init();
        }
    }

    function Delete() {
        global $tc_config;
        $i = 0;
        if ($this->post_isthread==true) {
            $result = mysql_query("SELECT `id`,`image`,`imagetype` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND `threadid` = '".mysql_real_escape_string($this->post_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."'",$tc_config['dblink']);
            while ($line = mysql_fetch_assoc($result)) {
                $i++;
                mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `IS_DELETED` = '1' WHERE `id` = '".$line['id']."' AND `threadid` = '".mysql_real_escape_string($this->post_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' LIMIT 1",$tc_config['dblink']);
            }
            unlink($tc_config['boardsdir'].'/'.$this->board_dir.'/res/'.$this->post_id.'.html');
            $this->DeleteImage(false,true);
            mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `IS_DELETED` = '1' WHERE `id` = '".mysql_real_escape_string($this->post_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' LIMIT 1",$tc_config['dblink']);
            return $i.' ';
        } else {
            $this->DeleteImage(false);
            mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `IS_DELETED` = '1' WHERE `id` = '".mysql_real_escape_string($this->post_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' LIMIT 1",$tc_config['dblink']);
            return true;
        }
    }

    function DeleteImage($update_to_removed = true, $whole_thread = false) {
        global $tc_config;
        if ($whole_thread&&$this->post_isthread) {
            $result = mysql_query("SELECT `id`,`image`,`imagetype` FROM `{$tc_config['dbprefix']}posts` WHERE `IS_DELETED` = 0 AND `threadid` = ".mysql_real_escape_string($this->post_id)." AND `boardid` = ".mysql_real_escape_string($this->board_id)." LIMIT 1",$tc_config['dblink']);
            $rows = mysql_num_rows($result);
            if ($rows>0) {
                while ($line = mysql_fetch_assoc($result)) {
                    if ($line['image']!=''&&$line['image']!="removed") {
                        unlink($tc_config['boardsdir'].'/'.$this->board_dir.'/src/'.$line['image'].'.'.$line['imagetype']);
                        unlink($tc_config['boardsdir'].'/'.$this->board_dir.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
                    }
                    if ($update_to_removed) {
                        mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `image` = 'removed' WHERE `id` = '".$line['id']."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' LIMIT 1",$tc_config['dblink']);
                    }
                }
            }
            $this->DeleteImage($update_to_removed);
        } else {
            if ($this->post_image!=''&&$this->post_image!='removed') {
                unlink($tc_config['boardsdir'].'/'.$this->board_dir.'/src/'.$this->post_image.'.'.$this->post_imagetype);
                unlink($tc_config['boardsdir'].'/'.$this->board_dir.'/thumb/'.$this->post_image.'s.'.$this->post_imagetype);
                if ($update_to_removed) {
                    mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `image` = 'removed' WHERE `id` = '".mysql_real_escape_string($this->post_id)."' AND `boardid` = '".mysql_real_escape_string($this->board_id)."' LIMIT 1",$tc_config['dblink']);
                }
            }
        }
    }
    
    function Insert($threadid,$user,$tripcode,$email,$subject,$message,$image,$imagetype,$imagemd5,$image_w,$image_h,$image_size,$thumb_w,$thumb_h,$password,$postedat,$lastbumped,$ip,$posterauthority,$stickied,$locked) {
        global $tc_config;
        require_once($tc_config['rootdir']."/inc/encryption.php");
        $query = "INSERT INTO `{$tc_config['dbprefix']}posts` ( `boardid` , `id` , `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `imagemd5` , `image_w` , `image_h` , `image_size` , `thumb_w` , `thumb_h` , `password` , `postedat` , `lastbumped` , `ip` , `ipmd5` , `posterauthority` , `stickied` , `locked` ) VALUES ( '".mysql_real_escape_string($this->board_id)."','".mysql_real_escape_string($this->post_id)."','".mysql_real_escape_string($threadid)."','".mysql_real_escape_string($user)."','".mysql_real_escape_string($tripcode)."','".mysql_real_escape_string($email)."','".mysql_real_escape_string($subject)."','".mysql_real_escape_string($message)."','".mysql_real_escape_string($image)."','".mysql_real_escape_string($imagetype)."','".mysql_real_escape_string($imagemd5)."','".mysql_real_escape_string($image_w)."','".mysql_real_escape_string($image_h)."','".mysql_real_escape_string($image_size)."','".mysql_real_escape_string($thumb_w)."','".mysql_real_escape_string($thumb_h)."','".mysql_real_escape_string($password)."','".mysql_real_escape_string($postedat)."','".mysql_real_escape_string($lastbumped)."','".mysql_real_escape_string(md5_encrypt($ip,$tc_config['randomseed']))."','".md5($ip)."','".mysql_real_escape_string($posterauthority)."','".mysql_real_escape_string($stickied)."','".mysql_real_escape_string($locked)."' )";
        return mysql_query($query,$tc_config['dblink']);
    }

    function Report() {
        global $tc_config;
        require_once($tc_config['rootdir']."/inc/encryption.php");
        $result = mysql_query("INSERT INTO `{$tc_config['dbprefix']}reports` ( `boardid` , `postid` , `when` , `ip` ) VALUES ( ".mysql_real_escape_string($this->board_id)." , ".mysql_real_escape_string($this->post_id)." , ".time()." , '".md5_encrypt($_SERVER['REMOTE_ADDR'],$tc_config['randomseed'])."' )",$tc_config['dblink']);
        if ($result) {
            return true;
        } else {
            return false;
        }
    }
}

?>
