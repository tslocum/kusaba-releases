<?php

//For PHP4 installations
if (!function_exists("file_put_contents")) {
	define('FILE_APPEND', 1);
	function file_put_contents($n, $d, $flag = false) {
	   $mode = ($flag == FILE_APPEND || strtoupper($flag) == 'FILE_APPEND') ? 'a' : 'w';
	   $f = @fopen($n, $mode);
	   if ($f === false) {
		   return 0;
	   } else {
		   if (is_array($d)) $d = implode($d);
		   $bytes_written = fwrite($f, $d);
		   fclose($f);
		   return $bytes_written;
	   }
	}
}
// <3 coda for this wonderful snippet
// print $contents to $filename by using a temporary file and renaming it
function print_page($filename,$contents,$board) {
	require("config.php");
	$tempfile = tempnam($chan_webfolder."/".$board."/res", "tmp"); //note: THIS actually creates the file
	$fp = fopen($tempfile, "w");
	fwrite($fp, $contents);
	fclose($fp);
	rename($tempfile, $filename);
	chmod($filename, 0664); //it was created 0600
}
if(!function_exists('str_ireplace')) {
	function str_ireplace( $find, $replace, $string ) {
		// Case-insensitive str_replace()
		
		$parts = explode( strtolower($find), strtolower($string) );
		
		$pos = 0;
		
		foreach( $parts as $key=>$part ){
		$parts[ $key ] = substr($string, $pos, strlen($part));
		$pos += strlen($part) + strlen($find);
		}
		
		return( join( $replace, $parts ) );
	}
}

//Header
function print_stylesheets($prefered_stylesheet = 'Burichan') {
	require("config.php");
	if ($prefered_stylesheet!='Burichan'&&$prefered_stylesheet!='Futaba'&&$prefered_stylesheet!='Gurochan'&&$prefered_stylesheet!='Photon'&&$prefered_stylesheet!='Fuhrerchan') {
		$prefered_stylesheet = 'Burichan';
	}
	if ($prefered_stylesheet=='Burichan') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/burichan.css" title="Burichan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/futaba.css" title="Futaba" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/gurochan.css" title="Gurochan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/photon.css" title="Photon" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/fuhrerchan.css" title="Fuhrerchan" />';
	} else if ($prefered_stylesheet=='Futaba') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/futaba.css" title="Futaba" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/burichan.css" title="Burichan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/gurochan.css" title="Gurochan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/photon.css" title="Photon" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/fuhrerchan.css" title="Fuhrerchan" />';
	} else if ($prefered_stylesheet=='Gurochan') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/gurochan.css" title="Gurochan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/burichan.css" title="Burichan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/futaba.css" title="Futaba" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/photon.css" title="Photon" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/fuhrerchan.css" title="Fuhrerchan" />';
	} else if ($prefered_stylesheet=='Photon') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/photon.css" title="Photon" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/burichan.css" title="Burichan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/futaba.css" title="Futaba" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/gurochan.css" title="Gurochan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/fuhrerchan.css" title="Fuhrerchan" />';
	} else if ($prefered_stylesheet=='Fuhrerchan') {
		return '<link rel="stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/fuhrerchan.css" title="Fuhrerchan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/burichan.css" title="Burichan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/futaba.css" title="Futaba" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/gurochan.css" title="Gurochan" />
	<link rel="alternate stylesheet" type="text/css" href="'.$chan_webpath.$chan_webfolder.'/css/photon.css" title="Photon" />';
	}
	
}

//Management
function management_adminsonly() {
	require("config.php");
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['isadmin']!="1") {
				die();
			}
		}
	} else {
		die();
	}
}
function management_isadmin() {
	require("config.php");
	//die("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."'".$dblink);
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$_SESSION['manageusername']."' AND `password` = '".$_SESSION['managepassword']."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['isadmin']=="1") {
				return true;
			} else {
				return false;
			}
		}
	} else {
		die();
	}
}
function management_addlogentry($entry) {
	require("config.php");
	if ($entry!="") {
		mysql_query("INSERT INTO `modlog` ( `entry` , `user` , `timestamp` ) VALUES ( '".$entry."' , '".$_SESSION['manageusername']."' , '".time()."' )",$dblink);
	}
}
function moderator_ismodofboard($board,$username) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$username."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['isadmin']=="1") {
				return true;
			} else {
				$array_boards = explode('|',$line['boards']);
				if (in_array($board,$array_boards)) {
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
function moderator_boardlist($username) {
	is_file("config.php") ? require("config.php") : require("../config.php");
	$staff_boardsmoderated = array();
	$result = mysql_query("SELECT * FROM `staff` WHERE `username` = '".$username."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		if (management_isadmin()) {
			$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
			while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
				$staff_boardsmoderated = array_merge($staff_boardsmoderated,array($lineboard['name']));
			}
		} else {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$array_boards = explode('|',$line['boards']);
			}
			foreach ($array_boards as $this_board_name) {
				$staff_boardsmoderated = array_merge($staff_boardsmoderated,array($this_board_name));
			}
		}
	}
	return $staff_boardsmoderated;
}
function moderator_deleteandbanlinks($chan_webfolder,$board_dir,$post_id, $is_thread) {
	is_file("config.php") ? require("config.php") : require("../config.php");
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board_dir."'",$dblink);
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$board_id = $line['id'];
	}
	$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `IS_DELETED` = '0' AND `boardid` = '".$board_id."' AND `id` = '".$post_id."'",$dblink);
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$poster_ip = $line['ip'];
	}
	if ($is_thread==true) {
		$post_threadorpost = 'thread';
	} else {
		$post_threadorpost = 'post';
	}
	return "<script type=\"text/javascript\">
	if (getCookie(\"tcmod\")==\"yes\") {
		document.write('&nbsp;&#91;<a href=\"".$chan_webfolder."/manage.php?action=delposts&boarddir=".$board_dir."&del".$post_threadorpost."id=".$post_id."\" onclick=\"return confirm(\\'Are you sure you want to delete this ".$post_threadorpost."?\\');\"><acronym title=\"Delete\">D</acronym></a>&nbsp;<a href=\"".$chan_webfolder."/manage.php?action=delposts&boarddir=".$board_dir."&del".$post_threadorpost."id=".$post_id."&postid=".$post_id."\" onclick=\"return confirm(\\'Are you sure you want to delete and ban the poster of this ".$post_threadorpost."?\\');\"><acronym title=\"Delete &amp; Ban\">&amp;</acronym></a>&nbsp;<a href=\"".$chan_webfolder."/manage.php?action=bans&banboard=".$board_dir."&banpost=".$post_id."\"><acronym title=\"Ban\">B</acronym></a>&#93;');
	}
	</script>";
}
function make_boardlist_dropdown($name,$boards) {
	echo '<select name="'.$name.'"><option value="">Select a Board</option>';
	if ($boards!="") {
		foreach ($boards as $board) {
			echo '<option value="'.$board.'">/'.$board.'/</option>';
		}
	}
	echo '</select>';
}
function make_boardlist_checkbox($prefix,$boards) {
	if ($boards!="") {
		foreach ($boards as $board) {
			echo '<label for="'.$prefix.$board.'">'.$board.'</label><input type="checkbox" name="'.$prefix.$board.'" /> ';
		}
	}
}

//Configuration
function config_getvalue($key) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `config` WHERE `key` = '".$key."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			return $line['value'];
		}
	} else {
		return false;
	}
}
function config_setvalue($key,$value) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `config` WHERE `key` = '".$key."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		mysql_query("UPDATE `config` SET `value` = '".$value."' WHERE `key` = '".$key."'",$dblink);
		return true;
	} else {
		return false;
	}
}

//Maintenance
function delunusedimages($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		$imagelist = array();
		$imagethumbnaillist = array();
		$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lineboard['id']."'",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$imagelist = array_merge($imagelist,array($line['image'].'.'.$line['imagetype']));
			$imagethumbnaillist = array_merge($imagethumbnaillist,array($line['image'].'s.'.$line['imagetype']));
		}
		$dir = './'.$lineboard['name'].'/src';
		$files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $image) {
				if (in_array(basename($image),$imagelist)==false) {
					if (time()-filemtime($chan_rootdir.'/'.$lineboard['name'].'/src/'.basename($image))>120) {
						if ($verbose==true) {
							echo $image.' NOT IN DATABASE! Deleted.<br />';
						}
						unlink($chan_rootdir.'/'.$lineboard['name'].'/src/'.basename($image));
					}
				}
			}
		}
		$dir = './'.$lineboard['name'].'/thumb';
		$files = glob ("$dir/{*.jpg,*.png,*.gif}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $image) {
				if (in_array(basename($image),$imagethumbnaillist)==false) {
					if (time()-filemtime($chan_rootdir.'/'.$lineboard['name'].'/thumb/'.basename($image))>120) {
						if ($verbose==true) {
							echo $image.' (thumb) NOT IN DATABASE! Deleted.<br />';
						}
						unlink($chan_rootdir.'/'.$lineboard['name'].'/thumb/'.basename($image));
					}
				}
			}
		}
	}
	return true;
}

//Boards
function regenerate_board($board,$pagesonly = false) {
	is_file("config.php") ? require("config.php") : require("../config.php");
	$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		$board_id = $lineboard['id'];
		$board_dir = $lineboard['name'];
		$board_desc = $lineboard['desc'];
		$board_locked = $lineboard['locked'];
		//Rebuild pages
		$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC",$dblink);
		$numpostsleft = mysql_num_rows($result);
		$boardstooutput = floor(($numpostsleft-1) / 10);
		$boardpage = 0;
		if ($numpostsleft>0) {
			while ($numpostsleft>0) {
				if (!ob_start()) {
					die("Unable to initiate the output buffering!");
				}
				require_once("header.php");
				echo chan_header($board_dir);
				require_once("postbox.php");
				echo chan_postbox($board_dir);
				$query = "SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."' AND `threadid` = '0' ORDER BY `stickied` DESC, `lastbumped` DESC LIMIT ".($boardpage*10).",10";
				$result_leftposts = mysql_query($query,$dblink);
				while ($line = mysql_fetch_array($result_leftposts, MYSQL_ASSOC)) {
					$thread_id = $line['id'];
					echo buildthread($board_dir,$thread_id,true);
				}
				/*
				This will be implemented in 0.4, feel free to modify it if you wish.  If you do implement it yourself, I'd love to see the code :)
				*/
				echo '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br />Password <input type="password" name="postpassword" size="8" />&nbsp;<input value="Delete" type="submit" /></td></tr></tbody></table></form>';
				echo '<table border="1"><tbody><tr><td>';
				if ($boardpage==0) {
					echo 'Previous';
				} else {
					if ($boardpage-1!=0) {
						echo '<form method="get" action="'.$chan_webfolder.'/'.$board_dir.'/'.($boardpage-1).'.html"><input value="Previous" type="submit" /></form>';
					} else {
						echo '<form method="get" action="'.$chan_webfolder.'/'.$board_dir.'/board.html"><input value="Previous" type="submit" /></form>';
					}
				}
				echo '</td><td>';
				$i = 0;
				while ($i <= $boardstooutput) {
					if ($boardpage==$i) {
						echo '&#91;'.$i.'&#93;';
					} else {
						if ($i!=0) {
							echo '&#91;<a href="'.$chan_webfolder.'/'.$board_dir.'/'.$i.'.html">'.$i.'</a>&#93;';
						} else {
							echo '&#91;<a href="'.$chan_webfolder.'/'.$board_dir.'/board.html">'.$i.'</a>&#93;';
						}
					}
					$i++;
				}
				echo '</td><td>';
				if ($boardpage==$boardstooutput) {
					echo "Next";
				} else {
					echo '<form method="get" action="'.$chan_webfolder.'/'.$board_dir.'/'.($boardpage+1).'.html"><input value="Next" type="submit" /></form>';
				}
				echo '</td></tr></tbody></table></form>';
				require_once("footer.php");
				echo chan_footer();
				$page = ob_get_clean();
				if ($boardpage==0) {
					print_page($chan_rootdir."/".$board_dir."/board.html",$page,$board_dir);
				} else {
					print_page($chan_rootdir."/".$board_dir."/".$boardpage.".html",$page,$board_dir);
				}
				$page = "";
				$boardpage++;
				$numpostsleft -= 10;
			}
		} else { //Make a blank index
			ob_start();
			require_once("header.php");
			echo chan_header($board_dir);
			require_once("postbox.php");
			echo chan_postbox($board_dir);
			require_once("footer.php");
			echo chan_footer();
			$page = ob_get_clean();
			print_page($chan_rootdir."/".$board_dir."/board.html",$page,$board_dir);
		}
		//Rebuild /res/
		if ($pagesonly==false) {
			$res_threadlist = array();
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lineboard['id']."' AND `threadid` = '0' ORDER BY `lastbumped` DESC",$dblink);
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$res_threadlist = array_merge($res_threadlist,array($line['id'].'.html'));
				regenerate_thread($lineboard['name'],$line['id']);
			}
			$dir = $chan_rootdir.'/'.$board_dir.'/res';
			$files = glob ("$dir/{*.html}",  GLOB_BRACE);
			if (is_array($files)) { 
				foreach ($files as $htmlfile) {
					if (!in_array(basename($htmlfile),$res_threadlist)) {
						unlink($htmlfile);
					}
				}
			}
		}
		//Delete old pages
		$dir = $chan_rootdir.'/'.$board_dir;
		$files = glob ("$dir/{*.html}",  GLOB_BRACE);
		if (is_array($files)) { 
			foreach ($files as $htmlfile) {
				if (preg_match("/[0-9+].html/",$htmlfile)) {
					if (substr(basename($htmlfile),0,strpos(basename($htmlfile),'.html'))>$boardstooutput) {
						unlink($htmlfile);
					}
				}
			}
		}
	}
}
function regenerate_all_boards($verbose = false) {
	require("config.php");
	$resultboard = mysql_query("SELECT * FROM `boards`",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		regenerate_board($lineboard['name']);
		if ($verbose==true) {
			echo 'Regenerated /'.$lineboard['name'].'/<br />';
		}
	}
}
function removeDir($path) {
   // Add trailing slash to $path if one is not there
   /*if (substr($path, -1, 1) != "/") {
       $path .= "/";
   }*/

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
	require("config.php");
	$dir = '/'.$dir;
	/*if (removeDir($chan_rootdir.$chan_webfolder."/".$dir)) {
		echo 'Directory removed!';
	} else {
		echo 'Error.';
	}*/
	
	define('loc1', $chan_rootdir, true);

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
     echo '<b><font color="red">Could not remove directory "' . $dir . '". This may be due to a permissions problem.</font></b><br />'.$GLOBALS['remerror'];
     return false;
   } else
     return true;

  return false;
}

//Threads
function buildthread($board,$threadid,$page = false) {
	ob_start();
	require("config.php");
	if (!$threadid>0||$board=="") {
		die("Invalid arguments sent to function buildthread()");
	}
	if ($board!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
				$board_dir = $line['name'];
				$board_desc = $line['desc'];
			}
		} else {
			die("Invlaid board ID.");
		}
	} else {
		die("Invlaid board ID.");
	}
	
	$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".$threadid."' AND `boardid` = '".$board_id."' AND `threadid` = '0'",$dblink);
	$rows = mysql_num_rows($result);
	if (!$rows>0) {
		die("No posts in thread to build from.");
	}
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$thread_id = $line['id'];
		if ($line['image']=="removed") {
			$imgWidth = '0';
			$imgHeight = '0';
			$imgWidth_thumb = '189';
			$imgHeight_thumb = '16';
		} else {
			$imageDim = getimagesize($chan_rootdir."/".$board_dir."/src/".$line['image'].'.'.$line['imagetype']);
			$imgWidth = $imageDim[0];
			$imgHeight = $imageDim[1];
			$imageDim_thumb = getimagesize($chan_rootdir."/".$board_dir."/thumb/".$line['image'].'s.'.$line['imagetype']);
			$imgWidth_thumb = $imageDim_thumb[0];
			$imgHeight_thumb = $imageDim_thumb[1];
		}
		$result2 = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."'");
		$numReplies = mysql_num_rows($result2);
		$numImageReplies = 0;
		if ($page==true) {
			if ($line['stickied']=="0") {
				if ($numReplies>3) {
					$result2 = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-3)."",$dblink);
					while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
						if ($line2['image']!="") {
							$numImageReplies++;
						}
					}
				}
			} else {
				if ($numReplies>1) {
					$result2 = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."' AND `threadid` = '".$thread_id."' ORDER BY `postedat` ASC LIMIT 0,".($numReplies-1)."",$dblink);
					while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
						if ($line2['image']!="") {
							$numImageReplies++;
						}
					}
				}
			}
		}
		/*
		This will be implemented in 0.4, feel free to modify it if you wish.  If you do implement it yourself, I'd love to see the code :)
		*/
		echo '<form id="delform" action="'.$chan_webfolder.'/board.php" method="post"><input type="hidden" name="board" value="'.$board_dir.'" />';
		?>
		<span class="filesize">File: <a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($line['image']=="removed") { echo $chan_webfolder."/imageremoved"; } else { echo $chan_webfolder."/".$board_dir."/src/".$line['image']; }?>.<?php if ($line['image']=="removed") { echo 'png'; } else { echo $line['imagetype']; } ?>"><?php if ($line['image']=="removed") { echo 'imageremoved.png'; } else { echo $line['image'].'.'.$line['imagetype']; } ?></a> -(<em><?php
		if ($line['image']=="removed") { echo '&nbsp'; } else { echo round(filesize($chan_rootdir."/".$board_dir."/src/".$line['image'].'.'.$line['imagetype'])/1024).' KB, '.$imgWidth.'x'.$imgHeight; } ?>
		</em>)</span><span class="thumbnailmsg"> Thumbnail displayed, click image for full size.</span><br />
		<a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($line['image']=="removed") { echo $chan_webfolder."/imageremoved"; } else { echo $chan_webfolder."/".$board_dir."/src/".$line['image']; } ?>.<?php if ($line['image']=="removed") { echo "png"; } else { echo $line['imagetype']; } ?>">
		<img src="<?php if ($line['image']=="removed") { echo $chan_webfolder."/imageremoved"; } else { echo $chan_webfolder."/".$board_dir."/thumb/".$line['image']."s"; } ?>.<?php if ($line['image']=="removed") { echo "png"; } else { echo $line['imagetype']; } ?>" width="<?php echo $imgWidth_thumb; ?>" height="<?php echo $imgHeight_thumb; ?>" alt="<?php echo $thread_id; ?>" class="thumb" /></a><a name="<?php echo $thread_id; ?>">
		</a><label><input type="checkbox" name="delete" value="<?php echo $thread_id; ?>" />&nbsp;
		<?php if ($line['subject']!="") { echo '<span class="filetitle">'.stripslashes($line['subject']).'</span>&nbsp;'; } ?>
		<span class="postername"><?php
		if ($line['email']!="") {
			echo '<a href="mailto:'.$line['email'].'">';
		}
		if ($line['user']==""&&$line['tripcode']=="") {
			echo 'Anonymous';
		} else if ($line['user']==""&&$line['tripcode']!="") {
			echo ''; // If they have a tripcode, just display the tripcode
		} else {
			echo stripslashes($line[user]);
		}
		if ($line['email']!="") {
			echo '</a>';
		}
		echo '</span>';
		if ($line['tripcode']!="") {
			echo '<span class="postertrip">!'.$line['tripcode'].'</span>';
		}
		if ($line['posterauthority']>0) {
			if ($line['posterauthority']=="1") {
				echo '&nbsp;<span class="admin">##&nbsp;Admin&nbsp;##</span>';
			} else if ($line['posterauthority']=="2") {
				echo '&nbsp;<span class="mod">##&nbsp;Mod&nbsp;##</span>';
			}
		}
		echo '&nbsp;'.date("y/m/d(D)H:i",$line['postedat']); ?></label>&nbsp;
		<span class="reflink"><a href="<?php echo $chan_webfolder."/".$board_dir."/res/".$thread_id; ?>.html#<?php echo $thread_id; ?>">No.</a><a href="<?php echo $chan_webfolder."/".$board_dir."/res/".$thread_id; ?>.html#i<?php echo $thread_id; ?>" <?php if ($page==false) { echo ' onclick="insert(\'>>'.$thread_id.'\');"'; } ?>><?php echo $thread_id; ?></a><?php if ($line['locked']=="1") { echo '&nbsp;<img style="border: 0;" src="'.$chan_webfolder.'/locked.gif" alt="locked" />'; } ?><?php if ($line['stickied']=="1") { echo '<img style="border: 0;" src="'.$chan_webfolder.'/sticky.gif" alt="stickied" />'; } ?></span>
		<?php if ($page==true) { echo '&nbsp;&#91;<a href="'.$chan_webfolder.'/'.$board_dir.'/res/'.$thread_id.'.html">Reply</a>&#93;'; } ?>
		<?php echo moderator_deleteandbanlinks($chan_webfolder,$board_dir,$thread_id,true); ?>
		<blockquote>
		<p><?php if (count(explode("<br />", $line['message']))>15&&$page==true) {
				$i = 0;
				$message_exploded = explode("<br />", $line['message']);
				foreach ($message_exploded as $message_line) {
					$i++;
					if ($i<=15) {
						echo stripslashes($message_line)."<br />";
					}
				}
				echo '</p><div class="abbrev">Comment too long. Click <a href="'.$chan_webfolder.'/'.$board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
			} else {
				echo stripslashes($line['message']);
				echo '</p>';
			}
		?>
		</blockquote>
		<?php
		if ($numReplies>0) {
			if ($page==false) {
				$result2 = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".$thread_id."' AND `boardid` = '".$board_id."' ORDER BY `postedat` ASC",$dblink);
			} else {
				$query = "SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".$thread_id."' AND `boardid` = '".$board_id."' ORDER BY `postedat` ASC LIMIT ";
				if ($line['stickied']=="0") {
					$query .= max($numReplies-3,0).",3";
					if ($numReplies>3) {
						echo '<span class="omittedposts"> '.($numReplies-3).' post';
						if ($numReplies-3!=1) {
							echo 's';
						}
						if ($numImageReplies>0) {
							echo ' and '.$numImageReplies.' image';
							if ($numImageReplies!=1) {
								echo 's';
							}
						}
						echo ' omitted. Click Reply to view.  </span>';
					}
				} else {
					$query .= max($numReplies-1,0).",1";
					if ($numReplies>1) {
						echo '<span class="omittedposts"> '.($numReplies-1).' post';
						if ($numReplies-1!=1) {
							echo 's';
						}
						if ($numImageReplies>0) {
							echo ' and '.$numImageReplies.' image';
							if ($numImageReplies!=1) {
								echo 's';
							}
						}
						echo ' omitted. Click Reply to view.  </span>';
					}
				}
				$result2 = mysql_query($query,$dblink);
			}
			while ($line2 = mysql_fetch_array($result2, MYSQL_ASSOC)) {
				$reply_id = $line2['id'];
				$reply_user = stripslashes($line2['user']);
				$reply_tripcode = $line2['tripcode'];
				$reply_email = stripslashes($line2['email']);
				$reply_subject = stripslashes($line2['subject']);
				$reply_message = stripslashes($line2['message']);
				$reply_postedat = $line2['postedat'];
				$reply_image = $line2['image'];
				$reply_imagetype = $line2['imagetype'];
				$reply_posterauthority = $line2['posterauthority'];
				if ($reply_image!="") {
					if ($reply_image=="removed") {
						$reply_imgWidth = '0';
						$reply_imgHeight = '0';
						$reply_imgWidth_thumb = '189';
						$reply_imgHeight_thumb = '16';
					} else {
						$reply_imageDim = getimagesize($chan_rootdir."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype);
						$reply_imgWidth = $reply_imageDim[0];
						$reply_imgHeight = $reply_imageDim[1];
						$reply_imageDim_thumb = getimagesize($chan_rootdir."/".$board_dir."/thumb/".$reply_image.'s.'.$reply_imagetype);
						$reply_imgWidth_thumb = $reply_imageDim_thumb[0];
						$reply_imgHeight_thumb = $reply_imageDim_thumb[1];
					}
				}
				?>
				<table>
				<tbody>
				<tr>
				<td class="doubledash">&gt;&gt;</td>
				<td class="reply" id="reply<?php echo $reply_id; ?>"><a name="<?php echo $reply_id; ?>"></a>
				<label><input type="checkbox" name="delete" value="<?php echo $reply_id; ?>" />&nbsp;
				<?php if ($reply_subject!="") { echo '<span class="filetitle">'.$reply_subject.'</span>&nbsp;'; } ?>
				<span class="commentpostername">
				<?php
				if ($reply_email!="") {
					echo '<a href="';
					if ($reply_email!="age"&&$reply_email!="sage") {
						echo 'mailto:';
					}
					echo $reply_email.'">';
				}
				if ($reply_user==""&&$reply_tripcode=="") {
					echo 'Anonymous';
				} else if ($reply_user==""&&$line['tripcode']!="") {
					echo ''; // If they have a tripcode, just display the tripcode
				} else {
					echo $reply_user;
				}
				if ($reply_email!="") {
					echo '</a>';
				}
				echo '</span>';
				if ($reply_tripcode!="") {
					echo '<span class="postertrip">!'.$reply_tripcode.'</span>';
				}
				if ($reply_posterauthority) {
					if ($reply_posterauthority=="1") {
						echo '&nbsp;<span class="admin">##&nbsp;Admin&nbsp;##</span>';
					} else if ($reply_posterauthority=="2") {
						echo '&nbsp;<span class="mod">##&nbsp;Mod&nbsp;##</span>';
					}
				}
				echo '&nbsp;'.date("y/m/d(D)H:i",$reply_postedat); ?></label> <span class="reflink"><a href="<?php echo $chan_webfolder."/".$board_dir."/res/".$thread_id; ?>.html#<?php echo $reply_id; ?>">No.</a><a href="<?php echo $chan_webfolder."/".$board_dir."/res/".$thread_id; ?>.html#i<?php echo $reply_id; ?>" <?php if ($page==false) { echo ' onclick="insert(\'>>'.$reply_id.'\');"'; } ?>><?php echo $reply_id; ?></a></span>
				<?php echo moderator_deleteandbanlinks($chan_webfolder,$board_dir,$reply_id,false); ?>
				&nbsp;<br />
				<?php
				if ($reply_image!="") {
					?>
					<span class="filesize">File: <a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($reply_image=="removed") { echo $chan_webfolder."/imageremoved.png"; } else { echo $chan_webfolder."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype; } ?>"><?php if ($reply_image=="removed") { echo 'imageremoved.png'; } else { echo $reply_image.'.'.$reply_imagetype; } ?></a> -(<em><?php if ($reply_image=="removed") { echo '&nbsp'; } else { echo round(filesize($chan_rootdir."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype)/1024).' KB, '.$reply_imgWidth.'x'.$reply_imgHeight; }  ?></em>)</span> <span class="thumbnailmsg">Thumbnail displayed, click image for full size.</span><br />
					<a <?php if (config_getvalue("imagesinnewwindow")=="1") { echo "onclick=\"window.open(this.href,'_blank');return false;\""; } ?> href="<?php if ($reply_image=="removed") { echo $chan_webfolder."/imageremoved.png"; } else {echo $chan_webfolder."/".$board_dir."/src/".$reply_image.'.'.$reply_imagetype; } ?>"><img src="<?php if ($reply_image=="removed") { echo $chan_webfolder."/imageremoved.png"; } else { echo $chan_webfolder."/".$board_dir."/thumb/".$reply_image.'s.'.$reply_imagetype; } ?>" width="<?php echo $reply_imgWidth_thumb; ?>" height="<?php echo $reply_imgHeight_thumb; ?>" alt="<?php echo $reply_id; ?>" class="thumb" /></a>
					<?php
				}
				?>
				<blockquote>
				<p><?php if (count(explode("<br />", $reply_message))>15&&$page==true) {
					$i = 0;
					$message_exploded = explode("<br />", $reply_message);
					foreach ($message_exploded as $message_line) {
						$i++;
						if ($i<=15) {
							echo preg_replace('/<a href="\/'.$board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $message_line)."<br />";
						}
					}
					echo '</p><div class="abbrev">Comment too long. Click <a href="'.$chan_webfolder.'/'.$board_dir.'/res/'.$thread_id.'.html">here</a> to view the full text.</div>';
				} else {
					//echo '/[\<a href\="\/b\/res\/'.$thread_id.'\.html#i]([0-9]+)["\>&gt;&gt;]/'.'<br /><br />'.'<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;\\1</a>'.'<br /><br />';
					if ($page==true) {
						echo preg_replace('/<a href="\/'.$board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1">&gt;&gt;', $reply_message);
					} else {
						echo preg_replace('/<a href="\/'.$board_dir.'\/res\/'.$thread_id.'\.html#i([0-9]+)">&gt;&gt;/', '<a href="/'.$board_dir.'/res/'.$thread_id.'.html#\\1" onclick="highlight(\'\\1\');">&gt;&gt;', $reply_message);
					}
					//echo preg_replace('/'.preg_quote('<a href="\/b\/res\/'.$thread_id.'.html#i').'([0-9]+)'.preg_quote('">&gt;&gt;').'/', '<a href="/b/res/'.$thread_id.'.html#\\1">&gt;&gt;',stripslashes($line['message']));
					echo '</p>';
				}
				?></p>
				</blockquote>
				</td>
				</tr>
				</tbody>
				</table>
				<?php
			}
		}
		?>
		<br clear="left" />
		<hr />
		<?php
		if (!$page) {
			echo '<table class="userdelete"><tbody><tr><td>Delete Post [<label><input type="checkbox" name="fileonly" value="on" />File Only</label>]<br />Password <input type="password" name="postpassword" size="8" />&nbsp;<input value="Delete" type="submit" /></td></tr></tbody></table></form>';
		}
		$output = ob_get_clean();
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
function regenerate_thread($board,$threadid) {
	require("config.php");
	$resultboard = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
	while ($lineboard = mysql_fetch_array($resultboard, MYSQL_ASSOC)) {
		if (file_exists($chan_rootdir.'/'.$lineboard['name'].'/res/'.$threadid.'.html')) {
			unlink($chan_rootdir.'/'.$lineboard['name'].'/res/'.$threadid.'.html');
		}
		$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$lineboard['id']."' AND `threadid` = '0' AND `id` = '".$threadid."'",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$thread_page = '';
			$post_id = $line['id'];
			require_once("header.php");
			$thread_page .= chan_header($lineboard['name']);
			$thread_page .= '&#91;<a href="'.$chan_webfolder.'/'.$lineboard['name'].'/board.html">Return</a>&#93; <div class="theader">Posting mode: Reply</div>';
			require_once("postbox.php");
			$thread_page .= chan_postbox($lineboard['name'],$threadid);
			$thread_page .= buildthread($lineboard['name'],$post_id);
			require_once("footer.php");
			$thread_page .= chan_footer();
			print_page($lineboard['name']."/res/".$post_id.".html",$thread_page,$board);
		}
	}
}
function delete_post($post,$board) {
	require("config.php");
	$query = "SELECT * FROM `boards` WHERE `name` = '".$board."'";
	$result = mysql_query($query,$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_id = $line['id'];
		}
		$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);	
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['threadid']=="0") {
				$post_is_thread = true;
			} else {
				$post_is_thread = false;
			}
		}
		if ($post_is_thread==true) {
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".$post."' AND `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if ($line['image']!=""&&$line['image']!="removed") {
						unlink($chan_rootdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
						unlink($chan_rootdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
					}
				}
				$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if ($line['image']!=""&&$line['image']!="removed") {
						unlink($chan_rootdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
						unlink($chan_rootdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
					}
					mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$line['id']."' AND `threadid` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
				}
				unlink($chan_rootdir.'/'.$board.'/res/'.$post.'.html');
				mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
			}
			return true;
		} else {
			$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
			$rows = mysql_num_rows($result);
			if ($rows>0) {
				while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
					if ($line['threadid']!="0") {
						if ($line['image']!="") {
							unlink($chan_rootdir.'/'.$board.'/src/'.$line['image'].'.'.$line['imagetype']);
							unlink($chan_rootdir.'/'.$board.'/thumb/'.$line['image'].'s.'.$line['imagetype']);
						}
						mysql_query("UPDATE `posts` SET `IS_DELETED` = '1' WHERE `id` = '".$post."' AND `boardid` = '".$board_id."'",$dblink);
						return true;
					} else {
						return false;
					}
				}
			}
			return true;
		}
	} else {
		return false;
	}
}
function trimtopagelimit($board) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		//print(time()." Trimming board: ".$board."<br />");
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_id = $line['id'];
			$board_dir = $line['name'];
			$board_desc = $line['desc'];
			$board_maxage = $line['maxage'];
			$board_maxpages = $line['maxpages'];
			$board_locked = $line['locked'];
			$board_redirecttothread = $line['redirecttothread'];
			$board_forcedanon = $line['forcedanon'];
		}
		$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0'",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			if ($line['postedat']+($board_maxage*3600)<time()) {
				delete_post($line['id'],$board_dir);
			}
		}
		$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '0' AND `boardid` = '".$board_id."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows/10>=$board_maxpages) {
			//print(time()." Board is over the limit!<br />");
			$board_maxthreads = $board_maxpages * 10;
			//print(time()." Maximum threads for this board: ".$board_maxthreads."<br />");
			$numthreadsover = $rows - $board_maxthreads;
			//print(time()." This board has ".$rows." threads, which makes it ".$numthreadsover." threads too many!<br />");
			if ($numthreadsover>0) {
				$resultpost = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '0' AND `boardid` = '".$board_id."' AND `stickied` = '0' ORDER BY `lastbumped` ASC LIMIT ".$numthreadsover."",$dblink);
				while ($linepost = mysql_fetch_array($resultpost, MYSQL_ASSOC)) {
					$oldthread_id = $linepost['id'];
					$oldthread_stickied = $linepost['stickied'];
					//print(time()." Found a thread to delete, it is ID # ".$oldthread_id."<br />");
					if ($oldthread_stickied=='0') {
						$result = mysql_query("SELECT * FROM `posts` WHERE `IS_DELETED` = '0' AND  `threadid` = '".$oldthread_id."' AND `boardid` = '".$board_id."'",$dblink);
						while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
							delete_post($line['id'],$board_dir);
						}
						delete_post($oldthread_id,$board_dir);
					}
				}
			}
		}
		//print(time()." Trim complete!<br />");
	} else {
		die("Invalid board.");
	}
}
function display_numuniqueposts($board = "") {
	require("config.php");
	if ($board!="") {
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
			}
			$result = mysql_query("SELECT COUNT(DISTINCT `ip`) FROM `posts` WHERE `IS_DELETED` = '0' AND  `boardid` = '".$board_id."'",$dblink);
			$numuniqueposts = mysql_fetch_row($result);
			return $numuniqueposts[0];
		} else {
			return '0';
		}
	} else {
		$result = mysql_query("SELECT COUNT(DISTINCT `ip`) FROM `posts` WHERE `IS_DELETED` = '0'",$dblink);
		$numuniqueposts = mysql_fetch_row($result);
		return $numuniqueposts[0];
	}
}

//Users
function ban_user($ip,$modname,$duration,$reason) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `banlist` WHERE `ip` = '".$ip."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows==0) {
		if ($duration>0) {
			mysql_query("INSERT INTO `banlist` ( `ip` , `by` , `at` , `until` , `reason` ) VALUES ( '".$ip."' , '".$modname."' , '".time()."' , '".(time()+$duration)."' , '".$reason."' )",$dblink);
			return true;
		} else {
			return false;
		}
	} else {
		return true();
	}
}
function display_bannedmessage($globalban,$boards,$reason,$at,$until) {
	$output = "<title>YOU ARE BANNED!</title><div style=\"text-align: center;\"><img src=\"/youarebanned.jpg\" alt=\":'(\"><br /><h1>YOU ARE BANNED :'(</h1><br />You are banned from posting on: ";
	if ($globalban=='1') {
		$output .= '<b>ALL BOARDS</b>';
	} else {
		$output .= $boards;
	}
	$output .= "<br /><br />Reason: ".stripslashes($reason)."<br /><br />Placed: ".$at."<br />Expires: ".$until."</div>";
	return $output;
}
function removed_expired_bans() {
	require("config.php");
	mysql_query("DELETE FROM `banlist` WHERE `until` != '0' AND `until` < '".time()."'",$dblink);
}

//Posting
function getnextpostid($dblink,$boardid) {
	$result = mysql_query("SELECT * FROM `posts` WHERE `boardid` = '".$boardid."' ORDER BY `id` DESC LIMIT 1",$dblink);	
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			return $line['id']+1;
		}
	} else {
		return '1';
	}
}

//Image handling
function createthumb($name,$filename,$new_w,$new_h) {
	$system=explode(".",$filename);
	$system = array_reverse($system);
	if (preg_match("/jpg|jpeg/",$system[0])) {
		$src_img=imagecreatefromjpeg($name);
	} else if (preg_match("/png/",$system[0])) {
		$src_img=imagecreatefrompng($name);
	} else if (preg_match("/gif/",$system[0])) {
		$src_img=imagecreatefromgif($name);
	} else {
		return false;
	}
	if (!$src_img) {
		echo '<br />Unable to open the uploaded image for thumbnailing.  Maybe its a different filetype, and has the wrong extension?';
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
	/*if ($old_x > $old_y) 
	{
		$thumb_w=$new_w;
		$thumb_h=$old_y*($new_h/$old_x);
	}
	if ($old_x < $old_y) 
	{
		$thumb_w=$old_x*($new_w/$old_y);
		$thumb_h=$new_h;
	}
	if ($old_x == $old_y) 
	{
		$thumb_w=$new_w;
		$thumb_h=$new_h;
	}*/
	$dst_img=ImageCreateTrueColor($thumb_w,$thumb_h);
	imagecopyresampled($dst_img,$src_img,0,0,0,0,$thumb_w,$thumb_h,$old_x,$old_y);
	if (preg_match("/png/",$system[0])) {
		if (!imagepng($dst_img,$filename)) {
			echo 'unable to imagepng.';
			return false;
		}
	} else if (preg_match("/jpg|jpeg/",$system[0])) {
		if (!imagejpeg($dst_img,$filename)) {
			echo 'unable to imagejpg.';
			return false;
		}
	} else if (preg_match("/gif/",$system[0])) {
		if (!imagegif($dst_img,$filename)) { 
			echo 'unable to imagegif.';
			return false;
		}
	}
	imagedestroy($dst_img); 
	imagedestroy($src_img); 
	return true;
}

//Header
function display_boardlist() {
	$output = '';
	/*require("config.php");
	$result = mysql_query("SELECT DISTINCT `section` FROM `boards` ORDER BY `section` ASC",$dblink);
	$rows = mysql_num_rows($result);
	$board_sections = array();
	while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
		$board_sections = array_merge($board_sections,array($line['section']));
	}
	foreach ($board_sections as $board_section) {
		$board_this_section = '';
		$output .= '[';
		$result = mysql_query("SELECT * FROM `boards` WHERE `section` = '".$board_section."' ORDER BY `order` ASC",$dblink);
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$board_this_section .= ' <a title="'.$line['desc'].'" href="'.$chan_webfolder.'/'.$line['name'].'/">'.$line['name'].'</a> /';
		}
		$board_this_section = substr($board_this_section,0,strlen($board_this_section)-1);
		$output .= $board_this_section;
		$output .= ']&nbsp;';
	}*/
	if (is_file("boards.html")) {
		return '<div class="navbar">'.stripslashes(file_get_contents("boards.html")).'</div>';
	} else {
		return '<div class="navbar">'.stripslashes(file_get_contents("../boards.html")).'</div>';
	}
	//return $output;
}
function format_postboxnotice($notice,$board) {
	require("config.php");
	$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
	$rows = mysql_num_rows($result);
	if ($rows>0) {
		while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
			$notice = str_replace('<!tc_maxthumbwidth />',config_getvalue('maxthumbwidth'),$notice);
			$notice = str_replace('<!tc_maxthumbheight />',config_getvalue('maxthumbheight'),$notice);
			$notice = str_replace('<!tc_uniqueposts />',display_numuniqueposts($board),$notice);
			$notice = str_replace('<!tc_maximagekb />',round($line['maximagesize']/1024),$notice);
			$filetypes = '';
			$filetypes_allowed = explode('|',$line['filetypes']);
			if ($filetypes_allowed=='') {
				$filetypes = 'NONE';
			} else {
				foreach ($filetypes_allowed as $filetype) {
					$filetypes .= $filetype.', ';
				}
				$filetypes = substr($filetypes,0,strlen($filetypes)-2);
			}
			$notice = str_replace('<!tc_filetypes />',$filetypes,$notice);
		}
	}
	return $notice;
}
?>