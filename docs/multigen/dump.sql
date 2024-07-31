CREATE TABLE IF NOT EXISTS `mlg_acct` (
  `radacctid` bigint(21) NOT NULL AUTO_INCREMENT,
  `acctsessionid` varchar(64) NOT NULL DEFAULT '',
  `acctuniqueid` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `realm` varchar(64) DEFAULT '',
  `nasipaddress` varchar(15) NOT NULL DEFAULT '',
  `nasportid` varchar(120) DEFAULT NULL,
  `nasporttype` varchar(32) DEFAULT NULL,
  `acctstarttime` datetime DEFAULT NULL,
  `acctstoptime` datetime DEFAULT NULL,
  `acctsessiontime` int(12) DEFAULT NULL,
  `acctauthentic` varchar(32) DEFAULT NULL,
  `connectinfo_start` varchar(50) DEFAULT NULL,
  `connectinfo_stop` varchar(50) DEFAULT NULL,
  `acctinputoctets` bigint(20) DEFAULT NULL,
  `acctoutputoctets` bigint(20) DEFAULT NULL,
  `calledstationid` varchar(50) NOT NULL DEFAULT '',
  `callingstationid` varchar(50) NOT NULL DEFAULT '',
  `acctterminatecause` varchar(32) NOT NULL DEFAULT '',
  `servicetype` varchar(32) DEFAULT NULL,
  `framedprotocol` varchar(32) DEFAULT NULL,
  `framedipaddress` varchar(15) NOT NULL DEFAULT '',
  `acctstartdelay` int(12) DEFAULT NULL,
  `acctstopdelay` int(12) DEFAULT NULL,
  `xascendsessionsvrkey` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`radacctid`),
  UNIQUE KEY `acctuniqueid` (`acctuniqueid`),
  KEY `username` (`username`),
  KEY `framedipaddress` (`framedipaddress`),
  KEY `acctsessionid` (`acctsessionid`),
  KEY `acctsessiontime` (`acctsessiontime`),
  KEY `acctstarttime` (`acctstarttime`),
  KEY `acctstoptime` (`acctstoptime`),
  KEY `nasipaddress` (`nasipaddress`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

CREATE TABLE  IF NOT EXISTS `mlg_postauth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL default '',
  `pass` varchar(64) NOT NULL default '',
  `reply` varchar(32) NOT NULL default '',
  `authdate` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8

CREATE TABLE IF NOT EXISTS `mlg_nascustom` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ip` varchar(32) NOT NULL,
  `name` varchar(64) NOT NULL,
  `secret` varchar(64) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

-- old-view without custom NASes
-- CREATE OR REPLACE VIEW `mlg_clients` (`nasname`, `shortname`, `type`, `ports`, `secret`, `server`) AS 
-- SELECT DISTINCT `nasip` AS `nasname`,`nasname` AS `shortname`,'other' AS `type`,NULL AS `ports`,left(md5(inet_aton(`nasip`)),12) AS `secret`,NULL AS `server` from `nas` GROUP BY `nasip`;

CREATE OR REPLACE VIEW `mlg_clients` (`nasname`, `shortname`, `type`, `ports`, `secret`, `server`) AS
SELECT DISTINCT 
  COALESCE(mlg_nascustom.ip, nas.`nasip`, NULL) AS `nasname`,
  COALESCE(mlg_nascustom.name, nas.`nasname`, NULL) AS `shortname`,
  'other' AS `type`,
  NULL AS `ports`,
  COALESCE(mlg_nascustom.secret, left(md5(inet_aton(nas.`nasip`)),12), NULL) AS `secret`,
  NULL AS `server` 
from `nas` 
left join mlg_nascustom on (nas.nasip = mlg_nascustom.ip) 
GROUP BY nasname
UNION SELECT DISTINCT 
  `ip` AS `nasname`, 
  `name` AS `shortname`, 
  'other' AS `type`, 
  NULL AS `ports`, 
  `secret` as `secret`, 
  NULL as `server` 
from `mlg_nascustom` 
LEFT JOIN nas ON (mlg_nascustom.ip = nas.nasip) 
where nasname is null
GROUP BY `ip`;

CREATE TABLE IF NOT EXISTS `mlg_check` (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '==',
  value varchar(253) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY username (username(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `mlg_reply` (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  attribute varchar(64) NOT NULL default '',
  op char(2) NOT NULL DEFAULT '=',
  value varchar(253) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY username (username(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS mlg_groupcheck (
  id int(11) unsigned NOT NULL auto_increment,
  groupname varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '==',
  value varchar(253)  NOT NULL default '',
  PRIMARY KEY  (id),
  KEY groupname (groupname(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS mlg_groupreply (
  id int(11) unsigned NOT NULL auto_increment,
  groupname varchar(64) NOT NULL default '',
  attribute varchar(64)  NOT NULL default '',
  op char(2) NOT NULL DEFAULT '=',
  value varchar(253)  NOT NULL default '',
  PRIMARY KEY  (id),
  KEY groupname (groupname(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS mlg_usergroup (
  username varchar(64) NOT NULL default '',
  groupname varchar(64) NOT NULL default '',
  priority int(11) NOT NULL default '1',
  KEY username (username(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `mlg_nasattributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nasid` int(11) NOT NULL,
  `scenario` varchar(30) NOT NULL,
  `attribute` varchar(255) NOT NULL,
  `operator` varchar(10) NOT NULL,
  `content` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nasid` (`nasid`,`scenario`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `mlg_nasoptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nasid` int(11) NOT NULL,
  `usernametype` varchar(30) NOT NULL,
  `service` varchar(255) NOT NULL,
  `onlyactive` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `nasid` (`nasid`,`usernametype`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mlg_services` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `nasid` int(11) NOT NULL,
  `pod` TEXT default NULL,
  `coaconnect` TEXT default NULL,
  `coadisconnect` TEXT default NULL,
  PRIMARY KEY (`id`),
  KEY `nasid` (`nasid`)
) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `mlg_userstates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(64) NOT NULL,
  `state` int(11) NOT NULL,
  PRIMARY KEY (`id`)
  ) ENGINE=MyISAM  DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `mlg_nasattributes` ADD `modifier` VARCHAR(15) NOT NULL DEFAULT 'all' AFTER `scenario`; 

ALTER TABLE `mlg_nasoptions` ADD `port` INT(11) NOT NULL DEFAULT '3799' AFTER `onlyactive`; 

drop table `mlg_groupreply`;

CREATE TABLE IF NOT EXISTS `mlg_groupreply` (
  id int(11) unsigned NOT NULL auto_increment,
  username varchar(64) NOT NULL default '',
  attribute varchar(64) NOT NULL default '',
  op char(2) NOT NULL DEFAULT '=',
  value varchar(253) NOT NULL default '',
  PRIMARY KEY  (id),
  KEY username (username(32))
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `mlg_traffic` (
  `login` varchar(100) NOT NULL,
  `down` bigint(30) DEFAULT NULL,
  `up` bigint(30) DEFAULT NULL,
  `act` int(11) DEFAULT NULL,
  PRIMARY KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;