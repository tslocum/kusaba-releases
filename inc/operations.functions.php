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
 * Secondary functions
 * +------------------------------------------------------------------------------+
 * Contains functions which aren't used very often, so they are placed into a
 * new file, and only called when needed to save compile time.
 * +------------------------------------------------------------------------------+
 */

/* Will check if there is already a cached version of the name string in the database, and if so, get it, instead of recalculating it */
function calculateNameAndTripcode($post_name) {
	global $tc_db;
	
	if(ereg("(#|!)(.*)", $post_name, $regs)){
		$results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."passcache` WHERE `md5` = '".md5($post_name)."' LIMIT 1");
		if (isset($results[0])) {
			foreach ($results AS $line) {
				return array($line['name'], $line['tripcode']);
			}
		} else {
			/* From Futabally */
			
			$cap = $regs[2];
			$cap = strtr($cap, "&amp;", "&");
			$cap = strtr($cap, "&#44;", ", ");
			$name = ereg_replace("(#|!)(.*)", "", $post_name);
			$salt = substr($cap."H.", 1, 2);
			$salt = ereg_replace("[^\.-z]", ".", $salt);
			$salt = strtr($salt, ":;<=>?@[\\]^_`", "ABCDEFGabcdef"); 
			$tripcode = substr(crypt($cap, $salt), -10)."";
			$tc_db->Execute("INSERT INTO `".TC_DBPREFIX."passcache` ( `md5` , `name` , `tripcode` ) VALUES ( '".md5($post_name)."' , '".$name."' , '".$tripcode."' )");
			return array($name, $tripcode);
		}
	} else {
		return $post_name;
	}
}

/* Thanks milianw - php.net */
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

function removeDir($path) {
$normal_files = glob($path . "*");
$hidden_files = glob($path . "\.?*");
$all_files = array_merge($normal_files, $hidden_files);

foreach ($all_files as $file) {
	/* Skip pseudo links to current and parent dirs (./ and ../). */
	if (preg_match("/(\.|\.\.)$/", $file))
	{
			continue;
	}

	if (is_file($file) === TRUE) {
		/* Remove each file in this Directory */
		unlink($file);
		echo "Removed File: " . $file . "<br>";
	}
	else if (is_dir($file) === TRUE) {
		/* If this Directory contains a Subdirectory, run this Function on it */
		removeDir($file);
	}
}
/* Remove Directory once Files have been removed (If Exists) */
if (is_dir($path) === TRUE) {
	rmdir($path);
	echo "<br>Removed Directory: " . $path . "<br><br>";
}
}

function remove_board($dir){
	global $tc_db;
	$dir = '/'.$dir;

	define('loc1', TC_BOARDSDIR, true);

	if(!isset($GLOBALS['remerror'])) {
		$GLOBALS['remerror'] = false;
	}

if($handle = opendir(loc1.$dir)){          /* If the folder exploration is sucsessful, continue */
while (false !== ($file = readdir($handle))){ /* As long as storing the next file to $file is successful, continue */
	$path = $dir . '/' . $file;

	if(is_file(loc1 . $path)){
	if(!unlink(loc1 . $path)){
		echo '<u><font color="red">"' . $path . '" could not be deleted. This may be due to a permissions problem.</u><br>Directory cannot be deleted until all files are deleted.</font><br>';
		$GLOBALS['remerror'] = true;
		return false;
	}
	} else
	if(is_dir(loc1 . $path) && substr($file, 0, 1) != '.'){
	remove_board($path);
	@rmdir(loc1 . $path);
	}
}
closedir($handle); /* Close the folder exploration */
}

if(!$GLOBALS['remerror']) /* If no errors occured, delete the now empty directory */
if(!rmdir(loc1 . $dir)){
	echo '<b><font color="red">Could not remove directory "' . $dir . '". This may be due to a permissions problem.</font></b><br>'.$GLOBALS['remerror'];
	return false;
} else
	return true;

return false;
}

/* Image handling */
function createthumb($name, $filename, $new_w, $new_h) {
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
		echo '<br>Unable to open the uploaded image for thumbnailing.  Maybe its a different filetype, and has the wrong extension?';
		return false;
	}
	$old_x=imageSX($src_img);
	$old_y=imageSY($src_img);
	if ($old_x > $old_y) {
		$percent = $new_w / $old_x;
	} else {
		$percent = $new_h / $old_y;
	}
	
	$thumb_w = round($old_x * $percent);
	$thumb_h = round($old_y * $percent);
	
	$dst_img=ImageCreateTrueColor($thumb_w, $thumb_h);
	
	fastimagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
	
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

/* Author: Tim Eckel - Date: 12/17/04 - Project: FreeRingers.net - Freely distributable. */
function fastimagecopyresampled(&$dst_image, &$src_image, $dst_x, $dst_y, $src_x, $src_y, $dst_w, $dst_h, $src_w, $src_h, $quality = 3) {
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

/* By Darien Hager, Jan 2007 */
function ords_to_unistr($ords, $encoding = 'UTF-8'){
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

function unistr_to_ords($str, $encoding = 'UTF-8'){       
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

/*
------------ lixlpixel recursive PHP functions -------------
recursive_directory_size( directory, human readable format )
expects path to directory and optional TRUE / FALSE
PHP has to have the rights to read the directory you specify
and all files and folders inside the directory to count size
if you choose to get human readable format,
the function returns the filesize in bytes, KB and MB
------------------------------------------------------------
to use this function to get the filesize in bytes, write:
recursive_directory_size('path/to/directory/to/count');
to use this function to get the size in a nice format, write:
recursive_directory_size('path/to/directory/to/count',TRUE);
*/

function recursive_directory_size($directory, $format=FALSE)
{
	$size = 0;
	$files = 0;

	/* If the path has a slash at the end we remove it here */
	if(substr($directory,-1) == '/')
	{
		$directory = substr($directory,0,-1);
	}

	/* If the path is not valid or is not a directory ... */
	if(!file_exists($directory) || !is_dir($directory) || !is_readable($directory))
	{
		/* ... We return -1 and exit the function */
		return -1;
	}
	/* We open the directory */
	if($handle = opendir($directory))
	{
		/* And scan through the items inside */
		while(($file = readdir($handle)) !== false)
		{
			/* We build the new path */
			$path = $directory.'/'.$file;

			/* If the filepointer is not the current directory or the parent directory */
			if($file != '.' && $file != '..')
			{
				/* If the new path is a file */
				if(is_file($path))
				{
					/* We add the filesize to the total size */
					$size += filesize($path);
					$files++;

				/* If the new path is a directory */
				}elseif(is_dir($path))
				{
					/* We call this function with the new path */
					$handlesize = recursive_directory_size($path);

					/* If the function returns more than zero */
					if($handlesize >= 0)
					{
						/* We add the result to the total size */
						$size += $handlesize;

					/* Else we return -1 and exit the function */
					}else{
						return -1;
					}
				}
			}
		}
		/* Close the directory */
		closedir($handle);
	}
	/* Return the total filesize in bytes */
	return array($size,$files);
}

/*
------------------------------------------------------------
*/

function ConvertBytes($number)
{
	$len = strlen($number);
	if($len < 4)
	{
		return sprintf("%d B", $number);
	}
	if($len >= 4 && $len <=6)
	{
		return sprintf("%0.2f KB", $number/1024);
	}
	if($len >= 7 && $len <=9)
	{
		return sprintf("%0.2f MB", $number/1024/1024);
	}

	return sprintf("%0.2f GB", $number/1024/1024/1024);
						
}

if (!function_exists('mime_content_type')) {
   function mime_content_type($f) {
       return trim(exec('file -bi ' . escapeshellarg ($f )));
   }
}

?>