CREATE TABLE IF NOT EXISTS `switchuplinks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `media` varchar(10) DEFAULT NULL,
  `port` int (11) DEFAULT NULL,
  `speed` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `switchuplinks` ADD INDEX(`switchid`); 