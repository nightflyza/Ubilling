CREATE TABLE IF NOT EXISTS `ddt_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `tariffname` varchar(40) NOT NULL,
  `period` varchar(10) NOT NULL,
  `startnow` tinyint(4) NOT NULL,
  `duration` int(11) NOT NULL,
  `chargefee` tinyint(4) NOT NULL,
  `chargeuntilday` int(11) DEFAULT NULL,
  `tariffmove` varchar(40) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `ddt_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(32) NOT NULL,
  `active` tinyint(4) NOT NULL,
  `startdate` datetime NOT NULL,
  `curtariff` varchar(40) NOT NULL,
  `enddate` date NOT NULL,
  `nexttariff` varchar(40) NOT NULL,
  `dwiid` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `switch_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `groupname` varchar(255) NOT NULL,
  `groupdescr` varchar(500) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY (`groupname`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `switch_groups_relations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `switch_id` int(11) NOT NULL,
  `sw_group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`switch_id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;