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
	var $board_maximagesize;
	var $board_maxage;
	var $board_markpage;
	var $board_maxreplies;
	var $board_maxpages;
	var $board_messagelength;
	var $board_locked;
	var $board_defaultstyle;
	var $board_redirecttothread;
	var $board_enablecaptcha;
	var $board_enablenofile;
	var $board_enablearchiving;
	var $board_loadbalanceurl;
	var $board_loadbalanceurl_formatted;
	var $board_loadbalancepassword;
	var $board_postboxnotice;
	var $pageheader_reply;
	var $pageheader_noreply;
	var $pageheader_css;
	var $pageheader_boardlist;
	var $archive_dir;
	var $smarty;
	var $loadbalancer;
	
	/* Initialization function for the Board class, which is called when a new instance of this class is created.  Takes a board directory as an argument */
	function Board($board) {
		global $tc_db;

		/* If the instance was created with the board argument present, get all of the board info and configuration values and save it inside of the class */
		if ($board!='') {
			$query = "SELECT * FROM `".TC_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($board)."' LIMIT 1";
			$results = $tc_db->GetAssoc($query);
			foreach($results AS $line) {
				$this->board_id                       = $line['id'];
				$this->board_type                     = $line['type'];
				$this->board_uploadtype               = $line['uploadtype'];
				$this->board_dir                      = $line['name'];
				$this->board_desc                     = $line['desc'];
				$this->board_enablereporting          = $line['enablereporting'];
				$this->board_image                    = $line['image'];
				$this->board_includeheader            = $line['includeheader'];
				$this->board_forcedanon               = $line['forcedanon'];
				$this->board_maximagesize             = $line['maximagesize'];
				$this->board_maxage                   = $line['maxage'];
				$this->board_markpage                 = $line['markpage'];
				$this->board_maxreplies               = $line['maxreplies'];
				$this->board_maxpages                 = $line['maxpages'];
				$this->board_messagelength            = $line['messagelength'];
				$this->board_locked                   = $line['locked'];
				$this->board_defaultstyle             = $line['defaultstyle'];
				$this->board_redirecttothread         = $line['redirecttothread'];
				$this->board_enablecaptcha            = $line['enablecaptcha'];
				$this->board_enablenofile             = $line['enablenofile'];
				$this->board_enablearchiving          = $line['enablearchiving'];
				$this->board_enablecatalog            = $line['enablecatalog'];
				$this->board_loadbalanceurl           = $line['loadbalanceurl'];
				$this->board_loadbalancepassword      = $line['loadbalancepassword'];
				$this->board_loadbalanceurl_formatted = ($line['loadbalanceurl'] != '') ? substr($line['loadbalanceurl'], 0, strrpos($line['loadbalanceurl'], '/')) : '';
			}
			
			/* Format the postbox according to this board */
			$this->board_postboxnotice = $this->FormatPostbox(TC_POSTBOX, $this->board_dir);
			
			if ($this->board_loadbalanceurl != '' && $this->board_loadbalancepassword != '') {
				require_once TC_ROOTDIR . 'inc/classes/loadbalancer.class.php';
				$this->loadbalancer = new Load_Balancer;
				
				$this->loadbalancer->url = $this->board_loadbalanceurl;
				$this->loadbalancer->password = $this->board_loadbalancepassword;
			}
		}
	}

	/* Regenerate all board and thread pages */
	function RegenerateAll() {
		$this->RegeneratePages();
		$this->RegenerateThreads();
	}

	function RegeneratePages() {
		global $tc_db;
		
		$this->InitializeSmarty();
		
		$numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
		$boardpage = 0;
		
		$hide_extra = false;
		switch ($this->board_type) {
		case 1:
			$numthreadsdisplayed = 15;
			$hide_extra = true;
			break;
			
		case 3:
			$numthreadsdisplayed = 30;
			break;
			
		default:
			$numthreadsdisplayed = TC_THREADS;
		}
		
		$boardstooutput = calculatenumpages($this->board_type, ($numpostsleft-1));
		$this->CachePageHeaderData();
		
		if ($numpostsleft>0) {
			$cached_postbox = $this->Postbox(0, '', $this->board_postboxnotice);
			while ($numpostsleft>0) {
				$executiontime_start_regeneratepages = microtime_float();
				
				$page = $this->pageheader_noreply . $cached_postbox;
				
				if ($this->board_type != 1) {
					$page .= '<script type="text/javascript">' . "\n" .
					'if (getCookie("tcmod")=="yes") {' . "\n" .
					'	document.write(\'<form id="delform" action="' . TC_CGIPATH . '/manage_page.php" method="post">\');' . "\n" .
					'} else {' . "\n" .
					'	document.write(\'<form id="delform" action="' . TC_CGIPATH . '/board.php" method="post">\');' . "\n" .
					'}' . "\n" .
					'</script>' . "\n" .
					'<input type="hidden" name="board" value="' . mysql_real_escape_string($this->board_dir) . '">';
				}
				
				$results = $tc_db->GetAll("SELECT `id` , `deletedat` FROM `" . TC_DBPREFIX . "posts_" . $this->board_dir . "` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT " . ($boardpage * $numthreadsdisplayed) . ', ' . $numthreadsdisplayed);
				
				$thread_relative_id = 0;
				$thread_ids = array();
				foreach($results AS $line) {
					/* If the thread is on the page set to mark, and hasn't been marked yet, mark it */
					if ($line['deletedat'] == 0 && $this->board_markpage > 0 && $boardpage >= $this->board_markpage) {
						$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `deletedat` = '" . (time() + 7200) . "' WHERE `id` = '" . $line['id'] . "' LIMIT 1");
					}
					$thread_ids[] = $line[0];
				}
				
				if ($this->board_type == 3) {
					$page .= '<center><table width="98%"><tr><td class="postblock" align="center" width="1%">No.</td><td class="postblock" style="text-align:center;width:25%;">Name</td><td class="postblock" align="center" width="1%">File</td><td class="postblock" align="center" width="1%">Tag</td><td class="postblock" style="text-align:center;width:40%;">Subject</td><td class="postblock" align="center" width="1%">Size</td><td class="postblock" align="center" width="1%">Date</td><td class="postblock" style="text-align:center;width:1px;">Rep.</td><td class="postblock" style="width:1px;">&nbsp;</td></tr>';
				}
				$page .= $this->BuildThread($thread_ids, true, false, $thread_relative_id);
				if ($this->board_type == 3) {
					$page .= '</table></center><br>';
				}
				
				if ($this->board_type != 1) {
					$page .= '<table class="userdelete"><tbody><tr><td>'.ucwords(_gettext('Delete Post')).' [<script type="text/javascript">' . "\n" .
					'if (getCookie("tcmod")=="yes") {' . "\n" .
					'	document.write(\'<input type="checkbox" name="multiban" id="multiban" value="on"><label for="multiban">and ban<\/label>]<br>Reason: <input name="reason" size="8" />\');' . "\n" .
					'} else {' . "\n" .
					'	document.write(\'';
					if ($this->board_type != 1) {
						$page .= '<input type="checkbox" name="fileonly" id="fileonly" value="on"><label for="fileonly">'._gettext('File Only').'<\/label>]<br>';
					}
					$page .= _gettext('Password').' <input type="password" name="postpassword" size="8" />\');' . "\n" .
					'}' . "\n" .
					'</script>&nbsp;<script type="text/javascript">' . "\n" .
					'if (getCookie("tcmod")=="yes") {' . "\n" .
					'	document.write(\'<input name="action" value="multidel" type="submit" />\');' . "\n" .
					'} else {' . "\n" .
					'	document.write(\'<input name="deletepost" value="'._gettext('Delete').'" type="submit">\');' . "\n" .
					'}' . "\n" .
					'</script>' . "\n";
		
					if ($this->board_enablereporting == 1) {
						$page .= '<input name="reportpost" value="'._gettext('Report').'" type="submit">' . "\n";
					}
					
					$page .= '</td></tr></tbody></table><script type="text/javascript">' . "\n" .
					'	document.write(\'<\/form>\');' . "\n" .
					'</script>' . "\n";
					
					$page .= "\n" . '<script type="text/javascript">set_delpass("delform")</script>' . "\n";

					$page .= '<table border="1"><tbody><tr><td>';
					
					if ($boardpage==0) {
						$page .= _gettext('Previous');
					} else {
						if ($boardpage-1!=0) {
							$page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/'.($boardpage-1).'.html"><input value="'._gettext('Previous').'" type="submit"></form>';
						} else {
							$page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/"><input value="'._gettext('Previous').'" type="submit"></form>';
						}
					}
					
					$page .= '</td><td>';
					
					for ($i=0;$i<=$boardstooutput;$i++) {
						if ($boardpage == $i) {
							$page .= '&#91;'.$i.'&#93;';
						} else {
							if ($i != 0) {
								$page .= '&#91;<a href="'.TC_BOARDSFOLDER.$this->board_dir.'/'.$i.'.html">'.$i.'</a>&#93;';
							} else {
								$page .= '&#91;<a href="'.TC_BOARDSFOLDER.$this->board_dir.'/">'.$i.'</a>&#93;';
							}
						}
						$page .= ' ';
					}
					
					/* Remove the unwanted space */
					$page = substr($page, 0, -1);
					
					$page .= '</td><td>';
					
					if ($boardpage == $boardstooutput) {
						$page .= _gettext('Next');
					} else {
						$page .= '<form method="get" action="'.TC_BOARDSFOLDER.$this->board_dir.'/'.($boardpage+1).'.html"><input value="'._gettext('Next').'" type="submit"></form>';
					}
					
					$page .= '</td></tr></tbody></table>';
				}
				
				$page .= $this->Footer(false, (microtime_float()-$executiontime_start_regeneratepages), $hide_extra);
				
				if ($boardpage==0) {
					$this->PrintPage(TC_BOARDSDIR.$this->board_dir.'/'.TC_FIRSTPAGE, $page, $this->board_dir);
				} else {
					$this->PrintPage(TC_BOARDSDIR.$this->board_dir.'/'.$boardpage.'.html', $page, $this->board_dir);
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
			$page = $this->pageheader_noreply;
			$page .= $this->Postbox(0, '', $this->board_postboxnotice);
			$page .= $this->Footer(false, (microtime_float()-$executiontime_start_blankindex), $hide_extra);
			$this->PrintPage(TC_BOARDSDIR.$this->board_dir.'/'.TC_FIRSTPAGE, $page, $this->board_dir);
		}
		/* If text board, rebuild thread list html files */
		if ($this->board_type==1) {
			$numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
			$liststooutput = floor(($numpostsleft-1) / 40);
			$listpage = 0;
			$currentpostwave = 0;
			while ($numpostsleft>0) {
				$executiontime_start_list = microtime_float();
				$page = $this->PageHeader(0, $currentpostwave, $listpage, $liststooutput);
				$page .= $this->Footer(false, (microtime_float()-$executiontime_start_list), $hide_extra);
				if ($listpage==0) {
					$this->PrintPage(TC_BOARDSDIR.$this->board_dir.'/list.html', $page, $this->board_dir);
				} else {
					$this->PrintPage(TC_BOARDSDIR.$this->board_dir.'/list'.$listpage.'.html', $page, $this->board_dir);
				}
				$currentpostwave += 40;
				$numpostsleft -= 40;
				$listpage++;
			}
		}
		/* If the board has catalog mode enabled, build it */
		if ($this->board_enablecatalog == 1 && ($this->board_type == 0 || $this->board_type == 2)) {
			$executiontime_start_catalog = microtime_float();
			$catalog_page = $this->PageHeader(0, 0, -1, -1, false, true);
			$catalog_page .= '&#91;<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/">'._gettext('Return').'</a>&#93; <div class="catalogmode">'._gettext('Catalog Mode').'</div>' . "\n";
			$catalog_page .= '<table border="1" align="center">' . "\n" . '<tr>' . "\n";
			
			$results = $tc_db->GetAll("SELECT `id` , `subject` , `filename` , `filetype` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
			$numresults = count($results);
			if ($numresults > 0) {
				$celnum = 0;
				$trbreak = 0;
				$row = 1;
				/* Calculate the number of rows we will actually output */
				$maxrows = max(1, (($numresults - ($numresults % 12)) / 12));
				foreach ($results as $line) {
					$celnum++;
					$trbreak++;
					if ($trbreak == 13 && $celnum != $numresults) {
						$catalog_page .= '</tr>' . "\n" . '<tr>' . "\n";
						$row++;
						$trbreak = 1;
					}
					if ($row <= $maxrows) {
						$replies = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = " . $line['id']);
						$catalog_page .= '<td valign="middle">' . "\n" . '<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $line['id'] . '.html"';
						if ($line['subject'] != '') {
							$catalog_page .= ' title="' . $line['subject'] . '"';
						}
						$catalog_page .= '>';
						if ($line['filename'] != '' && $line['filename'] != 'removed') {
							if ($line['filetype'] == 'jpg' || $line['filetype'] == 'png' || $line['filetype'] == 'gif') {
								$file_path = ($this->board_loadbalanceurl == '') ? (TC_BOARDSFOLDER . $this->board_dir) : $this->board_loadbalanceurl_formatted;
								$catalog_page .= '<img src="' . $file_path . '/thumb/' . $line['filename'] . 'c.' . $line['filetype'] . '" alt="' . $line['id'] . '" border="0">';
							} else {
								$catalog_page .= 'File';
							}
						} elseif ($line['filename'] == 'removed') {
							$catalog_page .= 'Rem.';
						} else {
							$catalog_page .= 'None';
						}
						$catalog_page .= '</a><br>' . "\n" . '<small>' . $replies . '</small>' . "\n" . '</td>' . "\n";
					}
				}
			} else {
				$catalog_page .= '<td>' . "\n" . 'No threads.' . "\n" . '</td>' . "\n";
			}
			
			$catalog_page .= '</tr>' . "\n" . '</table><br>';
			
			$catalog_page .= $this->Footer(false, (microtime_float()-$executiontime_start_catalog));
			
			$this->PrintPage(TC_BOARDSDIR . $this->board_dir . '/catalog.html', $catalog_page, $this->board_dir);
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
				if (preg_match("/catalog.html/", $htmlfile)) {
					if (!($this->board_enablecatalog == 1 && ($this->board_type == 0 || $this->board_type == 2))) {
						unlink($htmlfile);
					}
				}
			}
		}
	}

	/* Regenerate each thread's corresponding html file, starting with the most recently bumped */
	function RegenerateThreads() {
		global $tc_db;
		
		$res_threadlist = array();
		$results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `parentid` = 0 AND `IS_DELETED` = 0 ORDER BY `lastbumped` DESC");
		foreach($results AS $line) {
			$res_threadlist[] = $line['id'].'.html';
			if (TC_FIRSTLAST) {
				$res_threadlist[] = $line['id'].'-100.html';
				$res_threadlist[] = $line['id'].'+50.html';
			}
			
			$this->RegenerateThread($line['id']);
		}
		
		/* Remove any html files which shouldn't be there */
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

	function RegenerateThread($thread_op_id) {
		global $tc_db;
		
		$this->InitializeSmarty();
		$this->CachePageHeaderData();

		$thread_exists = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `id` = ".mysql_real_escape_string($thread_op_id)." AND `IS_DELETED` = 0 AND `parentid` = 0 LIMIT 1");
		if ($thread_exists == 1) {
			$executiontime_start_regeneratethread = microtime_float();
			
			$numreplies = $tc_db->GetOne("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `parentid` = ".mysql_real_escape_string($thread_op_id)." AND `IS_DELETED` = 0");
			$modifier_last50 = false;
			$modifier_first100 = false;
			if (TC_FIRSTLAST && $numreplies > 49) {
				$modifier_last50 = true;
				if ($numreplies > 99) {
					$modifier_first100 = true;
				}
			}
			
			$thread_page = $this->PageHeader($thread_op_id, '', $this->board_postboxnotice);
			if ($this->board_type == 1) {
				$thread_page .= '<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/">'._gettext('Return').'</a>';
				if ($modifier_last50) {
					$thread_page .= ' <a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_op_id . '.html">' . _gettext('Entire Thread') . '</a>';
					if ($modifier_first100) {
						$thread_page .= ' <a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_op_id . '-100.html">' . _gettext('First 100 posts') . '</a>';
					}
					$thread_page .= ' <a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_op_id . '+50.html">' . _gettext('Last 50 posts') . '</a>';
				}
				$thread_page .= '<br><br>';
				$hide_extra = true;
			} else {
				$thread_page .= '&#91;<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/">'._gettext('Return').'</a>&#93; ';
				if ($modifier_last50) {
					$thread_page .= '&#91;<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_op_id . '.html">' . _gettext('Entire Thread') . '</a>&#93; ';
					if ($modifier_first100) {
						$thread_page .= '&#91;<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_op_id . '-100.html">' . _gettext('First 100 posts') . '</a>&#93; ';
					}
					$thread_page .= '&#91;<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_op_id . '+50.html">' . _gettext('Last 50 posts') . '</a>&#93; ';
				}
				$thread_page .= '<div class="replymode">'._gettext('Posting mode: Reply').'<!tc_postmodeinfo></div>';
				$hide_extra = false;
			}
			$thread_page .= $this->Postbox($thread_op_id, '', $this->board_postboxnotice);
			
			if ($modifier_last50) {
				$thread_page_last50 = $thread_page;
				$thread_page_last50 .= $this->BuildThread($thread_op_id, false, false, 0, 'last50');
				if ($modifier_first100) {
					$thread_page_first100 = $thread_page;
					$thread_page_first100 .= $this->BuildThread($thread_op_id, false, false, 0, 'first100');
				}
			}
			$thread_page .= $this->BuildThread($thread_op_id);
			
			$footer = $this->Footer(false, (microtime_float()-$executiontime_start_regeneratethread), $hide_extra);
			$thread_page .= $footer;
			if ($modifier_last50) {
				$thread_page_last50 .= $footer;
				if ($modifier_first100) {
					$thread_page_first100 .= $footer;
				}
			}
			
			$thread_page = str_replace('<!tc_postmodeinfo>', '', $thread_page);
			if ($modifier_last50) {
				$thread_page_last50 = str_replace('<!tc_postmodeinfo>', ' &#91;' . _gettext('Last 50 posts') . '&#93;', $thread_page_last50);
				if ($modifier_first100) {
					$thread_page_first100 = str_replace('<!tc_postmodeinfo>', ' &#91;' . _gettext('First 100 posts') . '&#93;', $thread_page_first100);
				}
			}
			
			$this->PrintPage(TC_BOARDSDIR . $this->board_dir . $this->archive_dir . '/res/' . $thread_op_id . '.html', $thread_page, $this->board_dir);
			if ($modifier_last50) {
				$this->PrintPage(TC_BOARDSDIR . $this->board_dir . $this->archive_dir . '/res/' . $thread_op_id . '+50.html', $thread_page_last50, $this->board_dir);
				if ($modifier_first100) {
					$this->PrintPage(TC_BOARDSDIR . $this->board_dir . $this->archive_dir . '/res/' . $thread_op_id . '-100.html', $thread_page_first100, $this->board_dir);
				}
			}
		}
	}

	function BuildThread($parentid, $page = false, $resurrect = false, $thread_relative_id = 0, $modifier = '') {
		global $tc_db;
		$buildthread_output = '';
		
		// {{{ Check if an array of ID's were supplied, and if not, make it an array anyways
		
		if (!is_array($parentid)) {
			$parentid = array($parentid);
		}
		
		// }}}
		// {{{ Validate each ID being a number greater than zero
		
		validateIds($parentid);
		
		// }}}
		
		/*if ($resurrect) {
			$isdeleted_check = '1';
		} else {
			$isdeleted_check = '0';
		}*/
		
		// {{{ Data-fetch for each thread ID supplied
		
		if ($this->board_type == 1) {
			// {{{ Calculate the number of threads which will be displayed on the front page
			
			$num_threads_onfrontpage = min(15, $tc_db->GetOne('SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = 0 AND `parentid` = 0'));
			$isdeleted_check = '';
			
			// }}}
		} else {
			$isdeleted_check = 'AND `IS_DELETED` = \'0\' ';
		}
		$query_idsegment = '';
		foreach ($parentid as $this_parentid) {
			$query_idsegment .= '`id` = ' . mysql_real_escape_string($this_parentid) . ' OR ';
		}
		$query_idsegment = substr($query_idsegment, 0, -4);
		$results = $tc_db->GetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE ('.$query_idsegment.') AND `parentid` = 0 ' . $isdeleted_check . 'ORDER BY `stickied` DESC, `lastbumped` DESC');
		if (count($results) == 0) {
			die('buildthread(): error.  No posts in thread to build from.');
		}
		
		// }}}
		// {{{ Javascript which adds the form for post deletion
		
		if (!$page) {
			$buildthread_output .= '<script type="text/javascript">' . "\n" .
			'if (getCookie("tcmod")=="yes") {' . "\n" .
			'	document.write(\'<form id="delform" action="' . TC_CGIPATH . '/manage_page.php" method="post">\');' . "\n" .
			'} else {' . "\n" .
			'	document.write(\'<form id="delform" action="' . TC_CGIPATH. '/board.php" method="post">\');' . "\n" .
			'}' . "\n" .
			'</script>' . "\n" .
			'<input type="hidden" name="board" value="' . $this->board_dir . '">' . "\n";
		}
		
		// }}}
		
		foreach($results AS $line) {
			$thread_id = $line['id'];
			
			$numReplies = $tc_db->GetOne('SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id) . ' ' . $isdeleted_check);
			if (($this->board_type == 0 ||$this->board_type == 2) || ($this->board_type == 3 && !$page)) {
				$numImageReplies = 0;
				
				// {{{ Calculate the number of image replies to the thread being generated
				
				if ($page) {
					$query = 'SELECT COUNT(*) FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ' . $isdeleted_check . 'AND `filename` != \'\' AND `filename` != \'removed\' ORDER BY `id` DESC LIMIT 0, ';
					if ($line['stickied'] == 0 && $numReplies > TC_REPLIES) {
						$query .= ($numReplies - TC_REPLIES);
					} elseif ($numReplies > TC_REPLIESSTICKY) {
						$query .= ($numReplies - TC_REPLIESSTICKY);
					} else {
						$query = '';
					}
					if ($query != '') {
						$numImageReplies = $tc_db->GetOne($query);
					}
				}
				
				// }}}				
				// {{{ Span displayed if a user has hidden a thread, which includes a link to the thread along with a button to un-hide it
				
				if ($page) {
					$buildthread_output .= '<span id="unhidethread'.$line['id'].$this->board_dir.'" style="display: none;">' . "\n" .
					'	Thread <a href="'.TC_BOARDSFOLDER.$this->board_dir.'/res/'.$line['id'].'.html">' . $line['id'] . '</a> hidden. ' . "\n" .
					'	<a href="#" onclick="javascript:togglethread(\''.$line['id'].$this->board_dir.'\');return false;" title="Un-Hide Thread">' . "\n" .
					'		<img src="' . TC_WEBPATH . '/lib/icons/zoom-in.gif" border="0" alt="zoom in">' . "\n" .
					'	</a>' . "\n" .
					'</span>' . "\n";
				}
				
				// }}}
				// {{{ Div which will contain the entire thread, including its replies
				
				$buildthread_output .= '<div id="thread'.$line['id'].$this->board_dir.'">' . "\n";
				
				// }}}
				// {{{ Javascript which will automatically hide the thread if it finds the ID in the hidden threads cookie
				
				if ($page) {
					$buildthread_output .= '<script type="text/javascript">' . "\n" .
						'if (hiddenthreads.toString().indexOf(\''.$line['id'].$this->board_dir.'\')!==-1) {' . "\n" .
						'	document.getElementById(\'unhidethread'.$line['id'].$this->board_dir.'\').style.display = \'block\';' . "\n" .
						'	document.getElementById(\'thread'.$line['id'].$this->board_dir.'\').style.display = \'none\';' . "\n" .
						'}' . "\n" .
						'</script>' . "\n";
				}
				// }}}
				// {{{ Thread-starting post, which is not added if the modifier of last50 is being used
				
				if ($modifier != 'last50') {
					$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line, $numReplies, $thread_relative_id);
				}
				
				// }}}
				// {{{ Thread replies display
				
				if ($numReplies>0) {
					if (!$page) {
						// {{{ Non-page reply fetch
						
						$query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ' . $isdeleted_check . 'ORDER BY `id` ';
						switch ($modifier) {
						case 'last50':
							$query .= 'DESC LIMIT 50';
							break;
							
						case 'first100':
							$query .= 'ASC LIMIT 99';
							break;
							
						default:
							$query .= 'ASC';
							break;
							
						}
						$results = $tc_db->GetAll($query);
						if ($modifier == 'last50') {
							$results = array_reverse($results);
						}
						
						// }}}
					} else {
						// {{{ Page reply fetch
						
						$buildthread_output .= '<div id="replies'.$line['id'].$this->board_dir.'">';
						if ($line['stickied'] == 0) {
							$numrepliesdisplayed = TC_REPLIES;
						} else {
							$numrepliesdisplayed = TC_REPLIESSTICKY;
						}
						if ($numReplies > $numrepliesdisplayed) {
							$buildthread_output .= '<span class="omittedposts">' . "\n" .
							'	 ' . ($numReplies-$numrepliesdisplayed).' post';
							$buildthread_output .= ($numReplies-$numrepliesdisplayed != 1) ? 's' : '';
							if ($numImageReplies > 0) {
								$buildthread_output .= ' and ' . $numImageReplies . ' image';
								$buildthread_output .= ($numImageReplies != 1) ? 's' : '';
							}
							$buildthread_output .= ' omitted. '._gettext('Click Reply to view.') . "\n" .
							'</span>' . "\n";
						}
						/* Retrieves the three newest posts from the thread in descending order, which is backwards for what we want, so we apply array_reverse on the result */
						$query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ' . $isdeleted_check . 'ORDER BY `id` DESC LIMIT '.$numrepliesdisplayed;
						$results = array_reverse($tc_db->GetAll($query));
						
						// }}}
					}
					foreach($results AS $line_reply) {
						$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line_reply);
					}
					if (!$page) {
						$buildthread_output .= '</span>' . "\n";
					}
					$buildthread_output .= '</div>' . "\n";
				}
				
				// }}}
				
				$buildthread_output .= '</div>' . "\n" .
				'<br clear="left">' . "\n" . 
				'<hr>' . "\n";
			} elseif ($this->board_type == 3 && $page) {
				// {{{ Upload imageboard page generation
				
				$buildthread_output .= '<tr';
				/* If the thread is two hours or less from being pruned, add the style for old rows */
				if (!$line['stickied'] && (($this->board_maxage > 0 && ($line['postedat']  + ($this->board_maxage * 3600)) < (time() + 7200)) || ($line['deletedat'] > 0 && $line['deletedat'] <= (time() + 7200)))) {
					$buildthread_output .= ' class="replyhl"';
				}
				$buildthread_output .= '>' . "\n" .
				'<td align="center">' . "\n" .
				'	' . $thread_id . "\n" .
				'</td>' . "\n" .
				'<td>' . "\n" .
				'<span class="commentpostername">';
				if ($line['name'] == '' && $line['tripcode'] == '') {
					$buildthread_output .= TC_ANONYMOUS;
				} else if (!($line['name'] == '' && $line['tripcode'] != '')) {
					$buildthread_output .= $line['name'];
				}
				$buildthread_output .= '</span><span class="postertrip">';
				if ($line['tripcode'] != '') {
					$buildthread_output .= '!'.$line['tripcode'];
				}
				$buildthread_output .= '</td>' . "\n" .
				'<td align="center">[<a href="' . TC_BOARDSFOLDER . $this->board_dir . $this->archive_dir . '/src/' . $line['filename'] . '.' . $line['filetype'] . '" target="_blank">' . $line['filename'] . '.' . $line['filetype'] . '</a>]</td>';
				if ($line['tag'] == '') $line['tag'] = '*';
				$buildthread_output .= '<td align="center">[' . $line['tag'] . ']</td>' . "\n" .
				'<td>' . $line['subject'] . '</td>' . "\n" .
				'<td align="center">';
				if ($line['filesize_formatted'] == '') {
					$buildthread_output .= ConvertBytes($line['filesize']);
				} else {
					$buildthread_output .=$line['filesize_formatted'];
				}
				$buildthread_output .= '</td>' . "\n" .
				'<td><nobr>' . date("y/m/d(D)H:i", $line['postedat']) . '</nobr></td>' . "\n" .
				'<td align="center">' . $numReplies . '</td>' . "\n" .
				'<td align="center">[<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $line['id'] . '.html">Reply</a>]</td>' . "\n" .
				'</tr>';
				
				// }}}
			} else {
				// {{{ Text board page generation
				
				if ($modifier != 'last50') {
					$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line, $numReplies, $thread_relative_id, 1, $num_threads_onfrontpage);
				}
				
				if ($numReplies > 5 && $page) {
					$buildthread_output .= '<p class="hidden">'._gettext('The 5 newest replies are shown below.').'<br/><a href="res/'.$line['id'].'.html">'._gettext('Read this thread from the beginning').'</a>';
					if (TC_FIRSTLAST && $numReplies > 49) {
						if ($numReplies > 99) {
							$buildthread_output .= ' <a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_id . '-100.html">' . _gettext('First 100 posts') . '</a>';
						}
						$buildthread_output .= ' <a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $thread_id . '+50.html">' . _gettext('Last 50 posts') . '</a>';
					}
					$buildthread_output .= '</p>';
					
					$reply_relative_id = $numReplies-4;
				} else {
					$reply_relative_id = 1;
				}
				$query = 'SELECT * FROM `'.TC_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ORDER BY `id` ';
				
				if ($page) {
					$query .= 'DESC LIMIT 5';
				} else {
					switch ($modifier) {
					case 'last50':
						$query .= 'DESC LIMIT 50';
						$reply_relative_id = $numReplies - 49;
						break;
						
					case 'first100':
						$query .= 'ASC LIMIT 99';
						break;
						
					default:
						$query .= 'ASC';
						break;
					
					}
				}
				$results2 = $tc_db->GetAll($query);
				if ($page || $modifier == 'last50') {
					$results2 = array_reverse($results2);
				}
				foreach($results2 AS $line2) {
					$reply_relative_id++;
					$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line2, $numReplies, $thread_relative_id, $reply_relative_id, $num_threads_onfrontpage);
				}
				unset($results2);
				
				if (!$page) {
					$buildthread_output .= '<table class="hborder"><tbody><tr><td>'.ucwords(_gettext('Delete Post')).': <script type="text/javascript">' . "\n" .
					'if (getCookie("tcmod")=="yes") {' . "\n" .
					'	document.write(\'[<input type="checkbox" name="multiban" id="multiban" value="on"><label for="multiban">and ban<\/label>]<br>Reason: <input name="reason" size="8" />\');' . "\n" .
					'} else {' . "\n" .
					'	document.write(\'<input type="password" name="postpassword" size="8" />\');' . "\n" .
					'}' . "\n" .
					'</script>&nbsp;<script type="text/javascript">' . "\n" .
					'if (getCookie("tcmod")=="yes") {' . "\n" .
					'	document.write(\'<input name="action" value="multidel" type="submit" />\');' . "\n" .
					'} else {' . "\n" .
					'	document.write(\'<input name="deletepost" value="'._gettext('Delete').'" type="submit">\');' . "\n" .
					'}' . "\n" .
					'</script>' . "\n";
		
					if ($this->board_enablereporting == 1) {
						$buildthread_output .= '<input name="reportpost" value="'._gettext('Report').'" type="submit">' . "\n";
					}
					
					$buildthread_output .= '</td></tr></tbody></table><script type="text/javascript">' . "\n" .
					'	document.write(\'<\/form>\');' . "\n" .
					'</script>' . "\n";
					
					$buildthread_output .= "\n" . '<script type="text/javascript">set_delpass("delform")</script>' . "\n";
				}
				
				$buildthread_output .= '<form name="post'.$line['id'].'" id="post'.$line['id'].'" action="' . TC_CGIPATH . '/board.php" method="post"';
				if ($this->board_enablecaptcha == 1) {
					$buildthread_output .= ' onsubmit="return checkcaptcha(\'post' . $line['id'] . '\');"';
				}
				$buildthread_output .= '>' . "\n" .
				'<input type="hidden" name="board" value="'.$this->board_dir.'">' . "\n" .
				'<input type="hidden" name="replythread" value="'.$thread_id.'">' . "\n" .
				'<input name="email" size="25" value="" style="display: none;">' . "\n" .
				'<table class="postform">' . "\n" .
				'<tr>' . "\n" .
				'	<td class="label">'._gettext('Name').':</td>' . "\n" .
				'	<td><input name="name" size="25"></td>' . "\n" .
				'	<td class="label">'._gettext('Email').':</td>' . "\n" .
				'	<td><input name="em" size="25"></td>' . "\n" .
				'	<td><input type="submit" value="Reply" class="submit"></td>' . "\n" .
				'</tr>' . "\n";
				
				$buildthread_output .= '<tr>' . "\n";
				if ($this->board_enablecaptcha == 1) {
					$buildthread_output .= '<td class="label">'._gettext('Captcha').':</td>' . "\n" .
					'<td>' . "\n" .
					'	<a href="#" onclick="javascript:document.getElementById(\'captchaimage\').src = \'' . TC_CGIPATH . '/captcha.php?\' + Math.random();return false;">' . "\n" .
					'	<img id="captchaimage" src="' . TC_CGIPATH .'/captcha.php" border="0" width="90" height="30" alt="Captcha image">' . "\n" .
					'	</a>&nbsp;' . "\n" .
					'	<input type="text" id="captcha" name="captcha" size="8" maxlength="6">' . "\n" .
					'</td>' . "\n";
				}
				$buildthread_output .= '<td class="label">'._gettext('Password').':</td>' . "\n" .
				'<td>' . "\n" .
				'	<input type="password" name="postpassword" size="8" accesskey="p">' . "\n" .
				'</td>' . "\n" .
				'</tr>' . "\n";
				
				$buildthread_output .= '<tr>' . "\n" .
				'	<td class="postfieldleft">' . "\n" .
				'		<span class="postnum">' . ($numReplies + 2) . '</span>' . "\n" .
				'	</td>' . "\n" .
				'	<td colspan="4">' . "\n" .
				'		<textarea name="message" rows="8" cols="64"></textarea>' . "\n" .
				'	</td>' . "\n" .
				'</tr>' . "\n" .
				'<tr>' . "\n" .
				'<td>&nbsp;</td>' . "\n" .
				'</tr>' . "\n" .
				'</table>' . "\n" .
				'</form>' . "\n" .
				'<script type="text/javascript">set_inputs(\'post' . $line['id'] . '\')</script>' . "\n";
				
				if ($page) {
					$buildthread_output .= '</div></div>';
				} else {
					$buildthread_output .= '<hr>';
				}
				
				// }}}
			}
			
			$thread_relative_id++;
		}
		
		if (!$page) {
			if ($this->board_type != 1) {
				$buildthread_output .= '<table class="userdelete"><tbody><tr><td>'.ucwords(_gettext('Delete Post')).' [<script type="text/javascript">' . "\n" .
				'if (getCookie("tcmod")=="yes") {' . "\n" .
				'	document.write(\'<input type="checkbox" name="multiban" value="on"><label for="multiban">and ban</label>]<br>Reason: <input name="reason" size="8" />\');' . "\n" .
				'} else {' . "\n" .
				'	document.write(\'<input type="checkbox" name="fileonly" value="on"><label for="fileonly">'._gettext('File Only').'</label>]<br>' . _gettext('Password') . ' <input type="password" name="postpassword" size="8" />\');' . "\n" .
				'}' . "\n" .
				'</script>&nbsp;<script type="text/javascript">' . "\n" .
				'if (getCookie("tcmod")=="yes") {' . "\n" .
				'	document.write(\'<input name="action" value="multidel" type="submit" />\');' . "\n" .
				'} else {' . "\n" .
				'	document.write(\'<input name="deletepost" value="'._gettext('Delete').'" type="submit" />\');' . "\n" .
				'}' . "\n" .
				'</script>' . "\n";
				if ($this->board_enablereporting==1) {
					$buildthread_output .= '<input name="reportpost" value="'._gettext('Report').'" type="submit">' . "\n";
				}
				$buildthread_output .= '</td></tr></tbody></table></form>' . "\n";
			}
			$buildthread_output .= '<script type="text/javascript">set_delpass("delform")</script>' . "\n";
		}
		
		return $buildthread_output;
	}
	
	function BuildPost($page, $post_board, $post_board_type, $post, $thread_replies=0, $thread_relative_id='', $reply_relative_id=0, $threads_on_front_page=0) {
		/* If caching is enabled, and this post has already been cached, skip the process and fetch the cached version */
		if (TC_APC && !$page) {
			$cache_post = apc_fetch('post|' . $post_board . '|' . $post['id']);
			if ($cache_post !== false) {
				return $cache_post;
			}
		}
		
		$buildpost_output = '';
		$post_thread_start_id = ($post['parentid']==0) ? $post['id'] : $post['parentid'];
		$post_is_thread = ($post['parentid']==0) ? true : false;
		$post['message'] = stripslashes($post['message']);
		if ($post_board_type != 1) {
			/* Build a post imageboard style */
			$info_file = '';
			$info_post = '';
			$info_image = '';
			$post_is_standard = true;
			$post_is_nofile = true;
			$file_path = ($this->board_loadbalanceurl == '') ? (TC_BOARDSFOLDER . $post_board . $this->archive_dir) : $this->board_loadbalanceurl_formatted;
			$post_thumb = $file_path . '/thumb/' . $post['filename'] . 's.' . $post['filetype'];
			if ($post['filename'] != '' || $post['filetype'] != '' || $post_is_thread != '') {
				$post_is_nofile = false;
				if ($post['filename']=='removed') {
					$post_thumb = 'removed';
				} else {
					/* Check if the filetype is not a default type */
					if ($post['filetype']!='jpg'&&$post['filetype']!='gif'&&$post['filetype']!='png'&&$post['filetype']!='you'&&$post['filetype']!='goo') {
						$post_is_standard = false;
						$filetype_info = getfiletypeinfo($post['filetype']);
						$post_thumb = TC_WEBPATH . '/inc/filetypes/' . $filetype_info[0];
						$post['thumb_w'] = $filetype_info[1];
						$post['thumb_h'] = $filetype_info[2];
					}
				}
			}
			$post_file_url = $file_path . '/src/' . $post['filename'] . '.' . $post['filetype'];
			$post_file_imgelement = '<img src="'.$post_file_url.'" alt="'.$post['id'].'" class="thumb" height="'.$post['image_h'].'" width="'.$post['image_w'].'">';
			$post_file_thumblement = '<img src="'.$post_thumb.'" alt="'.$post['id'].'" class="thumb" height="'.$post['thumb_h'].'" width="'.$post['thumb_w'].'">';
			/* Build the "File: ..." line */
			if (!$post_is_nofile&&($post['filetype']!='you'&&$post['filetype']!='goo'&&$post['filename']!='') && $post['filename'] != 'removed') {
				if ($post['parentid'] == 0 && $thread_relative_id !== '') {
					$info_file .= '<a name="s' . $thread_relative_id . '"></a>';
				}
				$info_file .= '<span class="filesize">'._gettext('File:').' ';
				if ($post_is_standard) {
					$info_file .= '<a href="' . $post_file_url . '" onclick="javascript:expandimg(\'' . $post['id'] . '\', \'' . $post_file_url . '\', \'' . $post_thumb . '\', \'' . $post['image_w'] . '\', \'' . $post['image_h'] . '\', \'' . $post['thumb_w'] . '\', \'' . $post['thumb_h'] . '\');return false;">';
				} else {
					$info_file .= '<a ';
					if (TC_NEWWINDOW) {
						$info_file .= 'target="_blank" ';
					}
					$info_file .= 'href="' . $post_file_url . '">';
				}
				$info_file .= $post['filename'] . '.' . $post['filetype'] . '</a>-(';
				if ($post['filesize_formatted'] == '') {
					$info_file .= ConvertBytes($post['filesize']);
				} else {
					$info_file .= $post['filesize_formatted'];
				}
				if ($post['image_w'] > 0 && $post['image_h'] > 0) {
					$info_file .= ', ' . $post['image_w'] . 'x' . $post['image_h'];
				}
				if ($post['filename_original'] != '' && $post['filename_original'] != $post['filename']) {
					$info_file .= ', ' . $post['filename_original'] . '.' . $post['filetype'];
				}
				$info_file .= ')</span>' . "\n";
				if (TC_THUMBMSG) {
					$info_file .= '<span class="thumbnailmsg"> ';
					if ($post_is_standard) {
						$info_file .= _gettext('Thumbnail displayed, click image for full size.');
					} else {
						$info_file .= _gettext('Extension icon displayed, click image to open file.');
					}
					$info_file .= '</span>' . "\n";
				}
			} else {
				if ($post['parentid'] == 0 && $thread_relative_id !== '') {
					$info_image .= '<a name="s' . $thread_relative_id . '"></a>' . "\n";
				}
			}
			/* Build the "[checkbox] (Poster name)..." line */
			if ($post['filetype'] != 'you' && $post['filetype'] != 'goo' && $post['filename'] != '' && !$post_is_nofile) {
				if ($post['filename'] == 'removed') {
					$info_image .=  '<div hspace="20" style="float:left;text-align:center;padding:14px;margin:3px;border:black 3px dashed;">' . "\n" .
					'	Image<br>' . "\n" .
					'	removed' . "\n" .
					'</div>' . "\n";
				} else {
					$info_image .= '<a ';
					if (TC_NEWWINDOW) {
						$info_image .= 'target="_blank" ';
					}
					$info_image .= 'href="' . $file_path . '/src/'.$post['filename'].'.'.$post['filetype'].'">' . "\n" .
					'<span id="thumb' . $post['id'] . '">' . $post_file_thumblement . '</span>' . "\n" .
					'</a>' . "\n";
				}
			}
			$info_post .= '<a name="'.$post['id'].'"></a>' . "\n" . 
			'<label>' . "\n" . 
			'<script type="text/javascript">' . "\n" .
			'if (getCookie("tcmod")=="yes") {' . "\n" .
			'	document.write(\'<input type="checkbox" name="' . $post['id'] . '" value="POST' . $post['id'] . '">\');' . "\n" .
			'} else {' . "\n" .
			'	document.write(\'<input type="checkbox" name="delete" value="' . $post['id'] . '">\');' . "\n" .
			'}' . "\n" .
			'</script>' . "\n";
			if ($post['subject']!='') {
				$info_post .= '<span class="filetitle">' . "\n" .
				'	' . $post['subject'] . "\n" .
				'</span> ' . "\n";
			}
			
			$info_post .= formatNameAndTrip($post['name'], $post['email'], $post['tripcode']);
			
			if ($post['posterauthority'] == 1) {
				$info_post .= ' <span class="admin">' . "\n" .
				'	##&nbsp;' . _gettext('Admin') . '&nbsp;##' . "\n" .
				'</span>' . "\n";
			} else if ($post['posterauthority'] == 2) {
				$info_post .= ' <span class="mod">' . "\n" .
				'	##&nbsp;'._gettext('Mod').'&nbsp;##' . "\n" .
				'</span>' . "\n";
			} else if ($post['posterauthority'] == 3) {
				$info_post .= ' <span class="vip">' . "\n" .
				'	##&nbsp;VIP&nbsp;##' . "\n" .
				'</span>' . "\n";
			}
			
			$info_post .= ' '.date("y/m/d(D)H:i", $post['postedat']) . "\n" .
			'</label>' . "\n" .
			' <span class="reflink">' . "\n" .
			'	<a href="'.TC_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html#'.$post['id'].'"';
			if (!$page) {
				$info_post .= ' onclick="javascript:highlight(\'' . $post['id'] . '\');"';
			}
			$info_post .= '>' . "\n" .
			'		No.' . "\n" .
			'	</a>' . "\n" .
			'	<a href="'.TC_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html#i'.$post['id'].'"';
			if (!$page) {
				$info_post .= ' onclick="insert(\'>>'.$post['id'].'\');"';
			}
			$info_post .= '>' . "\n" .
			'		' . $post['id'] . "\n" .
			'	</a>' . "\n" .
			'</span>' . "\n" .
			'<span class="extrabtns">' . "\n";
			if ($post['locked']==1) {
				$info_post .= '	 <img style="border: 0;" src="'.TC_BOARDSFOLDER.'locked.gif" alt="'._gettext('Locked').'">' . "\n";
			}
			if ($post['stickied']==1) {
				$info_post .= '	<img style="border: 0;" src="'.TC_BOARDSFOLDER.'sticky.gif" alt="'._gettext('Stickied').'">' . "\n";
			}
			if ($page && $post_is_thread) {
				$info_post .= '	 <span id="hide' . $post['id'] . '"><a href="#" onclick="javascript:togglethread(\'' . $post_thread_start_id . $this->board_dir . '\');return false;" title="Hide Thread"><img src="' . TC_WEBPATH . '/lib/icons/zoom-out.gif" border="0" alt="zoom out"></a></span>' . "\n";
			}
			if (TC_WATCHTHREADS && $post_is_thread) {
				$info_post .= '	 <a href="#" onclick="javascript:addtowatchedthreads(\'' . $post_thread_start_id . '\', \'' . $this->board_dir . '\');return false;" title="Watch Thread"><img src="' . TC_WEBPATH . '/lib/icons/bookmark.gif" border="0" alt="bookmark"></a>' . "\n";
			}
			if ($page && $post_is_thread) {
				if (TC_EXPAND && $thread_replies > TC_REPLIES && $thread_replies < 300) {
					$info_post .= '	 <a href="#" onclick="javascript:expandthread(\'' . $post_thread_start_id . '\', \'' . $this->board_dir . '\');return false;" title="Expand Thread"><img src="' . TC_WEBPATH . '/lib/icons/move.gif" border="0" alt="expand"></a>' . "\n";
				}
				if (TC_QUICKREPLY) {
					$info_post .= '	 <a href="#postbox" onclick="javascript:quickreply(\'' . $post_thread_start_id . '\');" title="' . _gettext('Quick Reply') . '"><img src="' . TC_WEBPATH . '/lib/icons/eject.gif" border="0" alt="up"></a>' . "\n";
				}
			}
			$info_post .= '</span>' . "\n";
			if ($page && $post_is_thread) {
				$info_post .= ' &nbsp; &#91;<a href="'.TC_BOARDSFOLDER . $post_board . '/res/' . $post_thread_start_id . '.html">' . _gettext('Reply') . '</a>&#93;';
				if (TC_FIRSTLAST && $thread_replies > 49) {
					if ($thread_replies > 99) {
						$info_post .= ' &#91;<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $post_thread_start_id . '-100.html">' . _gettext('First 100 posts') . '</a>&#93;';
					}
					$info_post .= ' &#91;<a href="' . TC_BOARDSFOLDER . $this->board_dir . '/res/' . $post_thread_start_id . '+50.html">' . _gettext('Last 50 posts') . '</a>&#93;';
				}
				$info_post .= "\n";
			}
			
			$info_post .= $this->DeleteAndBanLinks($post['id'], $post_is_thread);
			if (!$post_is_thread) {
				$buildpost_output .= '<table>' . "\n" .
				'<tbody>' . "\n" .
				'<tr>' . "\n" .
				'	<td class="doubledash">' . "\n" .
				'		&gt;&gt;' . "\n" .
				'	</td>' . "\n" .
				'	<td class="reply" id="reply'.$post['id'].'">' . "\n" .
				'		' . $info_post . "\n";
				/* Make sure the file line is actually in use to prevent an unwanted <br> */
				if ($info_file!='') {
					$buildpost_output .= '		<br>' . $info_file . "\n";
				}
				/* Another check for an unwanted <br> */
				if ($info_image!='') {
					$buildpost_output .= '		<br>' . $info_image . "\n";
				}
			} else {
				/* And another check for an unwanted <br> */
				if ($info_file!='') {
					$buildpost_output .= $info_file . '		<br>' . "\n";
				}
				$buildpost_output .= $info_image . $info_post;
			}
			$buildpost_output .= '<blockquote>' . "\n";
			if ($post['filetype'] == 'you' || $post['filetype'] == 'goo') {
				$buildpost_output .= '<span style="float: left;">';
				
				if ($post['filetype'] == 'you') {
					$buildpost_output .= '<object width="200" height="164"><param name="movie" value="http://www.youtube.com/v/'.$post['filename'].'"></param><param name="wmode" value="transparent"></param><embed src="http://www.youtube.com/v/'.$post['filename'].'" type="application/x-shockwave-flash" wmode="transparent" width="200" height="164"></embed></object>';
				} elseif ($post['filetype'] == 'goo') {
					$buildpost_output .= '<embed style="width:200px; height:164px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId='.$post['filename'].'&hl=en" flashvars=""></embed>';
				}
				
				$buildpost_output .= '</span>&nbsp;' . "\n";
			}
			
			$buildpost_output .= formatLongMessage($post['message'], $this->board_dir, $post_thread_start_id, $page);
			
			$buildpost_output .= '</blockquote>' . "\n";
			/* If the thread is two hours or less from being pruned, add the marked for deletion message */
			if ($post_board_type != 1 && $post_is_thread && !$post['stickied'] && $this->archive_dir == '' && (($this->board_maxage > 0 && ($post['postedat']  + ($this->board_maxage * 3600)) < (time() + 7200)) || ($post['deletedat'] > 0 && $post['deletedat'] <= (time() + 7200)))) {
				$buildpost_output .= '<span class="oldpost">' . "\n" .
				'	Marked for deletion (old).' . "\n" .
				'</span>' . "\n" .
				'<br>' . "\n";
			}
			if (!$post_is_thread) {
				$buildpost_output .= '	</td>' . "\n" .
				'</tr>' . "\n" . 
				'</tbody>' . "\n" .
				'</table>' . "\n";
			}
		} else {
			if ($thread_relative_id == '') {
				$thread_relative_id = 0;
			}
			/* Build a post text-board style */
			if ($post['IS_DELETED'] == '1') {
				$post['name'] = '';
				$post['email'] = '';
				$post['tripcode'] = 'Deleted';
				$post['message'] = '<font color="gray">'._gettext('This post has been deleted.').'</font>';
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
				$buildpost_output .= $post['subject'];
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
			$buildpost_output .= '>'.$reply_relative_id.'</a></span>&nbsp;<span class="postinfo">Name:&nbsp;';
			
			$buildpost_output .= formatNameAndTrip($post['name'], $post['email'], $post['tripcode']);
			
			$buildpost_output .= ' @ ' . date('Y-m-d H:i', $post['postedat']);
			if (!$page) {
				$buildpost_output .= ' <input type="checkbox" name="delete" value="' . $post['id'] . '">';
			}
			$buildpost_output .= $this->DeleteAndBanLinks($post['id'], $post_is_thread) . "\n" .
			'<span class="id"></span></span></h3><blockquote>' . "\n" .
			formatLongMessage($post['message'], $this->board_dir, $post_thread_start_id, $page) .
			'</blockquote></div>' . "\n";
		}
		
		if (TC_APC && !$page) {
			apc_store('post|' . $post_board . '|' . $post['id'], $buildpost_output);
		}
		
		return $buildpost_output;
	}

	function PageHeader($replythread = '0', $liststart = '0', $listpage = '-1', $liststooutput = '-1', $isoekaki = false, $hidewatchedthreads = false) {
		global $tc_db, $tc_config, $tpl;
		
		$tpl['title'] = '';
		if (TC_DIRTITLE) {
			$tpl['title'] .= '/' . $this->board_dir . '/ - ';
		}
		$tpl['title'] .= $this->board_desc;
		$tpl['head'] = '';
		$tpl['head2'] = '';
		$output = '';
		
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$tpl['head'] .= '<link rel="stylesheet" href="' . TC_BOARDSPATH . '/css/img_global.css">' . "\n" . $this->pageheader_css;
		} else {
			$tpl['head'] .= '<link rel="stylesheet" href="' . TC_BOARDSPATH . '/css/txt_global.css">' . "\n" . 
			'<link rel="stylesheet" href="' . TC_BOARDSPATH . '/css/txt_pseud0ch.css" title="Pseud0ch" media="screen">' . "\n" . 
			'<link rel="stylesheet" href="' . TC_BOARDSPATH . '/css/txt_headline.css" title="Headline" media="screen">' . "\n" . 
			'<link rel="alternate stylesheet" href="' . TC_BOARDSPATH . '/css/txt_yotsuba.css" title="Yotsuba" media="screen">' . "\n";
		}
		if (TC_RSS) {
			$tpl['head'] .= '<link rel="alternate" type="application/rss+xml" title="RSS" href="' . TC_BOARDSPATH . '/' . $this->board_dir . '/rss.xml">' . "\n";
		}
		$tpl['head'] .= '<script type="text/javascript">var style_cookie';
		if ($this->board_type==1) {
			$tpl['head'] .= '_txt';
		}
		$tpl['head'] .= '="tcstyle';
		if ($this->board_type==1) {
			$tpl['head'] .= '_txt';
		}
		$tpl['head'] .= '";</script>';
		$tpl['head2'] .= '<script type="text/javascript">var ispage = ';
		if ($replythread > 0) {
			$tpl['head2'] .= 'false';
		} else {
			$tpl['head2'] .= 'true';
		}
		$tpl['head2'] .= ';</script>';
		if ($this->board_type == 1) {
			if ($replythread == 0) {
				$output .= '<body class="board">';
			} else {
				$output .= '<body class="read">';
			}
		} else {
			$output .= '<body>';
		}
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$output .= '<div class="adminbar">';
			if (TC_STYLESWITCHER) {
				$styles = explode(':', TC_STYLES);
				
				foreach ($styles as $stylesheet) {
					$output .= '[<a href="javascript:set_stylesheet(\'' . ucfirst($stylesheet) . '\')">' . ucfirst($stylesheet) . '</a>]&nbsp;';
				}
				
				if (count($styles) > 0) {
					$output .= '-&nbsp;';
				}
			}
			$output .= '[<a href="'.TC_WEBPATH.'" target="_top">Home</a>]&nbsp;[<a href="' . TC_CGIPATH . '/manage.php" target="_top">Manage</a>]</div>';
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
		if (TC_WATCHTHREADS && !$isoekaki && ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) && !$hidewatchedthreads) {
			$output .= '<div id="watchedthreads" style="top: ' . $ad_top . 'px; left: 25px;" class="watchedthreads">' . "\n" .
			'<div class="postblock" id="watchedthreadsdraghandle" style="width: 100%;">' . _gettext('Watched Threads') . '</div>' . "\n" .
			'<span id="watchedthreadlist"></span>' . "\n" .
			'<div id="watchedthreadsbuttons">' . "\n" .
			'<a href="#" onclick="javascript:hidewatchedthreads();return false;" title="Hide the watched threads box">' . "\n" .
			'<img src="' . TC_WEBPATH . '/lib/icons/arrow-upleft.gif" border="0" alt="hide">' . "\n" .
			'</a>&nbsp;' . "\n" .
			'<a href="#" onclick="javascript:getwatchedthreads(\'0\', \'' . $this->board_dir . '\');return false;" title="Refresh watched threads">' . "\n" .
			'<img src="' . TC_WEBPATH . '/lib/icons/refresh.gif" border="0" alt="refresh">' . "\n" .
			'</a>' . "\n" .
			'</div>' . "\n" .
			'<script type="text/javascript">' . "\n" .
			'watchedthreadselement = document.getElementById(\'watchedthreads\');' . "\n" .
			'if (getCookie(\'showwatchedthreads\') == \'1\') {' . "\n" .
			'	watchedthreadselement.style.top = getCookie(\'watchedthreadstop\');' . "\n" .
			'	watchedthreadselement.style.left = getCookie(\'watchedthreadsleft\');' . "\n" .
			'	watchedthreadselement.style.width = Math.max(250,getCookie(\'watchedthreadswidth\')) + \'px\';' . "\n" .
			'	watchedthreadselement.style.height = Math.max(75,getCookie(\'watchedthreadsheight\')) + \'px\';' . "\n" .
			'	getwatchedthreads(\'' . $replythread . '\', \'' . $this->board_dir . '\');' . "\n" .
			'} else {' . "\n" .
			'	watchedthreadselement.innerHTML = \'<a href="#" onclick="javascript:showwatchedthreads();return false"><img src="' . TC_WEBPATH . '/lib/icons/grow.gif" border="0" title="Restore watched threads"><\/a>\';' . "\n" .
			'	watchedthreadselement.style.width = \'16px\';' . "\n" .
			'	watchedthreadselement.style.height = \'16px\';' . "\n" .
			'}' . "\n" .
			'</script>' . "\n" .
			'</div>' . "\n";
		}
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$output .= '<div class="logo">';
			if ($this->board_image=='') {
				if (TC_HEADERURL!='') {
					$output .= '<img src="'.TC_HEADERURL.'" alt="Logo"><br>' . "\n";
				}
			} else if ($this->board_image!=''&&$this->board_image!="none") {
				$output .= '<img src="'.$this->board_image.'" alt="Logo"><br>' . "\n";
			}
			if (TC_DIRTITLE) {
				$output .= '/'.$this->board_dir.'/ - ';
			}
			$output .= $this->board_desc . '</div>' . "\n" .
			$this->board_includeheader . "\n" .
			'<hr>' . "\n";
		} else if ($this->board_type == 1 && $replythread == 0) {
			/* Text board header */
			
			$output .= '<div class="hborder">' . "\n" .
			'<div class="head">' . "\n" .
			'<a name="menu" rev="contents"></a>' . "\n";
			if ($listpage<0||$liststooutput<0) {
				$output .= '<span class="navlinks"><a href="#0">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>' . "\n";
			}
			$output .= '<h1 align="center">'.$this->board_desc.'</h1>' . "\n" .
			'</div>' . "\n" .
			'</div>' . "\n" .
			'<div class="hborder">' . "\n" .
			'	<div class="head midhead">' . "\n" .
			'		<b>Style:</b> <a href="#" onclick="javascript:set_stylesheet(\'Yotsuba\',true);return false;">Yotsuba</a> <a href="#" onclick="javascript:set_stylesheet(\'Headline\',true);return false;">Headline</a> <a href="#" onclick="javascript:set_stylesheet(\'Pseud0ch\',true);return false;">Pseud0ch</a>' . "\n" .
			'	</div>' . "\n" .
			'</div>';
			
			if ($listpage>=0&&$liststooutput>=0) {
				$output .= 'Pages:&nbsp;<a href="'.TC_BOARDSPATH . '/' . $this->board_dir . '/">Front</a>';
				for ($i = 0; $i <= $liststooutput; $i++) {
					$output .= '&nbsp;<a href="list';
					if ($i!=0) {
						$output .= $i;
					}
					$output .= '.html">'.($i+1).'</a>';
				}
				$output .= '<br>';
			}
			$is_page = ($listpage < 0 || $liststooutput < 0) ? false : true;
			$output .= $this->TextBoardThreadList($this->board_dir, $liststart, $liststooutput, $is_page);
		}
		return $output;
	}
	
	function OekakiHeader($replyto, $postoek) {
		$executiontime_start = microtime_float();
		$this->InitializeSmarty();
		
		$this->CachePageHeaderData();
		
		$page = $this->PageHeader('0', '0', '-1', '-1', true);
		
		$page .= $this->Postbox($replyto, $postoek) .
		'<div style="text-align: center;">' . "\n" .
		'	' . _gettext('Your Image:') . '<br>' . "\n" .
		'	<img src="' . TC_CGIFOLDER . 'tcdrawings/' . $postoek . '.png">' . "\n" .
		'</div>';
	
		$executiontime_stop = microtime_float();
		$page .= $this->Footer(false, ($executiontime_stop - $executiontime_start));
		
		$this->PrintPage('', $page, true);
	}
	
	function TextBoardThreadList($board, $liststart, $liststooutput, $ispage = false) {
		global $tc_db;
		
		$output = '<div class="hborder">' . "\n" . '<div class="head threadldiv">' . "\n" . '<a name="menu"></a>' . "\n" . '<table class="threads">' . "\n" .
		'<thead>' . "\n" .
		'	<tr>' . "\n" .
		'		<th width="10%">#</th>' . "\n" .
		'		<th nowrap="nowrap" width="100%">' . _gettext('Subject') . '</th>' . "\n" .
		'		<th>' . _gettext('Posts') . '</th>' . "\n" .
		'		<th>' . _gettext('Last Post') . '</th>' . "\n" .
		'	</tr>' . "\n" .
		'</thead>' . "\n" .
		'<tbody>';
		if ($liststooutput >= 0) {
			$startrecord = 40;
		} else {
			$startrecord = 15;
		}
		$query = "SELECT * FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `parentid` = '0' AND `IS_DELETED` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT $liststart,$startrecord";
		$results = $tc_db->GetAll($query);
		if (count($results)>0) {
			$relative_id = $liststart;
			foreach($results AS $line) {
				$results2 = $tc_db->GetAll("SELECT COUNT(*) FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `parentid` = '".$line['id']."' AND `IS_DELETED` = 0");
				$replies = $results2[0][0];
				$output .= '<tr><td><a href="res/'.$line['id'].'.html">'.($relative_id+1).'</a></td><td><a href="';
				if ($relative_id<15&&!$ispage) {
					$output .= '#'.$relative_id;
				} else {
					$output .= 'res/'.$line['id'].'.html';
				}
				$output .= '">'.$line['subject'].'</a></td><td>'.($replies+1).'</td><td nowrap><small>'.date('j F Y H:i', $line['lastbumped']).'</small></td></tr>';
				$relative_id++;
			}
		} else {
			$output .= '<tr><td>N/A</td><td>'._gettext('There are currently no threads to display.').'</td><td>N/A</td><td>N/A</td></td>';
		}
		$output .= '</tbody></table>';
		if ($liststooutput < 0) {
			$output .= '<span class="threadlinks"><a href="#newthread">'._gettext('New Thread').'</a> | <a href="list.html">'._gettext('All Threads').'</a></span>';
		}
		$output .= '</div></div>';
		
		return $output;
	}
	
	function UniquePosts($board = '') {
		global $tc_db;
		
		if ($board!='') {
			return $tc_db->GetOne("SELECT COUNT(DISTINCT `ipmd5`) FROM `" . TC_DBPREFIX . "posts_" . mysql_real_escape_string($board) . "` WHERE `IS_DELETED` = 0");
		} else {
			/*$results = $tc_db->GetAll("SELECT COUNT(DISTINCT `ipmd5`) FROM `".TC_DBPREFIX."posts_` WHERE `IS_DELETED` = 0");
			$numuniqueposts = mysql_fetch_row($result);
			return $numuniqueposts[0];*/ //Devnote: Broke when switched to multiple table spanning posts, might fix later
		}
	}
	
	function FormatPostbox($notice, $board) {
		global $tc_db;
		
		$results = $tc_db->GetAll("SELECT `maximagesize` FROM `".TC_DBPREFIX."boards` WHERE `name` = '" . mysql_real_escape_string($board) . "' LIMIT 1");
		foreach($results AS $line) {
			$filetypes = '';
			$filetypes_allowed = $tc_db->GetAll("SELECT ".TC_DBPREFIX."filetypes.filetype FROM ".TC_DBPREFIX."boards, ".TC_DBPREFIX."filetypes, ".TC_DBPREFIX."board_filetypes WHERE ".TC_DBPREFIX."boards.id = '" . $this->board_id . "' AND ".TC_DBPREFIX."board_filetypes.boardid = ".TC_DBPREFIX."boards.id AND ".TC_DBPREFIX."board_filetypes.typeid = ".TC_DBPREFIX."filetypes.id ORDER BY ".TC_DBPREFIX."filetypes.filetype ASC;");
			if ($filetypes_allowed == '') {
				$filetypes = _gettext('None');
			} else {
				foreach ($filetypes_allowed as $filetype) {
					$filetypes .= strtoupper($filetype['filetype']) . ', ';
				}
				$filetypes = substr($filetypes, 0, (strlen($filetypes)-2));
			}
			
			$catalogmsg = ($this->board_enablecatalog == 1 && $this->board_type != 1 && $this->board_type != 3) ? ' <a href="' . TC_BOARDSFOLDER . $this->board_dir . '/catalog.html">' . _gettext('View catalog') . '</a>' : '';
			
			$patterns = array ('/\<\!tc_maxthumbwidth \/\>/', '/\<\!tc_maxthumbheight \/\>/', '/\<\!tc_uniqueposts \/\>/', '/\<\!tc_maximagekb \/\>/', '/\<\!tc_filetypes \/\>/', '/\<\!tc_catalog \/\>/');
			$replace = array (TC_THUMBWIDTH, TC_THUMBHEIGHT, $this->UniquePosts($board), round($line['maximagesize']/1024), $filetypes, $catalogmsg);
			
			$notice = preg_replace($patterns, $replace, $notice);
		}
		
		return $notice;
	}

	function CachePageHeaderData() {
		if ($this->board_defaultstyle != '') {
			$this->pageheader_css = printStylesheets($this->board_defaultstyle);
		} else {
			$this->pageheader_css = printStylesheets(TC_DEFAULTSTYLE);
		}
		
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$this->pageheader_boardlist = $this->DisplayBoardList(false);
		} else {
			$this->pageheader_boardlist = $this->DisplayBoardList(true);
		}
		
		//$this->pageheader_reply = $this->PageHeader('1');
		$this->pageheader_noreply = $this->PageHeader('0');
	}

	function Postbox($replythread = 0, $oekaki = '', $postboxnotice = '') {
		global $tc_db;
		
		$output = '';
		if (!($this->board_type == 1 && $replythread != 0)) {
			if ($this->board_type ==0 || $this->board_type == 2 || $this->board_type == 3) {
				$output .= '<div class="postarea">' . "\n";
				$label_class = 'postblock';
			} else {
				$output .= '<div class="hborder head newthread">' . "\n" . '<a id="newthread"></a><h2>New Thread</h2>' . "\n";
				$label_class = 'label';
			}
			/* Create anchor to allow links to scroll directly to the post box */
			$output .= '<a id="postbox"></a>' . "\n";
			if ($this->board_type == 2 && $oekaki == '') {
				$output .= '<form action="' . TC_CGIPATH . '/paint.php" method="post">' . "\n" .
				'<input type="hidden" name="board" value="'.$this->board_dir.'">' . "\n" .
				'<input type="hidden" name="replyto" value="'.$replythread.'">' . "\n" .
				'<label for="applet">Paint with:&nbsp;</label>' . "\n" .
				'<select name="applet">' . "\n" .
				'	<option value="shipainter">Shi-Painter</option>' . "\n" .
				'	<option value="shipainterpro">Shi-Painter Pro</option>' . "\n" .
				'	<option value="shipainter_selfy">Shi-Painter+Selfy</option>' . "\n" .
				'	<option value="shipainterpro_selfy">Shi-Painter Pro+Selfy</option>' . "\n" .
				'</select>&nbsp;' . "\n" .
				'<label for="width">Width:&nbsp;<input type="text" name="width" size="3" value="300">&nbsp;' . "\n" .
				'<label for="height">Height:&nbsp;<input type="text" name="height" size="3" value="300">&nbsp;' . "\n" .
				'<label for="useanim">Use animation?&nbsp;<input type="checkbox" name="useanim" checked>&nbsp;' . "\n";
				if ($replythread != 0) {
					$output .= '<label for="replyimage">Source:&nbsp;</label><select name="replyimage"><option value="0">New Image</option>';
					$results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `id` = $replythread AND `filename` != '' AND `filename` != 'removed' AND `filetype` != 'swf' AND `IS_DELETED` = 0");
					foreach($results AS $line) {
						$output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
					}
					$results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `parentid` = $replythread AND `filename` != '' AND `filename` != 'removed' AND `filetype` != 'swf' AND `IS_DELETED` = 0");
					foreach($results AS $line) {
						$output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
					}
					$output .= '</select>&nbsp;';
				}
				$output .= '<input type="submit" value="Paint!"></form><hr>' . "\n";
			}
			if (($this->board_type == 2 && $oekaki != '' && $replythread == 0) ||($this->board_type == 2 && $replythread != 0) || ($this->board_type == 0 || $this->board_type == 1 || $this->board_type == 3)) {
				$output .= '<form name="postform" id="postform" action="' . TC_CGIPATH . '/board.php" method="post" enctype="multipart/form-data"';
				if ($this->board_enablecaptcha == 1) {
					$output .= ' onsubmit="return checkcaptcha(\'postform\');"';
				}
				$output .= '>' . "\n" .
				'<input type="hidden" name="board" value="'.$this->board_dir.'">' . "\n" .
				'<input type="hidden" name="replythread" value="' . $replythread . '">' . "\n";
				if ($this->board_maximagesize > 0) {
					$output .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $this->board_maximagesize . '">' . "\n";
				}
				$output .= '<input type="text" name="email" size="28" maxlength="75" value="" style="display: none;">' . "\n" .
				'<p>' . "\n" .
				'<table class="postform">' . "\n" .
				'<tbody>' . "\n";
				if ($this->board_forcedanon != 1) {
					$output .= '<tr>' . "\n" .
					'	<td class="'.$label_class.'">' . "\n" .
					'		' . _gettext('Name').'</td>' . "\n" .
					'	<td>' . "\n" .
					'		<input type="text" name="name" size="28" maxlength="75" accesskey="n">' . "\n" .
					'	</td>' . "\n" .
					'</tr>' . "\n";
				}
				$output .= '<tr>' . "\n" .
				'	<td class="'.$label_class.'">' . "\n" .
				'		' . _gettext('Email') . "\n" .
				'	</td>' . "\n" .
				'	<td>' . "\n" .
				'		<input type="text" name="em" size="28" maxlength="75" accesskey="e">' . "\n" .
				'	</td>' . "\n" .
				'</tr>'. "\n";
				if ($this->board_enablecaptcha == 1) {
					$output .= '<tr>' . "\n" .
					'	<td class="'.$label_class.'">' . "\n" .
					'		<a href="#" onclick="javascript:document.getElementById(\'captchaimage\').src = \'' . TC_CGIPATH . '/captcha.php?\' + Math.random();return false;"><img id="captchaimage" src="' . TC_CGIPATH . '/captcha.php" border="0" width="90" height="30" alt="Captcha image"></a>' . "\n" .
					'	</td>' . "\n" .
					'	<td>' . "\n" .
					'		<input type="text" name="captcha" size="28" maxlength="6" accesskey="c">' . "\n" .
					'	</td>' . "\n" .
					'</tr>' . "\n";
				}
				$output .= '<tr>' . "\n" .
				'	<td class="'.$label_class.'">' . "\n" .
				'		' . _gettext('Subject').'</td>' . "\n" .
				'	<td>' . "\n" .
				'		<input type="text" name="subject" size="35" maxlength="75" accesskey="s">&nbsp;<input type="submit" value="Submit" accesskey="z">';
				/* Qucik reply indicator for a postbox on a board page */
				if (TC_QUICKREPLY && $replythread == 0 && ($this->board_type == 0 || $this->board_type == 3)) {
					$output .= '&nbsp;<small>(<span id="posttypeindicator">new thread</span>)</small>';
				/* Qucik reply indicator for a postbox on a thread page */
				} elseif (TC_QUICKREPLY && $replythread != 0 && ($this->board_type == 0 || $this->board_type == 3)) {
					$output .= '&nbsp;<small>(<span id="posttypeindicator">reply to ' . $replythread . '</span>)</small>';
				}
				$output .= "\n" . '	</td>' . "\n" .
				'</tr>' . "\n" .
				'<tr>' . "\n" .
				'	<td class="'.$label_class.'">' . "\n" .
				'		' . _gettext('Message') . "\n" .
				'	</td>' . "\n" .
				'	<td>' . "\n" .
				'		<textarea name="message" cols="48" rows="4" accesskey="m"></textarea>' . "\n" .
				'	</td>' . "\n" .
				'</tr>' . "\n";
				if ($this->board_type != 1) {
					if ($this->board_uploadtype == 0 || $this->board_uploadtype == 1) {
						$output .= '<tr>' . "\n" .
						'	<td class="'.$label_class.'">' . "\n" .
						'		' . _gettext('File') . '<a href="#" onclick="togglePassword(); return false;" style="text-decoration: none;" accesskey="x">&nbsp;</a>' . "\n" .
						'	</td>' . "\n" .
						'	<td>' . "\n";
						if ($oekaki=='') {
							$output .= '		<input type="file" name="imagefile" size="35" accesskey="f">';
							if ($replythread == 0 && $this->board_enablenofile == 1) {
								$output .= ' [<input type="checkbox" name="nofile" id="nofile" accesskey="q"><label for="nofile"> '._gettext('No File').'</label>]';
							}
						} else {
							$output .= _gettext('Shown Below').'<input type="hidden" name="oekaki" value="'.$oekaki.'">';
						}
						$output .= "\n" . '	</td>' . "\n" .
						'</tr>' . "\n";
					}
					if ($replythread == 0 && $this->board_type == 3 && TC_TAGS != '') {
						$output .= '<tr>' . "\n" .
						'	<td class="'.$label_class.'">' . "\n" .
						'		Tag' . "\n" .
						'	</td>' . "\n" .
						'	<td>' . "\n" .
						'	<select name="tag">' . "\n" .
						'		<option value="" selected>' . "\n" .
						'			Choose one:' . "\n" .
						'		</option>' . "\n";
						$tags = unserialize(TC_TAGS);
						if ($tags != '') {
							while (list($tag, $tag_abbr) = each($tags)) {
								$output .= '		<option value="' . $tag_abbr . '">' . "\n" .
								'			' . $tag . ' [' . $tag_abbr . ']' . "\n" .
								'		</option>' . "\n";
							}
						}
						$output .= '	</select>' . "\n" .
						'	</td>' . "\n" .
						'</tr>' . "\n";
					}
					if ($oekaki == '' && ($this->board_uploadtype == 1 || $this->board_uploadtype == 2)) {
						$output .= '<tr>' . "\n" .
						'	<td class="'.$label_class.'">' . "\n" .
						'		Embed' . "\n" .
						'	</td>' . "\n" .
						'	<td>' . "\n" .
						'		<input type="text" name="embed" size="28" maxlength="75" accesskey="e">&nbsp;<select name="embedtype"><option value="youtube">YouTube</option></select>' . "\n" .
						'	</td>' . "\n" .
						'</tr>' . "\n";
					}
				}
				$output .= '<tr>' . "\n" .
				'	<td class="'.$label_class.'">' . "\n" .
				'		' . _gettext('Password') . "\n" .
				'	</td>' . "\n" .
				'	<td>' . "\n" .
				'		<input type="password" name="postpassword" size="8" accesskey="p">&nbsp;'._gettext('(for post and file deletion)') . "\n" .
				'	</td>' . "\n" .
				'</tr>' . "\n";
				if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
					$output .= '<tr id="passwordbox"><td></td><td></td></tr>' . "\n" .
					'<tr>' . "\n" .
					'	<td colspan="2">' . "\n" .
					'		<div class="rules">' . "\n" .
					'			' . $postboxnotice . "\n" .
					'		</div>' . "\n" .
					'	</td>' . "\n" .
					'</tr>' . "\n";
				}
				$output .= '</tbody>' . "\n" .
				'</table>' . "\n" .
				'</form>' . "\n";
				if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
					$output .= '<hr>' . "\n";
				}
			}
			$output .= '</div>' . "\n" .
			'<script type="text/javascript">' . "\n" .
			'	set_inputs("postform");' . "\n" .
			'</script>' . "\n";
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
		$is_thread_jsfriendly = ($is_thread) ? 'true' : 'false';
		
		return '<span id="dnb'.$post_id.'">' . "\n" . 
		'	<script type="text/javascript">' . "\n" . 
		'		delandbanlinks(\'dnb'.$post_id.'\', \''.$this->board_dir.'\', '.$post_id.', '.$is_thread_jsfriendly.');' . "\n" . 
		'	</script>' . "\n" . 
		'</span>' . "\n";
	}

	/* Trim the threads to the page limit and delete posts which are older than limited */
	function TrimToPageLimit() {
		global $tc_db;
		
		if ($this->board_maxage != 0) {
			/* If the maximum thread age setting is not zero (do not delete old threads), find posts which are older than the limit, and delete them */
			$results = $tc_db->GetAll("SELECT `id`, `postedat` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 AND `stickied` = 0 AND ((`postedat` + " . ($this->board_maxage*3600) . ") < " . time() . ")");
			foreach($results AS $line) {
				/* If it is older than the limit */
				$post_class = new Post($line['id'], $this->board_dir);
				$post_class->Delete(true);
			}
		}
		if ($this->board_maxpages != 0) {
			/* If the maximum pages setting is not zero (do not limit pages), find posts which are over the limit, and delete them */
			$results = $tc_db->GetAll("SELECT `id`, `stickied` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `parentid` = 0");
			$results_count = count($results);
			if (calculatenumpages($this->board_type, $results_count) >= $this->board_maxpages) {
				$this->board_maxthreads = ($this->board_maxpages * TC_THREADS);
				$numthreadsover = ($results_count - $this->board_maxthreads);
				if ($numthreadsover > 0) {
					$resultspost = $tc_db->GetAll("SELECT `id`, `stickied` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `parentid` = '0' AND `stickied` = '0' ORDER BY `lastbumped` ASC LIMIT " . $numthreadsover);
					foreach($resultspost AS $linepost) {
						$post_class = new Post($linepost['id'], $this->board_dir);
						$post_class->Delete(true);
					}
				}
			}
		}
		/* If the thread was marked for deletion more than two hours ago, delete it */
		$results = $tc_db->GetAll("SELECT `id` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 AND `stickied` = 0 AND `deletedat` > 0 AND (`deletedat` <= " . time() . ")");
		foreach($results AS $line) {
			/* If it is older than the limit */
			$post_class = new Post($line['id'], $this->board_dir);
			$post_class->Delete(true);
		}
	}

	function Footer($noboardlist = false, $executiontime = '', $hide_extra = false) {
		global $tc_db;
		$output = '';
		if (!$hide_extra && !$noboardlist) {
			$output .= '<br>' . $this->DisplayBoardList();
		}
		
		/* I'd really appreciate it if you left the link to trevorchan.org in the footer, if you decide to modify this.  That being said, you are not bound by license or any other terms to keep it there */
		$footer = '- <a href="http://www.trevorchan.org/" target="_top">Trevorchan ' . TC_VERSION . '</a> + <a href="http://www.tj9991.com/" target="_top">tj9991</a> ';
		if ($executiontime != '') {
			$footer .= '+ Took ' . round($executiontime, 2) . 's ';
		}
		$footer .= '-';
		
		if ($hide_extra) {
			$output .= '<div class="legal">' . $footer . '<br>- <a href="' . TC_CGIPATH . '/manage.php">' . _gettext('Manage boards') . '</a> -</div>';
		} else {
			$output .= '<p class="footer">' . $footer . '</p>';
		}
		
		return $output;
	}
	
	function PrintPage($filename, $contents, $board) {
		global $tpl;
		
		$this->smarty->assign('title', $tpl['title']);
		$this->smarty->assign('head', $tpl['head']);
		$this->smarty->assign('head2', $tpl['head2']);
		$this->smarty->assign('page', $contents);
		
		$contents = $this->smarty->fetch('board.tpl');
		
		if ($board !== true) {
			print_page($filename, $contents, $board);
		} else {
			echo $contents;
		}
	}
	
	function InitializeSmarty() {
		global $tpl;
		
		$tpl = array();
	
		require_once TC_ROOTDIR . 'lib/smarty/Smarty.class.php';
		$this->smarty = new Smarty();
		
		$this->smarty->template_dir = TC_TEMPLATEDIR;
		if (TC_CACHEDTEMPLATEDIR != '') {
			$this->smarty->compile_dir = TC_CACHEDTEMPLATEDIR;
			$this->smarty->cache_dir = TC_CACHEDTEMPLATEDIR;
		}
		$this->smarty->config_dir = TC_ROOTDIR . 'smarty/configs';
		
		$this->smarty->assign('tc_name', TC_NAME);
		$this->smarty->assign('tc_webpath', TC_WEBPATH);
		$this->smarty->assign('tc_boardspath', TC_BOARDSPATH);
		$this->smarty->assign('tc_boardsfolder', TC_BOARDSFOLDER);
		$this->smarty->assign('tc_cgipath', TC_CGIPATH);
	}
	
	function ArchiveMode($mode) {
		if ($mode && $this->board_enablearchiving == 1) {
			$this->archive_dir = '/arch';
		} else {
			$this->archive_dir = '';
		}
	}
}

class Post extends Board {
	/* Declare the public variables */
	var $post_id;
	var $post_parentid;
	var $post_filename;
	var $post_filetype;
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
				$this->post_id         = $line['id'];
				$this->post_parentid   = $line['parentid'];
				$this->post_filename   = $line['filename'];
				$this->post_filetype   = $line['filetype'];
				$this->post_password   = $line['password'];
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
			if ($this->post_parentid == 0) {
				$this->post_isthread = true;
			} else {
				$this->post_isthread = false;
			}
			if (!$this->board_dir==$board) {
				$this->Board($board);
			}
		}
	}

	function Delete($allow_archive = false) {
		global $tc_db;
		
		$i = 0;
		if ($this->post_isthread == true) {
			if ($allow_archive && $this->board_enablearchiving == 1 && $this->board_loadbalanceurl == '') {
				$this->ArchiveMode(true);
				$this->RegenerateThread($this->post_id);
				@copy(TC_BOARDSDIR . $this->board_dir . '/src/' . $this->post_filename . '.' . $this->post_filetype, TC_BOARDSDIR . $this->board_dir . $this->archive_dir . '/src/' . $this->post_filename . '.' . $this->post_filetype);
				@copy(TC_BOARDSDIR . $this->board_dir . '/thumb/' . $this->post_filename . 's.' . $this->post_filetype, TC_BOARDSDIR . $this->board_dir . $this->archive_dir . '/thumb/' . $this->post_filename . 's.' . $this->post_filetype);
			}
			$results = $tc_db->GetAll("SELECT `id`, `filename`, `filetype` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = ".mysql_real_escape_string($this->post_id));
			foreach($results AS $line) {
				$i++;
				if ($allow_archive && $this->board_enablearchiving == 1) {
					@copy(TC_BOARDSDIR . $this->board_dir . '/src/' . $line['filename'] . '.' . $line['filetype'], TC_BOARDSDIR . $this->board_dir . $this->archive_dir . '/src/' . $line['filename'] . '.' . $line['filetype']);
					@copy(TC_BOARDSDIR . $this->board_dir . '/thumb/' . $line['filename'] . 's.' . $line['filetype'], TC_BOARDSDIR . $this->board_dir . $this->archive_dir . '/thumb/' . $line['filename'] . 's.' . $line['filetype']);
				}
			}
			if ($allow_archive && $this->board_enablearchiving == 1) {
				$this->ArchiveMode(false);
			}
			@unlink(TC_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'.html');
			@unlink(TC_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'-100.html');
			@unlink(TC_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'+50.html');
			$this->DeleteFile(false, true);
			foreach($results AS $line) {
				$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 , `deletedat` = '" . time() . "' WHERE `id` = ".$line['id']." AND `parentid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
				clearPostCache($line['id'], $this->board_dir);
			}
			$tc_db->Execute("DELETE FROM `".TC_DBPREFIX."watchedthreads` WHERE `threadid` = ".mysql_real_escape_string($this->post_id)." AND `board` = '".$this->board_dir."'");
			$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 , `deletedat` = '" . time() . "' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
			clearPostCache($this->post_id, $this->board_dir);
			
			return $i.' ';
		} else {
			$this->DeleteFile(false);
			$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 , `deletedat` = '" . time() . "' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
			clearPostCache($this->post_id, $this->board_dir);
			
			return true;
		}
	}

	function DeleteFile($update_to_removed = true, $whole_thread = false) {
		global $tc_db;
		
		if ($whole_thread && $this->post_isthread) {
			$results = $tc_db->GetAll("SELECT `id`, `filename`, `filetype` FROM `".TC_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = ".mysql_real_escape_string($this->post_id));
			if (count($results)>0) {
				foreach($results AS $line) {
					if ($line['filename'] != '' && $line['filename'] != 'removed') {
						if ($this->board_loadbalanceurl != '') {
							$this->loadbalancer->Delete($line['filename'], $line['filetype']);
						} else {
							@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$line['filename'].'.'.$line['filetype']);
							@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$line['filename'].'.pch');
							@unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$line['filename'].'s.'.$line['filetype']);
							@unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$line['filename'].'c.'.$line['filetype']);
						}
						if ($update_to_removed) {
							$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `filename` = 'removed', `filemd5` = '' WHERE `id` = ".$line['id']." LIMIT 1");
							clearPostCache($line['id'], $this->board_dir);
						}
					}
				}
			}
			$this->DeleteFile($update_to_removed);
		} else {
			if ($this->post_filename!=''&&$this->post_filename!='removed') {
				if ($this->board_loadbalanceurl != '') {
					$this->loadbalancer->Delete($this->post_filename, $this->post_filetype);
				} else {
					@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$this->post_filename.'.'.$this->post_filetype);
					@unlink(TC_BOARDSDIR.$this->board_dir.'/src/'.$this->post_filename.'.pch');
					@unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$this->post_filename.'s.'.$this->post_filetype);
					@unlink(TC_BOARDSDIR.$this->board_dir.'/thumb/'.$this->post_filename.'c.'.$this->post_filetype);
				}
				if ($update_to_removed) {
					$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".$this->board_dir."` SET `filename` = 'removed', `filemd5` = '' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
					clearPostCache($this->post_id, $this->board_dir);
				}
			}
		}
	}

	function Insert($parentid, $name, $tripcode, $email, $subject, $message, $filename, $filename_original, $filetype, $filemd5, $image_w, $image_h, $filesize, $thumb_w, $thumb_h, $password, $postedat, $lastbumped, $ip, $posterauthority, $tag, $stickied, $locked) {
		global $tc_db;
		require_once TC_ROOTDIR . 'inc/encryption.php';
		
		$query = "INSERT INTO `".TC_DBPREFIX."posts_".$this->board_dir."` ( `parentid` , `name` , `tripcode` , `email` , `subject` , `message` , `filename` , `filename_original`, `filetype` , `filemd5` , `image_w` , `image_h` , `filesize` , `filesize_formatted` , `thumb_w` , `thumb_h` , `password` , `postedat` , `lastbumped` , `ip` , `ipmd5` , `posterauthority` , `tag` , `stickied` , `locked` ) VALUES ( '".mysql_real_escape_string($parentid)."', '".mysql_real_escape_string($name)."', '".mysql_real_escape_string($tripcode)."', '".mysql_real_escape_string($email)."', '".mysql_real_escape_string($subject)."', '".mysql_real_escape_string($message)."', '".mysql_real_escape_string($filename)."', '".mysql_real_escape_string($filename_original)."', '".mysql_real_escape_string($filetype)."', '".mysql_real_escape_string($filemd5)."', '".mysql_real_escape_string($image_w)."', '".mysql_real_escape_string($image_h)."', '".mysql_real_escape_string($filesize)."', '".mysql_real_escape_string(ConvertBytes($filesize))."', '".mysql_real_escape_string($thumb_w)."', '".mysql_real_escape_string($thumb_h)."', '".mysql_real_escape_string($password)."', '".mysql_real_escape_string($postedat)."', '".mysql_real_escape_string($lastbumped)."', '".mysql_real_escape_string(md5_encrypt($ip, TC_RANDOMSEED))."', '".md5($ip)."', '".mysql_real_escape_string($posterauthority)."', '".mysql_real_escape_string($tag)."', '".mysql_real_escape_string($stickied)."', '".mysql_real_escape_string($locked)."' )";
		$tc_db->Execute($query);
		
		return $tc_db->Insert_Id();
	}

	function Report() {
		global $tc_db;
		require_once TC_ROOTDIR . 'inc/encryption.php';
		
		return $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."reports` ( `board` , `postid` , `when` , `ip` ) VALUES ( '".mysql_real_escape_string($this->board_dir)."' , ".mysql_real_escape_string($this->post_id)." , ".time()." , '".md5_encrypt($_SERVER['REMOTE_ADDR'], TC_RANDOMSEED)."' )");
	}
}

?>