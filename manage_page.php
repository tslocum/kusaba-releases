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
session_set_cookie_params(60 * 60 * 24 * 100); /* 100 Days */
session_start();

require('config.php');
require(TC_ROOTDIR.'lib/smarty.php');
require_once(TC_ROOTDIR . 'inc/functions.php');
require_once(TC_ROOTDIR . 'inc/operations.functions.php');
require_once(TC_ROOTDIR . 'inc/classes/manage.class.php');
require_once(TC_ROOTDIR . 'inc/classes/board-post.class.php');
require_once(TC_ROOTDIR . 'inc/classes/bans.class.php');
require_once(TC_ROOTDIR . 'inc/encryption.php');

$smarty->assign('lang_manageboards', _gettext('Manage boards'));

$manage_class = new Manage();
$bans_class = new Bans();

/* Do these tasks each time manage.php is loaded */
/* Doesn't matter if this is run by a non-mod */
$bans_class->RemoveExpiredBans();
/* Doesn't matter if this is run by a non-mod */
delete_accepted_reports();
/* Does nothing if the user isn't logged in */
$manage_class->SetModerationCookies();
  
/* Decide what needs to be done */
$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : 'posting_rates';
switch ($action) {
    case 'logout':
        $manage_class->Logout();
        break;
    case 'showlogin':
        $manage_class->LoginForm();
        break;
    case 'login':
        $manage_class->CheckLogin();
        /* Halts execution if not validated */
        $manage_class->ValidateSession();
        manage_page();
        break;
    default:
        /* Halts execution if not validated */
        $manage_class->ValidateSession();
        manage_page($action);
        break;
}
  
/* Show a particular manage function */
function manage_page($action = 'posting_rates') {
    global $manage_class, $tpl_page;
    
    $manage_class->Header();
    
    if (is_callable(array($manage_class, $action))) {
        $manage_class->$action();
    } else {
        $tpl_page .= sprintf(_gettext('%s not implemented.'), $action);
    }
    
    $manage_class->Footer();
}
  
/* Deletes reports that have the reported post has been deleted */
function delete_accepted_reports() {
    /*    global $tc_db;
    $results = $tc_db->GetAll("SELECT ".TC_DBPREFIX."reports.id FROM ".TC_DBPREFIX."reports JOIN ".TC_DBPREFIX."posts ON ".TC_DBPREFIX."reports.postid = ".TC_DBPREFIX."posts.id AND ".TC_DBPREFIX."reports.boardid = ".TC_DBPREFIX."posts.boardid WHERE ".TC_DBPREFIX."posts.IS_DELETED = 1");
    if (count($results)>0) {
    foreach($results AS $line) {
    $tc_db->Execute("DELETE FROM ".TC_DBPREFIX."reports WHERE id = ".$line['id']."");
    }
    }*/
}
//Devnote:  Broken, needs to be fixed

/* Check if a tab is currently open */
function pagetaken_check($pagename) {
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

?>
