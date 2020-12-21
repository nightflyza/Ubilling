CREATE TABLE IF NOT EXISTS `garage_cars` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `vendor` VARCHAR(40) NOT NULL,
  `model` VARCHAR(40) NOT NULL,
  `number` VARCHAR(20) DEFAULT NULL,
  `vin` VARCHAR(40) DEFAULT NULL,
  `year` INT(11) DEFAULT NULL,
  `power` INT(11) DEFAULT NULL,
  `engine` INT(11) DEFAULT NULL,
  `fuelconsumption` DOUBLE DEFAULT NULL,
  `fueltype` VARCHAR(16) DEFAULT NULL,
  `gastank` INT(11) DEFAULT NULL,
  `weight` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `garage_drivers` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `employeeid` INT(11) NOT NULL,
  `carid` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `garage_mileage` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `date` DATETIME NOT NULL,
  `carid` INT(11) NOT NULL,
  `mileage` INT(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

CREATE TABLE IF NOT EXISTS `garage_mapon` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `carid` INT(11) NOT NULL,
  `unitid` INT(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8 AUTO_INCREMENT=1;

ALTER TABLE `banksta2_presets` ADD `col_srvidents` varchar(20) DEFAULT '' AFTER `col_contract`;
ALTER TABLE `banksta2_presets` ADD `srvidents_preffered` tinyint(3) DEFAULT 0 AFTER `guess_contract`;

CREATE TABLE IF NOT EXISTS `user_dataexport_allowed` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `login` varchar(100) NOT NULL,
  `export_allowed` tinyint(3) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY (`login`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;

CREATE TABLE IF NOT EXISTS `contrahens_extinfo` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `agentid` int(11) NOT NULL,
  `service_type` varchar(50) NOT NULL DEFAULT '',
  `internal_paysys_name` varchar(50)  NOT NULL DEFAULT '',
  `internal_paysys_id` varchar(50)  NOT NULL DEFAULT '',
  `internal_paysys_srv_id` varchar(50)  NOT NULL DEFAULT '',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;