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

ALTER TABLE `pononu` ADD `geo` VARCHAR(64) NULL DEFAULT NULL AFTER `login`;

CREATE TABLE IF NOT EXISTS `ub_im_pinned` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `login` VARCHAR(64) NOT NULL,
  `pinned` VARCHAR(64) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_login` (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `weblogs` ADD FULLTEXT INDEX `ft_event` (`event`);