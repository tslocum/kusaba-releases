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
* Penis Module
* +------------------------------------------------------------------------------+
* When activated for a board, any post containing the word penis will have the
* word changed to have random and fun colors.   
* +------------------------------------------------------------------------------+
*/

/* Is this module authorized to be used right now? */
function penis_authorized($board) {
	$boards_authorized = explode('|', module_setting_get('penis', 'boards'));
	
	if (in_array($board, $boards_authorized)) {
		return true;
	} else {
		return false;
	}
}

function penis_info() {
	$info = array();
	$info['type']['board-specific'] = true;
	
	return $info;
}

function penis_settings() {
	$settings = array();
	
}

function penis_help() {
	$output = 'Penis Plugin:  A plugin which will convert the word "penis" into something more colorful, to brighten your day.';
	
	return $output;
}

function penis_process_post($post) {
	$post['message'] = preg_replace_callback('/penis/i', 'penis__replace_callback', $post['message']);
	
	return $post;
}

function penis__replace_callback($matches) {
	$random_colors = penis__random_colors();
	$color_foreground = $random_colors[0];
	$color_background = $random_colors[1];
	
	return '<span style="color: ' . $color_foreground . '; background-color: ' . $color_background . '"><b>PENIS</b></span>';
}

function penis__random_colors() {
	$colors = array('red', 'black', 'yellow', 'green', 'blue');
	$color_foreground = '';
	$color_background = '';
	
	while ($color_foreground == $color_background) {
		$color_index = rand(0, (count($colors) - 1));
		$color_foreground = $colors[$color_index];
		
		$color_index = rand(0, (count($colors) - 1));
		$color_background = $colors[$color_index];
	}
	
	return array($color_foreground, $color_background);
}

?>