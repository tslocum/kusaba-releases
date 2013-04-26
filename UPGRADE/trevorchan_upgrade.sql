INSERT INTO `config` ( `key` , `value` ) VALUES ('numrepliesdisplayed', '3'),('numrepliesdisplayedsticky', '1'),('numthreadsdisplayed', '10');

ALTER TABLE `modlog` ADD `category` TINYINT(2) NOT NULL AFTER `user` ;

ALTER TABLE `posts` ADD `imagemd5` TEXT NULL AFTER `imagetype` ;
