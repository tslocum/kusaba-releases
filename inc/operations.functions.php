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
   * Contains functiosn which aren't used very often, so they are placed into a
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

//Thanks milianw - php.net
function closeOpenTags($html){
    //put all opened tags into an array
    preg_match_all("#<([a-z]+)( .*)?(?!/)>#iU", $html, $result);
    $openedtags=$result[1];

    //put all closed tags into an array
    preg_match_all("#</([a-z]+)>#iU", $html, $result);
    $closedtags=$result[1];
    $len_opened = count($openedtags);
    // all tags are closed
    if(count($closedtags) == $len_opened){
        return $html;
    }
    $openedtags = array_reverse($openedtags);
    //close tags
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
       # Skip pseudo links to current and parent dirs (./ and ../).
       if (preg_match("/(\.|\.\.)$/", $file))
       {
               continue;
       }

       if (is_file($file) === TRUE) {
           // Remove each file in this Directory
           unlink($file);
           echo "Removed File: " . $file . "<br>";
       }
       else if (is_dir($file) === TRUE) {
           // If this Directory contains a Subdirectory, run this Function on it
           removeDir($file);
       }
   }
   // Remove Directory once Files have been removed (If Exists)
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

  if($handle = opendir(loc1.$dir)){          // if the folder exploration is sucsessful, continue
   while (false !== ($file = readdir($handle))){ // as long as storing the next file to $file is successful, continue
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
   closedir($handle); // close the folder exploration
  }

  if(!$GLOBALS['remerror']) // if no errors occured, delete the now empty directory.
   if(!rmdir(loc1 . $dir)){
     echo '<b><font color="red">Could not remove directory "' . $dir . '". This may be due to a permissions problem.</font></b><br>'.$GLOBALS['remerror'];
     return false;
   } else
     return true;

  return false;
}

//Image handling
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
    imagecopyresampled($dst_img, $src_img, 0, 0, 0, 0, $thumb_w, $thumb_h, $old_x, $old_y);
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

/*
    By Darien Hager, Jan 2007
*/
function ords_to_unistr($ords, $encoding = 'UTF-8'){
    // Turns an array of ordinal values into a string of unicode characters
    $str = '';
    for($i = 0; $i < sizeof($ords); $i++){
        // Pack this number into a 4-byte string
        // (Or multiple one-byte strings, depending on context.)               
        $v = $ords[$i];
        $str .= pack("N",$v);
    }
    $str = mb_convert_encoding($str,$encoding,"UCS-4BE");
    return($str);           
}

function unistr_to_ords($str, $encoding = 'UTF-8'){       
    // Turns a string of unicode characters into an array of ordinal values,
    // Even if some of those characters are multibyte.
    $str = mb_convert_encoding($str,"UCS-4BE",$encoding);
    $ords = array();
   
    // Visit each unicode character
    for($i = 0; $i < mb_strlen($str,"UCS-4BE"); $i++){       
        // Now we have 4 bytes. Find their total
        // numeric value.
        $s2 = mb_substr($str,$i,1,"UCS-4BE");                   
        $val = unpack("N",$s2);           
        $ords[] = $val[1];               
    }       
    return($ords);
}

?>