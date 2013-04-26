<?php

require("config.php");
require_once(TC_ROOTDIR."inc/functions.php");
require_once(TC_ROOTDIR."inc/encryption.php");

/* Uncomment the line below to show debug information if your upgrade isn't going so well */
//$tc_db->debug = true;

if ($_GET['do']=='insertsql') {
    $tc_db->Execute("ALTER TABLE `boards` ADD `".TC_DBPREFIX."defaultstyle` VARCHAR( 50 ) NOT NULL DEFAULT 'Futaba' AFTER `forcedanon` ;
");

    $tc_db->Execute("ALTER TABLE `boards` ADD `".TC_DBPREFIX."enablenofile` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `enablecaptcha` ;");

    $tc_db->Execute("CREATE TABLE `".TC_DBPREFIX."filetypes` (
  `id` mediumint(5) NOT NULL auto_increment,
  `filetype` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `image_w` int(7) NOT NULL default '0',
  `image_h` int(7) NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;");

    echo 'SQL inserted.<br><br><b>Notice</b>: The config table has been removed from use.  It was, however, not dropped by this script.  To remove it, please use a database explorer.  Removing it is not required, but it will not be used in this release.';
} else {
    echo '<a href="?do=insertsql">Insert upgrade SQL</a>';
}

?>