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
  require(TC_ROOTDIR . 'inc/functions.php');
  require_once(TC_ROOTDIR . 'inc/operations.functions.php');
  require_once(TC_ROOTDIR . 'inc/classes.php');
  
  if (isset($_POST['email'])) {
    if ($_POST['email']!='') {
      die("Spam bot detected");
    }
  }
  
  /* In some cases, the board value is sent through post, others get.  Throw either of them into the post */
  if (isset($_GET['board'])) {
      $_POST['board'] = $_GET['board'];
  }
  
  /* If the script was called using a board name: */
  if (isset($_POST['board'])) {
      $board_name = $tc_db->GetOne("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['board']) . "'");
      if ($board_name!='') {
          $board_class = new Board($board_name);
      } else {
          echo '<meta http-equiv="refresh" content="0;url=' . TC_WEBPATH . '">';
          die();
      }
  } else {
      /* A board is required for this script to function */
      echo '<meta http-equiv="refresh" content="0;url=' . TC_WEBPATH . '">';
      die();
  }
  
  removed_expired_bans();
  
  ban_check($_SERVER['REMOTE_ADDR'], $board_class->board_dir);
  
  $is_oekaki = false;
  if (isset($_POST['oekaki'])) {
    if ($_POST['oekaki'] != '' && is_file('tcdrawings/' . $_POST['oekaki'] . '.png') && $board_class->board_type == '2') {
      $is_oekaki = true;
      $oekaki = 'tcdrawings/' . $_POST['oekaki'] . '.png';
    }
  }
  
  if ((isset($_POST['message']) || isset($_FILES['imagefile'])) || $is_oekaki || ($board_class->board_type == '1' && isset($_POST['message']))) {
      $results = $tc_db->GetAll("SELECT `lastpost` FROM `" . TC_DBPREFIX . "iplist` WHERE `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "'");
      
      if (count($results) > 0) {
          foreach ($results as $line) {
              if (time() - $line['lastpost'] <= TC_POSTDELAY) {
                  die(_('Error: please wait a moment before posting again.'));
              }
          }
      }
      
      $results = $tc_db->GetAll("SELECT `id` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND  `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "' AND `postedat` > '" . (time() - 60) . "'");
      
      if (count($results) > TC_MAXPPM) {
          die(_('Error: please wait a moment before posting again.'));
      }
      
      if (strlen($_POST['message']) > $board_class->board_messagelength) {
          printf(_('Sorry, your message is too long.  Message length: %d, maxmimum allowed length: %d'), strlen($_POST['message']), $board_class->board_messagelength);
          die();
      }
      
      if ($board_class->board_enablecaptcha == 1) {
          if ($_SESSION['security_code'] != strtolower($_POST['captcha']) || empty($_SESSION['security_code'])) {
              die(_('Error: Incorrect captcha entered.'));
          }
      }
      
      $thread_locked = 0;
      $thread_replyto = 0;
      $isreplying = false;
      $imageused = false;
      $onpost_sticky = false;
      $onpost_lock = false;
      
      if (isset($_POST['replythread'])) {
          if ($_POST['replythread'] != '0') {
              $results = $tc_db->GetAll("SELECT `id`,`locked` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `id` = '" . mysql_real_escape_string($_POST['replythread']) . "' AND `threadid` = '0'");
              if (count($results) > 0) {
                  foreach ($results as $line) {
                      $thread_locked = $line['locked'];
                      $thread_replyto = $line['id'];
                  }
                  $results = $tc_db->GetAll("SELECT `id` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `IS_DELETED` = '0' AND `threadid` = '" . mysql_real_escape_string($_POST['replythread']) . "'");
                  $thread_replies = count($results);
                  $isreplying = true;
              } else {
                  die(_('Invalid thread ID.  This may have been caused by the thread recently being deleted'));
              }
          }
      }
      
      $post_name = isset($_POST['name']) ? addslashes(htmlspecialchars($_POST['name'], ENT_QUOTES)) : '';
      $post_email = isset($_POST['em']) ? addslashes(str_replace('"', '', strip_tags($_POST['em']))) : '';
      /* Feb 22nd 07: Bug was found which allowed users to inject attributes by placing them beginning with a " in the mail field. */
      $post_subject = isset($_POST['subject']) ? addslashes(htmlspecialchars($_POST['subject'], ENT_QUOTES)) : '';
      $user_authority = 0;
      
      if (isset($_POST['modpassword'])) {
          require(TC_ROOTDIR . 'inc/encryption.php');
          
          $results = $tc_db->GetAll("SELECT `isadmin`,`boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . md5_decrypt($_POST['modpassword'], TC_RANDOMSEED) . "'");
          
          if (count($results) > 0) {
              foreach ($results as $line) {
                  if ($line['isadmin'] == 1) {
                      $user_authority = 1;
                  } else {
                      if (in_array($board_class->board_dir, explode('|', $line['boards']))) {
                          $user_authority = 2;
                      }
                  }
              }
          }
      }
      
      if ($_POST['postpassword'] != '') {
          $post_password = $_POST['postpassword'];
      } else {
          $post_password = '';
      }
      
      $post_displaystaffstatus = false;
      $badlinks = file(TC_ROOTDIR . 'spam.txt');
      
      foreach ($badlinks as $badlink) {
          if (strpos($_POST['message'], substr($badlink, 0, -1)) != false) {
              //They included a blacklisted link in their post.  Ban them for an hour
              ban_user($_SERVER['REMOTE_ADDR'], 'board.php', 1, 3600, '', _('Posting a blacklisted link.'));
              die();
          }
      }
      
      if ($user_authority == 0) {
          if ($thread_locked == 1) {
              die(_('Sorry, this thread is locked and can not be replied to.'));
          }
          
          require_once(TC_ROOTDIR . 'inc/parse.php');
          
          $post_message = parse_post($_POST['message'], $board_class->board_dir, $board_class->board_type, $thread_replyto);
      } else {
          if (isset($_POST['displaystaffstatus'])) {
              $post_displaystaffstatus = true;
          }
          
          if (isset($_POST['rawhtml'])) {
              $post_message = addslashes($_POST['message']);
          } else {
              require_once(TC_ROOTDIR . 'inc/parse.php');
              
              $post_message = parse_post($_POST['message'], $board_class->board_dir, $board_class->board_type, $thread_replyto);
          }
          
          if (isset($_POST['lockonpost'])) {
              $onpost_lock = true;
          }
          
          if (isset($_POST['stickyonpost'])) {
              $onpost_sticky = true;
          }
      }
      
      if ($isreplying) {
          if (($_FILES['imagefile']['name'] == '' && !$is_oekaki) && $post_message == '') {
              die(_('An image, or message, is required for a reply.'));
          }
      } else {
          if ($_FILES['imagefile']['name'] == "" && !$is_oekaki && (!isset($_POST['nofile'])&&$board_class->board_enablenofile==1) && ($board_class->board_type == 0 || $board_class->board_type == 2)) {
              if ($_POST['embed'] == '' && $board_class->board_uploadtype != 1) {
                  die(_('An image is required for a new thread.') . '<br>Or, if supported, an embed ID.');
              }
          }
      }
      
      if (isset($_POST['nofile'])&&$board_class->board_enablenofile==1) {
          if ($post_message == '') {
            die('A message is required to post without a file.');
          }
      }
      
      if ($board_class->board_type == 1 && !$isreplying && $post_subject == '') {
          die('A subject is required to make a new thread.');
      }
      
      if ($board_class->board_locked == 0 || $user_authority > 0) {
      
          $board_class->TrimToPageLimit();
          $thisimage_name = "";
          $filetype = "";
          $file_md5 = "";
          $file_is_special = false;
          $imgWidth = 0;
          $imgHeight = 0;
          $imgSize = 0;
          $imgWidth_thumb = 0;
          $imgHeight_thumb = 0;
          
          if ((!isset($_POST['nofile'])&&$board_class->board_enablenofile==1)||$board_class->board_enablenofile==0) {
              if (!$is_oekaki) {
                  if ($board_class->board_type == 0 || $board_class->board_type == 2) {
                      if ($_FILES['imagefile']['name'] != '') {
                          if (strpos($_FILES['imagefile']['name'], ',') != false) {
                              die(_('Please select only one image to upload.'));
                          }
                          
                          if (!file_exists($_FILES['imagefile']['tmp_name'])) {
                              echo _('Error, it appears your file did not transfer properly.  Please go back and try again.');
                          }
                          
                          $file = $_FILES['imagefile']['name'];
                          $file_md5 = md5_file($_FILES['imagefile']['tmp_name']);
                          
                          $exists_thread = check_md5($file_md5, $board_class->board_dir);
                          if (is_array($exists_thread)) {
                              die(_('Duplicate file entry detected.') . '<br><br>'.sprintf(_('Already posted %shere%s.'),'<a href="' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/res/' . $exists_thread[0] . '.html#' . $exists_thread[1] . '">','</a>'));
                          }
                          
                          $filetype = substr($file, -4);
                          if ($filetype == "jpeg") {
                              // Fix for the rarely used 4-char format
                              $filetype = ".jpg";
                          }
                          
                          $imageDim = getimagesize($_FILES['imagefile']['tmp_name']);
                          $imgWidth = $imageDim[0];
                          $imgHeight = $imageDim[1];
                          $filetype = strtolower($filetype);
                          
                          if ($_FILES['imagefile']['size'] > $board_class->board_maximagesize) {
                              printf(_('Please make sure your file is smaller than %dB'), $board_class->board_maximagesize);
                              die();
                          } else {
                              $imgSize = $_FILES['imagefile']['size'];
                              if (in_array(strtoupper(substr($filetype, 1)), $board_class->board_filetypes)) {
                                  if ($filetype == ".jpg" || $filetype == ".png" || $filetype == ".gif") {
                                      $thisimage_name = time() . mt_rand(1, 99);
                                      
                                      if (!move_uploaded_file($_FILES['imagefile']['tmp_name'], TC_BOARDSDIR . $board_class->board_dir . "/src/" . $thisimage_name . $filetype)) {
                                          die(_('Could not copy uploaded image.'));
                                      }
                                      
                                      if ($_FILES['imagefile']['size'] == filesize(TC_BOARDSDIR . $board_class->board_dir . "/src/" . $thisimage_name . $filetype)) {
                                          if ($imgWidth > TC_THUMBWIDTH || $imgHeight > TC_THUMBHEIGHT) {
                                              if (!createthumb(TC_BOARDSDIR . $board_class->board_dir . "/src/" . $thisimage_name . $filetype, TC_BOARDSDIR . $board_class->board_dir . "/thumb/" . $thisimage_name . 's' . $filetype, TC_THUMBWIDTH, TC_THUMBHEIGHT)) {
                                                  die(_('Could not create thumbnail.'));
                                              }
                                          } else {
                                              if (!createthumb(TC_BOARDSDIR . $board_class->board_dir . "/src/" . $thisimage_name . $filetype, TC_BOARDSDIR . $board_class->board_dir . "/thumb/" . $thisimage_name . 's' . $filetype, $imgWidth, $imgHeight)) {
                                                  die(_('Could not create thumbnail.'));
                                              }
                                          }
                                          
                                          $imageDim_thumb = getimagesize(TC_BOARDSDIR . $board_class->board_dir . "/thumb/" . $thisimage_name . 's' . $filetype);
                                          $imgWidth_thumb = $imageDim_thumb[0];
                                          $imgHeight_thumb = $imageDim_thumb[1];
                                          $imageused = true;
                                      } else {
                                          die(_('File was not fully uploaded.  Please go back and try again.'));
                                      }
                                  } else {
                                      if (!is_file(TC_BOARDSDIR . '' . $board_class->board_dir . '/src/' . $_FILES['imagefile']['name'])) {
                                          $thisimage_name = substr($_FILES['imagefile']['name'], 0, -4);
                                          $thisimage_name = str_replace(' ', '_', $thisimage_name);
                                          $thisimage_name = str_replace('#', '(number)', $thisimage_name);
                                          $thisimage_name = str_replace('@', '(at)', $thisimage_name);
                                          $thisimage_name = str_replace('/', '(fwslash)', $thisimage_name);
                                          $thisimage_name = str_replace('\\', '(bkslash)', $thisimage_name);
                                          if (!move_uploaded_file($_FILES['imagefile']['tmp_name'], TC_BOARDSDIR . $board_class->board_dir . "/src/" . $thisimage_name . $filetype)) {
                                              die(_('Could not copy uploaded image.'));
                                          }
                                          
                                          if ($_FILES['imagefile']['size'] == filesize(TC_BOARDSDIR . $board_class->board_dir . "/src/" . $thisimage_name . $filetype)) {
                                              $imageused = true;
                                          } else {
                                              die(_('Error, it appears your file did not transfer properly.  Please go back and try again.'));
                                          }
                                          
                                          $file_is_special = true;
                                      } else {
                                          die(_('There is already a file with that name.'));
                                      }
                                  }
                              } else {
                                  die(_('Sorry, that filetype is not allowed on this board.'));
                              }
                          }
                      } elseif (isset($_POST['embed'])) {
                          require_once(TC_ROOTDIR . 'inc/checklink.php');
                          
                          $video_id = $_POST['embed'];
                          $thisimage_name = $video_id;
                          
                          if ($video_id != '' && strpos($video_id, '@') == false && strpos($video_id, '&') == false) {
                              if ($_POST['embedtype'] == 'youtube') {
                                  $videourl_start = 'http://www.youtube.com/watch?v=';
                                  $filetype = '.you';
                              } elseif ($_POST['embedtype'] == 'google') {
                                  $videourl_start = 'http://video.google.com/videoplay?docid=';
                                  $filetype = '.goo';
                              } else {
                                  die(_('Invalid video type.'));
                              }
                              
                              $results = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `image` = '" . mysql_real_escape_string($video_id) . "' AND `IS_DELETED` = 0");
                              if ($results[0] == 0) {
                                  $video_check = check_link($videourl_start . $video_id);
                                  switch ($video_check[1]) {
                                      case 404:
                                          die(_('Unable to connect to: ') . $videourl_start . $video_id);
                                          break;
                                      case 303:
                                          die(_('Invalid video ID.'));
                                          break;
                                      case 200:
                                          //Continue
                                          break;
                                      default:
                                          die(_('Invalid response code: ') . $video_check[1]);
                                          break;
                                  }
                              } else {
                                  $results = $tc_db->SelectLimit("SELECT `id`,`threadid` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `image` = '" . mysql_real_escape_string($video_id) . "' AND `IS_DELETED` = 0", 1);
                                  foreach ($results as $line) {
                                      $real_threadid = ($line[1] == 0) ? $line[0] : $line[1];
                                      echo sprintf(_('That video ID has already been posted %shere%s.'),'<a href="' . TC_BOARDSFOLDER . '/' . $board_class->board_dir . '/res/' . $real_threadid . '.html#' . $line[1] . '">','</a>');
                                  }
                              }
                          } else {
                              die(_('Invalid ID'));
                          }
                      }
                  }
              } else {
                  $thisimage_name = time() . mt_rand(1, 99);
                  $file_md5 = md5_file($oekaki);
                  $filetype = '.png';
                  $imgSize = filesize($oekaki);
                  $imageDim = getimagesize($oekaki);
                  $imgWidth = $imageDim[0];
                  $imgHeight = $imageDim[1];
                  
                  if (!copy($oekaki, TC_BOARDSDIR . $board_class->board_dir . '/src/' . $thisimage_name . $filetype)) {
                      die(_('Could not copy uploaded image.'));
                  }
                  
                  $thumbpath = TC_BOARDSDIR . $board_class->board_dir . '/thumb/' . $thisimage_name . 's' . $filetype;
                  if ($imgWidth > TC_THUMBWIDTH || $imgHeight > TC_THUMBHEIGHT) {
                      if (!createthumb($oekaki, $thumbpath, TC_THUMBWIDTH, TC_THUMBHEIGHT)) {
                          die(_('Could not create thumbnail.'));
                      }
                  } else {
                      if (!createthumb($oekaki, $thumbpath, $imgWidth, $imgHeight)) {
                          die(_('Could not create thumbnail.'));
                      }
                  }
                  
                  $imgDim_thumb = getimagesize($thumbpath);
                  $imgWidth_thumb = $imgDim_thumb[0];
                  $imgHeight_thumb = $imgDim_thumb[1];
                  unlink($oekaki);
              }
          }
          
          if ($board_class->board_forcedanon == '1') {
              if ($user_authority == 0) {
                  $post_name = '';
              }
          }
          
          /* From Futabally */
          $nameandtripcode = calculateNameAndTripcode($post_name);
          if (is_array($nameandtripcode)) {
              $name = $nameandtripcode[0];
              $tripcode = $nameandtripcode[1];
          } else {
              $name = $post_name;
              $tripcode = '';
          }
          
          $filetype_withoutdot = substr($filetype, 1);
          $post_password_md5 = ($post_password == '') ? '' : md5($post_password);
          
          if ($onpost_sticky == true) {
              if ($thread_replyto == 0) {
                  $sticky = 1;
              } else {
                  $result = $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` SET `stickied` = '1' WHERE `id` = '" . $thread_replyto . "'");
                  $sticky = 0;
              }
          } else {
              $sticky = 0;
          }
          
          if ($onpost_lock == true) {
              if ($thread_replyto == 0) {
                  $lock = 1;
              } else {
                  $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` SET `locked` = '1' WHERE `id` = '" . $thread_replyto . "'");
                  $lock = 0;
              }
          } else {
              $lock = 0;
          }
          
          if (!$post_displaystaffstatus && $user_authority > 0) {
              $user_authority_display = 0;
          } elseif ($user_authority > 0) {
              $user_authority_display = $user_authority;
          } else {
              $user_authority_display = 0;
          }
          
          if ((file_exists(TC_BOARDSDIR . $board_class->board_dir . '/src/' . $thisimage_name . $filetype) && file_exists(TC_BOARDSDIR . $board_class->board_dir . '/thumb/' . $thisimage_name . 's' . $filetype)) || ($file_is_special && file_exists(TC_BOARDSDIR . $board_class->board_dir . '/src/' . $thisimage_name . $filetype)) || $imageused == false) {
              $post_class = new Post(0, $board_class->board_dir, true);
              $post_id = $post_class->Insert($thread_replyto, substr($name, 0, 100), $tripcode, substr($post_email, 0, 100), substr($post_subject, 0, 100), $post_message, mysql_real_escape_string($thisimage_name), $filetype_withoutdot, $file_md5, $imgWidth, $imgHeight, $imgSize, $imgWidth_thumb, $imgHeight_thumb, $post_password_md5, time(), time(), $_SERVER['REMOTE_ADDR'], $user_authority_display, $sticky, $lock);
              
              if ($thread_replyto != '0') {
                  flushthreadrepliescache($thread_replyto, $board_class->board_dir);
              }
              
              if (isset($_POST['name'])) {
                  setcookie('name', urldecode($_POST['name']), time() + 31556926, '/');
              }
              
              /* First array is the converted form of the japanese characters meaning sage, second meaning age */
              if (strtolower($_POST['em']) != 'sage' && unistr_to_ords($_POST['em']) != array(19979, 12370) && strtolower($_POST['em']) != 'age' && unistr_to_ords($_POST['em']) != array(19978, 12370)) {
                  setcookie('email', urldecode($_POST['em']), time() + 31556926, '/');
              }

              setcookie('postpassword', urldecode($_POST['postpassword']), time() + 31556926, '/');
          } else {
              die(_('Could not copy uploaded image.'));
          }
          
          if ($thread_replyto != '0' && strtolower($_POST['em']) != 'sage' && unistr_to_ords($_POST['em']) != array(19979, 12370)) {
              if ($thread_replies <= $board_class->board_maxreplies) {
                  $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` SET `lastbumped` = '" . time() . "' WHERE `id` = '" . $thread_replyto . "'");
              }
          }
          
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "iplist` WHERE `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "'");
          if (count($results) == 0) {
              $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "iplist` ( `ip` , `ipmd5` , `lastpost` ) VALUES ( '" . md5_encrypt($_SERVER['REMOTE_ADDR'], TC_RANDOMSEED) . "' , '" . md5($_SERVER['REMOTE_ADDR']) . "' , '" . time() . "' )");
          } else {
              $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "iplist` SET `lastpost` = '" . time() . "' WHERE `ipmd5` = '" . md5($_SERVER['REMOTE_ADDR']) . "'");
          }
          
          /* Initialize the board class */
          $board_class = new Board($board_class->board_dir);
          if ($thread_replyto == "0") {
              /* If they started a new thread, regenerate it (Technically it isn't regeneration, as this is the first time it is being built) */
              $board_class->RegenerateThread($post_id);
          } else {
              /* If they made a reply to an existing thread, regenerate it */
              $board_class->RegenerateThread($thread_replyto);
          }
          /* Regenerate board pages */
          $board_class->RegeneratePages();
          
      } else {
          die(_('Sorry, this board is locked and can not be posted in.'));
      }
  } elseif (isset($_POST['delete']) && isset($_POST['postpassword'])) {
      /* Initialize the post class */
      $post_class = new Post(mysql_real_escape_string($_POST['delete']), $board_class->board_dir);
      
      if (isset($_POST['reportpost'])) {
          /* They clicked the Report button */
          if ($board_class->board_enablereporting == 1) {
              $post_reported = $post_class->post_isreported;
              
              if ($post_reported === 'cleared') {
                  echo _('That post has been cleared as not requiring any deletion.');
              } elseif ($post_reported) {
                  echo _('That post is already in the report list.');
              } else {
                  if ($post_class->Report()) {
                      echo _('Post successfully reported.');
                      echo '<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/board.html">';
                  } else {
                      echo _('Unable to report post.  Please go back and try again.');
                  }
              }
          } else {
              echo _('This board does not allow post reporting.');
          }
      } else {
          /* They clicked the Delete button */
          if ($_POST['postpassword'] != "") {
              if (md5($_POST['postpassword']) == $post_class->post_password) {
                  if (isset($_POST['fileonly'])) {
                      if ($post_class->post_image != '' && $post_class->post_image != 'removed') {
                          $post_class->DeleteImage();
                          $board_class->RegeneratePages();
                          if ($post_class->post_threadid != '0') {
                              $board_class->RegenerateThread($post_class->post_threadid);
                          }
                          echo _('Image successfully deleted from your post.');
                          echo '<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/board.html">';
                      } else {
                          echo _('Your post already doesn\'t have an image!');
                      }
                  } else {
                      if ($post_class->Delete()) {
                          if ($post_class->post_threadid != '0') {
                              flushthreadrepliescache($post_class->post_threadid, $board_class->board_dir);
                              $board_class->RegenerateThread($post_class->post_threadid);
                          }
                          $board_class->RegeneratePages();
                          
                          echo _('Post successfully deleted.');
                          echo '<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/board.html">';
                      } else {
                          echo _('There was an error in trying to delete your post');
                      }
                  }
              } else {
                  echo _('Incorrect password.');
              }
          } else {
              echo '<meta http-equiv="refresh" content="0;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/board.html">';
          }
      }
      die();
  } elseif (isset($_GET['postoek'])) {
      ob_start();
      $executiontime_start = microtime_float();
      echo $board_class->PageHeader();
      echo $board_class->Postbox($_GET['replyto'], $_GET['postoek']);
      echo '<div style="text-align: center;">'._('Your Image:').'<br><img src="' . TC_BOARDSFOLDER . 'tcdrawings/' . $_GET['postoek'] . '.png"></div>';
      $executiontime_stop = microtime_float();
      echo $board_class->Footer(false, ($executiontime_stop - $executiontime_start));
      $page = ob_get_clean();
      echo $page;
      die();
  } else {
      echo '<meta http-equiv="refresh" content="0;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/board.html">';
      die();
  }
  
    if (!TC_INSTANTREDIRECT) {
        if ($thread_replyto == '0') {
            print(_('Thread successfully posted.  You are now being redirected.'));
        } else {
            print(_('Reply successfully posted.  You are now being redirected.'));
        }
    }

    if ($board_class->board_redirecttothread == 1) {
        if ($thread_replyto == "0") {
          do_redirect(TC_BOARDSPATH . '/' . $board_class->board_dir . '/res/' . $post_id . '.html');
        } else {
          do_redirect(TC_BOARDSPATH . '/' . $board_class->board_dir . '/res/' . $thread_replyto . '.html');
        }
    } else {
        do_redirect(TC_BOARDSPATH . '/' . $board_class->board_dir . '/');
    }
    
    /* Depending on the configuration, use either a meta refresh or a direct header */
    function do_redirect($url) {
        global $board_class;
        
        if (!TC_INSTANTREDIRECT) {
            echo '<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/">';
        } else {
            header('Location: ' . $url);
            die();
        }
    }
?>