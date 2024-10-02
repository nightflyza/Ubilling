ALTER TABLE `wh_out` ADD `netw` tinyint(4) NULL DEFAULT 0 AFTER `notes`;

CREATE TABLE IF NOT EXISTS`gr_strat` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL,
  `useassigns` tinyint(4) NOT NULL DEFAULT '0',
  `primaryagentid` int(11) DEFAULT NULL,
  `maxamount` int(11) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `gr_spec` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `stratid` int(11) NOT NULL,
  `agentid` int(11) NOT NULL,
  `type` varchar(32) NOT NULL,
  `value` int(11) DEFAULT NULL,
  `customdata` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `gr_strat` ADD `tariff` VARCHAR(64) NULL AFTER `maxamount`; 