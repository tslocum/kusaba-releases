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
* "You are banned" page
* +------------------------------------------------------------------------------+
* Users will be redirected to this page when they are banned from posting to or
* viewing the boards.
* +------------------------------------------------------------------------------+
*/
require('config.php');
require(TC_ROOTDIR . 'inc/functions.php');
require_once(TC_ROOTDIR . 'inc/classes/bans.class.php');

$bans_class = new Bans();

$bans_class->RemoveExpiredBans();
$bans_class->BanCheck($_SERVER['REMOTE_ADDR'], '', true);

?>