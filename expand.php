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
 */
/** 
 * AJAX thread expansion handler
 *
 * Returns replies of threads which have been requested through AJAX
 * 
 * @package Trevorchan  
 */ 

require 'config.php';
/* No need to waste effort if expansion is disabled */
if (!TC_EXPAND) die();
require TC_ROOTDIR . 'inc/functions.php';
require TC_ROOTDIR . 'inc/classes/board-post.class.php';

$board_name = $tc_db->GetOne("SELECT `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
if ($board_name != '') {
	$board_class = new Board($board_name);
} else {
	die('<font color="red">Invalid board.</font>');
}

$results = $tc_db->GetAll('SELECT * FROM `'.TC_DBPREFIX.'posts_'.$board_class->board_dir.'` WHERE `IS_DELETED` = 0 AND `parentid` = '.mysql_real_escape_string($_GET['threadid']).' ORDER BY `id` ASC');

$output = '';
foreach($results AS $line_reply) {
	$output .= $board_class->BuildPost(true, $board_class->board_dir, $board_class->board_type, $line_reply);
}

echo $output;

?>