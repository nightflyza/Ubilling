CREATE TABLE IF NOT EXISTS `whiteboard` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `categoryid` int(11) NOT NULL,
  `admin` varchar(255) NOT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `createdate` datetime NOT NULL,
  `donedate` datetime DEFAULT NULL,
  `priority` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `text` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `taskmanlogs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `taskid` int(11) NOT NULL,
  `date` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `admin` varchar(45) DEFAULT NULL,
  `ip` varchar(64) DEFAULT NULL,
  `event` varchar(255) NOT NULL,
  `logs` text,
  PRIMARY KEY (`id`),
  KEY `taskid` (`taskid`) USING BTREE,
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `om_tariffs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariffid` int(11) NOT NULL,
  `tariffname` varchar(255) NOT NULL,
  `type` varchar(64) NOT NULL,
  `fee` DOUBLE DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `om_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  `customerid` bigint(20) NOT NULL,
  `basetariffid` int(11) DEFAULT NULL,
  `bundletariffs` varchar(255) DEFAULT NULL,
  `active` int(11) DEFAULT NULL,
  `actdate` datetime NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `om_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `customerid` bigint(20) NOT NULL,
  `tariffid` int(11) DEFAULT NULL,
  `action` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `om_suspend` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;