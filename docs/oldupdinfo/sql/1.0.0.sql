ALTER TABLE `visor_users` ADD `primarylogin` VARCHAR(255) NULL AFTER `chargecams`, ADD INDEX (`primarylogin`);


CREATE TABLE IF NOT EXISTS `fdbarchive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `devid` int(11) DEFAULT NULL,
  `devip` varchar(64) DEFAULT NULL,
  `data` longtext,
  `pon` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `devid` (`devid`,`devip`),
  KEY `pon` (`pon`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `fdbarchive` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `devid` int(11) DEFAULT NULL,
  `devip` varchar(64) DEFAULT NULL,
  `data` longtext,
  `pon` tinyint(4) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `devid` (`devid`,`devip`),
  KEY `pon` (`pon`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `askcalls` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `filename` varchar(250) DEFAULT NULL,
  `login` varchar(250) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;