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
 * Common encryption functions
 * 
 * Allow for more advanced encryption using the random seed set in the
 * configuration.
 *  
 * @package kusaba   
 */

if (!function_exists('get_rnd_iv')) {
	function get_rnd_iv($iv_len) {
		$iv = '';
		while ($iv_len-- > 0) {
			$iv .= chr(mt_rand() & 0xff);
		}
		return $iv;
	}
	function md5_encrypt($plain_text, $password, $iv_len = 16) {
		$plain_text .= "\x13";
		$n = strlen($plain_text);
		if ($n % 16) $plain_text .= str_repeat("\0", 16 - ($n % 16));
		$i = 0;
		$enc_text = get_rnd_iv($iv_len);
		$iv = substr($password ^ $enc_text, 0, 512);
		while ($i < $n) {
			$block = substr($plain_text, $i, 16) ^ pack('H*', md5($iv));
			$enc_text .= $block;
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return base64_encode($enc_text);
	}
	function md5_decrypt($enc_text, $password, $iv_len = 16) {
		$enc_text = base64_decode($enc_text);
		$n = strlen($enc_text);
		$i = $iv_len;
		$plain_text = '';
		$iv = substr($password ^ substr($enc_text, 0, $iv_len), 0, 512);
		while ($i < $n) {
			$block = substr($enc_text, $i, 16);
			$plain_text .= $block ^ pack('H*', md5($iv));
			$iv = substr($block . $iv, 0, 512) ^ $password;
			$i += 16;
		}
		return preg_replace('/\\x13\\x00*$/', '', $plain_text);
	}
}

?>