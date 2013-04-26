<?php
/*
* +------------------------------------------------------------------------------+
* Classes derived from board and post functions from functions.php
* +------------------------------------------------------------------------------+
* These classes are used for most board and post operations.  The functions were
* switched to use this system for increased readability and ease of use.  By
* using OOP, the overall flow of the program is more stable.
* +------------------------------------------------------------------------------+
*/
class Board {
    function Board($board) {
        global $tc_db;

        if ($board!='') {
        	$query = "SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($board)."' LIMIT 1";
            $results = (TC_DBUSECACHE) ? $tc_db->CacheGetAssoc($query) : $tc_db->GetAssoc($query); 
            foreach($results AS $line) {
                $this->board_id = $line['id'];
                $this->board_type = $line['type'];
                $this->board_uploadtype = $line['uploadtype'];
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
                $this->board_enablecaptcha = $line['enablecaptcha'];
            }
            $this->config_postboxnotice = format_postboxnotice(config_getvalue('postboxnotice'),$this->board_dir);
            $this->config_numrepliesdisplayed = config_getvalue("numrepliesdisplayed");
            $this->config_numrepliesdisplayedsticky = config_getvalue("numrepliesdisplayedsticky");
            $this->config_numthreadsdisplayed = config_getvalue("numthreadsdisplayed");
            $this->config_imagesinnewwindow = config_getvalue("imagesinnewwindow");
        }
    }

    /* Regenerate all board and thread pages */
    function RegenerateAll() {
        $this->RegeneratePages();
        $this->RegenerateThreads();
    }

    function RegeneratePages() {
        global $tc_db;
        global $clock;
        //$clock[] = 'Regenerate pages start: '.microtime_float();
        //$clock[] = '1: '.microtime_float();
        $numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
        if ($this->board_type==1) {
            $numthreadsdisplayed = 15;
            $hide_extra = true;
        } else {
            $numthreadsdisplayed = $this->config_numthreadsdisplayed;
            $hide_extra = false;
        }
        //$clock[] = '2: '.microtime_float();
        $boardstooutput = floor(($numpostsleft-1) / $numthreadsdisplayed);
        $boardpage = 0;
        if ($numpostsleft>0) {
            $cached_pageheader = $this->PageHeader($this->board_dir);
            $cached_postbox = $this->Postbox(0, '', $this->config_postboxnotice);
            while ($numpostsleft>0) {
                //$clock[] = 'RUN 1: '.microtime_float();
                $executiontime_start_regeneratepages = microtime_float();
                $page = $cached_pageheader;
                $page .= $cached_postbox;
                if ($this->board_type!=1) {
                    $page .= '<form id="delform" action="'.TC_BOARDSFOLDER.'board.php" method="post"><input type="hidden" name="board" value="'.mysql_real_escape_string($this->board_dir).'" />';
                }
                $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".($boardpage*$numthreadsdisplayed).','.$numthreadsdisplayed);
                $thread_relative_id = 0;
                $thread_ids = array();
                foreach($results AS $line) { 
                    $thread_ids[] = $line[0];
                    //$thread_relative_id++;
                }
                $page .= $this->BuildThread($thread_ids,true,false,$thread_relative_id);
                //$clock[] = 'RUN 2: '.microtime_float();
                if ($this->board_type!=1) {
                    $page .= '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br>Password <input type="password" name="postpassword" size="8" />&nbsp;<input name="deletepost" value="Delete" type="submit" />';
                    if ($this->board_enablereporting==1) {
                        $page .= '<input name="reportpost" value="Report" type="submit" />';
                    }
                    $page .= '</td></tr></tbody></table></form><script type="text/javascript">set_delpass("delform")</script><table border="1"><tbody><tr><td>';
                    if ($boardpage==0) {
                        $page .= 'Previous';
                    } else {
                        if ($boardpage-1!=0) {
                            $page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/'.($boardpage-1).'.html"><input value="Previous" type="submit" /></form>';
                        } else {
                            $page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/board.html"><input value="Previous" type="submit" /></form>';
                        }
                    }
                    $page .= '</td><td>';
                    for ($i=0;$i<=$boardstooutput;$i++) {
                        if ($boardpage==$i) {
                            $page .= '&#91;'.$i.'&#93;';
                        } else {
                            if ($i!=0) {
                                $page .= '&#91;<a href="'.TC_BOARDSFOLDER.$this->board_dir.'/'.$i.'.html">'.$i.'</a>&#93;';
                            } else {
                                $page .= '&#91;<a href="'.TC_BOARDSFOLDER.$this->board_dir.'/board.html">'.$i.'</a>&#93;';
                            }
                        }
                    }
                    $page .= '</td><td>';
                    if ($boardpage==$boardstooutput) {
                        $page .= 'Next';
                    } else {
                        $page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/'.($boardpage+1).'.html"><input value="Next" type="submit" /></form>';
                    }
                    $page .= '</td></tr></tbody></table>';
                }
                //$clock[] = 'RUN 3: '.microtime_float();
                $page .= $this->Footer(false,(microtime_float()-$executiontime_start_regeneratepages),$hide_extra);
                if ($boardpage==0) {
                    print_page(TC_BOARDSDIR.$this->board_dir."/board.html",$page,$this->board_dir);
                } else {
                    print_page(TC_BOARDSDIR.$this->board_dir."/".$boardpage.".html",$page,$this->board_dir);
                }
                $page = '';
                $boardpage++;
                if ($this->board_type==1) {
                    $numpostsleft = 0;
                } else {
                    $numpostsleft -= $numthreadsdisplayed;
                }
                //$clock[] = 'RUN 4: '.microtime_float();
            }
        } else { //Make a blank index
            $executiontime_start_blankindex = microtime_float();
            $page = $this->PageHeader();
            $page .= $this->Postbox(0, '', $this->config_postboxnotice);
            $page .= $this->Footer(false,(microtime_float()-$executiontime_start_blankindex),$hide_extra);
            print_page(TC_BOARDSDIR.$this->board_dir."/board.html",$page,$this->board_dir);
        }
        //$clock[] = '3: '.microtime_float();
        //If text board, rebuild thread list html files
        if ($this->board_type==1) {
            $numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
            $liststooutput = floor(($numpostsleft-1) / 40);
            $listpage = 0;
            $currentpostwave = 0;
            while ($numpostsleft>0) {
                $executiontime_start_list = microtime_float();
                $page = $this->PageHeader(0, $currentpostwave, $listpage, $liststooutput);
                $page .= $this->Footer(false,(microtime_float()-$executiontime_start_list),$hide_extra);
                if ($listpage==0) {
                    print_page(TC_BOARDSDIR.$this->board_dir.'/list.html',$page,$this->board_dir);
                } else {
                    print_page(TC_BOARDSDIR.$this->board_dir.'/list'.$listpage.'.html',$page,$this->board_dir);
                }
                $currentpostwave += 40;
                $numpostsleft -= 40;
                $listpage++;
            }
        }
        //$clock[] = '4: '.microtime_float();
        //Delete old pages
        $dir = TC_BOARDSDIR.$this->board_dir;
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
        //$clock[] = 'Regenerate pages end: '.microtime_float();
    }

    function RegenerateThreads() {
        global $tc_db;
        $res_threadlist = array();
        $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `threadid` = 0 AND `IS_DELETED` = 0 ORDER BY `lastbumped` DESC");
        foreach($results AS $line) {
            $res_threadlist = array_merge($res_threadlist,array($line['id'].'.html'));
            $this->RegenerateThread($line['id']);
        }
        $dir = TC_BOARDSDIR.$this->board_dir.'/res';
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
        global $tc_db;
        $resultsboard = $tc_db->CacheGetAssoc("SELECT `id`,`type`,`name` FROM `".TC_DBPREFIX."boards` WHERE `id` = ".mysql_real_escape_string($this->board_id)." LIMIT 1");
        foreach($resultsboard AS $lineboard) {
            $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 AND `id` = ".mysql_real_escape_string($threadid)." LIMIT 1");
            foreach($results AS $line) {
                $executiontime_start_regeneratethread = microtime_float();
                $thread_page = '';
                $this->post_id_id = $line['id'];
                $thread_page .= $this->PageHeader($threadid, '', $this->config_postboxnotice);
                if ($lineboard['type']==1) {
                    $thread_page .= "<a href=\"".TC_BOARDSPATH."/{$lineboard['name']}/board.html\">Return</a><br><br>";
                    $hide_extra = true;
                } else {
                    $thread_page .= "&#91;<a href=\"".TC_BOARDSPATH."/{$lineboard['name']}/board.html\">Return</a>&#93; <div class=\"theader\">Posting mode: Reply</div>";
                    $hide_extra = false;
                }
                $thread_page .= $this->Postbox($threadid,'',$this->config_postboxnotice);
                $thread_page .= $this->BuildThread($this->post_id_id);
                $thread_page .= $this->Footer(false,(microtime_float()-$executiontime_start_regeneratethread),$hide_extra);
                print_page(TC_BOARDSDIR.$lineboard['name'].'/res/'.$threadid.'.html',$thread_page,$lineboard['name']);
            }
        }
    }

    function BuildThread($threadid, $page = false, $resurrect = false, $thread_relative_id = 0) {
        global $tc_db;
        global $clock;
        //$clock[] = 'BuildThread start: '.microtime_float();
        if (!is_array($threadid)) {
            $threadid = array($threadid);
        }
        $buildthread_output = '';
        foreach ($threadid AS $this_threadid) {
            if ($this_threadid<=0) {
                die("Invalid arguments sent to function buildthread(): threadid <= 0");
            }
        }
        if ($this->board_dir=='') {
            die("Invalid arguments sent to function buildthread(): boarddir = ''");
        }
        if ($resurrect) {
            $isdeleted_check = '1';
        } else {
            $isdeleted_check = '0';
        }
        if ($this->board_type==1) {
            $results = $tc_db->GetAll('SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = 0 ORDER BY `stickied`,`lastbumped` DESC LIMIT 15');
            $num_threads_onfrontpage = $results[0][0];
        }
        $query_idsegment = '';
        foreach ($threadid as $this_threadid) {
            $query_idsegment .= '`id` = '.mysql_real_escape_string($this_threadid).' OR ';
        }
        $query_idsegment = substr($query_idsegment,0,-4);
        $results = $tc_db->GetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND ('.$query_idsegment.') AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC');
        if (count($results)==0) {
            die('No posts in thread to build from.');
        }
        $final_output = '';
        if (!$page&&$this->board_type!=1) {
           $buildthread_output .= '<form id="delform" action="'.TC_BOARDSFOLDER.'board.php" method="post"><input type="hidden" name="board" value="'.$this->board_dir.'" />';
        }
        //$clock[] = 'BuildThread 1: '.microtime_float();
        foreach($results AS $line) {
            //$clock[] = 'BuildThread Line 1: '.microtime_float();
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
            //$clock[] = 'BuildThread Line 2: '.microtime_float();
            $results2 = $tc_db->GetAll('SELECT `id` FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id));
            $numReplies = count($results2);
            if ($this->board_type==0||$this->board_type==2) {
                $numImageReplies = 0;
                if ($page==true) {
                    if ($line['stickied']==0) {
                        if ($numReplies>$this->config_numrepliesdisplayed) {
                            $results = $tc_db->GetAll('SELECT `id` FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' AND `image` != \'\' ORDER BY `postedat` ASC LIMIT 0,'.($numReplies-$this->config_numrepliesdisplayed));
                            $numImageReplies = count($results);
                        }
                    } else {
                        if ($numReplies>$this->config_numrepliesdisplayedsticky) {
                            $results = $tc_db->GetAll('SELECT `id` FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' AND `image` != \'\' ORDER BY `postedat` ASC LIMIT 0,'.($numReplies-$this->config_numrepliesdisplayedsticky));
                            $numImageReplies = count($results);
                        }
                    }
                }
                //$clock[] = 'BuildThread Line Mark 1: '.microtime_float();
                if ($line['imagetype']!='you'&&$line['imagetype']!='goo') {
                    $buildthread_output .= '<span class="filesize">File: <a ';
                    if ($this->config_imagesinnewwindow==1) {
                        $buildthread_output .= 'target="_blank" ';
                    }
                    $buildthread_output .= 'href="';
                    if ($line['image']=='removed') {
                        $buildthread_output .= TC_BOARDSFOLDER.'imageremoved.png">imageremoved.png';
                    } else {
                        $buildthread_output .= TC_BOARDSFOLDER.$this->board_dir.'/src/'.$line['image'].'.'.$line['imagetype'].'">'.$line['image'].'.'.$line['imagetype'];
                    }
                    $buildthread_output .= '</a> -(<em>';
                    if ($line['image']=='removed'||$resurrect) {
                        $buildthread_output .= '&nbsp';
                    } else {
                        $buildthread_output .= round($line['image_size']/1024)." KB, {$imgWidth}x$imgHeight";
                    }
                    $buildthread_output .= '</em>)</span><span class="thumbnailmsg"> Thumbnail displayed, click image for full size.</span><br>';
                    if (!$resurrect) {
                        $buildthread_output .= '<a ';
                        if ($this->config_imagesinnewwindow==1) {
                            $buildthread_output .= 'target="_blank" ';
                        }
                        $buildthread_output .= 'href="';
                        if ($line['image']=='removed') {
                            $buildthread_output .= TC_BOARDSFOLDER.'imageremoved.png"><img src="'.TC_BOARDSFOLDER.'imageremoved.png';
                        } else if ($line['imagetype']=="swf") {
                            $buildthread_output .= TC_BOARDSPATH."/{$this->board_dir}/src/{$line['image']}.{$line['imagetype']}\"><img src=\"".TC_BOARDSPATH."/flash.png";
                        } else {
                            $buildthread_output .= TC_BOARDSPATH."/{$this->board_dir}/src/{$line['image']}.{$line['imagetype']}\"><img src=\"".TC_BOARDSPATH."/{$this->board_dir}/thumb/{$line['image']}s.{$line['imagetype']}";
                        }
                        $buildthread_output .= "\" width=\"$imgWidth_thumb\" height=\"$imgHeight_thumb\" alt=\"$thread_id\" class=\"thumb\" /></a>";
                    }
                }
                //$clock[] = 'BuildThread Line Mark 2: '.microtime_float();
                $buildthread_output .= "<a name=\"$thread_id\"></a><label><input type=\"checkbox\" name=\"delete\" value=\"$thread_id\" />&nbsp;";
                if ($line['subject']!='') {
                    $buildthread_output .= '<span class="filetitle">'.stripslashes($line['subject']).'</span> ';
                }
                $buildthread_output .= '<span class="postername">';
                if ($line['email']!='') {
                    $buildthread_output .= '<a href="mailto:'.$line['email'].'">';
                }
                if ($line['user']==''&&$line['tripcode']=='') {
                    $buildthread_output .= 'Anonymous';
                } else if ($line['user']==''&&$line['tripcode']!='') {
                    $buildthread_output .= ''; // If they have a tripcode, just display the tripcode
                } else {
                    $buildthread_output .= stripslashes($line['user']);
                }
                if ($line['email']!='') {
                    $buildthread_output .= '</a>';
                }
                $buildthread_output .= '</span>';
                if ($line['tripcode']!='') {
                    $buildthread_output .= '<span class="postertrip">!'.$line['tripcode'].'</span>';
                }
                //$clock[] = 'BuildThread Line Mark 3: '.microtime_float();
                if ($line['posterauthority']>0) {
                    if ($line['posterauthority']==1) {
                        $buildthread_output .= ' <span class="admin">##&nbsp;Admin&nbsp;##</span>';
                    } else if ($line['posterauthority']==2) {
                        $buildthread_output .= ' <span class="mod">##&nbsp;Mod&nbsp;##</span>';
                    }
                }
                $buildthread_output .= ' '.date("y/m/d(D)H:i",$line['postedat']).'</label> <span class="reflink"><a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$thread_id.'.html#'.$thread_id.'">No.</a><a href="'.TC_BOARDSPATH.'/'.$this->board_dir.'/res/'.$thread_id.'.html#i'.$thread_id.'"';
                if (!$page) {
                    $buildthread_output .= ' onclick="insert(\'>>'.$thread_id.'\');"';
                }
                //$clock[] = 'BuildThread Line Mark 2.1: '.microtime_float();
                $buildthread_output .= ">$thread_id</a>";
                //$clock[] = 'BuildThread Line Mark 2.2: '.microtime_float();
                if ($line['locked']==1) {
                    $buildthread_output .= '&nbsp;<img style="border: 0;" src="'.TC_BOARDSPATH.'/locked.gif" alt="locked" />';
                }
                if ($line['stickied']==1) {
                    $buildthread_output .= '<img style="border: 0;" src="'.TC_BOARDSPATH.'/sticky.gif" alt="stickied" />';
                }
                $buildthread_output .= '</span>';
                if ($page) {
                    $buildthread_output .= '&nbsp;&#91;<a href="'.TC_BOARDSPATH.'/'.$this->board_dir.'/res/'.$thread_id.'.html">Reply</a>&#93;';
                }
                //$clock[] = 'BuildThread Line Mark 2.3: '.microtime_float();
                $buildthread_output .= $this->DeleteAndBanLinks($thread_id,true);
                //$clock[] = 'BuildThread Line Mark 2.4: '.microtime_float();
                $buildthread_output .= '<blockquote><p>';
                if ($line['imagetype']=='you'||$line['imagetype']=='goo') {
                    $buildthread_output .= '<span style="float: left;">';
                    if ($line['imagetype']=='you') {
                        $buildthread_output .= '<object width="200" height="164"><param name="movie" value="http://www.youtube.com/v/'.$line['image'].'"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/'.$line['image'].'" type="application/x-shockwave-flash" wmode="transparent" width="200" height="164"></embed></object>';
                    } elseif ($line['imagetype']=='goo') {
                        $buildthread_output .= '<embed style="width:200px; height:164px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId='.$line['image'].'&hl=en" flashvars=""></embed>';
                    }
                    $buildthread_output .= '</span>&nbsp;';
                }
                if (count(explode(chr(10), $line['message']))>15&&$page) {
                    $message_exploded = explode(chr(10), stripslashes($line['message']));
                    $message_shortened = '';
                    for ($i = 0; $i <= 14; $i++) {
                        $message_shortened .= $message_exploded[$i];
                    }
                    $message_shortened = preg_replace('/<a href="'.preg_quote(TC_BOARDSFOLDER).$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $message_shortened);
                    $message_shortened = closeOpenTags($message_shortened);
                    //$clock[] = 'BuildThread Line Mark 2.5: '.microtime_float();
                    $buildthread_output .= $message_shortened;
                    $buildthread_output .= '<div class="abbrev">Comment too long. Click <a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
                } else {
                    $buildthread_output .= stripslashes($line['message']);
                }
                //$clock[] = 'BuildThread Line Mark 4: '.microtime_float();
                $buildthread_output .= '</blockquote>';
                if ($numReplies>0) {
                    if (!$page) {
                        $results = $tc_db->CacheGetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `postedat` ASC');
                    } else {
                        if ($line['stickied']==0) {
                            $numrepliesdisplayed = $this->config_numrepliesdisplayed;
                        } else {
                            $numrepliesdisplayed = $this->config_numrepliesdisplayedsticky;
                        }
                        if ($numReplies>$numrepliesdisplayed) {
                            $buildthread_output .= '<span class="omittedposts"> '.($numReplies-$numrepliesdisplayed).' post';
                            $buildthread_output .= ($numReplies-$numrepliesdisplayed!=1) ? 's' : '';
                            if ($numImageReplies>0) {
                                $buildthread_output .= ' and '.$numImageReplies.' image';
                                $buildthread_output .= ($numImageReplies!=1) ? 's' : '';
                            }
                            $buildthread_output .= ' omitted. Click Reply to view.  </span>';
                        }
                        $results = array_reverse($tc_db->CacheGetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `postedat` DESC LIMIT '.$numrepliesdisplayed)); /* Retrieves the three newest posts from the thread in descending order, which is backwards for what we want, so we apply array_reverse on the result */
                    }
                    //$clock[] = 'BuildThread Line Mark 5: '.microtime_float();
                    foreach($results AS $line_reply) {
                        //$clock[] = 'BuildThread Line Mark 5 Reply 1: '.microtime_float();
                        if ($line_reply['image']!='') {
                            if ($resurrect==false) {
                                if ($line_reply['image']=='removed') {
                                    $reply_imgWidth = '0';
                                    $reply_imgHeight = '0';
                                    $reply_imgWidth_thumb = '189';
                                    $reply_imgHeight_thumb = '16';
                                } else if ($line_reply['imagetype']=='swf') {
                                    $reply_imgWidth = '0';
                                    $reply_imgHeight = '0';
                                    $reply_imgWidth_thumb = '86';
                                    $reply_imgHeight_thumb = '86';
                                } else if ($line_reply['image']!='') {
                                    $reply_imgWidth = $line_reply['image_w'];
                                    $reply_imgHeight = $line_reply['image_h'];
                                    $reply_imgWidth_thumb = $line_reply['thumb_w'];
                                    $reply_imgHeight_thumb = $line_reply['thumb_h'];
                                }
                            }
                        }
                        //$clock[] = 'BuildThread Line Mark 5 Reply 2: '.microtime_float();
                        $buildthread_output .= '<table><tbody><tr>
                        <td class="doubledash">&gt;&gt;</td>
                        <td class="reply" id="reply'.$line_reply['id'].'"><a name="'.$line_reply['id'].'"></a>
                        <label><input type="checkbox" name="delete" value="'.$line_reply['id'].'" />&nbsp;';
                        if ($line_reply['subject']!='') { $buildthread_output .= '<span class="filetitle">'.stripslashes($line_reply['subject']).'</span>&nbsp;'; }
                        $buildthread_output .= '<span class="commentpostername">';
                        if ($line_reply['email']!='') {
                            $buildthread_output .= '<a href="';
                            if (strtolower($line_reply['email'])!="age"&&strtolower($line_reply['email'])!="sage") {
                                $buildthread_output .= 'mailto:';
                            }
                            $buildthread_output .= stripslashes($line_reply['email']).'">';
                        }
                        //$clock[] = 'BuildThread Line Mark 5 Reply 3: '.microtime_float();
                        if (stripslashes($line_reply['user'])==''&&$line_reply['tripcode']=='') {
                            $buildthread_output .= 'Anonymous';
                        } else if (stripslashes($line_reply['user'])==''&&$line['tripcode']!='') {
                            // If they have a tripcode, just display the tripcode
                        } else {
                            $buildthread_output .= stripslashes($line_reply['user']);
                        }
                        if (stripslashes($line_reply['email'])!='') {
                            $buildthread_output .= '</a>';
                        }
                        //$clock[] = 'BuildThread Line Mark 5 Reply 4: '.microtime_float();
                        $buildthread_output .= '</span>';
                        if ($line_reply['tripcode']!='') {
                            $buildthread_output .= '<span class="postertrip">!'.$line_reply['tripcode'].'</span>';
                        }
                        if ($line_reply['posterauthority']) {
                            if ($line_reply['posterauthority']==1) {
                                $buildthread_output .= ' <span class="admin">##&nbsp;Admin&nbsp;##</span>';
                            } else if ($line_reply['posterauthority']==2) {
                                $buildthread_output .= ' <span class="mod">##&nbsp;Mod&nbsp;##</span>';
                            }
                        }
                        $buildthread_output .= ' '.date('y/m/d(D)H:i',$line_reply['postedat']);
                        $buildthread_output .= '</label> <span class="reflink"><a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$thread_id.'.html#'.$line_reply['id'].'">No.</a><a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$thread_id.'.html#i'.$line_reply['id'].'"';
                        if (!$page) {
                            $buildthread_output .= ' onclick="insert(\'>>'.$line_reply['id'].'\');"';
                        }
                        //$clock[] = 'BuildThread Line Mark 5 Reply 5: '.microtime_float();
                        $buildthread_output .= '>'.$line_reply['id'].'</a></span>'.$this->DeleteAndBanLinks($line_reply['id'],false).'&nbsp;<br>';
                        if ($line_reply['image']!=''&&!$resurrect&&$line_reply['imagetype']!='you'&&$line_reply['imagetype']!='goo') {
                            //$clock[] = 'BuildThread Line Mark 5 Reply 6: '.microtime_float();
                            $buildthread_output .= '<span class="filesize">File: <a ';
                            if ($this->config_imagesinnewwindow==1) {
                                $buildthread_output .= 'target="_blank" ';
                            }
                            $buildthread_output .= 'href="';
                            if ($line_reply['image']=='removed') {
                                $buildthread_output .= TC_BOARDSFOLDER.'imageremoved.png">imageremoved.png';
                            } else {
                                $buildthread_output .= TC_BOARDSFOLDER.$this->board_dir.'/src/'.$line_reply['image'].'.'.$line_reply['imagetype'].'">'.$line_reply['image'].'.'.$line_reply['imagetype'];
                            }
                            $buildthread_output .= '</a> -(<em>';
                            if ($line_reply['image']=='removed') {
                                $buildthread_output .= '&nbsp';
                            } else {
                                $buildthread_output .= round($line_reply['image_size']/1024)." KB, {$reply_imgWidth}x$reply_imgHeight";
                            }
                            //$clock[] = 'BuildThread Line Mark 5 Reply 7: '.microtime_float();
                            $buildthread_output .= '</em>)</span> <span class="thumbnailmsg">Thumbnail displayed, click image for full size.</span><br><a ';
                            if ($this->config_imagesinnewwindow==1)
                            {
                                $buildthread_output .= 'target="_blank" ';
                            }
                            $buildthread_output .= 'href="';
                            if ($line_reply['image']=='removed') {
                                $buildthread_output .= TC_BOARDSFOLDER.'imageremoved.png"><img src="'.TC_BOARDSFOLDER.'imageremoved.png';
                            } else if ($line_reply['imagetype']=='swf') {
                                $buildthread_output .= TC_BOARDSPATH.'/'.$this->board_dir.'/src/'.$line_reply['image'].'.'.$line_reply['imagetype'].'"><img src="'.TC_BOARDSPATH.'/flash.png';
                            } else {
                                $buildthread_output .= TC_BOARDSPATH.'/'.$this->board_dir.'/src/'.$line_reply['image'].'.'.$line_reply['imagetype'].'"><img src="'.TC_BOARDSPATH.'/'.$this->board_dir.'/thumb/'.$line_reply['image'].'s.'.$line_reply['imagetype'];
                            }
                            $buildthread_output .= '" width="'.$reply_imgWidth_thumb.'" height="'.$reply_imgHeight_thumb.'" alt="'.$line_reply['id'].'" class="thumb" /></a>';
                        }
                        //$clock[] = 'BuildThread Line Mark 5 Reply 8: '.microtime_float();
                        $buildthread_output .= '<blockquote><p>';
                        if ($line_reply['imagetype']=='you'||$line_reply['imagetype']=='goo') {
                            $buildthread_output .= '<span style="float: left;">';
                            if ($line_reply['imagetype']=='you') {
                                $buildthread_output .= '<object width="200" height="164"><param name="movie" value="http://www.youtube.com/v/'.$line_reply['image'].'"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/'.$line_reply['image'].'" type="application/x-shockwave-flash" wmode="transparent" width="200" height="164"></embed></object>';
                            } elseif ($line_reply['imagetype']=='goo') {
                                $buildthread_output .= '<embed style="width:200px; height:164px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId='.$line_reply['image'].'&hl=en" flashvars=""></embed>';
                            }
                            $buildthread_output .= '</span>&nbsp;';
                        }
                        if (count(explode(chr(10), stripslashes($line_reply['message'])))>15&&$page) {
                            $message_exploded = explode(chr(10), stripslashes($line_reply['message']));
                            $message_shortened = '';
                            for ($i = 0; $i <= 14; $i++) {
                                $message_shortened .= $message_exploded[$i];
                            }
                            $message_shortened = preg_replace('/<a href="'.preg_quote(TC_BOARDSFOLDER).$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $message_shortened);
                            $message_shortened = closeOpenTags($message_shortened);
                            $buildthread_output .= $message_shortened;
                            $buildthread_output .= '<div class="abbrev">Comment too long. Click <a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
                        } else {
                            //$buildthread_output .= '/[\<a href\="\/b\/res\/'.$thread_id.'\.html#i]([0-9]+)["\>&gt;&gt;]/'.'<br><br>'.'<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;\\1</a>'.'<br><br>';
                            if ($page==true) {
                                $buildthread_output .= preg_replace('/<a href="\/'.$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', stripslashes($line_reply['message']));
                            } else {
                                $buildthread_output .= preg_replace('/<a href="\/'.$this->board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$this->board_dir.'/res/'.$thread_id.'.html#\\1" onclick="highlight(\'\\1\');">&gt;&gt;', stripslashes($line_reply['message']));
                            }
                            //$buildthread_output .= preg_replace('/'.preg_quote('<a href="\/b\/res\/'.$thread_id.'.html#i').'([0-9]+)'.preg_quote('">&gt;&gt;').'/', '<a href="/b/res/'.$thread_id.'.html#\\1">&gt;&gt;',stripslashes($line['message']));
                        }
                        //$clock[] = 'BuildThread Line Mark 5 Reply 9: '.microtime_float();
                        $buildthread_output .= '</blockquote></td></tr></tbody></table>';
                    }
                }
                $buildthread_output .= '<br clear="left" /><hr />';
            } else {
                if ($page) {
                    $buildthread_output .= '<div class="border"><div class="thread">';
                } else {
                    $buildthread_output .= '<hr>';
                }
                $buildthread_output .= '<a name="'.$thread_relative_id.'"></a>';
                if ($page) {
                    $buildthread_output .= '<span class="navlinks"><a href="#';
                    if (($thread_relative_id-1)==-1) {
                        $buildthread_output .= ($num_threads_onfrontpage-1);
                    } else {
                        $buildthread_output .= ($thread_relative_id-1);
                    }
                    $buildthread_output .= '">&uarr;</a>&nbsp;<a href="#';
                    if (($thread_relative_id+1)==$num_threads_onfrontpage) {
                        $buildthread_output .= '0';
                    } else {
                        $buildthread_output .= ($thread_relative_id+1);
                    }
                    $buildthread_output .= '">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>';
                }
                if ($page) {
                    $buildthread_output .= '<h2>';
                    $buildthread_output .= '<a href="res/'.$thread_id.'.html">';
                } else {
                    $buildthread_output .= '<h3>';
                }
                $buildthread_output .= $line['subject'];
                if ($page) {
                    $buildthread_output .= '</a>';
                }
                if ($page) {
                    $buildthread_output .= '<span class="replies">&nbsp;('.$numReplies.')</span></h2>';
                    if ($thread_relative_id%2==0) {
                        $buildthread_output .= '<div class="post even">';
                    } else {
                        $buildthread_output .= '<div class="post odd">';
                    }
                } else {
                    $buildthread_output .= '<span class="replies">&nbsp;('.$numReplies.')</span></h3>';
                    $buildthread_output .= '<div class="post even">';
                }
                $buildthread_output .= '<span class="postnum"><a href="javascript:quote(1,\'post'.$line['id'].'\');"';
                if (!$page)
                {
                    $buildthread_output .= ' name="1"';
                }
                $buildthread_output .= '>1</a></span>&nbsp;';
                $buildthread_output .= '<span class="postinfo">Name:&nbsp;<span class="postername">';
                if ($line['user']==''&&$line['tripcode']=='') {
                    $buildthread_output .= 'Anonymous';
                } else if ($line['user']==''&&$line['tripcode']!='') {
                    $buildthread_output .= ''; // If they have a tripcode, just display the tripcode
                } else {
                    $buildthread_output .= stripslashes($line['user']);
                }
                $buildthread_output .= '</span><span class="postertrip">';
                if ($line['tripcode']!='') {
                    $buildthread_output .= '!'.stripslashes($line['tripcode']);
                }
                $buildthread_output .= '</span> @ ';
                $buildthread_output .= date('Y-m-d H:i',$line['postedat']);
                $buildthread_output .= $this->DeleteAndBanLinks($thread_id,true);
                $buildthread_output .= '<span class="id"></span></span><blockquote>';
                $buildthread_output .= stripslashes($line['message']);
                $buildthread_output .= '</blockquote></div>';
                $is_even = false;
                if ($page) {
                    $query .= ' LIMIT '.max(0,$numReplies-5).',5';
                }
                if ($numReplies>5&&$page) {
                    $buildthread_output .= '<p class="hidden">The 5 newest replies are shown below.<br/><a href="res/'.$line['id'].'.html">Read this thread from the beginning</a></p>';
                    $reply_relative_id = $numReplies-4;
                } else {
                    $reply_relative_id = 1;
                }
                if ($page) {
                    $query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `postedat` DESC LIMIT 5';
                    $results2 = array_reverse($tc_db->GetAll($query));
                } else {
                    $query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `postedat` ASC';
                    $results2 = $tc_db->GetAll($query);
                }
                //$results2 = $tc_db->GetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `postedat` ASC');
                foreach($results2 AS $line2) {
                    $reply_relative_id++;
                    if ($is_even) {
                        $buildthread_output .= '<div class="post even">';
                    } else {
                        $buildthread_output .= '<div class="post odd">';
                    }
                    $buildthread_output .= '<span class="postnum"><a href="javascript:quote('.$reply_relative_id.',\'post'.$line['id'].'\');"';
                    if (!$page) {
                        $buildthread_output .= ' name="'.$reply_relative_id.'"';
                    }
                    $buildthread_output .= '>'.$reply_relative_id.'</a></span>&nbsp;
                    <span class="postinfo">Name:&nbsp;<span class="postername">';
                    if ($line2['user']==''&&$line2['tripcode']=='') {
                        $buildthread_output .= 'Anonymous';
                    } else if ($line2['user']==''&&$line2['tripcode']!='') {
                        $buildthread_output .= ''; // If they have a tripcode, just display the tripcode
                    } else {
                        $buildthread_output .= stripslashes($line2['user']);
                    }
                    $buildthread_output .= '</span><span class="postertrip">';
                    if ($line2['tripcode']!='') {
                        $buildthread_output .= '!'.stripslashes($line2['tripcode']);
                    }
                    $buildthread_output .= '</span> @ ';
                    $buildthread_output .= date('Y-m-d H:i',$line2['postedat']);
                    $buildthread_output .= $this->DeleteAndBanLinks($line2['id'],false);
                    $buildthread_output .= '<span class="id"></span></span><blockquote>';
                    $buildthread_output .= stripslashes($line2['message']);
                    $buildthread_output .= '</blockquote></div>';
                    $is_even = !$is_even;
                }
                $buildthread_output .= '
                <form name="post'.$line['id'].'" action="'.TC_BOARDSFOLDER.'board.php" method="post">
                <input type="hidden" name="board" value="'.$this->board_dir.'" />
                <input type="hidden" name="replythread" value="'.$thread_id.'" />
                <input name="email" size="25" value="" style="display: none;" />
                <table class="postform">
                <tr>
                <td class="label">Name:</td>
                <td><input name="name" size="25" /></td>
                <td class="label">Email:</td>
                <td><input name="em" size="25" /></td>
                <td><input type="submit" value="Reply" class="submit" /></td>
                </tr>';
                if ($this->board_enablecaptcha==1) {
                    $buildthread_output .= '<tr>
                    <td class="label">Captcha:</td>
                    <td colspan="2"><img src="'.TC_BOARDSFOLDER.'captcha.php" alt="Captcha image">&nbsp;<input type="text" name="captcha" size="8" maxlength="6"></td>
                    </tr>';
                }
                $buildthread_output .= '<tr>
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
                    $buildthread_output .= '</div></div>';
                } else {
                    $buildthread_output .= '<hr>';
                }
            }
            $thread_relative_id++;
            //$clock[] = 'BuildThread Line 3: '.microtime_float();
        }
        if (!$page&&$this->board_type!=1) {
            $buildthread_output .= '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br>Password <input type="password" name="postpassword" size="8" />&nbsp;<input name="deletepost" value="Delete" type="submit" />';
            if ($this->board_enablereporting==1) {
                $buildthread_output .= '<input name="reportpost" value="Report" type="submit" />';
            }
            $buildthread_output .= '</td></tr></tbody></table></form>';
        }
        //$clock[] = 'BuildThread end: '.microtime_float();
        return $buildthread_output;
    }

    function PageHeader($replythread = '0',$liststart = '0',$listpage = '-1',$liststooutput = '-1') {
        global $tc_db, $tc_config, $clock;
        //$clock[] = 'Pageheader 1: '.microtime_float();
        $output = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
        <html>
        <head>
        <title>';
        if ($tc_config['is_7chan']) {
          $output .= $this->board_desc.' @ 7chan';
        } else {
          $output .= '/'.$this->board_dir.'/ - '.$this->board_desc;
        }
        $output .= '</title><script type="text/javascript">var style_cookie="tcstyle";</script>
        <script type="text/javascript" src="'.TC_BOARDSFOLDER.'javascript.js">
        </script>';
        if ($this->board_type==0||$this->board_type==2) {
            $output .= '
            <style type="text/css">
            body { margin: 0; padding: 8px; margin-bottom: auto; } blockquote blockquote { margin-left: 0em } form { margin-bottom: 0px } form .trap { display:none } .postarea { text-align: center } .postarea table { margin: 0px auto; text-align: left } .thumb { border: none; float: left; margin: 2px 20px } .nothumb { float: left; background: #eee; border: 2px dashed #aaa; text-align: center; margin: 2px 20px; padding: 1em 0.5em 1em 0.5em; } .reply blockquote, blockquote :last-child { margin-bottom: 0em } .reflink a { color: inherit; text-decoration: none } .reply .filesize { margin-left: 20px } .userdelete { float: right; text-align: center; white-space: nowrap } .replypage .replylink { display: none }
            .admin { color: purple;    font-weight:normal; }
            .mod { color: red; font-weight:normal; }
            </style>';
            if ($tc_config['is_7chan']) {
             $output .= '<link rel="stylesheet" type="text/css" href="'.TC_BOARDSPATH.'/css/burichan.css" title="Burichan">';
            } else {
             $output .= print_stylesheets('Futaba');
            }
        } else {
            $output .= '<link rel="stylesheet" href="'.TC_BOARDSFOLDER.'css/global.css">';
            $output .= '<link rel="stylesheet" href="'.TC_BOARDSFOLDER.'css/0ch.css" title="Pseud0ch" media="screen">';
            $output .= '<style type="text/css">body.board {background-image: url('.TC_BOARDSFOLDER.'ba.gif);}</style>';
        }
        $output .= '<link rel="shortcut icon" href="'.TC_WEBPATH.'/favicon.ico">
        <meta http-equiv="cache-control" content="no-cache">
        <meta http-equiv="pragma" content="no-cache">
        <meta http-equiv="expires" content="-1">';
        $output .= '</head>';
        if ($this->board_type==1) {
            if ($replythread==0) {
                $output .= '<body class="board">';
            } else {
                $output .= '<body class="read">';
            }
        } else {
            $output .= '<body>';
        }
        //$clock[] = 'Pageheader 2: '.microtime_float();
        if ($this->board_type==0||$this->board_type==2) {
            $output .= '<div class="adminbar">';
            if (!$tc_config['is_7chan']) {
                $output .= '[<a href="javascript:set_stylesheet(\'Burichan\')">Burichan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Futaba\')">Futaba</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Gurochan\')">Gurochan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Photon\')">Photon</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Fuhrerchan\')">Fuhrerchan</a>]&nbsp;-&nbsp;';
            }
            $output .= '[<a href="'.TC_WEBPATH.'" target="_top">Home</a>]&nbsp;';
            if (!$tc_config['is_7chan']) {
                $output .= '[<a href="'.TC_BOARDSPATH.'/manage.php">Manage</a>]';
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
            if ($this->board_type != 1) {
                if ($this->board_type != 2) {
                    // Linjutsu Ad
                    $output .= '<!-- Begin: Linjutsu --><div id="linjutsu-ad" style="top:'.$ad_top.'px;left:5px;"><a href="http://www.linjutsu.net" target="_new"><img src="http://www.7chan.org/donotblock/linjutsu.jpg" border="0" alt="Linjutsu Hosting"></a></div><!-- End: Linjutsu -->';
                }
                // Adbrite Ads
                $output .= '<!-- Begin: AdBrite --><div id="ad" style="top:'.$ad_top.'px;"> <div class="ad-title">Advertisements</div><script type="text/javascript" src="http://ads.adbrite.com/mb/text_group.php?sid=195242&amp;br=1&amp;dk=72656c6174696f6e73686970735f335f325f776562"></script><div><a class="adHeadline" target="_top" href="http://www.adbrite.com/mb/commerce/purchase_form.php?opid=195242&amp;afsid=1">Advertise Here</a></div></div><!-- End: AdBrite -->';
            }
        } elseif ($tc_config['is_trevorchan']) {
            $ad_top = 185;
            $ad_right = 25;
            if ($this->board_type==1)  {
                $ad_top -= 50;
            } else {
                if ($replythread!=0) {
                    $ad_top += 50;
                }
            }
            if ($this->board_type==2) {
                $ad_top += 40;
            }
            if ($this->board_type!=1) {
                $output .=  '<div id="ad" style="position: absolute;top:'.$ad_top.'px;right:'.$ad_right.'px">
                <script type="text/javascript"><!--
                google_ad_client = "pub-6158454562572132";
                google_ad_width = 120;
                google_ad_height = 240;
                google_ad_format = "120x240_as";
                google_ad_type = "text_image";
                google_ad_channel = "7008956366";
                google_color_border = "FFFFEE";
                google_color_bg = "FFFFEE";
                google_color_link = "800000";
                google_color_text = "CB7E46";
                google_color_url = "800000";
                --></script>
                <script type="text/javascript"
                src="http://pagead2.googlesyndication.com/pagead/show_ads.js">
                </script>
                </div>';
            }
        } 
        if ($this->board_type==0||$this->board_type==2) {
            $output .= '<div class="logo">';
            if ($this->board_image=='') {
                if (TC_HEADERURL!='') {
                    $output .= '<img src="'.TC_HEADERURL.'" alt="Logo" /><br />';
                }
            } else if ($this->board_image!=''&&$this->board_image!="none") {
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
            $output .= '<div class="hborder"><div class="head">';
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
            $output .= display_textboard_threadlist($this->board_dir,$liststart,$liststooutput,$is_page);
        }
        //$clock[] = 'Pageheader 3: '.microtime_float();
        return $output;
    }

    function CachePostbox() {
        if (!isset($this->postbox_noreply)&&!isset($this->postbox_isreply)) {
            $this->postbox_noreply = $this->Postbox();
            $this->postbox_noreply = $this->Postbox(1);
        }
    }

    function Postbox($replythread = 0,$oekaki = '',$postboxnotice = '') {
        global $tc_db;
        global $clock;
        //$clock[] = 'Postbox 1: '.microtime_float();
        $output = '';
        if ($this->board_type==1&&$replythread!=0) {
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
                $output .= '<form action="'.TC_BOARDSFOLDER.'paint.php" method="post">
                <input type="hidden" name="board" value="'.$this->board_dir.'">
                <input type="hidden" name="replyto" value="'.$replythread.'">
                <label for="applet">Paint with:&nbsp;</label><select name="applet"><option value="shipainter">Shi-Painter</option><option value="shipainterpro">Shi-Painter Pro</option></select>&nbsp;
                <label for="width">Width:&nbsp;<input type="text" name="width" size="3" value="300">&nbsp;<label for="height">Height:&nbsp;<input type="text" name="height" size="3" value="300">&nbsp;';
                if ($replythread!=0) {
                    $output .= '<label for="replyimage">Source:&nbsp;</label><select name="replyimage"><option value="0">New Image</option>';
                    $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `id` = $replythread AND `image` != '' AND `image` != 'removed' AND `imagetype` != 'swf' AND `IS_DELETED` = 0");
                    foreach($results AS $line) {
                        $output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
                    }
                    $results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `threadid` = $replythread AND `image` != '' AND `image` != 'removed' AND `imagetype` != 'swf' AND `IS_DELETED` = 0");
                    foreach($results AS $line) {
                        $output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
                    }
                    $output .= '</select>&nbsp;';
                }
                $output .= '<input type="submit" value="Paint!"></form><hr>';
            }
            if (($this->board_type==2&&$oekaki!=''&&$replythread==0)||($this->board_type==2&&$replythread!=0)||($this->board_type==0||$this->board_type==1)) {
                $output .= '<form id="postform" action="'.TC_BOARDSPATH.'/board.php" method="post" enctype="multipart/form-data">
                <input type="hidden" name="board" value="'.$this->board_dir.'" />';
                if ($replythread!=0) {
                    $output .= '
                    <input type="hidden" name="replythread" value="'.$replythread.'" />';
                }
                $output .= '<input type="text" name="email" size="28" maxlength="75" value="" style="display: none;" /><p>
                <table class="postform"><tbody>';
                if ($this->board_forcedanon!=1) {
                    $output .= '<tr>
                    <td class="'.$label_class.'">Name</td><td><input type="text" name="name" size="28" maxlength="75" /></td>
                    </tr>';
                }
                $output .= '<tr>
                <td class="'.$label_class.'">E-mail</td><td><input type="text" name="em" size="28" maxlength="75" /></td>
                </tr>
                <tr>
                <td class="'.$label_class.'">Subject</td><td><input type="text" name="subject" size="35" maxlength="75" />&nbsp;<input type="submit" value="Submit" /></td>
                </tr>
                <tr>
                <td class="'.$label_class.'">Message</td><td><textarea name="message" cols="48" rows="4"></textarea></td>
                </tr>';
                if ($this->board_type==0||$this->board_type==2) {
                    if ($this->board_uploadtype==0||$this->board_uploadtype==1) {
                        $output .= '<tr>
                        <td class="'.$label_class.'">File<a href="#" onclick="togglePassword();" style="text-decoration: none;">&nbsp;</a></td><td>';
                        if ($oekaki=='') {
                            $output .= '<input type="file" name="imagefile" size="35" />';
                        } else {
                            $output .= 'Shown Below<input type="hidden" name="oekaki" value="'.$oekaki.'" />';
                        }
                        $output .= '</td>
                        </tr>';
                    }
                    if ($oekaki==''&&($this->board_uploadtype==1||$this->board_uploadtype==2)) {
                        $output .= '<tr><td class="'.$label_class.'">Embed</td><td><input type="text" name="embed" size="28" maxlength="75">&nbsp;<select name="embedtype"><option value="youtube">YouTube</option></select></td></tr>';
                    }
                    $output .= '<tr>
                    <td class="'.$label_class.'">Password</td><td><input type="password" name="postpassword" size="8" />&nbsp;(for post and file deletion)</td>
                    </tr>';
                }
                if ($this->board_enablecaptcha==1) {
                    $output .= '<tr>
                    <td class="'.$label_class.'"><img src="'.TC_BOARDSFOLDER.'captcha.php" alt="Captcha image"></td><td><input type="text" name="captcha" size="8" maxlength="6">&nbsp;(captcha)</td>
                    </tr>';
                }
                if ($this->board_type==0||$this->board_type==2) {
                    $output .= '<tr id="passwordbox"><td></td><td></td></tr>
                    <tr>
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
        //$clock[] = 'Postbox 2: '.microtime_float();
        return $output;
    }

    function DeleteAndBanLinks($post_id, $is_thread) {
        global $tc_db;
        if ($is_thread==true) {
            $post_threadorpost = 'thread';
        } else {
            $post_threadorpost = 'post';
        }
        return "<script type=\"text/javascript\">
        if (getCookie(\"tcmod\")==\"yes\") {
            document.write('&nbsp;&#91;<a href=\"".TC_BOARDSFOLDER."manage.php?action=delposts&boarddir=".$this->board_dir."&del".$post_threadorpost."id=".$post_id."\" title=\"Delete\" onclick=\"return confirm(\\'Are you sure you want to delete this ".$post_threadorpost."?\\');\">D<\/a>&nbsp;<a href=\"".TC_BOARDSFOLDER."manage.php?action=delposts&boarddir=".$this->board_dir."&del".$post_threadorpost."id=".$post_id."&postid=".$post_id."\" title=\"Delete &amp; Ban\" onclick=\"return confirm(\\'Are you sure you want to delete and ban the poster of this ".$post_threadorpost."?\\');\">&amp;<\/a>&nbsp;<a href=\"".TC_BOARDSFOLDER."manage.php?action=bans&banboard=".$this->board_dir."&banpost=".$post_id."\" title=\"Ban\">B<\/a>&#93;');
        }
        </script>";
    }

    /* The name is deceiving, this function will trim the threads to the page limit AND delete posts which are older than limited */
    function TrimToPageLimit() {
        global $tc_db;
        if ($this->board_maxage!=0) { //If the maximum thread age setting is not zero (do not delete old threads), find posts which are older than the limit, and delete them
            $results = $tc_db->GetAll("SELECT `id`,`postedat` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 AND `stickied` = 0"); //Fetch all non-deleted thread-starting posts from the selected board
            foreach($results AS $line) {
                if ($line['postedat']+($this->board_maxage*3600)<time()) { //If it is older than the limit
                    $post_class = new Post($line['id'],$this->board_dir);
                    $post_class->Delete();
                }
            }
        }
        if ($this->board_maxpages!='0') { //If the maximum pages setting is not zero (do not limit pages), find posts which are over the limit, and delete them
            $results = $tc_db->GetAll("SELECT `id`,`stickied` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `threadid` = 0"); //Fetch all non-deleted thread-starting posts from the selected board
            if (calculatenumpages($this->board_type,count($results))>=$this->board_maxpages) {
                $this->board_maxthreads = $this->board_maxpages * $this->config_numthreadsdisplayed;
                $numthreadsover = count($results) - $this->board_maxthreads;
                if ($numthreadsover>0) {
                    $resultspost = $tc_db->SelectLimit("SELECT `id`,`stickied` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `threadid` = '0' AND `stickied` = '0' ORDER BY `lastbumped` ASC",$numthreadsover);
                    foreach($resultspost AS $linepost) {
                        if ($linepost['stickied']==0) {
                            $post_class = new Post($linepost['id'],$this->board_dir);
                            $post_class->Delete();
                        }
                    }
                }
            }
        }
    }

    function Footer($noboardlist = false,$executiontime = '',$hide_extra = false) {
        global $tc_db;
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
        $output .= '- <a href="http://www.trevorchan.org/" target="_top">Trevorchan</a> v'.TC_VERSION.' + Created by <a href="http://www.tj9991.com/" target="_top">tj9991</a> -';
        if ($executiontime!='') {
            $output .= '<br>Took '.round($executiontime,2).'s';
        }
        if ($hide_extra) {
            $output .= '<br><a href="'.TC_BOARDSPATH.'/manage.php">Manage Boards</a></div>';
        } else {
            $output .= '</p>';
        }
        $output .= '</body>
        </html>';
        return $output;
    }
}

class Post extends Board {
    function Post($postid,$board,$is_inserting = false) {
        global $tc_db;
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$board."` WHERE `id` = ".mysql_real_escape_string($postid)." LIMIT 1");
        if (count($results)==0&&!$is_inserting) {
            die("Invalid post ID.");
        } elseif ($is_inserting) {
            $this->Board($board);
        } else {
            foreach($results AS $line) {
                $this->post_id = $line['id'];
                $this->post_threadid = $line['threadid'];
                $this->post_image = $line['image'];
                $this->post_imagetype = $line['imagetype'];
                $this->post_password = $line['password'];
            }
            $results = $tc_db->GetAll("SELECT `cleared` FROM `".TC_DBPREFIX."reports` WHERE `postid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
            if (count($results)>0) {
                foreach($results AS $line) {
                    if ($line['cleared']==0) {
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
            if (!$this->board_dir==$board) {
                $this->Board($board);
            }
        }
    }

    function Delete() {
        global $tc_db;
        $i = 0;
        if ($this->post_isthread==true) {
            $results = $tc_db->GetAll("SELECT `id`,`image`,`imagetype` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = ".mysql_real_escape_string($this->post_id));
            foreach($results AS $line) {
                $i++;
                $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 WHERE `id` = ".$line['id']." AND `threadid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
            }
            unlink(TC_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'.html');
            $this->DeleteImage(false,true);
            $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
            return $i.' ';
        } else {
            $this->DeleteImage(false);
            $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
            return true;
        }
    }

    function DeleteImage($update_to_removed = true, $whole_thread = false) {
        global $tc_db;
        if ($whole_thread&&$this->post_isthread) {
            $results = $tc_db->GetAll("SELECT `id`,`image`,`imagetype` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
            if (count($results)>0) {
                foreach($results AS $line) {
                    if ($line['image']!=''&&$line['image']!="removed") {
                        unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$line['image'].'.'.$line['imagetype']);
                        unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
                    }
                    if ($update_to_removed) {
                        $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `image` = 'removed' WHERE `id` = ".$line['id']." LIMIT 1");
                    }
                }
            }
            $this->DeleteImage($update_to_removed);
        } else {
            if ($this->post_image!=''&&$this->post_image!='removed') {
                unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$this->post_image.'.'.$this->post_imagetype);
                unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$this->post_image.'s.'.$this->post_imagetype);
                if ($update_to_removed) {
                    $tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `image` = 'removed' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
                }
            }
        }
    }

    function Insert($threadid,$user,$tripcode,$email,$subject,$message,$image,$imagetype,$imagemd5,$image_w,$image_h,$image_size,$thumb_w,$thumb_h,$password,$postedat,$lastbumped,$ip,$posterauthority,$stickied,$locked) {
        global $tc_db;
        require_once(TC_ROOTDIR."inc/encryption.php");
        $query = "INSERT INTO `".TC_DBPREFIX."posts_".$this->board_dir."` ( `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `imagemd5` , `image_w` , `image_h` , `image_size` , `thumb_w` , `thumb_h` , `password` , `postedat` , `lastbumped` , `ip` , `ipmd5` , `posterauthority` , `stickied` , `locked` ) VALUES ( '".mysql_real_escape_string($threadid)."','".mysql_real_escape_string($user)."','".mysql_real_escape_string($tripcode)."','".mysql_real_escape_string($email)."','".mysql_real_escape_string($subject)."','".mysql_real_escape_string($message)."','".mysql_real_escape_string($image)."','".mysql_real_escape_string($imagetype)."','".mysql_real_escape_string($imagemd5)."','".mysql_real_escape_string($image_w)."','".mysql_real_escape_string($image_h)."','".mysql_real_escape_string($image_size)."','".mysql_real_escape_string($thumb_w)."','".mysql_real_escape_string($thumb_h)."','".mysql_real_escape_string($password)."','".mysql_real_escape_string($postedat)."','".mysql_real_escape_string($lastbumped)."','".mysql_real_escape_string(md5_encrypt($ip,TC_RANDOMSEED))."','".md5($ip)."','".mysql_real_escape_string($posterauthority)."','".mysql_real_escape_string($stickied)."','".mysql_real_escape_string($locked)."' )";
        $tc_db->Execute($query);
        return $tc_db->Insert_Id();
    }

    function Report() {
        global $tc_db;
        require_once(TC_ROOTDIR."inc/encryption.php");
        return $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."reports` ( `board` , `postid` , `when` , `ip` ) VALUES ( '".mysql_real_escape_string($this->board_dir)."' , ".mysql_real_escape_string($this->post_id)." , ".time()." , '".md5_encrypt($_SERVER['REMOTE_ADDR'],TC_RANDOMSEED)."' )");
    }
}

?>