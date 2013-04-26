<?php
/**
 * Format the display of the name and tripcode of a post
 * 
 * @param string $name Name used in post
 * @param string $email Email used in post
 * @param string $tripcode Tripcode used in post
 * @return string Generated name and tripcode html
 */  
function formatNameAndTrip($name, $email, $tripcode, $anonymous = 'Anonymous') {
	$output = '<span class="postername">';
			
	if ($email != '') {
		$output .= '<a href="mailto:' . $email . '">';
	}
	
	if ($name == '' && $tripcode == '') {
		$output .= $anonymous;
	} else if ($name == '' && $tripcode != '') {
		/* Just display the tripcode, no added html */
	} else {
		$output .= $name;
	}
	
	if ($email != '') {
		$output .= '</a>';
	}
	
	$output .= '</span>';
	
	if ($tripcode != '') {
		$output .= '<span class="postertrip">!' . $tripcode . '</span>';
	}
	
	$output .= "\n";
		
	return $output;
}

function formatDate($timestamp, $type = 'post', $locale = 'en') {
	if ($type == 'post') {
		if ($locale == 'ja') {
			/* Format the timestamp japanese style */
			$fulldate = strftime("%Y年%m月%d日(DAYOFWEEK)%H時%M分", $timestamp);
			$dayofweek = strftime("%a", $timestamp);
			
			/* I don't like this method, but I can't rely on PHP's locale settings to do it for me... */
			switch ($dayofweek) {
			case 'Sun':
				$dayofweek = '日';
				break;
				
			case 'Mon':
				$dayofweek = '月';
				break;
				
			case 'Tue':
				$dayofweek = '火';
				break;
				
			case 'Wed':
				$dayofweek = '水';
				break;
				
			case 'Thu':
				$dayofweek = '木';
				break;
				
			case 'Fri':
				$dayofweek = '金';
				break;
				
			case 'Sat':
				$dayofweek = '土';
				break;
				
			default:
				break; /* Leave it as the english term */
				
			}
			
			$fulldate = str_replace('DAYOFWEEK', $dayofweek, $fulldate);
			return $fulldate;
		} else {
			/* Format the timestamp english style */
			return date("y/m/d(D)H:i", $timestamp);
		}
	}
	
	return date("y/m/d(D)H:i", $timestamp);
}

/**
 * Calculate the different name and tripcode for the name field provided
 *
 * @param string $post_name Text entered in the Name field 
 * @return array Name and tripcode
 */ 
function calculateNameAndTripcode($post_name) {
	global $tc_db;
	
	if(ereg("(#|!)(.*)", $post_name, $regs)){
		$cap = $regs[2];
		$cap_full = '#' . $regs[2];
		
		// {{{ Special tripcode check
		
		$trips = unserialize(KU_TRIPS);
		if (count($trips) > 0) {
			if (isset($trips[$cap_full])) {
				$forcedtrip = $trips[$cap_full];
				return array(ereg_replace("(#)(.*)", "", $post_name), $forcedtrip);
			}
		}
		
		// }}}
		
		if (function_exists('mb_convert_encoding')) {
			$recoded_cap = mb_convert_encoding($cap, 'SJIS', 'UTF-8');
			if ($recoded_cap != '') {
				$cap = $recoded_cap;
			}
		}
		
		if (strpos($post_name, '#') === false) {
			$cap_delimiter = '!';
		} elseif (strpos($post_name, '!') === false) {
			$cap_delimiter = '#';
		} else {
			$cap_delimiter = (strpos($post_name, '#') < strpos($post_name, '!')) ? '#' : '!';
		}
		
		if (ereg("(.*)(" . $cap_delimiter . ")(.*)", $cap, $regs_secure)) {
			$cap = $regs_secure[1];
			$cap_secure = $regs_secure[3];
			$is_secure_trip = true;
		} else {
			$is_secure_trip = false;
		}
		
		$tripcode = '';
		if ($cap != '') {
			/* From Futabally */
			$cap = strtr($cap, "&amp;", "&");
			$cap = strtr($cap, "&#44;", ", ");
			$salt = substr($cap."H.", 1, 2);
			$salt = ereg_replace("[^\.-z]", ".", $salt);
			$salt = strtr($salt, ":;<=>?@[\\]^_`", "ABCDEFGabcdef"); 
			$tripcode = substr(crypt($cap, $salt), -10);
		}
		
		if ($is_secure_trip) {
			if ($cap != '') {
				$tripcode .= '!';
			}
			
			$secure_tripcode = md5($cap_secure . KU_RANDOMSEED);
			if (function_exists('base64_encode')) {
				$secure_tripcode = base64_encode($secure_tripcode);
			}
			if (function_exists('str_rot13')) {
				$secure_tripcode = str_rot13($secure_tripcode);
			}
			
			$secure_tripcode = substr($secure_tripcode, 2, 10);
			
			$tripcode .= '!' . $secure_tripcode;
		}
		
		$name = ereg_replace("(" . $cap_delimiter . ")(.*)", "", $post_name);
		
		$tc_db->Execute("INSERT INTO `".KU_DBPREFIX."passcache` ( `md5` , `name` , `tripcode` ) VALUES ( '" . md5($post_name) . "' , '" . $name . "' , '" . $tripcode . "' )");
		
		return array($name, $tripcode);
	}
	
	return $post_name;
}

/**
 * Format a long message to be shortened if it exceeds the allowed length on a page
 * 
 * @param string $message Post message
 * @param string $board Board directory
 * @param integer $threadid Thread ID
 * @param boolean $page Is rendering for a page 
 * @return string The formatted message
 */ 
function formatLongMessage($message, $board, $threadid, $page) {
	$output = '';
	if ((strlen($message) > KU_LINELENGTH || count(explode('<br>', $message)) > 15) && $page) {
		$message_exploded = explode('<br>', $message);
		$message_shortened = '';
		for ($i = 0; $i <= 14; $i++) {
			if (isset($message_exploded[$i])) {
				$message_shortened .= $message_exploded[$i] . '<br>';
			}
		}
		if (strlen($message_shortened) > KU_LINELENGTH) {
			$message_shortened = substr($message_shortened, 0, KU_LINELENGTH);
		}
		$message_shortened = closeOpenTags($message_shortened);
		
		$output = $message_shortened . '<div class="abbrev">' . "\n" .
		'	' . sprintf(_gettext('Message too long. Click %shere%s to view the full text.'), '<a href="' . KU_BOARDSFOLDER . $board . '/res/' . $threadid . '.html">', '</a>') . "\n" .
		'</div>' . "\n";
	} else {
		$output .= $message . "\n";
	}
	
	return $output;
}

/* Thanks milianw - php.net */
/**
 * Closes all HTML tags left open
 *
 * @param string $html HTML to be checked
 * @return string HTML with all tags closed 
 */ 
function closeOpenTags($html){
	/* Put all opened tags into an array */
	preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $result);
	$openedtags=$result[1];

	/* Put all closed tags into an array */
	preg_match_all("#</([a-z]+)>#iU", $html, $result);
	$closedtags=$result[1];
	$len_opened = count($openedtags);
	/* All tags are closed */
	if(count($closedtags) == $len_opened){
		return $html;
	}
	$openedtags = array_reverse($openedtags);
	/* Close tags */
	for($i=0;$i<$len_opened;$i++) {
		if ($openedtags[$i]!='br') {
			if (!in_array($openedtags[$i], $closedtags)){
				$html .= '</'.$openedtags[$i].'>';
			} else {
				unset($closedtags[array_search($openedtags[$i], $closedtags)]);
			}
		}
	}
	return $html;
}

/* By Darien Hager, Jan 2007 */
/**
 * Find the string value of a pair of ords
 *
 * @param string $ords Ords
 * @param string $encoding Encoding  
 * @return string String
 */ 
function ords_to_unistr($ords, $encoding = 'UTF-8'){
	if (!function_exists('mb_convert_encoding')) {
		return false;
	}
	/*  Turns an array of ordinal values into a string of unicode characters */
	$str = '';
	for($i = 0; $i < sizeof($ords); $i++){
		/* Pack this number into a 4-byte string
		(Or multiple one-byte strings, depending on context.) */
		$v = $ords[$i];
		$str .= pack("N",$v);
	}
	$str = mb_convert_encoding($str,$encoding,"UCS-4BE");
	return($str);           
}

/**
 * Find the ord value of a string
 *
 * @param string $str String
 * @param string $encoding Encoding  
 * @return array Ords
 */ 
function unistr_to_ords($str, $encoding = 'UTF-8'){
	if (!function_exists('mb_convert_encoding')) {
		return false;
	}
	/* Turns a string of unicode characters into an array of ordinal values,
	Even if some of those characters are multibyte. */
	$str = mb_convert_encoding($str,"UCS-4BE",$encoding);
	$ords = array();

	/* Visit each unicode character */
	for($i = 0; $i < mb_strlen($str,"UCS-4BE"); $i++){       
		/* Now we have 4 bytes. Find their total numeric value */
		$s2 = mb_substr($str,$i,1,"UCS-4BE");                   
		$val = unpack("N",$s2);           
		$ords[] = $val[1];               
	}       
	return($ords);
}

function processPost($id, $newthreadid, $oldthreadid) {
	global $tc_db, $board_from, $board_to;
	
	$message = $tc_db->GetOne("SELECT `message` FROM " . KU_DBPREFIX . "posts_" . $board_to . " WHERE `id` = " . $id . " LIMIT 1");
	
	if ($message != '') {
		$message_new = str_replace('/read.php/' . $board_from . '/' . $oldthreadid, '/read.php/' . $board_to . '/' . $newthreadid, $message);
		
		if ($message_new != $message) {
			$tc_db->GetOne("UPDATE " . KU_DBPREFIX . "posts_" . $board_to . " SET `message` = '" . mysql_real_escape_string($message) . "' WHERE `id` = " . $id);
		}
	}
}
?>