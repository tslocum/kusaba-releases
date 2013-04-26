<?php

require("config.php");
require($tc_config['rootdir']."/inc/functions.php");
removed_expired_bans();

ban_check($_SERVER['REMOTE_ADDR'],'',true);

?>