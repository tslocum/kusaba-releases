<?php

require("config.php");
require_once(TC_ROOTDIR."inc/functions.php");
require_once(TC_ROOTDIR."inc/encryption.php");

/* Uncomment the line below to show debug information if your upgrade isn't going so well */
//$tc_db->debug = true;

if ($_GET['do']=='insertsql') {
    $tc_db->Execute("ALTER TABLE `".TC_DBPREFIX."reports` CHANGE `ip` `ip` VARCHAR( 75 ) NOT NULL ;");

    $tc_db->Execute("CREATE TABLE `".TC_DBPREFIX."passcache` (
`md5` VARCHAR( 100 ) NOT NULL ,
`tripcode` VARCHAR( 10 ) NOT NULL
) ENGINE = MYISAM ;");

    $tc_db->Execute("ALTER TABLE `".TC_DBPREFIX."banlist` CHANGE `id` `id` SMALLINT( 5 ) NOT NULL AUTO_INCREMENT");

    $tc_db->Execute("ALTER TABLE `".TC_DBPREFIX."sections` ADD `hidden` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `order`");

    $tc_db->Execute("ALTER TABLE `".TC_DBPREFIX."boards` ADD `uploadtype` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `type`");

    $tc_db->Execute("ALTER TABLE `".TC_DBPREFIX."boards` ADD `enablecaptcha` TINYINT( 1 ) NOT NULL DEFAULT '0' AFTER `enablereporting`");

    //New table system, I pray to raptor jesus it works
    echo '<br>Creating individual tables for each board<br><ul>';
    $resultsboard = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."boards`");
    foreach ($resultsboard AS $lineboard) {
        echo '<li>Creating table for /'.$lineboard['name'].'/...</li>';
        $results = $tc_db->SelectLimit("SELECT `id` FROM `".TC_DBPREFIX."posts` WHERE `boardid` = '".$lineboard['id']."' ORDER BY `id` DESC",1);
        $nextpost = 1;
        foreach($results AS $line) {
            $nextpost = $line['id']+1;
        }
        $tc_db->Execute("CREATE TABLE `".TC_DBPREFIX."posts_".$lineboard['name']."` (
        `id` int(10) NOT NULL auto_increment,
        `threadid` int(10) NOT NULL default '0',
        `user` varchar(255) NOT NULL,
        `tripcode` varchar(10) NOT NULL,
        `email` varchar(255) NOT NULL,
        `subject` varchar(255) NOT NULL,
        `message` text NOT NULL,
        `image` varchar(20) NOT NULL,
        `imagetype` varchar(5) NOT NULL,
        `imagemd5` text NOT NULL,
        `image_w` smallint(5) NOT NULL default '0',
        `image_h` smallint(5) NOT NULL default '0',
        `image_size` int(10) NOT NULL default '0',
        `thumb_w` smallint(5) NOT NULL default '0',
        `thumb_h` smallint(5) NOT NULL default '0',
        `password` varchar(255) NOT NULL,
        `postedat` int(20) NOT NULL,
        `lastbumped` int(20) NOT NULL default '0',
        `ip` varchar(75) NOT NULL,
        `ipmd5` varchar(200) NOT NULL,
        `stickied` tinyint(1) NOT NULL default '0',
        `locked` tinyint(1) NOT NULL default '0',
        `posterauthority` tinyint(1) NOT NULL default '0',
        `IS_DELETED` tinyint(1) NOT NULL default '0',
        UNIQUE KEY `id` (`id`),
        KEY `threadid` (`threadid`),
        KEY `lastbumped` (`lastbumped`)
        ) ENGINE=MyISAM AUTO_INCREMENT=".$nextpost." ;");
        echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;Table created.</li>';
        echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;Copying board posts from singular table to new table...</li>';
        $results = $tc_db->GetAll("SELECT * FROM `".TC_DBPREFIX."posts` WHERE `boardid` = ".$lineboard['id']."");
        foreach ($results AS $line) {
            $tc_db->Execute("INSERT INTO `".TC_DBPREFIX."posts_".$lineboard['name']."` ( `id` , `threadid` , `user` , `tripcode` , `email` , `subject` , `message` , `image` , `imagetype` , `imagemd5` , `image_w` , `image_h` , `image_size` , `thumb_w` , `thumb_h` , `password` , `postedat` , `lastbumped` , `ip` , `ipmd5` , `stickied` , `locked` , `posterauthority` , `IS_DELETED` )
            VALUES
            ( '".$line['id']."' , '".$line['threadid']."' , '".$line['user']."' , '".$line['tripcode']."' , '".mysql_real_escape_string($line['email'])."' , '".mysql_real_escape_string($line['subject'])."' , '".mysql_real_escape_string($line['message'])."' , '".$line['image']."' , '".$line['imagetype']."' , '".$line['imagemd5']."' , '".$line['image_w']."' , '".$line['image_h']."' , '".$line['image_size']."' , '".$line['thumb_w']."' , '".$line['thumb_h']."' , '".$line['password']."' , '".$line['postedat']."' , '".$line['lastbumped']."' , '".$line['ip']."' , '".$line['ipmd5']."' , '".$line['stickied']."' , '".$line['locked']."' , '".$line['posterauthority']."' , '".$line['IS_DELETED']."' );");
        }
        echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;'.count($results).' posts copied.</li>';
    }
    echo '<li>Renaming the old posts table to '.TC_DBPREFIX.'posts__deleteme...</li>';
    $tc_db->Execute("RENAME TABLE `".TC_DBPREFIX."posts` TO `".TC_DBPREFIX."posts__deleteme`");
    echo '<li>&nbsp;&nbsp;&nbsp;&nbsp;Table renamed.</li>';
    echo '</ul>';

    echo 'SQL inserted.';
} else {
    echo '<a href="?do=insertsql">Insert upgrade SQL</a>';
}

?>