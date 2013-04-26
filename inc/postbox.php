<?php
if (!function_exists("chan_postbox")) {
	function chan_postbox($board, $replythread = '0') {
		require("config.php");
		$result = mysql_query("SELECT * FROM `boards` WHERE `name` = '".$board."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_forcedanon = $line['forcedanon'];
			}
		} else {
			die("INVALID BOARD FED TO POSTBOX");
		}
		$output = '';
		$output .= '
		<div class="postarea">
		<form id="postform" action="'.$chan_boardspath.'/board.php" method="post" enctype="multipart/form-data">
		<input type="hidden" name="board" value="'.$board.'" />';
		if ($replythread!='0') {
			$output .= '
			<input type="hidden" name="replythread" value="'.$replythread.'" />';
		}
		$output .= '
		<p>
		<table><tbody>';
		if ($board_forcedanon!='1') {
			$output .= '<tr>
			<td class="postblock">Name</td><td><input type="text" name="name" size="28" maxlength="75" /></td>
			</tr>';
		}
		$output .= '<tr>
		<td class="postblock">E-mail</td><td><input type="text" name="email" size="28" maxlength="75" /></td>
		</tr>
		<tr>
		<td class="postblock">Subject</td><td><input type="text" name="subject" size="35" maxlength="75" /><input type="submit" value="Submit" /></td>
		</tr>
		<tr>
		<td class="postblock">Message</td><td><textarea name="message" cols="48" rows="4"></textarea></td>
		</tr>
		<tr>
		<td class="postblock">File<a href="#" onclick="togglePassword();" style="text-decoration: none;">&nbsp;</a></td><td><input type="file" name="imagefile" size="35" /></td>
		</tr>
		<tr>
		<td class="postblock">Password</td><td><input type="password" name="postpassword" size="8" />&nbsp;(for post and file deletion)</td>
		</tr>
		<tr id="passwordbox">
		<td></td><td></td>
		</tr>
		<tr>
		<td colspan="2"><div class="rules">'.format_postboxnotice(config_getvalue('postboxnotice'),$board).'</div></td>
		</tr>
		</tbody></table>
		</form>
		</div>
		<script type="text/javascript">set_inputs("postform")</script>
		<hr />';
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
?>