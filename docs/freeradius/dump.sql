CREATE TABLE `radius_custom_attributes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `netid` int(11) DEFAULT NULL,
  `login` varchar(50) NOT NULL DEFAULT '*',
  `Attribute` varchar(32) NOT NULL,
  `op` varchar(2) NOT NULL DEFAULT '=',
  `Value` varchar(253) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM;

CREATE TABLE IF NOT EXISTS `radius_acct` (
  `radacctid` bigint(21) NOT NULL AUTO_INCREMENT,
  `acctsessionid` varchar(64) NOT NULL DEFAULT '',
  `acctuniqueid` varchar(32) NOT NULL DEFAULT '',
  `username` varchar(64) NOT NULL DEFAULT '',
  `groupname` varchar(64) NOT NULL DEFAULT '',
  `realm` varchar(64) DEFAULT '',
  `nasipaddress` varchar(15) NOT NULL DEFAULT '',
  `nasportid` varchar(15) DEFAULT NULL,
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
) ENGINE=InnoDB;

CREATE OR REPLACE VIEW `radius_check` (`UserName`, `Attribute`, `op`, `Value`) AS
-- Get `Cleartext-Password` attributes' values:
SELECT `users`.`login`, 'Cleartext-Password', ':=', `users`.`Password` FROM `users`
JOIN `nethosts` ON `users`.`IP` = `nethosts`.`ip`
JOIN `networks` ON `nethosts`.`netid` = `networks`.`id` AND `networks`.`use_radius` = 1;

CREATE TABLE `radius_groupcheck` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `GroupName` varchar(64) NOT NULL DEFAULT '',
  `Attribute` varchar(32) NOT NULL DEFAULT '',
  `op` char(2) NOT NULL DEFAULT '==',
  `Value` varchar(253) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `GroupName` (`GroupName`(32))
);

CREATE OR REPLACE VIEW `radius_groupreply` (`netid`, `GroupName`, `Attribute`, `op`, `Value`) AS 
SELECT DISTINCT `nethosts`.`netid`, `users`.`Tariff`, `radius_custom_attributes`.`Attribute`, `radius_custom_attributes`.`op`, CONCAT_WS('/',`speeds`.`speedup` * 1000, `speeds`.`speeddown` * 1000) FROM `users`
JOIN `nethosts` ON `users`.`IP` = `nethosts`.`ip`
JOIN `speeds` ON `users`.`Tariff` = `speeds`.`tariff`
JOIN `radius_custom_attributes` ON `nethosts`.`netid` = `radius_custom_attributes`.`netid` AND `radius_custom_attributes`.`Value` = '{RATE}'
JOIN `networks` ON `nethosts`.`netid` = `networks`.`id` AND `networks`.`use_radius` = 1;

CREATE OR REPLACE VIEW `radius_reply` (`UserName`, `Attribute`, `op`, `Value`) AS 
SELECT `users`.`login`, `radius_custom_attributes`.`Attribute`, `radius_custom_attributes`.`op`, 
CASE
	-- From `users`:
	WHEN `radius_custom_attributes`.`Value` = '{LOGIN}'		THEN `users`.`login`
	WHEN `radius_custom_attributes`.`Value` = '{PASSWORD}'	THEN `users`.`Password`
	WHEN `radius_custom_attributes`.`Value` = '{TARIFF}'	THEN `users`.`Tariff`
	WHEN `radius_custom_attributes`.`Value` = '{CASH}'		THEN `users`.`Cash`
	WHEN `radius_custom_attributes`.`Value` = '{CREDIT}'	THEN `users`.`Credit`
	WHEN `radius_custom_attributes`.`Value` = '{DOWN}'		THEN `users`.`Down`
	WHEN `radius_custom_attributes`.`Value` = '{PASSIVE}'	THEN `users`.`Passive`
	WHEN `radius_custom_attributes`.`Value` = '{FREEMB}'	THEN `users`.`FreeMb`
	-- From `nethosts`:
	WHEN `radius_custom_attributes`.`Value` = '{IP}'	THEN `nethosts`.`ip`
	WHEN `radius_custom_attributes`.`Value` = '{MAC}'	THEN `nethosts`.`mac`
	-- From `networks`:
	WHEN `radius_custom_attributes`.`Value` = '{NETWORK_ID}'	THEN `networks`.`id`
	WHEN `radius_custom_attributes`.`Value` = '{NETWORK_START}'	THEN `networks`.`startip`
	WHEN `radius_custom_attributes`.`Value` = '{NETWORK_END}'	THEN `networks`.`endip`
	WHEN `radius_custom_attributes`.`Value` = '{NETWORK_IP}'	THEN SUBSTRING_INDEX(`networks`.`desc`, '/', 1)
	WHEN `radius_custom_attributes`.`Value` = '{NETWORK_CIDR}'	THEN SUBSTRING_INDEX(`networks`.`desc`, '/', -1)
	WHEN `radius_custom_attributes`.`Value` = '{NETWORK_DESC}'	THEN `networks`.`desc`
	-- Reassigned rate:
	WHEN `radius_custom_attributes`.`Value` = '{RATE}'	THEN 
		CASE
			WHEN `userspeeds`.`speed` != '0' THEN `userspeeds`.`speed` * 1000
			ELSE NULL
		END
	ELSE `radius_custom_attributes`.`Value`
END as `Value`
FROM `users`
JOIN `nethosts` ON `users`.`IP` = `nethosts`.`ip`
JOIN `nas` ON `nethosts`.`netid` = `nas`.`netid`
JOIN `networks` ON `nethosts`.`netid` = `networks`.`id` AND `networks`.`use_radius` = 1
JOIN `radius_custom_attributes` ON `nethosts`.`netid` = `radius_custom_attributes`.`netid` OR `users`.`login` = `radius_custom_attributes`.`login`
JOIN `userspeeds` ON `users`.`login` = `userspeeds`.`login`
-- Order by `users`.`login` and `radius_custom_attributes`.`Attribute`:
GROUP BY `users`.`login`, `radius_custom_attributes`.`Attribute`
-- Filter `NULL` attribute's value:
HAVING `Value` IS NOT NULL;

CREATE OR REPLACE VIEW `radius_usergroup` (`UserName`, `GroupName`, `priority`) AS 
-- Get groups' names for every user. (GroupName is first 10 characters of md5 hash of user's network id):
SELECT `users`.`login`, `users`.`Tariff`, 1 FROM `users`
JOIN `nethosts` ON `users`.`IP` = `nethosts`.`ip`
JOIN `networks` ON `nethosts`.`netid` = `networks`.`id` AND `networks`.`use_radius` = 1;

CREATE TABLE radius_postauth (
  `id` int(11) NOT NULL auto_increment,
  `username` varchar(64) NOT NULL default '',
  `pass` varchar(64) NOT NULL default '',
  `reply` varchar(32) NOT NULL default '',
  `authdate` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = INNODB;

CREATE OR REPLACE VIEW `radius_clients` (`nasname`, `shortname`, `type`, `ports`, `secret`, `server`, `community`, `description`) AS 
-- Generate FreeRADIUS clients list
SELECT DISTINCT `nas`.`nasip`, `nas`.`nasname`, 'other', NULL, LEFT(MD5(INET_ATON(`nas`.`nasip`)), 12), NULL, `switches`.`snmp`, `switches`.`desc` FROM `nas`
LEFT JOIN `switches` ON `nas`.`nasip` = `switches`.`ip`
JOIN `networks` ON `nas`.`netid` = `networks`.`id` AND `networks`.`use_radius` = 1;