<?php
header('Content-type: text/html; charset=utf-8');
if (KU_TRADITIONALREAD) {
	$pairs = explode('/', $_SERVER['PATH_INFO']);
	if (count($pairs) < 4) {
		die();
	}
	
	$board  = $pairs[1];
	$thread = $pairs[2];
	$posts  = $pairs[3];
} else {
	if (!isset($_GET['b']) || !isset($_GET['t']) || !isset($_GET['p'])) {
		die();
	}
	
	$board  = $_GET['b'];
	$thread = $_GET['t'];
	$posts  = $_GET['p'];
}

if ($board == '' || $thread == '' || $posts == '') {
	die();
}

require 'config.php';
require KU_ROOTDIR . 'inc/functions.php';
require KU_ROOTDIR . 'inc/classes/board-post.class.php';

$postids = getQuoteIds($posts);

if (count($postids) == 0) {
	die('No valid posts specified.');
}

$results = $tc_db->GetOne("SELECT COUNT(*) FROM `".KU_DBPREFIX."boards` WHERE `name` = '".mysql_real_escape_string($board)."' LIMIT 1");
if ($results == 0) {
	die('Invalid board.');
}

$postidquery = '';
foreach ($postids as $postid) {
	if ($postid == $thread) {
		$postidquery .= "(`parentid` = 0 AND ";
	} else {
		$postidquery .= "(`parentid` = '" . mysql_real_escape_string($thread) . "' AND ";
	}
	$postidquery .= "`id` = '" . mysql_real_escape_string($postid) . "') OR ";
}
$postidquery = substr($postidquery, 0, -4);

$executiontime_start = microtime_float();
$board_class = new Board($board);

if ($board_class->board_type == 1) {
	$noboardlist = true;
	$hide_extra = true;
} else {
	$noboardlist = false;
	$hide_extra = false;
}

$board_class->InitializeSmarty();
$board_class->CachePageHeaderData();
$page = $board_class->PageHeader($thread, 0, -1, -1, false, true);
$page .= threadLinks('return', $thread, $board_class->board_dir, $board_class->board_type, false, false, true, true);

if ($board_class->board_type == 1) {
	$page .= '<form id="delform" action="http://cgi.kusaba.org/board.php" method="post">' . "\n";
	
	$relative_id = 0;
	$results = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `id` = '" . mysql_real_escape_string($thread) . "' OR `parentid` = '" . mysql_real_escape_string($thread) . "' ORDER BY `id` ASC");
	foreach ($results as $line) {
		$relative_id++;
		
		if (in_array($relative_id, $postids)) {
			$page .= $board_class->BuildPost(false, $board_class->board_dir, $board_class->board_type, $line, 0, 0, $relative_id);
		}
	}
	
	$page .= '</form>';
} else {
	$page .= '<br>' . "\n";
	
	$results = $tc_db->GetAll("SELECT * FROM `" . KU_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE (" . $postidquery . ")");
	foreach ($results as $line) {
		$page .= $board_class->BuildPost(false, $board_class->board_dir, $board_class->board_type, $line);
	}
	
	$page .= '<br clear="left">' . "\n";
}

$page .= '<hr>' . "\n" .
$board_class->Footer($noboardlist, (microtime_float() - $executiontime_start), $hide_extra);

$board_class->PrintPage('', $page, true);
?>