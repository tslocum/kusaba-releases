<?php
/**
 * Display the embedded video
 *
 * @param array $post Post data 
 * @return string Embedded video
 */ 
function embeddedVideoBox($post) {
	$output = '<span style="float: left;">' . "\n";
				
	if ($post['filetype'] == 'you') {
		$output .= '<script type="text/javascript"><!--' . "\n" .
		'document.write(\'<object width="' . KU_YOUTUBEWIDTH . '" height="' . KU_YOUTUBEHEIGHT . '"><param name="movie" value="http://www.youtube.com/v/' . $post['filename'] . '"><\/param><param name="wmode" value="transparent"><\/param><embed src="http://www.youtube.com/v/' . $post['filename'] . '" type="application/x-shockwave-flash" wmode="transparent" width="' . KU_YOUTUBEWIDTH . '" height="' . KU_YOUTUBEHEIGHT . '"><\/embed><\/object>\');' . "\n" .
		'--></script>' . "\n";
	} elseif ($post['filetype'] == 'goo') {
		$output .= '<script type="text/javascript"><!--' . "\n" .
		'document.write(\'<embed style="width:200px; height:164px;" id="VideoPlayback" type="application/x-shockwave-flash" src="http://video.google.com/googleplayer.swf?docId=' . $post['filename'] . '&hl=en" flashvars=""><\/embed>\');' . "\n" .
		'--></script>' . "\n";
	}
	
	$output .= '</span>&nbsp;' . "\n";
	
	return $output;
}

/**
 * Check if the supplied md5 file hash is currently recorded inside of the database, attached to a non-deleted post
 */
function checkMd5($md5, $board) {
	global $tc_db;
	
	$matches = $tc_db->GetAll("SELECT `id`, `parentid` FROM `".KU_DBPREFIX."posts_".mysql_real_escape_string($board)."` WHERE `IS_DELETED` = 0 AND `filemd5` = '".mysql_real_escape_string($md5)."' LIMIT 1");
	if (count($matches) > 0) {
		$real_parentid = ($matches[0][1] == 0) ? $matches[0][0] : $matches[0][1];
		
		return array($real_parentid, $matches[0][0]);
	}
	
	return false;
}

/* Image handling */
/**
 * Create a thumbnail
 *
 * @param string $name File to be thumbnailed
 * @param string $filename Path to place the thumbnail
 * @param integer $new_w Maximum width 
 * @param integer $new_h Maximum height
 * @return boolean Success/fail 
 */ 
function createThumbnail($name, $filename, $new_w, $new_h) {
	if (KU_THUMBMETHOD == 'imagemagick') {
		$convert = 'convert ' . escapeshellarg($name) . ' ';
		if (!KU_ANIMATEDTHUMBS) {
			$convert .= '-coalesce ';
		}
		$convert .= '-resize ' . $new_w . 'x' . $new_h . ' -quality ';
		if (substr($filename, 0, -3) != 'gif') {
			$convert .= '70';
		} else {
			$convert .= '90';
		}
		$convert .= ' ' . escapeshellarg($filename);
		exec($convert);
		
		if (is_file($filename)) {
			return true;
		} else {
			return false;
		}
	} elseif (KU_THUMBMETHOD == 'gd') {
		$system=explode(".", $filename);
		$system = array_reverse($system);
		if (preg_match("/jpg|jpeg/", $system[0])) {
			$src_img=imagecreatefromjpeg($name);
		} else if (preg_match("/png/", $system[0])) {
			$src_img=imagecreatefrompng($name);
		} else if (preg_match("/gif/", $system[0])) {
			$src_img=imagecreatefromgif($name);
		} else {
			return false;
		}
		
		if (!$src_img) {
			exitWithErrorPage(_gettext('Unable to read uploaded file during thumbnailing.'), _gettext('A common cause for this is an incorrect extension when the file is actually of a different type.'));
		}
		$old_x = imageSX($src_img);
		$old_y = imageSY($src_img);
		if ($old_x > $old_y) {
			$percent = $new_w / $old_x;
		} else {
			$percent = $new_h / $old_y;
		}
		$thumb_w = round($old_x * $percent);
		$thumb_h = round($old_y * $percent);
		
		$dst_img = ImageCreateTrueColor($thumb_w, $thumb_h);
		fastImageCopyResampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
		
		if (preg_match("/png/", $system[0])) {
			if (!imagepng($dst_img, $filename)) {
				echo 'unable to imagepng.';
				return false;
			}
		} else if (preg_match("/jpg|jpeg/", $system[0])) {
			if (!imagejpeg($dst_img, $filename, 70)) {
				echo 'unable to imagejpg.';
				return false;
			}
		} else if (preg_match("/gif/", $system[0])) {
			if (!imagegif($dst_img, $filename)) { 
				echo 'unable to imagegif.';
				return false;
			}
		}
		
		imagedestroy($dst_img); 
		imagedestroy($src_img); 
		
		return true;
	}
	
	return false;
}

/* Author: Tim Eckel - Date: 12/17/04 - Project: FreeRingers.net - Freely distributable. */
/**
 * Faster method than only calling imagecopyresampled()
 *
 * @return boolean Success/fail 
 */ 
function fastImageCopyResampled(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
	/*
	Optional "quality" parameter (defaults is 3).  Fractional values are allowed, for example 1.5.
	1 = Up to 600 times faster.  Poor results, just uses imagecopyresized but removes black edges.
	2 = Up to 95 times faster.  Images may appear too sharp, some people may prefer it.
	3 = Up to 60 times faster.  Will give high quality smooth results very close to imagecopyresampled.
	4 = Up to 25 times faster.  Almost identical to imagecopyresampled for most images.
	5 = No speedup.  Just uses imagecopyresampled, highest quality but no advantage over imagecopyresampled.
	*/
	
	if (empty($src_image) || empty($dst_image)) { return false; }

	if ($quality <= 1) {
		$temp = imagecreatetruecolor ($dst_w + 1, $dst_h + 1);
		imagecopyresized ($temp, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w + 1, $dst_h + 1, $src_w, $src_h);
		imagecopyresized ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $dst_w, $dst_h);
		imagedestroy ($temp);
	} elseif ($quality < 5 && (($dst_w * $quality) < $src_w || ($dst_h * $quality) < $src_h)) {
		
		$tmp_w = $dst_w * $quality;
		$tmp_h = $dst_h * $quality;
		$temp = imagecreatetruecolor ($tmp_w + 1, $tmp_h + 1);
		
		imagecopyresized ($temp, $src_image, $dst_x * $quality, $dst_y * $quality, $src_x, $src_y, $tmp_w + 1, $tmp_h + 1, $src_w, $src_h);
		
		imagecopyresampled ($dst_image, $temp, 0, 0, 0, 0, $dst_w, $dst_h, $tmp_w, $tmp_h);
		
		imagedestroy ($temp);
		
	} else {
		imagecopyresampled ($dst_image, $src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h);
	}

	
	return true;
}

/**
 * Check if a thread is marked for deletion
 * 
 * @param string $post Post data
 * @param integer $maxage Maximum thread age
 * @return boolean Marked or not
 */ 
function checkMarkedForDeletion($post, $maxage) {
	if (!$post['stickied'] && $post['parentid'] == 0 && (($maxage > 0 && ($post['postedat']  + ($maxage * 3600)) < (time() + 7200)) || ($post['deletedat'] > 0 && $post['deletedat'] <= (time() + 7200)))) {
		return true;
	}
	
	return false;
}

function textBoardReplyBox($board, $forcedanon, $enablecaptcha, $numreplies = false, $threadid = false, $formid = '') {
	if ($threadid === false) {
		$threadid = '0';
	}
	$output = '<table class="postform">' . "\n";
	if ($numreplies === false) {
		$output .= textBoardReplyBoxSubject();
	}
	$output .= '<tr>' . "\n";
	if ($forcedanon != 1) {
		$output .= textBoardReplyBoxName() .
		textBoardReplyBoxEmail() .
		textBoardReplyBoxSubmit($board, $numreplies, $threadid, $formid) .
		'</tr>' . "\n" .
		'<tr>' . "\n";
		if ($enablecaptcha == 1) {
			$output .= textBoardReplyBoxCaptcha();
		}
		$output .= textBoardReplyBoxPassword();
	} else {
		$output .= textBoardReplyBoxEmail();
		if ($enablecaptcha == 1) {
			$output .= textBoardReplyBoxCaptcha();
		} else {
			$output .= textBoardReplyBoxPassword();
		}
		$output .= textBoardReplyBoxSubmit($board, $numreplies, $threadid, $formid);
		if ($enablecaptcha == 1) {
			$output .= '</tr>' . "\n" .
			'<tr>' . "\n" .
			textBoardReplyBoxPassword();
		}
	}
	$output .= '</tr>' . "\n" .
	'<tr style="display: none;" id="opt' . $threadid . '"><td></td></tr>' . "\n" .
	'<tr>' . "\n" .
	'	<td class="postfieldleft">' . "\n" .
	'		<span class="postnum">' . "\n";
	if ($numreplies !== false) {
		$output .= '			' . ($numreplies + 2) . "\n";
	} else {
		$output .= '			1' . "\n";
	}
	$output .= '		</span>' . "\n" .
	'	</td>' . "\n" .
	'	<td colspan="4">' . "\n" .
	'		<textarea name="message" rows="8" cols="64"></textarea>' . "\n" .
	'	</td>' . "\n" .
	'</tr>' . "\n" .
	'</table>' . "\n" .
	'<div id="preview' . $threadid . '"></div>' . "\n";
	
	return $output;
}

function textBoardReplyBoxSubject() {
	return '<tr>' . "\n" .
	'	<td class="label">' . "\n" .
	'		<label>' . _gettext('Subject').':</label>' . "\n" .
	'	</td>' . "\n" .
	'	<td colspan="4">' . "\n" .//
	'		<input name="subject" maxlength="75" size="50" style="width: 70%;">' . "\n" .
	'	</td>' . "\n" .
	'</tr>' . "\n";
}

function textBoardReplyBoxName() {
	return '	<td class="label">' . "\n" .
	'		<label>' . _gettext('Name').':</label>' . "\n" .
	'	</td>' . "\n" .
	'	<td>' . "\n" .
	'		<input name="name" size="25" maxlength="75">' . "\n" .
	'	</td>' . "\n";
}

function textBoardReplyBoxEmail() {
	return '	<td class="label">' . "\n" .
	'		<label>' . _gettext('Email') . ':</label>' . "\n" .
	'	</td>' . "\n" .
	'	<td>' . "\n" .
	'		<input name="em" size="25" maxlength="75">' . "\n" .
	'	</td>' . "\n";
}

function textBoardReplyBoxCaptcha() {
	return '<td class="label"><label for="captcha">'._gettext('Captcha').':</label></td>' . "\n" .
	'<td>' . "\n" .
	'	<a href="#" onclick="javascript:document.getElementById(\'captchaimage\').src = \'' . KU_CGIPATH . '/captcha.php?\' + Math.random();return false;">' . "\n" .
	'	<img id="captchaimage" src="' . KU_CGIPATH .'/captcha.php" border="0" width="90" height="30" alt="Captcha image">' . "\n" .
	'	</a>&nbsp;' . "\n" .
	'	<input type="text" id="captcha" name="captcha" size="8" maxlength="6">' . "\n" .
	'</td>' . "\n";
}

function textBoardReplyBoxPassword() {
	return  '	<td class="label">' . "\n" .
	'		<label>' . _gettext('Password') . ':</label>' . "\n" .
	'	</td>' . "\n" .
	'	<td>' . "\n" .
	'		<input type="password" name="postpassword" size="8" accesskey="p" maxlength="75">' . "\n" .
	'	</td>' . "\n";
}

function textBoardReplyBoxSubmit($board, $numreplies, $threadid, $formid) {
	$return = '	<td>' . "\n";
	if ($numreplies !== false) {
		$return .= '		<input type="submit" name="submit" value="' . _gettext('Reply') . '" class="submit">' . "\n";
	} else {
		$return .= '		<input type="submit" name="submit" value="' . _gettext('Submit') . '" class="submit">' . "\n";
	}
	$return .= '		<a href="#" onclick="toggleOptions(\'' . $threadid . '\', \'' . $formid . '\', \'' . $board . '\');return false;">' . _gettext('More') . '...</a>' . "\n" .
	'	</td>' . "\n";
	
	return $return;
}

/*
Link validator

Will use cURL to attempt to visit a webpage, and then return based upon how the
request was handled.  Used for embedded videos to validate the ID is existant.

Thanks phadeguy - http://www.zend.com/codex.php?id=1256&single=1
expects a link url as string
returns an array of three elements:
return_array[0] = HTTP version
return_array[1] = Returned error number (200, 404, etc)
return_array[2] = Returned error text ("OK", "File Not Found", etc) */
function check_link($link) {
	$main = array();
	$ch = curl_init();
	curl_setopt ($ch, CURLOPT_URL, $link);
	curl_setopt ($ch, CURLOPT_HEADER, 1);
	curl_setopt ($ch, CURLOPT_NOBODY, 1);
	curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt ($ch, CURLOPT_TIMEOUT, 10);
	curl_setopt ($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
	ob_start();
	curl_exec ($ch);
	$stuff = ob_get_contents();
	ob_end_clean();
	curl_close ($ch);
	$parts = split("n",$stuff,2);
	$main = split(" ",$parts[0],3);
	return $main;
}
?>