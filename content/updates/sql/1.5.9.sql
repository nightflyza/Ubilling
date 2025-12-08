CREATE TABLE IF NOT EXISTS `gen_devices` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `running` TINYINT(1) NOT NULL DEFAULT 0,
  `model` VARCHAR(255) NOT NULL,
  `fuel` VARCHAR(64) NOT NULL,
  `tankvolume` INT(11) NOT NULL,
  `consumption` FLOAT NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `geo` VARCHAR(64) NOT NULL,
  `motohours` FLOAT NOT NULL DEFAULT 0,
  `serviceinterval` INT NOT NULL DEFAULT 0,
  `intank` FLOAT NOT NULL DEFAULT 0,
  `opalias` varchar(64) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `running` (`running`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;


CREATE TABLE IF NOT EXISTS `gen_services` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `genid` INT(11) NOT NULL,
  `date` DATETIME NOT NULL,
  `motohours` FLOAT NOT NULL,
  `notes` TEXT,
  PRIMARY KEY (`id`),
  KEY `genid` (`genid`)  
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `gen_events` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `genid` INT(11) NOT NULL,
  `event` varchar(16) NOT NULL,
  `date` DATETIME NOT NULL,
  PRIMARY KEY (`id`),
  KEY `genid` (`genid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `gen_refuels` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `genid` INT(11) NOT NULL,
  `date` DATETIME NOT NULL,
  `liters` FLOAT NOT NULL,
  `price` FLOAT NOT NULL,
  PRIMARY KEY (`id`),
  KEY `genid` (`genid`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;
