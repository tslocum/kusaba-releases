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
 * Classes derived from board and post functions from functions.php
 * +------------------------------------------------------------------------------+
 * These classes are used for most board and post operations.
 * +------------------------------------------------------------------------------+
 */
class Board {
	/* Declare the public variables */
	var $board_id;
	var $board_type;
	var $board_uploadtype;
	var $board_dir;
	var $board_desc;
	var $board_enablereporting;
	var $board_image;
	var $board_includeheader;
	var $board_forcedanon;
	var $board_filetypes;
	var $board_maximagesize;
	var $board_maxage;
	var $board_maxreplies;
	var $board_maxpages;
	var $board_messagelength;
	var $board_locked;
	var $board_defaultstyle;
	var $board_redirecttothread;
	var $board_enablecaptcha;
	var $board_enablenofile;
	var $board_postboxnotice;
	
	/* Initialization function for the Board class, which is called when a new instance of this class is created.  Takes a board directory as an argument */
	function Board($board) {
		global $tc_db;

		/* If the instance was created with the board argument present, get all of the board info and configuration values and save it inside of the class */
		if ($board!='') {
			$query = "SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($board)."' LIMIT 1";
			$results = $tc_db->GetAssoc($query);
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
				$this->board_filetypes = explode('|', $line['filetypes']);
				$this->board_maximagesize = $line['maximagesize'];
				$this->board_maxage = $line['maxage'];
				$this->board_maxreplies = $line['maxreplies'];
				$this->board_maxpages = $line['maxpages'];
				$this->board_messagelength = $line['messagelength'];
				$this->board_locked = $line['locked'];
				$this->board_defaultstyle = $line['defaultstyle'];
				$this->board_redirecttothread = $line['redirecttothread'];
				$this->board_enablecaptcha = $line['enablecaptcha'];
				$this->board_enablenofile = $line['enablenofile'];
			}
			
			/* Format the postbox according to this board */
			$this->board_postboxnotice = $this->FormatPostbox(TC_POSTBOX, $this->board_dir);
		}
	}

	/* Regenerate all board and thread pages */
	function RegenerateAll() {
		$this->RegeneratePages();
		$this->RegenerateThreads();
	}

	function RegeneratePages() {
		global $tc_db;
		
		$numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
		$boardpage = 0;
		
		if ($this->board_type==1) {
			$numthreadsdisplayed = 15;
			$hide_extra = true;
		} else {
			$numthreadsdisplayed = TC_THREADS;
			$hide_extra = false;
		}
		
		$boardstooutput = calculatenumpages($this->board_type, ($numpostsleft-1));
		
		if ($numpostsleft>0) {
			$cached_pageheader = $this->PageHeader($this->board_dir);
			$cached_postbox = $this->Postbox(0, '', $this->board_postboxnotice);
			while ($numpostsleft>0) {
				$executiontime_start_regeneratepages = microtime_float();
				
				$page = $cached_pageheader . $cached_postbox;
				
				if ($this->board_type!=1) {
					$page .= '<script type="text/javascript">
						if (getCookie("tcmod")=="yes") {
							document.write(\'<form id="delform" action="'.TC_BOARDSFOLDER.'manage_page.php" method="post">\');
						} else { document.write(\'<form id="delform" action="'.TC_BOARDSFOLDER.'board.php" method="post">\') }
						</script><input type="hidden" name="board" value="'.mysql_real_escape_string($this->board_dir).'">';
				}
				
				$results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".($boardpage*$numthreadsdisplayed).', '.$numthreadsdisplayed);
				
				$thread_relative_id = 0;
				$thread_ids = array();
				foreach($results AS $line) { 
					$thread_ids[] = $line[0];
				}
				
				$page .= $this->BuildThread($thread_ids, true, false, $thread_relative_id);
				if ($this->board_type!=1) {
					$page .= '<table class="userdelete"><tbody><tr><td>'.ucwords(_('Delete Post')).' [<script type="text/javascript">
						if (getCookie("tcmod")=="yes") {
							document.write(\'<label><input type="checkbox" name="multiban" value="on">and ban</label>]<br>Reason: <input name="reason" size="8" />\');
						} else { document.write(\'<label><input type="checkbox" name="fileonly" value="on">'._('File Only').'</label>]<br>'._('Password').' <input type="password" name="postpassword" size="8" />\') }
						</script>&nbsp;<script type="text/javascript">
						if (getCookie("tcmod")=="yes") {
							document.write(\'<input name="action" value="multidel" type="submit" />\');
						} else { document.write(\'<input name="deletepost" value="'._('Delete').'" type="submit">\') }
						</script>';
		
					if ($this->board_enablereporting==1) {
						$page .= '<input name="reportpost" value="'._('Report').'" type="submit">';
					}
					
					$page .= '</td></tr></tbody></table></form><script type="text/javascript">set_delpass("delform")</script><table border="1"><tbody><tr><td>';
					
					if ($boardpage==0) {
						$page .= _('Previous');
					} else {
						if ($boardpage-1!=0) {
							$page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/'.($boardpage-1).'.html"><input value="'._('Previous').'" type="submit"></form>';
						} else {
							$page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/'.TC_FIRSTPAGE.'"><input value="'._('Previous').'" type="submit"></form>';
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
								$page .= '&#91;<a href="'.TC_BOARDSFOLDER.$this->board_dir.'/'.TC_FIRSTPAGE.'">'.$i.'</a>&#93;';
							}
						}
					}
					
					$page .= '</td><td>';
					
					if ($boardpage==$boardstooutput) {
						$page .= _('Next');
					} else {
						$page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/'.($boardpage+1).'.html"><input value="'._('Next').'" type="submit"></form>';
					}
					
					$page .= '</td></tr></tbody></table>';
				}
				
				$page .= $this->Footer(false, (microtime_float()-$executiontime_start_regeneratepages), $hide_extra);
				
				if ($boardpage==0) {
					print_page(TC_BOARDSDIR.$this->board_dir."/".TC_FIRSTPAGE."", $page, $this->board_dir);
				} else {
					print_page(TC_BOARDSDIR.$this->board_dir."/".$boardpage.".html", $page, $this->board_dir);
				}
				
				$page = '';
				$boardpage++;
				if ($this->board_type==1) {
					$numpostsleft = 0;
				} else {
					$numpostsleft -= $numthreadsdisplayed;
				}
			}
		} else {
			/* Make a blank index page */
			$executiontime_start_blankindex = microtime_float();
			$page = $this->PageHeader();
			$page .= $this->Postbox(0, '', $this->board_postboxnotice);
			$page .= $this->Footer(false, (microtime_float()-$executiontime_start_blankindex), $hide_extra);
			print_page(TC_BOARDSDIR.$this->board_dir."/".TC_FIRSTPAGE, $page, $this->board_dir);
		}
		/* If text board, rebuild thread list html files */
		if ($this->board_type==1) {
			$numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
			$liststooutput = floor(($numpostsleft-1) / 40);
			$listpage = 0;
			$currentpostwave = 0;
			while ($numpostsleft>0) {
				$executiontime_start_list = microtime_float();
				$page = $this->PageHeader(0, $currentpostwave, $listpage, $liststooutput);
				$page .= $this->Footer(false, (microtime_float()-$executiontime_start_list), $hide_extra);
				if ($listpage==0) {
					print_page(TC_BOARDSDIR.$this->board_dir.'/list.html', $page, $this->board_dir);
				} else {
					print_page(TC_BOARDSDIR.$this->board_dir.'/list'.$listpage.'.html', $page, $this->board_dir);
				}
				$currentpostwave += 40;
				$numpostsleft -= 40;
				$listpage++;
			}
		}
		/* Delete old pages */
		$dir = TC_BOARDSDIR.$this->board_dir;
		$files = glob ("$dir/{*.html}", GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $htmlfile) {
				if (preg_match("/[0-9+].html/", $htmlfile)) {
					if (substr(basename($htmlfile), 0, strpos(basename($htmlfile), '.html'))>$boardstooutput) {
						unlink($htmlfile);
					}
				}
				if (preg_match("/list[0-9+].html/", $htmlfile)) {
					if (substr(basename($htmlfile), 4, strpos(basename($htmlfile), '.html'))>$liststooutput) {
						unlink($htmlfile);
					}
				}
			}
		}
	}

	function RegenerateThreads() {
		global $tc_db;
		$res_threadlist = array();
		$results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `threadid` = 0 AND `IS_DELETED` = 0 ORDER BY `lastbumped` DESC");
		foreach($results AS $line) {
			$res_threadlist = array_merge($res_threadlist, array($line['id'].'.html'));
			$this->RegenerateThread($line['id']);
		}
		$dir = TC_BOARDSDIR.$this->board_dir.'/res';
		$files = glob ("$dir/{*.html}", GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $htmlfile) {
				if (!in_array(basename($htmlfile), $res_threadlist)) {
					unlink($htmlfile);
				}
			}
		}
	}

	function RegenerateThread($threadid) {
		global $tc_db;
		$query = "SELECT `id`, `type`, `name` FROM `".TC_DBPREFIX."boards` WHERE `id` = ".mysql_real_escape_string($this->board_id)." LIMIT 1";
		$resultsboard = $tc_db->GetAssoc($query);
		foreach($resultsboard AS $lineboard) {
			$results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 AND `id` = ".mysql_real_escape_string($threadid)." LIMIT 1");
			foreach($results AS $line) {
				$executiontime_start_regeneratethread = microtime_float();
				$thread_page = '';
				$this->post_id_id = $line['id'];
				$thread_page .= $this->PageHeader($threadid, '', $this->board_postboxnotice);
				if ($lineboard['type']==1) {
					$thread_page .= '<a href="'.TC_BOARDSFOLDER.$lineboard['name'].'/'.TC_FIRSTPAGE.'">'._('Return').'</a><br><br>';
					$hide_extra = true;
				} else {
					$thread_page .= '&#91;<a href="'.TC_BOARDSFOLDER.$lineboard['name'].'/'.TC_FIRSTPAGE.'">'._('Return').'</a>&#93; <div class="theader">'._('Posting mode: Reply').'</div>';
					$hide_extra = false;
				}
				$thread_page .= $this->Postbox($threadid, '', $this->board_postboxnotice);
				$thread_page .= $this->BuildThread($this->post_id_id);
				$thread_page .= $this->Footer(false, (microtime_float()-$executiontime_start_regeneratethread), $hide_extra);
				print_page(TC_BOARDSDIR.$lineboard['name'].'/res/'.$threadid.'.html', $thread_page, $lineboard['name']);
			}
		}
	}

	function BuildThread($threadid, $page = false, $resurrect = false, $thread_relative_id = 0) {
		global $tc_db;
		
		if (!is_array($threadid)) {
			$threadid = array($threadid);
		}
		$buildthread_output = '';
		foreach ($threadid AS $this_threadid) {
			if ($this_threadid<=0) {
				die("buildthread(): error.  threadid <= 0");
			}
		}
		if ($this->board_dir=='') {
			die("Ibuildthread(): error. boarddir = ''");
		}
		if ($resurrect) {
			$isdeleted_check = '1';
		} else {
			$isdeleted_check = '0';
		}
		if ($this->board_type==1) {
			$results = $tc_db->GetAll('SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = 0');
			$num_threads_onfrontpage = min($results[0][0], 15);
		}
		$query_idsegment = '';
		foreach ($threadid as $this_threadid) {
			$query_idsegment .= '`id` = '.mysql_real_escape_string($this_threadid).' OR ';
		}
		$query_idsegment = substr($query_idsegment, 0, -4);
		$results = $tc_db->GetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND ('.$query_idsegment.') AND `threadid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC');
		if (count($results)==0) {
			die('buildthread(): error.  No posts in thread to build from.');
		}
		$final_output = '';
		if (!$page&&$this->board_type!=1) {
			$buildthread_output .= '<script type="text/javascript">
				if (getCookie("tcmod")=="yes") {
					document.write(\'<form id="delform" action="'.TC_BOARDSFOLDER.'manage_page.php" method="post">\');
				} else { document.write(\'<form id="delform" action="'.TC_BOARDSFOLDER.'board.php" method="post">\') }
				</script><input type="hidden" name="board" value="'.$this->board_dir.'">';
		}
		foreach($results AS $line) {
			$thread_id = $line['id'];

			$results2 = $tc_db->GetAll('SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id));
			$numReplies = $results2[0][0];
			if ($this->board_type==0||$this->board_type==2) {
				$numImageReplies = 0;
				if ($page==true) {
					$query = 'SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' AND `image` != \'\' AND `image` != \'removed\' ORDER BY `id` ASC LIMIT 0, ';
					if ($line['stickied'] == 0 && $numReplies > TC_REPLIES) {
						$query .= ($numReplies - TC_REPLIES);
					} elseif ($numReplies>TC_REPLIESSTICKY) {
						$query .= ($numReplies-TC_REPLIESSTICKY);
					} else {
						$query = '';
					}
					if ($query != '') {
						$results = $tc_db->GetAll($query);
						$numImageReplies = $results[0][0];
					}
				}
				if ($page) {
					$buildthread_output .= '<span id="unhidethread'.$line['id'].$this->board_dir.'" style="display: none;">Thread <a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$line['id'].'.html">'.$line['id'].'</a> hidden. [<a href="#" onclick="javascript:togglethread(\''.$line['id'].$this->board_dir.'\');return false;" title="Un-Hide Thread">+</a>]</span>';
				}
				$buildthread_output .= '<span id="thread'.$line['id'].$this->board_dir.'">';
				if ($page) {
					$buildthread_output .= '<script type="text/javascript">
						if (hiddenthreads.toString().indexOf(\''.$line['id'].$this->board_dir.'\')!==-1) {
							document.getElementById(\'unhidethread'.$line['id'].$this->board_dir.'\').style.display = \'block\';
							document.getElementById(\'thread'.$line['id'].$this->board_dir.'\').style.display = \'none\';
						}
						</script>';
				}
				$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line['id'], $line['threadid'], $line['user'], $line['tripcode'], $line['email'], $line['subject'], $line['message'], $line['image'], $line['imagetype'], $line['image_w'], $line['image_h'], $line['image_size'], $line['thumb_w'], $line['thumb_h'], $line['postedat'], $line['stickied'], $line['locked'], $line['posterauthority'], $numReplies);
				if ($numReplies>0) {
					if (!$page) {
						$results = $tc_db->GetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `id` ASC');
					} else {
						$buildthread_output .= '<span id="replies'.$line['id'].$this->board_dir.'">';
						if ($line['stickied']==0) {
							$numrepliesdisplayed = TC_REPLIES;
						} else {
							$numrepliesdisplayed = TC_REPLIESSTICKY;
						}
						if ($numReplies>$numrepliesdisplayed) {
							$buildthread_output .= '<span class="omittedposts"> '.($numReplies-$numrepliesdisplayed).' post';
							$buildthread_output .= ($numReplies-$numrepliesdisplayed!=1) ? 's' : '';
							if ($numImageReplies>0) {
								$buildthread_output .= ' and '.$numImageReplies.' image';
								$buildthread_output .= ($numImageReplies!=1) ? 's' : '';
							}
							if (TC_EXPAND && $numReplies > TC_REPLIES && $numReplies < 300) {
								$buildthread_output .= ' omitted. '.sprintf(_('Click Reply to view, or %sexpand%s.'), '<a href="#" onclick="javascript:expandthread(\''.$line['id'].'\', \''.$this->board_dir.'\');return false;" title="Expand Thread">', '</a>').'</span>';
							} else {
								$buildthread_output .= ' omitted. '._('Click Reply to view.').'</span>';
							}
							
						}
						/* Retrieves the three newest posts from the thread in descending order, which is backwards for what we want, so we apply array_reverse on the result */
						$query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = '.$isdeleted_check.' AND `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `id` DESC LIMIT '.$numrepliesdisplayed;
						$results = array_reverse($tc_db->GetAll($query));
					}
					foreach($results AS $line_reply) {
						$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line_reply['id'], $line_reply['threadid'], $line_reply['user'], $line_reply['tripcode'], $line_reply['email'], $line_reply['subject'], $line_reply['message'], $line_reply['image'], $line_reply['imagetype'], $line_reply['image_w'], $line_reply['image_h'], $line_reply['image_size'], $line_reply['thumb_w'], $line_reply['thumb_h'], $line_reply['postedat'], $line_reply['stickied'], $line_reply['locked'], $line_reply['posterauthority']);
					}
					if (!$page) {
						$buildthread_output .= '</span>';
					}
					$buildthread_output .= '</span>';
				}
				$buildthread_output .= '</span>';
				$buildthread_output .= '<br clear="left"><hr>';
			} else {
				$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line['id'], $line['threadid'], $line['user'], $line['tripcode'], $line['email'], $line['subject'], $line['message'], $line['image'], $line['imagetype'], $line['image_w'], $line['image_h'], $line['image_size'], $line['thumb_w'], $line['thumb_h'], $line['postedat'], $line['stickied'], $line['locked'], $line['posterauthority'], $numReplies, false, $thread_relative_id, 1, $num_threads_onfrontpage);
				if ($numReplies>5&&$page) {
					$buildthread_output .= '<p class="hidden">'._('The 5 newest replies are shown below.').'<br/><a href="res/'.$line['id'].'.html">'._('Read this thread from the beginning').'</a></p>';
					$reply_relative_id = $numReplies-4;
				} else {
					$reply_relative_id = 1;
				}
				if ($page) {
					$query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `id` DESC LIMIT 5';
					$results2 = array_reverse($tc_db->GetAll($query));
				} else {
					$query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `threadid` = '.mysql_real_escape_string($thread_id).' ORDER BY `id` ASC';
					$results2 = $tc_db->GetAll($query);
				}
				foreach($results2 AS $line2) {
					$reply_relative_id++;
					$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line2['id'], $line2['threadid'], $line2['user'], $line2['tripcode'], $line2['email'], $line2['subject'], $line2['message'], $line2['image'], $line2['imagetype'], $line2['image_w'], $line2['image_h'], $line2['image_size'], $line2['thumb_w'], $line2['thumb_h'], $line2['postedat'], $line2['stickied'], $line2['locked'], $line2['posterauthority'], $numReplies, $line2['IS_DELETED'], $thread_relative_id, $reply_relative_id, $num_threads_onfrontpage);
				}
				$buildthread_output .= '
				<form name="post'.$line['id'].'" id="post'.$line['id'].'" action="'.TC_BOARDSFOLDER.'board.php" method="post">
				<input type="hidden" name="board" value="'.$this->board_dir.'">
				<input type="hidden" name="replythread" value="'.$thread_id.'">
				<input name="email" size="25" value="" style="display: none;">
				<table class="postform">
				<tr>
				<td class="label">'._('Name').':</td>
				<td><input name="name" size="25"></td>
				<td class="label">'._('Email').':</td>
				<td><input name="em" size="25"></td>
				<td><input type="submit" value="Reply" class="submit"></td>
				</tr>';
				if ($this->board_enablecaptcha==1) {
					$buildthread_output .= '<tr>
					<td class="label">'._('Captcha').':</td>
					<td colspan="2"><a href="#" onclick="javascript:document.getElementById(\'captchaimage\').src = \''.TC_BOARDSFOLDER.'captcha.php?\' + Math.random();return false;"><img id="captchaimage" src="'.TC_BOARDSFOLDER.'captcha.php" border="0" alt="Captcha image"></a>&nbsp;<input type="text" name="captcha" size="8" maxlength="6"></td>
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
				<input type="hidden" name="postpassword" value="">
				</form>
				<script type="text/javascript">set_inputs(\'post'.$line['id'].'\')</script>';
				if ($page) {
					$buildthread_output .= '</div></div>';
				} else {
					$buildthread_output .= '<hr>';
				}
			}
			$thread_relative_id++;
		}
		if (!$page&&$this->board_type!=1) {
			$buildthread_output .= '<table class="userdelete"><tbody><tr><td>'.ucwords(_('Delete Post')).' [<script type="text/javascript">
				if (getCookie("tcmod")=="yes") {
					document.write(\'<label><input type="checkbox" name="multiban" value="on">and ban</label>]<br>Reason: <input name="reason" size="8" />\');
				} else { document.write(\'<label><input type="checkbox" name="fileonly" value="on">'._('File Only').'</label>]<br>'._('Password').' <input type="password" name="postpassword" size="8" />\') }
				</script>&nbsp;<script type="text/javascript">
				if (getCookie("tcmod")=="yes") {
					document.write(\'<input name="action" value="multidel" type="submit" />\');
				} else { document.write(\'<input name="deletepost" value="'._('Delete').'" type="submit" />\') }
				</script>';
			if ($this->board_enablereporting==1) {
				$buildthread_output .= '<input name="reportpost" value="'._('Report').'" type="submit">';
			}
			$buildthread_output .= '</td></tr></tbody></table></form>';
		}
		return $buildthread_output;
	}
	
	function BuildPost($page, $post_board, $post_board_type, $post_id, $post_threadid, $post_user, $post_tripcode, $post_email, $post_subject, $post_message, $post_image, $post_imagetype, $post_image_w, $post_image_h, $post_image_size, $post_thumb_w, $post_thumb_h, $post_postedat, $post_stickied, $post_locked, $post_posterauthority, $thread_replies=0, $post_is_deleted=false, $thread_relative_id=0, $reply_relative_id=0, $threads_on_front_page=0) {
		$buildpost_output = '';
		$post_thread_start_id = ($post_threadid==0) ? $post_id : $post_threadid;
		$post_is_thread = ($post_threadid==0) ? true : false;
		if ($post_board_type!=1) {
			/* Build a post imageboard style */
			$info_file = '';
			$info_post = '';
			$info_image = '';
			$post_is_standard = true;
			$post_is_nofile = true;
			if ($post_image!=''||$post_imagetype!=''||$post_is_thread!='') {
				$post_is_nofile = false;
				if ($post_image=='removed') {
					$post_thumb = 'removed';
				} else {
					/* Check if the filetype is not a default type */
					if ($post_imagetype!='jpg'&&$post_imagetype!='gif'&&$post_imagetype!='png'&&$post_imagetype!='you'&&$post_imagetype!='goo') {
						$post_is_standard = false;
						$filetype_info = getfiletypeinfo($post_imagetype);
						$post_thumb = TC_WEBPATH.'/inc/filetypes/'.$filetype_info[0];
						$post_thumb_w = $filetype_info[1];
						$post_thumb_h = $filetype_info[2];
					} else {
						/* It is a default format, calculate the thumbnail location */
						$post_thumb = TC_BOARDSFOLDER.$post_board.'/thumb/'.$post_image.'s.'.$post_imagetype;
					}
				}
			}
			/* Build the "File: ..." line */
			if (!$post_is_nofile&&($post_imagetype!='you'&&$post_imagetype!='goo'&&$post_image!='') && $post_image != 'removed') {
				$info_file .= '<span class="filesize">'._('File:').' <a ';
				if (TC_NEWWINDOW) {
					$info_file .= 'target="_blank" ';
				}
				$info_file .= 'href="'.TC_BOARDSFOLDER.$post_board.'/src/'.$post_image.'.'.$post_imagetype.'">'.$post_image.'.'.$post_imagetype.'</a> -(<em>'.round($post_image_size/1024).' KB, '.$post_image_w.'x'.$post_image_h.'</em>)</span><span class="thumbnailmsg"> ';
				if ($post_is_standard) {
					$info_file .= _('Thumbnail displayed, click image for full size.');
				} else {
					$info_file .= _('Extension icon displayed, click image to open file.');
				}
				$info_file .= '</span>';
			}
			/* Build the "[checkbox] (Poster name)..." line */
			if ($post_imagetype!='you'&&$post_imagetype!='goo'&&$post_image!=''&&!$post_is_nofile) {
				if ($post_image == 'removed') {
					$info_image .=  '<div hspace="20" style="float:left;text-align:center;padding:14px;margin:3px;border:black 3px dashed;">Image<br>removed</div>';
				} else {
					$info_image .= '<a ';
					if (TC_NEWWINDOW) {
						$info_image .= 'target="_blank" ';
					}
					$info_image .= 'href="'.TC_BOARDSFOLDER.$post_board.'/src/'.$post_image.'.'.$post_imagetype.'"><img src="'.$post_thumb.'" width="'.$post_thumb_w.'" height="'.$post_thumb_h.'" alt="'.$post_id.'" class="thumb"></a>';
				}
			}
			$info_post .= '<a name="'.$post_id.'"></a><label><script type="text/javascript">
			if (getCookie("tcmod")=="yes") {
				document.write(\''."<input type=\"checkbox\" name=\"$post_id\" value=\"POST$post_id\" />&nbsp;".'\');
			} else {
				document.write(\''.'<input type="checkbox" name="delete" value="'.$post_id.'">&nbsp;'.'\');
			}
			</script>';
			if ($post_is_thread && $page) {
				$info_post .= '<span name="hide">[<a href="#" onclick="javascript:togglethread(\''.$post_id.$post_board.'\');return false;" title="Hide Thread">-</a>]</span>&nbsp;';
			}
			if ($post_subject!='') {
				$info_post .= '<span class="filetitle">'.stripslashes($post_subject).'</span> ';
			}
			$info_post .= '<span class="postername">';
			
			if ($post_email!='') { $info_post .= '<a href="mailto:'.$post_email.'">'; }
			
			if ($post_user==''&&$post_tripcode=='') {
				$info_post .= TC_ANONYMOUS;
			} else if ($post_user==''&&$post_tripcode!='') {
				/* Just display the tripcode, no added html */
			} else {
				$info_post .= stripslashes($post_user);
			}
			
			if ($post_email!='') { $info_post .= '</a>'; }
			
			$info_post .= '</span>';
			
			if ($post_tripcode!='') {
				$info_post .= '<span class="postertrip">!'.$post_tripcode.'</span>';
			}
			
			if ($post_posterauthority==1) {
				$info_post .= ' <span class="admin">##&nbsp;'._('Admin').'&nbsp;##</span>';
			} else if ($post_posterauthority==2) {
				$info_post .= ' <span class="mod">##&nbsp;'._('Mod').'&nbsp;##</span>';
			}
			
			$info_post .= ' '.date("y/m/d(D)H:i", $post_postedat).'</label> <span class="reflink"><a href="'.TC_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html#'.$post_id.'">No.</a><a href="'.TC_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html#i'.$post_id.'"';
			if (!$page) {
				$info_post .= ' onclick="insert(\'>>'.$post_id.'\');"';
			}
			$info_post .= '>'.$post_id.'</a>';
			if ($post_locked==1) {
				$info_post .= '&nbsp;<img style="border: 0;" src="'.TC_BOARDSFOLDER.'locked.gif" alt="'._('Locked').'">';
			}
			if ($post_stickied==1) {
				$info_post .= '<img style="border: 0;" src="'.TC_BOARDSFOLDER.'sticky.gif" alt="'._('Stickied').'">';
			}
			$info_post .= '</span>';
			if ($page&&$post_is_thread) {
				if (TC_QUICKREPLY) {
					$info_post .= ' &#91;<a href="#postbox" onclick="javascript:quickreply(\'' . $post_thread_start_id . '\');" title="' . _('Quick Reply') . '">Q</a>&#93;';
				}
				$info_post .= ' &#91;<a href="'.TC_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html">'._('Reply').'</a>&#93;';
			}
			$info_post .= $this->DeleteAndBanLinks($post_id, $post_is_thread);
			if (!$post_is_thread) {
				$buildpost_output .= '<table><tbody><tr>
				<td class="doubledash">&gt;&gt;</td>
				<td class="reply" id="reply'.$post_id.'"><a name="'.$post_id.'"></a>'.$info_post;
				/* Make sure the file line is actually in use to prevent an unwanted <br> */
				if ($info_file!='') {
					$buildpost_output .= '<br>'.$info_file;
				}
				/* Another check for an unwanted <br> */
				if ($info_image!='') {
					$buildpost_output .= '<br>'.$info_image;
				}
			} else {
				/* And another check for an unwanted <br> */
				if ($info_file!='') {
					$buildpost_output .= $info_file.'<br>';
				}
				$buildpost_output .= $info_image.$info_post;
			}
			$buildpost_output .= '<blockquote><p>';
			if ($post_imagetype=='you'||$post_imagetype=='goo') {
				$buildpost_output .= '<span style="float: left;">';
				
				if ($post_imagetype=='you') {
					$buildpost_output .= '<object width="200" height="164"><param name="movie" value="http://www.youtube.com/v/'.$post_image.'"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/'.$post_image.'" type="application/x-shockwave-flash" wmode="transparent" width="200" height="164"></embed></object>';
				} elseif ($post_imagetype=='goo') {
					$buildpost_output .= '<embed style="width:200px; height:164px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId='.$post_image.'&hl=en" flashvars=""></embed>';
				}
				
				$buildpost_output .= '</span>&nbsp;';
			}
			if (count(explode('<br>', $post_message))>15&&$page) {
				$message_exploded = explode('<br>', stripslashes($post_message));
				$message_shortened = '';
				for ($i = 0; $i <= 14; $i++) {
					$message_shortened .= $message_exploded[$i].'<br>';
				}
				$message_shortened = closeOpenTags($message_shortened);
				$buildpost_output .= $message_shortened;
				$buildpost_output .= '<div class="abbrev">'.sprintf(_('Comment too long. Click %shere%s to view the full text.'), '<a href="'.TC_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html">', '</a>').'</div>';
			} else {
				$buildpost_output .= stripslashes($post_message);
			}
			$buildpost_output .= '</p></blockquote>';
			if (!$post_is_thread) {
				$buildpost_output .= '</td></tr></tbody></table>';
			}
		} else {
			/* Build a post text-board style */
			if ($post_is_deleted) {
				$post_user = '';
				$post_email = '';
				$post_tripcode = 'Deleted';
				$post_message = '<font color="gray">'._('This post has been deleted.').'</font>';
			}
			if ($post_is_thread) {
				if ($page) {
					$buildpost_output .= '<div class="border"><div class="thread">';
				} else {
					$buildpost_output .= '<hr>';
				}
			}
			$buildpost_output .= '<a name="'.$thread_relative_id.'"></a>';
			if ($post_is_thread) {
				if ($page) {
					$buildpost_output .= '<span class="navlinks"><a href="#';
					if (($thread_relative_id-1)==-1) {
						$buildpost_output .= ($threads_on_front_page-1);
					} else {
						$buildpost_output .= ($thread_relative_id-1);
					}
					$buildpost_output .= '">&uarr;</a>&nbsp;<a href="#';
					if (($thread_relative_id+1)==$threads_on_front_page) {
						$buildpost_output .= '0';
					} else {
						$buildpost_output .= ($thread_relative_id+1);
					}
					$buildpost_output .= '">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>';
				}
				$buildpost_output .= '<h2>';
				if ($page) {
					$buildpost_output .= '<a href="res/'.$post_thread_start_id.'.html">';
				}
				$buildpost_output .= $post_subject;
				if ($page) {
					$buildpost_output .= '</a>';
				}
				if ($page) {
					$buildpost_output .= '<span class="replies">&nbsp;('.$thread_replies.')</span></h2>';
					if ($thread_relative_id%2==0) {
						$buildpost_output .= '<div class="post even">';
					} else {
						$buildpost_output .= '<div class="post odd">';
					}
				} else {
					$buildpost_output .= '<span class="replies">&nbsp;('.$thread_replies.')</span></h3>';
					$buildpost_output .= '<div class="post even">';
				}
			} else {
				if ($thread_relative_id%2==0) {
					$buildpost_output .= '<div class="post even">';
				} else {
					$buildpost_output .= '<div class="post odd">';
				}
			}
			$buildpost_output .= '<h3><span class="postnum"><a href="javascript:quote('.$reply_relative_id.', \'post'.$post_thread_start_id.'\');"';
			if (!$page) {
				$buildpost_output .= ' name="'.$reply_relative_id.'"';
			}
			$buildpost_output .= '>'.$reply_relative_id.'</a></span>&nbsp;';
			$buildpost_output .= '<span class="postinfo">Name:&nbsp;<span class="postername">';
			if ($post_user==''&&$post_tripcode=='') {
				$buildpost_output .= TC_ANONYMOUS;
			} else if ($post_user==''&&$post_tripcode!='') {
				$buildpost_output .= '';
			} else {
				$buildpost_output .= stripslashes($post_user);
			}
			$buildpost_output .= '</span><span class="postertrip">';
			if ($post_tripcode!='') {
				$buildpost_output .= '!'.stripslashes($post_tripcode);
			}
			$buildpost_output .= '</span> @ ';
			$buildpost_output .= date('Y-m-d H:i', $post_postedat);
			$buildpost_output .= $this->DeleteAndBanLinks($post_id, $post_is_thread);
			$buildpost_output .= '<span class="id"></span></span></h3><blockquote>';
			$buildpost_output .= stripslashes($post_message);
			$buildpost_output .= '</blockquote></div>';
		}
		
		return $buildpost_output;
	}

	function PageHeader($replythread = '0', $liststart = '0', $listpage = '-1', $liststooutput = '-1') {
		global $tc_db, $tc_config;
		$output = '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
		<html>
		<head>
		<title>/'.$this->board_dir.'/ - '.$this->board_desc.'</title>';
		
		if ($this->board_type==0||$this->board_type==2) {
			$output .= '
			<style type="text/css">
			body { margin: 0; padding: 8px; margin-bottom: auto; } blockquote blockquote { margin-left: 0em } form { margin-bottom: 0px } form .trap { display:none } .postarea { text-align: center } .postarea table { margin: 0px auto; text-align: left } .thumb { border: none; float: left; margin: 2px 20px } .nothumb { float: left; background: #eee; border: 2px dashed #aaa; text-align: center; margin: 2px 20px; padding: 1em 0.5em 1em 0.5em; } .reply blockquote, blockquote :last-child { margin-bottom: 0em } .reflink a { color: inherit; text-decoration: none } .reply .filesize { margin-left: 20px } .userdelete { float: right; text-align: center; white-space: nowrap } .replypage .replylink { display: none }
			.admin { color: purple;    font-weight:normal; }
			.mod { color: red; font-weight:normal; }
			</style>';
			$output .= '<link rel="stylesheet" href="'.TC_BOARDSFOLDER.'css/img_global.css">';
			if ($this->board_defaultstyle != '') {
				$output .= print_stylesheets($this->board_defaultstyle);
			} else {
				$output .= print_stylesheets('Futaba');
			}
		} else {
			$output .= '<link rel="stylesheet" href="'.TC_BOARDSFOLDER.'css/txt_global.css">';
			$output .= '<link rel="stylesheet" href="'.TC_BOARDSFOLDER.'css/txt_pseud0ch.css" title="Pseud0ch" media="screen">';
			$output .= '<link rel="alternate stylesheet" href="'.TC_BOARDSFOLDER.'css/txt_yotsuba.css" title="Yotsuba" media="screen">';
		}
		if (TC_RSS) {
		$output .= '<link rel="alternate" type="application/rss+xml" title="RSS" href="'.TC_BOARDSPATH.'/'.$this->board_dir.'/rss.xml">';
		}
		$output .= '<script type="text/javascript">var style_cookie';
		if ($this->board_type==1) {
			$output .= '_txt';
		}
		$output .= '="tcstyle';
		if ($this->board_type==1) {
			$output .= '_txt';
		}
		$output .= '";</script>
		<script type="text/javascript">var tc_boardsfolder=\''.TC_BOARDSFOLDER.'\';</script>
		<script type="text/javascript" src="'.TC_WEBPATH.'/lib/prototype.js"></script>
		<script type="text/javascript" src="'.TC_WEBPATH.'/lib/scriptaculous/scriptaculous.js"></script>
		<script type="text/javascript" src="'.TC_WEBPATH.'/lib/trevorchan.js"></script>
		<script type="text/javascript">var hiddenthreads = getCookie(\'hiddenthreads\').split(\'!\');</script>';
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
		if ($this->board_type==0||$this->board_type==2) {
			$output .= '<div class="adminbar">[<a href="javascript:set_stylesheet(\'Burichan\')">Burichan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Futaba\')">Futaba</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Gurochan\')">Gurochan</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Photon\')">Photon</a>]&nbsp;[<a href="javascript:set_stylesheet(\'Fuhrerchan\')">Fuhrerchan</a>]&nbsp;-&nbsp;';
			$output .= '[<a href="'.TC_WEBPATH.'" target="_top">Home</a>]&nbsp;[<a href="'.TC_BOARDSFOLDER.'manage.php">Manage</a>]</div>';
			$output .= $this->DisplayBoardList(false);
		} else {
			$output .= $this->DisplayBoardList(true);
		}
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
		if (isset($tc_config['is_trevorchan']) && $this->board_type != 1) {
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
		if (TC_WATCHTHREADS) {
			$output .= '<div id="watchedthreads" style="top: ' . $ad_top . 'px; left: 25px;" class="watchedthreads">
			<div id="watchedthreadsdraghandle" style="text-align: center;cursor: move;" width="100%">
			<font face="Arial"><b>Watched threads</b></font>
			</div>
			<span id="watchedthreadlist"></span>
			<script type="text/javascript">
			watchedthreadselement = document.getElementById(\'watchedthreads\');
			if (getCookie(\'hidewatchedthreads\') == \'1\') {
				watchedthreadselement.innerHTML = \'<a href="#" onclick="javascript:showwatchedthreads();return false">Show watched threads</a>\';
			} else {
				watchedthreadselement.style.top = getCookie(\'watchedthreadstop\');
				watchedthreadselement.style.left = getCookie(\'watchedthreadsleft\');
				getwatchedthreads(\'' . $replythread . '\', \'' . $this->board_dir . '\');
			}
			</script>
			</div>';
		}
		if ($this->board_type==0||$this->board_type==2) {
			$output .= '<div class="logo">';
			if ($this->board_image=='') {
				if (TC_HEADERURL!='') {
					$output .= '<img src="'.TC_HEADERURL.'" alt="Logo"><br>';
				}
			} else if ($this->board_image!=''&&$this->board_image!="none") {
				$output .= '<img src="'.$this->board_image.'" alt="Logo"><br>';
			}
			$output .= '/'.$this->board_dir.'/ - '.$this->board_desc;
			$output .= '</div>';
			$output .= stripslashes($this->board_includeheader);
			$output .= '<hr>';
		} else if ($this->board_type==1&&$replythread==0) {
			/* Text board header */
			$output .= '<div class="hborder">
			<div class="head">
			<a name="menu" rev="contents"></a>';
			if ($listpage<0||$liststooutput<0) {
				$output .= '<span class="navlinks"><a href="#0">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>';
			}
			$output .= '<h1 align="center">'.$this->board_desc.'</h1>
			</div>
			</div>
			<div class="hborder">
				<div class="head midhead">
					<b>Style:</b> <a href="#" onclick="javascript:set_stylesheet(\'Yotsuba\',true);return false;">Yotsuba</a> <a href="#" onclick="javascript:set_stylesheet(\'Pseud0ch\',true);return false;">Pseud0ch</a>
				</div>
			</div>';
			
			if ($listpage>=0&&$liststooutput>=0) {
				$output .= 'Pages:&nbsp;<a href="'.TC_FIRSTPAGE.'">Front</a>';
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
			$output .= $this->TextBoardThreadList($this->board_dir, $liststart, $liststooutput, $is_page);
		}
		return $output;
	}
	
	function TextBoardThreadList($board, $liststart, $liststooutput, $ispage = false) {
		global $tc_db;
		
		$output = '<div class="hborder">
		<div class="head threadldiv">
		<a name="menu"></a>
		<table class="threads"><thead>
		<tr>
		<th width="10%">#</th>
		<th nowrap="nowrap" width="100%">' . _('Subject') . '</th>
		<th>' . _('Posts') . '</th>
		<th>' . _('Last Post') . '</th>
		</tr>
		</thead>
		<tbody>';
		if ($liststooutput >= 0) {
			$startrecord = 40;
		} else {
			$startrecord = 15;
		}
		$query = "SELECT * FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `threadid` = '0' AND `IS_DELETED` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT $liststart,$startrecord";
		$results = $tc_db->GetAll($query);
		if (count($results)>0) {
			$relative_id = $liststart;
			foreach($results AS $line) {
				$results2 = $tc_db->GetAll("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `threadid` = '".$line['id']."' AND `IS_DELETED` = 0");
				$replies = $results2[0][0];
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
			$output .= '<tr><td>N/A</td><td>'._('There are currently no threads to display.').'</td><td>N/A</td><td>N/A</td></td>';
		}
		$output .= '</tbody></table>';
		if ($liststooutput < 0) {
			$output .= '<span class="threadlinks"><a href="#newthread">'._('New Thread').'</a> | <a href="list.html">'._('All Threads').'</a></span>';
		}
		$output .= '</div></div>';
		
		return $output;
	}
	
	function UniquePosts($board = '') {
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
	
	function FormatPostbox($notice, $board) {
		global $tc_db;
		
		$query = "SELECT `maximagesize`, `filetypes` FROM `".TC_DBPREFIX."boards` WHERE `name` = '$board' LIMIT 1";
		$results = $tc_db->GetAll($query);
		foreach($results AS $line) {
			$filetypes = '';
			$filetypes_allowed = explode('|', $line['filetypes']);
			if ($filetypes_allowed=='') {
				$filetypes = '<b>'._('None').'</b>';
			} else {
				foreach ($filetypes_allowed as $filetype) {
					$filetypes .= $filetype.', ';
				}
				$filetypes = substr($filetypes, 0, strlen($filetypes)-2);
			}
			$patterns = array ('/\<\!tc_maxthumbwidth \/\>/', '/\<\!tc_maxthumbheight \/\>/', '/\<\!tc_uniqueposts \/\>/', '/\<\!tc_maximagekb \/\>/', '/\<\!tc_filetypes \/\>/');
			$replace = array (TC_THUMBWIDTH, TC_THUMBHEIGHT, $this->UniquePosts($board), round($line['maximagesize']/1024), $filetypes);
			$notice = preg_replace($patterns, $replace, $notice);
		}
		return $notice;
	}

	function CachePostbox() {
		if (!isset($this->postbox_noreply)&&!isset($this->postbox_isreply)) {
			$this->postbox_noreply = $this->Postbox();
			$this->postbox_isreply = $this->Postbox(1);
		}
	}

	function Postbox($replythread = 0, $oekaki = '', $postboxnotice = '') {
		global $tc_db;
		
		$output = '';
		if (!($this->board_type == 1 && $replythread != 0)) {
			if ($this->board_type ==0 || $this->board_type == 2) {
				$output .= '<div class="postarea">';
				$label_class = 'postblock';
			} else {
				$output .= '<div class="hborder head newthread"><a id="newthread"></a><h2>New Thread</h2>';
				$label_class = 'label';
			}
			/* Create anchor to allow links to scroll directly to the post box */
			$output .= '<a id="postbox"></a>';
			if ($this->board_type == 2 && $oekaki == '') {
				$output .= '<form action="'.TC_BOARDSFOLDER.'paint.php" method="post">
				<input type="hidden" name="board" value="'.$this->board_dir.'">
				<input type="hidden" name="replyto" value="'.$replythread.'">
				<label for="applet">Paint with:&nbsp;</label><select name="applet"><option value="shipainter">Shi-Painter</option><option value="shipainterpro">Shi-Painter Pro</option></select>&nbsp;
				<label for="width">Width:&nbsp;<input type="text" name="width" size="3" value="300">&nbsp;<label for="height">Height:&nbsp;<input type="text" name="height" size="3" value="300">&nbsp;
				<label for="useanim">Use animation?&nbsp;<input type="checkbox" name="useanim" checked>&nbsp;';
				if ($replythread != 0) {
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
			if (($this->board_type == 2&&$oekaki != '' && $replythread == 0) ||($this->board_type == 2 && $replythread != 0) || ($this->board_type == 0 || $this->board_type == 1)) {
				$output .= '<form name="postform" id="postform" action="'.TC_BOARDSFOLDER.'board.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="board" value="'.$this->board_dir.'">
				<input type="hidden" name="replythread" value="' . $replythread . '">
				<input type="text" name="email" size="28" maxlength="75" value="" style="display: none;"><p>
				<table class="postform"><tbody>';
				if ($this->board_forcedanon != 1) {
					$output .= '<tr>
					<td class="'.$label_class.'">'._('Name').'</td><td><input type="text" name="name" size="28" maxlength="75" accesskey="n"></td>
					</tr>';
				}
				$output .= '<tr>
				<td class="'.$label_class.'">'._('Email').'</td><td><input type="text" name="em" size="28" maxlength="75" accesskey="e"></td>
				</tr>';
				if ($this->board_enablecaptcha == 1) {
					$output .= '<tr>
					<td class="'.$label_class.'"><a href="#" onclick="javascript:document.getElementById(\'captchaimage\').src = \''.TC_BOARDSFOLDER.'captcha.php?\' + Math.random();return false;"><img id="captchaimage" src="'.TC_BOARDSFOLDER.'captcha.php" border="0" alt="Captcha image"></a></td><td><input type="text" name="captcha" size="28" maxlength="6" accesskey="c"></td>
					</tr>';
				}
				$output .= '
				<tr>
				<td class="'.$label_class.'">'._('Subject').'</td><td><input type="text" name="subject" size="35" maxlength="75" accesskey="s">&nbsp;<input type="submit" value="Submit" accesskey="z">';
				/* Qucik reply indicator for a postbox on a board page */
				if (TC_QUICKREPLY && $replythread == 0 && $this->board_type == 0) {
					$output .= '&nbsp;<small>(<span id="posttypeindicator">new thread</span>)</small>';
				/* Qucik reply indicator for a postbox on a thread page */
				} elseif (TC_QUICKREPLY && $replythread != 0 && $this->board_type == 0) {
					$output .= '&nbsp;<small>(<span id="posttypeindicator">reply to ' . $replythread . '</span>)</small>';
				}
				$output .= '</td>
				</tr>
				<tr>
				<td class="'.$label_class.'">'._('Message').'</td><td><textarea name="message" cols="48" rows="4" accesskey="m"></textarea></td>
				</tr>';
				if ($this->board_type==0||$this->board_type==2) {
					if ($this->board_uploadtype==0||$this->board_uploadtype==1) {
						$output .= '<tr>
						<td class="'.$label_class.'">'._('File').'<a href="#" onclick="togglePassword();" style="text-decoration: none;">&nbsp;</a></td><td>';
						if ($oekaki=='') {
							$output .= '<input type="file" name="imagefile" size="35" accesskey="f">';
							if ($replythread==0&&$this->board_enablenofile==1) {
								$output .= ' [<label><input type="checkbox" name="nofile" accesskey="x"> '._('No File').'</label>]';
							}
						} else {
							$output .= _('Shown Below').'<input type="hidden" name="oekaki" value="'.$oekaki.'">';
						}
						$output .= '</td>
						</tr>';
					}
					if ($oekaki == '' && ($this->board_uploadtype == 1 || $this->board_uploadtype == 2)) {
						$output .= '<tr><td class="'.$label_class.'">Embed</td><td><input type="text" name="embed" size="28" maxlength="75" accesskey="e">&nbsp;<select name="embedtype"><option value="youtube">YouTube</option></select></td></tr>';
					}
					$output .= '<tr>
					<td class="'.$label_class.'">'._('Password').'</td><td><input type="password" name="postpassword" size="8" accesskey="p">&nbsp;'._('(for post and file deletion)').'</td>
					</tr>';
				}
				if ($this->board_type == 0 || $this->board_type == 2) {
					$output .= '<tr id="passwordbox"><td></td><td></td></tr>
					<tr>
					<td colspan="2"><div class="rules">'.$postboxnotice .'</div></td>
					</tr>';
				}
				$output .= '</tbody></table>';
				if ($this->board_type == 1) {
					$output .= '<input type="hidden" name="postpassword" value="">';
				}
				$output .= '</form>';
				if ($this->board_type == 0 || $this->board_type == 2) {
					$output .= '<hr>';
				}
			}
			$output .= '</div><script type="text/javascript">set_inputs("postform")</script>';
		}
		return $output;
	}
	
	function DisplayBoardList($is_textboard = false) {
		/*global $tc_db;
		
		$output = '';
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
		if (is_file(TC_ROOTDIR . 'boards.html')) {
			$div_name = ($is_textboard) ? 'topbar' : 'navbar';
			return '<div class="'.$div_name.'">'.file_get_contents(TC_ROOTDIR . 'boards.html').'</div>';
		} else {
			return '';
		}
	}

	/* Have javascript add the [D & B] links on the page if the tcmod cookie is set to yes */
	function DeleteAndBanLinks($post_id, $is_thread) {
		global $tc_db;
		$is_thread_jsfriendly = ($is_thread) ? 'true' : 'false';
		return '<span id="dnb'.$post_id.'"><script type="text/javascript">delandbanlinks(\'dnb'.$post_id.'\', \''.$this->board_dir.'\', '.$post_id.', '.$is_thread_jsfriendly.');</script></span>';
	}

	/* The name is deceiving, this function will trim the threads to the page limit AND delete posts which are older than limited */
	function TrimToPageLimit() {
		global $tc_db;
		if ($this->board_maxage!=0) {
			/* If the maximum thread age setting is not zero (do not delete old threads), find posts which are older than the limit, and delete them */
			$results = $tc_db->GetAll("SELECT `id`, `postedat` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = 0 AND `stickied` = 0");
			foreach($results AS $line) {
				if ($line['postedat']+($this->board_maxage*3600)<time()) {
					/* If it is older than the limit */
					$post_class = new Post($line['id'], $this->board_dir);
					$post_class->Delete();
				}
			}
		}
		if ($this->board_maxpages!='0') {
			/* If the maximum pages setting is not zero (do not limit pages), find posts which are over the limit, and delete them */
			$results = $tc_db->GetAll("SELECT `id`, `stickied` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `threadid` = 0");
			if (calculatenumpages($this->board_type, count($results))>=$this->board_maxpages) {
				$this->board_maxthreads = $this->board_maxpages * TC_THREADS;
				$numthreadsover = count($results) - $this->board_maxthreads;
				if ($numthreadsover>0) {
					$resultspost = $tc_db->GetAll("SELECT `id`, `stickied` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `threadid` = '0' AND `stickied` = '0' ORDER BY `lastbumped` ASC LIMIT " . $numthreadsover);
					foreach($resultspost AS $linepost) {
						if ($linepost['stickied']==0) {
							$post_class = new Post($linepost['id'], $this->board_dir);
							$post_class->Delete();
						}
					}
				}
			}
		}
	}

	function Footer($noboardlist = false, $executiontime = '', $hide_extra = false) {
		global $tc_db;
		$output = '';
		if (!$hide_extra && !$noboardlist) {
			$output .= '<br>'.$this->DisplayBoardList();
		}
		if ($hide_extra) {
			$output .= '<div class="legal">';
		} else {
			$output .= '<p class="footer">';
		}
		$output .= '- <a href="http://www.trevorchan.org/" target="_top">Trevorchan</a> v'.TC_VERSION.' + Created by <a href="http://www.tj9991.com/" target="_top">tj9991</a> -';
		if ($executiontime!='') {
			$output .= '<br>Took '.round($executiontime, 2).'s';
		}
		if ($hide_extra) {
			$output .= '<br><a href="'.TC_BOARDSFOLDER.'manage.php">'._('Manage boards').'</a></div>';
		} else {
			$output .= '</p>';
		}
		$output .= '</body>
		</html>';
		return $output;
	}
}

class Post extends Board {
	/* Declare the public variables */
	var $post_id;
	var $post_threadid;
	var $post_image;
	var $post_imagetype;
	var $post_password;
	var $post_isreported;
	var $post_isthread;
	
	function Post($postid, $board, $is_inserting = false) {
		global $tc_db;
		$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts_".$board."` WHERE `id` = ".mysql_real_escape_string($postid)." LIMIT 1");
		if (count($results)==0&&!$is_inserting) {
			die('Invalid post ID.');
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
			$results = $tc_db->GetAll("SELECT `id`, `image`, `imagetype` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = ".mysql_real_escape_string($this->post_id));
			foreach($results AS $line) {
				$i++;
				$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 WHERE `id` = ".$line['id']." AND `threadid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
			}
			@unlink(TC_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'.html');
			$this->DeleteImage(false, true);
			$tc_db->Execute("DELETE FROM `".TC_DBPREFIX."watchedthreads` WHERE `threadid` = ".mysql_real_escape_string($this->post_id)." AND `board` = '".$this->board_dir."'");
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
			$results = $tc_db->GetAll("SELECT `id`, `image`, `imagetype` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `threadid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
			if (count($results)>0) {
				foreach($results AS $line) {
					if ($line['image']!=''&&$line['image']!='removed') {
						@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$line['image'].'.'.$line['imagetype']);
						@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$line['image'].'.pch');
						@unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
					}
					if ($update_to_removed) {
						$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `image` = 'removed', `imagemd5` = '' WHERE `id` = ".$line['id']." LIMIT 1");
					}
				}
			}
			$this->DeleteImage($update_to_removed);
		} else {
			if ($this->post_image!=''&&$this->post_image!='removed') {
				@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$this->post_image.'.'.$this->post_imagetype);
				@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$this->post_image.'.pch');
				@unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$this->post_image.'s.'.$this->post_imagetype);
				if ($update_to_removed) {
					$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `image` = 'removed', `imagemd5` = '' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
				}
			}
		}
	}

	function Insert($threadid, $user, $tripcode, $email, $subject, $message, $image, $imagetype, $imagemd5, $image_w, $image_h, $image_size, $thumb_w, $thumb_h, $password, $postedat, $lastbumped, $ip, $posterauthority, $stickied, $locked) {
		global $tc_db;
		require_once(TC_ROOTDIR."inc/encryption.php");
		$query = "INSERT INTO `".TC_DBPREFIX."posts_".$this->board_dir."` ( `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `imagemd5` , `image_w` , `image_h` , `image_size` , `thumb_w` , `thumb_h` , `password` , `postedat` , `lastbumped` , `ip` , `ipmd5` , `posterauthority` , `stickied` , `locked` ) VALUES ( '".mysql_real_escape_string($threadid)."', '".mysql_real_escape_string($user)."', '".mysql_real_escape_string($tripcode)."', '".mysql_real_escape_string($email)."', '".mysql_real_escape_string($subject)."', '".mysql_real_escape_string($message)."', '".mysql_real_escape_string($image)."', '".mysql_real_escape_string($imagetype)."', '".mysql_real_escape_string($imagemd5)."', '".mysql_real_escape_string($image_w)."', '".mysql_real_escape_string($image_h)."', '".mysql_real_escape_string($image_size)."', '".mysql_real_escape_string($thumb_w)."', '".mysql_real_escape_string($thumb_h)."', '".mysql_real_escape_string($password)."', '".mysql_real_escape_string($postedat)."', '".mysql_real_escape_string($lastbumped)."', '".mysql_real_escape_string(md5_encrypt($ip, TC_RANDOMSEED))."', '".md5($ip)."', '".mysql_real_escape_string($posterauthority)."', '".mysql_real_escape_string($stickied)."', '".mysql_real_escape_string($locked)."' )";
		$tc_db->Execute($query);
		return $tc_db->Insert_Id();
	}

	function Report() {
		global $tc_db;
		require_once(TC_ROOTDIR."inc/encryption.php");
		return $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."reports` ( `board` , `postid` , `when` , `ip` ) VALUES ( '".mysql_real_escape_string($this->board_dir)."' , ".mysql_real_escape_string($this->post_id)." , ".time()." , '".md5_encrypt($_SERVER['REMOTE_ADDR'], TC_RANDOMSEED)."' )");
	}
}

?>