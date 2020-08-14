CREATE TABLE IF NOT EXISTS `filestorage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `scope` VARCHAR(255) NOT NULL,
  `item` VARCHAR(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` VARCHAR(40) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `swcash` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switchid` int(11) NOT NULL,
  `placecontract` varchar(200) DEFAULT NULL,
  `placeprice` double NOT NULL DEFAULT '0',
  `powercontract` varchar(200) DEFAULT NULL,
  `powerprice` double NOT NULL DEFAULT '0',
  `transportcontract` varchar(200) DEFAULT NULL,
  `transportprice` double NOT NULL DEFAULT '0',
  `switchprice` double NOT NULL DEFAULT '0',
  `switchdate` date DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `switchid` (`switchid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;