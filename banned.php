<?php

require('config.php');
require(TC_ROOTDIR.'inc/functions.php');
removed_expired_bans();

ban_check($_SERVER['REMOTE_ADDR'],'',true);

?>