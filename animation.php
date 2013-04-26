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
* oekaki animation viewer
* +------------------------------------------------------------------------------+
*/
if (!isset($_GET['board']) || !isset($_GET['id'])) {
	die();
}

require('config.php');

?>
<applet name="pch" code="pch2.PCHViewer.class" archive="<?php echo TC_BOARDSFOLDER . PCHViewer123.jar; ?>" width="400" height="426" alt="Applet requires Java 1.1 or later to run!" mayscript">
<param name="archive" value="PCHViewer123.jar">
 <param name="image_width" value="400">
 <param name="image_height" value="400">

 <param name="pch_file" value="<?php echo TC_BOARDSFOLDER . $_GET['board'] . '/src/' . $_GET['id'] . '.pch'; ?>">
 <param name="run" value="true">
 <param name="buffer_progress" value="false">
 <param name="buffer_canvas" value="false">
 <param name="dir_resource" value="./tcdrawings/res/">
 <param name="res.zip" value="./tcdrawings/res/res.zip">
 <param name="tt.zip" value="./tcdrawings/res/tt.zip">
 <param name="tt_size" value="31">
 <param name="color_text" value="#000000">

 <param name="color_bk" value="#EEEEFF">
 <param name="color_bk2" value="#CCCCFF">
 <param name="color_icon" value="#CCCCFF">
 <param name="color_iconselect" value="#AAAAFF">
 <param name="tool_color_button" value="#CCCCFF">
 <param name="tool_color_button2" value="#CCCCFF">
 <param name="tool_color_text" value="#000000">
 <param name="tool_color_frame" value="#CCCCFF">
 <param name="color_bar" value="#AAAAFF">

 <param name="color_bar_hl" value="#CCCCFF">
 <param name="color_bar_frame_hl" value="#CCCCFF">
 <param name="color_bar_frame_shadow" value="#CCCCFF">
 <div align="center">Java must be installed and enabled to use this applet.  Please refer to our Java setup tutorial for more information.</div>
</applet>

<?php

?>