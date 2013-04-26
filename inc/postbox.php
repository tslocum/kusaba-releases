<?php
if (!function_exists("chan_postbox")) {
	function chan_postbox($board, $replythread = '0',$oekaki = '') {
		require("config.php");
		$result = mysql_query("SELECT * FROM `".$chan_prefix."boards` WHERE `name` = '".$board."'",$dblink);
		$rows = mysql_num_rows($result);
		if ($rows>0) {
			while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
				$board_id = $line['id'];
				$board_type = $line['type'];
				$board_forcedanon = $line['forcedanon'];
			}
		} else {
			die("INVALID BOARD FED TO POSTBOX");
		}
		$output = '';
		if ($board_type=='1'&&$replythread!='0') {
			//Do nothing
		} else {
			if ($board_type=='0'||$board_type=='2') {
				$output .= '
				<div class="postarea">';
				$label_class = 'postblock';
			} else {
				$output .= '<div class="hborder head newthread">
				<a id="newthread"></a>
				<h2>New Thread</h2>';
				$label_class = 'label';
			}
			if ($board_type=='2'&&$oekaki=='') {
				$output .= '<form action="'.$chan_boardsfolder.'/paint.php" method="post">
				<input type="hidden" name="board" value="'.$board.'">
				<input type="hidden" name="replyto" value="'.$replythread.'">
				<label for="applet">Paint with:&nbsp;</label><select name="applet"><option value="shipainter">Shi-Painter</option><option value="shipainterpro">Shi-Painter Pro</option></select>&nbsp;
				<label for="width">Width:&nbsp;<input type="text" name="width" size="3" value="300">&nbsp;<label for="height">Height:&nbsp;<input type="text" name="height" size="3" value="300">&nbsp;';
				if ($replythread!='0') {
					$output .= '<label for="replyimage">Source:&nbsp;</label><select name="replyimage"><option value="0">New Image</option>';
					$result = mysql_query("SELECT * FROM `".$chan_prefix."posts` WHERE `boardid` = '".$board_id."' AND `id` = '".$replythread."' AND `image` != '' AND `image` != 'removed' AND `imagetype` != 'swf' AND `IS_DELETED` = '0'",$dblink);
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
					}
					$result = mysql_query("SELECT * FROM `".$chan_prefix."posts` WHERE `boardid` = '".$board_id."' AND `threadid` = '".$replythread."' AND `image` != '' AND `image` != 'removed' AND `imagetype` != 'swf' AND `IS_DELETED` = '0'",$dblink);
					while ($line = mysql_fetch_array($result, MYSQL_ASSOC)) {
						$output .= '<option value="'.$line['id'].'">Modify No.'.$line['id'].'</option>';
					}
					$output .= '</select>&nbsp;';
				}
				$output .= '<input type="submit" value="Paint!">
				</form><hr>';
			}
			if (($board_type=='2'&&$oekaki!=''&&$replythread=='0')||($board_type=='2'&&$replythread!='0')||($board_type=='0'||$board_type=='1')) {
				$output .= '
				<form id="postform" action="'.$chan_boardspath.'/board.php" method="post" enctype="multipart/form-data">
				<input type="hidden" name="board" value="'.$board.'" />';
				if ($replythread!='0') {
					$output .= '
					<input type="hidden" name="replythread" value="'.$replythread.'" />';
				}
				$output .= '
				<p>
				<table class="postform"><tbody>';
				if ($board_forcedanon!='1') {
					$output .= '<tr>
					<td class="'.$label_class.'">Name</td><td><input type="text" name="name" size="28" maxlength="75" /></td>
					</tr>';
				}
				$output .= '<tr>
				<td class="'.$label_class.'">E-mail</td><td><input type="text" name="email" size="28" maxlength="75" /></td>
				</tr>
				<tr>
				<td class="'.$label_class.'">Subject</td><td><input type="text" name="subject" size="35" maxlength="75" />&nbsp;<input type="submit" value="Submit" /></td>
				</tr>
				<tr>
				<td class="'.$label_class.'">Message</td><td><textarea name="message" cols="48" rows="4"></textarea></td>
				</tr>';
				if ($board_type=='0'||$board_type=='2') {
					$output .= '<tr>
					<td class="'.$label_class.'">File<a href="#" onclick="togglePassword();" style="text-decoration: none;">&nbsp;</a></td><td>';
					if ($oekaki=='') {
						$output .= '
						<input type="file" name="imagefile" size="35" />';
					} else {
						$output .= 'Shown Below';
						$output .= '<input type="hidden" name="oekaki" value="'.$oekaki.'" />';
					}
					$output .= '
					</td>
					</tr>
					<tr>
					<td class="'.$label_class.'">Password</td><td><input type="password" name="postpassword" size="8" />&nbsp;(for post and file deletion)</td>
					</tr>
					<tr id="passwordbox">
					<td></td><td></td>
					</tr>';
				}
				if ($board_type=='0'||$board_type=='2') {
					$output .= '<tr>
					<td colspan="2"><div class="rules">'.format_postboxnotice(config_getvalue('postboxnotice'),$board).'</div></td>
					</tr>';
				}
				$output .= '
				</tbody></table>
				</form>';
				if ($board_type=='0'||$board_type=='2') {
					$output .= '<hr>';
				}
			}
			$output .= '</div>';
			$output .= '<script type="text/javascript">set_inputs("postform")</script>';
		}
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
?>
