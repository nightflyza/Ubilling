CREATE TABLE IF NOT EXISTS `wcpedevices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelid` int(11) NOT NULL,
  `ip` varchar(45) DEFAULT NULL,
  `mac` varchar(45) DEFAULT NULL,
  `location` varchar(255) DEFAULT NULL,
  `bridge` tinyint(4) NOT NULL DEFAULT '0',
  `uplinkapid` int(11) DEFAULT NULL,
  `uplinkcpeid` int(11) DEFAULT NULL,
  `geo` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `wcpeusers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cpeid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;