CREATE TABLE IF NOT EXISTS `filestorage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `scope` VARCHAR(255) NOT NULL,
  `item` VARCHAR(255) NOT NULL,
  `date` datetime NOT NULL,
  `admin` VARCHAR(40) NOT NULL,
  `filename` VARCHAR(255) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `scope` (`scope`),
  KEY `item` (`item`),
  KEY `date` (`date`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1 ;