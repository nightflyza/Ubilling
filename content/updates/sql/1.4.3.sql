ALTER TABLE `envydevices` ADD `port` INT NULL DEFAULT NULL AFTER `cutend`;

CREATE TABLE IF NOT EXISTS `ophtraff` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(50) NOT NULL,
  `month` tinyint(4) NOT NULL,
  `year` SMALLINT(6) NOT NULL,
  `U0` BIGINT(20) DEFAULT NULL,
  `D0` BIGINT(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 ;
