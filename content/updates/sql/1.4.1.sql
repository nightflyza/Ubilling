CREATE TABLE IF NOT EXISTS `crm_leads` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `address` varchar(255) NOT NULL,
  `realname` varchar(255) NOT NULL,
  `phone` varchar(32) DEFAULT NULL,
  `mobile` varchar(32) NOT NULL,
  `extmobile` varchar(32) DEFAULT NULL,
  `email` varchar(64) DEFAULT NULL,
  `branch` int(11) DEFAULT NULL,
  `tariff` varchar(64) DEFAULT NULL,
  `login` varchar(64) DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `crm_activities` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `leadid` INT(11) NOT NULL,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `employeeid` int(11) DEFAULT NULL,
  `state` tinyint(1) DEFAULT 0,
  `notes` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `leadid` (`leadid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `crm_stateslog` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` varchar(64) DEFAULT NULL,
  `scope` varchar(64) DEFAULT NULL,
  `itemid` varchar(128) NOT NULL,
  `action` varchar(32) DEFAULT NULL,
  `state` varchar(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `scope` (`scope`),
   KEY `itemid` (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `stealthtariffs` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `tariff` varchar(64) DEFAULT NULL,
   PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `contrahens_extinfo` ADD `paysys_token` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `contrahens_extinfo` ADD `paysys_secret_key` VARCHAR(255) NOT NULL DEFAULT '';
ALTER TABLE `contrahens_extinfo` ADD `paysys_password` VARCHAR(255) NOT NULL DEFAULT '';