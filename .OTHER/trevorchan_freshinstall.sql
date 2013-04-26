-- --------------------------------------------------------

-- 
-- Table structure for table `banlist`
-- 

CREATE TABLE `banlist` (
  `id` tinyint(5) NOT NULL auto_increment,
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
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `boards`
-- 

CREATE TABLE `boards` (
  `id` tinyint(5) NOT NULL auto_increment,
  `order` tinyint(5) NOT NULL default '0',
  `name` varchar(75) NOT NULL,
  `type` tinyint(1) NOT NULL default '0',
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
  `enablereporting` tinyint(1) NOT NULL default '1',
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `config`
-- 

CREATE TABLE `config` (
  `key` varchar(255) NOT NULL,
  `value` text NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `iplist`
-- 

CREATE TABLE `iplist` (
  `ip` varchar(200) NOT NULL,
  `ipmd5` varchar(200) NOT NULL,
  `lastpost` int(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `loginattempts`
-- 

CREATE TABLE `loginattempts` (
  `username` varchar(255) NOT NULL,
  `ip` varchar(20) NOT NULL,
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `modlog`
-- 

CREATE TABLE `modlog` (
  `entry` text NOT NULL,
  `user` varchar(255) NOT NULL,
  `category` tinyint(2) NOT NULL default '0',
  `timestamp` int(20) NOT NULL
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `news`
-- 

CREATE TABLE `news` (
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
-- Table structure for table `posts`
-- 

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
  KEY `id` (`id`),
  KEY `threadid` (`threadid`),
  KEY `boardid` (`boardid`),
  KEY `lastbumped` (`lastbumped`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `reports`
-- 

CREATE TABLE `reports` (
  `id` int(5) NOT NULL auto_increment,
  `cleared` tinyint(1) NOT NULL default '0',
  `boardid` int(5) NOT NULL,
  `postid` int(20) NOT NULL,
  `when` int(20) NOT NULL,
  `ip` varchar(15) NOT NULL,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `sections`
-- 

CREATE TABLE `sections` (
  `id` int(5) NOT NULL auto_increment,
  `order` tinyint(3) NOT NULL default '0',
  `name` varchar(255) NOT NULL,
  `abbreviation` char(4) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `staff`
-- 

CREATE TABLE `staff` (
  `id` tinyint(5) NOT NULL auto_increment,
  `username` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `isadmin` tinyint(1) NOT NULL default '0',
  `boards` text,
  `addedon` int(20) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;

-- --------------------------------------------------------

-- 
-- Table structure for table `wordfilter`
-- 

CREATE TABLE `wordfilter` (
  `id` tinyint(5) NOT NULL auto_increment,
  `word` varchar(75) NOT NULL,
  `replacedby` varchar(75) NOT NULL,
  `boards` text NOT NULL,
  `time` int(20) NOT NULL,
  KEY `id` (`id`)
) ENGINE=MyISAM;


-- 
-- Dumping data for table `config`
-- 

INSERT INTO `config` (`key`, `value`) VALUES ('imagesinnewwindow', '1'),
('postboxnotice', '<ul><li>Supported file types are: <!tc_filetypes /></li><li>Maximum file size allowed is <!tc_maximagekb /> KB.</li><li>Images greater than <!tc_maxthumbwidth />x<!tc_maxthumbheight /> pixels will be thumbnailed.</li><li>Currently <!tc_uniqueposts /> unique user posts.</li></ul>'),
('modlogmaxdays', '7'),
('maxthumbwidth', '200'),
('maxthumbheight', '200'),
('numrepliesdisplayed', '3'),
('numrepliesdisplayedsticky', '1'),
('numthreadsdisplayed', '10'),
('makeurlsclickable', '1'),
('ircinfo', '<a href="irc://irc.fukt.us/trevorchan" title="#trevorchan IRC">#trevorchan @ irc.fukt.us</a>');
