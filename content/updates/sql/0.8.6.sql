ALTER TABLE `salary_jobs` ADD INDEX(`taskid`);

ALTER TABLE `wh_out` ADD INDEX(`desttype`);

ALTER TABLE `wh_out` ADD INDEX(`destparam`);

CREATE TABLE IF NOT EXISTS `polls` (
  `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` varchar(255) NOT NULL DEFAULT '',
  `enabled` tinyint(1) NOT NULL DEFAULT '0',
  `start_date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `end_date` datetime DEFAULT '0000-00-00 00:00:00',
  `params` text NOT NULL,
  `admin` varchar(255) NOT NULL DEFAULT '',
  `voting` VARCHAR(255) NOT NULL DEFAULT 'Users',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `polls_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `poll_id` int(11) NOT NULL DEFAULT '0',
  `text` text NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `poll_id` (`id`,`poll_id`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `polls_votes` (
  `id` bigint(20) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `option_id` int(11) NOT NULL DEFAULT '0',
  `poll_id` int(11) NOT NULL DEFAULT '0',
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `login_poll` (`poll_id`,`login`) USING BTREE,
  UNIQUE KEY `login_poll_option` (`option_id`,`poll_id`,`login`) USING BTREE
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `zbsannhist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `annid` int(11) NOT NULL,
  `login` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `annid` (`annid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `vservices` ADD `fee_charge_always` TINYINT(1) NOT NULL DEFAULT 1;

CREATE TABLE IF NOT EXISTS `zte_cards` (
`id` INT NOT NULL AUTO_INCREMENT, 
`swid` INT NOT NULL, 
`slot_number` INT NOT NULL, 
`card_name` VARCHAR(5) NOT NULL, 
PRIMARY KEY (`id`), 
KEY (`swid`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;
 
CREATE TABLE IF NOT EXISTS `zte_vlan_bind` (
`id` INT NOT NULL AUTO_INCREMENT,
`swid` INT NOT NULL,
`slot_number` INT NOT NULL,
`port_number` INT(2) NOT NULL,
`vlan` INT(4) NOT NULL,
PRIMARY KEY (`id`),
KEY (`swid`) )
ENGINE = MyISAM DEFAULT CHARSET=UTF8;
 
ALTER TABLE `zte_cards` ADD COLUMN `chasis_number` INT (1) NOT NULL;