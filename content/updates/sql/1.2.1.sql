CREATE TABLE IF NOT EXISTS `wh_returns` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `outid` int(11) NOT NULL,
  `storageid` int(11) NOT NULL,
  `itemtypeid` int(11) NOT NULL,
  `count` DOUBLE NOT NULL,
  `price` DOUBLE NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `note` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `outid` (`outid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;