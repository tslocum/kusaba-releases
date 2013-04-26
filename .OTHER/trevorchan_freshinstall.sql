-- 
-- Table structure for table `PREFIX_banlist`
-- 

CREATE TABLE `PREFIX_banlist` (
  `id` smallint(5) NOT NULL auto_increment,
  `type` tinyint(1) NOT NULL default '0',
  `allowread` tinyint(1) NOT NULL default '1',
  `ip` varchar(255) NOT NULL,
  `ipmd5` varchar(200) NOT NULL,
  `globalban` tinyint(1) NOT NULL default '0',
  `boards` varchar(255) NOT NULL,
  `by` varchar(75) NOT NULL,
  `at` int(20) NOT NULL,
  `until` int(20) NOT NULL,
  `reason` text NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_boards`
-- 

CREATE TABLE `PREFIX_boards` (
  `id` tinyint(5) NOT NULL auto_increment,
  `order` tinyint(5) NOT NULL default '0',
  `name` varchar(75) NOT NULL,
  `type` tinyint(1) NOT NULL default '0',
  `uploadtype` tinyint(1) NOT NULL default '0',
  `desc` varchar(75) NOT NULL,
  `image` varchar(255) NOT NULL,
  `section` tinyint(2) NOT NULL default '0',
  `maximagesize` int(20) NOT NULL default '1024000',
  `maxpages` int(20) NOT NULL default '10',
  `maxage` int(20) NOT NULL default '96',
  `maxreplies` int(5) NOT NULL default '200',
  `filetypes` varchar(255) NOT NULL default 'GIF|JPG|PNG',
  `messagelength` int(10) NOT NULL default '8192',
  `createdon` int(20) NOT NULL,
  `locked` tinyint(1) NOT NULL default '0',
  `includeheader` text NOT NULL,
  `redirecttothread` tinyint(1) NOT NULL default '0',
  `forcedanon` tinyint(1) NOT NULL default '0',
  `trial` tinyint(1) NOT NULL default '0',
  `popular` tinyint(1) NOT NULL default '0',
  `defaultstyle` varchar(50) NOT NULL default 'Futaba',
  `enablereporting` tinyint(1) NOT NULL default '1',
  `enablecaptcha` tinyint(1) NOT NULL default '0',
  `enablenofile` tinyint(1) NOT NULL default '0',
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_dnsbl`
-- 

CREATE TABLE `PREFIX_dnsbl` (
  `ip` varchar(30) NOT NULL,
  `date` int(15) NOT NULL,
  `proxy` int(1) NOT NULL,
  `list` int(1) NOT NULL,
  `reason` text NOT NULL,
  PRIMARY KEY  (`ip`)
) ENGINE=MyISAM ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_filetypes`
-- 

CREATE TABLE `PREFIX_filetypes` (
  `id` mediumint(5) NOT NULL auto_increment,
  `filetype` varchar(255) NOT NULL,
  `mime` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `image_w` int(7) NOT NULL default '0',
  `image_h` int(7) NOT NULL default '0',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_loginattempts`
-- 

CREATE TABLE `PREFIX_loginattempts` (
  `username` varchar(255) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_modlog`
-- 

CREATE TABLE `PREFIX_modlog` (
  `entry` text NOT NULL,
  `user` varchar(255) NOT NULL,
  `category` tinyint(2) NOT NULL default '0',
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_module_settings`
-- 

CREATE TABLE `PREFIX_module_settings` (
  `module` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `type` varchar(255) NOT NULL
) ENGINE=MyISAM ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_news`
-- 

CREATE TABLE `PREFIX_news` (
  `id` tinyint(5) NOT NULL auto_increment,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `postedat` int(20) NOT NULL,
  `postedby` varchar(75) NOT NULL,
  `postedemail` varchar(75) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_passcache`
-- 

CREATE TABLE `PREFIX_passcache` (
  `md5` varchar(100) NOT NULL,
  `name` varchar(75) NOT NULL,
  `tripcode` varchar(10) NOT NULL
) ENGINE=MyISAM ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_reports`
-- 

CREATE TABLE `PREFIX_reports` (
  `id` int(5) NOT NULL auto_increment,
  `cleared` tinyint(1) NOT NULL default '0',
  `board` varchar(255) NOT NULL,
  `postid` int(20) NOT NULL,
  `when` int(20) NOT NULL,
  `ip` varchar(75) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_sections`
-- 

CREATE TABLE `PREFIX_sections` (
  `id` int(5) NOT NULL auto_increment,
  `order` tinyint(3) NOT NULL default '0',
  `hidden` tinyint(1) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `abbreviation` char(4) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_staff`
-- 

CREATE TABLE `PREFIX_staff` (
  `id` tinyint(5) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `isadmin` tinyint(1) NOT NULL default '0',
  `boards` text,
  `addedon` int(20) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_watchedthreads`
-- 

CREATE TABLE `PREFIX_watchedthreads` (
  `id` int(20) NOT NULL auto_increment,
  `threadid` int(20) NOT NULL,
  `board` varchar(255) NOT NULL,
  `ip` char(15) NOT NULL,
  `lastsawreplyid` int(20) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

-- 
-- Table structure for table `PREFIX_wordfilter`
-- 

CREATE TABLE `PREFIX_wordfilter` (
  `id` tinyint(5) NOT NULL auto_increment,
  `word` varchar(75) NOT NULL,
  `replacedby` varchar(75) NOT NULL,
  `boards` text NOT NULL,
  `time` int(20) NOT NULL,
  `regex` tinyint(1) NOT NULL default '0',
  KEY `id` (`id`)
) ENGINE=MyISAM AUTO_INCREMENT=1 ;

INSERT INTO `PREFIX_filetypes` (`id`, `filetype`, `mime`, `image`, `image_w`, `image_h`) VALUES (1, 'swf', '', 'flash.png', 86, 86) ;