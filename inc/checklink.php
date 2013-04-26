<?php
/*
 * This file is part of kusaba.
 *
 * kusaba is free software; you can redistribute it and/or modify it under the
 * terms of the GNU General Public License as published by the Free Software
 * Foundation; either version 2 of the License, or (at your option) any later
 * version.
 *
 * kusaba is distributed in the hope that it will be useful, but WITHOUT ANY
 * WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR
 * A PARTICULAR PURPOSE.  See the GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along with
 * kusaba; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */
/** 
 * Link validator
 * 
 * Will use cURL to attempt to visit a webpage, and then return based upon how the
 * request was handled.  Used for embedded videos to validate the ID is existant.
 *  
 * @package kusaba   
 */

/* Thanks phadeguy - http://www.zend.com/codex.php?id=1256&single=1
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