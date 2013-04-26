<?php
/*
* +------------------------------------------------------------------------------+
* Login page for the manage panel
* +------------------------------------------------------------------------------+
* Username and password input to log in, which has been separated from the
* manage.php file for ease of modification.
* +------------------------------------------------------------------------------+
*/
?>
<script type="text/javascript">function sf(){document.managelogin.username.focus();}</script><body onload="sf();"></body><div style="text-align: center;"><img src="hardgay.gif" alt="Pikachu" title="PIKA PIKA! CHUUUUU~~~" /><br /><form action="?action=login" method="post" name="managelogin"><input type="text" name="username"><br /><input type="password" name="password"><br /><input type="submit" value="Submit"></form></div>