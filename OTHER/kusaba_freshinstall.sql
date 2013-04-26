-- 
-- Table structure for table `banlist`
-- 

CREATE TABLE `PREFIX_banlist` (
  `id` smallint(5) NOT NULL auto_increment,
  `type` tinyint(1) NOT NULL default '0',
  `allowread` tinyint(1) NOT NULL default '1',
  `ip` varchar(50) NOT NULL,
  `ipmd5` char(32) NOT NULL,
  `globalban` tinyint(1) NOT NULL default '0',
  `boards` varchar(255) NOT NULL,
  `by` varchar(75) NOT NULL,
  `at` int(20) NOT NULL,
  `until` int(20) NOT NULL,
  `reason` text NOT NULL,
  `appealat` int(20) NOT NULL default '0',
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `bannedhashes`
-- 

CREATE TABLE `PREFIX_bannedhashes` (
  `id` int(10) NOT NULL auto_increment,
  `md5` varchar(255) NOT NULL,
  `bantime` int(10) NOT NULL default '0',
  `description` text NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `blotter`
-- 

CREATE TABLE `PREFIX_blotter` (
  `id` int(5) NOT NULL auto_increment,
  `important` tinyint(1) NOT NULL,
  `at` int(20) NOT NULL,
  `message` text NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `boards`
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
  `maxage` int(20) NOT NULL default '0',
  `markpage` tinyint(4) NOT NULL default '9',
  `maxreplies` int(5) NOT NULL default '200',
  `messagelength` int(10) NOT NULL default '8192',
  `createdon` int(20) NOT NULL,
  `locked` tinyint(1) NOT NULL default '0',
  `includeheader` text character set utf8 collate utf8_unicode_ci NOT NULL,
  `redirecttothread` tinyint(1) NOT NULL default '0',
  `anonymous` varchar(255) NOT NULL default 'Anonymous',
  `forcedanon` tinyint(1) NOT NULL default '0',
  `trial` tinyint(1) NOT NULL default '0',
  `popular` tinyint(1) NOT NULL default '0',
  `defaultstyle` varchar(50) NOT NULL,
  `locale` varchar(30) NOT NULL,
  `showid` tinyint(1) NOT NULL default '0',
  `enablereporting` tinyint(1) NOT NULL default '1',
  `enablecaptcha` tinyint(1) NOT NULL default '0',
  `enablenofile` tinyint(1) NOT NULL default '0',
  `enablearchiving` tinyint(1) NOT NULL default '0',
  `enablecatalog` tinyint(1) NOT NULL default '1',
  `loadbalanceurl` varchar(255) NOT NULL,
  `loadbalancepassword` varchar(255) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `board_filetypes`
-- 

CREATE TABLE `PREFIX_board_filetypes` (
  `boardid` tinyint(5) NOT NULL default '0',
  `typeid` mediumint(5) NOT NULL default '0'
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `events`
-- 

CREATE TABLE `PREFIX_events` (
  `name` varchar(255) NOT NULL,
  `at` int(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `filetypes`
-- 

CREATE TABLE `PREFIX_filetypes` (
  `id` mediumint(5) NOT NULL auto_increment,
  `filetype` varchar(255) NOT NULL,
  `mime` varchar(255) NOT NULL,
  `image` varchar(255) NOT NULL,
  `image_w` int(7) NOT NULL default '0',
  `image_h` int(7) NOT NULL default '0',
  `force_thumb` int(1) NOT NULL default '1',
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `loginattempts`
-- 

CREATE TABLE `PREFIX_loginattempts` (
  `username` varchar(255) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `modlog`
-- 

CREATE TABLE `PREFIX_modlog` (
  `entry` text NOT NULL,
  `user` varchar(255) NOT NULL,
  `category` tinyint(2) NOT NULL default '0',
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `module_settings`
-- 

CREATE TABLE `PREFIX_module_settings` (
  `module` varchar(255) NOT NULL,
  `key` varchar(255) NOT NULL,
  `value` text character set utf8 collate utf8_unicode_ci NOT NULL,
  `type` varchar(255) NOT NULL default 'string'
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `news`
-- 

CREATE TABLE `PREFIX_news` (
  `id` tinyint(5) NOT NULL auto_increment,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `postedat` int(20) NOT NULL,
  `postedby` varchar(75) NOT NULL,
  `postedemail` varchar(75) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `passcache`
-- 

CREATE TABLE `PREFIX_passcache` (
  `md5` char(32) NOT NULL,
  `name` varchar(75) NOT NULL,
  `tripcode` varchar(30) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `reports`
-- 

CREATE TABLE `PREFIX_reports` (
  `id` int(5) NOT NULL auto_increment,
  `cleared` tinyint(1) NOT NULL default '0',
  `board` varchar(255) NOT NULL,
  `postid` int(20) NOT NULL,
  `when` int(20) NOT NULL,
  `ip` varchar(75) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `sections`
-- 

CREATE TABLE `PREFIX_sections` (
  `id` int(5) NOT NULL auto_increment,
  `order` tinyint(3) NOT NULL default '0',
  `hidden` tinyint(1) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `abbreviation` varchar(10) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff`
-- 

CREATE TABLE `PREFIX_staff` (
  `id` tinyint(5) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL default '0',
  `boards` text,
  `addedon` int(20) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `watchedthreads`
-- 

CREATE TABLE `PREFIX_watchedthreads` (
  `id` int(20) NOT NULL auto_increment,
  `threadid` int(20) NOT NULL,
  `board` varchar(255) NOT NULL,
  `ip` char(15) NOT NULL,
  `lastsawreplyid` int(20) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `wordfilter`
-- 

CREATE TABLE `PREFIX_wordfilter` (
  `id` int(5) NOT NULL auto_increment,
  `word` varchar(75) NOT NULL,
  `replacedby` varchar(75) NOT NULL,
  `boards` text NOT NULL,
  `time` int(20) NOT NULL,
  `regex` tinyint(1) NOT NULL default '0',
  KEY `id` (`id`)
) ENGINE=MyISAM ;

INSERT INTO `PREFIX_filetypes` (filetype, force_thumb) VALUES ('jpg', 0), ('gif', 0), ('png', 0) ;
INSERT INTO `PREFIX_events` (`name`, `at`) VALUES ('pingback', 0);