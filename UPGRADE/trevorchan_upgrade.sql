INSERT INTO `config` ( `key` , `value` ) VALUES ('maxthumbwidth', '200'), ('maxthumbheight', '200');

ALTER TABLE `boards` ADD `section` TINYINT( 2 ) NOT NULL DEFAULT '0' AFTER `image` ;
ALTER TABLE `boards` ADD `maxreplies` INT( 5 ) NOT NULL DEFAULT '200' AFTER `maxage` ;

DROP TABLE IF EXISTS `loginattempts`;
CREATE TABLE `loginattempts` (
  `username` varchar(255) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

ALTER TABLE `posts` ADD `password` VARCHAR( 255 ) NOT NULL AFTER `imagetype` ;

ALTER TABLE `staff` CHANGE `boards` `boards` TEXT NULL