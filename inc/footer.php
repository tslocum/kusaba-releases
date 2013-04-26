<?php
if (!function_exists("chan_footer")) {
	function chan_footer($noboardlist = false) {
		if ($noboardlist==false) {
			$output = '<br />'.display_boardlist();
		} else {
			$output = '';
		}
		is_file("config.php") ? require("config.php") : require("../config.php");
		$output .= '
		<p class="footer"> - <a href="http://trevorchan.tj9991.com/">Trevorchan</a> v'.$chan_version.' + Created by <a href="http://www.tj9991.com/">tj9991</a> -</p>
		</body>
		</html>';
		$output = str_replace(chr(9),"",$output);
		$output = str_replace(chr(10),"",$output);
		$output = str_replace(chr(13),"",$output);
		return $output;
	}
}
?>