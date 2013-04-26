-- --------------------------------------------------------

-- 
-- Table structure for table `banlist`
-- 

DROP TABLE IF EXISTS `banlist`;
CREATE TABLE `banlist` (
  `id` tinyint(5) NOT NULL auto_increment,
  `ip` varchar(255) NOT NULL,
  `globalban` tinyint(1) NOT NULL default '0',
  `boards` varchar(255) NOT NULL,
  `by` varchar(75) NOT NULL,
  `at` int(20) NOT NULL,
  `until` int(20) NOT NULL,
  `reason` text NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `boards`
-- 

DROP TABLE IF EXISTS `boards`;
CREATE TABLE `boards` (
  `id` tinyint(5) NOT NULL auto_increment,
  `order` tinyint(5) NOT NULL default '0',
  `name` varchar(75) NOT NULL,
  `desc` varchar(75) NOT NULL,
  `image` varchar(255) NOT NULL,
  `maximagesize` int(20) NOT NULL default '1024000',
  `maxpages` int(20) NOT NULL default '10',
  `maxage` int(20) NOT NULL default '96',
  `filetypes` varchar(255) NOT NULL default 'GIF|JPG|PNG',
  `messagelength` int(10) NOT NULL default '8192',
  `createdon` int(20) NOT NULL,
  `locked` tinyint(1) NOT NULL default '0',
  `includeheader` text NOT NULL,
  `redirecttothread` tinyint(1) NOT NULL default '0',
  `forcedanon` tinyint(1) NOT NULL default '0',
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `config`
-- 

DROP TABLE IF EXISTS `config`;
CREATE TABLE `config` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `iplist`
-- 

DROP TABLE IF EXISTS `iplist`;
CREATE TABLE `iplist` (
  `ip` varchar(200) NOT NULL,
  `lastpost` int(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `modlog`
-- 

DROP TABLE IF EXISTS `modlog`;
CREATE TABLE `modlog` (
  `entry` text NOT NULL,
  `user` varchar(255) NOT NULL,
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `news`
-- 

DROP TABLE IF EXISTS `news`;
CREATE TABLE `news` (
  `id` tinyint(5) NOT NULL auto_increment,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `postedat` int(20) NOT NULL,
  `postedby` varchar(75) NOT NULL,
  `postedemail` varchar(75) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `posts`
-- 

DROP TABLE IF EXISTS `posts`;
CREATE TABLE `posts` (
  `boardid` tinyint(5) NOT NULL,
  `id` int(10) NOT NULL,
  `threadid` int(10) NOT NULL default '0',
  `user` varchar(255) NOT NULL,
  `tripcode` varchar(10) NOT NULL,
  `email` varchar(255) NOT NULL,
  `subject` varchar(255) NOT NULL,
  `message` text NOT NULL,
  `image` varchar(20) NOT NULL,
  `imagetype` varchar(5) NOT NULL,
  `postedat` int(20) NOT NULL,
  `lastbumped` int(20) NOT NULL default '0',
  `ip` varchar(75) NOT NULL,
  `stickied` tinyint(1) NOT NULL default '0',
  `locked` tinyint(1) NOT NULL default '0',
  `posterauthority` tinyint(1) NOT NULL default '0',
  `IS_DELETED` tinyint(1) NOT NULL default '0',
  KEY `id` (`id`),
  KEY `threadid` (`threadid`),
  KEY `boardid` (`boardid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff`
-- 

DROP TABLE IF EXISTS `staff`;
CREATE TABLE `staff` (
  `id` tinyint(5) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `isadmin` tinyint(1) NOT NULL default '0',
  `boards` text,
  `addedon` int(20) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

-- 
-- Table structure for table `wordfilter`
-- 

DROP TABLE IF EXISTS `wordfilter`;
CREATE TABLE `wordfilter` (
  `id` tinyint(5) NOT NULL auto_increment,
  `word` varchar(75) NOT NULL,
  `replacedby` varchar(75) NOT NULL,
  `boards` text NOT NULL,
  `time` int(20) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;