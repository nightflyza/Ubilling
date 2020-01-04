CREATE TABLE IF NOT EXISTS `envyscripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modelid` int(11) NOT NULL,
  `data` TEXT DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `envydevices` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  `password` varchar(255) DEFAULT NULL,
  `enablepassword` varchar(255) DEFAULT NULL,
  `custom1` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `envydata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `date` datetime NOT NULL,
  `config` mediumtext,
  PRIMARY KEY (`id`),
  KEY `switchid` (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;