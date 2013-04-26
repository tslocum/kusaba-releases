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
 * AJAX thread expansion handler
 * +------------------------------------------------------------------------------+
 * Returns replies of threads which have been requested through AJAX
 * +------------------------------------------------------------------------------+
 */

require('config.php');
/* No need to waste effort if expansion is disabled */
if (!TC_EXPAND) die();
require(TC_ROOTDIR . 'inc/functions.php');
require_once(TC_ROOTDIR . 'inc/operations.functions.php');
require_once(TC_ROOTDIR . 'inc/classes/board-post.class.php');

$board_name = $tc_db->GetOne("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
if ($board_name != '') {
	$board_class = new Board($board_name);
} else {
	die('<font color="red">Invalid board.</font>');
}

$results = $tc_db->GetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$board_class->board_dir.'` WHERE `IS_DELETED` = 0 AND `threadid` = '.mysql_real_escape_string($_GET['threadid']).' ORDER BY `id` ASC');

$output = '';
foreach($results AS $line_reply) {
	$output .= $board_class->BuildPost($page, $board_class->board_dir, $board_class->board_type, $line_reply['id'], $line_reply['threadid'], $line_reply['user'], $line_reply['tripcode'], $line_reply['email'], $line_reply['subject'], $line_reply['message'], $line_reply['image'], $line_reply['imagetype'], $line_reply['image_w'], $line_reply['image_h'], $line_reply['image_size'], $line_reply['thumb_w'], $line_reply['thumb_h'], $line_reply['postedat'], $line_reply['stickied'], $line_reply['locked'], $line_reply['posterauthority']);
}

echo $output;

?>