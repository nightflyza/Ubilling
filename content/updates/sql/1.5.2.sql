CREATE TABLE IF NOT EXISTS `pbxcalls` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `filename` VARCHAR(250) DEFAULT NULL,
  `login` VARCHAR(64) DEFAULT NULL,
  `size` INT(11) DEFAULT NULL,
  `direction` VARCHAR(4) DEFAULT NULL,
  `storage` VARCHAR(4) DEFAULT NULL,
  `date` DATETIME DEFAULT NULL,
  `number` VARCHAR(32) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login` (`login`),
  KEY `idx_date` (`date`),
  KEY `idx_number` (`number`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;