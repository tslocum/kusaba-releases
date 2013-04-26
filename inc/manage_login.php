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
   * Login page for the manage panel
   * +------------------------------------------------------------------------------+
   * Username and password input to log in, which has been separated from the
   * manage.php file for ease of modification.
   * +------------------------------------------------------------------------------+
   */
?>
<script type="text/javascript">function sf(){document.managelogin.username.focus();}</script><body onload="sf();"></body><div style="text-align: center;"><img src="hardgay.gif" alt="Pikachu" title="PIKA PIKA! CHUUUUU~~~" /><br /><form action="?action=login" method="post" name="managelogin"><input type="text" name="username"><br /><input type="password" name="password"><br /><input type="submit" value="Submit"></form></div>