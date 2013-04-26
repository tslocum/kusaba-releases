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
   * Manage panel for administrative/moderator operations
   * +------------------------------------------------------------------------------+
   * The manage panel is used for changing configurations, adding/modifying/deleting
   * boards, locking/stickying/deleting posts, banning users, and more.  The manage
   * panel is able to be logged in to by both administrators and moderators, however
   * moderators will be restricted to only the boards which they moderate, and cannot
   * perform any actions on the "Administration:" link-line.
   * +------------------------------------------------------------------------------+
   */
  // 100 days
  session_set_cookie_params(60 * 60 * 24 * 100);
  session_start();
  
  require('config.php');
  require_once(TC_ROOTDIR . 'inc/functions.php');
  require_once(TC_ROOTDIR . 'inc/operations.functions.php');
  require_once(TC_ROOTDIR . 'inc/classes.php');
  require_once(TC_ROOTDIR . 'inc/encryption.php');
  
  /* Do these tasks each time manage.php is loaded */
  // Doesn't matter if this is run by a non-mod
  removed_expired_bans();
  // Doesn't matter if this is run by a non-mod
  delete_accepted_reports();
  // Does nothing if the user isn't logged in
  set_mod_cookies();
  
  /* Decide what needs to be done */
  $action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'posting_rates';
  switch ($action) {
      case 'logout':
          logout();
          break;
      case 'showlogin':
          show_login();
          break;
      case 'login':
          checklogin();
          // Halts execution if not validated
          session_validate();
          manage_page();
          break;
      default:
          // Halts execution if not validated
          session_validate();
          manage_page($action);
          break;
  }
  
  /* Show a particular manage function */
  function manage_page($action = 'posting_rates')
  {
      manage_header();
      $actions = array('news', 'addboard', 'clearcache', 'delboard', 'wordfilter', 'misc', 'editsections', 'editfiletypes', 'rebuildall', 'viewdeletedthread', 'cleanup', 'search', 'staff', 'modlog', 'sql', 'boardopts', 'stickypost', 'unstickypost', 'lockpost', 'unlockpost', 'delposts', 'reports', 'bans', 'deletepostsbyip', 'changepwd', 'posting_rates');
      if (in_array($action, $actions)) {
          $action();
      } else {
          echo sprintf(_('%s not implemented.'),$action);
      }
      manage_footer();
  }
  
  /* Log current user out */
  function logout()
  {
      global $tc_db, $lang;

      $resultsboard = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards`");
      foreach ($resultsboard as $lineboard) {
          setcookie("tcmod", "", 0, TC_BOARDSFOLDER . $lineboard['name'] . "/");
      }

      session_destroy();
      unset($_SESSION['manageusername']);
      unset($_SESSION['managepassword']);
      echo _('You have been successfully logged out.') . '<meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/manage.php">';
  }
  
  /* Check login names and create session if user/pass is correct */
  function checklogin()
  {
      global $tc_db, $lang, $action;
      
      $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "loginattempts` WHERE `timestamp` < '" . (time() - 1200) . "'");
      $results = $tc_db->GetAll("SELECT `ip` FROM `" . TC_DBPREFIX . "loginattempts` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' LIMIT 6");
      if (count($results) > 5) {
          die(_('Sorry, because of your numerous failed logins, you have been locked out from logging in for 20 minutes.  Please wait and then try again.'));
      } else {
          $results = $tc_db->GetAll("SELECT `username` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_POST['username']) . "' AND `password` = '" . md5($_POST['password']) . "' LIMIT 1");
          if (count($results) > 0) {
              $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "loginattempts` WHERE `ip` < '" . $_SERVER['REMOTE_ADDR'] . "'");
              $_SESSION['manageusername'] = $_POST['username'];
              $_SESSION['managepassword'] = md5($_POST['password']);
              set_mod_cookies();
              $action = 'posting_rates';
              management_addlogentry(_('Logged in'), 1);
          } else {
              $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "loginattempts` ( `username` , `ip` , `timestamp` ) VALUES ( '" . mysql_real_escape_string($_POST['username']) . "' , '" . $_SERVER['REMOTE_ADDR'] . "' , '" . time() . "' )");
              die(_('Incorrect username/password.'));
          }
      }
  }
  
  /* Set mod cookies for boards */
  function set_mod_cookies()
  {
      global $tc_db;
      
      if (isset($_SESSION['manageusername'])) {
          $results = $tc_db->GetAll("SELECT `boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "' LIMIT 1");
          if (count($results) > 0) {
              if (management_isadmin()) {
                  $resultsboard = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards`");
                  foreach ($resultsboard as $lineboard) {
                      setcookie("tcmod", "yes", time() + 3600, TC_BOARDSFOLDER . $lineboard['name'] . "/");
                  }
              } else {
                  foreach ($results as $line) {
                      $array_boards = explode('|', $line['boards']);
                  }
                  foreach ($array_boards as $this_board_name) {
                      setcookie("tcmod", "yes", time() + 3600, TC_BOARDSFOLDER . $this_board_name . "/");
                  }
              }
          }
      }
  }
  
  /* Show the login form and halt execution */
  function show_login()
  {
      global $tc_db;
      require(TC_ROOTDIR . 'inc/manage_login.php');
      die();
  }
  
  /* Validate the current session */
  function session_validate()
  {
      global $tc_db;
      
      if (isset($_SESSION['manageusername']) && isset($_SESSION['managepassword'])) {
          $results = $tc_db->GetAll("SELECT `username` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "' AND `password` = '" . mysql_real_escape_string($_SESSION['managepassword']) . "' LIMIT 1");
          if (count($results) == 0) {
              session_destroy();
              die(_('Invalid session.') . "<br><br><a href=\"manage.php\">" . _('Log in again.') . "</a>");
          }
      } else {
          show_login();
      }
  }
  
  /* Deletes reports that have the reported post has been deleted */
  function delete_accepted_reports()
  {
      /*    global $tc_db;
       $results = $tc_db->GetAll("SELECT ".TC_DBPREFIX."reports.id FROM ".TC_DBPREFIX."reports JOIN ".TC_DBPREFIX."posts ON ".TC_DBPREFIX."reports.postid = ".TC_DBPREFIX."posts.id AND ".TC_DBPREFIX."reports.boardid = ".TC_DBPREFIX."posts.boardid WHERE ".TC_DBPREFIX."posts.IS_DELETED = 1");
       if (count($results)>0) {
       foreach($results AS $line) {
       $tc_db->Execute("DELETE FROM ".TC_DBPREFIX."reports WHERE id = ".$line['id']."");
       }
       }*/
  }
  //Devnote:  Broken, needs to be fixed
  
  /* Show the header of the manage page */
  function manage_header()
  {
      global $tc_db, $lang, $action;
      $manage_postpassword = md5_encrypt($_SESSION['manageusername'], TC_RANDOMSEED);
      echo '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
    <html>
    <head>
  <title>' . _('Manage boards') . '</title>
    <!--<link rel="stylesheet" type="text/css" href="' . TC_BOARDSPATH . '/css/manage.css" title="Manage">-->
    <link rel="stylesheet" type="text/css" href="' . TC_BOARDSPATH . '/css/manage_basic.css" media="screen">
    <link rel="stylesheet" type="text/css" href="' . TC_BOARDSPATH . '/css/manage_tabs.css" media="screen">
    </head>
    <table border="0" cellspacing="0" cellpadding="0" width="100%"><tr><td align="left" valign="top"><h1>' . TC_NAME . ' - '._('Manage boards').'</h1></td><td align="right" valign="top">';
      echo _('Welcome') . ', ' . $_SESSION['manageusername'] . ' [<a href="?action=logout">' . _('log out') . '</a>]<br>';
      if ($_SESSION['manageusername'] == "admin" && $_SESSION['managepassword'] == md5("admin")) {
          echo '<font color="red"><b>' . _('NOTICE: You are using the default administrator account.  Anyone can log in to this account, so a second administrator account needs to be created.  Create another, log in to it, and delete this one.') . '</b></font><br>';
      }
      echo _('Staff rights') . ': <b>';
      if (management_isadmin()) {
          echo _('Administrator');
      } else {
          echo _('Moderator');
      }
      echo '</b><br><span id="postingpassword"><button id="showpwd" onclick="javascript:document.getElementById(\'postingpassword\').innerHTML = \'<b>' . $manage_postpassword . '</b>\';">'._('Show Posting Password').'</button></span>';
      if (!management_isadmin()) {
          echo '<br>' . _('Moderating boards') . ': ';
          $i = 0;
          $resultsboard = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
          foreach ($resultsboard as $lineboard) {
              $i++;
              if (moderator_ismodofboard($lineboard['name'], $_SESSION['manageusername'])) {
                  echo '<b>/' . $lineboard['name'] . '/</b>, ';
              }
          }
          if ($i == 0) {
              echo _('No boards');
          } else {
              echo $i . ' ' . _('Boards');
          }
      }
      echo '</td></tr></table>';
      if (is_file(TC_ROOTDIR . "inc/modheader.html")) {
          include(TC_ROOTDIR . "inc/modheader.html");
      }
      require_once(TC_ROOTDIR . "inc/encryption.php");
      /* Begin Tabs */
      echo '<div id="header">
  <ul id="primary">';
      /* Home */
      echo '<li>';
      if ($action == 'posting_rates' && pagetaken_check('home')) {
          echo '<span>'._('Home').'</span>
        <ul id="secondary">
        <li><a href="?">'._('Posting rates (past hour)').'</a></li>
        </ul>';
      } else {
          echo '<a href="?">'._('Home').'</a>';
      }
      echo '</li>';
      /* Administration */
      if (management_isadmin()) {
          echo '<li>';
          if (($action == 'news' || $action == 'addboard' || $action == 'delboard' || $action == 'wordfilter' || $action == 'misc' || $action == 'rebuildall' || $action == 'viewdeletedthread' || $action == 'cleanup' || $action == 'search' || $action == 'staff' || $action == 'modlog' || $action == 'editfiletypes' || $action == 'editsections' || $action == 'sql' || $action == 'clearcache') || pagetaken_check('administration')) {
              echo '<span>' . _('Administration') . '</span>
            <ul id="secondary">
            <li><a href="?action=news">' . _('News') . '</a></li>
            <li><a href="?action=addboard">' . _('Add board') . '</a></li>
            <li><a href="?action=delboard">' . _('Delete board') . '</a></li>
            <li><a href="?action=wordfilter">' . _('Wordfilter') . '</a></li>
            <li><a name="misclink" href="?action=misc" onclick="javascript:void(if(document.getElementById(\'miscdiv\').style.visibility == \'visible\' || document.getElementById(\'miscdiv\').style.visibility == \'\') { document.getElementById(\'miscdiv\').style.visibility = \'hidden\'; } else { document.getElementById(\'miscdiv\').style.top = (document.getElementByName(\'misclink\').style.top + document.getElementByName(\'misclink\').style.height); document.getElementById(\'miscdiv\').style.visibility = \'visible\'; }return false;);">' . _('Misc') . '</a></li>
            </ul>';
          } else {
              echo '<a href="?administration">' . _('Administration') . '</a>';
          }
          echo '</li>';
      }
      /* Boards */
      echo '<li>';
      if (($action == 'boardopts' || $action == 'stickypost' || $action == 'unstickypost' || $action == 'lockpost' || $action == 'unlockpost' || $action == 'lockpost' || $action == 'delposts') || pagetaken_check('boards')) {
          echo '<span>' . _('Boards') . '</span>
        <ul id="secondary">
        <li><a href="?action=boardopts">' . _('Board options') . '</a></li>
        <li><a href="?action=stickypost">' . _('Manage stickies') . '</a></li>
        <li><a href="?action=lockpost">' . _('Manage locked threads') . '</a></li>
        <li><a href="?action=delposts">' . _('Delete thread/post') . '</a></li>
        </ul>';
      } else {
          echo '<a href="?boards">' . _('Boards') . '</a>';
      }
      echo '</li>';
      /* Moderation */
      $open_reports = $tc_db->GetAll("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "reports` WHERE `cleared` = '0'");
      echo '<li>';
      if (($action == 'reports' || $action == 'bans' || $action == 'deletepostsbyip' || $action == 'changepwd') || pagetaken_check('moderation')) {
          echo '<span>' . _('Moderation') . '[' . $open_reports[0][0] . ']</span>
        <ul id="secondary">
        <li><a href="?action=reports">' . 'View Reports' . '</a></li>
        <li><a href="?action=bans">' . _('View/Add/Remove bans') . '</a></li>
        <li><a href="?action=deletepostsbyip">' . _('Delete all posts by IP') . '</a></li>
        <li><a href="?action=changepwd">' . _('Change account password') . '</a></li>
        </ul>';
      } else {
          echo '<a href="?moderation">' . _('Moderation') . '[' . $open_reports[0][0] . ']</a>';
      }
      echo '</li>';
      echo '</ul>
  </div>';
      /* End Tabs */
      echo '<div id="main">
  <div id="contents">';
  }
  
  function pagetaken_check($pagename)
  {
      global $action;
      $tab_is_selected = false;
      $pages = array('home', 'administration', 'boards', 'moderation');
      foreach ($pages as $page) {
          if (isset($_GET[$page])) {
              $tab_is_selected = true;
          }
      }
      if ($tab_is_selected && isset($_GET[$pagename])) {
          return true;
      } else {
          /* Special workaround for index page */
          if ($pagename == 'home' && ($action == 'posting_rates' || $action == '') && !$tab_is_selected) {
              return true;
          } else {
              return false;
          }
      }
  }
  
  /* Show the footer of the manage page */
  function manage_footer()
  {
      global $tc_db;
      
      echo '</div>
  </div>';
      
      $board_class = new Board('');
      echo $board_class->Footer(true);
  }
  
  function editsections()
  {
      global $tc_db, $lang;
      management_adminsonly();
      if ($_GET['do'] == 'addsection') {
          if ($_POST['name'] != '' && $_POST['abbreviation'] != '') {
              $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "sections` ( `name` , `abbreviation` , `order` , `hidden` ) VALUES ( '" . mysql_real_escape_string($_POST['name']) . "' , '" . mysql_real_escape_string($_POST['abbreviation']) . "' , '" . mysql_real_escape_string($_POST['order']) . "' , '" . (isset($_POST['hidden']) ? '1' : '0') . "' )");
              echo _('Section added.');
          } else {
              echo '<form action="?action=editsections&do=addsection" method="post">
            <label for="name">Name:</label><input type="text" name="name"><div class="desc">The name of the section</div><br>
            <label for="abbreviation">Abbreviation:</label><input type="text" name="abbreviation"><div class="desc">Abbreviation (less then 10 characters)</div><br>
            <label for="order">Order:</label><input type="text" name="order"><div class="desc">Order to show this section with others, in ascending order</div><br>
            <label for="hidden">Hidden:</label><input type="checkbox" name="hidden" ><div class="desc">If checked, this section will be collapsed by default when a user visits the site.</div><br>
            <input type="submit" value="Add">
            </form>';
          }
          echo '<br><hr>';
      }
      if ($_GET['do'] == 'editsection' && $_GET['sectionid'] > 0) {
          if ($_POST['name'] != '' && $_POST['abbreviation'] != '') {
              $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "sections` SET `name` = '" . mysql_real_escape_string($_POST['name']) . "' , `abbreviation` = '" . mysql_real_escape_string($_POST['abbreviation']) . "' , `order` = '" . mysql_real_escape_string($_POST['order']) . "' , `hidden` = '" . (isset($_POST['hidden']) ? '1' : '0') . "' WHERE `id` = '" . $_GET['sectionid'] . "'");
              echo _('Section updated.');
          } else {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "sections` WHERE `id` = '" . mysql_real_escape_string($_GET['sectionid']) . "'");
              if (count($results) > 0) {
                  foreach ($results as $line) {
                      echo '<form action="?action=editsections&do=editsection&sectionid=' . $_GET['sectionid'] . '" method="post">
                    <input type="hidden" name="id" value="' . $_GET['sectionid'] . '">
                    <label for="name">Name:</label><input type="text" name="name" value="' . $line['name'] . '"><div class="desc">The name of the section</div><br>
                    <label for="abbreviation">Abbreviation:</label><input type="text" name="abbreviation" value="' . $line['abbreviation'] . '"><div class="desc">Abbreviation (less then 10 characters)</div><br>
                    <label for="order">Order:</label><input type="text" name="order" value="' . $line['order'] . '"><div class="desc">Order to show this section with others, in ascending order</div><br>
                    <label for="hidden">Hidden:</label><input type="checkbox" name="hidden" ' . ($line['hidden'] == 0 ? '' : 'checked') . '><div class="desc">If checked, this section will be collapsed by default when a user visits the site.</div><br>
                    <input type="submit" value="Edit">
                    </form>';
                  }
              } else {
                  echo _('Unable to locate a section with that ID.');
              }
          }
          echo '<br><hr>';
      }
      if ($_GET['do'] == 'deletesection' && $_GET['sectionid'] > 0) {
          $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "sections` WHERE `id` = '" . mysql_real_escape_string($_GET['sectionid']) . "'");
          echo _('Section deleted.');
          echo '<br><hr>';
      }
      echo '<a href="?action=editsections&do=addsection">Add section</a><br><br>';
      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "sections` ORDER BY `order` ASC");
      if (count($results) > 0) {
          echo '<table border="1"><tr><th>'.('ID').'</th><th>'.('Order').'</th><th>Abbreviation</th><th>Name</th><th>Edit/Delete</th></tr>';
          foreach ($results as $line) {
              echo '<tr><td>' . $line['id'] . '</td><td>' . $line['order'] . '</td><td>' . $line['abbreviation'] . '</td><td>' . $line['name'] . '</td><td><a href="?action=editsections&do=editsection&sectionid=' . $line['id'] . '">Edit</a> <a href="?action=editsections&do=deletesection&sectionid=' . $line['id'] . '">Delete</a></td></tr>';
          }
          echo '</table>';
      } else {
          echo _('There are currently no sections.');
      }
  }
  
  function editfiletypes()
  {
      global $tc_db, $lang;
      management_adminsonly();
      if ($_GET['do'] == 'addfiletype') {
          if ($_POST['filetype'] != '' && $_POST['image'] != '') {
              $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "filetypes` ( `filetype` , `image` , `image_w` , `image_h` ) VALUES ( '" . mysql_real_escape_string($_POST['filetype']) . "' , '" . mysql_real_escape_string($_POST['image']) . "' , '" . mysql_real_escape_string($_POST['image_w']) . "' , '" . mysql_real_escape_string($_POST['image_h']) . "' )");
              echo _('Filetype added.');
          } else {
              echo '<form action="?action=editfiletypes&do=addfiletype" method="post">
            <label for="filetype">Filetype:</label><input type="text" name="filetype"><div class="desc">The extension this will be applied to.  <b>Must be lowercase</b></div><br>
            <label for="image">Image:</label><input type="text" name="image" value="generic.png"><div class="desc">The image which will be used, found in inc/filetypes.</div><br>
            <label for="image_w">Image width:</label><input type="text" name="image_w" value="48"><div class="desc">The height of the image.  Needs to be set to prevent the page from jumping around while images load.</div><br>
            <label for="image_h">Image height:</label><input type="text" name="image_h" value="48"><div class="desc">See above.</div><br>
            <input type="submit" value="Add">
            </form>';
          }
          echo '<br><hr>';
      }
      if ($_GET['do'] == 'editfiletype' && $_GET['filetypeid'] > 0) {
          if ($_POST['filetype'] != '' && $_POST['image'] != '') {
              $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "filetypes` SET `filetype` = '" . mysql_real_escape_string($_POST['filetype']) . "' , `image` = '" . mysql_real_escape_string($_POST['image']) . "' , `image_w` = '" . mysql_real_escape_string($_POST['image_w']) . "' , `image_h` = '" . mysql_real_escape_string($_POST['image_h']) . "' WHERE `id` = '" . mysql_real_escape_string($_GET['filetypeid']) . "'");
              echo _('Filetype updated.');
          } else {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "filetypes` WHERE `id` = '" . mysql_real_escape_string($_GET['filetypeid']) . "'");
              if (count($results) > 0) {
                  foreach ($results as $line) {
                      echo '<form action="?action=editfiletypes&do=editfiletype&filetypeid=' . $_GET['filetypeid'] . '" method="post">
                    <label for="filetype">Filetype:</label><input type="text" name="filetype" value="' . $line['filetype'] . '"><div class="desc">The extension this will be applied to.  <b>Must be lowercase</b></div><br>
                    <label for="image">Image:</label><input type="text" name="image" value="' . $line['image'] . '"><div class="desc">The image which will be used, found in inc/filetypes.</div><br>
                    <label for="image_w">Image width:</label><input type="text" name="image_w" value="' . $line['image_w'] . '"><div class="desc">The height of the image.  Needs to be set to prevent the page from jumping around while images load.</div><br>
                    <label for="image_h">Image height:</label><input type="text" name="image_h" value="' . $line['image_h'] . '"><div class="desc">See above.</div><br>
                    <input type="submit" value="Edit">
                    </form>';
                  }
              } else {
                  echo _('Unable to locate a filetype with that ID.');
              }
          }
          echo '<br><hr>';
      }
      if ($_GET['do'] == 'deletefiletype' && $_GET['filetypeid'] > 0) {
          $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "filetypes` WHERE `id` = '" . mysql_real_escape_string($_GET['filetypeid']) . "'");
          echo _('Filetype deleted.');
          echo '<br><hr>';
      }
      echo '<a href="?action=editfiletypes&do=addfiletype">Add filetype</a><br><br>';
      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "filetypes` ORDER BY `filetype` ASC");
      if (count($results) > 0) {
          echo '<table border="1"><tr><th>ID</th><th>Filetype</th><th>Image</th><th>Edit/Delete</th></tr>';
          foreach ($results as $line) {
              echo '<tr><td>' . $line['id'] . '</td><td>' . $line['filetype'] . '</td><td>' . $line['image'] . '</td><td><a href="?action=editfiletypes&do=editfiletype&filetypeid=' . $line['id'] . '">Edit</a> <a href="?action=editfiletypes&do=deletefiletype&filetypeid=' . $line['id'] . '">Delete</a></td></tr>';
          }
          echo '</table>';
      } else {
          echo _('There are currently no filetypes.');
      }
  }
  function rebuildall()
  {
      global $tc_db, $lang;
      management_adminsonly();
      $time_start = time();
      $results = $tc_db->GetAll("SELECT `id`, `name` FROM `" . TC_DBPREFIX . "boards`");
      foreach ($results as $line) {
          $board_class = new Board($line['name']);
          $board_class->RegenerateAll();
          echo sprintf(_('Regenerated %s'), '/' . $line['name'] . '/') . '<br>';
          unset($board_class);
          flush();
      }
      printf(_('Rebuild complete.  Took <b>%d</b> seconds.'), time() - $time_start);
      management_addlogentry(_('Rebuilt all boards and threads'), 2);
      unset($board_class);
  }
  
  function modlog()
  {
      global $tc_db, $lang;
      management_adminsonly();
      $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "modlog` WHERE `timestamp` < '" . (time() - TC_MODLOGDAYS * 86400) . "'");
      echo '<h2>'._('ModLog').'</h2><table cellspacing="2" cellpadding="1" border="1"><tr><th>Time</th><th>User</th><th width="100%">Action</th></tr>';
      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "modlog` ORDER BY `timestamp` DESC");
      foreach ($results as $line) {
          echo "<tr><td>" . date("y/m/d(D)H:i", $line['timestamp']) . "</td><td>" . $line['user'] . "</td><td>" . $line['entry'] . "</td></tr>";
      }
      echo '</table>';
  }
  
  function sql()
  {
      global $tc_db, $lang;
      management_adminsonly();
      echo '<h2>' . _('SQL query') . '</h2>';
      if (isset($_POST['query'])) {
          echo '<hr>';
          $result = $tc_db->Execute($_POST['query']);
          if ($result) {
              echo _('Query executed successfully');
          } else {
              echo 'Error: ' . $tc_db->Error();
          }
          echo '<hr>';
      }
      echo '<form method="post" action="?action=sql"><textarea name="query" rows="20" cols="60"></textarea><br><input type="submit" value="' . _('Inject') . '"></form>';
  }
  
  function news()
  {
      global $tc_db, $lang;
      
      management_adminsonly();
      if ($_GET['edit'] > 0) {
          if (isset($_POST['news'])) {
              $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "news` SET `subject` = '" . $_POST['subject'] . "', `message` = '" . $_POST['news'] . "', `postedemail` = '" . $_POST['postedemail'] . "' WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
              echo '<h3>News post edited</h3>';
          }
          echo '<h1>Edit news post</h1>';
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "news` WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
          foreach ($results as $line) {
              echo '<form method="post" action="?action=news&edit=' . $_GET['edit'] . '"><label for="subject">' . _('Subject') . ':</label><input type="text" name="subject" value="' . $line['subject'] . '"><div class="desc">' . _('Can not be left blank.') . '</div><br>

                <textarea name="news" rows="25" cols="80">' . $line['message'] . '</textarea><br><label for="email">' . _('E-mail') . ':</label><input type="text" name="email" value="' . $line['postedemail'] . '"><div class="desc">' . _('Can be left blank.') . '</div><br><input type="submit" value="Edit"></form>';
          }
      } elseif ($_GET['delete'] > 0) {
          $results = $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "news` WHERE `id` = '" . mysql_real_escape_string($_GET['delete']) . "'");
          echo '<h3>News post deleted</h3>';
      } else {
          echo _('<h2>Add News Post</h2>This message will be displayed as it is written, so make sure you add the proper HTML.') . '<br><br>';
          if (isset($_POST['news']) && isset($_POST['subject']) && isset($_POST['email'])) {
              if ($_POST['news'] != "") {
                  echo '<hr>';
                  if ($_POST['subject'] != "") {
                      $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "news` ( `subject` , `message` , `postedat` , `postedby` , `postedemail` ) VALUES ( '" . mysql_real_escape_string($_POST['subject']) . "' , '" . mysql_real_escape_string($_POST['news']) . "' , '" . time() . "' , '" . mysql_real_escape_string($_SESSION['manageusername']) . "' , '" . mysql_real_escape_string($_POST['email']) . "' )");
                      echo '<h3>' . _('News entry successfully added.') . '</h3>';
                      management_addlogentry(_('Added a news entry'), 9);
                  } else {
                      echo _('You must enter a subject.');
                  }
                  echo '<hr>';
              }
          }
          echo '<form method="post" action="?action=news"><label for="subject">' . _('Subject') . ':</label><input type="text" name="subject" value=""><div class="desc">' . _('Can not be left blank.') . '</div><br>

            <textarea name="news" rows="25" cols="80"></textarea><br><label for="email">' . _('E-mail') . ':</label><input type="text" name="email" value=""><div class="desc">' . _('Can be left blank.') . '</div><br><input type="submit" value="' . _('Add') . '"></form>';
          echo '<br><hr><h1>Edit/Delete News</h1>';
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "news`");
          if (count($results) > 0) {
              echo '<table border="1"><tr><th>Date Added</th><th>Subject</th><th>Message</th><th>Edit/Delete</th></tr>';
              foreach ($results as $line) {
                  echo '<tr><td>' . date('F j, Y, g:i a', $line['postedat']) . '</td><td>' . $line['subject'] . '</td><td>' . $line['message'] . '</td><td><a href="?action=news&edit=' . $line['id'] . '">Edit</a>/<a href="?action=news&delete=' . $line['id'] . '">Delete</a></td></tr>';
              }
              echo '</table>';
          } else {
              echo 'No news posts yet.';
          }
      }
  }
  
  function clearcache()
  {
      global $tc_db, $lang;
      
      management_adminsonly();
      if (TC_DBUSECACHE) {
          $tc_db->CacheFlush();
          echo _('Cache successfully flushed.');
      } else {
          echo _('Unable to clear cache: you do not have caching enabled.');
      }
  }
  
  function boardopts()
  {
      global $tc_db, $lang;

      management_adminsonly();
      if (isset($_GET['updateboard']) && isset($_POST['order']) && isset($_POST['maxpages']) && isset($_POST['maxage']) && isset($_POST['messagelength'])) {
          if (!moderator_ismodofboard($_GET['updateboard'], $_SESSION['manageusername'])) {
              die(_('You are not a moderator of this board.'));
          }
          $resultsboard = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['updateboard']) . "'");
          if (count($resultsboard) > 0) {
              if ($_POST['order'] >= 0 && $_POST['maxpages'] >= 0 && $_POST['maxage'] >= 0 && $_POST['messagelength'] >= 0 && ($_POST['enablereporting'] == '0' || $_POST['enablereporting'] == '1') && ($_POST['enablecaptcha'] == '0' || $_POST['enablecaptcha'] == '1') && ($_POST['defaultstyle'] == 'Burichan' || $_POST['defaultstyle'] == 'Futaba' || $_POST['defaultstyle'] == 'Gurochan' || $_POST['defaultstyle'] == 'Photon' || $_POST['defaultstyle'] == 'Fuhrerchan')) {
                  $filetypes = array();
                  while (list($postkey, $postvalue) = each($_POST)) {
                      if (substr($postkey, 0, 9) == 'filetype_') {
                          $filetypes[] = strtoupper(substr($postkey, 9));
                      }
                  }
                  if (isset($_POST['locked'])) {
                      $updateboard_locked = $_POST['locked'] == "on" ? "1" : "0";
                  } else {
                      $updateboard_locked = "0";
                  }
                  if (($_POST['type'] == '0' || $_POST['type'] == '1' || $_POST['type'] == '2') && ($_POST['uploadtype'] == '0' || $_POST['uploadtype'] == '1' || $_POST['uploadtype'] == '2')) {
                      $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "boards` SET `type` = '" . mysql_real_escape_string($_POST['type']) . "' , `uploadtype` = '" . mysql_real_escape_string($_POST['uploadtype']) . "' , `order` = '" . mysql_real_escape_string($_POST['order']) . "' , `section` = '" . mysql_real_escape_string($_POST['section']) . "' , `desc` = '" . mysql_real_escape_string($_POST['desc']) . "' , `filetypes` = '" . implode('|', $filetypes) . "' , `locked` = '" . $updateboard_locked . "' , `maximagesize` = '" . mysql_real_escape_string($_POST['maximagesize']) . "' , `messagelength` = '" . mysql_real_escape_string($_POST['messagelength']) . "' , `maxpages` = '" . mysql_real_escape_string($_POST['maxpages']) . "' , `maxage` = '" . mysql_real_escape_string($_POST['maxage']) . "' , `maxreplies` = '" . mysql_real_escape_string($_POST['maxreplies']) . "' , `image` = '" . mysql_real_escape_string($_POST['image']) . "' , `includeheader` = '" . mysql_real_escape_string($_POST['includeheader']) . "' , `redirecttothread` = '" . mysql_real_escape_string($_POST['redirecttothread']) . "' , `forcedanon` = '" . mysql_real_escape_string($_POST['forcedanon']) . "' , `defaultstyle` = '" . $_POST['defaultstyle'] . "' , `enablereporting` = '" . mysql_real_escape_string($_POST['enablereporting']) . "' , `enablecaptcha` = '" . mysql_real_escape_string($_POST['enablecaptcha']) . "' , `enablenofile` = '" . mysql_real_escape_string($_POST['enablenofile']) . "' WHERE `name` = '" . mysql_real_escape_string($_GET['updateboard']) . "'");
                      echo _('Update successful.');
                      management_addlogentry(_('Updated board configuration') . " - /" . $_GET['updateboard'] . "/", 4);
                  } else {
                      echo _('Sorry, a generic error has occurred.');
                  }
              } else {
                  echo _('Integer values must be entered correctly.');
              }
          } else {
              echo _('Unable to locate a board named') . ' <b>' . $_GET['updateboard'] . '</b>.';
          }
      } elseif (isset($_POST['board'])) {
          if (!moderator_ismodofboard($_POST['board'], $_SESSION['manageusername'])) {
              die(_('You are not a moderator of this board.'));
          }
          $resultsboard = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['board']) . "'");
          if (count($resultsboard) > 0) {
              foreach ($resultsboard as $lineboard) {
?>
                    <div class="container">

                    <form action="?action=boardopts&updateboard=<?php echo $_POST['board']; ?>" method="post">
                    <label for="board"><?php echo _('Directory'); ?>:</label><input type="text" name="board" value="<?php echo $_POST['board']; ?>" disabled><div class="desc"><?php echo _('The directory of the board.'); ?></div><br>
                    <label for="type">Board type:</label><select name="type"><option value="0"<?php if ($lineboard['type'] == '0') { echo ' selected'; } ?>>Normal Imageboard</option><option value="1"<?php if ($lineboard['type'] == '1') { echo ' selected'; } ?>>Text Board</option><option value="2"<?php if ($lineboard['type'] == '2') { echo ' selected'; } ?>>Oekaki Imageboard</option></select><div class="desc"><?php echo 'The type of posts which will be accepted on this board.  A normal imageboard will feature image and extended format posts, a text board will have no images, and an Oekaki board will allow users to draw images and use them in their posts.' . ' ' . _('Default');?>: <b>Normal Imageboard</b></div><br>
                    <label for="uploadtype">Upload type:</label><select name="uploadtype"><option value="0"<?php
                  if ($lineboard['uploadtype'] == '0') {
                      echo ' selected';
                  }
?>>No Embedding</option><option value="1"<?php
                  if ($lineboard['uploadtype'] == '1') {
                      echo ' selected';
                  }
?>>Images and Embedding</option><option value="2"<?php
                  if ($lineboard['uploadtype'] == '2') {
                      echo ' selected';
                  }
?>>Embedding Only</option></select><div class="desc"><?php
                  echo 'Whether or not to allow embedding of videos.' . ' ' . _('Default');
?>: <b>No Embedding</b></div><br>

                    <label for="desc"><?php
                  echo _('Description');
?>:</label><input type="text" name="desc" value="<?php
                  echo $lineboard['desc'];
?>"><div class="desc"><?php
                  echo _('The name of the board.');
?></div><br>
                    <label for="order"><?php
                  echo _('Order');
?>:</label><input type="text" name="order" value="<?php
                  echo $lineboard['order'];
?>"><div class="desc"><?php
                  echo _('Order to show board in menu list, in ascending order.');
?> <b>0</b></div><br>
                    <label for="section"><?php
                  echo _('Section');
?>:</label><input type="text" name="section" value="<?php
                  echo $lineboard['section'];
?>"><div class="desc"><?php
                  echo _('The section the board is in.  This is used for displaying the list of boards on the top and bottom of pages.');
?><br>If this is set to 0, <b>it will not be shown in the menu</b>.</div><br>
                    <label for="locked"><?php
                  echo _('Locked');
?>: (<img src="<?php
                  echo TC_BOARDSPATH;
?>/locked.gif" alt="Lock">)</label><input type="checkbox" name="locked" <?php
                  if ($lineboard['locked'] == "1") {
                      echo 'checked ';
                  }
?>><div class="desc"><?php
                  echo _('Only moderators of the board and admins can make new posts/replies');
?></div><br>

                    <label for="enablereporting">Enable reporting:</label><select name="enablereporting"><option value="0"<?php
                  if ($lineboard['enablereporting'] == '0') {
                      echo ' selected';
                  }
?>><?php
                  echo _('No');
?></option><option value="1"<?php
                  if ($lineboard['enablereporting'] == '1') {
                      echo ' selected';
                  }
?>><?php
                  echo _('Yes');
?></option></select><div class="desc"><?php
                  echo 'Reporting allows users to report posts, adding the post to the report list.' . ' ' . _('Default');
?>: <b><?php
                  echo _('Yes');
?></b></div><br>
                    <label for="enablecaptcha">Enable captcha:</label><select name="enablecaptcha"><option value="0"<?php
                  if ($lineboard['enablecaptcha'] == '0') {
                      echo ' selected';
                  }
?>><?php
                  echo _('No');
?></option><option value="1"<?php
                  if ($lineboard['enablecaptcha'] == '1') {
                      echo ' selected';
                  }
?>><?php
                  echo _('Yes');
?></option></select><div class="desc"><?php
                  echo 'Enable/disable captcha system for this board.  If captcha is enabled, in order for a user to post, they must first correctly enter the text on an image.' . ' ' . _('Default');
?>: <b><?php
                  echo _('No');
?></b></div><br>
                    <label><?php
                  echo _('Allowed image types');
?>:</label><div class="desc"><?php
                  echo _('What filetypes users are allowed to upload.');
?></div><br><label for="filetype_gif">GIF</label><input type="checkbox" name="filetype_gif" <?php
                  if (in_array('GIF', explode('|', $lineboard['filetypes']))) {
                      echo 'checked ';
                  }
?>><br><label for="filetype_jpg">JPG</label><input type="checkbox" name="filetype_jpg" <?php
                  if (in_array('JPG', explode('|', $lineboard['filetypes']))) {
                      echo 'checked ';
                  }
?>><br><label for="filetype_png">PNG</label><input type="checkbox" name="filetype_png" <?php
                  if (in_array('PNG', explode('|', $lineboard['filetypes']))) {
                      echo 'checked ';
                  }
?>><br>

                    <?php
                  $results_filetypes = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "filetypes`");
                  foreach ($results_filetypes as $line_filetypes) {
                      echo '<label for="filetype_' . $line_filetypes['filetype'] . '">' . strtoupper($line_filetypes['filetype']) . '</label><input type="checkbox" name="filetype_' . $line_filetypes['filetype'] . '"';
                      if (in_array(strtoupper($line_filetypes['filetype']), explode('|', $lineboard['filetypes']))) {
                          echo ' checked';
                      }
                      echo '><br>';
                  }
?>
                    <label for="maximagesize"><?php
                  echo _('Maximum image size');
?>:</label><input type="text" name="maximagesize" value="<?php
                  echo $lineboard['maximagesize'];
?>"><div class="desc"><?php
                  echo _('Maxmimum size of uploaded images, in <b>bytes</b>.') . ' ' . _('Default');
?>: <b>1024000</b></div><br>
                    <label for="messagelength"><?php
                  echo _('Maximum message length');
?>:</label><input type="text" name="messagelength" value="<?php
                  echo $lineboard['messagelength'];
?>"><div class="desc"><?php
                  echo _('Default');
?>: <b>8192</b></div><br>
                    <label for="maxpages"><?php
                  echo _('Maximum board pages');
?>:</label><input type="text" name="maxpages" value="<?php
                  echo $lineboard['maxpages'];
?>"><div class="desc"><?php
                  echo _('Default');
?>: <b>10</b></div><br>

                    <label for="maxage"><?php
                  echo _('Maximum thread age (Hours)');
?>:</label><input type="text" name="maxage" value="<?php
                  echo $lineboard['maxage'];
?>"><div class="desc"><?php
                  echo _('Default');
?>: <b>96</b></div><br>
                    <label for="maxreplies"><?php
                  echo _('Maximum thread replies');
?>:</label><input type="text" name="maxreplies" value="<?php
                  echo $lineboard['maxreplies'];
?>"><div class="desc"><?php
                  echo _('The number of replies a thread can have before autosaging to the back of the board.') . ' ' . _('Default');
?>: <b>200</b></div><br>
                    <label for="image"><?php
                  echo _('Header image');
?>:</label><input type="text" name="image" value="<?php
                  echo $lineboard['image'];
?>"><div class="desc"><?php
                  echo _('Overrides the header set in the config file.  Leave blank to use configured global header image.  Needs to be a full url including http://.  Set to none to show no header image.');
?></div><br>
                    <label for="includeheader"><?php
                  echo _('Include header');
?>:</label><textarea name="includeheader" rows="12" cols="80"><?php
                  echo $lineboard['includeheader'];
?></textarea><div class="desc"><?php
                  echo _('Raw HTML which will be inserted at the top of each page of the board.');
?></div><br>
                    <label for="enablenofile"><?php
                  echo _('Enable "no file" posting');
?>:</label><select name="enablenofile"><?php
                  echo($lineboard['enablenofile'] == '1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>';
?></select><div class="desc"><?php
                  echo _('If set to yes, new threads will not require an image to be posted.') . ' ' . _('Default');
?>: <b><?php
                  echo _('No');
?></b></div><br>
                    <label for="redirecttothread"><?php
                  echo _('Redirect to thread');
?>:</label><select name="redirecttothread"><?php
                  echo($lineboard['redirecttothread'] == '1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>';
?></select><div class="desc"><?php
                  echo _('If set to yes, users will be redirected to the thread they replied to/posted after posting.  If set to no, users will be redirected to the first page of the board.') . ' ' . _('Default');
?>: <b><?php
                  echo _('No');
?></b></div><br>
                    <label for="forcedanon"><?php
                  echo _('Forced anonymous');
?>:</label><select name="forcedanon"><?php
                  echo($lineboard['forcedanon'] == '1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>';
?></select><div class="desc"><?php
                  echo _('If set to yes, users will not be allowed to enter a name, making everyone appear as Anonymous') . ' ' . _('Default');
?>: <b><?php
                  echo _('No');
?></b></div><br>

                    <label for="defaultstyle">Default style:</label><select name="defaultstyle"><option value="Burichan"<?php
                  echo($lineboard['defaultstyle'] == 'Burichan') ? ' selected' : '';
?>>Burichan</option><option value="Futaba"<?php
                  echo($lineboard['defaultstyle'] == 'Futaba') ? ' selected' : '';
?>>Futaba</option><option value="Gurochan"<?php
                  echo($lineboard['defaultstyle'] == 'Gurochan') ? ' selected' : '';
?>>Gurochan</option><option value="Photon"<?php
                  echo($lineboard['defaultstyle'] == 'Photon') ? ' selected' : '';
?>>Photon</option><option value="Fuhrerchan"<?php
                  echo($lineboard['defaultstyle'] == 'Fuhrerchan') ? ' selected' : '';
?>>Fuhrerchan</option></select><div class="desc">The style which will be set when the user first visits the board.<?php
                  echo ' ' . _('Default');
?>: <b>Futaba</b></div><br>
                    <input type="submit" name="submit" value="<?php
                  echo _('Update');
?>">
                    </form>
                    </div>

<?php
              }
          } else {
              echo _('Unable to locate a board named') . ' <b>' . $_POST['board'] . '</b>.';
          }
      } else {
?>
            <form action="?action=boardopts" method="post">
            <label for="board"><?php
          echo _('Board');
?>:</label><?php
          echo make_boardlist_dropdown('board', moderator_boardlist($_SESSION['manageusername']));
?>
            <input type="submit" value="<?php
          echo _('Go');
?>">
            </form>
<?php
      }
  }
//dgsdgdg
  function deletepostsbyip()
  {
      global $tc_db, $board_class, $lang;
      
      if (isset($_POST['ip'])) {
          if ($_POST['ip'] != "") {
              $deletion_boards = array();
              $deletion_new_boards = array();
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
              if (isset($_POST['banfromall'])) {
                  foreach ($results as $line) {
                      $deletion_new_boards[] = $line['name'];
                  }
              } else {
                  foreach ($results as $line) {
                      $deletion_boards[] = $line['name'];
                  }
                  $deletion_changed_boards = array();
                  $deletion_new_boards = array();
                  while (list($postkey, $postvalue) = each($_POST)) {
                      if (substr($postkey, 0, 10) == "deletefrom") {
                          $deletion_changed_boards[] = substr($postkey, 10);
                      }
                  }
                  while (list(, $deletion_thisboard_name) = each($deletion_boards)) {
                      if (in_array($deletion_thisboard_name, $deletion_changed_boards)) {
                          $deletion_new_boards[] = $deletion_thisboard_name;
                      }
                  }
                  if ($deletion_new_boards == array()) {
                      die(_('Please select a board.'));
                  }
              }
              $delete_boards = implode('|', $deletion_new_boards);
              foreach ($deletion_new_boards as $board) {
                  if (!moderator_ismodofboard($board, $_SESSION['manageusername'])) {
                      die('/' . $board . '/: ' . _('You can only delete posts from boards you moderate.'));
                  }
              }
              $i = 0;
              foreach ($deletion_new_boards as $deletion_board) {
                  $results = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($deletion_board) . "'");
                  foreach ($results as $line) {
                      $board_name = $line['name'];
                  }
                  $result = $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $board_name . "` SET `IS_DELETED` = '1' WHERE `is_deleted` = '0' AND `ipmd5` = '" . md5($_POST['ip']) . "'");
                  if (!$result) {
                      echo _('Sorry, a generic error has occurred.');
                      echo mysql_error();
                      die();
                  }
                  $i += $tc_db->Affected_Rows();
                  $board_class = new Board($board_name);
                  $board_class->RegenerateAll();
              }
              echo _('All threads/posts by that IP in selected boards successfully deleted.') . '<br><b>' . $i . '</b> posts were removed.<br>';
              echo '<hr>';
              management_addlogentry(_('Deleted posts by ip') . ' ' . $_POST['ip'], 7);
          }
      }
?>
        <form action="?action=deletepostsbyip" method="post">
        <label for="ip"><?php
      echo _('IP');
?>:</label><input type="text" name="ip" value="<?php
      if (isset($_GET['ip'])) {
          echo $_GET['ip'];
      }
?>"><br>
        <?php
      echo _('Boards');
?>:<label for="banfromall"><b><?php
      echo _('All boards');
?></b></label><input type="checkbox" name="banfromall"><br>OR<br><?php
      make_boardlist_checkbox('deletefrom', moderator_boardlist($_SESSION['manageusername']));
?><br>

        <input type="submit" value="<?php
      echo _('Delete posts');
?>">
        </form>
<?php
  }
  
  function unstickypost()
  {
      global $tc_db, $board_class, $lang;
      
      if (isset($_GET['postid']) && isset($_GET['board'])) {
          if ($_GET['postid'] > 0 && $_GET['board'] != "") {
              $results = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
              if (count($results) > 0) {
                  if (!moderator_ismodofboard($_GET['board'], $_SESSION['manageusername'])) {
                      die(_('You are not a moderator of this board.'));
                  }
                  foreach ($results as $line) {
                      $sticky_board_name = $line['name'];
                  }
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
                  if (count($results) > 0) {
                      $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` SET `stickied` = '0' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
                      $board_class = new Board($sticky_board_name);
                      $board_class->RegenerateAll();
                      echo _('Thread successfully un-stickied');
                      management_addlogentry(_('Unstickied thread') . ' #' . mysql_real_escape_string($_GET['postid']) . ' - /' . mysql_real_escape_string($_GET['board']) . '/', 5);
                  } else {
                      echo _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
                  }
              } else {
                  echo _('Invalid board directory.');
              }
              echo '<hr>';
          }
      }
      stickyforms();
  }
  
  function stickypost()
  {
      global $tc_db, $board_class, $lang;
      
      if (isset($_GET['postid']) && isset($_GET['board'])) {
          if ($_GET['postid'] > 0 && $_GET['board'] != "") {
              $results = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . $_GET['board'] . "'");
              if (count($results) > 0) {
                  if (!moderator_ismodofboard($_GET['board'], $_SESSION['manageusername'])) {
                      die(_('You are not a moderator of this board.'));
                  }
                  foreach ($results as $line) {
                      $sticky_board_name = $line['name'];
                  }
                  $result = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
                  if ($result > 0) {
                      $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` SET `stickied` = '1' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
                      $board_class = new Board($sticky_board_name);
                      $board_class->RegenerateAll();
                      echo _('Thread successfully stickied.');
                      management_addlogentry(_('Stickied thread') . ' #' . mysql_real_escape_string($_GET['postid']) . ' - /' . mysql_real_escape_string($_GET['board']) . '/', 5);
                  } else {
                      echo _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
                  }
              } else {
                  echo _('Invalid board directory.');
              }
              echo '<hr>';
          }
      }
      stickyforms();
  }
  
  function stickyforms()
  {
      global $lang;
?>
        <form action="manage.php" method="get"><input type="hidden" name="action" value="stickypost">
        <label for="board"><?php
      echo _('Board');
?>:</label><?php
      echo make_boardlist_dropdown('board', moderator_boardlist($_SESSION['manageusername']));
?><br>

        <label for="postid"><?php
      echo _('Thread');
?>:</label><input type="text" name="postid"><input type="submit" value="<?php
      echo _('Sticky');
?>">
        </form>
        <br><hr>
        <form action="manage.php" method="get"><input type="hidden" name="action" value="unstickypost">
        <label for="board"><?php
      echo _('Board');
?>:</label><?php
      echo make_boardlist_dropdown('board', moderator_boardlist($_SESSION['manageusername']));
?><br>
        <label for="postid"><?php
      echo _('Thread');
?>:</label><input type="text" name="postid"><input type="submit" value="<?php
      echo _('Unsticky');
?>">
        </form>

<?php
  }
  function lockpost()
  {
      global $tc_db, $board_class, $lang;
      
      if (isset($_GET['postid']) && isset($_GET['board'])) {
          if ($_GET['postid'] > 0 && $_GET['board'] != "") {
              $results = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
              if (count($results) > 0) {
                  if (!moderator_ismodofboard($_GET['board'], $_SESSION['manageusername'])) {
                      die(_('You are not a moderator of this board.'));
                  }
                  foreach ($results as $line) {
                      $lock_board_name = $line['name'];
                  }
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
                  if (count($results) > 0) {
                      $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` SET `locked` = '1' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
                      $board_class = new Board($lock_board_name);
                      $board_class->RegenerateAll();
                      echo _('Thread successfully locked.');
                      management_addlogentry(_('Locked thread') . ' #' . mysql_real_escape_string($_GET['postid']) . ' - /' . mysql_real_escape_string($_GET['board']) . '/', 5);
                  } else {
                      echo _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
                  }
              } else {
                  echo _('Invalid board directory.');
              }
              echo '<hr>';
          }
      }
      lockforms();
  }
  
  function unlockpost()
  {
      global $tc_db, $board_class, $lang;
      
      if ($_GET['postid'] > 0 && $_GET['board'] != "") {
          $results = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
          if (count($results) > 0) {
              if (!moderator_ismodofboard($_GET['board'], $_SESSION['manageusername'])) {
                  die(_('You are not a moderator of this board.'));
              }
              foreach ($results as $line) {
                  $lock_board_name = $line['name'];
              }
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
              if (count($results) > 0) {
                  $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` SET `locked` = '0' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
                  $board_class = new Board($lock_board_name);
                  $board_class->RegenerateAll();
                  echo _('Thread successfully unlocked.');
                  management_addlogentry(_('Unlocked thread') . ' #' . $_GET['postid'] . ' - /' . $_GET['board'] . '/', 5);
              } else {
                  echo _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
              }
          } else {
              echo _('Invalid board directory.');
          }
          echo '<hr>';
      }
      lockforms();
  }
  
  function lockforms()
  {
      global $lang;
?>
        <form action="manage.php" method="get"><input type="hidden" name="action" value="lockpost">
        <label for="board"><?php
      echo _('Board');
?>:</label><?php
      echo make_boardlist_dropdown('board', moderator_boardlist($_SESSION['manageusername']));
?><br>

        <label for="postid"><?php
      echo _('Thread');
?>:</label><input type="text" name="postid"><input type="submit" value="<?php
      echo _('Lock');
?>">
        </form>
        <br><hr>
        <form action="manage.php" method="get"><input type="hidden" name="action" value="unlockpost">
        <label for="board"><?php
      echo _('Board');
?>:</label><?php
      echo make_boardlist_dropdown('board', moderator_boardlist($_SESSION['manageusername']));
?><br>
        <label for="postid"><?php
      echo _('Thread');
?>:</label><input type="text" name="postid"><input type="submit" value="<?php
      echo _('Unlock');
?>">
        </form>

<?php
  }
  
  function cleanup()
  {
      global $lang;
      management_adminsonly();
      delorphanreplies(true);
      echo '<hr>Deleting unused images.<hr>';
      flush();
      delunusedimages_beta(true);
      echo _('Cleanup finished.');
      management_addlogentry(_('Ran cleanup'), 2);
  }
  
  function bans()
  {
      global $tc_db, $lang;
      $ban_ip = "";
      if (isset($_POST['ip']) && isset($_POST['seconds'])) {
          if ($_POST['ip'] != "") {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "banlist` WHERE `ipmd5` = '" . md5($_POST['ip']) . "'");
              if (count($results) == 0) {
                  if ($_POST['seconds'] >= 0) {
                      $banning_boards = array();
                      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
                      foreach ($results as $line) {
                          $banning_boards = array_merge($banning_boards, array($line['name']));
                      }
                      $banning_changed_boards = array();
                      $banning_new_boards = array();
                      while (list($postkey, $postvalue) = each($_POST)) {
                          if (substr($postkey, 0, 10) == "bannedfrom") {
                              $banning_changed_boards = array_merge($banning_changed_boards, array(substr($postkey, 10)));
                          }
                      }
                      while (list(, $banning_thisboard_name) = each($banning_boards)) {
                          if (in_array($banning_thisboard_name, $banning_changed_boards)) {
                              $banning_new_boards = array_merge($banning_new_boards, array($banning_thisboard_name));
                          }
                      }
                      if ($banning_new_boards == array() && $_POST['banfromall'] != 'on') {
                          die(_('Please select a board.'));
                      }
                      $ban_globalban = ($_POST['banfromall'] == 'on') ? '1' : '0';
                      $ban_allowread = ($_POST['allowread'] == '1') ? '1' : '0';
                      if ($ban_globalban == '0') {
                          $ban_boards = implode('|', $banning_new_boards);
                          foreach (explode('|', $ban_boards) as $board) {
                              if (!moderator_ismodofboard($board, $_SESSION['manageusername'])) {
                                  die(_('You can only make board specific bans to boards which you moderate.'));
                              }
                          }
                      } else {
                          $ban_boards = '';
                      }
                      if ($_POST['seconds'] == '0') {
                          //Permanent ban
                          $ban_duration = '0';
                      } else {
                          //Timed ban
                          $ban_duration = mysql_real_escape_string($_POST['seconds']);
                      }
                      if ($_POST['type'] == '0') {
                          //Normal IP address ban
                          $ban_type = '0';
                      } else {
                          //IP range ban
                          $ban_type = '1';
                      }
                      if (ban_user(mysql_real_escape_string($_POST['ip']), $_SESSION['manageusername'], $ban_globalban, $ban_duration, $ban_boards, mysql_real_escape_string($_POST['reason']), $ban_type, $ban_allowread)) {
                          echo _('Ban successfully placed.');
                      } else {
                          echo _('Sorry, a generic error has occurred.');
                          die();
                      }
                      $logentry = _('Banned') . ' ' . $_POST['ip'] . ' until ';
                      if ($_POST['seconds'] == '0') {
                          $logentry .= _('forever');
                      } else {
                          $logentry .= date('F j, Y, g:i a', time() + $_POST['seconds']);
                      }
                      $logentry .= ' - ' . _('Reason') . ': ' . $_POST['reason'] . ' - ' . _('Banned from') . ': ';
                      if ($ban_globalban == '1') {
                          $logentry .= _('All boards') . ' ';
                      } else {
                          $logentry .= '/' . implode('/, /', explode('|', $ban_boards)) . '/ ';
                      }
                      management_addlogentry($logentry, 8);
                      if ($_POST['quickbanboard'] != "" && $_POST['quickbanthreadid'] != "") {
                          echo '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $_POST['quickbanboard'] . '/';
                          if ($_POST['quickbanthreadid'] != "0") {
                              echo 'res/' . $_POST['quickbanthreadid'] . '.html';
                          }
                          echo '"><a href="' . TC_BOARDSPATH . '/' . $_POST['quickbanboard'] . '/';
                          if ($_POST['quickbanthreadid'] != "0") {
                              echo 'res/' . $_POST['quickbanthreadid'] . '.html';
                          }
                          echo '">' . _('Redirecting') . '</a>...';
                      }
                  } else {
                      echo _('Please enter a positive amount of seconds, or zero for a permanent ban.');
                  }
              } else {
                  echo _('That IP has already been banned.');
              }
              echo '<hr>';
          }
      } elseif (isset($_GET['delban'])) {
          if ($_GET['delban'] > 0) {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "banlist` WHERE `id` = '" . mysql_real_escape_string($_GET['delban']) . "'");
              if (count($results) > 0) {
                  foreach ($results as $line) {
                      $unban_ip = md5_decrypt($line['ip'], TC_RANDOMSEED);
                  }
                  $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "banlist` WHERE `id` = '" . mysql_real_escape_string($_GET['delban']) . "'");
                  update_htaccess();
                  echo _('Ban successfully removed.');
                  management_addlogentry(_('Unbanned') . ' ' . $unban_ip, 8);
              } else {
                  echo _('Invalid ban ID');
              }
              echo '<hr>';
          }
      }
      if (isset($_GET['banboard']) && isset($_GET['banpost'])) {
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['banboard']) . "'");
          if (count($results) > 0) {
              foreach ($results as $line) {
                  $ban_board_name = $line['name'];
              }
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $ban_board_name . "` WHERE `id` = '" . mysql_real_escape_string($_GET['banpost']) . "'");
              if (count($results) > 0) {
                  foreach ($results as $line) {
                      $ban_ip = md5_decrypt($line['ip'], TC_RANDOMSEED);
                      $ban_threadid = $line['threadid'];
                  }
              } else {
                  echo _('A post with that ID does not exist.') . '<hr>';
              }
          }
      }
      flush();
      echo '<form action="manage.php?action=bans" method="post" name="banform">';
      if ($ban_ip != "") {
          echo '<input type="hidden" name="quickbanboard" value="' . $_GET['banboard'] . '"><input type="hidden" name="quickbanthreadid" value="' . $ban_threadid . '">';
      } elseif (isset($_GET['ip'])) {
          $ban_ip = $_GET['ip'];
      }
?>
        <label for="ip"><?php
      echo _('IP');
?>:</label><input type="text" name="ip" value="<?php
      echo $ban_ip;
?>"><?php
      if ($ban_ip != "") {
          echo '&nbsp;&nbsp;<a href="?action=deletepostsbyip&ip=' . $ban_ip . '" target="_blank">' . _('Delete all posts by this IP') . '</a>';
      }
?><br>

        <label for="allowread">Allow read:</label><select name="allowread"><option value="1">Yes</option><option value="0">No</option></select><div class="desc">Whether or not the user(s) affected by this ban will be allowed to read the boards.<br><b>Warning</b>: Selecting No will prevent any reading of any page on the level of the boards on the server.<br>Changing this option to No will provide a global ban, whether or not you set the option below.</div><br>
        <label for="type">Type:</label><select name="type"><option value="0">Single IP</option><option value="1">IP Range</option></select><div class="desc">The type of the ban.  A single IP can be banned by providing the full address, or an IP range can be banned by providing the range you wish to ban.</div><br>
        <?php
      echo _('Ban from');
?>:&nbsp;

        <label for="banfromall"><b><?php
      echo _('All boards');
?></b></label><input type="checkbox" name="banfromall"><br>OR<br>
<?php
      make_boardlist_checkbox('bannedfrom', moderator_boardlist($_SESSION['manageusername']));
?>
        <br>
        <label for="seconds"><?php
      echo _('Seconds');
?>:</label><input type="text" name="seconds"><div class="desc"><?php
      echo _('Presets');
?>:&nbsp;<a href="#" onclick="document.banform.seconds.value='3600';">1hr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='604800';">1w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='1209600';">2w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='2592000';">30d</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='31536000';">1yr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value='0';">never</a></div><br>
        <label for="reason"><?php
      echo _('Reason');
?>:</label><input type="text" name="reason"><div class="desc"><?php
      echo _('Presets');
?>:&nbsp;<a href="#" onclick="document.banform.reason.value='Child Pornography';">CP</a>&nbsp;<a href="#" onclick="document.banform.reason.value='Proxy';">Proxy</a></div><br>

        <input type="submit" value="<?php
      echo _('Add ban');
?>">
        </form>
        <hr><br>
<?php
      for ($i = 1; $i >= 0; $i--) {
          if ($i == 1) {
              echo '<b>IP Range bans:</b><br>';
          } else {
              echo '<br><b>Single IP bans:</b><br>';
          }
          
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "banlist` WHERE `type` = '" . $i . "' ORDER BY `id` DESC");
          if (count($results) > 0) {
              echo '<table border="1"><tr><th>';
              if ($i == 1) {
                  echo 'IP Range';
              } else {
                  echo 'IP Address';
              }
              echo '</th><th>Boards</th><th>Reason</th><th>Date Added</th><th>Expires</th><th>Added By</th><th>&nbsp;</th></tr>';
              foreach ($results as $line) {
                  echo '<tr>';
                  echo '<td><a href="?action=bans&ip=' . md5_decrypt($line['ip'], TC_RANDOMSEED) . '">' . md5_decrypt($line['ip'], TC_RANDOMSEED) . '</a></td><td>';
                  if ($line['globalban'] == '1') {
                      echo '<b>' . _('All boards') . '</b>';
                  } else {
                      if ($line['boards'] != '') {
                          echo '<b>/' . implode('/</b>, <b>/', explode('|', $line['boards'])) . '/</b>&nbsp;';
                      }
                  }
                  echo '</td><td>' . stripslashes($line['reason']) . '</td><td>' . date("F j, Y, g:i a", $line['at']) . '</td><td>';
                  if ($line['until'] == '0') {
                      echo '<b>' . _('forever') . '</b>';
                  } else {
                      echo date("F j, Y, g:i a", $line['until']);
                  }
                  echo '</td><td>' . $line['by'] . '</td><td>[<a href="manage.php?action=bans&delban=' . $line['id'] . '">x</a>]</td>';
                  echo '</tr>';
              }
              echo '</table>';
          } else {
              echo _('There are currently no bans.');
          }
      }
  }
  function delposts()
  {
      global $tc_db, $board_class, $lang;
      if (isset($_POST['boarddir']) || isset($_GET['boarddir'])) {
          if (isset($_GET['boarddir'])) {
              $isquickdel = true;
              $_POST['boarddir'] = $_GET['boarddir'];
              if (isset($_GET['delthreadid'])) {
                  $_POST['delthreadid'] = $_GET['delthreadid'];
              }
              if (isset($_GET['delpostid'])) {
                  $_POST['delpostid'] = $_GET['delpostid'];
              }
          }
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['boarddir']) . "'");
          if (count($results) > 0) {
              if (!moderator_ismodofboard($_POST['boarddir'], $_SESSION['manageusername'])) {
                  die(_('You are not a moderator of this board.'));
              }
              foreach ($results as $line) {
                  $board_id = $line['id'];
                  $board_dir = $line['name'];
              }
              if ($_POST['delthreadid'] > 0) {
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $board_dir . "` WHERE `IS_DELETED` = '0' AND  `id` = '" . mysql_real_escape_string($_POST['delthreadid']) . "' AND `threadid` = '0'");
                  if (count($results) > 0) {
                      foreach ($results as $line) {
                          $delthread_id = $line['id'];
                      }
                      $post_class = new Post($delthread_id, $board_dir);
                      $numposts_deleted = $post_class->Delete();
                      $board_class = new Board($board_dir);
                      $board_class->RegenerateAll();
                      echo _('Thread successfully deleted.');
                      management_addlogentry(_('Deleted thread') . ' #<a href="?action=viewdeletedthread&threadid=' . $delthread_id . '&board=' . $_POST['boarddir'] . '">' . $delthread_id . '</a> (' . $numposts_deleted . ' replies) - /' . $board_dir . '/', 7);
                      if ($_GET['postid'] != "") {
                          echo '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/manage.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '"><a href="' . TC_BOARDSPATH . '/manage.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '">' . _('Redirecting') . '</a> to ban page...';
                      } elseif ($isquickdel) {
                          echo '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/"><a href="' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/">' . _('Redirecting') . '</a> back to board...';
                      }
                  } else {
                      echo _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
                  }
              } elseif ($_POST['delpostid'] > 0) {
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $board_dir . "` WHERE `IS_DELETED` = '0' AND  `id` = '" . mysql_real_escape_string($_POST['delpostid']) . "'");
                  if (count($results) > 0) {
                      foreach ($results as $line) {
                          $delpost_id = $line['id'];
                          $delpost_threadid = $line['threadid'];
                      }
                      $post_class = new Post($delpost_id, $board_dir);
                      $post_class->Delete();
                      flushthreadrepliescache($delpost_threadid, $board_dir);
                      $board_class = new Board($board_dir);
                      $board_class->RegenerateThread($delpost_threadid);
                      $board_class->RegeneratePages();
                      echo _('Post successfully deleted.');
                      management_addlogentry(_('Deleted post') . ' #<a href="?action=viewdeletedthread&threadid=' . $delpost_threadid . '&board=' . $_POST['boarddir'] . '#' . $delpost_id . '">' . $delpost_id . '</a> - /' . $board_dir . '/', 7);
                      if ($_GET['postid'] != "") {
                          echo '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/manage.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '"><a href="' . TC_BOARDSPATH . '/manage.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '">' . _('Redirecting') . '</a> to ban page...';
                      } elseif ($isquickdel) {
                          echo '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/res/' . $delpost_threadid . '.html"><a href="' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/res/' . $delpost_threadid . '.html">' . _('Redirecting') . '</a> back to thread...';
                      }
                  } else {
                      echo _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
                  }
              }
          } else {
              echo _('Invalid board directory.');
          }
          echo '<hr>';
      }
?>
        <form action="manage.php?action=delposts" method="post">

        <label for="boarddir"><?php
      echo _('Board');
?>:</label><?php
      echo make_boardlist_dropdown('boarddir', moderator_boardlist($_SESSION['manageusername']));
?><br>
        <label for="delthreadid"><?php
      echo _('Thread');
?>:</label><input type="text" name="delthreadid"><br>
        <input type="submit" value="<?php
      echo _('Delete thread');
?>">
        </form>
        <br><hr>
        <form action="manage.php?action=delposts" method="post">
        <label for="boarddir"><?php
      echo _('Board');
?>:</label><?php
      echo make_boardlist_dropdown('boarddir', moderator_boardlist($_SESSION['manageusername']));
?><br>

        <label for="delpostid"><?php
      echo _('Post');
?>:</label><input type="text" name="delpostid"><br>
        <input type="submit" value="<?php
      echo _('Delete post');
?>">
        </form>
<?php
  }
  
  function wordfilter()
  {
      global $tc_db, $lang;
      
      management_adminsonly();
      if (isset($_POST['word'])) {
          if ($_POST['word'] != "" && $_POST['replacedby'] != "") {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `word` = '" . mysql_real_escape_string($_POST['word']) . "'");
              if (count($results) == 0) {
                  $wordfilter_boards = array();
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
                  foreach ($results as $line) {
                      $wordfilter_boards = array_merge($wordfilter_boards, array($line['name']));
                  }
                  $wordfilter_changed_boards = array();
                  $wordfilter_new_boards = array();
                  while (list($postkey, $postvalue) = each($_POST)) {
                      if (substr($postkey, 0, 10) == "wordfilter") {
                          $wordfilter_changed_boards = array_merge($wordfilter_changed_boards, array(substr($postkey, 10)));
                      }
                  }
                  while (list(, $wordfilter_thisboard_name) = each($wordfilter_boards)) {
                      if (in_array($wordfilter_thisboard_name, $wordfilter_changed_boards)) {
                          $wordfilter_new_boards = array_merge($wordfilter_new_boards, array($wordfilter_thisboard_name));
                      }
                  }
                  $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "wordfilter` ( `word` , `replacedby` , `boards` , `time` ) VALUES ( '" . mysql_real_escape_string($_POST['word']) . "' , '" . mysql_real_escape_string($_POST['replacedby']) . "' , '" . mysql_real_escape_string(implode('|', $wordfilter_new_boards)) . "' , '" . time() . "' )");
                  echo _('Word successfully added.');
                  management_addlogentry("Added word to wordfilter: " . $_POST['word'] . " - Changes to: " . $_POST['replacedby'] . " - Boards: /" . implode('/, /', explode('|', implode('|', $wordfilter_new_boards))) . "/", 11);
              } else {
                  echo $land['that word already exists'];
              }
          } else {
              echo _('Please fill in all required fields.');
          }
          echo '<hr>';
      } elseif (isset($_GET['delword'])) {
          if ($_GET['delword'] > 0) {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['delword']) . "'");
              if (count($results) > 0) {
                  foreach ($results as $line) {
                      $del_word = $line['word'];
                  }
                  $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['delword']) . "'");
                  echo _('Word successfully removed.');
                  management_addlogentry(_('Removed word from wordfilter') . ': ' . $del_word, 11);
              } else {
                  echo _('That ID does not exist.');
              }
              echo '<hr>';
          }
      } elseif (isset($_GET['editword'])) {
          if ($_GET['editword'] > 0) {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['editword']) . "'");
              if (count($results) > 0) {
                  if (!isset($_POST['replacedby'])) {
                      foreach ($results as $line) {
?>
                            <form action="manage.php?action=wordfilter&editword=<?php
                          echo $_GET['editword'];
?>" method="post">
                            <label for="word"><?php
                          echo _('Word');
?>:</label><input type="text" name="word" value="<?php
                          echo $line['word'];
?>" disabled><br>
                            <label for="replacedby"><?php
                          echo _('Is replaced by');
?>:</label><input type="text" name="replacedby" value="<?php
                          echo $line['replacedby'];
?>"><br>

                            <label><?php
                          echo _('Boards');
?>:</label><br>
<?php
                          $array_boards = array();
                          $resultsboard = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
                          foreach ($resultsboard as $lineboard) {
                              $array_boards = array_merge($array_boards, array($lineboard['name']));
                          }
                          foreach ($array_boards as $this_board_name) {
                              echo '<label for="wordfilter' . $this_board_name . '">' . $this_board_name . '</label><input type="checkbox" name="wordfilter' . $this_board_name . '" ';
                              if (in_array($this_board_name, explode("|", $line['boards'])) && explode("|", $line['boards']) != "") {
                                  echo 'checked ';
                              }
                              echo '><br>';
                          }
?><br>
                            <input type="submit" value="<?php
                          echo _('Edit word');
?>">
                            </form>
<?php
                      }
                  } else {
                      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['editword']) . "'");
                      if (count($results) > 0) {
                          foreach ($results as $line) {
                              $wordfilter_word = $line['word'];
                          }
                          $wordfilter_boards = array();
                          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
                          foreach ($results as $line) {
                              $wordfilter_boards = array_merge($wordfilter_boards, array($line['name']));
                          }
                          $wordfilter_changed_boards = array();
                          $wordfilter_new_boards = array();
                          while (list($postkey, $postvalue) = each($_POST)) {
                              if (substr($postkey, 0, 10) == "wordfilter") {
                                  $wordfilter_changed_boards = array_merge($wordfilter_changed_boards, array(substr($postkey, 10)));
                              }
                          }
                          while (list(, $wordfilter_thisboard_name) = each($wordfilter_boards)) {
                              if (in_array($wordfilter_thisboard_name, $wordfilter_changed_boards)) {
                                  $wordfilter_new_boards = array_merge($wordfilter_new_boards, array($wordfilter_thisboard_name));
                              }
                          }
                          $tc_db->Execute("UPDATE `wordfilter` SET `replacedby` = '" . mysql_real_escape_string($_POST['replacedby']) . "' , `boards` = '" . mysql_real_escape_string(implode('|', $wordfilter_new_boards)) . "' WHERE `id` = '" . mysql_real_escape_string($_GET['editword']) . "'");
                          echo _('Word successfully updated.');
                          management_addlogentry(_('Updated word on wordfilter') . ': ' . $wordfilter_word, 11);
                      } else {
                          echo _('Unable to locate that word.');
                      }
                  }
              } else {
                  echo _('That ID does not exist.');
              }
              echo '<hr>';
          }
      } else {
?>
            <form action="manage.php?action=wordfilter" method="post">
            <label for="word"><?php
          echo _('Word');
?>:</label><input type="text" name="word"><br>

            <label for="replacedby"><?php
          echo _('Is replaced by');
?>:</label><input type="text" name="replacedby"><br>
            <label><?php
          echo _('Boards');
?>:</label><br>
<?php
          $array_boards = array();
          $resultsboard = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
          foreach ($resultsboard as $lineboard) {
              $array_boards = array_merge($array_boards, array($lineboard['name']));
          }
          make_boardlist_checkbox('wordfilter', $array_boards);
?><br>
            <input type="submit" value="<?php
          echo _('Add word');
?>">
            </form>
            <hr>
<?php
      }
      echo '<br>';
      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "wordfilter`");
      foreach ($results as $line) {
          echo 'Word: ' . $line['word'] . ' - Replaced by: ' . $line['replacedby'] . ' - Boards: ';
          if (explode('|', $line['boards']) != "") {
              echo '<b>/' . implode('/</b>, <b>/', explode('|', $line['boards'])) . '/</b>&nbsp;';
          } else {
              echo _('No boards');
          }
          echo '[<a href="manage.php?action=wordfilter&editword=' . $line['id'] . '">' . _('Edit') . '</a>]&nbsp;[<a href="manage.php?action=wordfilter&delword=' . $line['id'] . '">del</a>]<br>';
      }
  }
  
  function addboard()
  {
      global $tc_db, $board_class, $lang;
      
      management_adminsonly();
      if (isset($_POST['directory'])) {
          if ($_POST['directory'] != "" && $_POST['desc'] != "") {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['directory']) . "'");
              if (count($results) == 0) {
                  if (mkdir(TC_BOARDSDIR . "" . $_POST['directory'], 0777) && mkdir(TC_BOARDSDIR . "" . $_POST['directory'] . "/res", 0777) && mkdir(TC_BOARDSDIR . "" . $_POST['directory'] . "/src", 0777) && mkdir(TC_BOARDSDIR . "" . $_POST['directory'] . "/thumb", 0777)) {
                      file_put_contents(TC_BOARDSDIR . "" . $_POST['directory'] . "/.htaccess", "DirectoryIndex board.html");
                      $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "boards` ( `name` , `desc` , `createdon` ) VALUES ( '" . mysql_real_escape_string($_POST['directory']) . "' , '" . mysql_real_escape_string($_POST['desc']) . "' , '" . time() . "' )");
                      if ($_POST['firstpostid'] < 1) {
                          $_POST['firstpostid'] = 1;
                      }
                      $tc_db->Execute("CREATE TABLE `" . TC_DBPREFIX . "posts_" . mysql_real_escape_string($_POST['directory']) . "` (
                    `id` int(10) NOT NULL auto_increment, 
                    `threadid` int(10) NOT NULL default '0', 
                    `user` varchar(255) NOT NULL, 
                    `tripcode` varchar(10) NOT NULL, 
                    `email` varchar(255) NOT NULL, 
                    `subject` varchar(255) NOT NULL, 
                    `message` text NOT NULL, 
                    `image` varchar(20) NOT NULL, 
                    `imagetype` varchar(5) NOT NULL, 
                    `imagemd5` text NOT NULL, 
                    `image_w` smallint(5) NOT NULL default '0', 
                    `image_h` smallint(5) NOT NULL default '0', 
                    `image_size` int(10) NOT NULL default '0', 
                    `thumb_w` smallint(5) NOT NULL default '0', 
                    `thumb_h` smallint(5) NOT NULL default '0', 
                    `password` varchar(255) NOT NULL, 
                    `postedat` int(20) NOT NULL, 
                    `lastbumped` int(20) NOT NULL default '0', 
                    `ip` varchar(75) NOT NULL, 
                    `ipmd5` varchar(200) NOT NULL, 
                    `stickied` tinyint(1) NOT NULL default '0', 
                    `locked` tinyint(1) NOT NULL default '0', 
                    `posterauthority` tinyint(1) NOT NULL default '0', 
                    `IS_DELETED` tinyint(1) NOT NULL default '0', 
                    UNIQUE KEY `id` (`id`), 
                    KEY `threadid` (`threadid`), 
                    KEY `lastbumped` (`lastbumped`)
                    ) ENGINE=MyISAM AUTO_INCREMENT=" . mysql_real_escape_string($_POST['firstpostid']) . " ;");
                      $board_class = new Board(mysql_real_escape_string($_POST['directory']));
                      $board_class->RegenerateAll();
                      echo _('Board successfully added.') . '<br><br><a href="' . TC_BOARDSPATH . '/' . $_POST['directory'] . '/">/' . $_POST['directory'] . '/</a>!';
                      management_addlogentry(_('Added board') . ': /' . $_POST['directory'] . '/', 3);
                  } else {
                      echo '<br>' . _('Unable to create directories.');
                  }
              } else {
                  echo _('A board with that name already exists.');
              }
          } else {
              echo _('Please fill in all required fields.');
          }
      }
?>
        <form action="manage.php?action=addboard" method="post">

        <label for="directory">Directory:</label><input type="text" name="directory"><div class="desc">The directory of the board.  <b>Only put in the letter(s) of the board directory, no slashes!</b></div><br>
        <label for="desc">Description:</label><input type="text" name="desc"><div class="desc">The name of the board.</div><br>
        <label for="firstpostid">First Post ID:</label><input type="text" name="firstpostid" value="1"><div class="desc">The first post of this board will recieve this ID.</div><br>
        <input type="submit" value="Add Board">
        </form>

<?php
  }
  
  function delboard()
  {
      global $tc_db, $lang;
      
      management_adminsonly();
      if (isset($_POST['directory'])) {
          if ($_POST['directory'] != "") {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['directory']) . "'");
              foreach ($results as $line) {
                  $board_id = $line['id'];
                  $board_dir = $line['name'];
              }
              if (count($results) > 0) {
                  if ($_POST['confirmation'] == 'yes') {
                      if (remove_board($board_dir)) {
                          $tc_db->Execute("DROP TABLE `" . TC_DBPREFIX . "posts_" . $board_dir . "`");
                          $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "boards` WHERE `id` = '" . $board_id . "'");
                          echo 'Board successfully deleted!';
                          management_addlogentry("Deleted board: /" . $_POST['directory'] . "/", 3);
                      } else {
                          //Error
                          echo 'Unable to delete board.';
                      }
                  } else {
                      echo 'Are you absolutely sure you want to delete /' . $board_dir . '/ ?<br>
                        <form action="manage.php?action=delboard" method="post"><input type="hidden" name="directory" value="' . $_POST['directory'] . '"><input type="hidden" name="confirmation" value="yes"><input type="submit" value="Continue"></form>';
                  }
              } else {
                  echo _('A board with that name does not exist.');
              }
          }
          echo '<hr>';
      }
?>
        <form action="manage.php?action=delboard" method="post">
        <label for="directory">Directory:</label><?php
      echo make_boardlist_dropdown('directory', moderator_boardlist($_SESSION['manageusername']));
?><br>
        <input type="submit" value="Delete Board">
        </form>
<?php
  }
  
  function changepwd()
  {
      global $tc_db, $lang;
      if (isset($_POST['oldpwd']) && isset($_POST['newpwd']) && isset($_POST['newpwd2'])) {
          if ($_POST['oldpwd'] != "" && $_POST['newpwd'] != "" && $_POST['newpwd2'] != "") {
              if ($_POST['newpwd'] == $_POST['newpwd2']) {
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "'");
                  foreach ($results as $line) {
                      $staff_passwordenc = $line['password'];
                  }
                  if (md5($_POST['oldpwd']) == $staff_passwordenc) {
                      $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "staff` SET `password` = '" . md5($_POST['newpwd']) . "' WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "'");
                      $_SESSION['managepassword'] = md5($_POST['newpwd']);
                      echo _('Password successfully changed.');
                  } else {
                      echo _('The old password you provided did not match the current one.');
                  }
              } else {
                  echo _('The second password did not match the first.');
              }
          } else {
              echo _('Please fill in all required fields.');
          }
          echo '<hr>';
      }
?>
        <form action="manage.php?action=changepwd" method="post">
        <label for="oldpwd"><?php
      echo _('Old password');
?>:</label><input type="password" name="oldpwd"><br>

        <label for="newpwd"><?php
      echo _('New password');
?>:</label><input type="password" name="newpwd"><br>
        <label for="newpwd2"><?php
      echo _('New password again');
?>:</label><input type="password" name="newpwd2"><br>
        <input type="submit" value="<?php
      echo _('Change account password');
?>">
        </form>
<?php
  }
  
  function staff()
  {
      global $tc_db, $lang;
      management_adminsonly();
      if (isset($_POST['staffusername']) && isset($_POST['staffpassword'])) {
          if ($_POST['staffusername'] != "" && $_POST['staffpassword'] != "") {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_POST['staffusername']) . "'");
              if (count($results) == 0) {
                  if ($_POST['isadmin'] == "on") {
                      $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '" . mysql_real_escape_string($_POST['staffusername']) . "' , '" . md5($_POST['staffpassword']) . "' , '1' , '" . time() . "' )");
                  } else {
                      $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '" . mysql_real_escape_string($_POST['staffusername']) . "' , '" . md5($_POST['staffpassword']) . "' , '0' , '" . time() . "' )");
                  }
                  echo _('Staff member successfully added.');
                  $logentry = _('Added staff member') . ' - ';
                  if ($_POST['isadmin'] == "on") {
                      $logentry .= _('Administrator');
                  } else {
                      $logentry .= _('Moderator');
                  }
                  $logentry .= ": " . $_POST['staffusername'];
                  management_addlogentry($logentry, 6);
              } else {
                  echo _('A staff member with that ID already exists.');
              }
              echo '<hr>';
          }
      } elseif (isset($_GET['del'])) {
          if ($_GET['del'] > 0) {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . mysql_real_escape_string($_GET['del']) . "'");
              if (count($results) > 0) {
                  foreach ($results as $line) {
                      $staff_username = $line['username'];
                  }
                  $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . mysql_real_escape_string($_GET['del']) . "'");
                  echo _('Staff successfully deleted');
                  management_addlogentry(_('Deleted staff member') . ': ' . $staff_username, 6);
              } else {
                  echo _('Invalid staff ID.');
              }
              echo '<hr>';
          }
      } elseif (isset($_GET['edit'])) {
          if ($_GET['edit'] > 0) {
              $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
              if (count($results) > 0) {
                  if (isset($_POST['submitting'])) {
                      foreach ($results as $line) {
                          $staff_username = $line['username'];
                          $staff_isadmin = $line['isadmin'];
                      }
                      $staff_boards = array();
                      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
                      foreach ($results as $line) {
                          $staff_boards = array_merge($staff_boards, array($line['name']));
                      }
                      $staff_changed_boards = array();
                      $staff_new_boards = array();
                      while (list($postkey, $postvalue) = each($_POST)) {
                          if (substr($postkey, 0, 8) == "moderate") {
                              $staff_changed_boards = array_merge($staff_changed_boards, array(substr($postkey, 8)));
                          }
                      }
                      while (list(, $staff_thisboard_name) = each($staff_boards)) {
                          if (in_array($staff_thisboard_name, $staff_changed_boards)) {
                              $staff_new_boards = array_merge($staff_new_boards, array($staff_thisboard_name));
                          }
                      }
                      $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "staff` SET `boards` = '" . mysql_real_escape_string(implode('|', $staff_new_boards)) . "' WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
                      echo _('Staff successfully updated') . '<hr>';
                      $logentry = _('Updated staff member') . ' - ';
                      if ($_POST['isadmin'] == 'on') {
                          $logentry .= _('Administrator');
                      } else {
                          $logentry .= _('Moderator');
                      }
                      $logentry .= ': ' . $staff_username;
                      if ($_POST['isadmin'] != 'on') {
                          $logentry .= ' - ' . _('Moderates') . ': /' . implode('/, /', $staff_new_boards) . '/';
                      }
                      management_addlogentry($logentry, 6);
                  }
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . $_GET['edit'] . "'");
                  foreach ($results as $line) {
                      $staff_username = $line['username'];
                      $staff_isadmin = $line['isadmin'];
                      $staff_boards = explode('|', $line['boards']);
                  }
?>
                    <form action="manage.php?action=staff&edit=<?php
                  echo $_GET['edit'];
?>" method="post">
                    <label for="staffname"><?php
                  echo _('Username');
?>:</label><input type="text" name="staffname" value="<?php
                  echo $staff_username;
?>" disabled><br>

                    <label for="isadmin"><?php
                  echo _('Administrator');
?>?</label><input type="checkbox" name="isadmin" <?php
                  if ($staff_isadmin == "1") {
                      echo 'checked ';
                  }
?>><br><br>
<?php
                  echo _('Moderates') . '<br>';
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards`");
                  foreach ($results as $line) {
                      echo '<label for="moderate' . $line['name'] . '">' . $line['name'] . '</label><input type="checkbox" name="moderate' . $line['name'] . '" ';
                      if (in_array($line['name'], $staff_boards)) {
                          echo 'checked ';
                      }
                      echo '><br>';
                  }
?>
                    <input type="submit" value="<?php
                  echo _('Modify staff member');
?>" name="submitting">
                    </form>
                    <br>
<?php
                  } else
                  {
                      echo _('A staff member with that id does not appear to exist.');
                  }
                  echo '<hr>';
              }
          }
?>
        <form action="manage.php?action=staff" method="post">
        <label for="username"><?php
          echo _('Username');
?>:</label><input type="text" name="staffusername"><br>

        <label for="password"><?php
          echo _('Password');
?>:</label><input type="text" name="staffpassword"><br>
        <label for="isadmin"><?php
          echo _('Administrator');
?>?</label><input type="checkbox" name="isadmin"><br>
        <input type="submit" value="<?php
          echo _('Add staff member');
?>">
        </form>
        <hr><br>
<?php
          echo _('Administrators') . ':<br>';
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "staff` WHERE `isadmin` = '1' ORDER BY `username` ASC");
          if (count($results) > 0) {
              echo '<table border="1"><tr><th>Username</th><th>Added on</th><th>&nbsp;</th></tr>';
              foreach ($results as $line) {
                  echo '<tr>';
                  echo '<td>' . $line['username'] . '</td><td>' . date("y/m/d(D)H:i", $line['addedon']) . '</td><td>[<a href="?action=staff&edit=' . $line['id'] . '">' . _('Edit') . '</a>]&nbsp;[<a href="?action=staff&del=' . $line['id'] . '">x</a>]</td>';
                  echo '</tr>';
              }
              echo '</table>';
          } else {
              echo _('None');
          }
          echo _('Moderators') . ':<br>';
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "staff` WHERE `isadmin` = '0' ORDER BY `username` ASC");
          if (count($results) > 0) {
              echo '<table border="1"><tr><th>' . _('Username') . '</th><th>' . _('Added on') . '</th><th>' . _('Moderating boards') . '</th><th>&nbsp;</th></tr>';
              foreach ($results as $line) {
                  echo '<tr>';
                  echo '<td>' . $line['username'] . '</td><td>' . date("y/m/d(D)H:i", $line['addedon']) . '</td><td>';
                  if ($line['boards'] != "") {
                      echo '<b>/' . implode('/</b>, <b>/', explode('|', $line['boards'])) . '/</b>';
                  } else {
                      echo _('No boards');
                  }
                  echo '</td><td>[<a href="?action=staff&edit=' . $line['id'] . '">' . _('Edit') . '</a>]&nbsp;[<a href="?action=staff&del=' . $line['id'] . '">x</a>]</td>';
                  echo '</tr>';
              }
              echo '</table>';
          } else {
              echo _('None');
          }
      }
      
      function viewdeletedthread()
      {
          global $tc_db, $lang;
          
          management_adminsonly();
          if (isset($_GET['threadid']) && isset($_GET['board'])) {
              if ($_GET['threadid'] > 0) {
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
                  foreach ($results as $line) {
                      $board_id = $line['id'];
                      $board_dir = $line['name'];
                  }
                  if (count($results) > 0) {
                      $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $board_dir . "` WHERE `id` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
                      if (count($results) > 0) {
                          foreach ($results as $line) {
                              $thread_isdeleted = $line['IS_DELETED'];
                              $thread_threadid = $line['threadid'];
                          }
                          if ($thread_isdeleted == '1') {
                              if ($thread_threadid == '0') {
                                  echo '<head><link rel="stylesheet" type="text/css" href="' . TC_BOARDSPATH . '/css/burichan.css" title="Burichan"></head>';
                                  //Devnote:  fix viewing deleted threads
                                  echo 'Broke for now, will return soon.';
                                  //echo buildthread($board_id, $_GET['threadid'], false, true);
                              } else {
                                  echo _('That ID is a reply, not a thread.');
                              }
                          } else {
                              echo _('That thread has yet to be deleted.');
                          }
                      } else {
                          echo _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
                      }
                  }
              }
          } else {
?>
            <form action="?" method="get">

            <input type="hidden" name="action" value="viewdeletedthread">
            <label for="board"><?php
              echo _('Board');
?>:</label><?php
              echo make_boardlist_dropdown('board', moderator_boardlist($_SESSION['manageusername']));
?><br>
            <label for="threadid"><?php
              echo _('Thread') . ' ' . _('');
?>:</label><input type="text" name="threadid"><br>
            <input type="submit" value="<?php
              echo _('View deleted thread');
?>">
            </form>
<?php
          }
      }
      
      function search()
      {
          global $tc_db, $lang;
          management_adminsonly();
          //Devnote: fix searching
          die("Broken for now due to the new table system.");
          if (isset($_GET['query'])) {
              $search_query = $_GET['query'];
              if (isset($_GET['s'])) {
                  $s = $_GET['s'];
              } else {
                  $s = 0;
              }
              $trimmed = trim($search_query);
              $limit = 10;
              if ($trimmed == "") {
                  echo _('Please enter a search query.');
                  exit;
              }
              $query = "SELECT * FROM `" . TC_DBPREFIX . "posts` WHERE `IS_DELETED` = '0' AND `message` LIKE '%" . $trimmed . "%' ORDER BY `postedat` DESC";
              $numresults = $tc_db->GetAll($query);
              $numrows = count($numresults);
              if ($numrows == 0) {
                  echo '<h4>' . _('Results') . '</h4>';
                  echo '<p>' . _('Sorry, your search returned zero results.') . '</p>';
                  die();
              }
              $query .= " LIMIT $s, $limit";
              $results = $tc_db->GetAll($query);
              echo '<p>' . _('You searched for') . ': &quot;' . $search_query . '&quot;</p>';
              echo _('Results') . ':<br><br>';
              $count = 1 + $s;
              foreach ($results as $line) {
                  $board = boardid_to_dir($line['boardid']);
                  echo $count . ". Board: /" . $board . "/, Thread #<a href=\"";
                  if ($line['threadid'] == "0") {
                      echo TC_BOARDSPATH . '/' . $board . '/res/' . $line['id'] . '.html">' . $line['id'] . '</a>';
                  } else {
                      echo TC_BOARDSPATH . '/' . $board . '/res/' . $line['threadid'] . '.html#' . $line['id'] . '">' . $line['threadid'] . '</a>, Post #' . $line['id'];
                  }
                  echo '<fieldset>' . $line['message'] . '</fieldset><br>';
                  $count++;
              }
              $currPage = (($s / $limit) + 1);
              echo "<br>";
              if ($s >= 1) {
                  $prevs = ($s - $limit);
                  print "&nbsp;<a href=\"?action=search&s=$prevs&query=$search_query\">&lt;&lt; 
            Prev 10</a>&nbsp&nbsp;";
              }
              $pages = intval($numrows / $limit);
              if ($numrows % $limit) {
                  $pages++;
              }
              if (!((($s + $limit) / $limit) == $pages) && $pages != 1) {
                  $news = $s + $limit;
                  echo "&nbsp;<a href=\"?action=search&s=$news&query=$search_query\">Next 10 &gt;&gt;</a>";
              }
              
              $a = $s + ($limit);
              if ($a > $numrows) {
                  $a = $numrows;
              }
              $b = $s + 1;
              echo '<p>' . _('Results') . ' ' . $b . ' &gt;&gt; ' . $a . ' of ' . $numrows . '</p>';
              echo '<hr>';
          }
?>
        <form action="?" method="get">

        <input type="hidden" name="action" value="search">
        <input type="hidden" name="s" value="0">
        <label for="query"><?php
          echo _('Query');
?>:</label><input type="text" name="query" value="<?php
          echo $_GET['query'];
?>"><br>
        <input type="submit" value="<?php
          echo _('Search');
?>">
        </form>
<?php
      }
      
      function reports()
      {
          global $tc_db, $lang;

          if (isset($_GET['clear'])) {
              $results = $tc_db->GetAll("SELECT `id` FROM `" . TC_DBPREFIX . "reports` WHERE `id` = '" . mysql_real_escape_string($_GET['clear']) . "' LIMIT 1");
              if (count($results) > 0) {
                  $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "reports` SET `cleared` = '1' WHERE `id` = '" . mysql_real_escape_string($_GET['clear']) . "' LIMIT 1");
                  echo 'Report successfully cleared.<hr>';
              }
          }
          $query = "SELECT " . TC_DBPREFIX . "posts.id, " . TC_DBPREFIX . "posts.threadid, " . TC_DBPREFIX . "posts.image, " . TC_DBPREFIX . "posts.imagetype, " . TC_DBPREFIX . "posts.message, " . TC_DBPREFIX . "boards.name, " . TC_DBPREFIX . "reports.id as reportid, " . TC_DBPREFIX . "reports.ip as reporterip FROM " . sqlboardlist() . " JOIN " . TC_DBPREFIX . "reports ON " . TC_DBPREFIX . "reports.postid = " . TC_DBPREFIX . "posts.id AND " . TC_DBPREFIX . "reports.boardid = " . TC_DBPREFIX . "posts.boardid JOIN " . TC_DBPREFIX . "boards ON " . TC_DBPREFIX . "posts.boardid = " . TC_DBPREFIX . "boards.id WHERE " . TC_DBPREFIX . "posts.IS_DELETED = 0 AND " . TC_DBPREFIX . "reports.cleared = 0";
          if (!management_isadmin()) {
              $boardlist = moderator_boardlist($_SESSION['manageusername']);
              if (!empty($boardlist)) {
                  $query .= ' AND (';
                  foreach ($boardlist as $board) {
                      $query .= ' boards.name = \'' . $board . '\' OR';
                  }
                  $query = substr($query, 0, -3) . ')';
              } else {
                  echo 'You do not moderate any boards :(';
              }
          }
          $results = $tc_db->GetAll($query);
          if (count($results) > 0) {
              echo '<table border="1"><tr><th>Board</th><th>Post</th><th>Picture</th><th>Message</th><th>Reporter IP</th><th>Action</th></tr>';
              foreach ($results as $line) {
                  echo '<tr><td>/' . $line['name'] . '/</td><td><a href="' . TC_BOARDSFOLDER . '' . $line['name'] . '/res/';
                  if ($line['threadid'] == '0') {
                      echo $line['id'];
                      $post_threadorpost = 'thread';
                  } else {
                      echo $line['threadid'];
                      $post_threadorpost = 'post';
                  }
                  echo '.html#' . $line['id'] . '">' . $line['id'] . '</a></td><td>';
                  if ($line['image'] == 'removed') {
                      echo 'removed';
                  } elseif ($line['image'] == '') {
                      echo 'none';
                  } else {
                      echo '<a href="' . TC_BOARDSFOLDER . '' . $line['name'] . '/src/' . $line['image'] . '.' . $line['imagetype'] . '"><img src="' . TC_BOARDSFOLDER . '' . $line['name'] . '/thumb/' . $line['image'] . 's.' . $line['imagetype'] . '" border="0"></a>';
                  }
                  echo '</td><td>';
                  if ($line['message'] != '') {
                      echo stripslashes($line['message']);
                  } else {
                      echo '&nbsp;';
                  }
                  echo '</td><td>' . md5_decrypt($line['reporterip'], TC_RANDOMSEED) . '</td><td><a href="?action=reports&clear=' . $line['reportid'] . '">Clear</a>&nbsp;&#91;<a href="?action=delposts&boarddir=' . $line['name'] . '&del' . $post_threadorpost . 'id=' . $line['id'] . '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this thread/post?\');">D</a>&nbsp;<a href="' . TC_BOARDSFOLDER . 'manage.php?action=delposts&boarddir=' . $line['name'] . '&del' . $post_threadorpost . 'id=' . $line['id'] . '&postid=' . $line['id'] . '" title="Delete &amp; Ban" onclick="return confirm(\'Are you sure you want to delete and ban this poster?\');">&amp;</a>&nbsp;<a href="?action=bans&banboard=' . $line['name'] . '&banpost=' . $line['id'] . '" title="Ban">B</a>&#93;</td></tr>';
              }
              echo '</table>';
          } else {
              echo 'No reports to show.';
          }
      }
      
      function misc()
      {
          global $tc_db, $lang;
          echo '<a href="?action=rebuildall">' . _('Rebuild all boards and html files') . '</a><br><a href="?action=viewdeletedthread">' . _('View deleted thread') . '</a><br><a href="?action=cleanup">' . _('Cleanup') . '</a><br><a href="?action=search">' . _('Search posts') . '</a><br><a href="?action=staff">' . _('Staff') . '</a><br><a href="?action=modlog">' . _('ModLog') . '</a><br><a href="?action=editfiletypes">' . 'Edit filetypes' . '</a><br><a href="?action=editsections">' . 'Edit sections' . '</a><br><a href="?action=sql">' . _('SQL query') . '</a>';
          if (TC_DBUSECACHE) {
              echo '<br><a href="?action=clearcache">Clear query cache</a>';
          }
      }
      
      function posting_rates()
      {
          global $tc_db, $lang;
          echo '<h3>' . _('Posting rates (past hour)') . '</h3><br>';
          $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "boards` ORDER BY `order` ASC");
          if (count($results) > 0) {
              echo '<table border="1" cellspacing="2" cellpadding="2"><tr><th>' . _('Board') . '</th><th>' . _('Threads') . '</th><th>' . _('Replies') . '</th><th>' . _('Posts') . '</th></tr>';
              foreach ($results as $line) {
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $line['name'] . "` WHERE `threadid` = 0 AND `postedat` >= " . (time() - 3600) . "");
                  $rows_threads = count($results);
                  $results = $tc_db->GetAll("SELECT * FROM `" . TC_DBPREFIX . "posts_" . $line['name'] . "` WHERE `threadid` != 0 AND `postedat` >= " . (time() - 3600) . "");
                  $rows_replies = count($results);
                  $rows_posts = $rows_threads + $rows_replies;
                  $threads_perminute = $rows_threads;
                  $replies_perminute = $rows_replies;
                  $posts_perminute = $rows_posts;
                  echo '<tr><td><b>' . $line['name'] . '</b></td><td>' . $threads_perminute . '</td><td>' . $replies_perminute . '</td><td>' . $posts_perminute . '</td></tr>';
              }
              echo '</table>';
          } else {
              echo _('No boards');
          }
      }
      
      /* Secondary functions */
      
      function management_adminsonly()
      {
          global $tc_db;
          if (!management_isadmin()) {
              die('That page is for admins only.');
          }
      }
      
      function management_isadmin()
      {
          global $tc_db;
          $results = $tc_db->GetAll("SELECT `isadmin` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . $_SESSION['manageusername'] . "' AND `password` = '" . $_SESSION['managepassword'] . "' LIMIT 1");
          foreach ($results as $line) {
              if ($line['isadmin'] == 1) {
                  return true;
              } else {
                  return false;
              }
          }
          session_destroy();
          die('Invalid session, please log in again.');
      }
      
      function management_addlogentry($entry, $category = 0)
      {
          // Categories
          // 0 - no category
          // 1 - login
          // 2 - cleanup/rebuild boards and html files
          // 3 - board adding/deleting
          // 4 - board updates
          // 5 - locking/stickying
          // 6 - staff changes
          // 7 - thread deletion/post deletion
          // 8 - bans
          // 9 - news
          // 10 - global changes
          // 11 - wordfilter
          global $tc_db;
          if ($entry != '') {
              $tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "modlog` ( `entry` , `user` , `category` , `timestamp` ) VALUES ( '" . mysql_real_escape_string($entry) . "' , '" . $_SESSION['manageusername'] . "' , '" . mysql_real_escape_string($category) . "' , '" . time() . "' )");
          }
      }
      
      function moderator_ismodofboard($board, $username)
      {
          global $tc_db;
          $results = $tc_db->GetAll("SELECT `isadmin`, `boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . $username . "' LIMIT 1");
          if (count($results) > 0) {
              foreach ($results as $line) {
                  if ($line['isadmin'] == "1") {
                      return true;
                  } else {
                      $array_boards = explode('|', $line['boards']);
                      if (in_array($board, $array_boards)) {
                          return true;
                      } else {

                          return false;
                      }
                  }
              }
          } else {
              return false;
          }
      }
      
      function moderator_boardlist($username)
      {
          global $tc_db;
          $staff_boardsmoderated = array();
          $results = $tc_db->GetAll("SELECT `boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . $username . "' LIMIT 1");
          if (count($results) > 0) {
              if (management_isadmin()) {
                  $resultsboard = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards`");
                  foreach ($resultsboard as $lineboard) {
                      $staff_boardsmoderated = array_merge($staff_boardsmoderated, array($lineboard['name']));
                  }
              } else {
                  foreach ($results as $line) {
                      $array_boards = explode('|', $line['boards']);
                  }
                  foreach ($array_boards as $this_board_name) {
                      $staff_boardsmoderated = array_merge($staff_boardsmoderated, array($this_board_name));
                  }
              }
          }
          return $staff_boardsmoderated;
      }
      
      function sqlboardlist()
      {
          global $tc_db;
          $results = $tc_db->GetAll("SELECT `name` FROM `" . TC_DBPREFIX . "boards` ORDER BY `name` ASC");
          $sqlboards = '';
          foreach ($results as $lineboard) {
              $sqlboards .= 'posts_' . $line['name'] . ', ';
          }
          return substr($sqlboards, 0, -2);
      }
      
      function make_boardlist_dropdown($name, $boards)
      {
          echo '<select name="' . $name . '"><option value="">Select a Board</option>';
          if ($boards != '') {
              foreach ($boards as $board) {
                  echo '<option value="' . $board . '">/' . $board . '/</option>';
              }
          }
          echo '</select>';
      }
      
      function make_boardlist_checkbox($prefix, $boards)
      {
          if ($boards != '') {
              foreach ($boards as $board) {
                  echo '<label for="' . $prefix . $board . '">' . $board . '</label><input type="checkbox" name="' . $prefix . $board . '"> ';
              }
          }
      }
      
      function delunusedimages($verbose = false)
      {
          global $tc_db;
          $resultsboard = $tc_db->GetAll("SELECT `id`, `name` FROM `" . TC_DBPREFIX . "boards`");
          foreach ($resultsboard as $lineboard) {
              $imagelist = array();
              $imagethumbnaillist = array();
              $results = $tc_db->GetAll("SELECT `image`, `imagetype` FROM `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` WHERE `IS_DELETED` = 0");
              foreach ($results as $line) {
                  $imagelist = array_merge($imagelist, array($line['image'] . '.' . $line['imagetype']));
                  $imagethumbnaillist = array_merge($imagethumbnaillist, array($line['image'] . 's.' . $line['imagetype']));
              }
              $dir = './' . $lineboard['name'] . '/src';
              $files = glob("$dir/{*.jpg, *.png, *.gif}", GLOB_BRACE);
              if (is_array($files)) {
                  foreach ($files as $image) {
                      if (in_array(basename($image), $imagelist) == false) {
                          if (time() - filemtime(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image)) > 120) {
                              if ($verbose == true) {
                                  echo $image . ' NOT IN DATABASE! Deleted.<br>';
                              }
                              unlink(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image));
                          }
                      }
                  }
              }
              $dir = './' . $lineboard['name'] . '/thumb';
              $files = glob("$dir/{*.jpg, *.png, *.gif}", GLOB_BRACE);
              if (is_array($files)) {
                  foreach ($files as $image) {
                      if (in_array(basename($image), $imagethumbnaillist) == false) {
                          if (time() - filemtime(TC_BOARDSDIR . $lineboard['name'] . '/thumb/' . basename($image)) > 120) {
                              if ($verbose == true) {
                                  echo $image . ' (thumb) NOT IN DATABASE! Deleted.<br>';
                              }
                              unlink(TC_BOARDSDIR . $lineboard['name'] . '/thumb/' . basename($image));
                          }
                      }
                  }
              }
          }
          return true;
      }
      
      function delunusedimages_beta($verbose = false)
      {
          global $tc_db;
          $resultsboard = $tc_db->GetAll("SELECT `id`, `name` FROM `" . TC_DBPREFIX . "boards`");
          foreach ($resultsboard as $lineboard) {
              if ($verbose) {
                  echo '<b>Looking for unused images in /' . $lineboard['name'] . '/</b><br>';
              }
              $imagemd5list = array();
              $results = $tc_db->GetAll("SELECT `imagemd5` FROM `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` WHERE `IS_DELETED` = 0 AND `image` != '' AND `image` != 'removed' AND `imagemd5` != ''");
              foreach ($results as $line) {
                  $imagemd5list[] = $line['imagemd5'];
              }
              $dir = './' . $lineboard['name'] . '/src';
              $files = glob("$dir/{*.jpg, *.png, *.gif, *.swf}", GLOB_BRACE);
              if (is_array($files)) {
                  foreach ($files as $image) {
                      if (in_array(md5_file(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image)), $imagemd5list) == false) {
                          if (time() - filemtime(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image)) > 120) {
                              if ($verbose == true) {
                                  echo $image . ' NOT IN DATABASE! Deleted.<br>';
                              }
                              unlink(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image));
                              unlink(TC_BOARDSDIR . $lineboard['name'] . '/thumb/' . substr(basename($image), 0, -4) . 's' . substr(basename($image), strlen(basename($image)) - 4));
                          }
                      }
                  }
              }
          }
          return true;
      }
      
      function delorphanreplies($verbose = false)
      {
          global $tc_db;
          $resultsboard = $tc_db->GetAll("SELECT `id`, `name` FROM `" . TC_DBPREFIX . "boards`");
          foreach ($resultsboard as $lineboard) {
              if ($verbose) {
                  echo '<b>Looking for orphans in /' . $lineboard['name'] . '/</b><br>';
              }
              $results = $tc_db->GetAll("SELECT `id`, `threadid` FROM `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` WHERE `threadid` != '0' AND `IS_DELETED` = 0");
              foreach ($results as $line) {
                  $exists_rows = $tc_db->GetAll("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` WHERE `id` = '" . $line['threadid'] . "' AND `IS_DELETED` = 0", 1);
                  if ($exists_rows[0] == 0) {
                      $tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` SET `IS_DELETED` = '1' WHERE `id` = '" . $line['id'] . "' LIMIT 1");
                      if ($verbose) {
                          echo 'Reply #' . $line['id'] . '\'s thread (#' . $line['threadid'] . ') does not exist!  It has been deleted.<br>';
                      }
                  }
              }
          }
          return true;
      }
?>