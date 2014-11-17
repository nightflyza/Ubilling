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

CREATE TABLE  IF NOT EXISTS `radius_postauth` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(64) NOT NULL default '',
  `pass` varchar(64) NOT NULL default '',
  `reply` varchar(32) NOT NULL default '',
  `authdate` timestamp NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE = INNODB;

CREATE TABLE IF NOT EXISTS `radius_attributes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `scenario` enum('check','reply') DEFAULT NULL,
  `login` varchar(50) DEFAULT NULL,
  `netid` int(11) unsigned DEFAULT NULL,
  `nasip` int(15) unsigned DEFAULT NULL,
  `Attribute` varchar(32) NOT NULL,
  `op` varchar(2) NOT NULL,
  `Value` varchar(253) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `radius_username_bind` (
  `id` int(10) unsigned NOT NULL  AUTO_INCREMENT,
  `netid` int(10) unsigned DEFAULT NULL,
  `value` enum('IP','MAC') DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

CREATE OR REPLACE VIEW `radius_clients` (`nasname`, `shortname`, `type`, `ports`, `secret`, `server`, `community`, `description`) AS
SELECT DISTINCT `nas`.`nasip`, `nas`.`nasname`, 'other', NULL, LEFT(MD5(INET_ATON(`nas`.`nasip`)), 12), NULL, `switches`.`snmp`, `switches`.`desc` FROM `nas`
     JOIN `networks` ON `networks`.`id` = `nas`.`netid`
LEFT JOIN `switches` ON `switches`.`ip` = `nas`.`nasip`
WHERE `networks`.`use_radius` = TRUE;

CREATE OR REPLACE VIEW `radius_check` (`UserName`, `Attribute`, `op`, `Value`) AS
SELECT 
  CASE `radius_username_bind`.`value`
    WHEN 'IP'   THEN `nethosts`.`ip`
    WHEN 'MAC'  THEN `nethosts`.`mac`
  ELSE `users`.`login`
END, `radius_attributes`.`Attribute`, `radius_attributes`.`op`, 
-- Обработка макросов значений
CASE 
	-- Общая информация о пользователе
	WHEN `radius_attributes`.`Value` LIKE '%{user[login]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[login]}',    `users`.`login`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Password]}%' THEN REPLACE(`radius_attributes`.`Value`, '{user[Password]}', `users`.`Password`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Tariff]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{user[Tariff]}',   `users`.`Tariff`)
  -- Информация IP/MAC
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{nethost[ip]}',    `nethosts`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[mac]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{nethost[mac]}',   `nethosts`.`mac`)
  -- Информация о сети пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{network[id]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[id]}',    `networks`.`id`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[ip]}',    SUBSTRING_INDEX(`networks`.`desc`, '/',  1))
	WHEN `radius_attributes`.`Value` LIKE '%{network[start]}%' THEN REPLACE(`radius_attributes`.`Value`, '{network[start]}', `networks`.`startip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[end]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{network[end]}',   `networks`.`endip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[desc]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[desc]}',  `networks`.`desc`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[cidr]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[cidr]}',  SUBSTRING_INDEX(`networks`.`desc`, '/', -1))
  -- Информации о принадлежности к коммутатору
	WHEN `radius_attributes`.`Value` LIKE '%{switch[ip]}%'     THEN REPLACE(`radius_attributes`.`Value`, '{switch[ip]}',     `switches`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{switch[port]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{switch[port]}',   `switchportassign`.`port`)
  -- Информация о скорости пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{speed[up]}%'      THEN REPLACE(`radius_attributes`.`Value`, '{speed[up]}',      `speeds`.`speedup`)
	WHEN `radius_attributes`.`Value` LIKE '%{speed[down]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{speed[down]}',    `speeds`.`speeddown`)
  -- Состояние пользователя ( OFF-LINE, ON-LINE, PASSIVE или DOWN )
	WHEN `radius_attributes`.`Value` LIKE '%{user[state]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[state]}',   (
    CASE
      WHEN `users`.`Down`     THEN 'DOWN'
      WHEN `users`.`Passive`  THEN 'PASSIVE'
      WHEN `users`.`Cash` < -`users`.`Credit`
                              THEN 'OFF-LINE'
      ELSE 'ON-LINE'
    END
  ))
  -- Или возвращаем значание
	ELSE `radius_attributes`.`Value`
END as `Value`
-- Конец обработки макросов
 FROM `users`
 -- ...для получения IP/MAC
      JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
 -- ...для получения информации о сети
      JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
      JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
 -- ...для сбора всех атрибутов для пользователя или всех пользователях
      JOIN `radius_attributes` ON  `radius_attributes`.`login` = `users`.`login`
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`netid` = `networks`.`id` )
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`nasip` = INET_ATON(`nas`.`nasip`))
 -- ...
 LEFT JOIN `radius_username_bind` ON `radius_username_bind`.`netid` = `nethosts`.`netid`
 -- ...для получения информации о коммутаторе, к которому подключен пользователь
 LEFT JOIN `switchportassign` ON `switchportassign`.`login` = `users`.`login`
 LEFT JOIN `switches` ON `switches`.`id` = `switchportassign`.`switchid`
 -- ...для получения информации о скорости по тарифному плану
 LEFT JOIN `speeds`   ON `speeds`.`tariff` = `users`.`Tariff`
WHERE `radius_attributes`.`scenario` = 'check' AND `networks`.`use_radius` = TRUE
ORDER BY `users`.`login`;

CREATE OR REPLACE VIEW `radius_reply` (`UserName`, `Attribute`, `op`, `Value`) AS
SELECT 
  CASE `radius_username_bind`.`value`
    WHEN 'IP'   THEN `nethosts`.`ip`
    WHEN 'MAC'  THEN `nethosts`.`mac`
  ELSE `users`.`login`
END, `radius_attributes`.`Attribute`, `radius_attributes`.`op`, 
-- Обработка макросов значений
CASE 
	-- Общая информация о пользователе
	WHEN `radius_attributes`.`Value` LIKE '%{user[login]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[login]}',    `users`.`login`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Password]}%' THEN REPLACE(`radius_attributes`.`Value`, '{user[Password]}', `users`.`Password`)
	WHEN `radius_attributes`.`Value` LIKE '%{user[Tariff]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{user[Tariff]}',   `users`.`Tariff`)
  -- Информация IP/MAC
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{nethost[ip]}',    `nethosts`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{nethost[mac]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{nethost[mac]}',   `nethosts`.`mac`)
  -- Информация о сети пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{network[id]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[id]}',    `networks`.`id`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[ip]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{network[ip]}',    SUBSTRING_INDEX(`networks`.`desc`, '/',  1))
	WHEN `radius_attributes`.`Value` LIKE '%{network[start]}%' THEN REPLACE(`radius_attributes`.`Value`, '{network[start]}', `networks`.`startip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[end]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{network[end]}',   `networks`.`endip`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[desc]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[desc]}',  `networks`.`desc`)
	WHEN `radius_attributes`.`Value` LIKE '%{network[cidr]}%'  THEN REPLACE(`radius_attributes`.`Value`, '{network[cidr]}',  SUBSTRING_INDEX(`networks`.`desc`, '/', -1))
  -- Информации о принадлежности к коммутатору
	WHEN `radius_attributes`.`Value` LIKE '%{switch[ip]}%'     THEN REPLACE(`radius_attributes`.`Value`, '{switch[ip]}',     `switches`.`ip`)
	WHEN `radius_attributes`.`Value` LIKE '%{switch[port]}%'   THEN REPLACE(`radius_attributes`.`Value`, '{switch[port]}',   `switchportassign`.`port`)
  -- Информация о скорости пользователя
	WHEN `radius_attributes`.`Value` LIKE '%{speed[up]}%'      THEN REPLACE(`radius_attributes`.`Value`, '{speed[up]}',      `speeds`.`speedup`)
	WHEN `radius_attributes`.`Value` LIKE '%{speed[down]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{speed[down]}',    `speeds`.`speeddown`)
  -- Состояние пользователя ( OFF-LINE, ON-LINE, PASSIVE или DOWN )
	WHEN `radius_attributes`.`Value` LIKE '%{user[state]}%'    THEN REPLACE(`radius_attributes`.`Value`, '{user[state]}',    (
    CASE
      WHEN `users`.`Down`     THEN 'DOWN'
      WHEN `users`.`Passive`  THEN 'PASSIVE'
      WHEN `users`.`Cash` < -`users`.`Credit`
                              THEN 'OFF-LINE'
      ELSE 'ON-LINE'
    END
  ))
  -- Или возвращаем значание
	ELSE `radius_attributes`.`Value`
END as `Value`
-- Конец обработки макросов
 FROM `users`
 -- ...для получения IP/MAC
      JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
 -- ...для получения информации о сети
      JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
      JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
 -- ...для сбора всех атрибутов пользователя, сети, сервера доступа, группы
      JOIN `radius_attributes` ON  `radius_attributes`.`login` = `users`.`login`
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`netid` = `networks`.`id` )
                               OR (`radius_attributes`.`login` = '*' AND `radius_attributes`.`nasip` = INET_ATON(`nas`.`nasip`))
 -- ...
 LEFT JOIN `radius_username_bind` ON `radius_username_bind`.`netid` = `nethosts`.`netid`
 -- ...для получения информации о коммутаторе, к которому подключен пользователь
 LEFT JOIN `switchportassign` ON `switchportassign`.`login` = `users`.`login`
 LEFT JOIN `switches` ON `switches`.`id` = `switchportassign`.`switchid`
 -- ...для получения информации о скорости по тарифному плану
 LEFT JOIN `speeds`   ON `speeds`.`tariff` = `users`.`Tariff`
WHERE `radius_attributes`.`scenario` = 'reply' AND `networks`.`use_radius` = TRUE
ORDER BY `users`.`login`;

CREATE OR REPLACE VIEW `radius_usergroup` (`UserName`, `GroupName`, `priority`) AS 
SELECT `users`.`login`, CONCAT(`networks`.`id`, ':', INET_ATON(`nas`.`nasip`)), '1'
 FROM `users`
 JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
 JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
 JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
WHERE `networks`.`use_radius` = TRUE;

CREATE OR REPLACE VIEW `radius_groupcheck` (`GroupName`, `Attribute`, `op`, `Value`) AS 
SELECT DISTINCT CONCAT(`networks`.`id`, ':', INET_ATON(`nas`.`nasip`)), `radius_attributes`.`Attribute`, `radius_attributes`.`op`, `radius_attributes`.`Value` FROM `users`
 JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
 JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
 JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
 JOIN `radius_attributes` ON `radius_attributes`.`netid` = `networks`.`id`
                          OR `radius_attributes`.`nasip` = INET_ATON(`nas`.`nasip`)
WHERE `radius_attributes`.`scenario` = 'check'
  AND `radius_attributes`.`login` IS NULL
  AND `networks`.`use_radius` = TRUE;

CREATE OR REPLACE VIEW `radius_groupreply` (`GroupName`, `Attribute`, `op`, `Value`) AS 
SELECT DISTINCT CONCAT(`networks`.`id`, ':', INET_ATON(`nas`.`nasip`)), `radius_attributes`.`Attribute`, `radius_attributes`.`op`, `radius_attributes`.`Value` FROM `users`
 JOIN `nethosts` ON `nethosts`.`ip` = `users`.`IP`
 JOIN `networks` ON `networks`.`id` = `nethosts`.`netid`
 JOIN `nas`      ON `nas`.`netid`   = `nethosts`.`netid`
 JOIN `radius_attributes` ON `radius_attributes`.`netid` = `networks`.`id`
                          OR `radius_attributes`.`nasip` = INET_ATON(`nas`.`nasip`)
WHERE `radius_attributes`.`scenario` = 'reply'
  AND `radius_attributes`.`login` IS NULL
  AND `networks`.`use_radius` = TRUE;
