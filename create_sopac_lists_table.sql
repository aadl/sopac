-- --------------------------------------------------------

--
-- Table structure for table `sopac_lists`
--

CREATE TABLE IF NOT EXISTS `sopac_lists` (
  `list_id` int(10) unsigned NOT NULL auto_increment,
  `uid` int(10) unsigned NOT NULL,
  `title` varchar(128) default NULL,
  `description` varchar(256) default NULL,
  `public` tinyint(3) unsigned NOT NULL,
  PRIMARY KEY  (`list_id`),
  KEY `uid` (`uid`)
) ENGINE=MyISAM