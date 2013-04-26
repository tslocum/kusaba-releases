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
* Upload class
* +------------------------------------------------------------------------------+
* Used for image/misc file upload through the post form on board/thread pages
* +------------------------------------------------------------------------------+
*/

class Upload {
	var $file_name = '';
	var $file_type = '';
	var $file_md5 = '';
	var $file_location = '';
	var $file_thumb_location = '';
	var $file_is_special = false;
	var $imgWidth = 0;
	var $imgHeight = 0;
	var $imgSize = 0;
	var $imgWidth_thumb = 0;
	var $imgHeight_thumb = 0;
	
	function HandleUpload() {
		global $tc_db, $board_class, $is_oekaki, $oekaki;

		if (!$is_oekaki) {
			if ($board_class->board_type == 0 || $board_class->board_type == 2) {
				if ($_FILES['imagefile']['name'] != '') {
					if (strpos($_FILES['imagefile']['name'], ',') != false) {
						die(_('Please select only one image to upload.'));
					}
					
					if (!file_exists($_FILES['imagefile']['tmp_name'])) {
						echo _('Error, it appears your file did not transfer properly.  Please go back and try again.');
					}
					
					$file = substr($_FILES['imagefile']['name'],0,20);
					$this->file_md5 = md5_file($_FILES['imagefile']['tmp_name']);
					
					$exists_thread = check_md5($this->file_md5, $board_class->board_dir);
					if (is_array($exists_thread)) {
						die(_('Duplicate file entry detected.') . '<br><br>'.sprintf(_('Already posted %shere%s.'),'<a href="' . TC_BOARDSPATH . '/' . $board_class->board_dir . '/res/' . $exists_thread[0] . '.html#' . $exists_thread[1] . '">','</a>'));
					}
					
					$this->file_type = substr($_FILES['imagefile']['name'], -4);
					if ($this->file_type == 'jpeg') {
						/* Fix for the rarely used 4-char format */
						$this->file_type = '.jpg';
					}
					
					$imageDim = getimagesize($_FILES['imagefile']['tmp_name']);
					$this->imgWidth = $imageDim[0];
					$this->imgHeight = $imageDim[1];
					$this->file_type = strtolower($this->file_type);
					
					if ($_FILES['imagefile']['size'] > $board_class->board_maximagesize) {
						printf(_('Please make sure your file is smaller than %dB'), $board_class->board_maximagesize);
						die();
					} else {
						$this->imgSize = $_FILES['imagefile']['size'];
						if (in_array(strtoupper(substr($this->file_type, 1)), $board_class->board_filetypes)) {
							if ($this->file_type == '.jpg' || $this->file_type == '.png' || $this->file_type == '.gif') {
								$this->file_name = time() . mt_rand(1, 99);
								
								$this->file_location = TC_BOARDSDIR . $board_class->board_dir . '/src/' . $this->file_name . $this->file_type;
								$this->file_thumb_location = TC_BOARDSDIR . $board_class->board_dir . '/thumb/' . $this->file_name . 's' . $this->file_type;
								
								if (!move_uploaded_file($_FILES['imagefile']['tmp_name'], $this->file_location)) {
									die(_('Could not copy uploaded image.'));
								}
								
								if ($_FILES['imagefile']['size'] == filesize($this->file_location)) {
									if ($this->imgWidth > TC_THUMBWIDTH || $this->imgHeight > TC_THUMBHEIGHT) {
										if (!createthumb($this->file_location, $this->file_thumb_location, TC_THUMBWIDTH, TC_THUMBHEIGHT)) {
											die(_('Could not create thumbnail.'));
										}
									} else {
										if (!createthumb($this->file_location, $this->file_thumb_location, $this->imgWidth, $this->imgHeight)) {
											die(_('Could not create thumbnail.'));
										}
									}
									$imageDim_thumb = getimagesize($this->file_thumb_location);
									$this->imgWidth_thumb = $imageDim_thumb[0];
									$this->imgHeight_thumb = $imageDim_thumb[1];
									$imageused = true;
								} else {
									die(_('File was not fully uploaded.  Please go back and try again.'));
								}
							} else {
								if (!is_file(TC_BOARDSDIR . '' . $board_class->board_dir . '/src/' . $file)) {
									/* Fetch the mime requirement for this special filetype */
									$results = $tc_db->GetAll("SELECT `mime` FROM `" . TC_DBPREFIX . "filetypes` WHERE `filetype` = '" . mysql_real_escape_string(substr($this->file_type, 1)) . "' LIMIT 1");
									foreach ($results as $line) {
										$filetype_required_mime = $line['mime'];
									}
									
									$this->file_name = substr($file, 0, -4);
									$this->file_name = str_replace(' ', '_', $this->file_name);
									$this->file_name = str_replace('#', '(number)', $this->file_name);
									$this->file_name = str_replace('@', '(at)', $this->file_name);
									$this->file_name = str_replace('/', '(fwslash)', $this->file_name);
									$this->file_name = str_replace('\\', '(bkslash)', $this->file_name);
									$this->file_location = TC_BOARDSDIR . $board_class->board_dir . '/src/' . $this->file_name . $this->file_type;
									
									/* Move the file from the post data to the server */
									if (!move_uploaded_file($_FILES['imagefile']['tmp_name'], $this->file_location)) {
										die(_('Could not copy uploaded image.'));
									}
									
									/* Check if the filetype provided comes with a MIME restriction */
									if ($filetype_required_mime != '') {
										/* Check if the MIMEs don't match up */
										if (mime_content_type($this->file_location) != $filetype_required_mime) {
											/* Delete the file we just uploaded and kill the script */
											unlink($this->file_location);
											die(_('Invalid MIME type for this filetype.'));
										}
									}
									
									/* Make sure the entire file was uploaded */
									if ($_FILES['imagefile']['size'] == filesize($this->file_location)) {
										$imageused = true;
									} else {
										die(_('Error, it appears your file did not transfer properly.  Please go back and try again.'));
									}
									
									/* Flag that the file used isn't an internally supported type */
									$this->file_is_special = true;
								} else {
									die(_('There is already a file with that name.'));
								}
							}
						} else {
							die(_('Sorry, that file_type is not allowed on this board.'));
						}
					}
				} elseif (isset($_POST['embed'])) {
					require_once(TC_ROOTDIR . 'inc/checklink.php');
					
					$video_id = $_POST['embed'];
					$this->file_name = $video_id;
					
					if ($video_id != '' && strpos($video_id, '@') == false && strpos($video_id, '&') == false) {
						if ($_POST['embedtype'] == 'youtube') {
							$videourl_start = 'http://www.youtube.com/watch?v=';
							$this->file_type = '.you';
						} elseif ($_POST['embedtype'] == 'google') {
							$videourl_start = 'http://video.google.com/videoplay?docid=';
							$this->file_type = '.goo';
						} else {
							die(_('Invalid video type.'));
						}
						
						$results = $tc_db->GetOne("SELECT COUNT(*) FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `image` = '" . mysql_real_escape_string($video_id) . "' AND `IS_DELETED` = 0");
						if ($results[0] == 0) {
							$video_check = check_link($videourl_start . $video_id);
							switch ($video_check[1]) {
								case 404:
									die(_('Unable to connect to: ') . $videourl_start . $video_id);
									break;
								case 303:
									die(_('Invalid video ID.'));
									break;
								case 200:
									/* Continue */
									break;
								default:
									die(_('Invalid response code: ') . $video_check[1]);
									break;
							}
						} else {
							$results = $tc_db->GetAll("SELECT `id`,`threadid` FROM `" . TC_DBPREFIX . "posts_" . $board_class->board_dir . "` WHERE `image` = '" . mysql_real_escape_string($video_id) . "' AND `IS_DELETED` = 0 LIMIT 1");
							foreach ($results as $line) {
								$real_threadid = ($line[1] == 0) ? $line[0] : $line[1];
								echo sprintf(_('That video ID has already been posted %shere%s.'),'<a href="' . TC_BOARDSFOLDER . '/' . $board_class->board_dir . '/res/' . $real_threadid . '.html#' . $line[1] . '">','</a>');
							}
						}
					} else {
						die(_('Invalid ID'));
					}
				}
			}
		} else {
			$this->file_name = time() . mt_rand(1, 99);
			$this->file_md5 = md5_file($oekaki);
			$this->file_type = '.png';
			$this->imgSize = filesize($oekaki);
			$imageDim = getimagesize($oekaki);
			$this->imgWidth = $imageDim[0];
			$this->imgHeight = $imageDim[1];
			
			if (!copy($oekaki, TC_BOARDSDIR . $board_class->board_dir . '/src/' . $this->file_name . $this->file_type)) {
				die(_('Could not copy uploaded image.'));
			}
			
			$oekaki_animation = substr($oekaki, 0, -4) . '.pch';
			if (file_exists($oekaki_animation)) {
				if (!copy($oekaki_animation, TC_BOARDSDIR . $board_class->board_dir . '/src/' . $this->file_name . '.pch')) {
					die(_('Could not copy animation.'));
				}
				unlink($oekaki_animation);
			}
			
			$thumbpath = TC_BOARDSDIR . $board_class->board_dir . '/thumb/' . $this->file_name . 's' . $this->file_type;
			if ($this->imgWidth > TC_THUMBWIDTH || $this->imgHeight > TC_THUMBHEIGHT) {
				if (!createthumb($oekaki, $thumbpath, TC_THUMBWIDTH, TC_THUMBHEIGHT)) {
					die(_('Could not create thumbnail.'));
				}
			} else {
				if (!createthumb($oekaki, $thumbpath, $this->imgWidth, $this->imgHeight)) {
					die(_('Could not create thumbnail.'));
				}
			}
			
			$imgDim_thumb = getimagesize($thumbpath);
			$this->imgWidth_thumb = $imgDim_thumb[0];
			$this->imgHeight_thumb = $imgDim_thumb[1];
			unlink($oekaki);
		}
	}
}
?>
