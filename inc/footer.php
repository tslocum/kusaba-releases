<?php
if (!function_exists("chan_footer")) {
	function chan_footer($noboardlist = false,$executiontime = 0,$hide_extra = false) {
		if (!$hide_extra) {
			if ($noboardlist==false) {
				$output = '<br />'.display_boardlist();
			} else {
				$output = '';
			}
		}
		is_file("config.php") ? require("config.php") : require("../config.php");
		if ($hide_extra) {
			$output .= '<div class="legal">';
		} else {
			$output .= '<p class="footer">';
		}
		$output .= '- <a href="http://www.trevorchan.org/" target="_top">Trevorchan</a> v'.$chan_version.' + Created by <a href="http://www.tj9991.com/" target="_top">tj9991</a> -';
		if ($executiontime>0) {
			$output .= '<br>Took '.round($executiontime,2).'s';
		}
		if ($hide_extra) {
			$output .= '<br><a href="'.$chan_boardspath.'/manage.php">Manage Boards</a></div>';
		} else {
			$output .= '</p>';
		}
		$output .= '</body>
		</html>';
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
?>
