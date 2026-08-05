CREATE TABLE IF NOT EXISTS `churnreasons` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` datetime NOT NULL,
  `admin` VARCHAR(64) DEFAULT NULL,
  `scope` VARCHAR(64) DEFAULT NULL,
  `itemid` VARCHAR(128) NOT NULL,
  `action` VARCHAR(32) DEFAULT NULL,
  `state` VARCHAR(255) DEFAULT NULL,
   PRIMARY KEY (`id`),
   KEY `scope` (`scope`),
   KEY `itemid` (`itemid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;