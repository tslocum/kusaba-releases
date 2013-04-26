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
 * Manage Class
 * +------------------------------------------------------------------------------+
 * Manage functions, along with the pages available
 * +------------------------------------------------------------------------------+
 */
class Manage {

	/* Show the header of the manage page */
	function Header() {
		global $tc_db, $smarty, $tpl_page;
		
		if (is_file(TC_ROOTDIR . 'inc/pages/modheader.html')) {
			$tpl_includeheader = file_get_contents(TC_ROOTDIR . 'inc/pages/modheader.html');
		} else {
			$tpl_includeheader = '';
		}
		
		$smarty->assign('includeheader', $tpl_includeheader);
	}
	
	/* Show the footer of the manage page */
	function Footer() {
		global $tc_db, $smarty, $tpl_page;
		
		$smarty->assign('page', $tpl_page);
		
		$board_class = new Board('');
		$smarty->assign('footer', $board_class->Footer(true));
		
		$smarty->display('manage.tpl');
	}
	
	/* Validate the current session */
	function ValidateSession($is_menu = false) {
		global $tc_db, $smarty, $tpl_page;
	
		if (isset($_SESSION['manageusername']) && isset($_SESSION['managepassword'])) {
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `username` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "' AND `password` = '" . mysql_real_escape_string($_SESSION['managepassword']) . "' LIMIT 1");
			if (count($results) == 0) {
				session_destroy();
				die(_('Invalid session.') . "<br><br><a href=\"manage_page.php\">" . _('Log in again.') . "</a>");
			}
			return true;
		} else {
			if (!$is_menu) {
				$this->LoginForm();
				die($tpl_page);
			} else {
				return false;
			}
		}
	}
	
	/* Show the login form and halt execution */
	function LoginForm() {
		global $tc_db, $smarty, $tpl_page;
		
		if (file_exists(TC_ROOTDIR . 'inc/pages/manage_login.html')) {
			$tpl_page .= file_get_contents(TC_ROOTDIR . 'inc/pages/manage_login.html');
		}
	}
	
	/* Check login names and create session if user/pass is correct */
	function CheckLogin() {
		global $tc_db, $smarty, $tpl_page, $action;
		
		$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "loginattempts` WHERE `timestamp` < '" . (time() - 1200) . "'");
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `ip` FROM `" . TC_DBPREFIX . "loginattempts` WHERE `ip` = '" . $_SERVER['REMOTE_ADDR'] . "' LIMIT 6");
		if (count($results) > 5) {
			die(_('Sorry, because of your numerous failed logins, you have been locked out from logging in for 20 minutes.  Please wait and then try again.'));
		} else {
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `username` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_POST['username']) . "' AND `password` = '" . md5($_POST['password']) . "' LIMIT 1");
			if (count($results) > 0) {
				$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "loginattempts` WHERE `ip` < '" . $_SERVER['REMOTE_ADDR'] . "'");
				$_SESSION['manageusername'] = $_POST['username'];
				$_SESSION['managepassword'] = md5($_POST['password']);
				$this->SetModerationCookies();
				$action = 'posting_rates';
				management_addlogentry(_('Logged in'), 1);
				die('<script type="text/javascript">top.location.href = \'' . TC_BOARDSPATH . '/manage.php\';</script>');
			} else {
				$tc_db->Execute("INSERT HIGH_PRIORITY INTO `" . TC_DBPREFIX . "loginattempts` ( `username` , `ip` , `timestamp` ) VALUES ( '" . mysql_real_escape_string($_POST['username']) . "' , '" . $_SERVER['REMOTE_ADDR'] . "' , '" . time() . "' )");
				die(_('Incorrect username/password.'));
			}
		}
	}
	
	/* Set mod cookies for boards */
	function SetModerationCookies() {
		global $tc_db, $smarty, $tpl_page;
		
		if (isset($_SESSION['manageusername'])) {
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "' LIMIT 1");
			if (count($results) > 0) {
				if ($this->CurrentUserIsAdministrator()) {
					$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards`");
					foreach ($resultsboard as $lineboard) {
						setcookie("tcmod", "yes", time() + 3600, TC_BOARDSFOLDER . $lineboard['name'] . "/");
					}
				} else {
					foreach ($results as $line) {
						$array_boards = explode('|', $line['boards']);
					}
					foreach ($array_boards as $this_board_name) {
						setcookie("tcmod", "yes", time() + 3600, TC_BOARDSFOLDER . $this_board_name . "/");
					}
				}
			}
		}
	}

	/* Log current user out */
	function Logout() {
		global $tc_db, $smarty, $tpl_page;
		
		$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards`");
		foreach ($resultsboard as $lineboard) {
			setcookie('tcmod', '', 0, TC_BOARDSFOLDER . $lineboard['name'] . '/');
		}
		
		session_destroy();
		unset($_SESSION['manageusername']);
		unset($_SESSION['managepassword']);
		die('<script type="text/javascript">top.location.href = \'' . TC_BOARDSPATH . '/manage.php\';</script>');
		
		die($tpl_page);
	}
	
	/*
	 * +------------------------------------------------------------------------------+
	 * Manage pages
	 * +------------------------------------------------------------------------------+
	 */          

	/* Add, view, and delete sections */
	function editsections() {
		global $tc_db, $smarty, $tpl_page;
		
		$this->AdministratorsOnly();
		$tpl_page .= '<h2>' . ucwords(_('Edit sections')) . '</h2><br>';
		if ($_GET['do'] == 'addsection') {
			if ($_POST['name'] != '' && $_POST['abbreviation'] != '') {
				$tc_db->Execute("INSERT HIGH_PRIORITY INTO `" . TC_DBPREFIX . "sections` ( `name` , `abbreviation` , `order` , `hidden` ) VALUES ( '" . mysql_real_escape_string($_POST['name']) . "' , '" . mysql_real_escape_string($_POST['abbreviation']) . "' , '" . mysql_real_escape_string($_POST['order']) . "' , '" . (isset($_POST['hidden']) ? '1' : '0') . "' )");
				$tpl_page .= _('Section added.');
			} else {
				$tpl_page .= '<form action="?action=editsections&do=addsection" method="post">
				<label for="name">Name:</label><input type="text" name="name"><div class="desc">The name of the section</div><br>
				<label for="abbreviation">Abbreviation:</label><input type="text" name="abbreviation"><div class="desc">Abbreviation (less then 10 characters)</div><br>
				<label for="order">Order:</label><input type="text" name="order"><div class="desc">Order to show this section with others, in ascending order</div><br>
				<label for="hidden">Hidden:</label><input type="checkbox" name="hidden" ><div class="desc">If checked, this section will be collapsed by default when a user visits the site.</div><br>
				<input type="submit" value="Add">
				</form>';
			}
			$tpl_page .= '<br><hr>';
		}
		if ($_GET['do'] == 'editsection' && $_GET['sectionid'] > 0) {
			if ($_POST['name'] != '' && $_POST['abbreviation'] != '') {
				$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "sections` SET `name` = '" . mysql_real_escape_string($_POST['name']) . "' , `abbreviation` = '" . mysql_real_escape_string($_POST['abbreviation']) . "' , `order` = '" . mysql_real_escape_string($_POST['order']) . "' , `hidden` = '" . (isset($_POST['hidden']) ? '1' : '0') . "' WHERE `id` = '" . $_GET['sectionid'] . "'");
				$tpl_page .= _('Section updated.');
			} else {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "sections` WHERE `id` = '" . mysql_real_escape_string($_GET['sectionid']) . "'");
				if (count($results) > 0) {
					foreach ($results as $line) {
						$tpl_page .= '<form action="?action=editsections&do=editsection&sectionid=' . $_GET['sectionid'] . '" method="post">
						<input type="hidden" name="id" value="' . $_GET['sectionid'] . '">
						
						<label for="name">Name:</label>
						<input type="text" name="name" value="' . $line['name'] . '">
						<div class="desc">The name of the section</div><br>
						
						<label for="abbreviation">Abbreviation:</label>
						<input type="text" name="abbreviation" value="' . $line['abbreviation'] . '">
						<div class="desc">Abbreviation (less then 10 characters)</div><br>
						
						<label for="order">Order:</label>
						<input type="text" name="order" value="' . $line['order'] . '">
						<div class="desc">Order to show this section with others, in ascending order</div><br>
						
						<label for="hidden">Hidden:</label>
						<input type="checkbox" name="hidden" ' . ($line['hidden'] == 0 ? '' : 'checked') . '>
						<div class="desc">If checked, this section will be collapsed by default when a user visits the site.</div><br>
						
						<input type="submit" value="Edit">
						
						</form>';
					}
				} else {
					$tpl_page .= _('Unable to locate a section with that ID.');
				}
			}
			$tpl_page .= '<br><hr>';
		}
		if ($_GET['do'] == 'deletesection' && $_GET['sectionid'] > 0) {
			$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "sections` WHERE `id` = '" . mysql_real_escape_string($_GET['sectionid']) . "'");
			$tpl_page .= _('Section deleted.');
			$tpl_page .= '<br><hr>';
		}
		$tpl_page .= '<a href="?action=editsections&do=addsection">Add section</a><br><br>';
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "sections` ORDER BY `order` ASC");
		if (count($results) > 0) {
			$tpl_page .= '<table border="1"><tr><th>'.('ID').'</th><th>'.('Order').'</th><th>Abbreviation</th><th>Name</th><th>Edit/Delete</th></tr>';
			foreach ($results as $line) {
				$tpl_page .= '<tr><td>' . $line['id'] . '</td><td>' . $line['order'] . '</td><td>' . $line['abbreviation'] . '</td><td>' . $line['name'] . '</td><td><a href="?action=editsections&do=editsection&sectionid=' . $line['id'] . '">Edit</a> <a href="?action=editsections&do=deletesection&sectionid=' . $line['id'] . '">Delete</a></td></tr>';
			}
			$tpl_page .= '</table>';
		} else {
			$tpl_page .= _('There are currently no sections.');
		}
	}
	
	/* Add, view, and delete filetypes */
	function editfiletypes() {
		global $tc_db, $smarty, $tpl_page;
		
		$this->AdministratorsOnly();
		$tpl_page .= '<h2>' . ucwords(_('Edit filetypes')) . '</h2><br>';
		if (isset($_GET['do'])) {
			if ($_GET['do'] == 'addfiletype') {
				if (isset($_POST['filetype']) || isset($_POST['image'])) {
					if ($_POST['filetype'] != '' && $_POST['image'] != '') {
						$tc_db->Execute("INSERT HIGH_PRIORITY INTO `" . TC_DBPREFIX . "filetypes` ( `filetype` , `mime` , `image` , `image_w` , `image_h` ) VALUES ( '" . mysql_real_escape_string($_POST['filetype']) . "' , '" . mysql_real_escape_string($_POST['mime']) . "' , '" . mysql_real_escape_string($_POST['image']) . "' , '" . mysql_real_escape_string($_POST['image_w']) . "' , '" . mysql_real_escape_string($_POST['image_h']) . "' )");
						$tpl_page .= _('Filetype added.');
					}
				} else {
					$tpl_page .= '<form action="?action=editfiletypes&do=addfiletype" method="post">
					<label for="filetype">Filetype:</label>
					<input type="text" name="filetype">
					<div class="desc">The extension this will be applied to.  <b>Must be lowercase</b></div><br>
					
					<label for="mime">MIME type:</label>
					<input type="text" name="mime">
					<div class="desc">The MIME type which must be present with an image uploaded in this type.  Leave blank to disable.</div><br>
					
					<label for="image">Image:</label>
					<input type="text" name="image" value="generic.png">
					<div class="desc">The image which will be used, found in inc/filetypes.</div><br>
					
					<label for="image_w">Image width:</label>
					<input type="text" name="image_w" value="48">
					<div class="desc">The height of the image.  Needs to be set to prevent the page from jumping around while images load.</div><br>
					
					<label for="image_h">Image height:</label>
					<input type="text" name="image_h" value="48">
					<div class="desc">See above.</div><br>
					
					<input type="submit" value="Add">
					
					</form>';
				}
				$tpl_page .= '<br><hr>';
			}
			if ($_GET['do'] == 'editfiletype' && $_GET['filetypeid'] > 0) {
				if ($_POST['filetype'] != '' && $_POST['image'] != '') {
					$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "filetypes` SET `filetype` = '" . mysql_real_escape_string($_POST['filetype']) . "' , `mime` = '" . mysql_real_escape_string($_POST['mime']) . "' , `image` = '" . mysql_real_escape_string($_POST['image']) . "' , `image_w` = '" . mysql_real_escape_string($_POST['image_w']) . "' , `image_h` = '" . mysql_real_escape_string($_POST['image_h']) . "' WHERE `id` = '" . mysql_real_escape_string($_GET['filetypeid']) . "'");
					$tpl_page .= _('Filetype updated.');
				} else {
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "filetypes` WHERE `id` = '" . mysql_real_escape_string($_GET['filetypeid']) . "'");
					if (count($results) > 0) {
						foreach ($results as $line) {
							$tpl_page .= '<form action="?action=editfiletypes&do=editfiletype&filetypeid=' . $_GET['filetypeid'] . '" method="post">
							
							<label for="filetype">Filetype:</label>
							<input type="text" name="filetype" value="' . $line['filetype'] . '">
							<div class="desc">The extension this will be applied to.  <b>Must be lowercase</b></div><br>
							
							<label for="mime">MIME type:</label>
							<input type="text" name="mime" value="' . $line['mime'] . '">
							<div class="desc">The MIME type which must be present with an image uploaded in this type.  Leave blank to disable.</div><br>
							
							<label for="image">Image:</label>
							<input type="text" name="image" value="' . $line['image'] . '">
							<div class="desc">The image which will be used, found in inc/filetypes.</div><br>
							
							<label for="image_w">Image width:</label>
							<input type="text" name="image_w" value="' . $line['image_w'] . '">
							<div class="desc">The height of the image.  Needs to be set to prevent the page from jumping around while images load.</div><br>
							
							<label for="image_h">Image height:</label>
							<input type="text" name="image_h" value="' . $line['image_h'] . '">
							<div class="desc">See above.</div><br>
							
							<input type="submit" value="Edit">
							
							</form>';
						}
					} else {
						$tpl_page .= _('Unable to locate a filetype with that ID.');
					}
				}
				$tpl_page .= '<br><hr>';
			}
			if ($_GET['do'] == 'deletefiletype' && $_GET['filetypeid'] > 0) {
				$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "filetypes` WHERE `id` = '" . mysql_real_escape_string($_GET['filetypeid']) . "'");
				$tpl_page .= _('Filetype deleted.');
				$tpl_page .= '<br><hr>';
			}
		}
		$tpl_page .= '<a href="?action=editfiletypes&do=addfiletype">Add filetype</a><br><br>';
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "filetypes` ORDER BY `filetype` ASC");
		if (count($results) > 0) {
			$tpl_page .= '<table border="1"><tr><th>ID</th><th>Filetype</th><th>Image</th><th>Edit/Delete</th></tr>';
			foreach ($results as $line) {
				$tpl_page .= '<tr><td>' . $line['id'] . '</td><td>' . $line['filetype'] . '</td><td>' . $line['image'] . '</td><td><a href="?action=editfiletypes&do=editfiletype&filetypeid=' . $line['id'] . '">Edit</a> <a href="?action=editfiletypes&do=deletefiletype&filetypeid=' . $line['id'] . '">Delete</a></td></tr>';
			}
			$tpl_page .= '</table>';
		} else {
			$tpl_page .= _('There are currently no filetypes.');
		}
	}
	
	/* Rebuild all boards */
	function rebuildall() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . ucwords(_('Rebuild all html files')) . '</h2><br>';
		$time_start = time();
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `id`, `name` FROM `" . TC_DBPREFIX . "boards`");
		foreach ($results as $line) {
			$board_class = new Board($line['name']);
			$board_class->RegenerateAll();
			$tpl_page .= sprintf(_('Regenerated %s'), '/' . $line['name'] . '/') . '<br>';
			unset($board_class);
			flush();
		}
		$tpl_page .= sprintf(_('Rebuild complete.  Took <b>%d</b> seconds.'), time() - $time_start);
		management_addlogentry(_('Rebuilt all boards and threads'), 2);
		unset($board_class);
	}
	
	/* Display an iframe with the latest version, and the current version installed above it */
	function checkversion() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . ucwords(_('Check for new version')) . '</h2><br>';
		$tpl_page .= _('Current version:') . ' v'.TC_VERSION;
		$tpl_page .= '<br><iframe src="http://www.trevorchan.org/version.php">';
	}
	
	/* Display disk space used per board, and finally total in a large table */
	function spaceused() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . ucwords(_('Disk space used')) . '</h2><br>';
		$spaceused_res = 0;
		$spaceused_src = 0;
		$spaceused_thumb = 0;
		$spaceused_total = 0;
		$files_res = 0;
		$files_src = 0;
		$files_thumb = 0;
		$files_total = 0;
		$tpl_page .= '<table border="1" width="100%"><tr><th>Board</th><th>Area</th><th>Files</th><th>Space Used</th></tr>';
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards`");
		foreach ($results as $line) {
		list($spaceused_board_res, $files_board_res) = recursive_directory_size(TC_BOARDSDIR . $line['name'] . '/res');
		list($spaceused_board_src, $files_board_src) = recursive_directory_size(TC_BOARDSDIR . $line['name'] . '/src');
		list($spaceused_board_thumb, $files_board_thumb) = recursive_directory_size(TC_BOARDSDIR . $line['name'] . '/thumb');
		
		$spaceused_board_total = $spaceused_board_res + $spaceused_board_src + $spaceused_board_thumb;
		$files_board_total = $files_board_res + $files_board_src + $files_board_thumb;
		
		$spaceused_res += $spaceused_board_res;
		$files_res += $files_board_res;
		
		$spaceused_src += $spaceused_board_src;
		$files_src += $files_board_src;
		
		$spaceused_thumb += $spaceused_board_thumb;
		$files_thumb += $files_board_thumb;
		
		$spaceused_total += $spaceused_board_total;
		$files_total += $files_board_total;
		
		$tpl_page .= '<tr><td rowspan="4">/'.$line['name'].'/</td><td>res/</td><td>' . number_format($files_board_res) . '</td><td>' . ConvertBytes($spaceused_board_res) . '</td></tr>';
		$tpl_page .= '<tr><td>src/</td><td>' . number_format($files_board_src) . '</td><td>' . ConvertBytes($spaceused_board_src) . '</td></tr>';
		$tpl_page .= '<tr><td>thumb/</td><td>' . number_format($files_board_thumb) . '</td><td>' . ConvertBytes($spaceused_board_thumb) . '</td></tr>';
		$tpl_page .= '<tr><td><b>Total</b></td><td>' . number_format($files_board_total) . '</td><td>' . ConvertBytes($spaceused_board_total) . '</td></tr>';
		}
		$tpl_page .= '<tr><td rowspan="4"><b>All boards</b></td><td>res/</td><td>' . number_format($files_res) . '</td><td>' . ConvertBytes($spaceused_res) . '</td></tr>';
		$tpl_page .= '<tr><td>src/</td><td>' . number_format($files_src) . '</td><td>' . ConvertBytes($spaceused_src) . '</td></tr>';
		$tpl_page .= '<tr><td>thumb/</td><td>' . number_format($files_thumb) . '</td><td>' . ConvertBytes($spaceused_thumb) . '</td></tr>';
		$tpl_page .= '<tr><td><b>Total</b></td><td>' . number_format($files_total) . '</td><td>' . ConvertBytes($spaceused_total) . '</td></tr>';
		$tpl_page .= '</table>';
	}
	
	/* Display moderators and administrators actions which were logged */
	function modlog() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . ('ModLog') . '</h2><br>';
		$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "modlog` WHERE `timestamp` < '" . (time() - TC_MODLOGDAYS * 86400) . "'");
		$tpl_page .= '<h2>'._('ModLog').'</h2><table cellspacing="2" cellpadding="1" border="1"><tr><th>Time</th><th>User</th><th width="100%">Action</th></tr>';
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "modlog` ORDER BY `timestamp` DESC");
		foreach ($results as $line) {
			$tpl_page .= "<tr><td>" . date("y/m/d(D)H:i", $line['timestamp']) . "</td><td>" . $line['user'] . "</td><td>" . $line['entry'] . "</td></tr>";
		}
		$tpl_page .= '</table>';
	}
	
	/* Allow SQL injection for administrators */
	function sql() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . _('SQL query') . '</h2><br>';
		if (isset($_POST['query'])) {
			$tpl_page .= '<hr>';
			$result = $tc_db->Execute($_POST['query']);
			if ($result) {
				$tpl_page .= _('Query executed successfully');
			} else {
				$tpl_page .= 'Error: ' . $tc_db->ErrorMsg();
			}
			$tpl_page .= '<hr>';
		}
		$tpl_page .= '<form method="post" action="?action=sql">
		
		<textarea name="query" rows="20" cols="60"></textarea><br>
		
		<input type="submit" value="' . _('Inject') . '">
		
		</form>';
	}
	
	/* Add, edit, delete, and view news entries */
	function news() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		if (isset($_GET['edit'])) {
			if (isset($_POST['news'])) {
				$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "news` SET `subject` = '" . $_POST['subject'] . "', `message` = '" . $_POST['news'] . "', `postedemail` = '" . $_POST['postedemail'] . "' WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
				$tpl_page .= '<h3>News post edited</h3>';
			}
			$tpl_page .= '<h1>Edit news post</h1>';
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "news` WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
			foreach ($results as $line) {
			$tpl_page .= '<form method="post" action="?action=news&edit=' . $_GET['edit'] . '">
			<label for="subject">' . _('Subject') . ':</label>
			<input type="text" name="subject" value="' . $line['subject'] . '">
			<div class="desc">' . _('Can not be left blank.') . '</div><br>
			
			<textarea name="news" rows="25" cols="80">' . $line['message'] . '</textarea><br>
			
			<label for="email">' . _('E-mail') . ':</label>
			<input type="text" name="email" value="' . $line['postedemail'] . '">
			<div class="desc">' . _('Can be left blank.') . '</div><br>
			<input type="submit" value="Edit">
			</form>';
			}
		} elseif (isset($_GET['delete'])) {
			$results = $tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "news` WHERE `id` = '" . mysql_real_escape_string($_GET['delete']) . "'");
			$tpl_page .= '<h3>News post deleted</h3>';
		} else {
			$tpl_page .= _('<h2>Add News Post</h2>This message will be displayed as it is written, so make sure you add the proper HTML.') . '<br><br>';
			if (isset($_POST['news']) && isset($_POST['subject']) && isset($_POST['email'])) {
				if ($_POST['news'] != '') {
					$tpl_page .= '<hr>';
					if ($_POST['subject'] != '') {
						$tc_db->Execute("INSERT HIGH_PRIORITY INTO `" . TC_DBPREFIX . "news` ( `subject` , `message` , `postedat` , `postedby` , `postedemail` ) VALUES ( '" . mysql_real_escape_string($_POST['subject']) . "' , '" . mysql_real_escape_string($_POST['news']) . "' , '" . time() . "' , '" . mysql_real_escape_string($_SESSION['manageusername']) . "' , '" . mysql_real_escape_string($_POST['email']) . "' )");
						$tpl_page .= '<h3>' . _('News entry successfully added.') . '</h3>';
						management_addlogentry(_('Added a news entry'), 9);
					} else {
						$tpl_page .= _('You must enter a subject.');
					}
					$tpl_page .= '<hr>';
				}
			}
			$tpl_page .= '<form method="post" action="?action=news">
			<label for="subject">' . _('Subject') . ':</label>
			<input type="text" name="subject" value="">
			<div class="desc">' . _('Can not be left blank.') . '</div><br>
			
			<textarea name="news" rows="25" cols="80"></textarea><br>
			
			<label for="email">' . _('E-mail') . ':</label>
			<input type="text" name="email" value="">
			<div class="desc">' . _('Can be left blank.') . '</div><br>
			
			<input type="submit" value="' . _('Add') . '">
			</form>';
			
			$tpl_page .= '<br><hr><h1>Edit/Delete News</h1>';
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "news`");
			if (count($results) > 0) {
				$tpl_page .= '<table border="1"><tr><th>Date Added</th><th>Subject</th><th>Message</th><th>Edit/Delete</th></tr>';
				foreach ($results as $line) {
					$tpl_page .= '<tr><td>' . date('F j, Y, g:i a', $line['postedat']) . '</td><td>' . $line['subject'] . '</td><td>' . $line['message'] . '</td><td><a href="?action=news&edit=' . $line['id'] . '">Edit</a>/<a href="?action=news&delete=' . $line['id'] . '">Delete</a></td></tr>';
				}
				$tpl_page .= '</table>';
			} else {
				$tpl_page .= 'No news posts yet.';
			}
		}
	}
	
	/* Edit a boards options */
	function boardopts() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . ucwords(_('Board options')) . '</h2><br>';
		if (isset($_GET['updateboard']) && isset($_POST['order']) && isset($_POST['maxpages']) && isset($_POST['maxage']) && isset($_POST['messagelength'])) {
			if (!$this->CurrentUserIsModeratorOfBoard($_GET['updateboard'], $_SESSION['manageusername'])) {
				die(_('You are not a moderator of this board.'));
			}
			$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['updateboard']) . "'");
			if (count($resultsboard) > 0) {
				if ($_POST['order'] >= 0 && $_POST['maxpages'] >= 0 && $_POST['maxage'] >= 0 && $_POST['messagelength'] >= 0 && ($_POST['enablereporting'] == '0' || $_POST['enablereporting'] == '1') && ($_POST['enablecaptcha'] == '0' || $_POST['enablecaptcha'] == '1') && ($_POST['trial'] == '0' || $_POST['trial'] == '1') && ($_POST['popular'] == '0' || $_POST['popular'] == '1') && ($_POST['defaultstyle'] == 'Burichan' || $_POST['defaultstyle'] == 'Futaba' || $_POST['defaultstyle'] == 'Gurochan' || $_POST['defaultstyle'] == 'Photon' || $_POST['defaultstyle'] == 'Fuhrerchan')) {
					$filetypes = array();
					while (list($postkey, $postvalue) = each($_POST)) {
						if (substr($postkey, 0, 9) == 'filetype_') {
							$filetypes[] = strtoupper(substr($postkey, 9));
						}
					}
					if (isset($_POST['locked'])) {
						$updateboard_locked = $_POST['locked'] == "on" ? "1" : "0";
					} else {
						$updateboard_locked = "0";
					}
					if (($_POST['type'] == '0' || $_POST['type'] == '1' || $_POST['type'] == '2') && ($_POST['uploadtype'] == '0' || $_POST['uploadtype'] == '1' || $_POST['uploadtype'] == '2')) {
						$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "boards` SET `type` = '" . mysql_real_escape_string($_POST['type']) . "' , `uploadtype` = '" . mysql_real_escape_string($_POST['uploadtype']) . "' , `order` = '" . mysql_real_escape_string($_POST['order']) . "' , `section` = '" . mysql_real_escape_string($_POST['section']) . "' , `desc` = '" . mysql_real_escape_string($_POST['desc']) . "' , `filetypes` = '" . implode('|', $filetypes) . "' , `locked` = '" . $updateboard_locked . "' , `maximagesize` = '" . mysql_real_escape_string($_POST['maximagesize']) . "' , `messagelength` = '" . mysql_real_escape_string($_POST['messagelength']) . "' , `maxpages` = '" . mysql_real_escape_string($_POST['maxpages']) . "' , `maxage` = '" . mysql_real_escape_string($_POST['maxage']) . "' , `maxreplies` = '" . mysql_real_escape_string($_POST['maxreplies']) . "' , `image` = '" . mysql_real_escape_string($_POST['image']) . "' , `includeheader` = '" . mysql_real_escape_string($_POST['includeheader']) . "' , `redirecttothread` = '" . mysql_real_escape_string($_POST['redirecttothread']) . "' , `forcedanon` = '" . mysql_real_escape_string($_POST['forcedanon']) . "' , `trial` = '" . mysql_real_escape_string($_POST['trial']) . "' , `popular` = '" . mysql_real_escape_string($_POST['popular']) . "' , `defaultstyle` = '" . $_POST['defaultstyle'] . "' , `enablereporting` = '" . mysql_real_escape_string($_POST['enablereporting']) . "' , `enablecaptcha` = '" . mysql_real_escape_string($_POST['enablecaptcha']) . "' , `enablenofile` = '" . mysql_real_escape_string($_POST['enablenofile']) . "' WHERE `name` = '" . mysql_real_escape_string($_GET['updateboard']) . "'");
						$tpl_page .= _('Update successful.');
						management_addlogentry(_('Updated board configuration') . " - /" . $_GET['updateboard'] . "/", 4);
					} else {
						$tpl_page .= _('Sorry, a generic error has occurred.');
					}
				} else {
					$tpl_page .= _('Integer values must be entered correctly.');
				}
			} else {
				$tpl_page .= _('Unable to locate a board named') . ' <b>' . $_GET['updateboard'] . '</b>.';
			}
		} elseif (isset($_POST['board'])) {
			if (!$this->CurrentUserIsModeratorOfBoard($_POST['board'], $_SESSION['manageusername'])) {
				die(_('You are not a moderator of this board.'));
			}
			$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['board']) . "'");
			if (count($resultsboard) > 0) {
				foreach ($resultsboard as $lineboard) {
					$tpl_page .= '<div class="container">
					<form action="?action=boardopts&updateboard='.$_POST['board'].'" method="post">';
					
					/* Directory */
					$tpl_page .= '<label for="board">'._('Directory').':</label>
					<input type="text" name="board" value="'.$_POST['board'].'" disabled>
					<div class="desc">'._('The directory of the board.').'</div><br>';
					
					/* Description */
					$tpl_page .= '<label for="desc">'._('Description').':</label>
					<input type="text" name="desc" value="'.$lineboard['desc'].'">
					<div class="desc">'._('The name of the board.').'</div><br>';
					
					/* Board type */
					$tpl_page .= '<label for="type">'._('Board type:').'</label>
					<select name="type">
					<option value="0"';
					if ($lineboard['type'] == '0') { $tpl_page .= ' selected'; }
					$tpl_page .= '>'._('Normal imageboard').'</option>
					<option value="1"';
					if ($lineboard['type'] == '1') { $tpl_page .= ' selected'; }
					$tpl_page .= '>'._('Text board').'</option><option value="2"';
					if ($lineboard['type'] == '2') { $tpl_page .= ' selected'; }
					$tpl_page .= '>'._('Oekaki imageboard').'</option>
					</select>
					<div class="desc">'._('The type of posts which will be accepted on this board.  A normal imageboard will feature image and extended format posts, a text board will have no images, and an Oekaki board will allow users to draw images and use them in their posts.').' '._('Default').': <b>Normal Imageboard</b></div><br>';
					
					/* Upload type */
					$tpl_page .= '<label for="uploadtype">'._('Upload type:').'</label>
					<select name="uploadtype">
					<option value="0"';
					if ($lineboard['uploadtype'] == '0') {
						$tpl_page .= ' selected';
					}
					$tpl_page .= '>'._('No embedding').'</option>
					<option value="1"';
					if ($lineboard['uploadtype'] == '1') {
						$tpl_page .= ' selected';
					}
					$tpl_page .= '>'._('Images and embedding').'</option>
					<option value="2"';
					if ($lineboard['uploadtype'] == '2') {
						$tpl_page .= ' selected';
					}
					$tpl_page .= '>'._('Embedding only').'</option>
					</select>
					<div class="desc">'._('Whether or not to allow embedding of videos.').' '._('Default').'.: <b>No Embedding</b></div><br>';
					
					/* Order */
					$tpl_page .= '<label for="order">'._('Order').':</label>
					<input type="text" name="order" value="'.$lineboard['order'].'">
					<div class="desc">'._('Order to show board in menu list, in ascending order.').' '._('Default').': <b>0</b></div><br>';
					
					/* Section */
					$tpl_page .= '<label for="section">'._('Section').':</label>
					<input type="text" name="section" value="'.$lineboard['section'].'">
					<div class="desc">'._('The section the board is in.  This is used for displaying the list of boards on the top and bottom of pages.').'<br>If this is set to 0, <b>it will not be shown in the menu</b>.</div><br>';
					
					/* Locked */
					$tpl_page .= '<label for="locked">'._('Locked').': (<img src="'.TC_BOARDSPATH.'/locked.gif" alt="Lock">)</label>
					<input type="checkbox" name="locked" ';
					if ($lineboard['locked'] == "1") {
						$tpl_page .= 'checked ';
					}
					$tpl_page .= '>
					<div class="desc">'._('Only moderators of the board and admins can make new posts/replies').'</div><br>';
	
					/* Enable reporting */
					$tpl_page .= '<label for="enablereporting">'._('Enable reporting:').'</label>
					<select name="enablereporting">';
					$tpl_page .= ($lineboard['enablereporting'] == '1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>';
					$tpl_page .= '</select>
					<div class="desc">'._('Reporting allows users to report posts, adding the post to the report list.').' '._('Default').': <b>'._('Yes').'</b></div><br>';
					
					/* Enable captcha */
					$tpl_page .= '<label for="enablecaptcha">'._('Enable captcha:').'</label>
					<select name="enablecaptcha">';
					$tpl_page .= ($lineboard['enablecaptcha'] == '1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>';
					$tpl_page .= '</select>
					<div class="desc">'._('Enable/disable captcha system for this board.  If captcha is enabled, in order for a user to post, they must first correctly enter the text on an image.').' '._('Default').': <b>'._('No').'</b></div><br>';
				
					/* Allowed image types */
					$tpl_page .= '<label>'._('Allowed image types').':</label>
					<div class="desc">'._('What filetypes users are allowed to upload.').'</div><br>';
						/* GIF */
						$tpl_page .= '<label for="filetype_gif">GIF</label><input type="checkbox" name="filetype_gif" ';
						if (in_array('GIF', explode('|', $lineboard['filetypes']))) {
							$tpl_page .= 'checked ';
						}
						$tpl_page .= '><br>';
						/* JPG */
						$tpl_page .= '<label for="filetype_jpg">JPG</label><input type="checkbox" name="filetype_jpg" ';
						if (in_array('JPG', explode('|', $lineboard['filetypes']))) {
							$tpl_page .= 'checked ';
						}
						$tpl_page .= '><br>';
						/* PNG */
						$tpl_page .= '<label for="filetype_png">PNG</label><input type="checkbox" name="filetype_png" ';
						if (in_array('PNG', explode('|', $lineboard['filetypes']))) {
							$tpl_page .= 'checked ';
						}
						$tpl_page .= '><br>';
						/* User-set filetypes */
						$results_filetypes = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "filetypes`");
						foreach ($results_filetypes as $line_filetypes) {
							$tpl_page .= '<label for="filetype_' . $line_filetypes['filetype'] . '">' . strtoupper($line_filetypes['filetype']) . '</label><input type="checkbox" name="filetype_' . $line_filetypes['filetype'] . '"';
							if (in_array(strtoupper($line_filetypes['filetype']), explode('|', $lineboard['filetypes']))) {
								$tpl_page .= ' checked';
							}
							$tpl_page .= '><br>';
						}
					/* END Allowed image types */
	
					/* Maximum image size */
					$tpl_page .= '<label for="maximagesize">'._('Maximum image size').':</label>
					<input type="text" name="maximagesize" value="'.$lineboard['maximagesize'].'">
					<div class="desc">'._('Maxmimum size of uploaded images, in <b>bytes</b>.') . ' ' . _('Default').': <b>1024000</b></div><br>';
					
					/* Maximum message length */
					$tpl_page .= '<label for="messagelength">'._('Maximum message length').':</label>
					<input type="text" name="messagelength" value="'.$lineboard['messagelength'].'">
					<div class="desc">'._('Default').': <b>8192</b></div><br>';
					
					/* Maximum board pages */
					$tpl_page .= '<label for="maxpages">'._('Maximum board pages').':</label>
					<input type="text" name="maxpages" value="'.$lineboard['maxpages'].'">
					<div class="desc">'._('Default').': <b>10</b></div><br>';
	
					/* Maximum thread age */
					$tpl_page .= '<label for="maxage">'._('Maximum thread age (Hours)').':</label>
					<input type="text" name="maxage" value="'.$lineboard['maxage'].'">
					<div class="desc">'._('Default').': <b>96</b></div><br>';
					
					/* Maximum thread replies */
					$tpl_page .= '<label for="maxreplies">'._('Maximum thread replies').':</label>
					<input type="text" name="maxreplies" value="'.$lineboard['maxreplies'].'">
					<div class="desc">'._('The number of replies a thread can have before autosaging to the back of the board.') . ' ' . _('Default').': <b>200</b></div><br>';
					
					/* Header image */
					$tpl_page .= '<label for="image">'._('Header image').':</label>
					<input type="text" name="image" value="'.$lineboard['image'].'">
					<div class="desc">'._('Overrides the header set in the config file.  Leave blank to use configured global header image.  Needs to be a full url including http://.  Set to none to show no header image.').'</div><br>';
	
					/* Include header */
					$tpl_page .= '<label for="includeheader">'._('Include header').':</label>
					<textarea name="includeheader" rows="12" cols="80">'.$lineboard['includeheader'].'</textarea>
					<div class="desc">'._('Raw HTML which will be inserted at the top of each page of the board.').'</div><br>';
					
					/* Enable "no file" posting */
					$tpl_page .= '<label for="enablenofile">.'._('Enable "no file" posting').':</label>
					<select name="enablenofile">';
					$tpl_page .= ($lineboard['enablenofile'] == '1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>';
					$tpl_page .= '</select>
					<div class="desc">'._('If set to yes, new threads will not require an image to be posted.') . ' ' . _('Default').': <b>'._('No').'</b></div><br>';
	
					/* Redirect to thread */
					$tpl_page .= '<label for="redirecttothread">'._('Redirect to thread').':</label>
					<select name="redirecttothread">';
					$tpl_page .= ($lineboard['redirecttothread'] == '1') ? '<option value="1">Yes</option><option value="0">No</option>' : '<option value="0">No</option><option value="1">Yes</option>';
					$tpl_page .= '</select>
					<div class="desc">'._('If set to yes, users will be redirected to the thread they replied to/posted after posting.  If set to no, users will be redirected to the first page of the board.') . ' ' . _('Default').': <b>'.('No').'</b></div><br>';
	
					/* Forced anonymous */
					$tpl_page .= '<label for="forcedanon">'._('Forced anonymous').':</label>
					<select name="forcedanon">';
					$tpl_page .= ($lineboard['forcedanon'] == '1') ? '<option value="1">'._('Yes').'</option><option value="0">'._('No').'</option>' : '<option value="0">'._('No').'</option><option value="1">'._('Yes').'</option>';
					$tpl_page .= '</select>
					<div class="desc">'._('If set to yes, users will not be allowed to enter a name, making everyone appear as Anonymous') . ' ' . _('Default').': <b>'._('No').'</b></div><br>';
	
					/* Trial */
					$tpl_page .= '<label for="trial">'._('Trial').':</label>
					<select name="trial">';
					$tpl_page .= ($lineboard['trial'] == '1') ? '<option value="1">'._('Yes').'</option><option value="0">'._('No').'</option>' : '<option value="0">'._('No').'</option><option value="1">'._('Yes').'</option>';
					$tpl_page .= '</select>
					<div class="desc">'._('If set to yes, this board will appear in italics in the menu') . ' ' . _('Default').': <b>'._('No').'</b></div><br>';
					
					/* Popular */
					$tpl_page .= '<label for="popular">'._('Popular').':</label>
					<select name="popular">';
					$tpl_page .= ($lineboard['popular'] == '1') ? '<option value="1">'._('Yes').'</option><option value="0">'._('No').'</option>' : '<option value="0">'._('No').'</option><option value="1">'._('Yes').'</option>';
					$tpl_page .= '</select>
					<div class="desc">'._('If set to yes, this board will appear in bold in the menu') . ' ' . _('Default').': <b>'._('No').'</b></div><br>';
	
					/* Default style */
					$tpl_page .= '<label for="defaultstyle">'._('Default style:').'</label>
					<select name="defaultstyle">
					<option value="Burichan"';
					$tpl_page .= ($lineboard['defaultstyle'] == 'Burichan') ? ' selected' : '';
					$tpl_page .= '>Burichan</option>
					<option value="Futaba"';
					$tpl_page .= ($lineboard['defaultstyle'] == 'Futaba') ? ' selected' : '';
					$tpl_page .= '>Futaba</option>
					<option value="Gurochan"';
					$tpl_page .= ($lineboard['defaultstyle'] == 'Gurochan') ? ' selected' : '';
					$tpl_page .= '>Gurochan</option>
					<option value="Photon"';
					$tpl_page .= ($lineboard['defaultstyle'] == 'Photon') ? ' selected' : '';
					$tpl_page .= '>Photon</option>
					<option value="Fuhrerchan"';
					$tpl_page .= ($lineboard['defaultstyle'] == 'Fuhrerchan') ? ' selected' : '';
					$tpl_page .= '>Fuhrerchan</option>
					</select>
					<div class="desc">'._('The style which will be set when the user first visits the board.').' '._('Default').': <b>Futaba</b></div><br>';
					
					/* Submit form */
					$tpl_page .= '<input type="submit" name="submit" value="'._('Update').'">
					
					</form>
					</div>';
	
				}
			} else {
				$tpl_page .= _('Unable to locate a board named') . ' <b>' . $_POST['board'] . '</b>.';
			}
		} else {
			$tpl_page .= '<form action="?action=boardopts" method="post">
			<label for="board">'._('Board').':</label>' .
			$this->MakeBoardListDropdown('board', $this->BoardList($_SESSION['manageusername'])) .
			'<input type="submit" value="'._('Go').'">
			</form>';
		}
	}
	
	/* Search for all posts by a selected IP address and delete them */
	function deletepostsbyip() {
		global $tc_db, $smarty, $tpl_page, $board_class;
		
		$tpl_page .= '<h2>' . ucwords(_('Delete all posts by IP')) . '</h2><br>';
		if (isset($_POST['ip'])) {
			if ($_POST['ip'] != '') {
				$deletion_boards = array();
				$deletion_new_boards = array();
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
				if (isset($_POST['banfromall'])) {
					foreach ($results as $line) {
						$deletion_new_boards[] = $line['name'];
					}
				} else {
					foreach ($results as $line) {
						$deletion_boards[] = $line['name'];
					}
					$deletion_changed_boards = array();
					$deletion_new_boards = array();
					while (list($postkey, $postvalue) = each($_POST)) {
						if (substr($postkey, 0, 10) == 'deletefrom') {
							$deletion_changed_boards[] = substr($postkey, 10);
						}
					}
					while (list(, $deletion_thisboard_name) = each($deletion_boards)) {
						if (in_array($deletion_thisboard_name, $deletion_changed_boards)) {
							$deletion_new_boards[] = $deletion_thisboard_name;
						}
					}
					if ($deletion_new_boards == array()) {
						die(_('Please select a board.'));
					}
				}
				$delete_boards = implode('|', $deletion_new_boards);
				foreach ($deletion_new_boards as $board) {
					if (!$this->CurrentUserIsModeratorOfBoard($board, $_SESSION['manageusername'])) {
						die('/' . $board . '/: ' . _('You can only delete posts from boards you moderate.'));
					}
				}
				$i = 0;
				foreach ($deletion_new_boards as $deletion_board) {
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($deletion_board) . "'");
					foreach ($results as $line) {
						$board_name = $line['name'];
					}
					$post_list = $tc_db->GetAll("SELECT `id` FROM `" . TC_DBPREFIX . "posts_" . $board_name . "` WHERE `IS_DELETED` = '0' AND `ipmd5` = '" . md5($_POST['ip']) . "'");
					foreach ($post_list as $post) {
						$i++;

						$post_class = new Post($post['id'], $board_name);
						$post_class->Delete();
					}
					$board_class = new Board($board_name);
					$board_class->RegenerateAll();
				}
				$tpl_page .= _('All threads/posts by that IP in selected boards successfully deleted.') . '<br><b>' . $i . '</b> posts were removed.<br>';
				$tpl_page .= '<hr>';
				management_addlogentry(_('Deleted posts by ip') . ' ' . $_POST['ip'], 7);
			}
		}
		$tpl_page .= '<form action="?action=deletepostsbyip" method="post">
		
		<label for="ip">'._('IP').':</label>
		<input type="text" name="ip"';
		if (isset($_GET['ip'])) {
			$tpl_page .= ' value="' . $_GET['ip'] . '"';
		}
		$tpl_page .= '><br>
		'._('Boards').':
		
		<label for="banfromall"><b>'._('All boards').'</b></label>
		<input type="checkbox" name="banfromall"><br>OR<br>' .
		$this->MakeBoardListCheckboxes('deletefrom', $this->BoardList($_SESSION['manageusername'])) .
		'<br>
		
		<input type="submit" value="'._('Delete posts').'">
		
		</form>';
	}
	
	function unstickypost() {
		global $tc_db, $smarty, $tpl_page, $board_class;
		
		$tpl_page .= '<h2>' . ucwords(_('Manage stickies')) . '</h2><br>';
		if (isset($_GET['postid']) && isset($_GET['board'])) {
			if ($_GET['postid'] > 0 && $_GET['board'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
				if (count($results) > 0) {
					if (!$this->CurrentUserIsModeratorOfBoard($_GET['board'], $_SESSION['manageusername'])) {
						die(_('You are not a moderator of this board.'));
					}
					foreach ($results as $line) {
						$sticky_board_name = $line['name'];
					}
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
					if (count($results) > 0) {
						$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` SET `stickied` = '0' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
						$board_class = new Board($sticky_board_name);
						$board_class->RegenerateAll();
						$tpl_page .= _('Thread successfully un-stickied');
						management_addlogentry(_('Unstickied thread') . ' #' . mysql_real_escape_string($_GET['postid']) . ' - /' . mysql_real_escape_string($_GET['board']) . '/', 5);
					} else {
						$tpl_page .= _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
					}
				} else {
					$tpl_page .= _('Invalid board directory.');
				}
				$tpl_page .= '<hr>';
			}
		}
		$tpl_page .= $this->stickyforms();
	}
	
	function stickypost() {
		global $tc_db, $smarty, $tpl_page, $board_class;
		
		$tpl_page .= '<h2>' . ucwords(_('Manage stickies')) . '</h2><br>';
		if (isset($_GET['postid']) && isset($_GET['board'])) {
			if ($_GET['postid'] > 0 && $_GET['board'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . $_GET['board'] . "'");
				if (count($results) > 0) {
					if (!$this->CurrentUserIsModeratorOfBoard($_GET['board'], $_SESSION['manageusername'])) {
						die(_('You are not a moderator of this board.'));
					}
					foreach ($results as $line) {
						$sticky_board_name = $line['name'];
					}
					$result = $tc_db->GetOne("SELECT HIGH_PRIORITY COUNT(*) FROM `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
					if ($result > 0) {
						$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $sticky_board_name . "` SET `stickied` = '1' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
						$board_class = new Board($sticky_board_name);
						$board_class->RegenerateAll();
						$tpl_page .= _('Thread successfully stickied.');
						management_addlogentry(_('Stickied thread') . ' #' . mysql_real_escape_string($_GET['postid']) . ' - /' . mysql_real_escape_string($_GET['board']) . '/', 5);
					} else {
						$tpl_page .= _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
					}
				} else {
					$tpl_page .= _('Invalid board directory.');
				}
				$tpl_page .= '<hr>';
			}
		}
		$tpl_page .= $this->stickyforms();
	}
	
	/* Create forms for stickying a post */
	function stickyforms() {
		$output = '<form action="manage_page.php" method="get"><input type="hidden" name="action" value="stickypost">
	
		<label for="board">'._('Board').':</label>' .
		$this->MakeBoardListDropdown('board', $this->BoardList($_SESSION['manageusername'])) .
		'<br>
	
		<label for="postid">'._('Thread').':</label>
		<input type="text" name="postid"><input type="submit" value="'._('Sticky').'">
		
		</form>
		<br><hr>
	
		<form action="manage_page.php" method="get"><input type="hidden" name="action" value="unstickypost">
		<label for="board">'._('Board').':</label>' .
		$this->MakeBoardListDropdown('board', $this->BoardList($_SESSION['manageusername'])) .
		'<br>
		<label for="postid">'._('Thread').':</label>
		<input type="text" name="postid"><input type="submit" value="'._('Unsticky').'">
	
		</form>';
		
		return $output;
	}
	
	function lockpost() {
		global $tc_db, $smarty, $tpl_page, $board_class;
		
		$tpl_page .= '<h2>' . ucwords(_('Manage locked threads')) . '</h2><br>';
		if (isset($_GET['postid']) && isset($_GET['board'])) {
			if ($_GET['postid'] > 0 && $_GET['board'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
				if (count($results) > 0) {
					if (!$this->CurrentUserIsModeratorOfBoard($_GET['board'], $_SESSION['manageusername'])) {
						die(_('You are not a moderator of this board.'));
					}
					foreach ($results as $line) {
						$lock_board_name = $line['name'];
					}
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
					if (count($results) > 0) {
						$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` SET `locked` = '1' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
						$board_class = new Board($lock_board_name);
						$board_class->RegenerateAll();
						$tpl_page .= _('Thread successfully locked.');
						management_addlogentry(_('Locked thread') . ' #' . mysql_real_escape_string($_GET['postid']) . ' - /' . mysql_real_escape_string($_GET['board']) . '/', 5);
					} else {
						$tpl_page .= _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
					}
				} else {
					$tpl_page .= _('Invalid board directory.');
				}
				$tpl_page .= '<hr>';
			}
		}
		$tpl_page .= $this->lockforms();
	}
	
	function unlockpost() {
		global $tc_db, $smarty, $tpl_page, $board_class;
		
		$tpl_page .= '<h2>' . ucwords(_('Manage locked threads')) . '</h2><br>';
		if ($_GET['postid'] > 0 && $_GET['board'] != '') {
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
			if (count($results) > 0) {
				if (!$this->CurrentUserIsModeratorOfBoard($_GET['board'], $_SESSION['manageusername'])) {
					die(_('You are not a moderator of this board.'));
				}
				foreach ($results as $line) {
					$lock_board_name = $line['name'];
				}
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` WHERE `IS_DELETED` = '0' AND `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
				if (count($results) > 0) {
					$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "posts_" . $lock_board_name . "` SET `locked` = '0' WHERE `threadid` = '0' AND `id` = '" . mysql_real_escape_string($_GET['postid']) . "'");
					$board_class = new Board($lock_board_name);
					$board_class->RegenerateAll();
					$tpl_page .= _('Thread successfully unlocked.');
					management_addlogentry(_('Unlocked thread') . ' #' . $_GET['postid'] . ' - /' . $_GET['board'] . '/', 5);
				} else {
					$tpl_page .= _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
				}
			} else {
				$tpl_page .= _('Invalid board directory.');
			}
			$tpl_page .= '<hr>';
		}
		$tpl_page .= $this->lockforms();
	}
	
	function lockforms() {
		$output = '<form action="manage_page.php" method="get"><input type="hidden" name="action" value="lockpost">
		<label for="board">'._('Board').':</label>' .
		$this->MakeBoardListDropdown('board', $this->BoardList($_SESSION['manageusername'])) .
		'<br>
		
		<label for="postid">'._('Thread').':</label>
		<input type="text" name="postid">
		
		<input type="submit" value="'._('Lock').'">
		</form>
		<br><hr>
		
		<form action="manage_page.php" method="get">
		<input type="hidden" name="action" value="unlockpost">
		
		<label for="board">'._('Board').':</label>' .
		$this->MakeBoardListDropdown('board', $this->BoardList($_SESSION['manageusername'])) .
		'<br>
		
		<label for="postid">'._('Thread').':</label>
		<input type="text" name="postid">
		
		<input type="submit" value="'._('Unlock').'">
		
		</form>';
		
		return $output;
	}
	
	/* Run delorphanreplies() verbosely, followed by delunusedimages() verbosely */
	function cleanup() {
		global $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . _('Cleanup') . '</h2><br>';
		$tpl_page .= '<hr>'._('Deleting non-deleted replies which belond to deleted threads.').'<hr>';
		$this->delorphanreplies(true);
		$tpl_page .= '<hr>'._('Deleting unused images.').'<hr>';
		flush();
		$this->delunusedimages(true);
		$tpl_page .= _('Cleanup finished.');
		management_addlogentry(_('Ran cleanup'), 2);
	}
	
	/* Addition, modification, deletion, and viewing of bans */
	function bans() {
		global $tc_db, $smarty, $tpl_page, $bans_class;
		
		$tpl_page .= '<h2>' . _('Bans') . '</h2><br>';
		$ban_ip = '';
		if (isset($_POST['ip']) && isset($_POST['seconds'])) {
			if ($_POST['ip'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "banlist` WHERE `ipmd5` = '" . md5($_POST['ip']) . "'");
				if (count($results) == 0) {
					if ($_POST['seconds'] >= 0) {
						$banning_boards = array();
						$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
						foreach ($results as $line) {
							$banning_boards = array_merge($banning_boards, array($line['name']));
						}
						$banning_changed_boards = array();
						$banning_new_boards = array();
						while (list($postkey, $postvalue) = each($_POST)) {
							if (substr($postkey, 0, 10) == "bannedfrom") {
								$banning_changed_boards = array_merge($banning_changed_boards, array(substr($postkey, 10)));
							}
						}
						while (list(, $banning_thisboard_name) = each($banning_boards)) {
							if (in_array($banning_thisboard_name, $banning_changed_boards)) {
								$banning_new_boards = array_merge($banning_new_boards, array($banning_thisboard_name));
							}
						}
						if ($banning_new_boards == array() && $_POST['banfromall'] != 'on') {
							die(_('Please select a board.'));
						}
						$ban_globalban = (isset($_POST['banfromall'])) ? '1' : '0';
						$ban_allowread = (isset($_POST['allowread'])) ? '1' : '0';
						if ($ban_globalban == '0') {
							$ban_boards = implode('|', $banning_new_boards);
							foreach (explode('|', $ban_boards) as $board) {
								if (!$this->CurrentUserIsModeratorOfBoard($board, $_SESSION['manageusername'])) {
									die(_('You can only make board specific bans to boards which you moderate.'));
								}
							}
						} else {
							$ban_boards = '';
						}
						if ($_POST['seconds'] == '0') {
							/* Permanent ban */
							$ban_duration = '0';
						} else {
							/* Timed ban */
							$ban_duration = mysql_real_escape_string($_POST['seconds']);
						}
						if ($_POST['type'] == '0') {
							/* Normal IP address ban */
							$ban_type = '0';
						} else {
							/* IP range ban */
							$ban_type = '1';
						}
						if ($bans_class->BanUser(mysql_real_escape_string($_POST['ip']), $_SESSION['manageusername'], $ban_globalban, $ban_duration, $ban_boards, mysql_real_escape_string($_POST['reason']), $ban_type, $ban_allowread)) {
							if (TC_ADDBANMSG && isset($_POST['quickbanpostid'])) {
								$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `threadid`, `message` FROM `".TC_DBPREFIX."posts_".mysql_real_escape_string($_POST['quickbanboard'])."` WHERE `id` = ".mysql_real_escape_string($_POST['quickbanpostid'])." LIMIT 1");
								foreach($results AS $line) {
									$tc_db->Execute("UPDATE `".TC_DBPREFIX."posts_".mysql_real_escape_string($_POST['quickbanboard'])."` SET `message` = '".mysql_real_escape_string($line['message'].'<br><font color="#FF0000"><b>(USER WAS BANNED FOR THIS POST)</b></font>')."' WHERE `id` = ".mysql_real_escape_string($_POST['quickbanpostid'])." LIMIT 1");
									$board_class = new Board($_POST['quickbanboard']);
									if ($line['threadid']==0) {
										$board_class->RegenerateThread($_POST['quickbanpostid']);
									} else {
										$board_class->RegenerateThread($line['threadid']);
									}
									$board_class->RegeneratePages();
								}
							}
							$tpl_page .= _('Ban successfully placed.');
						} else {
							$tpl_page .= _('Sorry, a generic error has occurred.');
							die();
						}
						$logentry = _('Banned') . ' ' . $_POST['ip'] . ' until ';
						if ($_POST['seconds'] == '0') {
							$logentry .= _('forever');
						} else {
							$logentry .= date('F j, Y, g:i a', time() + $_POST['seconds']);
						}
						$logentry .= ' - ' . _('Reason') . ': ' . $_POST['reason'] . ' - ' . _('Banned from') . ': ';
						if ($ban_globalban == '1') {
							$logentry .= _('All boards') . ' ';
						} else {
							$logentry .= '/' . implode('/, /', explode('|', $ban_boards)) . '/ ';
						}
						management_addlogentry($logentry, 8);
						if ($_POST['quickbanboard'] != '' && $_POST['quickbanthreadid'] != '') {
							$tpl_page .= '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $_POST['quickbanboard'] . '/';
							if ($_POST['quickbanthreadid'] != "0") {
								$tpl_page .= 'res/' . $_POST['quickbanthreadid'] . '.html';
							}
							$tpl_page .= '"><a href="' . TC_BOARDSPATH . '/' . $_POST['quickbanboard'] . '/';
							if ($_POST['quickbanthreadid'] != "0") {
								$tpl_page .= 'res/' . $_POST['quickbanthreadid'] . '.html';
							}
							$tpl_page .= '">' . _('Redirecting') . '</a>...';
						}
					} else {
						$tpl_page .= _('Please enter a positive amount of seconds, or zero for a permanent ban.');
					}
				} else {
					$tpl_page .= _('That IP has already been banned.');
				}
				$tpl_page .= '<hr>';
			}
		} elseif (isset($_GET['delban'])) {
			if ($_GET['delban'] > 0) {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "banlist` WHERE `id` = '" . mysql_real_escape_string($_GET['delban']) . "'");
				if (count($results) > 0) {
					foreach ($results as $line) {
						$unban_ip = md5_decrypt($line['ip'], TC_RANDOMSEED);
					}
					$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "banlist` WHERE `id` = '" . mysql_real_escape_string($_GET['delban']) . "'");
					$bans_class->UpdateHtaccess();
					$tpl_page .= _('Ban successfully removed.');
					management_addlogentry(_('Unbanned') . ' ' . $unban_ip, 8);
				} else {
					$tpl_page .= _('Invalid ban ID');
				}
				$tpl_page .= '<hr>';
			}
		}
		if (isset($_GET['banboard']) && isset($_GET['banpost'])) {
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['banboard']) . "'");
			if (count($results) > 0) {
				foreach ($results as $line) {
					$ban_board_name = $line['name'];
				}
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts_" . $ban_board_name . "` WHERE `id` = '" . mysql_real_escape_string($_GET['banpost']) . "'");
				if (count($results) > 0) {
					foreach ($results as $line) {
						$ban_ip = md5_decrypt($line['ip'], TC_RANDOMSEED);
						$ban_threadid = $line['threadid'];
					}
				} else {
					$tpl_page .= _('A post with that ID does not exist.') . '<hr>';
				}
			}
		}
		
		flush();
		
		$tpl_page .= '<form action="manage_page.php?action=bans" method="post" name="banform">';
		if ($ban_ip != '') {
			$tpl_page .= '<input type="hidden" name="quickbanboard" value="' . $_GET['banboard'] . '"><input type="hidden" name="quickbanthreadid" value="' . $ban_threadid . '"><input type="hidden" name="quickbanpostid" value="' . $_GET['banpost'] . '">';
		} elseif (isset($_GET['ip'])) {
			$ban_ip = $_GET['ip'];
		}
		
		$tpl_page .= '<label for="ip">'._('IP').':</label>
		<input type="text" name="ip" value="'.$ban_ip.'">';
		if ($ban_ip != '') { $tpl_page .= '&nbsp;&nbsp;<a href="?action=deletepostsbyip&ip=' . $ban_ip . '" target="_blank">' . _('Delete all posts by this IP') . '</a>'; }
		$tpl_page .= '<br>
		
		<label for="allowread">Allow read:</label>
		<select name="allowread"><option value="1">Yes</option><option value="0">No</option></select>
		<div class="desc">Whether or not the user(s) affected by this ban will be allowed to read the boards.<br><b>Warning</b>: Selecting No will prevent any reading of any page on the level of the boards on the server.<br>Changing this option to No will provide a global ban, whether or not you set the option below.</div><br>
		
		<label for="type">Type:</label>
		<select name="type"><option value="0">Single IP</option><option value="1">IP Range</option></select>
		<div class="desc">The type of the ban.  A single IP can be banned by providing the full address, or an IP range can be banned by providing the range you wish to ban.</div><br>
		
		'._('Ban from').':&nbsp;<label for="banfromall"><b>'._('All boards').'</b></label>
		<input type="checkbox" name="banfromall"><br>OR<br>' .
		$this->MakeBoardListCheckboxes('bannedfrom', $this->BoardList($_SESSION['manageusername'])) .
		'<br>
		
		<label for="seconds">'._('Seconds').':</label>
		<input type="text" name="seconds">
		<div class="desc">'._('Presets').':&nbsp;<a href="#" onclick="document.banform.seconds.value=\'3600\';">1hr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value=\'604800\';">1w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value=\'1209600\';">2w</a>&nbsp;<a href="#" onclick="document.banform.seconds.value=\'2592000\';">30d</a>&nbsp;<a href="#" onclick="document.banform.seconds.value=\'31536000\';">1yr</a>&nbsp;<a href="#" onclick="document.banform.seconds.value=\'0\';">never</a></div><br>
		
		<label for="reason">'._('Reason').':</label>
		<input type="text" name="reason">
		<div class="desc">'._('Presets').':&nbsp;<a href="#" onclick="document.banform.reason.value=\'Child Pornography\';">CP</a>&nbsp;<a href="#" onclick="document.banform.reason.value=\'Proxy\';">Proxy</a></div><br>
		
		<input type="submit" value="'._('Add ban').'">
		
		</form>
		<hr><br>';
		
		for ($i = 1; $i >= 0; $i--) {
			if ($i == 1) {
				$tpl_page .= '<b>IP Range bans:</b><br>';
			} else {
				$tpl_page .= '<br><b>Single IP bans:</b><br>';
			}
		
			if (isset($_GET['allbans'])) {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "banlist` WHERE `type` = '" . $i . "' ORDER BY `id` DESC");
				$hiddenbans = 0;
			} else {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "banlist` WHERE `type` = '" . $i . "' ORDER BY `id` DESC LIMIT 15");
				/* Get the number of bans in the database of this type */
				$hiddenbans = $tc_db->GetAll("SELECT HIGH_PRIORITY COUNT(*) FROM `" . TC_DBPREFIX . "banlist` WHERE `type` = '" . $i . "'");
				/* Subtract 15 from the count, since we only want the number not shown */
				$hiddenbans = $hiddenbans[0][0] - 15;
			}
			if (count($results) > 0) {
				$tpl_page .= '<table border="1"><tr><th>';
				if ($i == 1) {
					$tpl_page .= 'IP Range';
				} else {
					$tpl_page .= 'IP Address';
				}
				$tpl_page .= '</th><th>Boards</th><th>Reason</th><th>Date Added</th><th>Expires</th><th>Added By</th><th>&nbsp;</th></tr>';
				foreach ($results as $line) {
					$tpl_page .= '<tr>';
					$tpl_page .= '<td><a href="?action=bans&ip=' . md5_decrypt($line['ip'], TC_RANDOMSEED) . '">' . md5_decrypt($line['ip'], TC_RANDOMSEED) . '</a></td><td>';
					if ($line['globalban'] == '1') {
						$tpl_page .= '<b>' . _('All boards') . '</b>';
					} else {
						if ($line['boards'] != '') {
							$tpl_page .= '<b>/' . implode('/</b>, <b>/', explode('|', $line['boards'])) . '/</b>&nbsp;';
						}
					}
					$tpl_page .= '</td><td>' . htmlentities(stripslashes($line['reason'])) . '</td><td>' . date("F j, Y, g:i a", $line['at']) . '</td><td>';
					if ($line['until'] == '0') {
						$tpl_page .= '<b>' . _('forever') . '</b>';
					} else {
						$tpl_page .= date("F j, Y, g:i a", $line['until']);
					}
					$tpl_page .= '</td><td>' . $line['by'] . '</td><td>[<a href="manage_page.php?action=bans&delban=' . $line['id'] . '">x</a>]</td>';
					$tpl_page .= '</tr>';
				}
				$tpl_page .= '</table>';
				if ($hiddenbans>0) {
					$tpl_page .= sprintf(_('%s bans not shown.'), $hiddenbans) .
					' <a href="?action=bans&allbans=1">' . _('View all bans') . '</a>';
				}
			} else {
				$tpl_page .= _('There are currently no bans.');
			}
		}
	}
	
	/* Delete a post, or multiple posts */
	function delposts($multidel=false) {
		global $tc_db, $smarty, $tpl_page, $board_class;
		
		$tpl_page .= '<h2>' . ucwords(_('Delete thread/post')) . '</h2><br>';
		if (isset($_POST['boarddir']) || isset($_GET['boarddir'])) {
			if (isset($_GET['boarddir'])) {
				$isquickdel = true;
				$_POST['boarddir'] = $_GET['boarddir'];
				if (isset($_GET['delthreadid'])) {
					$_POST['delthreadid'] = $_GET['delthreadid'];
				}
				if (isset($_GET['delpostid'])) {
					$_POST['delpostid'] = $_GET['delpostid'];
				}
			}
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['boarddir']) . "'");
			if (count($results) > 0) {
				if (!$this->CurrentUserIsModeratorOfBoard($_POST['boarddir'], $_SESSION['manageusername'])) {
					die(_('You are not a moderator of this board.'));
				}
				foreach ($results as $line) {
					$board_id = $line['id'];
					$board_dir = $line['name'];
				}
				if ($_POST['delthreadid'] > 0) {
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts_" . $board_dir . "` WHERE `IS_DELETED` = '0' AND  `id` = '" . mysql_real_escape_string($_POST['delthreadid']) . "' AND `threadid` = '0'");
					if (count($results) > 0) {
						foreach ($results as $line) {
							$delthread_id = $line['id'];
						}
						$post_class = new Post($delthread_id, $board_dir);
						$numposts_deleted = $post_class->Delete();
						$board_class = new Board($board_dir);
						$board_class->RegenerateAll();
						$tpl_page .= _('Thread '.$delthread_id.' successfully deleted.');
						management_addlogentry(_('Deleted thread') . ' #<a href="?action=viewdeletedthread&threadid=' . $delthread_id . '&board=' . $_POST['boarddir'] . '">' . $delthread_id . '</a> (' . $numposts_deleted . ' replies) - /' . $board_dir . '/', 7);
						if ($_GET['postid'] != '') {
							$tpl_page .= '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/manage_page.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '"><a href="' . TC_BOARDSPATH . '/manage_page.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '">' . _('Redirecting') . '</a> to ban page...';
						} elseif ($isquickdel) {
							$tpl_page .= '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/"><a href="' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/">' . _('Redirecting') . '</a> back to board...';
						}
					} else {
						$tpl_page .= _('Invalid thread ID '.$delpost_id.'.  This may have been caused by the thread recently being deleted.');
					}
				} elseif ($_POST['delpostid'] > 0) {
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts_" . $board_dir . "` WHERE `IS_DELETED` = '0' AND  `id` = '" . mysql_real_escape_string($_POST['delpostid']) . "'");
					if (count($results) > 0) {
						foreach ($results as $line) {
							$delpost_id = $line['id'];
							$delpost_threadid = $line['threadid'];
						}
						$post_class = new Post($delpost_id, $board_dir);
						$post_class->Delete();
						$board_class = new Board($board_dir);
						$board_class->RegenerateThread($delpost_threadid);
						$board_class->RegeneratePages();
						$tpl_page .= _('Post '.$delpost_id.' successfully deleted.');
						management_addlogentry(_('Deleted post') . ' #<a href="?action=viewdeletedthread&threadid=' . $delpost_threadid . '&board=' . $_POST['boarddir'] . '#' . $delpost_id . '">' . $delpost_id . '</a> - /' . $board_dir . '/', 7);
						if ($_GET['postid'] != '') {
							$tpl_page .= '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/manage_page.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '"><a href="' . TC_BOARDSPATH . '/manage_page.php?action=bans&banboard=' . $_GET['boarddir'] . '&banpost=' . $_GET['postid'] . '">' . _('Redirecting') . '</a> to ban page...';
						} elseif ($isquickdel) {
							$tpl_page .= '<br><br><meta http-equiv="refresh" content="1;url=' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/res/' . $delpost_threadid . '.html"><a href="' . TC_BOARDSPATH . '/' . $_GET['boarddir'] . '/res/' . $delpost_threadid . '.html">' . _('Redirecting') . '</a> back to thread...';
						}
					} else {
						$tpl_page .= _('Invalid thread ID '.$delpost_id.'.  This may have been caused by the thread recently being deleted.');
					}
				}
			} else {
				$tpl_page .= _('Invalid board directory.');
			}
			$tpl_page .= '<hr>';
		}
		if (!$multidel) {
			$tpl_page .= '<form action="manage_page.php?action=delposts" method="post">
			<label for="boarddir">'._('Board').':</label>' .
			$this->MakeBoardListDropdown('boarddir', $this->BoardList($_SESSION['manageusername'])) .
			'<br>
			
			<label for="delthreadid">'._('Thread').':</label>
			<input type="text" name="delthreadid"><br>
			
			<input type="submit" value="'._('Delete thread').'">
			
			</form>
			<br><hr>
			
			<form action="manage_page.php?action=delposts" method="post">
			<label for="boarddir">'._('Board').':</label>' .
			$this->MakeBoardListDropdown('boarddir', $this->BoardList($_SESSION['manageusername'])) .
			'<br>
			
			<label for="delpostid">'._('Post').':</label>
			<input type="text" name="delpostid"><br>
			
			<input type="submit" value="'._('Delete post').'">
		
			</form>';
		}
	}
	function proxyban() {
		$this->AdministratorsOnly();
		global $tpl_page;
		$tpl_page .= '<h2>' . _('Ban proxy list') . '</h2><br>';
		if (isset($_FILES['imagefile'])) {
		$bans_class = new Bans;
		$ips = 0;
		$successful = 0;
		$proxies = file($_FILES['imagefile']['tmp_name']);
		foreach($proxies as $proxy) {
			if (preg_match('/.[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+.*/',$proxy)) {
				$ips++;
				if ($bans_class->banuser(preg_replace("/:.*/","",$proxy),"SERVER",1,0,"","IP from proxylist automatically banned")) {
					$successful++;
				}
			}
		}
		management_addlogentry("banned ".$successful." proxies automatically.",8);
		$tpl_page .= $successful." of ".$ips." proxies banned.";
		} else {
			$tpl_page .= '<form id="postform" action="'.TC_BOARDSFOLDER.'manage_page.php?action=proxyban" method="post" enctype="multipart/form-data">'._('Proxy list').'<input type="file" name="imagefile" size="35" accesskey="f"><br>
			<input type="submit" value="Submit">
			<br>The proxy list is assumed to be in plaintext *.*.*.*:port or *.*.*.* format, one IP per line.';
		}
	}
	
	/* Called from a board's page using the multidel button */
	function multidel() {
		global $tc_db, $smarty, $tpl_page;
		
		$multidel = TRUE;
		$_POST['seconds'] = 0;
		$multiban_query = 'WHERE `id` = "0 " ';
		foreach($_POST AS $TOAST) {
			if (ereg('POST*',$TOAST)){
				$_POST['boarddir'] = $_POST['board'];
				$_POST['delpostid'] = ereg_replace('POST','',$TOAST);
				delposts($multidel);
				if ($_POST['multiban']=="on") { $multiban_query .= "OR `id` = '".mysql_real_escape_string($_POST['delpostid'])."'"; }
			}
		}
		
		if (isset($_POST['multiban'])) {
			$ban_globalban = '1';
			$_POST['seconds'] = '0';
			$ban_boards = '';
			
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `ip` FROM `".TC_DBPREFIX . "posts_".mysql_real_escape_string($_POST['board'])."` ".$multiban_query);
			if (count($results) > 0) {
				foreach ($results as $line) {
					$ban_ip = md5_decrypt($line['ip'], TC_RANDOMSEED);
					$bans_class->BanUser($ban_ip, mysql_real_escape_string($_SESSION['manageusername']), $ban_globalban, 0, $ban_boards, mysql_real_escape_string($_POST['reason']), 0, 1);
					$logentry = _('Banned') . ' ' . $ban_ip . ' until ';
					if ($_POST['seconds'] == '0') {
						$logentry .= _('forever');
					} else {
						$logentry .= date('F j, Y, g:i a', time() + $_POST['seconds']);
					}
					$logentry .= ' - ' . _('Reason') . ': ' . $_POST['reason'] . ' - ' . _('Banned from') . ': ';
					if ($ban_globalban == '1') {
						$logentry .= _('All boards') . ' ';
					} else {
						$logentry .= '/' . implode('/, /', explode('|', $ban_boards)) . '/ ';
					}
					management_addlogentry($logentry, 8);
				}
			} else {
				$tpl_page .= _('A post with that ID does not exist.') . '<hr>';
			}
		}
	}
	
	/* Replace words in posts with something else */
	function wordfilter() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . _('Wordfilter') . '</h2><br>';
		if (isset($_POST['word'])) {
			if ($_POST['word'] != '' && $_POST['replacedby'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `word` = '" . mysql_real_escape_string($_POST['word']) . "'");
				if (count($results) == 0) {
					$wordfilter_boards = array();
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
					foreach ($results as $line) {
						$wordfilter_boards = array_merge($wordfilter_boards, array($line['name']));
					}
					$wordfilter_changed_boards = array();
					$wordfilter_new_boards = array();
					while (list($postkey, $postvalue) = each($_POST)) {
						if (substr($postkey, 0, 10) == 'wordfilter') {
							$wordfilter_changed_boards = array_merge($wordfilter_changed_boards, array(substr($postkey, 10)));
						}
					}
					while (list(, $wordfilter_thisboard_name) = each($wordfilter_boards)) {
						if (in_array($wordfilter_thisboard_name, $wordfilter_changed_boards)) {
							$wordfilter_new_boards = array_merge($wordfilter_new_boards, array($wordfilter_thisboard_name));
						}
					}
					$is_regex = (isset($_POST['regex'])) ? '1' : '0';
					
					$tc_db->Execute("INSERT HIGH_PRIORITY INTO `" . TC_DBPREFIX . "wordfilter` ( `word` , `replacedby` , `boards` , `time` , `regex` ) VALUES ( '" . mysql_real_escape_string($_POST['word']) . "' , '" . mysql_real_escape_string($_POST['replacedby']) . "' , '" . mysql_real_escape_string(implode('|', $wordfilter_new_boards)) . "' , '" . time() . "' , '" . $is_regex . "' )");
					
					$tpl_page .= _('Word successfully added.');
					management_addlogentry("Added word to wordfilter: " . $_POST['word'] . " - Changes to: " . $_POST['replacedby'] . " - Boards: /" . implode('/, /', explode('|', implode('|', $wordfilter_new_boards))) . "/", 11);
				} else {
					$tpl_page .= _('That word already exists.');
				}
			} else {
				$tpl_page .= _('Please fill in all required fields.');
			}
			$tpl_page .= '<hr>';
		} elseif (isset($_GET['delword'])) {
			if ($_GET['delword'] > 0) {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['delword']) . "'");
				if (count($results) > 0) {
					foreach ($results as $line) {
						$del_word = $line['word'];
					}
					$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['delword']) . "'");
					$tpl_page .= _('Word successfully removed.');
					management_addlogentry(_('Removed word from wordfilter') . ': ' . $del_word, 11);
				} else {
					$tpl_page .= _('That ID does not exist.');
				}
				$tpl_page .= '<hr>';
			}
		} elseif (isset($_GET['editword'])) {
			if ($_GET['editword'] > 0) {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['editword']) . "'");
				if (count($results) > 0) {
					if (!isset($_POST['replacedby'])) {
						foreach ($results as $line) {
							$tpl_page .= '<form action="manage_page.php?action=wordfilter&editword='.$_GET['editword'].'" method="post">
							
							<label for="word">'._('Word').':</label>
							<input type="text" name="word" value="'.$line['word'].'" disabled><br>
							
							<label for="replacedby">'._('Is replaced by').':</label>
							<input type="text" name="replacedby" value="'.$line['replacedby'].'"><br>
							
							<label for="regex">'._('Regular expression').':</label>
							<input type="checkbox" name="regex"';
							if ($line['regex'] == '1') {
								$tpl_page .= ' checked';
							}
							$tpl_page .= '><br>
				
							<label>'._('Boards').':</label><br>';
							
							$array_boards = array();
							$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
							foreach ($resultsboard as $lineboard) {
								$array_boards = array_merge($array_boards, array($lineboard['name']));
							}
							foreach ($array_boards as $this_board_name) {
								$tpl_page .= '<label for="wordfilter' . $this_board_name . '">' . $this_board_name . '</label><input type="checkbox" name="wordfilter' . $this_board_name . '" ';
								if (in_array($this_board_name, explode("|", $line['boards'])) && explode("|", $line['boards']) != '') {
									$tpl_page .= 'checked ';
								}
								$tpl_page .= '><br>';
							}
							$tpl_page .= '<br>
							
							<input type="submit" value="'._('Edit word').'">
							
							</form>';
						}
					} else {
						$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "wordfilter` WHERE `id` = '" . mysql_real_escape_string($_GET['editword']) . "'");
						if (count($results) > 0) {
							foreach ($results as $line) {
								$wordfilter_word = $line['word'];
							}
							$wordfilter_boards = array();
							$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
							foreach ($results as $line) {
								$wordfilter_boards = array_merge($wordfilter_boards, array($line['name']));
							}
							$wordfilter_changed_boards = array();
							$wordfilter_new_boards = array();
							while (list($postkey, $postvalue) = each($_POST)) {
								if (substr($postkey, 0, 10) == "wordfilter") {
									$wordfilter_changed_boards = array_merge($wordfilter_changed_boards, array(substr($postkey, 10)));
								}
							}
							while (list(, $wordfilter_thisboard_name) = each($wordfilter_boards)) {
								if (in_array($wordfilter_thisboard_name, $wordfilter_changed_boards)) {
									$wordfilter_new_boards = array_merge($wordfilter_new_boards, array($wordfilter_thisboard_name));
								}
							}
							$is_regex = (isset($_POST['regex'])) ? '1' : '0';
							
							$tc_db->Execute("UPDATE `wordfilter` SET `replacedby` = '" . mysql_real_escape_string($_POST['replacedby']) . "' , `boards` = '" . mysql_real_escape_string(implode('|', $wordfilter_new_boards)) . "' , `regex` = '" . $is_regex . "' WHERE `id` = '" . mysql_real_escape_string($_GET['editword']) . "'");
							
							$tpl_page .= _('Word successfully updated.');
							management_addlogentry(_('Updated word on wordfilter') . ': ' . $wordfilter_word, 11);
						} else {
							$tpl_page .= _('Unable to locate that word.');
						}
					}
				} else {
					$tpl_page .= _('That ID does not exist.');
				}
				$tpl_page .= '<hr>';
			}
		} else {
			$tpl_page .= '<form action="manage_page.php?action=wordfilter" method="post">
			
			<label for="word">'._('Word').'.:</label>
			<input type="text" name="word"><br>
		
			<label for="replacedby">'._('Is replaced by').':</label>
			<input type="text" name="replacedby"><br>
			
			<label for="regex">'._('Regular expression').':</label>
			<input type="checkbox" name="regex"><br>
			
			<label>'._('Boards').':</label><br>';
	
			$array_boards = array();
			$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
			foreach ($resultsboard as $lineboard) {
				$array_boards = array_merge($array_boards, array($lineboard['name']));
			}
			$tpl_page .= $this->MakeBoardListCheckboxes('wordfilter', $array_boards) .
			'<br>
			
			<input type="submit" value="'._('Add word').'">
			
			</form>
			<hr>';
		}
		$tpl_page .= '<br>';
		
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "wordfilter`");
		foreach ($results as $line) {
			$tpl_page .= 'Word: ' . $line['word'] . ' - Replaced by: ' . $line['replacedby'] . ' - Boards: ';
			if (explode('|', $line['boards']) != '') {
				$tpl_page .= '<b>/' . implode('/</b>, <b>/', explode('|', $line['boards'])) . '/</b>&nbsp;';
			} else {
				$tpl_page .= _('No boards');
			}
			$tpl_page .= '[<a href="manage_page.php?action=wordfilter&editword=' . $line['id'] . '">' . _('Edit') . '</a>]&nbsp;[<a href="manage_page.php?action=wordfilter&delword=' . $line['id'] . '">del</a>]<br>';
		}
	}
	
	function addboard() {
		global $tc_db, $smarty, $tpl_page, $board_class;
		
		$this->AdministratorsOnly();
		$tpl_page .= '<h2>' . ucwords(_('Add board')) . '</h2><br>';
		if (isset($_POST['directory'])) {
			if ($_POST['directory'] != '' && $_POST['desc'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['directory']) . "'");
				if (count($results) == 0) {
					if (mkdir(TC_BOARDSDIR . $_POST['directory'], 0777) && mkdir(TC_BOARDSDIR . $_POST['directory'] . '/res', 0777) && mkdir(TC_BOARDSDIR . $_POST['directory'] . '/src', 0777) && mkdir(TC_BOARDSDIR . $_POST['directory'] . '/thumb', 0777)) {
						file_put_contents(TC_BOARDSDIR . $_POST['directory'] . '/.htaccess', 'DirectoryIndex board.html');
						$tc_db->Execute("INSERT INTO `" . TC_DBPREFIX . "boards` ( `name` , `desc` , `createdon` ) VALUES ( '" . mysql_real_escape_string($_POST['directory']) . "' , '" . mysql_real_escape_string($_POST['desc']) . "' , '" . time() . "' )");
						if ($_POST['firstpostid'] < 1) {
							$_POST['firstpostid'] = 1;
						}
						$tc_db->Execute("CREATE TABLE `" . TC_DBPREFIX . "posts_" . mysql_real_escape_string($_POST['directory']) . "` (
						`id` int(10) NOT NULL auto_increment, 
						`threadid` int(10) NOT NULL default '0', 
						`user` varchar(255) NOT NULL, 
						`tripcode` varchar(10) NOT NULL, 
						`email` varchar(255) NOT NULL, 
						`subject` varchar(255) NOT NULL, 
						`message` text NOT NULL, 
						`image` varchar(20) NOT NULL, 
						`imagetype` varchar(5) NOT NULL, 
						`imagemd5` text NOT NULL, 
						`image_w` smallint(5) NOT NULL default '0', 
						`image_h` smallint(5) NOT NULL default '0', 
						`image_size` int(10) NOT NULL default '0', 
						`thumb_w` smallint(5) NOT NULL default '0', 
						`thumb_h` smallint(5) NOT NULL default '0', 
						`password` varchar(255) NOT NULL, 
						`postedat` int(20) NOT NULL, 
						`lastbumped` int(20) NOT NULL default '0', 
						`ip` varchar(75) NOT NULL, 
						`ipmd5` varchar(200) NOT NULL, 
						`stickied` tinyint(1) NOT NULL default '0', 
						`locked` tinyint(1) NOT NULL default '0', 
						`posterauthority` tinyint(1) NOT NULL default '0', 
						`IS_DELETED` tinyint(1) NOT NULL default '0', 
						UNIQUE KEY `id` (`id`), 
						KEY `threadid` (`threadid`), 
						KEY `lastbumped` (`lastbumped`)
						) ENGINE=MyISAM AUTO_INCREMENT=" . mysql_real_escape_string($_POST['firstpostid']) . " ;");
						/* Sleep for five seconds, to ensure the table was created before attempting to initialize a board class with it */
						sleep(5);
						$board_class = new Board(mysql_real_escape_string($_POST['directory']));
						$board_class->RegenerateAll();
						$tpl_page .= _('Board successfully added.') . '<br><br><a href="' . TC_BOARDSPATH . '/' . $_POST['directory'] . '/">/' . $_POST['directory'] . '/</a>!';
						management_addlogentry(_('Added board') . ': /' . $_POST['directory'] . '/', 3);
					} else {
						$tpl_page .= '<br>' . _('Unable to create directories.');
					}
				} else {
					$tpl_page .= _('A board with that name already exists.');
				}
			} else {
				$tpl_page .= _('Please fill in all required fields.');
			}
		}
		$tpl_page .= '<form action="manage_page.php?action=addboard" method="post">
	
		<label for="directory">Directory:</label>
		<input type="text" name="directory">
		<div class="desc">The directory of the board.  <b>Only put in the letter(s) of the board directory, no slashes!</b></div><br>
		
		<label for="desc">Description:</label>
		<input type="text" name="desc"><div class="desc">The name of the board.</div><br>
		
		<label for="firstpostid">First Post ID:</label>
		<input type="text" name="firstpostid" value="1">
		<div class="desc">The first post of this board will recieve this ID.</div><br>
		
		<input type="submit" value="Add Board">
		
		</form>';
	}
	
	function delboard() {
		global $tc_db, $smarty, $tpl_page;
		
		$this->AdministratorsOnly();
		$tpl_page .= '<h2>' . ucwords(_('Delete board')) . '</h2><br>';
		if (isset($_POST['directory'])) {
			if ($_POST['directory'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_POST['directory']) . "'");
				foreach ($results as $line) {
					$board_id = $line['id'];
					$board_dir = $line['name'];
				}
				if (count($results) > 0) {
					if ($_POST['confirmation'] == 'yes') {
						if (remove_board($board_dir)) {
							$tc_db->Execute("DROP TABLE `" . TC_DBPREFIX . "posts_" . $board_dir . "`");
							$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "boards` WHERE `id` = '" . $board_id . "'");
							$tpl_page .= _('Board successfully deleted.');
							management_addlogentry(_('Deleted board').': /' . $_POST['directory'] . '/', 3);
						} else {
							/* Error */
							$tpl_page .= _('Unable to delete board.');
						}
					} else {
						$tpl_page .= sprintf(_('Are you absolutely sure you want to delete %s?'),'/' . $board_dir . '/') .
						'<br>
						<form action="manage_page.php?action=delboard" method="post">
						<input type="hidden" name="directory" value="' . $_POST['directory'] . '">
						<input type="hidden" name="confirmation" value="yes">
						
						<input type="submit" value="'._('Continue').'">
						
						</form>';
					}
				} else {
					$tpl_page .= _('A board with that name does not exist.');
				}
			}
			$tpl_page .= '<hr>';
		}
		$tpl_page .= '<form action="manage_page.php?action=delboard" method="post">
		
		<label for="directory">'._('Directory').':</label>' .
		$this->MakeBoardListDropdown('directory', $this->BoardList($_SESSION['manageusername'])) .
		'<br>
		
		<input type="submit" value="'._('Delete board').'">
		
		</form>';
	}
	
	function changepwd() {
		global $tc_db, $smarty, $tpl_page;
		
		$tpl_page .= '<h2>' . ucwords(_('Change account password')) . '</h2><br>';
		if (isset($_POST['oldpwd']) && isset($_POST['newpwd']) && isset($_POST['newpwd2'])) {
			if ($_POST['oldpwd'] != '' && $_POST['newpwd'] != '' && $_POST['newpwd2'] != '') {
				if ($_POST['newpwd'] == $_POST['newpwd2']) {
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "'");
					foreach ($results as $line) {
						$staff_passwordenc = $line['password'];
					}
					if (md5($_POST['oldpwd']) == $staff_passwordenc) {
						$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "staff` SET `password` = '" . md5($_POST['newpwd']) . "' WHERE `username` = '" . mysql_real_escape_string($_SESSION['manageusername']) . "'");
						$_SESSION['managepassword'] = md5($_POST['newpwd']);
						$tpl_page .= _('Password successfully changed.');
					} else {
						$tpl_page .= _('The old password you provided did not match the current one.');
					}
				} else {
					$tpl_page .= _('The second password did not match the first.');
				}
			} else {
				$tpl_page .= _('Please fill in all required fields.');
			}
			$tpl_page .= '<hr>';
		}
		$tpl_page .= '<form action="manage_page.php?action=changepwd" method="post">
		
		<label for="oldpwd">' . _('Old password') . ':</label>
		<input type="password" name="oldpwd"><br>
	
		<label for="newpwd">' . _('New password') . ':</label>
		<input type="password" name="newpwd"><br>
		
		<label for="newpwd2">' . _('New password again') . ':</label>
		<input type="password" name="newpwd2"><br>
		
		<input type="submit" value="' ._('Change account password') . '">
		
		</form>';
	}
	
	function staff() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . _('Staff') . '</h2><br>';
		if (isset($_POST['staffusername']) && isset($_POST['staffpassword'])) {
			if ($_POST['staffusername'] != '' && $_POST['staffpassword'] != '') {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . mysql_real_escape_string($_POST['staffusername']) . "'");
				if (count($results) == 0) {
					if ($_POST['isadmin'] == "on") {
						$tc_db->Execute("INSERT HIGH_PRIORITY INTO `" . TC_DBPREFIX . "staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '" . mysql_real_escape_string($_POST['staffusername']) . "' , '" . md5($_POST['staffpassword']) . "' , '1' , '" . time() . "' )");
					} else {
						$tc_db->Execute("INSERT HIGH_PRIORITY INTO `" . TC_DBPREFIX . "staff` ( `username` , `password` , `isadmin` , `addedon` ) VALUES ( '" . mysql_real_escape_string($_POST['staffusername']) . "' , '" . md5($_POST['staffpassword']) . "' , '0' , '" . time() . "' )");
					}
					$tpl_page .= _('Staff member successfully added.');
					$logentry = _('Added staff member') . ' - ';
					if ($_POST['isadmin'] == "on") {
						$logentry .= _('Administrator');
					} else {
						$logentry .= _('Moderator');
					}
					$logentry .= ": " . $_POST['staffusername'];
					management_addlogentry($logentry, 6);
				} else {
					$tpl_page .= _('A staff member with that ID already exists.');
				}
				$tpl_page .= '<hr>';
			}
		} elseif (isset($_GET['del'])) {
			if ($_GET['del'] > 0) {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . mysql_real_escape_string($_GET['del']) . "'");
				if (count($results) > 0) {
					foreach ($results as $line) {
						$staff_username = $line['username'];
					}
					$tc_db->Execute("DELETE FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . mysql_real_escape_string($_GET['del']) . "'");
					$tpl_page .= _('Staff successfully deleted');
					management_addlogentry(_('Deleted staff member') . ': ' . $staff_username, 6);
				} else {
					$tpl_page .= _('Invalid staff ID.');
				}
				$tpl_page .= '<hr>';
			}
		} elseif (isset($_GET['edit'])) {
			if ($_GET['edit'] > 0) {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
				if (count($results) > 0) {
					if (isset($_POST['submitting'])) {
						foreach ($results as $line) {
							$staff_username = $line['username'];
							$staff_isadmin = $line['isadmin'];
						}
						$staff_boards = array();
						$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
						foreach ($results as $line) {
							$staff_boards = array_merge($staff_boards, array($line['name']));
						}
						$staff_changed_boards = array();
						$staff_new_boards = array();
						while (list($postkey, $postvalue) = each($_POST)) {
							if (substr($postkey, 0, 8) == "moderate") {
								$staff_changed_boards = array_merge($staff_changed_boards, array(substr($postkey, 8)));
							}
						}
						while (list(, $staff_thisboard_name) = each($staff_boards)) {
							if (in_array($staff_thisboard_name, $staff_changed_boards)) {
								$staff_new_boards = array_merge($staff_new_boards, array($staff_thisboard_name));
							}
						}
						$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "staff` SET `boards` = '" . mysql_real_escape_string(implode('|', $staff_new_boards)) . "' WHERE `id` = '" . mysql_real_escape_string($_GET['edit']) . "'");
						$tpl_page .= _('Staff successfully updated') . '<hr>';
						$logentry = _('Updated staff member') . ' - ';
						if ($_POST['isadmin'] == 'on') {
							$logentry .= _('Administrator');
						} else {
							$logentry .= _('Moderator');
						}
						$logentry .= ': ' . $staff_username;
						if ($_POST['isadmin'] != 'on') {
							$logentry .= ' - ' . _('Moderates') . ': /' . implode('/, /', $staff_new_boards) . '/';
						}
						management_addlogentry($logentry, 6);
					}
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "staff` WHERE `id` = '" . $_GET['edit'] . "'");
					foreach ($results as $line) {
						$staff_username = $line['username'];
						$staff_isadmin = $line['isadmin'];
						$staff_boards = explode('|', $line['boards']);
					}
					
					$tpl_page .= '<form action="manage_page.php?action=staff&edit=' . $_GET['edit'] . '" method="post">
					
					<label for="staffname">' . _('Username') . ':</label>
					<input type="text" name="staffname" value="' . $staff_username . '" disabled><br>
	
					<label for="isadmin">' . _('Administrator') . '?</label>
					<input type="checkbox" name="isadmin" ';
					if ($staff_isadmin == "1") {
						$tpl_page .= 'checked ';
					}
					$tpl_page .= '><br><br>';

					$tpl_page .= _('Moderates') . '<br>';
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards`");
					foreach ($results as $line) {
						$tpl_page .= '<label for="moderate' . $line['name'] . '">' . $line['name'] . '</label><input type="checkbox" name="moderate' . $line['name'] . '" ';
						if (in_array($line['name'], $staff_boards)) {
							$tpl_page .= 'checked ';
						}
						$tpl_page .= '><br>';
					}
					$tpl_page .= '<input type="submit" value="' . _('Modify staff member') . '" name="submitting">
					
					</form>
					<br>';

				} else {
					$tpl_page .= _('A staff member with that id does not appear to exist.');
				}
				$tpl_page .= '<hr>';
			}
		}
		$tpl_page .= '<form action="manage_page.php?action=staff" method="post">
		
		<label for="username">' . _('Username') . ':</label>
		<input type="text" name="staffusername"><br>
	
		<label for="password">' . _('Password') . ':</label>
		<input type="text" name="staffpassword"><br>
		
		<label for="isadmin">' . _('Administrator') . '?</label>
		<input type="checkbox" name="isadmin"><br>
		
		<input type="submit" value="' .  _('Add staff member') . '">
		
		</form>
		<hr><br>';
	
		$tpl_page .= _('Administrators') . ':<br>';
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "staff` WHERE `isadmin` = '1' ORDER BY `username` ASC");
		if (count($results) > 0) {
			$tpl_page .= '<table border="1"><tr><th>Username</th><th>Added on</th><th>&nbsp;</th></tr>';
			foreach ($results as $line) {
				$tpl_page .= '<tr>';
				$tpl_page .= '<td>' . $line['username'] . '</td><td>' . date("y/m/d(D)H:i", $line['addedon']) . '</td><td>[<a href="?action=staff&edit=' . $line['id'] . '">' . _('Edit') . '</a>]&nbsp;[<a href="?action=staff&del=' . $line['id'] . '">x</a>]</td>';
				$tpl_page .= '</tr>';
			}
			$tpl_page .= '</table>';
		} else {
			$tpl_page .= _('None');
		}
		$tpl_page .= _('Moderators') . ':<br>';
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "staff` WHERE `isadmin` = '0' ORDER BY `username` ASC");
		if (count($results) > 0) {
			$tpl_page .= '<table border="1"><tr><th>' . _('Username') . '</th><th>' . _('Added on') . '</th><th>' . _('Moderating boards') . '</th><th>&nbsp;</th></tr>';
			foreach ($results as $line) {
				$tpl_page .= '<tr>';
				$tpl_page .= '<td>' . $line['username'] . '</td><td>' . date("y/m/d(D)H:i", $line['addedon']) . '</td><td>';
				if ($line['boards'] != '') {
					$tpl_page .= '<b>/' . implode('/</b>, <b>/', explode('|', $line['boards'])) . '/</b>';
				} else {
					$tpl_page .= _('No boards');
				}
				$tpl_page .= '</td><td>[<a href="?action=staff&edit=' . $line['id'] . '">' . _('Edit') . '</a>]&nbsp;[<a href="?action=staff&del=' . $line['id'] . '">x</a>]</td>';
				$tpl_page .= '</tr>';
			}
			$tpl_page .= '</table>';
		} else {
			$tpl_page .= _('None');
		}
	}
		
	/* View a thread marked as deleted */
	function viewdeletedthread() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		$tpl_page .= '<h2>' . ucwords(_('View deleted thread')) . '</h2><br>';
		if (isset($_GET['threadid']) && isset($_GET['board'])) {
			if ($_GET['threadid'] > 0) {
				$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` WHERE `name` = '" . mysql_real_escape_string($_GET['board']) . "'");
				foreach ($results as $line) {
					$board_id = $line['id'];
					$board_dir = $line['name'];
				}
				if (count($results) > 0) {
					$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts_" . $board_dir . "` WHERE `id` = '" . mysql_real_escape_string($_GET['threadid']) . "'");
					if (count($results) > 0) {
						foreach ($results as $line) {
							$thread_isdeleted = $line['IS_DELETED'];
							$thread_threadid = $line['threadid'];
						}
						if ($thread_isdeleted == '1') {
							if ($thread_threadid == '0') {
								$tpl_page .= '<head><link rel="stylesheet" type="text/css" href="' . TC_BOARDSPATH . '/css/burichan.css" title="Burichan"></head>';
								//Devnote:  fix viewing deleted threads
								$tpl_page .= 'Broke for now, will return soon.';
								/* $tpl_page .= buildthread($board_id, $_GET['threadid'], false, true); */
							} else {
								$tpl_page .= _('That ID is a reply, not a thread.');
							}
						} else {
							$tpl_page .= _('That thread has yet to be deleted.');
						}
					} else {
						$tpl_page .= _('Invalid thread ID.  This may have been caused by the thread recently being deleted.');
					}
				}
			}
		} else {
			$tpl_page .= '<form action="?" method="get">
	
			<input type="hidden" name="action" value="viewdeletedthread">
			<label for="board"><'._('Board').':</label>' .
			$this->MakeBoardListDropdown('board', $this->BoardList($_SESSION['manageusername'])) .
			'<br>
			
			<label for="threadid">'._('Thread').':</label>
			<input type="text" name="threadid"><br>
			
			<input type="submit" value="'._('View deleted thread').'">
			
			</form>';
		}
	}
	
	/* Search for text in posts */
	function search() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
		
		//Devnote: fix searching
		die("Broken for now due to the new table system.");
		if (isset($_GET['query'])) {
			$search_query = $_GET['query'];
			if (isset($_GET['s'])) {
				$s = $_GET['s'];
			} else {
				$s = 0;
			}
			$trimmed = trim($search_query);
			$limit = 10;
			if ($trimmed == '') {
				$tpl_page .= _('Please enter a search query.');
				exit;
			}
			$query = "SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "posts` WHERE `IS_DELETED` = '0' AND `message` LIKE '%" . $trimmed . "%' ORDER BY `postedat` DESC";
			$numresults = $tc_db->GetAll($query);
			$numrows = count($numresults);
			if ($numrows == 0) {
				$tpl_page .= '<h4>' . _('Results') . '</h4>';
				$tpl_page .= '<p>' . _('Sorry, your search returned zero results.') . '</p>';
				die();
			}
			$query .= " LIMIT $s, $limit";
			$results = $tc_db->GetAll($query);
			$tpl_page .= '<p>' . _('You searched for') . ': &quot;' . $search_query . '&quot;</p>';
			$tpl_page .= _('Results') . ':<br><br>';
			$count = 1 + $s;
			foreach ($results as $line) {
				$board = boardid_to_dir($line['boardid']);
				$tpl_page .= $count . '. Board: /' . $board . '/, Thread #<a href="'.TC_BOARDSPATH . '/' . $board . '/res/';
				if ($line['threadid'] == 0) {
					$tpl_page .= $line['id'] . '.html">' . $line['id'] . '</a>';
				} else {
					$tpl_page .= $line['threadid'] . '.html#' . $line['id'] . '">' . $line['threadid'] . '</a>, Post #' . $line['id'];
				}
				$tpl_page .= '<fieldset>' . $line['message'] . '</fieldset><br>';
				$count++;
			}
			$currPage = (($s / $limit) + 1);
			$tpl_page .= '<br>';
			if ($s >= 1) {
				$prevs = ($s - $limit);
				print "&nbsp;<a href=\"?action=search&s=$prevs&query=$search_query\">&lt;&lt; Prev 10</a>&nbsp&nbsp;";
			}
			$pages = intval($numrows / $limit);
			if ($numrows % $limit) {
				$pages++;
			}
			if (!((($s + $limit) / $limit) == $pages) && $pages != 1) {
				$news = $s + $limit;
				$tpl_page .= "&nbsp;<a href=\"?action=search&s=$news&query=$search_query\">Next 10 &gt;&gt;</a>";
			}
		
			$a = $s + ($limit);
			if ($a > $numrows) {
				$a = $numrows;
			}
			$b = $s + 1;
			$tpl_page .= '<p>' . _('Results') . ' ' . $b . ' &gt;&gt; ' . $a . ' of ' . $numrows . '</p>';
			$tpl_page .= '<hr>';
		}
		
		$tpl_page .= '<form action="?" method="get">
		<input type="hidden" name="action" value="search">
		<input type="hidden" name="s" value="0">
		
		<label for="query">'._('Query').':</label>
		<input type="text" name="query" value="'.$_GET['query'].'"><br>
		
		<input type="submit" value="'._('Search').'">
		
		</form>';
	}
	
	/* View and delete reports */
	function reports() {
		global $tc_db, $smarty, $tpl_page;
		
		$tpl_page .= '<h2>' . _('Reports') . '</h2><br>';
		if (isset($_GET['clear'])) {
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `id` FROM `" . TC_DBPREFIX . "reports` WHERE `id` = '" . mysql_real_escape_string($_GET['clear']) . "' LIMIT 1");
			if (count($results) > 0) {
				$tc_db->Execute("UPDATE `" . TC_DBPREFIX . "reports` SET `cleared` = '1' WHERE `id` = '" . mysql_real_escape_string($_GET['clear']) . "' LIMIT 1");
				$tpl_page .= 'Report successfully cleared.<hr>';
			}
		}
		$query = "SELECT HIGH_PRIORITY " . TC_DBPREFIX . "posts.id, " . TC_DBPREFIX . "posts.threadid, " . TC_DBPREFIX . "posts.image, " . TC_DBPREFIX . "posts.imagetype, " . TC_DBPREFIX . "posts.message, " . TC_DBPREFIX . "boards.name, " . TC_DBPREFIX . "reports.id as reportid, " . TC_DBPREFIX . "reports.ip as reporterip FROM " . sqlboardlist() . " JOIN " . TC_DBPREFIX . "reports ON " . TC_DBPREFIX . "reports.postid = " . TC_DBPREFIX . "posts.id AND " . TC_DBPREFIX . "reports.boardid = " . TC_DBPREFIX . "posts.boardid JOIN " . TC_DBPREFIX . "boards ON " . TC_DBPREFIX . "posts.boardid = " . TC_DBPREFIX . "boards.id WHERE " . TC_DBPREFIX . "posts.IS_DELETED = 0 AND " . TC_DBPREFIX . "reports.cleared = 0";
		if (!$this->CurrentUserIsAdministrator()) {
			$boardlist = $this->BoardList($_SESSION['manageusername']);
			if (!empty($boardlist)) {
				$query .= ' AND (';
				foreach ($boardlist as $board) {
					$query .= ' boards.name = \'' . $board . '\' OR';
				}
				$query = substr($query, 0, -3) . ')';
			} else {
				$tpl_page .= 'You do not moderate any boards :(';
			}
		}
		$results = $tc_db->GetAll($query);
		if (count($results) > 0) {
			$tpl_page .= '<table border="1"><tr><th>Board</th><th>Post</th><th>Picture</th><th>Message</th><th>Reporter IP</th><th>Action</th></tr>';
			foreach ($results as $line) {
				$tpl_page .= '<tr><td>/' . $line['name'] . '/</td><td><a href="' . TC_BOARDSFOLDER . '' . $line['name'] . '/res/';
				if ($line['threadid'] == '0') {
					$tpl_page .= $line['id'];
					$post_threadorpost = 'thread';
				} else {
					$tpl_page .= $line['threadid'];
					$post_threadorpost = 'post';
				}
				$tpl_page .= '.html#' . $line['id'] . '">' . $line['id'] . '</a></td><td>';
				if ($line['image'] == 'removed') {
					$tpl_page .= 'removed';
				} elseif ($line['image'] == '') {
					$tpl_page .= 'none';
				} else {
					$tpl_page .= '<a href="' . TC_BOARDSFOLDER . '' . $line['name'] . '/src/' . $line['image'] . '.' . $line['imagetype'] . '"><img src="' . TC_BOARDSFOLDER . '' . $line['name'] . '/thumb/' . $line['image'] . 's.' . $line['imagetype'] . '" border="0"></a>';
				}
				$tpl_page .= '</td><td>';
				if ($line['message'] != '') {
					$tpl_page .= stripslashes($line['message']);
				} else {
					$tpl_page .= '&nbsp;';
				}
				$tpl_page .= '</td><td>' . md5_decrypt($line['reporterip'], TC_RANDOMSEED) . '</td><td><a href="?action=reports&clear=' . $line['reportid'] . '">Clear</a>&nbsp;&#91;<a href="?action=delposts&boarddir=' . $line['name'] . '&del' . $post_threadorpost . 'id=' . $line['id'] . '" title="Delete" onclick="return confirm(\'Are you sure you want to delete this thread/post?\');">D</a>&nbsp;<a href="' . TC_BOARDSFOLDER . 'manage_page.php?action=delposts&boarddir=' . $line['name'] . '&del' . $post_threadorpost . 'id=' . $line['id'] . '&postid=' . $line['id'] . '" title="Delete &amp; Ban" onclick="return confirm(\'Are you sure you want to delete and ban this poster?\');">&amp;</a>&nbsp;<a href="?action=bans&banboard=' . $line['name'] . '&banpost=' . $line['id'] . '" title="Ban">B</a>&#93;</td></tr>';
			}
			$tpl_page .= '</table>';
		} else {
			$tpl_page .= 'No reports to show.';
		}
	}
	
	/* Display links to miscellaneous administrator functions */
	function misc() {
		global $tc_db, $smarty, $tpl_page;
		$this->AdministratorsOnly();
	
		$tpl_page .= '<a href="?action=rebuildall">' . _('Rebuild all boards and html files') . '</a><br><a href="?action=checkversion">' . _('Check for new version') . '</a><br><a href="?action=spaceused">' . _('Disk space used') . '</a><br><a href="?action=viewdeletedthread">' . _('View deleted thread') . '</a><br><a href="?action=cleanup">' . _('Cleanup') . '</a><br><a href="?action=search">' . _('Search posts') . '</a><br><a href="?action=staff">' . _('Staff') . '</a><br><a href="?action=modlog">' . _('ModLog') . '</a><br><a href="?action=editfiletypes">' . 'Edit filetypes' . '</a><br><a href="?action=editsections">' . 'Edit sections' . '</a><br><a href="?action=sql">' . _('SQL query') . '</a>';
	}
	
	/* Display posting rates for the past hour */
	function posting_rates() {
		global $tc_db, $smarty, $tpl_page;
		
		$tpl_page .= '<h2>' . _('Posting rates (past hour)') . '</h2><br>';
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY * FROM `" . TC_DBPREFIX . "boards` ORDER BY `order` ASC");
		if (count($results) > 0) {
			$tpl_page .= '<table border="1" cellspacing="2" cellpadding="2"><tr><th>' . _('Board') . '</th><th>' . _('Threads') . '</th><th>' . _('Replies') . '</th><th>' . _('Posts') . '</th></tr>';
			foreach ($results as $line) {
				$rows_threads = $tc_db->GetOne("SELECT HIGH_PRIORITY count(id) FROM `" . TC_DBPREFIX . "posts_" . $line['name'] . "` WHERE `threadid` = 0 AND `postedat` >= " . (time() - 3600));
				$rows_replies = $tc_db->GetOne("SELECT HIGH_PRIORITY count(id) FROM `" . TC_DBPREFIX . "posts_" . $line['name'] . "` WHERE `threadid` != 0 AND `postedat` >= " . (time() - 3600));
				$rows_posts = $rows_threads + $rows_replies;
				$threads_perminute = $rows_threads;
				$replies_perminute = $rows_replies;
				$posts_perminute = $rows_posts;
				$tpl_page .= '<tr><td><b>' . $line['name'] . '</b></td><td>' . $threads_perminute . '</td><td>' . $replies_perminute . '</td><td>' . $posts_perminute . '</td></tr>';
			}
			$tpl_page .= '</table>';
		} else {
			$tpl_page .= _('No boards');
		}
	}
	
	/* If the user logged in isn't an admin, kill the script */
	function AdministratorsOnly() {
		global $tc_db, $smarty, $tpl_page;
		
		if (!$this->CurrentUserIsAdministrator()) {
			die('That page is for admins only.');
		}
	}
	
	/* See if the user logged in is an admin */
	function CurrentUserIsAdministrator() {
		global $tc_db, $smarty, $tpl_page;
		
		if ($_SESSION['manageusername'] == '' || $_SESSION['managepassword'] == '') {
			$_SESSION['manageusername'] = '';
			$_SESSION['managepassword'] = '';
			return false;
		}
		
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `isadmin` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . $_SESSION['manageusername'] . "' AND `password` = '" . $_SESSION['managepassword'] . "' LIMIT 1");
		foreach ($results as $line) {
			if ($line['isadmin'] == 1) {
				return true;
			} else {
				return false;
			}
		}
		
		/* If the function reaches this point, something is fishy.  Kill their session */
		session_destroy();
		die('Invalid session, please log in again.');
	}
	
	/* See if the user logged in is a moderator of a specified board */
	function CurrentUserIsModeratorOfBoard($board, $username) {
		global $tc_db, $smarty, $tpl_page;
		
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `isadmin`, `boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . $username . "' LIMIT 1");
		if (count($results) > 0) {
			foreach ($results as $line) {
				if ($line['isadmin'] == "1") {
					return true;
				} else {
					$array_boards = explode('|', $line['boards']);
					if (in_array($board, $array_boards)) {
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
	
	/* Generate a list of boards a moderator controls */
	function BoardList($username) {
		global $tc_db, $smarty, $tpl_page;
		
		$staff_boardsmoderated = array();
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `boards` FROM `" . TC_DBPREFIX . "staff` WHERE `username` = '" . $username . "' LIMIT 1");
		if (count($results) > 0) {
			if ($this->CurrentUserIsAdministrator()) {
				$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards` ORDER BY `name` ASC");
				foreach ($resultsboard as $lineboard) {
					$staff_boardsmoderated = array_merge($staff_boardsmoderated, array($lineboard['name']));
				}
			} else {
				foreach ($results as $line) {
					$array_boards = explode('|', $line['boards']);
				}
				foreach ($array_boards as $this_board_name) {
					$staff_boardsmoderated = array_merge($staff_boardsmoderated, array($this_board_name));
				}
			}
		}
		return $staff_boardsmoderated;
	}
	
	/* Generate a list of boards in query format */
	function sqlboardlist() {
		global $tc_db, $smarty, $tpl_page;
		
		$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `name` FROM `" . TC_DBPREFIX . "boards` ORDER BY `name` ASC");
		$sqlboards = '';
		foreach ($results as $lineboard) {
			$sqlboards .= 'posts_' . $line['name'] . ', ';
		}
		
		return substr($sqlboards, 0, -2);
	}
	
	/* Generate a dropdown box from a supplied array of boards */
	function MakeBoardListDropdown($name, $boards) {
		$output = '<select name="' . $name . '"><option value="">Select a Board</option>';
		if ($boards != '') {
			foreach ($boards as $board) {
				$output .= '<option value="' . $board . '">/' . $board . '/</option>';
			}
		}
		$output .= '</select>';
		
		return $output;
	}
	
	/* Generate a series of checkboxes from a supplied array of boards */
	function MakeBoardListCheckboxes($prefix, $boards) {
		$output = '';
		
		if ($boards != '') {
			foreach ($boards as $board) {
				$output .= '<label for="' . $prefix . $board . '">' . $board . '</label><input type="checkbox" name="' . $prefix . $board . '"> ';
			}
		}
		
		return $output;
	}
		
	/* Delete files without their md5 stored in the database */
	function delunusedimages($verbose = false) {
		global $tc_db, $smarty, $tpl_page;
		
		$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY `id`, `name` FROM `" . TC_DBPREFIX . "boards`");
		foreach ($resultsboard as $lineboard) {
			if ($verbose) {
				$tpl_page .= '<b>Looking for unused images in /' . $lineboard['name'] . '/</b><br>';
			}
			$imagemd5list = array();
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `imagemd5` FROM `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` WHERE `IS_DELETED` = 0 AND `image` != '' AND `image` != 'removed' AND `imagemd5` != ''");
			foreach ($results as $line) {
				$imagemd5list[] = $line['imagemd5'];
			}
			$dir = './' . $lineboard['name'] . '/src';
			$files = glob("$dir/{*.jpg, *.png, *.gif, *.swf}", GLOB_BRACE);
			if (is_array($files)) {
				foreach ($files as $image) {
					if (in_array(md5_file(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image)), $imagemd5list) == false) {
						if (time() - filemtime(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image)) > 120) {
							if ($verbose == true) {
								$tpl_page .= 'A live record for ' . $image . ' was not found;  the file has been removed.<br>';
							}
							unlink(TC_BOARDSDIR . $lineboard['name'] . '/src/' . basename($image));
							@unlink(TC_BOARDSDIR . $lineboard['name'] . '/thumb/' . substr(basename($image), 0, -4) . 's' . substr(basename($image), strlen(basename($image)) - 4));
						}
					}
				}
			}
		}
		return true;
	}
	
	/* Delete replies currently not marked as deleted who belong to a thread which is marked as deleted */
	function delorphanreplies($verbose = false) {
		global $tc_db, $smarty, $tpl_page;
		
		$resultsboard = $tc_db->GetAll("SELECT HIGH_PRIORITY `id`, `name` FROM `" . TC_DBPREFIX . "boards`");
		foreach ($resultsboard as $lineboard) {
			if ($verbose) {
				$tpl_page .= '<b>Looking for orphans in /' . $lineboard['name'] . '/</b><br>';
			}
			$results = $tc_db->GetAll("SELECT HIGH_PRIORITY `id`, `threadid` FROM `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` WHERE `threadid` != '0' AND `IS_DELETED` = 0");
			foreach ($results as $line) {
				$exists_rows = $tc_db->GetAll("SELECT HIGH_PRIORITY COUNT(*) FROM `" . TC_DBPREFIX . "posts_" . $lineboard['name'] . "` WHERE `id` = '" . $line['threadid'] . "' AND `IS_DELETED` = 0", 1);
				if ($exists_rows[0] == 0) {
					$post_class = new Post($line['id'], $lineboard['name']);
					$post_class->Delete;
					
					if ($verbose) {
						$tpl_page .= 'Reply #' . $line['id'] . '\'s thread (#' . $line['threadid'] . ') does not exist!  It has been deleted.<br>';
					}
				}
			}
		}
		return true;
	}
}
?>