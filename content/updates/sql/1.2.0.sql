CREATE TABLE IF NOT EXISTS `stigma` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scope` varchar(64) DEFAULT NULL,
  `itemid` varchar(128) NOT NULL,
  `state` varchar(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;