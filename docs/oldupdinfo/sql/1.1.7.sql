CREATE TABLE IF NOT EXISTS `ins_homereq` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `login` varchar(64) DEFAULT NULL,
  `address` varchar(200) NOT NULL,
  `realname` varchar(200) NOT NULL,
  `mobile` varchar(64) NOT NULL,
  `email` varchar(64) NOT NULL,
  `state` tinyint(1) NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `youtv_subscribers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `active` tinyint(1) DEFAULT NULL,
  `subscriberid` int(11) NOT NULL,
  `login` varchar(64) NOT NULL,
  `maintariff` int(11) DEFAULT NULL,
  `addtariffs` varchar(255) DEFAULT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `youtv_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serviceid` int(11) NOT NULL,
  `main` tinyint(1) NOT NULL,
  `name` varchar(64) NOT NULL,
  `chans` varchar(42) DEFAULT NULL,
  `fee` double NOT NULL,
PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;