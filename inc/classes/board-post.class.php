<?php
/*
 * This file is part of kusaba.
 *
 * kusaba is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * kusaba is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * kusaba; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
/** 
 * Board and Post classes
 *  
 * @package kusaba   
 */
/** 
 * Board class
 * 
 * Contains all board configurations.  This class handles all board page
 * rendering, using the template board.tpl
 * 
 * @package kusaba  
 */
class Board {
	/* Declare the public variables */
	/**
	 * @var integer Board ID
	 */	 	
	var $board_id;
	/**
	 * Board types are: 0 - Normal imageboard, 1 - Text board, 2 - Oekaki
	 * imageboard, 3 - Upload imageboard
	 * 
	 * @var integer Board type
	 */	 
	var $board_type;
	/**
	 * Upload types are: 0 - File upload only, 1 - File upload and video
	 * embedding allowed, 2 - Video embedding only
	 * 	 	 	
	 * @var integer Board upload type
	 */	 
	var $board_uploadtype;
	/**	 	
	 * @var string Board directory
	 */	 
	var $board_dir;
	/**
	 * Displayed at the top of the board's pages
	 * 	 	
	 * @var string Board description
	 */	 
	var $board_desc;
	/**	 	
	 * @var integer Enable reporting
	 */	 
	var $board_enablereporting;
	/**	 	
	 * Displayed at the top of the board, if it is set.  Otherwise, the image
	 * defined in config.php is used
	 * 	 	 	
	 * @var string Board image
	 */	 
	var $board_image;
	/**	 	
	 * HTML which is inserted into the top of the page
	 * 	 	 	
	 * @var string Include header
	 */	 
	var $board_includeheader;
	/**	 	
	 * Name to display when no name is entered
	 * 	 	 	
	 * @var string Anonymous
	 */	 
	var $board_anonymous;
	/**	 	
	 * Sets whether users are allowed to post with names or not
	 * 	 	 	
	 * @var integer Forced anonymous
	 */	 
	var $board_forcedanon;
	/**	 	
	 * Maxmimum file size allowed to be uploaded
	 * 	 	 	
	 * @var integer Maxmimum image size
	 */	 
	var $board_maximagesize;
	/**	 	
	 * Maxmimum thread age before it is pruned
	 * 	 	 	
	 * @var integer Maxmimum thread age
	 */	 
	var $board_maxage;
	/**	 	
	 * Page at which threads are marked for deletion two hours later, regardless
	 * of how much they are bumped	 
	 * 	 	 	
	 * @var integer Mark page
	 */	 
	var $board_markpage;
	/**	 	
	 * Number of replies to a thread allowed to bump the thread, which when
	 * reached, the thread automatically sages with each reply	  
	 * 	 	 	
	 * @var integer Maxmimum replies
	 */	 
	var $board_maxreplies;
	/**	 	
	 * Maxmimum number of pages allowed, where any threads which go past the
	 * last allowed page will be deleted	   
	 * 	 	 	
	 * @var integer Maxmimum pages
	 */	 
	var $board_maxpages;
	/**	 	
	 * Maxmimum number of characters allowed in a message  
	 * 	 	 	
	 * @var integer Message length	 
	 */	 
	var $board_messagelength;
	/**	 	
	 * If locked, no posts may be made unless they use a mod/admin password in the Mod box	  
	 * 	 	 	
	 * @var integer Locked
	 */	 
	var $board_locked;
	/**	 	
	 * The style which a user will be presented with if they do not already have a preferred style set
	 * 	 	 	
	 * @var string Default style
	 */	 
	var $board_defaultstyle;
	/**	 	
	 * The locale to be forced on any pages made by the board
	 * 	 	 	
	 * @var string Locale
	 */	 
	var $board_locale;
	/**	 	
	 * Whether to show the poster's ID (representation of IP) on each post
	 * 	 	 	
	 * @var string Show ID
	 */	 
	var $board_showid;
	/**	 	
	 * Sets if after making a post, the user will be redirected to the thread they just posted in
	 * 	 	 	
	 * @var integer Redirect to thread
	 */	 
	var $board_redirecttothread;
	/**	 	
	 * If captcha is enabled, all posts must pass a test by having the user enter the letters in the captcha image	 
	 * 	 	 	
	 * @var integer Enable captcha
	 */	 
	var $board_enablecaptcha;
	/**	 	
	 * If enabled, users will be able to check a box which reads "No file", and then will not need to choose a file when making a thread	 
	 * 	 	 	
	 * @var integer Enable no file
	 */	 
	var $board_enablenofile;
	/**	 	
	 * When threads are deleted, if archiving is enabled they are moved to the arch/ directory, preserved so others may view them	  
	 * 	 	 	
	 * @var integer Enable archiving
	 */	 
	var $board_enablearchiving;
	/**	 	
	 * URL of the load balance script which is included with kusaba  
	 * 	 	 	
	 * @var string Load balance URL
	 */	 
	var $board_loadbalanceurl;
	/**	 	
	 * Formatted version of the set load balance URL
	 * 	 	 	
	 * @var string Load balance URL formatted
	 */	 
	var $board_loadbalanceurl_formatted;
	/**	 	
	 * Password sent to the load balance script when sending a command
	 * 	 	 	
	 * @var string Load balance password
	 */	 
	var $board_loadbalancepassword;
	/**	 	
	 * Text displayed under the posting area, which usually includes board
	 * information, such as number of unique posts and upload limitations	 
	 * 	 	 	
	 * @var string Postbox notice
	 */	 
	var $board_postboxnotice;
	/**	 	
	 * Cached header formatted for a reply
	 * 	 	 	
	 * @var string Page header (reply)
	 */	 
	var $pageheader_reply;
	/**	 	
	 * Cached header formatted not for a reply
	 * 	 	 	
	 * @var string Page header (no reply)
	 */	 
	var $pageheader_noreply;
	/**	 	
	 * Cached page header css
	 * 	 	 	
	 * @var string Page header (css)
	 */	 
	var $pageheader_css;
	/**	 	
	 * Cached page header board list
	 * 	 	 	
	 * @var string Page header (board list)
	 */	 
	var $pageheader_boardlist;
	/**	 	
	 * Archive directory, set when archiving is enabled
	 * 	 	 	
	 * @var string Archive directory
	 */	 
	var $archive_dir;
	/**	 	
	 * Smarty class
	 * 	 	 	
	 * @var class Smarty
	 */	 
	var $smarty;
	/**	 	
	 * Load balancer class
	 * 	 	 	
	 * @var class Load balancer
	 */	 
	var $loadbalancer;
	
	/**
	 * Initialization function for the Board class, which is called when a new
	 * instance of this class is created. Takes a board directory as an
	 * argument
	 *
	 * @param string $board Board name/directory	 
	 * @return class	 	 
	 */	  
	function Board($board) {
		global $tc_db;

		/* If the instance was created with the board argument present, get all of the board info and configuration values and save it inside of the class */
		if ($board!='') {
			$query = "SELECT * FROM `".KU_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($board)."' LIMIT 1";
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
				$this->board_anonymous                = $line['anonymous'];
				$this->board_forcedanon               = $line['forcedanon'];
				$this->board_maximagesize             = $line['maximagesize'];
				$this->board_maxage                   = $line['maxage'];
				$this->board_markpage                 = $line['markpage'];
				$this->board_maxreplies               = $line['maxreplies'];
				$this->board_maxpages                 = $line['maxpages'];
				$this->board_messagelength            = $line['messagelength'];
				$this->board_locked                   = $line['locked'];
				$this->board_defaultstyle             = $line['defaultstyle'];
				$this->board_locale                   = $line['locale'];
				$this->board_showid                   = $line['showid'];
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
			$this->board_postboxnotice = $this->FormatPostbox(KU_POSTBOX, $this->board_dir);
			
			if ($this->board_loadbalanceurl != '' && $this->board_loadbalancepassword != '') {
				require_once KU_ROOTDIR . 'inc/classes/loadbalancer.class.php';
				$this->loadbalancer = new Load_Balancer;
				
				$this->loadbalancer->url = $this->board_loadbalanceurl;
				$this->loadbalancer->password = $this->board_loadbalancepassword;
			}
		}
	}
	
	/**
	 * Regenerate all board and thread pages	 
	 */
	function RegenerateAll() {
		$this->RegeneratePages();
		$this->RegenerateThreads();
	}
	
	/**
	 * Regenerate all pages	 
	 */	  
	function RegeneratePages() {
		global $tc_db;
		
		if ($this->board_locale != '') {
			changeLocale($this->board_locale);
		}
		
		$this->InitializeSmarty();
		
		$numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
		$boardpage = 0;
		
		$hide_extra = false;
		switch ($this->board_type) {
		case 1:
			$numthreadsdisplayed = KU_THREADSTXT;
			$hide_extra = true;
			break;
			
		case 3:
			$numthreadsdisplayed = 30;
			break;
			
		default:
			$numthreadsdisplayed = KU_THREADS;
		}
		
		$boardstooutput = calculatenumpages($this->board_type, ($numpostsleft-1));
		$this->CachePageHeaderData();
		
		if ($numpostsleft>0) {
			$cached_postbox = $this->Postbox(0, '', $this->board_postboxnotice);
			while ($numpostsleft>0) {
				$executiontime_start_regeneratepages = microtime_float();
				
				$page = $this->pageheader_noreply . $cached_postbox;
				
				if ($this->board_type != 1) {
					$page .= deletionForm($this->board_dir);
				}
				
				$results = $tc_db->GetAll("SELECT `id` , `deletedat` FROM `" . KU_DBPREFIX . "posts_" . $this->board_dir . "` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT " . ($boardpage * $numthreadsdisplayed) . ', ' . $numthreadsdisplayed);
				
				$thread_relative_id = 0;
				$thread_ids = array();
				foreach($results AS $line) {
					/* If the thread is on the page set to mark, and hasn't been marked yet, mark it */
					if ($line['deletedat'] == 0 && $this->board_markpage > 0 && $boardpage >= $this->board_markpage) {
						$tc_db->Execute("UPDATE `".KU_DBPREFIX."posts_".$this->board_dir."` SET `deletedat` = '" . (time() + 7200) . "' WHERE `id` = '" . $line['id'] . "' LIMIT 1");
						$this->RegenerateThread($line['id']);
					}
					$thread_ids[] = $line[0];
				}
				
				if ($this->board_type == 3) {
					$page .= '<center>' . "\n" .
					'<table width="98%">' . "\n" .
					'<tr>' . "\n" .
					'	<td class="postblock" align="center" width="1%">' . "\n" .
					'		No.' . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" style="text-align:center;width:25%;">' . "\n" .
					'		' . _gettext('Name') . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" align="center" width="1%">' . "\n" .
					'		' . _gettext('File') . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" align="center" width="1%">' . "\n" .
					'		' . _gettext('Tag') . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" style="text-align:center;width:40%;">' . "\n" .
					'		' . _gettext('Subject') . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" align="center" width="1%">' . "\n" .
					'		' . _gettext('Size') . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" align="center" width="1%">' . "\n" .
					'		' . _gettext('Date') . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" style="text-align:center;width:1px;">' . "\n" .
					'		Rep.' . "\n" .
					'	</td>' . "\n" .
					'	<td class="postblock" style="width:1px;">' . "\n" .
					'		&nbsp;' . "\n" .
					'	</td>' . "\n" .
					'</tr>' . "\n";
				}
				$page .= $this->BuildThread($thread_ids, ($boardpage + 1), false, $thread_relative_id);
				if ($this->board_type == 3) {
					$page .= '</table></center><br>';
				}
				
				if ($this->board_type != 1) {
					$page .= deletePostBox($this->board_type, $this->board_enablereporting);
					$page .= setDelPassJavascript();
					$page .= pageList($boardpage, $boardstooutput, $this->board_dir);
				}
				
				$page .= $this->Footer(false, (microtime_float()-$executiontime_start_regeneratepages), $hide_extra);
				
				if ($boardpage == 0) {
					$this->PrintPage(KU_BOARDSDIR.$this->board_dir.'/'.KU_FIRSTPAGE, $page, $this->board_dir);
				} else {
					$this->PrintPage(KU_BOARDSDIR.$this->board_dir.'/'.$boardpage.'.html', $page, $this->board_dir);
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
			$this->PrintPage(KU_BOARDSDIR.$this->board_dir.'/'.KU_FIRSTPAGE, $page, $this->board_dir);
		}
		/* If text board, rebuild thread list html files */
		if ($this->board_type==1) {
			$numpostsleft = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
			$liststooutput = floor(($numpostsleft-1) / 40);
			$listpage = 0;
			$currentpostwave = 0;
			while ($numpostsleft>0) {
				$executiontime_start_list = microtime_float();
				$page = $this->PageHeader(0, $currentpostwave, $listpage, $liststooutput);
				$page .= $this->Footer(false, (microtime_float()-$executiontime_start_list), $hide_extra);
				if ($listpage==0) {
					$this->PrintPage(KU_BOARDSDIR.$this->board_dir.'/list.html', $page, $this->board_dir);
				} else {
					$this->PrintPage(KU_BOARDSDIR.$this->board_dir.'/list'.$listpage.'.html', $page, $this->board_dir);
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
			$catalog_page .= '&#91;<a href="' . KU_BOARDSFOLDER . $this->board_dir . '/">'._gettext('Return').'</a>&#93; <div class="catalogmode">'._gettext('Catalog Mode').'</div>' . "\n";
			$catalog_page .= '<table border="1" align="center">' . "\n" . '<tr>' . "\n";
			
			$results = $tc_db->GetAll("SELECT `id` , `subject` , `filename` , `filetype` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC");
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
						$replies = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = " . $line['id']);
						$catalog_page .= '<td valign="middle">' . "\n" . 
						'<a href="' . KU_BOARDSFOLDER . $this->board_dir . '/res/' . $line['id'] . '.html"';
						if ($line['subject'] != '') {
							$catalog_page .= ' title="' . $line['subject'] . '"';
						}
						$catalog_page .= '>';
						if ($line['filename'] != '' && $line['filename'] != 'removed') {
							if ($line['filetype'] == 'jpg' || $line['filetype'] == 'png' || $line['filetype'] == 'gif') {
								$file_path = getCLBoardPath($this->board_dir, $this->board_loadbalanceurl_formatted, $this->archive_dir);
								$catalog_page .= '<img src="' . $file_path . '/thumb/' . $line['filename'] . 'c.' . $line['filetype'] . '" alt="' . $line['id'] . '" border="0">';
							} else {
								$catalog_page .= _gettext('File');
							}
						} elseif ($line['filename'] == 'removed') {
							$catalog_page .= 'Rem.';
						} else {
							$catalog_page .= _gettext('None');
						}
						$catalog_page .= '</a><br>' . "\n" . '<small>' . $replies . '</small>' . "\n" . '</td>' . "\n";
					}
				}
			} else {
				$catalog_page .= '<td>' . "\n" .
				_gettext('No threads.') . "\n" .
				'</td>' . "\n";
			}
			
			$catalog_page .= '</tr>' . "\n" . '</table><br>';
			
			$catalog_page .= $this->Footer(false, (microtime_float()-$executiontime_start_catalog));
			
			$this->PrintPage(KU_BOARDSDIR . $this->board_dir . '/catalog.html', $catalog_page, $this->board_dir);
		}
		/* Delete old pages */
		$dir = KU_BOARDSDIR.$this->board_dir;
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
		
		if ($this->board_locale != '') {
			changeLocale(KU_LOCALE);
		}
	}
	
	/**
	 * Regenerate each thread's corresponding html file, starting with the most recently bumped 	 
	 */
	function RegenerateThreads() {
		global $tc_db;
		
		$res_threadlist = array();
		$results = $tc_db->GetAll("SELECT `id` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `parentid` = 0 AND `IS_DELETED` = 0 ORDER BY `lastbumped` DESC");
		foreach($results AS $line) {
			$res_threadlist[] = $line['id'].'.html';
			if (KU_FIRSTLAST) {
				$res_threadlist[] = $line['id'].'-100.html';
				$res_threadlist[] = $line['id'].'+50.html';
			}
			
			$this->RegenerateThread($line['id']);
		}
		
		/* Remove any html files which shouldn't be there */
		$dir = KU_BOARDSDIR.$this->board_dir.'/res';
		$files = glob ("$dir/{*.html}", GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $htmlfile) {
				if (!in_array(basename($htmlfile), $res_threadlist)) {
					unlink($htmlfile);
				}
			}
		}
	}
	
	/**
	 * Regenerate an individual thread
	 * 
	 * @param integer $thread_op_id Thread ID	 	 
	 */	 	
	function RegenerateThread($thread_op_id) {
		global $tc_db;
		$hide_extra = ($this->board_type == 1) ? true : false;
		
		if ($this->board_locale != '') {
			changeLocale($this->board_locale);
		}
		
		$this->InitializeSmarty();
		$this->CachePageHeaderData();

		$thread_exists = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `id` = ".mysql_real_escape_string($thread_op_id)." AND `IS_DELETED` = 0 AND `parentid` = 0 LIMIT 1");
		if ($thread_exists == 1) {
			$executiontime_start_regeneratethread = microtime_float();
			
			$numreplies = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `parentid` = ".mysql_real_escape_string($thread_op_id)." AND `IS_DELETED` = 0");
			$modifier_last50 = false;
			$modifier_first100 = false;
			if (KU_FIRSTLAST && $numreplies > 49) {
				$modifier_last50 = true;
				if ($numreplies > 99) {
					$modifier_first100 = true;
				}
			}
			
			$thread_page = $this->PageHeader($thread_op_id, '', $this->board_postboxnotice);
			$thread_page .= threadLinks('return', $thread_op_id, $this->board_dir, $this->board_type, $modifier_last50, $modifier_first100);
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
			
			$this->PrintPage(KU_BOARDSDIR . $this->board_dir . $this->archive_dir . '/res/' . $thread_op_id . '.html', $thread_page, $this->board_dir);
			if ($modifier_last50) {
				$this->PrintPage(KU_BOARDSDIR . $this->board_dir . $this->archive_dir . '/res/' . $thread_op_id . '+50.html', $thread_page_last50, $this->board_dir);
				if ($modifier_first100) {
					$this->PrintPage(KU_BOARDSDIR . $this->board_dir . $this->archive_dir . '/res/' . $thread_op_id . '-100.html', $thread_page_first100, $this->board_dir);
				}
			}
		}
		
		if ($this->board_locale != '') {
			changeLocale(KU_LOCALE);
		}
	}
	
	/**
	 * Build a thread
	 * 
	 * @param integer $parentid Thread ID	 
	 * @param boolean $page Is rendering for a board page
	 * @param boolean $resurrect Is an already deleted thread
	 * @param integer $thread_relative_id Thread's relative ID
	 * @param string $modifier Type modifier, such as first 100, or last 50
	 * @return string The built thread	 	 	 	 	 	 
	 */	 
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
			
			$num_threads_onfrontpage = min(KU_THREADSTXT, $tc_db->GetOne('SELECT COUNT(*) FROM `'.KU_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `IS_DELETED` = 0 AND `parentid` = 0'));
			$isdeleted_check = '';
			
			// }}}
		} else {
			$isdeleted_check = 'AND `IS_DELETED` = 0 ';
		}
		$query_idsegment = '';
		foreach ($parentid as $this_parentid) {
			$query_idsegment .= '`id` = ' . mysql_real_escape_string($this_parentid) . ' OR ';
		}
		$query_idsegment = substr($query_idsegment, 0, -4);
		$results = $tc_db->GetAll('SELECT * FROM `'.KU_DBPREFIX.'posts_'.$this->board_dir.'` WHERE ('.$query_idsegment.') ' . $isdeleted_check . 'ORDER BY `stickied` DESC, `lastbumped` DESC');
		if (count($results) == 0) {
			exitWithErrorPage('BuildThread(): error.  No posts in thread to build from.');
		}
		
		// }}}
		// {{{ Javascript which adds the form for post deletion
		
		if (!$page) {
			$buildthread_output .= deletionForm($this->board_dir);
		}
		
		// }}}
		
		foreach($results AS $line) {
			$thread_id = $line['id'];
			
			$numReplies = $tc_db->GetOne('SELECT COUNT(*) FROM `'.KU_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id) . ' ' . $isdeleted_check);
			if (($this->board_type == 0 ||$this->board_type == 2) || ($this->board_type == 3 && !$page)) {
				$numImageReplies = 0;
				
				// {{{ Calculate the number of image replies to the thread being generated
				
				if ($page) {
					$query = 'SELECT COUNT(*) FROM `'.KU_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ' . $isdeleted_check . 'AND `filename` != \'\' AND `filename` != \'removed\' ORDER BY `id` DESC LIMIT 0, ';
					if ($line['stickied'] == 0 && $numReplies > KU_REPLIES) {
						$query .= ($numReplies - KU_REPLIES);
					} elseif ($numReplies > KU_REPLIESSTICKY) {
						$query .= ($numReplies - KU_REPLIESSTICKY);
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
					$buildthread_output .= unHideThreadSpan($line['id'], $this->board_dir);
				}
				
				// }}}
				// {{{ Div which will contain the entire thread, including its replies
				
				$buildthread_output .= '<div id="thread' . $line['id'] . $this->board_dir . '">' . "\n";
				
				// }}}
				// {{{ Javascript which will automatically hide the thread if it finds the ID in the hidden threads cookie
				
				if ($page) {
					$buildthread_output .= autoHideThreadJavascript($line['id'], $this->board_dir);
				}
				// }}}
				// {{{ Thread-starting post
				
				if ($this->board_type == 1 && $page) {
					$buildthread_output .= '<hr>';
				}
				$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line, $numReplies, $thread_relative_id);
				
				// }}}
				// {{{ Thread replies display
				
				if ($numReplies > 0) {
					if (!$page) {
						// {{{ Non-page reply fetch
						
						$query = 'SELECT * FROM `'.KU_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ' . $isdeleted_check . 'ORDER BY `id` ';
						switch ($modifier) {
						case 'last50':
							$query .= 'DESC LIMIT 50';
							$buildthread_output .= '<span class="omittedposts">' . "\n" .
							'	 ' . ($numReplies - 50) . ' ';
							$buildthread_output .= (($numReplies - 50) != 1) ? strtolower(_gettext('Posts')) : strtolower(_gettext('Post'));
							$buildthread_output .= ' ' . _gettext('omitted') . '.  ' . _gettext('Last 50 posts shown.') . "\n" .
							'</span>' . "\n";
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
							$numrepliesdisplayed = KU_REPLIES;
						} else {
							$numrepliesdisplayed = KU_REPLIESSTICKY;
						}
						if ($numReplies > $numrepliesdisplayed) {
							$buildthread_output .= '<span class="omittedposts">' . "\n" .
							'	 ' . ($numReplies-$numrepliesdisplayed) . ' ';
							$buildthread_output .= ($numReplies-$numrepliesdisplayed != 1) ? strtolower(_gettext('Posts')) : strtolower(_gettext('Post'));
							if ($numImageReplies > 0) {
								$buildthread_output .= ' ' . _gettext('and') . ' ' . $numImageReplies . ' ';
								$buildthread_output .= ($numImageReplies != 1) ? strtolower(_gettext('Images')) : strtolower(_gettext('Image'));
							}
							$buildthread_output .= ' ' . _gettext('omitted') . '. '._gettext('Click Reply to view.') . "\n" .
							'</span>' . "\n";
						}
						/* Retrieves the three newest posts from the thread in descending order, which is backwards for what we want, so we apply array_reverse on the result */
						$query = 'SELECT * FROM `'.KU_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ' . $isdeleted_check . 'ORDER BY `id` DESC LIMIT '.$numrepliesdisplayed;
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
					
					if (!$page) {
						if ($modifier == 'first100') {
							$buildthread_output .= '<span class="omittedposts" style="float: left">' . "\n" .
							'	 ' . ($numReplies - 100) . ' ';
							$buildthread_output .= (($numReplies - 100) != 1) ? strtolower(_gettext('Posts')) : strtolower(_gettext('Post'));
							$buildthread_output .= ' ' . _gettext('omitted') . '.  ' . _gettext('First 100 posts shown.') . "\n" .
							'</span>' . "\n";
						}
						
						if ($numReplies > 2) {
							$buildthread_output .= '<span style="float: right;">' . "\n" .
							'	' . threadLinks('return', $thread_id, $this->board_dir, $this->board_type, ($numReplies > 49), ($numReplies > 99), true) .
							'</span>' . "\n";
						}
					}
				}
				
				// }}}
				
				$buildthread_output .= '</div>' . "\n" .
				'<br clear="left">' . "\n" . 
				'<hr>' . "\n";
			} elseif ($this->board_type == 3 && $page) {
				// {{{ Upload imageboard page generation
				
				$buildthread_output .= uploadImageboardPageRow($line, $this->board_dir, $this->board_maxage, $numReplies);
				
				// }}}
			} else {
				// {{{ Text board page generation
				
				$buildthread_output .= $this->BuildPost($page, $this->board_dir, $this->board_type, $line, $numReplies, $thread_relative_id, 1, $num_threads_onfrontpage);
				
				if ($numReplies > 5 && $page) {
					$modifier_last50 = ($numReplies > 49) ? true : false;
					$modifier_first100 = ($numReplies > 99) ? true : false;
						
					$buildthread_output .= threadLinks('page', $thread_id, $this->board_dir, $this->board_type, $modifier_last50, $modifier_first100);
					
					$reply_relative_id = $numReplies-4;
				} else {
					$reply_relative_id = 1;
				}
				$query = 'SELECT * FROM `'.KU_DBPREFIX.'posts_'.$this->board_dir.'` WHERE `parentid` = '.mysql_real_escape_string($thread_id).' ORDER BY `id` ';
				
				if ($page) {
					$query .= 'DESC LIMIT 5';
				} else {
					switch ($modifier) {
					case 'last50':
						$query .= 'DESC LIMIT 50';
						$buildthread_output .= '<span class="abbrev">' . "\n" .
						'	 ' . ($numReplies - 50).' ';
						$buildthread_output .= (($numReplies - 50) != 1) ? strtolower(_gettext('Posts')) : strtolower(_gettext('Post'));
						$buildthread_output .= ' ' . _gettext('omitted') . '.  ' . _gettext('Last 50 posts shown.') . "\n" .
						'</span>' . "\n";
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
					$buildthread_output .= deletePostBox($this->board_type, $this->board_enablereporting);
					$buildthread_output .= setDelPassJavascript();
				}
				
				$buildthread_output .= '<form name="post' . $line['id'] . '" id="post' . $line['id'] . '" action="' . KU_CGIPATH . '/board.php" method="post"';
				if ($this->board_enablecaptcha == 1) {
					$buildthread_output .= ' onsubmit="return checkcaptcha(\'post' . $line['id'] . '\');"';
				}
				$buildthread_output .= '>' . "\n" .
				'<input type="hidden" name="board" value="' . $this->board_dir . '">' . "\n" .
				'<input type="hidden" name="replythread" value="' . $thread_id . '">' . "\n" .
				'<input name="email" size="25" value="" style="display: none;">' . "\n" .
				textBoardReplyBox($this->board_dir, $this->board_forcedanon, $this->board_enablecaptcha, $numReplies, $line['id'], 'post' . $line['id']) .
				'</form>' . "\n" .
				'<script type="text/javascript"><!--' . "\n" .
				'	set_inputs(\'post' . $line['id'] . '\');' . "\n" .
				'//--></script>' . "\n";
				
				if ($page) {
					$buildthread_output .= '</div></div>';
				} else {
					$buildthread_output .= '<hr>';
				}
				
				// }}}
			}
			
			$thread_relative_id++;
		}
		
		if (!$page && $this->board_type != 1) {
			$buildthread_output .= deletePostBox($this->board_type, $this->board_enablereporting);
			$buildthread_output .= setDelPassJavascript();			
		}
		
		return $buildthread_output;
	}
	
	/**
	 * Build an individual post
	 * 
	 * @param boolean $page If the post is being rendered on a board page or not
	 * @param string $post_board Board which this post resides on	 	 
	 * @param array $post Post data 
	 * @param integer $thread_replies Number of replies to the thread this post is in
	 * @param string $thread_relative_id The number this thread is relative to the other threads in the page
	 * @param integer $reply_relative_id The number this reply is relative to the other replies in the thread
	 * @param integer $threads_on_front_page The number of threads on the front page
	 * @return string The built post	 	 	 	 	 	 
	 */	 
	function BuildPost($page, $post_board, $post_board_type, $post, $thread_replies=0, $thread_relative_id='', $reply_relative_id=0, $threads_on_front_page=0) {
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
			$file_path = getCLBoardPath($this->board_dir, $this->board_loadbalanceurl_formatted, $this->archive_dir);
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
						$post_thumb = KU_WEBPATH . '/inc/filetypes/' . $filetype_info[0];
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
					if (KU_NEWWINDOW) {
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
				if (KU_THUMBMSG) {
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
					'	' . _gettext('File<br>Removed') . "\n" .
					'</div>' . "\n";
				} else {
					$info_image .= '<a ';
					if (KU_NEWWINDOW) {
						$info_image .= 'target="_blank" ';
					}
					$info_image .= 'href="' . $file_path . '/src/'.$post['filename'].'.'.$post['filetype'].'">' . "\n" .
					'<span id="thumb' . $post['id'] . '">' . $post_file_thumblement . '</span>' . "\n" .
					'</a>' . "\n";
				}
			}
			$info_post .= '<a name="'.$post['id'].'"></a>' . "\n" . 
			'<label>' . "\n" . 
			'<input type="checkbox" name="delete" value="' . $post['id'] . '">' . "\n";
			if ($post['subject']!='') {
				$info_post .= '<span class="filetitle">' . "\n" .
				'	' . $post['subject'] . "\n" .
				'</span> ' . "\n";
			}
			
			$info_post .= formatNameAndTrip($post['name'], $post['email'], $post['tripcode'], $this->board_anonymous);
			
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
			'	<a href="'.KU_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html#'.$post['id'].'"';
			if (!$page) {
				$info_post .= ' onclick="javascript:highlight(\'' . $post['id'] . '\');"';
			}
			$info_post .= '>' . "\n" .
			'		No.' . "\n" .
			'	</a>' . "\n" .
			'	<a href="'.KU_BOARDSFOLDER.$post_board.'/res/'.$post_thread_start_id.'.html#i'.$post['id'].'"';
			if (!$page) {
				$info_post .= ' onclick="insert(\'>>'.$post['id'].'\');"';
			}
			$info_post .= '>' . "\n" .
			'		' . $post['id'] . "\n" .
			'	</a>' . "\n" .
			'</span>' . "\n";
			if ($this->board_showid) {
				$info_post .= ' ID: ' . substr($post['ipmd5'], 0, 6) . "\n";
			}
			/*if ($page && $post_is_thread && $thread_relative_id !== '' && $post['stickied'] == 0 && $post['locked'] == 0) {
				$info_post .= ' Estimated lifespan: ' . calculateThreadLifespan($post['id'], $page, $thread_relative_id, $this->board_dir, $this->board_maxpages, $this->board_maxage) . "\n";
			}*/
			$info_post .= '<span class="extrabtns">' . "\n";
			if ($post['locked']==1) {
				$info_post .= '	 <img style="border: 0;" src="' . getCLBoardPath() . 'css/locked.gif" alt="'._gettext('Locked').'">' . "\n";
			}
			if ($post['stickied']==1) {
				$info_post .= '	<img style="border: 0;" src="' . getCLBoardPath() . 'css/sticky.gif" alt="'._gettext('Stickied').'">' . "\n";
			}
			if ($page && $post_is_thread) {
				$info_post .= '	 <span id="hide' . $post['id'] . '"><a href="#" onclick="javascript:togglethread(\'' . $post_thread_start_id . $this->board_dir . '\');return false;" title="Hide Thread"><img src="' . getCLBoardPath() . 'css/icons/blank.gif" border="0" class="hidethread" alt="hide"></a></span>' . "\n";
			}
			if (KU_WATCHTHREADS && $post_is_thread) {
				$info_post .= '	 <a href="#" onclick="javascript:addtowatchedthreads(\'' . $post_thread_start_id . '\', \'' . $this->board_dir . '\');return false;" title="Watch Thread"><img src="' . getCLBoardPath() . 'css/icons/blank.gif" border="0" class="watchthread" alt="watch"></a>' . "\n";
			}
			if ($page && $post_is_thread) {
				if (KU_EXPAND && $thread_replies > KU_REPLIES && $thread_replies < 300) {
					$info_post .= '	 <a href="#" onclick="javascript:expandthread(\'' . $post_thread_start_id . '\', \'' . $this->board_dir . '\');return false;" title="Expand Thread"><img src="' . getCLBoardPath() . 'css/icons/blank.gif" border="0" class="expandthread" alt="expand"></a>' . "\n";
				}
				if (KU_QUICKREPLY) {
					$info_post .= '	 <a href="#postbox" onclick="javascript:quickreply(\'' . $post_thread_start_id . '\');" title="' . _gettext('Quick Reply') . '"><img src="' . getCLBoardPath() . 'css/icons/blank.gif" border="0" class="quickreply" alt="quickreply"></a>' . "\n";
				}
			}
			$info_post .= '</span>' . "\n" .
			$this->DeleteAndBanLinks($post['id'], $post_is_thread);
			
			if ($page && $post_is_thread) {
				$modifier_last50 = ($thread_replies > 49) ? true : false;
				$modifier_first100 = ($thread_replies > 99) ? true : false;
				
				$info_post .= ' &nbsp; ' . threadLinks('page', $post_thread_start_id, $this->board_dir, $this->board_type, $modifier_last50, $modifier_first100);
			}
			
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
				if ($info_file != '') {
					$buildpost_output .= '		<br>' . $info_file . "\n";
				}
				/* Another check for an unwanted <br> */
				if ($info_image != '') {
					$buildpost_output .= '		<br>' . $info_image . "\n";
				}
			} else {
				/* And another check for an unwanted <br> */
				if ($info_file != '') {
					$buildpost_output .= $info_file . '		<br>' . "\n";
				}
				$buildpost_output .= $info_image . $info_post;
			}
			$buildpost_output .= '<blockquote>' . "\n";
			if ($post['filetype'] == 'you' || $post['filetype'] == 'goo') {
				$buildpost_output .= embeddedVideoBox($post);
			}
			
			$buildpost_output .= formatLongMessage($post['message'], $this->board_dir, $post_thread_start_id, $page);
			
			$buildpost_output .= '</blockquote>' . "\n";
			/* If the thread is two hours or less from being pruned, add the marked for deletion message */
			if ($this->board_type != 1 && $this->archive_dir == '' && checkMarkedForDeletion($post, $this->board_maxage)) {
				$buildpost_output .= markedForDeletionMessage();
			}
			if (!$post_is_thread) {
				$buildpost_output .= '	</td>' . "\n" .
				'</tr>' . "\n" . 
				'</tbody>' . "\n" .
				'</table>' . "\n";
			}
		} else {
			/* Build a post text board style */
			if ($thread_relative_id == '') {
				$thread_relative_id = 0;
			}
			if ($post['IS_DELETED'] == '1') {
				$post['name'] = '';
				$post['email'] = '';
				$post['tripcode'] = _gettext('Deleted');
				$post['message'] = '<font color="gray">'._gettext('This post has been deleted.').'</font>';
			}
			if ($post_is_thread) {
				if ($page) {
					$buildpost_output .= '<div class="border">' . "\n" .
					'<div class="thread">' . "\n";
				}
			}
			$buildpost_output .= '<a name="'.$thread_relative_id.'"></a>' . "\n";
			if ($post_is_thread) {
				if ($page) {
					$buildpost_output .= '<span class="navlinks">' . "\n" .
					'	<a href="#';
					if (($thread_relative_id-1)==-1) {
						$buildpost_output .= ($threads_on_front_page-1);
					} else {
						$buildpost_output .= ($thread_relative_id-1);
					}
					$buildpost_output .= '">&uarr;</a>&nbsp;' . "\n" .
					'	<a href="#';
					if (($thread_relative_id+1)==$threads_on_front_page) {
						$buildpost_output .= '0';
					} else {
						$buildpost_output .= ($thread_relative_id+1);
					}
					$buildpost_output .= '">&darr;</a>&nbsp;' . "\n" .
					'	<a href="#menu">&#9632;</a>' . "\n" .
					'</span>' . "\n";
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
					$buildpost_output .= '<span class="replies"> ('.$thread_replies.')</span></h2>' . "\n";
					if ($thread_relative_id%2==0) {
						$buildpost_output .= '<div class="post even">' . "\n";
					} else {
						$buildpost_output .= '<div class="post odd">' . "\n";
					}
				} else {
					$buildpost_output .= '<span class="replies">&nbsp;('.$thread_replies.')</span>' . "\n" .
					'</h3>' . "\n";
					$buildpost_output .= '<div class="post even">' . "\n";
				}
			} else {
				if ($thread_relative_id%2==0) {
					$buildpost_output .= '<div class="post even">' . "\n";
				} else {
					$buildpost_output .= '<div class="post odd">' . "\n";
				}
			}
			$buildpost_output .= '<h3>' . "\n" .
			'<span class="postnum">' . "\n" .
			'<a href="javascript:quote('.$reply_relative_id.', \'post'.$post_thread_start_id.'\');"';
			if (!$page) {
				$buildpost_output .= ' name="'.$reply_relative_id.'"';
			}
			$buildpost_output .= '>'.$reply_relative_id.'</a>' .
			'<a href="' . KU_BOARDSPATH . '/' . $this->board_dir . '/res/' . $post_thread_start_id . '.html#' . $reply_relative_id . '">.</a>' .
			'</span>' .
			'<span class="postinfo">' .
			_gettext('Name') . ': ';
			
			$buildpost_output .= formatNameAndTrip($post['name'], $post['email'], $post['tripcode'], $this->board_anonymous);
			
			$buildpost_output .= ' @ ' . date('Y-m-d H:i', $post['postedat']);
			if ($this->board_showid) {
				$buildpost_output .= ' ' . _gettext('ID') . ': ' . substr($post['ipmd5'], 0, 6);
			}
			if (!$page) {
				$buildpost_output .= ' <input type="checkbox" name="delete" value="' . $post['id'] . '">';
			}
			$buildpost_output .= ' ' . $this->DeleteAndBanLinks($post['id'], $post_is_thread) .
			'<span class="id"></span>' . "\n" .
			'</span>' . "\n" .
			'</h3>' . "\n" .
			'<blockquote>' . "\n" .
			formatLongMessage($post['message'], $this->board_dir, $post_thread_start_id, $page) .
			'</blockquote>' . "\n" .
			'</div>' . "\n";
		}
		
		return $buildpost_output;
	}
	
	/**
	 * Build the page header
	 * 
	 * @param integer $replythread The ID of the thread the header is being build for.  0 if it is for a board page
	 * @param integer $liststart The number which the thread list starts on (text boards only)	 
	 * @param integer $liststooutput The number of list pages which will be generated (text boards only)	
	 * @param boolean $isoekaki If this header is being generated for an oekaki posting
	 * @param boolean $hidewatchedthreads If the watched threads box should be forced to not display on the page
	 * @return string The built header	 	 	 	 	 	 
	 */	
	function PageHeader($replythread = '0', $liststart = '0', $listpage = '-1', $liststooutput = '-1', $isoekaki = false, $hidewatchedthreads = false) {
		global $tc_db, $kusabaorg, $tpl;
		
		$tpl['title'] = '';
		if (KU_DIRTITLE) {
			$tpl['title'] .= '/' . $this->board_dir . '/ - ';
		}
		$tpl['title'] .= $this->board_desc;
		$tpl['head'] = '<script type="text/javascript" src="' . getCWebPath() . 'lib/javascript/protoaculous-compressed.js"></script>' . "\n";
		$output = '';
		
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$tpl['head'] .= '<link rel="stylesheet" href="' . getCLBoardPath() . 'css/img_global.css">' . "\n" .
			$this->pageheader_css;
		} else {
			$tpl['head'] .= '<link rel="stylesheet" href="' . getCLBoardPath() . 'css/txt_global.css">' . "\n" . 
			printStylesheetsTXT($this->board_defaultstyle);
		}
		if (KU_RSS) {
			$tpl['head'] .= '<link rel="alternate" type="application/rss+xml" title="RSS" href="' . KU_BOARDSPATH . '/' . $this->board_dir . '/rss.xml">' . "\n";
		}
		$tpl['head'] .= '<script type="text/javascript"><!--' . "\n" .
		'	var ku_boardspath = \'' . KU_BOARDSPATH . '\';' . "\n" .
		'	var ku_cgipath = \'' . KU_CGIPATH . '\'' . "\n" .
		'	var style_cookie';
		if ($this->board_type==1) {
			$tpl['head'] .= '_txt';
		}
		$tpl['head'] .= '="kustyle';
		if ($this->board_type==1) {
			$tpl['head'] .= '_txt';
		}
		$tpl['head'] .= '";' . "\n" .
		'	var ispage = ';
		if ($replythread > 0) {
			$tpl['head'] .= 'false';
		} else {
			$tpl['head'] .= 'true';
		}
		$tpl['head'] .= ';' . "\n" .
		'//--></script>' . "\n";
		if ($this->board_type == 1) {
			if ($replythread == 0) {
				$output .= '<body class="board">' . "\n";
			} else {
				$output .= '<body class="read">' . "\n";
			}
		} else {
			$output .= '<body>' . "\n";
		}
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$output .= '<div class="adminbar">' . "\n";
			if (KU_STYLESWITCHER) {
				$styles = explode(':', KU_STYLES);
				
				foreach ($styles as $stylesheet) {
					$output .= '[<a href="#" onclick="javascript:set_stylesheet(\'' . ucfirst($stylesheet) . '\');return false;">' . ucfirst($stylesheet) . '</a>]&nbsp;';
				}
				
				if (count($styles) > 0) {
					$output .= '-&nbsp;';
				}
			}
			$output .= '[<a href="'.KU_WEBPATH.'" target="_top">' . _gettext('Home') . '</a>]&nbsp;[<a href="' . KU_CGIPATH . '/manage.php" target="_top">' . _gettext('Manage') . '</a>]</div>';
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
		if (isset($kusabaorg) && $this->board_type != 1) {
			$output .=  '<div id="ad" style="position: absolute;top:'.$ad_top.'px;right:'.$ad_right.'px">' . "\n" .
			'<script type="text/javascript"><!--' . "\n" .
			'google_ad_client = "pub-6158454562572132";' . "\n" .
			'google_ad_width = 120;' . "\n" .
			'google_ad_height = 240;' . "\n" .
			'google_ad_format = "120x240_as";' . "\n" .
			'google_ad_type = "text_image";' . "\n" .
			'google_ad_channel = "7008956366";' . "\n" .
			'google_color_border = "FFFFEE";' . "\n" .
			'google_color_bg = "FFFFEE";' . "\n" .
			'google_color_link = "800000";' . "\n" .
			'google_color_text = "CB7E46";' . "\n" .
			'google_color_url = "800000";' . "\n" .
			'--></script>' . "\n" .
			'<script type="text/javascript"' . "\n" .
			'src="http://pagead2.googlesyndication.com/pagead/show_ads.js">' . "\n" .
			'</script>' . "\n" .
			'</div>' . "\n";
		}
		if (KU_WATCHTHREADS && !$isoekaki && ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) && !$hidewatchedthreads) {
			$output .= '<div id="watchedthreads" style="top: ' . $ad_top . 'px; left: 25px;" class="watchedthreads">' . "\n" .
			'<div class="postblock" id="watchedthreadsdraghandle" style="width: 100%;">' . _gettext('Watched Threads') . '</div>' . "\n" .
			'<span id="watchedthreadlist"></span>' . "\n" .
			'<div id="watchedthreadsbuttons">' . "\n" .
			'<a href="#" onclick="javascript:hidewatchedthreads();return false;" title="' . _gettext('Hide the watched threads box') . '">' . "\n" .
			'<img src="' . getCLBoardPath() . 'css/icons/blank.gif" border="0" class="hidewatchedthreads" alt="hide">' . "\n" .
			'</a>&nbsp;' . "\n" .
			'<a href="#" onclick="javascript:getwatchedthreads(\'0\', \'' . $this->board_dir . '\');return false;" title="' . _gettext('Refresh watched threads') . '">' . "\n" .
			'<img src="' . getCLBoardPath() . 'css/icons/blank.gif" border="0" class="refreshwatchedthreads" alt="refresh">' . "\n" .
			'</a>' . "\n" .
			'</div>' . "\n" .
			'<script type="text/javascript"><!--' . "\n" .
			'watchedthreadselement = document.getElementById(\'watchedthreads\');' . "\n" .
			'if (getCookie(\'showwatchedthreads\') == \'1\') {' . "\n" .
			'	watchedthreadselement.style.top = getCookie(\'watchedthreadstop\');' . "\n" .
			'	watchedthreadselement.style.left = getCookie(\'watchedthreadsleft\');' . "\n" .
			'	watchedthreadselement.style.width = Math.max(250,getCookie(\'watchedthreadswidth\')) + \'px\';' . "\n" .
			'	watchedthreadselement.style.height = Math.max(75,getCookie(\'watchedthreadsheight\')) + \'px\';' . "\n" .
			'	getwatchedthreads(\'' . $replythread . '\', \'' . $this->board_dir . '\');' . "\n" .
			'} else {' . "\n" .
			'	watchedthreadselement.innerHTML = \'<a href="#" onclick="javascript:showwatchedthreads();return false"><img src="' . getCLBoardPath() . 'css/icons/blank.gif" border="0" class="restorewatchedthreads" title="' . _gettext('Restore watched threads') . '"><\/a>\';' . "\n" .
			'	watchedthreadselement.style.width = \'16px\';' . "\n" .
			'	watchedthreadselement.style.height = \'16px\';' . "\n" .
			'}' . "\n" .
			'//--></script>' . "\n" .
			'</div>' . "\n";
		}
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$output .= '<div class="logo">';
			if ($this->board_image=='') {
				if (KU_HEADERURL!='') {
					$output .= '<img src="'.KU_HEADERURL.'" alt="' . _gettext('Logo') . '"><br>' . "\n";
				}
			} else if ($this->board_image!=''&&$this->board_image!="none") {
				$output .= '<img src="'.$this->board_image.'" alt="' . _gettext('Logo') . '"><br>' . "\n";
			}
			if (KU_DIRTITLE) {
				$output .= '/'.$this->board_dir.'/ - ';
			}
			$output .= $this->board_desc . '</div>' . "\n" .
			$this->board_includeheader . "\n" .
			'<hr>' . "\n";
		} else if ($this->board_type == 1 && $replythread == 0) {
			/* Text board header */
			
			$output .= '<div class="fullhead">' . "\n" .
			'<div class="hborder">' . "\n" .
			'	<div class="head">' . "\n" .
			'		<a name="menu" rev="contents"></a>' . "\n";
			if ($listpage < 0 || $liststooutput < 0) {
				$output .= '		<span class="navlinks"><a href="#0">&darr;</a>&nbsp;<a href="#menu">&#9632;</a></span>' . "\n";
			}
			$output .= '		<h1 align="center">'.$this->board_desc.'</h1>' . "\n" .
			$this->board_includeheader . "\n" .
			'	</div>' . "\n" .
			'</div>' . "\n";
			if (KU_TXTSTYLESWITCHER && ($listpage < 0 || $liststooutput < 0)) {
				$output .= '<div class="hborder">' . "\n" .
				'	<div class="head midhead">' . "\n" .
				'		<b>' . _gettext('Style') . ':</b> ' . "\n";
				$styles = explode(':', KU_TXTSTYLES);
				foreach ($styles as $style) {
					$output .= '		<a href="#" onclick="javascript:set_stylesheet(\'' . ucfirst($style) . '\',true);return false;">';
					if ($style == 'futatxt') {
						$output .= 'FutaTXT';
					} elseif ($style == 'buritxt') {
						$output .= 'BuriTXT';
					} else {
						$output .= ucfirst($style);
					}
					$output .= '</a>' . "\n";
				}
				$output .= '	</div>' . "\n" .
				'</div>' . "\n";
			}
			
			if ($listpage >= 0 && $liststooutput >= 0) {
				$output .= _gettext('Pages') . ':&nbsp;<a href="'.KU_BOARDSPATH . '/' . $this->board_dir . '/">' . _gettext('Front') . '</a>';
				for ($i = 0; $i <= $liststooutput; $i++) {
					$output .= '&nbsp;<a href="list';
					if ($i!=0) {
						$output .= $i;
					}
					$output .= '.html">'.($i+1).'</a>';
				}
				$output .= '<br>' . "\n";
			}
			$is_page = ($listpage < 0 || $liststooutput < 0) ? false : true;
			$output .= $this->TextBoardThreadList($this->board_dir, $liststart, $liststooutput, $is_page);
		}
		
		return $output;
	}
	
	/**
	 * Build the page header for an oekaki posting
	 * 
	 * @param integer $replyto The ID of the thread being replied to.  0 for a new thread
	 * @param string $postoek The path to the oekaki picture being posted	 	 	 	 	 	 	 
	 */	
	function OekakiHeader($replyto, $postoek) {
		$executiontime_start = microtime_float();
		$this->InitializeSmarty();
		
		$this->CachePageHeaderData();
		
		$page = $this->PageHeader('0', '0', '-1', '-1', true);
		
		$page .= $this->Postbox($replyto, $postoek) .
		'<div style="text-align: center;">' . "\n" .
		'	' . _gettext('Your image') . ':<br>' . "\n" .
		'	<img src="' . KU_CGIFOLDER . 'kusabaoek/' . $postoek . '.png">' . "\n" .
		'</div>';
	
		$executiontime_stop = microtime_float();
		$page .= $this->Footer(false, ($executiontime_stop - $executiontime_start));
		
		$this->PrintPage('', $page, true);
	}
	
	/**
	 * Build the text board thread list
	 * 
	 * @param string $board Board name
	 * @param integer $liststart The number which the list starts at
	 * @param integer $liststooutput The number of list pages which will be generated
	 * @param boolean $ispage If the list is being generated for a board page
	 * @return string The generated thread list	 	 	 	 	 	 	 	 	 
	 */	
	function TextBoardThreadList($board, $liststart, $liststooutput, $ispage = false) {
		global $tc_db;
		
		$output = '<div class="hborder">' . "\n" . 
		'<div class="head threadldiv">' . "\n" . 
		'<a name="menu"></a>' . "\n" . 
		'<table class="threads">' . "\n" .
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
			$startrecord = KU_THREADSTXT;
		}
		$query = "SELECT * FROM `".KU_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `parentid` = '0' AND `IS_DELETED` = 0 ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT $liststart,$startrecord";
		$results = $tc_db->GetAll($query);
		if (count($results)>0) {
			$relative_id = $liststart;
			foreach($results AS $line) {
				$results2 = $tc_db->GetAll("SELECT COUNT(*) FROM `".KU_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `parentid` = '".$line['id']."' AND `IS_DELETED` = 0");
				$replies = $results2[0][0];
				$output .= '<tr><td><a href="res/'.$line['id'].'.html">'.($relative_id+1).'</a></td><td><a href="';
				if ($relative_id<KU_THREADSTXT&&!$ispage) {
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
		if ($liststooutput < 0) {
			$output .= '<tr><td colspan="4" class="threadlinks"><a href="#newthread" style="display: inline;">'._gettext('New Thread').'</a> | <a href="list.html" style="display: inline;">'._gettext('All Threads').'</a></td></tr>' . "\n";
		}
		$output .= '</tbody></table>' . "\n" .
		'</div>' . "\n" .
		'</div>' . "\n" .
		'</div>';
		
		return $output;
	}
	
	/**
	 * Fetch the number of unique posts for a board
	 * 
	 * @param string $board The board to fetch for.  If blank, get the total number of unique posts [todo]
	 * @return integer The number of unique posts	 	 	 	 	 	 	 
	 */	
	function UniquePosts($board = '') {
		global $tc_db;
		
		if ($board!='') {
			return $tc_db->GetOne("SELECT COUNT(DISTINCT `ipmd5`) FROM `" . KU_DBPREFIX . "posts_" . mysql_real_escape_string($board) . "` WHERE `IS_DELETED` = 0");
		} else {
			/*$results = $tc_db->GetAll("SELECT COUNT(DISTINCT `ipmd5`) FROM `".KU_DBPREFIX."posts_` WHERE `IS_DELETED` = 0");
			$numuniqueposts = mysql_fetch_row($result);
			return $numuniqueposts[0];*/ //Devnote: Broke when switched to multiple table spanning posts, might fix later
		}
	}
	
	/**
	 * Format the postbox notice with dynamic values
	 * 
	 * @param string $notice The unformatted postbox notice
	 * @param string $board The board	 
	 * @return string The formatted postbox notice	 	 	 	 	 	 
	 */	
	function FormatPostbox($notice, $board) {
		global $tc_db;
		
		$results = $tc_db->GetAll("SELECT `maximagesize` FROM `".KU_DBPREFIX."boards` WHERE `name` = '" . mysql_real_escape_string($board) . "' LIMIT 1");
		foreach($results AS $line) {
			$filetypes = '';
			$filetypes_allowed = $tc_db->GetAll("SELECT ".KU_DBPREFIX."filetypes.filetype FROM ".KU_DBPREFIX."boards, ".KU_DBPREFIX."filetypes, ".KU_DBPREFIX."board_filetypes WHERE ".KU_DBPREFIX."boards.id = '" . $this->board_id . "' AND ".KU_DBPREFIX."board_filetypes.boardid = ".KU_DBPREFIX."boards.id AND ".KU_DBPREFIX."board_filetypes.typeid = ".KU_DBPREFIX."filetypes.id ORDER BY ".KU_DBPREFIX."filetypes.filetype ASC;");
			if ($filetypes_allowed == '') {
				$filetypes = _gettext('None');
			} else {
				foreach ($filetypes_allowed as $filetype) {
					$filetypes .= strtoupper($filetype['filetype']) . ', ';
				}
				$filetypes = substr($filetypes, 0, (strlen($filetypes)-2));
			}
			
			$catalogmsg = ($this->board_enablecatalog == 1 && $this->board_type != 1 && $this->board_type != 3) ? ' <a href="' . KU_BOARDSFOLDER . $this->board_dir . '/catalog.html">' . _gettext('View catalog') . '</a>' : '';
			
			$patterns = array ('/\<\!tc_maxthumbwidth \/\>/', '/\<\!tc_maxthumbheight \/\>/', '/\<\!tc_uniqueposts \/\>/', '/\<\!tc_maximagekb \/\>/', '/\<\!tc_filetypes \/\>/', '/\<\!tc_catalog \/\>/');
			$replace = array (KU_THUMBWIDTH, KU_THUMBHEIGHT, $this->UniquePosts($board), round($line['maximagesize']/1024), $filetypes, $catalogmsg);
			
			$notice = preg_replace($patterns, $replace, $notice);
		}
		
		return $notice;
	}
	
	/**
	 * Perform caching on the page header and its data	 	 	 	 	 
	 */	
	function CachePageHeaderData() {
		if ($this->board_defaultstyle != '') {
			$this->pageheader_css = printStylesheets($this->board_defaultstyle);
		} else {
			$this->pageheader_css = printStylesheets(KU_DEFAULTSTYLE);
		}
		
		if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
			$this->pageheader_boardlist = $this->DisplayBoardList(false);
		} else {
			$this->pageheader_boardlist = $this->DisplayBoardList(true);
		}
		
		//$this->pageheader_reply = $this->PageHeader('1');
		$this->pageheader_noreply = $this->PageHeader('0');
	}
	
	/**
	 * Generate the postbox area
	 * 
	 * @param integer $replythread The ID of the thread being replied to.  0 if not replying
	 * @param string $isoekaki The oekaki path, set to '' if it is not an oekaki post
	 * @param string $postboxnotice The postbox notice
	 * @return string The generated postbox 	 	 	 	 	 
	 */	
	function Postbox($replythread = 0, $oekaki = '', $postboxnotice = '') {
		global $tc_db;
		
		$output = '';
		if (!($this->board_type == 1 && $replythread != 0)) {
			if ($this->board_type ==0 || $this->board_type == 2 || $this->board_type == 3) {
				$output .= '<div class="postarea">' . "\n";
				$label_class = 'postblock';
			} else {
				$output .= '<div class="hborder head newthread">' . "\n";
				/*if ($this->board_includeheader != '') {
					'<div style="float: right;text-align: left;vertical-align: middle;">' . $postboxnotice . '</div>' . "\n";
				}*/
				$output .= '<a id="newthread"></a><h2>' . _gettext('New Thread') . '</h2>' . "\n";
				$label_class = 'label';
			}
			/* Create anchor to allow links to scroll directly to the post box */
			$output .= '<a id="postbox"></a>' . "\n";
			if ($this->board_type == 2 && $oekaki == '') {
				$output .= '<form action="' . KU_CGIPATH . '/paint.php" method="post">' . "\n" .
				'<input type="hidden" name="board" value="'.$this->board_dir.'">' . "\n" .
				'<input type="hidden" name="replyto" value="'.$replythread.'">' . "\n" .
				'<label for="applet">' . _gettext('Paint with') . ':&nbsp;</label>' . "\n" .
				'<select name="applet" id="applet">' . "\n" .
				'	<option value="shipainter">Shi-Painter</option>' . "\n" .
				'	<option value="shipainterpro">Shi-Painter Pro</option>' . "\n" .
				'	<option value="shipainter_selfy">Shi-Painter+Selfy</option>' . "\n" .
				'	<option value="shipainterpro_selfy">Shi-Painter Pro+Selfy</option>' . "\n" .
				'</select>&nbsp;' . "\n" .
				'<label for="width">' . _gettext('Width') . ':&nbsp;</label><input type="text" name="width" id="width" size="3" value="300">&nbsp;' . "\n" .
				'<label for="height">' . _gettext('Height') . ':&nbsp;</label><input type="text" name="height" id="height" size="3" value="300">&nbsp;' . "\n" .
				'<label for="useanim">' . _gettext('Use animation?') . '&nbsp;</label><input type="checkbox" name="useanim" id="useanim" checked>&nbsp;' . "\n";
				if ($replythread != 0) {
					$output .= '<label for="replyimage">' . _gettext('Source') . ':&nbsp;</label><select name="replyimage" id="replyimage">' . "\n" .
					'<option value="0">' . _gettext('New image') . '</option>' . "\n";
					$results = $tc_db->GetAll("SELECT `id` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `id` = $replythread AND `filename` != '' AND `filename` != 'removed' AND `filetype` != 'swf' AND `IS_DELETED` = 0");
					foreach($results AS $line) {
						$output .= '<option value="'.$line['id'].'">' . _gettext('Modify') . ' No.'.$line['id'].'</option>' . "\n";
					}
					$results = $tc_db->GetAll("SELECT `id` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `parentid` = $replythread AND `filename` != '' AND `filename` != 'removed' AND `filetype` != 'swf' AND `IS_DELETED` = 0");
					foreach($results AS $line) {
						$output .= '<option value="'.$line['id'].'">' . _gettext('Modify') . ' No.'.$line['id'].'</option>' . "\n";
					}
					$output .= '</select>&nbsp;';
				}
				$output .= '<input type="submit" value="' . _gettext('Paint!') . '"></form>' . "\n" .
				'<hr>' . "\n";
			}
			if (($this->board_type == 2 && $oekaki != '' && $replythread == 0) ||($this->board_type == 2 && $replythread != 0) || ($this->board_type == 0 || $this->board_type == 1 || $this->board_type == 3)) {
				$output .= '<form name="postform" id="postform" action="' . KU_CGIPATH . '/board.php" method="post" enctype="multipart/form-data"';
				if ($this->board_enablecaptcha == 1) {
					$output .= ' onsubmit="return checkcaptcha(\'postform\');"';
				}
				$output .= '>' . "\n" .
				'<input type="hidden" name="board" value="'.$this->board_dir.'">' . "\n" .
				'<input type="hidden" name="replythread" value="' . $replythread . '">' . "\n";
				if ($this->board_maximagesize > 0) {
					$output .= '<input type="hidden" name="MAX_FILE_SIZE" value="' . $this->board_maximagesize . '">' . "\n";
				}
				$output .= '<input type="text" name="email" size="28" maxlength="75" value="" style="display: none;">' . "\n";
				if ($this->board_type != 1) {
					$output .= '<p>' . "\n" .
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
						'		<a href="#" onclick="javascript:document.getElementById(\'captchaimage\').src = \'' . KU_CGIPATH . '/captcha.php?\' + Math.random();return false;"><img id="captchaimage" src="' . KU_CGIPATH . '/captcha.php" border="0" width="90" height="30" alt="Captcha image"></a>' . "\n" .
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
					'		<input type="text" name="subject" size="35" maxlength="75" accesskey="s">&nbsp;<input type="submit" value="' . _gettext('Submit') . '" accesskey="z">';
					/* Qucik reply indicator for a postbox on a board page */
					if (KU_QUICKREPLY && $replythread == 0 && ($this->board_type == 0 || $this->board_type == 3)) {
						$output .= '&nbsp;<small>(<span id="posttypeindicator">new thread</span>)</small>';
					/* Qucik reply indicator for a postbox on a thread page */
					} elseif (KU_QUICKREPLY && $replythread != 0 && ($this->board_type == 0 || $this->board_type == 3)) {
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
						if ($replythread == 0 && $this->board_type == 3 && KU_TAGS != '') {
							$output .= '<tr>' . "\n" .
							'	<td class="'.$label_class.'">' . "\n" .
							'		' . _gettext('Tag') . "\n" .
							'	</td>' . "\n" .
							'	<td>' . "\n" .
							'	<select name="tag">' . "\n" .
							'		<option value="" selected>' . "\n" .
							'			' . _gettext('Choose one') . ':' . "\n" .
							'		</option>' . "\n";
							$tags = unserialize(KU_TAGS);
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
							'		' . _gettext('Embed') . "\n" .
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
						'	<td colspan="2" class="rules">' . "\n" .
						'		' . $postboxnotice . "\n";
						if (KU_BLOTTER) {
							$blotter = getBlotter();
							if ($blotter != '') {
								$output .= '<br>' . "\n" .
								'<li style="position: relative;">' . "\n" .
								'	<span style="color: red;">' . "\n" .
								'		' . _gettext('Blotter updated') . ': ' . date('m/d/y', getBlotterLastUpdated()) . "\n" .
								'	</span>' . "\n" .
								'	<span style="color: red;text-align: right;position: absolute;right: 0px;">' . "\n" .
								'		<a href="#" onclick="javascript:toggleblotter(true);return false;">' . _gettext('Show/Hide') . '</a> <a href="' . KU_WEBPATH . '/blotter.php">' . _gettext('Show All') . '</a>' . "\n" .
								'	</span>' . "\n" .
								'</li>' . "\n" .
								$blotter .
								'<script type="text/javascript"><!--' . "\n" .
								'if (getCookie(\'ku_showblotter\') == \'1\') {' . "\n" .
								'	toggleblotter(false);' . "\n" .
								'}' . "\n" .
								'--></script>' . "\n";
							}
						}
						$output .= '	</td>' . "\n" .
						'</tr>' . "\n";
					}
					$output .= '</tbody>' . "\n" .
					'</table>' . "\n";
				} else {
					$output .= textBoardReplyBox($this->board_dir, $this->board_forcedanon, $this->board_enablecaptcha, false, false, 'postform');
				}
				$output .= '</form>' . "\n";
				if ($this->board_type == 0 || $this->board_type == 2 || $this->board_type == 3) {
					$output .= '<hr>' . "\n";
				}
			}
			$output .= '</div>' . "\n" .
			'<script type="text/javascript"><!--' . "\n" .
			'	set_inputs("postform");' . "\n" .
			'//--></script>' . "\n";
		}
		
		return $output;
	}
	
	/**
	 * Display the user-defined list of boards found in boards.html
	 * 
	 * @param boolean $is_textboard If the board this is being displayed for is a text board
	 * @return string The board list
	 */	 	 	 	 	
	function DisplayBoardList($is_textboard = false) {
		$div_name = ($is_textboard) ? 'topbar' : 'navbar';
		
		if (KU_GENERATEBOARDLIST) {
			global $tc_db;
			
			$output = '';
			$results = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "sections` ORDER BY `order` ASC");
			$board_sections = array();
			foreach($results AS $line) {
				$board_sections[] = $line['id'];
			}
			foreach ($board_sections as $board_section) {
				$board_this_section = '';
				$results = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "boards` WHERE `section` = '" .  $board_section . "' ORDER BY `order` ASC");
				if (count($results) > 0) {
					$output .= '[';
					foreach($results AS $line) {
						$board_this_section .= ' <a title="' . $line['desc'] . '" href="' . KU_BOARDSFOLDER . $line['name'] . '/">' . $line['name'] . '</a> /';
					}
					$board_this_section = substr($board_this_section, 0, strlen($board_this_section)-1);
					$output .= $board_this_section;
					$output .= '] ';
				}
			}
			
			return '<div class="'.$div_name.'">' . $output . '</div>';
		} else {
			if (is_file(KU_ROOTDIR . 'boards.html')) {
				return '<div class="'.$div_name.'">' . file_get_contents(KU_ROOTDIR . 'boards.html') . '</div>';
			} else {
				return '';
			}
		}
	}

	/**
	 * Have javascript add the [D & B] links on the page if the kumod cookie is set to yes
	 * 
	 * @param integer $post_id ID of the post the [D & B] links are being created for
	 * @param boolean $is_thread If the post is a thread starting post	 
	 * @return string The [D & B] javascript
	 */	 	 
	function DeleteAndBanLinks($post_id, $is_thread) {
		$is_thread_jsfriendly = ($is_thread) ? 'y' : 'n';
		
		return '<span class="dnb|' . $this->board_dir . '|' . $post_id . '|' . $is_thread_jsfriendly . '"></span>' . "\n";
	}

	/**
	 * Trim the threads to the page limit and delete posts which are older than limited
	 */	 
	function TrimToPageLimit() {
		global $tc_db;
		
		if ($this->board_maxage != 0) {
			/* If the maximum thread age setting is not zero (do not delete old threads), find posts which are older than the limit, and delete them */
			$results = $tc_db->GetAll("SELECT `id`, `postedat` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 AND `stickied` = 0 AND ((`postedat` + " . ($this->board_maxage*3600) . ") < " . time() . ")");
			foreach($results AS $line) {
				/* If it is older than the limit */
				$post_class = new Post($line['id'], $this->board_dir);
				$post_class->Delete(true);
			}
		}
		if ($this->board_maxpages != 0) {
			/* If the maximum pages setting is not zero (do not limit pages), find posts which are over the limit, and delete them */
			$results = $tc_db->GetAll("SELECT `id`, `stickied` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `parentid` = 0");
			$results_count = count($results);
			if (calculatenumpages($this->board_type, $results_count) >= $this->board_maxpages) {
				$this->board_maxthreads = ($this->board_maxpages * KU_THREADS);
				$numthreadsover = ($results_count - $this->board_maxthreads);
				if ($numthreadsover > 0) {
					$resultspost = $tc_db->GetAll("SELECT `id`, `stickied` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND  `parentid` = '0' AND `stickied` = '0' ORDER BY `lastbumped` ASC LIMIT " . $numthreadsover);
					foreach($resultspost AS $linepost) {
						$post_class = new Post($linepost['id'], $this->board_dir);
						$post_class->Delete(true);
					}
				}
			}
		}
		/* If the thread was marked for deletion more than two hours ago, delete it */
		$results = $tc_db->GetAll("SELECT `id` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = 0 AND `stickied` = 0 AND `deletedat` > 0 AND (`deletedat` <= " . time() . ")");
		foreach($results AS $line) {
			/* If it is older than the limit */
			$post_class = new Post($line['id'], $this->board_dir);
			$post_class->Delete(true);
		}
	}

	/**
	 * Display the page footer
	 * 
	 * @param boolean $noboardlist Force the board list to not be displayed
	 * @param string $executiontime The time it took the page to be created
	 * @param boolean $hide_extra Hide extra footer information, and display the manage link
	 * @return string The generated footer
	 */	 	 	 	 	 	 	
	function Footer($noboardlist = false, $executiontime = '', $hide_extra = false) {
		global $tc_db;
		$output = '';
		if (!$hide_extra && !$noboardlist) {
			$output .= '<br>' . $this->DisplayBoardList();
		}
		
		/* I'd really appreciate it if you left the link to kusaba.org in the footer, if you decide to modify this.  That being said, you are not bound by license or any other terms to keep it there */
		$footer = '- <a href="http://www.kusaba.org/" target="_top">kusaba ' . KU_VERSION . '</a> ';
		if ($executiontime != '') {
			$footer .= '+ Took ' . round($executiontime, 2) . 's ';
		}
		$footer .= '-';
		
		if ($hide_extra) {
			$output .= '<div class="legal">' . $footer . '<br>- <a href="' . KU_CGIPATH . '/manage.php" target="_top">' . _gettext('Manage boards') . '</a> -</div>';
		} else {
			$output .= '<br><div class="footer" style="clear: both;">' . $footer . '</div>';
		}
		
		return $output;
	}
	
	/**
	 * Finalize the page and print it to the specified filename
	 * 
	 * @param string $filename File to print the page to
	 * @param string $contents Page contents
	 * @param string $board Board which the file is being generated for
	 * @return string The page contents, if requested
	 */	 
	function PrintPage($filename, $contents, $board) {
		global $tpl;
		
		$this->smarty->assign('title', $tpl['title']);
		$this->smarty->assign('head', $tpl['head']);
		$this->smarty->assign('page', $contents);
		
		$contents = $this->smarty->fetch('board.tpl');
		
		if ($board !== true) {
			print_page($filename, $contents, $board);
		} else {
			echo $contents;
		}
	}
	
	/**
	 * Initialize the instance of smary which will be used for generating pages
	 */ 	 	
	function InitializeSmarty() {
		global $tpl;
		
		$tpl = array();
	
		require_once KU_ROOTDIR . 'lib/smarty/Smarty.class.php';
		$this->smarty = new Smarty();
		
		$this->smarty->template_dir = KU_TEMPLATEDIR;
		if (KU_CACHEDTEMPLATEDIR != '') {
			$this->smarty->compile_dir = KU_CACHEDTEMPLATEDIR;
			$this->smarty->cache_dir = KU_CACHEDTEMPLATEDIR;
		}
		$this->smarty->config_dir = KU_ROOTDIR . 'smarty/configs';
		
		$this->smarty->assign('ku_name', KU_NAME);
		$this->smarty->assign('ku_webpath', KU_WEBPATH);
		$this->smarty->assign('ku_boardspath', KU_BOARDSPATH);
		$this->smarty->assign('ku_boardsfolder', KU_BOARDSFOLDER);
		$this->smarty->assign('ku_cgipath', KU_CGIPATH);
	}
	
	/**
	 * Enable/disable archive mode
	 * 
	 * @param boolean $mode True/false for enabling/disabling archive mode	 	 	 
	 */ 
	function ArchiveMode($mode) {
		if ($mode && $this->board_enablearchiving == 1) {
			$this->archive_dir = '/arch';
		} else {
			$this->archive_dir = '';
		}
	}
}

/** 
 * Post class
 * 
 * Used for post insertion, deletion, and reporting.
 * 
 * @package kusaba  
 */
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
		
		$results = $tc_db->GetAll("SELECT * FROM `".KU_DBPREFIX."posts_".$board."` WHERE `id` = ".mysql_real_escape_string($postid)." LIMIT 1");
		if (count($results)==0&&!$is_inserting) {
			exitWithErrorPage('Invalid post ID.');
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
			$results = $tc_db->GetAll("SELECT `cleared` FROM `".KU_DBPREFIX."reports` WHERE `postid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
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
				@copy(KU_BOARDSDIR . $this->board_dir . '/src/' . $this->post_filename . '.' . $this->post_filetype, KU_BOARDSDIR . $this->board_dir . $this->archive_dir . '/src/' . $this->post_filename . '.' . $this->post_filetype);
				@copy(KU_BOARDSDIR . $this->board_dir . '/thumb/' . $this->post_filename . 's.' . $this->post_filetype, KU_BOARDSDIR . $this->board_dir . $this->archive_dir . '/thumb/' . $this->post_filename . 's.' . $this->post_filetype);
			}
			$results = $tc_db->GetAll("SELECT `id`, `filename`, `filetype` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = ".mysql_real_escape_string($this->post_id));
			foreach($results AS $line) {
				$i++;
				if ($allow_archive && $this->board_enablearchiving == 1) {
					@copy(KU_BOARDSDIR . $this->board_dir . '/src/' . $line['filename'] . '.' . $line['filetype'], KU_BOARDSDIR . $this->board_dir . $this->archive_dir . '/src/' . $line['filename'] . '.' . $line['filetype']);
					@copy(KU_BOARDSDIR . $this->board_dir . '/thumb/' . $line['filename'] . 's.' . $line['filetype'], KU_BOARDSDIR . $this->board_dir . $this->archive_dir . '/thumb/' . $line['filename'] . 's.' . $line['filetype']);
				}
			}
			if ($allow_archive && $this->board_enablearchiving == 1) {
				$this->ArchiveMode(false);
			}
			@unlink(KU_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'.html');
			@unlink(KU_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'-100.html');
			@unlink(KU_BOARDSDIR.$this->board_dir.'/res/'.$this->post_id.'+50.html');
			$this->DeleteFile(false, true);
			foreach($results AS $line) {
				$tc_db->Execute("UPDATE `".KU_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 , `deletedat` = '" . time() . "' WHERE `id` = ".$line['id']." AND `parentid` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
				clearPostCache($line['id'], $this->board_dir);
			}
			$tc_db->Execute("DELETE FROM `".KU_DBPREFIX."watchedthreads` WHERE `threadid` = ".mysql_real_escape_string($this->post_id)." AND `board` = '".$this->board_dir."'");
			$tc_db->Execute("UPDATE `".KU_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 , `deletedat` = '" . time() . "' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
			clearPostCache($this->post_id, $this->board_dir);
			
			return $i.' ';
		} else {
			$this->DeleteFile(false);
			$tc_db->Execute("UPDATE `".KU_DBPREFIX."posts_".$this->board_dir."` SET `IS_DELETED` = 1 , `deletedat` = '" . time() . "' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
			clearPostCache($this->post_id, $this->board_dir);
			
			return true;
		}
	}

	function DeleteFile($update_to_removed = true, $whole_thread = false) {
		global $tc_db;
		
		if ($whole_thread && $this->post_isthread) {
			$results = $tc_db->GetAll("SELECT `id`, `filename`, `filetype` FROM `".KU_DBPREFIX."posts_".$this->board_dir."` WHERE `IS_DELETED` = 0 AND `parentid` = ".mysql_real_escape_string($this->post_id));
			if (count($results)>0) {
				foreach($results AS $line) {
					if ($line['filename'] != '' && $line['filename'] != 'removed') {
						if ($this->board_loadbalanceurl != '') {
							$this->loadbalancer->Delete($line['filename'], $line['filetype']);
						} else {
							@unlink(KU_BOARDSDIR.$this->board_dir.'/src/'.$line['filename'].'.'.$line['filetype']);
							@unlink(KU_BOARDSDIR.$this->board_dir.'/src/'.$line['filename'].'.pch');
							@unlink(KU_BOARDSDIR.$this->board_dir.'/thumb/'.$line['filename'].'s.'.$line['filetype']);
							@unlink(KU_BOARDSDIR.$this->board_dir.'/thumb/'.$line['filename'].'c.'.$line['filetype']);
						}
						if ($update_to_removed) {
							$tc_db->Execute("UPDATE `".KU_DBPREFIX."posts_".$this->board_dir."` SET `filename` = 'removed', `filemd5` = '' WHERE `id` = ".$line['id']." LIMIT 1");
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
					@unlink(KU_BOARDSDIR.$this->board_dir.'/src/'.$this->post_filename.'.'.$this->post_filetype);
					@unlink(KU_BOARDSDIR.$this->board_dir.'/src/'.$this->post_filename.'.pch');
					@unlink(KU_BOARDSDIR.$this->board_dir.'/thumb/'.$this->post_filename.'s.'.$this->post_filetype);
					@unlink(KU_BOARDSDIR.$this->board_dir.'/thumb/'.$this->post_filename.'c.'.$this->post_filetype);
				}
				if ($update_to_removed) {
					$tc_db->Execute("UPDATE `".KU_DBPREFIX."posts_".$this->board_dir."` SET `filename` = 'removed', `filemd5` = '' WHERE `id` = ".mysql_real_escape_string($this->post_id)." LIMIT 1");
					clearPostCache($this->post_id, $this->board_dir);
				}
			}
		}
	}

	function Insert($parentid, $name, $tripcode, $email, $subject, $message, $filename, $filename_original, $filetype, $filemd5, $image_w, $image_h, $filesize, $thumb_w, $thumb_h, $password, $postedat, $lastbumped, $ip, $posterauthority, $tag, $stickied, $locked) {
		global $tc_db;
		
		$query = "INSERT INTO `".KU_DBPREFIX."posts_".$this->board_dir."` ( `parentid` , `name` , `tripcode` , `email` , `subject` , `message` , `filename` , `filename_original`, `filetype` , `filemd5` , `image_w` , `image_h` , `filesize` , `filesize_formatted` , `thumb_w` , `thumb_h` , `password` , `postedat` , `lastbumped` , `ip` , `ipmd5` , `posterauthority` , `tag` , `stickied` , `locked` ) VALUES ( '".mysql_real_escape_string($parentid)."', '".mysql_real_escape_string($name)."', '".mysql_real_escape_string($tripcode)."', '".mysql_real_escape_string($email)."', '".mysql_real_escape_string($subject)."', '".mysql_real_escape_string($message)."', '".mysql_real_escape_string($filename)."', '".mysql_real_escape_string($filename_original)."', '".mysql_real_escape_string($filetype)."', '".mysql_real_escape_string($filemd5)."', '".mysql_real_escape_string($image_w)."', '".mysql_real_escape_string($image_h)."', '".mysql_real_escape_string($filesize)."', '".mysql_real_escape_string(ConvertBytes($filesize))."', '".mysql_real_escape_string($thumb_w)."', '".mysql_real_escape_string($thumb_h)."', '".mysql_real_escape_string($password)."', '".mysql_real_escape_string($postedat)."', '".mysql_real_escape_string($lastbumped)."', '".mysql_real_escape_string(md5_encrypt($ip, KU_RANDOMSEED))."', '".md5($ip)."', '".mysql_real_escape_string($posterauthority)."', '".mysql_real_escape_string($tag)."', '".mysql_real_escape_string($stickied)."', '".mysql_real_escape_string($locked)."' )";
		$tc_db->Execute($query);
		
		return $tc_db->Insert_Id();
	}

	function Report() {
		global $tc_db;
		
		return $tc_db->Execute("INSERT INTO `".KU_DBPREFIX."reports` ( `board` , `postid` , `when` , `ip` ) VALUES ( '" . mysql_real_escape_string($this->board_dir) . "' , " . mysql_real_escape_string($this->post_id) . " , ".time()." , '" . md5_encrypt($_SERVER['REMOTE_ADDR'], KU_RANDOMSEED) . "' )");
	}
}

?>