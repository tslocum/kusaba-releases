INSERT INTO `config` ( `key` , `value` ) VALUES ('makeurlsclickable', '1'),('ircinfo', '<a href="irc://irc.fukt.us/trevorchan" title="#trevorchan IRC">#trevorchan @ irc.fukt.us</a>');

ALTER TABLE `banlist` ADD `type` tinyint(1) NOT NULL default '0' AFTER `id` ;

ALTER TABLE `boards` ADD `type` tinyint(1) NOT NULL default '0' AFTER `name` ;

CREATE TABLE `sections` (
  `id` int(5) NOT NULL auto_increment,
  `order` tinyint(3) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;
