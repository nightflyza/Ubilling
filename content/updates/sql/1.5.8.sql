CREATE TABLE IF NOT EXISTS `ct_auth` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `chatid` VARCHAR(40) NOT NULL,
  `login` VARCHAR(64) NOT NULL,
  `password` VARCHAR(64) NOT NULL,
  `date` DATETIME DEFAULT NULL,
  `active` TINYINT(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_chatid` (`chatid`),
  KEY `idx_login` (`login`),
  KEY `idx_active` (`active`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;