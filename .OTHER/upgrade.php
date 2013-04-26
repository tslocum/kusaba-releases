<?php
require("config.php");
require_once($tc_config['rootdir']."/inc/functions.php");
require_once($tc_config['rootdir']."/inc/encryption.php");

echo 'Note:  Run each step ONCE.<br><br>1. <a href="?do=insertimageinfo">Update image dimensions to new system</a><br>2. <a href="?do=cryptip">Encrypt IP addresses</a><br>3. <a href="?do=insertsql">Insert upgrade SQL</a><br><br>';

if ($_GET['do']=='insertimageinfo') {
    $i = 0;
    $result = mysql_query("SELECT {$tc_config['dbprefix']}posts.id , {$tc_config['dbprefix']}posts.image , {$tc_config['dbprefix']}posts.imagetype, {$tc_config['dbprefix']}posts.boardid, {$tc_config['dbprefix']}boards.name AS boardname FROM `{$tc_config['dbprefix']}posts` JOIN `{$tc_config['dbprefix']}boards` ON {$tc_config['dbprefix']}posts.boardid = {$tc_config['dbprefix']}boards.id WHERE {$tc_config['dbprefix']}posts.image_w = '0' AND {$tc_config['dbprefix']}posts.image != '' AND {$tc_config['dbprefix']}posts.image != 'removed' AND {$tc_config['dbprefix']}posts.IS_DELETED = 0",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    while ($line = mysql_fetch_assoc($result)) {
        $image_path = $tc_config['boardsdir']."/".$line['boardname']."/src/".$line['image'].'.'.$line['imagetype'];
        $image_thumb_path = $tc_config['boardsdir']."/".$line['boardname']."/thumb/".$line['image'].'s.'.$line['imagetype'];
        $imgSize = filesize($image_path);
        $imageDim = getimagesize($image_path);
        $imgWidth = $imageDim[0];
        $imgHeight = $imageDim[1];
        $imgDim_thumb = getimagesize($image_thumb_path);
        $imgWidth_thumb = $imgDim_thumb[0];
        $imgHeight_thumb = $imgDim_thumb[1];
        mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `image_w` = '".$imgWidth."' , `image_h` = '".$imgHeight."' , `image_size` = '".$imgSize."' , `thumb_w` = '".$imgWidth_thumb."' , `thumb_h` = '".$imgHeight_thumb."' WHERE `id` = '".$line['id']."' AND `boardid` = '".$line['boardid']."' LIMIT 1",$tc_config['dblink']);
        echo mysql_error($tc_config['dblink']);
        $i++;
    }
    echo 'Updated posts\' stored image size.  '.$i.' posts updated.<br>';
} elseif ($_GET['do']=='cryptip') {
    $i = 0;
    $result = mysql_query("SELECT `ip` FROM `{$tc_config['dbprefix']}iplist`",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    while ($line = mysql_fetch_assoc($result)) {
        mysql_query("UPDATE `{$tc_config['dbprefix']}iplist` SET `ip` = '".md5_encrypt($line['ip'],$tc_config['randomseed'])."' , `ipmd5` = '".md5($line['ip'])."' WHERE `ip` = '".$line['ip']."'",$tc_config['dblink']);
        $i++;
    }
    echo 'Encrypted IP addresses in iplist.  '.$i.' addresses encrypted.<br>';
    
    $i = 0;
    $result = mysql_query("SELECT `ip` FROM `{$tc_config['dbprefix']}banlist`",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    while ($line = mysql_fetch_assoc($result)) {
        mysql_query("UPDATE `{$tc_config['dbprefix']}banlist` SET `ip` = '".md5_encrypt($line['ip'],$tc_config['randomseed'])."' , `ipmd5` = '".md5($line['ip'])."' WHERE `ip` = '".$line['ip']."'",$tc_config['dblink']);
        $i++;
    }
    echo 'Encrypted IP addresses in banlist.  '.$i.' addresses encrypted.<br>';
    
    $i = 0;
    $result = mysql_query("SELECT `ip` FROM `{$tc_config['dbprefix']}posts`",$tc_config['dblink']);
    $rows = mysql_num_rows($result);
    while ($line = mysql_fetch_assoc($result)) {
        mysql_query("UPDATE `{$tc_config['dbprefix']}posts` SET `ip` = '".md5_encrypt($line['ip'],$tc_config['randomseed'])."' , `ipmd5` = '".md5($line['ip'])."' WHERE `ip` = '".$line['ip']."'",$tc_config['dblink']);
        $i++;
    }
    echo 'Encrypted IP addresses in posts.  '.$i.' addresses encrypted.<br>';
} elseif ($_GET['do']=='insertsql') {
    $result = mysql_query("ALTER TABLE `boards` ADD `enablereporting` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `forcedanon` ;",$tc_config['dblink']);
$result = mysql_query("CREATE TABLE `reports` (
  `id` int(5) NOT NULL auto_increment,
  `cleared` tinyint(1) NOT NULL default '0',
  `boardid` int(5) NOT NULL,
  `postid` int(20) NOT NULL,
  `when` int(20) NOT NULL,
  `ip` varchar(15) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=12 DEFAULT CHARSET=latin1;",$tc_config['dblink']);

$result = mysql_query("ALTER TABLE `sections` ADD `abbreviation` CHAR( 4 ) NOT NULL AFTER `name` ;",$tc_config['dblink']);

$result = mysql_query("ALTER TABLE `posts` ADD `image_w` SMALLINT( 5 ) NOT NULL DEFAULT '0' AFTER `imagemd5` ,
ADD `image_h` SMALLINT( 5 ) NOT NULL DEFAULT '0' AFTER `image_w` ,
ADD `image_size` INT( 10 ) NOT NULL DEFAULT '0' AFTER `image_h` ,
ADD `thumb_w` SMALLINT( 5 ) NOT NULL DEFAULT '0' AFTER `image_size` ,
ADD `thumb_h` SMALLINT( 5 ) NOT NULL DEFAULT '0' AFTER `thumb_w` ;",$tc_config['dblink']);

$result = mysql_query("ALTER TABLE `banlist` ADD `ipmd5` VARCHAR( 200 ) NOT NULL AFTER `ip` ;",$tc_config['dblink']);
$result = mysql_query("ALTER TABLE `iplist` ADD `ipmd5` VARCHAR( 200 ) NOT NULL AFTER `ip` ;",$tc_config['dblink']);
$result = mysql_query("ALTER TABLE `posts` ADD `ipmd5` VARCHAR( 200 ) NOT NULL AFTER `ip` ;",$tc_config['dblink']);

$result = mysql_query("ALTER TABLE `banlist` ADD `allowread` TINYINT( 1 ) NOT NULL DEFAULT '1' AFTER `type` ;",$tc_config['dblink']);
    if ($result) {
        echo 'SQL successfully inserted.';
    } else {
        echo 'Error inserting SQL: '.mysql_error($tc_config['dblink']);
    }
}

?>