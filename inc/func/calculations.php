<?php
function getQuoteIds($quote, $replies = false) {
	$rangeids_validated = array();
	if (strpos($quote, ',') !== false) {
		$postids = split(',', $quote);
	} else {
		$postids = array($quote);
	}
	
	foreach ($postids as $postid) {
		if (strpos($postid, 'l') === 0 && $replies !== false) {
			if (strlen($postid) > 1) {
				$last_posts_to_fetch = substr($postid, 1);
				if ($last_posts_to_fetch >= 1) {
					$last_posts_to_fetch = min($last_posts_to_fetch, $replies);
					$min_posts_to_fetch = max((($replies + 1) - $last_posts_to_fetch), 1);
					if ($min_posts_to_fetch > 1) {
						$min_posts_to_fetch++;
					}
					
					$lastposts = range($min_posts_to_fetch, ($replies + 1));
					
					$key = array_search($postid, $postids, true);
					array_insert($postids, $key, $lastposts);
					
					$key = array_search($postid, $postids, true);
					if ($key !== false) {
						unset($postids[$key]);
					}
				}
			}
		}
		
		if (strpos($postid, '-') !== false) {
			$rangeids = split('-', $postid);
			if (count($rangeids) == 2) {
				if ($rangeids[0] >= 0 && $rangeids[1] >= 0) {
					if ($rangeids[1] == '' && $replies !== false) {
						$range_processed = range($rangeids[0], ($replies + 1));
					} else {
						$range_processed = range($rangeids[0], $rangeids[1]);
					}
					foreach ($range_processed as $range) {
						$rangeids_validated[] = $range;
					}
				}
			}
			
			if (count($rangeids_validated) > 0) {
				$key = array_search($postid, $postids, true);
				array_insert($postids, $key, $rangeids_validated);
			}
			
			$key = array_search($postid, $postids, true);
			if ($key !== false) {
				unset($postids[$key]);
			}
		}
		
		if (strpos($postid, 'r') === 0 && $replies !== false) {
			if (strlen($postid) > 1) {
				$random_posts_to_fetch = substr($postid, 1);
				if ($random_posts_to_fetch >= 1) {
					$randposts = array();
					//$random_posts_to_fetch = min($random_posts_to_fetch, $replies);
					for ($i=0;$i<$random_posts_to_fetch;$i++) {
						$postinserted = false;
						
						while (!$postinserted) {
							$randpost = rand(1, $replies);
							//if (!in_array($randpost, $randposts)) {
								$randposts[] = $randpost;
								$postinserted = true;
							//}
						}
					}
					
					$key = array_search($postid, $postids, true);
					array_insert($postids, $key, $randposts);
					
					$key = array_search($postid, $postids, true);
					if ($key !== false) {
						unset($postids[$key]);
					}
				}
			}
		}
	}
	
	return $postids;
}

function array_insert(&$array, $position, $insert_array) {
    if (!is_int($position)) {
        $i = 0;
        foreach ($array as $key => $value) {
            if ($key == $position) {
                $position = $i;
                break;
            }
            $i++;
        }
    }
    $first_array = array_splice($array, 0, $position);
    $array = array_merge($first_array, $insert_array, $array);
}

function calculateGlobalPostingRate($board) {
	global $tc_db;
	
	$posts = $tc_db->GetAll("SELECT `postedat` FROM `" . KU_DBPREFIX . "posts_" . $board . "` WHERE `postedat` > " . (time() - 604800) . " ORDER BY `id` ASC");
	if (count($posts) > 0) {
		$i = 0;
		$lastpost_time = 0;
		$times = array();
		foreach ($posts as $post) {
			$i++;
			if ($i > 1) {
				$times[] = ($post['postedat'] - $lastpost_time);
			}
			$lastpost_time = $post['postedat'];
		}
		
		$times_sum = array_sum($times);
		if ($times_sum > 0) {
			$times_sum = ($times_sum / 60);
			$times_avg = ($times_sum / count($times));
		} else {
			$times_avg = 0;
		}
	} else {
		$times_avg = 0;
	}
	
	if ($times_avg > 0) {
		return $times_avg;
	} else {
		return 0;
	}
}

function calculateThreadLifespan($id, $threadpage, $threadposition, $board, $board_maxpages, $board_maxage) {
	global $tc_db;
	
	return calculateGlobalPostingRate($board);
}

function cleanBoardName($board) {
	return trim(str_replace('/', '', str_replace('|', '', str_replace(' ', '', $board))));
}

/**
 * Convert a board ID to a board name
 * 
 * @param integer $boardid Board ID
 * @return string Board directory
 */    
function boardid_to_dir($boardid) {
	global $tc_db;
	
	$query = "SELECT `name` FROM `".KU_DBPREFIX."boards` WHERE `id` = '".mysql_real_escape_string($boardid)."'";
	$results = $tc_db->SelectLimit($query, 1);
	if (count($results)>0) {
		foreach($results AS $line) {
			return $line['name'];
		}
	}
}

/**
 * Calculate the number of pages which will be needed for the supplied number of posts
 * 
 * @param integer $boardtype Board type
 * @param integer $numposts Number of posts
 * @return integer Number of pages required
 */      
function calculatenumpages($boardtype, $numposts) {
	if ($boardtype==1) {
		return (floor($numposts/KU_THREADSTXT));
	} else {
		return (floor($numposts/KU_THREADS));
	}
}
?>