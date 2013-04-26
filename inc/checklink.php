<?php

/* Thanks phadeguy - http://www.zend.com/codex.php?id=1256&single=1 */
// function definition for check_link...
// expects a link url as string
// returns an array of three elements:
//   return_array[0] = HTTP version
//   return_array[1] = Returned error number (200, 404, etc)
//   return_array[2] = Returned error text ("OK", "File Not Found", etc)
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
} // function 

?>