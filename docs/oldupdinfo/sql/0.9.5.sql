ALTER TABLE `employee` ADD `amountLimit` VARCHAR(45) NOT NULL DEFAULT '0';

CREATE TABLE IF NOT EXISTS `callshist` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `number` varchar(120) NOT NULL,
  `login` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

CREATE TABLE IF NOT EXISTS `stickyrevelations` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner` varchar(255) NOT NULL,
  `showto` text,
  `createdate` datetime NOT NULL,
  `dayfrom` int(11) DEFAULT NULL,
  `dayto` int(11) DEFAULT NULL,
  `active` tinyint(1) NOT NULL DEFAULT '1',
  `text` text,
  PRIMARY KEY (`id`),
  KEY `owner` (`owner`),
  KEY `dayfrom` (`dayfrom`),
  KEY `dayto` (`dayto`),
  KEY `active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;

ALTER TABLE `trinitytv_tariffs` ADD `description` VARCHAR(128) NULL DEFAULT NULL AFTER `name`;

ALTER TABLE `wh_reshist` ADD `resid` INT NULL AFTER `id`; 

CREATE TABLE IF NOT EXISTS `mlg_ishimura` (
  `login` varchar(50) DEFAULT NULL,
  `month` tinyint(4) DEFAULT NULL,
  `year` smallint(6) DEFAULT NULL,
  `U0` bigint(20) DEFAULT NULL,
  `D0` bigint(20) DEFAULT NULL,
  `cash` double DEFAULT NULL,
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;